<?php

namespace App\Services\RawMaterial;

use App\Models\RawMaterial;
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
        $query = RawMaterial::query();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($term = self::searchTerm($request)) {
            $query->where('name', 'like', '%' . $term . '%');
        }

        return $query->orderByDesc('id');
    }

    public static function orders(Request $request): Builder
    {
        $query = RawMaterialOrder::with('supplier');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('supplier_id') && $request->supplier_id !== 'all') {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }
        if ($term = self::searchTerm($request)) {
            $query->where(function ($sub) use ($term) {
                $sub->where('order_unique_id', 'like', "%{$term}%")
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$term}%"));
            });
        }

        return $query->orderByDesc('id');
    }

    public static function receives(Request $request): Builder
    {
        $query = RawMaterialReceive::with(['order', 'rawMaterial']);

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
            $query->whereDate('received_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('received_date', '<=', $request->date_to);
        }
        if ($term = self::searchTerm($request)) {
            $query->where(function ($sub) use ($term) {
                $sub->whereHas('order', fn ($o) => $o->where('order_unique_id', 'like', "%{$term}%"))
                    ->orWhereHas('rawMaterial', fn ($m) => $m->where('name', 'like', "%{$term}%"));
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
