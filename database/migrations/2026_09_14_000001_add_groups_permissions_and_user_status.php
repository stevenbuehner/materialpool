<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void {
		$permissions = [
			'materials.create', 'materials.view-all', 'materials.update-own', 'materials.update-all',
			'materials.update-metadata-own', 'materials.update-metadata-all', 'materials.delete-own', 'materials.delete-all',
			'resources.create', 'resources.view-all', 'resources.update-own', 'resources.update-all',
			'resources.delete-own', 'resources.delete-all', 'keywords.manage', 'bundles.manage', 'system.shutdown',
		];
		$defaultPermissions = [
			'materials.create', 'materials.update-own', 'materials.update-metadata-own', 'materials.delete-own',
			'resources.create', 'resources.update-own', 'resources.delete-own',
		];

		Schema::create('permissions', function (Blueprint $table): void {
			$table->id();
			$table->string('name');
			$table->string('guard_name');
			$table->timestamps();
			$table->unique(['name', 'guard_name']);
		});

		Schema::create('roles', function (Blueprint $table): void {
			$table->id();
			$table->string('name');
			$table->string('guard_name');
			$table->timestamps();
			$table->unique(['name', 'guard_name']);
		});

		Schema::create('model_has_permissions', function (Blueprint $table): void {
			$table->unsignedBigInteger('permission_id');
			$table->string('model_type');
			$table->unsignedBigInteger('model_id');
			$table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
			$table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
			$table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
		});

		Schema::create('model_has_roles', function (Blueprint $table): void {
			$table->unsignedBigInteger('role_id');
			$table->string('model_type');
			$table->unsignedBigInteger('model_id');
			$table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
			$table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
			$table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
		});

		Schema::create('role_has_permissions', function (Blueprint $table): void {
			$table->unsignedBigInteger('permission_id');
			$table->unsignedBigInteger('role_id');
			$table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
			$table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
			$table->primary(['permission_id', 'role_id'], 'role_has_permissions_permission_id_role_id_primary');
		});

		Schema::table('users', function (Blueprint $table): void {
			$table->string('status', 20)->default('active')->index()->after('is_admin');
		});

		$now = now();
		$permissionIds = [];
		foreach ($permissions as $permission) {
			$permissionIds[$permission] = DB::table('permissions')->insertGetId([
				'name' => $permission,
				'guard_name' => 'web',
				'created_at' => $now,
				'updated_at' => $now,
			]);
		}

		$roleId = DB::table('roles')->insertGetId([
			'name' => 'Standardnutzer',
			'guard_name' => 'web',
			'created_at' => $now,
			'updated_at' => $now,
		]);

		DB::table('role_has_permissions')->insert(array_map(
			fn(string $permission): array => ['permission_id' => $permissionIds[$permission], 'role_id' => $roleId],
			$defaultPermissions
		));

		DB::table('users')->where('is_admin', false)->orderBy('id')->chunkById(500, function ($users) use ($roleId): void {
			DB::table('model_has_roles')->insert($users->map(fn($user): array => [
				'role_id' => $roleId,
				'model_type' => 'App\\Models\\User',
				'model_id' => $user->id,
			])->all());
		});
	}

	public function down(): void {
		Schema::dropIfExists('role_has_permissions');
		Schema::dropIfExists('model_has_roles');
		Schema::dropIfExists('model_has_permissions');
		Schema::dropIfExists('roles');
		Schema::dropIfExists('permissions');

		Schema::table('users', function (Blueprint $table): void {
			$table->dropIndex(['status']);
			$table->dropColumn('status');
		});
	}
};
