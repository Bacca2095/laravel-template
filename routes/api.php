<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\PasskeyController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [RegisteredUserController::class, 'store'])->middleware('guest');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('guest');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth:sanctum');

    Route::post('otp/request', [OtpController::class, 'store'])->middleware('auth:sanctum');
    Route::post('otp/verify', [OtpController::class, 'verify']);

    Route::post('passkeys/options', [PasskeyController::class, 'options'])->middleware('auth:sanctum');
    Route::post('passkeys/register', [PasskeyController::class, 'register'])->middleware('auth:sanctum');
    Route::post('passkeys/authenticate/options', [PasskeyController::class, 'authenticationOptions']);
    Route::post('passkeys/authenticate', [PasskeyController::class, 'authenticate']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/me', [AuthenticatedSessionController::class, 'me']);
});
