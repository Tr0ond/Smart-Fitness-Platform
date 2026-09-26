# FE5-ALL Task 2 — Repair brief, round 2

`STATUS: READY_FOR_FIXER`  
`OPEN_FINDINGS: FE5-T2-R05, FE5-T2-R06, FE5-T2-R07`  
`CLOSED_FINDINGS: FE5-T2-R01, FE5-T2-R02, FE5-T2-R03, FE5-T2-R04`  
`WORKFLOW_ROLE: Implementer/Fixer`  
`MODEL: gpt-6-luna`  
`REASONING_EFFORT: max`  
`TASK: FE5-ALL (one aggregate task; repair round 2)`  
`REPORT: E:/Fitness/.fitness-sdd/fe5-all/task-2-report.md`

## Authority, entry state and edit boundary

Repair only the three Important findings in `E:/Fitness/.fitness-sdd/fe5-all/task-2-review-round-1.md`. R01–R04 were independently closed and must remain closed unless new evidence demonstrates a regression. The original FE5-ALL packet and its seven-screen aggregate scope remain binding. The Task 1 Backend prerequisite and API contract remain unchanged.

Before editing, follow `C:/Users/xtung/.codex/plugins/cache/personal/fitness-sdd/0.1.0+codex.20260926003101/skills/fitness-sdd/SKILL.md` and `references/role-contracts.md`; read `E:/Fitness/AGENTS.md`, discover/read any deeper `AGENTS.md` applicable to each path (the planner's inventory found no nested FE instruction), and read `E:/Fitness/.fitness-rules/PROJECT_CORE.md` completely. Read `E:/Fitness/.fitness-sdd/fe5-all/task-2-brief.md` and retain its complete `REQUIRED_CONTEXT`:

- `E:/Fitness/.fitness-rules/RULE_INDEX.md`
- `E:/Fitness/.fitness-rules/frontend/vue-core.md`
- `E:/Fitness/.fitness-rules/frontend/visual-ui.md`
- `E:/Fitness/.fitness-rules/domains/pt-chat.md`
- `E:/Fitness/.fitness-rules/domains/workout.md`
- `E:/Fitness/.fitness-rules/domains/auth-resource-scope.md`
- `E:/Fitness/.fitness-rules/engineering/coding-conventions.md`
- `E:/Fitness/.fitness-sdd/context/FE5_CONTEXT.md`
- `E:/Fitness/.fitness-sdd/context/TASK_PACKET_TEMPLATE.md`
- `E:/Fitness/.fitness-sdd/fe5-all/plan.md`
- `E:/Fitness/.fitness-sdd/fe5-all/task-1-report.md`
- `E:/Fitness/docs/BACKEND_API_CONTRACT.md` §4 and §6/§6.1, plus the actual affected FE source/tests and directly reused route/client helpers.

Also read `task-2-report.md`, `task-2-review.md`, `task-2-repair-brief-round-1.md`, `task-2-review-round-1.md`, `task-2-review-package.md`, `task-2-repair-review-package-round-1.md`, `baseline-status.txt`, the empty `baseline-working.patch` and `baseline-staged.patch`, `baseline-tree/`, and the `task-2-round-0-before/after`, `task-2-round-1-before/after`, and `task-2-round-2-before/` snapshots. These are evidence, not edit targets. The original product baseline was clean. The planner compared all 44 FE paths: round-0 after equals round-1 before; round-1 repair changed exactly the nine documented files; round-1 after equals round-2 before; all 44 live paths currently equal round-2 before. Recheck preservation after writing.

No new conflict or business ambiguity was found. `PROJECT_RULES.md` and `docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md` remain conditional authority: consult only relevant sections if a new conflict, ambiguity, missing coverage, or version mismatch appears, and record the reason and resolution. Binding IDs here are Q04, RULE CODE 17/18–20, and RULE GYM 13/15. Do not change any Business Rule.

Only the following original Task 2 allow-listed product/test paths may change for this repair:

- `E:/Fitness/FE/src/stores/hoi_vien_pt.store.js`
- `E:/Fitness/FE/src/stores/hoi_vien_pt.store.test.js`
- `E:/Fitness/FE/src/pages/pt/hoi_vien/hoi_vien.index.vue`
- `E:/Fitness/FE/src/pages/pt/hoi_vien/hoi_vien.index.test.js`
- `E:/Fitness/FE/src/pages/pt/ghi_chu/ghi_chu.index.vue`
- `E:/Fitness/FE/src/pages/pt/ghi_chu/ghi_chu.index.test.js`
- `E:/Fitness/FE/src/pages/pt/lich_su_tap/lich_su_tap.index.vue`
- `E:/Fitness/FE/src/pages/pt/lich_su_tap/lich_su_tap.index.test.js`

Append the round-2 change/test evidence to `E:/Fitness/.fitness-sdd/fe5-all/task-2-report.md`; the controller owns snapshots, review packages and ledger. Do not touch Backend, API contract, checkpoint, package/lock/config, rules, baseline or other product paths. Use `apply_patch` for text edits. No subagents, branch/worktree/commit/push/merge/reset/restore/checkout/stash/clean or destructive DB action. Every shell command starts `rtk` per `C:/Users/xtung/.codex/RTK.md`.

## FE5-T2-R05 — Assignment list remains stale after scope loss

**Root cause and observed path.** `hoi_vien_pt.store.js::taiDanhSachHoiVienDuocPhanCong` returns its cached list when `daTaiDanhSachHoiVien` is true and `force` is false (line 241). A member-scoped 403/404 invokes `xuLyMatPhamVi`, which purges the selected member but leaves `danhSachHoiVien` and the loaded flag intact (lines 585–619). `hoi_vien.index.vue` mounts with a non-forced load (lines 54–59) and renders the stale link (lines 92–123). The list action's own 403/404 path also leaves the previous list visible. Backend assignment scope is authoritative at request time; a cached list cannot continue advertising a revoked member.

**Exact repair.** In `hoi_vien_pt.store.js`, make scope loss invalidate the assigned-list cache and remove the revoked member immediately, or clear the list entirely until a fresh GET completes. A list-level 403/404 must clear all previously displayed assignment entries and mark the list non-authoritative while retaining an actionable error result; do not leave a stale link alongside the error. Keep generation invalidation so an older in-flight list request cannot repopulate entries after scope loss. Distinguish a successful authoritative list refresh that omits the selected member: preserve its newly returned valid entries while purging/redirecting the lost selection. In `hoi_vien.index.vue`, require an authoritative refresh on return from a member route (a forced mount request is acceptable), and prevent cached entries from rendering while that result is invalidated. Keep the existing page-level `router.replace({ name: 'ptHoiVien' })` boundary; do not import Router into the store. If a forced request can encounter an older in-flight list request, the newer request must own the generation and its result.

**Preserve.** Exact PT `/api/pt/members` contract, Backend Q04 resource authority, safe other-member entries from a successful fresh list, selected-member cache purge, logout/current-token-401/role cleanup, and ordinary Loading/Empty/Error/retry states. Do not infer current assignment from client dates or use Admin/Member-self endpoints.

**Executable regressions and acceptance.** In `hoi_vien_pt.store.test.js` and `hoi_vien.index.test.js`, load a list containing A, trigger A detail 404, follow the same redirect/remount flow as the page, and assert A disappears immediately and the return performs a new GET; when Backend returns only B, show/link only B. Hold an older list request across the revocation and show it cannot restore A. Separately start from a loaded list, force a list 403 and 404, and assert no old member card/link remains, the error is visible, and the next authorized retry can recover. Preserve the test in which a successful refreshed list excludes the selected member, including any other valid entries.

## FE5-T2-R06 — Local note draft can be posted to another member

**Root cause and observed path.** `ghi_chu.index.vue` owns `banNhap.noi_dung` as a component-local draft (line 25). `App.vue` renders the route component without a member key, so navigation from A's notes URL to B's notes URL reuses the same page instance. The watcher reloads notes (lines 74–76) but does not reset or rebind the draft. `themGhiChu` then submits that A text using current route ID B (lines 56–58). This is a wrong-resource append even though the Backend correctly authorizes B.

**Exact repair.** In `ghi_chu.index.vue`, associate the draft with a validated member ID and clear it synchronously when the route member ID changes, or keep separately owned drafts that never render/submit under another ID. Guard the submit handler so a draft owned by A cannot be posted with route ID B, including before a route watcher or notes GET settles. A same-member 422, timeout, 5xx or reconciliation must retain that member's draft and the existing unknown-outcome guidance; navigation to a different member must not transfer it. Keep one POST per deliberate submit and the existing GET reconciliation instead of blind retry. No store API or Backend change is needed for draft ownership.

**Preserve.** Append-only notes, 100-item newest-first list, positive route-ID validation, 403/404 scope purge/redirect, pending guard, and 422/timeout draft retention for the same member. Do not persist note text across logout or in shared storage.

**Executable regressions and acceptance.** In `ghi_chu.index.test.js`, mount through a real memory `RouterView` so A→B changes the params on the same component instance. Type A's draft, navigate to B, and assert the textarea no longer contains A text and cannot submit it to B. Type B's own draft, submit, and assert the single POST targets B with only B content. Exercise navigation while A's note GET or POST is pending so a late A completion cannot clear or replace B's draft. Keep the existing same-member timeout/no-blind-retry assertion and add/retain same-member 422 draft evidence.

## FE5-T2-R07 — Previous session detail remains visible for a new selection

**Root cause and observed path.** `hoi_vien_pt.store.js::taiChiTietLichSuTapHoiVien` changes request generation/loading/error at lines 484–486 but leaves `chiTietPhien` from session A. The history page sets `phienDangXem` to B (lines 59–63) while its template renders any non-null `chiTietPhien` during B loading or after B failure (lines 153–204). A's immutable snapshot is therefore presented beside B's selected/loading/error state.

**Exact repair.** In `hoi_vien_pt.store.js`, clear the visible `chiTietPhien` when starting a different session detail request (and on invalid detail input), or track its requested session ID and expose it only when it matches the active selection. Preserve the per-resource generation check so A's late response cannot commit after B starts, and preserve member-scope purge on 403/404. In `lich_su_tap.index.vue`, if the page needs a guard, render detail only when its ID matches `phienDangXem`; Loading, Error/retry and Empty should remain mutually understandable for the currently selected session. Retry B by its ID and show only B after success. Per-member immutable snapshot cache may remain internally if it never leaks into the wrong visible detail.

**Preserve.** GET-only history list/detail endpoints, descending bounded cursor, immutable completed-session data, A→B/member/logout cleanup, and no Plan/Session edit/delete controls.

**Executable regressions and acceptance.** In `hoi_vien_pt.store.test.js` and `lich_su_tap.index.test.js`, load A detail, select B with a deferred GET, and assert A's name/exercises/snapshot are absent during B loading. Reject B with a transient error: show B's error and retry, never A's snapshot. Retry B and resolve it: show B only. Resolve A late after B starts and assert it cannot overwrite B. Keep successful-empty detail/list states distinct from loading and error. A 403/404 must still purge sensitive state and redirect.

## Verification and exit gate

First run the four affected focused files from `E:/Fitness/FE`:

```powershell
rtk proxy npm run test -- src/stores/hoi_vien_pt.store.test.js src/pages/pt/hoi_vien/hoi_vien.index.test.js src/pages/pt/ghi_chu/ghi_chu.index.test.js src/pages/pt/lich_su_tap/lich_su_tap.index.test.js
```

Then run the exact 22-file Task 2 focused command in `task-2-brief.md` §6, `rtk proxy npm run test`, `rtk proxy npm run lint`, `rtk proxy npm run build`, and `rtk proxy npm ls --depth=0`. Record actual counts, failures/skips and lint warnings. Run `rtk git diff --check`, `rtk git status --short --branch`, the prohibited endpoint/mutation/logging scans, and compare every changed path against `task-2-round-2-before/` plus the original baseline. The expected repair delta is confined to the eight paths above and the appended report. Preserve Task 1 Backend changes and the nine-file round-1 repair. The controller creates the round-2 after snapshot/review package; a fresh GPT-6 Sol High Task Reviewer must independently re-review R05–R07 and confirm R01–R04 remain closed before Final Review.

Do not claim the protected-page browser smoke is complete from the prior public login-shell checks. If a safe authenticated fixture is available, verify 1440×900, 768×1024, and 390×844 focus/overflow, assignment-loss redirect, note A→B draft isolation, and history A→B loading/error/retry. Otherwise record it as an explicit verification limitation. No previous green suite closes these three state-transition findings.
