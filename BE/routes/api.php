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
use App\Http\Controllers\Api\Pt\PtAssignmentController;
use App\Http\Controllers\Api\Pt\PtChatController;
use App\Http\Controllers\Api\Pt\PtDirectServiceController;
use App\Http\Controllers\Api\Workout\WorkoutController;
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

Route::middleware(['auth:api', 'role:ADMIN'])->prefix('pt/assignments')->group(function (): void {
    Route::post('/', [PtAssignmentController::class, 'tao']);
    Route::patch('/{assignment}/end', [PtAssignmentController::class, 'ketThuc'])->whereNumber('assignment');
    Route::post('/{assignment}/reassign', [PtAssignmentController::class, 'phanCongLai'])->whereNumber('assignment');
});

Route::middleware(['auth:api', 'role:MEMBER'])
    ->get('/pt/assignment', [PtAssignmentController::class, 'cuaHoiVien']);

Route::middleware(['auth:api', 'role:PT'])
    ->get('/pt/members', [PtAssignmentController::class, 'thanhVienCuaHuanLuyenVien']);

Route::middleware(['auth:api', 'role:MEMBER,PT'])
    ->get('/pt/direct-sessions', [PtDirectServiceController::class, 'lichSu']);

Route::middleware(['auth:api', 'role:PT'])
    ->post('/pt/direct-sessions/complete', [PtDirectServiceController::class, 'hoanTat']);

Route::middleware(['auth:api', 'role:MEMBER,PT'])->prefix('pt/chat')->group(function (): void {
    Route::get('/conversations', [PtChatController::class, 'danhSach']);
    Route::post('/conversations/current', [PtChatController::class, 'hienTai']);
    Route::get('/conversations/{conversation}', [PtChatController::class, 'chiTiet'])
        ->whereNumber('conversation');
    Route::get('/conversations/{conversation}/messages', [PtChatController::class, 'tinNhans'])
        ->whereNumber('conversation');
    Route::post('/conversations/{conversation}/messages', [PtChatController::class, 'gui'])
        ->whereNumber('conversation');
});

Route::middleware(['auth:api', 'role:MEMBER'])->prefix('workout')->group(function (): void {
    Route::get('/templates', [WorkoutController::class, 'templates']);
    Route::get('/templates/{template}', [WorkoutController::class, 'template'])->whereNumber('template');
    Route::get('/plans/current', [WorkoutController::class, 'currentPlan']);
    Route::get('/plans', [WorkoutController::class, 'plans']);
    Route::get('/plans/{plan}', [WorkoutController::class, 'plan'])->whereNumber('plan');
    Route::get('/schedule', [WorkoutController::class, 'schedule']);
    Route::post('/scheduled-sessions/{scheduled}/start', [WorkoutController::class, 'start'])->whereNumber('scheduled');
    Route::post('/scheduled-sessions/{scheduled}/skip', [WorkoutController::class, 'skip'])->whereNumber('scheduled');
    Route::get('/sessions', [WorkoutController::class, 'sessions']);
    Route::get('/sessions/{session}', [WorkoutController::class, 'session'])->whereNumber('session');
    Route::post('/sessions/{session}/exercises/{exercise}/sets', [WorkoutController::class, 'recordSet'])->whereNumber(['session', 'exercise']);
    Route::post('/sessions/{session}/complete', [WorkoutController::class, 'complete'])->whereNumber('session');
});
