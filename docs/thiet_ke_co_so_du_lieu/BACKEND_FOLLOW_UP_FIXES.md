# CÔNG VIỆC BACKEND CẦN SỬA TIẾP

Ngày hậu kiểm: **31/08/2026**

Phạm vi: Laravel Backend Smart Fitness Platform

Nguồn đối chiếu: `PROJECT_RULES.md`, đặc biệt Q10, Q12, quy tắc Role Audit và Definition of Done.

## 1. Trạng thái hiện tại

Backend đã có các module CORE chính, guard database test, bootstrap Admin, onboarding PT, API quản trị Payment, copy-on-write Workout Template, Password Reset qua Queue và production preflight.

Baseline đã xác minh trước khi tạo commit bàn giao:

| Kiểm tra | Kết quả |
| --- | --- |
| PHPUnit | PASS — 301 tests, 3.207 assertions |
| Pint `--test` | PASS |
| Composer validate `--strict` | PASS |
| Composer audit | PASS — không có security advisory |
| Platform requirements | PASS — PHP 8.4.25 |
| Fresh production-like migrate/seed | PASS — M001–M060 |
| Bootstrap Admin trên database mới | PASS — một account, một active Admin, một audit |
| Production preflight trên môi trường local hiện tại | FAIL-CLOSED đúng thiết kế |

Mã nguồn Backend/CI/test của baseline này được lưu tại commit `7446219` — `hoàn thiện lõi backend, quản trị và kiểm thử tích hợp`. Tài liệu bàn giao và backlog nằm trong commit chứa chính tệp này; dùng `git log` để lấy hash tương ứng.

Test xanh không đồng nghĩa toàn bộ các đường lỗi vận hành đã hoàn tất. Các mục bên dưới là backlog bắt buộc trước khi xác nhận production-ready.

## 2. BE-FOLLOWUP-01 — Hiển thị đầy đủ sự kiện Payment cần đối soát

**Ưu tiên:** P1

**Business Rule:** Q10

**File chính:** `BE/app/Services/Admin/AdminPaymentQueryService.php`, `BE/app/Services/Payments/PayOSWebhookService.php`.

### Hiện trạng

`AdminPaymentQueryService::danhSachSuKien()` đang loại các hàng có `lan_thanh_toan_id = NULL`. Trong khi đó webhook chủ động lưu các tình huống `INVALID_SIGNATURE` và `UNKNOWN_PROVIDER_ORDER` với FK này bằng NULL để giữ dấu vết.

Filter `reconciliation_required` của danh sách Payment mới xét trạng thái `lan_thanh_toan` và `don_mua_goi`; chưa xét event liên quan đang `CAN_DOI_SOAT`. Một Payment đã thành công nhưng nhận callback bất thường sau đó có thể không xuất hiện trong danh sách cần kiểm tra.

### Cách sửa

1. Cho Admin xem event chưa ghép được Payment trong MVP một chi nhánh; response phải cho biết `payment_id = null` và không trả raw payload/chữ ký.
2. Event đã ghép Payment tiếp tục được scope qua Member/chi nhánh.
3. Bổ sung nhánh `orWhereHas('suKienThanhToans')` với `trang_thai_xu_ly = CAN_DOI_SOAT` vào filter Payment cần đối soát.
4. Không sửa tay Payment thành công, không tự refund và không xóa event.

### Test bắt buộc

- Unknown provider order xuất hiện trong `/api/admin/payment-events`.
- Invalid signature xuất hiện dưới dạng dữ liệu đã lọc, không lộ payload/signature.
- Payment thành công có event bất thường vẫn xuất hiện với `reconciliation_required=1`.
- Sai role/không đăng nhập tiếp tục nhận 403/401.

### Output mong đợi

Admin truy được toàn bộ dấu vết Q10, gồm cả event chưa xác định được Payment, mà không thay đổi trạng thái giao dịch hoặc cấp thêm Membership.

## 3. BE-FOLLOWUP-02 — Khôi phục invitation khi Queue lỗi

**Ưu tiên:** P1

**File chính:** `BootstrapAdminCommand.php`, `TrainerOnboardingController.php`, `TrainerOnboardingService.php`, `PasswordResetService.php`.

### Hiện trạng

Account/role/profile/audit được commit trước khi dispatch Password Reset. Nếu Queue lỗi sau commit:

1. Lần đầu trả failure/503.
2. Retry bootstrap trả `UNCHANGED`, hoặc retry onboarding trả `replayed=true`.
3. Hai nhánh retry hiện bỏ qua enqueue invitation.
4. Kết quả onboarding đã ghi `invitation=QUEUED` trước khi Queue thực sự chấp nhận job.

### Cách sửa

1. Không công bố `QUEUED` trước khi dispatch thành công.
2. Retry `UNCHANGED`/idempotency replay phải có đường gửi lại invitation an toàn, hoặc cung cấp recovery command/workflow rõ ràng.
3. Không nhận password qua command option; không log raw reset token.
4. Nếu chọn transactional outbox hoặc thêm trạng thái invitation bền vững, phải được chủ dự án phê duyệt trước khi thêm migration ngoài M001–M060.

### Test bắt buộc

- Giả lập Queue throw sau khi transaction account đã commit.
- Retry khôi phục được invitation mà không tạo account/profile/role/audit trùng.
- Output chỉ ghi `QUEUED` sau khi enqueue thành công.
- Không có raw token/password trong console, response, log hoặc queue payload ban đầu.

