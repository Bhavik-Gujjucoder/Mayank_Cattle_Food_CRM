<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DispatchManagement extends Model
{
    use SoftDeletes;

    public const STATUS_UNPAID = 0;
    public const STATUS_PAID   = 1;

    protected $table = 'dispatch_management';

    protected $guarded = [];

    protected $casts = [
        'dispatch_date' => 'date',
        'status'        => 'integer',
    ];

    public function statusBadge(): string
    {
        return (int) $this->status === self::STATUS_PAID
            ? '<span class="badge bg-success-light text-success">Paid</span>'
            : '<span class="badge bg-danger-light text-danger">Unpaid</span>';
    }

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
