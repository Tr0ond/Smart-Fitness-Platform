<?php

namespace App\Services\Pt\Chat;

use App\Exceptions\Chat\PtChatWorkflowException;
use App\Exceptions\MembershipLifecycleException;
use App\Models\HoiThoai;
use App\Models\HoSoHoiVien;
use App\Models\HoSoHuanLuyenVien;
use App\Models\NguoiDung;
use App\Models\PhanCongHuanLuyenVien;
use App\Services\MembershipEntitlementService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PtChatConversationService
{
    public function __construct(
        private readonly PtChatAuthorizationService $phanQuyen,
        private readonly PtChatQueryService $truyVan,
        private readonly MembershipEntitlementService $quyenLoi,
    ) {}

    /**
     * Materialize hội thoại từ principal + assignment đã được xác minh.
     * Thao tác này không tạo usage và không kích hoạt Membership.
     *
     * @return array<string, mixed>
     */
    public function hienTai(NguoiDung $nguoiDung, ?int $phanCongId = null): array
    {
        $hienTai = CarbonImmutable::now('UTC');
        [$phanCongBanDau, $loaiActor] = $this->timPhanCongBanDau($nguoiDung, $phanCongId, $hienTai);

        $hoiThoaiId = DB::transaction(function () use (
            $nguoiDung,
            $phanCongBanDau,
            $loaiActor,
            $hienTai,
        ): int {
            $hoiVien = HoSoHoiVien::query()
                ->with('nguoiDung')
                ->lockForUpdate()
                ->find($phanCongBanDau->hoi_vien_id);
            if (! $hoiVien instanceof HoSoHoiVien || $hoiVien->nguoiDung?->trang_thai !== 'HOAT_DONG') {
                throw new PtChatWorkflowException(
                    'Tài khoản hội viên không hoạt động.',
                    409,
                    'CHAT_MEMBER_NOT_ACTIVE',
                );
            }

            $cacPhanCong = PhanCongHuanLuyenVien::query()
                ->where('hoi_vien_id', $hoiVien->getKey())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            // Dùng clock sau khi lấy các khóa có thể phải chờ để bảo toàn biên [start,end).
            $hienTai = CarbonImmutable::now('UTC');
            $phanCong = $cacPhanCong->firstWhere('id', (int) $phanCongBanDau->getKey());
            if (! $phanCong instanceof PhanCongHuanLuyenVien || ! $this->dangHieuLuc($phanCong, $hienTai)) {
                throw new PtChatWorkflowException(
                    'Phân công PT không còn hiệu lực.',
                    409,
                    'CHAT_ASSIGNMENT_NOT_ACTIVE',
                );
            }

            $huanLuyenVien = HoSoHuanLuyenVien::query()
                ->with('nguoiDung')
                ->lockForUpdate()
                ->find($phanCong->huan_luyen_vien_id);
            $this->damBaoActorHopLe($nguoiDung, $loaiActor, $hoiVien, $huanLuyenVien);

            try {
                $quyen = $this->quyenLoi->kiemTra(
                    (int) $hoiVien->getKey(),
                    MembershipEntitlementService::TRO_CHUYEN_HUAN_LUYEN,
                    $hienTai,
                    true,
                );
            } catch (MembershipLifecycleException) {
                throw $this->khongCoQuyenChat();
            }
            if (! $quyen['allowed']) {
                throw $this->khongCoQuyenChat();
            }

            $hoiThoai = HoiThoai::query()
                ->where('phan_cong_huan_luyen_vien_id', $phanCong->getKey())
                ->lockForUpdate()
                ->first();
            if (! $hoiThoai instanceof HoiThoai) {
                $hoiThoai = HoiThoai::query()->create([
                    'hoi_vien_id' => $hoiVien->getKey(),
                    'huan_luyen_vien_id' => $huanLuyenVien->getKey(),
                    'phan_cong_huan_luyen_vien_id' => $phanCong->getKey(),
                    'so_thu_tu_cuoi' => 0,
                    'hoi_vien_doc_den_so' => 0,
                    'huan_luyen_vien_doc_den_so' => 0,
                    'ngay_tao' => $hienTai,
                    'ngay_cap_nhat' => $hienTai,
                ]);
            }

            return (int) $hoiThoai->getKey();
        }, 3);

        return $this->truyVan->chiTiet($nguoiDung, $hoiThoaiId);
    }

    /** @return array{0: PhanCongHuanLuyenVien, 1: string} */
    private function timPhanCongBanDau(
        NguoiDung $nguoiDung,
        ?int $phanCongId,
        CarbonImmutable $hienTai,
    ): array {
        if ($phanCongId !== null && $this->phanQuyen->coVaiTro($nguoiDung, PtChatAuthorizationService::PT)) {
            $huanLuyenVien = HoSoHuanLuyenVien::query()
                ->where('nguoi_dung_id', $nguoiDung->getKey())
                ->first();
            $phanCong = PhanCongHuanLuyenVien::query()->find($phanCongId);
            if (! $huanLuyenVien instanceof HoSoHuanLuyenVien
                || ! $phanCong instanceof PhanCongHuanLuyenVien
                || (int) $phanCong->huan_luyen_vien_id !== (int) $huanLuyenVien->getKey()) {
                throw $this->khongTimThay();
            }

            return [$phanCong, PtChatAuthorizationService::PT];
        }

        if ($this->phanQuyen->coVaiTro($nguoiDung, PtChatAuthorizationService::MEMBER)) {
            if ($phanCongId !== null) {
                throw new PtChatWorkflowException(
                    'Member không được chọn assignment từ request.',
                    422,
                    'CHAT_ASSIGNMENT_FIELD_NOT_ALLOWED',
                );
            }
            $hoiVien = HoSoHoiVien::query()->where('nguoi_dung_id', $nguoiDung->getKey())->first();
            if (! $hoiVien instanceof HoSoHoiVien) {
                throw $this->khongTimThay();
            }
            $phanCong = $this->phanCongDangHieuLuc(
                PhanCongHuanLuyenVien::query()->where('hoi_vien_id', $hoiVien->getKey())->get(),
                $hienTai,
            )->first();
            if (! $phanCong instanceof PhanCongHuanLuyenVien) {
                throw new PtChatWorkflowException(
                    'Hội viên chưa có phân công PT đang hiệu lực.',
                    409,
                    'CHAT_ASSIGNMENT_NOT_ACTIVE',
                );
            }

            return [$phanCong, PtChatAuthorizationService::MEMBER];
        }

        if ($this->phanQuyen->coVaiTro($nguoiDung, PtChatAuthorizationService::PT)) {
            $huanLuyenVien = HoSoHuanLuyenVien::query()
                ->where('nguoi_dung_id', $nguoiDung->getKey())
                ->first();
            if (! $huanLuyenVien instanceof HoSoHuanLuyenVien) {
                throw $this->khongTimThay();
            }
            $cacPhanCong = $this->phanCongDangHieuLuc(
                PhanCongHuanLuyenVien::query()
                    ->where('huan_luyen_vien_id', $huanLuyenVien->getKey())
                    ->get(),
                $hienTai,
            );
            if ($cacPhanCong->count() !== 1) {
                throw new PtChatWorkflowException(
                    'PT phải chọn assignment_id khi không có đúng một phân công hiện tại.',
                    422,
                    'CHAT_ASSIGNMENT_REQUIRED',
                );
            }

            return [$cacPhanCong->first(), PtChatAuthorizationService::PT];
        }

        throw $this->khongTimThay();
    }

    private function damBaoActorHopLe(
        NguoiDung $nguoiDung,
        string $loaiActor,
        HoSoHoiVien $hoiVien,
        ?HoSoHuanLuyenVien $huanLuyenVien,
    ): void {
        if (! $huanLuyenVien instanceof HoSoHuanLuyenVien
            || $huanLuyenVien->trang_thai !== 'HOAT_DONG'
            || $huanLuyenVien->nguoiDung?->trang_thai !== 'HOAT_DONG') {
            throw new PtChatWorkflowException(
                'Hồ sơ PT không hoạt động.',
                409,
                'CHAT_TRAINER_NOT_ACTIVE',
            );
        }

        $hopLe = $loaiActor === PtChatAuthorizationService::MEMBER
            ? (int) $hoiVien->nguoi_dung_id === (int) $nguoiDung->getKey()
                && $this->phanQuyen->coVaiTro($nguoiDung, PtChatAuthorizationService::MEMBER)
            : (int) $huanLuyenVien->nguoi_dung_id === (int) $nguoiDung->getKey()
                && $this->phanQuyen->coVaiTro($nguoiDung, PtChatAuthorizationService::PT);
        if (! $hopLe) {
            throw $this->khongTimThay();
        }
    }

    /** @param Collection<int, PhanCongHuanLuyenVien> $cacPhanCong */
    private function phanCongDangHieuLuc(Collection $cacPhanCong, CarbonImmutable $hienTai): Collection
    {
        return $cacPhanCong
            ->filter(fn (PhanCongHuanLuyenVien $phanCong): bool => $this->dangHieuLuc($phanCong, $hienTai))
            ->values();
    }

    private function dangHieuLuc(PhanCongHuanLuyenVien $phanCong, CarbonImmutable $hienTai): bool
    {
        return CarbonImmutable::instance($phanCong->ngay_bat_dau)->lessThanOrEqualTo($hienTai)
            && ($phanCong->ngay_ket_thuc === null
                || $hienTai->lessThan(CarbonImmutable::instance($phanCong->ngay_ket_thuc)));
    }

    private function khongCoQuyenChat(): PtChatWorkflowException
    {
        return new PtChatWorkflowException(
            'Kỳ Membership hiện tại không cấp quyền Chat PT.',
            409,
            'CHAT_ENTITLEMENT_DENIED',
        );
    }

    private function khongTimThay(): PtChatWorkflowException
    {
        return new PtChatWorkflowException(
            'Không tìm thấy phân công trong phạm vi truy cập.',
            404,
            'CHAT_ASSIGNMENT_NOT_FOUND',
        );
    }
}
