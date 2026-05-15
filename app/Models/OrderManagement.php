<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderManagement extends Model
{
    use SoftDeletes;

    protected $table = 'order_management';

    protected $guarded = [];

    protected $casts = [
        'order_date'           => 'date',
        'partial_paid_amount'  => 'decimal:2',
        'total_order_amount'   => 'decimal:2',
        'grand_total'          => 'decimal:2',
    ];

    /* ── Relationships ───────────────────────────────────────────── */

    public function broker()
    {
        return $this->belongsTo(User::class, 'broker_id');
    }

    public function brand()
    {
        return $this->belongsTo(BrandManagement::class, 'brand_id');
    }

    public function dealer()
    {
        return $this->belongsTo(DealerManagement::class, 'dealer_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function dispatches()
    {
        return $this->hasMany(DispatchManagement::class, 'order_id');
    }

    /* ── Helpers ─────────────────────────────────────────────────── */

    public function statusBadge(): string
    {
        return $this->status
            ? '<span class="badge bg-success-light text-success">Active</span>'
            : '<span class="badge bg-danger-light text-danger">Inactive</span>';
    }

    public function paymentBadge(): string
    {
        return match ($this->payment_status) {
            'paid'    => '<span class="badge bg-success-light text-success">Paid</span>',
            'partial' => '<span class="badge bg-warning-light text-warning">Partial</span>',
            default   => '<span class="badge bg-danger-light text-danger">Unpaid</span>',
        };
    }
}
