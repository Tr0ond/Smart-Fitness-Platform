# GYM QR + CHECK-IN FOUNDATION REPORT

## 1. Scope

Đã triển khai Backend foundation cho QR vào phòng tập: phát hành QR động cho Member, xác nhận check-in bởi Receptionist/Admin, tái kiểm tra entitlement hiện thời, kích hoạt Membership qua usage đầu tiên, lịch sử self-service, replay/concurrency protection và kiểm thử. Không triển khai QR bitmap, scanner/camera, check-out, phần cứng, FE, Mobile, AI, PT, Chat hoặc Workout.

## 2. Environment

- PHP: 8.4.25
- Laravel: 13.29.0
- DB_CONNECTION: `mysql`
- DBMS: MariaDB 10.4.32, InnoDB
- Laravel SESSION sql_mode: `STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION`
- Test schema: `smart_fitness_gym_test_20260829_c8d41f` (đã xóa)

## 3. QR Architecture

- Token type: opaque hex credential từ `random_bytes(32)`, 256-bit entropy, 64 ký tự chữ thường.
- Raw token stored: NO; chỉ trả một lần qua response có `Cache-Control: no-store`.
- Hash: SHA-256 lưu tại `ma_vao_phong_tap.ma_bam_bi_mat`.
- TTL: 90 giây mặc định từ `config('gym.qr_ttl_seconds')` / `GYM_QR_TTL_SECONDS`.
- One-time: YES; `da_su_dung_luc` và UNIQUE lịch sử theo QR bảo vệ kết quả.
- Multiple-active policy: QR mới thu hồi mọi QR cũ cùng Member còn hạn/chưa dùng trong transaction khóa Member; cuối transaction chỉ QR mới còn dùng được.
- QR không gắn cứng kỳ; kỳ được xác định lại lúc scan.

## 4. Endpoints

| Method | Path | Role | Purpose |
| --- | --- | --- | --- |
| POST | `/api/gym/qr` | MEMBER | Phát hành QR của chính Member. |
| POST | `/api/gym/check-in` | RECEPTIONIST, ADMIN | Xác nhận một QR và ghi check-in. |
| GET | `/api/gym/check-ins` | MEMBER | Đọc lịch sử check-in của chính Member. |

## 5. QR Generation

- Ownership: principal Bearer → `ho_so_hoi_vien`; không nhận Member/User ID từ client.
- Membership eligibility: đúng head `CHO_KICH_HOAT` hoặc kỳ hiện tại theo `[start,end)`, với `cho_phep_vao_phong_tap = true`.
- Client không điều khiển TTL, mốc phát hành/hết hạn hoặc chi nhánh.
- Chi nhánh lấy từ chuỗi Membership đã cấp quyền.
- Activation on generation: NO.
- Side effect hợp lệ duy nhất: thu hồi credential cũ còn active và tạo credential mới; không usage/lịch sử/activation.

## 6. Check-In Flow

1. Form Request giới hạn `qr_token` đúng 64 hex, không nhận Member/branch từ client.
2. Băm SHA-256 để tìm QR; token rõ không được ghi log hoặc DB.
3. Transaction khóa `ho_so_hoi_vien` trước, rồi đọc lại account/role của Member và nhân viên.
4. Khóa QR, kiểm tra chưa dùng, chưa thu hồi và `now < het_han_luc`.
5. Gọi `MembershipEntitlementService::kiemTra(..., VAO_PHONG_TAP, ..., true)` để đối chiếu lifecycle và đúng kỳ hiện thời.
6. Tạo `su_dung_quyen_loi` loại `VAO_PHONG_TAP` cho đúng Member/kỳ.
7. Gọi lại `MembershipActivationService::kichHoatNeuCan()`; không sao chép logic activation.
8. Tạo `lich_su_vao_phong_tap`, gán `da_su_dung_luc` và commit atomically.
9. Bất kỳ lỗi nào rollback usage, activation, history và consumed marker.

