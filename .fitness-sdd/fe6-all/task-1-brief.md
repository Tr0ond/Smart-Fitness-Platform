### TASK_PACKET: FE6-ALL

**ROLE:** Implementation Writer  
**WORKFLOW_ROLE:** Implementer/Fixer  
**MODEL:** `gpt-6-luna`  
**REASONING_EFFORT:** `max`  
**OBJECTIVE:** Implement all three PT Direct + Proposal screens in one FE-only task, including stable-key recovery, Q06/Q13 behavior, and final FE gates.

## State and gate

- **CURRENT_CHECKPOINT:** `E:/Fitness/docs/VUE_WEB_COMPLETION_CHECKPOINT.md` records FE5-ALL PASS and FE6-ALL as exact next action. Controller must supply baseline/snapshot and explicitly confirm actual Direct/Proposal contracts and tests before writer dispatch. If any gate is unmet, `WAITING_DEPENDENCY` for the whole task.
- **Observed contracts:** `E:/Fitness/BE/routes/api.php`, `E:/Fitness/BE/app/Http/Requests/Pt/CompletePtDirectServiceRequest.php`, `E:/Fitness/BE/app/Http/Requests/Pt/CreatePtProposalRequest.php`, corresponding PT controllers/services, `E:/Fitness/BE/tests/Feature/PtDirectServiceTest.php`, `E:/Fitness/BE/tests/Feature/PtProposalApiTest.php`, `E:/Fitness/BE/tests/Feature/PtProposalConcurrencyTest.php`. Controller independently verified M001–M061 Ran and targeted Backend `PtDirectServiceTest|PtProposalApiTest|PtProposalConcurrencyTest` 24/24 tests, 180 assertions, exit 0 with bundled `E:/Fitness/.tools/php/php.exe` 8.4.25 and explicit `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_DATABASE=SMART_FITNESS_TEST_DATABASE=smart_fitness_fe5_round2_test`. No default/demo DB used. Existing tests show same-key replay; current source must be rechecked before edit. No Backend writer.
- **BASELINE_AND_PREEXISTING:** Controller supplies `E:/Fitness/.fitness-sdd/fe6-all/baseline-status.txt`, `baseline-tree/`, and `task-1-round-0-before/`. Preserve any pre-existing changes; no destructive Git actions. Planner observed clean `git status --short`.

## REQUIRED_CONTEXT (read to EOF independently)

1. `C:/Users/xtung/.codex/RTK.md`; `E:/Fitness/AGENTS.md`; discover deeper applicable `AGENTS.md` before touching each path. For inspected Backend paths, also `E:/Fitness/BE/AGENTS.md`.
2. `E:/Fitness/.fitness-rules/PROJECT_CORE.md`; `E:/Fitness/.fitness-rules/RULE_INDEX.md`; `E:/Fitness/.fitness-sdd/context/FE6_CONTEXT.md`; `E:/Fitness/.fitness-sdd/fe6-all/task-1-brief.md`.
3. `E:/Fitness/.fitness-rules/frontend/vue-core.md`; `E:/Fitness/.fitness-rules/frontend/visual-ui.md`; `E:/Fitness/.fitness-rules/domains/pt-chat.md`; `E:/Fitness/.fitness-rules/domains/workout.md`; `E:/Fitness/.fitness-rules/domains/membership-payment.md`; `E:/Fitness/.fitness-rules/domains/auth-resource-scope.md`; `E:/Fitness/.fitness-rules/engineering/security-integrity.md`; `E:/Fitness/.fitness-rules/engineering/coding-conventions.md`; `E:/Fitness/.fitness-rules/engineering/testing-definition-of-done.md`.
4. Relevant FE6/PT screens/aggregate sections only of `E:/Fitness/docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md`, checkpoint above, relevant `E:/Fitness/docs/BACKEND_API_CONTRACT.md` lines and actual Backend routes/request/controller/service/tests. Inspect existing `E:/Fitness/FE/src/` before creating files.

