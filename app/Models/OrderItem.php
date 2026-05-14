<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    // use SoftDeletes;

    protected $table = 'order_items';

    protected $guarded = [];

    protected $casts = [
        'unit_price'  => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /* ── Relationships ───────────────────────────────────────────── */

    public function order()
    {
        return $this->belongsTo(OrderManagement::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
