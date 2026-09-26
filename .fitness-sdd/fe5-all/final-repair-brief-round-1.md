# FE5-ALL — Consolidated final repair brief, round 1

**Status:** READY_FOR_FIXER  
**Writer:** fresh or available `gpt-6-luna` with reasoning effort `max`, one writer for this wave, `fork_turns: "none"`.  
**Scope:** close `FE5-FINAL-001` (Important) and the two small, Task 2 allowed-file findings `FE5-FINAL-002` and `FE5-FINAL-003` together. `FE5-T2-R01`–`R07` and Task 1 `F-001`/`F-002` remain CLOSED. No other finding is reopened.

## Entry evidence and authority

- `E:/Fitness/.fitness-sdd/fe5-all/final-review.md` is the exact open-finding source. The final gate is FAIL; checkpoint transition is not permitted yet.
- Task 2 Round 2 review is PASS; final-review package and `progress.md` document all prior gates. The initial product baseline was clean, with zero-byte `baseline-working.patch` and `baseline-staged.patch`. The controller captured all 44 FE targets in `final-repair-round-1-before/`, matching `task-2-round-2-after/`. Read-only comparison of the six targets below against the round-1-before snapshot returned MATCH for each.
- No new business-rule conflict or ambiguity was found in these three defects. `PROJECT_RULES.md` and the Vue plan remain conditional authorities; no full reread is required. The exact Backend contract in `docs/BACKEND_API_CONTRACT.md` §6.1 and current `PtAssignmentService::duLieuPhanCong`, `PtMemberWorkspaceService::cuaSoLichTuongLai`, and `WorkoutScheduleService::SO_NGAY_TOI_DA` resolve the two displayed-field defects.
- Actor/use case: authenticated PT views a currently assigned Member's read-only history, assigned list, and official Plan. Backend retains assignment authority (Q04/RULE CODE 17), history is immutable (RULE GYM 13/15), and `schedule_window` is server authoritative. There is no database write, new API, transaction, idempotency key, or audit change in this repair.

## Required context for the Luna Max fixer

Independently discover applicable `AGENTS.md` files and read `E:/Fitness/AGENTS.md`; there is no nested FE `AGENTS.md` in the current tree. Read to completion:

- `C:/Users/xtung/.codex/RTK.md`
- `E:/Fitness/.fitness-rules/PROJECT_CORE.md`
- `E:/Fitness/.fitness-rules/RULE_INDEX.md`
- `E:/Fitness/.fitness-rules/frontend/vue-core.md`
- `E:/Fitness/.fitness-rules/frontend/visual-ui.md`
- `E:/Fitness/.fitness-rules/domains/pt-chat.md`
- `E:/Fitness/.fitness-rules/domains/workout.md`
- `E:/Fitness/.fitness-rules/domains/auth-resource-scope.md`
- `E:/Fitness/.fitness-rules/engineering/coding-conventions.md`
- `E:/Fitness/.fitness-sdd/context/FE5_CONTEXT.md`
- `E:/Fitness/.fitness-sdd/context/TASK_PACKET_TEMPLATE.md`
- `E:/Fitness/.fitness-sdd/fe5-all/task-2-brief.md` and its exact `REQUIRED_CONTEXT`
- `E:/Fitness/.fitness-sdd/fe5-all/plan.md`
- `E:/Fitness/.fitness-sdd/fe5-all/task-1-report.md`
- `E:/Fitness/.fitness-sdd/fe5-all/task-2-report.md`
- `E:/Fitness/.fitness-sdd/fe5-all/task-2-review-round-2.md`
- `E:/Fitness/.fitness-sdd/fe5-all/final-review.md`
- `E:/Fitness/.fitness-sdd/fe5-all/final-review-package.md`
- `E:/Fitness/.fitness-sdd/fe5-all/progress.md`
- Relevant `E:/Fitness/docs/BACKEND_API_CONTRACT.md` sections: `/profile/trainer`, `/pt/members`, and §6.1 PT member detail, official Plan/schedule window, immutable history.

Inspect live source/tests, `E:/Fitness/.fitness-sdd/fe5-all/baseline-status.txt`, baseline patches/tree, `task-2-round-2-after/`, and `final-repair-round-1-before/` before editing. Retain `FULL_CANONICAL_REREAD: CONDITIONAL`; if a new authority conflict appears, consult only the exact relevant canonical section and notify the controller before changing affected behavior.

