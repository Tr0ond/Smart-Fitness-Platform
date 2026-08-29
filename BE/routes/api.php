<?php

use App\Http\Controllers\Api\Ai\AiRequestController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Gym\GymController;
use App\Http\Controllers\Api\Membership\MembershipController;
use App\Http\Controllers\Api\Package\PackageController;
use App\Http\Controllers\Api\Payment\OrderController;
use App\Http\Controllers\Api\Payment\PayOSWebhookController;
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

Route::middleware('auth:api')->prefix('packages')->group(function (): void {
    Route::get('/', [PackageController::class, 'danhSach']);
    Route::get('/{package}', [PackageController::class, 'chiTiet'])->whereNumber('package');
});

Route::post('/webhooks/payos', [PayOSWebhookController::class, 'xuLy']);

Route::middleware(['auth:api', 'role:MEMBER'])->group(function (): void {
    Route::post('/packages/{package}/orders', [OrderController::class, 'tao'])->whereNumber('package');
    Route::get('/orders', [OrderController::class, 'danhSach']);
    Route::get('/orders/{order}', [OrderController::class, 'chiTiet'])->whereNumber('order');
    Route::get('/orders/{order}/payment', [OrderController::class, 'thanhToan'])->whereNumber('order');
});

Route::middleware(['auth:api', 'role:MEMBER'])
    ->get('/membership', [MembershipController::class, 'hienThi']);

Route::middleware(['auth:api', 'role:MEMBER'])->prefix('gym')->group(function (): void {
    Route::post('/qr', [GymController::class, 'phatHanhQr']);
    Route::get('/check-ins', [GymController::class, 'lichSu']);
});

Route::middleware(['auth:api', 'role:MEMBER'])->prefix('assistant')->group(function (): void {
    Route::post('/requests', [AiRequestController::class, 'tao']);
    Route::get('/requests', [AiRequestController::class, 'danhSach']);
    Route::get('/requests/{assistantRequest}', [AiRequestController::class, 'chiTiet'])
        ->whereNumber('assistantRequest');
    Route::get('/proposals/{proposal}', [AiRequestController::class, 'deXuat'])
        ->whereNumber('proposal');
});

Route::middleware(['auth:api', 'role:RECEPTIONIST,ADMIN'])
    ->post('/gym/check-in', [GymController::class, 'xacNhan']);
