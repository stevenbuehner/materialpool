<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const PERMISSION = 'materials.view-public';
    private const GUARD = 'web';
    private const DEFAULT_GROUP = 'Standardnutzer';

    public function up(): void {
        Schema::table('materials', function (Blueprint $table): void {
            $table->boolean('is_public')->default(true)->after('from_bot');
        });

        $now = now();
        DB::table('permissions')->insertOrIgnore([
            'name' => self::PERMISSION,
            'guard_name' => self::GUARD,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $permissionId = DB::table('permissions')
            ->where('name', self::PERMISSION)
            ->where('guard_name', self::GUARD)
            ->value('id');
        $roleId = DB::table('roles')
            ->where('name', self::DEFAULT_GROUP)
            ->where('guard_name', self::GUARD)
            ->value('id');

        if ($permissionId && $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }

    public function down(): void {
        $permissionId = DB::table('permissions')
            ->where('name', self::PERMISSION)
            ->where('guard_name', self::GUARD)
            ->value('id');

        if ($permissionId) {
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        Schema::table('materials', function (Blueprint $table): void {
            $table->dropColumn('is_public');
        });
    }
};
