# FE3-ALL Fix Review — Round 3

VERDICT: FAIL

SPEC: FAIL

QUALITY: FAIL

## Review result

- **REVIEW_STATUS: DONE_WITH_CONCERNS**
- **FE3_TASK_GATE: FAIL**
- **FINAL_REVIEW_PERMITTED: NO**

Round 3 closes F-003, F-006, F-011 and F-012, and it repairs the partial-update branch of F-013. It also preserves the findings closed in earlier rounds. The Exercise request builder, however, regresses the create contract: omitting `instructions` now produces no client validation error and permits the public create mutation to reach Axios. F-013 therefore remains open at Important severity. One minor user-facing copy inconsistency remains on Template detail.

## Context and review boundary

The review followed `E:\Fitness\AGENTS.md`, the mandatory Fitness SDD reviewer contract and the layered-context protocol. It read the complete round-2 review, round-3 plan/brief/review package, task report, original FE3 plan/brief/entry revalidation/implementation brief, prior task and repair reviews/packages/plans, `PROJECT_CORE.md`, `RULE_INDEX.md`, `FE3_CONTEXT.md`, the selected frontend/workout/membership/auth/security/coding/testing modules, and `BE\AGENTS.md`. `BE\app\Http\Requests\Admin\Catalog\UpdateExerciseRequest.php` was inspected read-only for the authoritative `sometimes|required|string` partial contract.

The review inspected the exact 14-product-file round-3 delta, before/current manifests, controller gate, preservation manifest, final source and mounted tests. No full canonical or Vue-plan reread was triggered because the selected modules, prior canonical rulings and current request contract were sufficient and no new ambiguity or version conflict appeared. Backend source remained read-only. No product file or existing report was edited.

## Finding closure matrix

| ID | Status | Independent evidence |
|---|---|---|
| F-001 | **CLOSED — preserved** | Inactive M061 controls remain locked and the mounted selector/detail coverage retains the exact id/role echo. Preservation suite passes. |
| F-002 | **CLOSED — preserved** | Template stale recovery keeps the draft separate, adopts only the explicitly reconciled version and performs no automatic second POST. |
| F-003 | **CLOSED** | The three create suites now mount server 422 responses. Template detail covers deferred load, error/retry, 422, pending lock and authoritative refresh. Template revision covers nullable full COW success/payload/navigation, ordinary 422 and double-submit, while retaining retry/stale coverage. |
| F-004 | **CLOSED — preserved** | Full ESLint exits 0 with no diagnostics. |
| F-005 | **CLOSED — preserved** | Package, Equipment, Muscle Group and Template list suites retain local two-step unknown-outcome flows, GET-only reconciliation, deliberate retry and required cache bypass. |
| F-006 | **CLOSED** | The comments immediately above `taiNenGiaoAnMau` and `xuLyGiaoAnMauDaCu` separately cover purpose, input, processing, result, side effects and the COW/version contract. The latter names exact `409 + WORKOUT_TEMPLATE_STALE`, the returned shape and no request/mutation/retry behavior. |
| F-007 | **CLOSED — preserved** | Reviewed visible copy remains UTF-8 Vietnamese with diacritics; technical keys/status/error codes remain canonical. |
| F-008 | **CLOSED — preserved** | Shared dialog tests retain accessible naming/description, focus containment and return, Escape handling and pending lock. |
| Vue compiler warning | **CLOSED — preserved** | No `v-model="form"` residue or compiler warning was observed in focused, full test or build output. |
| F-009 | **CLOSED — preserved** | Initial Template revision retry performs exactly the guarded GET path and no revision POST. |
| F-010 | **CLOSED — preserved** | Package/Template descriptions, Exercise image/video paths and Template exercise notes retain valid nulls in mounted and service payload evidence. |
| F-011 | **CLOSED** | Public `taoGoiTap` and `thayTheQuyenLoiGoiTap` calls independently reject the exact all-disabled shape before POST/PUT. Five public positive shapes prove exact nested create and benefit PUT payloads. |
| F-012 | **CLOSED** | Package and Template detail action controls, dialogs and local status state machines are absent; status remains disabled/read-only and metadata submissions omit `status`. Unchanged list suites remain the operational transition entry points. |
| F-013 | **OPEN — Important** | Present partial null/blank/object instructions and the mounted blank-detail path are fixed, but create with the field omitted is no longer required before POST. See Finding 1. |
| F-014 | **CLOSED — preserved** | Package detail retains authoritative `configuration_version` display/refetch `1 -> 2 -> 3` and last-confirmed value on refresh failure. |

## Findings

### 1. F-013 — Important — Exercise create no longer requires an omitted `instructions` field

