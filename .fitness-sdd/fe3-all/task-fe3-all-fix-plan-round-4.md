# FE3-ALL consolidated fix plan — round 4

`STATUS: READY_FOR_FIX`  
`TASK_ID: FE3-ALL`  
`FIX_ROUND: 4`  
`OPEN_FINDINGS: F-013, F-015`  
`MAPPED_FINDING_COUNT: 2`  
`PRESERVE_CLOSED: F-001, F-002, F-003, F-004, F-005, F-006, F-007, F-008, F-009, F-010, F-011, F-012, F-014, VUE_COMPILER_WARNING`  
`NEEDS_USER_DECISION: NO`  
`FULL_CANONICAL_REREAD: CONDITIONAL_NOT_REQUIRED`  
`ORIGINAL_PRODUCT_BOUNDARY: 51 PATHS`  
`ROUND_4_PRODUCT_ALLOW_LIST: 4 PATHS`  
`ROUND_4_PRODUCT_ADDITIONS: 0`

## 1. Repair outcome and boundary

Run one consolidated round-4 repair with a fresh GPT-5.6 Luna Max fixer. Close the remaining Important create-validation defect F-013 and the related Minor visible-copy defect F-015 without reopening any closed behavior.

The actor remains an authenticated `ADMIN`. The use cases are limited to creating an Exercise through the existing public Frontend service and reading the explanatory copy on Workout Template detail. No route, endpoint, screen, dependency, configuration, Backend rule, database behavior, or workflow is added.

The exact product allow-list is the four existing files in section 7. Every path is already inside the original 51-path FE3-ALL boundary. No Backend, Database, FE4+, Mobile, router, store, shared component, package/lock, checkpoint, rule/context, controller artifact, prior plan/brief/review/package, or existing report may be changed by this planner. The fixer must not expand its product writes beyond the four paths.

## 2. Evidence and contract ruling

- Round-3 independent review is authoritative over the round-3 implementation report and leaves F-013 open at Important severity. It independently closes F-003, F-006, F-011 and F-012 and confirms preservation of all earlier closed findings.
- `BE/app/Http/Requests/Admin/Catalog/CreateExerciseRequest.php` requires `instructions` with `required|string`.
- `BE/app/Http/Requests/Admin/Catalog/UpdateExerciseRequest.php` uses `sometimes|required|string`: omission is legal for PATCH, while a present null, blank, object, array, or other non-string is invalid.
- The current `taoPayloadBaiTap` makes `instructions` required only when the property is present with a non-`undefined` value. Because the non-partial required list omits `instructions`, an otherwise valid create without the property reaches `ketNoiApi.post`. This is the root cause of F-013.
- The current Template detail heading says `PATCH chỉ sửa metadata/status`, while the same page keeps status disabled/read-only and sends metadata without `status`. Status transitions are performed from the Template list. This is the root cause of F-015.
- No conflict, ambiguity, missing rule coverage, or version mismatch was found. `PROJECT_RULES.md` and `docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md` remain conditional authorities and were not reread.

## 3. Direct finding mapping

### F-013 — Important — restore required Exercise instructions on create

Root cause: round 3 removed `instructions` from the non-partial required-field list while adding property-presence logic for partial updates. The new condition fixed PATCH omission but accidentally weakened POST create validation.

Repair:

- In `FE/src/services/bai_tap.api.js`, make `instructions` required whenever `partial === false`.
- For partial payloads, keep `instructions` required only when it is present with a concrete value under the existing convention. Omitted `instructions`, including the existing `undefined` omission convention, must remain absent from PATCH.
- Keep the existing normalized `INVALID_EXERCISE_REQUEST` 422 shape and `fieldErrors.instructions` behavior. The invalid create must throw before evaluating `ketNoiApi.post`.
- Do not make other partial strings globally required. Preserve nullable `image_path`/`video_path`, status validation, authority-field exclusion, complete Q11 `equipment_ids`, M061 `muscle_groups` id/role behavior, and every public endpoint.

Focused regression:

- In `FE/src/services/bai_tap.api.test.js`, add a dedicated direct-service case that removes `instructions` entirely from an otherwise valid create payload, calls `taoBaiTap`, expects the normalized 422 with an `instructions` field error, and proves zero POST calls.
- Retain and rerun the existing create null/blank/object rejection, partial null/blank/object rejection, partial omission reaching exact PATCH without `instructions`, valid partial string PATCH, nullable media, Q11, and M061-related service evidence.

Acceptance: missing create instructions never reaches Axios; valid create still reaches the existing POST with trimmed instructions; partial omission remains legal and does not synthesize an `instructions` field.

### F-015 — Minor — make Template detail copy match list-only status transitions

Root cause: the status action/state machine was correctly removed from Template detail in round 3, but the visible page description still claims the detail PATCH edits status.

Repair:

- In `FE/src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.vue`, replace only the inaccurate `TieuDeTrang` description. The new Vietnamese copy must state that this detail PATCH edits metadata only, status transitions are performed from the Workout Template list, and `days` content changes require a new revision.
- Do not change the disabled status display, metadata payload, tree, revision navigation, load/retry/error/pending state, or styling.
- In `FE/src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.test.js`, extend the existing mounted read-only-status test with a semantic visible-copy assertion covering both `metadata only` and `status transition from the list`. This test edit is justified because F-015 is specifically a user-visible semantic contract and the test already owns that page behavior.

