# AI PROPOSAL → APPLY WORKOUT PLAN REPORT

## 1. Scope

Đã triển khai foundation cho Member áp dụng một AI Proposal đã được lưu thành Workout Plan/Plan Version và lịch tập tương lai.

Phạm vi gồm endpoint Apply, ownership/IDOR, kiểm tra Proposal bất biến và TTL, stale base, revalidation candidate/dụng cụ, tạo Plan mới hoặc version kế tiếp, tái tạo lịch tương lai, idempotency, concurrency, rollback và audit.

Không gọi lại LLM, không trừ quota AI lần hai, không tạo `su_dung_quyen_loi`, không kích hoạt Membership, không sửa migration/schema, không triển khai FE/Mobile và không triển khai chức năng Workout mới ngoài việc tái sử dụng các Workout service hiện hành.

## 2. Environment

| Thành phần | Giá trị đã kiểm tra |
| --- | --- |
| PHP | 8.4.25 CLI, `E:\Fitness\.tools\php\php.exe` |
| Laravel | 13.29.0, khóa tại `composer.lock` |
| DBMS | MariaDB 10.4.32, `mariadb.org binary distribution` |
| Storage engine | InnoDB |
| Laravel connection `sql_mode` | `STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION` |
| Connection charset/collation | `utf8mb4` / `utf8mb4_unicode_ci` |
| Application timezone | UTC |
| Test schema cuối | `smart_fitness_ai_apply_test_20260830_final` |
| Schema probe/concurrency | `smart_fitness_ai_apply_test_20260830_a`, `_b`, `_c` |

M001-M060 và seeder hiện hành được chạy trên schema test cô lập. Không migrate, truncate hoặc chạy destructive test trên `smart_fitness`.

## 3. Architecture

Luồng Apply được tách theo trách nhiệm:

- `ApplyAiProposalRequest`: kiểm tra `Idempotency-Key` và chặn các authority field do client gửi;
- `AiProposalApplyService`: transaction, lock order, state/TTL/stale validation, idempotency và orchestration;
- `AiProposalApplyValidator`: integrity hash, source request/context, availability, candidate và equipment revalidation, sau đó ánh xạ payload canonical;
- `WorkoutPlanService`: tạo Plan/version snapshot dùng chung;
- `WorkoutScheduleService`: tái tạo lịch theo quy tắc bảo toàn lịch đã bắt đầu/kết thúc;
- `AiProposalAuditService`: audit đã lọc trong cùng transaction;
- `AiRequestController`: chỉ chuyển HTTP request/response.

Database là source of truth. Apply không nhận Plan content, Member ID, Plan ID, version ID, quota hoặc Membership term từ client.

## 4. Endpoint

| Thuộc tính | Giá trị |
| --- | --- |
| Method | `POST` |
| Path | `/api/assistant/proposals/{proposal}/apply` |
| Middleware | `auth:api`, `role:MEMBER` |
| Header bắt buộc | `Idempotency-Key: <UUID>` |
| Success | HTTP 200 |

Response đã lọc trả Proposal ID/status, Plan ID/status, current version ID/number/source/effective date, khoảng lịch 92 ngày, số lịch và cờ `replayed`. Response không trả prompt, structured context nội bộ, quota ledger hoặc dữ liệu nhạy cảm.

## 5. Proposal Validation

Apply chỉ chấp nhận Proposal:

- thuộc đúng Member đăng nhập và có `nguon_de_xuat = TRO_LY`;
- trạng thái `CHO_XAC_NHAN` hoặc `DA_AP_DUNG` cho natural replay;
- còn hiệu lực tại thời điểm transaction; `now >= het_han_luc` là hết hạn;
- có AI Request nguồn `THANH_CONG`, hạn mức đã `DA_TINH` và cùng Member;
- dùng schema `workout-proposal-v1`;
- giữ nguyên profile version, Plan-change marker, content hash, change type và effective date;
- vượt qua `AiStructuredOutputValidator` bằng context đã chốt.

`DA_TU_CHOI`, `XUNG_DOT` và `HET_HAN` bị chặn. Hết hạn được ghi terminal state `HET_HAN`; xung đột revalidation được ghi `XUNG_DOT`. Các thay đổi lifecycle này không sửa nội dung Proposal.

## 6. Proposal to Workout Mapping

Payload Proposal canonical được ánh xạ sang cấu trúc nội bộ của Workout service:

- tên Plan từ tiêu đề Proposal;
- mục tiêu và thời lượng từ hồ sơ Member hiện hành đã revalidate;
- `effective_from` từ Proposal;
- ngày tập, thứ tự bài, sets, reps và thời gian nghỉ từ structured Proposal;
- logical IDs mới do Backend tạo;
- source snapshot là `TRO_LY`, gắn `de_xuat_ke_hoach_tap_id` và lý do đã giới hạn độ dài.

Client không có quyền thay nội dung, source hoặc snapshot của version khi Apply.

## 7. Workout Service Reuse

Apply gọi trực tiếp:

- `WorkoutPlanService::taoMoi()` cho `TAO_MOI`;
- `WorkoutPlanService::taoPhienBanTiepTheo()` cho `DIEU_CHINH`/`THAY_BAI`;
- `WorkoutScheduleService::lapLich()` cho khoảng 92 ngày kể từ ngày hiệu lực.

`WorkoutPlanService` chỉ được mở rộng tham số source snapshot tùy chọn; call site cũ vẫn giữ mặc định `HOI_VIEN`. Không duplicate thuật toán tạo Plan, version, ngày, bài hoặc lịch.

## 8. Plan Versioning

| Trường hợp | Kết quả |
| --- | --- |
| `TAO_MOI`, chưa có active Plan, base IDs NULL | Tạo một Plan active và version 1 |
| `TAO_MOI` nhưng đã có active Plan | `AI_PLAN_STALE` |
| `DIEU_CHINH`/`THAY_BAI`, base đúng current version | Tạo version kế tiếp trên cùng Plan |
| Base Plan/version đã đổi | `AI_PLAN_STALE` |

Version cũ không bị update. Test so sánh nguyên trạng snapshot ngày/bài của version cũ sau Apply. Unique schema và lock Member/Plan tiếp tục bảo vệ một active Plan cho mỗi Member.

## 9. Schedule Regeneration

Lịch được tạo lại từ `effective_from` đến `effective_from + 91 ngày` bằng `WorkoutScheduleService`.

- lịch tương lai `CHUA_TAP` có thể được thay;
- lịch `DANG_TAP`, `HOAN_THANH` và `BO_QUA` được giữ nguyên;
- slot mới tuân thủ unique một lịch còn hiệu lực cho cùng Member/ngày;
- lịch/version/Plan mới cùng owner được kiểm tra bằng schema và service hiện hành.

## 10. History Preservation

Focused test chụp snapshot trước Apply và xác nhận không thay đổi:

- completed Workout Session;
- in-progress Workout Session được gắn với lịch `DANG_TAP`;
- `bai_tap_trong_phien` của các Session được bảo vệ;
- toàn bộ `hiep_tap` đã ghi;
- lịch `DANG_TAP`, `HOAN_THANH`, `BO_QUA`;
- version cũ cùng cấu trúc ngày/bài.

Apply không update hoặc delete Workout history tables.

## 11. Idempotency

Scope idempotency là `AP_DUNG_DE_XUAT_TRO_LY`, lưu tại `yeu_cau_chong_lap` theo user + UUID key và hash Proposal ID.

- cùng Proposal/cùng key: trả cùng business result với `replayed = true`;
- cùng key nhưng khác Proposal: bị `IDEMPOTENCY_CONFLICT` khi key được đối chiếu;
- Proposal đã `DA_AP_DUNG`: natural replay đọc version kết quả đã tồn tại, không tạo mutation mới;
- response idempotency chỉ lưu DTO đã lọc;
- bản ghi chuyển `DANG_XU_LY` → `DA_HOAN_TAT` trong cùng transaction với business mutation.

Sequential replay chỉ có một Plan/version, một marker increment và một audit thành công.

## 12. Stale Protection

Stale protection được kiểm tra lại dưới lock tại thời điểm Apply:

- exact active Plan/current version phải trùng Proposal base;
- profile version và `moc_thay_doi_ke_hoach` phải trùng snapshot;
- ngày rảnh phải trùng context đã chốt;
- danh sách dụng cụ Member và trạng thái catalog phải còn hợp lệ;
- từng exercise phải là candidate của exact AI Request, còn active và đúng content version;
- mọi dụng cụ bắt buộc của exercise phải nằm trong tập dụng cụ Member theo AND semantics.

Hai Proposal cùng base chỉ có một winner. Plan-change transaction thắng lock khiến Apply chờ rồi re-read và trả `AI_PLAN_STALE`.

## 13. Membership and Quota

| Invariant | Kết quả đo |
| --- | --- |
| Provider/LLM calls trong Apply | 0 |
| AI quota charge lần hai | 0 |
| `su_dung_quyen_loi` do Apply tạo | 0 |
| Membership activation do Apply | 0 |
| PT direct-session quota thay đổi | 0 |
| PT Chat usage/quota thay đổi | 0 |

