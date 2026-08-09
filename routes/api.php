<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public API routes
Route::prefix('chatbot')->group(function () {
    Route::post('/message', [App\Http\Controllers\Api\ChatbotController::class, 'message']);
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

    // Emergencies
    Route::apiResource('emergencies', App\Http\Controllers\Api\EmergencyController::class)->only(['store', 'show', 'update']);
    Route::get('/my-emergencies', [App\Http\Controllers\Api\EmergencyController::class, 'myEmergencies']);

    // Reviews
    Route::post('/reviews', [App\Http\Controllers\Api\ReviewController::class, 'store']);
    Route::get('/my-reviews', [App\Http\Controllers\Api\ReviewController::class, 'myReviews']);

    // Memory Timeline
    Route::post('/memory-timeline/items', [App\Http\Controllers\Api\MemoryTimelineController::class, 'addItem']);
    Route::delete('/memory-timeline/items/{id}', [App\Http\Controllers\Api\MemoryTimelineController::class, 'removeItem']);
    Route::put('/memory-timeline/items/{id}/select', [App\Http\Controllers\Api\MemoryTimelineController::class, 'toggleSelect']);
    Route::post('/memory-timeline/generate', [App\Http\Controllers\Api\MemoryTimelineController::class, 'generate']);
    Route::get('/memory-timeline/download/{id}', [App\Http\Controllers\Api\MemoryTimelineController::class, 'download']);

    // Notifications
    Route::get('/notifications', [App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markRead']);
    Route::put('/notifications/read-all', [App\Http\Controllers\Api\NotificationController::class, 'markAllRead']);

    // Packages (read for tourists)
    Route::get('/packages', [App\Http\Controllers\Api\PackageController::class, 'index']);
    Route::get('/packages/{package}', [App\Http\Controllers\Api\PackageController::class, 'show']);
});

// Staff/Admin API routes
Route::middleware(['auth:sanctum', 'role:admin,staff'])->prefix('staff')->group(function () {
    // Emergencies management
    Route::get('/emergencies', [App\Http\Controllers\Api\EmergencyController::class, 'index']);
    Route::put('/emergencies/{emergency}/status', [App\Http\Controllers\Api\EmergencyController::class, 'updateStatus']);
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
    Route::get('/reports/packages', [App\Http\Controllers\Api\ReportController::class, 'popularPackages']);
    Route::get('/reports/cancellations', [App\Http\Controllers\Api\ReportController::class, 'cancellations']);
    Route::get('/reports/satisfaction', [App\Http\Controllers\Api\ReportController::class, 'satisfaction']);

    // Audit logs
    Route::get('/audit-logs', [App\Http\Controllers\Api\AuditLogController::class, 'index']);
});

// Stripe webhook (no auth)
Route::post('/stripe/webhook', [App\Http\Controllers\Api\PaymentController::class, 'webhook']);
