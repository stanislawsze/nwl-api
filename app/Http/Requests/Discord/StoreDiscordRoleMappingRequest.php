<?php

namespace App\Http\Requests\Discord;

use Illuminate\Foundation\Http\FormRequest;

class StoreDiscordRoleMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'discord_integration_id' => ['required', 'integer', 'exists:discord_integrations,id'],
            'discord_role_id' => ['required', 'string', 'max:255'],
            'discord_role_name' => ['required', 'string', 'max:255'],
            'local_role_id' => ['required', 'integer', 'exists:roles,id'],
        ];
    }
}
