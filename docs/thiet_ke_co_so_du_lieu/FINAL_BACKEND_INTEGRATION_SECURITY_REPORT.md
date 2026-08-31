# FINAL BACKEND INTEGRATION + SECURITY REPORT

## 1. Scope

Đã kiểm toán và chạy regression cho toàn bộ Laravel Backend hiện hành trước khi tích hợp client. Phạm vi gồm schema/migration/model/seeder/test isolation, Auth/Profile, Membership/Package, Payment/payOS, Gym QR, AI Request/Quota, PT Assignment/Direct, PT Chat/Reverb, Workout và AI Proposal Apply. Không triển khai feature mới, không sửa logical schema, không gọi payOS/LLM thật, không sửa FE/Mobile và không commit/push.

Post-audit Q05 phát hiện hai lỗi production có thể tái hiện: PT cũ còn quyền đọc/subscribe Chat sau khi assignment kết thúc, và demo seeder có thể chạy ở production/tái cấp role đã thu hồi mà không audit. Hai lỗi đã được sửa tối thiểu, không đổi logical schema; test integration trước đó và guard schema cô lập vẫn được giữ nguyên.

## 2. Environment

| Thành phần | Giá trị đã kiểm chứng |
| --- | --- |
| PHP | 8.4.25 CLI — `E:\Fitness\.tools\php\php.exe` |
| Laravel | 13.29.0 |
| MariaDB | 10.4.32, vendor `mariadb.org binary distribution` |
| Engine | InnoDB |
| Laravel session sql_mode | `STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION` |
| Laravel connection charset/collation | `utf8mb4` / `utf8mb4_unicode_ci` |
| Timezone | application UTC; DB session `SYSTEM`; branch timezone được áp dụng ở nghiệp vụ |
| Test schemas | Các schema final cũ và `smart_fitness_security_fix_test_20260830_main`, `_prod`, `_concurrency` |
| Baseline trước task | 202 tests / 2.844 assertions |
| Final suite sau Q05 security correction | 211 tests / 3.028 assertions |

Schema `_main` và `_concurrency` được chạy đủ M001–M060 và current `DatabaseSeeder`; dataset seed trả 1.324 bài tập, 28 dụng cụ, 50 nhóm cơ và 2.648 media không thiếu. Các schema probe riêng được migrate theo nhu cầu, không dùng database phát triển.

## 3. Modules Audited

| Module | Source/report/schema đối chiếu | Kết quả |
| --- | --- | --- |
| Database, migrations, models, seeder, isolation | 52 bảng nghiệp vụ, M001–M060, 52 model và reports nền | PASS |
| Authentication / Authorization | custom `AccessTokenGuard`, role middleware, API routes | PASS |
| User / Profile | self profile, Member/PT profile, preferences | PASS |
| Membership / Package | snapshot, queue, activation, interval, entitlement | PASS |
| Payment / payOS | order, link adapter, signed webhook, reconciliation | PASS |
| Gym QR | issue, TTL, one-time consume, check-in history | PASS |
| AI Request / Quota | reserve/consume/refund, candidate authority, Proposal | PASS |
| PT Assignment / Direct | overlap, reassignment, current quota, history | PASS |
| PT Chat / Reverb | conversation, message/outbox, channel authorization | PASS |
| Workout | Plan/version/schedule/Session/set/history | PASS |
| AI Proposal Apply | TTL/stale/idempotency/version/schedule/history | PASS |

Audit matrix sau correction PASS. Hai bug production và một lỗi test infrastructure được ghi tại mục 22.

## 4. Route / Role Matrix

`php artisan route:list --json` trả 58 route. Chỉ hai API route cố ý public: `POST api/auth/login` có throttle và `POST api/webhooks/payos` tự xác minh chữ ký. `php artisan channel:list` trả đúng một private channel: `pt.conversation.{hoiThoaiId}`.

