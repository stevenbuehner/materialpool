<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\UserInvitation;
use App\Support\Authorization\SystemPermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use Throwable;

class AdminUserController extends Controller {
	public function index(Request $request): array {
		$validated = $request->validate([
			'search' => ['nullable', 'string', 'max:255'],
			'status' => ['nullable', Rule::enum(UserStatus::class)],
			'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
		]);

		$query = User::query()->with('roles:id,name')->orderBy('name')->orderBy('id');
		if (!empty($validated['search'])) {
			$search = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $validated['search']) . '%';
			$query->where(fn($q) => $q->where('name', 'like', $search)->orWhere('email', 'like', $search));
		}
		if (!empty($validated['status'])) {
			$query->where('status', $validated['status']);
		}

		$page = $query->paginate($validated['per_page'] ?? 20);
		$page->getCollection()->transform(fn(User $user): array => $this->serialize($user));

		return $page->toArray();
	}

	public function store(Request $request): JsonResponse {
		$validated = $this->validateUser($request);
		$actor = $request->user();
		$invitationError = null;

		$user = DB::transaction(function () use ($validated): User {
			$user = User::create([
				'name' => $validated['name'],
				'email' => mb_strtolower($validated['email']),
				'password' => Hash::make(Str::random(80)),
				'is_admin' => $validated['is_admin'] ?? false,
				'status' => UserStatus::Invited,
			]);

			$groupIds = array_key_exists('group_ids', $validated)
				? $validated['group_ids']
				: Role::query()->where('name', SystemPermissions::DEFAULT_GROUP)->pluck('id')->all();
			$user->syncRoles(Role::query()->whereIn('id', $groupIds)->get());

			return $user;
		});

		try {
			$this->sendInvitation($user);
		} catch (Throwable $exception) {
			$invitationError = __('admin.invitation_failed');
			Log::error('admin.user.invitation_failed', [
				'actor_id' => $actor->id,
				'target_user_id' => $user->id,
				'exception' => $exception::class,
			]);
		}

		Log::notice('admin.user.created', ['actor_id' => $actor->id, 'target_user_id' => $user->id]);

		return response()->json([
			'user' => $this->serialize($user->fresh('roles:id,name')),
			'invitation_sent' => $invitationError === null,
			'invitation_error' => $invitationError,
		], 201);
	}

	public function update(User $user, Request $request): array {
		$validated = $this->validateUser($request, $user, true);
		$actor = $request->user();
		$nextStatus = isset($validated['status']) ? UserStatus::from($validated['status']) : $user->status;
		$nextIsAdmin = $validated['is_admin'] ?? $user->is_admin;

		if ($actor->is($user) && $nextStatus !== UserStatus::Active) {
			abort(422, 'Das eigene Benutzerkonto kann nicht deaktiviert werden.');
		}
		if ($user->status === UserStatus::Invited && $nextStatus === UserStatus::Active) {
			abort(422, 'Ein eingeladener Benutzer wird ausschließlich durch den Abschluss der Einladung aktiviert.');
		}
		if ($user->status !== UserStatus::Invited && $nextStatus === UserStatus::Invited) {
			abort(422, 'Ein bestehender Benutzer kann nicht erneut in den Status „eingeladen“ versetzt werden.');
		}

		if ($user->is_admin && $user->isActive() && (!$nextIsAdmin || $nextStatus !== UserStatus::Active)) {
			$this->ensureAnotherActiveAdmin($user);
		}

		$wasSuspended = $user->status === UserStatus::Suspended;
		DB::transaction(function () use ($user, $validated, $nextStatus, $nextIsAdmin): void {
			$user->fill(array_filter([
				'name' => $validated['name'] ?? null,
				'email' => isset($validated['email']) ? mb_strtolower($validated['email']) : null,
			], fn($value) => $value !== null));
			$user->is_admin = $nextIsAdmin;
			$user->status = $nextStatus;
			$user->save();

			if (array_key_exists('group_ids', $validated)) {
				$user->syncRoles(Role::query()->whereIn('id', $validated['group_ids'])->get());
			}

			if ($nextStatus === UserStatus::Suspended) {
				$this->revokeTokens($user);
			}
		});

		Log::notice('admin.user.updated', [
			'actor_id' => $actor->id,
			'target_user_id' => $user->id,
			'suspended' => !$wasSuspended && $nextStatus === UserStatus::Suspended,
		]);

		return ['user' => $this->serialize($user->fresh('roles:id,name'))];
	}

	public function invitation(User $user, Request $request): array {
		abort_unless($user->status === UserStatus::Invited, 422, 'Einladungen können nur für eingeladene Benutzer erneut gesendet werden.');
		$this->sendInvitation($user);
		Log::notice('admin.user.invitation_resent', ['actor_id' => $request->user()->id, 'target_user_id' => $user->id]);

		return ['invitation_sent' => true];
	}

	private function validateUser(Request $request, ?User $user = null, bool $partial = false): array {
		$prefix = $partial ? 'sometimes' : 'required';

		return $request->validate([
			'name' => [$prefix, 'string', 'max:255'],
			'email' => [$prefix, 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
			'is_admin' => ['sometimes', 'boolean'],
			'status' => ['sometimes', Rule::enum(UserStatus::class)],
			'group_ids' => ['sometimes', 'array'],
			'group_ids.*' => ['integer', Rule::exists('roles', 'id')->where('guard_name', 'web')],
		]);
	}

	private function sendInvitation(User $user): void {
		$token = Password::broker()->createToken($user);
		$user->notify(new UserInvitation($token));
	}

	private function ensureAnotherActiveAdmin(User $user): void {
		$exists = User::query()
			->whereKeyNot($user->getKey())
			->where('is_admin', true)
			->where('status', UserStatus::Active->value)
			->exists();
		abort_unless($exists, 422, 'Der letzte aktive Global-Admin darf nicht gesperrt oder herabgestuft werden.');
	}

	private function revokeTokens(User $user): void {
		Passport::tokenModel()::query()->where('user_id', $user->getAuthIdentifier())->get()->each(function ($token): void {
			$token->refreshToken()->update(['revoked' => true]);
			$token->revoke();
		});
		$user->setRememberToken(Str::random(60));
		$user->save();
	}

	private function serialize(User $user): array {
		return [
			'id' => $user->id,
			'name' => $user->name,
			'email' => $user->email,
			'status' => $user->status->value,
			'is_admin' => $user->is_admin,
			'groups' => $user->roles->map->only(['id', 'name'])->values()->all(),
			'created_at' => $user->created_at,
			'updated_at' => $user->updated_at,
		];
	}
}
