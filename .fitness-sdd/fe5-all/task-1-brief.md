### TASK_PACKET: BE-FE5-PREREQ

**ROLE:** Implementation Writer  
**WORKFLOW_ROLE:** Implementer/Fixer  
**MODEL:** `gpt-5.6-luna`  
**REASONING_EFFORT:** `max`  
**MODEL_SELECTION_RULE:** Controller supplies the exact model and effort above; recipient must not substitute them.  
**OBJECTIVE:** Bổ sung tối thiểu source-authoritative PT-scoped read contracts và executable tests cho BLOCKER-03/04/05 để mở gate FE5-ALL. Không thêm migration, không thay đổi Business Rule, không triển khai Frontend.

---

#### 1. TÌNH TRẠNG HIỆN TẠI (STATE & CHECKPOINT)

- **CURRENT_CHECKPOINT:** `E:/Fitness/docs/VUE_WEB_COMPLETION_CHECKPOINT.md`; FE0–FE4 PASS, next phase FE5-ALL.
- **CURRENT_STATE:** `artisan route:list`, `BE/routes/api.php`, controllers/services, `docs/BACKEND_API_CONTRACT.md` và tests chỉ có PT assigned list, progress, notes, proposals; chưa có PT member detail, official current/future Workout Plan hay Workout History.
- **ENTRY_RESULT:** FAIL cho BLOCKER-03/04/05; documentation/report không thay source/test evidence.
- **SEQUENCE:** Đây là Task 1/2. `FE5-ALL` tuyệt đối chưa được dispatch cho đến khi task này, focused/full Backend gates và Task Reviewer đều PASS.

#### 2. PHẠM VI CHO PHÉP & CẤM (SCOPE BOUNDARIES)

**ALLOWED_SCOPE:**

- Thêm bốn GET route PT workspace: member detail, official current/future plan, session list, session detail.
- Tái sử dụng `PtAssignmentScopeService` cho exact current assignment và mở các query method nhận `HoSoHoiVien` trên services hiện hữu; Member-self methods phải giữ tương thích.
- Thêm một controller, một orchestration service, một feature-test suite; cập nhật official API contract.
- Bổ sung regression evidence cho PT self profile, assigned list, progress và notes trong cùng test file khi cần.

**PROHIBITED_SCOPE:**

- Cấm sửa Frontend, migration/schema/model/factory, auth semantics, proposal workflow, Member/Admin route behavior hoặc Business Rule.
- Cấm tạo write endpoint cho Plan/Session, cấm edit/delete history, cấm dùng Proposal làm Plan chính thức.
- Cấm thêm Membership check, activation/benefit usage, external call, package/provider/dependency.
- Cấm branch/worktree/commit/push/reset/restore/checkout/stash/clean và destructive DB commands.

**ALLOWED_FILES:** chỉ các absolute paths sau:

- `E:/Fitness/BE/routes/api.php`
- `E:/Fitness/BE/app/Http/Controllers/Api/Pt/PtMemberWorkspaceController.php`
- `E:/Fitness/BE/app/Services/Pt/PtMemberWorkspaceService.php`
- `E:/Fitness/BE/app/Services/Workout/WorkoutPlanQueryService.php`
- `E:/Fitness/BE/app/Services/Workout/WorkoutScheduleService.php`
- `E:/Fitness/BE/app/Services/Workout/WorkoutSessionQueryService.php`
- `E:/Fitness/BE/tests/Feature/PtMemberWorkspaceApiTest.php`
- `E:/Fitness/docs/BACKEND_API_CONTRACT.md`
- `E:/Fitness/.fitness-sdd/fe5-all/task-1-report.md`

**PROHIBITED_FILES:** mọi file khác, đặc biệt `PROJECT_RULES.md`, Vue plan, AGENTS.md, checkpoint, baseline artifacts, Composer files và source Frontend.

**AUTHORITIES:** `E:/Fitness/PROJECT_RULES.md`, `E:/Fitness/docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md`, root/BE `AGENTS.md`, và selected layered context dưới đây.

**BASELINE_AND_PREEXISTING:**

