<?php

namespace Tests\Concerns;

use App\Models\HoiThoai;
use App\Models\KyHanHoiVien;
use App\Models\SuDungQuyenLoi;
use App\Models\SuKienPhatTinNhan;
use App\Models\TinNhan;
use App\Services\MembershipActivationService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

/**
 * Dựng dữ liệu PT Chat trên schema MariaDB kiểm thử cô lập.
 *
 * Test sử dụng trait này cần dùng kèm CreatesAuthenticationFixtures,
 * CreatesProfileFixtures, CreatesMembershipFixtures và CreatesPtFixtures.
 * Giao dịch bao ngoài vẫn do batDauGiaoDichAuthCoLap()/
 * ketThucGiaoDichAuthCoLap() quản lý giống các suite hiện có.
 */
trait CreatesPtChatFixtures
{
    /**
     * Tạo hai cặp Member/PT độc lập để bắt lỗi dùng nhầm user ID, profile ID
     * hoặc assignment ID. Có thể bỏ tạo assignment cho ca kiểm thử chưa phân công.
     *
     * @return array<string, mixed>
     */
    protected function taoBoPtChatFixtures(
        bool $taoPhanCong = true,
        bool $kemTheoToken = false,
    ): array {
        $this->damBaoDatabasePtChatCoLap();
        $fixture = $this->taoBoPtFixtures();

        $this->damBaoDinhDanhPtChatTachBiet($fixture);

        if ($taoPhanCong) {
            $fixture['assignment_a_id'] = $this->taoPhanCongPtChat(
                $fixture,
                $fixture['member_a_id'],
                $fixture['pt_a_id'],
            );
            $fixture['assignment_b_id'] = $this->taoPhanCongPtChat(
                $fixture,
                $fixture['member_b_id'],
                $fixture['pt_b_id'],
            );

            if ($fixture['assignment_a_id'] === $fixture['assignment_b_id']) {
                throw new LogicException('Fixture PT Chat phải có hai assignment ID khác nhau.');
            }
        }

        if ($kemTheoToken) {
            foreach (['admin', 'member_a', 'member_b', 'pt_a', 'pt_b'] as $actor) {
                $fixture[$actor.'_token'] = $this->layTokenPtChat($fixture[$actor]);
            }
        }

        return $fixture;
    }

    /**
     * Tạo phân công trực tiếp từ profile ID, lấy người phân công từ Admin fixture.
     */
    protected function taoPhanCongPtChat(
        array $fixture,
        int $hoiVienId,
        int $huanLuyenVienId,
        ?CarbonImmutable $batDauLuc = null,
        ?CarbonImmutable $ketThucLuc = null,
    ): int {
        $this->damBaoDatabasePtChatCoLap();

        return $this->taoPhanCongPt(
            $fixture,
            $hoiVienId,
            $huanLuyenVienId,
            $batDauLuc,
            $ketThucLuc,
        );
    }