Membership có thể hết hạn sau khi Proposal được tạo mà Apply vẫn dùng Proposal đã được charge trước đó, nếu Proposal còn hiệu lực và không stale. Apply không vay quyền kỳ sau và không đọc catalog live để tự cấp quyền.

## 14. Audit

Một audit `AP_DUNG_DE_XUAT_TRO_LY` được tạo đúng một lần trong cùng transaction với mutation thành công.

Audit chỉ chứa actor, Proposal ID, base Plan/version, result Plan/version, correlation key và trạng thái trước/sau. Không lưu prompt, structured output, candidate payload, secret hoặc quota internals.

Late audit failure test chứng minh toàn bộ Plan, version, schedule, Proposal status, Member marker và idempotency record được rollback.

## 15. Security

- unauthenticated request bị 401;
- non-MEMBER role bị chặn;
- Proposal foreign owner được che bằng 404, chống IDOR;
- account, current MEMBER role và Member profile được revalidate dưới lock;
- request chặn Member/user/Proposal/request/Plan/version/base/status/change/effective/content/structured/candidate/quota/Membership authority fields;
- route parameter chỉ dùng để lookup trong owner scope;
- lỗi trả safe code/message, không lộ nội dung Proposal hoặc exception SQL.

## 16. Atomicity

Proposal lock, source/context validation, idempotency, Plan/version creation, schedule regeneration, marker increment, Proposal transition và audit nằm trong một outer database transaction. Nested Workout service transactions dùng cùng connection và không phá atomicity.

Business conflict cần lưu terminal state được trả ra khỏi transaction bằng result sentinel rồi mới ném safe exception sau commit. Technical/late failure làm rollback toàn bộ mutation.

## 17. Concurrency

Ba probe dùng process PHP và connection MariaDB độc lập, trên dữ liệu đã commit:

| Probe | Kết quả |
| --- | --- |
| Hai process Apply cùng Proposal | Cả hai nhận success/replay; chỉ một business mutation, một version và một audit |
| Hai Proposal cùng base version | Chỉ một winner; loser stale; không tạo version/audit thứ hai |
| Plan-change giữ lock rồi Apply | Apply chờ lock, đọc current version mới và bị stale |

Focused concurrency result: **3 tests, 38 assertions, PASS**. Lock order chính là Member → account/role → Proposal → source request → các Plan → availability/equipment/candidate → idempotency/mutation.

## 18. Tests

| Gate | Kết quả |
| --- | --- |
| Focused Apply + concurrency | **12 tests, 187 assertions, PASS** |
| Full Backend run 1 | **202 tests, 2,844 assertions, PASS** |
| Full Backend run 2 | **202 tests, 2,844 assertions, PASS** |
| Random order, seed `20260830` | **202 tests, 2,844 assertions, PASS** |
| Baseline trước task | 190 tests, 2,657 assertions |
| Test tăng thêm | 12 tests, 187 assertions |
| Pint `--test`, chỉ affected PHP files | PASS |
| PHP syntax lint, 12 affected PHP files | PASS |
| `git diff --check` | PASS |

Focused coverage gồm no-Plan Apply, existing Plan → next version, state/TTL exact boundary, integrity/context/candidate/equipment stale, role/IDOR/mass assignment, history preservation, sequential idempotency, actual-process concurrency và atomic rollback.

## 19. Development Database Safety

Database `smart_fitness` chỉ được query read-only trước và sau test. Các bảng sau giữ nguyên count `0`: `yeu_cau_tro_ly`, `de_xuat_ke_hoach_tap`, `ke_hoach_tap`, `phien_ban_ke_hoach_tap`, `ngay_trong_ke_hoach`, `bai_tap_trong_ke_hoach`, `buoi_tap_du_kien`, `phien_tap`, `bai_tap_trong_phien`, `hiep_tap`, `su_dung_quyen_loi`, `nhat_ky_he_thong`.

Đã drop toàn bộ bốn schema `smart_fitness_ai_apply_test_20260830_*` do task tạo. Query `information_schema.SCHEMATA` sau cleanup trả 0 dòng. Không còn barrier file `ai_apply_*` hoặc `ai_plan_*` trong thư mục temp.

## 20. Deferred

| Phạm vi | Trạng thái |
| --- | --- |
| PT Proposal | DEFERRED |
| PT Proposal Apply | DEFERRED |
| Manual Plan Editor | DEFERRED |
| FE | DEFERRED |
| Mobile | DEFERRED |
| Dashboard | DEFERRED |

