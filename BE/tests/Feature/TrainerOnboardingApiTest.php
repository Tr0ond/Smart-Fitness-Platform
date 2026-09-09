<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessPasswordResetRequest;
use App\Models\NhatKyHeThong;
use App\Notifications\PasswordResetNotification;
use App\Services\Admin\TrainerOnboardingService;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Mockery;
use RuntimeException;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\TestCase;

class TrainerOnboardingApiTest extends TestCase
{
    use CreatesAuthenticationFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batDauGiaoDichAuthCoLap();
    }

    protected function tearDown(): void
    {
        Event::forget('eloquent.creating: '.NhatKyHeThong::class);
        $this->ketThucGiaoDichAuthCoLap();
        parent::tearDown();
    }

    public function test_admin_creates_trainer_account_profile_role_and_password_setup_request_idempotently(): void
    {
        Notification::fake();
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $token = (string) $this->dangNhapApi($admin['user']->thu_dien_tu)->json('data.access_token');
        $payload = [
            'name' => 'Trainer Onboarding',
            'email' => '  TRAINER.ONBOARDING@EXAMPLE.COM ',
            'phone' => '0901234567',
            'introduction' => 'PT mới.',
            'specialties' => 'Sức mạnh',
            'status' => 'HOAT_DONG',
        ];
        $key = '1d14ca70-22f0-4c6b-9d17-0858842bb9fe';

        $this->postJson('/api/admin/trainers', $payload)->assertUnauthorized();
        $member = $this->taoNguoiDungAuth(['MEMBER']);
        $memberToken = (string) $this->dangNhapApi($member['user']->thu_dien_tu)->json('data.access_token');
        $this->withHeaders([...$this->bearer($memberToken), 'Idempotency-Key' => $key])
            ->postJson('/api/admin/trainers', $payload)
            ->assertForbidden();

        $first = $this->withHeaders([...$this->bearer($token), 'Idempotency-Key' => $key])
            ->postJson('/api/admin/trainers', $payload)
            ->assertCreated()
            ->assertJsonPath('data.account.email', 'trainer.onboarding@example.com')
            ->assertJsonPath('data.account.status', 'HOAT_DONG')
            ->assertJsonPath('data.role.code', 'PT')
            ->assertJsonPath('data.role.active', true)
            ->assertJsonPath('data.invitation', 'QUEUED')
            ->assertJsonPath('data.replayed', false);
        $taiKhoanId = (int) $first->json('data.account.id');
        $hoSoId = (int) $first->json('data.trainer_profile.id');
        $this->assertSame('PT'.str_pad((string) $hoSoId, 6, '0', STR_PAD_LEFT), $first->json('data.trainer_profile.trainer_code'));
        $this->assertSame(1, DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('nguoi_dung_id', $taiKhoanId)
            ->where('vai_tro.ma_vai_tro', 'PT')
            ->whereNull('thu_hoi_luc')
            ->count());
        $this->assertSame(
            ['TAO_TAI_KHOAN_PT', 'TAO_HO_SO_HUAN_LUYEN_VIEN', 'CAP_VAI_TRO_PT'],
            DB::table('nhat_ky_he_thong')->where('nguoi_thuc_hien_id', $admin['user']->getKey())->orderBy('id')->pluck('hanh_dong')->all(),
        );
        $audits = DB::table('nhat_ky_he_thong')
            ->where('nguoi_thuc_hien_id', $admin['user']->getKey())
            ->orderBy('id')
            ->get();
        $this->assertCount(3, $audits);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            (string) $audits[0]->khoa_tuong_quan,
        );
        $this->assertSame([$audits[0]->khoa_tuong_quan], $audits->pluck('khoa_tuong_quan')->unique()->values()->all());
        $this->assertNull($audits[0]->du_lieu_truoc);
        $this->assertSame([
            'id' => $taiKhoanId,
            'name' => 'Trainer Onboarding',
            'email' => 'trainer.onboarding@example.com',
            'branch_id' => $admin['branch_id'],
            'status' => 'HOAT_DONG',
        ], json_decode((string) $audits[0]->du_lieu_sau, true, 512, JSON_THROW_ON_ERROR));
        $this->assertNull($audits[1]->du_lieu_truoc);
        $this->assertSame([
            'id' => $hoSoId,
            'account_id' => $taiKhoanId,
            'trainer_code' => 'PT'.str_pad((string) $hoSoId, 6, '0', STR_PAD_LEFT),
            'introduction' => 'PT mới.',
            'specialties' => 'Sức mạnh',
            'status' => 'HOAT_DONG',
        ], json_decode((string) $audits[1]->du_lieu_sau, true, 512, JSON_THROW_ON_ERROR));
        $roleRow = DB::table('phan_quyen_nguoi_dung')->where('id', $audits[2]->dinh_danh_doi_tuong)->sole();
        $this->assertNull($audits[2]->du_lieu_truoc);
        $this->assertSame([
            'assignment_id' => (int) $roleRow->id,
            'account_id' => $taiKhoanId,
            'role' => 'PT',
            'granted_by_id' => $admin['user']->getKey(),
            'granted_at' => $roleRow->cap_luc,
            'revoked_at' => null,
            'active' => true,
        ], json_decode((string) $audits[2]->du_lieu_sau, true, 512, JSON_THROW_ON_ERROR));
        $auditIdsAfterFirstSuccess = $audits->pluck('id')->all();
        $serializedAudits = serialize($audits->all());
        foreach (['mat_khau_bam', 'raw_token', '0901234567', $key] as $sensitiveValue) {
            $this->assertStringNotContainsString($sensitiveValue, $serializedAudits);
        }
        $notification = $this->passwordResetNotificationFor('trainer.onboarding@example.com');

        $this->withHeaders([...$this->bearer($token), 'Idempotency-Key' => $key])
            ->postJson('/api/admin/trainers', $payload)
            ->assertOk()
            ->assertJsonPath('data.replayed', true)
            ->assertJsonPath('data.account.id', $taiKhoanId);
        $this->assertSame(1, DB::table('nguoi_dung')->where('id', $taiKhoanId)->count());
        $this->assertSame(1, DB::table('ho_so_huan_luyen_vien')->where('id', $hoSoId)->count());
        $this->assertSame($auditIdsAfterFirstSuccess, DB::table('nhat_ky_he_thong')
            ->where('nguoi_thuc_hien_id', $admin['user']->getKey())
            ->orderBy('id')
            ->pluck('id')
            ->all());
        Notification::assertSentOnDemandTimes(PasswordResetNotification::class, 1);

        $this->withHeaders([...$this->bearer($token), 'Idempotency-Key' => $key])
            ->postJson('/api/admin/trainers', [...$payload, 'specialties' => 'Khác'])
            ->assertConflict()
            ->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT');

        $this->postJson('/api/auth/reset-password', [
            'token' => $notification->rawToken,
            'password' => 'Trainer!Password123',
            'password_confirmation' => 'Trainer!Password123',
        ])->assertOk();
        $ptToken = (string) $this->dangNhapApi(
            'trainer.onboarding@example.com',
            'Trainer!Password123',
        )->json('data.access_token');
        $this->getJson('/api/profile/trainer', $this->bearer($ptToken))
            ->assertOk()
            ->assertJsonPath('data.id', $hoSoId);
        $this->patchJson('/api/profile/trainer', ['specialties' => 'Sức mạnh, Mobility'], $this->bearer($ptToken))
            ->assertOk()
            ->assertJsonPath('data.specialties', 'Sức mạnh, Mobility');
        $this->getJson('/api/pt/members', $this->bearer($ptToken))->assertOk()->assertExactJson(['data' => []]);
    }

    public function test_admin_can_onboard_existing_active_account_and_regrant_without_duplicate_profile(): void
    {
        Queue::fake();
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $account = $this->taoNguoiDungAuth(['MEMBER']);
        $account['user']->forceFill(['chi_nhanh_id' => $admin['branch_id']])->save();
        $this->assertSame($admin['branch_id'], (int) $account['user']->fresh()->chi_nhanh_id);
        $adminToken = (string) $this->dangNhapApi($admin['user']->thu_dien_tu)->json('data.access_token');
        $uri = '/api/admin/accounts/'.$account['user']->getKey().'/trainer-profile';
        $key = '94e1cf74-7a9f-42d1-bf0d-f3bfdd4b8b4c';
        $headers = [...$this->bearer($adminToken), 'Idempotency-Key' => $key];
        $body = [
            'introduction' => 'Onboard account đã có.',
            'specialties' => 'Phục hồi',
            'status' => 'HOAT_DONG',
        ];

        $this->postJson($uri, [], $this->bearer($adminToken))
            ->assertStatus(422);

        $created = $this->postJson($uri, $body, $headers)->assertOk()
            ->assertJsonPath('data.invitation', 'NOT_REQUESTED')
            ->assertJsonPath('data.replayed', false);
        $profileId = (int) $created->json('data.trainer_profile.id');
        $this->assertSame('GRANTED', $created->json('data.role.transition'));

        $profileRowsAfterCreate = DB::table('ho_so_huan_luyen_vien')
            ->where('nguoi_dung_id', $account['user']->getKey())
            ->get();
        $this->assertCount(1, $profileRowsAfterCreate);
        $profileRowAfterCreate = $profileRowsAfterCreate[0];
        $this->assertSame($profileId, (int) $profileRowAfterCreate->id);
        $ptRoleRowsAfterCreate = DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $account['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'PT')
            ->select('phan_quyen_nguoi_dung.*')
            ->get();
        $this->assertCount(1, $ptRoleRowsAfterCreate);
        $ptRoleRowAfterCreate = $ptRoleRowsAfterCreate[0];
        $this->assertSame($account['user']->getKey(), (int) $ptRoleRowAfterCreate->nguoi_dung_id);
        $logicalActionTimestamp = (string) $profileRowAfterCreate->ngay_tao;
        $this->assertSame($logicalActionTimestamp, (string) $ptRoleRowAfterCreate->cap_luc);
        $this->assertSame($logicalActionTimestamp, (string) $ptRoleRowAfterCreate->ngay_tao);

        $auditsAfterCreate = DB::table('nhat_ky_he_thong')
            ->where('nguoi_thuc_hien_id', $admin['user']->getKey())
            ->orderBy('id')
            ->get();
        $this->assertCount(2, $auditsAfterCreate);
        $this->assertSame(
            ['TAO_HO_SO_HUAN_LUYEN_VIEN', 'CAP_VAI_TRO_PT'],
            $auditsAfterCreate->pluck('hanh_dong')->all(),
        );
        $profileAuditAfterCreate = $auditsAfterCreate[0];
        $roleAuditAfterCreate = $auditsAfterCreate[1];
        $this->assertSame('HO_SO_HUAN_LUYEN_VIEN', $profileAuditAfterCreate->loai_doi_tuong);
        $this->assertSame($profileId, (int) $profileAuditAfterCreate->dinh_danh_doi_tuong);
        $this->assertSame('PHAN_QUYEN_NGUOI_DUNG', $roleAuditAfterCreate->loai_doi_tuong);
        $this->assertSame((int) $ptRoleRowAfterCreate->id, (int) $roleAuditAfterCreate->dinh_danh_doi_tuong);
        foreach ($auditsAfterCreate as $audit) {
            $this->assertSame($admin['user']->getKey(), (int) $audit->nguoi_thuc_hien_id);
            $this->assertSame('NGUOI_DUNG', $audit->loai_tac_nhan);
            $this->assertSame('THANH_CONG', $audit->ket_qua);
            $this->assertNotNull($audit->thuc_hien_luc);
            $this->assertNotNull($audit->ngay_tao);
            $this->assertSame($logicalActionTimestamp, (string) $audit->thuc_hien_luc);
            $this->assertSame($logicalActionTimestamp, (string) $audit->ngay_tao);
        }
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            (string) $profileAuditAfterCreate->khoa_tuong_quan,
        );
        $this->assertSame(
            [$profileAuditAfterCreate->khoa_tuong_quan],
            $auditsAfterCreate->pluck('khoa_tuong_quan')->unique()->values()->all(),
        );
        $this->assertNull($profileAuditAfterCreate->du_lieu_truoc);
        $profileSnapshotAfterCreate = [
            'id' => $profileId,
            'account_id' => $account['user']->getKey(),
            'trainer_code' => (string) $profileRowAfterCreate->ma_huan_luyen_vien,
            'introduction' => 'Onboard account đã có.',
            'specialties' => 'Phục hồi',
            'status' => 'HOAT_DONG',
        ];
        $this->assertSame(
            $profileSnapshotAfterCreate,
            json_decode((string) $profileAuditAfterCreate->du_lieu_sau, true, 512, JSON_THROW_ON_ERROR),
        );
        $this->assertNull($roleAuditAfterCreate->du_lieu_truoc);
        $roleSnapshotAfterCreate = [
            'assignment_id' => (int) $ptRoleRowAfterCreate->id,
            'account_id' => $account['user']->getKey(),
            'role' => 'PT',
            'granted_by_id' => $admin['user']->getKey(),
            'granted_at' => $ptRoleRowAfterCreate->cap_luc,
            'revoked_at' => null,
            'active' => true,
        ];
        $this->assertSame(
            $roleSnapshotAfterCreate,
            json_decode((string) $roleAuditAfterCreate->du_lieu_sau, true, 512, JSON_THROW_ON_ERROR),
        );
        $serializedExistingAccountAudits = serialize([
            'audits' => $auditsAfterCreate->all(),
            'snapshots' => [$profileSnapshotAfterCreate, $roleSnapshotAfterCreate],
        ]);
        foreach ([
            $key,
            'password=Trainer!Password123',
            'mat_khau_bam=hash-secret',
            'raw_token=raw-reset-token',
            'Bearer token=bearer-secret',
            'Authorization: Bearer bearer-secret',
            'raw-request=raw-request-secret',
        ] as $sensitiveValue) {
            $this->assertStringNotContainsString($sensitiveValue, $serializedExistingAccountAudits);
        }

        $profileIds = DB::table('ho_so_huan_luyen_vien')
            ->where('nguoi_dung_id', $account['user']->getKey())
            ->orderBy('id')
            ->pluck('id')
            ->all();
        $ptRoleIds = DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $account['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'PT')
            ->orderBy('phan_quyen_nguoi_dung.id')
            ->pluck('phan_quyen_nguoi_dung.id')
            ->all();
        $auditIds = DB::table('nhat_ky_he_thong')
            ->where('nguoi_thuc_hien_id', $admin['user']->getKey())
            ->orderBy('id')
            ->pluck('id')
            ->all();
        $idempotency = DB::table('yeu_cau_chong_lap')
            ->where('nguoi_dung_id', $admin['user']->getKey())
            ->where('pham_vi', 'ADMIN_TRAINER_EXISTING_ACCOUNT')
            ->where('khoa_yeu_cau', $key)
            ->first();
        $this->assertNotNull($idempotency);
        $idempotencySnapshot = [
            'id' => (int) $idempotency->id,
            'ma_bam_noi_dung' => (string) $idempotency->ma_bam_noi_dung,
            'trang_thai' => (string) $idempotency->trang_thai,
            'ma_phan_hoi' => (int) $idempotency->ma_phan_hoi,
            'ket_qua_da_loc' => json_decode((string) $idempotency->ket_qua_da_loc, true, 512, JSON_THROW_ON_ERROR),
        ];

        $this->postJson($uri, $body, $headers)->assertOk()
            ->assertJsonPath('data.replayed', true)
            ->assertJsonPath('data.trainer_profile.id', $profileId);
        $this->assertSame($profileIds, DB::table('ho_so_huan_luyen_vien')
            ->where('nguoi_dung_id', $account['user']->getKey())
            ->orderBy('id')
            ->pluck('id')
            ->all());
        $this->assertSame($ptRoleIds, DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $account['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'PT')
            ->orderBy('phan_quyen_nguoi_dung.id')
            ->pluck('phan_quyen_nguoi_dung.id')
            ->all());
        $this->assertSame($auditIds, DB::table('nhat_ky_he_thong')
            ->where('nguoi_thuc_hien_id', $admin['user']->getKey())
            ->orderBy('id')
            ->pluck('id')
            ->all());
        $idempotencySauReplay = DB::table('yeu_cau_chong_lap')
            ->where('id', $idempotencySnapshot['id'])
            ->first();
        $this->assertNotNull($idempotencySauReplay);
        $this->assertSame($idempotencySnapshot, [
            'id' => (int) $idempotencySauReplay->id,
            'ma_bam_noi_dung' => (string) $idempotencySauReplay->ma_bam_noi_dung,
            'trang_thai' => (string) $idempotencySauReplay->trang_thai,
            'ma_phan_hoi' => (int) $idempotencySauReplay->ma_phan_hoi,
            'ket_qua_da_loc' => json_decode((string) $idempotencySauReplay->ket_qua_da_loc, true, 512, JSON_THROW_ON_ERROR),
        ]);

        $this->postJson($uri, [...$body, 'specialties' => 'Khác'], $headers)->assertConflict()
            ->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT');

        $secondAccount = $this->taoNguoiDungAuth(['MEMBER']);
        $secondAccount['user']->forceFill(['chi_nhanh_id' => $admin['branch_id']])->save();
        $this->assertSame($admin['branch_id'], (int) $secondAccount['user']->fresh()->chi_nhanh_id);
        $secondUri = '/api/admin/accounts/'.$secondAccount['user']->getKey().'/trainer-profile';
        $this->postJson($secondUri, $body, $headers)->assertConflict()
            ->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT');
        $this->assertSame(0, DB::table('ho_so_huan_luyen_vien')
            ->where('nguoi_dung_id', $secondAccount['user']->getKey())
            ->count());
        $this->assertSame(0, DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $secondAccount['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'PT')
            ->count());
        $this->assertSame($auditIds, DB::table('nhat_ky_he_thong')
            ->where('nguoi_thuc_hien_id', $admin['user']->getKey())
            ->orderBy('id')
            ->pluck('id')
            ->all());
        $idempotencySauXungDot = DB::table('yeu_cau_chong_lap')
            ->where('id', $idempotencySnapshot['id'])
            ->first();
        $this->assertNotNull($idempotencySauXungDot);
        $this->assertSame($idempotencySnapshot, [
            'id' => (int) $idempotencySauXungDot->id,
            'ma_bam_noi_dung' => (string) $idempotencySauXungDot->ma_bam_noi_dung,
            'trang_thai' => (string) $idempotencySauXungDot->trang_thai,
            'ma_phan_hoi' => (int) $idempotencySauXungDot->ma_phan_hoi,
            'ket_qua_da_loc' => json_decode((string) $idempotencySauXungDot->ket_qua_da_loc, true, 512, JSON_THROW_ON_ERROR),
        ]);
        $this->assertSame($profileIds, DB::table('ho_so_huan_luyen_vien')
            ->where('nguoi_dung_id', $account['user']->getKey())
            ->orderBy('id')
            ->pluck('id')
            ->all());
        $this->assertSame($ptRoleIds, DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $account['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'PT')
            ->orderBy('phan_quyen_nguoi_dung.id')
            ->pluck('phan_quyen_nguoi_dung.id')
            ->all());

        $roleBeforeRevoke = DB::table('phan_quyen_nguoi_dung')
            ->where('id', $ptRoleIds[0])
            ->sole();
        $this->deleteJson('/api/admin/accounts/'.$account['user']->getKey().'/roles/PT', [], $this->bearer($adminToken))
            ->assertOk();
        $roleAfterRevoke = DB::table('phan_quyen_nguoi_dung')->where('id', $ptRoleIds[0])->sole();
        $this->assertNotNull($roleAfterRevoke->thu_hoi_luc);
        $regranted = $this->postJson($uri, [], [
            ...$this->bearer($adminToken),
            'Idempotency-Key' => '4df0c270-11b1-4d9a-b6e2-cf4f845d40c6',
        ])->assertOk();
        $this->assertSame($profileId, (int) $regranted->json('data.trainer_profile.id'));
        $this->assertSame('REGRANTED', $regranted->json('data.role.transition'));
        $this->assertSame(1, DB::table('ho_so_huan_luyen_vien')->where('nguoi_dung_id', $account['user']->getKey())->count());
        $roleAfterRegrant = DB::table('phan_quyen_nguoi_dung')->where('id', $ptRoleIds[0])->sole();
        $this->assertSame((int) $roleBeforeRevoke->id, (int) $roleAfterRegrant->id);
        $this->assertSame($roleBeforeRevoke->ngay_tao, $roleAfterRegrant->ngay_tao);
        $regrantAudit = DB::table('nhat_ky_he_thong')
            ->where('nguoi_thuc_hien_id', $admin['user']->getKey())
            ->where('hanh_dong', 'CAP_LAI_VAI_TRO_PT')
            ->latest('id')
            ->first();
        $this->assertNotNull($regrantAudit);
        $this->assertSame([
            'assignment_id' => (int) $roleBeforeRevoke->id,
            'account_id' => $account['user']->getKey(),
            'role' => 'PT',
            'granted_by_id' => $roleBeforeRevoke->nguoi_cap_id,
            'granted_at' => $roleBeforeRevoke->cap_luc,
            'revoked_at' => $roleAfterRevoke->thu_hoi_luc,
            'active' => false,
        ], json_decode((string) $regrantAudit->du_lieu_truoc, true, 512, JSON_THROW_ON_ERROR));
        $this->assertSame([
            'assignment_id' => (int) $roleAfterRegrant->id,
            'account_id' => $account['user']->getKey(),
            'role' => 'PT',
            'granted_by_id' => $admin['user']->getKey(),
            'granted_at' => $roleAfterRegrant->cap_luc,
            'revoked_at' => null,
            'active' => true,
        ], json_decode((string) $regrantAudit->du_lieu_sau, true, 512, JSON_THROW_ON_ERROR));
        Queue::assertNotPushed(ProcessPasswordResetRequest::class);
    }

    public function test_existing_profile_update_audits_normalized_before_after_and_active_role_noop(): void
    {
        Queue::fake();
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $account = $this->taoNguoiDungAuth(['MEMBER']);
        $account['user']->forceFill(['chi_nhanh_id' => $admin['branch_id']])->save();
        $adminToken = (string) $this->dangNhapApi($admin['user']->thu_dien_tu)->json('data.access_token');
        $uri = '/api/admin/accounts/'.$account['user']->getKey().'/trainer-profile';
        $createKey = '2a5c7e12-1fcb-4c28-b1d8-6e15ec7ec101';
        $this->postJson($uri, [
            'introduction' => 'Giới thiệu ban đầu',
            'specialties' => 'Phục hồi',
            'status' => 'HOAT_DONG',
        ], [...$this->bearer($adminToken), 'Idempotency-Key' => $createKey])->assertOk();

        $profileId = (int) DB::table('ho_so_huan_luyen_vien')
            ->where('nguoi_dung_id', $account['user']->getKey())
            ->value('id');
        $auditCountBeforeUpdate = DB::table('nhat_ky_he_thong')
            ->where('nguoi_thuc_hien_id', $admin['user']->getKey())
            ->count();
        $updateKey = '4f13e892-0cc0-49f5-a4f8-8ca13b0dd7a1';
        $this->postJson($uri, [
            'introduction' => '  Giới thiệu đã chuẩn hóa  ',
            'specialties' => null,
        ], [...$this->bearer($adminToken), 'Idempotency-Key' => $updateKey])
            ->assertOk()
            ->assertJsonPath('data.role.transition', 'UNCHANGED');

        $audits = DB::table('nhat_ky_he_thong')
            ->where('nguoi_thuc_hien_id', $admin['user']->getKey())
            ->orderBy('id')
            ->get();
        $this->assertCount($auditCountBeforeUpdate + 1, $audits);
        $profileAudit = $audits->last();
        $this->assertSame('CAP_NHAT_HO_SO_HUAN_LUYEN_VIEN', $profileAudit->hanh_dong);
        $this->assertSame('HO_SO_HUAN_LUYEN_VIEN', $profileAudit->loai_doi_tuong);
        $this->assertSame($profileId, (int) $profileAudit->dinh_danh_doi_tuong);
        $this->assertSame([
            'id' => $profileId,
            'account_id' => $account['user']->getKey(),
            'trainer_code' => 'PT'.str_pad((string) $profileId, 6, '0', STR_PAD_LEFT),
            'introduction' => 'Giới thiệu ban đầu',
            'specialties' => 'Phục hồi',
            'status' => 'HOAT_DONG',
        ], json_decode((string) $profileAudit->du_lieu_truoc, true, 512, JSON_THROW_ON_ERROR));
        $this->assertSame([
            'id' => $profileId,
            'account_id' => $account['user']->getKey(),
            'trainer_code' => 'PT'.str_pad((string) $profileId, 6, '0', STR_PAD_LEFT),
            'introduction' => 'Giới thiệu đã chuẩn hóa',
            'specialties' => null,
            'status' => 'HOAT_DONG',
        ], json_decode((string) $profileAudit->du_lieu_sau, true, 512, JSON_THROW_ON_ERROR));
        $this->assertSame(
            ['TAO_HO_SO_HUAN_LUYEN_VIEN', 'CAP_VAI_TRO_PT', 'CAP_NHAT_HO_SO_HUAN_LUYEN_VIEN'],
            $audits->pluck('hanh_dong')->all(),
        );

        $noOpKey = '9cdd20f6-108d-4c63-8c94-770a2e5de2be';
        $this->postJson($uri, [
            'introduction' => 'Giới thiệu đã chuẩn hóa',
            'specialties' => null,
        ], [...$this->bearer($adminToken), 'Idempotency-Key' => $noOpKey])
            ->assertOk()
            ->assertJsonPath('data.role.transition', 'UNCHANGED');
        $this->assertSame($auditCountBeforeUpdate + 1, DB::table('nhat_ky_he_thong')
            ->where('nguoi_thuc_hien_id', $admin['user']->getKey())
            ->count());
        Queue::assertNotPushed(ProcessPasswordResetRequest::class);
    }

    public function test_malformed_idempotency_key_on_both_trainer_onboarding_endpoints_has_no_side_effect(): void
    {
        Queue::fake();
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $target = $this->taoNguoiDungAuth(['MEMBER']);
        $target['user']->forceFill(['chi_nhanh_id' => $admin['branch_id']])->save();
        $this->assertSame($admin['branch_id'], (int) $target['user']->fresh()->chi_nhanh_id);
        $adminToken = (string) $this->dangNhapApi($admin['user']->thu_dien_tu)->json('data.access_token');
        $malformedKey = 'not-a-uuid';
        $newEmail = 'malformed-key.trainer@example.com';
        $newHeaders = [...$this->bearer($adminToken), 'Idempotency-Key' => $malformedKey];
        $auditIds = DB::table('nhat_ky_he_thong')
            ->where('nguoi_thuc_hien_id', $admin['user']->getKey())
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->postJson('/api/admin/trainers', [
            'name' => 'Malformed Key Trainer',
            'email' => $newEmail,
            'phone' => null,
            'introduction' => 'Không được tạo.',
            'specialties' => 'Không được tạo.',
            'status' => 'HOAT_DONG',
        ], $newHeaders)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['_idempotency_key']);

        $this->postJson('/api/admin/accounts/'.$target['user']->getKey().'/trainer-profile', [
            'introduction' => 'Không được tạo.',
            'specialties' => 'Không được tạo.',
            'status' => 'HOAT_DONG',
        ], $newHeaders)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['_idempotency_key']);

        $this->assertSame(0, DB::table('nguoi_dung')->where('thu_dien_tu', $newEmail)->count());
        $this->assertSame(0, DB::table('ho_so_huan_luyen_vien')
            ->where('nguoi_dung_id', $target['user']->getKey())
            ->count());
        $this->assertSame(0, DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $target['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'PT')
            ->count());
        $this->assertSame($auditIds, DB::table('nhat_ky_he_thong')
            ->where('nguoi_thuc_hien_id', $admin['user']->getKey())
            ->orderBy('id')
            ->pluck('id')
            ->all());
        $this->assertSame(0, DB::table('yeu_cau_chong_lap')
            ->where('nguoi_dung_id', $admin['user']->getKey())
            ->whereIn('pham_vi', ['ADMIN_TRAINER_ONBOARDING', 'ADMIN_TRAINER_EXISTING_ACCOUNT'])
            ->where('khoa_yeu_cau', $malformedKey)
            ->count());
        Queue::assertNotPushed(ProcessPasswordResetRequest::class);
    }

    public function test_onboarding_rejects_inactive_account_and_rolls_back_everything_when_audit_fails(): void
    {
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $inactive = $this->taoNguoiDungAuth(['MEMBER'], trangThai: 'BI_KHOA');
        $inactive['user']->forceFill(['chi_nhanh_id' => $admin['branch_id']])->save();
        $this->assertSame($admin['branch_id'], (int) $inactive['user']->fresh()->chi_nhanh_id);
        $token = (string) $this->dangNhapApi($admin['user']->thu_dien_tu)->json('data.access_token');
        $this->postJson(
            '/api/admin/accounts/'.$inactive['user']->getKey().'/trainer-profile',
            [],
            [...$this->bearer($token), 'Idempotency-Key' => '5a2a5d53-4e63-4a85-b93c-a37e0bd03eb1'],
        )->assertConflict()->assertJsonPath('code', 'ACCOUNT_NOT_ACTIVE');

        $foreign = $this->taoNguoiDungAuth(['MEMBER']);
        $this->assertNotSame($admin['branch_id'], (int) $foreign['user']->chi_nhanh_id);
        $this->postJson(
            '/api/admin/accounts/'.$foreign['user']->getKey().'/trainer-profile',
            ['specialties' => 'Không được đọc'],
            [...$this->bearer($token), 'Idempotency-Key' => '7c61c74e-291f-46de-a0a7-90a5d02c10a2'],
        )->assertNotFound()->assertJsonPath('code', 'ACCOUNT_NOT_FOUND');
        $this->assertSame(0, DB::table('ho_so_huan_luyen_vien')->where('nguoi_dung_id', $foreign['user']->getKey())->count());

        Event::listen('eloquent.creating: '.NhatKyHeThong::class, static function (): never {
            throw new RuntimeException('Forced trainer audit failure');
        });
        $email = 'trainer.rollback@example.com';
        $this->expectException(RuntimeException::class);
        try {
            app(TrainerOnboardingService::class)->tao($admin['user'], [
                'name' => 'Rollback Trainer',
                'email' => $email,
                'phone' => null,
                'introduction' => null,
                'specialties' => null,
                'status' => 'HOAT_DONG',
                '_idempotency_key' => '2f3470af-45b3-498d-a5fe-408a63a3684a',
            ]);
        } finally {
            $this->assertSame(0, DB::table('nguoi_dung')->where('thu_dien_tu', $email)->count());
            $this->assertSame(0, DB::table('ho_so_huan_luyen_vien')
                ->whereIn('nguoi_dung_id', DB::table('nguoi_dung')->where('thu_dien_tu', $email)->pluck('id'))
                ->count());
            $this->assertSame(0, DB::table('phan_quyen_nguoi_dung')
                ->whereIn('nguoi_dung_id', DB::table('nguoi_dung')->where('thu_dien_tu', $email)->pluck('id'))
                ->count());
            $this->assertSame(0, DB::table('yeu_cau_chong_lap')
                ->where('pham_vi', 'ADMIN_TRAINER_ONBOARDING')
                ->where('khoa_yeu_cau', '2f3470af-45b3-498d-a5fe-408a63a3684a')
                ->count());
        }
    }

    public function test_existing_account_profile_audit_failure_rolls_back_and_same_key_retry_is_clean(): void
    {
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $account = $this->taoNguoiDungAuth(['MEMBER']);
        $account['user']->forceFill(['chi_nhanh_id' => $admin['branch_id']])->save();
        $baselineRoleIds = DB::table('phan_quyen_nguoi_dung')
            ->where('nguoi_dung_id', $account['user']->getKey())
            ->orderBy('id')
            ->pluck('id')
            ->all();
        $this->assertNotEmpty($baselineRoleIds);
        $this->assertSame($baselineRoleIds, DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $account['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'MEMBER')
            ->orderBy('phan_quyen_nguoi_dung.id')
            ->pluck('phan_quyen_nguoi_dung.id')
            ->all());
        $key = 'c3a9b81e-844a-4a2f-96ed-7aa87b8792e8';
        $body = [
            'introduction' => 'Profile audit rollback',
            'specialties' => 'Mobility',
            'status' => 'HOAT_DONG',
            '_idempotency_key' => $key,
        ];

        Event::listen('eloquent.creating: '.NhatKyHeThong::class, static function (NhatKyHeThong $audit): void {
            if ($audit->hanh_dong === 'TAO_HO_SO_HUAN_LUYEN_VIEN') {
                throw new RuntimeException('Forced profile audit failure');
            }
        });
        try {
            app(TrainerOnboardingService::class)->onboardTaiKhoanDaCo($admin['user'], (int) $account['user']->getKey(), $body);
            $this->fail('Expected the profile audit boundary to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced profile audit failure', $exception->getMessage());
        }
        Event::forget('eloquent.creating: '.NhatKyHeThong::class);

        $this->assertSame(0, DB::table('ho_so_huan_luyen_vien')->where('nguoi_dung_id', $account['user']->getKey())->count());
        $this->assertSame($baselineRoleIds, DB::table('phan_quyen_nguoi_dung')
            ->where('nguoi_dung_id', $account['user']->getKey())
            ->orderBy('id')
            ->pluck('id')
            ->all());
        $this->assertSame(0, DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $account['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'PT')
            ->count());
        $this->assertSame(0, DB::table('yeu_cau_chong_lap')
            ->where('nguoi_dung_id', $admin['user']->getKey())
            ->where('pham_vi', 'ADMIN_TRAINER_EXISTING_ACCOUNT')
            ->where('khoa_yeu_cau', $key)
            ->count());
        $this->assertSame(0, DB::table('nhat_ky_he_thong')->where('nguoi_thuc_hien_id', $admin['user']->getKey())->count());

        $result = app(TrainerOnboardingService::class)->onboardTaiKhoanDaCo($admin['user'], (int) $account['user']->getKey(), $body);
        $this->assertFalse($result['replayed']);
        $this->assertSame('GRANTED', $result['role']['transition']);
        $this->assertSame(1, DB::table('ho_so_huan_luyen_vien')->where('nguoi_dung_id', $account['user']->getKey())->count());
        $this->assertSame(1, DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $account['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'PT')
            ->count());
        $auditRows = DB::table('nhat_ky_he_thong')->where('nguoi_thuc_hien_id', $admin['user']->getKey())->orderBy('id')->get();
        $this->assertSame(['TAO_HO_SO_HUAN_LUYEN_VIEN', 'CAP_VAI_TRO_PT'], $auditRows->pluck('hanh_dong')->all());
        $this->assertSame([$auditRows[0]->khoa_tuong_quan], $auditRows->pluck('khoa_tuong_quan')->unique()->values()->all());

        $replay = app(TrainerOnboardingService::class)->onboardTaiKhoanDaCo($admin['user'], (int) $account['user']->getKey(), $body);
        $this->assertTrue($replay['replayed']);
        $this->assertCount(2, DB::table('nhat_ky_he_thong')->where('nguoi_thuc_hien_id', $admin['user']->getKey())->get());
    }

    public function test_existing_account_role_audit_failure_rolls_back_profile_grant_and_same_key_retry_succeeds_once(): void
    {
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $account = $this->taoNguoiDungAuth(['MEMBER']);
        $account['user']->forceFill(['chi_nhanh_id' => $admin['branch_id']])->save();
        $baselineRoleIds = DB::table('phan_quyen_nguoi_dung')
            ->where('nguoi_dung_id', $account['user']->getKey())
            ->orderBy('id')
            ->pluck('id')
            ->all();
        $this->assertNotEmpty($baselineRoleIds);
        $this->assertSame($baselineRoleIds, DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $account['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'MEMBER')
            ->orderBy('phan_quyen_nguoi_dung.id')
            ->pluck('phan_quyen_nguoi_dung.id')
            ->all());
        $key = 'e0a77ff5-1b79-4d0e-ae8e-0edb0e0bc8c4';
        $body = [
            'introduction' => 'Role audit rollback',
            'specialties' => 'Strength',
            'status' => 'HOAT_DONG',
            '_idempotency_key' => $key,
        ];

        Event::listen('eloquent.creating: '.NhatKyHeThong::class, static function (NhatKyHeThong $audit): void {
            if ($audit->hanh_dong === 'CAP_VAI_TRO_PT') {
                throw new RuntimeException('Forced role audit failure');
            }
        });
        try {
            app(TrainerOnboardingService::class)->onboardTaiKhoanDaCo($admin['user'], (int) $account['user']->getKey(), $body);
            $this->fail('Expected the role audit boundary to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced role audit failure', $exception->getMessage());
        }
        Event::forget('eloquent.creating: '.NhatKyHeThong::class);

        $this->assertSame(0, DB::table('ho_so_huan_luyen_vien')->where('nguoi_dung_id', $account['user']->getKey())->count());
        $this->assertSame($baselineRoleIds, DB::table('phan_quyen_nguoi_dung')
            ->where('nguoi_dung_id', $account['user']->getKey())
            ->orderBy('id')
            ->pluck('id')
            ->all());
        $this->assertSame(0, DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $account['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'PT')
            ->count());
        $this->assertSame(0, DB::table('yeu_cau_chong_lap')
            ->where('nguoi_dung_id', $admin['user']->getKey())
            ->where('pham_vi', 'ADMIN_TRAINER_EXISTING_ACCOUNT')
            ->where('khoa_yeu_cau', $key)
            ->count());
        $this->assertSame(0, DB::table('nhat_ky_he_thong')->where('nguoi_thuc_hien_id', $admin['user']->getKey())->count());

        $result = app(TrainerOnboardingService::class)->onboardTaiKhoanDaCo($admin['user'], (int) $account['user']->getKey(), $body);
        $this->assertFalse($result['replayed']);
        $this->assertSame('GRANTED', $result['role']['transition']);
        $ptRoleIdsAfterSuccess = DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $account['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'PT')
            ->orderBy('phan_quyen_nguoi_dung.id')
            ->pluck('phan_quyen_nguoi_dung.id')
            ->all();
        $this->assertCount(1, $ptRoleIdsAfterSuccess);
        $auditRows = DB::table('nhat_ky_he_thong')->where('nguoi_thuc_hien_id', $admin['user']->getKey())->orderBy('id')->get();
        $this->assertSame(['TAO_HO_SO_HUAN_LUYEN_VIEN', 'CAP_VAI_TRO_PT'], $auditRows->pluck('hanh_dong')->all());

        $replay = app(TrainerOnboardingService::class)->onboardTaiKhoanDaCo($admin['user'], (int) $account['user']->getKey(), $body);
        $this->assertTrue($replay['replayed']);
        $this->assertSame($ptRoleIdsAfterSuccess, DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $account['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'PT')
            ->orderBy('phan_quyen_nguoi_dung.id')
            ->pluck('phan_quyen_nguoi_dung.id')
            ->all());
        $this->assertCount(2, DB::table('nhat_ky_he_thong')->where('nguoi_thuc_hien_id', $admin['user']->getKey())->get());
    }

    public function test_existing_account_regrant_audit_failure_rolls_back_profile_role_and_idempotency_then_same_key_retry_succeeds_once(): void
    {
        Queue::fake();
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $account = $this->taoNguoiDungAuth(['MEMBER']);
        $account['user']->forceFill(['chi_nhanh_id' => $admin['branch_id']])->save();
        $adminToken = (string) $this->dangNhapApi($admin['user']->thu_dien_tu)->json('data.access_token');
        $uri = '/api/admin/accounts/'.$account['user']->getKey().'/trainer-profile';
        $setupKey = 'b1fbb0ae-f01a-4f1b-a3f3-4b7ea2fc7251';
        $this->postJson($uri, [
            'introduction' => 'Regrant before',
            'specialties' => 'Strength',
            'status' => 'HOAT_DONG',
        ], [
            ...$this->bearer($adminToken),
            'Idempotency-Key' => $setupKey,
        ])
            ->assertOk()
            ->assertJsonPath('data.invitation', 'NOT_REQUESTED')
            ->assertJsonPath('data.replayed', false)
            ->assertJsonPath('data.role.transition', 'GRANTED');

        $profileRows = DB::table('ho_so_huan_luyen_vien')
            ->where('nguoi_dung_id', $account['user']->getKey())
            ->get();
        $this->assertCount(1, $profileRows);
        $profileId = (int) $profileRows[0]->id;
        $ptRoleRows = DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $account['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'PT')
            ->select('phan_quyen_nguoi_dung.*')
            ->get();
        $this->assertCount(1, $ptRoleRows);
        $ptRoleId = (int) $ptRoleRows[0]->id;

        $this->deleteJson(
            '/api/admin/accounts/'.$account['user']->getKey().'/roles/PT',
            [],
            $this->bearer($adminToken),
        )->assertOk();

        $chupHoSo = static function (object $row): array {
            return [
                'id' => (int) $row->id,
                'nguoi_dung_id' => (int) $row->nguoi_dung_id,
                'ma_huan_luyen_vien' => (string) $row->ma_huan_luyen_vien,
                'gioi_thieu' => $row->gioi_thieu === null ? null : (string) $row->gioi_thieu,
                'chuyen_mon' => $row->chuyen_mon === null ? null : (string) $row->chuyen_mon,
                'trang_thai' => (string) $row->trang_thai,
                'ngay_tao' => (string) $row->ngay_tao,
                'ngay_cap_nhat' => (string) $row->ngay_cap_nhat,
            ];
        };
        $chupPhanQuyen = static function (object $row): array {
            return [
                'id' => (int) $row->id,
                'nguoi_dung_id' => (int) $row->nguoi_dung_id,
                'vai_tro_id' => (int) $row->vai_tro_id,
                'nguoi_cap_id' => $row->nguoi_cap_id === null ? null : (int) $row->nguoi_cap_id,
                'cap_luc' => (string) $row->cap_luc,
                'thu_hoi_luc' => $row->thu_hoi_luc === null ? null : (string) $row->thu_hoi_luc,
                'ngay_tao' => (string) $row->ngay_tao,
                'ngay_cap_nhat' => (string) $row->ngay_cap_nhat,
            ];
        };
        $chupIdempotency = static function (int $adminId): array {
            return DB::table('yeu_cau_chong_lap')
                ->where('nguoi_dung_id', $adminId)
                ->where('pham_vi', 'ADMIN_TRAINER_EXISTING_ACCOUNT')
                ->orderBy('id')
                ->get()
                ->map(static function (object $row): array {
                    return [
                        'id' => (int) $row->id,
                        'nguoi_dung_id' => (int) $row->nguoi_dung_id,
                        'pham_vi' => (string) $row->pham_vi,
                        'khoa_yeu_cau' => (string) $row->khoa_yeu_cau,
                        'ma_bam_noi_dung' => (string) $row->ma_bam_noi_dung,
                        'trang_thai' => (string) $row->trang_thai,
                        'ma_phan_hoi' => $row->ma_phan_hoi === null ? null : (int) $row->ma_phan_hoi,
                        'ket_qua_da_loc' => $row->ket_qua_da_loc === null
                            ? null
                            : json_decode((string) $row->ket_qua_da_loc, true, 512, JSON_THROW_ON_ERROR),
                        'het_han_luc' => (string) $row->het_han_luc,
                        'ngay_tao' => (string) $row->ngay_tao,
                        'ngay_cap_nhat' => (string) $row->ngay_cap_nhat,
                    ];
                })
                ->all();
        };

        $profileBefore = DB::table('ho_so_huan_luyen_vien')->where('id', $profileId)->sole();
        $profileBeforeSnapshot = $chupHoSo($profileBefore);
        $ptRoleBefore = DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $account['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'PT')
            ->select('phan_quyen_nguoi_dung.*')
            ->sole();
        $this->assertSame($ptRoleId, (int) $ptRoleBefore->id);
        $this->assertNotNull($ptRoleBefore->thu_hoi_luc);
        $ptRoleBeforeSnapshot = $chupPhanQuyen($ptRoleBefore);
        $idempotencyBaseline = $chupIdempotency((int) $admin['user']->getKey());
        $this->assertNotEmpty($idempotencyBaseline);
        $auditBaselineIds = DB::table('nhat_ky_he_thong')
            ->where('nguoi_thuc_hien_id', $admin['user']->getKey())
            ->orderBy('id')
            ->pluck('id')
            ->all();
        $auditBaselineCount = count($auditBaselineIds);
        $this->assertGreaterThanOrEqual(3, $auditBaselineCount);

        $key = '79dbce45-2ad8-4f92-b4d7-c8d546f91f70';
        $body = [
            'introduction' => '  Regrant after  ',
            'specialties' => null,
            'status' => 'HOAT_DONG',
            '_idempotency_key' => $key,
        ];
        Event::listen('eloquent.creating: '.NhatKyHeThong::class, static function (NhatKyHeThong $audit): void {
            if ($audit->hanh_dong === 'CAP_LAI_VAI_TRO_PT') {
                throw new RuntimeException('Forced regrant audit failure');
            }
        });
        try {
            app(TrainerOnboardingService::class)->onboardTaiKhoanDaCo(
                $admin['user'],
                (int) $account['user']->getKey(),
                $body,
            );
            $this->fail('Expected the regrant audit boundary to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced regrant audit failure', $exception->getMessage());
        } finally {
            Event::forget('eloquent.creating: '.NhatKyHeThong::class);
        }

        $profileAfterFailureRows = DB::table('ho_so_huan_luyen_vien')
            ->where('nguoi_dung_id', $account['user']->getKey())
            ->get();
        $this->assertCount(1, $profileAfterFailureRows);
        $this->assertSame($profileBeforeSnapshot, $chupHoSo($profileAfterFailureRows[0]));
        $ptRoleAfterFailureRows = DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $account['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'PT')
            ->select('phan_quyen_nguoi_dung.*')
            ->get();
        $this->assertCount(1, $ptRoleAfterFailureRows);
        $this->assertSame($ptRoleBeforeSnapshot, $chupPhanQuyen($ptRoleAfterFailureRows[0]));
        $this->assertSame(1, DB::table('ho_so_huan_luyen_vien')
            ->where('nguoi_dung_id', $account['user']->getKey())
            ->count());
        $this->assertSame(1, DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $account['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'PT')
            ->count());
        $this->assertSame(0, DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $account['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'PT')
            ->whereNull('phan_quyen_nguoi_dung.thu_hoi_luc')
            ->count());
        $this->assertSame(1, DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $account['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'MEMBER')
            ->whereNull('phan_quyen_nguoi_dung.thu_hoi_luc')
            ->count());
        $auditRowsAfterFailure = DB::table('nhat_ky_he_thong')
            ->where('nguoi_thuc_hien_id', $admin['user']->getKey())
            ->orderBy('id')
            ->get();
        $this->assertCount($auditBaselineCount, $auditRowsAfterFailure);
        $this->assertSame($auditBaselineIds, $auditRowsAfterFailure->pluck('id')->all());
        $this->assertSame($idempotencyBaseline, $chupIdempotency((int) $admin['user']->getKey()));
        $this->assertSame(0, DB::table('yeu_cau_chong_lap')
            ->where('nguoi_dung_id', $admin['user']->getKey())
            ->where('pham_vi', 'ADMIN_TRAINER_EXISTING_ACCOUNT')
            ->where('khoa_yeu_cau', $key)
            ->count());
        Queue::assertNotPushed(ProcessPasswordResetRequest::class);

        $result = app(TrainerOnboardingService::class)->onboardTaiKhoanDaCo(
            $admin['user'],
            (int) $account['user']->getKey(),
            $body,
        );
        $this->assertFalse($result['replayed']);
        $this->assertSame('REGRANTED', $result['role']['transition']);
        $this->assertSame('NOT_REQUESTED', $result['invitation']);
        $profileAfterRetry = DB::table('ho_so_huan_luyen_vien')->where('id', $profileId)->sole();
        $profileAfterRetrySnapshot = $chupHoSo($profileAfterRetry);
        $this->assertSame($profileBeforeSnapshot['id'], $profileAfterRetrySnapshot['id']);
        $this->assertSame($profileBeforeSnapshot['ngay_tao'], $profileAfterRetrySnapshot['ngay_tao']);
        $this->assertSame('Regrant after', $profileAfterRetrySnapshot['gioi_thieu']);
        $this->assertNull($profileAfterRetrySnapshot['chuyen_mon']);
        $this->assertSame('HOAT_DONG', $profileAfterRetrySnapshot['trang_thai']);
        $ptRoleAfterRetry = DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $account['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'PT')
            ->select('phan_quyen_nguoi_dung.*')
            ->sole();
        $ptRoleAfterRetrySnapshot = $chupPhanQuyen($ptRoleAfterRetry);
        $this->assertSame($ptRoleBeforeSnapshot['id'], $ptRoleAfterRetrySnapshot['id']);
        $this->assertSame($ptRoleBeforeSnapshot['ngay_tao'], $ptRoleAfterRetrySnapshot['ngay_tao']);
        $this->assertSame($admin['user']->getKey(), $ptRoleAfterRetrySnapshot['nguoi_cap_id']);
        $this->assertNotNull($ptRoleAfterRetrySnapshot['cap_luc']);
        $this->assertNull($ptRoleAfterRetrySnapshot['thu_hoi_luc']);
        $auditRowsAfterRetry = DB::table('nhat_ky_he_thong')
            ->where('nguoi_thuc_hien_id', $admin['user']->getKey())
            ->orderBy('id')
            ->get();
        $this->assertCount($auditBaselineCount + 2, $auditRowsAfterRetry);
        $newAudits = $auditRowsAfterRetry->slice($auditBaselineCount)->values();
        $this->assertSame(
            ['CAP_NHAT_HO_SO_HUAN_LUYEN_VIEN', 'CAP_LAI_VAI_TRO_PT'],
            $newAudits->pluck('hanh_dong')->all(),
        );
        $this->assertSame(
            ['HO_SO_HUAN_LUYEN_VIEN', 'PHAN_QUYEN_NGUOI_DUNG'],
            $newAudits->pluck('loai_doi_tuong')->all(),
        );
        $this->assertSame($profileId, (int) $newAudits[0]->dinh_danh_doi_tuong);
        $this->assertSame($ptRoleId, (int) $newAudits[1]->dinh_danh_doi_tuong);
        $logicalActionTimestampAfterRetry = $profileAfterRetrySnapshot['ngay_cap_nhat'];
        $this->assertSame($logicalActionTimestampAfterRetry, $ptRoleAfterRetrySnapshot['cap_luc']);
        foreach ($newAudits as $audit) {
            $this->assertSame($admin['user']->getKey(), (int) $audit->nguoi_thuc_hien_id);
            $this->assertSame('NGUOI_DUNG', $audit->loai_tac_nhan);
            $this->assertSame('THANH_CONG', $audit->ket_qua);
            $this->assertNotNull($audit->thuc_hien_luc);
            $this->assertNotNull($audit->ngay_tao);
            $this->assertSame($logicalActionTimestampAfterRetry, (string) $audit->thuc_hien_luc);
            $this->assertSame($logicalActionTimestampAfterRetry, (string) $audit->ngay_tao);
        }
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            (string) $newAudits[0]->khoa_tuong_quan,
        );
        $this->assertSame(
            [$newAudits[0]->khoa_tuong_quan],
            $newAudits->pluck('khoa_tuong_quan')->unique()->values()->all(),
        );
        $this->assertSame([
            'id' => $profileBeforeSnapshot['id'],
            'account_id' => $account['user']->getKey(),
            'trainer_code' => $profileBeforeSnapshot['ma_huan_luyen_vien'],
            'introduction' => $profileBeforeSnapshot['gioi_thieu'],
            'specialties' => $profileBeforeSnapshot['chuyen_mon'],
            'status' => $profileBeforeSnapshot['trang_thai'],
        ], json_decode((string) $newAudits[0]->du_lieu_truoc, true, 512, JSON_THROW_ON_ERROR));
        $this->assertSame([
            'id' => $profileAfterRetrySnapshot['id'],
            'account_id' => $account['user']->getKey(),
            'trainer_code' => $profileAfterRetrySnapshot['ma_huan_luyen_vien'],
            'introduction' => $profileAfterRetrySnapshot['gioi_thieu'],
            'specialties' => $profileAfterRetrySnapshot['chuyen_mon'],
            'status' => $profileAfterRetrySnapshot['trang_thai'],
        ], json_decode((string) $newAudits[0]->du_lieu_sau, true, 512, JSON_THROW_ON_ERROR));
        $this->assertSame([
            'assignment_id' => $ptRoleBeforeSnapshot['id'],
            'account_id' => $account['user']->getKey(),
            'role' => 'PT',
            'granted_by_id' => $ptRoleBeforeSnapshot['nguoi_cap_id'],
            'granted_at' => $ptRoleBeforeSnapshot['cap_luc'],
            'revoked_at' => $ptRoleBeforeSnapshot['thu_hoi_luc'],
            'active' => false,
        ], json_decode((string) $newAudits[1]->du_lieu_truoc, true, 512, JSON_THROW_ON_ERROR));
        $this->assertSame([
            'assignment_id' => $ptRoleAfterRetrySnapshot['id'],
            'account_id' => $account['user']->getKey(),
            'role' => 'PT',
            'granted_by_id' => $ptRoleAfterRetrySnapshot['nguoi_cap_id'],
            'granted_at' => $ptRoleAfterRetrySnapshot['cap_luc'],
            'revoked_at' => null,
            'active' => true,
        ], json_decode((string) $newAudits[1]->du_lieu_sau, true, 512, JSON_THROW_ON_ERROR));

        $idempotencyAfterRetry = $chupIdempotency((int) $admin['user']->getKey());
        $this->assertCount(count($idempotencyBaseline) + 1, $idempotencyAfterRetry);
        $regrantIdempotencyRows = array_values(array_filter(
            $idempotencyAfterRetry,
            static fn (array $row): bool => $row['khoa_yeu_cau'] === $key,
        ));
        $this->assertCount(1, $regrantIdempotencyRows);
        $regrantIdempotency = $regrantIdempotencyRows[0];
        $this->assertSame('DA_HOAN_TAT', $regrantIdempotency['trang_thai']);
        $this->assertSame(200, $regrantIdempotency['ma_phan_hoi']);
        $this->assertSame([
            'account' => [
                'id' => $account['user']->getKey(),
                'email' => (string) $account['user']->thu_dien_tu,
                'status' => 'HOAT_DONG',
            ],
            'trainer_profile' => [
                'id' => $profileAfterRetrySnapshot['id'],
                'trainer_code' => $profileAfterRetrySnapshot['ma_huan_luyen_vien'],
                'status' => $profileAfterRetrySnapshot['trang_thai'],
            ],
            'role' => [
                'code' => 'PT',
                'active' => true,
                'transition' => 'REGRANTED',
            ],
            'invitation' => 'NOT_REQUESTED',
        ], $regrantIdempotency['ket_qua_da_loc']);

        $profileSnapshotBeforeReplay = $profileAfterRetrySnapshot;
        $ptRoleSnapshotBeforeReplay = $ptRoleAfterRetrySnapshot;
        $idempotencySnapshotBeforeReplay = $regrantIdempotency;
        $auditIdsAfterRetry = $auditRowsAfterRetry->pluck('id')->all();
        $ptRoleIdsAfterRetry = DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $account['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'PT')
            ->orderBy('phan_quyen_nguoi_dung.id')
            ->pluck('phan_quyen_nguoi_dung.id')
            ->all();
        $replay = app(TrainerOnboardingService::class)->onboardTaiKhoanDaCo(
            $admin['user'],
            (int) $account['user']->getKey(),
            $body,
        );
        $this->assertTrue($replay['replayed']);
        $this->assertSame($result['account'], $replay['account']);
        $this->assertSame($result['trainer_profile'], $replay['trainer_profile']);
        $this->assertSame($result['role'], $replay['role']);
        $profileAfterReplay = DB::table('ho_so_huan_luyen_vien')->where('id', $profileId)->sole();
        $this->assertSame($profileSnapshotBeforeReplay, $chupHoSo($profileAfterReplay));
        $ptRoleAfterReplay = DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $account['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'PT')
            ->select('phan_quyen_nguoi_dung.*')
            ->get();
        $this->assertCount(1, $ptRoleAfterReplay);
        $this->assertSame($ptRoleSnapshotBeforeReplay, $chupPhanQuyen($ptRoleAfterReplay[0]));
        $this->assertSame(1, DB::table('ho_so_huan_luyen_vien')
            ->where('nguoi_dung_id', $account['user']->getKey())
            ->count());
        $this->assertSame($ptRoleIdsAfterRetry, DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $account['user']->getKey())
            ->where('vai_tro.ma_vai_tro', 'PT')
            ->orderBy('phan_quyen_nguoi_dung.id')
            ->pluck('phan_quyen_nguoi_dung.id')
            ->all());
        $auditRowsAfterReplay = DB::table('nhat_ky_he_thong')
            ->where('nguoi_thuc_hien_id', $admin['user']->getKey())
            ->orderBy('id')
            ->get();
        $this->assertCount($auditBaselineCount + 2, $auditRowsAfterReplay);
        $this->assertSame($auditIdsAfterRetry, $auditRowsAfterReplay->pluck('id')->all());
        $idempotencyAfterReplay = $chupIdempotency((int) $admin['user']->getKey());
        $regrantIdempotencyAfterReplay = array_values(array_filter(
            $idempotencyAfterReplay,
            static fn (array $row): bool => $row['khoa_yeu_cau'] === $key,
        ));
        $this->assertCount(1, $regrantIdempotencyAfterReplay);
        $this->assertSame($idempotencySnapshotBeforeReplay, $regrantIdempotencyAfterReplay[0]);
        Queue::assertNotPushed(ProcessPasswordResetRequest::class);
    }

    public function test_new_account_queue_failure_commits_domain_and_same_key_recovers_once(): void
    {
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $token = (string) $this->dangNhapApi($admin['user']->thu_dien_tu)->json('data.access_token');
        $payload = [
            'name' => 'Recoverable Trainer',
            'email' => 'recoverable.trainer@example.com',
            'phone' => null,
            'introduction' => 'Chờ xếp lời mời.',
            'specialties' => 'Mobility',
            'status' => 'HOAT_DONG',
        ];
        $key = 'c8cb2c71-53a4-4e88-8d35-9e40f294dc85';
        $soLanThu = 0;
        $sensitiveSentinels = [
            'password=Trainer!Password123',
            'mat_khau_bam=hash-secret',
            'raw_token=raw-reset-token',
            'Bearer token=bearer-secret',
            'exception body=exception-body',
            'QUEUE_CONNECTION=database',
        ];
        $queueFailureMessage = implode(' | ', [...$sensitiveSentinels, 'email='.$payload['email']]);
        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->twice()->andReturnUsing(function ($job) use (&$soLanThu, $queueFailureMessage) {
            $soLanThu++;
            if ($soLanThu === 1) {
                throw new RuntimeException($queueFailureMessage);
            }

            return $job;
        });
        $this->app->instance(Dispatcher::class, $dispatcher);
        $warnings = [];
        Log::listen(function (MessageLogged $event) use (&$warnings): void {
            if ($event->level === 'warning') {
                $warnings[] = [$event->message, $event->context];
            }
        });
        $headers = [...$this->bearer($token), 'Idempotency-Key' => $key];

        $this->postJson('/api/admin/trainers', $payload, $headers)
            ->assertStatus(503)
            ->assertJsonPath('code', 'PASSWORD_RESET_QUEUE_UNAVAILABLE');
        $this->assertCount(1, $warnings);
        $this->assertSame([
            'Không thể xếp yêu cầu đặt lại mật khẩu.',
            ['loai_loi' => RuntimeException::class],
        ], $warnings[0]);
        $serializedWarning = serialize($warnings[0]);
        foreach ([...$sensitiveSentinels, $payload['email'], $queueFailureMessage] as $sensitiveValue) {
            $this->assertStringNotContainsString($sensitiveValue, $serializedWarning);
        }

        $accountId = (int) DB::table('nguoi_dung')->where('thu_dien_tu', 'recoverable.trainer@example.com')->value('id');
        $this->assertGreaterThan(0, $accountId);
        $profileId = (int) DB::table('ho_so_huan_luyen_vien')->where('nguoi_dung_id', $accountId)->value('id');
        $roleId = (int) DB::table('phan_quyen_nguoi_dung')
            ->join('vai_tro', 'vai_tro.id', '=', 'phan_quyen_nguoi_dung.vai_tro_id')
            ->where('phan_quyen_nguoi_dung.nguoi_dung_id', $accountId)
            ->where('vai_tro.ma_vai_tro', 'PT')
            ->value('phan_quyen_nguoi_dung.id');
        $this->assertGreaterThan(0, $profileId);
        $this->assertGreaterThan(0, $roleId);
        $auditIds = DB::table('nhat_ky_he_thong')
            ->where('nguoi_thuc_hien_id', $admin['user']->getKey())
            ->orderBy('id')
            ->pluck('id')
            ->all();
        $idempotency = DB::table('yeu_cau_chong_lap')
            ->where('nguoi_dung_id', $admin['user']->getKey())
            ->where('pham_vi', 'ADMIN_TRAINER_ONBOARDING')
            ->where('khoa_yeu_cau', $key)
            ->first();
        $this->assertNotNull($idempotency);
        $ketQuaDaLoc = json_decode((string) $idempotency->ket_qua_da_loc, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('DA_HOAN_TAT', $idempotency->trang_thai);
        $this->assertSame('NOT_QUEUED', $ketQuaDaLoc['invitation']);
        $this->assertArrayNotHasKey('replayed', $ketQuaDaLoc);

        $this->postJson('/api/admin/trainers', $payload, $headers)
            ->assertOk()
            ->assertJsonPath('data.invitation', 'QUEUED')
            ->assertJsonPath('data.replayed', true)
            ->assertJsonPath('data.account.id', $accountId);

        $this->assertSame(2, $soLanThu);
        $this->assertSame($accountId, (int) DB::table('nguoi_dung')->where('thu_dien_tu', 'recoverable.trainer@example.com')->value('id'));
        $this->assertSame(1, DB::table('nguoi_dung')->where('id', $accountId)->count());
        $this->assertSame(1, DB::table('ho_so_huan_luyen_vien')->where('id', $profileId)->count());
        $this->assertSame(1, DB::table('phan_quyen_nguoi_dung')->where('id', $roleId)->count());
        $this->assertSame($auditIds, DB::table('nhat_ky_he_thong')
            ->where('nguoi_thuc_hien_id', $admin['user']->getKey())
            ->orderBy('id')
            ->pluck('id')
            ->all());
        $this->assertSame('QUEUED', json_decode(
            (string) DB::table('yeu_cau_chong_lap')->where('id', $idempotency->id)->value('ket_qua_da_loc'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        )['invitation']);

        $this->postJson('/api/admin/trainers', [...$payload, 'specialties' => 'Khác'], $headers)
            ->assertConflict()
            ->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT');
        $this->assertSame(2, $soLanThu);
    }

    public function test_new_account_success_keeps_response_and_initial_job_payload_allowlisted(): void
    {
        Queue::fake();
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $token = (string) $this->dangNhapApi($admin['user']->thu_dien_tu)->json('data.access_token');
        $payload = [
            'name' => 'Safe Payload Trainer',
            'email' => 'safe.payload.trainer@example.com',
            'phone' => '0900000000',
            'introduction' => null,
            'specialties' => null,
        ];
        $key = 'f4a6f73a-5f5e-4e06-88e6-8e9e3caa56b7';

        $response = $this->postJson('/api/admin/trainers', $payload, [
            ...$this->bearer($token),
            'Idempotency-Key' => $key,
        ])->assertCreated()
            ->assertJsonPath('data.invitation', 'QUEUED')
            ->assertJsonPath('data.replayed', false);

        $this->assertSame(
            ['account', 'trainer_profile', 'role', 'invitation', 'replayed'],
            array_keys($response->json('data')),
        );
        $this->assertStringNotContainsString('mat_khau_bam', (string) $response->getContent());
        $this->assertStringNotContainsString('raw_token', (string) $response->getContent());
        $this->assertStringNotContainsString('QUEUE_CONNECTION', (string) $response->getContent());
        Queue::assertPushed(ProcessPasswordResetRequest::class, function (ProcessPasswordResetRequest $job): bool {
            $serialized = serialize($job);

            return $job->email === 'safe.payload.trainer@example.com'
                && $job->correlationId !== ''
                && ! str_contains($serialized, 'raw_token')
                && ! str_contains($serialized, 'mat_khau_bam');
        });
        $ketQuaDaLoc = json_decode(
            (string) DB::table('yeu_cau_chong_lap')
                ->where('nguoi_dung_id', $admin['user']->getKey())
                ->where('pham_vi', 'ADMIN_TRAINER_ONBOARDING')
                ->where('khoa_yeu_cau', $key)
                ->value('ket_qua_da_loc'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $this->assertSame(['account', 'trainer_profile', 'role', 'invitation'], array_keys($ketQuaDaLoc));
        $this->assertSame('QUEUED', $ketQuaDaLoc['invitation']);

        $this->postJson('/api/admin/trainers', [
            'name' => 'Explicit Null Status Trainer',
            'email' => 'explicit-null-status.trainer@example.com',
            'status' => null,
        ], [
            ...$this->bearer($token),
            'Idempotency-Key' => 'f5f25fb5-b651-44b2-a7f5-720a2ec1b7b9',
        ])->assertStatus(422);
        $this->assertSame(0, DB::table('nguoi_dung')
            ->where('thu_dien_tu', 'explicit-null-status.trainer@example.com')
            ->count());
    }

    private function passwordResetNotificationFor(string $email): PasswordResetNotification
    {
        $found = null;
        Notification::assertSentOnDemand(
            PasswordResetNotification::class,
            function (PasswordResetNotification $notification, array $channels, AnonymousNotifiable $notifiable) use ($email, &$found): bool {
                if ($channels !== ['mail'] || $notifiable->routeNotificationFor('mail') !== $email) {
                    return false;
                }
                $found = $notification;

                return true;
            },
        );
        $this->assertInstanceOf(PasswordResetNotification::class, $found);

        return $found;
    }
}
