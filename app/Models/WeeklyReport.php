<?php

namespace App\Models;

use App\Support\ProductUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WeeklyReport extends Model
{
    use SoftDeletes;

    public const BAGS_PER_HOUR = 135;

    protected $table = 'weekly_reports';

    protected $guarded = [];

    protected $casts = [
        'report_date'       => 'date',
        'already_produced'  => 'decimal:2',
        'production_hours'  => 'decimal:4',
    ];

    /* ── Relationships ───────────────────────────────────────────── */

    public function items()
    {
        return $this->hasMany(WeeklyReportItem::class, 'weekly_report_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ── Helpers ─────────────────────────────────────────────────── */

    /** Sum of line quantities converted to bag-equivalents. */
    public function totalQuantityInBags(): float
    {
        $items = $this->relationLoaded('items')
            ? $this->items
            : $this->items()->with('product:id,unit')->get();

        return (float) $items->sum(
            fn (WeeklyReportItem $item) => ProductUnit::toBags(
                (float) $item->quantity,
                $item->product?->unit
            )
        );
    }

    /**
     * Remaining to produce: Total − ready stock.
     * When ready stock is 0, difference equals total quantity.
     */
    public function differenceInBags(): float
    {
        return max(0, $this->totalQuantityInBags() - (float) $this->already_produced);
    }

    /** Auto hours from difference ÷ 135. */
    public function calculatedProductionHours(): float
    {
        return $this->differenceInBags() / self::BAGS_PER_HOUR;
    }

    /**
     * Effective hours: stored admin override when set, otherwise auto calc.
     */
    public function productionHours(): float
    {
        if ($this->production_hours !== null) {
            return (float) $this->production_hours;
        }

        return $this->calculatedProductionHours();
    }

    public function hasConfirmedItems(): bool
    {
        if ($this->relationLoaded('items')) {
            return $this->items->contains(
                fn (WeeklyReportItem $item) => $item->isConfirmed()
            );
        }

        return $this->items()->where('status', WeeklyReportItem::STATUS_CONFIRMED)->exists();
    }
}
