<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OAuthLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', 'in:google,github'],
            'token' => ['required', 'string'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'provider.in' => 'Supported OAuth providers are: google, github.',
            'token.required' => 'OAuth access token is required.',
        ];
    }
}
