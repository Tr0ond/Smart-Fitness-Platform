# PT ASSIGNMENT + DIRECT SERVICE REPORT

## 1. Scope

Đã triển khai foundation Backend cho vòng đời phân công PT và xác nhận buổi PT trực tiếp. Phạm vi gồm tạo/kết thúc/chuyển phân công, Member/PT self-read, kiểm tra quyền PT, Membership entitlement, trừ quota đúng một lần, kích hoạt Membership ở lần sử dụng trả phí đầu tiên, idempotency, chống race và ownership/IDOR.

Không thay đổi logical schema, business rule, M001–M060, Model, Seeder hoặc migration. Không triển khai PT booking, calendar, PT Chat/WebSocket/Reverb, PT AI Proposal, Workout mutation, FE hay Mobile.

## 2. Environment

- PHP: **8.4.25** (`E:\Fitness\.tools\php\php.exe`).
- Laravel: **13.29.0** (khóa trong `composer.lock`).
- DB_CONNECTION: `mysql`.
- DBMS: **MariaDB 10.4.32**, InnoDB.
- Laravel MySQL session policy: `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION`.
- Global XAMPP `sql_mode`: `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`; không thay đổi global mode.
- Schema chạy test cuối: `smart_fitness_pt_test_20260829_review3`.
- M001–M060 và `DatabaseSeeder` chạy thành công trên schema test; schema đã được cleanup sau test.

## 3. Assignment Architecture

- Bảng dùng: `phan_cong_huan_luyen_vien`.
- Một Member có tối đa một assignment đang hiệu lực tại một thời điểm.
- Khoảng thời gian là **`[ngay_bat_dau, ngay_ket_thuc)`**; `ngay_ket_thuc = NULL` là khoảng mở.
- Backend kiểm tra đầy đủ overlap hữu hạn và vô hạn bằng điều kiện khoảng, không chỉ dựa vào generated guard `hoi_vien_dang_phan_cong_id`.
- Mutation dùng transaction và khóa theo thứ tự: hồ sơ Member → toàn bộ assignment của Member theo `id` tăng dần → hồ sơ PT.
- Kết thúc và chuyển PT giữ lại hàng lịch sử; không hard-delete.

## 4. Assignment Authorization

| Operation | Allowed actor | Enforcement |
| --- | --- | --- |
| Create, end, reassign | `ADMIN` đang hoạt động | route role middleware + service role/profile check |
| Member self-read | `MEMBER` đang xác thực | resolve `ho_so_hoi_vien` từ Bearer principal |
| PT assigned-member read | `PT` có role hiệu lực, profile `HOAT_DONG` | query lọc bằng PT profile của principal |

`RECEPTIONIST` không được tự cấp thêm quyền quản lý assignment. Member/PT profile và user account phải ở trạng thái hoạt động; PT phải có role `PT` chưa bị thu hồi.

## 5. Assignment Endpoints

| Method | Path | Role | Purpose |
| --- | --- | --- | --- |
| POST | `/api/pt/assignments` | ADMIN | Tạo assignment |
| PATCH | `/api/pt/assignments/{assignment}/end` | ADMIN | Kết thúc bằng server time |
| POST | `/api/pt/assignments/{assignment}/reassign` | ADMIN | Đóng assignment cũ và mở PT mới |
| GET | `/api/pt/assignment` | MEMBER | Đọc PT hiện tại và lịch sử của chính Member |
| GET | `/api/pt/members` | PT | Đọc Member đang thuộc PT hiện tại |
| GET | `/api/pt/direct-sessions` | MEMBER/PT | Đọc lịch sử direct service trong phạm vi principal |
| POST | `/api/pt/direct-sessions/complete` | PT | Xác nhận buổi PT đã hoàn thành |

Response là DTO allow-list, không trả raw Model, password, token, payment payload hoặc counter nội bộ không cần thiết.

## 6. Reassignment

Assignment cũ được cập nhật `ngay_ket_thuc = ngay_bat_dau` của assignment mới; assignment mới được tạo trong cùng transaction. Mốc biên hợp lệ vì hai khoảng chỉ chạm nhau. Lịch sử PT và các dòng sử dụng cũ không bị rewrite. Chuyển assignment chỉ áp dụng với assignment đang hiệu lực và không backdate.

## 7. Direct Service Architecture

