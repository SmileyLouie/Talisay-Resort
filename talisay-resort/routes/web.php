<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\WebBookingController;
use App\Http\Controllers\WebPaymentController;
use App\Http\Controllers\WebChatbotController;
use App\Http\Controllers\WebTourController;
use App\Http\Controllers\WebReviewController;
use App\Http\Controllers\WebReportController;
use App\Http\Controllers\WebUserController;
use App\Http\Controllers\WebProfileController;
use App\Http\Controllers\WebSettingsController;
use App\Http\Controllers\WebTouristPortalController;
use App\Http\Controllers\WebAccommodationController;
use App\Http\Controllers\WebStaffManagementController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ── Landing / Welcome Page ──────────────────────────────────────────────
Route::get('/', function () {
    // Already logged-in users go straight to their dashboard
    if (auth()->check()) {
        $user = auth()->user();
        if (method_exists($user, 'isAdmin') && $user->isAdmin())   return redirect()->route('admin.dashboard');
        if (method_exists($user, 'isStaff') && $user->isStaff())   return redirect()->route('staff.dashboard');
        if (method_exists($user, 'isTourist') && $user->isTourist()) return redirect()->route('tourist.dashboard');
        // Fallback by role string
        return match($user->role) {
            'tourist' => redirect()->route('tourist.dashboard'),
            default   => redirect()->route('dashboard'),
        };
    }
    return view('landing');
})->name('home');

