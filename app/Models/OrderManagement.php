<?php

namespace App\Models;

use App\Services\PaymentReceivableService;
use App\Support\SalesScope;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Limit orders to what the given user may see (role-based sales scope).
     *
     * @param  Builder<OrderManagement>  $query
     */
    public function scopeForUser(Builder $query, ?\App\Models\User $user = null): Builder
    {
        return SalesScope::scopeOrders($query, $user);
    }

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

    /**
     * Returns true when every order item has been completely dispatched.
     * Requires 'items.dispatches' to be eager-loaded before calling.
     * An order with no items is treated as complete (nothing to block).
     */
    public function isFullyDispatched(): bool
    {
        if ($this->items->isEmpty()) return true;

        return $this->items->every(
            fn($item) => (int) $item->dispatches->sum('no_of_bags') >= (int) $item->qty
        );
    }

    /** Sum of line item qty (bags/ton). Requires items loaded. */
    public function totalOrderedQty(): int
    {
        return (int) $this->items->sum('qty');
    }

    /** Sum of dispatched bags across all line items. Requires items.dispatches loaded. */
    public function totalDispatchedQty(): int
    {
        return (int) $this->items->sum(
            fn($item) => (int) $item->dispatches->sum('no_of_bags')
        );
    }

    public function dispatchPercent(): int
    {
        $ordered = $this->totalOrderedQty();

        return $ordered > 0
            ? (int) round(($this->totalDispatchedQty() / $ordered) * 100)
            : 0;
    }

    /** Weighted average unit price: grand_total ÷ total ordered qty. */
    public function weightedAvgUnitPrice(): float
    {
        $ordered = $this->totalOrderedQty();

        return $ordered > 0
            ? (float) $this->grand_total / $ordered
            : 0.0;
    }

    public function pendingLineItemCount(): int
    {
        return $this->items->filter(
            fn($item) => max(0, (int) $item->qty - (int) $item->dispatches->sum('no_of_bags')) > 0
        )->count();
    }

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

    /** @return list<string> */
    public static function pendingPaymentStatuses(): array
    {
        return ['unpaid', 'partial'];
    }

    /**
     * Sync parent order payment status from dispatch payment rows.
     */
    public function syncPaymentStatusFromDispatches(): void
    {
        app(PaymentReceivableService::class)->syncOrderPaymentStatus($this);
    }
}
