# FE3-ALL fix brief — round 4

`STATUS: READY_FOR_FIX`  
`TASK_ID: FE3-ALL`  
`FIX_ROUND: 4`  
`MAPPED_FINDINGS: F-013, F-015`  
`PRESERVE_CLOSED: F-001, F-002, F-003, F-004, F-005, F-006, F-007, F-008, F-009, F-010, F-011, F-012, F-014, VUE_COMPILER_WARNING`  
`PRODUCT_ALLOW_LIST_COUNT: 4`  
`PRODUCT_ADDITION_COUNT: 0`  
`NEEDS_USER_DECISION: NO`  
`EXPECTED_REPORT: E:\Fitness\.fitness-sdd\fe3-all\task-fe3-all-report.md`

Implement one consolidated repair for exactly F-013 Important and F-015 Minor. Do not split the repair, dismiss either finding, reopen closed behavior, or edit outside the exact boundary below.

## Required context

Read completely before editing:

- `E:\Fitness\AGENTS.md`; discover any deeper `AGENTS.md` governing inspected/edited paths. No nested FE instruction file currently exists. Read `E:\Fitness\BE\AGENTS.md` before the two read-only Backend request files.
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
- `E:\Fitness\.fitness-sdd\fe3-all\task-fe3-all-fix-plan-round-3.md`
- `E:\Fitness\.fitness-sdd\fe3-all\task-fe3-all-fix-brief-round-3.md`
- `E:\Fitness\.fitness-sdd\fe3-all\task-fe3-all-report.md`
- `E:\Fitness\.fitness-sdd\fe3-all\task-fe3-all-fix-review-package-round-3.md`
- `E:\Fitness\.fitness-sdd\fe3-all\task-fe3-all-fix-review-round-3.md`
- `E:\Fitness\.fitness-sdd\fe3-all\task-fe3-all-fix-plan-round-4.md` and this brief.
- Controller-provided original baseline/target preservation artifacts and the round-4 before snapshot, target list, current manifest, exact delta, unexpected-path check, and preservation evidence.
- Read-only request authority: `E:\Fitness\BE\app\Http\Requests\Admin\Catalog\CreateExerciseRequest.php` and `E:\Fitness\BE\app\Http\Requests\Admin\Catalog\UpdateExerciseRequest.php`.

Binding IDs: Q11, Q12, M061, RULE GYM 11–16, RULE CODE 05–11, 13, 17–20. `E:\Fitness\PROJECT_RULES.md` and `E:\Fitness\docs\VUE_WEB_IMPLEMENTATION_PLAN_V2.md` remain conditional authorities. No full reread is required. Consult only a precise passage if a new conflict, ambiguity, missing rule, or version mismatch appears and record the trigger and resolution.

## Required repair by finding

### F-013 — Exercise create must reject missing instructions before network

In `FE/src/services/bai_tap.api.js`, correct only the requiredness calculation inside `taoPayloadBaiTap`:

- Create (`partial === false`): `instructions` is always required, matching Backend `CreateExerciseRequest` `required|string`.
- Partial update: omission remains legal, matching `UpdateExerciseRequest` `sometimes|required|string`; when `instructions` is present with a concrete value, it remains required and must be a non-blank string.
- Preserve the current convention that an omitted/`undefined` optional partial field is not emitted. Do not turn `name`, `difficulty`, or any other partial field into a global requirement.
- Missing create, present null, blank, object, array, or other non-string instructions must produce the existing normalized 422 shape before Axios. Do not change error codes/messages unless required to retain the existing `fieldErrors.instructions` contract.
- Preserve exact endpoints and all existing nullable media, status, authority allow-list, Q11 equipment, M061 muscle relation, trimming, max-length, and no-blind-retry behavior.

In `FE/src/services/bai_tap.api.test.js`:

- Add a dedicated test that constructs an otherwise valid create payload with the `instructions` property absent, calls `taoBaiTap`, asserts `{ httpStatus: 422, code: 'INVALID_EXERCISE_REQUEST' }` plus an `instructions` field error, and asserts `post` was never called.
- Keep the missing-property case independent enough that a prior mock call cannot make the zero-network assertion ambiguous.
- Retain the existing create null/blank/object and partial null/blank/object zero-network cases.
- Retain the exact partial omission PATCH `{ name: 'Moi' }`, the valid string instructions PATCH, nullable image/video, relation validation, and Q11 AND evidence.

Acceptance evidence: missing create instructions is rejected before `ketNoiApi.post`; valid creates still POST; partial omission still PATCHes without `instructions`; a valid present partial instruction is trimmed and PATCHed.

### F-015 — Template detail copy must describe metadata-only PATCH

In `FE/src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.vue`:

- Change only the `TieuDeTrang` `mo-ta` copy currently claiming `PATCH chỉ sửa metadata/status`.
- Use clear Vietnamese copy stating all three facts: this detail PATCH edits metadata only; status transitions are performed from the Workout Template list; `days` content changes require a new revision.
- Keep status disabled/read-only. Keep the metadata request without `status` and `days`. Do not change Template list actions, page state, COW/version behavior, tree, navigation, error handling, or styling.

