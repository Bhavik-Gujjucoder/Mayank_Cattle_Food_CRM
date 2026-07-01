<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the mobile_otps table used by the Mobile API OTP verification flow.
 *
 * This table is SEPARATE from the web app's OTP mechanism (users.otp_code /
 * users.otp_expires_at). Modifying this migration will NOT affect web login.
 *
 * Lifecycle of a row:
 *   1. Created when a mobile email login succeeds (OtpService::createAndSend).
 *   2. attempts incremented on each failed verification.
 *   3. resend_count incremented and last_sent_at updated on each resend.
 *   4. used_at set on successful verification; row is then inert.
 *   5. Expired / used rows should be pruned periodically (artisan schedule).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_otps', function (Blueprint $table) {
            $table->id();

            // The user this OTP belongs to. Cascade-delete keeps the table clean.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // bcrypt hash of the 6-digit numeric OTP — never stored in plain text.
            $table->string('otp', 255);

            // Absolute expiry timestamp. OTPs are rejected after this point.
            $table->timestamp('expires_at');

            // Set to now() when the OTP is successfully verified. Prevents reuse.
            $table->timestamp('used_at')->nullable();

            // Incremented on each wrong OTP attempt; locked when >= config('otp.max_attempts').
            $table->tinyInteger('attempts')->default(0);

            // Incremented on each resend; capped at config('otp.max_resend_attempts').
            $table->tinyInteger('resend_count')->default(0);

            // Updated each time the OTP is sent or resent; drives cooldown enforcement.
            $table->timestamp('last_sent_at')->nullable();

            $table->timestamps();

            // Composite index for the most common query: fetch pending OTP by user.
            $table->index(['user_id', 'used_at'], 'mobile_otps_user_pending_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_otps');
    }
};
