# MEMBERSHIP + PACKAGE FOUNDATION REPORT

## 1. Scope

Đã triển khai Package Catalog chỉ đọc, Membership self-read, lifecycle/queue/snapshot, internal activation, internal provisioning hook và entitlement check foundation. Không triển khai Payment/payOS, webhook, QR, AI request, PT usage/chat, Workout, catalog mutation, FE hoặc Mobile. Không sửa migration, logical schema, Model hay Seeder.

## 2. Environment

PHP: 8.4.25

Laravel: 13.29.0

DB_CONNECTION: mysql (PDO MySQL đến MariaDB)

DBMS: MariaDB 10.4.32, InnoDB; không có bảng test ngoài InnoDB

Test schema: `smart_fitness_membership_test_20260829_d82f`

SESSION sql_mode: `STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION`

Migrations: M001–M060, 60/60 đã chạy trên schema test cô lập.

## 3. Business Rules Implemented

Separate purchase entitlement: PASS — mỗi đơn có đúng một snapshot `ky_han_hoi_vien`, không gộp quyền.

Snapshot: PASS — giá, thời hạn, version và toàn bộ quyền B18 được chụp khi tạo đơn; thay đổi catalog không sửa kỳ cũ.

No benefit merge: PASS — entitlement chỉ đọc một kỳ áp dụng.

Payment success starts time: NO — internal provisioning chỉ xếp kỳ thành `CHO_KICH_HOAT`/`CHO_DEN_LUOT`.

First paid-use activation: IMPLEMENTED AS INTERNAL FOUNDATION — nhận ID `su_dung_quyen_loi` đã được trusted Backend workflow chấp nhận; dùng `chap_nhan_luc`, không nhận timestamp từ client.

Interval: `[start, end)`.

Queue: một chuỗi mở mỗi Member; kỳ đầu chờ kích hoạt, các kỳ sau nối thứ tự và đồng hồ liên tục.

## 4. Package API

Endpoints:

- `GET /api/packages`
- `GET /api/packages/{package}`

Authentication: Bearer token bắt buộc; không yêu cầu role hoặc Membership đang hoạt động.

Visibility: chỉ `DANG_BAN` có bản ghi quyền lợi hợp lệ; ID phải là số; gói không tồn tại/ngừng bán/thiếu quyền trả 404 ở detail.

Response: explicit allow-list; không trả creator, hash, token hay dữ liệu payment.

Catalog mutation: DEFERRED.

Development catalog: EMPTY trong `smart_fitness`; API trả danh sách rỗng hợp lệ.

## 5. Membership API

Endpoint: `GET /api/membership`.

Authorization: `auth:api` + active role `MEMBER`.

Ownership: hồ sơ Member luôn được suy ra từ Bearer user. Query/body `member_id` bị bỏ qua; không có route đọc Membership theo ID tùy ý.

Response: current registration, applicable snapshot term, queued terms, pending-payment snapshots và history. Response không chứa payment ID, provider reference, webhook payload, secret, token hoặc audit internals.

No Membership: trả `current: null`, các collection rỗng có kiểm soát.

## 6. Membership Lifecycle

CHO_THANH_TOAN: snapshot đã chốt nhưng chưa thuộc chuỗi; không kích hoạt.

CHO_KICH_HOAT: head đã được cấp sau payment confirmation nhưng cả hai ngày vẫn NULL.

CHO_DEN_LUOT: term phía sau; trước activation chưa có ngày, sau activation nhận khoảng nối tiếp nhưng chưa được dùng sớm.

DANG_HOAT_DONG: đúng một kỳ thỏa `start <= now < end`.

HET_HAN: được reconciliation chuyển tại `now >= end`; khi hết toàn chuỗi, registration đóng và ghi nhận mốc kết thúc.

HUY: terminal; không cấp quyền và không được kích hoạt. Task này không tạo cancellation endpoint.

Lifecycle reconciliation không tạo usage, không kích hoạt và không dịch ngày đã materialize.

## 7. Activation Service

Service: `MembershipActivationService::kichHoatNeuCan(int $suDungQuyenLoiId)`.

Input contract: usage B19 đã được paid-service workflow tin cậy tạo và chấp nhận. Service không tạo usage, không trừ quota và không nhận ngày từ frontend.

Transaction: YES.

Locking: Member → open registration → ordered terms → usage; state được đọc lại sau khóa.

