### Bao cao Hoan thanh Nhiem vu: BE-FE5-PREREQ

1. Muc tieu module

Implemented the source-authoritative PT member workspace prerequisite for BLOCKER-03/04/05. Four read-only GET routes now require an authenticated active PT and an exact current assignment, reuse the existing official workout DTO mappers, and do not add membership, quota, activation, proposal, or mutation behavior.

Actor/use case: active PT reads a currently assigned Member profile, official current Plan plus bounded future schedule, immutable session history, or one immutable session snapshot.

2. File da tao

- `BE/app/Http/Controllers/Api/Pt/PtMemberWorkspaceController.php`
- `BE/app/Services/Pt/PtMemberWorkspaceService.php`
- `BE/tests/Feature/PtMemberWorkspaceApiTest.php`
- `.fitness-sdd/fe5-all/task-1-report.md`

3. File da sua

- `BE/routes/api.php`: registered four PT GET routes and controller import.
- `BE/app/Services/Workout/WorkoutPlanQueryService.php`: added `HoSoHoiVien` query path while preserving Member-self delegation.
- `BE/app/Services/Workout/WorkoutScheduleService.php`: added `HoSoHoiVien` query path while preserving Member-self delegation.
- `BE/app/Services/Workout/WorkoutSessionQueryService.php`: added `HoSoHoiVien` list/detail query paths while preserving Member-self delegation.
- `docs/BACKEND_API_CONTRACT.md`: documented routes, safe DTOs, exact assignment semantics, validation, immutable history, and no-write boundary.

4. Database lien quan

No migration, schema, model, factory, or seed file was changed. Read-only queries use `nguoi_dung`, `ho_so_hoi_vien`, `phan_cong_huan_luyen_vien`, `ke_hoach_tap`/official versions, `buoi_tap_du_kien`, `phien_tap`, and immutable session child snapshots. No Membership or usage-ledger tables are queried by the new workspace service. All verification used `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_DATABASE=smart_fitness_test`, and `SMART_FITNESS_TEST_DATABASE=smart_fitness_test`; no drop/create/migrate:fresh/rollback database command was run.

5. API da tao/sua

- `GET /api/pt/members/{member}`: safe member coaching profile plus `{ id, start_at, end_at }` current assignment.
- `GET /api/pt/members/{member}/workout/plans/current`: official current Plan or `null`, existing safe schedule DTOs, and server-authoritative `schedule_window` capped at 92 business-calendar dates in `Asia/Ho_Chi_Minh`.
- `GET /api/pt/members/{member}/workout/sessions?before_id&limit`: existing safe session list DTO, descending ID cursor, `limit` 1..100.
- `GET /api/pt/members/{member}/workout/sessions/{session}`: existing immutable snapshot only when owned by the authorized Member.

There are no Plan/Session write routes in this scope. Existing PT profile, assigned-list, progress, notes, and Member-self workout routes remain unchanged in behavior.

6. Ham chinh

- `PtMemberWorkspaceController::chiTietHoiVien`: maps request actor/member route to the scoped orchestration service and `{data: ...}` envelope.
- `PtMemberWorkspaceController::keHoachHienTai`: returns official Plan/future schedule envelope.
- `PtMemberWorkspaceController::danhSachPhien`: reuses `ListWorkoutSessionsRequest` and passes bounded cursor values.
- `PtMemberWorkspaceController::chiTietPhien`: returns the authorized immutable snapshot and maps workout not-found to the existing error envelope.
- `PtMemberWorkspaceService::trongPhamVi`: one transaction-read orchestration that locks active Member, assignment history, and active PT profile, then applies the shared half-open assignment predicate.
- `PtMemberWorkspaceService::cuaSoLichTuongLai`: calculates the server-authoritative 92-day window from the current business date/timezone.
- `WorkoutPlanQueryService::hienTaiChoHoiVien`, `WorkoutScheduleService::danhSachChoHoiVien`, `WorkoutSessionQueryService::danhSachChoHoiVien`, and `chiTietChoHoiVien`: reuse existing DTO mappers for a pre-authorized `HoSoHoiVien`.

7. Business Rule da xu ly

