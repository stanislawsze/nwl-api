<?php

namespace App\Http\Resources\Discord;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscordIntegrationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'guild_id' => $this->resource->guild_id,
            'guild_name' => $this->resource->guild_name,
            'bot_enabled' => $this->resource->bot_enabled,
            'is_active' => $this->resource->is_active,
            'oauth' => [
                'client_id' => $this->resource->oauth_client_id,
                'redirect_uri' => $this->resource->oauth_redirect_uri,
                'has_client_secret' => $this->resource->oauth_client_secret !== null,
                'is_configured' => $this->resource->oauth_client_id !== null
                    && $this->resource->oauth_client_secret !== null
                    && $this->resource->oauth_redirect_uri !== null,
            ],
            'bot' => [
                'has_token' => $this->resource->bot_token !== null,
                'is_configured' => $this->resource->bot_enabled && $this->resource->bot_token !== null,
            ],
            'status' => [
                'is_ready_for_oauth' => $this->resource->oauth_client_id !== null
                    && $this->resource->oauth_client_secret !== null
                    && $this->resource->oauth_redirect_uri !== null,
                'is_ready_for_bot_sync' => $this->resource->bot_enabled && $this->resource->bot_token !== null,
                'has_role_mappings' => $this->resource->relationLoaded('roleMappings')
                    ? $this->resource->roleMappings->isNotEmpty()
                    : $this->resource->roleMappings()->exists(),
            ],
            'settings' => $this->resource->settings ?? [],
            'role_mappings' => DiscordRoleMappingResource::collection($this->whenLoaded('roleMappings')),
            'created_at' => $this->resource->created_at?->toISOString(),
            'updated_at' => $this->resource->updated_at?->toISOString(),
        ];
    }
}
