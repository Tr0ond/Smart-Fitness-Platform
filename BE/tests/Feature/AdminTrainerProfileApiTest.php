<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\TestCase;

class AdminTrainerProfileApiTest extends TestCase
{
    use CreatesAuthenticationFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batDauGiaoDichAuthCoLap();
    }

    protected function tearDown(): void
    {
        $this->ketThucGiaoDichAuthCoLap();
        parent::tearDown();
    }

    public function test_same_branch_admin_receives_exact_full_profile_dto_without_secrets(): void
    {
        $hienTai = CarbonImmutable::parse('2026-09-06 08:15:30.123456', 'UTC');
        CarbonImmutable::setTestNow($hienTai);
        $admin = $this->taoNguoiDungAuth(['ADMIN'], thuDienTu: 'admin.trainer-profile@example.com');
        $trainer = $this->taoNguoiDungAuth(['PT'], thuDienTu: 'trainer.private@example.com');
        $trainer['user']->forceFill([
            'chi_nhanh_id' => $admin['branch_id'],
            'so_dien_thoai' => '0909123456',
        ])->save();
        $profileId = $this->taoHoSoHuanLuyenVien(
            $trainer['user']->getKey(),
            $hienTai,
            'Huấn luyện sức mạnh và kỹ thuật.',
            'Sức mạnh, mobility',
        );
        $token = (string) $this->dangNhapApi($admin['user']->thu_dien_tu, diaChiIp: '192.0.2.81')
            ->json('data.access_token');

        $response = $this->getJson(
            '/api/admin/accounts/'.$trainer['user']->getKey().'/trainer-profile',
            $this->bearer($token),
        );
        $response->assertOk()->assertExactJson([
            'data' => [
                'account_id' => $trainer['user']->getKey(),
                'trainer_profile_id' => $profileId,
                'trainer_code' => 'PT-'.str_pad((string) $profileId, 6, '0', STR_PAD_LEFT),
                'status' => 'HOAT_DONG',
                'introduction' => 'Huấn luyện sức mạnh và kỹ thuật.',
                'specialties' => 'Sức mạnh, mobility',
                'updated_at' => $hienTai->toISOString(),
            ],
        ]);

        $body = (string) $response->getContent();
        foreach ([
            $trainer['user']->thu_dien_tu,
            '0909123456',
            (string) $trainer['user']->mat_khau_bam,
            $token,
            'mat_khau_bam',
            'token',
            'role',
            'audit',
            'assignment',
            'phan_cong',
            'arbitrary_internal_value',
        ] as $biMat) {
            $this->assertStringNotContainsString($biMat, $body);
        }
        $response->assertJsonMissingPath('data.email')
            ->assertJsonMissingPath('data.phone')
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.roles')
            ->assertJsonMissingPath('data.audits')
            ->assertJsonMissingPath('data.assignment');
    }

    public function test_trainer_profile_get_is_read_only_and_does_not_change_timestamps_or_audits(): void
    {
        $hienTai = CarbonImmutable::parse('2026-09-06 09:20:10.000001', 'UTC');
        CarbonImmutable::setTestNow($hienTai);
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $trainer = $this->taoNguoiDungAuth(['PT']);
        $trainer['user']->forceFill(['chi_nhanh_id' => $admin['branch_id']])->save();
        $profileId = $this->taoHoSoHuanLuyenVien($trainer['user']->getKey(), $hienTai);
        $token = (string) $this->dangNhapApi($admin['user']->thu_dien_tu, diaChiIp: '192.0.2.82')
            ->json('data.access_token');
        $tokenHash = hash('sha256', $token);
        $tokenBefore = (array) DB::table('the_truy_cap')->where('ma_bam_the', $tokenHash)->sole();
        $accountUpdatedAt = DB::table('nguoi_dung')->where('id', $trainer['user']->getKey())->value('ngay_cap_nhat');
        $profileUpdatedAt = DB::table('ho_so_huan_luyen_vien')->where('id', $profileId)->value('ngay_cap_nhat');
        $auditCount = DB::table('nhat_ky_he_thong')->count();

        CarbonImmutable::setTestNow($hienTai->addMinutes(7));
        $connection = DB::connection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();
        try {
            $response = $this->getJson(
                '/api/admin/accounts/'.$trainer['user']->getKey().'/trainer-profile',
                $this->bearer($token),
            );
        } finally {
            $queries = $connection->getQueryLog();
            $connection->disableQueryLog();
        }
        $response->assertOk()->assertExactJson([
            'data' => [
                'account_id' => $trainer['user']->getKey(),
                'trainer_profile_id' => $profileId,
                'trainer_code' => 'PT-'.str_pad((string) $profileId, 6, '0', STR_PAD_LEFT),
                'status' => 'HOAT_DONG',
                'introduction' => null,
                'specialties' => null,
                'updated_at' => $hienTai->toISOString(),
            ],
        ]);

        $this->assertRequestQueryLogIsReadOnly($queries);
        $this->assertSame(
            $tokenBefore,
            (array) DB::table('the_truy_cap')->where('ma_bam_the', $tokenHash)->sole(),
        );
        $this->assertSame(
            $accountUpdatedAt,
            DB::table('nguoi_dung')->where('id', $trainer['user']->getKey())->value('ngay_cap_nhat'),
        );
        $this->assertSame(
            $profileUpdatedAt,
            DB::table('ho_so_huan_luyen_vien')->where('id', $profileId)->value('ngay_cap_nhat'),
        );
        $this->assertSame($auditCount, DB::table('nhat_ky_he_thong')->count());
        $this->assertSame(0, DB::table('yeu_cau_chong_lap')->count());
    }

    public function test_authenticated_forbidden_trainer_profile_get_does_not_touch_access_token_telemetry(): void
    {
        $hienTai = CarbonImmutable::parse('2026-09-06 10:40:20.000001', 'UTC');
        CarbonImmutable::setTestNow($hienTai);
        $member = $this->taoNguoiDungAuth(['MEMBER']);
        $token = (string) $this->dangNhapApi($member['user']->thu_dien_tu, diaChiIp: '192.0.2.85')
            ->json('data.access_token');
        $tokenHash = hash('sha256', $token);
        $tokenBefore = (array) DB::table('the_truy_cap')->where('ma_bam_the', $tokenHash)->sole();

        CarbonImmutable::setTestNow($hienTai->addMinutes(11));
        $connection = DB::connection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();
        try {
            $response = $this->getJson(
                '/api/admin/accounts/999999/trainer-profile',
                $this->bearer($token),
            );
        } finally {
            $queries = $connection->getQueryLog();
            $connection->disableQueryLog();
        }

        $response->assertForbidden();
        $this->assertRequestQueryLogIsReadOnly($queries);
        $this->assertSame(
            $tokenBefore,
            (array) DB::table('the_truy_cap')->where('ma_bam_the', $tokenHash)->sole(),
        );
    }

    public function test_trainer_profile_get_requires_authenticated_active_admin(): void
    {
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $target = $this->taoNguoiDungAuth(['PT']);
        $target['user']->forceFill(['chi_nhanh_id' => $admin['branch_id']])->save();
        $uri = '/api/admin/accounts/'.$target['user']->getKey().'/trainer-profile';

        $this->getJson($uri)->assertUnauthorized();

        $member = $this->taoNguoiDungAuth(['MEMBER']);
        $memberToken = (string) $this->dangNhapApi($member['user']->thu_dien_tu, diaChiIp: '192.0.2.83')
            ->json('data.access_token');
        $this->getJson($uri, $this->bearer($memberToken))->assertForbidden();
    }

    public function test_missing_foreign_and_no_profile_targets_share_generic_not_found_response(): void
    {
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $noProfile = $this->taoNguoiDungAuth(['MEMBER']);
        $noProfile['user']->forceFill(['chi_nhanh_id' => $admin['branch_id']])->save();
        $foreign = $this->taoNguoiDungAuth(['PT']);
        $foreignProfileId = $this->taoHoSoHuanLuyenVien($foreign['user']->getKey(), CarbonImmutable::now('UTC'));
        $token = (string) $this->dangNhapApi($admin['user']->thu_dien_tu, diaChiIp: '192.0.2.84')
            ->json('data.access_token');
        $missingId = (int) DB::table('nguoi_dung')->max('id') + 1000;
        $uris = [
            '/api/admin/accounts/'.$missingId.'/trainer-profile',
            '/api/admin/accounts/'.$foreign['user']->getKey().'/trainer-profile',
            '/api/admin/accounts/'.$noProfile['user']->getKey().'/trainer-profile',
        ];
        $responses = [];
        foreach ($uris as $uri) {
            $responses[] = $this->getJson($uri, $this->bearer($token));
        }

        foreach ($responses as $response) {
            $response->assertNotFound()
                ->assertExactJson([
                    'message' => 'Không tìm thấy hồ sơ huấn luyện viên.',
                    'code' => 'TRAINER_PROFILE_NOT_FOUND',
                ]);
        }
        $this->assertSame($responses[0]->json(), $responses[1]->json());
        $this->assertSame($responses[1]->json(), $responses[2]->json());
        $this->assertDatabaseHas('ho_so_huan_luyen_vien', ['id' => $foreignProfileId]);
    }

    private function taoHoSoHuanLuyenVien(
        int $nguoiDungId,
        CarbonImmutable $hienTai,
        ?string $gioiThieu = null,
        ?string $chuyenMon = null,
        string $trangThai = 'HOAT_DONG',
    ): int {
        $profileId = DB::table('ho_so_huan_luyen_vien')->insertGetId([
            'nguoi_dung_id' => $nguoiDungId,
            'ma_huan_luyen_vien' => 'PT_GET_'.strtoupper(bin2hex(random_bytes(5))),
            'gioi_thieu' => $gioiThieu,
            'chuyen_mon' => $chuyenMon,
            'trang_thai' => $trangThai,
            'ngay_tao' => $hienTai->format('Y-m-d H:i:s.u'),
            'ngay_cap_nhat' => $hienTai->format('Y-m-d H:i:s.u'),
        ]);

        DB::table('ho_so_huan_luyen_vien')->where('id', $profileId)->update([
            'ma_huan_luyen_vien' => 'PT-'.str_pad((string) $profileId, 6, '0', STR_PAD_LEFT),
        ]);

        return (int) $profileId;
    }

    /**
     * Chứng minh toàn bộ request GET hồ sơ PT chỉ phát sinh câu lệnh đọc.
     *
     * Input: query log của đúng request đang kiểm thử.
     * Cách hoạt động: chuẩn hóa SQL, yêu cầu mọi câu lệnh bắt đầu bằng SELECT và
     * loại trừ DML/DDL cùng khóa FOR UPDATE.
     * Kết quả: assertion pass khi request không ghi hoặc khóa hàng Database.
     * Side effect: chỉ làm test fail nếu route/auth/service phát sinh write.
     *
     * @param  array<int, array{query?: string}>  $queries
     */
    private function assertRequestQueryLogIsReadOnly(array $queries): void
    {
        $this->assertNotEmpty($queries, 'Request phải phát sinh query log để chứng minh read-only.');

        foreach ($queries as $query) {
            $sql = strtoupper((string) preg_replace('/\s+/', ' ', trim((string) ($query['query'] ?? ''))));
            $this->assertStringStartsWith('SELECT', $sql, 'Request read-only chỉ được phát sinh SELECT: '.$sql);
            $this->assertStringNotContainsString('FOR UPDATE', $sql, 'Request read-only không được khóa hàng: '.$sql);
            $this->assertDoesNotMatchRegularExpression(
                '/\b(INSERT|UPDATE|DELETE|REPLACE|ALTER|CREATE|DROP|TRUNCATE|LOCK|UNLOCK)\b/',
                $sql,
                'Request read-only không được phát sinh DML/DDL: '.$sql,
            );
        }
    }
}
