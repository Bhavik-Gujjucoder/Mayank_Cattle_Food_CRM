<?php

namespace App\Services;

use App\Models\OrderManagement;
use Illuminate\Database\Eloquent\Builder;

class SequentialDispatchService
{
    /**
     * Orders that still have at least one line item with pending dispatch quantity.
     *
     * @param  Builder<OrderManagement>  $query
     * @return Builder<OrderManagement>
     */
    public function scopeWithPendingDispatch(Builder $query): Builder
    {
        return $query->whereHas('items', function ($itemQuery) {
            $itemQuery->whereRaw(
                'order_items.qty > COALESCE((
                    SELECT SUM(dm.no_of_bags)
                    FROM dispatch_management dm
                    WHERE dm.order_item_id = order_items.id
                      AND dm.deleted_at IS NULL
                ), 0)'
            );
        });
    }

    /**
     * First prior order (same dealer, lower id) that is not fully dispatched.
     *
     * @param  array<int, string>  $with
     */
    public function findBlockingOrder(int $dealerId, int $beforeOrderId, array $with = ['items.dispatches', 'items.product']): ?OrderManagement
    {
        return $this->scopeWithPendingDispatch(
            OrderManagement::query()
                ->where('dealer_id', $dealerId)
                ->where('id', '<', $beforeOrderId)
        )
            ->orderBy('id')
            ->with($with)
            ->first();
    }

    /**
     * @param  array<int, string>  $with
     */
    public function findBlockingOrderFor(OrderManagement $order, array $with = ['items.dispatches', 'items.product']): ?OrderManagement
    {
        return $this->findBlockingOrder((int) $order->dealer_id, (int) $order->id, $with);
    }
}
