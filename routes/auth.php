<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
| Include in web.php: require __DIR__.'/auth.php';
*/

// Pagina login (solo guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
});

// API WebAuthn (no CSRF per chiamate JS)
Route::prefix('auth')->group(function () {
    // Registrazione
    Route::post('/register/options', [AuthController::class, 'registerOptions']);
    Route::post('/register/verify', [AuthController::class, 'registerVerify']);
    
    // Login
    Route::post('/login/options', [AuthController::class, 'loginOptions']);
    Route::post('/login/verify', [AuthController::class, 'loginVerify']);
    
    // Session check (pubblico)
    Route::get('/session', [AuthController::class, 'checkSession']);
});

// Logout (richiede auth)
Route::post('/auth/logout', [AuthController::class, 'logout'])
    ->middleware('auth');