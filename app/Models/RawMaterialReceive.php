<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RawMaterialReceive extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = ['received_date' => 'date'];

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(RawMaterialOrder::class, 'raw_material_order_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(RawMaterialOrderItem::class, 'raw_material_order_item_id');
    }

    public function statusBadge(): string
    {
        return match ((int) $this->status) {
            1 => '<span class="badge badge-pill badge-status bg-success">Received</span>',
            2 => '<span class="badge badge-pill badge-status bg-danger">Cancelled</span>',
            default => '<span class="badge badge-pill badge-status bg-secondary">On Road</span>',
        };
    }

    public function isEditable(): bool
    {
        return (int) $this->status === 0;
    }
}