- Q04/RULE CODE 17: exact assignment interval is `[ngay_bat_dau, ngay_ket_thuc)`, with inclusive start, exclusive end, and open `NULL` end. Future, ended, old-PT, foreign-PT, and unassigned resources are concealed.
- Q08: workout reads do not require Membership, activation, quota, or paid entitlement.
- RULE GYM 13/15: session history is read-only and completed session snapshots are not edited, deleted, or rewritten; no write endpoint was added.
- Official Plan is read from `ke_hoach_tap` and never replaced by an unconfirmed Proposal.
- Existing Member-self methods delegate to the new pre-authorized query methods, preserving their ownership behavior and DTOs.

8. Authorization

All four routes are under `auth:api` and `role:PT`. `PtAssignmentScopeService` revalidates active account/role/profile and current Member assignment in the transaction. The service does not trust Member IDs for ownership and never falls back to Member-self or Admin routes. Unauthenticated requests are `401`, wrong role/profile authority is `403`, and out-of-scope resources are concealed as `404`.

9. Validation

Route IDs are numeric. Session list reuses `ListWorkoutSessionsRequest`: `limit` is optional/default 20 and bounded 1..100; `before_id` is an optional positive integer; `member_id` and `hoi_vien_id` remain prohibited. Member detail DTO keys are exactly `id`, `code`, `name`, `training_goal`, `training_experience`, `desired_training_days`, `session_duration_minutes`, `profile_version`, `updated_at`; assignment keys are exactly `id`, `start_at`, `end_at`. Email, phone, birth date, credentials, branch authority, end reason, and audit fields are excluded.

10. Transaction / Idempotency

Each workspace GET executes its scope check and query within a short `DB::transaction(..., 3)` using the shared scope service's row locks. GETs are read-only, create no audit rows, and do not require an Idempotency-Key. Existing Member-self query delegation and existing mutation idempotency paths were preserved.

11. Error Case

- `401`: missing/invalid Bearer authentication.
- `403`: non-PT actor, revoked PT role, inactive account, or unavailable PT profile.
- `404`: missing/inactive/out-of-scope Member, old/foreign/unassigned/future/ended assignment, or cross-member session detail; workout query exceptions are mapped to the same safe envelope.
- `422`: invalid session `limit`, `before_id`, or prohibited query override.
- No membership activation, quota consumption, audit write, Plan mutation, Session mutation, or external call is performed.

12. Test Case

`PtMemberWorkspaceApiTest` covers:

- unauthenticated and non-PT denial on all four routes;
- current PT safe DTO allow-list and PII/credential exclusion;
- foreign, unassigned, future, ended, and old-PT negative matrix on all four route families;
- frozen-time assignment start-inclusive/end-exclusive boundary;
- official Plan versus bounded future schedule and no Membership side effect;
- bounded cursor list, immutable detail, cross-member concealment, and absent write route;
- invalid `limit`, `before_id`, and prohibited `member_id` validation;
- regression availability for PT profile, assigned list, progress, and notes.

13. Test Result

Exact commands and results (all shell invocations used the required `rtk` prefix):

