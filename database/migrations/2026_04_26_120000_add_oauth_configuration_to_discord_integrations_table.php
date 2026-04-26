<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discord_integrations', function (Blueprint $table) {
            $table->string('oauth_client_id')->nullable()->after('is_active');
            $table->text('oauth_client_secret')->nullable()->after('oauth_client_id');
            $table->string('oauth_redirect_uri')->nullable()->after('oauth_client_secret');
            $table->text('bot_token')->nullable()->after('oauth_redirect_uri');
        });
    }

    public function down(): void
    {
        Schema::table('discord_integrations', function (Blueprint $table) {
            $table->dropColumn([
                'oauth_client_id',
                'oauth_client_secret',
                'oauth_redirect_uri',
                'bot_token',
            ]);
        });
    }
};