Free Workout, provider retry mới, background Apply và notification/push cũng nằm ngoài task này.

## 21. Files Changed

### Modified

- `BE/app/Http/Controllers/Api/Ai/AiRequestController.php`
- `BE/app/Services/Workout/WorkoutPlanService.php`
- `BE/routes/api.php`
- `BE/tests/Fakes/FakeWorkoutAiProvider.php`

### Added

- `BE/app/Http/Requests/Ai/ApplyAiProposalRequest.php`
- `BE/app/Services/Ai/AiProposalApplyService.php`
- `BE/app/Services/Ai/AiProposalApplyValidator.php`
- `BE/app/Services/Ai/AiProposalAuditService.php`
- `BE/tests/Feature/AiProposalApplyConcurrencyTest.php`
- `BE/tests/Feature/AiProposalApplyWorkoutPlanTest.php`
- `BE/tests/Support/run_ai_plan_change.php`
- `BE/tests/Support/run_ai_proposal_apply.php`
- `docs/thiet_ke_co_so_du_lieu/AI_PROPOSAL_APPLY_WORKOUT_PLAN_REPORT.md`

Không sửa migration, schema design, data dictionary, ERD, PROJECT_RULES, FE hoặc Mobile.

## 22. Deviations

**NONE.**

Test fake đổi provider request ID sang giá trị unique để nhiều fixture đã commit có thể chạy trên cùng schema mà không vi phạm unique vật lý của provider reference. Thay đổi này chỉ làm fake phản ánh đúng tính duy nhất của provider request, không đổi business rule hoặc production workflow.

## 23. Final Gate

```text
AI PROPOSAL APPLY API = PASS
PROPOSAL OWNERSHIP = PASS
PROPOSAL IMMUTABLE = PASS
TTL ENFORCEMENT = PASS
EXACT EXPIRY BOUNDARY = PASS
PROPOSAL STATE VALIDATION = PASS
STALE BASE VERSION = BLOCKED
CANDIDATE REVALIDATION = PASS
EQUIPMENT REVALIDATION = PASS
LLM CALL DURING APPLY = 0
AI QUOTA SECOND CHARGE = 0
APPLY USAGE CREATION = NONE
MEMBERSHIP ACTIVATION DURING APPLY = BLOCKED
NO-PLAN APPLY = PASS
EXISTING-PLAN → NEXT VERSION = PASS
OLD PLAN VERSION IMMUTABLE = PASS
ONE ACTIVE PLAN / MEMBER = PASS
FUTURE SCHEDULE REGENERATION = PASS
ONE VALID SCHEDULE / MEMBER / DATE = PASS
DANG_TAP SCHEDULE PRESERVED = PASS
COMPLETED SCHEDULE PRESERVED = PASS
SKIPPED SCHEDULE PRESERVED = PASS
COMPLETED SESSION IMMUTABLE = PASS
SESSION EXERCISE HISTORY IMMUTABLE = PASS
SET HISTORY IMMUTABLE = PASS
SEQUENTIAL APPLY IDEMPOTENCY = PASS
SAME PROPOSAL CONCURRENCY = PASS
TWO PROPOSALS SAME BASE CONCURRENCY = PASS
PLAN CHANGE / APPLY STALE PROTECTION = PASS
ATOMIC ROLLBACK = PASS
AUDIT = PASS
IDOR = PASS
MASS ASSIGNMENT = BLOCKED
FULL BACKEND TEST SUITE = PASS
TEST ORDER INDEPENDENCE = PASS
DEVELOPMENT DB = SAFE
TEST DATABASE CLEANUP = PASS
GIT DIFF CHECK = PASS

AI PROPOSAL → APPLY WORKOUT PLAN FOUNDATION = PASS

AI PROPOSAL APPLY API = IMPLEMENTED
PROPOSAL IMMUTABILITY = PRESERVED
PROPOSAL TTL / STALE VALIDATION = IMPLEMENTED
AI PROPOSAL → WORKOUT PLAN VERSION = IMPLEMENTED
FUTURE SCHEDULE REGENERATION = IMPLEMENTED
WORKOUT HISTORY PRESERVATION = PASS
APPLY IDEMPOTENCY / CONCURRENCY = PASS
LLM DURING APPLY = NOT CALLED
AI QUOTA DURING APPLY = NOT CONSUMED
MEMBERSHIP ACTIVATION DURING APPLY = NOT PERFORMED
DATABASE = READY FOR FINAL BACKEND INTEGRATION
```
