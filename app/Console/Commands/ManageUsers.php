<?php

namespace App\Console\Commands;

use App\Enums\UserStatus;
use App\Models\User;
use App\Support\Authorization\SystemPermissions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;
use Spatie\Permission\Models\Role;

class ManageUsers extends Command {
	protected $signature = 'users:manage
        {action : create oder update}
        {--first-admin : Nur den ersten Global-Admin einer leeren Installation anlegen}
        {--short-password : Beim ersten Global-Admin die verkürzte Mindestlänge verwenden}';

	protected $description = 'Benutzer interaktiv anlegen oder Name, E-Mail und Passwort bearbeiten';

	public function handle(): int {
		$action        = (string)$this->argument('action');
		$firstAdmin    = (bool)$this->option('first-admin');
		$shortPassword = (bool)$this->option('short-password');

		if (!in_array($action, ['create', 'update'], TRUE) || ($firstAdmin && $action !== 'create') || ($shortPassword && !$firstAdmin)) {
			$this->components->error('Aufruf: users:manage create|update [--first-admin [--short-password]]');

			return self::INVALID;
		}

		return $action === 'create' ? $this->createUser($firstAdmin, $shortPassword) : $this->updateUser();
	}

	private function createUser(bool $firstAdmin, bool $shortPassword): int {
		$hasUsers = User::query()->exists();
		if (($firstAdmin && $hasUsers) || (!$firstAdmin && !$hasUsers)) {
			$this->components->error($firstAdmin
				? 'Der erste Global-Admin kann nur in einer leeren Benutzertabelle angelegt werden.'
				: 'Der erste Benutzer muss mit --first-admin angelegt werden.');

			return self::FAILURE;
		}

		do {
			$name         = trim((string)$this->ask('Name'));
			$email        = mb_strtolower(trim((string)$this->ask('E-Mail')));
			$password     = (string)$this->secret('Passwort (mindestens '.config($shortPassword ? 'password_policy.first_admin_min_length' : 'password_policy.min_length').' Zeichen)', FALSE);
			$confirmation = (string)$this->secret('Passwort bestätigen', FALSE);

			if ($this->validInput($name, $email, $password, $confirmation, NULL, $shortPassword)) {
				break;
			}

			if (!$firstAdmin) {
				return self::INVALID;
			}

			$this->components->warn('Eingabe ungültig. Bitte Name, E-Mail und Passwort erneut eingeben.');
		} while (TRUE);

		$user = DB::transaction(function () use ($name, $email, $password, $firstAdmin): User {
			if ($firstAdmin && User::query()->exists()) {
				throw new RuntimeException('Die Benutzertabelle ist nicht mehr leer.');
			}

			$user = User::create([
				'name'     => $name,
				'email'    => $email,
				'password' => Hash::make($password),
				'is_admin' => $firstAdmin,
				'status'   => UserStatus::Active,
			]);

			if (!$firstAdmin) {
				$user->assignRole(Role::findByName(SystemPermissions::DEFAULT_GROUP));
			}

			return $user;
		});

		Log::notice('console.user.created', ['target_user_id' => $user->id, 'is_admin' => $firstAdmin]);
		$this->components->info('Benutzer angelegt.');

		return self::SUCCESS;
	}

	private function validInput(
		string $name,
		string $email,
		string $password,
		string $confirmation,
		?User  $user = NULL,
		bool   $shortPassword = FALSE
	): bool {
		$data  = ['name' => $name, 'email' => $email];
		$rules = [
			'name'  => ['required', 'string', 'max:255'],
			'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
		];

		if ($user === NULL || $password !== '') {
			$data['password']              = $password;
			$data['password_confirmation'] = $confirmation;
			$rules['password']             = ['required', 'string', 'min:'.config($shortPassword ? 'password_policy.first_admin_min_length' : 'password_policy.min_length'), 'confirmed'];
		}

		$validator = Validator::make($data, $rules);
		if ($validator->fails()) {
			foreach ($validator->errors()->all() as $error) {
				$this->components->error($error);
			}

			return FALSE;
		}

		return TRUE;
	}

	private function updateUser(): int {
		$currentEmail = mb_strtolower(trim((string)$this->ask('Aktuelle E-Mail des Benutzers')));
		$user         = User::query()->where('email', $currentEmail)->first();

		if ($user === NULL) {
			$this->components->error('Benutzer nicht gefunden.');

			return self::FAILURE;
		}

		$name         = trim((string)$this->ask('Name', $user->name));
		$email        = mb_strtolower(trim((string)$this->ask('E-Mail', $user->email)));
		$password     = (string)$this->secret('Neues Passwort (leer lassen für unverändert)', FALSE);
		$confirmation = $password === '' ? '' : (string)$this->secret('Neues Passwort bestätigen', FALSE);

		if (!$this->validInput($name, $email, $password, $confirmation, $user)) {
			return self::INVALID;
		}

		$user->name  = $name;
		$user->email = $email;
		if ($password !== '') {
			$user->password = Hash::make($password);
			$user->setRememberToken(Str::random(60));
		}
		$user->save();

		Log::notice('console.user.updated', ['target_user_id' => $user->id, 'password_changed' => $password !== '']);
		$this->components->info('Benutzer aktualisiert.');

		return self::SUCCESS;
	}
}
