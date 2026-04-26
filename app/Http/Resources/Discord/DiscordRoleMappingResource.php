<?php

namespace App\Http\Resources\Discord;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscordRoleMappingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'discord_role_id' => $this->resource->discord_role_id,
            'discord_role_name' => $this->resource->discord_role_name,
            'local_role_id' => $this->resource->local_role_id,
            'local_role_name' => $this->resource->localRole?->name,
        ];
    }
}
