<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use SoftDeletes;

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

    public function dispatches()
    {
        return $this->hasMany(DispatchManagement::class, 'order_item_id');
    }

    /* ── Helpers ─────────────────────────────────────────────────── */

    public function dispatchedQty(): int
    {
        if ($this->relationLoaded('dispatches')) {
            return (int) $this->dispatches->sum('no_of_bags');
        }

        return (int) $this->dispatches()->sum('no_of_bags');
    }

    public function pendingQty(): int
    {
        return max(0, (int) $this->qty - $this->dispatchedQty());
    }

    /** Max bags allowed when editing an existing dispatch (includes its current qty). */
    public function maxBagsWhenEditing(DispatchManagement $dispatch): int
    {
        $otherBags = $this->relationLoaded('dispatches')
            ? (int) $this->dispatches->where('id', '!=', $dispatch->id)->sum('no_of_bags')
            : (int) $this->dispatches()
                ->where('id', '!=', $dispatch->id)
                ->sum('no_of_bags');

        $maxFromOrder = max(0, (int) $this->qty - $otherBags);

        return max($maxFromOrder, (int) $dispatch->no_of_bags);
    }
}
