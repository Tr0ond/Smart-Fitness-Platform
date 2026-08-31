<?php

namespace App\Services\Pt\Chat;

use App\Events\PtChatMessageSent;
use App\Models\SuKienPhatTinNhan;
use App\Models\TinNhan;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
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
        $tenKhoa = 'smart_fitness:pt_chat_outbox:'.$tinNhanId;
        $choKhoa = max(0, min(30, (int) config('pt_chat.outbox_advisory_lock_timeout_seconds', 5)));
        $daKhoa = (int) (DB::selectOne('SELECT GET_LOCK(?, ?) AS da_khoa', [$tenKhoa, $choKhoa])->da_khoa ?? 0);
        if ($daKhoa !== 1) {
            return (string) SuKienPhatTinNhan::query()
                ->where('tin_nhan_id', $tinNhanId)
                ->firstOrFail()
                ->trang_thai;
        }

        try {
            $outbox = SuKienPhatTinNhan::query()->where('tin_nhan_id', $tinNhanId)->firstOrFail();
            if ($outbox->trang_thai === 'DA_PHAT') {
                return 'DA_PHAT';
            }

            $tinNhan = TinNhan::query()
                ->with(['hoiThoai.hoiVien'])
                ->findOrFail($tinNhanId);
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

                $hienTai = CarbonImmutable::now('UTC');
                $outbox->forceFill([
                    'trang_thai' => 'DA_PHAT',
                    'so_lan_thu' => $soLanThu,
                    'thu_lai_luc' => null,
                    'phat_luc' => $hienTai,
                    'loi_gan_nhat' => null,
                    'ngay_cap_nhat' => $hienTai,
                ])->save();

                return 'DA_PHAT';
            } catch (Throwable) {
                $hienTai = CarbonImmutable::now('UTC');
                $outbox->forceFill([
                    'trang_thai' => 'CHO_THU_LAI',
                    'so_lan_thu' => $soLanThu,
                    'thu_lai_luc' => $hienTai->addSeconds($this->doTreThuLai($soLanThu)),
                    'phat_luc' => null,
                    'loi_gan_nhat' => 'REALTIME_DELIVERY_FAILED',
                    'ngay_cap_nhat' => $hienTai,
                ])->save();

                return 'CHO_THU_LAI';
            }
        } finally {
            DB::selectOne('SELECT RELEASE_LOCK(?) AS da_mo_khoa', [$tenKhoa]);
        }
    }

    /**
     * Xử lý một batch bounded; CHO_PHAT khôi phục crash-gap, CHO_THU_LAI chỉ
     * được chọn khi đã đến mốc retry. Hàm không đọc lại entitlement/assignment
     * và không chạm message, usage hoặc Membership.
     *
     * @return array{selected: int, delivered: int, retrying: int}
     */
    public function phatDangCho(?int $gioiHan = null): array
    {
        $gioiHan ??= (int) config('pt_chat.outbox_retry_batch_size', 100);
        $gioiHan = max(1, min(500, $gioiHan));
        $hienTai = CarbonImmutable::now('UTC');
        $tinNhanIds = SuKienPhatTinNhan::query()
            ->where(function ($query) use ($hienTai): void {
                $query->where('trang_thai', 'CHO_PHAT')
                    ->orWhere(function ($retry) use ($hienTai): void {
                        $retry->where('trang_thai', 'CHO_THU_LAI')
                            ->whereNotNull('thu_lai_luc')
                            ->where('thu_lai_luc', '<=', $hienTai);
                    });
            })
            ->orderByRaw("CASE WHEN trang_thai = 'CHO_PHAT' THEN 0 ELSE 1 END")
            ->orderBy('thu_lai_luc')
            ->orderBy('id')
            ->limit($gioiHan)
            ->pluck('tin_nhan_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $delivered = 0;
        $retrying = 0;
        foreach ($tinNhanIds as $tinNhanId) {
            $this->phat($tinNhanId) === 'DA_PHAT' ? $delivered++ : $retrying++;
        }

        return ['selected' => count($tinNhanIds), 'delivered' => $delivered, 'retrying' => $retrying];
    }

    private function doTreThuLai(int $soLanThu): int
    {
        $coSo = max(1, min(3600, (int) config('pt_chat.outbox_retry_base_seconds', 15)));
        $toiDa = max($coSo, min(86400, (int) config('pt_chat.outbox_retry_max_seconds', 3600)));
        $heSo = 2 ** min(20, max(0, $soLanThu - 1));

        return (int) min($toiDa, $coSo * $heSo);
    }
}
