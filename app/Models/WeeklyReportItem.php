<?php

namespace App\Models;

use App\Support\ProductUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WeeklyReportItem extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';

    protected $table = 'weekly_report_items';

    protected $guarded = [];

    protected $casts = [
        'sort_order' => 'integer',
        'quantity'   => 'integer',
    ];

    /* ── Relationships ───────────────────────────────────────────── */

    public function report()
    {
        return $this->belongsTo(WeeklyReport::class, 'weekly_report_id');
    }

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

    public function dispatch()
    {
        return $this->belongsTo(DispatchManagement::class, 'dispatch_id');
    }

    /* ── Helpers ─────────────────────────────────────────────────── */

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isLocked(): bool
    {
        return $this->isConfirmed();
    }

    public function quantityInBags(): float
    {
        return ProductUnit::toBags((float) $this->quantity, $this->product?->unit);
    }

    public function quantityLabel(): string
    {
        return ProductUnit::formatWithUnit($this->quantity, $this->product?->unit);
    }

    public function statusBadge(): string
    {
        return $this->isConfirmed()
            ? '<span class="badge bg-success-light text-success">Confirmed</span>'
            : '<span class="badge bg-warning-light text-warning">Pending</span>';
    }
}
