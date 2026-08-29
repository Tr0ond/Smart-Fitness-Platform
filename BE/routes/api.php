<?php

use App\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'dangNhap'])
        ->middleware('throttle:dang-nhap');

    Route::middleware('auth:api')->group(function (): void {
        Route::get('/me', [AuthController::class, 'thongTinHienTai']);
        Route::post('/logout', [AuthController::class, 'dangXuat']);
    });
});
