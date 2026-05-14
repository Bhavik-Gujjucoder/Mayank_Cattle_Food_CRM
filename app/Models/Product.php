<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'products';
    protected $guarded = [];

    public function statusBadge(): string
    {
        return $this->status == 1
            ? '<span class="badge badge-pill badge-status bg-success">Active</span>'
            : '<span class="badge badge-pill badge-status bg-danger">Inactive</span>';
    }

    public function brand()
    {
        return $this->belongsTo(BrandManagement::class, 'brand_id');
    }
}
