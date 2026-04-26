<?php

namespace App\Http\Resources\Discord;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscordAccountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'discord_user_id' => $this->resource->discord_user_id,
            'username' => $this->resource->username,
            'global_name' => $this->resource->global_name,
            'email' => $this->resource->email,
            'avatar' => $this->resource->avatar,
            'token_expires_at' => $this->resource->token_expires_at?->toISOString(),
        ];
    }
}