- Actor xác nhận: **PT của assignment đó**; Member, Receptionist và Admin bị chặn.
- Booking/appointment/calendar: **NOT IMPLEMENTED**.
- `hoan_thanh_luc` và `xac_nhan_luc`: do server tạo tại thời điểm xác nhận, không nhận timestamp client.
- Assignment phải hiệu lực ngay lúc xác nhận; assignment quá hạn, tương lai hoặc thuộc PT khác bị từ chối.
- Transaction gồm Member lock → assignment locks → Membership/term lock → entitlement/quota → usage/history → activation/counter → idempotency result.

## 8. Membership Integration

`MembershipLifecycleService`, `MembershipEntitlementService` và `MembershipActivationService` là authority dùng lại, không đọc live package catalog và không cộng quota từ nhiều kỳ.

- Kỳ áp dụng là head/current term hợp lệ tại server time, theo `[ngay_bat_dau, ngay_ket_thuc)`.
- `CHO_KICH_HOAT` có quota PT hợp lệ được kích hoạt bởi completion đầu tiên; transaction tạo usage, kích hoạt, history và counter cùng commit.
- `DANG_HOAT_DONG` chỉ trừ thêm một quota; không reset `ngay_bat_dau`, `ngay_ket_thuc` hay first-use pointer.
- `CHO_THANH_TOAN`, `CHO_DEN_LUOT` tương lai, `HET_HAN`, `HUY`, không entitlement hoặc quota hết đều bị từ chối.
- Không borrow quota kỳ tương lai và không carry-over phần chưa dùng sang kỳ sau.

## 9. PT Rights

`cho_phep_tro_chuyen_huan_luyen_vien` (Chat) và `so_buoi_huan_luyen_vien` (direct quota) được kiểm tra độc lập. Chat không tạo assignment và assignment không tự cấp Chat.

- Chat `true`, direct quota `0`: direct completion bị từ chối.
- Chat `false`, direct quota `> 0`: direct completion hợp lệ vẫn thành công.

## 10. Usage / Ledger

- Direct history: `lich_su_su_dung_huan_luyen_vien`.
- Usage: `su_dung_quyen_loi` với `loai_su_dung = BUOI_HUAN_LUYEN`.
- `ma_buoi_huan_luyen` dùng UUID Idempotency-Key ổn định; `trang_thai = HOAN_THANH`, `so_luot_su_dung = 1`, `nguon_thao_tac = WEB_HUAN_LUYEN_VIEN`.
- Một completion hợp lệ tạo đúng một usage, một history và tăng `so_buoi_huan_luyen_vien_da_dung` đúng một lần.
- `dang_ky_goi_tap.lan_su_dung_dau_tien_id` trỏ đúng usage direct đầu tiên; completion sau không ghi đè.
- PT Chat không tạo usage cho từng tin nhắn; Chat module để task sau.

## 11. Idempotency

`yeu_cau_chong_lap` dùng scope `XAC_NHAN_BUOI_HUAN_LUYEN`, `nguoi_dung_id` của PT và UUID `Idempotency-Key`. Hash request chuẩn hóa gồm assignment và notes.

- Cùng key/cùng payload: trả lại kết quả cũ, không tạo history/usage/counter thứ hai.
- Cùng key/khác payload: HTTP 409 `IDEMPOTENCY_CONFLICT`.
- Replay sau hoàn tất giữ nguyên completion timestamp đầu tiên.

## 12. Concurrency

| Scenario | Evidence | Result |
| --- | --- | --- |
| Hai staff assign hai PT cho cùng Member và cùng khoảng | Hai PHP process độc lập, schema `smart_fitness_pt_test_20260829_conc3` | 1 success (`assignment_id=1`), 1 HTTP 409 `ASSIGNMENT_OVERLAP`; final one valid row |
| Hai completion hợp lệ đua quota còn 1 | Hai PHP process/connection, schema `smart_fitness_pt_test_20260829_directconc` | 1 success, 1 HTTP 409 `PT_QUOTA_EXHAUSTED`; used=1, history=1, usage=1 |
| Cùng completion/key chạy đồng thời | Hai PHP process/connection, schema `smart_fitness_pt_test_20260829_replayconc` | 1 commit mới + 1 replay; history=1, usage=1, counter=1 |

Probe scripts: `BE/tests/Support/prepare_pt_concurrency.php`, `run_pt_assignment.php`, `run_pt_direct_service.php`. Tất cả schema concurrency đã drop và không còn trong `information_schema`.

## 13. Boundary Tests

- Assignment tại đúng `ngay_ket_thuc` không còn hiệu lực; khoảng liền kề `[end,start)` được phép.
- Membership tại đúng `ngay_ket_thuc` không còn cấp quota.
- Assignment hết hạn, assignment tương lai và completion gửi timestamp quá khứ đều bị chặn; server time là authority.
- Không dùng `sleep()` để kiểm tra biên; các test dùng mốc Carbon cố định hoặc mốc tương đối rõ ràng.