| Actor | Quyền chính đã xác nhận | Bị chặn |
| --- | --- | --- |
| MEMBER | self profile/Membership/order/QR/AI/Workout, own PT history và Chat | foreign Member, PT mutation, staff check-in, foreign Proposal/Plan/Session/Chat |
| PT | assigned-member list, Direct completion, exact assigned Chat | Admin APIs, Member-only AI/Workout, foreign assignment/conversation |
| RECEPTIONIST | Gym check-in | private PT Chat, PT/Admin mutation, Member self APIs |
| ADMIN | package visibility và approved PT assignment management, Gym check-in | private PT Chat subscription/read/send |

Route middleware và service-level owner/assignment checks đều được kiểm tra; không thấy privilege escalation.

## 5. Authentication

- **Token:** Bearer token chỉ lưu hash; valid/missing/invalid/expired token trả đúng contract.
- **Revocation/logout:** revoked token không dùng lại được; logout thu hồi current token.
- **Disabled account:** account bị khóa được revalidate trên mỗi request và trả 401.
- **Role revoke:** role được đọc lại; token cũ không giữ quyền đã bị thu hồi và trả 403.
- **Cross-account:** token không thể chọn hoặc thay principal qua payload/resource ID.
- Không có token/password được trả trong business DTO hoặc log production.

## 6. IDOR

- **Member/Profile/Order/Membership/QR:** owner luôn suy ra từ authenticated user; foreign resource bị conceal bằng 404 hoặc forbidden theo contract.
- **PT:** assignment và Direct history dùng đúng Member-profile/PT-profile tuple; foreign PT không thể confirm.
- **Chat:** conversation gắn exact assignment; Member đọc history cũ, PT cũ mất scope tại assignment end, PT mới không đọc conversation cũ; Admin/Receptionist không vào private Chat.
- **AI:** foreign request/Proposal và Apply đều bị 404; client không chọn Member/candidate/base version.
- **Workout:** foreign Plan/schedule/Session/session exercise bị 404; test cuối còn thử foreign completion và mass-assigned Member ID.

IDOR = PASS.

## 7. Membership Lifecycle

| Hành động | Kích hoạt `CHO_KICH_HOAT`? | Evidence |
| --- | --- | --- |
| Payment success | NO | scenario A/B và PayOS suite |
| Gym check-in hợp lệ | YES | Gym integration + actual-process race |
| AI Request hợp lệ | YES | scenario A, quota suite |
| PT Direct completion hợp lệ | YES | PT Direct suite |
| Member gửi Chat đủ điều kiện lần đầu | YES | scenario B/C + Chat process race |
| PT gửi/read/subscribe Chat | NO | scenario B và Chat suites |
| Workout | NO | scenario A/B/C và no-Membership test |
| AI Proposal Apply | NO | scenario A và Apply suites |

Đồng hồ dùng interval `[start,end)`, giữ microsecond; `now < end` hợp lệ và `now == end` bị chặn xuyên Gym/AI/PT Direct/Chat. Activation không reset khi dùng dịch vụ tiếp theo.

## 8. Membership Queue / Snapshot

- Mỗi purchase tạo registration/term và snapshot riêng; không merge benefit hoặc counter.
- Kỳ sau ở `CHO_DEN_LUOT`; nối đuôi theo thứ tự đã duyệt.
- Scenario C làm cạn AI quota kỳ hiện tại rồi tạo kỳ sau có quota 5: request tiếp theo trả 429 `AI_QUOTA_EXHAUSTED`; kỳ sau vẫn `CHO_DEN_LUOT`, counter 0, usage 0.
- PT quota, Chat và Gym future borrowing được các module suites xác nhận BLOCKED.
- Thay đổi live package/benefit không sửa snapshot đã mua cho Gym/AI/PT/Chat.

Separate terms = PASS; future borrowing = BLOCKED; snapshot rights = PASS.

## 9. Payment Integration

