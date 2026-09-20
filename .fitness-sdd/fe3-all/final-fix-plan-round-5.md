# FE3-ALL consolidated final repair plan — wave 5

`STATUS: READY_FOR_FIX`  
`TASK_ID: FE3-ALL`  
`REPAIR_WAVE: 5`  
`OPEN_FINDINGS: FINAL-F-001, FINAL-F-002, FINAL-F-003, FINAL-F-004`  
`MAPPED_FINDING_COUNT: 4`  
`OPEN_CRITICAL: 0`  
`OPEN_IMPORTANT: 3`  
`OPEN_MINOR: 1`  
`PRESERVE_CLOSED: F-001..F-015, VUE_COMPILER_WARNING, BACKEND_ENTRY_REPAIR`  
`NEEDS_USER_DECISION: NO`  
`FULL_CANONICAL_REREAD: CONDITIONAL_NOT_REQUIRED`  
`ORIGINAL_FE3_PRODUCT_BOUNDARY: 51 PATHS`  
`WAVE_5_PRODUCT_ALLOW_LIST: 11 EXISTING PATHS`  
`WAVE_5_RUNTIME_PATHS: 4`  
`WAVE_5_TEST_PATHS: 7`  
`WAVE_5_PRODUCT_ADDITIONS: 0`  
`REQUIRED_FOCUSED_REGRESSION_CASES: 8`

## 1. Outcome, actor, use cases, and boundary

Run one consolidated Wave 5 repair with one fresh GPT-5.6 Luna Max fixer. Close all four final-review findings together, then obtain a fresh independent Sol High final re-review. FE3-ALL remains one aggregate phase task; do not split the findings into separate writers or start FE4-ALL.

The actor remains an authenticated `ADMIN`. The only affected use cases are:

1. inspect and author every persisted Workout Template exercise prescription field;
2. identify a retired Exercise in historical Template detail and replace it explicitly when authoring a new copy-on-write revision;
3. edit an Exercise whose valid Backend `metadata` is `null` without silently rewriting it to `{}`;
4. use every visible save control on Package create with a defined outcome.

The exact product allow-list is the 11 existing files in section 8: four runtime files and seven colocated tests. All 11 exist and are members of the original 51-path FE3-ALL target list. There are zero additions. No Backend, database, route, store, API endpoint, shared auth/session logic, CSS, dependency, lockfile, checkpoint, rule/context, progress, prior report/review/package, or FE4+ file is required.

## 2. Authority and targeted escalation record

### Mandatory working authority

- `E:\Fitness\AGENTS.md`, `E:\Fitness\BE\AGENTS.md`, `PROJECT_CORE.md`, `RULE_INDEX.md`, `FE3_CONTEXT.md`.
- `frontend/vue-core.md`, `frontend/visual-ui.md`, `domains/workout.md`, `domains/membership-payment.md`, `engineering/security-integrity.md`, `engineering/coding-conventions.md`, and `engineering/testing-definition-of-done.md`.
- Original implementation authority/evidence: `fe3-all-implementation-brief.md`, `task-fe3-all-report.md`, `final-review-package.md`, and `final-review.md`.

Binding IDs remain Q01, Q11, Q12, M061, RULE GYM 11–16, and RULE CODE 05–11, 13, 17–20.

### Precise Backend authorities inspected

- `BE/routes/api.php`: all relevant catalog routes remain under `auth:api` plus `role:ADMIN`; methods remain `POST /api/admin/workout-templates`, `GET /api/admin/workout-templates/{id}`, `POST /api/admin/workout-templates/{id}/revisions`, and `PATCH /api/admin/exercises/{id}`.
- `CreateWorkoutTemplateRequest.php:32-39` and `CreateWorkoutTemplateRevisionRequest.php:35-42`: each tree item requires `exercise_id`, `order`, `target_sets`, `min_reps`, `max_reps`, and integer `rest_seconds` in `0..65535`; `notes` is optional/nullable string with maximum 1000 characters.
- `WorkoutTemplateCatalogAdminService.php:339-353`: detail returns `exercise_name`, all prescription fields, `rest_seconds`, and nullable `notes` for each persisted item. Its structure validator accepts only active Exercises for a new Template/revision and its copy-on-write transaction preserves the old tree.
- `CreateExerciseRequest.php:33`, `UpdateExerciseRequest.php:24`, migration M027 line defining `thong_tin_bo_sung JSON NULL`, and `ExerciseCatalogAdminService.php:522`: Exercise `metadata` is a valid nullable array/JSON field and detail returns the stored value unchanged.

