<?php

namespace Tests\Feature;

use App\Data\Payments\PaymentLinkResult;
use App\Exceptions\Payments\PaymentGatewayException;
use App\Models\KyHanHoiVien;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesAuthenticationFixtures;
use Tests\Concerns\CreatesMembershipFixtures;
use Tests\Concerns\CreatesPaymentFixtures;
use Tests\Concerns\CreatesProfileFixtures;
use Tests\TestCase;

class PaymentOrderApiTest extends TestCase
{
    use CreatesAuthenticationFixtures;
    use CreatesMembershipFixtures;
    use CreatesPaymentFixtures;
    use CreatesProfileFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batDauGiaoDichAuthCoLap();
        $this->cauHinhPaymentTest();
    }

    protected function tearDown(): void
    {
        $this->ketThucGiaoDichAuthCoLap();
        parent::tearDown();
    }

    public function test_order_routes_require_member_authentication(): void
    {
        $this->postJson('/api/packages/1/orders', [], ['Idempotency-Key' => $this->uuidPayment()])
            ->assertUnauthorized();
        $this->getJson('/api/orders')->assertUnauthorized();

        $pt = $this->taoNguoiDungAuth(['PT']);
        $token = $this->layTokenProfile($pt);
        $this->postJson('/api/packages/1/orders', [], array_merge($this->bearer($token), ['Idempotency-Key' => $this->uuidPayment()]))
            ->assertForbidden();
        $this->getJson('/api/orders', $this->bearer($token))->assertForbidden();
    }

    public function test_member_creates_server_priced_snapshot_and_idempotent_payment_link(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $memberId = $this->taoHoSoHoiVien($fixture);
        $goi = $this->taoGoiTapMembership($fixture, ['gia' => 500000, 'ten_goi' => 'PLUS 30']);
        $token = $this->layTokenProfile($fixture);
        $key = $this->uuidPayment();
        $headers = array_merge($this->bearer($token), ['Idempotency-Key' => $key]);

        $this->postJson('/api/packages/'.$goi['package']->getKey().'/orders', ['price' => 1000], $headers)
            ->assertUnprocessable();
        $this->postJson('/api/packages/'.$goi['package']->getKey().'/orders', ['member_id' => $memberId + 1], $headers)
            ->assertUnprocessable();
        $this->assertDatabaseCount('don_mua_goi', 0);

        $created = $this->postJson('/api/packages/'.$goi['package']->getKey().'/orders', [], $headers)
            ->assertCreated()
            ->assertJsonPath('data.amount', '500000')
            ->assertJsonPath('data.currency', 'VND')
            ->assertJsonPath('data.status', 'CHO_THANH_TOAN')
            ->assertJsonPath('data.snapshot.package_name', 'PLUS 30')
            ->assertJsonPath('data.snapshot.status', 'CHO_THANH_TOAN')
            ->assertJsonPath('data.snapshot.starts_at', null)
            ->assertJsonPath('data.payments.0.status', 'CHO_THANH_TOAN')
            ->assertJsonPath('data.payments.0.qr_code', fn (string $value): bool => str_starts_with($value, 'PAYOS-QR-'));
        $orderId = (int) $created->json('data.id');
        $paymentId = (int) $created->json('data.payments.0.id');

        $this->assertCount(1, $this->fakePaymentGateway->requests);
        $providerRequest = $this->fakePaymentGateway->requests[0];
        $this->assertSame(500000, $providerRequest['amount']);
        $this->assertSame(($orderId * 1000) + 1, $providerRequest['order_code']);
        $this->assertSame('https://frontend.test/payment/return', $providerRequest['return_url']);
        $this->assertSame('https://frontend.test/payment/cancel', $providerRequest['cancel_url']);
        $this->assertDatabaseHas('don_mua_goi', ['id' => $orderId, 'hoi_vien_id' => $memberId, 'so_tien_phai_thu' => 500000]);
        $this->assertDatabaseHas('lan_thanh_toan', ['id' => $paymentId, 'so_tien_yeu_cau' => 500000, 'trang_thai' => 'CHO_THANH_TOAN']);
        $this->assertDatabaseHas('ky_han_hoi_vien', ['don_mua_goi_id' => $orderId, 'gia_da_mua' => 500000, 'trang_thai' => 'CHO_THANH_TOAN']);
        $this->assertDatabaseCount('dang_ky_goi_tap', 0);

        $retried = $this->postJson('/api/packages/'.$goi['package']->getKey().'/orders', [], $headers)
            ->assertCreated()
            ->assertJsonPath('data.id', $orderId);
        $this->assertSame($paymentId, (int) $retried->json('data.payments.0.id'));
        $this->assertSame($created->json('data.payments.0.qr_code'), $retried->json('data.payments.0.qr_code'));
        $this->assertCount(1, $this->fakePaymentGateway->requests);
        $this->assertDatabaseCount('don_mua_goi', 1);
        $this->assertDatabaseCount('lan_thanh_toan', 1);
        $this->assertDatabaseCount('ky_han_hoi_vien', 1);
    }

    public function test_idempotency_key_conflict_is_rejected(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $this->taoHoSoHoiVien($fixture);
        $goiA = $this->taoGoiTapMembership($fixture);
        $goiB = $this->taoGoiTapMembership($fixture);
        $token = $this->layTokenProfile($fixture);
        $headers = array_merge($this->bearer($token), ['Idempotency-Key' => $this->uuidPayment()]);

        $this->postJson('/api/packages/'.$goiA['package']->getKey().'/orders', [], $headers)->assertCreated();
        $this->postJson('/api/packages/'.$goiB['package']->getKey().'/orders', [], $headers)
            ->assertConflict()
            ->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT');
        $this->assertDatabaseCount('don_mua_goi', 1);
    }

    public function test_invalid_or_stopped_package_cannot_create_order(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $this->taoHoSoHoiVien($fixture);
        $stopped = $this->taoGoiTapMembership($fixture, ['trang_thai' => 'NGUNG_BAN']);
        $missingBenefits = $this->taoGoiTapMembership($fixture);
        DB::table('quyen_loi_goi_tap')->where('goi_tap_id', $missingBenefits['package']->getKey())->delete();
        $token = $this->layTokenProfile($fixture);

        foreach ([$stopped['package']->getKey(), $missingBenefits['package']->getKey(), 999999999] as $packageId) {
            $this->postJson(
                '/api/packages/'.$packageId.'/orders',
                [],
                array_merge($this->bearer($token), ['Idempotency-Key' => $this->uuidPayment()]),
            )->assertNotFound();
        }
        $this->assertDatabaseCount('don_mua_goi', 0);
    }

    public function test_catalog_changes_do_not_change_existing_order_snapshot(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $this->taoHoSoHoiVien($fixture);
        $goi = $this->taoGoiTapMembership($fixture, ['gia' => 500000, 'ten_goi' => 'ORIGINAL']);
        $created = $this->taoDonPaymentQuaApi($fixture, $goi['package']->getKey());

        $goi['package']->forceFill(['gia' => 900000, 'ten_goi' => 'CHANGED', 'phien_ban_cau_hinh' => 2])->save();
        DB::table('quyen_loi_goi_tap')->where('goi_tap_id', $goi['package']->getKey())->update(['so_buoi_huan_luyen_vien' => 12]);
        $snapshot = KyHanHoiVien::query()->where('don_mua_goi_id', $created['order']->getKey())->sole();

        $this->assertSame('500000', (string) $snapshot->gia_da_mua);
        $this->assertSame('ORIGINAL', $snapshot->ten_goi);
        $this->assertSame(1, (int) $snapshot->phien_ban_goi);
        $this->assertSame(4, (int) $snapshot->so_buoi_huan_luyen_vien);
        $this->assertSame('500000', (string) $created['order']->so_tien_phai_thu);
    }

    public function test_member_cannot_read_another_members_order_or_payment(): void
    {
        $memberA = $this->taoNguoiDungAuth(['MEMBER']);
        $this->taoHoSoHoiVien($memberA);
        $goi = $this->taoGoiTapMembership($memberA);
        $created = $this->taoDonPaymentQuaApi($memberA, $goi['package']->getKey());
        $memberB = $this->taoNguoiDungAuth(['MEMBER']);
        $this->taoHoSoHoiVien($memberB);
        $tokenB = $this->layTokenProfile($memberB);

        $this->getJson('/api/orders/'.$created['order']->getKey(), $this->bearer($tokenB))->assertNotFound();
        $this->getJson('/api/orders/'.$created['order']->getKey().'/payment', $this->bearer($tokenB))->assertNotFound();
        $this->getJson('/api/orders', $this->bearer($tokenB))->assertOk()->assertJsonPath('data', []);
    }

    public function test_provider_failures_never_mark_paid_or_provision_membership(): void
    {
        $cases = [
            ['PAYOS_AUTH', false, 502, 'THAT_BAI'],
            ['PAYOS_RATE_LIMIT', true, 503, 'THAT_BAI'],
            ['PAYOS_TIMEOUT', true, 503, 'CAN_DOI_SOAT'],
            ['PAYOS_UPSTREAM', true, 503, 'CAN_DOI_SOAT'],
        ];

        foreach ($cases as [$code, $retryable, $status, $paymentStatus]) {
            $fixture = $this->taoNguoiDungAuth(['MEMBER']);
            $this->taoHoSoHoiVien($fixture);
            $goi = $this->taoGoiTapMembership($fixture);
            $this->fakePaymentGateway->createException = new PaymentGatewayException($code, $retryable, $status);

            $this->postJson(
                '/api/packages/'.$goi['package']->getKey().'/orders',
                [],
                array_merge($this->bearer($this->layTokenProfile($fixture)), ['Idempotency-Key' => $this->uuidPayment()]),
            )->assertStatus($status)->assertJsonPath('code', $code);
            $this->assertDatabaseHas('lan_thanh_toan', ['ma_loi' => $code, 'trang_thai' => $paymentStatus]);
            $this->assertSame(0, DB::table('dang_ky_goi_tap')->where('hoi_vien_id', DB::table('ho_so_hoi_vien')->where('nguoi_dung_id', $fixture['user']->getKey())->value('id'))->count());
            $this->fakePaymentGateway->createException = null;
        }
    }

    public function test_invalid_provider_response_is_persisted_for_reconciliation(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $this->taoHoSoHoiVien($fixture);
        $goi = $this->taoGoiTapMembership($fixture, ['gia' => 500000]);
        $this->fakePaymentGateway->resultFactory = static fn (array $request): PaymentLinkResult => new PaymentLinkResult(
            orderCode: $request['order_code'],
            amount: 1,
            currency: 'VND',
            paymentLinkId: 'mismatch-link',
            checkoutUrl: 'https://pay.test/mismatch',
            qrCode: 'MISMATCH-QR',
            expiredAt: $request['expired_at'],
        );

        $this->postJson(
            '/api/packages/'.$goi['package']->getKey().'/orders',
            [],
            array_merge($this->bearer($this->layTokenProfile($fixture)), ['Idempotency-Key' => $this->uuidPayment()]),
        )->assertStatus(502)->assertJsonPath('code', 'PAYOS_RESPONSE_MISMATCH');

        $this->assertDatabaseHas('don_mua_goi', ['trang_thai' => 'CAN_DOI_SOAT']);
        $this->assertDatabaseHas('lan_thanh_toan', ['trang_thai' => 'CAN_DOI_SOAT', 'ma_loi' => 'PAYOS_RESPONSE_MISMATCH']);
        $this->assertDatabaseCount('dang_ky_goi_tap', 0);
    }

    public function test_failed_create_link_idempotency_replays_same_controlled_failure(): void
    {
        $fixture = $this->taoNguoiDungAuth(['MEMBER']);
        $this->taoHoSoHoiVien($fixture);
        $goi = $this->taoGoiTapMembership($fixture);
        $key = $this->uuidPayment();
        $headers = array_merge($this->bearer($this->layTokenProfile($fixture)), ['Idempotency-Key' => $key]);
        $this->fakePaymentGateway->createException = new PaymentGatewayException('PAYOS_RATE_LIMIT', true, 503);

        $this->postJson('/api/packages/'.$goi['package']->getKey().'/orders', [], $headers)
            ->assertStatus(503)
            ->assertJsonPath('code', 'PAYOS_RATE_LIMIT');
        $this->fakePaymentGateway->createException = null;
        $this->postJson('/api/packages/'.$goi['package']->getKey().'/orders', [], $headers)
            ->assertStatus(503)
            ->assertJsonPath('code', 'PAYOS_RATE_LIMIT');

        $this->assertCount(1, $this->fakePaymentGateway->requests);
        $this->assertDatabaseCount('don_mua_goi', 1);
        $this->assertDatabaseCount('lan_thanh_toan', 1);
    }
}