- Order/link dùng fake gateway; không gọi payOS thật.
- PayOS webhook dùng `PayOSGateway` local để verify checksum, filter raw payload và đối chiếu amount/currency/reference/link.
- Invalid signature/authority manipulation không mutate payment/Membership.
- Duplicate/out-of-order webhook tuyến tính hóa; một business effect, một term; anomaly đi `CAN_DOI_SOAT`.
- Payment success chỉ provision quyền sở hữu `CHO_KICH_HOAT`, không tạo usage/start/end.
- Actual-process duplicate webhook test PASS.

## 10. Gym QR

- QR random/high entropy, chỉ lưu hash; TTL theo config mặc định 90 giây.
- Receptionist/Admin scan; Member/PT không có staff authority.
- QR one-time, expired/replayed/foreign manipulation bị chặn.
- First valid check-in có thể kích hoạt đúng một lần; một scan thành công tạo đúng một usage và một history.
- Hai process scan cùng QR: một success, một `QR_ALREADY_USED`.

## 11. AI Request

- Quota được reserve trong transaction, provider được gọi ngoài long transaction, success chuyển reserve thành used.
- Provider timeout/rate-limit/server/malformed failure trả reserve và không tạo successful Proposal; activation đã hợp lệ không bị rollback/reset.
- Candidate Rule Engine chỉ đưa exercise/dụng cụ khả dụng; Backend validate lại output có cấu trúc.
- Unknown exercise, ngoài candidate, equipment/context stale và range sai bị chặn.
- Idempotency một logical request tạo một provider-success accounting/Proposal; conflicting payload trả 409.

## 12. AI Apply

- LLM calls during Apply = **0**.
- Second AI quota = **0**; usage created = **NONE**; Membership activation = **BLOCKED**.
- Proposal content/hash/title bất biến; TTL exact boundary và terminal states được enforce.
- Base Plan/version, profile, availability, candidate và equipment stale đều bị chặn.
- No-Plan Apply tạo active Plan/v1; existing Plan tạo next immutable version.
- Chỉ future replaceable schedule được tạo lại; `DANG_TAP`, `HOAN_THANH`, `BO_QUA`, completed Session/exercise/set history được giữ nguyên.

## 13. PT

- **Assignment:** Admin-only; tối đa một assignment active/Member; overlap đầy đủ bị chặn, adjacent interval hợp lệ.
- **Direct:** chỉ active assigned PT confirm bằng server time; Member/foreign PT/backdate bị chặn; current-term quota exact-once và không phụ thuộc Chat right.
- **Reassignment:** old end = new start; old row/history giữ nguyên; PT B chỉ ghi Direct history qua assignment mới.
- Assignment operations không kích hoạt Membership.
- Actual-process last-quota: một success, một `PT_QUOTA_EXHAUSTED`; used/history/usage đều bằng 1.

## 14. PT Chat

- Conversation gắn exact assignment tuple và chỉ một conversation/assignment.
- First eligible Member message kích hoạt, tạo đúng một `TRO_CHUYEN_HUAN_LUYEN` usage và pointer trên message; message sau không tạo usage mới.
- PT message/read/channel auth hợp lệ không kích hoạt và không trừ direct quota.
- Reassign giữ conversation cũ readable cho Member, chặn toàn bộ old-PT scope, tạo conversation mới; PT mới không đọc conversation cũ.
- Private channel authorization dùng custom Bearer guard, participant/role và exact active-assignment check cho PT; event payload allow-list chỉ gồm conversation/message/sequence/sender type/content/sent time.
- Message + outbox cùng transaction; broadcast failure không xóa message/activation/usage và giữ retry state.

## 15. Workout

- Một active Plan/Member; version cũ bất biến, version mới không rewrite history.
- Một valid date slot theo generated UNIQUE; `CHUA_TAP`, `DANG_TAP`, `HOAN_THANH`, `BO_QUA` giữ slot; `HUY`, `DA_THAY_THE` nhường slot.
- Schedule date/owner guard và exact one Session/scheduled workout được enforce.
- Free Workout bị chặn bởi API absence và `phien_tap.buoi_tap_du_kien_id NOT NULL`.
- Workout chạy được khi không có/đã hết Membership và không kích hoạt hoặc trừ quota.
- Completed Session, materialized exercises và sets bất biến; restart/add set/different completion mutation bị chặn.

