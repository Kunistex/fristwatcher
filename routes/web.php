<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ContractActionController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExpiryItemController;
use App\Http\Controllers\LetterController;
use App\Http\Controllers\PriceChangeController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\SavingsController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:5,1');
    Route::get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    Route::post('/contracts/preview', [ContractController::class, 'preview'])->name('contracts.preview');
    Route::resource('contracts', ContractController::class);
    Route::post('/contracts/{contract}/status', [ContractActionController::class, 'status'])->name('contracts.status');
    Route::post('/contracts/{contract}/keep', [ContractActionController::class, 'keep'])->name('contracts.keep');
    Route::post('/contracts/{contract}/used', [ContractActionController::class, 'used'])->name('contracts.used');
    Route::post('/contracts/{contract}/price-changes', [PriceChangeController::class, 'store'])->name('contracts.price-changes.store');
    Route::delete('/contracts/{contract}/price-changes/{priceChange}', [PriceChangeController::class, 'destroy'])->name('contracts.price-changes.destroy');
    Route::get('/contracts/{contract}/letter', [LetterController::class, 'edit'])->name('contracts.letter.edit');
    Route::post('/contracts/{contract}/letter', [LetterController::class, 'download'])->name('contracts.letter.download');
    Route::post('/contracts/{contract}/documents', [DocumentController::class, 'store'])->name('contracts.documents.store');

    Route::get('/documents/{document}', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    Route::resource('expiry', ExpiryItemController::class)->except(['create', 'show'])->parameters(['expiry' => 'expiry']);

    Route::get('/savings', SavingsController::class)->name('savings');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::put('/settings/password', [SettingsController::class, 'password'])->name('settings.password');
    Route::post('/settings/test-notification', [SettingsController::class, 'testNotification'])->name('settings.test-notification')->middleware('throttle:5,1');

    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push.destroy');
});