## 14. Non-Activation Matrix

| Action | Activates Membership? |
| --- | --- |
| Create assignment | NO |
| End assignment | NO |
| Reassign | NO |
| Member self-read | NO |
| PT reads assigned Members | NO |
| Valid first direct service | YES |
| Invalid/expired/future/quota-denied direct service | NO |
| Direct service on active Membership | NO reactivation/reset |

## 15. Security

- Assignment and history self-read resolve owner từ Bearer principal; Member B/PT B không thấy dữ liệu của A.
- Wrong PT dùng assignment của PT khác bị conceal thành 404 `ASSIGNMENT_NOT_FOUND`; không tạo ledger/quota.
- Revoked role bị 403; disabled PT bị middleware 401; disabled Member bị 409 `MEMBER_NOT_ACTIVE`.
- Form Request cấm client truyền `member_id`, `trainer_id`, `membership_term_id`, `usage_id`, status, timestamps, quota/counter và các field authority ngoài contract.
- Không expose SQL exception, token, password, payment/provider secret.

## 16. Tests

- Focused PT suite: **PASS — 18 tests, 158 assertions**.
- Full backend run 1: **PASS — 156 tests, 2,131 assertions**.
- Full backend run 2: **PASS — 156 tests, 2,131 assertions**.
- Random order `--order-by=random --random-order-seed=20260829`: **PASS — 156 tests, 2,131 assertions**.
- PHP syntax lint cho toàn bộ file mới/sửa: **PASS**.
- `git diff --check`: **PASS**.
- `BE/vendor/bin/pint` tồn tại; chạy `..\.tools\php\php.exe vendor/bin/pint --test` cho **FAIL** vì nhiều fixer style trong source nền và một số file PT. Không chạy fixer để tránh thay đổi ngoài phạm vi.

## 17. Development Database Safety

- `smart_fitness` modified: **NO**.
- Read-only verification sau test: MariaDB 10.4.32; 53 base tables; `lich_su_su_dung_huan_luyen_vien=0`, `yeu_cau_tro_ly=0`, `de_xuat_ke_hoach_tap=0`.
- Không chạy `migrate:fresh`, rollback, truncate hoặc drop trên `smart_fitness`.
- Test schema cuối `smart_fitness_pt_test_20260829_review3` đã drop; `information_schema.SCHEMATA` không còn schema `smart_fitness_pt_test_20260829%`.
- Không có migration PHP mới hoặc migration M001–M060 bị sửa.

## 18. Deferred

- PT Booking/appointment/calendar: **NOT IMPLEMENTED**.
- PT Chat/realtime/WebSocket/Reverb: **DEFERRED**.
- PT AI Proposal: **DEFERRED**.
- Workout Plan/Version/Schedule mutation và Workout Session: **DEFERRED**.
- `ghi_chu_huan_luyen` workflow: **DEFERRED**; notes direct service hiện chỉ lưu ở trường ghi chú ledger được schema hỗ trợ.
- FE/Mobile: **DEFERRED**.

## 19. Files Changed

- `BE/routes/api.php`
- `BE/app/Exceptions/Pt/PtWorkflowException.php`
- `BE/app/Http/Controllers/Api/Pt/PtAssignmentController.php`
- `BE/app/Http/Controllers/Api/Pt/PtDirectServiceController.php`
- `BE/app/Http/Requests/Pt/CreatePtAssignmentRequest.php`
- `BE/app/Http/Requests/Pt/ReassignPtAssignmentRequest.php`
- `BE/app/Http/Requests/Pt/EndPtAssignmentRequest.php`
- `BE/app/Http/Requests/Pt/CompletePtDirectServiceRequest.php`
- `BE/app/Services/Pt/PtAssignmentService.php`
- `BE/app/Services/Pt/PtDirectService.php`
- `BE/tests/Concerns/CreatesPtFixtures.php`
- `BE/tests/Feature/PtAssignmentApiTest.php`
- `BE/tests/Feature/PtDirectServiceTest.php`
- `BE/tests/Support/prepare_pt_concurrency.php`
- `BE/tests/Support/run_pt_assignment.php`
- `BE/tests/Support/run_pt_direct_service.php`
- `docs/thiet_ke_co_so_du_lieu/PT_ASSIGNMENT_DIRECT_SERVICE_REPORT.md`