// Authentication Routes
Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->middleware('throttle:login')->name('login.post');
Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register');
Route::post('/register', [WebAuthController::class, 'register'])->middleware('throttle:register')->name('register.post');
Route::get('/forgot-password', [WebAuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [WebAuthController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
Route::get('/reset-password/{token}', [WebAuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [WebAuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');

// Public Routes
Route::get('/tour', [WebTourController::class, 'viewer'])->name('tour.viewer');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

    // Profile
    Route::get('/profile', [WebProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [WebProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [WebProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile/avatar', [WebProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');

    // Notifications
    Route::post('/notifications/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [App\Http\Controllers\Api\NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // Dashboard - Admin
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

    // Dashboard - Staff
    Route::middleware('role:staff')->prefix('staff')->name('staff.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'staffDashboard'])->name('dashboard');
    });

    // Shared Dashboard & Operations (both admin & staff, guarded by granular RBAC permissions)
    Route::middleware('role:admin,staff')->group(function () {
        // Bookings
        Route::get('/bookings', [WebBookingController::class, 'index'])->middleware('permission:bookings,view')->name('bookings.index');
        Route::post('/bookings/manual', [WebBookingController::class, 'manualStore'])->middleware('permission:bookings,create')->name('bookings.manual-store');
        Route::patch('/bookings/{booking}/status', [WebBookingController::class, 'updateStatus'])->middleware('permission:bookings,confirm')->name('bookings.update-status');

        // Payments
        Route::get('/payments', [WebPaymentController::class, 'index'])->middleware('permission:payments,view')->name('payments.index');
        Route::get('/payments/{payment}', [WebPaymentController::class, 'show'])->middleware('permission:payments,view')->name('payments.show');
        Route::post('/payments/{payment}/approve', [WebPaymentController::class, 'approvePayment'])->middleware('permission:payments,verify')->name('payments.approve');
        Route::post('/payments/{payment}/reject', [WebPaymentController::class, 'rejectPayment'])->middleware('permission:payments,verify')->name('payments.reject');

        // Reviews & Feedback Moderation
        Route::get('/reviews', [WebReviewController::class, 'index'])->middleware('permission:reviews,view')->name('reviews.index');
        Route::put('/reviews/{review}/block-comment', [WebReviewController::class, 'blockComment'])->middleware('permission:reviews,block_comment')->name('reviews.block-comment');
        Route::put('/reviews/{review}/unblock-comment', [WebReviewController::class, 'unblockComment'])->middleware('permission:reviews,block_comment')->name('reviews.unblock-comment');
        Route::put('/reviews/{review}/approve', [WebReviewController::class, 'approve'])->middleware('permission:reviews,approve')->name('reviews.approve');
        Route::put('/reviews/{review}/reject', [WebReviewController::class, 'reject'])->middleware('permission:reviews,reject')->name('reviews.reject');

        // Accommodations management
        Route::get('/accommodations', [WebAccommodationController::class, 'index'])->middleware('permission:accommodations,view')->name('accommodations.index');
        Route::post('/accommodations', [WebAccommodationController::class, 'store'])->middleware('permission:accommodations,create')->name('accommodations.store');
        Route::put('/accommodations/{accommodation}', [WebAccommodationController::class, 'update'])->middleware('permission:accommodations,edit')->name('accommodations.update');
        Route::delete('/accommodations/{accommodation}', [WebAccommodationController::class, 'destroy'])->middleware('permission:accommodations,delete')->name('accommodations.destroy');
        Route::post('/accommodations/{accommodation}/toggle-availability', [WebAccommodationController::class, 'toggleAvailability'])->middleware('permission:accommodations,toggle_availability')->name('accommodations.toggle-availability');
    });

    // Admin-only System Admin routes
    Route::middleware('role:admin')->group(function () {
        // Staff Management & RBAC Permissions
        Route::get('/staff-management', [WebStaffManagementController::class, 'staffIndex'])->name('tasks.staff');
        Route::post('/staff-management', [WebStaffManagementController::class, 'staffStore'])->name('tasks.staff.store');
        Route::put('/staff-management/{user}/permissions', [WebStaffManagementController::class, 'updatePermissions'])->name('tasks.staff.permissions');
        Route::patch('/staff-management/{user}/account-status', [WebStaffManagementController::class, 'updateAccountStatus'])->name('tasks.staff.account-status');
        Route::post('/staff-management/{user}/reset-password', [WebStaffManagementController::class, 'resetStaffPassword'])->name('tasks.staff.reset-password');
        Route::patch('/staff-management/{user}/duty-status', [WebStaffManagementController::class, 'updateStaffDuty'])->name('tasks.staff.duty');
        Route::get('/staff-roster', fn() => redirect()->route('tasks.staff'));

        // User Accounts in System Settings
        Route::get('/users', fn() => redirect()->route('settings.index', ['tab' => 'users']))->name('users.index');
        Route::get('/users/create', fn() => redirect()->route('settings.index', ['tab' => 'users', 'action' => 'create']))->name('users.create');
        Route::patch('/users/{user}/toggle-status', [WebUserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('/users/{user}/reset-password', [WebUserController::class, 'adminResetPassword'])->name('users.reset-password');
        Route::resource('users', WebUserController::class)->except(['index', 'create']);

        // AI Chatbot Operations & Knowledge Base
        Route::get('/chatbot', [WebChatbotController::class, 'index'])->name('chatbot.index');
        Route::post('/chatbot/settings', [WebChatbotController::class, 'updateSettings'])->name('chatbot.settings.update');
        Route::delete('/chatbot/logs', [WebChatbotController::class, 'clearLogs'])->name('chatbot.logs.clear');
        Route::post('/chatbot/intents', [WebChatbotController::class, 'store'])->name('chatbot.intents.store');
        Route::put('/chatbot/intents/{intent}', [WebChatbotController::class, 'update'])->name('chatbot.intents.update');
        Route::delete('/chatbot/intents/{intent}', [WebChatbotController::class, 'destroy'])->name('chatbot.intents.destroy');

        // Tour Assets Management
        Route::get('/tour-manage', [WebTourController::class, 'index'])->name('tour.manage');
        Route::post('/tour-manage', [WebTourController::class, 'store'])->name('tour.manage.store');
        Route::put('/tour-manage/{asset}', [WebTourController::class, 'update'])->name('tour.manage.update');
        Route::delete('/tour-manage/{asset}', [WebTourController::class, 'destroy'])->name('tour.manage.destroy');

        // Reports
        Route::get('/reports', [WebReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export-pdf', [WebReportController::class, 'exportPdf'])->name('reports.export-pdf');
        Route::get('/reports/export-excel', [WebReportController::class, 'exportExcel'])->name('reports.export-excel');

        // Special Booking Approvals (admin only)
        Route::post('/bookings/{booking}/approve-special', [WebBookingController::class, 'approveSpecialBooking'])->name('bookings.approve-special');
        Route::post('/bookings/{booking}/reject-special', [WebBookingController::class, 'rejectSpecialBooking'])->name('bookings.reject-special');

        // Settings
        Route::get('/settings', [WebSettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [WebSettingsController::class, 'update'])->name('settings.save');
        Route::post('/settings/chatbot', [WebSettingsController::class, 'updateChatbot'])->name('settings.chatbot.save');
        Route::post('/settings/chatbot/test-connection', [WebSettingsController::class, 'testAiConnection'])->name('settings.chatbot.test');
    });

    // Dashboard - Tourist
    Route::middleware('role:tourist')->prefix('tourist')->name('tourist.')->group(function () {
        Route::get('/dashboard', [WebTouristPortalController::class, 'dashboard'])->name('dashboard');

        // My Bookings
        Route::get('/bookings', [WebTouristPortalController::class, 'bookings'])->name('bookings');
        Route::post('/bookings', [WebTouristPortalController::class, 'storeBooking'])->name('bookings.store');
        Route::post('/bookings/special-resort', [WebTouristPortalController::class, 'storeSpecialResortBooking'])->name('bookings.special-resort');
        Route::post('/bookings/{booking}/modify', [WebTouristPortalController::class, 'modifyBooking'])->name('bookings.modify');
        Route::post('/bookings/{booking}/cancel', [WebTouristPortalController::class, 'cancelBooking'])->name('bookings.cancel');

        // Payments
        Route::post('/payments/{payment}/upload-proof', [WebTouristPortalController::class, 'uploadPaymentProof'])->name('payments.upload-proof');

        // Accommodations Browse (tourists)
        Route::get('/accommodations', [WebAccommodationController::class, 'guestIndex'])->name('accommodations');
        Route::get('/accommodations/{unit}', [WebAccommodationController::class, 'guestDetail'])->name('accommodations.detail');
        Route::get('/accommodations/{unit}/check-availability', [WebTouristPortalController::class, 'checkAvailability'])->name('accommodations.check-availability');

        // Reviews
        Route::get('/reviews', [WebTouristPortalController::class, 'reviews'])->name('reviews');
        Route::post('/reviews', [WebTouristPortalController::class, 'storeReview'])->name('reviews.store');
    });
});

