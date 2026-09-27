<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public API routes (with web session support for role detection)
Route::prefix('chatbot')->middleware('web')->group(function () {
    Route::post('/message', [App\Http\Controllers\Api\ChatbotController::class, 'message']);
    Route::get('/context', [App\Http\Controllers\Api\ChatbotController::class, 'context']);
});

// Tour public route
Route::get('/tour', [App\Http\Controllers\Api\TourAssetController::class, 'publicIndex']);

// Availability check (public)
Route::get('/availability', [App\Http\Controllers\Api\BookingController::class, 'availability']);

// Authenticated API routes (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [App\Http\Controllers\Api\AuthController::class, 'me']);
    Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::put('/profile', [App\Http\Controllers\Api\AuthController::class, 'updateProfile']);
    Route::put('/password', [App\Http\Controllers\Api\AuthController::class, 'updatePassword']);

    // Bookings (Tourist API)
    Route::apiResource('bookings', App\Http\Controllers\Api\BookingController::class)->except(['index']);
    Route::get('/my-bookings', [App\Http\Controllers\Api\BookingController::class, 'myBookings']);

    // Payments
    Route::post('/payments/process', [App\Http\Controllers\Api\PaymentController::class, 'process']);
    Route::get('/payments/{payment}', [App\Http\Controllers\Api\PaymentController::class, 'show']);
    Route::post('/payments/{payment}/upload-proof', [App\Http\Controllers\Api\PaymentController::class, 'uploadProof']);

    // Reviews
    Route::post('/reviews', [App\Http\Controllers\Api\ReviewController::class, 'store']);
    Route::get('/my-reviews', [App\Http\Controllers\Api\ReviewController::class, 'myReviews']);

    // Notifications
    Route::get('/notifications', [App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markRead']);
    Route::put('/notifications/read-all', [App\Http\Controllers\Api\NotificationController::class, 'markAllRead']);
});



// Admin-only API routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // Chatbot intent management
    Route::apiResource('chatbot-intents', App\Http\Controllers\Api\ChatbotIntentController::class);
    Route::get('/chatbot-logs', [App\Http\Controllers\Api\ChatbotIntentController::class, 'logs']);

    // Tour assets management
    Route::apiResource('tour-assets', App\Http\Controllers\Api\TourAssetController::class);

    // System settings
    Route::get('/settings', [App\Http\Controllers\Api\SystemSettingController::class, 'index']);
    Route::put('/settings', [App\Http\Controllers\Api\SystemSettingController::class, 'update']);

    // Reports
    Route::get('/reports/revenue', [App\Http\Controllers\Api\ReportController::class, 'revenue']);
    Route::get('/reports/occupancy', [App\Http\Controllers\Api\ReportController::class, 'occupancy']);
    Route::get('/reports/accommodations', [App\Http\Controllers\Api\ReportController::class, 'popularAccommodations']);
    Route::get('/reports/cancellations', [App\Http\Controllers\Api\ReportController::class, 'cancellations']);
    Route::get('/reports/satisfaction', [App\Http\Controllers\Api\ReportController::class, 'satisfaction']);

    // Audit logs
    Route::get('/audit-logs', [App\Http\Controllers\Api\AuditLogController::class, 'index']);
});

// Stripe webhook (no auth)
Route::post('/stripe/webhook', [App\Http\Controllers\Api\PaymentController::class, 'webhook']);
