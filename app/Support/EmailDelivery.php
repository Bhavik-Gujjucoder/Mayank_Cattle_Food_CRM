<?php

namespace App\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailDelivery
{
    public static function queue(
        string|array $to,
        Mailable $mailable,
        array $cc = [],
        array $bcc = []
    ): bool {
        return self::deliver($to, $mailable, $cc, $bcc, queued: true);
    }

    public static function send(
        string|array $to,
        Mailable $mailable,
        array $cc = [],
        array $bcc = []
    ): bool {
        return self::deliver($to, $mailable, $cc, $bcc, queued: false);
    }

    /**
     * @param  string|array<int, string>  $to
     * @param  array<int, string>  $cc
     * @param  array<int, string>  $bcc
     */
    private static function deliver(
        string|array $to,
        Mailable $mailable,
        array $cc,
        array $bcc,
        bool $queued
    ): bool {
        $to = self::normalizeRecipients($to);
        $cc = self::normalizeRecipients($cc);
        $bcc = self::normalizeRecipients($bcc);

        if ($to === []) {
            Log::info('Email skipped: no valid recipients.', [
                'mailable' => $mailable::class,
            ]);

            return false;
        }

        $mail = Mail::to($to);

        if ($cc !== []) {
            $mail->cc($cc);
        }

        if ($bcc !== []) {
            $mail->bcc($bcc);
        }

        if ($queued) {
            $mail->queue($mailable);
        } else {
            $mail->send($mailable);
        }

        return true;
    }

    /**
     * @param  string|array<int, string|null>  $recipients
     * @return list<string>
     */
    private static function normalizeRecipients(string|array $recipients): array
    {
        $list = is_array($recipients) ? $recipients : [$recipients];

        $normalized = [];

        foreach ($list as $recipient) {
            if (! is_string($recipient)) {
                continue;
            }

            $email = trim($recipient);

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $normalized[] = strtolower($email);
        }

        return array_values(array_unique($normalized));
    }
}
