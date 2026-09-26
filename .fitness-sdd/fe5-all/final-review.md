VERDICT: FAIL
SPEC: FAIL
QUALITY: FAIL
FE5_CHECKPOINT_TRANSITION_PERMITTED: NO

# FE5-ALL — Independent whole-phase final review

FINDINGS
- ID: FE5-FINAL-001
  Severity: Important
  File: E:/Fitness/FE/src/pages/pt/lich_su_tap/lich_su_tap.index.vue
  Line: 69
  Rule: E:/Fitness/.fitness-sdd/context/FE5_CONTEXT.md §3.1 and §5; E:/Fitness/.fitness-sdd/fe5-all/task-2-brief.md §5 (member-scoped 403/404 must purge and redirect to `ptHoiVien`); Q04/RULE CODE 17.
  Evidence: The initial history load and session-detail handlers redirect after a 403/404, but `taiThem()` at lines 69–71 only awaits the member-scoped append request. When an assignment is revoked before a “Tải thêm” request, `hoi_vien_pt.store.js::taiLichSuTapHoiVien` calls `xuLyMatPhamVi`, which purges selected data and returns `{ scopeLost: true }`; `taiThem()` ignores that result and the page stays at `/pt/hoi-vien/:id/lich-su-tap` with the revoked member's subnavigation. The Axios response handler only routes 401 globally. The history-page tests have no load-more 403/404 case; an independent run of its existing tests and the store tests passed 25/25 without exercising this transition.
  Required fix: Handle a scope-loss result or the resulting 403/404 in `taiThem()` with `router.replace({ name: 'ptHoiVien' })`, while retaining the store purge. Add a page regression that starts from a cursor-bearing list, revokes scope on load-more, and asserts immediate purge and redirect. Verify the ordinary append and transient retry paths still work.

- ID: FE5-FINAL-002
  Severity: Minor
  File: E:/Fitness/FE/src/pages/pt/hoi_vien/hoi_vien.index.vue
  Line: 42
  Rule: E:/Fitness/.fitness-sdd/fe5-all/task-2-brief.md §5 (render assigned-list data from the PT contract; no fabricated fields).
  Evidence: `layEmail()` reads `assignment.member.email` and renders “Chưa cập nhật” for every row. `PtAssignmentService::duLieuPhanCong` returns member `id`, `code`, and `name`, with no email, so this line does not represent a known missing email value.
  Required fix: Remove this unsupported email line or display an actual allow-listed field such as member code.

- ID: FE5-FINAL-003
  Severity: Minor
  File: E:/Fitness/FE/src/pages/pt/ke_hoach_tap/ke_hoach_tap.index.vue
  Line: 158
  Rule: E:/Fitness/docs/BACKEND_API_CONTRACT.md §6.1 and E:/Fitness/.fitness-sdd/fe5-all/task-2-brief.md §5 (server-authoritative schedule window).
  Evidence: The empty state says “cửa sổ 90 ngày” while the Backend returns a 92-calendar-day inclusive `schedule_window` from `WorkoutScheduleService::SO_NGAY_TOI_DA` and `PtMemberWorkspaceService::cuaSoLichTuongLai`.
  Required fix: Use the returned window dates or omit a hard-coded day count.

SPEC / QUALITY ASSESSMENT
- The Backend prerequisite is otherwise integrated correctly: four new GET-only PT routes under `auth:api` and `role:PT`; shared half-open current-assignment scope; exact safe member/assignment DTO; official Plan separate from pending Proposal; bounded future schedule; member-bound immutable Session list/detail. Existing Member-self query methods delegate to the same mappers. Focused Backend tests cover old/foreign/never-assigned/future/ended PT scope, interval boundaries, unavailable PT authority, cross-member Session concealment, unchanged Membership state, and append-only notes without Plan/Session mutation.
- The Frontend has all seven PT routes, PT-only metadata, matching PT-scoped services, Admin trainer export preservation, member cache generation checks, auth cleanup, scope-loss handling on the other page requests, official-Plan exercise selection, read-only history, and append-only note submission with reconciliation. Task 2 R01–R07 remain closed in the inspected live source. FE5-FINAL-001 is a new missed branch of the original scope-loss requirement and makes both specification and quality fail despite passing current suites.
- The original baseline was clean with empty staged/working patches. Read-only byte comparison confirmed the 5 existing Task 1 and 12 existing Task 2 targets match their original baseline copies; the remaining 3 Backend and 32 FE targets had absent markers. Current files match all 8 Task 1 round-1-after and all 44 Task 2 round-2-after snapshots byte-for-byte. Git status contains only the declared Backend/API-contract and FE5 product/test paths plus `.fitness-sdd/fe5-all/`; branch remains `main`, staged diff is empty, and no checkpoint/package/lock/config/rule path changed.

TEST EVIDENCE
- Independently ran from `E:/Fitness/FE`: `rtk proxy npm run test -- src/pages/pt/lich_su_tap/lich_su_tap.index.test.js src/stores/hoi_vien_pt.store.test.js` — PASS, 2 files/25 tests, exit 0. This suite has no load-more scope-loss assertion.
- Independently ran `rtk git diff --check` — PASS, no whitespace error; inspected `rtk git status --short --branch`, `rtk git diff --name-only`, empty `rtk git diff --cached --name-only`, source-level PT endpoint/mutation/logging scan, and byte comparisons above.
- Inspected final live-source FE evidence after the last product edit: affected 4 files/36 tests PASS; exact focused 22 files/169 tests PASS; full 79 files/794 tests PASS, with no reported failures/skips; lint exit 0 with 0 errors/110 formatting warnings; build PASS (190 modules, 581.63 kB chunk advisory); `npm ls --depth=0` PASS.
- Inspected the controller's sequential final Backend evidence after the last product edit on owner-approved disposable `smart_fitness_fe5_round2_test`, with `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_DATABASE=smart_fitness_fe5_round2_test`, and `SMART_FITNESS_TEST_DATABASE=smart_fitness_fe5_round2_test`: guard 7/7 assertions PASS; focused 62 tests/644 assertions PASS; full 346 tests/4,106 assertions PASS. Pint, Composer strict validation/audit/platform checks, route scan and diff check PASS. These recorded passes do not cover FE5-FINAL-001.
- No protected authenticated browser smoke was performed because no safe PT session/credential fixture was available. Public login-shell viewport evidence does not verify the seven protected pages.

AUTHORITY CHECK
- Targeted escalation was made for the ambiguity of historical `HUY`/`DA_THAY_THE` schedules in a future window: `PROJECT_RULES.md` Q07B (line 1260) requires preserving those rows, and `docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md` §31 FE-5 (lines 1486–1499) does not prescribe a filtered PT schedule DTO. The source contract returns each schedule's status; this did not establish another blocking violation. No full canonical reread was needed.

RESIDUAL RISKS
- Protected-page keyboard/focus, 1440/768/390 responsive behavior, live error/retry and assignment-loss transitions remain unverified in an authenticated browser. This is a named verification limitation, separate from the demonstrated FE5-FINAL-001 source defect.
- The 110 lint formatting warnings and production chunk-size advisory are nonblocking quality debt. The disposable test schema was retained; the earlier contaminated `smart_fitness_test` was not cleaned during recovery.

GATE CONCLUSION
- Final-review gate is closed. Do not advance `docs/VUE_WEB_COMPLETION_CHECKPOINT.md` until FE5-FINAL-001 is repaired and a fresh independent review confirms its regression and final-code-state gates. The two Minor findings may be handled with that repair or retained as explicit follow-up debt.
