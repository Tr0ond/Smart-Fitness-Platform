# FE3-ALL Controller Brief — Admin Catalog

`PLAN_STATUS: WAITING_BACKEND_FIX`  
`ENTRY_GATE: FAIL`  
`NEEDS_USER_DECISION: NO`  
`TASK_COUNT: 1`  
`TASK_ID: FE3-ALL`  
`DISPATCH_ALLOWED: NO`

## Controller instruction

Keep FE3-ALL as exactly one task and do not dispatch a writer. The approved phase requires Muscle Group list/create/edit/deactivate, but the actual Backend has no Muscle Group status authority. No partial FE3 implementation, partial review, checkpoint advance or evidence replacement is allowed.

Current product write allow-list: `EMPTY`.

Only the Initial Planner artifacts in `E:\Fitness\.fitness-sdd\fe3-all\plan.md` and `E:\Fitness\.fitness-sdd\fe3-all\fe3-all-brief.md` are produced by this turn. No Frontend, Backend, test, checkpoint or existing evidence file is authorized.

## Gate evidence

- FE0: PASS.
- FE1: PASS.
- FE2: PASS.
- Checkpoint exact next action: FE3-ALL.
- Admin shell readiness: PASS (layout, router/meta guard, menu registry, shared UI states, Axios client, auth/session cleanup patterns exist).
- Backend Package/benefits contract: present.
- Backend Equipment contract: present.
- Backend Exercise/relations and runtime Q11 `AND` contract: present.
- Backend Workout Template metadata/revision COW/stale contract: present.
- Backend Muscle Group deactivate contract: **FAIL**.

Authoritative blocker details:

- Routes exist for `GET|POST /api/admin/muscle-groups` and `PATCH /api/admin/muscle-groups/{muscleGroup}` under `auth:api`, `role:ADMIN`.
- `CreateMuscleGroupRequest` accepts only `code`, `name`, `description`.
- `UpdateMuscleGroupRequest` accepts only `name`, `description`.
- `ExerciseCatalogAdminService` persists/returns no Muscle Group status.
- `NhomCo` and the `nhom_co` schema contain no status field/check/default/index.
- `AdminCatalogApiTest` has no Muscle Group list/create/edit/deactivate scenario; its auth mutation matrix omits Muscle Group `PATCH`.
- No source-backed, non-destructive deactivation can therefore be called by FE3.

## Minimum dependency remediation (not an FE3 authorization)

- [ ] Add a forward schema change for `nhom_co` status with values `HOAT_DONG|NGUNG_SU_DUNG`, safe default/backfill and no row/relation deletion.
- [ ] Update `NhomCo`, create/update validation and service persistence/DTO output so list, create and patch expose the same status contract.
- [ ] Preserve all existing `bai_tap_nhom_co` and downstream history when a Muscle Group becomes inactive.
- [ ] Define source-authoritative behavior for inactive groups in new relation selectors/validation without rewriting prior relations.
- [ ] Add executable tests for Admin list/create/edit/deactivate, invalid status, 401/403, actor/branch boundary where applicable, PATCH auth matrix coverage and non-destructive relation/history preservation.
- [ ] Update API documentation only after the source and executable tests are complete.
- [ ] Pass catalog-focused and full Backend gates against the guarded MariaDB test database.

This remediation implements an already-approved requirement. It does not require a product-owner decision unless Backend analysis reveals a new business-policy choice not settled by `PROJECT_RULES.md`.

## One-task scope after the gate passes

Actor: authenticated `ADMIN`; Backend auth and branch ownership remain authoritative.

Use case: manage Admin catalogs for future use while preserving purchased-term snapshots, existing exercise relations, workout-template history and immutable completed Workout Sessions.

Binding scope: Package/benefits, Equipment, Muscle Group, Exercise relations/Q11, Workout Template metadata/revision COW, and full Admin integration only. Equipment maintenance and every FE4+ feature remain out of scope.

### Checklist A — Package and benefits

- [ ] List and detail all Admin-visible package statuses.
- [ ] Create a package plus benefit configuration in one explicit form workflow.
- [ ] Edit mutable metadata/status only; no client branch/creator/version/snapshot authority.
- [ ] Edit benefits through `PUT /api/admin/packages/{package}/benefits`.
- [ ] Enforce UI validation consistent with Backend: at least one benefit; AI off -> limit `0`; AI on -> `null` or positive.
- [ ] Retire by `NGUNG_BAN`; never delete.
- [ ] Explain that changes affect future configuration and do not mutate an existing membership term snapshot/shared activation clock.
- [ ] Refresh and display authoritative `configuration_version` after writes.

### Checklist B — Equipment

- [ ] List/create/edit/deactivate using `GET|POST /api/admin/equipment` and `PATCH /api/admin/equipment/{equipment}`.
- [ ] Use status `HOAT_DONG|NGUNG_SU_DUNG`; code is create-only/immutable.
- [ ] Never delete equipment or existing `bai_tap_dung_cu` relations/history.
- [ ] Cover duplicate/invalid input, 401/403/404/409/422/network states and submit locking.

