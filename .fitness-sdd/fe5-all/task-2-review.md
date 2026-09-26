VERDICT: FAIL
SPEC: FAIL
QUALITY: FAIL

# FE5-ALL Task 2 — Independent Task Review

## Findings

### FE5-T2-R01 — Important — Self-profile response envelope is consumed as the DTO

- **File/line:** `FE/src/services/huan_luyen_vien.api.js:137-140`, `FE/src/services/huan_luyen_vien.api.js:176-178`, `FE/src/pages/pt/ho_so/ho_so.index.vue:31-42`, `FE/src/pages/pt/ho_so/ho_so.index.vue:59-60`, `FE/src/pages/pt/ho_so/ho_so.index.test.js:32-36`.
- **Rule:** Task 2 brief lines 163, 198 and the Task 1 Backend contract require exact self-profile envelope handling; the Backend is authoritative.
- **Evidence:** Axios returns the response unchanged (`FE/src/services/api.js:194-196`). The profile service therefore returns Backend JSON `{ data: profileDto }`, and `TrainerProfileController.php:16,21-23` confirms that envelope for both GET and PATCH. The page passes that envelope directly to `ganBanNhap`, which reads `duLieu.introduction` and `duLieu.specialties`. With a real response both fields become empty. A save can consequently PATCH empty values and overwrite existing profile text. The page tests mock a direct DTO instead of the real `{ data: dto }` shape, so all green tests miss the integration defect.
- **Required minimal fix:** Unwrap the Backend envelope exactly once at a documented boundary and make GET/PATCH page tests use the real response shape. Assert that an existing profile pre-fills both fields and that saving one edit does not blank the untouched field. Preserve every existing Admin trainer export.

### FE5-T2-R02 — Important — Member detail renders fields absent from the safe DTO and omits the actual coaching profile

- **File/line:** `FE/src/pages/pt/hoi_vien/hoi_vien.chi_tiet.vue:115-143`, `FE/src/pages/pt/hoi_vien/hoi_vien.chi_tiet.vue:36`, `FE/src/pages/pt/hoi_vien/hoi_vien.chi_tiet.test.js:34-39`; authoritative mapper `BE/app/Services/Pt/PtMemberWorkspaceService.php:35-50,127-136`.
- **Rule:** Task 2 brief lines 164, 198 require the exact safe member detail contract and no fabricated/PII fields.
- **Evidence:** The Backend returns member `id`, `code`, `name`, `training_goal`, `training_experience`, `desired_training_days`, `session_duration_minutes`, `profile_version`, `updated_at`, plus assignment `id`, `start_at`, `end_at`. The page instead renders `member.email`, `member.phone`, `member.status`, and `assignment.trainer.name`, none of which exists in the contract, while it omits all five coaching profile fields. Its test invents `email`, `status`, and nested `trainer`, masking the mismatch. The template also calls `dinhDangNgay(assignment.end_at, 'Không giới hạn')`, but the function accepts only one argument, so an open-ended assignment is shown as `Chưa cập nhật`.
- **Required minimal fix:** Render only the allow-listed Task 1 member/assignment fields, include the actual coaching profile fields, implement the intended null end-date label, and replace the fixture/assertions with the exact Backend DTO shape.

### FE5-T2-R03 — Important — Independent progress queries do not have independent loading/error/retry states

- **File/line:** `FE/src/stores/hoi_vien_pt.store.js:185-189`, `FE/src/stores/hoi_vien_pt.store.js:310-336`, `FE/src/stores/hoi_vien_pt.store.js:340-366`, `FE/src/stores/hoi_vien_pt.store.js:370-400`, `FE/src/pages/pt/tien_do/tien_do.index.vue:61-80`, `FE/src/pages/pt/tien_do/tien_do.index.vue:110-120`, `FE/src/pages/pt/tien_do/tien_do.index.vue:194-220`.
- **Rule:** `frontend/visual-ui.md:96-110` and Task 2 brief lines 173, 200 require Loading/Data/Empty/Error with actionable retry for every independent query.
- **Evidence:** Overview, body metrics, and exercise trend share one `dangTaiTienDo` and one `loiTienDo` even though overview/body execute concurrently. The first completion can clear loading while another request remains pending, and a sibling request can clear/replace another query's error. Although `loiKeHoachTap` is read by the page, no Plan-specific error component is rendered. An exercise failure falls into the single generic error whose retry calls `taiDuLieu`; that reloads overview/body/Plan but never reissues `taiTienDoBaiTap`. The exercise section can therefore present an error as empty data and offers a retry that cannot recover the failed query.
- **Required minimal fix:** Give overview, body, exercise trend, and official Plan independent loading/error ownership; render each failed query's error state; wire each retry to the failed action (exercise retry must preserve/reload the selected exercise). Add deferred concurrent failure/completion and per-query retry tests.

### FE5-T2-R04 — Important — Note timeout reconciliation can commit stale mutation state after member change or cleanup