## 7. Authorization

- Member generate: active account + active MEMBER role + self profile + Gym entitlement.
- Receptionist scan: active Bearer + active RECEPTIONIST role; middleware và service đều đọc lại role.
- Admin scan: active Bearer + active ADMIN role; PASS.
- PT scan: BLOCKED.
- Member self-confirm: BLOCKED.
- Role revoke behavior: có hiệu lực ngay; cả target Member và staff đều được kiểm tra lại từ DB.
- Disabled account: BLOCKED; target Member bị khóa không thể dùng QR đã phát hành.
- STAFF BRANCH AUTHORIZATION: DEFERRED vì project chưa chốt policy cross-branch. Client không được gửi branch; history vẫn dùng đúng `chi_nhanh_id` của QR qua FK kép.

## 8. Membership Integration

- Gym entitlement source: `ky_han_hoi_vien.cho_phep_vao_phong_tap` của đúng kỳ snapshot.
- `CHO_KICH_HOAT` scan: tạo usage rồi kích hoạt chuỗi/kỳ bằng service hiện có.
- `DANG_HOAT_DONG` scan: tạo usage/check-in nhưng không đổi first usage, start hoặc end.
- Kỳ nối tiếp: lifecycle chuyển kỳ tại đúng biên; QR tạo ở kỳ A có thể check-in bằng kỳ B nếu B là kỳ hiện tại và cấp Gym.
- Kỳ tương lai không được mượn quyền; chuỗi HUY/hết hoặc snapshot không Gym bị từ chối.
- Payment success: không activation.
- QR issue: không activation.

## 9. Usage Ledger

- Usage table: `su_dung_quyen_loi`.
- Usage type: `VAO_PHONG_TAP` đúng CHECK/schema và Membership services.
- Ownership: cùng `hoi_vien_id` và `ky_han_hoi_vien_id` với lịch sử/FK kép.
- Actor: `nguoi_thuc_hien_id` là Receptionist/Admin xác nhận; `nguoi_xac_nhan_id` trong history lưu cùng nhân viên.
- First-use reference: check-in đầu tiên gán đúng usage vào `dang_ky_goi_tap.lan_su_dung_dau_tien_id`.
- Subsequent check-in không ghi đè nguồn đầu tiên.
- Duplicate usage cho cùng QR/check-in: BLOCKED bằng row lock, consumed state và UNIQUE B21.

## 10. QR Security

- Opaque: YES.
- Cryptographically random: YES, `random_bytes(32)`.
- Raw token persisted/logged/history: NO.
- Replay: BLOCKED bằng HTTP 409 `QR_ALREADY_USED`.
- Superseded QR: BLOCKED bằng HTTP 409 `QR_REVOKED`.
- Expired: BLOCKED bằng HTTP 410 `QR_EXPIRED`.
- Unknown token: concealed HTTP 404; malformed/unbounded input bị 422 trước query.
- Account recheck: YES.
- Role recheck: YES.
- Membership recheck: YES.
- QR expiry dùng server UTC clock; client không cung cấp thời gian.

## 11. Check-In History

- Table: `lich_su_vao_phong_tap`.
- Self history: `GET /api/gym/check-ins`, derive Member từ principal, không có tham số Member ID.
- Response allow-list: check-in time, term ID và branch metadata an toàn.
- Không trả token, hash, payment/provider data hoặc lịch sử của Member khác.
- Hard delete: NO trong code nghiệp vụ; chỉ fixture test được dọn khỏi schema disposable.
- Không áp giới hạn tùy ý một check-in/ngày; hai QR mới có thể tạo hai check-in cùng ngày.

## 12. Concurrency

