<?php

namespace App\Models;

use App\Support\SalesScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DispatchManagement extends Model
{
    use SoftDeletes;

    public const STATUS_UNPAID  = 0;
    public const STATUS_PAID    = 1;
    public const STATUS_PARTIAL = 2;

    protected $table = 'dispatch_management';

    protected $guarded = [];

    protected $casts = [
        'dispatch_date'           => 'date',
        'status'                  => 'integer',
        'partial_paid_amount'     => 'decimal:2',
        'accrued_late_fee'        => 'decimal:2',
        'late_fee_last_accrued_on' => 'date',
    ];

    /** @return list<int> */
    public static function pendingPaymentStatuses(): array
    {
        return [self::STATUS_UNPAID, self::STATUS_PARTIAL];
    }

    public function statusBadge(): string
    {
        return match ((int) $this->status) {
            self::STATUS_PAID    => '<span class="badge bg-success-light text-success">Paid</span>',
            self::STATUS_PARTIAL => '<span class="badge bg-warning-light text-warning">Partial Payment</span>',
            default              => '<span class="badge bg-danger-light text-danger">Unpaid</span>',
        };
    }

    /**
     * @param  Builder<DispatchManagement>  $query
     */
    public function scopeForUser(Builder $query, ?\App\Models\User $user = null): Builder
    {
        return SalesScope::scopeDispatches($query, $user);
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

    public function lateFeeLogs()
    {
        return $this->hasMany(DispatchLateFeeLog::class, 'dispatch_management_id');
    }
}
