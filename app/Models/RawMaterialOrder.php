<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RawMaterialOrder extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = ['order_date' => 'date'];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RawMaterialOrderItem::class);
    }

    public function receives(): HasMany
    {
        return $this->hasMany(RawMaterialReceive::class);
    }

    public function statusBadge(): string
    {
        return match ((int) $this->status) {
            1 => '<span class="badge badge-pill badge-status bg-info">Partially Received</span>',
            2 => '<span class="badge badge-pill badge-status bg-success">Received</span>',
            3 => '<span class="badge badge-pill badge-status bg-danger">Cancelled</span>',
            default => '<span class="badge badge-pill badge-status bg-warning">Pending</span>',
        };
    }

    public function isEditable(): bool
    {
        return (int) $this->status === 0;
    }
}
