# PT REALTIME CHAT FOUNDATION REPORT

## 1. Scope

Đã triển khai foundation Chat realtime 1-1 giữa Member và PT cho Laravel Backend:

- vòng đời hội thoại gắn với đúng một `phan_cong_huan_luyen_vien`;
- API lấy danh sách, chi tiết, lịch sử phân trang, resolve hội thoại hiện tại và gửi tin;
- kiểm tra participant, phân công và quyền Chat theo snapshot Membership;
- kích hoạt Membership chỉ bằng tin Member hợp lệ đầu tiên;
- lưu message, usage kích hoạt và outbox trong một transaction;
- phát sự kiện sau commit qua private channel Laravel Reverb;
- idempotency theo client message ID, bảo vệ concurrency và IDOR.

Không tạo/sửa migration, bảng, cột, CHECK, UNIQUE hoặc FK. Không triển khai PT Booking, attachment, presence, push notification, AI Chat, PT Proposal, Workout, FE hoặc Mobile.

## 2. Environment

| Thành phần | Giá trị đã kiểm tra |
| --- | --- |
| PHP | 8.4.25 CLI, `E:\Fitness\.tools\php\php.exe` |
| Laravel | 13.29.0 |
| DBMS | MariaDB 10.4.32, InnoDB |
| Laravel test connection `sql_mode` | `STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION` |
| Test schema chính | `smart_fitness_chat_test_20260830_final` |
| Concurrency schemas | `smart_fitness_chat_test_20260830_conv`, `_activation`, `_samekey`, `_assignrace` |
| Reverb | Official `laravel/reverb` v1.11.1 |

M001-M060 hiện hành được chạy trên schema test mới; seeder hoàn tất trước regression. Không migrate `smart_fitness`.

## 3. Chat Architecture

**HTTP persistence:** REST API là đường ghi business. `PtChatMessageService::gui()` revalidate và commit dữ liệu trước khi gọi transport.

**Realtime:** `PtChatMessageSent` implements `ShouldBroadcastNow` và phát trên Laravel Reverb sau khi transaction trả về thành công.

**Database source of truth:** **YES**. Client reconnect hoặc bỏ lỡ event lấy lại tin từ API history theo sequence cursor.

Phân tách trách nhiệm:

- `PtChatAuthorizationService`: participant/role/account authorization;
- `PtChatConversationService`: resolve hoặc lazy-create conversation theo assignment;
- `PtChatQueryService`: DTO an toàn và pagination;
- `PtChatMessageService`: send authorization, activation, idempotency và transaction;
- `PtChatDeliveryService`: phát event sau commit và cập nhật delivery ledger;
- `PtChatController`: chuyển HTTP request/response, không chứa workflow nghiệp vụ.

## 4. Conversation Model

| Nội dung | Kết quả |
| --- | --- |
| Conversation table | `hoi_thoai` |
| Assignment link | `hoi_thoai.phan_cong_huan_luyen_vien_id` |
| Participant tuple | `hoi_vien_id` + `huan_luyen_vien_id` được lấy từ assignment |
| Một assignment → conversation | UNIQUE schema + khóa Member/assignment + lookup dưới transaction |
| Reassignment | Tạo/resolve conversation mới cho assignment mới |
| Historical conversation | Giữ nguyên, không đổi FK hoặc chuyển message |

Member/PT không được tạo hội thoại bằng participant ID tùy ý. Member không được gửi `assignment_id`; PT chỉ được chọn assignment thuộc chính hồ sơ PT của mình.

## 5. Endpoints

| Method | Path | Role | Purpose |
| --- | --- | --- | --- |
| GET | `/api/pt/chat/conversations` | MEMBER, PT | Danh sách hội thoại trong participant scope |
| POST | `/api/pt/chat/conversations/current` | MEMBER, PT | Resolve/lazy-create theo assignment đã xác minh |
| GET | `/api/pt/chat/conversations/{conversation}` | MEMBER, PT | Chi tiết hội thoại |
| GET | `/api/pt/chat/conversations/{conversation}/messages` | MEMBER, PT | Lịch sử theo `before_sequence`, limit 1..100 |
| POST | `/api/pt/chat/conversations/{conversation}/messages` | MEMBER, PT | Gửi text message idempotent |
| GET/POST | `/api/broadcasting/auth` | MEMBER, PT | Authorize private channel bằng Bearer token hiện hành |

