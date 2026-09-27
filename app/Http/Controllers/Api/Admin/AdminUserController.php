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
			'search'   => ['nullable', 'string', 'max:255'],
			'status'   => ['nullable', Rule::enum(UserStatus::class)],
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

	private function serialize(User $user): array {
		return [
			'id'         => $user->id,
			'name'       => $user->name,
			'email'      => $user->email,
			'status'     => $user->status->value,
			'is_admin'   => $user->is_admin,
			'groups'     => $user->roles->map->only(['id', 'name'])->values()->all(),
			'created_at' => $user->created_at,
			'updated_at' => $user->updated_at,
		];
	}

	public function store(Request $request): JsonResponse {
		$validated       = $this->validateUser($request);
		$actor           = $request->user();
		$invitationError = NULL;

		$user = DB::transaction(function () use ($validated): User {
			$user = User::create([
				'name'     => $validated['name'],
				'email'    => mb_strtolower($validated['email']),
				'password' => Hash::make(Str::random(80)),
				'is_admin' => $validated['is_admin'] ?? FALSE,
				'status'   => UserStatus::Invited,
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
				'actor_id'       => $actor->id,
				'target_user_id' => $user->id,
				'exception'      => $exception::class,
			]);
		}

		Log::notice('admin.user.created', ['actor_id' => $actor->id, 'target_user_id' => $user->id]);

		return response()->json([
			'user'             => $this->serialize($user->fresh('roles:id,name')),
			'invitation_sent'  => $invitationError === NULL,
			'invitation_error' => $invitationError,
		], 201);
	}

	private function validateUser(Request $request, ?User $user = NULL, bool $partial = FALSE): array {
		$prefix = $partial ? 'sometimes' : 'required';

		return $request->validate([
			'name'        => [$prefix, 'string', 'max:255'],
			'email'       => [$prefix, 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
			'is_admin'    => ['sometimes', 'boolean'],
			'status'      => ['sometimes', Rule::enum(UserStatus::class)],
			'group_ids'   => ['sometimes', 'array'],
			'group_ids.*' => ['integer', Rule::exists('roles', 'id')->where('guard_name', 'web')],
		]);
	}

	private function sendInvitation(User $user): void {
		$token = Password::broker()->createToken($user);
		$user->notify(new UserInvitation($token));
	}

	public function update(User $user, Request $request): array {
		$validated    = $this->validateUser($request, $user, TRUE);
		$actor        = $request->user();
		$wasSuspended = FALSE;
		$user         = DB::transaction(function () use ($user, $validated, &$wasSuspended): User {
			// Konsistente Sperrreihenfolge verhindert, dass parallele Änderungen den letzten aktiven Admin entfernen.
			$activeAdmins = User::query()
				->where('is_admin', TRUE)
				->where('status', UserStatus::Active->value)
				->orderBy('id')
				->lockForUpdate()
				->get();
			$user         = $activeAdmins->firstWhere('id', $user->id)
				?? User::query()->lockForUpdate()->findOrFail($user->getKey());
			$nextStatus   = isset($validated['status']) ? UserStatus::from($validated['status']) : $user->status;
			$nextIsAdmin  = $validated['is_admin'] ?? $user->is_admin;

			if ($user->status === UserStatus::Invited && $nextStatus === UserStatus::Active) {
				abort(422, 'Ein eingeladener Benutzer wird ausschließlich durch den Abschluss der Einladung aktiviert.');
			}
			if ($user->status !== UserStatus::Invited && $nextStatus === UserStatus::Invited) {
				abort(422, 'Ein bestehender Benutzer kann nicht erneut in den Status „eingeladen“ versetzt werden.');
			}
			if ($user->is_admin && $user->isActive() && (!$nextIsAdmin || $nextStatus !== UserStatus::Active)) {
				abort_unless($activeAdmins->contains(fn(User $admin): bool => !$admin->is($user)), 422, 'Der letzte aktive Global-Admin darf nicht gesperrt oder herabgestuft werden.');
			}

			$wasSuspended = $user->status === UserStatus::Suspended;
			$user->fill(array_filter([
				'name'  => $validated['name'] ?? NULL,
				'email' => isset($validated['email']) ? mb_strtolower($validated['email']) : NULL,
			], fn($value) => $value !== NULL));
			$user->is_admin = $nextIsAdmin;
			$user->status   = $nextStatus;
			$user->save();

			if (array_key_exists('group_ids', $validated)) {
				$user->syncRoles(Role::query()->whereIn('id', $validated['group_ids'])->get());
			}

			if ($nextStatus === UserStatus::Suspended) {
				$this->revokeTokens($user);
			}

			return $user;
		});

		Log::notice('admin.user.updated', [
			'actor_id'       => $actor->id,
			'target_user_id' => $user->id,
			'suspended'      => !$wasSuspended && $user->status === UserStatus::Suspended,
		]);

		return ['user' => $this->serialize($user->fresh('roles:id,name'))];
	}

	private function revokeTokens(User $user): void {
		Passport::tokenModel()::query()->where('user_id', $user->getAuthIdentifier())->get()->each(function ($token): void {
			$token->refreshToken()->update(['revoked' => TRUE]);
			$token->revoke();
		});
		$user->setRememberToken(Str::random(60));
		$user->save();
	}

	public function invitation(User $user, Request $request): array {
		abort_unless($user->status === UserStatus::Invited, 422, 'Einladungen können nur für eingeladene Benutzer erneut gesendet werden.');
		$this->sendInvitation($user);
		Log::notice('admin.user.invitation_resent', ['actor_id' => $request->user()->id, 'target_user_id' => $user->id]);

		return ['invitation_sent' => TRUE];
	}
}
