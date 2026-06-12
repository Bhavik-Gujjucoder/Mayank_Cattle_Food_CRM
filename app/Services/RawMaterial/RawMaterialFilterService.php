<?php

namespace App\Services\RawMaterial;

use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\RawMaterialOrder;
use App\Models\RawMaterialReceive;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class RawMaterialFilterService
{
    /** DataTables sends search as an array; export uses a plain string query param. */
    public static function searchTerm(Request $request): ?string
    {
        if ($request->filled('search.value')) {
            $term = trim((string) $request->input('search.value'));
        } elseif (is_string($request->search)) {
            $term = trim($request->search);
        } else {
            return null;
        }

        return $term !== '' ? $term : null;
    }

    public static function materials(Request $request): Builder
    {
        $query = RawMaterial::with('category');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('raw_material_category_id') && $request->raw_material_category_id !== 'all') {
            $query->where('raw_material_category_id', $request->raw_material_category_id);
        }

        if ($term = self::searchTerm($request)) {
            $query->where(function ($sub) use ($term) {
                $sub->where('name', 'like', '%' . $term . '%')
                    ->orWhere('raw_material_unique_id', 'like', '%' . $term . '%')
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', '%' . $term . '%'));
            });
        }

        return $query->orderByDesc('id');
    }

    public static function categories(Request $request): Builder
    {
        $query = RawMaterialCategory::query();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($term = self::searchTerm($request)) {
            $query->where(function ($sub) use ($term) {
                $sub->where('name', 'like', '%' . $term . '%')
                    ->orWhere('category_unique_id', 'like', '%' . $term . '%');
            });
        }

        return $query->orderByDesc('id');
    }

    public static function orders(Request $request): Builder
    {
        $query = RawMaterialOrder::with(['supplier', 'supplierBroker']);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('supplier_id') && $request->supplier_id !== 'all') {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('date_from')) {
            $query->where('order_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('order_date', '<=', $request->date_to);
        }
        if ($term = self::searchTerm($request)) {
            $query->where(function ($sub) use ($term) {
                $sub->where('order_unique_id', 'like', "%{$term}%")
                    ->orWhere('supplier_order_id', 'like', "%{$term}%")
                    ->orWhere('price_basis', 'like', "%{$term}%")
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('supplierBroker', fn ($b) => $b->where('name', 'like', "%{$term}%"));
            });
        }

        return $query->orderByDesc('id');
    }

    public static function receives(Request $request): Builder
    {
        $query = RawMaterialReceive::with(['order', 'rawMaterial.category']);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('raw_material_id') && $request->raw_material_id !== 'all') {
            $query->where('raw_material_id', $request->raw_material_id);
        }
        if ($request->filled('raw_material_order_id') && $request->raw_material_order_id !== 'all') {
            $query->where('raw_material_order_id', $request->raw_material_order_id);
        }
        if ($request->filled('date_from')) {
            $query->where('received_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('received_date', '<=', $request->date_to);
        }
        if ($term = self::searchTerm($request)) {
            $query->where(function ($sub) use ($term) {
                $sub->whereHas('order', function ($o) use ($term) {
                    $o->where('order_unique_id', 'like', "%{$term}%")
                        ->orWhere('supplier_order_id', 'like', "%{$term}%");
                })
                    ->orWhereHas('rawMaterial', fn ($m) => $m->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('rawMaterial.category', fn ($c) => $c->where('name', 'like', "%{$term}%"));
            });
        }

        return $query->orderByDesc('id');
    }

    public static function materialStatusLabel(int $status): string
    {
        return (int) $status === 1 ? 'Active' : 'Inactive';
    }

    public static function orderStatusLabel(int $status): string
    {
        return match ((int) $status) {
            1 => 'Partially Received',
            2 => 'Received',
            3 => 'Cancelled',
            default => 'Pending',
        };
    }

    public static function orderItemStatusLabel(int $status): string
    {
        return self::orderStatusLabel($status);
    }

    public static function receiveStatusLabel(int $status): string
    {
        return match ((int) $status) {
            1 => 'Received',
            2 => 'Cancelled',
            default => 'On Road',
        };
    }
}
