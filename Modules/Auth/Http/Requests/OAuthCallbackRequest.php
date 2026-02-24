<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OAuthCallbackRequest extends FormRequest
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
            'code' => ['required_without:error', 'string'],
            'state' => ['nullable', 'string'],
            'error' => ['nullable', 'string'],
            'error_description' => ['nullable', 'string'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'provider.in' => 'Supported OAuth providers are: google, github.',
            'code.required_without' => 'Authorization code is required when no error is present.',
        ];
    }
}
