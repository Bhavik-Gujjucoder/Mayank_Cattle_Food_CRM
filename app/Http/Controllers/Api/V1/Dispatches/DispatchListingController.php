<?php

namespace App\Http\Controllers\Api\V1\Dispatches;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Dispatches\DispatchListingRequest;
use App\Http\Resources\Api\V1\DispatchResource;
use App\Models\DispatchManagement;
use App\Support\Api\ApiResponse;
use App\Support\SalesScope;
use Illuminate\Http\JsonResponse;

/**
 * API 10 — Dispatch Listing (Dealer / Broker — mobile only).
 *
 * ──────────────────────────────────────────────────────────────────────────
 * PURPOSE
 *   Dedicated, standalone endpoint that returns a paginated and filterable
 *   list of dispatch records for the authenticated mobile user.
 *
 *   This controller has ONE responsibility: serving the Dispatch Listing
 *   screen in the mobile application. It does not handle dispatch creation,
 *   updates, deletions, or any other operation.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * AUTHENTICATION
 *   Requires a valid Laravel Sanctum Bearer token issued during the mobile
 *   login flow. The auth:sanctum middleware on the route enforces this before
 *   the request reaches this controller.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * ROLE-BASED DATA VISIBILITY
 *   The role check below restricts access to Dealer and Broker accounts only.
 *   Staff / Admin users must use the web application; they receive a 403 here.
 *
 *   Once the role is confirmed, SalesScope::scopeDispatches() constrains the
 *   query to records the authenticated user is permitted to see:
 *     • Dealer  → dispatches where dealer_management.user_id = auth user id
 *     • Broker  → dispatches where order_management.broker_id = auth user id
 *
 *   This mirrors the business logic in the web app's
 *   DispatchManagementController::index() and ensures both surfaces are
 *   consistent.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * EAGER LOADING STRATEGY (N+1 prevention)
 *   All relations consumed by DispatchResource are loaded in a single batch:
 *     order           — parent order (unique_order_id, delivery_address, dates)
 *     order.brand     — brand name on the order
 *     order.dealer    — dealer profile + linked user account
 *     order.broker    — broker user who manages the order
 *     product         — dispatched product (name, unit)
 *     transporter     — transport company (name, phone)
 *     orderItem       — quantity + unit_price for fulfilment context;
 *                       withSum('dispatches','no_of_bags') adds a sub-select
 *                       that totals bags across ALL dispatch events for the
 *                       same order item — avoids loading every dispatch record.
 *
 * @see \App\Http\Requests\Api\V1\Dispatches\DispatchListingRequest (validation)
 * @see \App\Http\Resources\Api\V1\DispatchResource                 (response shape)
 * @see \App\Support\SalesScope::scopeDispatches()                  (row-level security)
 * @see \App\Http\Controllers\DispatchManagementController::index() (web-app equivalent)
 */
class DispatchListingController extends Controller
{
    /**
     * Spatie role names permitted to call this endpoint.
     * All other roles receive a 403 before the DB query runs.
     */
    private const ALLOWED_ROLES = ['dealer', 'broker'];

    /** Default records per page when per_page is omitted. */
    private const DEFAULT_PER_PAGE = 15;

    /** Hard ceiling on per_page to prevent excessively large payloads. */
    private const MAX_PER_PAGE = 100;

