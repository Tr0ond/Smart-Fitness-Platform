# BE-FE5-PREREQ — Repair Brief Round 1

`STATUS: READY_FOR_TEST_ONLY_FIX`
`OPEN_FINDINGS: F-001`
`ROUND: 1`
`FULL_CANONICAL_REREAD: CONDITIONAL`
`CANONICAL_ESCALATION: NONE`

## 1. Mục tiêu và phán quyết phạm vi

Sửa duy nhất finding `F-001` bằng cách làm đầy đủ ma trận regression bắt buộc cho bốn PT workspace GET route families. Source production hiện tại đã có đúng các điểm kiểm soát cần chứng minh:

- `PtMemberWorkspaceService::trongPhamVi()` dùng `PtAssignmentScopeService` cho PT profile authority và exact current assignment `[ngay_bat_dau, ngay_ket_thuc)`;
- `WorkoutPlanQueryService::hienTaiChoHoiVien()` chỉ đọc Plan chính thức từ `ke_hoach_tap`;
- `WorkoutSessionQueryService::chiTietChoHoiVien()` bind đồng thời Session với `hoi_vien_id`;
- các GET workspace không có Membership/usage/activation write path;
- notes hiện hữu là POST append-only, không có Plan/Session mutation route.

Vì vậy repair dự kiến là **test-only** trong `E:/Fitness/BE/tests/Feature/PtMemberWorkspaceApiTest.php`, rồi append Round 1 evidence vào `E:/Fitness/.fitness-sdd/fe5-all/task-1-report.md`. Không sửa production source, routes hoặc API contract trừ khi test mới phát hiện một lỗi production thực sự; nếu điều đó xảy ra, dừng và báo controller thay vì tự mở rộng repair.

Snapshot audit trước repair xác nhận cả tám product/test targets đều byte-identical giữa `task-1-round-0-after/`, `task-1-round-1-before/` và current tree. Năm tracked targets ban đầu byte-identical giữa `baseline-tree/` và `task-1-round-0-before/`; ba file mới có `.absent` marker tương ứng. Không có post-review product edit cần hòa giải.

## 2. Finding phải sửa — nguyên văn

- ID: F-001
  Severity: Important
  File: E:/Fitness/BE/tests/Feature/PtMemberWorkspaceApiTest.php
  Line: 78
  Rule: E:/Fitness/.fitness-sdd/fe5-all/task-1-brief.md Section 6; RULE CODE 19/20; E:/Fitness/.fitness-rules/engineering/testing-definition-of-done.md Sections 1.3 and 54.3
  Evidence: The mandatory acceptance matrix is not fully executable. The scope test at lines 78-105 creates only an ended assignment for PT A, a future assignment for Member B, and a never-assigned PT B request; it never creates a reassignment and therefore does not prove that an old PT loses all four workspace route families, and it does not exercise a Member with no assignment history as a distinct unassigned case. The authentication test at lines 31-47 covers unauthenticated and MEMBER-role requests but not an authenticated PT whose profile authority is inactive/missing, which the packet requires to return 403. The Plan test at lines 131-160 creates no pending PT Proposal, so its assertion cannot prove that an unconfirmed Proposal is excluded from the official Plan response; it also has no Membership row, so it cannot prove that a CHO_KICH_HOAT or expired term remains unactivated and unchanged. The cross-member test at lines 162-194 assigns Member B to PT B, not to the requesting PT A, so the contract's explicit same-PT-assigned-to-both-members binding case is absent. Finally, the compatibility test at lines 209-223 is happy-path GET-only and does not itself establish the packet's required negative matrix or note append-only/no-Plan-or-Session-mutation regression. All independently rerun suites pass, but passing tests cannot substitute for these expressly required scenarios.
  Required fix: Add focused feature cases that (1) close PT A's assignment and make PT B current, then assert PT A receives concealed 404 on all four new route families; (2) assert all four routes conceal a Member with no assignment history; (3) assert an authenticated PT with inactive/missing PT profile gets the documented 403; (4) persist a pending PT Proposal alongside an official Plan and assert only the official Plan is returned; (5) persist representative CHO_KICH_HOAT and expired Membership state and prove every GET leaves term/usage/activation state byte-for-byte unchanged; (6) assign the requesting PT to both Members and prove a session from Member B is concealed under Member A's route; and (7) retain executable compatibility evidence for profile/list/progress/notes, including append-only note and no Plan/Session mutation. Rerun the focused, related, and full Backend gates.