## 16. Cross-Module E2E

| Scenario | Luồng và invariant chính | Kết quả |
| --- | --- | --- |
| A | Payment → `CHO_KICH_HOAT` → AI activates once → Proposal → Apply no second charge → Plan/schedule → Start/set/Complete no Membership delta | PASS |
| B | Payment → assignment/no activation → PT send/read no activation → Member Chat activation → PT Direct decrement → Workout no Membership delta | PASS |
| C | Same term Chat → Direct → AI → Workout; independent ledgers/counters; future AI quota không được mượn | PASS |
| D | PT A Chat/Direct history → reassign PT B; Member giữ old read-only, PT A mất scope, PT B không đọc old, new conversation/direct authorization | PASS |

AI Apply completed-history preservation và Workout without Membership còn được kiểm tra độc lập trong module/final suite.

## 17. Idempotency Matrix

| Operation | Authority key/scope | Replay | Conflicting payload | Result |
| --- | --- | --- | --- | --- |
| Order create | Member + Idempotency-Key | same order | 409 | PASS |
| payOS webhook | signed event/reference | one business effect | reconciliation/reject | PASS |
| QR consume | QR hash/one-time state | blocked | blocked | PASS |
| Membership provisioning/activation | order/payment/usage | exact once | controlled | PASS |
| AI Request | Member + key + payload hash | same result | 409 | PASS |
| AI Apply | Member + key + Proposal | same Plan/version | 409/stale | PASS |
| PT Direct complete | PT + key + normalized payload | same history | 409 | PASS |
| PT Chat send | participant + client message UUID | same message | 409 | PASS |
| Workout Start/set/Complete | Member + operation key | same mutation | 409 | PASS |

CRITICAL IDEMPOTENCY MATRIX = PASS.

## 18. Concurrency Matrix

| Probe | Independent PHP processes/connections? | Evidence/result |
| --- | --- | --- |
| Membership activation exact-once | YES | actual-process `MembershipConcurrencyTest`; one activation materialization, no reset |
| Duplicate payment webhook | YES | one payment/provisioning business effect |
| Same QR scan | YES | one success, one `QR_ALREADY_USED` |
| AI last quota | YES | no oversubscription, one provider/quota effect |
| PT assignment overlap | YES | one assignment, one `ASSIGNMENT_OVERLAP` |
| PT Direct last quota | YES | one success/one 409; used=history=usage=1 |
| Chat first Member activation | YES | two messages/outboxes; one Chat usage/pointer; sequence=2 |
| Workout Start | YES | one create + one replay; one Session ID |
| AI same-Proposal Apply | YES | one business mutation/version/audit, replay-safe |
| Two AI Proposals same base | YES | one winner; loser stale/conflict |
| Plan-change vs Apply | YES | lock order preserves one valid linearized result |

Automated actual-process batch: 8 tests / 112 assertions PASS. PT/Chat/Workout probes dùng process PHP và DB connections riêng với file barrier. Không tạo probe hai loại service dị thể trong cùng race; actual-process Membership activation exact-once được dùng làm phương án tương đương được prompt cho phép, còn scenarios A–C kiểm tra no-reset xuyên service.

CRITICAL CONCURRENCY MATRIX = PASS.

## 19. Security

