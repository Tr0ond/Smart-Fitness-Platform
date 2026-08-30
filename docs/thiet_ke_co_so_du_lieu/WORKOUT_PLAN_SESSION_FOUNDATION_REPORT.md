# WORKOUT PLAN + WORKOUT SESSION FOUNDATION REPORT

## 1. Scope

Đã triển khai Backend foundation cho catalog giáo án chỉ đọc, Plan self-read, Plan/version service nội bộ, schedule cụ thể, Workout Session, materialized exercise snapshot, set thực tế, hoàn thành bất biến, lịch sử self-owned và các hàng rào concurrency/IDOR.

Không triển khai AI Proposal Apply, PT Proposal, PT Booking, Free Workout, Plan mutation HTTP thủ công, nutrition, medical, analytics, FE hoặc Mobile. Không sửa migration, Model, Seeder, Factory hay logical schema.

## 2. Environment

| Thành phần | Giá trị đã kiểm tra |
| --- | --- |
| PHP | 8.4.25 CLI — `E:\Fitness\.tools\php\php.exe` |
| Laravel | 13.29.0 |
| DB | MariaDB 10.4.32, InnoDB |
| Test schemas | `smart_fitness_workout_test_20260830_main`, `smart_fitness_workout_test_20260830_regression` |
| Migrations | M001–M060 hiện hành, không sửa |
| Seeder | Seeder hiện hành; 1,324 exercises / 28 equipment / 50 muscle groups |

Ba generated column Workout được xác nhận bằng `information_schema.COLUMNS` là `VIRTUAL GENERATED`: `ke_hoach_tap.hoi_vien_dang_su_dung_id`, `buoi_tap_du_kien.ma_buoi_con_hieu_luc`, `buoi_tap_du_kien.ngay_tap_con_hieu_luc`. Các UNIQUE `duy_nhat_b33_04`, `duy_nhat_b37_03`, `duy_nhat_b37_05`, `duy_nhat_b38_01` tồn tại thật.

## 3. Workout Architecture

**Plan:** `ke_hoach_tap` → `phien_ban_ke_hoach_tap` → `ngay_trong_ke_hoach` → `bai_tap_trong_ke_hoach` → `buoi_tap_du_kien` biểu diễn ý định và lịch tương lai.

**Session:** `phien_tap` → `bai_tap_trong_phien` → `hiep_tap` biểu diễn thực tế và lịch sử.

**Future vs historical separation:** lúc Start, nội dung kê được materialize sang cây Session. Version mới không UPDATE/DELETE version cũ và không rewrite cây Session.

Lock order thực tế: Member → Plan(s)/version/day → schedule theo ID/date → Session → session exercise → sets. Mọi mutation chính chạy trong transaction, DB UNIQUE là hàng rào cuối.

## 4. Template

Implemented: **YES — read only**.

Endpoints:

- `GET /api/workout/templates`
- `GET /api/workout/templates/{template}`

Chỉ `giao_an_mau.trang_thai = HOAT_DONG` được trả. Ngày và bài được sắp theo `so_thu_tu`, DTO chỉ chứa metadata an toàn và đường dẫn media. Template ngừng dùng được che bằng 404. Không có route mutation catalog.

## 5. Plan Lifecycle

Table: `ke_hoach_tap`.

One active Plan: generated `hoi_vien_dang_su_dung_id` + UNIQUE, kết hợp khóa Member và toàn bộ Plan theo ID.

Activation/switch: `WorkoutPlanService::kichHoat()` lưu trữ Plan active cũ trước khi kích hoạt Plan đích trong cùng transaction. Probe hai process kích hoạt Plan A/B đồng thời kết thúc với đúng một `DANG_SU_DUNG`.

Public mutation: **NO**. Tài liệu chưa phê duyệt payload cấu trúc Plan thủ công từ client. `WorkoutPlanService` là service nội bộ cho test và workflow Apply được phê duyệt sau này.

## 6. Plan Versioning

Version table: `phien_ban_ke_hoach_tap`.