    /**
     * Tạo kỳ Membership có snapshot Chat PT và quota buổi trực tiếp độc lập.
     *
     * Trang thái hỗ trợ:
     * - CHO_KICH_HOAT: kỳ đã thanh toán nhưng chưa có usage.
     * - DANG_HOAT_DONG: tạo một usage nguồn rồi kích hoạt qua service thật.
     *
     * Với kỳ hoạt động, loại nguồn mặc định VAO_PHONG_TAP giúp các test chứng
     * minh tin Chat đầu tiên sau đó không tạo usage Chat. Helper tự bật đúng
     * entitlement kỹ thuật cần cho nguồn kích hoạt; quyền Chat và quota buổi PT
     * vẫn giữ đúng hai tham số riêng.
     */
    protected function taoMembershipPtChat(
        array $fixture,
        int $hoiVienId,
        string $trangThai = 'CHO_KICH_HOAT',
        bool $choPhepChat = true,
        int $soBuoiTrucTiep = 0,
        ?CarbonImmutable $moc = null,
        string $loaiKichHoat = 'VAO_PHONG_TAP',
    ): KyHanHoiVien {
        $this->damBaoDatabasePtChatCoLap();
        if (! in_array($trangThai, ['CHO_KICH_HOAT', 'DANG_HOAT_DONG'], true)) {
            throw new InvalidArgumentException('Fixture chỉ hỗ trợ kỳ CHO_KICH_HOAT hoặc DANG_HOAT_DONG.');
        }
        if ($soBuoiTrucTiep < 0) {
            throw new InvalidArgumentException('Số buổi PT trực tiếp không được âm.');
        }

        $moc ??= CarbonImmutable::now('UTC');
        $quyenLoi = [
            'cho_phep_vao_phong_tap' => false,
            'cho_phep_tro_ly_tap_luyen' => false,
            'gioi_han_luot_tro_ly' => 0,
            'cho_phep_tro_chuyen_huan_luyen_vien' => $choPhepChat,
            'so_buoi_huan_luyen_vien' => $soBuoiTrucTiep,
        ];

        if ($trangThai === 'DANG_HOAT_DONG') {
            $this->chuanBiQuyenNguonKichHoatPtChat($quyenLoi, $loaiKichHoat);
        } elseif (! $choPhepChat && $soBuoiTrucTiep === 0) {
            // B18 yêu cầu một kỳ phải có ít nhất một quyền lợi trả phí.
            $quyenLoi['cho_phep_vao_phong_tap'] = true;
        }

        $goi = $this->taoGoiTapMembership($fixture['admin'], [], $quyenLoi);
        $ky = $this->taoDonVaSnapshotMembership($hoiVienId, $goi['package'], $moc);
        $this->xacNhanVaCapMembership($ky, $moc);
        $ky = $ky->fresh();

        if ($trangThai === 'DANG_HOAT_DONG') {
            $nguoiDungId = DB::table('ho_so_hoi_vien')
                ->where('id', $hoiVienId)
                ->value('nguoi_dung_id');
            if ($nguoiDungId === null) {
                throw new LogicException('Không tìm thấy tài khoản của Member fixture.');
            }

            $this->kichHoatMembershipPtChat(
                $ky,
                (int) $nguoiDungId,
                $loaiKichHoat,
                $moc,
            );
            $ky = $ky->fresh();
        }

        if ($ky->trang_thai !== $trangThai) {
            throw new LogicException(sprintf(
                'Kỳ PT Chat mong đợi %s nhưng nhận %s; Member có thể đã có chuỗi Membership khác.',
                $trangThai,
                $ky->trang_thai,
            ));
        }

        return $ky;
    }

    /**
     * Tạo usage nguồn và gọi MembershipActivationService thật để materialize
     * đồng hồ chung. Trả usage để test có thể đối chiếu nguồn kích hoạt.
     */
    protected function kichHoatMembershipPtChat(
        KyHanHoiVien $ky,
        int $nguoiDungId,
        string $loaiSuDung = 'VAO_PHONG_TAP',
        ?CarbonImmutable $moc = null,
    ): SuDungQuyenLoi {
        $usage = $this->taoUsageMembership($ky, $nguoiDungId, $loaiSuDung, $moc);
        app(MembershipActivationService::class)->kichHoatNeuCan((int) $usage->getKey());

        return $usage->fresh();
    }

    /** Lấy Bearer token thật qua endpoint đăng nhập hiện có. */
    protected function layTokenPtChat(array $actor): string
    {
        $token = (string) $this->dangNhapApi($actor['user']->thu_dien_tu)
            ->assertOk()
            ->json('data.access_token');
        if ($token === '') {
            throw new LogicException('Không tạo được Bearer token cho actor PT Chat.');
        }

        return $token;
    }

    /**
     * Tạo đúng một conversation cho assignment và sao chép tuple Member/PT từ
     * chính assignment để composite FK tiếp tục kiểm tra ownership.
     *
     * @param  array<string, mixed>  $ghiDe
     */
    protected function taoHoiThoaiPtChat(int $phanCongId, array $ghiDe = []): HoiThoai
    {
        $this->damBaoDatabasePtChatCoLap();
        $phanCong = DB::table('phan_cong_huan_luyen_vien')->where('id', $phanCongId)->first();
        if ($phanCong === null) {
            throw new InvalidArgumentException('Assignment fixture không tồn tại.');
        }
        $hienTai = CarbonImmutable::now('UTC');

        return HoiThoai::query()->create(array_merge([
            'hoi_vien_id' => (int) $phanCong->hoi_vien_id,
            'huan_luyen_vien_id' => (int) $phanCong->huan_luyen_vien_id,
            'phan_cong_huan_luyen_vien_id' => (int) $phanCong->id,
            'so_thu_tu_cuoi' => 0,
            'hoi_vien_doc_den_so' => 0,
            'huan_luyen_vien_doc_den_so' => 0,
            'ngay_tao' => $hienTai,
            'ngay_cap_nhat' => $hienTai,
        ], $ghiDe));
    }

