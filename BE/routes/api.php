<?php

use App\Http\Controllers\Api\Admin\AccountController;
use App\Http\Controllers\Api\Admin\AdminPaymentController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\ExerciseCatalogController;
use App\Http\Controllers\Api\Admin\PackageCatalogController;
use App\Http\Controllers\Api\Admin\TrainerOnboardingController;
use App\Http\Controllers\Api\Admin\WorkoutTemplateCatalogController;
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
use App\Http\Controllers\Api\Progress\ProgressController;
use App\Http\Controllers\Api\Pt\PtAssignmentController;
use App\Http\Controllers\Api\Pt\PtChatController;
use App\Http\Controllers\Api\Pt\PtDirectServiceController;
use App\Http\Controllers\Api\Pt\PtProposalController;
use App\Http\Controllers\Api\Workout\WorkoutController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'dangKy'])
        ->middleware('throttle:dang-ky');
    Route::post('/login', [AuthController::class, 'dangNhap'])
        ->middleware('throttle:dang-nhap');
    Route::post('/forgot-password', [AuthController::class, 'quenMatKhau'])
        ->middleware('throttle:quen-mat-khau');
    Route::post('/reset-password', [AuthController::class, 'datLaiMatKhau'])
        ->middleware('throttle:dat-lai-mat-khau');

    Route::middleware('auth:api')->group(function (): void {
        Route::get('/me', [AuthController::class, 'thongTinHienTai']);
        Route::post('/logout', [AuthController::class, 'dangXuat']);
    });
});

Route::middleware(['auth:api', 'role:ADMIN'])->prefix('admin/accounts')->group(function (): void {
    Route::get('/', [AccountController::class, 'danhSach']);
    Route::get('/{account}', [AccountController::class, 'chiTiet'])->whereNumber('account');
    Route::patch('/{account}/status', [AccountController::class, 'capNhatTrangThai'])->whereNumber('account');
    Route::put('/{account}/roles/{role}', [AccountController::class, 'ganVaiTro'])
        ->whereNumber('account')
        ->whereIn('role', ['MEMBER', 'PT', 'RECEPTIONIST', 'ADMIN']);
    Route::delete('/{account}/roles/{role}', [AccountController::class, 'thuHoiVaiTro'])
        ->whereNumber('account')
        ->whereIn('role', ['MEMBER', 'PT', 'RECEPTIONIST', 'ADMIN']);
    Route::post('/{account}/trainer-profile', [TrainerOnboardingController::class, 'onboardTaiKhoanDaCo'])
        ->whereNumber('account');
});