Version creation: `WorkoutPlanService::taoMoi()` tạo Plan + version 1 + content snapshots; `taoPhienBanTiepTheo()` tạo số version kế tiếp, `phien_ban_truoc_id`, hash nội dung và cập nhật con trỏ hiện tại atomically.

Historical versions: **IMMUTABLE**. Hàng `ngay_trong_ke_hoach` và `bai_tap_trong_ke_hoach` cũ không bị sửa/xóa.

Current version: `ke_hoach_tap.phien_ban_hien_tai_id`, được FK ghép bảo vệ cùng Plan.

Equipment validation: mọi `bai_tap_dung_cu` là điều kiện AND khi công bố cấu trúc nội bộ; exercise phải hoạt động. Snapshot tên, hướng dẫn và dụng cụ lấy từ catalog tại thời điểm công bố.

## 7. Schedule

Table: `buoi_tap_du_kien`.

One valid/member/date: `ngay_tap_con_hieu_luc` + UNIQUE `(hoi_vien_id, ngay_tap_con_hieu_luc)`, kèm khóa Member và kiểm soát conflict 409.

Generated guards: `ma_buoi_con_hieu_luc` và `ngay_tap_con_hieu_luc` đã được kiểm tra metadata và behavior.

Generation: `WorkoutScheduleService::lapLich()` ánh xạ deterministic theo `thu_trong_tuan` của version trong khoảng tối đa 92 ngày. Schedule là hàng cụ thể, không dựng động khi GET.

Canceled/replaced: `HUY` / `DA_THAY_THE` trả generated NULL và giải phóng slot.

Completed/skipped: `HOAN_THANH` / `BO_QUA` tiếp tục giữ slot.

Version mới chỉ thay hàng `CHUA_TAP` chưa có Session; hàng cũ chuyển `DA_THAY_THE` trước, hàng mới giữ `ma_buoi_logic` và `thay_the_buoi_tap_id`. `DANG_TAP`, `HOAN_THANH`, `BO_QUA` không bị regeneration đụng tới.

Timezone/date: schedule uniqueness là DATE địa phương. Start dùng ngày `Asia/Ho_Chi_Minh`. Vì source không duyệt tolerance sớm/trễ, foundation dùng chính sách bảo thủ: chỉ Start đúng `ngay_tap`.

## 8. Session

Table: `phien_tap`.

Free Workout: **BLOCKED**.

Scheduled FK: **REQUIRED** — `buoi_tap_du_kien_id NOT NULL`.

One Session/scheduled: UNIQUE `duy_nhat_b38_01`; service khóa Member/schedule và trả replay cho cùng Start key, conflict an toàn cho key khác.

Start chỉ chấp nhận lịch self-owned `CHUA_TAP`, đúng ngày địa phương, có content. `HUY`, `DA_THAY_THE`, `BO_QUA`, `HOAN_THANH` bị chặn. Start chuyển lịch và Session sang `DANG_TAP` atomically.

## 9. Session Exercises / Sets

Materialization: **PASS** — `bai_tap_trong_phien` sao chép exact Plan/version/day content gồm ID nguồn, logical ID, thứ tự, tên, hướng dẫn, dụng cụ và mục tiêu.

Set logging: `POST /api/workout/sessions/{session}/exercises/{exercise}/sets`; chỉ ghi vào exact child của Session đang `DANG_TAP`. Hỗ trợ `so_thu_tu`, reps kể cả 0, weight nullable >= 0, actual rest nullable và server completion timestamp.

Set idempotency: `ma_hiep_thuc_hien` nhận `Idempotency-Key`; cùng key/cùng payload replay, cùng key/khác payload hoặc trùng thứ tự trả 409. Không thêm cột/schema.

## 10. Completion / Immutability

Completion cập nhật `phien_tap` và `buoi_tap_du_kien` sang `HOAN_THANH` trong một transaction; end timestamp do server cấp. `ma_lan_hoan_thanh` bảo vệ replay.

