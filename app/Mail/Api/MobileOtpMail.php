<?php

namespace App\Mail\Api;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mobile API OTP email.
 *
 * Sent (queued) when a user logs in via email on the mobile app. This is
 * intentionally separate from the web app's LoginOtpMail so that changes
 * to either flow (branding, expiry wording, etc.) remain independent.
 *
 * The $otp value matches the plain-text value stored in mobile_otps.
 * Never log the OTP outside of the email dispatch.
 */
class MobileOtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  int   $otp   Plain-text 6-digit OTP to display in the email body.
     * @param  User  $user  Recipient — used for personalisation (name, email).
     */
    public function __construct(
        public readonly int $otp,
        public readonly User $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Mobile App Verification OTP',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.api.mobile_otp',
        );
    }
}
