# FE3-ALL consolidated fix plan — round 2

`STATUS: READY_FOR_FIX`  
`TASK_ID: FE3-ALL`  
`FIX_ROUND: 2`  
`OPEN_FINDINGS: F-003, F-005, F-006, F-009, F-010, F-011, F-012, F-013, F-014`  
`MAPPED_FINDING_COUNT: 9`  
`PRESERVE_CLOSED: F-001, F-002, F-004, F-007, F-008, VUE_COMPILER_WARNING`  
`NEEDS_USER_DECISION: NO`  
`FULL_CANONICAL_REREAD: CONDITIONAL_NOT_REQUIRED`  
`ORIGINAL_PRODUCT_BOUNDARY: 51 PATHS`  
`ROUND_2_PRODUCT_ALLOW_LIST: 36 PATHS`  
`ROUND_2_PRODUCT_ADDITIONS: 0`

## 1. Repair outcome and fixed boundary

Run one consolidated Luna Max repair for every Important finding left by `task-fe3-all-fix-review-round-1.md`. FE3-ALL remains one aggregate Admin Catalog task for the authenticated `ADMIN` actor. The use case remains Package/benefit, Equipment, Muscle Group, Exercise/relation, and Workout Template administration against the existing Backend authority.

This round repairs UI state, request normalization/validation, sensitive-status confirmation, configuration-version presentation, documentation, and meaningful test evidence. It does not alter Backend authorization, transactions, concurrency, audit, database state, or any business rule.

The original 51-target product boundary remains the maximum product boundary. Round 2 narrows writes to the 36 paths in section 8 and adds no product path. Do not edit Backend, database, dependency/configuration/lock files, shared dialog primitives, FE4+, Mobile, checkpoint, rules, canonical documents, original plan/brief, snapshots, manifests, controller scripts, or review files. Do not add a package, route, endpoint, persistent stale flag, global idempotency header, or catalog DELETE. Do not commit, push, branch, worktree, reset, restore, checkout, stash, clean, or publish.

## 2. Evidence and contract rulings

- Round 1 independently closed F-001, F-002, F-004, F-007, F-008 and the Vue `v-model` compiler warning. Their source behavior and regression tests must remain intact.
- Full tests, lint, build, dependency resolution, boundary, and preservation gates were green after round 1, but the 12 page files had only one narrow test each. Green totals did not exercise the required state matrix and did not catch F-009 through F-014.
- The round-1 reviewer cited stale paths under `FE/src/services/admin/*` and `FE/src/components/admin/*`. The actual inspected files are `FE/src/services/goi_tap.api.js`, `bai_tap.api.js`, `giao_an_mau.api.js`, and `FE/src/components/danh_muc/bo_sua_quyen_loi_goi_tap.vue`. This is a path correction, not a product or rule ambiguity.
- Backend request source confirms Package `price` is an integer `1..999999999999999`; Exercise create requires non-empty `instructions`; Package/Template `description`, Exercise `image_path`/`video_path`, and Template exercise `notes` are nullable.
- Backend benefit logic requires at least one effective benefit. AI disabled requires limit `0`; AI enabled permits `null` for unlimited or a positive integer. Backend Package responses return `configuration_version`, and authoritative writes/refetches expose its current value.
- Create-time status remains allowed by the request contracts. A transition of an existing record is sensitive and must not ride inside a generic metadata submit without confirmation.
- No new conflict, ambiguity, module-version mismatch, or missing business decision was found. The prior limited canonical escalation recorded in the round-1 plan remains sufficient. No additional canonical or Vue-plan passage was read for this plan.

## 3. Direct mapping of all open findings

### F-003 — complete the mounted page, route, menu, and auth matrix

Root cause: all 12 page tests mount components, but each has only one narrow happy/risk case. The suite does not demonstrate the query states, validation, pending lock, status flows, initial retry, nullable records, configuration refresh, or role/deep-link boundaries that the phase requires.

Repair files:

- All 12 `FE/src/pages/admin/**/**/*.test.js` files listed in section 8.
- `FE/src/router/index.test.js` and `FE/src/router/dieu_huong_admin.test.js`.
- Product source only where another mapped finding requires a behavior correction; do not add test-only production branches.

