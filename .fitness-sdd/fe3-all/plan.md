# FE3-ALL Initial Plan — Admin Catalog

`PLAN_STATUS: WAITING_BACKEND_FIX`  
`ENTRY_GATE: FAIL`  
`NEEDS_USER_DECISION: NO`  
`TASK_COUNT: 1`  
`TASK_ID: FE3-ALL`

## 1. Outcome and authority

FE3-ALL must remain one indivisible implementation task. It is not authorized to start because the actual Backend contract cannot complete the mandatory Muscle Group list/create/edit/deactivate workflow. This is a missing implementation dependency, not a product-owner decision.

The planner independently read the complete governing instructions and specifications (`AGENTS.md`, `PROJECT_RULES.md`, `BE/AGENTS.md`, `docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md`, `docs/VUE_WEB_COMPLETION_CHECKPOINT.md`) and inspected the actual Backend routes, controllers, requests, services, models, migrations and executable Admin catalog tests, plus the actual Frontend router, Admin navigation/layout, shared components, Axios/auth cleanup patterns, representative stores/tests, package and test configuration.

Entry evidence:

- FE0, FE1 and FE2 are recorded `PASS`; the checkpoint's `EXACT_NEXT_ACTION` is FE3-ALL.
- The Admin shell, role-aware router, Admin menu registry, shared loading/empty/error/table/form/dialog/status primitives, single Axios client and session cleanup mechanism exist.
- The Admin catalog routes are guarded by `auth:api` and `role:ADMIN`, and service methods independently revalidate the current Admin actor/branch context.
- Package/benefits, Equipment, Exercise and Workout Template contracts are substantially present.
- Muscle Group has `GET`, `POST` and `PATCH`, but it has no status column, accepted status field, returned status field or deactivate test. Consequently there is no non-destructive deactivate operation for this catalog.

No controller summary or documentation-only statement overrides the actual source evidence.

## 2. Actor, use case and scope classification

Actor: authenticated Web actor `ADMIN`, operating within the actor's authoritative branch context. Frontend guards are presentation only; Backend authorization remains authoritative for every request.

Use case: Admin manages the sellable package catalog and benefit configuration, equipment, muscle groups, exercises and relations, and workout-template metadata/revisions without deleting historical records or changing already materialized membership/workout history.

Scope classification:

- CORE: Package and benefit configuration; Equipment catalog; Muscle Group catalog; Exercise catalog plus relations; Q11 equipment `AND` semantics; Workout Template create/detail/metadata/deactivate/revision copy-on-write; Admin integration, auth/session cleanup and verification.
- OUT OF SCOPE: Equipment maintenance/ticket workflows; payments and order UI; FE4+ membership, workout, AI, PT, receptionist or reporting features; Backend feature development inside the FE3 writer task.
- FORBIDDEN: hard delete, relation/history destruction, direct edits to completed Workout Sessions, changing prior membership-term snapshots, reusing a later paid term early, commit/push/branch/worktree operations.

## 3. Binding business rules

1. Catalog retirement is status-based. No FE3 screen may expose or call `DELETE` for Package, Equipment, Muscle Group, Exercise or Workout Template.
2. Payment only records ownership. A valid first paid-benefit use activates the first term on the shared Gym/AI/PT clock; later purchased terms keep their own snapshots and order. FE3 may only edit future package configuration.
3. `configuration_version` and term benefit snapshots isolate existing terms from later package/benefit edits. The UI must never suggest that an edit retroactively changes an active or completed term.
4. Exercise equipment requirements use Q11 `AND`: a candidate is feasible only when every required equipment ID is available. UI copy and relation editing must not imply `OR`.
5. Exercise/equipment/muscle relations are updated transactionally; retirement does not delete their existing rows or historical consumers.
6. Workout Template `PATCH` is metadata/status only. Any tree change creates a new revision with full `days`, `new_code` and `expected_content_version`; the old template is retired atomically. A `409 WORKOUT_TEMPLATE_STALE` requires refetch and explicit user reconciliation, never a silent overwrite.
7. Completed Workout Sessions and their recorded contents are immutable. Template/relation changes affect future selection/materialization only.
8. Server response DTOs are authoritative. Frontend must not invent branch/creator/snapshot/version authority fields or trust client-side role checks as authorization.