Unauthenticated trả 401; Admin/Receptionist không có quyền Chat; foreign participant được che giấu bằng 404 theo convention hiện tại.

## 6. Channel

| Thuộc tính | Kết quả |
| --- | --- |
| Channel type | **PRIVATE** |
| Pattern | `pt.conversation.{hoiThoaiId}` |
| Broadcast event alias | `pt.chat.message.sent` |
| Authorization | Member participant được đọc lịch sử; PT phải thuộc exact assignment đang hiệu lực |
| Custom `AccessTokenGuard` | **PASS** |

`route:list -v` xác nhận broadcast auth middleware `api`, `auth:api`, `role:MEMBER,PT`. `channel:list` xác nhận một private channel đúng pattern. Channel auth không kiểm tra entitlement gửi và không kích hoạt Membership.

## 7. Send Authorization

**Member:** phải là Member của conversation, account/role còn hiệu lực, exact assignment còn hiệu lực theo `[start,end)`, PT còn hoạt động và đúng snapshot Membership hiện hành có quyền Chat.

**PT:** phải là PT của exact assignment, account/role/profile còn hoạt động, Member còn hoạt động, assignment còn hiệu lực và Member có quyền Chat hiện hành. PT send không gọi activation và không tạo usage Chat.

Mỗi send đọc lại database dưới khóa theo thứ tự Member → các assignment của Member → conversation → PT → Membership chain/term. Clock dùng để kiểm tra biên được lấy sau khi đã chờ khóa participant/assignment, tránh dùng thời điểm stale khi request bị chặn bởi transaction khác.

Các ca account Member bị khóa, role PT bị thu hồi và profile PT ngừng nhận phân công đều bị chặn trước insert message.

## 8. Chat Entitlement

| Nội dung | Kết quả |
| --- | --- |
| Snapshot field | `ky_han_hoi_vien.cho_phep_tro_chuyen_huan_luyen_vien` |
| Future borrowing | **NO** |
| Merge nhiều kỳ/gói | **NO** |
| Live package catalog | Không dùng để authorize kỳ đã mua |
| Direct PT quota dependency | **NO** |

Test xác nhận Chat=true với direct quota=0 vẫn gửi được; Chat=false với direct quota=10 bị chặn. Kỳ hiện tại Chat=false không được mượn quyền Chat=true của kỳ `CHO_DEN_LUOT`.

## 9. Membership Activation

| Action | Có kích hoạt? |
| --- | --- |
| Member first eligible send | **YES** |
| PT send | **NO** |
| Member/PT GET history | **NO** |
| Channel auth / subscribe / reconnect | **NO** |

Tin Member thực sự thắng activation:

1. tạo đúng một `su_dung_quyen_loi.loai_su_dung = TRO_CHUYEN_HUAN_LUYEN`;
2. gọi `MembershipActivationService` trong cùng transaction;
3. lưu usage ID vào đúng `tin_nhan.su_dung_quyen_loi_id` của tin gây kích hoạt;
4. dùng usage đó làm `dang_ky_goi_tap.lan_su_dung_dau_tien_id`.

Tin Member tiếp theo, mọi tin PT và tin đầu sau khi AI/QR/PT đã kích hoạt đều có usage pointer NULL. Chuỗi 1 tin kích hoạt + 20 tin Member + 20 tin PT vẫn chỉ có một usage Chat và không trừ direct PT quota.

## 10. Reassignment

| Case | Kết quả |
| --- | --- |
| Old conversation | **READABLE** cho Member; PT cũ mất toàn bộ resource scope |
| Old conversation send | **BLOCKED** khi assignment hết hiệu lực |
| New assignment | Conversation mới, ID khác |
| Member | Thấy history cũ và conversation hiện tại |
| Old PT | Không list/read/send/subscribe sau khi assignment kết thúc |
| New PT | Không đọc/subscribe conversation của PT cũ |

Không mutate conversation cũ, không rewrite sender, không chuyển/xóa message cũ.

## 11. Message Persistence

Trong một transaction:

- khóa/revalidate participant, assignment và Membership;
- tùy trường hợp tạo usage và kích hoạt;
- cấp `so_thu_tu` dưới khóa conversation;
- tạo `tin_nhan`;
- tạo `su_kien_phat_tin_nhan` trạng thái `CHO_PHAT`;
- cập nhật `hoi_thoai.so_thu_tu_cuoi`.

