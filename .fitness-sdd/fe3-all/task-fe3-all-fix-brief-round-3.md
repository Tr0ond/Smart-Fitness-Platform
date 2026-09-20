# FE3-ALL fix brief — round 3

`STATUS: READY_FOR_FIX`
`TASK_ID: FE3-ALL`
`FIX_ROUND: 3`
`MAPPED_FINDINGS: F-003, F-006, F-011, F-012, F-013`
`PRESERVE_CLOSED: F-001, F-002, F-004, F-005, F-007, F-008, F-009, F-010, F-014, VUE_COMPILER_WARNING`
`PRODUCT_ALLOW_LIST_COUNT: 14`
`PRODUCT_ADDITION_COUNT: 0`
`NEEDS_USER_DECISION: NO`
`EXPECTED_REPORT: E:\Fitness\.fitness-sdd\fe3-all\task-fe3-all-report.md`

Implement one consolidated repair for exactly the five Important findings left open by `task-fe3-all-fix-review-round-2.md`. Do not split the repair, dismiss a finding, reopen a closed finding, redesign unrelated behavior, or edit outside the exact boundary below.

## Required context

Read completely before editing:

- `E:\Fitness\AGENTS.md`; discover any deeper `AGENTS.md` governing inspected/edited paths. Read `E:\Fitness\BE\AGENTS.md` before the read-only Exercise request inspection. No nested FE instruction file currently exists, but discovery remains mandatory.
- `E:\Fitness\.fitness-rules\PROJECT_CORE.md`
- `E:\Fitness\.fitness-rules\RULE_INDEX.md`
- `E:\Fitness\.fitness-sdd\context\FE3_CONTEXT.md`
- `E:\Fitness\.fitness-rules\frontend\vue-core.md`
- `E:\Fitness\.fitness-rules\frontend\visual-ui.md`
- `E:\Fitness\.fitness-rules\domains\workout.md`
- `E:\Fitness\.fitness-rules\domains\membership-payment.md`
- `E:\Fitness\.fitness-rules\engineering\security-integrity.md`
- `E:\Fitness\.fitness-rules\engineering\coding-conventions.md`
- `E:\Fitness\.fitness-rules\engineering\testing-definition-of-done.md`
- `E:\Fitness\.fitness-sdd\fe3-all\plan.md`, `fe3-all-brief.md`, `entry-gate-revalidation.md`, `fe3-all-implementation-brief.md`, `task-fe3-all-report.md`, `task-fe3-all-review.md`, both prior fix plans/briefs/reviews/packages, `task-fe3-all-fix-review-package-round-2.md`, `task-fe3-all-fix-review-round-2.md`, `task-fe3-all-fix-plan-round-3.md`, and this brief.
- Original baseline/target/preservation artifacts and the controller-provided round-3 before snapshot, current manifest, exact delta, unexpected-path check and preservation evidence before writing.
- Read only `E:\Fitness\BE\app\Http\Requests\Admin\Catalog\UpdateExerciseRequest.php` as the current authoritative partial-Exercise request contract. It is read-only. Do not inspect broad Backend implementation unless a concrete new mismatch appears.

Binding IDs: Q01, Q11, Q12, M061, RULE GYM 11–16, RULE CODE 05–11, 13, 17–20. `E:\Fitness\PROJECT_RULES.md` and `E:\Fitness\docs\VUE_WEB_IMPLEMENTATION_PLAN_V2.md` remain conditional authorities. Prior limited escalations already resolved M061, naming/docblocks, visible copy, accessibility, status confirmation, nullable fields and Template COW. Do not reread either full document. Consult only a precise passage if a new conflict, ambiguity, missing rule, or version mismatch appears; record the trigger and resolution.

## Required repair by finding