- `E:/Fitness/.fitness-sdd/fe5-all/baseline-status.txt`
- `E:/Fitness/.fitness-sdd/fe5-all/baseline-working.patch`
- `E:/Fitness/.fitness-sdd/fe5-all/baseline-staged.patch`
- controller-provided `baseline-tree/` và `task-1-round-<R>-before/`
- Preserve mọi thay đổi có trước byte-for-byte ngoài declared hunks. Nếu cần file ngoài allow-list, dừng và báo controller; không tự mở rộng.

#### 3. TÀI LIỆU CONTEXT BẮT BUỘC (REQUIRED_CONTEXT)

Đọc đầy đủ trước khi sửa:

- `E:/Fitness/AGENTS.md`
- `E:/Fitness/BE/AGENTS.md`
- `E:/Fitness/.fitness-rules/PROJECT_CORE.md`
- `E:/Fitness/.fitness-rules/RULE_INDEX.md`
- `E:/Fitness/.fitness-rules/domains/pt-chat.md`
- `E:/Fitness/.fitness-rules/domains/workout.md`
- `E:/Fitness/.fitness-rules/domains/auth-resource-scope.md`
- `E:/Fitness/.fitness-rules/engineering/coding-conventions.md`
- `E:/Fitness/.fitness-sdd/context/FE5_CONTEXT.md`
- `E:/Fitness/.fitness-sdd/context/TASK_PACKET_TEMPLATE.md`
- `E:/Fitness/.fitness-sdd/fe5-all/plan.md`
- `E:/Fitness/docs/BACKEND_API_CONTRACT.md`
- actual source/tests trong ALLOWED_FILES và dependencies được gọi trực tiếp: `PtAssignmentScopeService`, relevant requests/models/exceptions, current profile/list/progress/notes tests. Read-only inspection ngoài allow-list được phép khi cần; write không được phép.

Canonical escalation đã được planner giải quyết bằng các phần liên quan: `PROJECT_RULES.md` Q04, Q08, RULE GYM 13/15, RULE CODE 17/19/20 và `VUE_WEB_IMPLEMENTATION_PLAN_V2.md` Section 31/33/38A. Nếu implementer phát hiện xung đột/ambiguity mới, đọc đúng phần authority liên quan rồi dừng báo controller; không đọc tràn lan để tự đổi rule.

#### 4. QUY TẮC CẦN TUÂN THỦ (CANONICAL_RULE_IDS)

- **RULE GYM:** RULE GYM 13, RULE GYM 15; Session hoàn thành/snapshot bất biến, PT chỉ đọc history.
- **RULE CODE:** RULE CODE 05–09, RULE CODE 17, RULE CODE 18–20.
- **Q DECISIONS:** Q04 exact assignment không overlap; Q08 Workout Tracking không phụ thuộc Membership.
- **BINDING:** interval chính xác `[ngay_bat_dau, ngay_ket_thuc)`; null end là mở. PT cũ/khác, unassigned, future và ended đều không có resource authority.
- **BINDING:** Official Plan khác Proposal; note append-only; không mutation/activation side effect trong các GET mới.

#### 5. MÃ NGUỒN VÀ CONTRACT CỤ THỂ (RELEVANT_SOURCE)

Actual source wins. Trước khi tạo file, xác nhận lại route/controller/service/request/test hiện hữu để không duplicate. Implement contract sau:

1. `GET /api/pt/members/{member}`
   - Guard `auth:api`, `role:PT` và service-level current assignment.
   - `data.member`: allow-list `id`, `code`, `name`, `training_goal`, `training_experience`, `desired_training_days`, `session_duration_minutes`, `profile_version`, `updated_at`.
   - `data.assignment`: allow-list `id`, `start_at`, `end_at`.
   - Không trả email, phone, credentials, birth date, branch authority, ending reason, audit actor/internal fields.

2. `GET /api/pt/members/{member}/workout/plans/current`
   - `data.plan`: cùng safe official current Plan DTO/`null` như query service hiện hữu; không dùng Proposal.
   - `data.future_schedule`: cùng safe schedule item DTO hiện hữu cho window server-authoritative tối đa 92 ngày, bắt đầu từ ngày hiện tại theo project business timezone đã dùng bởi Workout service.
   - `data.schedule_window`: `from`, `to`, `timezone` để FE không phải tự suy luận ngày/timezone.
   - Plan/schedule phải thuộc đúng member, official source; không Membership lookup/activation/benefit consumption.

