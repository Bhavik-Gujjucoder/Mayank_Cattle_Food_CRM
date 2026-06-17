<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchLateFeeLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'charge_date'   => 'date',
        'daily_amount'  => 'decimal:2',
        'rate_per_unit' => 'decimal:2',
        'quantity'      => 'integer',
    ];

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(DispatchManagement::class, 'dispatch_management_id');
    }
}
