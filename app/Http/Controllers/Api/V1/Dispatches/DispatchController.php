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
 * API 8 — Dispatch History (Dealer / Broker only).
 *
 * Purpose:
 *   Return a paginated, filterable list of dispatch records for the
 *   authenticated user. Access is restricted to the Dealer and Broker roles.
 *
 * Role-based data visibility (mirrors DispatchManagementController):
 *   • Dealer — sees dispatches linked to orders where dealer_management.user_id = auth user id.
 *   • Broker — sees dispatches linked to orders where broker_id = auth user id.
 *
 * @see \App\Support\SalesScope::scopeDispatches()
 * @see \App\Http\Controllers\DispatchManagementController::index()
 */
class DispatchController extends Controller
{
    private const ALLOWED_ROLES = ['dealer', 'broker'];

    private const DEFAULT_PER_PAGE = 15;
    private const MAX_PER_PAGE     = 100;

    /**
     * Return a paginated list of dispatch records accessible to the authenticated user.
     *
     * Filters (all optional):
     *   - order_number: partial match on the parent order's unique_order_id
     *   - status:       payment status string (unpaid | paid | partial)
     *   - date_from:    dispatch_date >= value (Y-m-d)
     *   - date_to:      dispatch_date <= value (Y-m-d)
     *   - brand_id:     filter by brand — applicable to brokers only, silently ignored for dealers
     *   - product_id:   filter by dispatched product
     *   - dealer_id:    filter by dealer — applicable to brokers only, silently ignored for dealers
     */
    public function index(DispatchListRequest $request): JsonResponse
    {
        $user = $request->user();

        // ── Role gate ──────────────────────────────────────────────────────────
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
        // All FK columns are included so Laravel's relation binder can match them.
        $query = DispatchManagement::with([
            // Parent order: include brand_id + dealer_id for nested relations
            'order:id,unique_order_id,order_date,brand_id,dealer_id',
            'order.brand:id,name',
            'order.dealer:id,user_id,firm_shop_name',   // user_id needed for dealer.user
            'order.dealer.user:id,name',

            // Dispatched product
            'product:id,name,unit',

            // Transporter (transport_id FK → users.id)
            'transporter:id,name,phone_no',
        ]);

        // ── Role-based row visibility ──────────────────────────────────────────
        // $user is passed explicitly to work correctly under auth:sanctum (the
        // web guard's auth()->user() is not active during API requests).
        SalesScope::scopeDispatches($query, $user);

        // ── Filters ────────────────────────────────────────────────────────────

        if ($request->filled('order_number')) {
            $query->whereHas('order', fn ($q) =>
                $q->where('unique_order_id', 'like', '%' . $request->input('order_number') . '%')
            );
        }

        // Map string status to the integer constant used in the DB.
        if ($request->filled('status')) {
            $statusInt = match ($request->input('status')) {
                'paid'    => DispatchManagement::STATUS_PAID,
                'partial' => DispatchManagement::STATUS_PARTIAL,
                default   => DispatchManagement::STATUS_UNPAID,
            };
            $query->where('status', $statusInt);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('dispatch_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('dispatch_date', '<=', $request->input('date_to'));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', (int) $request->input('product_id'));
        }

        // Brand filter — silently ignored for dealers (their brand is implicit).
        if (SalesScope::showBrandFilter($user) && $request->filled('brand_id')) {
            $query->whereHas('order', fn ($q) =>
                $q->where('brand_id', (int) $request->input('brand_id'))
            );
        }

        // Dealer filter — silently ignored for dealers (they can only see their own).
        if (SalesScope::showDealerFilter($user) && $request->filled('dealer_id')) {
            $query->whereHas('order', fn ($q) =>
                $q->where('dealer_id', (int) $request->input('dealer_id'))
            );
        }

        // ── Sort ───────────────────────────────────────────────────────────────
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
