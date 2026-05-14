<?php

use App\Models\GeneralSetting;

if (!function_exists('getSetting')) {
    function getSetting($key)
    {
        $value = GeneralSetting::where('key', $key)->first()->value ?? '';
        return $value;
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
