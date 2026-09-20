# FE3-ALL consolidated final fix brief — wave 5

`STATUS: READY_FOR_FIX`  
`TASK_ID: FE3-ALL`  
`REPAIR_WAVE: 5`  
`MAPPED_FINDINGS: FINAL-F-001, FINAL-F-002, FINAL-F-003, FINAL-F-004`  
`PRESERVE_CLOSED: F-001..F-015, VUE_COMPILER_WARNING, BACKEND_ENTRY_REPAIR`  
`PRODUCT_ALLOW_LIST_COUNT: 11`  
`RUNTIME_PATH_COUNT: 4`  
`TEST_PATH_COUNT: 7`  
`PRODUCT_ADDITION_COUNT: 0`  
`REQUIRED_FOCUSED_REGRESSION_CASES: 8`  
`NEEDS_USER_DECISION: NO`  
`EXPECTED_REPORT: E:\Fitness\.fitness-sdd\fe3-all\task-fe3-all-report.md`

Implement one consolidated repair for all four findings from `final-review.md`. Do not split the repair, dismiss a finding, reopen a closed item, edit Backend/authority/checkpoint files, or write outside the exact product boundary below.

## Required context

Read completely before editing:

- `E:\Fitness\AGENTS.md`; discover deeper `AGENTS.md` files. No nested FE instruction exists. Read `E:\Fitness\BE\AGENTS.md` before the read-only Backend authority files.
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
- `E:\Fitness\.fitness-sdd\fe3-all\fe3-all-implementation-brief.md`
- `E:\Fitness\.fitness-sdd\fe3-all\task-fe3-all-report.md`
- `E:\Fitness\.fitness-sdd\fe3-all\final-review-package.md`
- `E:\Fitness\.fitness-sdd\fe3-all\final-review.md`
- `E:\Fitness\.fitness-sdd\fe3-all\final-fix-plan-round-5.md` and this brief.
- Controller-provided original baseline/51-target artifacts, live Wave 5 before snapshot, exact delta/current manifest, unexpected-path check, 710-path preservation evidence, and the 17-path Backend entry-repair preservation check.

Read-only Backend request/DTO/schema authority:

- `E:\Fitness\BE\routes\api.php`
- `E:\Fitness\BE\app\Http\Requests\Admin\Catalog\CreateWorkoutTemplateRequest.php`
- `E:\Fitness\BE\app\Http\Requests\Admin\Catalog\CreateWorkoutTemplateRevisionRequest.php`
- `E:\Fitness\BE\app\Services\Admin\WorkoutTemplateCatalogAdminService.php`
- `E:\Fitness\BE\app\Http\Requests\Admin\Catalog\CreateExerciseRequest.php`
- `E:\Fitness\BE\app\Http\Requests\Admin\Catalog\UpdateExerciseRequest.php`
- `E:\Fitness\BE\database\migrations\2026_08_29_000027_m027_tao_bai_tap.php`
- `E:\Fitness\BE\app\Services\Admin\ExerciseCatalogAdminService.php`
- relevant assertions in `E:\Fitness\BE\tests\Feature\AdminCatalogApiTest.php`.

Binding IDs: Q01, Q11, Q12, M061, RULE GYM 11–16, RULE CODE 05–11, 13, 17–20. `PROJECT_RULES.md` remains conditional authority. The only already-triggered frontend-plan lookup is limited to `docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md` sections 23.27–23.29, 31 FE-3, and 38A FE3-ALL, resolving that the complete Template tree and inactive-Exercise cases are required. If a new conflict, ambiguity, missing rule, or version mismatch appears, stop the affected edit, consult only the precise canonical passage, and record the trigger/resolution. Do not read the full canonical file or unrelated modules.

## Required repair

### FINAL-F-001 — full Template prescription

In `FE/src/components/danh_muc/cay_giao_an.vue`:

- Render a labelled `rest_seconds` numeric control for every item with integer bounds `0..65535`, step 1, required semantics, and the existing disabled/pending behavior.
- Render a labelled `notes` control with maximum 1000 characters. Preserve nullable input; an untouched DTO null stays null, and clearing the note emits null rather than inventing text.
- Use Vietnamese-without-accents camelCase functions and `xuLy...` handlers. Keep copied-tree emissions and all existing set/rep/order behavior.
- Read-only Template detail must show both values; create and revision must edit them.