Route::middleware(['auth:api', 'role:ADMIN'])->prefix('admin')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'tongQuan']);
    Route::post('/trainers', [TrainerOnboardingController::class, 'tao']);
    Route::get('/payments', [AdminPaymentController::class, 'danhSach']);
    Route::get('/payments/{payment}', [AdminPaymentController::class, 'chiTiet'])->whereNumber('payment');
    Route::get('/payment-events', [AdminPaymentController::class, 'suKiens']);

    Route::prefix('packages')->group(function (): void {
        Route::get('/', [PackageCatalogController::class, 'danhSach']);
        Route::post('/', [PackageCatalogController::class, 'tao']);
        Route::get('/{package}', [PackageCatalogController::class, 'chiTiet'])->whereNumber('package');
        Route::patch('/{package}', [PackageCatalogController::class, 'capNhat'])->whereNumber('package');
        Route::put('/{package}/benefits', [PackageCatalogController::class, 'capNhatQuyenLoi'])
            ->whereNumber('package');
    });

    Route::prefix('equipment')->group(function (): void {
        Route::get('/', [ExerciseCatalogController::class, 'danhSachDungCu']);
        Route::post('/', [ExerciseCatalogController::class, 'taoDungCu']);
        Route::patch('/{equipment}', [ExerciseCatalogController::class, 'capNhatDungCu'])
            ->whereNumber('equipment');
    });

    Route::prefix('muscle-groups')->group(function (): void {
        Route::get('/', [ExerciseCatalogController::class, 'danhSachNhomCo']);
        Route::post('/', [ExerciseCatalogController::class, 'taoNhomCo']);
        Route::patch('/{muscleGroup}', [ExerciseCatalogController::class, 'capNhatNhomCo'])
            ->whereNumber('muscleGroup');
    });

    Route::prefix('exercises')->group(function (): void {
        Route::get('/', [ExerciseCatalogController::class, 'danhSachBaiTap']);
        Route::post('/', [ExerciseCatalogController::class, 'taoBaiTap']);
        Route::get('/{exercise}', [ExerciseCatalogController::class, 'chiTietBaiTap'])->whereNumber('exercise');
        Route::patch('/{exercise}', [ExerciseCatalogController::class, 'capNhatBaiTap'])->whereNumber('exercise');
    });

    Route::prefix('workout-templates')->group(function (): void {
        Route::get('/', [WorkoutTemplateCatalogController::class, 'danhSach']);
        Route::post('/', [WorkoutTemplateCatalogController::class, 'tao']);
        Route::get('/{workoutTemplate}', [WorkoutTemplateCatalogController::class, 'chiTiet'])
            ->whereNumber('workoutTemplate');
        Route::patch('/{workoutTemplate}', [WorkoutTemplateCatalogController::class, 'capNhat'])
            ->whereNumber('workoutTemplate');
        Route::post('/{workoutTemplate}/revisions', [WorkoutTemplateCatalogController::class, 'taoPhienBanMoi'])
            ->whereNumber('workoutTemplate');
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
    Route::post('/proposals/{proposal}/apply', [AiRequestController::class, 'apDung'])
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

Route::middleware(['auth:api', 'role:PT'])->prefix('pt/members/{member}')->group(function (): void {
    Route::get('/proposals', [PtProposalController::class, 'danhSachCuaPt'])->whereNumber('member');
    Route::post('/proposals', [PtProposalController::class, 'tao'])->whereNumber('member');
    Route::get('/notes', [PtProposalController::class, 'ghiChusCuaPt'])->whereNumber('member');
    Route::post('/notes', [PtProposalController::class, 'taoGhiChu'])->whereNumber('member');
    Route::get('/progress/overview', [ProgressController::class, 'tongQuanCuaPt'])->whereNumber('member');
    Route::get('/progress/body', [ProgressController::class, 'chiSosCuaPt'])->whereNumber('member');
    Route::get('/progress/exercises/{exercise}', [ProgressController::class, 'baiTapCuaPt'])
        ->whereNumber(['member', 'exercise']);
});

Route::middleware(['auth:api', 'role:MEMBER'])->prefix('pt')->group(function (): void {
    Route::get('/proposals', [PtProposalController::class, 'danhSach']);
    Route::get('/proposals/{proposal}', [PtProposalController::class, 'chiTiet'])->whereNumber('proposal');
    Route::post('/proposals/{proposal}/confirm', [PtProposalController::class, 'xacNhan'])->whereNumber('proposal');
    Route::post('/proposals/{proposal}/reject', [PtProposalController::class, 'tuChoi'])->whereNumber('proposal');
    Route::get('/notes', [PtProposalController::class, 'ghiChus']);
});

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

Route::middleware(['auth:api', 'role:MEMBER'])->prefix('progress')->group(function (): void {
    Route::post('/body', [ProgressController::class, 'taoChiSo']);
    Route::get('/body/latest', [ProgressController::class, 'chiSoMoiNhat']);
    Route::get('/body', [ProgressController::class, 'chiSos']);
    Route::get('/overview', [ProgressController::class, 'tongQuan']);
    Route::get('/exercises/{exercise}', [ProgressController::class, 'baiTap'])->whereNumber('exercise');
});
