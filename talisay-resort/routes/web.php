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
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\WebStaffManagementController;
use App\Http\Controllers\PublicPagesController;
use App\Http\Controllers\WebFileMaintenanceController;
use App\Http\Controllers\WebActivityController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ── Public guest pages (no login needed) ────────────────────────────────
Route::get('/', [PublicPagesController::class, 'home'])->name('home');
Route::get('/about', [PublicPagesController::class, 'about'])->name('public.about');
Route::get('/rooms', [PublicPagesController::class, 'rooms'])->name('public.rooms');
Route::get('/cottages', [PublicPagesController::class, 'cottages'])->name('public.cottages');
Route::get('/activities', [PublicPagesController::class, 'activities'])->name('public.activities');
Route::get('/booking', [PublicPagesController::class, 'booking'])->name('public.booking');
Route::get('/booking/exclusive', [PublicPagesController::class, 'exclusive'])->name('public.booking.exclusive');
Route::get('/stay/{unit}', [PublicPagesController::class, 'unit'])->name('public.unit');
Route::get('/guest-reviews', [PublicPagesController::class, 'reviews'])->name('public.reviews');
Route::get('/contact', [PublicPagesController::class, 'contact'])->name('public.contact');

// Authentication Routes
Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->middleware('throttle:login')->name('login.post');
Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register');
Route::get('/auth/{provider}', [SocialAuthController::class, 'redirect'])
    ->where('provider', 'google|facebook')
    ->middleware('throttle:20,1')
    ->name('auth.social.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->where('provider', 'google|facebook')
    ->middleware('throttle:20,1')
    ->name('auth.social.callback');
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
    Route::get('/booking/resume', function (\Illuminate\Http\Request $request) {
        return redirect()->route('public.booking', $request->query());
    })->name('public.booking.resume');

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
        Route::get('/bookings/unit-availability', [WebBookingController::class, 'unitAvailability'])->middleware('permission:bookings,create')->name('bookings.unit-availability');
        Route::post('/bookings/exclusive', [WebBookingController::class, 'storeExclusive'])->middleware('permission:bookings,create')->name('bookings.exclusive-store');
        Route::put('/bookings/{booking}/exclusive', [WebBookingController::class, 'updateExclusive'])->middleware('permission:bookings,edit')->name('bookings.exclusive-update');
        Route::put('/bookings/{booking}', [WebBookingController::class, 'update'])->middleware('permission:bookings,edit')->name('bookings.update');
        Route::delete('/bookings/{booking}', [WebBookingController::class, 'destroy'])->middleware('permission:bookings,delete')->name('bookings.destroy');
        Route::patch('/bookings/{booking}/status', [WebBookingController::class, 'updateStatus'])->middleware('permission:bookings,confirm')->name('bookings.update-status');
        Route::post('/bookings/{booking}/approve-special', [WebBookingController::class, 'approveSpecialBooking'])->middleware('permission:bookings,confirm')->name('bookings.approve-special');
        Route::post('/bookings/{booking}/reject-special', [WebBookingController::class, 'rejectSpecialBooking'])->middleware('permission:bookings,confirm')->name('bookings.reject-special');

        // Payments
        Route::get('/payments', [WebPaymentController::class, 'index'])->middleware('permission:payments,view')->name('payments.index');
        Route::get('/payments/{payment}', [WebPaymentController::class, 'show'])->middleware('permission:payments,view')->name('payments.show');
        Route::post('/payments/{payment}/approve', [WebPaymentController::class, 'approvePayment'])->middleware('permission:payments,verify')->name('payments.approve');
        Route::post('/payments/{payment}/reject', [WebPaymentController::class, 'rejectPayment'])->middleware('permission:payments,verify')->name('payments.reject');
        Route::post('/payments/{payment}/refund', [WebPaymentController::class, 'refundPayment'])->middleware('permission:payments,refund')->name('payments.refund');
        Route::post('/payments/{payment}/collect-balance', [WebPaymentController::class, 'collectBalance'])->middleware('permission:payments,verify')->name('payments.collect-balance');

        // Reviews & Feedback Moderation
        Route::get('/reviews', [WebReviewController::class, 'index'])->middleware('permission:reviews,view')->name('reviews.index');
        Route::put('/reviews/{review}/block-comment', [WebReviewController::class, 'blockComment'])->middleware('permission:reviews,block_comment')->name('reviews.block-comment');
        Route::put('/reviews/{review}/unblock-comment', [WebReviewController::class, 'unblockComment'])->middleware('permission:reviews,block_comment')->name('reviews.unblock-comment');
        Route::put('/reviews/{review}/approve', [WebReviewController::class, 'approve'])->middleware('permission:reviews,approve')->name('reviews.approve');
        Route::put('/reviews/{review}/reject', [WebReviewController::class, 'reject'])->middleware('permission:reviews,reject')->name('reviews.reject');

        // Accommodations management
        Route::get('/accommodations', [WebAccommodationController::class, 'index'])->middleware('permission:accommodations,view')->name('accommodations.index');
        Route::get('/accommodations/cottages', [WebAccommodationController::class, 'cottages'])->middleware('permission:accommodations,view')->name('accommodations.cottages');
        Route::post('/accommodations', [WebAccommodationController::class, 'store'])->middleware('permission:accommodations,create')->name('accommodations.store');
        Route::put('/accommodations/{accommodation}', [WebAccommodationController::class, 'update'])->middleware('permission:accommodations,edit')->name('accommodations.update');
        Route::delete('/accommodations/{accommodation}', [WebAccommodationController::class, 'destroy'])->middleware('permission:accommodations,delete')->name('accommodations.destroy');
        Route::post('/accommodations/{accommodation}/toggle-availability', [WebAccommodationController::class, 'toggleAvailability'])->middleware('permission:accommodations,toggle_availability')->name('accommodations.toggle-availability');

        Route::get('/reports', [WebReportController::class, 'index'])->middleware('permission:reports,view')->name('reports.index');
        Route::get('/reports/export-pdf', [WebReportController::class, 'exportPdf'])->middleware('permission:reports,export')->name('reports.export-pdf');
        Route::get('/reports/export-excel', [WebReportController::class, 'exportExcel'])->middleware('permission:reports,export')->name('reports.export-excel');

        Route::get('/chatbot', [WebChatbotController::class, 'index'])->middleware('permission:chatbot,view')->name('chatbot.index');
        Route::post('/chatbot/settings', [WebChatbotController::class, 'updateSettings'])->middleware('permission:chatbot,manage')->name('chatbot.settings.update');
        Route::delete('/chatbot/logs', [WebChatbotController::class, 'clearLogs'])->middleware('permission:chatbot,manage')->name('chatbot.logs.clear');
        Route::delete('/chatbot/logs/{log}', [WebChatbotController::class, 'destroyLog'])->middleware('permission:chatbot,manage')->name('chatbot.logs.destroy');
        Route::post('/chatbot/intents', [WebChatbotController::class, 'store'])->middleware('permission:chatbot,manage')->name('chatbot.intents.store');
        Route::put('/chatbot/intents/{intent}', [WebChatbotController::class, 'update'])->middleware('permission:chatbot,manage')->name('chatbot.intents.update');
        Route::delete('/chatbot/intents/{intent}', [WebChatbotController::class, 'destroy'])->middleware('permission:chatbot,manage')->name('chatbot.intents.destroy');
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
        Route::get('/users', fn() => redirect()->route('maintenance.section', 'accounts'))->name('users.index');
        Route::get('/users/create', fn() => redirect()->route('maintenance.section', ['section' => 'accounts', 'action' => 'create']))->name('users.create');
        Route::patch('/users/{user}/toggle-status', [WebUserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('/users/{user}/reset-password', [WebUserController::class, 'adminResetPassword'])->name('users.reset-password');
        Route::resource('users', WebUserController::class)->except(['index', 'create']);

        // Tour Assets Management
        Route::get('/tour-manage', [WebTourController::class, 'index'])->name('tour.manage');
        Route::post('/tour-manage', [WebTourController::class, 'store'])->name('tour.manage.store');
        Route::put('/tour-manage/{asset}', [WebTourController::class, 'update'])->name('tour.manage.update');
        Route::delete('/tour-manage/{asset}', [WebTourController::class, 'destroy'])->name('tour.manage.destroy');

        // Settings
        Route::get('/activities-manage', [WebActivityController::class, 'index'])->name('activities.manage');
        Route::post('/activities-manage', [WebActivityController::class, 'store'])->name('activities.store');
        Route::put('/activities-manage/{activity}', [WebActivityController::class, 'update'])->name('activities.update');
        Route::delete('/activities-manage/{activity}', [WebActivityController::class, 'destroy'])->name('activities.destroy');

        Route::get('/file-maintenance', [WebFileMaintenanceController::class, 'index'])->name('maintenance.index');
        Route::post('/file-maintenance/payments/install', [WebFileMaintenanceController::class, 'installPayments'])->name('maintenance.payments.install');
        Route::get('/file-maintenance/{section}', [WebFileMaintenanceController::class, 'section'])->name('maintenance.section');
        Route::post('/file-maintenance/{section}', [WebFileMaintenanceController::class, 'store'])->name('maintenance.store');
        Route::put('/file-maintenance/{section}/{id}', [WebFileMaintenanceController::class, 'update'])->name('maintenance.update');
        Route::delete('/file-maintenance/{section}/{id}', [WebFileMaintenanceController::class, 'destroy'])->name('maintenance.destroy');

        Route::get('/settings', [WebSettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [WebSettingsController::class, 'update'])->name('settings.save');
        Route::post('/settings/gallery', [WebSettingsController::class, 'storeGallery'])->name('settings.gallery.store');
        Route::put('/settings/gallery/{photo}', [WebSettingsController::class, 'updateGallery'])->name('settings.gallery.update');
        Route::delete('/settings/gallery/{photo}', [WebSettingsController::class, 'destroyGallery'])->name('settings.gallery.destroy');
        Route::post('/settings/chatbot', [WebSettingsController::class, 'updateChatbot'])->name('settings.chatbot.save');
        Route::post('/settings/chatbot/test-connection', [WebSettingsController::class, 'testAiConnection'])->name('settings.chatbot.test');
    });

    // Guest pages an admin or staff member can open to preview the virtual tour.
    Route::middleware('role:tourist,admin,staff')->prefix('tourist')->name('tourist.')->group(function () {
        Route::get('/dashboard', [WebTouristPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/accommodations', [WebAccommodationController::class, 'guestIndex'])->name('accommodations');
        Route::get('/accommodations/{unit}', [WebAccommodationController::class, 'guestDetail'])->name('accommodations.detail');
        Route::get('/accommodations/{unit}/check-availability', [WebTouristPortalController::class, 'checkAvailability'])->name('accommodations.check-availability');
    });

    // Dashboard - Tourist
    Route::middleware('role:tourist')->prefix('tourist')->name('tourist.')->group(function () {
        // My Bookings
        Route::get('/bookings', [WebTouristPortalController::class, 'bookings'])->name('bookings');
        Route::post('/bookings', [WebTouristPortalController::class, 'storeBooking'])->name('bookings.store');
        Route::post('/bookings/special-resort', [WebTouristPortalController::class, 'storeSpecialResortBooking'])->name('bookings.special-resort');
        Route::post('/bookings/{booking}/modify', [WebTouristPortalController::class, 'modifyBooking'])->name('bookings.modify');
        Route::post('/bookings/{booking}/cancel', [WebTouristPortalController::class, 'cancelBooking'])->name('bookings.cancel');

        // Payments
        Route::post('/payments/{payment}/upload-proof', [WebTouristPortalController::class, 'uploadPaymentProof'])->name('payments.upload-proof');

        // Reviews
        Route::get('/reviews', [WebTouristPortalController::class, 'reviews'])->name('reviews');
        Route::post('/reviews', [WebTouristPortalController::class, 'storeReview'])->name('reviews.store');
    });
});