### Canonical escalation

The final reviewer found a concrete UI/contract ambiguity about how much of the Template tree must be rendered and editable. The precise lookup was limited to `docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md` sections 23.27–23.29, FE-3 in section 31, and the `FE3-ALL` checklist in section 38A. Those passages require an initial full tree, a tree read view/editor, invalid/inactive-Exercise handling, and copy-on-write revision flow. Resolution: `rest_seconds`, `notes`, and the Backend-provided historical `exercise_name` are part of the required tree experience. No new ambiguity, rule conflict, missing coverage, or version mismatch remains, so no `PROJECT_RULES.md` passage or unrelated module is loaded for Wave 5.

## 3. Finding-to-repair mapping

### FINAL-F-001 — Important — complete Template prescription rendering/editing

Root cause: `cay_giao_an.vue` initializes `rest_seconds` and `notes` but renders controls only for Exercise, sets, min reps, and max reps. The common component therefore drops those two fields from the Admin's inspect/edit experience even though the service serializes them.

Repair:

- In `cay_giao_an.vue`, add a labelled integer input for rest seconds with `min="0"`, `max="65535"`, `step="1"`, required semantics, existing `disabled` behavior, and `xuLy...` change handling.
- Add a labelled notes textarea/input with `maxlength="1000"`, existing `disabled` behavior, and nullable semantics. Preserve a source `null`; normalize a deliberately cleared value to `null` before emitting so an unrelated render/edit does not invent content.
- Continue emitting a copied complete tree through `update:modelValue`, `update:days`, and `thayDoi`. Do not mutate Template history, generate authority fields, or bypass `giao_an_mau.api.js` validation.
- The disabled detail view must render the stored rest value and notes value; create and revision views must allow edits.

Focused tests:

- Component test changes rest and notes and asserts the emitted complete item plus bound/disabled attributes.
- Template detail test proves stored rest/notes are visible in the disabled tree.
- Template create test selects an active Exercise, edits rest/notes, submits, and asserts the exact full `days` payload.
- Template revision test edits prescription values and asserts the exact full revision payload.

Acceptance: an Admin can inspect every persisted prescription field and can author any Backend-valid rest value and nullable note; no field is silently reset to the hard-coded defaults during create/revision.

### FINAL-F-002 — Important — retain retired Exercise identity and require explicit replacement

Root cause: the common tree resolves labels only from the active `exerciseOptions` list. Detail and revision pages intentionally request active Exercises, so a stored retired Exercise has no option label even though detail supplies `exercise_name`.

Repair:

- In `cay_giao_an.vue`, derive each row's display options from the active option list plus, only when absent, the row's retained `{ exercise_id, exercise_name }` selection.
- Mark the retained fallback as retired/unavailable and disabled as a future/new choice. It may remain the selected historical value so detail and the initial revision draft show the identity. A newly added row must never receive or select this fallback.
- Keep every active option selectable in create/revision. Replacing a retired selection with an active Exercise must remove the retired ID from the emitted tree; do not auto-replace, auto-submit, or mutate the old Template.
- Keep detail read-only and keep revision copy-on-write, stable `expected_content_version`, stale-draft separation, and explicit reconciliation unchanged.

Focused tests:

- Common component test distinguishes the retained disabled historical option from active choices and proves explicit replacement emits the active ID.
- Detail page test uses a DTO with `exercise_name` whose ID is absent from the active list and asserts that identity remains visible.
- Revision page test starts with the same retired item, selects an active replacement, submits once, and asserts the full payload contains the replacement ID and not the retired ID.

