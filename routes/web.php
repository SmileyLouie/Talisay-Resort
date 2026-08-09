<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\WebPackageController;
use App\Http\Controllers\WebBookingController;
use App\Http\Controllers\WebPaymentController;
use App\Http\Controllers\WebEmergencyController;
use App\Http\Controllers\WebChatbotController;
use App\Http\Controllers\WebTourController;
use App\Http\Controllers\WebMemoryTimelineController;
use App\Http\Controllers\WebReviewController;
use App\Http\Controllers\WebReportController;
use App\Http\Controllers\WebUserController;
use App\Http\Controllers\WebProfileController;
use App\Http\Controllers\WebSettingsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Authentication Routes
Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->name('login.post');
Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register');
Route::post('/register', [WebAuthController::class, 'register'])->name('register.post');
Route::get('/forgot-password', [WebAuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [WebAuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [WebAuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [WebAuthController::class, 'resetPassword'])->name('password.update');

// Public Routes
Route::get('/tour', [WebTourController::class, 'viewer'])->name('tour.viewer');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

    // Profile
    Route::get('/profile', [WebProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [WebProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [WebProfileController::class, 'updatePassword'])->name('profile.password');

    // Dashboard - Admin
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

    // Dashboard - Staff
    Route::middleware('role:staff')->prefix('staff')->name('staff.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

    // Shared Dashboard (both admin & staff)
    Route::middleware('role:admin,staff')->group(function () {
        // Bookings list management view
        Route::get('/bookings', [WebBookingController::class, 'index'])->name('bookings.index');
        // Update booking lifecycle status route
        Route::patch('/bookings/{booking}/status', [WebBookingController::class, 'updateStatus'])->name('bookings.update-status');

        // Emergencies
        Route::get('/emergencies', [WebEmergencyController::class, 'index'])->name('emergencies.index');

        // Payments
        Route::get('/payments', [WebPaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/{payment}', [WebPaymentController::class, 'show'])->name('payments.show');
    });

    // Admin-only routes
    Route::middleware('role:admin')->group(function () {
        // Packages CRUD management resource routes
        Route::resource('packages', WebPackageController::class)->except(['show', 'create', 'edit']);
        // Toggle package visibility state post route
        Route::post('/packages/{package}/toggle-visibility', [WebPackageController::class, 'toggleVisibility'])->name('packages.toggle-visibility');

        // Users
        Route::resource('users', WebUserController::class);

        // Chatbot Intents web routes
        Route::get('/chatbot', [WebChatbotController::class, 'index'])->name('chatbot.index');
        Route::post('/chatbot/intents', [WebChatbotController::class, 'store'])->name('chatbot.intents.store');
        Route::put('/chatbot/intents/{intent}', [WebChatbotController::class, 'update'])->name('chatbot.intents.update');
        Route::delete('/chatbot/intents/{intent}', [WebChatbotController::class, 'destroy'])->name('chatbot.intents.destroy');

        // Tour Assets Management
        Route::get('/tour-manage', [WebTourController::class, 'index'])->name('tour.manage');
        Route::post('/tour-manage', [WebTourController::class, 'store'])->name('tour.manage.store');
        Route::delete('/tour-manage/{asset}', [WebTourController::class, 'destroy'])->name('tour.manage.destroy');

        // Memory Timelines
        Route::get('/memory-timelines', [WebMemoryTimelineController::class, 'index'])->name('memory-timelines.index');

        // Reviews
        Route::get('/reviews', [WebReviewController::class, 'index'])->name('reviews.index');
        Route::put('/reviews/{review}/approve', [WebReviewController::class, 'approve'])->name('reviews.approve');
        Route::put('/reviews/{review}/reject', [WebReviewController::class, 'reject'])->name('reviews.reject');

        // Reports view and exports
        Route::get('/reports', [WebReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export-pdf', [WebReportController::class, 'exportPdf'])->name('reports.export-pdf');
        Route::get('/reports/export-excel', [WebReportController::class, 'exportExcel'])->name('reports.export-excel');

    // Payments management
        Route::post('/payments/{payment}/approve', [WebPaymentController::class, 'approvePayment'])->name('payments.approve');
        Route::post('/payments/{payment}/reject', [WebPaymentController::class, 'rejectPayment'])->name('payments.reject');

        // Settings
        Route::get('/settings', [WebSettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [WebSettingsController::class, 'update'])->name('settings.save');
    });

    // Admin + Staff: Emergency status updates via web
    Route::middleware('role:admin,staff')->group(function () {
        Route::patch('/emergencies/{emergency}/status', [\App\Http\Controllers\WebEmergencyController::class, 'updateStatus'])->name('emergencies.update-status');
    });
});

// Default route
Route::get('/', function () {
    if (auth()->check()) {
        if (auth()->user()->isAdmin()) return redirect()->route('admin.dashboard');
        if (auth()->user()->isStaff()) return redirect()->route('staff.dashboard');
    }
    return redirect()->route('login');
});
