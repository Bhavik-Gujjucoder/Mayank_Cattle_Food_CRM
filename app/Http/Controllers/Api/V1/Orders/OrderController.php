<?php

namespace App\Http\Controllers\Api\V1\Orders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Orders\OrderListRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\OrderManagement;
use App\Support\Api\ApiResponse;
use App\Support\SalesScope;
use Illuminate\Http\JsonResponse;

/**
 * API 7 — Soda/Order Listing (Dealer / Broker only).
 *
 * Purpose:
 *   Return a paginated, filterable list of soda orders for the authenticated
 *   user. Access is restricted to the Dealer and Broker roles.
 *
 * Role-based data visibility (same rules as the web OrderManagementController):
 *   • Dealer — sees only orders where dealer_management.user_id = auth user id.
 *   • Broker — sees only orders where broker_id = auth user id.
 *
 * The existing SalesScope::scopeOrders() method is reused directly so that
 * the mobile API always produces the same row-set as the web DataTable.
 *
 * @see \App\Support\SalesScope::scopeOrders()
 * @see \App\Http\Controllers\OrderManagementController::index()
 */
class OrderController extends Controller
{
    /**
     * Roles permitted to call this endpoint.
     * Values must match Spatie role names stored in the `roles` table (lowercase).
     */
    private const ALLOWED_ROLES = ['dealer', 'broker'];

    /**
     * Default and maximum records returned per page.
     */
    private const DEFAULT_PER_PAGE = 15;
    private const MAX_PER_PAGE     = 100;

    /**
     * Return a paginated list of soda orders accessible to the authenticated user.
     *
     * Authorization:
     *   - Sanctum Bearer token must be valid (enforced by auth:sanctum middleware).
     *   - User must hold the 'dealer' or 'broker' Spatie role (enforced here → 403).
     *
     * Scoping:
     *   - SalesScope::scopeOrders() constrains the query to the user's own orders,
     *     identical to the web app's DataTable query in OrderManagementController.
     *
     * Filters (all optional, role-restrictions applied automatically):
     *   - order_number:   partial match on unique_order_id
     *   - payment_status: exact match (unpaid | paid | partial)
     *   - date_from:      order_date >= value (Y-m-d)
     *   - date_to:        order_date <= value (Y-m-d)
     *   - brand_id:       brand filter — applicable to brokers only; ignored for dealers
     *   - dealer_id:      dealer filter — applicable to brokers only; ignored for dealers
     *
     * Eager loads:
     *   All relations are loaded upfront to avoid N+1 queries on the resource layer.
     */
    public function index(OrderListRequest $request): JsonResponse
    {
        $user = $request->user(); // Resolved by auth:sanctum; never null here.

        // ── Role gate ──────────────────────────────────────────────────────────
        // Dealers and brokers are the only roles served by the mobile order API.
        // Staff / admin access is intentionally excluded — they use the web app.
        $userRole = null;
        foreach (self::ALLOWED_ROLES as $role) {
            if ($user->hasRole($role)) {
                $userRole = $role;
                break;
            }
        }

        if ($userRole === null) {
            return ApiResponse::error(
                'Access denied. This endpoint is restricted to Dealer and Broker accounts.',
                null,
                403
            );
        }

        // ── Base query ─────────────────────────────────────────────────────────
        // Eager-load every relation that the resources will access. All selected
        // column lists include the foreign key needed by Laravel's relation binder
        // (e.g. `order_id` for items, `order_item_id` for dispatches).
        $query = OrderManagement::with([
            // Broker user (FK: broker_id → users.id)
            'broker:id,name',

            // Brand (FK: brand_id → brand_management.id)
            'brand:id,name',

            // Dealer profile + linked user account (FK: dealer_id → dealer_management.id)
            'dealer:id,user_id,firm_shop_name',
            'dealer.user:id,name',

            // Line items: select only what the resource needs (FK: order_id)
            'items:id,order_id,product_id,qty,unit_price,total_price',

            // Product name + unit for each line item (FK: product_id → products.id)
            'items.product:id,name,unit',

            // Dispatch records per line item for qty + payment summary (FK: order_item_id)
            'items.dispatches:id,order_item_id,no_of_bags,dispatch_date,status',
        ]);

        // ── Role-based row visibility ──────────────────────────────────────────
        // Reuses the exact same scoping logic as the web app's DataTable so the
        // mobile API always serves the same orders the web list shows.
        //
        // The $user is passed explicitly so this works under auth:sanctum without
        // relying on auth()->user() (which would require the 'web' guard to be active).
        SalesScope::scopeOrders($query, $user);

        // ── Filters ────────────────────────────────────────────────────────────

        // Partial search on the human-readable order reference number.
        if ($request->filled('order_number')) {
            $query->where('unique_order_id', 'like', '%' . $request->input('order_number') . '%');
        }

        // Exact payment state match.
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        // Date range on order_date (both bounds are inclusive).
        if ($request->filled('date_from')) {
            $query->where('order_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('order_date', '<=', $request->input('date_to'));
        }

        // Brand filter — mirrors SalesScope::applyBrandFilter().
        // For dealers this filter is intentionally invisible (the dealer's brand
        // is already locked through the dealer_management record).
        if (SalesScope::showBrandFilter($user)) {
            SalesScope::applyBrandFilter($query, $request->input('brand_id'), $user);
        }

        // Dealer filter — mirrors SalesScope::applyDealerFilter().
        // For dealers this is a no-op: they can only ever see their own records.
        // For brokers it allows narrowing to a specific dealer under their portfolio.
        if (SalesScope::showDealerFilter($user)) {
            SalesScope::applyDealerFilter($query, $request->input('dealer_id'), $user);
        }

        // ── Sort ───────────────────────────────────────────────────────────────
        // Latest order first (matches the web DataTable default sort behaviour).
        $query->orderByDesc('order_date')->orderByDesc('id');

        // ── Paginate ───────────────────────────────────────────────────────────
        $perPage = min(
            max(1, (int) $request->input('per_page', self::DEFAULT_PER_PAGE)),
            self::MAX_PER_PAGE
        );

        $paginator = $query->paginate($perPage);

        // ── Response ───────────────────────────────────────────────────────────
        return ApiResponse::success('Orders retrieved successfully.', [
            'orders' => OrderResource::collection($paginator->items()),

            // Standard pagination envelope for mobile clients.
            'pagination' => [
                'current_page'  => $paginator->currentPage(),
                'per_page'      => $paginator->perPage(),
                'total'         => $paginator->total(),
                'last_page'     => $paginator->lastPage(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
            ],
        ]);
    }
}