Tests:

- `cay_giao_an.test.js`: change rest/notes, assert exact complete emitted tree and control bounds/disabled behavior.
- `giao_an_mau.chi_tiet.test.js`: assert stored rest/notes render in the disabled tree.
- `giao_an_mau.tao_moi.test.js`: select an active Exercise, edit rest/notes, submit, assert exact full `days` item.
- `giao_an_mau.tao_phien_ban.test.js`: retain/edit prescription values and assert exact full revision item.

### FINAL-F-002 — historical retired Exercise identity

In the same common component:

- For each stored row, merge active options with one retained fallback when its current `exercise_id` is absent. Label it from Backend `exercise_name`, with a safe ID fallback only when the name itself is unavailable.
- Mark the retained option as retired/unavailable and disabled for new selection, while allowing it to remain the initially selected historical value.
- Do not add retained choices to new rows. Active options remain selectable. Explicit replacement must emit only the selected active ID; never auto-replace or auto-submit.

Tests:

- `cay_giao_an.test.js`: retired label is visible and disabled; selecting an active replacement emits the active ID.
- `giao_an_mau.chi_tiet.test.js`: a detail DTO whose retired ID is absent from active options still renders `exercise_name`.
- `giao_an_mau.tao_phien_ban.test.js`: load a retired row, select an active replacement, submit once, and assert the complete revision tree contains the replacement and excludes the retired ID.

Do not edit any Template page runtime file. If a mounted regression proves the shared component cannot satisfy this contract, return `BLOCKED` with that evidence rather than broadening the allow-list.

### FINAL-F-003 — nullable Exercise metadata

In `FE/src/services/bai_tap.api.js`:

- When `metadata` is present, accept exactly `null` or a non-array object and preserve the value in the body.
- Reject arrays, strings, numbers, and other non-object non-null values with the existing normalized `422 / INVALID_EXERCISE_REQUEST` before Axios.
- Preserve all endpoints, status/instructions validation, nullable image/video, authority-field exclusion, Q11 arrays, M061 relations, and no-blind-retry behavior.

In `FE/src/pages/admin/bai_tap/bai_tap.chi_tiet.vue`:

- Remove the `null -> {}` hydration coercion. The initial form, `nap`, ordinary metadata/relation submit, status reconciliation, and authoritative refetch must remain null-safe.
- Do not add a metadata editor or change status/Q11/M061 flows.

Tests:

- `bai_tap.api.test.js`: public create/update accepts and sends exact `metadata: null`; invalid non-null shapes still make zero network calls.
- `bai_tap.chi_tiet.test.js`: load `metadata: null`, change an unrelated field, submit, and prove no `{}` rewrite; preserve null after authoritative GET.

### FINAL-F-004 — Package create benefit-save action

In `FE/src/pages/admin/goi_tap/goi_tap.tao_moi.vue`:

- Bind `BoSuaQuyenLoiGoiTap`'s existing `luu` event to the existing `xuLyLuu` create handler.
- Reuse the same validation, pending guard, one `taoGoiTap` call, error state, and response navigation. Do not add a second handler, benefits PUT, duplicate POST, or change the shared component.

In `goi_tap.tao_moi.test.js`, fill valid metadata and click the visible component-level “Lưu quyền lợi” action. Assert exactly one create call and the existing navigation; retain all current submit/422/bounds/benefit/pending tests.

## Preservation requirements

Preserve F-001..F-015 exactly as summarized in the Wave 5 plan, including M061 inactive echo, Template stale separation/no auto-POST, mounted coverage, zero-warning lint, unknown-outcome GET-only reconciliation, docblocks, Vietnamese text/official identifiers, dialog accessibility, initial revision GET retry, all nullable DTOs, Package benefit shapes, list-only status transitions, required Exercise instructions, authoritative Package configuration version, Template detail copy, and absence of `v-model="form"` compiler warnings.

No executable catalog DELETE/hard-delete, direct pivot removal, old Template/Plan/Session rewrite, completed Workout mutation, blind catalog mutation retry, invented idempotency header, auth/session cleanup change, or FE4+ scope is allowed.

The 17 unique Backend entry-repair paths listed in the plan must remain byte-for-byte identical to their live pre-writer snapshot. In particular, preserve the M061 schema/default/check, model/request/DTO contract, transaction/audit/no-op behavior, row-lock race serialization, seeder protection against recreating a missing inactive pivot, and all API/concurrency support tests.

