<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierBroker extends Model
{
    use SoftDeletes;

    protected $table = 'supplier_brokers';

    protected $guarded = [];

    public function state(): BelongsTo
    {
        return $this->belongsTo(StateManagement::class, 'state_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(CityManagement::class, 'city_id');
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function statusBadge(): string
    {
        return $this->status == 1
            ? '<span class="badge badge-pill badge-status bg-success">Active</span>'
            : '<span class="badge badge-pill badge-status bg-danger">Inactive</span>';
    }
}
