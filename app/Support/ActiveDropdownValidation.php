<?php

namespace App\Support;

use App\Models\BrandManagement;
use App\Models\User;

class ActiveDropdownValidation
{
    public static function brokerId(): array
    {
        return [
            'required',
            'integer',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (! User::isActiveBroker((int) $value)) {
                    $fail('Selected broker is invalid or inactive.');
                }
            },
        ];
    }

    public static function brandId(): array
    {
        return [
            'required',
            'integer',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (! BrandManagement::isActive((int) $value)) {
                    $fail('Selected brand is invalid or inactive.');
                }
            },
        ];
    }
}
