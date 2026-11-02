<?php

use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Cache;

if (!function_exists('forgetGeneralSettingsCache')) {
    function forgetGeneralSettingsCache(): void
    {
        Cache::forget('general_settings_all');
    }
}

if (! function_exists('getSetting')) {
    function getSetting($key)
    {
        $settings = Cache::remember('general_settings_all', 3600, function () {
            return GeneralSetting::query()->pluck('value', 'key')->all();
        });

        $value = $settings[$key] ?? '';

        return $value === null ? '' : (string) $value;
    }
}

if (!function_exists('companyLogoUrl')) {
    function companyLogoUrl(): string
    {
        $logo = getSetting('company_logo');

        return $logo !== ''
            ? asset('storage/company_logo/' . $logo)
            : asset('images/default-user.png');
    }
}

if (! function_exists('rmQty')) {
    /** Tons stored to 3 decimal places (1 kg). */
    function rmQty(mixed $value): float
    {
        return round((float) $value, 3);
    }
}

if (! function_exists('rmQtyInput')) {
    /** Decimal tons for a number input, without thousands separators. */
    function rmQtyInput(mixed $value): string
    {
        $trimmed = rtrim(rtrim(number_format(rmQty($value), 3, '.', ''), '0'), '.');

        return $trimmed === '' ? '0' : $trimmed;
    }
}

if (! function_exists('rmQtyFormat')) {
    /** Decimal tons for display, with thousands separators and trailing zeros removed. */
    function rmQtyFormat(mixed $value): string
    {
        $trimmed = rtrim(rtrim(number_format(rmQty($value), 3, '.', ','), '0'), '.');

        return ($trimmed === '' || $trimmed === '-') ? '0' : $trimmed;
    }
}

/* Raw Material Purchase Status */
if (! function_exists('rawMaterialPurchaseStatus')) {
    function rawMaterialPurchaseStatus($status_key = '', $status_value = '')
    {
        $status_list = [
            0 => 'Pending',
            1 => 'Received',
            2 => 'Cancelled',
        ];
        if ($status_key != '' && $status_value == '') {
            return $status_list[$status_key];
        } elseif ($status_key == '' && $status_value != '') {
            $key = array_search($status_value, $status_list);

            return $status_key;
        } else {
            return $status_list;
        }
    }
}
