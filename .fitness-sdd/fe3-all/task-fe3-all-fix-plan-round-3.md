# FE3-ALL consolidated fix plan — round 3

`STATUS: READY_FOR_FIX`
`TASK_ID: FE3-ALL`
`FIX_ROUND: 3`
`OPEN_FINDINGS: F-003, F-006, F-011, F-012, F-013`
`MAPPED_FINDING_COUNT: 5`
`PRESERVE_CLOSED: F-001, F-002, F-004, F-005, F-007, F-008, F-009, F-010, F-014, VUE_COMPILER_WARNING`
`NEEDS_USER_DECISION: NO`
`FULL_CANONICAL_REREAD: CONDITIONAL_NOT_REQUIRED`
`ORIGINAL_PRODUCT_BOUNDARY: 51 PATHS`
`ROUND_3_PRODUCT_ALLOW_LIST: 14 PATHS`
`ROUND_3_PRODUCT_ADDITIONS: 0`

## 1. Repair outcome and fixed boundary

Run one consolidated Luna Max repair for exactly the five Important findings left open by `task-fe3-all-fix-review-round-2.md`. FE3-ALL remains one aggregate Admin Catalog task for an authenticated `ADMIN`. The repair completes the missing mounted behavior evidence, two exact Template service contracts, Package benefit service-boundary coverage, the approved list-only Package/Template status binding, and Exercise partial-instructions validation.

The Backend remains the authority for authorization, validation, persistence, transactions, concurrency, audit, lifecycle, configuration/content versions, and history. Round 3 changes no database or Backend behavior and introduces no endpoint, route, screen, dependency, configuration, shared primitive, or business rule.

The original 51 product paths remain the absolute maximum boundary. Round 3 narrows writes to the 14 paths in section 8, all already present in that original boundary. Do not edit Backend, database, FE4+, Mobile, checkpoint, canonical/rule/context documents, dependencies, package/lock files, router/menu/auth/store/shared components, list pages, original reports/reviews, controller scripts, snapshots, or manifests. Do not commit, push, branch, create a worktree, reset, restore, checkout, stash, clean, publish, or add generated artifacts.

## 2. Evidence and rulings

- Round 2 independently closed F-005, F-009, F-010, and F-014 while preserving F-001, F-002, F-004, F-007, F-008 and the Vue compiler-warning repair. Those behaviors and their regression tests remain binding.
- Round-2 quality and boundary gates were green: 58 files/655 tests, lint with zero warnings/errors, build, dependency resolution, diff check, 36/36 authorized hashes, 674/674 non-target preservation rows, zero unexpected paths, and empty staged diff. These results do not close the five remaining behavioral/contract findings.
- F-003 is limited to the exact omissions identified by the round-2 reviewer: server 422 behavior in the three dedicated create suites; initial load/retry, metadata 422, pending lock, and authoritative refresh in Template detail; and valid nullable copy-on-write success, exact payload/navigation, ordinary 422, and pending/double-submit in Template revision. Existing Equipment/Muscle Group combined-page suites are not reopened because the review did not identify a new actionable gap there.
- F-006 concerns only `taiNenGiaoAnMau` and `xuLyGiaoAnMauDaCu` in `giao_an_mau.api.js`; other audited docblocks passed and must not be rewritten.
- F-011 runtime benefit normalization already passes. The missing work is public service-boundary proof, not a redesign of the benefit editor or service implementation.
- F-012 is resolved by removing Package/Template detail transition entry points and their duplicated dialog/state machines. Their existing list-page actions remain the sole confirmed transition entry points. Exercise detail keeps its separately approved confirmation flow.
- The read-only authoritative `UpdateExerciseRequest` uses `sometimes|required|string` for `instructions`. Omission is legal in a partial PATCH; a present null, blank, or non-string value is not. This resolves F-013 without changing the Backend contract.
- No new conflict, business ambiguity, module mismatch, or missing rule was found. The prior limited canonical escalation remains sufficient; no canonical or Vue-plan passage was reread for this round.

## 3. Direct mapping of every open finding

### F-003 — finish the exact mounted page matrix

Root cause: the round-2 suites prove many FE3 states, but five test files still omit explicitly required behavior. The production paths already expose the required create error, Template detail load/save, and Template revision states; production changes are authorized only where another mapped finding requires them.

Repair tests:

- `goi_tap.tao_moi.test.js`, `bai_tap.tao_moi.test.js`, and `giao_an_mau.tao_moi.test.js`: for each valid form, reject the create call with a normalized server 422 containing a field error. Assert exactly one POST-facing service call, the field/global error is rendered and associated with the affected control where supported, the draft remains, navigation is not called, and the submit returns from pending. Keep the existing client-validation, exact-payload, success-navigation, and double-submit cases.
- `giao_an_mau.chi_tiet.test.js`: add a deferred initial GET case that renders loading before hydration; an initial failure followed by a user-triggered retry that performs exactly two GETs and no PATCH; a metadata 422 case that renders the field/global error, keeps the edited form, performs one PATCH, and performs no success refresh; a deferred metadata PATCH case proving one mutation under two submit attempts and a disabled/pending submit; and a successful metadata case proving an exact payload without `days`/`status`, followed by one authoritative GET whose returned metadata replaces the form. Retain nullable `description`/`notes`, read-only tree, and revision navigation evidence.
- `giao_an_mau.tao_phien_ban.test.js`: add a valid COW success case whose initial DTO contains `description: null` and an exercise with `notes: null`. Assert the exact `taoPhienBanGiaoAnMau(7, payload)` body, including `new_code`, `expected_content_version`, metadata/status, complete ordered `days`, and preserved nulls; then assert navigation to `adminChiTietGiaoAnMau` with `new_template_id`. Add an ordinary normalized 422 case proving the field/global error, preserved draft, one POST-facing call, and no navigation. Add a deferred double-submit case proving one call, disabled pending control, and no early navigation. Retain initial GET retry and stale-draft/latest-base/explicit-reconciliation/no-auto-POST tests.

Acceptance: these are mounted interaction tests. Test names, file existence, helper-only calls, and duplicated render assertions do not count. No product branch may be added solely to make a test pass.

### F-006 — complete two exact Template service semantic contracts

Root cause: `taiNenGiaoAnMau` and `xuLyGiaoAnMauDaCu` have short comments that omit part of the six-part RULE CODE 09 contract.

Repair file: `FE/src/services/giao_an_mau.api.js` only.

- Replace the comment immediately above `taiNenGiaoAnMau` with a concise docblock that distinctly states purpose, input, processing, result, side effects, and the governing COW/Backend-authoritative `content_version` contract. It must explain that the function delegates to the validated detail GET and does not mutate/rebase an editor draft.
- Replace the comment immediately above `xuLyGiaoAnMauDaCu` with a concise docblock that distinctly states purpose, normalized-error input, exact `409 + WORKOUT_TEMPLATE_STALE` classification process, returned `{ laXungDot, maLoi, giuBanNhap }` shape, absence of network/state/retry side effects, and the rule that the caller preserves the draft and reconciles `expected_content_version` explicitly.

Do not edit other comments, change runtime behavior, or create brittle comment-count/token tests.

### F-011 — prove Package benefit invariants at public service boundaries

Root cause: the current service test exercises the payload helper, so its zero-network assertion does not prove that either public mutation rejects an all-disabled benefit set. It also omits four of the five required positive shapes.

Repair file: `FE/src/services/goi_tap.api.test.js` only.

- Define the exact all-disabled shape `{ gym_access: false, fitness_assistant: false, fitness_assistant_limit: 0, trainer_chat: false, direct_trainer_sessions: 0 }`.
- Call `taoGoiTap` with otherwise valid package metadata and that shape; assert normalized 422 and zero POST.
- Call `thayTheQuyenLoiGoiTap` with a valid id and that shape; assert normalized 422 and zero PUT.
- Table-drive these five valid shapes through public service mutations, not only `taoPayloadQuyenLoiGoiTap`: Gym-only `(true,false,0,false,0)`, Chat-only `(false,false,0,true,0)`, Direct-session-only `(false,false,0,false,1)`, Unlimited-AI `(false,true,null,false,0)`, and Limited-AI `(false,true,25,false,0)`. For every row, assert the exact nested create benefits and exact benefit PUT payload, including `0`, `null`, and the positive limit without coercing one mode into another.
- Retain the existing Q01 authority-field exclusion, endpoints, price bounds, nullable description, and AI-invalid-limit coverage.

Acceptance: both public mutation entry points independently reject the disabled shape before Axios, and all five valid shapes reach the intended POST/PUT boundary with exact payloads.

### F-012 — restore list-only Package/Template status transitions