Acceptance: the page no longer tells users that its PATCH edits status; the mounted test protects the intended list-only status workflow; metadata PATCH still excludes `status` and `days`.

## 4. Preservation constraints

- Preserve F-001 exact inactive M061 id/role echo and locked controls.
- Preserve F-002/F-009 stale Template draft separation, explicit version reconciliation, retry GET, and zero automatic POST.
- Preserve F-003 mounted create/detail/revision loading, retry, 422, pending, authoritative refresh, nullable COW payload, and navigation evidence.
- Preserve F-004 full lint at zero warnings and zero errors.
- Preserve F-005 Package/Equipment/Muscle Group/Template list unknown-outcome state machines, GET-only reconciliation, deliberate retry, and required cache bypass.
- Preserve F-006 Template service docblocks and their runtime behavior.
- Preserve F-007 UTF-8 Vietnamese visible copy; canonical technical identifiers, route names, payload keys, statuses, and error codes remain unchanged.
- Preserve F-008 shared-dialog accessibility and focus behavior.
- Preserve F-010 nullable Package/Template descriptions, Exercise image/video, and Template exercise notes.
- Preserve F-011 Package benefit service-boundary validation and all five valid benefit shapes.
- Preserve F-012 Package/Template detail list-only status binding and absence of detail status controls/dialog/state machines.
- Preserve F-014 authoritative Package `configuration_version` refresh and last-confirmed value.
- Do not restore const-reactive `v-model="form"`, add catalog DELETE/history mutation, weaken Q11/M061, add blind mutation retry/idempotency, or alter auth/session cleanup.

## 5. API, authorization, data integrity, and failure handling

- Database: no reads, writes, schema, migration, transaction, lock, concurrency, audit, or history change. Backend remains authoritative.
- API: no method or endpoint change. Exercise create remains `POST /admin/exercises`; partial update remains `PATCH /admin/exercises/{id}`. Template detail remains the existing metadata-only PATCH flow, while status transitions remain on the list flow.
- Authorization: no Frontend authorization change. Existing `auth:api`, effective `ADMIN` role, and Backend resource checks remain required.
- Validation/failure: predictable missing create instructions must produce the existing normalized client 422 before network. Backend validation remains mandatory and authoritative. No network/5xx result is treated as success.
- Transaction/concurrency/idempotency/audit: none is introduced on the client. Existing Backend protections and the Template `content_version` COW contract remain unchanged.

## 6. Implementation order and final gates

1. Fix only the Exercise create/partial `instructions` requiredness expression and add the missing-property direct-service regression.
2. Correct only the Template detail description and add its semantic mounted assertion.
3. Run the two focused suites from `E:\Fitness\FE`:
   - `rtk npm run test -- --run src/services/bai_tap.api.test.js src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.test.js`
   - Require all tests pass with no skip/only, unhandled rejection, Vue warning, or compiler warning.
4. Run the closed-finding preservation matrix covering the unchanged Exercise create/detail suites, Template list/revision/service suites, Package service/detail/list suites, M061 relation selector/detail suites, catalog store, shared dialogs, router/menu/auth, and the remaining FE3 page suites. Record exact file/test counts.
5. Run final-tree gates from `E:\Fitness\FE`: `rtk npm run test`, `rtk npm run lint`, `rtk npm run build`, and `rtk npm ls --depth=0`. Require 100% pass, lint with exactly zero warnings/errors, successful build, and no package/lock delta.
6. Run static checks for catalog DELETE/hard-delete, FE4/payment scope, hard-coded API roots, dependency/framework/font additions, secret/token logs, legacy `handleXxx`, bound handlers outside `xuLy...`, `__file`, skipped/only tests, TODO/FIXME, and `v-model="form"`. Confirm Template detail still has no status action/dialog/state machine and metadata excludes `status`/`days`.
7. Run `rtk git diff --check`; require an empty staged diff. The controller must snapshot the four targets before the writer and package the exact round-4 delta afterward. Exactly four-or-fewer changed product paths must be allow-listed, with zero additions/unexpected paths and all non-target/pre-existing work preserved byte-for-byte.
8. Browser smoke is optional only if an authenticated local Admin session already exists. Otherwise record the same environment limitation and rely on deterministic mounted/service evidence; do not request credentials or create a bypass.

## 7. Exact round-4 product allow-list — 4 paths, zero additions

```text
FE/src/services/bai_tap.api.js
FE/src/services/bai_tap.api.test.js
FE/src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.vue
FE/src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.test.js
```

The fixer may append round-4 implementation and final-state evidence to `E:\Fitness\.fitness-sdd\fe3-all\task-fe3-all-report.md` under the controller workflow; it is not a product target. No new source/test file or other workflow artifact is authorized.

## 8. Exit gate

Round 4 is ready for one fresh Luna Max fixer. Re-review must inspect F-013 and F-015 individually and verify every closed finding remains preserved. PASS requires F-013 closed, F-015 copy corrected, all focused/preservation/full gates green, exact boundary and preservation evidence green, staged diff empty, and no unresolved Critical or Important finding.

Current blocker: none.