    /**
     * GET /api/v1/dispatches
     *
     * Return a paginated, role-scoped list of dispatch records.
     *
     * Flow:
     *   1. Sanctum resolves the authenticated user from the Bearer token.
     *   2. Role gate: reject non-Dealer/Broker users with 403.
     *   3. Build the base query with all required eager loads.
     *   4. Apply SalesScope to constrain rows to this user's records.
     *   5. Apply any optional filters from the request query-string.
     *   6. Sort (latest dispatch date first), paginate, and return JSON.
     *
     * @param  DispatchListingRequest  $request  Validated filter + pagination input
     * @return JsonResponse
     */
    public function list(DispatchListingRequest $request): JsonResponse
    {
        /*
         * ── Step 1: Resolve the authenticated user ────────────────────────────
         * auth:sanctum resolves the user from the Bearer token before this
         * method is called, so $request->user() is always non-null here.
         */
        $user = $request->user();

        /*
         * ── Step 2: Role gate ─────────────────────────────────────────────────
         * Mobile dispatch listing is exclusively for Dealer and Broker accounts.
         * Staff / admin users manage dispatches through the web application.
         * We walk through ALLOWED_ROLES so that users with multiple roles (rare
         * but possible) are handled correctly without bypassing the gate.
         */
        $userRole = null;
        foreach (self::ALLOWED_ROLES as $role) {
            if ($user->hasRole($role)) {
                $userRole = $role;
                break;
            }
        }

        if ($userRole === null) {
            return ApiResponse::error(
                'Access denied. Only Dealer and Broker accounts may access the dispatch listing.',
                null,
                403
            );
        }

        /*
         * ── Step 3: Base query with eager loads ───────────────────────────────
         * All columns referenced by DispatchResource are specified explicitly
         * in each select list. FK columns (broker_id, dealer_id, user_id, etc.)
         * must always be included so Laravel's relation binder can match rows.
         */
        $query = DispatchManagement::with([

            /*
             * Parent order — delivery_address lets the mobile app display the
             * delivery location; broker_id is needed to load order.broker.
             */
            'order:id,unique_order_id,order_date,delivery_address,brand_id,dealer_id,broker_id',

            // Brand associated with the order.
            'order.brand:id,name',

            /*
             * Dealer profile linked to the order.
             * user_id must be in the select so order.dealer.user can be resolved.
             */
            'order.dealer:id,user_id,firm_shop_name',

            // User account behind the dealer profile (provides the dealer's name).
            'order.dealer.user:id,name',

            // Broker user who placed / manages this order.
            'order.broker:id,name',

            // Dispatched product (name and unit for display).
            'product:id,name,unit',

            /*
             * Transporter — transport_id FK maps to the users table.
             * phone_no lets the mobile app show contact details.
             */
            'transporter:id,name,phone_no',

            /*
             * Order item — qty and unit_price for fulfilment context.
             *
             * withSum('dispatches','no_of_bags') attaches a sub-select that sums
             * no_of_bags across ALL dispatch records for this order item.
             * The result (dispatches_sum_no_of_bags) is used in DispatchResource
             * to compute total_dispatched_qty and pending_qty without loading
             * every individual dispatch record, which would cause N+1.
             */
            'orderItem' => function ($q) {
                $q->select('id', 'qty', 'unit_price')
                  ->withSum('dispatches', 'no_of_bags');
            },
        ]);

        /*
         * ── Step 4: Role-based row visibility ─────────────────────────────────
         * SalesScope::scopeDispatches() adds WHERE clauses that restrict rows
         * to the authenticated user's own dispatches. The $user parameter is
         * passed explicitly because auth()->user() resolves via the 'web' guard
         * and would return null under the 'api' (Sanctum) guard.
         *
         *   Dealer → WHERE order.dealer_management.user_id = $user->id
         *   Broker → WHERE order_management.broker_id = $user->id
         */
        SalesScope::scopeDispatches($query, $user);

        /*
         * ── Step 5: Optional filters ──────────────────────────────────────────
         * All filters are applied on top of the role-scoped base query, so a
         * Dealer can never reach records owned by another Dealer even by
         * passing a dealer_id or dispatch_number that maps to another record.
         */

        /*
         * Dispatch number lookup — accepts DISP-000042, DISP-42, or plain 42.
         * Strips the optional "DISP-" prefix and leading zeros to extract the
         * integer ID. Non-numeric input (e.g. "DISP-ABC") resolves to 0 and
         * matches nothing (query returns empty, no error).
         */
        if ($request->filled('dispatch_number')) {
            $raw       = trim((string) $request->input('dispatch_number'));
            $numericId = (int) ltrim(preg_replace('/^DISP-/i', '', $raw), '0');
            $query->where('id', $numericId > 0 ? $numericId : -1);
        }

        /*
         * Order number — partial match on unique_order_id (e.g. "ALPHA" matches
         * "ORD/ALPHA/001"). Runs a sub-select to avoid a join on the main table.
         */
        if ($request->filled('order_number')) {
            $query->whereHas('order', fn ($q) =>
                $q->where('unique_order_id', 'like', '%' . $request->input('order_number') . '%')
            );
        }

        /*
         * Payment status — the API accepts human-readable strings; the DB stores
         * integers (0 = unpaid, 1 = paid, 2 = partial). The match maps between
         * the two representations. The default arm keeps "unpaid" as the fallback
         * since the validation rule already guarantees only the three valid values
         * reach this point.
         */
        if ($request->filled('status')) {
            $statusInt = match ($request->input('status')) {
                'paid'    => DispatchManagement::STATUS_PAID,
                'partial' => DispatchManagement::STATUS_PARTIAL,
                default   => DispatchManagement::STATUS_UNPAID,
            };
            $query->where('status', $statusInt);
        }

        // Dispatch date range — both bounds are inclusive.
        if ($request->filled('date_from')) {
            $query->whereDate('dispatch_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('dispatch_date', '<=', $request->input('date_to'));
        }

        // Product filter — applicable to both Dealer and Broker roles.
        if ($request->filled('product_id')) {
            $query->where('product_id', (int) $request->input('product_id'));
        }

        /*
         * Brand filter — silently ignored for Dealer accounts.
         * A dealer's accessible orders are already scoped to their brand via the
         * dealer_management record; applying brand_id on top would double-scope
         * and could incorrectly hide records.
         */
        if (SalesScope::showBrandFilter($user) && $request->filled('brand_id')) {
            $query->whereHas('order', fn ($q) =>
                $q->where('brand_id', (int) $request->input('brand_id'))
            );
        }

        /*
         * Dealer filter — silently ignored for Dealer accounts.
         * SalesScope already restricts a dealer to their own records; passing
         * a dealer_id would only make sense for Broker accounts who can see
         * multiple dealers.
         */
        if (SalesScope::showDealerFilter($user) && $request->filled('dealer_id')) {
            $query->whereHas('order', fn ($q) =>
                $q->where('dealer_id', (int) $request->input('dealer_id'))
            );
        }

        /*
         * ── Step 6: Sort ──────────────────────────────────────────────────────
         * Most-recent dispatch date first, then descending by primary key to
         * produce a stable, deterministic order when dates are identical.
         */
        $query->orderByDesc('dispatch_date')->orderByDesc('id');

        /*
         * ── Step 7: Paginate ──────────────────────────────────────────────────
         * Clamp per_page between 1 and MAX_PER_PAGE. The validation rule already
         * enforces this range, but we clamp defensively here as well.
         */
        $perPage = min(
            max(1, (int) $request->input('per_page', self::DEFAULT_PER_PAGE)),
            self::MAX_PER_PAGE
        );

        $paginator = $query->paginate($perPage);

        /*
         * ── Step 8: Response ──────────────────────────────────────────────────
         * DispatchResource transforms each DispatchManagement model into the
         * standardized JSON shape. Pagination metadata is returned alongside
         * the dispatch array in a consistent envelope.
         */
        return ApiResponse::success('Dispatch listing retrieved successfully.', [
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
