<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	private const TABLES = [
		'material_foreign_ids' => 'material_foreign_ids_foreign_id_user_id_unique',
		'resource_foreign_ids' => 'resource_foreign_ids_foreign_id_user_id_unique',
	];

	public function up(): void {
		foreach (self::TABLES as $foreignTable => $legacyUniqueIndex) {
			Schema::table($foreignTable, function (Blueprint $table): void {
				$table->string('scope_key', 32)->nullable()->after('bundle_id');
			});

			DB::table($foreignTable)->orderBy('id')->each(function (object $foreignId) use ($foreignTable): void {
				$scopeKey = $foreignId->bundle_id !== null
					? "bundle:{$foreignId->bundle_id}"
					: "user:{$foreignId->user_id}";

				DB::table($foreignTable)->where('id', $foreignId->id)->update(['scope_key' => $scopeKey]);
			});

			Schema::table($foreignTable, function (Blueprint $table) use ($legacyUniqueIndex, $foreignTable): void {
				$table->string('scope_key', 32)->nullable(false)->change();
				$table->dropUnique($legacyUniqueIndex);
				$table->unique(['scope_key', 'foreign_id'], "{$foreignTable}_scope_key_foreign_id_unique");
			});
		}
	}

	public function down(): void {
		foreach (self::TABLES as $foreignTable => $legacyUniqueIndex) {
			Schema::table($foreignTable, function (Blueprint $table) use ($legacyUniqueIndex, $foreignTable): void {
				$table->dropUnique("{$foreignTable}_scope_key_foreign_id_unique");
				$table->unique(['foreign_id', 'user_id'], $legacyUniqueIndex);
				$table->dropColumn('scope_key');
			});
		}
	}
};