    /**
     * Lưu một message fixture với sequence tăng dưới khóa conversation. Helper
     * chỉ nhận user thuộc đúng hai participant và luôn điền tuple FK denormalized.
     *
     * @param  array<string, mixed>|int  $nguoiGui
     * @param  array<string, mixed>  $ghiDe
     */
    protected function taoTinNhanPtChat(
        HoiThoai|int $hoiThoai,
        array|int $nguoiGui,
        string $noiDung = 'Tin nhắn fixture PT Chat',
        ?string $maTinNhanPhiaGui = null,
        ?int $suDungQuyenLoiId = null,
        ?CarbonImmutable $guiLuc = null,
        array $ghiDe = [],
    ): TinNhan {
        $this->damBaoDatabasePtChatCoLap();
        $hoiThoaiId = $hoiThoai instanceof HoiThoai ? (int) $hoiThoai->getKey() : $hoiThoai;
        $nguoiGuiId = is_array($nguoiGui)
            ? (int) $nguoiGui['user']->getKey()
            : $nguoiGui;

        return DB::transaction(function () use (
            $hoiThoaiId,
            $nguoiGuiId,
            $noiDung,
            $maTinNhanPhiaGui,
            $suDungQuyenLoiId,
            $guiLuc,
            $ghiDe,
        ): TinNhan {
            $hoiThoaiDaKhoa = HoiThoai::query()->lockForUpdate()->findOrFail($hoiThoaiId);
            $nguoiDungHoiVienId = DB::table('ho_so_hoi_vien')
                ->where('id', $hoiThoaiDaKhoa->hoi_vien_id)
                ->value('nguoi_dung_id');
            $nguoiDungPtId = DB::table('ho_so_huan_luyen_vien')
                ->where('id', $hoiThoaiDaKhoa->huan_luyen_vien_id)
                ->value('nguoi_dung_id');
            if (! in_array($nguoiGuiId, [(int) $nguoiDungHoiVienId, (int) $nguoiDungPtId], true)) {
                throw new InvalidArgumentException('Người gửi fixture không thuộc conversation PT Chat.');
            }

            $guiLucThucTe = $guiLuc ?? CarbonImmutable::now('UTC');
            $soThuTu = (int) $hoiThoaiDaKhoa->so_thu_tu_cuoi + 1;
            $tinNhan = TinNhan::query()->create(array_merge([
                'hoi_thoai_id' => (int) $hoiThoaiDaKhoa->getKey(),
                'so_thu_tu' => $soThuTu,
                'nguoi_gui_id' => $nguoiGuiId,
                'phan_cong_huan_luyen_vien_id' => (int) $hoiThoaiDaKhoa->phan_cong_huan_luyen_vien_id,
                'su_dung_quyen_loi_id' => $suDungQuyenLoiId,
                'ma_tin_nhan_phia_gui' => $maTinNhanPhiaGui ?? $this->uuidPtChat(),
                'noi_dung' => $noiDung,
                'gui_luc' => $guiLucThucTe,
                'hoi_vien_id' => (int) $hoiThoaiDaKhoa->hoi_vien_id,
                'huan_luyen_vien_id' => (int) $hoiThoaiDaKhoa->huan_luyen_vien_id,
                'ngay_tao' => $guiLucThucTe,
            ], $ghiDe));

            $hoiThoaiDaKhoa->forceFill([
                'so_thu_tu_cuoi' => $soThuTu,
                'ngay_cap_nhat' => $guiLucThucTe,
            ])->save();

            return $tinNhan;
        });
    }

