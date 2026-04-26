<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discord_integrations', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        if (Schema::hasTable('tenants')) {
            $ownerTenantMap = DB::table('tenants')
                ->select(['id', 'owner_user_id'])
                ->get()
                ->pluck('id', 'owner_user_id');

            DB::table('discord_integrations')
                ->select(['id', 'owner_user_id'])
                ->whereNull('tenant_id')
                ->get()
                ->each(function (object $integration) use ($ownerTenantMap): void {
                    $tenantId = $ownerTenantMap->get($integration->owner_user_id);

                    if ($tenantId === null) {
                        return;
                    }

                    DB::table('discord_integrations')
                        ->where('id', $integration->id)
                        ->update([
                            'tenant_id' => $tenantId,
                        ]);
                });
        }

        Schema::table('discord_integrations', function (Blueprint $table) {
            $table->dropUnique(['guild_id']);
            $table->dropUnique(['owner_user_id']);
            $table->unique(['tenant_id', 'guild_id']);
        });
    }

    public function down(): void
    {
        Schema::table('discord_integrations', function (Blueprint $table) {
            $table->dropUnique('discord_integrations_tenant_id_guild_id_unique');
            $table->dropConstrainedForeignId('tenant_id');
            $table->unique('guild_id');
            $table->unique('owner_user_id');
        });
    }
};
