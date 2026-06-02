<?php

namespace App\Support;

use App\Models\BrandManagement;
use App\Models\DealerManagement;
use App\Models\DispatchManagement;
use App\Models\OrderManagement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Role-based row visibility for the Sales module (orders + dispatches).
 *
 * - super admin, admin, staff → all records
 * - broker → orders where broker_id = user id
 * - dealer → orders where dealer_management.user_id = user id
 */
class SalesScope
{
    public const GLOBAL_ROLES = ['super admin', 'admin', 'staff'];

    public static function hasGlobalAccess(?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        return $user && $user->hasAnyRole(self::GLOBAL_ROLES);
    }

    public static function isBroker(?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        return $user && $user->hasRole('broker') && ! static::hasGlobalAccess($user);
    }

    public static function isDealer(?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        return $user && $user->hasRole('dealer') && ! static::hasGlobalAccess($user);
    }

    /** Whether the order list broker filter should be shown. */
    public static function showBrokerFilter(?User $user = null): bool
    {
        return ! static::isBroker($user) && ! static::isDealer($user);
    }

    /** Whether the order list brand filter should be shown (hidden for dealer role). */
    public static function showBrandFilter(?User $user = null): bool
    {
        return ! static::isDealer($user);
    }

    /**
     * Active brands for the Soda/Order list filter dropdown.
     * - super admin, admin, staff → all active brands
     * - broker → active brands linked via dealer_management for this broker
     * - dealer → none (filter hidden)
     *
     * @return Collection<int, BrandManagement>
     */
    public static function filterableBrands(?User $user = null): Collection
    {
        $user = $user ?? auth()->user();

        $query = BrandManagement::query()->where('status', 1)->orderBy('name');

        if (! $user || static::hasGlobalAccess($user)) {
            return $query->get();
        }

        if (static::isBroker($user)) {
            $brandIds = DealerManagement::query()
                ->where('broker_id', $user->id)
                ->distinct()
                ->pluck('brand_id');

            if ($brandIds->isEmpty()) {
                return collect();
            }

            return $query->whereIn('id', $brandIds)->get();
        }

        return collect();
    }

    /**
     * Apply brand_id list filter when the request value is allowed for this user.
     *
     * @param  Builder<OrderManagement>  $query
     * @return Builder<OrderManagement>
     */
    public static function applyBrandFilter(Builder $query, mixed $brandId, ?User $user = null): Builder
    {
        if (! static::showBrandFilter($user)) {
            return $query;
        }

        if ($brandId === null || $brandId === '' || $brandId === 'all') {
            return $query;
        }

        $brandId = (int) $brandId;

        if (! static::userCanFilterByBrand($brandId, $user)) {
            return $query;
        }

        return $query->where('brand_id', $brandId);
    }

    /** Whether the user may filter orders by this brand on the list page. */
    public static function userCanFilterByBrand(int $brandId, ?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (! $user || static::hasGlobalAccess($user)) {
            return BrandManagement::query()
                ->where('status', 1)
                ->whereKey($brandId)
                ->exists();
        }

        if (static::isBroker($user)) {
            return static::filterableBrands($user)->contains('id', $brandId);
        }

        return false;
    }

    /**
     * @param  Builder<OrderManagement>  $query
     * @return Builder<OrderManagement>
     */
    public static function scopeOrders(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();

        if (! $user || static::hasGlobalAccess($user)) {
            return $query;
        }

        if ($user->hasRole('broker')) {
            return $query->where('broker_id', $user->id);
        }

        if ($user->hasRole('dealer')) {
            return $query->whereHas('dealer', fn (Builder $q) => $q->where('user_id', $user->id));
        }

        return $query;
    }

    /**
     * @param  Builder<DispatchManagement>  $query
     * @return Builder<DispatchManagement>
     */
    public static function scopeDispatches(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();

        if (! $user || static::hasGlobalAccess($user)) {
            return $query;
        }

        if ($user->hasRole('broker')) {
            return $query->whereHas('order', fn (Builder $q) => $q->where('broker_id', $user->id));
        }

        if ($user->hasRole('dealer')) {
            return $query->whereHas('order.dealer', fn (Builder $q) => $q->where('user_id', $user->id));
        }

        return $query;
    }

    public static function userCanAccessOrder(OrderManagement $order, ?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return false;
        }

        return static::scopeOrders(OrderManagement::query()->whereKey($order->id), $user)->exists();
    }

    public static function authorizeOrderAccess(OrderManagement $order, ?User $user = null): void
    {
        if (! static::userCanAccessOrder($order, $user)) {
            abort(403, 'You do not have access to this order.');
        }
    }

    public static function authorizeDispatchAccess(DispatchManagement $dispatch, ?User $user = null): void
    {
        $dispatch->loadMissing('order');

        if (! $dispatch->order) {
            abort(404);
        }

        static::authorizeOrderAccess($dispatch->order, $user);
    }

    /**
     * Enforce broker_id / dealer_id on create & update payloads.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function enforceOrderAssignment(array $validated, ?User $user = null): array
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return $validated;
        }

        if (static::isBroker($user)) {
            $validated['broker_id'] = $user->id;
        }

        if (static::isDealer($user)) {
            $dealer = DealerManagement::where('user_id', $user->id)->first();

            if (! $dealer) {
                abort(403, 'No dealer profile linked to your account.');
            }

            $validated['dealer_id'] = $dealer->id;
        }

        return $validated;
    }

    /** Dealer may only use their own dealer_management id on forms / AJAX. */
    public static function authorizeDealerId(int|string $dealerId, ?User $user = null): void
    {
        $user = $user ?? auth()->user();

        if (! $user || ! static::isDealer($user)) {
            return;
        }

        $allowed = DealerManagement::where('user_id', $user->id)->whereKey($dealerId)->exists();

        if (! $allowed) {
            abort(403, 'You do not have access to this dealer.');
        }
    }
}