In `FE/src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.test.js`:

- Extend the existing `status detail chỉ hiển thị read-only và metadata không gửi status` mounted case, or add one equally focused semantic case, to assert the rendered description states metadata-only PATCH and status transition from the Template list.
- Retain its existing assertions that the status control is disabled, no detail status action/dialog exists, and metadata submit excludes status. Retain all loading/retry/422/pending/authoritative-refresh/nullable/tree/revision evidence.

Acceptance evidence: the inaccurate `metadata/status` phrase is absent, the intended list-only wording is visible, and no runtime status behavior changes.

## Closed behavior that must remain unchanged

- F-001 exact inactive M061 relation echo and locked controls.
- F-002/F-009 Template stale draft/latest base separation, explicit reconciliation, GET retry, and no automatic revision POST.
- F-003 mounted create/detail/revision loading, retry, 422, pending, authoritative refresh, nullable payload, and navigation matrix.
- F-004 lint with zero warnings/errors.
- F-005 four catalog list unknown-outcome flows and GET-only reconciliation.
- F-006 the two completed Template service docblocks.
- F-007 UTF-8 Vietnamese visible copy and canonical technical keys/status/error codes.
- F-008 shared-dialog accessibility, focus, Escape, and pending lock.
- F-010 nullable Package/Template description, Exercise image/video, and Template notes.
- F-011 public Package benefit boundary validation and five positive shapes.
- F-012 list-only Package/Template status transition binding; no detail status action/dialog/state machine.
- F-014 authoritative Package configuration version behavior.
- No `v-model="form"` compiler warning, catalog DELETE/history destruction, M061/Q11 weakening, blind mutation retry/idempotency invention, auth cleanup change, or FE4 scope.

## Exact product allow-list

Only these four existing paths, all inside the original 51-target FE3 boundary, may be modified:

```text
FE/src/services/bai_tap.api.js
FE/src/services/bai_tap.api.test.js
FE/src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.vue
FE/src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.test.js
```

Append round-4 implementation and exact final-state evidence to `E:\Fitness\.fitness-sdd\fe3-all\task-fe3-all-report.md`; it is not a product target. Do not create any source/test file or modify another workflow artifact.

## API, authorization, transaction, concurrency, idempotency, and audit

- No Backend or Database edit. No schema, transaction, lock, concurrency, audit, history, or lifecycle change.
- No endpoint/method change: Exercise create remains `POST /admin/exercises`; partial update remains `PATCH /admin/exercises/{id}`. Template detail remains metadata-only; list status mutation remains unchanged.
- Backend remains authoritative for `ADMIN` authorization and validation. The Frontend pre-network 422 is an early deterministic guard and does not replace server validation.
- Do not add client transactions, idempotency headers, retries, audit records, or authority fields.

## Required verification

After the final product change, run from `E:\Fitness\FE` and record exact observed results:

1. Focused mapped suites:
   - `rtk npm run test -- --run src/services/bai_tap.api.test.js src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.test.js`
   - Require all tests pass; no skipped/only tests, unhandled rejection, Vue warning, or compiler warning.
2. Closed-finding preservation matrix: rerun the unchanged Exercise create/detail suites; Template service/list/detail/revision suites; Package service/list/detail suites; Q11/M061 relation selector/detail; catalog store; shared dialogs; router/menu/auth; and all remaining FE3 page suites. Record exact files/tests and require 100% pass.
3. Full final-tree gates: `rtk npm run test`, `rtk npm run lint`, `rtk npm run build`, and `rtk npm ls --depth=0`. Record exact counts/results; lint must have zero warnings and zero errors; package/lock delta must be empty.
4. Static scans: no executable catalog DELETE/hard-delete, FE4/payment scope, hard-coded API root, forbidden dependency/framework/font, secret/token logging, `handleXxx`, native bound handler outside `xuLy...`, `__file`, `.skip`, `.only`, TODO/FIXME, or `v-model="form"`. Confirm Template detail has no status action/dialog/state-machine residue and its metadata payload excludes `status`/`days`.
5. Run `rtk git diff --check`; staged diff must be empty. Compare the controller round-4 before/current manifests and exact delta: every changed product path is one of the four allow-listed paths, zero product additions/unexpected paths, and all pre-existing non-target work/hunks remain byte-for-byte preserved. Unused allow-listed paths may remain unchanged.

Attempt browser smoke only if an authenticated local Admin application already exists. Otherwise record the limitation and use the approved mounted/service evidence. Do not request credentials or add an auth/API bypass.

Return `DONE` only when F-013 and F-015 are both closed and every preservation/final gate is green. Otherwise return `DONE_WITH_CONCERNS` or `BLOCKED` with exact unresolved evidence.