- **Mass assignment:** authority fields như Member/PT/sender/assignment/Plan/version/status/quota/timestamp/generated columns bị `prohibited` hoặc không nằm trong safe DTO; representative abuse trả 422.
- **Error leakage:** API business errors chỉ trả safe message/code/status; representative invalid/foreign/race cases không lộ SQL, stack, path hoặc model raw.
- **Secret leakage:** `.env` không tracked; tracked env files chỉ là examples/platform file. Scan chỉ gặp fake test payOS values; không có APP_KEY, DB password, payOS/Reverb/provider secret hay real Bearer token committed.
- **SQL/input:** Query Builder/Eloquent và validation được dùng; IDs numeric/ranges/UUID/text byte limit được kiểm tra; raw provider/LLM input không trở thành DB authority.
- **Logging:** không có `Log::`/`logger()` production path ghi token/password/raw payment/LLM payload.
- **N+1:** high-use Chat/Workout/AI list/query services có eager loading/bounded pagination; không tái hiện severe N+1.
- **Transactions:** payment, activation, QR, Direct, Chat, Workout và Apply có transaction/row locks; external AI/payment network work không bị giữ trong long DB transaction theo architecture hiện tại.

## 20. Database Integrity

Metadata trên schema đã migrate:

| Manifest | Thực tế | Kết quả |
| --- | --- | --- |
| 52 business tables / 575 columns | 53 / 578 khi tính thêm framework table `migrations` (3 columns) | PASS |
| 5 VIRTUAL generated columns | 5, đúng expression/nullable/UNIQUE | PASS |
| 98 UNIQUE | 98 | PASS |
| 144 FK | 144, toàn bộ `ON DELETE/UPDATE RESTRICT` | PASS |
| 83 business CHECK | 98 physical CHECK = 83 business + 15 MariaDB `JSON_VALID` | PASS |
| 95 technical string overrides + email | 96 `utf8mb4_nopad_bin` columns | PASS |

Composite ownership FK, nullable composite FK semantics, active-record generated UNIQUE, exact date slots, no Free Workout FK và history RESTRICT graph đều được đối chiếu. DATABASE CONSTRAINT AUDIT = PASS.

## 21. History Preservation

- **Payment:** attempts/events và filtered payload giữ nguyên; không hard-delete/rewrite khi provision/reconcile.
- **Membership:** registration/terms/usage là ledger riêng; future term không ghi đè current snapshot/counter.
- **PT:** old assignment và Direct history vẫn trỏ PT/assignment gốc sau reassign.
- **Chat:** old conversation/messages/outbox giữ nguyên và read-only sau assignment end.
- **AI:** request/provider-call/Proposal giữ content/hash/state history; Apply chỉ liên kết version mới.
- **Workout:** completed Session/materialized exercise/set values không đổi khi Plan v2/AI Apply tạo lịch tương lai.
- Metadata FK của các bảng history đều RESTRICT/RESTRICT; không thấy hard-delete business path làm mất ledger.

HISTORY PRESERVATION = PASS.

## 22. Bugs Found

**PRODUCTION BUGS FOUND/FIXED = 2.**

**TEST INFRASTRUCTURE BUGS FOUND/FIXED = 1.**

| Bug ID | Root cause | Fix | Regression evidence |
| --- | --- | --- | --- |
| FB-SEC-001 | Chat authorization chỉ đối chiếu historical participant nên PT cũ còn list/detail/messages/private-channel scope sau assignment end | Member giữ historical scope; PT bắt buộc exact assignment active theo `[start,end)` trong authorization và list query | Corrected Chat/broadcast/integration tests; exact end boundary; focused 49/708 PASS |
| FB-SEC-002 | `DatabaseSeeder` luôn gọi demo; direct demo seeder không fail closed; rerun đặt `thu_hoi_luc=NULL` cho role revoked | Demo/dataset chỉ local/testing; direct production guard trước mutation; revoked grant làm seeder fail trước mutation và không tạo audit giả | Fresh production-mode schema có 0 user/role/profile/exercise; direct demo exit 1; seeder security tests PASS |
| FB-TEST-001 | AI Apply/Chat/Workout process guards hard-code prefix module cũ nên chặn schema final hợp lệ dù schema vẫn là `smart_fitness_*test*`; baseline final ban đầu chỉ đạt 199/202 | Dùng regex anchored `\Asmart_fitness_[a-z0-9_]*test(?:_[a-z0-9_]+)?\z`, vẫn explicit reject `smart_fitness`; áp dụng chỉ trong test/support | AI Apply concurrency 3/38 PASS; actual Chat/Workout probes PASS; full final 206/2.966 PASS hai lượt |