3. `GET /api/pt/members/{member}/workout/sessions?before_id&limit`
   - Tái sử dụng `ListWorkoutSessionsRequest`, default/bounds hiện hữu (`limit` 1..100), cursor stable và safe list DTO.

4. `GET /api/pt/members/{member}/workout/sessions/{session}`
   - Tái sử dụng immutable detail snapshot mapper; session phải thuộc đúng authorized member, nếu không concealed `404`.

Implementation boundary:

- `PtMemberWorkspaceService` orchestration dùng `PtAssignmentScopeService` trong transaction đọc ngắn để khóa/xác thực PT profile, active member và assignment hiện tại theo server UTC trước khi query. Không replicate scope predicate ở controller.
- Thêm methods nhận `HoSoHoiVien` vào Plan/Schedule/Session query services; existing Member-self public methods delegate để tránh mapper duplication/regression.
- Controller chỉ validate/request-map/call service/return `{ data: ... }`; dùng exception/error conventions hiện hữu.
- GET không cần idempotency key, không ghi audit, không có DB write. Không thay semantics của existing note/profile transactions/audit.
- Không thêm route/model/migration/request nếu allow-list không có. Nếu contract không thể hoàn tất trong exact files, dừng báo controller.

#### 5A. DEPENDENCIES & PHASE ENTRY GATE

**DEPENDENCIES:** FE0–FE4 PASS; existing `PtAssignmentScopeService`, Member-self Workout query services/requests, PT self profile/list/progress/notes contracts và guarded MariaDB test infrastructure.

**ENTRY_GATE:** Controller phải xác nhận baseline/snapshot, branch/state đúng, không có overlapping writer, và DB test name khớp `smart_fitness_*test*`. Không chạy test trên non-test DB.

**EXIT/FE5 GATE:** tất cả điều sau bắt buộc PASS trước Task 2:

- route list hiện đủ bốn GET routes mới;
- source-level exact assignment scope và DTO allow-list review PASS;
- focused test có full auth/scope/boundary matrix;
- full Backend suite, Pint và Composer checks PASS;
- API contract cập nhật khớp source;
- `task-1-report.md` có exact command output/results;
- Task Reviewer độc lập PASS.

**EXPECTED_REPORT:** `E:/Fitness/.fitness-sdd/fe5-all/task-1-report.md`.

#### 6. TIÊU CHÍ NGHIỆM THU & TEST (ACCEPTANCE & TESTS)

Functional acceptance:

- [ ] Current PT đọc được đúng member detail, official current/future plan, session list/detail.
- [ ] Safe DTO đúng allow-list; không leak PII/credential/internal/audit fields.
- [ ] Unauthenticated `401`; wrong role/invalid PT profile authority `403` theo convention.
- [ ] Old PT, foreign/other PT, unassigned member, future assignment và ended/expired assignment đều concealed `404` trên mọi route family thích hợp.
- [ ] Frozen-time tests chứng minh start boundary inclusive và end boundary exclusive.
- [ ] Session detail cross-member concealed `404`, kể cả PT đang được assign member khác.
- [ ] Invalid `limit/before_id` trả `422`; list bounded/cursor stable.
- [ ] Plan response là official Plan, không Proposal; future schedule có bounded window/timezone; không Membership dependency/activation/usage mutation.
- [ ] Completed session detail giữ snapshot bất biến; không route write mới.
- [ ] Regression chứng minh self profile, assigned list, progress, notes còn hoạt động; notes append-only, tối đa 100, không mutate Plan/Session.
- [ ] Existing Member-self Plan/Schedule/Session endpoints và DTOs không regression.

Commands bắt buộc (PowerShell, từ `E:/Fitness/BE`; mọi shell invocation dùng prefix `rtk` theo `C:/Users/xtung/.codex/RTK.md`). Mỗi test command tự đặt lại guarded test environment để không phụ thuộc session state:

