<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DispatchManagement extends Model
{
    use SoftDeletes;

    protected $table = 'dispatch_management';

    protected $guarded = [];

    protected $casts = [
        'dispatch_date' => 'date',
    ];

    /* ── Relationships ───────────────────────────────────────────── */

    public function order()
    {
        return $this->belongsTo(OrderManagement::class, 'order_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function transporter()
    {
        return $this->belongsTo(User::class, 'transport_id');
    }
}
