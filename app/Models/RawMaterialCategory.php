<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RawMaterialCategory extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function materials(): HasMany
    {
        return $this->hasMany(RawMaterial::class, 'raw_material_category_id');
    }

    public function statusBadge(): string
    {
        return (int) $this->status === 1
            ? '<span class="badge badge-pill badge-status bg-success">Active</span>'
            : '<span class="badge badge-pill badge-status bg-danger">Inactive</span>';
    }
}
