{{ $order->order_unique_id }}{{ filled($order->supplier_order_id) ? ' | ' . $order->supplier_order_id : '' }}{{ $order->supplier ? ' | ' . $order->supplier->name : '' }}
