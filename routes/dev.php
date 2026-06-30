<?php

use App\Http\Controllers\Dev\EmailPreviewController;
use App\Http\Controllers\Dev\E2eSupportController;
use Illuminate\Support\Facades\Route;

Route::prefix('dev/email')->name('dev.email.')->group(function () {
    Route::get('/', [EmailPreviewController::class, 'index'])->name('index');
    Route::get('/login-otp', [EmailPreviewController::class, 'loginOtp'])->name('login-otp');
    Route::get('/dispatch-created', [EmailPreviewController::class, 'dispatchCreated'])->name('dispatch-created');
    Route::get('/dispatch-payment-changed', [EmailPreviewController::class, 'dispatchPaymentChanged'])->name('dispatch-payment-changed');
    Route::get('/dispatch-payment-pending', [EmailPreviewController::class, 'dispatchPaymentPending'])->name('dispatch-payment-pending');
    Route::get('/backup-created', [EmailPreviewController::class, 'backupCreated'])->name('backup-created');
});

Route::prefix('dev/e2e')->name('dev.e2e.')->group(function () {
    Route::post('/login', [E2eSupportController::class, 'login'])->name('login');
    Route::get('/latest-otp', [E2eSupportController::class, 'latestOtp'])->name('latest-otp');
});
