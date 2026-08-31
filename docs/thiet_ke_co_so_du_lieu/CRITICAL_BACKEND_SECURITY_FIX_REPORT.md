# CRITICAL BACKEND SECURITY FIX REPORT

## 1. Scope

Task chỉ sửa hai lỗi đã được chỉ định:

1. Q05 PT historical Chat authorization: PT cũ không được giữ LIST/DETAIL/MESSAGES/SEND/SUBSCRIBE sau khi exact assignment kết thúc.
2. Demo seeder/role regrant safety: production không tự seed demo, direct demo seeder fail closed, role đã thu hồi không bị tái cấp im lặng.

Không đổi logical schema, migration, Model, API contract ngoài authorization hiện hữu, FE/Mobile hoặc feature khác. Không commit/push.

Môi trường kiểm chứng: PHP 8.4.25, Laravel 13.29.0, MariaDB 10.4.32/InnoDB, session strict mode theo project. Các probe chỉ dùng `smart_fitness_security_fix_test_20260830_main`, `_prod`, `_concurrency`.

## 2. Source-of-Truth Rules

**Q05:** Member luôn được đọc conversation/messages cũ của chính mình và có thể authorize lại private channel lịch sử; read/subscribe không kích hoạt Membership. PT chỉ có resource scope khi exact `phan_cong_huan_luyen_vien` của conversation đang hiệu lực tại server time. Interval là `[ngay_bat_dau, ngay_ket_thuc)`: `now < end` hợp lệ, `now == end` đã hết hiệu lực. PT cũ và PT mới đều không được đọc/subscribe conversation cũ.

**Role regrant:** giữ UNIQUE `(nguoi_dung_id, vai_tro_id)`. Tái cấp phải cập nhật cùng row bằng workflow transaction + audit. Seeder không được đặt `thu_hoi_luc = NULL` để bỏ qua workflow; không tạo audit giả để hợp thức hóa.

## 3. Finding A — PT Historical Chat

**Previous behavior:** `PtChatAuthorizationService` chỉ kiểm tra account/role và PT profile là historical participant. `PtChatQueryService` liệt kê mọi conversation theo `huan_luyen_vien_id`. Private channel dùng cùng historical-participant check. Kết quả: PT cũ vẫn list/read/messages/subscribe sau assignment end.

**Correct behavior:** Member historical scope được giữ nguyên; PT scope phụ thuộc exact assignment active ngay tại request/reconnect. PT mới chỉ truy cập conversation mới gắn assignment mới.

**Root cause:** oracle cũ đã đồng nhất historical scope của Member và PT, trái Q05. Trước fix, corrected suite tái hiện **8 failures** trong 28 tests: old PT list/detail/messages/end-boundary/channel, final integration và hai nhóm seeder.

## 4. Chat Code Fix

**Authorization service:** `loaiNguoiThamGia()` vẫn nhận Member owner cho historical read. Nhánh PT nay xác minh assignment FK/Member/PT tuple và interval `[start,end)` bằng UTC server clock. Scope thất bại được conceal bằng 404. `laNguoiThamGia()` dùng cùng rule nên Reverb auth từ chối reconnect của PT cũ mà không đọc Membership.

**Query service:** Member query vẫn lấy toàn bộ conversation của chính Member. PT query thêm `whereHas(phanCongHuanLuyenVien)` với `ngay_bat_dau <= now` và `ngay_ket_thuc IS NULL OR ngay_ket_thuc > now`, nên LIST không làm lộ conversation cũ và không bị giới hạn 100 rows trước khi lọc.

**Channel authorization:** `routes/channels.php` không cần đổi. Callback hiện hữu gọi authorization service đã được harden; Member historical subscribe PASS, old/new PT old-channel auth trả 403. Channel auth không tạo usage, không kích hoạt Membership và không mutate conversation.

Send/activation services không đổi. Member gửi vào old conversation vẫn bị `CHAT_ASSIGNMENT_NOT_ACTIVE`; old PT không còn resource scope nên nhận 404 trước workflow gửi.

## 5. Correct Read/Send/Subscribe Matrix

| Actor / trạng thái | LIST/READ | SEND | SUBSCRIBE |
| --- | --- | --- | --- |
| Member, assignment active | YES | YES nếu current entitlement hợp lệ | YES |
| Member, historical assignment | YES | NO | YES |
| Exact PT, assignment active | YES | YES nếu assignment/entitlement/account/profile hợp lệ | YES |
| Old PT, assignment ended | NO | NO | NO |
| New PT, old conversation | NO | NO | NO |
| New PT, new active conversation | YES | YES nếu current entitlement hợp lệ | YES |
| Admin/Receptionist/foreign actor | NO | NO | NO |

## 6. Wrong Test Oracles Corrected