### Output mong đợi

Sự cố Queue tạm thời không để lại Admin/PT có random password nhưng không còn đường nhận invitation.

## 4. BE-FOLLOWUP-03 — Bổ sung snapshot trước/sau cho audit onboarding PT

**Ưu tiên:** P2

**Business Rule:** mục 17.1 và Authentication/Authorization Test

**File chính:** `BE/app/Services/Admin/TrainerOnboardingService.php`.

### Hiện trạng

Onboarding trực tiếp grant/regrant role PT nhưng `du_lieu_truoc` và `du_lieu_sau` trong audit đều đang NULL. Admin cập nhật hồ sơ PT đã tồn tại cũng chưa có snapshot thay đổi tương ứng.

### Cách sửa

1. Grant mới: `du_lieu_truoc = null`, `du_lieu_sau` chứa assignment ID, account ID, role, người cấp, thời điểm cấp và trạng thái active.
2. Regrant: lưu snapshot revoked trước thay đổi và active sau thay đổi; giữ nguyên assignment ID/ngày tạo.
3. Cập nhật profile: lưu các trường thay đổi trước/sau; không ghi secret hoặc dữ liệu ngoài allow-list.
4. Role/profile mutation và audit phải cùng transaction.

### Test bắt buộc

- Grant/regrant có audit trước/sau đúng dữ liệu.
- Retry không tạo audit thành công trùng.
- Forced audit failure rollback account/profile/role mutation.

### Output mong đợi

Mỗi lần onboarding hoặc regrant PT truy được actor, thời điểm, đối tượng và trạng thái trước/sau theo đúng PROJECT_RULES.

## 5. BE-FOLLOWUP-04 — Làm chặt Reverb external preflight

**Ưu tiên:** P2

**File chính:** `BE/app/Console/Commands/SmartFitnessPreflightCommand.php`.

### Hiện trạng

External probe đang coi mọi HTTP status nhỏ hơn 500 là thành công. Một host sai trả 401/403/404 vẫn có thể được báo PASS; probe cũng chưa chứng minh WebSocket handshake hoặc private-channel authorization.

### Cách sửa

1. Kiểm tra endpoint/protocol đặc trưng của Reverb/Pusher thay vì chỉ kiểm tra HTTP `< 500`.
2. Trên staging, thực hiện WebSocket handshake và subscribe private channel bằng test account được cấp quyền.
3. Xác minh account không thuộc resource scope không thể subscribe.
4. Không in Reverb key/secret trong output.

### Test bắt buộc

- 404/401/403 không được tính PASS.
- Endpoint Reverb hợp lệ vượt qua handshake.
- Private-channel authorization đúng/sai resource scope cho kết quả tương ứng.

### Output mong đợi

`Reverb external = PASS` chỉ khi endpoint WebSocket thật và authorization cơ bản hoạt động.

## 6. BE-FOLLOWUP-05 — Xác minh production services thật

**Ưu tiên:** P1 trước deploy

**Phạm vi:** cấu hình/vận hành, không phải sửa business rule.

### Việc cần làm

1. Cấu hình `APP_ENV=production`, `APP_DEBUG=false`.
2. Dùng Redis hoặc Database Queue đã có bảng queue phù hợp; tuyệt đối không dùng `sync` cho Password Reset production.
3. Chạy Queue worker, Scheduler và Reverb bằng process supervisor.
4. Cấu hình Gemini model/key qua secret manager.
5. Chạy `smart-fitness:preflight --no-network` trên production-like environment.
6. Chỉ chạy `--external` trên staging khi đã được phép gọi Gemini/Reverb.
7. Kiểm tra worker thực sự consume job, không chỉ kiểm tra backend Queue kết nối được.

### Output mong đợi

Preflight trả exit code 0, Password Reset xử lý ngoài HTTP request, Chat realtime nhận message và outbox retry hoạt động sau reconnect.

## 7. BE-FOLLOWUP-06 — Chuẩn bị catalog production ban đầu

**Ưu tiên:** P2 trước nghiệm thu end-to-end.

Fresh production-like seed hiện tạo branch/role nhưng không tạo package hoặc Exercise vì chưa có catalog chính thức được phê duyệt. Đây không phải migration failure.

Trước nghiệm thu cần chọn một trong hai cách:

1. Admin tạo package/quyền lợi/Exercise bằng API quản trị; hoặc
2. Chủ dự án phê duyệt command import production-safe có actor Admin, dry-run, audit và nguồn dataset cố định.

Không chạy `DemoNguoiDungSeeder` hoặc `ExerciseDatasetSeeder` phụ thuộc demo actor trên production.

## 8. Thứ tự xử lý đề xuất

1. BE-FOLLOWUP-01 — Payment reconciliation visibility.
2. BE-FOLLOWUP-02 — Invitation recovery.
3. BE-FOLLOWUP-03 — PT audit snapshots.
4. BE-FOLLOWUP-04 — Reverb probe.
5. BE-FOLLOWUP-05 — Staging/production verification.
6. BE-FOLLOWUP-06 — Initial production catalog.

Sau mỗi mục phải chạy lại full PHPUnit, Pint, Composer validate/audit, `git diff --check` và các test concurrency liên quan. Không đánh dấu hoàn thành chỉ dựa vào UI hoặc một test happy path.