```powershell
rtk proxy powershell -NoProfile -Command "& 'E:/Fitness/.tools/php/php.exe' artisan route:list --path=api/pt/members --json"
rtk proxy powershell -NoProfile -Command "$env:APP_ENV='testing'; $env:DB_CONNECTION='mysql'; $env:DB_DATABASE='smart_fitness_test'; $env:SMART_FITNESS_TEST_DATABASE='smart_fitness_test'; & 'E:/Fitness/.tools/php/php.exe' artisan test --filter=PtMemberWorkspaceApiTest"
rtk proxy powershell -NoProfile -Command "$env:APP_ENV='testing'; $env:DB_CONNECTION='mysql'; $env:DB_DATABASE='smart_fitness_test'; $env:SMART_FITNESS_TEST_DATABASE='smart_fitness_test'; & 'E:/Fitness/.tools/php/php.exe' artisan test --filter='(PtMemberWorkspaceApiTest|PtAssignmentApiTest|ProgressApiTest|PtProposalApiTest|TrainerProfileTest|WorkoutFoundationTest)'"
rtk proxy powershell -NoProfile -Command "$env:APP_ENV='testing'; $env:DB_CONNECTION='mysql'; $env:DB_DATABASE='smart_fitness_test'; $env:SMART_FITNESS_TEST_DATABASE='smart_fitness_test'; & 'E:/Fitness/.tools/php/php.exe' artisan test"
rtk proxy powershell -NoProfile -Command "& 'E:/Fitness/.tools/php/php.exe' vendor/bin/pint --test"
rtk proxy powershell -NoProfile -Command "& 'E:/Fitness/.tools/php/php.exe' 'E:/Fitness/.tools/composer/composer.phar' validate --strict --no-check-publish"
rtk proxy powershell -NoProfile -Command "& 'E:/Fitness/.tools/php/php.exe' 'E:/Fitness/.tools/composer/composer.phar' audit"
rtk proxy powershell -NoProfile -Command "& 'E:/Fitness/.tools/php/php.exe' 'E:/Fitness/.tools/composer/composer.phar' check-platform-reqs"
```

Từ `E:/Fitness`:

```powershell
rtk git diff --check
rtk git status --short --branch
rtk git diff -- BE/routes/api.php BE/app/Http/Controllers/Api/Pt/PtMemberWorkspaceController.php BE/app/Services/Pt/PtMemberWorkspaceService.php BE/app/Services/Workout/WorkoutPlanQueryService.php BE/app/Services/Workout/WorkoutScheduleService.php BE/app/Services/Workout/WorkoutSessionQueryService.php BE/tests/Feature/PtMemberWorkspaceApiTest.php docs/BACKEND_API_CONTRACT.md
```

Chỉ dùng schema đã được controller xác minh `smart_fitness_test`; không tự drop/create/migrate:fresh trên target. Báo cáo phải ghi exact pass/fail/count/skip và lý do mọi skip đã được phê duyệt; không sửa test để che lỗi.

#### 7. ĐỊNH DẠNG BÁO CÁO HOÀN THÀNH (CANONICAL 15-SECTION REPORT)

Ghi đúng 15 mục, không đổi tên/thứ tự/gộp:

```markdown
### Báo cáo Hoàn thành Nhiệm vụ: BE-FE5-PREREQ
1. Muc tieu module
2. File da tao
3. File da sua
4. Database lien quan
5. API da tao/sua
6. Ham chinh
   - ten ham
   - muc dich
   - cach hoat dong
7. Business Rule da xu ly
8. Authorization
9. Validation
10. Transaction / Idempotency
11. Error Case
12. Test Case
13. Test Result
14. Phan chua hoan thanh
15. Rui ro con lai
```

Sau 15 mục, thêm exact commands/results, route evidence, boundary/negative matrix, DTO allow-list, baseline preservation và concerns. Không tuyên bố PASS dựa trên UI/doc hoặc test chưa chạy.

#### 8. ĐIỀU KIỆN LEO THANG (ESCALATE WHEN)

- Cần bất kỳ file nào ngoài ALLOWED_FILES, migration/schema/model/request mới hoặc thay đổi auth/error semantics.
- Source cho thấy future schedule/branch timezone không thể lấy an toàn mà không đổi business contract.
- Exact assignment predicate xung đột giữa services/tests/canonical.
- Test DB không được provision/xác minh là isolated test DB.
- Có user/pre-existing change overlap target hunk.

**FULL_CANONICAL_REREAD:** CONDITIONAL — chỉ đọc đúng phần authority cần thiết khi có conflict/ambiguity/version mismatch; không tự đọc tràn lan hoặc đổi rule.