Không sửa Model, Seeder, migration, `PROJECT_RULES.md`, tài liệu Database/ERD, FE hoặc Mobile.

## 20. Deviations

**NONE.** Role quản lý assignment giữ đúng `ADMIN` theo PROJECT_RULES; không mở quyền Receptionist. Không thêm bảng/cột/check/FK, không thêm booking, không gọi payOS/LLM/Reverb và không triển khai Chat.

## 21. Final Gate

- PT ASSIGNMENT CREATE = PASS
- PT ASSIGNMENT END = PASS
- PT REASSIGNMENT = PASS
- MEMBER MAX ONE ACTIVE PT = PASS
- ASSIGNMENT OVERLAP = BLOCKED (conflict được từ chối)
- ASSIGNMENT CONCURRENCY = PASS
- MEMBER ASSIGNMENT SELF READ = PASS
- PT ASSIGNED MEMBER READ = PASS
- ASSIGNMENT IDOR = PASS
- PT DIRECT SERVICE = PASS
- ONLY ASSIGNED PT CONFIRMS = PASS
- PT BOOKING = NOT IMPLEMENTED
- PT DIRECT ENTITLEMENT = PASS
- PT QUOTA DEDUCTION = PASS
- PT QUOTA EXACT ONCE = PASS
- PT QUOTA CONCURRENCY = PASS
- FUTURE TERM BORROWING = BLOCKED
- PT QUOTA CARRY OVER = BLOCKED
- BACKDATE = BLOCKED
- PT CHAT / DIRECT RIGHTS SEPARATION = PASS
- FIRST PT SERVICE ACTIVATION = PASS
- ACTIVE MEMBERSHIP NO REACTIVATION = PASS
- FIRST-USAGE REFERENCE = PASS
- ASSIGNMENT NON-ACTIVATION = PASS
- FULL BACKEND TEST SUITE = PASS
- TEST ORDER INDEPENDENCE = PASS
- TEST DATABASE CLEANUP = PASS
- `smart_fitness` = SAFE

**PT ASSIGNMENT + DIRECT SERVICE FOUNDATION = PASS**

**PT ASSIGNMENT API = IMPLEMENTED**

**PT REASSIGNMENT = IMPLEMENTED**

**PT DIRECT SERVICE = IMPLEMENTED**

**PT DIRECT QUOTA = IMPLEMENTED**

**PT DIRECT SERVICE ACTIVATION = IMPLEMENTED**

**PT CHAT = NOT YET IMPLEMENTED**

**PT BOOKING = NOT IMPLEMENTED**

**DATABASE = READY FOR PT REALTIME CHAT FOUNDATION**

Đã dừng đúng phạm vi foundation; bước tiếp theo được đề xuất là PT Realtime Chat Foundation, chưa tự triển khai trong task này.


## Post-Implementation Verification

### Route verification

**PASS.** `artisan route:list --path=api/pt -v` đăng ký đúng 7 route:

| Method | URI | Middleware |
| --- | --- | --- |
| POST | `api/pt/assignments` | `api, auth:api, role:ADMIN` |
| PATCH | `api/pt/assignments/{assignment}/end` | `api, auth:api, role:ADMIN` |
| POST | `api/pt/assignments/{assignment}/reassign` | `api, auth:api, role:ADMIN` |
| GET | `api/pt/assignment` | `api, auth:api, role:MEMBER` |
| GET | `api/pt/members` | `api, auth:api, role:PT` |
| GET | `api/pt/direct-sessions` | `api, auth:api, role:MEMBER,PT` |
| POST | `api/pt/direct-sessions/complete` | `api, auth:api, role:PT` |

Không có route PT Booking, Chat, Reverb hay WebSocket.

### Four critical rules

- **Rule 1 — max-one/overlap: PASS.** `PtAssignmentService::tao` khóa Member và assignments theo ID; `damBaoKhongChongKhoang` xử lý finite interval và NULL end bằng công thức overlap. Generated guard không phải lớp duy nhất.
- **Rule 2 — only assigned PT: PASS.** `PtDirectService` resolve PT profile từ principal, kiểm tra assignment thuộc profile đó và hiệu lực NOW; PT khác bị conceal 404. Middleware chặn Member/Admin/Receptionist.
- **Rule 3 — applicable quota: PASS.** Quota lấy từ `MembershipEntitlementService`/snapshot kỳ hiện tại; không đọc catalog live, không mượn kỳ tương lai, không carry-over; usage/history/counter exact-once trong transaction.
- **Rule 4 — no backdate: PASS.** Server tạo completion/confirmation timestamps; Form Request cấm client timestamps. Assignment và Membership hết hạn tại NOW không thể được cứu bằng timestamp cũ.