- `rtk proxy powershell -NoProfile -Command '& { $env:APP_ENV="testing"; $env:DB_CONNECTION="mysql"; $env:DB_DATABASE="smart_fitness_test"; $env:SMART_FITNESS_TEST_DATABASE="smart_fitness_test"; & "E:/Fitness/.tools/php/php.exe" artisan test --filter=PtMemberWorkspaceApiTest }'` -> PASS, 8 tests, 57 assertions.
- `rtk proxy powershell -NoProfile -Command '& { $env:APP_ENV="testing"; $env:DB_CONNECTION="mysql"; $env:DB_DATABASE="smart_fitness_test"; $env:SMART_FITNESS_TEST_DATABASE="smart_fitness_test"; & "E:/Fitness/.tools/php/php.exe" artisan test --filter="(PtMemberWorkspaceApiTest|PtAssignmentApiTest|ProgressApiTest|PtProposalApiTest|TrainerProfileTest|WorkoutFoundationTest)" }'` -> PASS, 55 tests, 548 assertions.
- `rtk proxy powershell -NoProfile -Command '& { $env:APP_ENV="testing"; $env:DB_CONNECTION="mysql"; $env:DB_DATABASE="smart_fitness_test"; $env:SMART_FITNESS_TEST_DATABASE="smart_fitness_test"; & "E:/Fitness/.tools/php/php.exe" artisan test }'` -> PASS, 339 tests, 4,008 assertions.
- `rtk proxy powershell -NoProfile -Command '& { $env:APP_ENV="testing"; $env:DB_CONNECTION="mysql"; $env:DB_DATABASE="smart_fitness_test"; $env:SMART_FITNESS_TEST_DATABASE="smart_fitness_test"; & "E:/Fitness/.tools/php/php.exe" artisan test --filter=TestDatabaseGuard }'` -> PASS, 7 tests, 7 assertions.
- `rtk proxy powershell -NoProfile -Command '& { & "E:/Fitness/.tools/php/php.exe" vendor/bin/pint --test }'` -> PASS.
- `rtk proxy powershell -NoProfile -Command '& { & "E:/Fitness/.tools/php/php.exe" "E:/Fitness/.tools/composer/composer.phar" validate --strict --no-check-publish }'` -> PASS (`./composer.json is valid`).
- `rtk proxy powershell -NoProfile -Command '& { & "E:/Fitness/.tools/php/php.exe" "E:/Fitness/.tools/composer/composer.phar" audit }'` -> PASS (`No security vulnerability advisories found`).
- `rtk proxy powershell -NoProfile -Command '& { & "E:/Fitness/.tools/php/php.exe" "E:/Fitness/.tools/composer/composer.phar" check-platform-reqs }'` -> PASS (PHP 8.4.25 and all listed extensions success).
- `rtk proxy powershell -NoProfile -Command '& { & "E:/Fitness/.tools/php/php.exe" artisan route:list --path=api/pt/members --json }'` -> PASS. Evidence includes exactly the four new GET actions under middleware `[api, auth:api, role:PT]`: `PtMemberWorkspaceController@chiTietHoiVien`, `@keHoachHienTai`, `@danhSachPhien`, and `@chiTietPhien`.
- `rtk git diff --check` -> PASS (no output).
- Allowed-file trailing-whitespace scan -> PASS (`No trailing whitespace in 8 allowed files`).
- PHP lint on all changed/new PHP files -> PASS (`No syntax errors detected` for each of 7 PHP files).

14. Phan chua hoan thanh

No required Task 1 implementation item remains. Frontend FE5-ALL is intentionally outside this task and remains gated on this prerequisite plus independent review. No migration, schema, request, model, dependency, or external integration was added.

15. Rui ro con lai

No known functional concern after the focused and full gates. Task Reviewer/Controller acceptance is an external workflow step still required before dispatching FE5-ALL. The workspace intentionally reports only the safe DTO fields documented above; any future field expansion requires a separate contract decision and allow-list review.

Evidence bo sung: route/negative-matrix/DTO, baseline, and path preservation

- Route evidence: route scan listed the four GET routes with `auth:api` and `role:PT`; no Plan/Session write route was introduced.
- Negative matrix evidence: focused tests passed current PT, old PT, foreign PT, unassigned Member, future assignment, ended assignment, cross-member session, `401`, `403`, and `422` cases.
- DTO evidence: test asserted exact member/assignment key arrays and absence of email, phone, birth date, and password fields; Plan/schedule/session responses reuse existing safe mappers.
- Baseline evidence: `.fitness-sdd/fe5-all/baseline-status.txt` recorded branch `main`, clean product porcelain, no staged paths, and no untracked paths before workflow scratch artifacts; baseline working/staged patches were empty and baseline markers showed the three new product files absent. No baseline artifact was edited.
- Path preservation: product changes are limited to the eight allow-listed paths (five tracked modifications plus three new source/test files); the only other status entries are the pre-existing `.fitness-sdd/fe5-all/` workflow artifacts and the required report. `rtk git status --short --branch` remained on `main`; no branch/worktree/commit/push/reset/restore/checkout/stash/clean operation was used.
- Concerns: none blocking; no skipped tests, no database mutation command, and no out-of-scope product path.

## Round 1 - F-001 repair evidence