## 4. Actual Backend contract matrix

All paths below are relative to `/api`. Successful controller envelopes are `{ data: ... }`; create endpoints return HTTP 201, other successful reads/updates return HTTP 200.

### Package and benefits

- `GET /admin/packages` -> array of `{ id, branch_id, code, name, price, currency, duration_days, description, status, configuration_version, created_by_id, benefits, created_at, updated_at }`.
- `GET /admin/packages/{package}` -> the same detail DTO.
- `POST /admin/packages` accepts `code`, `name`, `price`, `duration_days`, optional `description`, `status` (`DANG_BAN|NGUNG_BAN`) and required nested `benefits`.
- `PATCH /admin/packages/{package}` accepts only mutable metadata/status: `name`, `price`, `duration_days`, `description`, `status`.
- `PUT /admin/packages/{package}/benefits` accepts `{ gym_access, fitness_assistant, fitness_assistant_limit, trainer_chat, direct_trainer_sessions }`.
- Benefit validation requires at least one benefit. AI disabled requires limit `0`; AI enabled requires `null` (unlimited) or a positive limit. Client-controlled branch, creator, version and snapshot authority are prohibited.
- Writes: `goi_tap`, `quyen_loi_goi_tap`; update locks the package and increments its configuration version. Package plus benefits are created/updated transactionally. Membership-term snapshots remain unchanged.

### Equipment

- `GET /admin/equipment` -> array of `{ id, code, name, description, status }`.
- `POST /admin/equipment` accepts `code`, `name`, optional `description`, `status` (`HOAT_DONG|NGUNG_SU_DUNG`).
- `PATCH /admin/equipment/{equipment}` accepts `name`, `description`, `status`; code is immutable.
- Writes: `dung_cu`; exercise relations in `bai_tap_dung_cu` are not deleted on retirement. Database foreign keys are restrictive.

### Muscle Group — blocking gap

- `GET /admin/muscle-groups` currently returns only `{ id, code, name, description }`.
- `POST /admin/muscle-groups` currently accepts only `code`, `name`, optional `description`.
- `PATCH /admin/muscle-groups/{muscleGroup}` currently accepts only `name`, `description`; code is immutable.
- `nhom_co`, `NhomCo`, `CreateMuscleGroupRequest`, `UpdateMuscleGroupRequest`, `ExerciseCatalogAdminService::taoNhomCo/capNhatNhomCo/duLieuNhomCo` contain no status authority.
- There is no executable list/create/edit/deactivate Muscle Group contract test, and the Admin auth mutation matrix omits Muscle Group `PATCH`.
- Therefore the required non-destructive deactivate use case cannot be implemented by Frontend.

### Exercise and Q11 relations

- `GET /admin/exercises?search=&status=` -> array; `GET /admin/exercises/{exercise}` -> detail.
- `POST /admin/exercises` and `PATCH /admin/exercises/{exercise}` use `code` (create only), `name`, `difficulty`, `instructions`, optional `image_path`, `video_path`, `metadata`, `status`, `equipment_ids`, and `muscle_groups: [{ id, role }]` where role is `CHINH|PHU`.
- DTO: `{ id, code, name, difficulty, instructions, image_path, video_path, metadata, content_version, status, created_by_id, equipment_semantics: "AND", equipment: [{id,code,name}], muscle_groups: [{id,code,name,role}] }`.
- Writes: `bai_tap`, replacement sets in `bai_tap_dung_cu` and `bai_tap_nhom_co`, in one transaction. The runtime candidate rule and proposal-apply validator both enforce required-equipment set inclusion (`AND`).
- Validation/failures include unknown relations, duplicate/invalid relation data, inactive/invalid referenced items where service rules apply, uniqueness conflicts and standard 401/403/404/422/409 responses.

### Workout Template

