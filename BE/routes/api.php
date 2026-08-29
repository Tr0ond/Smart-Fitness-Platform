<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Profile\MemberProfileController;
use App\Http\Controllers\Api\Profile\ProfileController;
use App\Http\Controllers\Api\Profile\TrainerProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'dangNhap'])
        ->middleware('throttle:dang-nhap');

    Route::middleware('auth:api')->group(function (): void {
        Route::get('/me', [AuthController::class, 'thongTinHienTai']);
        Route::post('/logout', [AuthController::class, 'dangXuat']);
    });
});

Route::middleware('auth:api')->prefix('profile')->group(function (): void {
    Route::get('/', [ProfileController::class, 'hienThi']);
    Route::patch('/', [ProfileController::class, 'capNhat']);

    Route::middleware('role:MEMBER')->prefix('member')->group(function (): void {
        Route::get('/', [MemberProfileController::class, 'hienThi']);
        Route::patch('/', [MemberProfileController::class, 'capNhat']);
        Route::get('/availability', [MemberProfileController::class, 'lichRanh']);
        Route::put('/availability', [MemberProfileController::class, 'thayTheLichRanh']);
        Route::get('/equipment', [MemberProfileController::class, 'dungCu']);
        Route::put('/equipment', [MemberProfileController::class, 'thayTheDungCu']);
    });

    Route::middleware('role:PT')->prefix('trainer')->group(function (): void {
        Route::get('/', [TrainerProfileController::class, 'hienThi']);
        Route::patch('/', [TrainerProfileController::class, 'capNhat']);
    });
});
