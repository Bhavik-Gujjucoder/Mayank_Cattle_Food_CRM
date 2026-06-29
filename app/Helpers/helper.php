<?php

use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Cache;

if (!function_exists('forgetGeneralSettingsCache')) {
    function forgetGeneralSettingsCache(): void
    {
        Cache::forget('general_settings_all');
    }
}

if (!function_exists('getSetting')) {
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

/* Raw Material Purchase Status */
if (!function_exists('rawMaterialPurchaseStatus')) {
    function rawMaterialPurchaseStatus($status_key='', $status_value='')
    {
        $status_list = [
            0 => 'Pending',
            1 => 'Received',
            2 => 'Cancelled',
        ];
        if($status_key != '' && $status_value == ''){
            return $status_list[$status_key];
        }else if($status_key == '' && $status_value != ''){
            $key = array_search($status_value, $status_list);
            return $status_key;
        }else{
            return $status_list;
        }
    }
}