- Finding/status: F-001 Important was addressed with a test-only repair. No production defect was demonstrated and no production source, route, or API-contract file was edited in Round 1.
- Round 1 write set: `E:/Fitness/BE/tests/Feature/PtMemberWorkspaceApiTest.php` and this appended report section.
- F-001-A (`test_old_pt_after_reassignment_loses_every_workspace_route_family`): creates a real completed Session, closes PT A at the frozen current instant, starts PT B at the same instant, asserts PT A receives `404 ASSIGNMENT_NOT_FOUND` on member detail, official plan, session list, and real session detail, then sanity-checks PT B member/session access.
- F-001-B (`test_never_assigned_member_is_concealed_for_every_workspace_route_family`): keeps the second Member's assignment table at zero rows, uses a real completed Session, and asserts all four route families conceal it from the requesting PT with `404 ASSIGNMENT_NOT_FOUND`.
- F-001-C (`test_authenticated_pt_without_available_profile_is_forbidden_on_every_workspace_route`): covers both an authenticated PT with `NGUNG_NHAN_PHAN_CONG` profile status and an authenticated PT account with no trainer profile; every route returns `403 TRAINER_NOT_AVAILABLE`.
- F-001-D (`test_pending_pt_proposal_is_excluded_from_official_plan_response`): persists a real `HUAN_LUYEN_VIEN` Proposal in `CHO_XAC_NHAN` through the official POST endpoint, then proves the Plan GET returns the official Plan/version/schedule only, excludes Proposal identity/content, preserves the Proposal row/status, and creates no applied Proposal version.
- F-001-E (`test_workspace_gets_leave_pending_and_expired_membership_snapshots_unchanged`): runs independent `CHO_KICH_HOAT` and `HET_HAN` terms, captures full persisted registration/term/usage rows, calls all four GET families with a real Session ID, and compares the complete snapshot after every GET, including status, timestamps, usage rows, and activation links.
- F-001-F (`test_same_pt_assigned_to_two_members_cannot_cross_member_bind_session_detail`): assigns the requesting PT to both Members, proves the real Member B Session is readable under Member B, rejects that same Session under Member A with `404 WORKOUT_SESSION_NOT_FOUND`, and retains valid Member A access.
- F-001-G (`test_profile_list_progress_and_append_only_notes_preserve_plan_and_session_snapshots`): exercises trainer profile, assigned list, overview/body/exercise progress, notes GET, and two real note POSTs; verifies note 1 remains unchanged after append-only note 2 and full Plan/version/day/exercise/schedule plus Session/exercise/set snapshots remain unchanged after each append.

### Round 1 gate evidence

All Artisan tests explicitly used `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_DATABASE=smart_fitness_test`, and `SMART_FITNESS_TEST_DATABASE=smart_fitness_test`; no drop/create/migrate:fresh/rollback/destructive database command was run.

- `rtk proxy powershell -NoProfile -Command '& { $env:APP_ENV="testing"; $env:DB_CONNECTION="mysql"; $env:DB_DATABASE="smart_fitness_test"; $env:SMART_FITNESS_TEST_DATABASE="smart_fitness_test"; & "E:/Fitness/.tools/php/php.exe" artisan test --filter=PtMemberWorkspaceApiTest }'` -> PASS, 15 tests, 153 assertions, 0 failures, 0 skipped.
- `rtk proxy powershell -NoProfile -Command '& { $env:APP_ENV="testing"; $env:DB_CONNECTION="mysql"; $env:DB_DATABASE="smart_fitness_test"; $env:SMART_FITNESS_TEST_DATABASE="smart_fitness_test"; & "E:/Fitness/.tools/php/php.exe" artisan test --filter="(PtMemberWorkspaceApiTest|PtAssignmentApiTest|ProgressApiTest|PtProposalApiTest|TrainerProfileTest|WorkoutFoundationTest)" }'` -> PASS, 62 tests, 644 assertions, 0 failures, 0 skipped.
- `rtk proxy powershell -NoProfile -Command '& { $env:APP_ENV="testing"; $env:DB_CONNECTION="mysql"; $env:DB_DATABASE="smart_fitness_test"; $env:SMART_FITNESS_TEST_DATABASE="smart_fitness_test"; & "E:/Fitness/.tools/php/php.exe" artisan test }'` -> PASS, 346 tests, 4,104 assertions, 0 failures, 0 skipped.
- `rtk proxy powershell -NoProfile -Command '& { $env:APP_ENV="testing"; $env:DB_CONNECTION="mysql"; $env:DB_DATABASE="smart_fitness_test"; $env:SMART_FITNESS_TEST_DATABASE="smart_fitness_test"; & "E:/Fitness/.tools/php/php.exe" artisan test --filter=TestDatabaseGuard }'` -> PASS, 7 tests, 7 assertions, 0 failures, 0 skipped.
- `rtk proxy powershell -NoProfile -Command '& { & "E:/Fitness/.tools/php/php.exe" vendor/bin/pint --test }'` -> PASS.
- `rtk proxy powershell -NoProfile -Command '& { & "E:/Fitness/.tools/php/php.exe" "E:/Fitness/.tools/composer/composer.phar" validate --strict --no-check-publish }'` -> PASS (`./composer.json is valid`).
- `rtk proxy powershell -NoProfile -Command '& { & "E:/Fitness/.tools/php/php.exe" "E:/Fitness/.tools/composer/composer.phar" audit }'` -> PASS (`No security vulnerability advisories found`).
- `rtk proxy powershell -NoProfile -Command '& { & "E:/Fitness/.tools/php/php.exe" "E:/Fitness/.tools/composer/composer.phar" check-platform-reqs }'` -> PASS (PHP 8.4.25 and all listed extensions successful).
- `rtk proxy powershell -NoProfile -Command '& { & "E:/Fitness/.tools/php/php.exe" artisan route:list --path=api/pt/members --json }'` -> PASS. The four new workspace actions remain GET-only and use `[api, auth:api, role:PT]`: `chiTietHoiVien`, `keHoachHienTai`, `danhSachPhien`, and `chiTietPhien`; no Plan/Session write route was added.
- `rtk powershell -NoProfile -Command '& { $php="E:/Fitness/.tools/php/php.exe"; $paths=@("E:/Fitness/BE/routes/api.php","E:/Fitness/BE/app/Http/Controllers/Api/Pt/PtMemberWorkspaceController.php","E:/Fitness/BE/app/Services/Pt/PtMemberWorkspaceService.php","E:/Fitness/BE/app/Services/Workout/WorkoutPlanQueryService.php","E:/Fitness/BE/app/Services/Workout/WorkoutScheduleService.php","E:/Fitness/BE/app/Services/Workout/WorkoutSessionQueryService.php","E:/Fitness/BE/tests/Feature/PtMemberWorkspaceApiTest.php"); foreach($p in $paths){ & $php -l $p } }'` -> PASS, no syntax errors detected for all seven changed/new PHP files.
- `rtk git diff --check` -> PASS (no output).