Acceptance: historical identity never disappears; retired Exercises cannot be selected for a new relation; a revision can deliberately replace one and remains subject to Backend active-Exercise validation.

### FINAL-F-003 — Important — preserve nullable Exercise metadata

Root cause: `bai_tap.api.js` rejects `metadata: null`, while `bai_tap.chi_tiet.vue` hydrates a valid null DTO as `{}` and always sends that value during an unrelated metadata/relation update.

Repair:

- In `bai_tap.api.js`, accept only `null` or a non-array object when `metadata` is present. Preserve the exact null in the request body; continue rejecting strings, numbers, arrays, and other invalid values with the existing normalized `422 / INVALID_EXERCISE_REQUEST` before Axios.
- In `bai_tap.chi_tiet.vue`, initialize/hydrate metadata without `?? {}` coercion. Preserve an explicit Backend null across mounted state, submit, authoritative refetch, status reconciliation, and unrelated field edits.
- Do not add a metadata editor, new DTO field, endpoint, client authority, or JSON transformation. Keep status-only PATCH, Q11 arrays, M061 inactive relation echo, nullable media, and instruction validation unchanged.

Focused tests:

- Public service test sends `metadata: null` through create/update as applicable and verifies exact payload; invalid non-null shapes remain rejected before network.
- Exercise detail mounted test loads `metadata: null`, changes an unrelated editable field, submits, and proves the PATCH does not contain `{}` in place of null and the authoritative refetch remains null-safe.

Acceptance: a valid null never becomes `{}` unless an explicit future metadata-edit feature changes it; Backend validation remains authoritative.

### FINAL-F-004 — Minor — give Package create's visible benefit-save action a consumer

Root cause: `bo_sua_quyen_loi_goi_tap.vue` emits `luu`, but `goi_tap.tao_moi.vue` mounts it without a listener, so its visible “Lưu quyền lợi” action does nothing.

Repair:

- In `goi_tap.tao_moi.vue`, bind the component's existing `luu` event to the existing guarded `xuLyLuu` create workflow.
- Reuse the current component validation, parent validation, double-submit lock, one POST, normalized errors, draft retention, and response navigation. Do not add a second mutation path or a benefits-only API call.
- Keep the outer submit button operational; both visible save affordances must invoke the same single guarded create outcome.

Focused test:

- Fill valid Package metadata, click the component-level “Lưu quyền lợi” button, and assert exactly one `taoGoiTap` call plus existing navigation. A pending call must remain protected by the same guard.

Acceptance: the visible action is no longer dead and cannot create duplicate requests.

## 4. Preservation gates for F-001 through F-015

The Wave 5 fixer must preserve every earlier closure:

| Closed item | Required unchanged behavior |
| --- | --- |
| F-001 | M061 inactive relation controls stay locked and replacement payload echoes exact id/role. |
| F-002 | Stale Template draft and latest base stay separate; only explicit reconciliation changes expected version; no automatic POST. |
| F-003 | All 12 mounted page suites retain loading/error/retry/empty/data/422/pending/payload/navigation evidence. |
| F-004 | Full lint has exactly zero warnings and zero errors. |
| F-005 | Package/Equipment/Muscle Group/Template unknown mutation outcomes use GET-only reconciliation and deliberate retry. |
| F-006 | Required Template service/runtime docblocks remain meaningful and behavior-aligned. |
| F-007 | Visible text remains valid UTF-8 Vietnamese; official keys, statuses, route names, and error codes remain unchanged. |
| F-008 | Shared dialogs retain accessible name/description, focus containment/return, Escape, and pending lock. |
| F-009 | Initial Template revision retry performs GET only and never submits a revision. |
| F-010 | Nullable Package/Template descriptions, Exercise image/video, and Template notes remain supported. Wave 5 extends rather than weakens Template-note coverage. |
| F-011 | Package benefit invariant and all five valid benefit shapes remain enforced at the public service boundary. |
| F-012 | Package/Template status transitions remain list-only; detail pages have no status action/dialog/state machine. |
| F-013 | Exercise create still requires instructions; partial omission remains legal and present invalid values fail before Axios. |
| F-014 | Package `configuration_version` refresh and last-confirmed fallback remain authoritative. |
| F-015 | Template detail copy remains metadata-only, points status changes to the list, and directs `days` changes to a revision. |
| Vue compiler warning | No const-reactive `v-model="form"` or new Vue/compiler warning. |