- `GET /admin/workout-templates` -> summaries `{ id, code, name, goal, level, sessions_per_week, content_version, status, created_by_id, day_count }`.
- `GET /admin/workout-templates/{workoutTemplate}` adds `description` and `days: [{ id, order, name, estimated_minutes, exercises: [{ id, exercise_id, exercise_name, order, target_sets, min_reps, max_reps, rest_seconds, notes }] }]`.
- `POST /admin/workout-templates` accepts code/metadata/status plus the full `days` tree.
- `PATCH /admin/workout-templates/{workoutTemplate}` accepts metadata/status only and explicitly rejects `days`.
- `POST /admin/workout-templates/{workoutTemplate}/revisions` accepts `new_code`, `expected_content_version`, optional replacement metadata/status and a full `days` tree; response is `{ new_template_id, replaces_template_id, content_version, status, previous_template_status }`.
- Writes: `giao_an_mau`, `ngay_trong_giao_an`, `bai_tap_trong_giao_an`; revision locks the old template, checks active status and expected version, writes the new tree, retires the old row and records audit in one transaction.
- Structure validation requires `sessions_per_week === days.length`, unique day/exercise order, `min_reps <= max_reps`, and existing active exercises.

## 5. Transaction, concurrency, idempotency and failure policy

- Reads do not require a client transaction. Multi-row catalog mutations are Backend transactions; the Frontend must treat a success response as the only completion signal.
- The catalog endpoints do not advertise idempotency keys. The shared Axios client correctly does not inject a global `Idempotency-Key`. FE3 must not fabricate one for these endpoints.
- After a timeout/network ambiguity, do not auto-repeat a mutation. Refetch the authoritative resource/list and let the Admin decide whether another submission is needed.
- Disable the active submit control while a request is in flight, but retain server field errors after 422 and display normalized global errors for 401/403/404/409/5xx/network cases.
- Exercise relation replacement and Package/benefit multi-row writes rely on Backend transaction/locking. Refetch detail after success to capture the returned content/configuration version.
- Workout-template revision uses optimistic concurrency through `expected_content_version`. On stale 409, preserve the local draft separately, fetch current detail, explain the conflict and require an explicit new action.
- Ordinary catalog writes do not expose an audit API to FE3. Workout revision creates a Backend audit record. Frontend logs must not contain secrets or pretend to be the audit source of truth.

## 6. Minimal Backend remediation required before redispatch

This section identifies a dependency; it does not authorize Backend edits in FE3-ALL.

1. Add a forward-compatible status authority for `nhom_co`, preserving all existing rows and relations. The contract must support `HOAT_DONG|NGUNG_SU_DUNG`, a safe default/backfill, and no hard delete.
2. Update the `NhomCo` model and create/update request validation/normalization so status is accepted only from the defined enum and prohibited authority fields remain prohibited.
3. Persist and return `status` from Muscle Group create, update and list DTOs using the existing `PATCH /admin/muscle-groups/{muscleGroup}` endpoint.
4. Define and test the non-destructive effect of an inactive Muscle Group on relation selectors and existing `bai_tap_nhom_co` rows. Existing exercise/history relations must remain intact; no cascade/history rewrite is acceptable.
5. Add executable Admin tests for list/create/edit/deactivate, invalid status, unauthenticated/unauthorized access, wrong branch/actor behavior where applicable, and relation/history preservation. Add Muscle Group `PATCH` to the catalog auth mutation matrix.
6. Update the Backend API contract after source and tests are authoritative, then pass catalog-focused and full Backend gates.

No product-owner choice is needed to establish deactivate support: it is already mandatory in the approved FE3 scope. If Backend owners discover a genuinely new business choice while implementing inactive-selector behavior, that choice must be escalated separately before implementation.

## 7. Deferred Frontend target surface (not authorized)

The following is the exact expected FE3 target surface after the Backend gate is repaired. It is a deferred design inventory, not a write allow-list:

Existing integration files:

- `E:\Fitness\FE\src\router\index.js`
- `E:\Fitness\FE\src\router\index.test.js`
- `E:\Fitness\FE\src\router\dieu_huong_admin.js`
- `E:\Fitness\FE\src\router\dieu_huong_admin.test.js`
- `E:\Fitness\FE\src\stores\xac_thuc.store.js`
- `E:\Fitness\FE\src\stores\xac_thuc.store.test.js`
- `E:\Fitness\FE\src\assets\main.css`

New state/service files:

- `E:\Fitness\FE\src\stores\danh_muc.store.js`
- `E:\Fitness\FE\src\stores\danh_muc.store.test.js`
- `E:\Fitness\FE\src\services\goi_tap.api.js`
- `E:\Fitness\FE\src\services\goi_tap.api.test.js`
- `E:\Fitness\FE\src\services\dung_cu.api.js`
- `E:\Fitness\FE\src\services\dung_cu.api.test.js`
- `E:\Fitness\FE\src\services\nhom_co.api.js`
- `E:\Fitness\FE\src\services\nhom_co.api.test.js`
- `E:\Fitness\FE\src\services\bai_tap.api.js`
- `E:\Fitness\FE\src\services\bai_tap.api.test.js`
- `E:\Fitness\FE\src\services\giao_an_mau.api.js`
- `E:\Fitness\FE\src\services\giao_an_mau.api.test.js`

New reusable FE3 components:

- `E:\Fitness\FE\src\components\danh_muc\bo_sua_quyen_loi_goi_tap.vue`
- `E:\Fitness\FE\src\components\danh_muc\bo_sua_quyen_loi_goi_tap.test.js`
- `E:\Fitness\FE\src\components\danh_muc\bo_chon_quan_he_bai_tap.vue`
- `E:\Fitness\FE\src\components\danh_muc\bo_chon_quan_he_bai_tap.test.js`
- `E:\Fitness\FE\src\components\danh_muc\cay_giao_an.vue`
- `E:\Fitness\FE\src\components\danh_muc\cay_giao_an.test.js`
- `E:\Fitness\FE\src\components\danh_muc\hop_thoai_xung_dot_giao_an.vue`
- `E:\Fitness\FE\src\components\danh_muc\hop_thoai_xung_dot_giao_an.test.js`

New Admin pages and their exact tests:

- `E:\Fitness\FE\src\pages\admin\goi_tap\goi_tap.index.vue`
- `E:\Fitness\FE\src\pages\admin\goi_tap\goi_tap.index.test.js`
- `E:\Fitness\FE\src\pages\admin\goi_tap\goi_tap.tao_moi.vue`
- `E:\Fitness\FE\src\pages\admin\goi_tap\goi_tap.tao_moi.test.js`
- `E:\Fitness\FE\src\pages\admin\goi_tap\goi_tap.chi_tiet.vue`
- `E:\Fitness\FE\src\pages\admin\goi_tap\goi_tap.chi_tiet.test.js`
- `E:\Fitness\FE\src\pages\admin\dung_cu\dung_cu.index.vue`
- `E:\Fitness\FE\src\pages\admin\dung_cu\dung_cu.index.test.js`
- `E:\Fitness\FE\src\pages\admin\nhom_co\nhom_co.index.vue`
- `E:\Fitness\FE\src\pages\admin\nhom_co\nhom_co.index.test.js`
- `E:\Fitness\FE\src\pages\admin\bai_tap\bai_tap.index.vue`
- `E:\Fitness\FE\src\pages\admin\bai_tap\bai_tap.index.test.js`
- `E:\Fitness\FE\src\pages\admin\bai_tap\bai_tap.tao_moi.vue`
- `E:\Fitness\FE\src\pages\admin\bai_tap\bai_tap.tao_moi.test.js`
- `E:\Fitness\FE\src\pages\admin\bai_tap\bai_tap.chi_tiet.vue`
- `E:\Fitness\FE\src\pages\admin\bai_tap\bai_tap.chi_tiet.test.js`
- `E:\Fitness\FE\src\pages\admin\giao_an_mau\giao_an_mau.index.vue`
- `E:\Fitness\FE\src\pages\admin\giao_an_mau\giao_an_mau.index.test.js`
- `E:\Fitness\FE\src\pages\admin\giao_an_mau\giao_an_mau.tao_moi.vue`
- `E:\Fitness\FE\src\pages\admin\giao_an_mau\giao_an_mau.tao_moi.test.js`
- `E:\Fitness\FE\src\pages\admin\giao_an_mau\giao_an_mau.chi_tiet.vue`
- `E:\Fitness\FE\src\pages\admin\giao_an_mau\giao_an_mau.chi_tiet.test.js`
- `E:\Fitness\FE\src\pages\admin\giao_an_mau\giao_an_mau.tao_phien_ban.vue`
- `E:\Fitness\FE\src\pages\admin\giao_an_mau\giao_an_mau.tao_phien_ban.test.js`