### Checklist C — Muscle Group

- [ ] **Blocked until remediation is source-authoritative and tested.**
- [ ] List/create/edit/deactivate using `GET|POST /api/admin/muscle-groups` and `PATCH /api/admin/muscle-groups/{muscleGroup}`.
- [ ] Use the repaired `HOAT_DONG|NGUNG_SU_DUNG` field consistently in request and response DTOs.
- [ ] Code is create-only/immutable; retirement must preserve `bai_tap_nhom_co` relations/history.
- [ ] Cover invalid status, relations containing retired groups, auth and failure states.

### Checklist D — Exercise relations and Q11

- [ ] List/search/filter/detail/create/edit/deactivate using `/api/admin/exercises`.
- [ ] Map exact DTO fields: code/name/difficulty/instructions/image/video/metadata/content version/status/creator.
- [ ] Edit `equipment_ids` and `muscle_groups: [{id, role}]`, with `role` limited to `CHINH|PHU`.
- [ ] Display and test `equipment_semantics: "AND"`: all required equipment must be available at candidate selection and proposal apply time.
- [ ] Replace relations through the Backend transactional update only; do not issue independent destructive relation calls.
- [ ] Preserve existing plan/session/history records when exercise status or relations change.
- [ ] Cover invalid/duplicate/missing relations and authoritative refetch after success/ambiguous network outcome.

### Checklist E — Workout Template metadata, COW and stale handling

- [ ] List/detail all Admin-visible templates and render the ordered day/exercise tree.
- [ ] Create with full `days` tree and validated `sessions_per_week === days.length`, unique order, valid rep range and active exercises.
- [ ] Use `PATCH /api/admin/workout-templates/{id}` for metadata/status only; never send `days`.
- [ ] For tree changes, call `POST /api/admin/workout-templates/{id}/revisions` with `new_code`, `expected_content_version`, replacement metadata/status and full tree.
- [ ] Treat the revision response `{new_template_id,replaces_template_id,content_version,status,previous_template_status}` as authoritative.
- [ ] On `409 WORKOUT_TEMPLATE_STALE`, preserve the draft separately, refetch current detail and require explicit reconciliation; never silently overwrite.
- [ ] Verify the old tree, linked plan data and completed Workout Session history remain unchanged; no delete.
- [ ] Do not mutate completed Workout Sessions under any UI path.

### Checklist F — Full integration and phase gates

- [ ] Add named ADMIN routes, correct static-before-parameter ordering, breadcrumb metadata and parent active-route mapping for all 12 approved pages.
- [ ] Add ordered Admin menu entries only when the corresponding routes exist; no duplicate labels/routes.
- [ ] Reuse Admin layout and shared page-title/table/form/status/loading/empty/error/dialog/notification primitives.
- [ ] Add a catalog store cleanup hook to logout, actor-role loss and current-token 401; a late 401 from an old token must not clear a newer session.
- [ ] Handle loading, empty, success, 422 field errors, 401, 403, 404, 409, 5xx and network ambiguity accessibly and responsively.
- [ ] Complete service, store, component, page, router/menu and auth-cleanup tests, then all repository gates.
- [ ] Run independent task review and independent final review; both must PASS before phase evidence/checkpoint changes.

## Exact Backend API/DB obligations

| Catalog | Methods | Request/response authority | DB/transaction/history |
|---|---|---|---|
| Package | `GET/POST /admin/packages`, `GET/PATCH /admin/packages/{id}`, `PUT /admin/packages/{id}/benefits` | Status `DANG_BAN|NGUNG_BAN`; package DTO includes nested benefits and configuration version | Transaction/row lock for multi-row updates; `goi_tap` + `quyen_loi_goi_tap`; membership-term snapshots unchanged |
| Equipment | `GET/POST /admin/equipment`, `PATCH /admin/equipment/{id}` | `{id,code,name,description,status}` | `dung_cu`; restrictive FK; retirement retains relations |
| Muscle Group | `GET/POST /admin/muscle-groups`, `PATCH /admin/muscle-groups/{id}` | Must become `{id,code,name,description,status}` with validated status | `nhom_co`; forward migration; retirement retains `bai_tap_nhom_co`; currently missing |
| Exercise | `GET/POST /admin/exercises`, `GET/PATCH /admin/exercises/{id}` | Full exercise DTO, relation arrays, `equipment_semantics:"AND"` | Transactional `bai_tap` + relation replacement; restrictive FKs; history retained |
| Workout Template | `GET/POST /admin/workout-templates`, `GET/PATCH /admin/workout-templates/{id}`, `POST .../{id}/revisions` | Metadata-only PATCH; revision uses expected content version/full tree | Atomic COW revision across template/day/exercise rows, old retirement and audit; stale -> 409 |