Required regression matrix:

| Page | Required mounted evidence |
|---|---|
| Package list | Deferred loading; initial error and user retry; empty; populated/navigation; confirmed status success; ordinary failure remains open; all two-step unknown-result branches from F-005. |
| Package create | Valid metadata/benefit payload; client rejection with no POST for invalid benefit set and invalid price; server 422 field display; pending/double-submit lock; successful detail navigation. |
| Package detail | Initial loading/error/retry; valid nullable description; Q01 copy; metadata PATCH excludes benefits/status/authority; benefit PUT; 422; pending lock; authoritative `configuration_version` refresh after metadata and benefit writes. |
| Equipment index/form | Loading/error/retry/empty/data; create and metadata-only edit; immutable code; edit payload excludes status; list status confirmation; ordinary failure; cache-bypassing two-step unknown-result flow. |
| Muscle Group index/form | Loading/error/retry/empty/data including inactive rows; create and metadata-only edit; M061 copy/no-delete; edit payload excludes status; list status confirmation; cache-bypassing two-step unknown-result flow. |
| Exercise list | Loading/error/retry/empty/data/local-no-result; server search/status filter; local difficulty/equipment/muscle filters; create/detail navigation; Q11 `AND` presentation. |
| Exercise create | Options and active-selector behavior; full Q11 arrays; required instructions and other client validation with no POST; 422 field feedback; pending/double-submit lock; success navigation. |
| Exercise detail | Initial loading/error/retry; nullable image/video DTO; inactive M061 exact echo; immutable-relation error; metadata PATCH excludes status; status changes only after shared confirmation; authoritative refresh and pending lock. |
| Template list | Loading/error/retry/empty/data/navigation; confirmed status; ordinary failure; all two-step unknown-result branches; retained shared-dialog keyboard/focus behavior. |
| Template create | Exercise options; full tree including nullable notes; structure/client error with no POST; 422; pending/double-submit lock; success navigation. |
| Template detail | Initial loading/error/retry; nullable description/notes DTO; read-only tree; metadata PATCH excludes `days` and status; 422; revision navigation and active/inactive state. |
| Template revision | Initial loading/error/retry (F-009); nullable description/notes; valid COW request/success; 422; pending/double-submit; retained stale draft/latest-base comparison, explicit reconciliation, and no automatic POST. |

Router/menu/auth tests must table-drive all 12 exact FE3 route names/paths and their Admin meta/breadcrumbs; prove static create and revision paths resolve before `:id`; prove the five catalog menu entries are ordered and every create/detail/revision route activates its correct parent; and prove guest redirect, PT denial, Receptionist denial, and Admin access on list plus deep create/detail/revision routes. Menu tests remain presentation evidence; Backend is still authorization authority.

Acceptance evidence: every page test contains multiple meaningful interaction/state cases appropriate to the page; assertions observe rendered state, API/store calls, payloads, navigation, and disabled/pending behavior. Test count alone, `__file`, shallow existence, or duplicate happy-path render assertions do not satisfy this finding.

### F-005 — make unknown status outcomes local, resolvable, and explicitly retryable

Root cause: four list dialogs use the resource store's sticky `loiMutation.outcomeUnknown` as their state machine. When the first reconciliation GET proves the previous status, the error stays sticky, so a second confirmation performs GET forever instead of an explicit retry. The same sticky error can leak into a new target.

Repair files:

- `goi_tap.index.vue`, `dung_cu.index.vue`, `nhom_co.index.vue`, `giao_an_mau.index.vue` and their tests.
- `danh_muc.store.js` only for meaningful documentation of the generic mutation/error contract; do not move the per-dialog workflow into global state.

Required logic for each of the four pages:

1. Capture target id, previous status, intended status, local normalized error, and local uncertainty phase when opening the dialog. Reset these values on close and before opening another target. Never derive a new dialog's mode from a prior shared store error.
2. First confirm sends exactly one PATCH. Confirmed success is followed by an authoritative list GET; close only when GET returns the intended status. Ordinary 4xx/409/422 failure remains visible and does not claim success.
3. Network/timeout/5xx sets the local phase to `CHUA_XAC_DINH`. The primary action becomes GET-only reconciliation; it must not send PATCH.
4. Reconciliation returning the intended status closes as applied. Returning the previous status clears uncertainty and enters `CO_THE_THU_LAI`, with explicit copy and a distinct user action for retry. Only the next deliberate click may send a second PATCH. Missing resource, a third status, or failed GET stays unresolved and cannot enable mutation retry.
5. Equipment/Muscle Group reconciliation must pass `boQuaCache: true`. Package/Template list GETs are already uncached. A confirmed close and a canceled/new dialog must clear local state even if the store still retains old diagnostic data.

Focused tests required on all four pages: unknown PATCH call count `1` -> GET returns previous -> UI exposes retry while PATCH stays `1` -> explicit retry makes PATCH `2` -> successful authoritative GET closes. Also cover GET-intended closing with one PATCH, failed/missing/other GET staying unresolved, and closing/opening another target without stale uncertainty.

Acceptance: the four two-step sequences are executable and no automatic or reconciliation-triggered mutation retry exists.

### F-006 — complete RULE CODE 09 documentation for important flows

Root cause: handler names are compliant, but several high-risk functions still have one-line labels. They omit purpose, input, process, output, side effect, and governing rule/contract.

Repair files: the status/metadata/benefit/relation/template pages and catalog services/store listed in section 8. Do not edit list-only helpers that are self-evident or add comments that merely repeat code.

Required documentation:

- The generic store mutation/error normalizer: confirmed result, sticky diagnostic behavior, unknown-outcome classification, cache invalidation, and no retry.
- Create/update service functions for Package, Equipment, Muscle Group, Exercise, and Template: request allow-list, nullable fields where applicable, output envelope, no hard delete/blind retry, and governing Q01/Q11/M061/COW contract.
- Package create/detail benefit and configuration-version flows.
- Four local status mutation/reconciliation state machines.
- Equipment/Muscle metadata edit versus separately confirmed status transition.
- Exercise create/relation payload and confirmed status transition.
- Template create, metadata-only PATCH, COW revision, initial retry, stale comparison, and explicit reconciliation.

Each important docblock must concisely state `Muc dich`, `Dau vao`, `Xu ly`, `Ket qua`, `Side effect`, and `Quy tac/Contract` (Vietnamese wording may vary but all meanings must be present). Proof is a targeted reviewer source audit plus `rg` adjacency scan; do not create brittle tests that merely count comment tokens.

### F-009 — repair initial Template revision retry

Root cause: `giao_an_mau.tao_phien_ban.vue` binds `@thu-lai="xuLyTaiLai"` without declaring the handler.

Repair files: the revision page and its test.

Required logic: define a named `xuLyTaiLai` event handler that invokes the guarded initial `tai()` path when no stale draft exists. It must not clear a retained stale draft, call revision POST, or use stale comparison load as initial form population. Pending retry remains disabled through the error primitive's existing contract.

Focused test: reject the first detail GET, assert the error state, click `Thử lại`, resolve the second GET, assert the form/base version renders and GET count is two; POST count remains zero.

### F-010 — accept valid nullable Backend DTO/request fields

Root cause: the three request normalizers' generic string readers reject `null`; detail/revision pages can pass valid nullable DTO values back into PATCH/POST. The service does not validate GET DTOs, so the correction belongs in explicit nullable request-field handling and page hydration tests.

Repair files: Package, Exercise, and Template services/tests; Package detail, Exercise detail, Template create/detail/revision tests and affected page hydration only if needed.

Required logic:

- Add an explicit `nullable` option to string normalization; preserve `null` only for fields the Backend marks nullable.
- Package: `description` accepts `null` on create/update.
- Exercise: `image_path` and `video_path` accept `null`; required strings still reject null/blank.
- Template: `description` and each exercise `notes` accept `null` for create, metadata update, and revision.
- Do not make code/name/goal/level/difficulty/instructions/new_code nullable, and do not silently turn invalid object/array values into strings.