**Broadcast after commit:** **YES**. Test không bọc outer transaction xác nhận callback transport nhìn thấy message, outbox và Membership `DANG_HOAT_DONG` khi `transactionLevel() = 0`.

**Broadcast failure rollback message:** **NO**.

**Broadcast failure rollback activation/usage:** **NO**.

Lỗi lưu `tin_nhan` sau bước activation giả lập bằng payload vượt giới hạn vật lý TEXT dưới strict mode đã rollback message, usage, activation và outbox cùng transaction.

## 12. `su_kien_phat_tin_nhan`

Đây là delivery/outbox ledger theo schema đã khóa:

- tạo atomically với message ở `CHO_PHAT`, `so_lan_thu = 0`;
- dispatch thành công → `DA_PHAT`, tăng attempt, gán `phat_luc`;
- dispatch lỗi → `CHO_THU_LAI`, tăng attempt, gán `thu_lai_luc` và lỗi rút gọn;
- UNIQUE theo message bảo đảm một business message có một event row.

`PtChatDeliveryService::phat()` có thể xử lý lại event chưa `DA_PHAT`; module này chưa thêm scheduler/queue worker retry. Trong lúc transport lỗi, HTTP history là kênh recovery dữ liệu đáng tin cậy. Không invent thêm trạng thái ngoài schema.

Client-message idempotency dùng UNIQUE `(hoi_thoai_id, nguoi_gui_id, ma_tin_nhan_phia_gui)`: cùng key + cùng content trả lại message cũ; cùng key + content khác trả 409; không tạo thêm usage hoặc outbox.

## 13. Realtime

| Kiểm tra | Kết quả |
| --- | --- |
| `reverb:start --help` | PASS |
| Config load với test credentials | PASS: `reverb|reverb|127.0.0.1|18080|pt-chat-config-test` |
| Server startup loopback | **PASS** |
| Startup evidence | PID test còn sống, bind `127.0.0.1:18080`, log `Starting server on 127.0.0.1:18080` |
| Process/port cleanup | PASS |
| Actual WebSocket client E2E | **NOT PERFORMED** |
| Event contract/fake test | PASS |
| HTTP broadcast auth integration | PASS, 1 test / 22 assertions |

Không gọi internet, không dùng credential thật. Startup smoke, event contract và HTTP channel authorization là ba lớp kiểm tra riêng; report không coi chúng là một WebSocket client E2E.

## 14. Security

| Control | Kết quả |
| --- | --- |
| Conversation/message IDOR | PASS |
| Private channel | PASS |
| Foreign Member/PT | BLOCKED |
| Old PT đọc/subscribe conversation cũ | BLOCKED |
| New PT đọc conversation cũ | BLOCKED |
| Admin/Receptionist Chat access | BLOCKED |
| Client-supplied sender/member/PT/assignment/usage/status | BLOCKED |
| Safe HTTP DTO | PASS |
| Safe event allow-list | PASS |

Event payload chỉ gồm `conversation_id`, `message_id`, `sequence`, `sender_id`, `sender_type`, `content`, `sent_at`. Không có token, email, payment, Membership internals, model thô hoặc outbox internals. Text được trả như plain data; Backend không thực thi HTML/JS từ message.

## 15. Concurrency

Tất cả probe dùng hai PHP process và hai database connection độc lập, đồng bộ bằng barrier file.

| Probe | Evidence | Result |
| --- | --- | --- |
| Lazy conversation creation | Hai process cùng trả `conversation_id=1`; DB có 1 conversation/1 assignment | PASS |
| Hai first Member messages | 2 messages + 2 outbox; đúng 1 Chat usage, 1 message pointer, first-use pointer=1, sequence cuối=2 | PASS |
| Same client key | Một process new, một replay; cùng message ID; DB 1 message/1 outbox/1 usage | PASS |
| Assignment end/send race | Send tuyến tính hóa trước end: `gui_luc=01:47:49.606706` < `ngay_ket_thuc=01:47:49.637715`; không có message sau end | PASS |

Exact assignment end và Membership end còn được kiểm tra riêng: `end - 1 microsecond` được phép, `now == end` bị chặn.

## 16. History

| Case | HTTP history | New send |
| --- | --- | --- |
| Assignment kết thúc/reassign | Member vẫn READABLE; PT cũ/new PT bị BLOCKED | BLOCKED |
| Chat entitlement/Membership hết hạn | READABLE | BLOCKED nếu không có term áp dụng mới |

