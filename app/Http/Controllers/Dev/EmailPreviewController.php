<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Mail\BackupCreatedMail;
use App\Mail\DispatchCreatedMail;
use App\Mail\DispatchPaymentPendingReminderMail;
use App\Mail\DispatchPaymentStatusChangedMail;
use App\Mail\LoginOtpMail;
use App\Support\EmailPreviewSamples;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EmailPreviewController extends Controller
{
    public function __construct()
    {
        if (! app()->environment('local')) {
            throw new NotFoundHttpException();
        }
    }

    public function index(): View
    {
        return view('dev.email-preview-index', [
            'previews' => [
                ['label' => 'Login OTP', 'route' => route('dev.email.login-otp')],
                ['label' => 'Dispatch Created', 'route' => route('dev.email.dispatch-created')],
                ['label' => 'Dispatch Payment Status Changed', 'route' => route('dev.email.dispatch-payment-changed')],
                ['label' => 'Dispatch Payment Pending Reminder', 'route' => route('dev.email.dispatch-payment-pending')],
                ['label' => 'System Backup Created', 'route' => route('dev.email.backup-created')],
            ],
        ]);
    }

    public function loginOtp(): LoginOtpMail
    {
        return new LoginOtpMail(123456, EmailPreviewSamples::user());
    }

    public function dispatchCreated(): DispatchCreatedMail
    {
        return new DispatchCreatedMail(EmailPreviewSamples::dispatchPayload());
    }

    public function dispatchPaymentChanged(): DispatchPaymentStatusChangedMail
    {
        return new DispatchPaymentStatusChangedMail(
            EmailPreviewSamples::dispatchPayload([
                'previous_payment_status'      => 'Unpaid',
                'previous_partial_paid_amount' => null,
                'dispatch' => array_merge(
                    EmailPreviewSamples::dispatchPayload()['dispatch'],
                    [
                        'payment_status'      => 'Partial Payment',
                        'partial_paid_amount' => '₹ 15,000.00',
                    ]
                ),
            ])
        );
    }

    public function dispatchPaymentPending(): DispatchPaymentPendingReminderMail
    {
        return new DispatchPaymentPendingReminderMail(
            EmailPreviewSamples::dispatchPayload([
                'late_fee_added_today' => '₹ 250.00',
                'overdue_days'         => 5,
            ])
        );
    }

    public function backupCreated(): BackupCreatedMail
    {
        $payload = EmailPreviewSamples::backupPayload();

        return new BackupCreatedMail(
            backupFilename: $payload['filename'],
            backupPath: storage_path('app/nonexistent-preview-backup.zip.enc'),
            payload: $payload,
        );
    }
}
