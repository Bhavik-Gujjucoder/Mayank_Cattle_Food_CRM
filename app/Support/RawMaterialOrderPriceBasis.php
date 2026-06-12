<?php

namespace App\Support;

class RawMaterialOrderPriceBasis
{
    public const FOR_GST = 'FOR + GST';

    public const EX_FACTORY_GST = 'Ex-Factory + GST';

    /** @return array<int, string> */
    public static function options(): array
    {
        return [
            self::FOR_GST,
            self::EX_FACTORY_GST,
        ];
    }
}