Root cause: Package and Template detail pages added local status buttons and duplicated transition/reconciliation dialog state machines, although the approved binding keeps current status read-only on detail and uses existing list actions as the confirmed transition entry points.

Repair files:

- `FE/src/pages/admin/goi_tap/goi_tap.chi_tiet.vue`
- `FE/src/pages/admin/goi_tap/goi_tap.chi_tiet.test.js`
- `FE/src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.vue`
- `FE/src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.test.js`

Required removal on both detail pages:

- Remove the `HopThoaiXacNhan` import, detail-only status target/intention/phase/error refs, open/reset/reconcile/confirm handlers and their status-transition docblocks, the detail status action button/test id, and the dialog markup.
- Keep the current status select/display disabled/read-only. Keep metadata PATCH exclusion of `status`; Package also keeps benefits/Q01/configuration-version flows, and Template keeps the read-only tree plus revision navigation.
- Do not move or duplicate status logic. Do not edit Package/Template list pages or the shared dialog primitive.

Test replacement:

- Replace each detail status-transition test with a mounted assertion that the disabled status display remains, the detail transition test id/control and confirmation dialog do not exist, and a metadata submit excludes `status`.
- Preserve Package `configuration_version` `1 -> 2 -> 3` and refresh-failure tests. Preserve Template nullable/tree/metadata/revision tests and extend them for F-003.
- Rerun the unchanged Package/Template list suites to prove their confirmed status and unknown-outcome state machines remain the sole operational entry points.

Acceptance: no Package/Template detail control can issue a status-only PATCH; list transitions remain unchanged and green.

### F-013 — reject present invalid Exercise partial instructions before PATCH

Root cause: `taoPayloadBaiTap(..., { partial: true })` treats every string as optional and therefore trims present whitespace-only `instructions` to `''`. The detail page also lacks an explicit pre-submit guard, so its mocked service boundary can be called with a predictably invalid value.

Repair files:

- `FE/src/services/bai_tap.api.js`
- `FE/src/services/bai_tap.api.test.js`
- `FE/src/pages/admin/bai_tap/bai_tap.chi_tiet.vue`
- `FE/src/pages/admin/bai_tap/bai_tap.chi_tiet.test.js`

Required logic:

- In the Exercise payload builder, require `instructions` when creating and also when the property is present in a partial value. Keep omission legal. A present null, whitespace-only string, object, array, or other non-string must throw the existing normalized 422 before Axios. Do not make other partial strings globally required and do not alter nullable `image_path`/`video_path`, Q11 relations, M061 echo, status allow-list, or authority-field exclusion.
- In Exercise detail `xuLyLuu`, before setting pending or calling the store, reject a missing/non-string/blank current instructions value with an inline `instructions` field error and global safe message. This page guard must issue no store/service call and no authoritative refresh.

Focused tests:

- Direct service tests call `capNhatBaiTap(1, { instructions: value })` for `null`, whitespace-only, and an object and assert normalized 422 plus zero PATCH. Add an omission case such as `{ name: 'Moi' }` that reaches PATCH without an `instructions` property. Keep valid-instructions PATCH evidence.
- The mounted detail test sets `#bai-tap-detail-instructions` to whitespace and submits; assert inline/global error, zero `capNhatBaiTap` calls, zero success refresh/navigation, preserved form, and submit not left pending. Retain the valid metadata/M061/status/pending cases.

Acceptance: Frontend behavior matches Backend `sometimes|required|string`: omitted partial instructions are accepted; any present invalid value is rejected before PATCH.

## 4. Closed-finding and boundary preservation

- F-001: inactive M061 relations remain disabled and echo the exact id/role; no relation delete.
- F-002: stale Template draft remains separate from the newest base; reconciliation changes only the expected version and never auto-POSTs.
- F-004: full lint remains zero warnings and zero errors; no lint configuration change.
- F-005: the four Package/Equipment/Muscle Group/Template list unknown-outcome flows keep local per-target phases, GET-only reconciliation, explicit retry, and cache bypass where required.
- F-007: visible copy remains UTF-8 Vietnamese with diacritics; identifiers, payload keys, route names, statuses, and official error codes remain canonical.
- F-008: shared dialog accessibility tests retain accessible name/description, safe initial focus, Tab containment, Escape, pending lock, and focus return.
- F-009: Template revision initial retry performs GET only and preserves any retained stale draft.
- F-010: Package/Template description, Exercise image/video, and Template exercise notes retain valid nulls; required strings stay strict.
- F-014: Package detail renders only Backend `configuration_version`, refreshes after metadata/benefit writes, and preserves the last confirmed value on refresh error.
- Vue compiler warning: do not restore const-reactive `v-model="form"`; full test/build output must remain clean.
- No catalog DELETE, hard delete, history rewrite, FE4 route, client authority field, blind mutation retry, invented idempotency key, or auth cleanup change.