### Membership activation

**PASS.** Code dùng lại `MembershipLifecycleService`, `MembershipEntitlementService` và `MembershipActivationService`; không duplicate lifecycle logic. Create/end/reassign/read assignment không activation. Direct service hợp lệ trong `CHO_KICH_HOAT` kích hoạt; Membership active không reset start/end/first-use pointer.

### Chat/direct separation

**PASS.** `cho_phep_tro_chuyen_huan_luyen_vien` không được đọc để quyết định direct completion. Test Chat=true + quota=0 bị từ chối; Chat=false + quota>0 thành công. Assignment không cấp Chat.

### Idempotency

**PASS.** Scope `XAC_NHAN_BUOI_HUAN_LUYEN` trong `yeu_cau_chong_lap`; cùng PT + key + normalized payload chỉ có 1 history, 1 usage, 1 quota decrement; khác payload trả 409.

### Concurrency probes

Các probe được chạy lại bằng hai PHP 8.4 process độc lập trên MariaDB:

- **Assignment race: PASS.** Schema `smart_fitness_pt_test_20260829_review_assignconc3`: `success assignment_id=1` và `error ASSIGNMENT_OVERLAP http_status=409`.
- **Direct quota race: PASS.** Schema `smart_fitness_pt_test_20260829_review_directconc`: một success, một `PT_QUOTA_EXHAUSTED 409`; final `used=1, history=1, usage=1`.
- **Same-key race: PASS.** Schema `smart_fitness_pt_test_20260829_review_replayconc`: một `replayed=false`, một `replayed=true`; final `used=1, history=1, usage=1`.

Ba schema và thư mục probe tạm đã được dọn.

### Quality and regression verification

- **PINT = FAIL.** `BE/vendor/bin/pint` tồn tại và đã chạy qua PHP 8.4. Full repository có fixer ở nhiều Model/migration/Seeder nền; file PT bị báo gồm `CompletePtDirectServiceRequest.php`, `PtDirectService.php` và `CreatesPtFixtures.php`. Không áp dụng fixer vì đây là review verification và không có lỗi logic/security cần sửa.
- **PHP syntax = PASS.** Tất cả PHP file PT mới/sửa lint không lỗi.
- **Focused suite = PASS.** `PtAssignmentApiTest.php` + `PtDirectServiceTest.php`: 18 tests, 158 assertions.
- **Full suite run 1 = PASS.** 156 tests, 2,131 assertions.
- **Full suite run 2 = PASS.** 156 tests, 2,131 assertions.
- **Random order = PASS.** Seed 20260829, 156 tests, 2,131 assertions.
- **Development DB = SAFE.** Kết nối read-only xác nhận `DATABASE() = smart_fitness`; 53 tables, PT history/AI request/AI proposal đều 0 sau verification.
- **Test schema cleanup = PASS.** Không còn `smart_fitness_pt_test_%` hoặc schema concurrency tương đương.
- **Git diff check = PASS.**

### Bugs found/fixed

Không phát hiện bug logic, authorization, ownership, transaction hoặc concurrency trong review. Không sửa source implementation sau review; chỉ cập nhật report để phản ánh đúng Pint và evidence verification.

## Verification Final Gate

- ROUTES = PASS
- MEMBER MAX ONE ACTIVE PT = PASS
- ASSIGNMENT OVERLAP = BLOCKED
- ASSIGNMENT CONCURRENCY = PASS
- ONLY ASSIGNED PT CONFIRMS = PASS
- PT DIRECT ENTITLEMENT = PASS
- PT QUOTA EXACT ONCE = PASS
- PT QUOTA CONCURRENCY = PASS
- FUTURE BORROWING = BLOCKED
- CARRY OVER = BLOCKED
- BACKDATE = BLOCKED
- PT CHAT / DIRECT RIGHTS SEPARATION = PASS
- FIRST DIRECT SERVICE ACTIVATION = PASS
- ASSIGNMENT NON-ACTIVATION = PASS
- IDEMPOTENCY = PASS
- FOCUSED TESTS = PASS
- FULL BACKEND = PASS
- RANDOM ORDER = PASS
- DEVELOPMENT DB = SAFE
- TEST DATABASE CLEANUP = PASS
- GIT DIFF CHECK = PASS

**PT ASSIGNMENT + DIRECT SERVICE VERIFICATION = PASS**

**PT REALTIME CHAT = READY TO START, BUT NOT IMPLEMENTED IN THIS TASK.**
