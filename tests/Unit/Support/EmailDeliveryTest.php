<?php

use App\Mail\LoginOtpMail;
use App\Models\User;
use App\Support\EmailDelivery;
use Illuminate\Support\Facades\Mail;

// Each test fakes mail to capture deliveries
beforeEach(function () {
    Mail::fake();
});

// ─────────────────────────────────────────────

describe('queue', function () {
    it('returns true and queues mail for a valid email address', function () {
        $user     = User::factory()->create(['email' => 'valid@example.com', 'status' => 1]);
        $mailable = new LoginOtpMail(123456, $user);

        $result = EmailDelivery::queue('valid@example.com', $mailable);

        expect($result)->toBeTrue();
        Mail::assertQueued(LoginOtpMail::class);
    });

    it('returns false and skips mail when email is empty string', function () {
        $user     = User::factory()->create(['status' => 1]);
        $mailable = new LoginOtpMail(123456, $user);

        $result = EmailDelivery::queue('', $mailable);

        expect($result)->toBeFalse();
        Mail::assertNothingQueued();
    });

    it('returns false and skips mail when email is invalid format', function () {
        $user     = User::factory()->create(['status' => 1]);
        $mailable = new LoginOtpMail(123456, $user);

        $result = EmailDelivery::queue('not-an-email', $mailable);

        expect($result)->toBeFalse();
        Mail::assertNothingQueued();
    });

    it('lowercases the recipient email before sending', function () {
        $user     = User::factory()->create(['status' => 1]);
        $mailable = new LoginOtpMail(123456, $user);

        EmailDelivery::queue('USER@EXAMPLE.COM', $mailable);

        Mail::assertQueued(LoginOtpMail::class, function ($mail) {
            return collect($mail->to)->pluck('address')->contains('user@example.com');
        });
    });

    it('deduplicates the same email appearing multiple times', function () {
        $user     = User::factory()->create(['status' => 1]);
        $mailable = new LoginOtpMail(123456, $user);

        // Pass array with duplicates — should still queue exactly one mail
        $result = EmailDelivery::queue(['dup@example.com', 'dup@example.com'], $mailable);

        expect($result)->toBeTrue();
        Mail::assertQueued(LoginOtpMail::class, 1);
    });

    it('accepts an array of multiple valid recipients', function () {
        $user     = User::factory()->create(['status' => 1]);
        $mailable = new LoginOtpMail(123456, $user);

        $result = EmailDelivery::queue(['a@example.com', 'b@example.com'], $mailable);

        expect($result)->toBeTrue();
        Mail::assertQueued(LoginOtpMail::class);
    });

    it('skips null entries in recipient array', function () {
        $user     = User::factory()->create(['status' => 1]);
        $mailable = new LoginOtpMail(123456, $user);

        // Mix of valid and null/empty
        $result = EmailDelivery::queue(['valid@example.com', '', 'bad-email'], $mailable);

        expect($result)->toBeTrue(); // valid one still succeeds
        Mail::assertQueued(LoginOtpMail::class);
    });
});

// ─────────────────────────────────────────────

describe('send', function () {
    it('returns true and sends mail synchronously for a valid email', function () {
        $user     = User::factory()->create(['email' => 'sync@example.com', 'status' => 1]);
        $mailable = new LoginOtpMail(123456, $user);

        $result = EmailDelivery::send('sync@example.com', $mailable);

        expect($result)->toBeTrue();
        Mail::assertQueued(LoginOtpMail::class);
    });

    it('returns false for an invalid email', function () {
        $user     = User::factory()->create(['status' => 1]);
        $mailable = new LoginOtpMail(123456, $user);

        $result = EmailDelivery::send('invalid', $mailable);

        expect($result)->toBeFalse();
        Mail::assertNothingSent();
    });
});