- `PtChatApiTest::test_reassignment_preserves_old_history_but_blocks_old_send_and_new_pt_access`: bỏ expectation old PT đọc history; thêm old PT LIST/DETAIL/MESSAGES/SEND block, Member historical read, new PT old-history block và new-conversation read.
- `PtChatApiTest::test_assignment_end_boundary_is_half_open_for_send_and_history`: PT read PASS tại `end - 1µs`, DETAIL/MESSAGES 404 tại `now == end`.
- Thêm `PtChatApiTest::test_member_historical_read_survives_assignment_end_without_activation_but_old_pt_loses_scope`.
- Đổi broadcast oracle thành `test_actual_broadcast_auth_allows_member_history_but_denies_old_and_new_pt_on_old_channel`; current PT/new conversation vẫn PASS.
- `FinalBackendIntegrationTest` scenario reassignment nay yêu cầu old PT messages/send đều 404.
- `PT_REALTIME_CHAT_FOUNDATION_REPORT.md` và `FINAL_BACKEND_INTEGRATION_SECURITY_REPORT.md` đã được sửa các tuyên bố sai và thêm correction evidence.

## 7. Finding B — Demo Seeder

**Previous behavior:** `DatabaseSeeder` luôn gọi `DemoNguoiDungSeeder` và dataset phụ thuộc demo Admin; direct demo seeder không có environment guard. Fixed-password active Admin/PT/Receptionist/Member vì vậy có thể được tạo ở production. Rerun còn đặt `thu_hoi_luc = NULL` cho role row đã thu hồi.

**Production guard:** `DatabaseSeeder` luôn chỉ gọi safe seeders `ChiNhanhSeeder`, `VaiTroSeeder`, `GoiTapSeeder`, `QuyenLoiGoiTapSeeder`. Demo users và dataset hiện phụ thuộc demo actor chỉ chạy trong `local`/`testing`.

**Direct seeder guard:** `DemoNguoiDungSeeder::run()` kiểm tra environment trước mọi query/mutation và ném controlled `RuntimeException` ngoài `local`/`testing`.

Fresh production-mode schema evidence sau M001–M060 và `DatabaseSeeder`:

```text
chi_nhanh=1, vai_tro=4, goi_tap=0
nguoi_dung=0, phan_quyen_nguoi_dung=0
ho_so_hoi_vien=0, ho_so_huan_luyen_vien=0, bai_tap=0
direct DemoNguoiDungSeeder exit=1; counts unchanged
```

Không có fixed demo Admin/password nào được tạo ở production.

## 8. Role Regrant Safety

Silent `thu_hoi_luc = NULL`: **REMOVED / BLOCKED**.

Audited role service reused: **NO**. Repository chưa có approved Admin RoleGrantService đáp ứng transaction + same-transaction audit + retry idempotency. Safe fallback trong scope này:

- preflight toàn bộ expected demo user/role pairs trước mọi demo mutation;
- nếu gặp row đã thu hồi, ném `RuntimeException` rõ ràng;
- giữ nguyên row, `thu_hoi_luc`, grant metadata, row count và audit count;
- role active hiện hữu được bỏ qua, không rewrite `cap_luc`/`nguoi_cap_id`/`thu_hoi_luc`;
- initial demo grant mới vẫn được phép trong local/testing.

Regrant phải đi qua Account/Role Admin workflow có audit trong task sau; seeder không insert audit giả.

## 9. Seeder Environment Matrix

| Environment | `DatabaseSeeder` | Direct `DemoNguoiDungSeeder` | Fixed demo credentials |
| --- | --- | --- | --- |
| local | safe catalog + demo + demo-dependent exercise dataset | ALLOWED | local demo only |
| testing | safe catalog + demo + demo-dependent exercise dataset | ALLOWED | isolated test schema only |
| production | safe reference/catalog only | BLOCKED before mutation | NOT CREATED |

Active demo roles chạy lại ở local/testing có zero logical delta và không duplicate row. Revoked role làm seeder fail closed trước mutation.

## 10. Tests

| Gate | Result |
| --- | --- |
| Corrected-oracle reproduction trước fix | FAIL như mong đợi: 28 tests, 8 failures; chứng minh hai lỗi |
| Chat + broadcast + seeder + integration focused | PASS — 28 tests / 575 assertions |
| Comprehensive affected security suite sau Pint | PASS — 49 tests / 708 assertions |
| Seeder production-mode executable probe | PASS — safe catalog only, 0 demo users/roles/profiles/exercises; direct demo blocked |
| Chat conversation creation concurrency | PASS — 2 process cùng `conversation_id=1`, DB 1 conversation |
| First Member activation concurrency | PASS — 2 messages/2 outbox, 1 usage/1 pointer, sequence 2, term active |
| Same message key concurrency | PASS — cùng message ID, 1 new + 1 replay, DB 1 message/1 outbox/1 usage |
| Assignment-end/send race | PASS — send `12:54:15.033890` trước end `12:54:15.085691`; 0 message tại/sau end |
| Full Backend run 1 | PASS — 211 tests / 3.028 assertions |
| Full Backend run 2 | PASS — 211 tests / 3.028 assertions |
| Random order seed `20260830` | PASS — 211 tests / 3.028 assertions |
| Pint, affected files only | PASS — 8 PHP files |
| PHP syntax lint | PASS — 8/8 files |
| `git diff --check` | PASS |

