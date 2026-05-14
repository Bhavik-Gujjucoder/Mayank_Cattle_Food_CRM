<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RawMaterialPurchase extends Model
{
    use SoftDeletes;

    protected $table = 'raw_material_purchases';
    protected $fillable = [
        'purchase_unique_id',
        'supplier_id',
        'raw_material_id',
        'invoice_no',
        'invoice_date',
        'quantity',
        'unit_price',
        'total_price',
        'status',
        'paid_amount',
        'due_amount',
        'remarks',
        'created_by',
        'updated_by',
    ];

    /* ------------------------------------------------------------------ */
    /*  Status Badge                                                        */
    /* ------------------------------------------------------------------ */
    public function statusBadge(): string
    {
        return match ((int) $this->status) {
            1       => '<span class="badge badge-pill badge-status bg-success">Received</span>',
            2       => '<span class="badge badge-pill badge-status bg-danger">Cancelled</span>',
            default => '<span class="badge badge-pill badge-status bg-secondary">Pending</span>',
        };
    }

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                       */
    /* ------------------------------------------------------------------ */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function raw_material()
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }
}
