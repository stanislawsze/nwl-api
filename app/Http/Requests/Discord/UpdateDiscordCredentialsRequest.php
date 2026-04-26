<?php

namespace App\Http\Requests\Discord;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDiscordCredentialsRequest extends FormRequest
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
            'oauth_client_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'oauth_client_secret' => ['sometimes', 'nullable', 'string'],
            'oauth_redirect_uri' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'bot_token' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
