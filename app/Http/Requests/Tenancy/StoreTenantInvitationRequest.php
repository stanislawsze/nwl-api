<?php

namespace App\Http\Requests\Tenancy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantInvitationRequest extends FormRequest
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
            'email' => ['required', 'email'],
            'role' => ['required', 'string', Rule::in(array_keys(config('tenancy.membership_roles', [])))],
            'expires_in_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
        ];
    }
}
