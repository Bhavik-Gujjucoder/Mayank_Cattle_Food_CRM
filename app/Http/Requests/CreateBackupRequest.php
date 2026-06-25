<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super admin') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'create_passphrase' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'create_passphrase.required' => 'Backup passphrase is required.',
            'create_passphrase.min' => 'Backup passphrase must be at least 8 characters.',
            'create_passphrase.confirmed' => 'Backup passphrase confirmation does not match.',
        ];
    }
}
