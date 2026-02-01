<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EnumController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\PassportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;


Route::get('/login', [AuthController::class, 'login'])->name('auth.login');

Route::prefix('auth')->group(function () {
    Route::post('/challenge', [AuthController::class, 'challenge'])->name('auth.challenge');
    Route::post('/verify', [AuthController::class, 'verify'])->name('auth.verify');

});

Route::prefix('products')->group(function () {
    Route::get('/{productId}', [ProductController::class, 'show']);
    Route::get('/{productId}/history', [ProductController::class, 'history']);
});

Route::get('/api/enums', [EnumController::class, 'index'])->name('api.enums');

Route::get('/api/enums/event-types', [EnumController::class, 'eventTypes'])->name('api.enums.eventTypes');
Route::get('/api/enums/trust-levels', [EnumController::class, 'trustLevels'])->name('api.enums.trustLevels');
Route::get('/verify/{passportNumber}', [PassportController::class, 'publicVerify'])
    ->name('passport.verify');

// API: verifica documento
Route::post('/api/verify-document/{eventId}', [PassportController::class, 'verifyDocument'])
    ->name('api.verify-document');

Route::get('/api/enums/events', [EnumController::class, 'eventEnums']);
Route::get('/', function () {
    return Inertia::render('Public/Landing');
})->name('landing');

// API ricerca pubblica
Route::get('/api/public/search', [PublicController::class, 'search']);

// API: stato passaporto (per widget)
Route::get('/api/passport/{passportNumber}/status', [PassportController::class, 'publicStatus'])
    ->name('api.passport.status');

Route::middleware('auth')->group(function () {

    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::prefix('admin')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/products', [ProductController::class, 'index']);
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::post('products/{product}', [ProductController::class, 'update']);
        Route::patch('/products/{id}/confirm', [ProductController::class, 'confirm'])->name('products.confirm'); // Conferma on-chain
        Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');
   Route::post('/products/{productId}/events', [EventController::class, 'store'])
        ->name('events.store');
    Route::patch('/events/{id}/confirm', [EventController::class, 'confirm'])
        ->name('events.confirm');
    Route::delete('/events/{id}', [EventController::class, 'destroy'])
        ->name('events.destroy');
        // routes/web.php
Route::post('/admin/products/{productId}/upload-document', [EventController::class, 'uploadDocument'])->name('events.upload_document');
        Route::get('/products/{productId}/events', [EventController::class, 'index']);
        Route::get('/products/{productId}/events/{index}/download', [EventController::class, 'downloadDocument']);
    });

    Route::get('/passports', [PassportController::class, 'index'])
        ->name('passports.index');
    
    // Verifica eleggibilità prodotto
    Route::get('/products/{productId}/passport-eligibility', [PassportController::class, 'checkEligibility'])
        ->name('passports.check-eligibility');
    
    // Richiedi passaporto
    Route::post('/products/{productId}/passport', [PassportController::class, 'request'])
        ->name('passports.request');
    
    // Dettaglio passaporto
    Route::get('/passports/{id}', [PassportController::class, 'show'])
        ->name('passports.show');

});

