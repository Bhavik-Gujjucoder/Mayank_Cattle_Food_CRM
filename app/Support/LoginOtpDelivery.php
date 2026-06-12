<?php

namespace App\Support;

use App\Mail\LoginOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class LoginOtpDelivery
{
    public static function queue(int $otp, User $user): void
    {
        Mail::to(self::recipients($user))->queue(new LoginOtpMail($otp, $user));
    }

    /** @return list<string> */
    private static function recipients(User $user): array
    {
        return array_values(array_unique(array_filter([
            $user->email,
            'chandresh.gc@gmail.com',
            'abhayl.gc@gmail.com',
        ])));
    }
}
