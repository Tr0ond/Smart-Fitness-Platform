<?php

namespace Tests\Feature;

use App\Contracts\Ai\WorkoutAiProvider;
use App\Services\Ai\AiRequestService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesWorkoutFixtures;
use Tests\Fakes\FakeWorkoutAiProvider;
use Tests\TestCase;

class AiProposalApplyConcurrencyTest extends TestCase
{
    use CreatesWorkoutFixtures;

    /** @var array<int, array<string, mixed>> */
    private array $cleanup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $database = (string) DB::selectOne('SELECT DATABASE() AS ten')->ten;
        $this->assertMatchesRegularExpression('/^smart_fitness_ai_apply_test_/i', $database);
        $this->assertNotSame('smart_fitness', strtolower($database));
        config(['ai.idempotency_ttl_hours' => 24, 'ai.proposal_ttl_hours' => 24]);
        app()->instance(WorkoutAiProvider::class, new FakeWorkoutAiProvider);
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->cleanup) as $bo) {
            $this->donBo($bo);
        }
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_two_actual_processes_apply_same_proposal_exactly_once(): void
    {
        $bo = $this->taoBo();
        $proposalId = $this->taoProposal($bo, 'TAO_KE_HOACH');
        $key = (string) Str::uuid();
        $results = $this->chayHaiTienTrinh($bo, [$proposalId, $proposalId], [$key, $key]);

        $this->assertSame(
            ['success', 'success'],
            collect($results)->pluck('status')->sort()->values()->all(),
            json_encode($results, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        );
        $this->assertSame([false, true], collect($results)->pluck('replayed')->sort()->values()->all());
        $this->assertCount(1, collect($results)->pluck('version_id')->unique());
        $this->assertSame(1, DB::table('phien_ban_ke_hoach_tap')->where('de_xuat_ke_hoach_tap_id', $proposalId)->count());
        $this->assertSame(1, DB::table('nhat_ky_he_thong')->where('hanh_dong', 'AP_DUNG_DE_XUAT_TRO_LY')->where('dinh_danh_doi_tuong', $proposalId)->count());
        $this->assertSame(1, (int) DB::table('ho_so_hoi_vien')->where('id', $bo['member_id'])->value('moc_thay_doi_ke_hoach'));
    }

    public function test_two_actual_processes_same_base_only_one_proposal_wins(): void
    {
        $bo = $this->taoBo();
        $plan = $this->taoPlanWorkout($bo, $bo['exercise_id']);
        $a = $this->taoProposal($bo, 'DIEU_CHINH');
        $b = $this->taoProposal($bo, 'DIEU_CHINH');
        $results = $this->chayHaiTienTrinh(
            $bo,
            [$a, $b],
            [(string) Str::uuid(), (string) Str::uuid()],
        );

        $this->assertSame(
            ['error', 'success'],
            collect($results)->pluck('status')->sort()->values()->all(),
            json_encode($results, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        );
        $this->assertSame(['AI_PLAN_STALE'], collect($results)->where('status', 'error')->pluck('code')->values()->all());
        $this->assertSame(2, DB::table('phien_ban_ke_hoach_tap')->where('ke_hoach_tap_id', $plan['plan']->getKey())->count());
        $this->assertSame(1, DB::table('de_xuat_ke_hoach_tap')->whereIn('id', [$a, $b])->where('trang_thai', 'DA_AP_DUNG')->count());
        $this->assertSame(1, DB::table('de_xuat_ke_hoach_tap')->whereIn('id', [$a, $b])->where('trang_thai', 'XUNG_DOT')->count());
        $this->assertSame(1, DB::table('nhat_ky_he_thong')->where('hanh_dong', 'AP_DUNG_DE_XUAT_TRO_LY')->whereIn('dinh_danh_doi_tuong', [$a, $b])->count());
    }

    public function test_actual_plan_change_lock_wins_and_apply_rechecks_stale_base(): void
    {
        $bo = $this->taoBo();
        $plan = $this->taoPlanWorkout($bo, $bo['exercise_id']);
        $proposalId = $this->taoProposal($bo, 'DIEU_CHINH');
        [$planResult, $applyResult] = $this->chayRaceDoiPlan($bo, $plan['plan']->getKey(), $proposalId);

        $this->assertSame('success', $planResult['status']);
        $this->assertSame('error', $applyResult['status']);
        $this->assertSame('AI_PLAN_STALE', $applyResult['code']);
        $this->assertSame('XUNG_DOT', DB::table('de_xuat_ke_hoach_tap')->where('id', $proposalId)->value('trang_thai'));
        $this->assertSame(2, DB::table('phien_ban_ke_hoach_tap')->where('ke_hoach_tap_id', $plan['plan']->getKey())->count());
        $this->assertSame(0, DB::table('phien_ban_ke_hoach_tap')->where('de_xuat_ke_hoach_tap_id', $proposalId)->count());
        $this->assertSame(0, DB::table('nhat_ky_he_thong')->where('hanh_dong', 'AP_DUNG_DE_XUAT_TRO_LY')->where('dinh_danh_doi_tuong', $proposalId)->count());
    }

    /** @return array<string, mixed> */
    private function taoBo(): array
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoiVienAi($fixture);
        $exerciseId = $this->taoBaiTapAi($fixture);
        $term = $this->taoMembershipAi($fixture, $memberId, 20);
        $bo = $fixture + compact('memberId', 'exerciseId', 'term') + [
            'member_id' => $memberId,
            'exercise_id' => $exerciseId,
        ];
        $this->cleanup[] = $bo;

        return $bo;
    }

    private function taoProposal(array $bo, string $loai): int
    {
        $result = app(AiRequestService::class)->tao($bo['user'], [
            'request_type' => $loai,
            'prompt' => 'Đề xuất cho concurrency Apply.',
        ], (string) Str::uuid());

        return (int) $result['proposal']['id'];
    }

    /** @return array<int, array<string, mixed>> */
    private function chayHaiTienTrinh(array $bo, array $proposalIds, array $keys): array
    {
        $database = (string) DB::selectOne('SELECT DATABASE() AS ten')->ten;
        $barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ai_apply_'.bin2hex(random_bytes(8));
        $script = base_path('tests/Support/run_ai_proposal_apply.php');
        $spec = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $processes = [];
        foreach ([0, 1] as $index) {
            $pipes = [];
            $process = proc_open([
                PHP_BINARY,
                $script,
                $database,
                (string) $bo['user']->getKey(),
                (string) $proposalIds[$index],
                $keys[$index],
                $barrier,
            ], $spec, $pipes, base_path());
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

    /** @return array{0: array<string, mixed>, 1: array<string, mixed>} */
    private function chayRaceDoiPlan(array $bo, int $planId, int $proposalId): array
    {
        $database = (string) DB::selectOne('SELECT DATABASE() AS ten')->ten;
        $suffix = bin2hex(random_bytes(8));
        $tmp = sys_get_temp_dir().DIRECTORY_SEPARATOR;
        $start = $tmp.'ai_plan_start_'.$suffix;
        $locked = $tmp.'ai_plan_locked_'.$suffix;
        $release = $tmp.'ai_plan_release_'.$suffix;
        $applyStart = $tmp.'ai_apply_start_'.$suffix;
        $spec = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $planPipes = [];
        $planProcess = proc_open([
            PHP_BINARY,
            base_path('tests/Support/run_ai_plan_change.php'),
            $database,
            (string) $bo['user']->getKey(),
            (string) $bo['member_id'],
            (string) $planId,
            (string) $bo['exercise_id'],
            $start,
            $locked,
            $release,
        ], $spec, $planPipes, base_path());
        $this->assertIsResource($planProcess);
        fclose($planPipes[0]);
        touch($start);
        $deadline = microtime(true) + 20;
        while (! is_file($locked) && microtime(true) < $deadline) {
            usleep(10000);
        }
        $this->assertFileExists($locked, 'Plan-change process did not acquire Member lock.');

        $applyPipes = [];
        $applyProcess = proc_open([
            PHP_BINARY,
            base_path('tests/Support/run_ai_proposal_apply.php'),
            $database,
            (string) $bo['user']->getKey(),
            (string) $proposalId,
            (string) Str::uuid(),
            $applyStart,
        ], $spec, $applyPipes, base_path());
        $this->assertIsResource($applyProcess);
        fclose($applyPipes[0]);
        touch($applyStart);
        usleep(200000);
        touch($release);

        try {
            $planResult = $this->docTienTrinh($planProcess, $planPipes);
            $applyResult = $this->docTienTrinh($applyProcess, $applyPipes);

            return [$planResult, $applyResult];
        } finally {
            foreach ([$start, $locked, $release, $applyStart] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    /** @param array<int, resource> $pipes @return array<string, mixed> */
    private function docTienTrinh($process, array $pipes): array
    {
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        $this->assertSame(0, $exit, trim((string) $stderr));

        return json_decode(trim((string) $stdout), true, 512, JSON_THROW_ON_ERROR);
    }

    private function donBo(array $bo): void
    {
        DB::transaction(function () use ($bo): void {
            $memberId = $bo['member_id'];
            $userId = $bo['user']->getKey();
            $requestIds = DB::table('yeu_cau_tro_ly')->where('hoi_vien_id', $memberId)->pluck('id');
            $proposalIds = DB::table('de_xuat_ke_hoach_tap')->where('hoi_vien_id', $memberId)->pluck('id');
            $planIds = DB::table('ke_hoach_tap')->where('hoi_vien_id', $memberId)->pluck('id');
            $versionIds = DB::table('phien_ban_ke_hoach_tap')->whereIn('ke_hoach_tap_id', $planIds)->pluck('id');
            $dayIds = DB::table('ngay_trong_ke_hoach')->whereIn('phien_ban_ke_hoach_tap_id', $versionIds)->pluck('id');
            $sessionIds = DB::table('phien_tap')->where('hoi_vien_id', $memberId)->pluck('id');

            DB::table('hiep_tap')->whereIn('phien_tap_id', $sessionIds)->delete();
            DB::table('bai_tap_trong_phien')->whereIn('phien_tap_id', $sessionIds)->delete();
            DB::table('phien_tap')->whereIn('id', $sessionIds)->delete();
            DB::table('buoi_tap_du_kien')->where('hoi_vien_id', $memberId)->delete();
            DB::table('nhat_ky_he_thong')->whereIn('dinh_danh_doi_tuong', $proposalIds)->where('loai_doi_tuong', 'DE_XUAT_KE_HOACH_TAP')->delete();
            DB::table('ke_hoach_tap')->whereIn('id', $planIds)->update(['phien_ban_hien_tai_id' => null]);
            DB::table('bai_tap_trong_ke_hoach')->whereIn('ngay_trong_ke_hoach_id', $dayIds)->delete();
            DB::table('ngay_trong_ke_hoach')->whereIn('id', $dayIds)->delete();
            DB::table('phien_ban_ke_hoach_tap')->whereIn('de_xuat_ke_hoach_tap_id', $proposalIds)->delete();
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
            DB::table('yeu_cau_chong_lap')->where('nguoi_dung_id', $userId)->delete();

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
            DB::table('ngay_ranh_hoi_vien')->where('hoi_vien_id', $memberId)->delete();
            DB::table('ho_so_hoi_vien')->where('id', $memberId)->delete();
            DB::table('bai_tap')->where('id', $bo['exercise_id'])->delete();
            DB::table('the_truy_cap')->where('nguoi_dung_id', $userId)->delete();
            DB::table('phan_quyen_nguoi_dung')->where('nguoi_dung_id', $userId)->delete();
            DB::table('nguoi_dung')->where('id', $userId)->delete();
            DB::table('chi_nhanh')->where('id', $bo['branch_id'])->delete();
        });
    }
}
