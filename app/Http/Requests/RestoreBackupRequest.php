<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RestoreBackupRequest extends FormRequest
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
        $extension = (string) config('backup.extension');

        return [
            'restore_source' => ['required', Rule::in(['server', 'upload'])],
            'backup_filename' => ['required_if:restore_source,server', 'nullable', 'string', 'max:255'],
            'backup_file' => [
                'required_if:restore_source,upload',
                'nullable',
                'file',
                'max:512000',
                function (string $attribute, mixed $value, \Closure $fail) use ($extension): void {
                    if (! $value) {
                        return;
                    }

                    $name = strtolower($value->getClientOriginalName());

                    if (! str_ends_with($name, '.'.$extension)) {
                        $fail('The backup file must be a .'.$extension.' file.');
                    }
                },
            ],
            'password' => ['required', 'string'],
            'restore_passphrase' => ['required', 'string', 'min:8'],
            'confirmation_text' => ['required', 'in:RESTORE'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'backup_filename.required_if' => 'Please select a backup from the server.',
            'backup_file.required_if' => 'Please upload a backup file.',
            'restore_passphrase.required' => 'Backup passphrase is required.',
            'restore_passphrase.min' => 'Backup passphrase must be at least 8 characters.',
            'confirmation_text.in' => 'Type RESTORE exactly to confirm this action.',
        ];
    }
}
