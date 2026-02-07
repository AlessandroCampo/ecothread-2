<?php

use App\Http\Controllers\SolanaController;
use App\Http\Controllers\EnumController;
use App\Http\Controllers\PassportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

// ============================================
// PUBLIC API (no auth)
// ============================================

// Enums
Route::prefix('enums')->name('api.enums.')->group(function () {
    Route::get('/', [EnumController::class, 'index'])->name('index');
    Route::get('/event-types', [EnumController::class, 'eventTypes'])->name('eventTypes');
    Route::get('/trust-levels', [EnumController::class, 'trustLevels'])->name('trustLevels');
    Route::get('/events', [EnumController::class, 'eventEnums'])->name('events');
});

// Products (public read)
Route::prefix('products')->name('api.products.')->group(function () {
    Route::get('/{productId}', [ProductController::class, 'show'])->name('show');
    Route::get('/{productId}/history', [ProductController::class, 'history'])->name('history');
});

// Search
Route::get('/public/search', [PublicController::class, 'search'])->name('api.public.search');

// Passport (public)
Route::prefix('passport')->name('api.passport.')->group(function () {
    Route::get('/{passportNumber}/status', [PassportController::class, 'publicStatus'])->name('status');
    Route::post('/verify-document/{eventId}', [PassportController::class, 'verifyDocument'])->name('verify-document');
});

// Solana (public read)
Route::prefix('solana')->name('api.solana.')->group(function () {
    Route::get('/fee-payer', [SolanaController::class, 'feePayer'])->name('fee-payer');
    Route::get('/health', [SolanaController::class, 'health'])->name('health');
    Route::get('/blockhash', [SolanaController::class, 'blockhash'])->name('blockhash');
});

// ============================================
// PROTECTED API (auth required)
// ============================================
Route::middleware(['web', 'auth'])->group(function () {
    
    // Solana transactions
    Route::post('/solana/sign-and-submit', [SolanaController::class, 'signAndSubmit'])
        ->name('api.solana.sign-and-submit');
});