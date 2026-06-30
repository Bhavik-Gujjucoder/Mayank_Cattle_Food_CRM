<?php

namespace App\Support;

use App\Models\User;

class EmailPreviewSamples
{
    public static function user(): User
    {
        return User::query()->first() ?? new User([
            'name'  => 'Sample Dealer',
            'email' => 'dealer@example.com',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function dispatchPayload(array $extras = []): array
    {
        return array_merge([
            'dealer_name' => 'Rajesh Traders',
            'order' => [
                'unique_order_id' => 'ORD-2026-0042',
                'order_date'      => '15 Jun 2026',
                'brand_name'      => 'Mayank Gold',
                'payment_status'  => 'Partial Payment',
                'grand_total'     => '₹ 1,25,000.00',
            ],
            'line_item' => [
                'product_name'   => 'Cattle Feed Premium 50kg',
                'qty'            => '200 Bags',
                'unit_price'     => '₹ 625.00',
                'line_total'     => '₹ 1,25,000.00',
                'dispatched_qty' => '120 Bags',
                'pending_qty'    => '80 Bags',
            ],
            'dispatch' => [
                'dispatch_date'       => '17 Jun 2026',
                'qty'                 => '50 Bags',
                'transporter_name'    => 'Sharma Transport',
                'truck_number'        => 'GJ-01-AB-1234',
                'driver_contact'      => '9876543210',
                'payment_status'      => 'Unpaid',
                'partial_paid_amount' => null,
            ],
            'receivable' => [
                'base_amount'      => '₹ 31,250.00',
                'accrued_late_fee' => '₹ 500.00',
                'total_receivable' => '₹ 31,750.00',
                'balance_due'      => '₹ 31,750.00',
            ],
        ], $extras);
    }

    /**
     * @return array<string, mixed>
     */
    public static function backupPayload(): array
    {
        return [
            'filename'        => 'backup_2026-06-17_120000.zip.enc',
            'passphrase'      => 'sample-passphrase-abc123',
            'size_label'      => '12.4 MB',
            'created_at'      => now()->format('Y-m-d H:i:s'),
            'created_by_name' => 'System Admin',
        ];
    }
}