- Actual parallel processes: YES; hai PHP process, hai Laravel bootstrap/DB connection riêng và file barrier.
- Raw test token truyền qua file tạm, không nằm trong command line/output và được xóa ở `finally`.
- Same QR simultaneous scan: một process success, process còn lại `QR_ALREADY_USED`.
- Exactly one success: PASS.
- History count: 1.
- Usage count: 1.
- QR consumed count: 1.
- Activation effect: đúng một source/clock; registration `DANG_HOAT_DONG` tham chiếu đúng một usage.

## 13. Boundary Tests

- QR TTL: `[created, expires)`; ngay trước hạn còn hợp lệ, đúng hạn bị 410, không dùng `sleep()`.
- Membership: `[start, end)` được tái sử dụng từ lifecycle foundation.
- Term boundary: đúng `term A.end = term B.start`, A thành `HET_HAN`, B thành `DANG_HOAT_DONG`, check-in gắn B.
- QR phát hành trước biên không bị gắn cứng vào A.
- Microseconds DATETIME(6) được giữ trong activation, expiry, usage và history assertions.

## 14. Non-Activation Matrix

- Login: NO.
- `/auth/me`: NO.
- Profile read/update: NO.
- Package read: NO.
- Membership read: NO.
- Payment success: NO.
- QR generation: NO.
- QR hết hạn/bị thu hồi/không đủ quyền: NO.
- Successful first Gym check-in: YES.
- Check-in sau khi đã kích hoạt: NO reactivation/reset.

## 15. Tests

- QR tests: ownership, format, entropy/hash, TTL config/default, no-store, no activation, no client authority, no Gym entitlement và supersession.
- Check-in tests: valid Receptionist/Admin, forbidden Member/PT, malformed/unknown/revoked/expired, cancelled Membership, account/role revoke, active no-reset và same-day multiple entry.
- Replay tests: PASS; lần hai 409, tổng history/usage giữ 1.
- Concurrency: PASS với hai process thật.
- Membership activation integration: PASS, gồm first usage pointer và queued-term boundary.
- History: PASS self-scope, no IDOR/secret/payment fields.
- Focused Gym suite: PASS — 16 tests, 182 assertions.
- Full backend run 1: PASS — 121 tests, 1,727 assertions.
- Full backend run 2: PASS — 121 tests, 1,727 assertions.
- Randomized: PASS — 121 tests, 1,727 assertions, seed `20260829`.
- Pint affected files: PASS.
- `git diff --check`: PASS.

## 16. Database Safety

- `smart_fitness` modified: NO.
- Read-only baseline sau test: migrations=60, nguoi_dung=8; `ma_vao_phong_tap=0`, `lich_su_vao_phong_tap=0`, `su_dung_quyen_loi=0`, `don_mua_goi=0`, `lan_thanh_toan=0`, `dang_ky_goi_tap=0`, `ky_han_hoi_vien=0`.
- M001–M060 chạy chỉ trên `smart_fitness_gym_test_20260829_c8d41f`; không migration nào được tạo/sửa.
- Test schema trước cleanup có migrations=60 và mọi bảng vận hành QR/Payment/Membership nêu trên đã về 0.
- Cleanup: PASS; schema test đã drop và `information_schema` xác nhận remaining count = 0.

## 17. Deferred

- Check-out/occupancy/visit duration: DEFERRED.
- Physical turnstile/camera scanner: DEFERRED.
- Staff history/admin dashboard: DEFERRED.
- Staff cross-branch authorization: DEFERRED chờ policy.
- QR image rendering: FE/Mobile.
- Audit event cho QR bị từ chối: DEFERRED chờ audit catalog chính thức.
- AI request/quota, PT assignment/direct service, PT Chat, Workout, FE, Mobile: DEFERRED.
- Không gọi payOS/provider/LLM và không tạo giao dịch thật.

## 18. Files Changed

Backend implementation/config:

