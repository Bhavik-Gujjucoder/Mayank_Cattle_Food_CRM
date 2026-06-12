<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class BrandManagement extends Model
{
    protected $table = 'brand_management';
    protected $guarded = [];

    /** Ascending order as stored in the database (by primary key). */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('id');
    }

    /** Active brands for dropdowns, in database storage order. */
    public static function activeForDropdown(array $columns = ['*']): Collection
    {
        return static::query()
            ->where('status', 1)
            ->ordered()
            ->get($columns);
    }

    public function statusBadge(): string
    {
        return $this->status == 1
            ? '<span class="badge badge-pill badge-status bg-success">Active</span>'
            : '<span class="badge badge-pill badge-status bg-danger">Inactive</span>';
    }

    public static function isActive(int $id): bool
    {
        return static::query()
            ->where('status', 1)
            ->whereKey($id)
            ->exists();
    }
}
