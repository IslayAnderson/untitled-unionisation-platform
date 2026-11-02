<?php

use App\Http\Controllers\AddressSearchController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\VouchController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/address/search', AddressSearchController::class)
    ->middleware('throttle:30,1')
    ->name('address.search');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/join/{building:signup_token}', [RegisterController::class, 'createJoin'])->name('join');
    Route::post('/join/{building:signup_token}', [RegisterController::class, 'storeJoin']);

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding');
    Route::post('/onboarding/unit', [OnboardingController::class, 'storeUnit'])->name('onboarding.unit');
    Route::post('/onboarding/building', [OnboardingController::class, 'storeBuilding'])->name('onboarding.building');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/vouch/{contact:vouch_token}', [VouchController::class, 'show'])->name('vouch.show');
    Route::post('/vouch/{contact:vouch_token}', [VouchController::class, 'store'])->name('vouch.store');
});
