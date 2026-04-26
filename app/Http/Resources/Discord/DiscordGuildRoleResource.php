<?php

namespace App\Http\Resources\Discord;

use App\Domain\Discord\DTOs\DiscordGuildRoleDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property DiscordGuildRoleDTO $resource
 */
class DiscordGuildRoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'position' => $this->resource->position,
            'color' => $this->resource->color,
            'managed' => $this->resource->managed,
            'mentionable' => $this->resource->mentionable,
        ];
    }
}