Không sửa migration/logical schema; production source chỉ thay đổi đúng Chat authorization/query và hai seeder cần hardening.

## 23. Tests Added

Thêm `BE/tests/Feature/Integration/FinalBackendIntegrationTest.php` với bốn test:

1. Payment → AI Request → Proposal Apply → Workout, một Membership clock và không second charge.
2. Payment → Chat activation → PT Direct → Workout → reassignment, giữ old/new history/authorization.
3. Chat → Direct → AI → Workout trên cùng kỳ, ledger độc lập và future borrowing bị chặn.
4. Workout không Membership và foreign/mass-assignment history mutation bị chặn.
5. Q05: Member historical read giữ nguyên; old PT list/detail/messages/send/subscribe bị chặn; new PT chỉ truy cập conversation mới.
6. Seeder: production auto/direct demo bị chặn, local/testing idempotent và role revoked không bị tái cấp im lặng.

Không thêm migration, package hoặc schema production.

## 24. Test Results

| Gate | Kết quả cuối |
| --- | --- |
| New focused E2E, run 1 | 4 tests / 122 assertions — PASS |
| New focused E2E, run 2 | 4 tests / 122 assertions — PASS |
| Focused integration/security, run 1 | 171 tests / 2.142 assertions — PASS |
| Focused integration/security, run 2 | 171 tests / 2.142 assertions — PASS |
| Actual-process automated batch | 8 tests / 112 assertions — PASS |
| Manual PT assignment | 1 success + 1 `ASSIGNMENT_OVERLAP`; one active row — PASS |
| Manual PT Direct | 1 success + 1 `PT_QUOTA_EXHAUSTED`; used/history/usage=1 — PASS |
| Manual Chat activation | messages=2, outbox=2, Chat usage=1, usage pointer count=1 — PASS |
| Manual Workout Start | one create + one replay, Session count=1 — PASS |
| Full Backend run 1 | 206 tests / 2.966 assertions — PASS |
| Full Backend run 2 | 206 tests / 2.966 assertions — PASS |
| Random order seed `20260830` | 206 tests / 2.966 assertions — PASS |
| Q05/seeder focused sau correction | 49 tests / 708 assertions — PASS |
| Full Backend run 1 sau correction | 211 tests / 3.028 assertions — PASS |
| Full Backend run 2 sau correction | 211 tests / 3.028 assertions — PASS |
| Random order seed `20260830` sau correction | 211 tests / 3.028 assertions — PASS |
| Pint affected security-fix files | PASS — 8 files |
| PHP lint | 8/8 affected security-fix PHP files — PASS |
| Composer | `composer.json`/`composer.lock` unchanged |
| `git diff --check` | PASS |

Security minimum 15 nhóm nằm trong focused suite và đều PASS: missing/revoked/disabled/revoked-role, foreign Member/PT/Proposal/Session/Chat/channel, Admin private Chat block, authority field abuse và safe error contract.

## 25. Development DB Safety

`smart_fitness` modified: **NO**.

| Bảng | Before | After |
| --- | ---: | ---: |
| `nguoi_dung` | 8 | 8 |
| `phan_quyen_nguoi_dung` | 8 | 8 |
| 17 bảng operational/history còn lại được yêu cầu | 0 mỗi bảng | 0 mỗi bảng |

Các bảng 0 đã đối chiếu gồm registration/term/usage/payment/check-in/PT assignment/direct history/Chat/AI/Plan/version/schedule/Session/set/audit. Không chạy destructive command trên `smart_fitness`.

## 26. Cleanup