- **File:** `E:\Fitness\FE\src\services\bai_tap.api.js`
- **Line:** 73
- **Rule:** Round-3 brief F-013; round-2 F-013 closed behavior; Backend `CreateExerciseRequest` create contract and `UpdateExerciseRequest.php:21` partial `sometimes|required|string`; RULE CODE 13/19/20.
- **Evidence:** `taoPayloadBaiTap` now marks `instructions` required only when the property is present with a non-`undefined` value. Its non-partial required list contains only `code`, `name` and `difficulty`. Calling `taoBaiTap` with otherwise valid create data and no `instructions` therefore makes `layChuoi(undefined, ..., { required: false })` return `undefined`, omits the field from the body and proceeds to `ketNoiApi.post`. The round-3 delta confirms this is a regression from the before snapshot, whose non-partial required list included `instructions`. `bai_tap.api.test.js:56` covers create null/blank/object and partial null/blank/object, but it has no create case where the property is absent, so all 674 tests remain green while the required public-service boundary is unprotected.
- **Impact:** A direct public-service caller can send a predictably invalid Exercise create request and receive a Backend 422 instead of being rejected before network. This violates the explicit round-3 acceptance condition and reopens F-013.
- **Required fix:** Make `instructions` required for every create and required for a partial update only when the property is present under the existing undefined/omission convention. Add a direct service regression that removes the property entirely from an otherwise valid create payload and asserts normalized 422 plus zero POST. Retain the current partial omission, valid partial string, nullable image/video, Q11/M061 and page no-PATCH behavior.

### 2. F-015 — Minor — Template detail still tells users that its PATCH edits status

- **File:** `E:\Fitness\FE\src\pages\admin\giao_an_mau\giao_an_mau.chi_tiet.vue`
- **Line:** 70
- **Rule:** Round-3 F-012 list-only Package/Template status binding; clear and accurate visible UI copy.
- **Evidence:** The detail status action and state machine were correctly removed, but the page description still says `PATCH chỉ sửa metadata/status`. On this page status is disabled and the actual metadata payload excludes it.
- **Impact:** The visible explanation contradicts the implemented list-only transition workflow.
- **Required fix:** Change the description to state that this detail PATCH edits metadata only and that status transitions are performed from the Template list.

## Gate and boundary evidence

| Check | Independent result |
|---|---|
| Round-3 focused suite | PASS; 10 files, 59 tests |
| Closed-finding preservation suite | PASS; 10 files, 73 tests |
| Full `rtk npm run test` | PASS; 58 files, 674 tests; no skip/only, unhandled rejection, Vue warning or compiler warning observed |
| `rtk npm run lint` | PASS; exit 0, no diagnostics |
| `rtk npm run build` | PASS; Vite transformed 169 modules |
| `rtk npm ls --depth=0` | PASS; dependency tree resolves |
| `rtk git diff --check` | PASS |
| Staged diff | PASS; empty |
| Package/lock delta | PASS; no `FE/package.json` or `FE/package-lock.json` diff |
| Current manifest hash audit | PASS; 15/15 rows match the final tree (14 product targets plus the authorized task report) |
| Preservation hash audit | PASS; 696/696 non-target rows unchanged, exactly 14 authorized product rows changed, 0 missing/mismatched rows |
| Controller boundary gate | PASS; 14/14 authorized product targets, zero unexpected paths, 710 preservation rows checked, report changed as authorized |
| Static scans | PASS for executable catalog DELETE/hard-delete calls, FE4/payment scope, forbidden dependencies, hard-coded API roots in the round-3 target set, secret/token logs, legacy `handleXxx`, `__file`, skip/only, TODO/FIXME, `v-model="form"`, and Package/Template detail status-action/dialog/state residue |

No authenticated local Admin application session was available, so browser smoke was not run. The approved mounted Vue/Pinia/router/service-boundary evidence was used. This environment limitation is not the cause of the failing verdict; the open service-contract defect is directly visible in the final source and round-3 delta.

## Boundary conclusion and residual risks

The exact round-3 file boundary and all pre-existing non-target work are preserved. The four list reconciliation machines, M061 relation behavior, COW/stale handling, nullable fields, shared-dialog accessibility, current-token auth behavior, Q01/Q11 behavior and authoritative Package version flow show no round-3 regression.

Residual risks:

- Exercise create omission remains untested and currently reaches the network.
- Browser-level responsive/session evidence remains unavailable; mounted deterministic coverage is the approved substitute for this environment.

`FE3_TASK_GATE: FAIL`

`FINAL_REVIEW_PERMITTED: NO`