- **File/line:** `FE/src/stores/hoi_vien_pt.store.js:535-576`, especially `FE/src/stores/hoi_vien_pt.store.js:556-570`; missing regression coverage after `FE/src/stores/hoi_vien_pt.store.test.js:118-137`.
- **Rule:** Task 2 brief lines 156-158, 169, 199, 201 require generation-safe A→B/unmount/logout behavior and safe recovery for non-idempotent append-only note POST.
- **Evidence:** On a transient POST failure, the catch block checks ownership once, awaits `taiDanhSachGhiChu(id)`, then unconditionally assigns `loiThemGhiChu`. If selection changes from A to B, the page unmounts, or global cleanup runs while that reconciliation GET is pending, the nested read correctly rejects its stale result, but the outer mutation still writes A's `outcomeUnknown` error into the new/clean store. The current timeout test only covers reconciliation while staying on the same member; the generic cleanup test covers a progress read, not this post-catch await window.
- **Required minimal fix:** Recheck both selected member and `ghiChuMutation` generation immediately after reconciliation (and before every catch-side commit). Add deferred tests for A→B, unmount, and global/auth cleanup while the reconciliation GET is pending; preserve the no-blind-retry behavior and draft.

## Specification assessment

The following reviewed areas conform in the inspected delta and focused regressions: seven named PT routes and PT layout/menu/breadcrumb/active-state wiring; exact PT workspace URLs and HTTP methods; no `/api/admin`, Member-self `/api/workout`, Proposal, DELETE, Plan mutation, or Session mutation call; existing Admin trainer exports remain present; official Plan is the exercise authority; history is read-only; notes remain append-only; 403/404 purges selected caches and redirects; auth cleanup is idempotent; valid-current-token 401 clears the session while a late old-token 401 is ignored; logout/actor/role transitions invoke PT cleanup; no Router/Auth eager-import cycle was introduced.

SPEC is nevertheless **FAIL** because R01 and R02 violate exact Backend DTO/envelope behavior, R03 violates the mandatory independent-query state contract, and R04 violates an explicit late-response/session-cleanup invariant.

## Quality assessment

QUALITY is **FAIL** because four Important runtime/test gaps remain despite green automation. Lint exits successfully with **0 errors and 110 warnings**; 107 are auto-fixable and the warnings are concentrated in the new FE5 Vue files (`vue/singleline-html-element-content-newline` and `vue/max-attributes-per-line`). They are formatting debt rather than an independent release blocker, but materially reduce signal/readability and should be cleaned during the repair pass without weakening rules or tests. Build produced only the advisory that a generated chunk exceeds 500 kB.

Static inspection found no FE5 dark/purple styling, prohibited font/dependency/motion addition, token/PII logging, or prohibited endpoint/mutation pattern. The Light Staff CSS uses existing variables, visible focus rules and responsive breakpoints. Unit/integration assertions provide useful accessibility evidence, but they do not replace authenticated visual verification of the protected pages.

## Commands and actual results

Run independently from `E:/Fitness/FE` unless noted:

- Exact Task 2 focused command from `task-2-brief.md:211`: **PASS — 22 files, 141 tests**.
- `rtk proxy npm run test`: **PASS — 79 files, 766 tests**.
- `rtk proxy npm run lint`: **PASS exit 0 — 0 errors, 110 warnings (107 auto-fixable)**.
- `rtk proxy npm run build`: **PASS — 190 modules transformed; chunk-size advisory only**.
- `rtk proxy npm ls --depth=0`: **PASS — installed top-level tree resolved; no invalid/extraneous dependency reported**.
- Static `rtk rg` scans over FE5 sources for DELETE, `/api/workout`, `/api/admin`, Proposal, logging, prohibited libraries/fonts/dark/purple styles: **PASS**, with no prohibited runtime use found.
- From `E:/Fitness`, `rtk git diff --check`: **PASS, no output**.
- `rtk git status --short --branch`: **main worktree; expected Task 1 Backend delta, Task 2 FE delta, and controller scratch artifacts visible; no staged files**.

No product source or test was edited by this review.

## Scope integrity

- Allowed after-snapshot inventory: **44 paths**.
- Actual Task 2 delta versus before snapshot: **exactly 42 paths** — **10 modified existing + 32 new**.
- Current Task 2 source/test bytes versus after snapshot: **0 mismatches**.
- The two allowed unchanged paths are `FE/src/layouts/bo_cuc.test.js` and `FE/src/stores/xac_thuc.store.test.js`.
- No package, lockfile, build config, Backend, rules/canonical, baseline, checkpoint, plan, or Task 1 artifact was changed by Task 2.

## Browser/responsive limitation

No safe authenticated credential/session fixture was available, so this reviewer did not perform an authenticated browser smoke of the seven protected screens. The writer's public login-shell evidence at 1440x900, 768x1024, and 390x844 (including no overflow at 768) is useful but does not exercise protected loading/empty/error/retry, scope-loss redirect, focus flow, or stale-data behavior. Existing unit/integration tests plus CSS inspection are not sufficient to claim complete protected responsive smoke, particularly with the confirmed contract/state defects above. This limitation is explicitly retained for the repair/final verification phase; no credentials or mutations were attempted.

## Task 2 gate

**CLOSED — FAIL. FE5-ALL must not proceed to Final Reviewer.** Return Task 2 to the repair loop for the four minimal fixes above, add the missing contract/race/state regressions, rerun the exact focused 22-file suite, full FE suite, lint, build, dependency tree, static scans, scope comparison and repository checks, then obtain a fresh independent Task Reviewer verdict. Authenticated protected-page smoke remains required when a safe fixture becomes available and otherwise must remain a named final-review limitation.