Focused tests: direct service tests assert the exact outgoing payload retains the allowed nulls; mounted detail/revision tests load actual DTO shapes containing null and complete the intended PATCH/POST without a client contract exception. Negative tests assert null remains rejected for required strings.

### F-011 — enforce Package benefit invariants, including unlimited AI

Root cause: disabling AI leaves a positive limit in a disabled input; enabling AI has no explicit unlimited control; the service permits an all-disabled benefit set to reach the network.

Repair files: `bo_sua_quyen_loi_goi_tap.vue` and its test; Package service/test; Package create/detail pages/tests as integration evidence.

Required logic:

- Maintain an explicit UI choice between limited and unlimited AI when AI is enabled. Unlimited emits `fitness_assistant_limit: null`; limited emits a positive integer.
- Turning AI off immediately normalizes the canonical payload value to `0`, clears the unlimited UI flag, and emits the normalized model. Turning limited mode on from null/zero supplies an editable positive default rather than an invalid disabled value.
- Before emitting `luu`, validate that at least one effective benefit is enabled: Gym, AI, Trainer Chat, or direct PT sessions greater than zero. Show an inline accessible error and emit no `luu` for the all-disabled state.
- Service validation independently enforces the same invariant for both Package create and benefit replacement so bypassing the component still makes no network call.
- Preserve Q01 snapshot warning and do not infer entitlement or activation on the client.

Focused tests: positive-limit -> AI off yields `0`; AI on + unlimited yields `null`; switching back to limited yields a positive value; all benefits disabled produces an alert and no `luu`; service create/PUT reject all-disabled without POST/PUT; valid gym-only, unlimited-AI, limited-AI, chat-only, and direct-session-only shapes pass.

### F-012 — require confirmation for every existing-record status transition

Root cause: existing-record status selects are included in generic metadata PATCH payloads on Package detail, Equipment edit, Muscle Group edit, Exercise detail, and Template detail.

Repair files: the five cited pages and their tests; shared `hop_thoai_xac_nhan.vue` remains unchanged.

Required design:

- Package and Template detail metadata forms must display current status read-only and omit `status` from metadata PATCH. Their existing list-page buttons remain the confirmed transition entry points.
- Equipment and Muscle Group combined create/edit forms may choose an initial status only while creating. In edit mode, show status read-only and omit it from the metadata PATCH; row status buttons remain the confirmed transition entry points.
- Exercise create may choose initial status. Exercise detail must separate metadata save from status transition: metadata PATCH omits status; a dedicated status action opens the shared confirmation dialog and only its explicit confirmation sends a status-only PATCH, followed by authoritative detail refresh. A failed or unknown result stays visible and is never blindly retried.
- Do not combine a metadata mutation and a status mutation into one confirmation, and do not add DELETE/deactivation endpoints.

Focused tests: for all five affected existing-record forms, change metadata and prove PATCH excludes status. For Package/Equipment/Muscle/Template, exercise the list entry point and prove no PATCH before shared-dialog confirmation. For Exercise detail, prove no status PATCH before confirmation, exactly one status-only PATCH after confirmation, cancel does nothing, and failure/unknown state remains in the dialog.

### F-013 — align client request validation with authoritative constraints

Root cause: Package price accepts zero/fractions, and Exercise create does not require instructions. Native form constraints mirror those incorrect rules.

Repair files: Package and Exercise services/tests plus Package create/detail and Exercise create/detail pages/tests.

Required logic:

- Package service accepts price only as a safe integer in Backend range `1..999999999999999`; invalid zero, negative, fractional, NaN/infinite, and above-max values throw a normalized 422 before Axios. Set Package price inputs to `min="1"`, `step="1"`, and the Backend max. Keep duration an integer `1..65535` and align relevant native max where touched.
- Exercise create requires non-blank `instructions`; update the service required-field set, visible field metadata, native `required`, and inline field error mapping. Keep update semantics `sometimes|required`: when the detail page submits instructions, blank/null is invalid. Do not weaken Q11 relation validation.
- Preserve the existing authority-field allow-lists and status enums.