Future implementation must reuse the existing Admin layout and shared loading/empty/error/table/form/status/confirmation/notification components. The catalog store must expose a cleanup function following existing store conventions and be invoked on logout, role loss and valid-current-token 401 without clearing a newer session after a late 401.

### Exact product write allow-list for the current task

`EMPTY`

No Backend file, Frontend file, test, checkpoint, evidence, configuration or lockfile is authorized for modification while `ENTRY_GATE: FAIL`.

## 8. Single-task implementation and test matrix after gate repair

The controller must redispatch exactly one task, FE3-ALL, to one Luna Max writer only after the entry gate passes. Do not split checklists A-F across writers or merge a partial catalog.

Required functional coverage in that future task:

- Package: list/detail/create/edit/deactivate, benefit edit, conditional AI-limit validation, snapshot-safe copy and configuration-version refresh.
- Equipment: list/create/edit/deactivate with relation preservation.
- Muscle Group: list/create/edit/deactivate from the repaired source-authoritative status contract.
- Exercise: search/filter/list/detail/create/edit/deactivate, equipment and muscle relation editing, `CHINH|PHU`, exact Q11 `AND` explanation and payloads.
- Workout Template: list/detail/create, metadata/status-only patch, full-tree revision COW, stale conflict/reload workflow, old-tree/history preservation.
- Integration: all named routes and breadcrumbs, ordered menu entries/active parent mapping, ADMIN-only guard presentation, store cleanup, normalized loading/empty/error/field-error/409/network states, responsive and keyboard/focus behavior.

Tests must cover service URL/method/payload/envelope mappings; DTO parsing; store isolation/cleanup/late-401 behavior; every list/create/detail/edit/deactivate flow; negative validation; double-submit prevention; ambiguous network failure; Q11 relation payload/wording; template stale reconciliation; no hard-delete calls; route ordering/meta/menu/active state; and accessibility behavior of dialogs/forms/tables. No visual-only assertion is a substitute for API/store/integration assertions.

## 9. Gates and acceptance criteria

Entry gate to reopen FE3-ALL:

- FE0/FE1/FE2 remain PASS and checkpoint still names FE3-ALL as exact next action.
- Working/staged baseline is captured and unrelated user changes are preserved.
- Actual Backend Muscle Group source and executable tests satisfy remediation items 1-6.
- Catalog-focused Backend tests and full Backend suite pass against the guarded MariaDB test database.
- Route/API scans confirm all required Admin methods and no catalog hard-delete route.
- A fresh planner or controller revalidates the complete source contract; documentation alone is insufficient.

Exit gate for the future one-task FE3 implementation:

- All checklist A-F acceptance cases pass; no partial checklist is deferred.
- Focused FE3 tests pass, then full `npm test -- --run`, `npm run lint`, and `npm run build` pass under the repository Node engine.
- Required Backend catalog tests and full Backend suite remain green.
- Static scans show no `DELETE` catalog call, no FE4+ endpoint/page, no client authority-field injection, no secrets/logged tokens, no broken route/menu target, and no prohibited English identifiers/comments under project naming rules.
- Browser-level smoke verifies desktop/mobile layout, keyboard/focus, loading/empty/error, successful refresh, 422 field errors, 401/403, 409 stale, and network retry guidance.
- Task reviewer independently returns PASS on the full FE3 diff; a separate final reviewer returns PASS on the complete phase and evidence. Only then may checkpoint/evidence be updated by the controller-authorized role.

Acceptance for this planning turn is met when the controller receives this explicit gate failure, keeps FE3-ALL unsplit and undispatched, obtains the minimal Backend remediation, and reruns entry validation. There is no user decision pending.

## 10. Current risks

- Primary blocker: Muscle Group cannot be retired without deletion because no status contract exists.
- Existing Muscle Group relations can become a history-integrity risk if remediation uses deletion/cascade or rewrites relations instead of status retirement.
- Catalog mutations lack advertised idempotency; blind retry after an unknown outcome could duplicate create operations.
- Ordinary catalog mutations expose no FE-facing audit trail; Frontend must not imply otherwise. Workout revision audit is Backend-owned.
- The deferred FE3 surface is broad, so route/menu/store cleanup and template conflict tests are essential once the gate reopens.
