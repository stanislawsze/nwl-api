<?php

namespace App\Http\Resources\Tenancy;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantAuditLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $properties = $this->resource->properties;

        if ($properties instanceof Arrayable) {
            $properties = $properties->toArray();
        }

        $causer = $this->resource->causer;

        return [
            'id' => $this->resource->id,
            'event' => $this->resource->event,
            'description' => $this->resource->description,
            'causer' => $causer === null ? null : [
                'id' => $causer->getKey(),
                'name' => $causer->getAttribute('name'),
                'email' => $causer->getAttribute('email'),
            ],
            'properties' => is_array($properties) ? $properties : [],
            'created_at' => $this->resource->created_at?->toISOString(),
        ];
    }
}