## Exact allowed product/test paths

Only these six existing Task 2 FE paths may change in this wave:

1. `E:/Fitness/FE/src/pages/pt/lich_su_tap/lich_su_tap.index.vue`
2. `E:/Fitness/FE/src/pages/pt/lich_su_tap/lich_su_tap.index.test.js`
3. `E:/Fitness/FE/src/pages/pt/hoi_vien/hoi_vien.index.vue`
4. `E:/Fitness/FE/src/pages/pt/hoi_vien/hoi_vien.index.test.js`
5. `E:/Fitness/FE/src/pages/pt/ke_hoach_tap/ke_hoach_tap.index.vue`
6. `E:/Fitness/FE/src/pages/pt/ke_hoach_tap/ke_hoach_tap.index.test.js`

The fixer may append round-1 implementation and actual test evidence to `E:/Fitness/.fitness-sdd/fe5-all/task-2-report.md` as the workflow report. All other source, test, Backend, API contract, checkpoint, package/lock/config, rules, baseline, and snapshot paths are read-only. The controller owns the post-writer snapshot/delta and independent final rereview. Do not create a branch/worktree, commit, push, reset, restore, checkout, stash, clean, or perform a destructive DB action. Use `apply_patch` for text edits; every shell command starts with `rtk`.

## Finding-to-fix map

### FE5-FINAL-001 — Important: load-more scope loss leaves a revoked-member page mounted

**Root cause.** In `lich_su_tap.index.vue`, `taiThem()` awaits `store.taiLichSuTapHoiVien(memberId.value, { append: true })` and ignores its result. The store already calls `xuLyMatPhamVi()` on a current member-scoped 403/404: it invalidates generations, clears selected/member caches and assigned-list authority, and returns `{ scopeLost: true, memberId }`. Initial history and detail handlers redirect, but the load-more branch does not. A stale request superseded by navigation returns `null`, so distinguish it from a current scope-loss result.

**Minimal correction.** Capture the append action result in `taiThem()` and, only when that result reports `scopeLost`, await `router.replace({ name: 'ptHoiVien' })`. Keep the existing store purge and router/store separation. Do not introduce a global 403 redirect or a second purge. Preserve cursor append and error/retry behavior for network/5xx; a stale/null result must not navigate the user away from a newer member selection.

**Regression in the history page test.** Mount via `RouterView` and an actual memory router. Begin with a cursor-bearing Member 7 history response (`next_cursor.before_id`, or an equivalent contract-valid cursor) and visible subnavigation; on click, make the second GET reject with 403 and 404 in separate cases. Assert `before_id` is sent, route becomes `ptHoiVien` by replace, selected ID and history/member-sensitive cache are purged, and revoked-member tabs/session content disappear. Also prove ordinary append adds the next page without redirect, and transient 5xx/network append failure retains a manual retry path and does not redirect. Keep existing immutable snapshot and A/B late-response tests passing.

**Acceptance:** a current 403/404 during load-more immediately removes the revoked view; a stale A response cannot redirect B; successful append and transient retry still work.

### FE5-FINAL-002 — Minor: unsupported email fallback on the assigned list

**Root cause.** `hoi_vien.index.vue::layEmail()` reads `assignment.member.email`, but `GET /pt/members` returns member `id`, `code`, and `name` (as shown by `PtAssignmentService::duLieuPhanCong`). Thus every real row prints “Chưa cập nhật” for a field that is intentionally absent from the safe DTO.

**Minimal correction.** Remove `layEmail()` and replace its display line with an allow-listed `member.code` label, conditional on a real code; omit the line if the code is absent. Do not request or infer email, expand the DTO, or alter the service/store. Keep assignment dates, status, links, loading/error/empty behavior intact.

**Regression in the assigned-list page test.** Use a contract-accurate fixture `{ member: { id, code, name }, start_at, ... }` without email. Assert the code is displayed, no fabricated “Chưa cập nhật” email line appears, and the existing assigned-member link/authorization recovery tests still pass. A missing code should omit only the optional code line.

