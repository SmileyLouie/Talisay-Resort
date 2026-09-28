<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\MobileClientController;
use App\Http\Controllers\Api\MobileIntegrationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\ChatbotIntentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SystemSettingController;
use App\Http\Controllers\Api\TourAssetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Browser widgets call these with the web session (Sanctum stateful) and a
| CSRF token; mobile/third-party clients use Bearer tokens.
*/

// Public chatbot (web session used only to detect the visitor's role)
Route::prefix('chatbot')->middleware(['web', 'throttle:chatbot'])->group(function () {
    Route::post('/message', [ChatbotController::class, 'message']);
    Route::get('/context', [ChatbotController::class, 'context']);
});

// Guest mobile app. Development builds call these with a hardcoded local base URL.
Route::prefix('mobile')->middleware('throttle:30,1')->group(function () {
    Route::post('/register', [MobileClientController::class, 'register']);
    Route::post('/login', [MobileClientController::class, 'login']);
    Route::get('/units', [MobileClientController::class, 'units']);
    Route::get('/units/{unit}/availability', [MobileClientController::class, 'stay']);
    Route::post('/chat', [ChatbotController::class, 'message'])->middleware('auth:sanctum');
});

// Public, aggregate-only data
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/tour', [TourAssetController::class, 'publicIndex']);
    Route::get('/availability', [BookingController::class, 'availability']);
});

// Authenticated (Sanctum token or stateful web session)
Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/password', [AuthController::class, 'updatePassword']);

    // Bookings — every action is authorised by BookingPolicy
    Route::get('/my-bookings', [BookingController::class, 'myBookings']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::patch('/bookings/{booking}/status', [BookingController::class, 'update']);
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    Route::get('/bookings/{booking}/receipt', [PaymentController::class, 'receipt']);

    // Payments — authorised by PaymentPolicy
    Route::get('/payments/{payment}', [PaymentController::class, 'show']);
    Route::post('/payments/{payment}/upload-proof', [PaymentController::class, 'uploadProof']);

    // Reviews
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/my-reviews', [ReviewController::class, 'myReviews']);

    // Notifications (own only)
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markRead']);
});

// Admin-only API
Route::middleware(['auth:sanctum', 'role:admin', 'throttle:120,1'])->prefix('admin')->group(function () {
    Route::apiResource('chatbot-intents', ChatbotIntentController::class);
    Route::get('/chatbot-logs', [ChatbotIntentController::class, 'logs']);

    Route::apiResource('tour-assets', TourAssetController::class);

    Route::get('/settings', [SystemSettingController::class, 'index']);
    Route::put('/settings', [SystemSettingController::class, 'update']);

    Route::get('/reports/revenue', [ReportController::class, 'revenue']);
    Route::get('/reports/occupancy', [ReportController::class, 'occupancy']);
    Route::get('/reports/accommodations', [ReportController::class, 'popularAccommodations']);
    Route::get('/reports/cancellations', [ReportController::class, 'cancellations']);
    Route::get('/reports/satisfaction', [ReportController::class, 'satisfaction']);

    Route::get('/audit-logs', [AuditLogController::class, 'index']);
});

// Stripe webhook — authenticated by signature, not by session
Route::post('/stripe/webhook', [PaymentController::class, 'webhook']);

// Server-to-server bridge used by Supabase Edge Functions. The mobile app
// does not call these routes and never receives the integration token.
Route::prefix('integration')->middleware(['integration', 'throttle:60,1'])->group(function () {
    Route::post('/clients', [MobileIntegrationController::class, 'upsertClient']);
    Route::post('/bookings', [MobileIntegrationController::class, 'storeBooking']);
    Route::post('/bookings/cancel', [MobileIntegrationController::class, 'cancelBooking']);
    Route::post('/reviews', [MobileIntegrationController::class, 'storeReview']);
    Route::get('/catalog', [MobileIntegrationController::class, 'catalog']);
    Route::post('/recommend', [MobileIntegrationController::class, 'recommend']);
    Route::post('/chat', [MobileIntegrationController::class, 'chat']);
});
