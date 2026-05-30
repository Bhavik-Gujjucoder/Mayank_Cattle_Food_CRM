<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RawMaterialOrderItem extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(RawMaterialOrder::class, 'raw_material_order_id');
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
}
