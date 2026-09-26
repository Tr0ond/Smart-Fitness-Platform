# FE6-ALL — authorized consolidated final repair

**STATUS: READY.** This brief supersedes `final-repair-brief.md` for the single final repair wave. The project owner explicitly authorized a dedicated PT Direct history GET, preservation of the existing Member read, Backend dual-role/scope regression, FE adaptation, and fixes F-006/F-007. That authorization removes the old `WAITING_DEPENDENCY` and Backend-edit prohibition **only for the paths and behavior below**. F-005/F-006/F-007 remain open until implementation, final-state tests, and independent final re-review pass. F-001–F-004 remain closed and their changes/tests must survive.

## Authority, context, and preservation

- Read to EOF before writing: `E:/Fitness/AGENTS.md`, `E:/Fitness/BE/AGENTS.md`, `C:/Users/xtung/.codex/RTK.md`, `E:/Fitness/.fitness-rules/PROJECT_CORE.md`, `E:/Fitness/.fitness-rules/RULE_INDEX.md`, `E:/Fitness/.fitness-sdd/context/FE6_CONTEXT.md`, `E:/Fitness/.fitness-sdd/fe6-all/task-1-brief.md`, its exact `REQUIRED_CONTEXT` modules, `E:/Fitness/.fitness-rules/engineering/testing-definition-of-done.md`, Fitness SDD `SKILL.md` and `references/role-contracts.md`, `final-review.md`, this brief, `task-1-report.md`, `plan.md`, and baseline/snapshot artifacts. Discover any deeper applicable `AGENTS.md`. Do not load `ai-proposal.md` or the complete canonical/Vue plan.
- Canonical escalation for F-005 was justified by the code/FE contract conflict and supported multiple roles. Consulted `PROJECT_RULES.md` Sections 12, 14, 14.1, 17/17.1, RULE CODE 17 and Vue plan Sections 17.8, 31 FE-6, 38A FE-6. The resolution is an explicit PT read path; it preserves Member ownership and historical PT ledger scope. No Q/GYM/CODE business rule changes.
- Controller snapshots **all exact planned targets** into a new `task-1-round-final-before/` with absent markers before the one writer. Compare against `baseline-status.txt`, `baseline-working.patch`, `baseline-staged.patch`, `baseline-tree/`, round-0 and round-1 before/after snapshots, current tree, and task delta. Initial product work was clean; current FE6 work spans 23 task-owned paths. Preserve every existing FE hunk, especially F-001–F-004, and all unrelated user work. No reset/restore/checkout/stash/clean, commit, push, worktree, migration, provider, dependency, or checkpoint edit.
- **One aggregate Luna Max repair writer** handles all F-005/F-006/F-007, Backend then FE in the same wave; no parallel writers or partial READY subset. Controller owns snapshot, dispatch, and test-environment verification. The writer appends exact results to `E:/Fitness/.fitness-sdd/fe6-all/task-1-report.md`; a fresh Sol High final reviewer examines all three findings and the full task delta.

### Exact repair allow-list

Backend code/tests (F-005 only):

1. `E:/Fitness/BE/routes/api.php`
2. `E:/Fitness/BE/app/Http/Controllers/Api/Pt/PtDirectServiceController.php`
3. `E:/Fitness/BE/app/Services/Pt/PtDirectService.php`
4. `E:/Fitness/BE/tests/Feature/PtDirectServiceTest.php`

Frontend code/tests:

5. `E:/Fitness/FE/src/services/buoi_huan_luyen.api.js`
6. `E:/Fitness/FE/src/services/buoi_huan_luyen.api.test.js`
7. `E:/Fitness/FE/src/pages/pt/buoi_huan_luyen/buoi_huan_luyen.index.test.js` (focused PT rendering/refetch regression only; page/store code should require no change because they call the service)
8. `E:/Fitness/FE/src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.tao_moi.vue`
9. `E:/Fitness/FE/src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.tao_moi.test.js`
10. `E:/Fitness/FE/src/components/PT/khung_xem_de_xuat.vue`
11. `E:/Fitness/FE/src/components/PT/khung_xem_de_xuat.test.js`

API documentation and report only:

12. `E:/Fitness/docs/BACKEND_API_CONTRACT.md` — document the two read paths, exact actor scopes, latest-100 envelope, and dual-role semantics.
13. `E:/Fitness/.fitness-sdd/fe6-all/task-1-report.md` — append repair/evidence in the 15-section DoD structure; no claim of completion before review.

No other product/test/documentation path is allowed. A newly necessary file requires controller scope review before editing. The prior task brief's other FE files remain preserved, not fresh repair targets.