Authorization/validation/failure rules apply server-side. Frontend may improve feedback but must not weaken them. No catalog endpoint advertises idempotency; after an unknown network outcome, refetch instead of blind retry. Workout revision concurrency is governed by `expected_content_version`. Only the Backend revision workflow creates the observed catalog audit record; the Frontend must not invent an audit trail.

## Deferred exact Frontend target paths

These targets describe the expected full task after the entry gate is repaired. They are not currently writable.

Integration:

- `E:\Fitness\FE\src\router\index.js`
- `E:\Fitness\FE\src\router\index.test.js`
- `E:\Fitness\FE\src\router\dieu_huong_admin.js`
- `E:\Fitness\FE\src\router\dieu_huong_admin.test.js`
- `E:\Fitness\FE\src\stores\xac_thuc.store.js`
- `E:\Fitness\FE\src\stores\xac_thuc.store.test.js`
- `E:\Fitness\FE\src\assets\main.css`

State/services:

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

Components:

- `E:\Fitness\FE\src\components\danh_muc\bo_sua_quyen_loi_goi_tap.vue`
- `E:\Fitness\FE\src\components\danh_muc\bo_sua_quyen_loi_goi_tap.test.js`
- `E:\Fitness\FE\src\components\danh_muc\bo_chon_quan_he_bai_tap.vue`
- `E:\Fitness\FE\src\components\danh_muc\bo_chon_quan_he_bai_tap.test.js`
- `E:\Fitness\FE\src\components\danh_muc\cay_giao_an.vue`
- `E:\Fitness\FE\src\components\danh_muc\cay_giao_an.test.js`
- `E:\Fitness\FE\src\components\danh_muc\hop_thoai_xung_dot_giao_an.vue`
- `E:\Fitness\FE\src\components\danh_muc\hop_thoai_xung_dot_giao_an.test.js`

Pages and their exact tests:

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

## Required controller verification after Backend repair

The controller must capture real command output; do not infer PASS from UI or docs.

Backend focused tests/scans:

1. Run the repaired Muscle Group tests plus `AdminCatalogApiTest`.
2. Run `AdminCatalogConcurrencyTest` and any new deactivation/history test class.
3. Run the complete Backend suite with the repository PHP runtime and guarded MariaDB test database.
4. Scan the route list for every exact Admin catalog method and confirm no catalog `DELETE` route.
5. Scan Muscle Group schema/model/request/service/DTO/test source for the same status enum and PATCH auth coverage.
6. Scan foreign keys and test assertions to confirm deactivation does not remove exercise relations or historical rows.

Frontend focused tests/scans after a future writer:

1. Run every new/changed FE3 service, store, component, page, router/menu and auth cleanup test.
2. Run full `npm test -- --run`, `npm run lint`, and `npm run build` under the Node engine declared in `FE/package.json`.
3. Scan for `/admin/packages`, `/admin/equipment`, `/admin/muscle-groups`, `/admin/exercises`, `/admin/workout-templates` and verify exact HTTP methods/payloads.
4. Scan for `delete(`, HTTP `DELETE`, destructive copy, cascade assumptions and direct relation-removal calls; expected FE3 catalog result is none.
5. Scan for FE4+ routes/endpoints/pages, payment/order/membership/workout/AI/PT feature expansion and Equipment maintenance; expected result is none.
6. Scan for client-supplied branch/creator/snapshot/version authority, logged tokens/secrets, hard-coded API roots, global idempotency injection, English identifiers/comments that violate project rules, and missing cleanup registration.
7. Verify all menu targets exist, all detail/create routes have correct breadcrumb/active parent metadata, and static routes precede parameter routes.
8. Browser smoke desktop/mobile and keyboard/focus for loading, empty, validation, success, confirmation, stale conflict and network ambiguity.

## Workflow and exit gates

- After entry repair, use exactly one `GPT-5.6 Luna Max` writer for the one FE3-ALL task.
- Use one fresh task reviewer for the complete task diff.
- Use one separate fresh final reviewer for the full phase and accumulated evidence.
- A reviewer may request fixes; fixes return to the same one writer, then the appropriate independent review reruns.
- Do not start FE4+, do not hard-delete or destroy history, and do not commit, push, create/switch a branch, or create/use a worktree.
- Do not update the checkpoint or replace existing evidence until every A-F item, focused/full test, static scan, task review and final review genuinely passes.

Exit is `PASS` only when the full, unsplit FE3-ALL scope is implemented against the repaired source-authoritative Backend contract and all gates above are green. Current exit remains `WAITING_BACKEND_FIX`.

## Decisions and risks

Decisions needed: none.

Risks:

- A hurried workaround could simulate deactivation only in Frontend while the Backend continues to accept/use the group.
- Adding status destructively could erase existing exercise relations/history; remediation must be forward and non-destructive.
- Blind mutation retry can duplicate creates because catalog idempotency is not advertised.
- Template stale handling or direct tree PATCH can overwrite concurrent work/history if the COW contract is bypassed.
- Splitting FE3 would allow dependent Exercise/Template screens to ship against an incomplete Muscle Group catalog; keep task count exactly one.
