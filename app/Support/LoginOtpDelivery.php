<?php

namespace App\Support;

use App\Mail\LoginOtpMail;
use App\Models\User;

class LoginOtpDelivery
{
    public static function queue(int $otp, User $user): void
    {
        /*EmailDelivery::queue(
            self::recipients($user),
            new LoginOtpMail($otp, $user)
        );*/
        EmailDelivery::send(
            self::recipients($user),
            new LoginOtpMail($otp, $user)
        );
    }

    /** @return list<string> */
    private static function recipients(User $user): array
    {
        return array_values(array_unique(array_filter([
            $user->email,
            'chandresh.gc@gmail.com',
            'abhayl.gc@gmail.com'
        ])));
    }
}