## F-005 — dedicated PT history read and dual-role scope

**Root cause.** `GET /api/pt/direct-sessions` uses `role:MEMBER,PT`, and `PtDirectService::layLichSu()` checks MEMBER before PT. A dual-role account therefore gets its Member slice (or `MEMBER_PROFILE_REQUIRED`) on the PT Web history page. The page and unknown-outcome refetch expect that trainer's own latest 100 across Members; existing Backend tests use separate actors.

**Binding API decision.** Add `GET /api/pt/direct-sessions/history`, no query/body/actor selector, under `auth:api` and `role:PT`. Return HTTP 200 `{data: DirectHistory[]}` ordered `xac_nhan_luc DESC, id DESC`, max 100, using the existing exact row DTO (`history_id`, `assignment_id`, `member_id`, `trainer_id`, `term_id`, `usage_id`, `status`, `completed_at`, `confirmed_at`, `notes`). Bind `trainer_id` from the authenticated user's `ho_so_huan_luyen_vien.id` on the server. Verify the effective PT grant in the service as well as route middleware; require the profile to exist, returning the established `404 TRAINER_PROFILE_REQUIRED` when absent. Authentication failure is 401; absent/revoked PT role is 403. No client `trainer_id`, `member_id`, role header, actor query, or selected Member filter may govern this route. The same authenticated dual-role token must be able to call both read paths: the new path returns **own PT-confirmed rows**, while the existing `GET /api/pt/direct-sessions` retains its current Member-first behavior and Member-owner rows. PT-only behavior on the legacy route stays as implemented. Do not reorder its branches or globally prefer PT.

**Exact Backend logic.** Register the new static route in `routes/api.php` alongside the existing GET/POST. Add a separate controller action (for example `lichSuCuaPt`) with the same safe response/error envelope. Add a separate service entry point (for example `layLichSuCuaPt`) that resolves effective PT role and profile and filters the ledger by authenticated trainer profile ID, regardless of any MEMBER grant. Reuse a private history query/DTO mapper if useful; preserve legacy `layLichSu` results and all POST completion paths byte-for-byte in behavior. PT historical own ledger rows may span Members and past assignments; this read grants no access to arbitrary Member Plan/chat/profile resources. Do not add assignment lookup, Membership/quota gate, mutation, or pagination to the history GET.

**Actor / use case / rules / DB / auth / validation / errors.** Actor is authenticated PT viewing and reconciling direct completions; the Member remains owner of their own read. Binding rules: Q04/Q06/Q12, RULE GYM 02/23, RULE CODE 13/17/18/19/20. Read only `phan_quyen_nguoi_dung`/`vai_tro`, `ho_so_huan_luyen_vien`, and `lich_su_su_dung_huan_luyen_vien` plus existing DTO relationships; no schema migration or new write. Auth and resource scope are server-side, with 401/403/404 as above; GET has no caller-supplied identity input and no 422 field validation. Role revocation must immediately deny the new route; Member-only accounts must not see PT rows. Existing Member history continues to use authenticated `ho_so_hoi_vien`, including when the same account also has PT. An absent Member profile on the legacy dual-role read retains `MEMBER_PROFILE_REQUIRED` rather than silently switching actor.

**Concurrency / idempotency / transaction / audit.** New GET is read-only, repeatable, and has no key, lock, transaction, quota/activation, or audit write. Concurrent completion can change the newest-100 slice; absence from a capped refetch is inconclusive. Preserve POST's frozen body/key replay, exact-term quota lock, activation transaction and audit semantics. No history deletion or rewrite.

**Focused regression and acceptance.** In `PtDirectServiceTest.php`, construct one real account with active `MEMBER` and `PT` grants and both profiles, distinct from at least one other Member and trainer. Complete a session as the dual-role PT and create a separate completed row in which that account is the **Member** under another PT. Assert the new route returns only the trainer-owned row, the old route returns only the self-Member row, and each excludes the other by `history_id`/`trainer_id`/`member_id`; both use the same token. Also cover dual-role without Member profile on the PT route, PT-only route success, Member-only 403, unauthenticated 401, missing PT profile 404, revoked PT role 403 even with MEMBER still active, and no cross-trainer leakage. Preserve the existing history test. Document and assert latest-100 order/limit if fixture size is practical; otherwise preserve existing query and test representative ordering without making an unrelated fixture-heavy test.

