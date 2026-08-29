<?php

namespace Tests\Feature;

use App\Models\SuKienThanhToan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesMembershipFixtures;
use Tests\Concerns\CreatesPaymentFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\TestCase;

class PayOSWebhookTest extends TestCase
{
    use CreatesAuthenticationFixtures;
    use CreatesMembershipFixtures;
    use CreatesPaymentFixtures;
    use CreatesProfileFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batDauGiaoDichAuthCoLap();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-29 12:00:00.123456', 'UTC'));
        $this->cauHinhPaymentTest();
    }

    protected function tearDown(): void
    {
        $this->ketThucGiaoDichAuthCoLap();
        parent::tearDown();
    }

    public function test_valid_webhook_provisions_once_without_activating_membership(): void
    {
        [$fixture, $created] = $this->taoDonMoi();
        $payload = $this->webhookPayment($created['payment']);
        $this->dungGatewayPayOSXacMinhChuKy();

        $this->postJson('/api/webhooks/payos', $payload)
            ->assertOk()
            ->assertJsonPath('data.result', 'DA_XAC_NHAN');

        $this->assertDatabaseHas('don_mua_goi', ['id' => $created['order']->getKey(), 'trang_thai' => 'DA_THANH_TOAN']);
        $this->assertDatabaseHas('lan_thanh_toan', [
            'id' => $created['payment']->getKey(),
            'trang_thai' => 'THANH_CONG',
            'ma_tham_chieu_duoc_chap_nhan' => $payload['data']['reference'],
        ]);
        $ky = DB::table('ky_han_hoi_vien')->where('don_mua_goi_id', $created['order']->getKey())->sole();
        $this->assertSame('CHO_KICH_HOAT', $ky->trang_thai);
        $this->assertNull($ky->ngay_bat_dau);
        $this->assertNull($ky->ngay_ket_thuc);
        $this->assertNotNull($ky->dang_ky_goi_tap_id);
        $this->assertSame(1, DB::table('dang_ky_goi_tap')->where('hoi_vien_id', $ky->hoi_vien_id)->count());
        $this->assertSame(0, DB::table('su_dung_quyen_loi')->where('hoi_vien_id', $ky->hoi_vien_id)->count());
        $event = SuKienThanhToan::query()
            ->where('lan_thanh_toan_id', $created['payment']->getKey())
            ->sole();
        $this->assertTrue($event->chu_ky_hop_le);
        $this->assertSame('DA_XU_LY', $event->trang_thai_xu_ly);
        $this->assertArrayNotHasKey('accountNumber', $event->du_lieu_da_loc);
        $this->assertArrayNotHasKey('counterAccountNumber', $event->du_lieu_da_loc);

        $token = $this->layTokenProfile($fixture);
        $read = $this->getJson('/api/orders/'.$created['order']->getKey(), $this->bearer($token))->assertOk();
        $this->assertStringNotContainsString('du_lieu_da_loc', $read->getContent());
        $this->assertStringNotContainsString('checksum', strtolower($read->getContent()));
        $this->assertStringNotContainsString('accountNumber', $read->getContent());
    }

    public function test_invalid_or_tampered_signature_never_mutates_payment_or_membership(): void
    {
        [, $created] = $this->taoDonMoi();
        $payload = $this->webhookPayment($created['payment']);
        $payload['data']['amount']--;
        $this->dungGatewayPayOSXacMinhChuKy();

        $this->postJson('/api/webhooks/payos', $payload)->assertStatus(400);
        $this->assertDatabaseHas('lan_thanh_toan', ['id' => $created['payment']->getKey(), 'trang_thai' => 'CHO_THANH_TOAN']);
        $this->assertDatabaseHas('don_mua_goi', ['id' => $created['order']->getKey(), 'trang_thai' => 'CHO_THANH_TOAN']);
        $this->assertDatabaseHas('su_kien_thanh_toan', ['chu_ky_hop_le' => false, 'trang_thai_xu_ly' => 'BI_TU_CHOI']);
        $this->assertDatabaseCount('dang_ky_goi_tap', 0);

        $payload = $this->webhookPayment($created['payment']);
        $payload['data']['orderCode']++;
        $this->postJson('/api/webhooks/payos', $payload)->assertStatus(400);
        $this->assertDatabaseCount('dang_ky_goi_tap', 0);
    }

    public function test_exact_webhook_repeated_ten_times_has_one_effect(): void
    {
        [, $created] = $this->taoDonMoi();
        $payload = $this->webhookPayment($created['payment']);
        $this->dungGatewayPayOSXacMinhChuKy();

        for ($i = 1; $i <= 10; $i++) {
            $this->postJson('/api/webhooks/payos', $payload)->assertOk();
        }

        $this->assertSame(1, SuKienThanhToan::query()->where('lan_thanh_toan_id', $created['payment']->getKey())->count());
        $this->assertSame(10, (int) SuKienThanhToan::query()->where('lan_thanh_toan_id', $created['payment']->getKey())->sole()->so_lan_nhan);
        $this->assertSame(1, DB::table('ky_han_hoi_vien')->where('don_mua_goi_id', $created['order']->getKey())->count());
        $this->assertDatabaseCount('dang_ky_goi_tap', 1);
        $this->assertDatabaseCount('su_dung_quyen_loi', 0);
    }

    public function test_signed_amount_mismatch_goes_to_reconciliation_without_membership(): void
    {
        [, $created] = $this->taoDonMoi(['gia' => 500000]);
        $payload = $this->webhookPayment($created['payment'], ['amount' => 499000]);
        $this->dungGatewayPayOSXacMinhChuKy();

        $this->postJson('/api/webhooks/payos', $payload)->assertOk()->assertJsonPath('data.result', 'CAN_DOI_SOAT');
        $this->assertDatabaseHas('don_mua_goi', ['id' => $created['order']->getKey(), 'trang_thai' => 'CAN_DOI_SOAT']);
        $this->assertDatabaseHas('lan_thanh_toan', ['id' => $created['payment']->getKey(), 'trang_thai' => 'CAN_DOI_SOAT', 'ma_loi' => 'AMOUNT_MISMATCH']);
        $this->assertDatabaseHas('su_kien_thanh_toan', ['trang_thai_xu_ly' => 'CAN_DOI_SOAT', 'ly_do' => 'AMOUNT_MISMATCH']);
        $this->assertDatabaseCount('dang_ky_goi_tap', 0);
    }

    public function test_currency_link_and_unknown_order_anomalies_are_safe(): void
    {
        foreach ([
            [['currency' => 'USD'], 'CURRENCY_MISMATCH'],
            [['paymentLinkId' => 'another-link'], 'PAYMENT_LINK_MISMATCH'],
        ] as [$override, $reason]) {
            $this->cauHinhPaymentTest();
            [, $created] = $this->taoDonMoi();
            $this->dungGatewayPayOSXacMinhChuKy();
            $this->postJson('/api/webhooks/payos', $this->webhookPayment($created['payment'], $override))
                ->assertOk()
                ->assertJsonPath('data.result', 'CAN_DOI_SOAT');
            $this->assertDatabaseHas('lan_thanh_toan', ['id' => $created['payment']->getKey(), 'trang_thai' => 'CAN_DOI_SOAT', 'ma_loi' => $reason]);
        }

        $this->cauHinhPaymentTest();
        [, $created] = $this->taoDonMoi();
        $this->dungGatewayPayOSXacMinhChuKy();
        $payload = $this->webhookPayment($created['payment'], ['orderCode' => 9007199254740000]);
        $this->postJson('/api/webhooks/payos', $payload)
            ->assertOk()
            ->assertJsonPath('data.result', 'CAN_DOI_SOAT');
        $this->assertDatabaseHas('su_kien_thanh_toan', ['lan_thanh_toan_id' => null, 'ly_do' => 'UNKNOWN_PROVIDER_ORDER']);
        $this->assertDatabaseHas('lan_thanh_toan', ['id' => $created['payment']->getKey(), 'trang_thai' => 'CHO_THANH_TOAN']);
        $this->assertDatabaseCount('dang_ky_goi_tap', 0);
    }

    public function test_same_reference_is_idempotent_but_conflicting_success_requires_reconciliation(): void
    {
        [, $created] = $this->taoDonMoi();
        $first = $this->webhookPayment($created['payment'], ['reference' => 'REF-FIRST']);
        $this->dungGatewayPayOSXacMinhChuKy();
        $this->postJson('/api/webhooks/payos', $first)->assertOk();

        $sameReference = $this->webhookPayment($created['payment'], [
            'reference' => 'REF-FIRST',
            'description' => 'SFP changed harmless text',
        ]);
        $this->postJson('/api/webhooks/payos', $sameReference)
            ->assertOk()
            ->assertJsonPath('data.result', 'DA_XAC_NHAN_TRUOC');
        $this->assertDatabaseHas('lan_thanh_toan', ['id' => $created['payment']->getKey(), 'trang_thai' => 'THANH_CONG']);

        $conflict = $this->webhookPayment($created['payment'], ['reference' => 'REF-SECOND']);
        $this->postJson('/api/webhooks/payos', $conflict)
            ->assertOk()
            ->assertJsonPath('data.result', 'CAN_DOI_SOAT');
        $this->assertDatabaseHas('lan_thanh_toan', ['id' => $created['payment']->getKey(), 'trang_thai' => 'CAN_DOI_SOAT', 'ma_loi' => 'CONFLICTING_SUCCESS_REFERENCE']);
        $this->assertSame(1, DB::table('ky_han_hoi_vien')->where('don_mua_goi_id', $created['order']->getKey())->count());
        $this->assertDatabaseCount('dang_ky_goi_tap', 1);
    }

    public function test_stale_non_success_event_cannot_downgrade_confirmed_payment(): void
    {
        [, $created] = $this->taoDonMoi();
        $this->dungGatewayPayOSXacMinhChuKy();
        $this->postJson('/api/webhooks/payos', $this->webhookPayment($created['payment']))->assertOk();

        $stale = $this->webhookPayment($created['payment'], ['code' => '01', 'desc' => 'stale pending']);
        $this->postJson('/api/webhooks/payos', $stale)
            ->assertOk()
            ->assertJsonPath('data.result', 'KHONG_THANH_CONG');
        $this->assertDatabaseHas('don_mua_goi', ['id' => $created['order']->getKey(), 'trang_thai' => 'DA_THANH_TOAN']);
        $this->assertDatabaseHas('lan_thanh_toan', ['id' => $created['payment']->getKey(), 'trang_thai' => 'THANH_CONG']);
    }

    public function test_membership_sequence_follows_backend_confirmation_order_not_order_creation(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoSoHoiVien($fixture);
        $goiA = $this->taoGoiTapMembership($fixture, ['ten_goi' => 'ORDER A']);
        $goiB = $this->taoGoiTapMembership($fixture, ['ten_goi' => 'ORDER B']);
        $orderA = $this->taoDonPaymentQuaApi($fixture, $goiA['package']->getKey());
        $orderB = $this->taoDonPaymentQuaApi($fixture, $goiB['package']->getKey());
        $this->dungGatewayPayOSXacMinhChuKy();

        $this->postJson('/api/webhooks/payos', $this->webhookPayment($orderB['payment'], ['reference' => 'REF-B']))->assertOk();
        CarbonImmutable::setTestNow(CarbonImmutable::now('UTC')->addSecond());
        $this->postJson('/api/webhooks/payos', $this->webhookPayment($orderA['payment'], ['reference' => 'REF-A']))->assertOk();

        $termA = DB::table('ky_han_hoi_vien')->where('don_mua_goi_id', $orderA['order']->getKey())->sole();
        $termB = DB::table('ky_han_hoi_vien')->where('don_mua_goi_id', $orderB['order']->getKey())->sole();
        $this->assertSame(1, (int) $termB->so_thu_tu);
        $this->assertSame(2, (int) $termA->so_thu_tu);
        $this->assertSame($termA->dang_ky_goi_tap_id, $termB->dang_ky_goi_tap_id);
        $this->assertSame(1, DB::table('dang_ky_goi_tap')->where('hoi_vien_id', $memberId)->count());
    }

    /**
     * @param  array<string, mixed>  $packageOverrides
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function taoDonMoi(array $packageOverrides = []): array
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $this->taoHoSoHoiVien($fixture);
        $goi = $this->taoGoiTapMembership($fixture, $packageOverrides);

        return [$fixture, $this->taoDonPaymentQuaApi($fixture, $goi['package']->getKey())];
    }
}
