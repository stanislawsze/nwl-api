<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

#[Fillable([
    'discord_integration_id',
    'discord_role_id',
    'discord_role_name',
    'local_role_id',
])]
class DiscordRoleMapping extends Model
{
    /**
     * @return BelongsTo<DiscordIntegration, $this>
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(DiscordIntegration::class, 'discord_integration_id');
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function localRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'local_role_id');
    }
}
