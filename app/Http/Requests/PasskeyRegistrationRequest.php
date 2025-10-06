<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PasskeyRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'attestation' => ['required', 'array'],
        ];
    }
}