The existing 28-file/209-test preservation matrix and full 58-file/675-test baseline are the minimum prior evidence. Final counts must be recorded from the post-fix tree and must not decrease silently.

## 5. Backend entry-repair preservation gate

Wave 5 authorizes zero Backend or authority-document edits. Before the writer, the controller must snapshot the live final-review state. After the writer, require byte-for-byte equality for the 17 unique non-scratch entry-repair paths: the M061 migration; `NhomCo`; both Muscle Group requests; `ExerciseCatalogAdminService`; `ExerciseDatasetSeeder`; `AdminCatalogApiTest`; `AdminCatalogConcurrencyTest`; `run_admin_catalog_action.php`; `PROJECT_RULES.md`; `BACKEND_API_CONTRACT.md`; `TU_DIEN_DU_LIEU.md`; `THIET_KE_DATABASE.md`; `MIGRATION_PLAN.md`; both ERDs; and `MUSCLE_GROUP_DEACTIVATION_REPORT.md`.

Re-run the guarded Backend evidence on the unchanged tree:

1. prove PHP 8.4+, `APP_ENV=testing`, MySQL, and configured/current/approved database all equal exact `smart_fitness_test` with `controller-test-guard.php`;
2. `AdminCatalogApiTest` must pass, including M061 lifecycle/history/seeder/audit rollback;
3. `AdminCatalogConcurrencyTest` must pass, including status/relation serialization;
4. the full Backend `artisan test` suite must pass with no failure/skip;
5. `vendor\bin\pint --test` and relevant PHP syntax checks must pass;
6. no catalog DELETE route, schema drift, audit drift, inactive-pivot recreation, or Backend file delta is permitted.

Do not run migrations down/rollback or any destructive database command. Use only the proven disposable `smart_fitness_test` database.

## 6. API, authorization, validation, failure, concurrency, idempotency, transaction, and history gates

- **API:** no endpoint or method changes. Template create/revision still send the exact allow-listed full tree; Template detail remains GET plus metadata-only PATCH; Exercise detail remains GET/PATCH; Package create remains one POST.
- **Authorization:** existing Vue route/meta/guard/menu behavior and Backend `auth:api`, `role:ADMIN` remain untouched. A 401 clears only the matching current-token session; 403 remains forbidden without logout.
- **Validation:** rest is integer `0..65535`; notes is nullable string `<=1000`; Template new/revision Exercises must be active; retained retired identity is display-only until explicitly replaced. Exercise metadata is `null` or object only. Existing required strings, tree ordering, rep bounds, `sessions_per_week === days.length`, Package benefit and numeric bounds remain intact.
- **Failures:** normalized 401/403/404/409/422/5xx/network handling remains. `WORKOUT_TEMPLATE_STALE` is only exact 409; draft remains, latest base is GET-only, and no mutation is retried blindly. Invalid metadata/prescription fails before Axios where deterministic; Backend errors remain authoritative.
- **Concurrency:** no new client concurrency primitive. Stable `expected_content_version` remains the Template optimistic-concurrency token. M061 locks and status/relation serialization remain Backend-only and unchanged.
- **Idempotency:** no catalog idempotency header is invented. Pending guards prevent duplicate UI submissions; uncertain mutation outcomes are not treated as success or blindly retried.
- **Transaction/audit:** no client transaction or audit record is added. Backend Template copy-on-write transaction and M061 transaction/audit remain authoritative and unchanged.
- **History:** no DELETE, direct pivot mutation, old Template rewrite, Plan/Session rewrite, or completed Workout mutation. Historical retired Exercise labels come only from the stored detail DTO.

## 7. Implementation order and verification