Completed Session: **IMMUTABLE**. Ghi set mới, complete bằng key khác hoặc restart bị chặn.

Future Plan edit affects history: **NO**. Test Plan v1 → Start → set → Complete → Plan v2 xác nhận toàn bộ `phien_tap`, `bai_tap_trong_phien`, `hiep_tap` giữ nguyên giá trị.

## 11. Membership Independence

Active Membership required: **NO**.

Workout activates Membership: **NO**.

Usage row created: **NO**.

Test Member không có Membership và Member có kỳ `HET_HAN` đều Start/Complete thành công. Số `su_dung_quyen_loi`, AI used/reserved và PT direct used không đổi; không gọi `MembershipActivationService` trong source Workout.

## 12. APIs

| Method | Path | Role | Purpose |
| --- | --- | --- | --- |
| GET | `/api/workout/templates` | MEMBER | Catalog template hoạt động |
| GET | `/api/workout/templates/{template}` | MEMBER | Template detail |
| GET | `/api/workout/plans/current` | MEMBER | Active Plan self-owned |
| GET | `/api/workout/plans` | MEMBER | Plan list self-owned |
| GET | `/api/workout/plans/{plan}` | MEMBER | Plan/current-version detail |
| GET | `/api/workout/schedule?from=&to=` | MEMBER | Schedule tối đa 92 ngày |
| POST | `/api/workout/scheduled-sessions/{scheduled}/start` | MEMBER | Start idempotent |
| POST | `/api/workout/scheduled-sessions/{scheduled}/skip` | MEMBER | `CHUA_TAP` → `BO_QUA` |
| GET | `/api/workout/sessions` | MEMBER | History cursor/limit tối đa 100 |
| GET | `/api/workout/sessions/{session}` | MEMBER | Session/exercises/sets detail |
| POST | `/api/workout/sessions/{session}/exercises/{exercise}/sets` | MEMBER | Ghi set thực tế |
| POST | `/api/workout/sessions/{session}/complete` | MEMBER | Complete idempotent |

Không có endpoint Free Workout, cancel công khai, hard-delete history hoặc Plan mutation thủ công.

## 13. Authorization

IDOR: **PASS**. Member/profile luôn suy ra từ Bearer principal; `member_id`, `hoi_vien_id` bị cấm ở request self mutation/query.

Member self-only: Plan, schedule, Session và session exercise của Member khác bị che bằng 404. Unauthenticated trả 401; PT và role MEMBER bị thu hồi trả 403; account status được revalidate trong service.

Không nhận owner, status, timestamp, exercise relation, generated column hoặc completion state từ client.

## 14. Concurrency

Actual independent PHP processes: **YES** — mỗi probe chạy hai PHP process/bootstrap Laravel/DB connection riêng và cùng chờ barrier file.

| Probe | Kết quả |
| --- | --- |
| Plan A/B activation | Hai process đều tuyến tính hóa; cuối cùng đúng 1 active Plan |
| Same Member/date schedule | Một success, một `WORKOUT_DATE_SLOT_CONFLICT` 409; đúng 1 valid slot |
| Same scheduled workout Start | Một create, một replay; cùng Session ID; DB count = 1 |
| Same Session Complete | Một transition, một replay; trạng thái cuối `HOAN_THANH`, cùng completion key |

Không có raw SQL exception lộ ra ở các race được kiểm soát.

## 15. History Preservation

Plan v1 → scheduled workout → Session → actual set → Complete → Plan v2: **PASS**.

Session changed: **NO**.

Session exercise snapshot changed: **NO**.

Actual set values changed: **NO**.

Catalog/current Plan/Membership không được dùng để dựng lại history; history đọc từ cây Session đã materialize.

## 16. Schedule State Matrix