**Acceptance:** assigned cards render only safe DTO fields and no synthetic email placeholder.

### FE5-FINAL-003 — Minor: hard-coded schedule-window duration in empty copy

**Root cause.** `ke_hoach_tap.index.vue` renders “cửa sổ 90 ngày” when `future_schedule` is empty, while the Backend's inclusive window covers up to 92 calendar dates and supplies `schedule_window.from/to/timezone`. The card already displays `from` and `to` from the response.

**Minimal correction.** Replace the empty-state description with duration-free wording tied to the displayed window, such as “Không có lịch tập trong khoảng ngày hiển thị.” Keep the existing server-provided date range; do not calculate a client duration, hard-code 92, or change Backend/service contracts.

**Regression in the Plan page test.** Return an official Plan with `future_schedule: []` and a non-default `schedule_window` fixture. Assert the empty-state wording and the exact response `from`/`to` appear; assert no “90 ngày” (or other hard-coded day count). Retain the existing read-only official Plan assertion.

**Acceptance:** UI truthfully describes any server-provided schedule window without inventing a duration.

## Preservation and verification

- Preserve Q04/RULE CODE 17 scope handling and the current store's generation/cache cleanup. Keep history GET-only and completed-session snapshots immutable. Keep official Plan distinct from Proposal and notes append-only. Do not change R01–R07 fixes, Task 1 routes/DTOs, role guard, auth cleanup, or other member pages.
- Test current 403 and 404 on load-more, an ordinary next page, transient retry, stale/superseded response; test contract-accurate member fields and the server window. Avoid tests that merely mirror a new branch without exercising the user-visible route and state transition.
- The fixer appends exact changed paths, commands, pass/fail counts and any limitation to `task-2-report.md`; do not claim acceptance from old runs. The controller compares all 44 targets against `final-repair-round-1-before/` and dispatches a fresh independent GPT-6 Sol High final rereviewer after the writer. The final gate remains closed until that review and final-code-state checks pass.

Run from `E:/Fitness/FE` after the last edit:

```powershell
rtk proxy npm run test -- src/pages/pt/lich_su_tap/lich_su_tap.index.test.js src/pages/pt/hoi_vien/hoi_vien.index.test.js src/pages/pt/ke_hoach_tap/ke_hoach_tap.index.test.js
rtk proxy npm run test -- src/services/huan_luyen_vien.api.test.js src/services/hoi_vien_pt.api.test.js src/services/tien_do.api.test.js src/services/ke_hoach_tap_pt.api.test.js src/services/lich_su_tap_pt.api.test.js src/services/ghi_chu.api.test.js src/stores/hoi_vien_pt.store.test.js src/stores/xac_thuc.store.test.js src/router/dieu_huong_pt.test.js src/router/index.test.js src/router/bao_ve_tuyen_duong.test.js src/layouts/bo_cuc.test.js src/pages/xac_thuc.test.js src/components/pt/thanh_dieu_huong_hoi_vien.test.js src/components/pt/the_tien_do.test.js src/pages/pt/ho_so/ho_so.index.test.js src/pages/pt/hoi_vien/hoi_vien.index.test.js src/pages/pt/hoi_vien/hoi_vien.chi_tiet.test.js src/pages/pt/tien_do/tien_do.index.test.js src/pages/pt/ke_hoach_tap/ke_hoach_tap.index.test.js src/pages/pt/lich_su_tap/lich_su_tap.index.test.js src/pages/pt/ghi_chu/ghi_chu.index.test.js
rtk proxy npm run test
rtk proxy npm run lint
rtk proxy npm run build
rtk proxy npm ls --depth=0
```

Run from `E:/Fitness`:

```powershell
rtk git diff --check
rtk git status --short --branch
```

Backend product/test code is unchanged in this wave; prior guarded Backend evidence remains recorded in `final-review-package.md`. Protected authenticated browser smoke still lacks a safe PT fixture and must be reported as unverified unless the controller supplies one. This limitation does not excuse the executable 403/404 page regression.

**Fixer return:** `DONE`, `DONE_WITH_CONCERNS`, `NEEDS_CONTEXT`, or `BLOCKED`; `E:/Fitness/.fitness-sdd/fe5-all/task-2-report.md`; exact changed files; one-line test result; genuine concerns.
