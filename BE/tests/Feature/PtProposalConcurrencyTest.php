<?php

namespace Tests\Feature;

use App\Contracts\Ai\WorkoutAiProvider;
use App\Services\Ai\AiRequestService;
use App\Services\Pt\PtProposalService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesPtFixtures;
use Tests\Concerns\CreatesWorkoutFixtures;
use Tests\Fakes\FakeWorkoutAiProvider;
use Tests\Support\TestDatabaseGuard;
use Tests\TestCase;

class PtProposalConcurrencyTest extends TestCase
{
    use CreatesPtFixtures;
    use CreatesWorkoutFixtures;

    /** @var array<int, array<string, mixed>> */
    private array $cleanup = [];

    protected function setUp(): void
    {
        parent::setUp();
        TestDatabaseGuard::damBaoDatabaseHienTai();
        config(['ai.proposal_ttl_hours' => 24, 'ai.idempotency_ttl_hours' => 24]);
        app()->instance(WorkoutAiProvider::class, new FakeWorkoutAiProvider);
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->cleanup) as $bo) {
            $this->donBo($bo);
        }
        parent::tearDown();
    }

    public function test_two_actual_processes_confirm_same_pt_proposal_exactly_once(): void
    {
        $bo = $this->taoBo();
        $proposalId = $this->taoPtProposal($bo, 'TAO_MOI');
        $key = (string) Str::uuid();
        $results = $this->chayHaiTienTrinh([
            $this->lenhPt($bo, $proposalId, $key),
            $this->lenhPt($bo, $proposalId, $key),
        ]);

        $this->assertSame(['success', 'success'], collect($results)->pluck('status')->sort()->values()->all());
        $this->assertSame([false, true], collect($results)->pluck('replayed')->sort()->values()->all());
        $this->assertCount(1, collect($results)->pluck('version_id')->unique());
        $this->assertSame(1, DB::table('phien_ban_ke_hoach_tap')->where('de_xuat_ke_hoach_tap_id', $proposalId)->count());
        $this->assertSame(1, DB::table('nhat_ky_he_thong')
            ->where('hanh_dong', 'AP_DUNG_DE_XUAT_HUAN_LUYEN_VIEN')
            ->where('dinh_danh_doi_tuong', $proposalId)->count());
    }

    public function test_two_pt_proposals_from_same_base_have_one_actual_process_winner(): void
    {
        $bo = $this->taoBo(true);
        $a = $this->taoPtProposal($bo, 'DIEU_CHINH');
        $b = $this->taoPtProposal($bo, 'DIEU_CHINH');
        $results = $this->chayHaiTienTrinh([
            $this->lenhPt($bo, $a, (string) Str::uuid()),
            $this->lenhPt($bo, $b, (string) Str::uuid()),
        ]);

        $this->assertSame(['error', 'success'], collect($results)->pluck('status')->sort()->values()->all());
        $this->assertContains(
            collect($results)->firstWhere('status', 'error')['code'],
            ['PT_PLAN_STALE', 'PT_PROPOSAL_CONTEXT_STALE'],
        );
        $this->assertSame(2, DB::table('phien_ban_ke_hoach_tap')->where('ke_hoach_tap_id', $bo['plan']['plan']->getKey())->count());
        $this->assertSame(1, DB::table('de_xuat_ke_hoach_tap')->whereIn('id', [$a, $b])->where('trang_thai', 'DA_AP_DUNG')->count());
        $this->assertSame(1, DB::table('de_xuat_ke_hoach_tap')->whereIn('id', [$a, $b])->where('trang_thai', 'XUNG_DOT')->count());
    }

    public function test_ai_and_pt_same_base_actual_process_race_has_one_winner(): void
    {
        $bo = $this->taoBo(true);
        $aiProposal = (int) app(AiRequestService::class)->tao($bo['member']['user'], [
            'request_type' => 'DIEU_CHINH',
            'prompt' => 'Đề xuất AI tranh cùng base với PT.',
        ], (string) Str::uuid())['proposal']['id'];
        $ptProposal = $this->taoPtProposal($bo, 'DIEU_CHINH');
        $results = $this->chayHaiTienTrinh([
            $this->lenhAi($bo, $aiProposal, (string) Str::uuid()),
            $this->lenhPt($bo, $ptProposal, (string) Str::uuid()),
        ]);

        $this->assertSame(['error', 'success'], collect($results)->pluck('status')->sort()->values()->all(), json_encode($results));
        $this->assertContains(collect($results)->firstWhere('status', 'error')['code'], [
            'AI_PLAN_STALE',
            'AI_PROPOSAL_CONTEXT_STALE',
            'PT_PLAN_STALE',
            'PT_PROPOSAL_CONTEXT_STALE',
        ]);
        $this->assertSame(2, DB::table('phien_ban_ke_hoach_tap')->where('ke_hoach_tap_id', $bo['plan']['plan']->getKey())->count());
        $this->assertSame(1, DB::table('de_xuat_ke_hoach_tap')->whereIn('id', [$aiProposal, $ptProposal])->where('trang_thai', 'DA_AP_DUNG')->count());
        $this->assertSame(1, DB::table('de_xuat_ke_hoach_tap')->whereIn('id', [$aiProposal, $ptProposal])->where('trang_thai', 'XUNG_DOT')->count());
    }

    /** @return array<string, mixed> */
    private function taoBo(bool $coPlan = false): array
    {
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $member = $this->taoNguoiDungAuth(['MEMBER']);
        $pt = $this->taoNguoiDungAuth(['PT']);
        $memberId = $this->taoHoiVienAi($member);
        $ptId = $this->taoHoSoHuanLuyenVien($pt);
        $assignmentFixture = ['admin' => $admin];
        $assignmentId = $this->taoPhanCongPt($assignmentFixture, $memberId, $ptId);
        $exerciseId = $this->taoBaiTapAi($member);
        $term = $this->taoMembershipAi($member, $memberId, 20);
        $member += ['member_id' => $memberId];
        $plan = $coPlan ? $this->taoPlanWorkout($member, $exerciseId) : null;
        $bo = compact(
            'admin',
            'member',
            'pt',
            'memberId',
            'ptId',
            'assignmentId',
            'exerciseId',
            'term',
            'plan',
        ) + [
            'member_id' => $memberId,
            'pt_id' => $ptId,
            'assignment_id' => $assignmentId,
            'exercise_id' => $exerciseId,
        ];
        $this->cleanup[] = $bo;

        return $bo;
    }

    private function taoPtProposal(array $bo, string $loai): int
    {
        $ngay = CarbonImmutable::now('Asia/Ho_Chi_Minh')->addDay();
        $thu = $ngay->dayOfWeekIso === 7 ? 8 : $ngay->dayOfWeekIso + 1;
        $result = app(PtProposalService::class)->tao($bo['pt']['user'], $bo['member_id'], [
            'change_type' => $loai,
            'title' => 'Proposal PT concurrency',
            'explanation' => 'Payload dùng để kiểm tra khóa thật.',
            'effective_from' => $ngay->toDateString(),
            'plan' => [
                'name' => 'Plan PT concurrency',
                'goal' => 'TANG_SUC_MANH',
                'days' => [[
                    'order' => 1,
                    'weekday' => $thu,
                    'name' => 'Ngày concurrency',
                    'estimated_minutes' => 60,
                    'exercises' => [[
                        'exercise_id' => $bo['exercise_id'],
                        'order' => 1,
                        'target_sets' => 3,
                        'min_reps' => 8,
                        'max_reps' => 12,
                        'target_weight_kg' => null,
                        'rest_seconds' => 90,
                        'notes' => null,
                    ]],
                ]],
            ],
        ], (string) Str::uuid());

        return (int) $result['id'];
    }

    /** @return array<int, string> */
    private function lenhPt(array $bo, int $proposalId, string $key): array
    {
        return [
            PHP_BINARY,
            base_path('tests/Support/run_pt_proposal_confirm.php'),
            $this->database(),
            (string) $bo['member']['user']->getKey(),
            (string) $proposalId,
            $key,
        ];
    }

    /** @return array<int, string> */
    private function lenhAi(array $bo, int $proposalId, string $key): array
    {
        return [
            PHP_BINARY,
            base_path('tests/Support/run_ai_proposal_apply.php'),
            $this->database(),
            (string) $bo['member']['user']->getKey(),
            (string) $proposalId,
            $key,
        ];
    }

    /** @param array<int, array<int, string>> $commands @return array<int, array<string, mixed>> */
    private function chayHaiTienTrinh(array $commands): array
    {
        $barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pt_proposal_'.bin2hex(random_bytes(8));
        $spec = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $processes = [];
        foreach ($commands as $command) {
            $pipes = [];
            $process = proc_open([...$command, $barrier], $spec, $pipes, base_path(), TestDatabaseGuard::moiTruongTienTrinhCon());
            $this->assertIsResource($process);
            fclose($pipes[0]);
            $processes[] = [$process, $pipes];
        }
        touch($barrier);

        try {
            $results = [];
            foreach ($processes as [$process, $pipes]) {
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $exit = proc_close($process);
                $this->assertSame(0, $exit, trim((string) $stderr));
                $results[] = json_decode(trim((string) $stdout), true, 512, JSON_THROW_ON_ERROR);
            }

            return $results;
        } finally {
            if (is_file($barrier)) {
                unlink($barrier);
            }
        }
    }

    private function database(): string
    {
        return (string) DB::selectOne('SELECT DATABASE() AS ten')->ten;
    }

    private function donBo(array $bo): void
    {
        DB::transaction(function () use ($bo): void {
            $memberId = $bo['member_id'];
            $userIds = collect([$bo['admin'], $bo['member'], $bo['pt']])
                ->map(fn (array $fixture): int => (int) $fixture['user']->getKey());
            $branchIds = collect([$bo['admin'], $bo['member'], $bo['pt']])->pluck('branch_id');
            $proposalIds = DB::table('de_xuat_ke_hoach_tap')->where('hoi_vien_id', $memberId)->pluck('id');
            $planIds = DB::table('ke_hoach_tap')->where('hoi_vien_id', $memberId)->pluck('id');
            $versionIds = DB::table('phien_ban_ke_hoach_tap')->whereIn('ke_hoach_tap_id', $planIds)->pluck('id');
            $dayIds = DB::table('ngay_trong_ke_hoach')->whereIn('phien_ban_ke_hoach_tap_id', $versionIds)->pluck('id');
            $requestIds = DB::table('yeu_cau_tro_ly')->where('hoi_vien_id', $memberId)->pluck('id');

            DB::table('yeu_cau_chong_lap')->whereIn('nguoi_dung_id', $userIds)->delete();
            DB::table('nhat_ky_he_thong')->whereIn('nguoi_thuc_hien_id', $userIds)->delete();
            DB::table('ghi_chu_huan_luyen')->where('hoi_vien_id', $memberId)->delete();
            DB::table('buoi_tap_du_kien')->where('hoi_vien_id', $memberId)->update(['thay_the_buoi_tap_id' => null]);
            DB::table('buoi_tap_du_kien')->where('hoi_vien_id', $memberId)->delete();
            DB::table('ke_hoach_tap')->whereIn('id', $planIds)->update(['phien_ban_hien_tai_id' => null]);
            DB::table('bai_tap_trong_ke_hoach')->whereIn('ngay_trong_ke_hoach_id', $dayIds)->delete();
            DB::table('ngay_trong_ke_hoach')->whereIn('id', $dayIds)->delete();
            DB::table('phien_ban_ke_hoach_tap')->whereIn('ke_hoach_tap_id', $planIds)
                ->whereNotNull('de_xuat_ke_hoach_tap_id')->delete();
            DB::table('de_xuat_ke_hoach_tap')->whereIn('id', $proposalIds)->delete();
            DB::table('phien_ban_ke_hoach_tap')->whereIn('ke_hoach_tap_id', $planIds)
                ->orderByDesc('id')->pluck('id')->each(
                    fn ($id) => DB::table('phien_ban_ke_hoach_tap')->where('id', $id)->delete(),
                );
            DB::table('ke_hoach_tap')->whereIn('id', $planIds)->delete();

            DB::table('lan_goi_mo_hinh')->whereIn('yeu_cau_tro_ly_id', $requestIds)->delete();
            DB::table('bai_tap_ung_vien')->whereIn('yeu_cau_tro_ly_id', $requestIds)->delete();
            DB::table('giao_an_ung_vien')->whereIn('yeu_cau_tro_ly_id', $requestIds)->delete();
            DB::table('tin_nhan_tro_ly')->whereIn('yeu_cau_tro_ly_id', $requestIds)->delete();
            DB::table('yeu_cau_tro_ly')->whereIn('id', $requestIds)->delete();
            $conversationIds = DB::table('hoi_thoai_tro_ly')->where('hoi_vien_id', $memberId)->pluck('id');
            DB::table('tin_nhan_tro_ly')->whereIn('hoi_thoai_tro_ly_id', $conversationIds)->delete();
            DB::table('hoi_thoai_tro_ly')->whereIn('id', $conversationIds)->delete();

            $term = $bo['term'];
            $order = DB::table('don_mua_goi')->find($term->don_mua_goi_id);
            DB::table('dang_ky_goi_tap')->where('id', $term->dang_ky_goi_tap_id)->update([
                'trang_thai' => 'HUY',
                'lan_su_dung_dau_tien_id' => null,
                'ngay_bat_dau' => null,
            ]);
            DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $memberId)->delete();
            DB::table('ky_han_hoi_vien')->where('id', $term->getKey())->delete();
            DB::table('dang_ky_goi_tap')->where('id', $term->dang_ky_goi_tap_id)->delete();
            DB::table('lan_thanh_toan')->where('id', $term->lan_thanh_toan_id)->delete();
            DB::table('don_mua_goi')->where('id', $term->don_mua_goi_id)->delete();
            DB::table('quyen_loi_goi_tap')->where('goi_tap_id', $order->goi_tap_id)->delete();
            DB::table('goi_tap')->where('id', $order->goi_tap_id)->delete();

            DB::table('phan_cong_huan_luyen_vien')->where('hoi_vien_id', $memberId)->delete();
            DB::table('ngay_ranh_hoi_vien')->where('hoi_vien_id', $memberId)->delete();
            DB::table('dung_cu_hoi_vien')->where('hoi_vien_id', $memberId)->delete();
            DB::table('ho_so_hoi_vien')->where('id', $memberId)->delete();
            DB::table('ho_so_huan_luyen_vien')->where('id', $bo['pt_id'])->delete();
            DB::table('bai_tap_dung_cu')->where('bai_tap_id', $bo['exercise_id'])->delete();
            DB::table('bai_tap_nhom_co')->where('bai_tap_id', $bo['exercise_id'])->delete();
            DB::table('bai_tap')->where('id', $bo['exercise_id'])->delete();
            DB::table('the_truy_cap')->whereIn('nguoi_dung_id', $userIds)->delete();
            DB::table('phan_quyen_nguoi_dung')->whereIn('nguoi_dung_id', $userIds)->delete();
            DB::table('nguoi_dung')->whereIn('id', $userIds)->delete();
            DB::table('chi_nhanh')->whereIn('id', $branchIds)->delete();
        });
    }
}
