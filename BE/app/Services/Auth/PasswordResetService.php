<?php

namespace App\Services\Auth;

use App\Exceptions\Auth\AuthWorkflowException;
use App\Jobs\ProcessPasswordResetRequest;
use App\Models\NguoiDung;
use App\Models\TheTruyCap;
use App\Models\YeuCauDatLaiMatKhau;
use App\Notifications\PasswordResetNotification;
use App\Support\EmailCanonicalizer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

class PasswordResetService
{
    public function __construct(private readonly EmailCanonicalizer $emailCanonicalizer) {}

    /**
     * HTTP path chỉ chuẩn hóa email rồi xếp đúng một job, không lookup account
     * hay gửi mail. Response vì vậy không phụ thuộc email có tồn tại hay không.
     */
    public function yeuCau(string $thuDienTu): void
    {
        try {
            ProcessPasswordResetRequest::dispatch(
                $this->emailCanonicalizer->chuanHoa($thuDienTu),
                (string) Str::uuid(),
            );
        } catch (Throwable $exception) {
            Log::warning('Không thể xếp yêu cầu đặt lại mật khẩu.', ['loai_loi' => $exception::class]);

            throw new AuthWorkflowException(
                'Hệ thống chưa thể tiếp nhận yêu cầu đặt lại mật khẩu. Vui lòng thử lại sau.',
                503,
                'PASSWORD_RESET_QUEUE_UNAVAILABLE',
            );
        }
    }

    /** Lookup, token generation and mail occur only in the queue worker. */
    public function xuLyYeuCauHangDoi(string $thuDienTu, string $correlationId): void
    {
        $nguoiDung = NguoiDung::query()->where('thu_dien_tu', $thuDienTu)->first();
        if ($nguoiDung === null) {
            return;
        }

        $duLieuGui = DB::transaction(function () use ($nguoiDung): array {
            $nguoiDungDaKhoa = NguoiDung::query()->lockForUpdate()->find($nguoiDung->getKey());
            if ($nguoiDungDaKhoa === null) {
                return [];
            }

            $hienTai = CarbonImmutable::now('UTC');
            YeuCauDatLaiMatKhau::query()
                ->where('nguoi_dung_id', $nguoiDungDaKhoa->getKey())
                ->whereNull('da_su_dung_luc')
                ->whereNull('thu_hoi_luc')
                ->update(['thu_hoi_luc' => $hienTai, 'ngay_cap_nhat' => $hienTai]);

            do {
                $rawToken = bin2hex(random_bytes(32));
                $maBam = hash('sha256', $rawToken);
            } while (YeuCauDatLaiMatKhau::query()->where('ma_bam_xac_nhan', $maBam)->exists());

            $soPhut = max(1, (int) config('auth.password_reset_lifetime_minutes', 30));
            $hetHanLuc = $hienTai->addMinutes($soPhut);
            YeuCauDatLaiMatKhau::query()->create([
                'nguoi_dung_id' => $nguoiDungDaKhoa->getKey(),
                'ma_bam_xac_nhan' => $maBam,
                'het_han_luc' => $hetHanLuc,
                'da_su_dung_luc' => null,
                'thu_hoi_luc' => null,
                'ngay_tao' => $hienTai,
                'ngay_cap_nhat' => $hienTai,
            ]);

            return [
                'user_id' => (int) $nguoiDungDaKhoa->getKey(),
                'email' => (string) $nguoiDungDaKhoa->thu_dien_tu,
                'raw_token' => $rawToken,
                'expires_at' => $hetHanLuc,
            ];
        }, 3);

        if ($duLieuGui === []) {
            return;
        }

        try {
            Notification::route('mail', $duLieuGui['email'])->notify(
                new PasswordResetNotification($duLieuGui['raw_token'], $duLieuGui['expires_at']),
            );
        } catch (Throwable $exception) {
            Log::warning('Không thể chuyển thông báo đặt lại mật khẩu.', [
                'nguoi_dung_id' => $duLieuGui['user_id'],
                'correlation_id' => $correlationId,
                'loai_loi' => $exception::class,
            ]);
        }
    }

    /** Consume token một lần, đổi hash mật khẩu và thu hồi mọi phiên hiện hữu. */
    public function datLai(string $rawToken, string $matKhauMoi): void
    {
        DB::transaction(function () use ($rawToken, $matKhauMoi): void {
            $hienTai = CarbonImmutable::now('UTC');
            $yeuCau = YeuCauDatLaiMatKhau::query()
                ->where('ma_bam_xac_nhan', hash('sha256', $rawToken))
                ->lockForUpdate()
                ->first();

            if (
                $yeuCau === null
                || $yeuCau->da_su_dung_luc !== null
                || $yeuCau->thu_hoi_luc !== null
                || ! $hienTai->lessThan(CarbonImmutable::instance($yeuCau->het_han_luc))
            ) {
                throw new AuthWorkflowException(
                    'Thông tin đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.',
                    422,
                    'INVALID_PASSWORD_RESET_TOKEN',
                );
            }

            $nguoiDung = NguoiDung::query()->lockForUpdate()->find($yeuCau->nguoi_dung_id);
            if ($nguoiDung === null) {
                throw new AuthWorkflowException(
                    'Thông tin đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.',
                    422,
                    'INVALID_PASSWORD_RESET_TOKEN',
                );
            }

            $nguoiDung->forceFill([
                'mat_khau_bam' => Hash::make($matKhauMoi),
                'ngay_cap_nhat' => $hienTai,
            ])->save();
            $yeuCau->forceFill([
                'da_su_dung_luc' => $hienTai,
                'ngay_cap_nhat' => $hienTai,
            ])->save();

            YeuCauDatLaiMatKhau::query()
                ->where('nguoi_dung_id', $nguoiDung->getKey())
                ->whereKeyNot($yeuCau->getKey())
                ->whereNull('da_su_dung_luc')
                ->whereNull('thu_hoi_luc')
                ->update(['thu_hoi_luc' => $hienTai, 'ngay_cap_nhat' => $hienTai]);
            TheTruyCap::query()
                ->where('nguoi_dung_id', $nguoiDung->getKey())
                ->whereNull('thu_hoi_luc')
                ->update(['thu_hoi_luc' => $hienTai, 'ngay_cap_nhat' => $hienTai]);
        }, 3);
    }
}
