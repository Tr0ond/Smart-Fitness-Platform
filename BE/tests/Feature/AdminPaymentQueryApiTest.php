<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\GoiTap;
use App\Models\LanThanhToan;
use App\Models\SuKienThanhToan;
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

    public function test_admin_can_read_unlinked_events_with_safe_redaction_and_no_mutation(): void
    {
        $fixture = $this->taoFixture();
        $created = $this->taoDonPaymentQuaApi($fixture['member'], $fixture['package']->getKey());
        $this->dungGatewayPayOSXacMinhChuKy();

        $unknownProviderOrderCode = 9007199254740000;
        $this->postJson('/api/webhooks/payos', $this->webhookPayment($created['payment'], [
            'orderCode' => $unknownProviderOrderCode,
            'reference' => 'REF-ADMIN-UNKNOWN',
        ]))->assertOk()->assertJsonPath('data.result', 'CAN_DOI_SOAT');

        $invalidSignature = $this->webhookPayment($created['payment'], ['reference' => 'REF-ADMIN-INVALID']);
        $invalidSignature['signature'] = 'invalid-signature';
        $this->postJson('/api/webhooks/payos', $invalidSignature)->assertBadRequest();

        $headers = $this->bearer($fixture['admin_token']);
        $snapshot = $this->snapshotPaymentTables();
        $unknown = $this->getJson('/api/admin/payment-events?provider_order_code='.$unknownProviderOrderCode, $headers)
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.payment_id', null)
            ->assertJsonPath('data.items.0.provider_order_code', $unknownProviderOrderCode)
            ->assertJsonPath('data.items.0.processing_status', 'CAN_DOI_SOAT')
            ->assertJsonPath('data.items.0.reconciliation_reason', 'UNKNOWN_PROVIDER_ORDER');
        $this->assertSame([
            'event_id',
            'payment_id',
            'channel',
            'provider_order_code',
            'provider_payment_link_id',
            'provider_reference',
            'amount',
            'currency',
            'provider_result_code',
            'processing_status',
            'receipt_count',
            'received_at',
            'last_received_at',
            'processed_at',
            'reconciliation_reason',
            'created_at',
        ], array_keys($unknown->json('data.items.0')));
        foreach (['du_lieu_da_loc', 'ma_bam_noi_dung', 'khoa_chong_lap', 'chu_ky_hop_le', 'signature', 'checkout_url', 'TEST-REDACTED'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $unknown->getContent());
        }

        $invalid = $this->getJson('/api/admin/payment-events?processing_status=BI_TU_CHOI', $headers)
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.payment_id', null)
            ->assertJsonPath('data.items.0.processing_status', 'BI_TU_CHOI')
            ->assertJsonPath('data.items.0.reconciliation_reason', 'INVALID_SIGNATURE');
        foreach (['du_lieu_da_loc', 'ma_bam_noi_dung', 'khoa_chong_lap', 'chu_ky_hop_le', 'signature', 'checkout_url', 'TEST-REDACTED'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $invalid->getContent());
        }
        $this->assertSame($snapshot, $this->snapshotPaymentTables());
    }

    public function test_reconciliation_includes_successful_payment_with_related_abnormal_event(): void
    {
        $fixture = $this->taoFixture();
        $created = $this->taoDonPaymentQuaApi($fixture['member'], $fixture['package']->getKey());
        $this->dungGatewayPayOSXacMinhChuKy();
        $this->postJson('/api/webhooks/payos', $this->webhookPayment($created['payment'], [
            'reference' => 'REF-ADMIN-SUCCESS',
        ]))->assertOk()->assertJsonPath('data.result', 'DA_XAC_NHAN');
        $this->taoSuKienPayment($created['payment'], 'REF-ADMIN-POST-SUCCESS', 'CAN_DOI_SOAT', 'POST_SUCCESS_RECONCILIATION');

        $headers = $this->bearer($fixture['admin_token']);
        $snapshot = $this->snapshotPaymentTables();
        $response = $this->getJson('/api/admin/payments?reconciliation_required=1&payment_status=THANH_CONG&order_status=DA_THANH_TOAN&per_page=1', $headers)
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.status', 'THANH_CONG')
            ->assertJsonPath('data.items.0.order.status', 'DA_THANH_TOAN');
        $items = $response->json('data.items');
        $this->assertCount(1, $items);
        $this->assertTrue(collect($items[0]['events'])->contains(
            fn (array $event): bool => $event['processing_status'] === 'CAN_DOI_SOAT'
                && $event['reconciliation_reason'] === 'POST_SUCCESS_RECONCILIATION',
        ));
        $this->assertSame($snapshot, $this->snapshotPaymentTables());
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
        $this->taoSuKienPayment($hidden['payment'], 'REF-FOREIGN-EVENT');
        $this->getJson('/api/admin/payment-events?provider_reference=REF-FOREIGN-EVENT', $adminHeaders)
            ->assertOk()->assertJsonPath('data.pagination.total', 0);
        $this->getJson('/api/admin/payments', $adminHeaders)
            ->assertOk()->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.payment_id', $visible['payment']->getKey());
        $this->getJson('/api/admin/payments?payment_status=INVALID', $adminHeaders)
            ->assertUnprocessable()->assertJsonValidationErrors('payment_status');
        $this->getJson('/api/admin/payment-events?processing_status=INVALID', $adminHeaders)
            ->assertUnprocessable()->assertJsonValidationErrors('processing_status');
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

    private function taoSuKienPayment(
        LanThanhToan $payment,
        string $reference,
        string $processingStatus = 'DA_XU_LY',
        ?string $reason = null,
    ): SuKienThanhToan {
        $now = CarbonImmutable::now('UTC');
        $hash = hash('sha256', $reference);

        return SuKienThanhToan::query()->create([
            'lan_thanh_toan_id' => $payment->getKey(),
            'ma_kenh_thanh_toan' => 'PAYOS',
            'ma_don_cong_thanh_toan' => $payment->ma_don_cong_thanh_toan,
            'ma_lien_ket_thanh_toan' => $payment->ma_lien_ket_thanh_toan,
            'ma_tham_chieu' => $reference,
            'so_tien' => $payment->so_tien_yeu_cau,
            'don_vi_tien' => $payment->don_vi_tien,
            'ma_ket_qua' => '00',
            'chu_ky_hop_le' => true,
            'khoa_chong_lap' => $hash,
            'ma_bam_noi_dung' => $hash,
            'du_lieu_da_loc' => ['reference' => $reference],
            'trang_thai_xu_ly' => $processingStatus,
            'so_lan_nhan' => 1,
            'nhan_dau_luc' => $now,
            'nhan_cuoi_luc' => $now,
            'xu_ly_luc' => $now,
            'ly_do' => $reason,
            'ngay_tao' => $now,
            'ngay_cap_nhat' => $now,
        ]);
    }
}
