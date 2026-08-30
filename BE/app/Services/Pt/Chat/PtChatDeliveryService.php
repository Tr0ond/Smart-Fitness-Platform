<?php

namespace App\Services\Pt\Chat;

use App\Events\PtChatMessageSent;
use App\Models\SuKienPhatTinNhan;
use App\Models\TinNhan;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Throwable;

class PtChatDeliveryService
{
    public function __construct(private readonly Dispatcher $suKien) {}

    /**
     * Phát sự kiện sau business COMMIT. Lỗi transport chỉ chuyển outbox sang
     * CHO_THU_LAI; message, usage và Membership đã commit không bị đảo ngược.
     */
    public function phat(int $tinNhanId): string
    {
        $tinNhan = TinNhan::query()
            ->with(['hoiThoai.hoiVien'])
            ->findOrFail($tinNhanId);
        $outbox = SuKienPhatTinNhan::query()->where('tin_nhan_id', $tinNhanId)->firstOrFail();
        if ($outbox->trang_thai === 'DA_PHAT') {
            return 'DA_PHAT';
        }

        $soLanThu = (int) $outbox->so_lan_thu + 1;
        $loaiNguoiGui = (int) $tinNhan->nguoi_gui_id === (int) $tinNhan->hoiThoai?->hoiVien?->nguoi_dung_id
            ? PtChatAuthorizationService::MEMBER
            : PtChatAuthorizationService::PT;

        try {
            $this->suKien->dispatch(new PtChatMessageSent(
                (int) $tinNhan->hoi_thoai_id,
                (int) $tinNhan->getKey(),
                (int) $tinNhan->so_thu_tu,
                (int) $tinNhan->nguoi_gui_id,
                $loaiNguoiGui,
                (string) $tinNhan->noi_dung,
                $tinNhan->gui_luc->toISOString(),
            ));

            SuKienPhatTinNhan::query()->whereKey($outbox->getKey())->update([
                'trang_thai' => 'DA_PHAT',
                'so_lan_thu' => $soLanThu,
                'thu_lai_luc' => null,
                'phat_luc' => CarbonImmutable::now('UTC'),
                'loi_gan_nhat' => null,
                'ngay_cap_nhat' => CarbonImmutable::now('UTC'),
            ]);

            return 'DA_PHAT';
        } catch (Throwable $exception) {
            $hienTai = CarbonImmutable::now('UTC');
            SuKienPhatTinNhan::query()->whereKey($outbox->getKey())->update([
                'trang_thai' => 'CHO_THU_LAI',
                'so_lan_thu' => $soLanThu,
                'thu_lai_luc' => $hienTai,
                'phat_luc' => null,
                'loi_gan_nhat' => mb_substr($exception->getMessage(), 0, 500),
                'ngay_cap_nhat' => $hienTai,
            ]);

            return 'CHO_THU_LAI';
        }
    }
}