Focused tests: Package service and create/detail pages reject `0` and `1.5` with no POST/PATCH; valid integer `1` reaches the API. Exercise service/create page rejects missing/blank instructions with no POST; valid instructions are present in the exact payload. Detail page renders instructions required and does not issue an invalid PATCH.

### F-014 — display and refresh authoritative Package configuration version

Root cause: Package detail never renders `configuration_version`, so no UI evidence proves the server value is refreshed after metadata or benefit changes.

Repair files: Package detail page and test.

Required logic: render the current Backend value with a clear label such as `Phiên bản cấu hình`; never calculate or increment it locally. After metadata PATCH success and after benefit PUT success, complete the existing authoritative detail GET and display the returned version. If refresh fails, keep the last displayed version, surface the read error, and do not claim a newer value.

Focused test: initial GET returns version 1; metadata update plus GET returns version 2; benefit update plus GET returns version 3. Assert the visible value at each step and assert the UI never fabricates `+1` from mutation intent. Cover a failed refresh leaving the last confirmed version visible with an error.

## 4. Closed-finding preservation gates

- **F-001:** rerun selector/detail tests proving inactive M061 id/role exact echo and blocked removal/role change. Do not edit the selector unless a new failing regression proves it necessary.
- **F-002:** revision tests must still prove stale draft separation, latest-base comparison, explicit version adoption, and no auto-POST.
- **F-004:** full lint must report exactly zero warnings and zero errors; do not change lint config or ignore rules.
- **F-007:** UTF-8 visible labels remain Vietnamese with diacritics; technical identifiers/status/error codes remain unchanged.
- **F-008:** Template list and Exercise status flows must use the existing shared accessible confirmation primitive; retain role/name/description, initial focus, Tab/Shift+Tab containment, Escape, pending lock, and focus return tests.
- **Compiler warning:** full focused/test/build output contains no const-reactive `v-model` warning; do not restore `v-model="form"`.

## 5. Database, API, authorization, transaction, failure and audit boundaries

- Database reads/writes remain Backend-owned: `goi_tap`, `quyen_loi_goi_tap`, `dung_cu`, `nhom_co`, `bai_tap` plus relation tables, and Workout Template tree/version tables. Round 2 changes no schema or persistence code.
- Exact API endpoints/methods remain unchanged. Metadata/status payload separation is a Frontend allow-list decision within the existing PATCH contracts.
- Router guards/menu are presentation. Every request still relies on `auth:api`, `role:ADMIN`, branch/resource scope, and Backend validation.
- Frontend creates no transaction, audit, lock, or idempotency key. It disables duplicate submits, sends one mutation per explicit action, and uses GET-only reconciliation for unknown outcomes.
- 401/403/404/409/422/network/5xx remain normalized and secret-safe. Current-token/late-old-token cleanup behavior remains untouched.

## 6. Implementation order

1. Repair the three nullable request normalizers and exact Package/Exercise validation; add focused service tests.
2. Repair the Package benefit editor/service invariants and integration tests.
3. Separate sensitive status transitions from metadata forms; implement the shared Exercise detail confirmation.
4. Replace the four sticky unknown-outcome flows with local state machines and their two-step regressions.
5. Add the initial revision retry and Package configuration-version display/refresh.
6. Complete meaningful docblocks on every important function touched.
7. Expand all 12 page tests plus route/menu/auth matrices; rerun preserved closed-finding tests.
8. Run final focused/full quality, static, boundary, and preservation gates from the final tree.

## 7. Required final verification

Run from `E:\Fitness\FE` and record exact final counts/results in the appended task report:

1. Focused Vitest for three service files, benefit component, four unknown-outcome pages, five status-boundary pages, revision initial retry/stale flow, Package configuration refresh, all 12 page files, router/menu/auth, and preserved M061/accessibility tests.
2. Full `rtk npm run test`: all files/tests pass, no skips/only, unhandled rejection, Vue warning, or compiler warning.
3. Full `rtk npm run lint`: literal 0 warnings and 0 errors.
4. `rtk npm run build` after the last change: PASS.
5. `rtk npm ls --depth=0`: PASS with no dependency or lockfile delta.
6. Static scans: no catalog DELETE/hard-delete/destructive relation call; no FE4 scope; no hard-coded API root; no new dependency/framework/font; no secret/token logging; no `handleXxx`; no bound handler outside `xuLy...`; no `__file`, `.skip`, `.only`, TODO/FIXME, `v-model="form"`, or status inside generic existing-record metadata payloads.
7. `rtk git diff --check`; staged diff empty; compare the round-2 before/current manifests and exact delta; every changed product path must be in section 8 and every pre-existing non-task path/hunk must remain intact.
8. Browser smoke only if an existing authenticated local app is available. Otherwise retain the already approved deterministic mounted-page/router/Pinia/service-boundary substitute and record the environment limitation honestly; do not request credentials or create an auth/API bypass.

## 8. Exact round-2 product allow-list — 36 paths, zero additions

Every path below is already one of the original 51 targets. Touch only the files required by the mapped findings.

```text
FE/src/router/index.test.js
FE/src/router/dieu_huong_admin.test.js
FE/src/stores/danh_muc.store.js
FE/src/services/goi_tap.api.js
FE/src/services/goi_tap.api.test.js
FE/src/services/dung_cu.api.js
FE/src/services/nhom_co.api.js
FE/src/services/bai_tap.api.js
FE/src/services/bai_tap.api.test.js
FE/src/services/giao_an_mau.api.js
FE/src/services/giao_an_mau.api.test.js
FE/src/components/danh_muc/bo_sua_quyen_loi_goi_tap.vue
FE/src/components/danh_muc/bo_sua_quyen_loi_goi_tap.test.js
FE/src/pages/admin/goi_tap/goi_tap.index.vue
FE/src/pages/admin/goi_tap/goi_tap.index.test.js
FE/src/pages/admin/goi_tap/goi_tap.tao_moi.vue
FE/src/pages/admin/goi_tap/goi_tap.tao_moi.test.js
FE/src/pages/admin/goi_tap/goi_tap.chi_tiet.vue
FE/src/pages/admin/goi_tap/goi_tap.chi_tiet.test.js
FE/src/pages/admin/dung_cu/dung_cu.index.vue
FE/src/pages/admin/dung_cu/dung_cu.index.test.js
FE/src/pages/admin/nhom_co/nhom_co.index.vue
FE/src/pages/admin/nhom_co/nhom_co.index.test.js
FE/src/pages/admin/bai_tap/bai_tap.index.test.js
FE/src/pages/admin/bai_tap/bai_tap.tao_moi.vue
FE/src/pages/admin/bai_tap/bai_tap.tao_moi.test.js
FE/src/pages/admin/bai_tap/bai_tap.chi_tiet.vue
FE/src/pages/admin/bai_tap/bai_tap.chi_tiet.test.js
FE/src/pages/admin/giao_an_mau/giao_an_mau.index.vue
FE/src/pages/admin/giao_an_mau/giao_an_mau.index.test.js
FE/src/pages/admin/giao_an_mau/giao_an_mau.tao_moi.vue
FE/src/pages/admin/giao_an_mau/giao_an_mau.tao_moi.test.js
FE/src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.vue
FE/src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.test.js
FE/src/pages/admin/giao_an_mau/giao_an_mau.tao_phien_ban.vue
FE/src/pages/admin/giao_an_mau/giao_an_mau.tao_phien_ban.test.js
```

The fixer may append round-2 implementation and final-state evidence to `E:\Fitness\.fitness-sdd\fe3-all\task-fe3-all-report.md`; that workflow report is not a product target. No other workflow artifact is writable by the fixer.

## 9. Exit gate

Round 2 is ready for one Luna Max fixer. Re-review must map all nine IDs individually. PASS requires F-003, F-005, F-006, and F-009 through F-014 closed; all closed round-1 findings preserved; final tests/lint/build/dependency/static checks green; exact boundary/preservation evidence green; and no unresolved Important/Critical issue.

Current blocker: none.