Baseline 206 tests / 2.966 assertions không giảm; final tăng 5 tests và 62 assertions. Existing Chat concurrency semantics, Membership activation, direct PT quota, IDOR concealment, safe error contract và message mass-assignment protection vẫn PASS.

## 11. Development DB Safety

`smart_fitness` chỉ được đọc count, không migrate/seed/test/mutate:

| Table | Before | After |
| --- | ---: | ---: |
| `nguoi_dung` | 8 | 8 |
| `phan_quyen_nguoi_dung` | 8 | 8 |
| `nhat_ky_he_thong` | 0 | 0 |
| `phan_cong_huan_luyen_vien` | 0 | 0 |
| `hoi_thoai` | 0 | 0 |
| `tin_nhan` | 0 | 0 |

`smart_fitness modified`: **NO**. Development DB safety: **PASS**.

## 12. Cleanup

- Dropped exact task schemas: `_main`, `_prod`, `_concurrency` under `smart_fitness_security_fix_test_20260830_*`.
- `information_schema.SCHEMATA` remaining for prefix: `[]`.
- Removed `.tmp/security_fix_chat_concurrency.ps1` and `.tmp/security_fix_chat_concurrency/` barrier/output files.
- Temp output remaining for task prefix: `[]`.

Cleanup: **PASS**.

## 13. Files Changed

Production/security fix:

- `BE/app/Services/Pt/Chat/PtChatAuthorizationService.php`
- `BE/app/Services/Pt/Chat/PtChatQueryService.php`
- `BE/database/seeders/DatabaseSeeder.php`
- `BE/database/seeders/DemoNguoiDungSeeder.php`

Tests/oracle:

- `BE/tests/Feature/PtChatApiTest.php`
- `BE/tests/Feature/PtChatBroadcastAuthorizationTest.php`
- `BE/tests/Feature/DemoSeederSecurityTest.php` (new)
- `BE/tests/Feature/Integration/FinalBackendIntegrationTest.php`

Reports:

- `docs/thiet_ke_co_so_du_lieu/PT_REALTIME_CHAT_FOUNDATION_REPORT.md`
- `docs/thiet_ke_co_so_du_lieu/FINAL_BACKEND_INTEGRATION_SECURITY_REPORT.md`
- `docs/thiet_ke_co_so_du_lieu/CRITICAL_BACKEND_SECURITY_FIX_REPORT.md` (new)

Các uncommitted test-support changes từ task Final Backend trước đó được giữ nguyên, không rollback và không được tính là security-fix source change. Migration/Model/FE/Mobile: **NONE**.

## 14. Remaining Backend Gaps

Không triển khai trong task này:

- real LLM provider;
- Registration/Password Reset API;
- Account/Role Admin API và audited role regrant workflow;
- Membership catalog Admin API;
- Exercise/Template Admin API;
- PT Proposal;
- Progress;
- Dashboard;
- Chat retry worker;
- docs/CI cleanup.

## 15. Final Gate

```text
PROJECT_RULES Q05 = ENFORCED
MEMBER HISTORICAL CHAT READ = PASS
OLD PT HISTORICAL CHAT READ = BLOCKED
OLD PT HISTORICAL MESSAGE HISTORY = BLOCKED
OLD PT SEND = BLOCKED
OLD PT PRIVATE CHANNEL SUBSCRIBE = BLOCKED
NEW PT OLD CONVERSATION READ = BLOCKED
NEW PT OLD CHANNEL SUBSCRIBE = BLOCKED
CURRENT PT ACTIVE CONVERSATION READ = PASS
CURRENT PT ACTIVE CHANNEL SUBSCRIBE = PASS
REASSIGNMENT NEW CONVERSATION = PASS
CHAT MEMBERSHIP ACTIVATION SEMANTICS = UNCHANGED/PASS
CHAT CONCURRENCY = PASS
DEMO USERS IN PRODUCTION AUTO-SEED = BLOCKED
DIRECT DEMO SEEDER IN PRODUCTION = BLOCKED
FIXED DEMO ADMIN PASSWORD IN PRODUCTION = NOT CREATED
SILENT ROLE REGRANT WITHOUT AUDIT = BLOCKED
TEST/LOCAL DEMO SEEDING = PASS
NO DATABASE SCHEMA CHANGE = PASS
FULL BACKEND RUN 1 = PASS
FULL BACKEND RUN 2 = PASS
RANDOM ORDER = PASS
DEVELOPMENT DB = SAFE
TEST SCHEMA CLEANUP = PASS
GIT DIFF CHECK = PASS

CRITICAL BACKEND SECURITY FIX = PASS
PT HISTORICAL CHAT AUTHORIZATION = FIXED
Q05 TEST ORACLE = CORRECTED
DEMO SEEDER PRODUCTION SAFETY = FIXED
SILENT ROLE REGRANT = BLOCKED
BACKEND SECURITY BASELINE = RESTORED
```