**Frontend adaptation.** `taiLichSuBuoiHuanLuyen()` in the allowed FE service calls `/pt/direct-sessions/history` for every load/refetch, with no actor/client identity argument. Keep response normalization, current selected-Member filtering, truthful latest-100 copy, request generations, 403/404 cleanup, and same-key/same-body retry. In the service test assert exact URL and no client selector; in the page test supply a PT-owned list containing selected and other Member rows and verify only selected rows render and refetch after an ambiguous completion still uses the PT service. A successful POST alone or a PT-only mocked read does not close F-005.

## F-006 — official decimal weight in proposal draft

**Root cause.** `WorkoutPlanQueryService` returns `BaiTapTrongKeHoach.khoi_luong_muc_tieu_kg` from Eloquent `decimal:2` as a JSON string such as `"20.00"`. The composer `saoChepNgayTap` copies this string unchanged; `de_xuat.api.js` correctly requires a finite numeric `target_weight_kg` or null. An otherwise untouched official Plan fails local 422.

**Exact FE logic.** Normalize only the official-plan weight while copying each exercise into the draft in `de_xuat_ke_hoach_tap.tao_moi.vue`: accept a syntactically valid decimal string with at most two fractional digits whose numeric value is finite and within 0..9999.99, convert it to a JavaScript number (e.g. `"20.00"` → `20`); preserve null, and leave already numeric values to existing validation. Do not coerce `""`, whitespace, malformed, negative, or out-of-range values to zero/null, silently omit them, or relax `de_xuat.api.js` validation. Leave bad source text invalid for the existing inline field validation. Preserve frozen POST body/key, Q13 assignment-only create, day/exercise order, and 409 draft behavior.

**Regression and acceptance.** Mount the composer with the real official `data.plan.current_version.days[].exercises[]` DTO, including non-null `target_weight_kg: "20.00"`, valid exercise/day fields, and no weight edit. Fill only proposal metadata, submit through the existing store/service builder, and assert numeric `20` in the actual POST body with other allowed fields intact. Cover null and malformed/out-of-range strings, proving they cannot silently submit as zero. No Backend change or official Plan mutation. A validated untouched weighted Plan must submit successfully.

## F-007 — rest and notes in proposal preview

**Root cause.** `PtProposalService` persists and returns proposal `content` as a **root** object `{name, goal, days}` with per-exercise `rest_seconds` and `notes`; the composer edits both, but `khung_xem_de_xuat.vue` currently renders only exercise/name, sets, reps, and weight. Its current test fixture incorrectly emphasizes `content.plan` and asserts neither omitted field.

**Exact FE logic.** In the existing exercise preview row, show `rest_seconds` with its unit even when zero, and show non-empty `notes` as Vue-interpolated text. Keep safe text rendering, no HTML injection, current root content support (legacy nested fallback may remain), drawer/Escape/focus trap/return, status copy, and Member-only approval boundary. Do not add a route or PT apply control.

**Regression and acceptance.** Mount a real-shaped Proposal DTO with `content: {name, goal, days:[{exercises:[{exercise_id, target_sets, min_reps, max_reps, target_weight_kg, rest_seconds, notes}]}]}`. Assert visible rest and note for a rest-only or notes-only meaningful change, zero rest, and no empty/null note label. Retain F-004 keyboard tests. Preview must truthfully show all editable exercise details of the submitted proposal.

## Verification and finish gate

1. Run focused Backend feature regression on final code, then full Backend suite **only** after verifying `APP_ENV=testing`, `DB_CONNECTION=mysql`, and an explicitly selected isolated MariaDB database matching `smart_fitness_*test*` (for example the verified `smart_fitness_fe5_round2_test`), through `Tests/Support/TestDatabaseGuard.php`; use bundled PHP >=8.4. Never run migrate/fresh or tests against `smart_fitness`, a default/demo DB, or data to keep. If the isolated DB cannot be verified, report the exact blocker and do not substitute an unsafe database.
2. Run focused FE tests for direct service/page/store, composer, preview and proposal service, then full `rtk npm run test`, `rtk npm run lint`, `rtk npm run build` from `E:/Fitness/FE`; run `rtk git diff --check`, naming/docblock/secret/contract scan, and baseline/round snapshot/unexpected-path comparison. Count passes/failures/skips and capture exit codes, environment, final tree state. Earlier 852 FE / 346 Backend passing tests predate this repair and cannot certify it.
3. Append actual Backend/FE changes, DB/API impact, authorization, validation, error behavior, concurrency/idempotency/transaction, tests, preservation and remaining risks to `task-1-report.md` under the exact 15 DoD sections. A fresh Sol High final reviewer independently verifies F-005/F-006/F-007, the complete FE6 delta, API documentation, and final-state evidence. Only that passing re-review permits the Controller to mark FE6-ALL complete or advance the checkpoint.