## 3. Root cause và exact file/logic

### Root cause

`PtMemberWorkspaceApiTest` dùng fixtures quá yếu hoặc conflated:

- ended, future và foreign actor được kiểm tra, nhưng không có old-PT-after-reassignment và không có một Member tồn tại với zero assignment rows;
- role middleware được kiểm tra, nhưng service-level PT profile authority không được thực thi bởi test;
- Plan test chỉ đếm zero Membership rows thay vì giữ một term thật qua GET;
- không có pending PT Proposal cạnh official Plan;
- cross-member test không cho requesting PT authority hợp lệ trên cả hai Members;
- compatibility test chỉ GET happy path, không POST note và không snapshot Plan/Session.

Đây là coverage defect theo RULE CODE 19/20, không phải bằng chứng production defect.

### Exact planned edits

1. `E:/Fitness/BE/tests/Feature/PtMemberWorkspaceApiTest.php`
   - Tách/đổi các test hiện hữu và thêm helper test-local nếu cần.
   - Mở rộng `workspacePaths` để nhận một Session ID thật cho route detail; không dùng một `999999999` giả trong các cases cần chứng minh scope trước resource existence.
   - Có thể thêm helper test-local để tạo pending PT Proposal bằng endpoint chính thức, capture Membership snapshot có thứ tự ổn định, capture Plan/Session tables, và assert cùng một status trên đủ bốn route families.
   - Không sửa helper traits/shared tests ngoài file này.
2. `E:/Fitness/.fitness-sdd/fe5-all/task-1-report.md`
   - Append mục `Round 1 — F-001` với changed files, từng executable case, exact commands/results/counts/skips, guarded DB environment, baseline/path preservation và concerns.
   - Không viết lại hoặc làm sai 15-section report hiện hữu.

Không có planned production-code edit.

## 4. REQUIRED_CONTEXT giữ nguyên và bổ sung tối thiểu

Đọc đầy đủ trước khi sửa:

- `E:/Fitness/AGENTS.md`
- `E:/Fitness/BE/AGENTS.md`
- `E:/Fitness/.fitness-rules/PROJECT_CORE.md`
- `E:/Fitness/.fitness-rules/RULE_INDEX.md`
- `E:/Fitness/.fitness-rules/domains/pt-chat.md`
- `E:/Fitness/.fitness-rules/domains/workout.md`
- `E:/Fitness/.fitness-rules/domains/auth-resource-scope.md`
- `E:/Fitness/.fitness-rules/engineering/coding-conventions.md`
- `E:/Fitness/.fitness-rules/engineering/testing-definition-of-done.md` — bổ sung tối thiểu vì `F-001` viện dẫn trực tiếp RULE CODE 19/20 và Sections 1.3/54.3.
- `E:/Fitness/.fitness-sdd/context/FE5_CONTEXT.md`
- `E:/Fitness/.fitness-sdd/context/TASK_PACKET_TEMPLATE.md`
- `E:/Fitness/.fitness-sdd/fe5-all/plan.md`
- `E:/Fitness/.fitness-sdd/fe5-all/task-1-brief.md`
- `E:/Fitness/.fitness-sdd/fe5-all/task-1-report.md`
- `E:/Fitness/.fitness-sdd/fe5-all/task-1-review.md`
- `E:/Fitness/.fitness-sdd/fe5-all/task-1-review-package.md`
- `E:/Fitness/.fitness-sdd/fe5-all/task-1-repair-brief-round-1.md`
- `E:/Fitness/docs/BACKEND_API_CONTRACT.md`
- actual source/tests trong exact allow-list và dependencies được gọi trực tiếp: `PtAssignmentScopeService`, relevant requests/models/exceptions, `CreatesPtFixtures`, `CreatesWorkoutFixtures`, membership/profile fixture helpers, và current profile/list/progress/notes tests.

Canonical authority vẫn là `E:/Fitness/PROJECT_RULES.md` và frontend plan authority vẫn là `E:/Fitness/docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md`, nhưng chỉ đọc đúng phần liên quan nếu phát hiện conflict/ambiguity/version mismatch. Repair planning không phát hiện condition escalation mới; không đọc rộng và không thay đổi Business Rule.

## 5. Exact allow-list giữ nguyên

Writer/fixer chỉ được ghi các path trong original Task 1 allow-list:

- `E:/Fitness/BE/routes/api.php`
- `E:/Fitness/BE/app/Http/Controllers/Api/Pt/PtMemberWorkspaceController.php`
- `E:/Fitness/BE/app/Services/Pt/PtMemberWorkspaceService.php`
- `E:/Fitness/BE/app/Services/Workout/WorkoutPlanQueryService.php`
- `E:/Fitness/BE/app/Services/Workout/WorkoutScheduleService.php`
- `E:/Fitness/BE/app/Services/Workout/WorkoutSessionQueryService.php`
- `E:/Fitness/BE/tests/Feature/PtMemberWorkspaceApiTest.php`
- `E:/Fitness/docs/BACKEND_API_CONTRACT.md`
- `E:/Fitness/.fitness-sdd/fe5-all/task-1-report.md`

Expected Round 1 write set theo plan test-only chỉ là `E:/Fitness/BE/tests/Feature/PtMemberWorkspaceApiTest.php` và `E:/Fitness/.fitness-sdd/fe5-all/task-1-report.md`. Mọi file khác trong allow-list phải giữ byte-identical nếu tests không chứng minh production defect. Cấm sửa review, review package, plan, brief, snapshots, baseline, progress/checkpoint, rules, AGENTS, Frontend, migrations/models/factories/dependencies hoặc bất kỳ path ngoài allow-list.

## 6. Focused regression cases bắt buộc

### F-001-A — Old PT sau reassignment trên đủ bốn route families

- Freeze server time như suite hiện tại.
- Tạo một Member có completed Session thật.
- Tạo assignment PT A kết thúc đúng `now`, sau đó assignment PT B bắt đầu đúng `now` (adjacent half-open intervals; PT B là current).
- Dùng token PT A gọi đủ:
  1. `GET /api/pt/members/{member}`;
  2. `GET /api/pt/members/{member}/workout/plans/current`;
  3. `GET /api/pt/members/{member}/workout/sessions`;
  4. `GET /api/pt/members/{member}/workout/sessions/{realSessionId}`.
- Cả bốn phải `404`, tốt nhất assert stable code `ASSIGNMENT_NOT_FOUND` để chứng minh old PT mất scope trước khi dữ liệu được trả.
- Sanity evidence: PT B current đọc được ít nhất member detail và Session detail tương ứng.

### F-001-B — Member chưa từng có assignment là case độc lập

- PT A có profile/token hợp lệ và có thể có assignment với Member A để chứng minh actor hợp lệ.
- Member B tồn tại, có completed Session thật nếu dùng detail path, nhưng `phan_cong_huan_luyen_vien` phải có zero rows cho Member B.
- PT A gọi đủ bốn route families của Member B với real Session B ID; cả bốn phải concealed `404`/`ASSIGNMENT_NOT_FOUND`.
- Không gộp case này với future assignment, ended assignment hoặc never-assigned PT actor.

### F-001-C — Authenticated PT profile authority trả 403

- Variant inactive: PT token/role vẫn hợp lệ, nhưng `ho_so_huan_luyen_vien.trang_thai = NGUNG_NHAN_PHAN_CONG`; gọi đủ bốn route families và assert `403` với code `TRAINER_NOT_AVAILABLE`.
- Variant missing: tạo authenticated account có active PT role nhưng không tạo `ho_so_huan_luyen_vien`; gọi đủ bốn route families và assert cùng documented `403`/`TRAINER_NOT_AVAILABLE`.
- Không đổi middleware hoặc error contract để làm test pass.

### F-001-D — Pending PT Proposal không phải official Plan

- Tạo current assignment, exercise, official `DANG_SU_DUNG` Plan/version/schedule với định danh/tên A.
- Qua endpoint PT Proposal chính thức, persist một Proposal nguồn `HUAN_LUYEN_VIEN`, trạng thái `CHO_XAC_NHAN`, nội dung Plan khác rõ ràng (tên/goal/version intent B), dùng UUID `Idempotency-Key`.
- Gọi workspace current-plan GET và assert exact official `plan.id`, `plan.name`, `current_version.id` và official schedule IDs vẫn là A.
- Assert response không chứa Proposal ID/nội dung B; Proposal row vẫn `CHO_XAC_NHAN`, content/hash không đổi, và chưa có `phien_ban_ke_hoach_tap.de_xuat_ke_hoach_tap_id` cho Proposal.

### F-001-E — CHO_KICH_HOAT và HET_HAN không đổi trên từng GET

Chạy hai representative scenarios độc lập:

1. term thật ở `CHO_KICH_HOAT`, `ngay_bat_dau = null`, `ngay_ket_thuc = null`, registration chưa có first-use link;
2. term thật ở `HET_HAN` với registration/term trạng thái expired và timestamps đã chốt.

Cho mỗi scenario:

- Tạo current assignment, official Plan và completed Session trước khi capture snapshot.
- Capture deterministic before snapshot của đúng Member: full rows của `dang_ky_goi_tap`, `ky_han_hoi_vien`, ordered `su_dung_quyen_loi`, và counts/links liên quan activation (`lan_su_dung_dau_tien_id`, term start/end/status, usage count). Snapshot phải so sánh persisted columns, không chỉ row counts.
- Gọi lần lượt bốn GET families với real Session ID. Sau **mỗi GET**, assert snapshot byte-for-byte/equivalent serialized persisted values vẫn bằng before snapshot; usage count vẫn không đổi và không có activation/paid-use row mới.
- Không tính access-token telemetry vào Membership snapshot; đó không phải domain side effect đang kiểm tra.

### F-001-F — Session bind theo Member route khi cùng PT phụ trách cả hai

- Assign chính requesting PT A hiện tại cho cả Member A và Member B.
- Tạo completed Session A và Session B.
- Sanity assert PT A đọc Session B thành công qua `/api/pt/members/{memberB}/workout/sessions/{sessionB}`.
- Sau đó request đúng Session B dưới `/api/pt/members/{memberA}/workout/sessions/{sessionB}` và assert concealed `404` với `WORKOUT_SESSION_NOT_FOUND`.
- Giữ bounded/cursor/list/immutable assertions hiện hữu cho Member A.

### F-001-G — Compatibility profile/list/progress/notes và no mutation

- Giữ executable success evidence cho:
  - `GET /api/profile/trainer`;
  - `GET /api/pt/members`;
  - ba progress reads: overview, body, exercise progress cho exercise thật thuộc official Plan;
  - `GET/POST /api/pt/members/{member}/notes`.
- Tạo official Plan/schedule và completed Session, rồi capture deterministic snapshots của Plan/version/day/exercise/schedule rows và Session/exercise/set rows trước note writes.
- POST note 1 có `plan_id`/`session_id`, capture row 1; POST note 2. Assert count tăng đúng từng lần, cả hai rows còn tồn tại, row 1 byte-identical sau append thứ hai, list order/limit contract vẫn đúng và không có update/delete semantics.
- Sau mỗi append hoặc ít nhất sau từng response, assert toàn bộ Plan/Session snapshots không đổi. Notes không được activate Membership, tạo usage, đổi Plan/version/schedule hoặc sửa completed Session/history.
- Retain related-suite negative coverage cho current/old/foreign/unassigned/ended progress/notes; nếu focused case dựa vào related tests cho một nhánh, report phải cite exact test name và result, không chỉ nói chung rằng suite passed.

## 7. Ràng buộc không được thay đổi

- Không thay Q04/RULE CODE 17: assignment vẫn start-inclusive/end-exclusive, current exact PT only, out-of-scope concealed `404`.
- Không thay documented `401`/`403`/`404` semantics hoặc stable error codes.
- Không thêm Membership check vào workspace GET; Q08 và no-paid-side-effect giữ nguyên.
- Không dùng Proposal làm official Plan và không confirm/apply Proposal trong Plan-read case.
- Không thêm Plan/Session mutation route; completed Session/history vẫn immutable theo RULE GYM 13/15.
- Notes chỉ append; không update/delete hoặc mutate Plan/Session.
- Không thay Member-self Plan/Schedule/Session behavior hoặc DTO mapper.
- Không đổi API contract chỉ để khớp test; test phải chứng minh contract hiện hữu.
- Không dùng mock để che database authorization/state behavior.

## 8. Acceptance evidence và gates

### Focused acceptance

`PtMemberWorkspaceApiTest` phải PASS với zero failures/skips và report phải nêu exact test/assertion count. Review evidence phải chỉ ra trực tiếp test names cho `F-001-A` đến `F-001-G`, không suy từ production code hoặc row-count-only assertions.

### Guarded database

Mọi Artisan test command phải tự set:

```text
APP_ENV=testing
DB_CONNECTION=mysql
DB_DATABASE=smart_fitness_test
SMART_FITNESS_TEST_DATABASE=smart_fitness_test
```