Validation: ownership composite, head/applicable term, state và entitlement snapshot theo đúng loại B19.

Repeated-call behavior: lần đầu trả `MOI_KICH_HOAT`; retry hoặc usage hợp lệ sau đó trả `DA_KICH_HOAT`, giữ nguyên first usage/start/end.

Public activation endpoint: NO.

## 8. Queue Semantics

Ordering: `so_thu_tu` tăng trong transaction theo payment confirmation đã được Backend xác nhận; cùng Member được serialize bằng row lock.

Head: sequence 1 là `CHO_KICH_HOAT` nếu chuỗi chưa chạy.

Tail: `CHO_DEN_LUOT`; không có activation riêng.

Continuity: khi head kích hoạt, toàn bộ kỳ chưa hủy được materialize liên tục; khi mua thêm vào chuỗi đang chạy, kỳ mới bắt đầu đúng tại end của tail hiện tại.

Overlap prevention: generated UNIQUE của B17 bảo vệ tối đa một chuỗi chưa kết thúc; transaction và UNIQUE B18 bảo vệ thứ tự kỳ. Test 2/3 purchases xác nhận không overlap và không duplicate.

Repeated reconciliation: PASS — ngày đầu/cuối không drift.

## 9. Snapshot / Entitlements

Catalog source: `goi_tap` + `quyen_loi_goi_tap`, chỉ tại lúc tạo snapshot đơn.

Historical snapshot source: các cột vật lý của `ky_han_hoi_vien`.

Live catalog mutation changes existing membership: NO.

Term entitlement merging: NO.

Future term borrowing: BLOCKED — chỉ kỳ hiện tại hoặc head chờ kích hoạt khi trusted workflow yêu cầu pre-check mới được xét.

AI quota: check `used + reserved < limit`; không reserve/consume trong task này.

## 10. PT Rights

Chat right: `cho_phep_tro_chuyen_huan_luyen_vien`.

Direct-session quota: `so_buoi_huan_luyen_vien - so_buoi_huan_luyen_vien_da_dung`.

Kept separate: YES.

Evidence: cả hai chiều đã test — Chat=true/PT=0 và Chat=false/PT>0.

## 11. Membership Non-Activation Matrix

Login: NO

Logout: NO

`/auth/me`: NO

Profile read: NO

Profile update: NO

Member profile read: NO

Availability read/update: NO

Equipment read/update: NO

Package list/detail read: NO

Membership read/history: NO

Payment success alone: NO

Sau toàn bộ ma trận, registration vẫn `CHO_KICH_HOAT`, start/end NULL và số usage không đổi.

## 12. Tests

Package tests: empty/list/detail/unknown/stopped/missing-benefit, response allow-list, no write/no activation.

Membership read tests: auth/role/revocation, empty response, self ownership/IDOR, pending snapshot, current/queue/history, cancelled/expired representation, secret exclusion.

Activation tests: valid first usage, exact timestamp, no extra ledger, repeated/later usage idempotency, missing/disallowed/unpaid/tail/cancelled/expired rejection.

Queue tests: 2 và 3 purchases, exact order, pre-activation NULL dates, continuous materialization, renewal after activation, idempotent provisioning, no merge và no drift.

Snapshot tests: V1 remains unchanged after catalog V2; new order gets V2.

Entitlement tests: AI quota, PT rights separation, no active/cancelled/expired/future denial, `[start,end)` exact microsecond boundary; không consume quota.

Focused Membership suite: PASS — 25 tests, 269 assertions.

Full backend suite run 1: PASS — 84 tests, 1,288 assertions.

Run 2: PASS — 84 tests, 1,288 assertions.

Randomized: PASS — 84 tests, 1,288 assertions, seed `20260829`.

## 13. Concurrency Evidence

Actual parallel connections/processes: YES — PHPUnit khởi chạy hai process PHP riêng, mỗi process bootstrap Laravel và dùng PDO connection riêng, cùng chờ một file barrier trước khi gọi service.

Activation exactly once: PASS — hai process tranh cùng usage cho kết quả `{MOI_KICH_HOAT, DA_KICH_HOAT}`; một source, một start/end, một term và không tạo thêm usage.

Duplicate provisioning prevented: PASS — hai process tranh cùng order/payment cho kết quả `{DA_CAP_MOI, DA_CAP_TRUOC}`; cuối cùng đúng một registration và một term sequence 1.

