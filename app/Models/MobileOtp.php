<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a single Mobile API OTP session.
 *
 * One row is created per email login. It tracks the plain-text OTP, expiry,
 * verification attempts, and resend history. The row is invalidated (used_at
 * set) on successful verification, or deleted when a resend is issued.
 *
 * @property int         $id
 * @property int         $user_id
 * @property string      $otp           plain-text 6-digit OTP
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon|null $used_at
 * @property int         $attempts
 * @property int         $resend_count
 * @property \Carbon\Carbon|null $last_sent_at
 */
class MobileOtp extends Model
{
    protected $fillable = [
        'user_id',
        'otp',
        'expires_at',
        'used_at',
        'attempts',
        'resend_count',
        'last_sent_at',
    ];

    protected $casts = [
        'expires_at'   => 'datetime',
        'used_at'      => 'datetime',
        'last_sent_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Helper methods ───────────────────────────────────────────────────────

    /** True if the OTP validity window has passed. */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /** True if this OTP has already been successfully verified. */
    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    /** Number of verification attempts still allowed before the session locks. */
    public function attemptsRemaining(): int
    {
        return max(0, config('otp.max_attempts') - $this->attempts);
    }

    /** Number of additional resends still permitted before the session locks. */
    public function resendsRemaining(): int
    {
        return max(0, config('otp.max_resend_attempts') - $this->resend_count);
    }
}