| Exact DB status | Giữ slot ngày | Start | Regeneration |
| --- | --- | --- | --- |
| `CHUA_TAP` | YES | Đúng ngày | Có thể thay nếu chưa có Session |
| `DANG_TAP` | YES | NO | Không đụng |
| `HOAN_THANH` | YES | NO | Không đụng |
| `BO_QUA` | YES | NO | Không đụng |
| `HUY` | NO | NO | Có thể làm nguồn lịch thay thế hợp lệ nội bộ |
| `DA_THAY_THE` | NO | NO | Giữ history/link cũ |

## 17. Tests

| Nhóm | Kết quả |
| --- | --- |
| Focused Workout | **PASS — 12 tests / 117 assertions** |
| Plan/version/ownership | PASS |
| Schedule/generated slot matrix | PASS |
| Session/materialization/set/complete | PASS |
| History immutability | PASS |
| Membership independence/expired | PASS |
| Actual-process concurrency | **PASS — 4/4 probes** |
| Full Backend run 1 (clean schema) | **PASS — 190 tests / 2,657 assertions** |
| Full Backend run 2 | **PASS — 190 tests / 2,657 assertions** |
| Random seed `20260830` | **PASS — 190 tests / 2,657 assertions** |
| Pint affected Workout files | **PASS — 19 files** |
| PHP syntax lint | PASS |
| Route list | PASS — 12 Workout routes, `auth:api` + `role:MEMBER` |
| `git diff --check` | PASS |

Một diagnostic full run trên schema đã cố ý nhận fixture concurrency committed fail 6 baseline inventory assertions vì có thêm 1 branch/user/exercise. Không sửa baseline để che lỗi; regression gate được chạy lại từ schema migrate+seed mới và PASS đủ ba lượt.

## 18. Development DB Safety

`smart_fitness` modified: **NO**.

Counts before/after:

| Table | Before | After |
| --- | ---: | ---: |
| `ke_hoach_tap` | 0 | 0 |
| `phien_ban_ke_hoach_tap` | 0 | 0 |
| `buoi_tap_du_kien` | 0 | 0 |
| `phien_tap` | 0 | 0 |
| `bai_tap_trong_phien` | 0 | 0 |
| `hiep_tap` | 0 | 0 |

Cleanup: **PASS**. Hai schema test exact đã được drop; `information_schema.SCHEMATA` không còn `smart_fitness_workout_test_%`. `.tmp/workout_concurrency` và mọi barrier/output đã được xóa.

## 19. Deferred

- AI Proposal Apply: **DEFERRED**.
- PT Proposal: **DEFERRED**.
- Public manual Plan creation/edit: **DEFERRED** vì source chưa duyệt payload authority.
- Member-facing cancel/replacement: **DEFERRED**; chỉ giữ workflow nội bộ regeneration.
- Free Workout: **NOT SUPPORTED**.
- Set correction/update before complete: **DEFERRED**; foundation hiện ghi set idempotent, không invent optimistic PATCH contract.
- Advanced analytics/body metrics: **DEFERRED**.
- FE/Mobile: **DEFERRED**.

## 20. Files Changed

### Modified

- `BE/routes/api.php`

### Created

- `BE/app/Exceptions/Workout/WorkoutWorkflowException.php`
- `BE/app/Http/Controllers/Api/Workout/WorkoutController.php`
- `BE/app/Http/Requests/Workout/CompleteWorkoutSessionRequest.php`
- `BE/app/Http/Requests/Workout/ListWorkoutScheduleRequest.php`
- `BE/app/Http/Requests/Workout/ListWorkoutSessionsRequest.php`
- `BE/app/Http/Requests/Workout/RecordWorkoutSetRequest.php`
- `BE/app/Http/Requests/Workout/StartWorkoutSessionRequest.php`
- `BE/app/Services/Workout/WorkoutMemberService.php`
- `BE/app/Services/Workout/WorkoutPlanQueryService.php`
- `BE/app/Services/Workout/WorkoutPlanService.php`
- `BE/app/Services/Workout/WorkoutScheduleService.php`
- `BE/app/Services/Workout/WorkoutSessionQueryService.php`
- `BE/app/Services/Workout/WorkoutSessionService.php`
- `BE/app/Services/Workout/WorkoutTemplateQueryService.php`
- `BE/tests/Concerns/CreatesWorkoutFixtures.php`
- `BE/tests/Feature/WorkoutFoundationTest.php`
- `BE/tests/Support/prepare_workout_concurrency.php`
- `BE/tests/Support/run_workout_action.php`
- `docs/thiet_ke_co_so_du_lieu/WORKOUT_PLAN_SESSION_FOUNDATION_REPORT.md`