## 5. Database, API, authorization, transaction, concurrency, idempotency and audit

- No database or Backend file changes. Existing Backend transactions, locks, validation, authorization, audit and history rules remain authoritative.
- Existing endpoints and methods are unchanged. Package benefit tests exercise `POST /admin/packages` and `PUT /admin/packages/{id}/benefits`; Exercise still uses `PATCH /admin/exercises/{id}`; Template detail/revision still use the existing GET/PATCH/POST contracts.
- Frontend route/menu checks remain presentation evidence; Backend `auth:api`, effective `ADMIN` role and resource/branch checks remain required for every request.
- The client adds no transaction, audit record, lock or idempotency header. Pending controls prevent duplicate clicks; ambiguous mutations are not retried automatically.
- Expected failures remain normalized and secret-safe. Server 422 field errors stay in the form; 409 stale recovery preserves the draft; network/5xx mutation outcomes never imply success.

## 6. Implementation order

1. Fix Exercise partial-instructions validation in the service and detail page; add direct and mounted regressions.
2. Remove Package/Template detail status state machines and replace their divergent tests while preserving read-only status and metadata flows.
3. Expand the three create, Template detail, and Template revision mounted suites for the exact F-003 rows.
4. Expand Package public service-boundary benefit tests for both negative boundaries and all five positive shapes.
5. Replace only the two incomplete Template service docblocks.
6. Run focused preservation suites, then full quality, static, boundary and preservation gates from the final tree.

## 7. Required final verification

Run from `E:\Fitness\FE` after the last product change and append exact observed results to `task-fe3-all-report.md`:

1. Focused Vitest for all nine modified test files in section 8 plus unchanged `giao_an_mau.api.test.js`, Package/Template list tests, benefit/relation/conflict/shared-dialog component tests, catalog store, router/menu/auth tests, and the remaining FE3 page suites. All pass; no skip/only, unhandled rejection, Vue warning, or compiler warning.
2. Full `rtk npm run test`: all files/tests pass from the final tree with exact counts recorded.
3. Full `rtk npm run lint`: exit 0 with literal zero warnings and zero errors.
4. `rtk npm run build` after the final change: PASS.
5. `rtk npm ls --depth=0`: PASS; no `FE/package.json` or `FE/package-lock.json` delta.
6. Static scans: no executable catalog DELETE/hard-delete/destructive relation call; no FE4/payment scope; no hard-coded API root; no dependency/framework/font addition; no secret/token logging; no `handleXxx`; no bound handler outside `xuLy...`; no `__file`, `.skip`, `.only`, TODO/FIXME, or `v-model="form"`. The two detail files must have no Package/Template status action test id, confirmation-dialog import/markup, or detail status mutation handler; their metadata calls must exclude status.
7. `rtk git diff --check`; staged diff empty. Compare the controller-created round-3 before/current manifests and exact delta: every changed product path is one of the 14 paths in section 8, no other original target changed, all pre-existing non-task paths/hunks remain byte-for-byte preserved, and the report is the only additional writable workflow artifact.
8. Browser smoke only if an already authenticated local Admin application exists. Otherwise record that limitation and use the approved mounted Vue/Pinia/router/service evidence; do not request credentials or add an auth/API bypass.

## 8. Exact round-3 product allow-list — 14 paths, zero additions

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

The fixer may append round-3 implementation and final-state evidence to `E:\Fitness\.fitness-sdd\fe3-all\task-fe3-all-report.md`; it is not a product target. No new source/test file and no other workflow artifact is writable by the fixer.

## 9. Exit gate

Round 3 is ready for one Luna Max fixer. Re-review must map F-003, F-006, F-011, F-012 and F-013 individually. PASS requires all five closed, every previously closed finding preserved, final focused/full test/lint/build/dependency/static gates green, exact 14-path boundary and preservation evidence green, staged diff empty, and no unresolved Critical or Important issue.

Current blocker: none.