## Exact product allow-list

Only these 11 existing paths may be modified; unused allow-listed paths may remain unchanged:

```text
FE/src/components/danh_muc/cay_giao_an.vue
FE/src/components/danh_muc/cay_giao_an.test.js
FE/src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.test.js
FE/src/pages/admin/giao_an_mau/giao_an_mau.tao_moi.test.js
FE/src/pages/admin/giao_an_mau/giao_an_mau.tao_phien_ban.test.js
FE/src/services/bai_tap.api.js
FE/src/services/bai_tap.api.test.js
FE/src/pages/admin/bai_tap/bai_tap.chi_tiet.vue
FE/src/pages/admin/bai_tap/bai_tap.chi_tiet.test.js
FE/src/pages/admin/goi_tap/goi_tap.tao_moi.vue
FE/src/pages/admin/goi_tap/goi_tap.tao_moi.test.js
```

Append Wave 5 implementation details and exact final-state evidence to `E:\Fitness\.fitness-sdd\fe3-all\task-fe3-all-report.md`; it is not a product target. Do not modify another workflow artifact or create a source/test file.

## API, authorization, validation, failure, concurrency, idempotency, transaction, and history

- Backend remains the sole authority. Relevant routes stay under `auth:api` and `role:ADMIN`; no frontend role shortcut or resource authority is added.
- Template create/revision payloads retain the exact Backend allow-list and full tree. Template detail PATCH remains metadata-only. Retired labels come from `exercise_name`; new/revision relations must use an active Exercise.
- Exercise metadata follows Backend nullable array/JSON semantics. Client validation is an early guard and never replaces Backend validation.
- Exact 409 `WORKOUT_TEMPLATE_STALE` handling, normalized 401/403/404/422/5xx/network behavior, draft retention, and GET-only reconciliation remain unchanged.
- No new concurrency or idempotency mechanism is needed. Keep `expected_content_version` and existing pending guards; never add a catalog idempotency header or blind retry.
- No client transaction/audit write. Backend Template COW and M061 transaction/locks/audit remain unchanged. No history or completed Session can be deleted or rewritten.

## Required verification

After the final product edit, record exact observed commands, environment, file/test/assertion counts, failures/skips, and warnings.

1. From `E:\Fitness\FE`, run the focused seven-file command in the Wave 5 plan. All eight required regression scenarios must be present and pass; no skip/only, unhandled rejection, Vue warning, or compiler warning.
2. Run the established 28-file closed-finding preservation matrix. Record final counts; do not accept a silent reduction from the prior 28 files / 209 tests.
3. Run `rtk npm run test`, `rtk npm run lint`, `rtk npm run build`, and `rtk npm ls --depth=0`. The prior baseline is 58 files / 675 tests; final test count must reflect the new coverage, lint must report zero warnings/errors, build must pass, and package/lock delta must be empty.
4. Run the static scans enumerated in the plan, plus checks that retained retired options are display-only, the full tree includes rest/notes, Exercise null metadata is not coerced, and Package create consumes `luu` exactly once.
5. From `E:\Fitness\BE`, first set `APP_ENV=testing`, MySQL, `DB_DATABASE=smart_fitness_test`, and `SMART_FITNESS_TEST_DATABASE=smart_fitness_test` in the same process. Use only `E:\Fitness\.tools\php\php.exe`. Run `controller-test-guard.php`, `artisan test --filter=AdminCatalogApiTest`, `artisan test --filter=AdminCatalogConcurrencyTest`, full `artisan test`, and `vendor\bin\pint --test`. Do not run destructive migrations or use another database.
6. Run `rtk git diff --check`; staged diff must be empty. The controller package must show at most 11 changed product paths, all allow-listed; zero additions/unexpected paths; all original 51 FE3 targets present; all non-target pre-existing changes preserved; and all 17 Backend entry-repair paths byte-identical.
7. Attempt browser smoke only if an authenticated local Admin session already exists. Otherwise record the known limitation without requesting credentials or adding an auth/API bypass.

Return `DONE` only when FINAL-F-001 through FINAL-F-004 are closed and every preservation and final gate is green. Otherwise return `DONE_WITH_CONCERNS` or `BLOCKED` with exact unresolved evidence. A fresh independent Sol High final review is mandatory before checkpoint advancement.
