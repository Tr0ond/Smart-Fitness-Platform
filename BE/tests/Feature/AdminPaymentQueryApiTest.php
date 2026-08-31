<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\GoiTap;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesMembershipFixtures;
use Tests\Concerns\CreatesPaymentFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\TestCase;

class AdminPaymentQueryApiTest extends TestCase
{
    use CreatesAuthenticationFixtures;
    use CreatesMembershipFixtures;
    use CreatesPaymentFixtures;
    use CreatesProfileFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batDauGiaoDichAuthCoLap();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-31 08:00:00.123456', 'UTC'));
        $this->cauHinhPaymentTest();
    }

    protected function tearDown(): void
    {
        $this->ketThucGiaoDichAuthCoLap();
        parent::tearDown();
    }

    public function test_admin_can_filter_reconciliation_payments_and_safe_events_without_mutation(): void
    {
        $fixture = $this->taoFixture();
        $amountMismatch = $this->taoDonPaymentQuaApi($fixture['member'], $fixture['package']->getKey());
        $this->dungGatewayPayOSXacMinhChuKy();
        $this->postJson('/api/webhooks/payos', $this->webhookPayment($amountMismatch['payment'], [
            'amount' => ((int) $amountMismatch['payment']->so_tien_yeu_cau) - 1000,
            'reference' => 'REF-ADMIN-AMOUNT',
        ]))->assertOk()->assertJsonPath('data.result', 'CAN_DOI_SOAT');

        // Webhook verification swaps in the real signature verifier; restore the
        // fake create-link gateway before creating the next independent order.
        $this->cauHinhPaymentTest();
        CarbonImmutable::setTestNow(CarbonImmutable::now('UTC')->addMinutes(31));
        $expired = $this->taoDonPaymentQuaApi($fixture['member'], $fixture['package']->getKey());
        CarbonImmutable::setTestNow(CarbonImmutable::now('UTC')->addMinutes(31));
        $this->dungGatewayPayOSXacMinhChuKy();
        $this->postJson('/api/webhooks/payos', $this->webhookPayment($expired['payment'], [
            'reference' => 'REF-ADMIN-EXPIRED',
        ]))->assertOk()->assertJsonPath('data.result', 'CAN_DOI_SOAT');

        $headers = $this->bearer($fixture['admin_token']);
        $snapshot = $this->snapshotPaymentTables();
        $response = $this->getJson('/api/admin/payments?reconciliation_required=1&payment_status=CAN_DOI_SOAT&per_page=1', $headers)
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 2)
            ->assertJsonPath('data.pagination.per_page', 1)
            ->assertJsonPath('data.items.0.status', 'CAN_DOI_SOAT')
            ->assertJsonPath('data.items.0.member.email', $fixture['member']['user']->thu_dien_tu);
        $this->assertSame($snapshot, $this->snapshotPaymentTables());
        foreach (['du_lieu_da_loc', 'ma_bam_noi_dung', 'khoa_chong_lap', 'chu_ky_hop_le', 'signature', 'checkout_url', 'TEST-REDACTED'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, strtolower($response->getContent()));
        }

        $this->getJson('/api/admin/payments?order_code='.$amountMismatch['order']->ma_don, $headers)
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.reconciliation_reason', 'AMOUNT_MISMATCH')
            ->assertJsonPath('data.items.0.order.code', $amountMismatch['order']->ma_don);
        $this->getJson('/api/admin/payments?member='.$fixture['member']['user']->thu_dien_tu, $headers)
            ->assertOk()->assertJsonPath('data.pagination.total', 2);
        $this->getJson('/api/admin/payments?provider_order_code='.$amountMismatch['payment']->ma_don_cong_thanh_toan, $headers)
            ->assertOk()->assertJsonPath('data.pagination.total', 1);
        $this->getJson('/api/admin/payments?from=2026-08-31&to=2026-08-31', $headers)
            ->assertOk()->assertJsonPath('data.pagination.total', 2);

        $detail = $this->getJson('/api/admin/payments/'.$amountMismatch['payment']->getKey(), $headers)
            ->assertOk()
            ->assertJsonPath('data.payment_id', $amountMismatch['payment']->getKey())
            ->assertJsonPath('data.events.0.reconciliation_reason', 'AMOUNT_MISMATCH');
        $this->assertStringNotContainsString('du_lieu_da_loc', $detail->getContent());
        $this->assertStringNotContainsString('signature', strtolower($detail->getContent()));

        $events = $this->getJson('/api/admin/payment-events?reconciliation_required=true&provider_reference=REF-ADMIN-AMOUNT&sort_by=received_at&sort_direction=asc', $headers)
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.processing_status', 'CAN_DOI_SOAT')
            ->assertJsonPath('data.items.0.reconciliation_reason', 'AMOUNT_MISMATCH');
        $this->assertStringNotContainsString('du_lieu_da_loc', $events->getContent());
        $this->assertStringNotContainsString('ma_bam_noi_dung', $events->getContent());

        $this->assertDatabaseHas('don_mua_goi', ['id' => $expired['order']->getKey(), 'trang_thai' => 'CAN_DOI_SOAT']);
        $this->assertDatabaseHas('lan_thanh_toan', ['id' => $expired['payment']->getKey(), 'ma_loi' => 'ORDER_EXPIRED']);
    }

    public function test_payment_queries_are_admin_only_and_do_not_cross_branch(): void
    {
        $fixture = $this->taoFixture();
        $visible = $this->taoDonPaymentQuaApi($fixture['member'], $fixture['package']->getKey());
        $otherPackage = $this->taoGoiTapMembership($fixture['foreign']);
        $hidden = $this->taoDonPaymentQuaApi($fixture['foreign'], $otherPackage['package']->getKey());
        $adminHeaders = $this->bearer($fixture['admin_token']);

        $this->getJson('/api/admin/payments')->assertUnauthorized();
        foreach ([$fixture['member_token'], $fixture['pt_token']] as $token) {
            $this->getJson('/api/admin/payments', $this->bearer($token))->assertForbidden();
            $this->getJson('/api/admin/payment-events', $this->bearer($token))->assertForbidden();
        }
        $this->getJson('/api/admin/payments/'.$hidden['payment']->getKey(), $adminHeaders)
            ->assertNotFound()->assertJsonPath('code', 'PAYMENT_NOT_FOUND');
        $this->getJson('/api/admin/payments', $adminHeaders)
            ->assertOk()->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.payment_id', $visible['payment']->getKey());
        $this->getJson('/api/admin/payments?payment_status=INVALID', $adminHeaders)
            ->assertUnprocessable()->assertJsonValidationErrors('payment_status');
    }

    /** @return array{admin:array<string,mixed>,member:array<string,mixed>,foreign:array<string,mixed>,admin_token:string,member_token:string,pt_token:string,package:GoiTap} */
    private function taoFixture(): array
    {
        $admin = $this->taoNguoiDungAuth(['ADMIN']);
        $member = $this->taoNguoiDungAuth(['MEMBER']);
        $pt = $this->taoNguoiDungAuth(['PT']);
        $foreign = $this->taoNguoiDungAuth(['MEMBER']);
        foreach ([$member, $pt] as $actor) {
            DB::table('nguoi_dung')->where('id', $actor['user']->getKey())->update(['chi_nhanh_id' => $admin['branch_id']]);
            $actor['user']->refresh();
        }
        $this->taoHoSoHoiVien($member);
        $this->taoHoSoHoiVien($foreign);
        $package = $this->taoGoiTapMembership($admin)['package'];
        $now = CarbonImmutable::now('UTC');

        return [
            'admin' => $admin,
            'member' => $member,
            'foreign' => $foreign,
            'admin_token' => $this->taoTheTruyCapThuCong($admin['user'], $now, $now->addDay()),
            'member_token' => $this->taoTheTruyCapThuCong($member['user'], $now, $now->addDay()),
            'pt_token' => $this->taoTheTruyCapThuCong($pt['user'], $now, $now->addDay()),
            'package' => $package,
        ];
    }

    /** @return array<string,string> */
    private function snapshotPaymentTables(): array
    {
        return collect(['don_mua_goi', 'lan_thanh_toan', 'su_kien_thanh_toan', 'ky_han_hoi_vien', 'dang_ky_goi_tap'])
            ->mapWithKeys(fn (string $table): array => [$table => hash('sha256', DB::table($table)->orderBy('id')->get()->toJson())])
            ->all();
    }
}
