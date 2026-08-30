<?php

namespace App\Services\Pt\Chat;

use App\Exceptions\Chat\PtChatWorkflowException;
use App\Exceptions\MembershipLifecycleException;
use App\Models\HoiThoai;
use App\Models\HoSoHoiVien;
use App\Models\HoSoHuanLuyenVien;
use App\Models\KyHanHoiVien;
use App\Models\NguoiDung;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\SuDungQuyenLoi;
use App\Models\SuKienPhatTinNhan;
use App\Models\TinNhan;
use App\Services\MembershipActivationService;
use App\Services\MembershipEntitlementService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class PtChatMessageService
{
    public function __construct(
        private readonly PtChatAuthorizationService $phanQuyen,
        private readonly PtChatQueryService $truyVan,
        private readonly PtChatDeliveryService $phatTin,
        private readonly MembershipEntitlementService $quyenLoi,
        private readonly MembershipActivationService $kichHoat,
    ) {}

    /**
     * @param  array{client_message_id: string, content: string}  $duLieu
     * @return array<string, mixed>
     */
    public function gui(NguoiDung $nguoiDung, int $hoiThoaiId, array $duLieu): array
    {
        $hoiThoaiBanDau = HoiThoai::query()->find($hoiThoaiId);
        if (! $hoiThoaiBanDau instanceof HoiThoai) {
            throw $this->khongTimThay();
        }

        $ketQua = DB::transaction(function () use (
            $nguoiDung,
            $hoiThoaiBanDau,
            $duLieu,
        ): array {
            $hoiVien = HoSoHoiVien::query()
                ->with('nguoiDung')
                ->lockForUpdate()
                ->find($hoiThoaiBanDau->hoi_vien_id);
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
            $hoiThoai = HoiThoai::query()->lockForUpdate()->find($hoiThoaiBanDau->getKey());
            if (! $hoiThoai instanceof HoiThoai) {
                throw $this->khongTimThay();
            }
            $loaiActor = $this->phanQuyen->loaiNguoiThamGia($nguoiDung, $hoiThoai, true);

            $phanCong = $cacPhanCong->firstWhere('id', (int) $hoiThoai->phan_cong_huan_luyen_vien_id);
            if (! $phanCong instanceof PhanCongHuanLuyenVien
                || (int) $phanCong->hoi_vien_id !== (int) $hoiThoai->hoi_vien_id
                || (int) $phanCong->huan_luyen_vien_id !== (int) $hoiThoai->huan_luyen_vien_id) {
                throw $this->khongTimThay();
            }

            $huanLuyenVien = HoSoHuanLuyenVien::query()
                ->with('nguoiDung')
                ->lockForUpdate()
                ->find($phanCong->huan_luyen_vien_id);
            // Đánh giá NOW sau khi đã lấy toàn bộ khóa assignment/participant;
            // thời gian chờ khóa không được làm lọt qua biên kết thúc hiệu lực.
            $hienTai = CarbonImmutable::now('UTC');
            if (! $this->dangHieuLuc($phanCong, $hienTai)) {
                throw new PtChatWorkflowException(
                    'Phân công PT không còn hiệu lực để gửi tin.',
                    409,
                    'CHAT_ASSIGNMENT_NOT_ACTIVE',
                );
            }
            if (! $huanLuyenVien instanceof HoSoHuanLuyenVien
                || $huanLuyenVien->trang_thai !== 'HOAT_DONG'
                || $huanLuyenVien->nguoiDung?->trang_thai !== 'HOAT_DONG') {
                throw new PtChatWorkflowException(
                    'PT không còn đủ điều kiện gửi tin.',
                    409,
                    'CHAT_TRAINER_NOT_ACTIVE',
                );
            }

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
            if (! $quyen['allowed'] || $quyen['term_id'] === null) {
                throw $this->khongCoQuyenChat();
            }

            $tinCu = TinNhan::query()
                ->where('hoi_thoai_id', $hoiThoai->getKey())
                ->where('nguoi_gui_id', $nguoiDung->getKey())
                ->where('ma_tin_nhan_phia_gui', $duLieu['client_message_id'])
                ->lockForUpdate()
                ->first();
            if ($tinCu instanceof TinNhan) {
                if (! hash_equals((string) $tinCu->noi_dung, $duLieu['content'])) {
                    throw new PtChatWorkflowException(
                        'Client message ID đã được dùng cho nội dung khác.',
                        409,
                        'CHAT_IDEMPOTENCY_CONFLICT',
                    );
                }

                return ['message_id' => (int) $tinCu->getKey(), 'replayed' => true];
            }

            $suDungId = null;
            if ($loaiActor === PtChatAuthorizationService::MEMBER && $quyen['can_activate']) {
                $ky = KyHanHoiVien::query()->lockForUpdate()->find($quyen['term_id']);
                if (! $ky instanceof KyHanHoiVien || (int) $ky->hoi_vien_id !== (int) $hoiVien->getKey()) {
                    throw $this->khongCoQuyenChat();
                }
                $suDung = SuDungQuyenLoi::query()->create([
                    'hoi_vien_id' => $hoiVien->getKey(),
                    'ky_han_hoi_vien_id' => $ky->getKey(),
                    'nguoi_thuc_hien_id' => $nguoiDung->getKey(),
                    'loai_su_dung' => MembershipEntitlementService::TRO_CHUYEN_HUAN_LUYEN,
                    'ma_hanh_dong' => $duLieu['client_message_id'],
                    'chap_nhan_luc' => $hienTai,
                    'ngay_tao' => $hienTai,
                ]);
                try {
                    $this->kichHoat->kichHoatNeuCan((int) $suDung->getKey());
                } catch (MembershipLifecycleException) {
                    throw new PtChatWorkflowException(
                        'Không thể kích hoạt Membership bằng tin Chat này.',
                        409,
                        'CHAT_MEMBERSHIP_STATE_CONFLICT',
                    );
                }
                $suDungId = (int) $suDung->getKey();
            }

            $soThuTu = (int) $hoiThoai->so_thu_tu_cuoi + 1;
            $tinNhan = TinNhan::query()->create([
                'hoi_thoai_id' => $hoiThoai->getKey(),
                'so_thu_tu' => $soThuTu,
                'nguoi_gui_id' => $nguoiDung->getKey(),
                'phan_cong_huan_luyen_vien_id' => $phanCong->getKey(),
                'su_dung_quyen_loi_id' => $suDungId,
                'ma_tin_nhan_phia_gui' => $duLieu['client_message_id'],
                'noi_dung' => $duLieu['content'],
                'gui_luc' => $hienTai,
                'hoi_vien_id' => $hoiVien->getKey(),
                'huan_luyen_vien_id' => $huanLuyenVien->getKey(),
                'ngay_tao' => $hienTai,
            ]);
            SuKienPhatTinNhan::query()->create([
                'tin_nhan_id' => $tinNhan->getKey(),
                'trang_thai' => 'CHO_PHAT',
                'so_lan_thu' => 0,
                'thu_lai_luc' => null,
                'phat_luc' => null,
                'loi_gan_nhat' => null,
                'ngay_tao' => $hienTai,
                'ngay_cap_nhat' => $hienTai,
            ]);
            $hoiThoai->forceFill([
                'so_thu_tu_cuoi' => $soThuTu,
                'ngay_cap_nhat' => $hienTai,
            ])->save();

            return ['message_id' => (int) $tinNhan->getKey(), 'replayed' => false];
        }, 3);

        $trangThaiPhat = $ketQua['replayed']
            ? (string) SuKienPhatTinNhan::query()
                ->where('tin_nhan_id', $ketQua['message_id'])
                ->value('trang_thai')
            : $this->phatTin->phat($ketQua['message_id']);
        $tinNhan = TinNhan::query()->findOrFail($ketQua['message_id']);

        return [
            'message' => $this->truyVan->duLieuTinNhan($tinNhan),
            'replayed' => $ketQua['replayed'],
            'realtime_delivery' => $trangThaiPhat,
        ];
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
            'Không tìm thấy hội thoại trong phạm vi truy cập.',
            404,
            'CHAT_CONVERSATION_NOT_FOUND',
        );
    }
}