Isolation cleanup: fixture committed của concurrency được gỡ theo graph FK sau mỗi test; full suite và random order không bị nhiễm dữ liệu.

## 14. Database Safety

`smart_fitness` modified: NO.

Baseline trước và sau: migrations=60, nguoi_dung=8; goi_tap=0, don_mua_goi=0, lan_thanh_toan=0, dang_ky_goi_tap=0, ky_han_hoi_vien=0, su_dung_quyen_loi=0.

Test schema: `smart_fitness_membership_test_20260829_d82f`.

Safety guard: tests fail fast nếu `DATABASE() = smart_fitness` hoặc tên không khớp `smart_fitness_*test`.

Cleanup: PASS — fixture Membership bằng 0 sau test; schema test được drop và xác nhận không còn trong `information_schema` khi kết thúc task.

## 15. Deferred

Payment/payOS: DEFERRED

Payment webhook/provider verification: DEFERRED

QR/check-in workflow: DEFERRED

Paid AI request/quota reservation-consumption: DEFERRED

PT direct usage/completion deduction: DEFERRED

PT Chat message usage: DEFERRED

Package admin mutation: DEFERRED

Audit catalog: DEFERRED

FE/Mobile: DEFERRED

## 16. Files Changed

- `BE/app/Exceptions/MembershipLifecycleException.php`
- `BE/app/Http/Controllers/Api/Membership/MembershipController.php`
- `BE/app/Http/Controllers/Api/Package/PackageController.php`
- `BE/app/Services/MembershipActivationService.php`
- `BE/app/Services/MembershipEntitlementService.php`
- `BE/app/Services/MembershipLifecycleService.php`
- `BE/app/Services/MembershipProvisioningService.php`
- `BE/app/Services/MembershipQueryService.php`
- `BE/app/Services/MembershipSnapshotService.php`
- `BE/app/Services/PackageCatalogService.php`
- `BE/routes/api.php`
- `BE/tests/Concerns/CreatesMembershipFixtures.php`
- `BE/tests/Feature/MembershipActivationTest.php`
- `BE/tests/Feature/MembershipConcurrencyTest.php`
- `BE/tests/Feature/MembershipEntitlementLifecycleTest.php`
- `BE/tests/Feature/MembershipSnapshotQueueTest.php`
- `BE/tests/Feature/PackageMembershipApiTest.php`
- `BE/tests/Support/run_membership_operation.php`
- `docs/thiet_ke_co_so_du_lieu/MEMBERSHIP_PACKAGE_FOUNDATION_REPORT.md`

Không có file migration, Model, Seeder, Factory, FE hoặc Mobile bị sửa.

## 17. Deviations

NONE.

## 18. Final Gate

PACKAGE CATALOG READ = PASS

MEMBERSHIP SELF READ = PASS

MEMBERSHIP OWNERSHIP / IDOR = PASS

MEMBERSHIP STATE MODEL = PASS

MEMBERSHIP QUEUE = PASS

MEMBERSHIP SNAPSHOT = PASS

NO BENEFIT MERGING = PASS

MEMBERSHIP ACTIVATION FOUNDATION = PASS

ACTIVATION IDEMPOTENCY = PASS

ACTIVATION CONCURRENCY = PASS

[start,end) BOUNDARY = PASS

ENTITLEMENT FOUNDATION = PASS

FUTURE TERM BORROWING = BLOCKED

PT RIGHTS SEPARATION = PASS

AUTH NON-ACTIVATION = PASS

PROFILE NON-ACTIVATION = PASS

FULL BACKEND TEST SUITE = PASS

TEST ORDER INDEPENDENCE = PASS

TEST DATABASE CLEANUP = PASS

smart_fitness = SAFE

MEMBERSHIP + PACKAGE FOUNDATION = PASS

PACKAGE CATALOG API = IMPLEMENTED

MEMBERSHIP READ API = IMPLEMENTED

MEMBERSHIP LIFECYCLE = IMPLEMENTED

MEMBERSHIP ACTIVATION SERVICE = IMPLEMENTED

MEMBERSHIP ENTITLEMENT FOUNDATION = IMPLEMENTED

PAYMENT INTEGRATION = NOT YET IMPLEMENTED

DATABASE = READY FOR PAYMENT / PAYOS IMPLEMENTATION