## 21. Deviations

NONE đối với logical schema và business rules.

Các ranh giới được chọn đúng prompt: không mở public Plan mutation chưa được duyệt; Start exact scheduled date vì không có tolerance sớm/trễ; cancel chỉ nội bộ; AI/PT Apply chưa triển khai.

## 22. Future AI Apply Integration

AI Proposal Apply nên mở outer transaction và tái sử dụng:

1. `WorkoutPlanService::taoPhienBanTiepTheo()` để validate/công bố snapshot version mới mà không sửa version cũ;
2. `WorkoutPlanService::kichHoat()` nếu Apply tạo/chuyển Plan;
3. `WorkoutScheduleService::lapLich()` để thay đúng lịch tương lai có thể thay, giữ `ma_buoi_logic`/replacement link và bảo toàn Session/history;
4. `WorkoutPlanQueryService` cho safe preview/current-version read.

Apply tương lai vẫn phải tự khóa/revalidate Proposal, TTL, base version, profile/plan change markers, ownership, candidate/equipment và ghi audit atomically. Task này không consume Proposal.

## 23. Final Gate

| Gate | Status |
| --- | --- |
| WORKOUT PLAN API / OWNERSHIP | PASS |
| ONE ACTIVE PLAN / MEMBER | PASS |
| PLAN ACTIVATION CONCURRENCY | PASS |
| PLAN VERSIONING / HISTORICAL VERSION | PASS |
| SCHEDULE API / BOUNDED RANGE | PASS |
| ONE VALID SCHEDULE / MEMBER / DATE | PASS |
| SCHEDULE DATE CONCURRENCY | PASS |
| CANCELED / REPLACED SLOT RELEASE | PASS |
| COMPLETED / SKIPPED SLOT HOLD | PASS |
| WORKOUT SESSION API | PASS |
| FREE WORKOUT | BLOCKED |
| SESSION REQUIRES SCHEDULED WORKOUT | PASS |
| ONE SESSION / SCHEDULED WORKOUT | PASS |
| SESSION START CONCURRENCY | PASS |
| SESSION EXERCISE MATERIALIZATION | PASS |
| SET LOGGING | PASS |
| SESSION COMPLETION / IMMUTABILITY | PASS |
| SESSION COMPLETE CONCURRENCY | PASS |
| PLAN CHANGE DOES NOT MODIFY HISTORY | PASS |
| WORKOUT WITHOUT / EXPIRED MEMBERSHIP | PASS |
| WORKOUT MEMBERSHIP ACTIVATION | BLOCKED |
| WORKOUT USAGE CREATION | NONE |
| AI / PT QUOTA / CHAT RIGHTS | UNCHANGED |
| IDOR | PASS |
| FULL BACKEND / RANDOM ORDER | PASS |
| DEVELOPMENT DB | SAFE |
| TEST DATABASE CLEANUP | PASS |
| GIT DIFF CHECK | PASS |

**WORKOUT PLAN + WORKOUT SESSION FOUNDATION = PASS**

**WORKOUT PLAN = IMPLEMENTED**

**WORKOUT PLAN VERSIONING = IMPLEMENTED**

**WORKOUT SCHEDULE = IMPLEMENTED**

**WORKOUT SESSION = IMPLEMENTED**

**WORKOUT HISTORY IMMUTABILITY = IMPLEMENTED**

**FREE WORKOUT = NOT SUPPORTED**

**AI PROPOSAL APPLY = NOT YET IMPLEMENTED**

**DATABASE = READY FOR AI PROPOSAL APPLY FOUNDATION**