**CANONICAL_RULE_IDS:** Q04/Q06/Q09/Q12/Q13; RULE GYM 01/02/08/09/13/15/16/21/22/23/24; RULE CODE 05–11/13/15/16/17/18/19/20.  
**AUTHORITIES (conditional consultation):** `E:/Fitness/PROJECT_RULES.md` and `E:/Fitness/docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md`. Consult only relevant canonical sections on conflict, ambiguity, missing rule or version mismatch; record cause, sections and resolution. **FULL_CANONICAL_REREAD: CONDITIONAL.** Do not read `ai-proposal.md` or the full canonical/Vue plan by default.

## Scope and exact ALLOWED_FILES

- `E:/Fitness/FE/src/pages/pt/buoi_huan_luyen/buoi_huan_luyen.index.vue`
- `E:/Fitness/FE/src/pages/pt/buoi_huan_luyen/buoi_huan_luyen.index.test.js`
- `E:/Fitness/FE/src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.index.vue`
- `E:/Fitness/FE/src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.index.test.js`
- `E:/Fitness/FE/src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.tao_moi.vue`
- `E:/Fitness/FE/src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.tao_moi.test.js`
- `E:/Fitness/FE/src/services/buoi_huan_luyen.api.js`
- `E:/Fitness/FE/src/services/buoi_huan_luyen.api.test.js`
- `E:/Fitness/FE/src/services/de_xuat.api.js`
- `E:/Fitness/FE/src/services/de_xuat.api.test.js`
- `E:/Fitness/FE/src/stores/de_xuat.store.js`
- `E:/Fitness/FE/src/stores/de_xuat.store.test.js`
- `E:/Fitness/FE/src/components/PT/khung_xem_de_xuat.vue`
- `E:/Fitness/FE/src/components/PT/khung_xem_de_xuat.test.js`
- `E:/Fitness/FE/src/router/index.js`
- `E:/Fitness/FE/src/router/index.test.js`
- `E:/Fitness/FE/src/router/dieu_huong_pt.js`
- `E:/Fitness/FE/src/router/dieu_huong_pt.test.js`
- `E:/Fitness/FE/src/components/PT/thanh_dieu_huong_hoi_vien.vue`
- `E:/Fitness/FE/src/components/PT/thanh_dieu_huong_hoi_vien.test.js`
- `E:/Fitness/FE/src/stores/hoi_vien_pt.store.js`
- `E:/Fitness/FE/src/stores/hoi_vien_pt.store.test.js`
- `E:/Fitness/FE/src/stores/xac_thuc.store.js`
- `E:/Fitness/FE/src/stores/xac_thuc.store.test.js`

Reuse existing `E:/Fitness/FE/src/components/dung_chung/hop_thoai_xac_nhan.vue`, `huy_hieu_trang_thai.vue`, query-state components, `E:/Fitness/FE/src/composables/su_dung_thao_tac_chong_lap.js`, and `E:/Fitness/FE/src/services/api.js` without editing them. The above is the complete allow-list; any newly discovered required path needs controller scope review before editing. `E:/Fitness/.fitness-sdd/fe6-all/task-1-report.md` is the only report write outside it. No Backend/rule/schema/package/config/checkpoint edits, no extra screen/route/feature, no UI framework/dark mode, no branch/worktree/commit/push/reset/restore/checkout/stash/clean.

## API shapes and acceptance behavior

