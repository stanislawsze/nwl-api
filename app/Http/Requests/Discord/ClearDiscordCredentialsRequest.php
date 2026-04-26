<?php

namespace App\Http\Requests\Discord;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClearDiscordCredentialsRequest extends FormRequest
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
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => [
                'string',
                Rule::in([
                    'oauth_client_id',
                    'oauth_client_secret',
                    'oauth_redirect_uri',
                    'bot_token',
                ]),
            ],
        ];
    }
}
