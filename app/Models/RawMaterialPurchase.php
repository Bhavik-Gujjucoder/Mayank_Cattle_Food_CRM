<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RawMaterialPurchase extends Model
{
    use SoftDeletes;
    protected $table = 'raw_material_purchases';
    protected $guarded = [];
    protected $fillable = [
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
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function statusBadge()
    {
        //return $this->status == 1 ? '<span class="badge badge-pill badge-status bg-success">Active</span>' : '<span class="badge badge-pill badge-status bg-danger">Inactive</span>';
        $status_badge = '';
        if($this->status == 1){
            $status = 'Received';
            $status_badge = '<span class="badge badge-pill badge-status bg-success">Received</span>';
        }else if($this->status == 2){
            $status = 'Cancelled';
            $status_badge = '<span class="badge badge-pill badge-status bg-danger">Cancelled</span>';
        }else{
            $status_badge = '<span class="badge badge-pill badge-status bg-secondary">Pending</span>';
        }
        return $status_badge;
    }

    /* Start:: Relationships */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
    public function raw_material()
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }
    /* End:: Relationships */
}
