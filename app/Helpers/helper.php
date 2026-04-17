<?php

use App\Models\GeneralSetting;

if (!function_exists('getSetting')) {
    function getSetting($key)
    {
        $value = GeneralSetting::where('key', $key)->first()->value ?? '';
        return $value;
    }
}