- Sáu schema `smart_fitness_final_backend_test_20260830_*`: **DROPPED**.
- Query `information_schema.SCHEMATA` sau cleanup: **0 rows**.
- `.tmp/final_backend_concurrency` chứa barrier/output/environment probe: **REMOVED**.
- Ba schema correction `smart_fitness_security_fix_test_20260830_main`, `_prod`, `_concurrency`: **DROPPED**; prefix query còn **0 rows**.
- `.tmp/security_fix_chat_concurrency*`: **REMOVED**.
- PowerShell jobs được remove; không còn process từ `.tools/php/php.exe`: **STOPPED/CLEAN**.
- Không dừng process PHP/Herd không thuộc task.

TEST DATABASE CLEANUP = PASS; TEMP FILE CLEANUP = PASS.

## 27. Deferred

| Hạng mục | Trạng thái |
| --- | --- |
| Actual browser/mobile WebSocket client E2E | DEFERRED TO CLIENT INTEGRATION |
| Automated Chat outbox retry worker | DEFERRED |
| Reverb production origin restriction/TLS/process deployment | DEPLOYMENT CONFIGURATION; private channel Backend auth PASS |
| PT Booking | NOT IMPLEMENTED |
| PT Proposal | DEFERRED |
| Dashboard | DEFERRED |
| FE | DEFERRED |
| Mobile | DEFERRED |

Không mục deferred nào được dùng để tuyên bố một feature chưa triển khai là PASS.

## 28. Files Changed

1. `BE/tests/Feature/Integration/FinalBackendIntegrationTest.php` — new final E2E/security tests.
2. `BE/tests/Feature/AiProposalApplyConcurrencyTest.php` — safe generic isolated-schema guard.
3. `BE/tests/Support/run_ai_proposal_apply.php` — same guard.
4. `BE/tests/Support/run_ai_plan_change.php` — same guard.
5. `BE/tests/Support/prepare_pt_chat_concurrency.php` — allow final isolated schema convention.
6. `BE/tests/Support/run_pt_chat_action.php` — allow final isolated schema convention.
7. `BE/tests/Support/prepare_workout_concurrency.php` — allow final isolated schema convention.
8. `BE/tests/Support/run_workout_action.php` — allow final isolated schema convention.
9. `docs/thiet_ke_co_so_du_lieu/FINAL_BACKEND_INTEGRATION_SECURITY_REPORT.md` — this report.
10. `BE/app/Services/Pt/Chat/PtChatAuthorizationService.php` — Q05 PT active-assignment read/subscribe scope.
11. `BE/app/Services/Pt/Chat/PtChatQueryService.php` — PT LIST chỉ gồm assignment active.
12. `BE/database/seeders/DatabaseSeeder.php` — production-safe seeder split.
13. `BE/database/seeders/DemoNguoiDungSeeder.php` — direct production guard và revoked-role preflight.
14. `BE/tests/Feature/PtChatApiTest.php`, `PtChatBroadcastAuthorizationTest.php`, `DemoSeederSecurityTest.php` — corrected security oracles.
15. `docs/thiet_ke_co_so_du_lieu/PT_REALTIME_CHAT_FOUNDATION_REPORT.md`, `CRITICAL_BACKEND_SECURITY_FIX_REPORT.md` — Q05 correction/evidence.

Production application files changed: **4**, đúng Chat authorization/query và seeder hardening. Database design/migrations changed: **NONE**. FE/Mobile changed: **NONE**.

## 29. Deviations

Expected: **NONE**.

Các điểm được phép và đã mô tả rõ: actual heterogeneous cross-module race không được tạo thêm vì actual-process Membership exact-once probe hiện có là phương án tương đương được prompt cho phép; browser/mobile WebSocket E2E tiếp tục ở client integration. Không hạ gate hoặc bỏ invariant.

## 30. Final Gate