History query không kích hoạt, không tạo usage và không yêu cầu quyền gửi hiện tại. Pagination dùng sequence cursor ổn định, giới hạn tối đa 100, không trả truy vấn không giới hạn.

## 17. Tests

| Nhóm | Kết quả |
| --- | --- |
| Focused Chat | **PASS — 22 tests / 409 assertions** |
| Broadcast auth riêng | **PASS — 1 test / 22 assertions** |
| Actual-process concurrency | **PASS — 4/4 probes** |
| Reverb config/startup | **PASS** |
| Full Backend run 1 | **PASS — 178 tests / 2,540 assertions** |
| Full Backend run 2 | **PASS — 178 tests / 2,540 assertions** |
| Random seed 20260830 | **PASS — 178 tests / 2,540 assertions** |
| Pint affected Chat files | **PASS — 22 files** |
| PHP syntax lint | **PASS — 22 files** |
| Composer validate strict | PASS |
| `git diff --check` | PASS |

Baseline trước module là 156 tests / 2,131 assertions. Kết quả mới tăng đúng 22 tests / 409 assertions; không có test bị mất. Hai full run và random-order cùng PASS chứng minh test-order independence trong phạm vi suite hiện hành.

## 18. Development DB Safety

`smart_fitness` chỉ được đọc count trước/sau:

| Bảng | Trước | Sau |
| --- | ---: | ---: |
| `hoi_thoai` | 0 | 0 |
| `tin_nhan` | 0 | 0 |
| `su_kien_phat_tin_nhan` | 0 | 0 |
| `su_dung_quyen_loi` | 0 | 0 |

**smart_fitness modified:** **NO**.

Đã drop chính xác sáu schema `smart_fitness_chat_test_20260830_*`. Truy vấn `information_schema.SCHEMATA` sau cleanup không còn schema khớp pattern. Đã xóa `.tmp/chat_concurrency`, `.tmp/reverb_smoke`; không còn listener Reverb ở port 18080.

**Cleanup:** **PASS**.

## 19. Deferred

- Attachments: DEFERRED.
- Typing indicator: DEFERRED.
- Presence: DEFERRED.
- Read receipts/counters: DEFERRED.
- Push notification: DEFERRED.
- Automated outbox retry worker/scheduler: DEFERRED.
- PT Booking: NOT IMPLEMENTED.
- PT AI Proposal: DEFERRED.
- Workout Plan/Session: DEFERRED.
- FE: DEFERRED.
- Mobile: DEFERRED.

## 20. Files Changed

### Modified

- `BE/.env.example`
- `BE/bootstrap/app.php`
- `BE/composer.json`
- `BE/composer.lock`
- `BE/routes/api.php`

### Created

- `BE/app/Events/PtChatMessageSent.php`
- `BE/app/Exceptions/Chat/PtChatWorkflowException.php`
- `BE/app/Http/Controllers/Api/Pt/PtChatController.php`
- `BE/app/Http/Requests/Pt/ListPtChatMessagesRequest.php`
- `BE/app/Http/Requests/Pt/ResolveCurrentPtChatRequest.php`
- `BE/app/Http/Requests/Pt/SendPtChatMessageRequest.php`
- `BE/app/Services/Pt/Chat/PtChatAuthorizationService.php`
- `BE/app/Services/Pt/Chat/PtChatConversationService.php`
- `BE/app/Services/Pt/Chat/PtChatDeliveryService.php`
- `BE/app/Services/Pt/Chat/PtChatMessageService.php`
- `BE/app/Services/Pt/Chat/PtChatQueryService.php`
- `BE/config/broadcasting.php`
- `BE/config/reverb.php`
- `BE/routes/channels.php`
- `BE/tests/Concerns/CreatesPtChatFixtures.php`
- `BE/tests/Feature/PtChatApiTest.php`
- `BE/tests/Feature/PtChatBroadcastAuthorizationTest.php`
- `BE/tests/Feature/PtChatCommitDeliveryTest.php`
- `BE/tests/Support/prepare_pt_chat_concurrency.php`
- `BE/tests/Support/run_pt_chat_action.php`
- `docs/thiet_ke_co_so_du_lieu/PT_REALTIME_CHAT_FOUNDATION_REPORT.md`

