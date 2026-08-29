# PAYMENT + PAYOS FOUNDATION REPORT

## 1. Scope

Đã triển khai nền tảng Backend cho luồng `Package → Order → payOS Payment Link → verified webhook → reconciliation → MembershipProvisioningService`. Phạm vi gồm tạo/đọc đơn của chính Member, snapshot tại lúc tạo đơn, payment attempt, adapter payOS, webhook idempotent, đối soát bất thường, concurrency và kiểm thử. Không triển khai refund, QR check-in, AI, PT, Chat, Workout, FE hoặc Mobile.

## 2. Environment

- PHP: 8.4.25
- Laravel: 13.29.0
- DB_CONNECTION: `mysql`
- DBMS: MariaDB 10.4.32, InnoDB
- Laravel SESSION sql_mode: `STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION`
- Test schema: `smart_fitness_payment_test_20260829_f7c91a` (đã xóa sau kiểm thử)

## 3. payOS Integration

- Official docs checked: YES — [API](https://payos.vn/docs/api/), [PHP SDK](https://payos.vn/docs/sdks/back-end/php/), [webhook signature](https://payos.vn/docs/tich-hop-webhook/kiem-tra-du-lieu-voi-signature/)
- SDK: official `payos/payos`
- SDK version: 2.0.0; yêu cầu PHP >= 8.2, tương thích PHP 8.4.25
- API host: `https://api-merchant.payos.vn`
- Create payment endpoint: SDK v2 gọi `POST /v2/payment-requests`
- Webhook verification: `PayOSGateway` gọi official SDK `webhooks->verify(...)`
- Real credentials used: NO
- Real payment created: NO
- SDK client dùng timeout/connect timeout rõ ràng và `maxRetries = 0` để không tự retry request tạo link có kết quả mơ hồ.

## 4. Configuration

Các biến môi trường được khai báo bằng placeholder trong `.env.example` và đọc qua `config/payos.php`:

- `PAYOS_CLIENT_ID`
- `PAYOS_API_KEY`
- `PAYOS_CHECKSUM_KEY`
- `PAYOS_RETURN_URL`
- `PAYOS_CANCEL_URL`
- `PAYOS_TIMEOUT_SECONDS`
- `PAYOS_ORDER_TTL_MINUTES`
- `PAYOS_IDEMPOTENCY_TTL_HOURS`

Secrets committed: NO. Business service chỉ dùng `config('payos...')`, tương thích config cache.

## 5. Order Flow

- Ownership: Bearer principal → `ho_so_hoi_vien`; không nhận `hoi_vien_id` từ client.
- Package: phải tồn tại, `DANG_BAN` và có `quyen_loi_goi_tap`.
- Snapshot: `MembershipSnapshotService::taoChoDon()` tạo `ky_han_hoi_vien` trong cùng transaction tạo đơn/payment attempt.
- Amount authority: Backend snapshot từ `goi_tap.gia`, currency `VND`.
- Client price/Member/duration/benefit/status/URL/orderCode: bị Form Request từ chối.
- Idempotency: `yeu_cau_chong_lap`, scope `TAO_DON_MUA_GOI`, khóa theo user + `Idempotency-Key`; cùng key/cùng package trả lại cùng đơn/link/QR, khác package trả 409.
- `orderCode`: `(don_mua_goi.id * 1000) + so_lan`; deterministic, unique theo attempt và có kiểm tra giới hạn payOS `1..9007199254740991`.

## 6. Payment Link Flow

1. Transaction khóa Member, idempotency và catalog; tạo `don_mua_goi`, snapshot và `lan_thanh_toan=DANG_TAO`.
2. Commit local durable state.
3. Gọi gateway ngoài transaction với amount/orderCode/returnUrl/cancelUrl do Backend sở hữu.
4. Transaction khóa lại Member → order → payment → idempotency, đối chiếu toàn bộ response rồi chuyển payment sang `CHO_THANH_TOAN`.

Provider request dùng amount của snapshot. Thành công lưu `paymentLinkId`, checkout URL và hạn link; QR thanh toán chỉ trả trong response tạo đơn/idempotency replay qua JSON kết quả đã lọc. Timeout/network/5xx được giữ `CAN_DOI_SOAT`; 401/429/reject được ghi `THAT_BAI`; response không khớp đi `CAN_DOI_SOAT`. Không trạng thái nào tự đánh dấu đã trả tiền hoặc provision Membership.

## 7. Webhook Flow

- Public endpoint: `POST /api/webhooks/payos`
- Signature verification: official SDK, trước khi tin `orderCode`, amount, currency, reference hoặc code.
- Source of truth: WEBHOOK
- returnUrl authoritative: NO
- cancelUrl authoritative: NO
- Signature sai: HTTP 400; không đổi order/payment/membership, chỉ append event `BI_TU_CHOI` với `chu_ky_hop_le=false` và payload tối thiểu.
- Signature đúng: payload được allow-list; bank/account PII không lưu hoặc trả cho Member.

## 8. Payment State Machine

- `don_mua_goi`: `CHO_THANH_TOAN`, `DA_THANH_TOAN`, `HET_HAN`, `HUY`, `CAN_DOI_SOAT`.
- `lan_thanh_toan`: `DANG_TAO`, `CHO_THANH_TOAN`, `THANH_CONG`, `THAT_BAI`, `HUY`, `HET_HAN`, `CAN_DOI_SOAT`.
- `su_kien_thanh_toan`: `CHO_XU_LY`, `DA_XU_LY`, `BI_TU_CHOI`, `CAN_DOI_SOAT`, `CHO_THU_LAI`.
- Chỉ event có chữ ký đúng, `code=00`, đúng orderCode/amount/currency/paymentLinkId/reference, chưa hết hạn và phù hợp local state mới chuyển order/payment sang thành công.
- Event non-success đến sau success chỉ được ghi nhận; không downgrade trạng thái cuối.

## 9. Reconciliation

- Amount mismatch: `CAN_DOI_SOAT`
- Currency mismatch: `CAN_DOI_SOAT`
- paymentLinkId/reference/local-state mismatch: `CAN_DOI_SOAT`
- Unknown orderCode: event độc lập `CAN_DOI_SOAT`, không tạo order giả
- Duplicate same reference: idempotent, không provision lại
- Conflicting success reference: order/payment `CAN_DOI_SOAT`, giữ kỳ đã cấp, không cấp kỳ thứ hai
- Late/expired or provider/local contradiction: `CAN_DOI_SOAT`
- Auto refund: NO

## 10. Membership Integration

- Provision service: `MembershipProvisioningService::capTuThanhToanDaXacNhan()` hiện có; Payment không sao chép logic.
- Valid payment provisions: YES
- One order max one term/registration: được bảo vệ bởi UNIQUE schema, event idempotency, khóa Member và provisioning idempotent.
- Q02: `xac_nhan_luc` do Backend gán tại lần xác nhận hợp lệ dưới khóa Member; thứ tự kỳ theo thời điểm confirm, không theo thời điểm tạo order.
- Payment success activates clock: NO
- First state: `CHO_KICH_HOAT`
- Start/end after first payment: NULL/NULL
- Usage created by payment: NO

## 11. Idempotency

- Order creation: B52 `yeu_cau_chong_lap`; same key/same normalized package returns same result, conflict returns 409, double tap không tạo thêm order.
- Webhook: SHA-256 dedupe key từ channel + orderCode + paymentLinkId + reference + canonical verified-content hash; exact retry tăng `so_lan_nhan` nhưng giữ một event/effect.
- Provisioning: Payment gọi service hiện có trong cùng finalize transaction; retry trả kết quả đã xử lý và không tạo kỳ/chuỗi mới.
- Create-link network retry API: DEFERRED; không tự retry một request có kết quả ngoài hệ thống chưa chắc chắn.

## 12. Concurrency

- Actual parallel processes: YES, hai PHP process/connection độc lập cùng xử lý một signed webhook.
- Lock order: Member → existing event → order → payment; nested provisioning tiếp tục theo contract Member-first hiện có.
- Duplicate webhook exactly-once: PASS (`DA_XAC_NHAN` + `DA_XU_LY_TRUOC`, một event với `so_lan_nhan=2`).
- Duplicate provisioning: PASS (một registration, một term/order, không usage).
- Deadlock/state corruption: không xảy ra trong test.

## 13. Security

- Secrets exposed/committed/logged: NO
- Client controls amount/member/status/URLs/orderCode: NO
- Raw webhook returned to Member: NO
- Signature verified before payment/order/membership mutation: YES
- Bank PII minimized: YES; các field account/counter-account bị loại khỏi `du_lieu_da_loc`.
- IDOR: order/payment queries luôn scope theo Member hiện tại; foreign ID trả 404.
- Webhook không dùng Bearer auth; Member mutations/reads yêu cầu active `MEMBER` role.

## 14. Tests

- Focused Payment suite: PASS — 21 tests, 257 assertions.
- Order tests: auth/role, valid/invalid/stopped/missing-benefit package, server price, client tampering, snapshot, idempotency/conflict, IDOR.
- Gateway tests: request mapping, trusted URLs, success, 401, 429, timeout, 5xx, invalid response.
- Signature tests: known create-signature fixture, changed field, valid webhook, tampered amount/orderCode, wrong key, altered signature case.
- Webhook tests: valid success, invalid signature, duplicate x10, same/conflicting reference, stale event, unknown order.
- Mismatch tests: amount, currency, paymentLinkId and unknown orderCode.
- Concurrency tests: PASS với hai process thật.
- Membership integration tests: provision once, Q02 reverse confirmation order, payment non-activation/no usage.
- Focused Payment suite final: PASS — 21/21, 257 assertions.
- Full backend run 1: PASS — 105/105, 1,545 assertions.
- Full backend run 2: PASS — 105/105, 1,545 assertions.
- Randomized `--random-order-seed=20260829`: PASS — 105/105, 1,545 assertions.
- Pint affected-files check: PASS.
- Composer validation: PASS.

## 15. Development Database Safety

- `smart_fitness` modified: NO
- Final read-only counts in `smart_fitness`: `goi_tap=0`, `don_mua_goi=0`, `lan_thanh_toan=0`, `su_kien_thanh_toan=0`, `dang_ky_goi_tap=0`, `ky_han_hoi_vien=0`, `su_dung_quyen_loi=0`.
- Real payment created: NO
- Test schema: `smart_fitness_payment_test_20260829_f7c91a`
- M001–M060 were run only on that isolated schema; no migration file was created or changed.
- Cleanup: PASS; information_schema count after `DROP DATABASE` = 0.

## 16. Remote payOS Operations

- Remote webhook registration: NOT PERFORMED
- Real credential smoke: NOT PERFORMED
- Real payment request/bank transaction: NOT PERFORMED
- payOS get/cancel/payout APIs: NOT CALLED

## 17. Deferred

- Payment retry API: DEFERRED pending approved lifecycle/reconciliation contract
- Refund/partial refund/chargeback/payout: DEFERRED
- Payment-link cancellation: DEFERRED
- Manual reconciliation UI/API and remote status recovery: DEFERRED
- Return/cancel Backend handler: DEFERRED; configured URLs are UX only and clients query status APIs
- QR check-in, Gym access, AI, PT, Chat, Workout, FE/Mobile: DEFERRED

## 18. Files Changed

Dependency/config/integration:

- `BE/composer.json`
- `BE/composer.lock`
- `BE/.env.example`
- `BE/config/payos.php`
- `BE/app/Providers/AppServiceProvider.php`
- `BE/routes/api.php`
- `BE/app/Contracts/Payments/PaymentGateway.php`
- `BE/app/Data/Payments/PaymentLinkResult.php`
- `BE/app/Exceptions/Payments/InvalidWebhookSignatureException.php`
- `BE/app/Exceptions/Payments/PaymentGatewayException.php`
- `BE/app/Exceptions/Payments/PaymentWorkflowException.php`
- `BE/app/Gateways/PayOSGateway.php`
- `BE/app/Http/Requests/Payment/CreateOrderRequest.php`
- `BE/app/Http/Controllers/Api/Payment/OrderController.php`
- `BE/app/Http/Controllers/Api/Payment/PayOSWebhookController.php`
- `BE/app/Services/Payments/OrderPaymentService.php`
- `BE/app/Services/Payments/OrderQueryService.php`
- `BE/app/Services/Payments/PayOSWebhookService.php`

Tests/report:

- `BE/tests/Concerns/CreatesPaymentFixtures.php`
- `BE/tests/Fakes/FakePaymentGateway.php`
- `BE/tests/Feature/PaymentOrderApiTest.php`
- `BE/tests/Feature/PayOSWebhookTest.php`
- `BE/tests/Feature/PayOSWebhookConcurrencyTest.php`
- `BE/tests/Support/run_payment_webhook.php`
- `BE/tests/Unit/PayOSSignatureTest.php`
- `docs/thiet_ke_co_so_du_lieu/PAYMENT_PAYOS_FOUNDATION_REPORT.md`

Không sửa M001–M060, Model, Seeder, Membership service, FE hoặc Mobile.

## 19. Deviations

NONE. Official SDK 2.0.0 quản lý signing/verification. Adapter cung cấp PSR-18 client và PSR-17 factories rõ ràng vì SDK cần cả ba dependency runtime; đây là cấu hình kỹ thuật, không đổi contract payOS hoặc business rule.

## 20. Final Gate

- ORDER CREATION = PASS
- ORDER OWNERSHIP / IDOR = PASS
- ORDER SNAPSHOT = PASS
- CLIENT PRICE TAMPERING = BLOCKED
- PAYOS GATEWAY = PASS
- PAYMENT LINK CREATION CONTRACT = PASS
- PAYOS SIGNATURE = PASS
- WEBHOOK SIGNATURE VERIFICATION = PASS
- WEBHOOK SOURCE OF TRUTH = PASS
- RETURN URL NOT AUTHORITATIVE = PASS
- PAYMENT IDEMPOTENCY = PASS
- WEBHOOK IDEMPOTENCY = PASS
- WEBHOOK CONCURRENCY = PASS
- AMOUNT MISMATCH = CAN_DOI_SOAT
- CURRENCY MISMATCH = SAFE
- PROVIDER REFERENCE MISMATCH = SAFE
- CONFLICTING SUCCESS = SAFE
- ONE ORDER MAX ONE MEMBERSHIP = PASS
- VALID PAYMENT PROVISIONING = PASS
- PAYMENT SUCCESS NON-ACTIVATION = PASS
- PAYMENT TESTS = PASS
- FULL BACKEND TEST SUITE = PASS
- TEST ORDER INDEPENDENCE = PASS
- TEST DATABASE CLEANUP = PASS
- smart_fitness = SAFE

- PAYMENT + PAYOS FOUNDATION = PASS
- ORDER API = IMPLEMENTED
- PAYOS PAYMENT LINK = IMPLEMENTED
- PAYOS WEBHOOK = IMPLEMENTED
- PAYMENT RECONCILIATION FOUNDATION = IMPLEMENTED
- PAYMENT IDEMPOTENCY = IMPLEMENTED
- MEMBERSHIP PROVISIONING INTEGRATION = IMPLEMENTED
- MEMBERSHIP ACTIVATION ON PAYMENT = NO
- REAL PAYOS TRANSACTION = NOT REQUIRED FOR AUTOMATED PASS
- DATABASE = READY FOR GYM QR / CHECK-IN FOUNDATION