    /**
     * Tạo outbox record tương ứng message; mặc định ở trạng thái chờ phát.
     *
     * @param  array<string, mixed>  $ghiDe
     */
    protected function taoSuKienPhatTinNhanPtChat(
        TinNhan|int $tinNhan,
        string $trangThai = 'CHO_PHAT',
        array $ghiDe = [],
    ): SuKienPhatTinNhan {
        $tinNhanId = $tinNhan instanceof TinNhan ? (int) $tinNhan->getKey() : $tinNhan;
        $hienTai = CarbonImmutable::now('UTC');

        return SuKienPhatTinNhan::query()->create(array_merge([
            'tin_nhan_id' => $tinNhanId,
            'trang_thai' => $trangThai,
            'so_lan_thu' => 0,
            'thu_lai_luc' => null,
            'phat_luc' => null,
            'loi_gan_nhat' => null,
            'ngay_tao' => $hienTai,
            'ngay_cap_nhat' => $hienTai,
        ], $ghiDe));
    }

    protected function uuidPtChat(): string
    {
        return (string) Str::uuid();
    }

    /** @param array<string, mixed> $quyenLoi */
    private function chuanBiQuyenNguonKichHoatPtChat(array &$quyenLoi, string $loaiKichHoat): void
    {
        match ($loaiKichHoat) {
            'VAO_PHONG_TAP' => $quyenLoi['cho_phep_vao_phong_tap'] = true,
            'YEU_CAU_TRO_LY' => [
                $quyenLoi['cho_phep_tro_ly_tap_luyen'] = true,
                $quyenLoi['gioi_han_luot_tro_ly'] = null,
            ],
            'BUOI_HUAN_LUYEN' => $quyenLoi['so_buoi_huan_luyen_vien'] > 0
                ?: throw new InvalidArgumentException('Nguồn BUOI_HUAN_LUYEN cần quota trực tiếp lớn hơn 0.'),
            'TRO_CHUYEN_HUAN_LUYEN' => $quyenLoi['cho_phep_tro_chuyen_huan_luyen_vien']
                ?: throw new InvalidArgumentException('Nguồn TRO_CHUYEN_HUAN_LUYEN cần quyền Chat PT.'),
            default => throw new InvalidArgumentException('Loại usage kích hoạt fixture không hợp lệ.'),
        };
    }

    /**
     * Ngăn fixture chạm database phát triển hoặc schema không đúng connection
     * cấu hình. Mọi test vẫn tự quản lý transaction/cleanup theo suite gọi nó.
     */
    private function damBaoDatabasePtChatCoLap(): void
    {
        $connection = (string) config('database.default');
        $databaseCauHinh = (string) config("database.connections.{$connection}.database");
        $databaseThucTe = (string) (DB::selectOne('SELECT DATABASE() AS ten_database')->ten_database ?? '');

        if ($connection !== 'mysql'
            || $databaseCauHinh !== $databaseThucTe
            || preg_match('/^smart_fitness_.*test/i', $databaseThucTe) !== 1
            || strtolower($databaseThucTe) === 'smart_fitness') {
            throw new LogicException(sprintf(
                'PT Chat fixtures chỉ được chạy trên schema smart_fitness_*test cô lập (hiện tại: %s).',
                $databaseThucTe !== '' ? $databaseThucTe : 'NONE',
            ));
        }
    }

    /** @param array<string, mixed> $fixture */
    private function damBaoDinhDanhPtChatTachBiet(array $fixture): void
    {
        $userIds = [
            (int) $fixture['member_a']['user']->getKey(),
            (int) $fixture['member_b']['user']->getKey(),
            (int) $fixture['pt_a']['user']->getKey(),
            (int) $fixture['pt_b']['user']->getKey(),
        ];
        $profileIds = [
            (int) $fixture['member_a_id'],
            (int) $fixture['member_b_id'],
            (int) $fixture['pt_a_id'],
            (int) $fixture['pt_b_id'],
        ];

        if (count(array_unique($userIds)) !== 4
            || $userIds[0] === $profileIds[0]
            || $userIds[1] === $profileIds[1]
            || $userIds[2] === $profileIds[2]
            || $userIds[3] === $profileIds[3]) {
            throw new LogicException('User ID và profile ID của fixture PT Chat phải tách biệt có chủ đích.');
        }
    }
}