Tên DB phải tiếp tục khớp `smart_fitness_*test*`. Cấm drop/create/migrate:fresh/rollback hoặc chạy trên `smart_fitness`.

### Commands bắt buộc

Từ `E:/Fitness/BE`, mọi shell invocation dùng `rtk`:

```powershell
rtk proxy powershell -NoProfile -Command '& { $env:APP_ENV="testing"; $env:DB_CONNECTION="mysql"; $env:DB_DATABASE="smart_fitness_test"; $env:SMART_FITNESS_TEST_DATABASE="smart_fitness_test"; & "E:/Fitness/.tools/php/php.exe" artisan test --filter=PtMemberWorkspaceApiTest }'
rtk proxy powershell -NoProfile -Command '& { $env:APP_ENV="testing"; $env:DB_CONNECTION="mysql"; $env:DB_DATABASE="smart_fitness_test"; $env:SMART_FITNESS_TEST_DATABASE="smart_fitness_test"; & "E:/Fitness/.tools/php/php.exe" artisan test --filter="(PtMemberWorkspaceApiTest|PtAssignmentApiTest|ProgressApiTest|PtProposalApiTest|TrainerProfileTest|WorkoutFoundationTest)" }'
rtk proxy powershell -NoProfile -Command '& { $env:APP_ENV="testing"; $env:DB_CONNECTION="mysql"; $env:DB_DATABASE="smart_fitness_test"; $env:SMART_FITNESS_TEST_DATABASE="smart_fitness_test"; & "E:/Fitness/.tools/php/php.exe" artisan test }'
rtk proxy powershell -NoProfile -Command '& { $env:APP_ENV="testing"; $env:DB_CONNECTION="mysql"; $env:DB_DATABASE="smart_fitness_test"; $env:SMART_FITNESS_TEST_DATABASE="smart_fitness_test"; & "E:/Fitness/.tools/php/php.exe" artisan test --filter=TestDatabaseGuard }'
rtk proxy powershell -NoProfile -Command '& { & "E:/Fitness/.tools/php/php.exe" vendor/bin/pint --test }'
rtk proxy powershell -NoProfile -Command '& { & "E:/Fitness/.tools/php/php.exe" "E:/Fitness/.tools/composer/composer.phar" validate --strict --no-check-publish }'
rtk proxy powershell -NoProfile -Command '& { & "E:/Fitness/.tools/php/php.exe" "E:/Fitness/.tools/composer/composer.phar" audit }'
rtk proxy powershell -NoProfile -Command '& { & "E:/Fitness/.tools/php/php.exe" "E:/Fitness/.tools/composer/composer.phar" check-platform-reqs }'
rtk proxy powershell -NoProfile -Command '& { & "E:/Fitness/.tools/php/php.exe" artisan route:list --path=api/pt/members --json }'
```

Từ `E:/Fitness`:

```powershell
rtk git diff --check
rtk git status --short --branch
rtk git diff -- BE/routes/api.php BE/app/Http/Controllers/Api/Pt/PtMemberWorkspaceController.php BE/app/Services/Pt/PtMemberWorkspaceService.php BE/app/Services/Workout/WorkoutPlanQueryService.php BE/app/Services/Workout/WorkoutScheduleService.php BE/app/Services/Workout/WorkoutSessionQueryService.php BE/tests/Feature/PtMemberWorkspaceApiTest.php docs/BACKEND_API_CONTRACT.md
```

### Exit evidence

Round 1 chỉ được coi là addressed khi:

- focused, related và full Backend suites PASS trên guarded DB, zero unapproved skips;
- DB guard, Pint, Composer validate/audit/platform checks, route scan và `git diff --check` PASS;
- diff cho thấy repair product delta chỉ ở `PtMemberWorkspaceApiTest.php`; production source/docs/routes byte-identical với `task-1-round-1-before/` nếu không có test-proven production defect;
- report append map từng case `F-001-A`…`F-001-G` tới exact assertions và observed results;
- không có unexpected product path hoặc baseline/pre-existing hunk loss;
- fresh reviewer có thể re-review `F-001` từ repair diff và evidence, không dựa vào writer self-report.

## 9. Genuine blocker / escalation

Không có blocker hiện tại. Nếu fixture hợp lệ làm một case bắt buộc fail vì production behavior thật, fixer phải dừng với exact failing command/output và báo controller; không được nới assertion, sửa Business Rule, thay documented status code hoặc mở rộng file scope một cách im lặng.
