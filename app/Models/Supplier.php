<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $table = 'suppliers';
    protected $guarded = [];

    public function supplierBrokers(): BelongsToMany
    {
        return $this->belongsToMany(SupplierBroker::class, 'supplier_broker_supplier')
            ->orderBy('name');
    }

    public function belongsToBroker(int $brokerId): bool
    {
        if ($this->relationLoaded('supplierBrokers')) {
            return $this->supplierBrokers->contains('id', $brokerId);
        }

        return $this->supplierBrokers()->whereKey($brokerId)->exists();
    }

    /**
     * Create a supplier and attach brokers.
     * Accepts supplier_broker_id or supplier_broker_ids so existing creates keep working.
     */
    public static function create(array $attributes = [])
    {
        $brokerIds = static::pullBrokerIds($attributes);

        /** @var static $supplier */
        $supplier = static::query()->create($attributes);

        if ($brokerIds !== []) {
            $supplier->supplierBrokers()->sync($brokerIds);
        }

        return $supplier;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return list<int>
     */
    private static function pullBrokerIds(array &$attributes): array
    {
        $raw = [];

        if (array_key_exists('supplier_broker_id', $attributes)) {
            $raw[] = $attributes['supplier_broker_id'];
            unset($attributes['supplier_broker_id']);
        }

        if (array_key_exists('supplier_broker_ids', $attributes)) {
            $raw = array_merge($raw, (array) $attributes['supplier_broker_ids']);
            unset($attributes['supplier_broker_ids']);
        }

        $ids = [];
        foreach ($raw as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(StateManagement::class, 'state_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(CityManagement::class, 'city_id');
    }

    public function rawMaterialOrders(): HasMany
    {
        return $this->hasMany(RawMaterialOrder::class);
    }

    public function statusBadge(): string
    {
        return $this->status == 1
            ? '<span class="badge badge-pill badge-status bg-success">Active</span>'
            : '<span class="badge badge-pill badge-status bg-danger">Inactive</span>';
    }
}
