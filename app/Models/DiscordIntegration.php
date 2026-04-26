<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'tenant_id',
    'owner_user_id',
    'guild_id',
    'guild_name',
    'bot_enabled',
    'is_active',
    'oauth_client_id',
    'oauth_client_secret',
    'oauth_redirect_uri',
    'bot_token',
    'settings',
])]
class DiscordIntegration extends Model
{
    protected function casts(): array
    {
        return [
            'bot_enabled' => 'boolean',
            'is_active' => 'boolean',
            'oauth_client_secret' => 'encrypted',
            'bot_token' => 'encrypted',
            'settings' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * @return HasMany<DiscordRoleMapping, $this>
     */
    public function roleMappings(): HasMany
    {
        return $this->hasMany(DiscordRoleMapping::class);
    }
}