```text
AUTHENTICATION = PASS
TOKEN REVOCATION = PASS
DISABLED ACCOUNT = BLOCKED
ROLE REVOCATION = BLOCKED

ROLE MATRIX = PASS
IDOR = PASS
MASS ASSIGNMENT = BLOCKED
SAFE ERROR CONTRACT = PASS
SECRET LEAKAGE = NONE

PAYMENT WEBHOOK IDEMPOTENCY = PASS
PAYMENT DOES NOT ACTIVATE MEMBERSHIP = PASS

MEMBERSHIP FIRST-USE ACTIVATION = PASS
MEMBERSHIP ACTIVATION EXACT-ONCE = PASS
MEMBERSHIP INTERVAL [start,end) = PASS
MEMBERSHIP QUEUE = PASS
FUTURE TERM BORROWING = BLOCKED
SNAPSHOT RIGHTS = PASS

GYM QR ONE-TIME = PASS
GYM QR ACTIVATION = PASS

AI REQUEST QUOTA = PASS
AI PROVIDER FAILURE REFUND = PASS
AI CANDIDATE VALIDATION = PASS
AI PROPOSAL IMMUTABILITY = PASS

AI APPLY LLM CALLS = 0
AI APPLY SECOND QUOTA = 0
AI APPLY USAGE = NONE
AI APPLY MEMBERSHIP ACTIVATION = BLOCKED
AI APPLY STALE PROTECTION = PASS
AI APPLY HISTORY PRESERVATION = PASS

PT ASSIGNMENT = PASS
PT ASSIGNMENT OVERLAP = BLOCKED
PT DIRECT QUOTA EXACT-ONCE = PASS
PT DIRECT FUTURE BORROW = BLOCKED

PT CHAT ASSIGNMENT BINDING = PASS
PT CHAT FIRST MEMBER ACTIVATION = PASS
PT CHAT PT-SEND ACTIVATION = BLOCKED
MEMBER PT CHAT HISTORY PRESERVED = PASS
OLD PT HISTORICAL READ = BLOCKED
OLD PT PRIVATE CHANNEL SUBSCRIBE = BLOCKED
NEW PT OLD CONVERSATION READ/SUBSCRIBE = BLOCKED
PT CHAT PRIVATE CHANNEL = PASS

WORKOUT ONE ACTIVE PLAN = PASS
WORKOUT PLAN VERSIONING = PASS
WORKOUT ONE VALID DATE SLOT = PASS
FREE WORKOUT = BLOCKED
ONE SESSION / SCHEDULED WORKOUT = PASS
COMPLETED SESSION IMMUTABLE = PASS
WORKOUT WITHOUT MEMBERSHIP = PASS
WORKOUT MEMBERSHIP ACTIVATION = BLOCKED

CROSS-MODULE E2E A = PASS
CROSS-MODULE E2E B = PASS
CROSS-MODULE E2E C = PASS
CROSS-MODULE E2E D = PASS

CRITICAL IDEMPOTENCY MATRIX = PASS
CRITICAL CONCURRENCY MATRIX = PASS

DATABASE CONSTRAINT AUDIT = PASS
HISTORY PRESERVATION = PASS

FULL BACKEND RUN 1 = PASS
FULL BACKEND RUN 2 = PASS
RANDOM ORDER = PASS
TEST ORDER INDEPENDENCE = PASS

DEVELOPMENT DATABASE = SAFE
TEST DATABASE CLEANUP = PASS
TEMP FILE CLEANUP = PASS
GIT DIFF CHECK = PASS

FINAL BACKEND INTEGRATION + SECURITY REGRESSION = PASS

BACKEND AUTHORIZATION = READY
BACKEND MEMBERSHIP LIFECYCLE = READY
BACKEND PAYMENT = READY
BACKEND GYM ACCESS = READY
BACKEND AI = READY
BACKEND PT = READY
BACKEND REALTIME CHAT FOUNDATION = READY
BACKEND WORKOUT = READY
BACKEND AI → WORKOUT APPLY = READY

BACKEND = READY FOR CLIENT INTEGRATION
```
