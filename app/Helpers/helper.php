<?php

use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Schema;

if (! function_exists('getSetting')) {
    function getSetting($key)
    {
        try {
            if (! Schema::hasTable('general_settings')) {
                return '';
            }

            return GeneralSetting::query()->where('key', $key)->value('value') ?? '';
        } catch (Throwable) {
            return '';
        }
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
