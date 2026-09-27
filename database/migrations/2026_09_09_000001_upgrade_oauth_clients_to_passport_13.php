<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Upgrade the legacy Passport client table to Passport 13's default schema.
     */
    public function up(): void
    {
        Schema::table('oauth_clients', function (Blueprint $table): void {
            $table->nullableMorphs('owner');
            $table->text('redirect_uris')->nullable();
            $table->text('grant_types')->nullable();
        });

        DB::table('oauth_clients')->orderBy('id')->each(function (object $client): void {
            $redirectUris = $client->redirect === ''
                ? []
                : explode(',', $client->redirect);

            $grantTypes = array_keys(array_filter([
                'authorization_code' => $redirectUris !== [],
                'client_credentials' => $client->secret !== null && $client->user_id === null,
                'implicit' => $redirectUris !== [],
                'password' => (bool) $client->password_client,
                'personal_access' => (bool) $client->personal_access_client && $client->secret !== null,
                'refresh_token' => true,
                'urn:ietf:params:oauth:grant-type:device_code' => true,
            ]));

            DB::table('oauth_clients')->where('id', $client->id)->update([
                'owner_type' => $client->user_id === null ? null : User::class,
                'owner_id' => $client->user_id,
                'redirect_uris' => json_encode($redirectUris, JSON_THROW_ON_ERROR),
                'grant_types' => json_encode($grantTypes, JSON_THROW_ON_ERROR),
                'secret' => $client->secret !== null && ! Hash::isHashed($client->secret)
                    ? Hash::make($client->secret)
                    : $client->secret,
            ]);
        });

        Schema::table('oauth_clients', function (Blueprint $table): void {
            $table->uuid('id')->change();
            $table->text('redirect_uris')->nullable(false)->change();
            $table->text('grant_types')->nullable(false)->change();
            $table->dropIndex(['user_id']);
            $table->dropColumn(['user_id', 'redirect', 'personal_access_client', 'password_client']);
        });

        Schema::table('oauth_auth_codes', function (Blueprint $table): void {
            $table->uuid('client_id')->change();
        });

        Schema::table('oauth_access_tokens', function (Blueprint $table): void {
            $table->uuid('client_id')->change();
        });
    }

    /**
     * Restore the legacy schema only while every client identifier is numeric.
     */
    public function down(): void
    {
        $hasUuidClients = DB::table('oauth_clients')
            ->pluck('id')
            ->contains(fn (string|int $id): bool => ! ctype_digit((string) $id));

        if ($hasUuidClients) {
            throw new LogicException(
                'Passport 13 clients with UUID identifiers prevent a lossless schema rollback. Restore the pre-upgrade database backup instead.'
            );
        }

        Schema::table('oauth_clients', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->text('redirect')->nullable();
            $table->boolean('personal_access_client')->default(false);
            $table->boolean('password_client')->default(false);
        });

        DB::table('oauth_clients')->orderBy('id')->each(function (object $client): void {
            $redirectUris = json_decode($client->redirect_uris, true, 512, JSON_THROW_ON_ERROR);
            $grantTypes = json_decode($client->grant_types, true, 512, JSON_THROW_ON_ERROR);

            DB::table('oauth_clients')->where('id', $client->id)->update([
                'user_id' => $client->owner_type === User::class ? $client->owner_id : null,
                'redirect' => implode(',', $redirectUris),
                'personal_access_client' => in_array('personal_access', $grantTypes, true),
                'password_client' => in_array('password', $grantTypes, true),
            ]);
        });

        Schema::table('oauth_auth_codes', function (Blueprint $table): void {
            $table->unsignedBigInteger('client_id')->change();
        });

        Schema::table('oauth_access_tokens', function (Blueprint $table): void {
            $table->unsignedBigInteger('client_id')->change();
        });

        Schema::table('oauth_clients', function (Blueprint $table): void {
            $table->unsignedBigInteger('id', true)->change();
            $table->dropMorphs('owner');
            $table->dropColumn(['redirect_uris', 'grant_types']);
        });
    }
};