- **F-003 — exact mounted gaps:** Add server-422 mounted cases to the Package, Exercise, and Template dedicated create suites. Expand Template detail with deferred initial loading, initial error/user retry, metadata 422, pending/double-submit, and successful authoritative post-PATCH refresh while retaining nullable/tree/revision behavior. Expand Template revision with a valid nullable COW request, exact full payload and success navigation, ordinary 422 with preserved draft/no navigation, and pending/double-submit; retain initial retry and stale draft/latest-base/explicit reconciliation with no automatic POST. Assert rendered state, call counts, exact payloads, disabled state and navigation. Do not add test-only production branches or reopen other pages.
- **F-006 — two docblocks only:** In `giao_an_mau.api.js`, replace only the comments above `taiNenGiaoAnMau` and `xuLyGiaoAnMauDaCu`. Each must separately cover purpose, input, processing, result, side effects, and governing COW/`expected_content_version` contract. The stale classifier must document exact `409 + WORKOUT_TEMPLATE_STALE`, its returned shape, and that it performs no request, mutation or retry. Do not test comments by token/count.
- **F-011 — public Package service proof:** In `goi_tap.api.test.js`, call both `taoGoiTap` and `thayTheQuyenLoiGoiTap` with the exact all-disabled benefit shape; assert normalized 422 and zero POST/PUT. Table-drive Gym-only, Chat-only, Direct-session-only, Unlimited-AI and Limited-AI through public service mutations and assert exact POST nested-benefits and PUT payloads. Helper-only assertions do not satisfy this finding. Retain Q01/authority, endpoints, nullable description, price and invalid-AI-limit tests.
- **F-012 — list-only status binding:** Remove Package and Template detail status action buttons, `HopThoaiXacNhan` usage, refs, handlers, reconciliation functions/docblocks and dialog markup. Keep disabled/read-only status display and metadata payload exclusion. Do not edit list pages: their existing confirmed transitions remain the sole entry points. Replace the two divergent detail status tests with absence-of-control/dialog plus metadata-without-status assertions. Preserve Package Q01/benefit/configuration-version behavior and Template tree/revision navigation.
- **F-013 — partial Exercise instructions:** Make Exercise service validation require `instructions` when creating or when the property is present in a partial payload, while allowing omission. Reject present null, blank, object/array or other non-string values with normalized 422 before Axios. Add a matching Exercise detail pre-submit guard so whitespace cannot call the store/service or trigger refresh. Direct tests cover null/blank/object with zero PATCH, omitted instructions reaching a metadata PATCH, and a valid instructions PATCH. Mounted detail coverage proves blank instructions render a safe inline/global error, issue no PATCH, keep the form, and leave submit unlocked. Preserve nullable image/video, Q11, M061, status confirmation and authority allow-lists.

## Closed behavior that must remain unchanged

- F-001 exact inactive M061 id/role echo and locked controls.
- F-002/F-009 Template stale-draft separation, explicit reconciliation, initial GET retry, and zero automatic POST.
- F-004 zero-warning/zero-error lint.
- F-005 local Package/Equipment/Muscle Group/Template list reconciliation state machines, GET-only uncertainty checks, explicit retry and cache bypass where required.
- F-007 UTF-8 Vietnamese visible copy with canonical technical identifiers/status/error codes.
- F-008 shared dialog name/description, focus containment/return, Escape and pending behavior.
- F-010 nullable Package/Template description, Exercise image/video and Template notes payloads; required strings remain strict.
- F-014 authoritative Package `configuration_version` refresh and last-confirmed-value behavior.
- No const-reactive `v-model="form"` compiler warning.
- No catalog DELETE/history destruction, no M061 or Q11 weakening, no mutation of prior Package snapshots, no Template `days` in metadata PATCH, no blind retry/idempotency invention, no auth cleanup change, and no FE4 scope.

## Exact product allow-list

Only these 14 paths, all already inside the original 51-target FE3 boundary, may be modified:

```text
FE/src/services/goi_tap.api.test.js
FE/src/services/bai_tap.api.js
FE/src/services/bai_tap.api.test.js
FE/src/services/giao_an_mau.api.js
FE/src/pages/admin/goi_tap/goi_tap.tao_moi.test.js
FE/src/pages/admin/goi_tap/goi_tap.chi_tiet.vue
FE/src/pages/admin/goi_tap/goi_tap.chi_tiet.test.js
FE/src/pages/admin/bai_tap/bai_tap.tao_moi.test.js
FE/src/pages/admin/bai_tap/bai_tap.chi_tiet.vue
FE/src/pages/admin/bai_tap/bai_tap.chi_tiet.test.js
FE/src/pages/admin/giao_an_mau/giao_an_mau.tao_moi.test.js
FE/src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.vue
FE/src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.test.js
FE/src/pages/admin/giao_an_mau/giao_an_mau.tao_phien_ban.test.js
```

