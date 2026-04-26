<?php

namespace App\Http\Requests\Discord;

use Illuminate\Foundation\Http\FormRequest;

class UpsertDiscordIntegrationRequest extends FormRequest
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
            'guild_id' => ['required', 'string', 'max:255'],
            'guild_name' => ['required', 'string', 'max:255'],
            'bot_enabled' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'oauth_client_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'oauth_client_secret' => ['sometimes', 'nullable', 'string'],
            'oauth_redirect_uri' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'bot_token' => ['sometimes', 'nullable', 'string'],
            'settings' => ['sometimes', 'array'],
        ];
    }
}
