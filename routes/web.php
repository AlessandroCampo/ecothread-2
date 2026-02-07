<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\PassportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ============================================
// PUBLIC PAGES
// ============================================
Route::get('/', function () {
    return Inertia::render('Public/Landing');
})->name('landing');

Route::get('/verify/{passportNumber}', [PassportController::class, 'publicVerify'])
    ->name('passport.verify');

// ============================================
// AUTH REQUIRED
// ============================================
Route::middleware('auth')->group(function () {

    // Profile
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Admin Dashboard
    Route::prefix('admin')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        
        // Products CRUD
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::post('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::patch('/products/{id}/confirm', [ProductController::class, 'confirm'])->name('products.confirm');
        Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');
        
        // Events CRUD
        Route::get('/products/{productId}/events', [EventController::class, 'index'])->name('events.index');
        Route::post('/products/{productId}/events', [EventController::class, 'store'])->name('events.store');
        Route::post('/products/{productId}/upload-document', [EventController::class, 'uploadDocument'])->name('events.upload_document');
        Route::patch('/events/{id}/confirm', [EventController::class, 'confirm'])->name('events.confirm');
        Route::delete('/events/{id}', [EventController::class, 'destroy'])->name('events.destroy');
        Route::get('/products/{productId}/events/{index}/download', [EventController::class, 'downloadDocument'])->name('events.download');
    });

    // Passports
    Route::prefix('passports')->name('passports.')->group(function () {
        Route::get('/', [PassportController::class, 'index'])->name('index');
        Route::get('/{id}', [PassportController::class, 'show'])->name('show');
        Route::post('/products/{productId}/passport', [PassportController::class, 'request'])->name('request');
        Route::get('/products/{productId}/eligibility', [PassportController::class, 'checkEligibility'])->name('check-eligibility');
    });
});

require __DIR__.'/auth.php';