- `BE/.env.example`
- `BE/config/gym.php`
- `BE/routes/api.php`
- `BE/app/Exceptions/Gym/GymWorkflowException.php`
- `BE/app/Http/Controllers/Api/Gym/GymController.php`
- `BE/app/Http/Requests/Gym/IssueGymQrRequest.php`
- `BE/app/Http/Requests/Gym/CheckInRequest.php`
- `BE/app/Services/Gym/GymQrService.php`
- `BE/app/Services/Gym/GymCheckInService.php`
- `BE/app/Services/Gym/GymCheckInQueryService.php`

Tests:

- `BE/tests/Concerns/CreatesGymFixtures.php`
- `BE/tests/Feature/GymQrApiTest.php`
- `BE/tests/Feature/GymCheckInTest.php`
- `BE/tests/Feature/GymCheckInConcurrencyTest.php`
- `BE/tests/Support/run_gym_check_in.php`
- `BE/tests/Feature/PayOSWebhookConcurrencyTest.php`
- `BE/tests/Support/run_payment_webhook.php`

Documentation:

- `PROJECT_RULES.md`
- `docs/thiet_ke_co_so_du_lieu/TU_DIEN_DU_LIEU.md`
- `docs/thiet_ke_co_so_du_lieu/MIGRATION_PLAN.md`
- `docs/thiet_ke_co_so_du_lieu/GYM_QR_CHECKIN_FOUNDATION_REPORT.md`

Hai file Payment test chỉ được tổng quát hóa safety regex từ prefix module-specific sang convention schema disposable chung; không đổi Payment behavior/assertion. Ba tài liệu rule chỉ đồng bộ policy replay mới “một success, retry conflict”; không đổi logical schema hoặc M001–M060.

## 19. Deviations

Business/schema deviations: NONE.

Implementation dùng một `GymController` và ba service thay cho nhiều controller/access wrapper minh họa; contract endpoint, authorization và transaction giữ nguyên. Audit catalog và staff cross-branch policy chưa được phê duyệt nên được ghi DEFERRED đúng yêu cầu.

## 20. Final Gate

- DYNAMIC QR = PASS
- QR TTL 90S = PASS
- QR ONE-TIME = PASS
- QR REPLAY PROTECTION = PASS
- QR GENERATION OWNERSHIP = PASS
- QR GENERATION NON-ACTIVATION = PASS
- GYM ENTITLEMENT = PASS
- CURRENT MEMBERSHIP RECHECK = PASS
- RECEPTIONIST CHECK-IN = PASS
- ADMIN CHECK-IN = PASS
- UNAUTHORIZED ROLE = BLOCKED
- FIRST CHECK-IN ACTIVATION = PASS
- ACTIVE MEMBER NO REACTIVATION = PASS
- USAGE LEDGER = PASS
- FIRST-USAGE REFERENCE = PASS
- CHECK-IN HISTORY = PASS
- CHECK-IN OWNERSHIP / IDOR = PASS
- EXPIRED QR = BLOCKED
- REVOKED ROLE = BLOCKED
- DISABLED ACCOUNT = BLOCKED
- EXPIRED/CANCELLED MEMBERSHIP = BLOCKED
- CHECK-IN IDEMPOTENCY = PASS
- CHECK-IN CONCURRENCY = PASS
- QR `[created,expires)` BOUNDARY = PASS
- MEMBERSHIP `[start,end)` BOUNDARY = PASS
- FULL BACKEND TEST SUITE = PASS
- TEST ORDER INDEPENDENCE = PASS
- TEST DATABASE CLEANUP = PASS
- `smart_fitness` = SAFE

- GYM QR + CHECK-IN FOUNDATION = PASS
- DYNAMIC QR API = IMPLEMENTED
- GYM CHECK-IN API = IMPLEMENTED
- QR REPLAY PROTECTION = IMPLEMENTED
- MEMBERSHIP ACTIVATION BY GYM CHECK-IN = IMPLEMENTED
- CHECK-IN HISTORY = IMPLEMENTED
- DATABASE = READY FOR NEXT PAID-SERVICE MODULE