1. Update the common Template tree and its component tests for complete prescription fields and retained retired selection.
2. Extend Template detail/create/revision mounted tests. Do not edit their page runtime files unless a proven failing focused test shows the common component cannot satisfy the reviewed contract; such a discovery is a blocker because those runtime paths are outside this exact allow-list.
3. Correct nullable Exercise metadata in the service and detail page with both regressions.
4. Bind Package create's existing benefit-save event to its guarded submit and test the visible action.
5. Run the seven-file focused command from `E:\Fitness\FE`:

   `rtk npm run test -- --run src/components/danh_muc/cay_giao_an.test.js src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.test.js src/pages/admin/giao_an_mau/giao_an_mau.tao_moi.test.js src/pages/admin/giao_an_mau/giao_an_mau.tao_phien_ban.test.js src/services/bai_tap.api.test.js src/pages/admin/bai_tap/bai_tap.chi_tiet.test.js src/pages/admin/goi_tap/goi_tap.tao_moi.test.js`

6. Run the prior 28-file closed-finding preservation matrix, then `rtk npm run test`, `rtk npm run lint`, `rtk npm run build`, and `rtk npm ls --depth=0`. Require no skip/only, unhandled rejection, Vue/compiler warning, or dependency/lock delta.
7. Run static scans for executable catalog DELETE/hard-delete, FE4/payment scope, hard-coded API roots, forbidden dependency/framework/font additions, secrets/token logs, `handleXxx`, native bound handlers outside `xuLy...`, `__file`, skipped/only tests, TODO/FIXME, and `v-model="form"`. Confirm Template detail metadata excludes `status`/`days`, retained retired options are disabled for new selection, and Exercise null metadata is not coerced.
8. Run the guarded Backend gates in section 5, `rtk git diff --check`, and require an empty staged diff.
9. Controller packaging must prove: all changed product paths are within the 11-path allow-list; zero additions/unexpected paths; all non-target current-tree paths preserved; all original 51 FE3 targets still exist; all 17 Backend entry-repair paths are byte-identical; no pre-existing dirty hunk is lost.
10. Browser smoke at 1440/768/390 is required only if an authenticated local Admin session already exists. Otherwise record the existing environment limitation and use the mounted/service/accessibility evidence; do not request credentials or add a bypass.

## 8. Exact Wave 5 product allow-list — 11 existing paths, zero additions

### Runtime — 4

```text
FE/src/components/danh_muc/cay_giao_an.vue
FE/src/services/bai_tap.api.js
FE/src/pages/admin/bai_tap/bai_tap.chi_tiet.vue
FE/src/pages/admin/goi_tap/goi_tap.tao_moi.vue
```

### Tests — 7

```text
FE/src/components/danh_muc/cay_giao_an.test.js
FE/src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.test.js
FE/src/pages/admin/giao_an_mau/giao_an_mau.tao_moi.test.js
FE/src/pages/admin/giao_an_mau/giao_an_mau.tao_phien_ban.test.js
FE/src/services/bai_tap.api.test.js
FE/src/pages/admin/bai_tap/bai_tap.chi_tiet.test.js
FE/src/pages/admin/goi_tap/goi_tap.tao_moi.test.js
```

The fixer may append Wave 5 implementation and exact final-state evidence to `E:\Fitness\.fitness-sdd\fe3-all\task-fe3-all-report.md` under controller orchestration; it is not a product target. No other workflow artifact is writable by the fixer.

## 9. Exit gate

Wave 5 is ready for one fresh Luna Max fixer. A fresh Sol High final reviewer must inspect the live exact delta, source, tests, baseline/current/preservation package, all four FINAL findings, F-001..F-015 preservation, and Backend entry-repair preservation.

PASS requires FINAL-F-001 through FINAL-F-004 closed, no new Critical/Important finding, all focused/preservation/full Frontend and Backend gates green, the exact boundary green, staged diff empty, and no unexplained limitation. Only then may the controller update the completion checkpoint to `EXACT_NEXT_ACTION: FE4-ALL`.

Current blocker: none.
