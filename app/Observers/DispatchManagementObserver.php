<?php

namespace App\Observers;

use App\Models\DispatchManagement;
use App\Support\DispatchEmailDelivery;

class DispatchManagementObserver
{
    public function created(DispatchManagement $dispatch): void
    {
        DispatchEmailDelivery::queueCreated($dispatch);
    }

    public function updated(DispatchManagement $dispatch): void
    {
        if ($dispatch->wasRecentlyCreated) {
            return;
        }

        if (! $dispatch->wasChanged('status') && ! $dispatch->wasChanged('partial_paid_amount')) {
            return;
        }

        DispatchEmailDelivery::queuePaymentChanged($dispatch);
    }
}
