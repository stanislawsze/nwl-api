<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discord_role_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discord_integration_id')->constrained()->cascadeOnDelete();
            $table->string('discord_role_id');
            $table->string('discord_role_name');
            $table->foreignId('local_role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['discord_integration_id', 'discord_role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discord_role_mappings');
    }
};
