<?php

namespace App\Http\Controllers\Api\V1\Dispatches;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Dispatches\DispatchListRequest;
use App\Http\Resources\Api\V1\DispatchResource;
use App\Models\DispatchManagement;
use App\Support\Api\ApiResponse;
use App\Support\SalesScope;
use Illuminate\Http\JsonResponse;

/**
 * API 9 — Dispatch Listing (Dealer / Broker only).
 *
 * Purpose:
 *   Return a paginated, filterable list of dispatch records for the
 *   authenticated user. Access is restricted to the Dealer and Broker roles.
 *
 * Role-based data visibility (mirrors DispatchManagementController::index()):
 *   • Dealer — dispatches linked to orders where dealer_management.user_id = auth user id.
 *   • Broker — dispatches linked to orders where broker_id = auth user id.
 *
 * Each dispatch record includes the parent order (with broker, brand, dealer),
 * the dispatched product, the transporter details, and order-item quantity
 * context (ordered qty, total dispatched, and pending qty) so the mobile app
 * can show fulfilment progress without additional API calls.
 *
 * @see \App\Support\SalesScope::scopeDispatches()
 * @see \App\Http\Controllers\DispatchManagementController::index()
 */
class DispatchController extends Controller
{
    /**
     * Roles permitted to call this endpoint (Spatie role names, lowercase).
     */
    private const ALLOWED_ROLES = ['dealer', 'broker'];

    private const DEFAULT_PER_PAGE = 15;
    private const MAX_PER_PAGE     = 100;

    /**
     * Return a paginated list of dispatch records accessible to the authenticated user.
     *
     * Authorization:
     *   - Sanctum Bearer token required (enforced by auth:sanctum middleware).
     *   - User must hold the 'dealer' or 'broker' Spatie role (enforced here → 403).
     *
     * Scoping:
     *   - SalesScope::scopeDispatches() constrains rows to the user's own dispatches,
     *     identical to the web app's DataTable query in DispatchManagementController.
     *
     * Filters (all optional, role-restrictions applied automatically):
     *   - order_number: partial match on the parent order's unique_order_id
     *   - status:       payment status (unpaid | paid | partial)
     *   - date_from:    dispatch_date >= value (Y-m-d)
     *   - date_to:      dispatch_date <= value (Y-m-d)
     *   - brand_id:     filter by brand — broker-applicable only, silently ignored for dealers
     *   - product_id:   filter by dispatched product
     *   - dealer_id:    filter by dealer — broker-applicable only, silently ignored for dealers
     *
     * Eager loads (all loaded upfront to avoid N+1 on the resource layer):
     *   order → brand, dealer.user, broker
     *   product, transporter
     *   orderItem (with withSum('dispatches','no_of_bags') for pending qty calculation)
     */
    public function index(DispatchListRequest $request): JsonResponse
    {
        $user = $request->user(); // Resolved by auth:sanctum; never null here.

        // ── Role gate ──────────────────────────────────────────────────────────
        // Only Dealer and Broker accounts use the mobile dispatch listing.
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

        // ── Base query with eager loads ────────────────────────────────────────
        // Every relation that the DispatchResource will access is loaded upfront.
        // FK columns are always included so Laravel's relation binder can match rows.
        $query = DispatchManagement::with([
            // Parent order — delivery_address and broker_id both needed downstream.
            'order:id,unique_order_id,order_date,delivery_address,brand_id,dealer_id,broker_id',
            'order.brand:id,name',

            // Dealer profile + linked user account (user_id needed for dealer.user).
            'order.dealer:id,user_id,firm_shop_name',
            'order.dealer.user:id,name',

            // Broker user who manages this order.
            'order.broker:id,name',

            // Dispatched product.
            'product:id,name,unit',

            // Transporter (transport_id FK → users.id).
            'transporter:id,name,phone_no',

            // Order item — qty + unit_price for fulfilment context.
            // withSum('dispatches','no_of_bags') computes the TOTAL bags dispatched
            // for the same order item across ALL dispatch records (not just this one).
            // This lets the resource calculate pending_qty without extra queries.
            'orderItem' => function ($q) {
                $q->select('id', 'qty', 'unit_price')
                  ->withSum('dispatches', 'no_of_bags');
            },
        ]);

        // ── Role-based row visibility ──────────────────────────────────────────
        // $user is passed explicitly so this works under auth:sanctum without
        // relying on auth()->user() (which resolves via the 'web' guard).
        SalesScope::scopeDispatches($query, $user);

        // ── Filters ────────────────────────────────────────────────────────────

        // Exact dispatch record lookup by human-readable ID.
        // Accepts "DISP-000042", "DISP-42", or plain "42".
        // Non-numeric input (e.g. "DISP-ABC") resolves to 0 and matches nothing.
        if ($request->filled('dispatch_number')) {
            $raw       = trim((string) $request->input('dispatch_number'));
            $numericId = (int) ltrim(preg_replace('/^DISP-/i', '', $raw), '0');
            $query->where('id', $numericId > 0 ? $numericId : -1);
        }

        // Partial search on the human-readable order reference number.
        if ($request->filled('order_number')) {
            $query->whereHas('order', fn ($q) =>
                $q->where('unique_order_id', 'like', '%' . $request->input('order_number') . '%')
            );
        }

        // Map payment status string to the integer stored in the DB.
        if ($request->filled('status')) {
            $statusInt = match ($request->input('status')) {
                'paid'    => DispatchManagement::STATUS_PAID,
                'partial' => DispatchManagement::STATUS_PARTIAL,
                default   => DispatchManagement::STATUS_UNPAID,
            };
            $query->where('status', $statusInt);
        }

        // Dispatch date range (both bounds inclusive).
        if ($request->filled('date_from')) {
            $query->whereDate('dispatch_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('dispatch_date', '<=', $request->input('date_to'));
        }

        // Product filter — applicable to both roles.
        if ($request->filled('product_id')) {
            $query->where('product_id', (int) $request->input('product_id'));
        }

        // Brand filter — silently ignored for dealers (brand is already implicit
        // via the dealer_management record; adding it would double-scope and could
        // hide records).
        if (SalesScope::showBrandFilter($user) && $request->filled('brand_id')) {
            $query->whereHas('order', fn ($q) =>
                $q->where('brand_id', (int) $request->input('brand_id'))
            );
        }

        // Dealer filter — silently ignored for dealers (they can only ever see their
        // own records; the SalesScope already enforces this).
        if (SalesScope::showDealerFilter($user) && $request->filled('dealer_id')) {
            $query->whereHas('order', fn ($q) =>
                $q->where('dealer_id', (int) $request->input('dealer_id'))
            );
        }

        // ── Sort ───────────────────────────────────────────────────────────────
        // Latest dispatch date first, then by id for stable ordering on same date.
        $query->orderByDesc('dispatch_date')->orderByDesc('id');

        // ── Paginate ───────────────────────────────────────────────────────────
        $perPage = min(
            max(1, (int) $request->input('per_page', self::DEFAULT_PER_PAGE)),
            self::MAX_PER_PAGE
        );

        $paginator = $query->paginate($perPage);

        // ── Response ───────────────────────────────────────────────────────────
        return ApiResponse::success('Dispatches retrieved successfully.', [
            'dispatches' => DispatchResource::collection($paginator->items()),

            // Standard pagination envelope consumed by mobile clients.
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
