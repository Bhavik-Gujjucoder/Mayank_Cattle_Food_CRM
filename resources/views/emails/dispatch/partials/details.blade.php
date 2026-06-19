@component('emails.dispatch.partials.section-card', ['title' => 'Order Details', 'marginTop' => '4px'])
    @include('emails.dispatch.partials.detail-row', [
        'label' => 'Order ID',
        'value' => e($payload['order']['unique_order_id']),
    ])
    @include('emails.dispatch.partials.detail-row', [
        'label' => 'Order Date',
        'value' => e($payload['order']['order_date']),
    ])
    @include('emails.dispatch.partials.detail-row', [
        'label' => 'Brand',
        'value' => e($payload['order']['brand_name']),
    ])
    @include('emails.dispatch.partials.detail-row', [
        'label' => 'Order Payment Status',
        'value' => view('emails.dispatch.partials.status-badge', [
            'status' => $payload['order']['payment_status'],
        ])->render(),
    ])
    @include('emails.dispatch.partials.detail-row', [
        'label' => 'Order Grand Total',
        'value' => e($payload['order']['grand_total']),
    ])
@endcomponent

@component('emails.dispatch.partials.section-card', ['title' => 'Order Line Item'])
    @include('emails.dispatch.partials.detail-row', [
        'label' => 'Product',
        'value' => e($payload['line_item']['product_name']),
    ])
    @include('emails.dispatch.partials.detail-row', [
        'label' => 'Ordered Qty',
        'value' => e($payload['line_item']['qty']),
    ])
    @include('emails.dispatch.partials.detail-row', [
        'label' => 'Unit Price',
        'value' => e($payload['line_item']['unit_price']),
    ])
    @include('emails.dispatch.partials.detail-row', [
        'label' => 'Line Total',
        'value' => e($payload['line_item']['line_total']),
    ])
    @include('emails.dispatch.partials.detail-row', [
        'label' => 'Dispatched',
        'value' => e($payload['line_item']['dispatched_qty']),
    ])
    @include('emails.dispatch.partials.detail-row', [
        'label' => 'Pending',
        'value' => e($payload['line_item']['pending_qty']),
    ])
@endcomponent

@component('emails.dispatch.partials.section-card', ['title' => 'Dispatch Details'])
    @include('emails.dispatch.partials.detail-row', [
        'label' => 'Dispatch Date',
        'value' => e($payload['dispatch']['dispatch_date']),
    ])
    @include('emails.dispatch.partials.detail-row', [
        'label' => 'Quantity',
        'value' => e($payload['dispatch']['qty']),
    ])
    @include('emails.dispatch.partials.detail-row', [
        'label' => 'Transporter',
        'value' => e($payload['dispatch']['transporter_name']),
    ])
    @include('emails.dispatch.partials.detail-row', [
        'label' => 'Truck Number',
        'value' => e($payload['dispatch']['truck_number']),
    ])
    @include('emails.dispatch.partials.detail-row', [
        'label' => 'Driver Contact',
        'value' => e($payload['dispatch']['driver_contact']),
    ])
    @include('emails.dispatch.partials.detail-row', [
        'label' => 'Payment Status',
        'value' => view('emails.dispatch.partials.status-badge', [
            'status' => $payload['dispatch']['payment_status'],
        ])->render(),
    ])
    @if (!empty($payload['dispatch']['partial_paid_amount']))
        @include('emails.dispatch.partials.detail-row', [
            'label' => 'Paid Amount',
            'value' => e($payload['dispatch']['partial_paid_amount']),
        ])
    @endif
@endcomponent

@component('emails.dispatch.partials.section-card', ['title' => 'Payment Receivable'])
    @include('emails.dispatch.partials.detail-row', [
        'label' => 'Base Amount',
        'value' => e($payload['receivable']['base_amount']),
    ])
    @include('emails.dispatch.partials.detail-row', [
        'label' => 'Accrued Late Fee',
        'value' => e($payload['receivable']['accrued_late_fee']),
    ])
    @include('emails.dispatch.partials.detail-row', [
        'label' => 'Total Receivable',
        'value' => e($payload['receivable']['total_receivable']),
    ])
    @include('emails.dispatch.partials.detail-row', [
        'label' => 'Balance Due',
        'value' => e($payload['receivable']['balance_due']),
        'emphasize' => true,
    ])
@endcomponent