Append round-3 implementation and exact final-state evidence to `E:\Fitness\.fitness-sdd\fe3-all\task-fe3-all-report.md`; this report is the only additional writable workflow artifact and is excluded from the product count. Do not create a source/test file. No other `.fitness-sdd` artifact is writable by the fixer.

## Implementation details and acceptance evidence

1. Exercise partial detection must be property-presence based. Do not turn every optional partial string into a required field. The service must distinguish omitted `instructions` from `{ instructions: undefined }` only according to the existing payload-builder convention; present invalid concrete values must never reach Axios.
2. Package/Template detail cleanup must remove dead imports/state/functions as well as template controls. Do not leave an unreachable duplicate status state machine or weaken list-page coverage.
3. Create 422 tests must use normalized service errors that the real Pinia mutation path consumes. Assert field/global UI and navigation/call behavior; do not call a validator helper in place of mounting the page.
4. Template detail refresh evidence must prove the second GET supplies the displayed authoritative metadata. A mutation resolution alone is not authoritative refresh proof.
5. Template revision success must assert the complete request body, including nullable description and nested notes, and route with the response `new_template_id`. The 422 and deferred cases must preserve the draft and prevent extra calls/navigation.
6. Package benefit table cases must use these exact effective shapes:
   - Gym-only: `{ gym_access: true, fitness_assistant: false, fitness_assistant_limit: 0, trainer_chat: false, direct_trainer_sessions: 0 }`
   - Chat-only: `{ gym_access: false, fitness_assistant: false, fitness_assistant_limit: 0, trainer_chat: true, direct_trainer_sessions: 0 }`
   - Direct-session-only: `{ gym_access: false, fitness_assistant: false, fitness_assistant_limit: 0, trainer_chat: false, direct_trainer_sessions: 1 }`
   - Unlimited-AI: `{ gym_access: false, fitness_assistant: true, fitness_assistant_limit: null, trainer_chat: false, direct_trainer_sessions: 0 }`
   - Limited-AI: `{ gym_access: false, fitness_assistant: true, fitness_assistant_limit: 25, trainer_chat: false, direct_trainer_sessions: 0 }`

## Required verification

From `E:\Fitness\FE`, after the last product change, run and record:

1. Focused Vitest covering all nine modified test files, unchanged Template service tests, Package/Template list transition suites, benefit/relation/conflict/shared-dialog component suites, catalog store, router/menu/auth, and all remaining FE3 page suites. Require 100% pass with no skipped/only tests, unhandled rejection, Vue warning or compiler warning.
2. Full `rtk npm run test` with exact final file/test counts.
3. Full `rtk npm run lint` with exactly zero warnings and zero errors.
4. `rtk npm run build` after the final change.
5. `rtk npm ls --depth=0`; no package/lock delta.
6. Static scans for executable catalog DELETE/hard delete, FE4/payment scope, hard-coded API root, dependencies/frameworks/fonts, secrets/token logs, `handleXxx`, bound handlers outside `xuLy...`, `__file`, skip/only, TODO/FIXME and `v-model="form"`. Verify no Package/Template detail status action/dialog/state-machine residue and no status in their metadata payloads.
7. `rtk git diff --check`; staged diff empty; controller round-3 before/current manifests, exact delta and preservation package report exactly 14-or-fewer changed allow-listed product paths, zero unexpected paths and no lost pre-existing hunk. Unused allow-listed paths may remain unchanged; no unlisted product path may change.

Attempt browser smoke only if an authenticated local Admin application already exists. Otherwise record the limitation and rely on the approved deterministic mounted page/router/Pinia/service-boundary evidence. Do not request credentials or add an auth/API bypass.

Return `DONE` only when all five mapped findings are closed and all preservation/final gates are green. Otherwise return `DONE_WITH_CONCERNS` or `BLOCKED` with exact unresolved evidence.
