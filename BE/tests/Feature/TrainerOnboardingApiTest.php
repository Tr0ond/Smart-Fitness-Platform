<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\NhatKyHeThong;
use App\Notifications\PasswordResetNotification;
use App\Services\Admin\TrainerOnboardingService;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
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
        $notification = $this->passwordResetNotificationFor('trainer.onboarding@example.com');

        $this->withHeaders([...$this->bearer($token), 'Idempotency-Key' => $key])
            ->postJson('/api/admin/trainers', $payload)
            ->assertOk()
            ->assertJsonPath('data.replayed', true)
            ->assertJsonPath('data.account.id', $taiKhoanId);
        $this->assertSame(1, DB::table('nguoi_dung')->where('id', $taiKhoanId)->count());
        $this->assertSame(1, DB::table('ho_so_huan_luyen_vien')->where('id', $hoSoId)->count());
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
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $account = $this->taoNguoiDungAuth(['MEMBER']);
        $adminToken = (string) $this->dangNhapApi($admin['user']->thu_dien_tu)->json('data.access_token');
        $uri = '/api/admin/accounts/'.$account['user']->getKey().'/trainer-profile';

        $created = $this->postJson($uri, [
            'introduction' => 'Onboard account đã có.',
            'specialties' => 'Phục hồi',
            'status' => 'HOAT_DONG',
        ], $this->bearer($adminToken))->assertOk();
        $profileId = (int) $created->json('data.trainer_profile.id');
        $this->assertSame('GRANTED', $created->json('data.role.transition'));

        $this->deleteJson('/api/admin/accounts/'.$account['user']->getKey().'/roles/PT', [], $this->bearer($adminToken))
            ->assertOk();
        $regranted = $this->postJson($uri, [], $this->bearer($adminToken))->assertOk();
        $this->assertSame($profileId, (int) $regranted->json('data.trainer_profile.id'));
        $this->assertSame('REGRANTED', $regranted->json('data.role.transition'));
        $this->assertSame(1, DB::table('ho_so_huan_luyen_vien')->where('nguoi_dung_id', $account['user']->getKey())->count());
    }

    public function test_onboarding_rejects_inactive_account_and_rolls_back_everything_when_audit_fails(): void
    {
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $inactive = $this->taoNguoiDungAuth(['MEMBER'], trangThai: 'BI_KHOA');
        $token = (string) $this->dangNhapApi($admin['user']->thu_dien_tu)->json('data.access_token');
        $this->postJson(
            '/api/admin/accounts/'.$inactive['user']->getKey().'/trainer-profile',
            [],
            $this->bearer($token),
        )->assertConflict()->assertJsonPath('code', 'ACCOUNT_NOT_ACTIVE');

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
            $this->assertSame(0, DB::table('yeu_cau_chong_lap')
                ->where('pham_vi', 'ADMIN_TRAINER_ONBOARDING')
                ->where('khoa_yeu_cau', '2f3470af-45b3-498d-a5fe-408a63a3684a')
                ->count());
        }
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