Route/negative-matrix/DTO evidence remains source-compatible: current, reassigned-old, foreign, never-assigned, future, ended, profile-unavailable, and cross-member Session cases are executable; the member and assignment allow-list assertions remain exact and reject PII/credential fields; official Plan responses use the existing safe DTO and exclude pending Proposal data.

Baseline/path preservation: `rtk git diff --no-index --exit-code` returned exit code 0 for each production route, controller, workspace service, Plan service, Schedule service, Session service, and API-contract path compared with `.fitness-sdd/fe5-all/task-1-round-1-before/`. The feature test is the only product file with a Round 1 delta; pre-existing Round 0 product changes remain present, no unexpected product path was added, and the branch remains `main`. No commit, push, merge, reset, restore, checkout, stash, clean, or destructive DB operation was used.

Concerns: none blocking; no skipped tests, no production defect found, and no Business Rule or documented status-code behavior was changed.

## Round 2 - F-002 test-environment recovery evidence

- Owner explicitly approved provisioning a fresh disposable test schema named `smart_fitness_fe5_round2_test`.
- Preflight was clean: the exact schema did not exist, no PHP/Artisan process was running, and no MariaDB connection targeted a `smart_fitness_*test*` schema.
- The controller created only `smart_fitness_fe5_round2_test` with `utf8mb4` / `utf8mb4_unicode_ci`; the contaminated `smart_fitness_test` and all development/production databases were left untouched.
- Migration completed sequentially through `2026_09_10_000061_m061_them_trang_thai_nhom_co` (M001-M061).
- Deterministic seeding completed successfully, including 1,324 exercises, 28 equipment rows, 50 muscle groups, and 2,648 media references with zero missing.
- Test database guard on the disposable schema: PASS, 7 tests, 7 assertions.
- Exactly one full Backend suite was then run sequentially with `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_DATABASE=smart_fitness_fe5_round2_test`, and `SMART_FITNESS_TEST_DATABASE=smart_fitness_fe5_round2_test`: PASS, 346 tests, 4,104 assertions, exit code 0.
- No focused/related/full suite overlapped the full run. No product source, test source, assertion, migration, seeder, package, config, or contract file changed during Round 2 recovery.
- No row deletion, truncate, drop, rollback, `migrate:fresh`, reset, restore, checkout, stash, clean, commit, push, branch, or worktree operation was used.
