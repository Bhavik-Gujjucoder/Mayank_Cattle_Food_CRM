<?php

namespace App\Support;

use App\Mail\DispatchCreatedMail;
use App\Mail\DispatchPaymentPendingReminderMail;
use App\Mail\DispatchPaymentStatusChangedMail;
use App\Models\DispatchManagement;

class DispatchEmailDelivery
{
    public static function queueCreated(DispatchManagement $dispatch): void
    {
        $email = self::dealerEmail($dispatch);

        if ($email === null) {
            return;
        }

        $payload = app(DispatchEmailPresenter::class)->forDispatch($dispatch);

        EmailDelivery::queue($email, new DispatchCreatedMail($payload));
    }

    public static function queuePaymentChanged(DispatchManagement $dispatch): void
    {
        $email = self::dealerEmail($dispatch);

        if ($email === null) {
            return;
        }

        $presenter = app(DispatchEmailPresenter::class);
        $payload = $presenter->forDispatch(
            $dispatch,
            $presenter->previousPaymentExtras($dispatch)
        );

        EmailDelivery::queue($email, new DispatchPaymentStatusChangedMail($payload));
    }

    public static function queuePaymentPendingReminder(
        DispatchManagement $dispatch,
        float $lateFeeAddedToday
    ): void {
        $dealerEmail = self::dealerEmail($dispatch);
        $companyEmail = trim(getSetting('company_email'));

        $to = $dealerEmail;
        $cc = [];

        if ($to === null && $companyEmail !== '') {
            $to = $companyEmail;
        } elseif ($to !== null && $companyEmail !== '' && strtolower($to) !== strtolower($companyEmail)) {
            $cc = [$companyEmail];
        }

        if ($to === null || trim($to) === '') {
            return;
        }

        $payload = app(DispatchEmailPresenter::class)->forPaymentPendingReminder($dispatch, $lateFeeAddedToday);

        EmailDelivery::queue($to, new DispatchPaymentPendingReminderMail($payload), cc: $cc);
    }

    private static function dealerEmail(DispatchManagement $dispatch): ?string
    {
        $dispatch->loadMissing('order.dealer.user');

        $email = $dispatch->order?->dealer?->user?->email;

        if ($email === null || trim($email) === '') {
            return null;
        }

        return trim($email);
    }
}