- `GET /api/pt/direct-sessions` (PT): `{data: DirectHistory[]}`, latest 100 **own PT** rows across Members, no pagination or member filter. Filter by route Member ID on FE; show honest latest-100 limitation and avoid interpreting empty filtered slice as lifetime absence. Row keys: `history_id`, `assignment_id`, `member_id`, `trainer_id`, `term_id`, `usage_id`, `status`, `completed_at`, `confirmed_at`, `notes`.
- `POST /api/pt/direct-sessions/complete` (PT): JSON **only** `{assignment_id: positive integer, notes?: string|null}` and `Idempotency-Key: UUIDv4`; max notes 1000. 201 `{data:{history_id,usage_id,assignment_id,member_id,trainer_id,term_id,status:"HOAN_THANH",completed_at,confirmed_at,replayed}}`. Never send member/trainer/term/quota/completion time. Select assignment from current PT Member context/detail response, not URL guess. Backend alone checks exact term/quota/assignment, may activate eligible head `CHO_KICH_HOAT` term, records one usage and decrements one direct quota. No client quota math or membership-active-only gate.
- `GET /api/pt/members/{member}/proposals` (current assigned PT): `{data: Proposal[]}` latest 100 only for **current assignment**, no PT detail endpoint. Proposal fields include `id`, `member_id`, `source`, `creator_user_id`, `assignment_id`, `base_plan_id`, `base_version_id`, `change_type`, `title`, `explanation`, `content`, `structure_version`, `effective_from`, `status`, `expires_at`, `decided_at`, `applied_at`, `terminal_reason`, `created_at`, `replayed`. Preview from the list object. Display statuses `CHO_XAC_NHAN`, `DA_TU_CHOI`, `HET_HAN`, `XUNG_DOT`, `DA_AP_DUNG` with text. Explain 100-row limit.
- `POST /api/pt/members/{member}/proposals`: header UUIDv4; JSON only `change_type` (`TAO_MOI|DIEU_CHINH|THAY_BAI`), `title` (1..200), `explanation` (1..10000), `effective_from` (`Y-m-d` today/future in server business timezone), and `plan:{name (1..150),goal (1..100),template_id? (positive/null),days (1..7)}`. Each day is exactly `{order (1..7),weekday (2..8),name (1..150),estimated_minutes (1..1440),exercises (1..50)}`; each exercise exactly `{exercise_id positive,order (1..50),target_sets (1..100),min_reps (1..1000),max_reps (>=min_reps, <=1000),target_weight_kg? (0..9999.99/null),rest_seconds (0..86400),notes? (<=1000/null)}`. 201 returns Proposal DTO, `source:HUAN_LUYEN_VIEN`, `status:CHO_XAC_NHAN`, server-owned 24-hour `expires_at`. Never submit `source`, `assignment_id`, creator, base IDs, status, expiry, markers or content hash. Use PT official Plan read for adjustment context; `TAO_MOI` when no active Plan. Do not invent PT catalog endpoint or represent a proposal as official plan.
- Q13: current assignment alone is prerequisite. No Membership/Chat/direct/AI quota gate or side effect, even if quota is zero. PT has no apply control. Member Mobile handles approval. FE 409 preserves draft and shows conflict/refetch guidance; assignment loss clears store and stops all mutations. 422 maps nested field errors. 401/403/404 follow existing shared auth/scope behavior.
- For each POST, freeze exact body and UUID per logical action. Pending UI disables repeat action and sets `aria-busy`. On network/timeout/unknown result, refetch first; because GET lists omit request key and cap at 100, absence/loose matching is not definitive. If uncertain, retry **only** original body and key; Backend same-key replay proves outcome. Do not regenerate either to evade 409. Reconcile returned `history_id`/proposal `id`; do not claim extra completion on replay. After terminal outcome, permit a genuinely new action/key. Use request generation/context checks so late response after Member switch/logout/assignment loss cannot write sensitive state.

## Tests, review and report

- Add focused tests for all three routes and navigation; actual API URLs, allow-listed payloads, UUID header, current-assignment ID, latest-100 copy/filter, preview/status text, Q13 no quota gate/apply control, 422, 409 draft preservation, 401/403/404 cleanup, double-click, timeout → refetch → same-key/same-body retry, replay reconciliation, late response/member switch, and non-duplicated UX. Test successful and ambiguous refetch; no mock that hides wrong endpoint or payload shape.
- From `E:/Fitness/FE`: `npm run test -- src/services/buoi_huan_luyen.api.test.js src/services/de_xuat.api.test.js src/stores/de_xuat.store.test.js src/pages/pt/buoi_huan_luyen/buoi_huan_luyen.index.test.js src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.index.test.js src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.tao_moi.test.js`; then `npm run test`, `npm run lint`, `npm run build`. Run affected router/store/component tests too; provide exact command/count/skip/environment evidence on final source, plus naming/docblock/secret/contract scan and `git diff --check`.
- **EXPECTED_REPORT:** `E:/Fitness/.fitness-sdd/fe6-all/task-1-report.md`. Use the exact 15 headings in `engineering/testing-definition-of-done.md` (goal, created/changed files, database, API, functions, rules, auth, validation, transaction/idempotency, errors, cases/results, unfinished, risks); add gate and preservation evidence. Task reviewer and final reviewer independently inspect source/delta/tests. Do not claim PASS from UI alone.