Không có migration, Model, Seeder, Factory, FE hoặc Mobile file nào bị sửa. `.env` không bị sửa và không có secret thật được commit.

## 21. Deviations

**NONE** đối với logical schema và business rule được yêu cầu.

Giới hạn được báo cáo rõ, không coi là đã thực hiện: actual WebSocket client E2E chưa chạy; automated outbox retry worker chưa nằm trong foundation này. HTTP history vẫn là database-backed recovery khi realtime transport bị lỡ.

## 22. Final Gate

| Gate | Status |
| --- | --- |
| PT CHAT CONVERSATION API | PASS |
| PT CHAT MESSAGE API | PASS |
| CONVERSATION BOUND TO ASSIGNMENT | PASS |
| REASSIGNMENT CREATES NEW CONVERSATION | PASS |
| MEMBER OLD CHAT HISTORY PRESERVED / READABLE | PASS |
| OLD PT HISTORICAL READ / SUBSCRIBE | BLOCKED |
| OLD CONVERSATION SEND | BLOCKED |
| CHAT ENTITLEMENT | PASS |
| PT DIRECT QUOTA INDEPENDENT | PASS |
| MEMBER FIRST MESSAGE ACTIVATION | PASS |
| ACTIVATION MESSAGE USAGE POINTER | PASS |
| SUBSEQUENT MESSAGE USAGE | NONE |
| PT SEND ACTIVATION | BLOCKED |
| MEMBER/PT READ ACTIVATION | BLOCKED |
| SUBSCRIBE ACTIVATION | BLOCKED |
| ASSIGNMENT REAUTHORIZATION PER SEND | PASS |
| ENTITLEMENT REAUTHORIZATION PER SEND | PASS |
| PRIVATE CHANNEL | PASS |
| CHANNEL OWNERSHIP / IDOR | PASS |
| MESSAGE PERSIST BEFORE BROADCAST | PASS |
| BROADCAST AFTER COMMIT | PASS |
| BROADCAST FAILURE BUSINESS ROLLBACK | BLOCKED |
| FIRST-MESSAGE ACTIVATION CONCURRENCY | PASS |
| CONVERSATION DUPLICATION CONCURRENCY | PASS |
| CLIENT MESSAGE IDEMPOTENCY | PASS |
| REAL LLM | NOT INVOLVED |
| PT BOOKING | NOT IMPLEMENTED |
| FULL BACKEND TEST SUITE | PASS |
| TEST ORDER INDEPENDENCE | PASS |
| DEVELOPMENT DB | SAFE |
| TEST DATABASE CLEANUP | PASS |
| REVERB PROCESS CLEANUP | PASS |

**PT REALTIME CHAT FOUNDATION = PASS**

**PT CHAT HTTP API = IMPLEMENTED**

**PT CHAT PRIVATE REALTIME CHANNEL = IMPLEMENTED**

**PT CHAT MEMBERSHIP ACTIVATION = IMPLEMENTED**

**PT CHAT HISTORY PRESERVATION = IMPLEMENTED**

**PT CHAT REASSIGNMENT ISOLATION = IMPLEMENTED**

**PT CHAT REALTIME FOUNDATION = IMPLEMENTED**

**DATABASE = READY FOR WORKOUT FOUNDATION**

Recommended next step: **WORKOUT PLAN + WORKOUT SESSION FOUNDATION**. Không bắt đầu trong nhiệm vụ này.

## 23. Post-audit correction — Q05 historical PT scope

Foundation report ban đầu đã dùng oracle sai khi cho PT cũ đọc lịch sử. Theo `PROJECT_RULES.md` Q05, quyền lịch sử không đối xứng:

- Member historical read: **YES**; list/detail/messages và reconnect private channel không kích hoạt Membership.
- Old PT sau `ngay_ket_thuc`: **NO** cho list/detail/messages/send/subscribe.
- New PT: **NO** đối với conversation cũ; chỉ đọc/subscribe conversation mới của exact assignment đang hiệu lực.
- Biên hiệu lực PT là `[ngay_bat_dau, ngay_ket_thuc)`: trước end được đọc, tại end bị chặn.

Correction đã được kiểm chứng trong Critical Backend Security Fix: focused Chat/security **49 tests / 708 assertions**, full Backend hai lượt và random seed `20260830` đều **211 tests / 3.028 assertions**. Conversation/message cũ vẫn được lưu nguyên vẹn; thay đổi chỉ siết resource scope của PT.
