# FE3-ALL Fix Review — Round 1

## Review result

- **VERDICT: FAIL**
- **SPEC: FAIL**
- **QUALITY: FAIL**
- **FE3_TASK_GATE: FAIL**
- **FINAL_REVIEW_PERMITTED: NO**

The repair closes F-001, F-002, F-004, F-007, F-008, and the Vue compiler warning. F-003, F-005, and F-006 remain open at Important severity. Independent inspection also found six Important defects (F-009 through F-014). The round cannot pass while these findings remain.

## Context and scope reviewed

The review followed the layered-context workflow in `AGENTS.md` and the Fitness SDD independent-reviewer contract. It read the core, index, FE3 phase context, relevant frontend/domain/engineering modules, the original FE3-ALL plan and implementation package, the first review, the round-one repair plan/brief/package, and the implementation report. The canonical document was not reread wholesale because no new module conflict or version mismatch required it.

The review inspected the exact repair delta, controller gate, target and preservation manifests, final product files, service tests, mounted component/page tests, and all required gate commands. It did not rely on the repair report or green command summaries as proof of behavior.

## Verification performed

| Check | Independent result |
|---|---|
| `rtk npm ls --depth=0` | PASS; expected dependency tree resolved |
| `rtk npm run test` | PASS; 58 files, 607 tests |
| Focused relation, detail, stale-revision, conflict-dialog, and four list-page tests | PASS; 8 files, 9 tests |
| `rtk npm run lint` | PASS; zero warnings and zero errors |
| `rtk npm run build` | PASS; 169 modules transformed; no `v-model` compiler warning observed |
| `rtk git diff --check` | PASS |
| Current-target manifest | 52/52 rows matched current SHA-256 hashes |
| Preservation manifest | 659/659 non-target paths matched preserved SHA-256 hashes |
| Controller gate | 0 unexpected paths; staged diff empty; 42 changed product targets, 9 unchanged product targets, report changed |
| Static scope checks | No `__file`, `v-model="form"`, `.skip`, `.only`, legacy `handleXxx`, hard-delete call, FE4 endpoint, or scoped TODO/FIXME residue |

No application server was listening on the expected local ports during review, so a real-browser smoke test was unavailable. The mounted test suite was inspected as the executable UI evidence; its missing cases are recorded under F-003 and the defects it failed to detect are reproduced from the mounted source paths below.

## Original finding closure

| ID | Status | Evidence |
|---|---|---|
| F-001 | **CLOSED** | `bo_chon_quan_he_bai_tap.vue` treats every inactive relation as disabled and treats an inactive selected relation as immutable. Both UI events and programmatic removal/role-change paths restore the canonical prop value. Mounted tests cover inactive selected/unselected items, and exercise-detail submission preserves the exact `{ id: 8, role: 'PHU' }` echo. |
| F-002 | **CLOSED** | `giao_an_mau.tao_phien_ban.vue` snapshots the pending draft separately from the newly fetched base. Reconcile updates only `expected_content_version`; it does not replace draft content and does not auto-submit. Mounted coverage asserts draft retention, explicit version reconciliation, and no second POST. |
| F-003 | **OPEN — Important** | All 12 page test files now mount their pages, but each contains only one `it(...)` case and exercises a narrow path. They do not provide the required loading/error/empty/data, 422, double-submit, auth, route/menu, and complete unknown-outcome matrix. The green 607-test total therefore does not establish FE3 acceptance depth. See the F-003 finding below. |
| F-004 | **CLOSED** | Independent `npm run lint` completed with no output, warnings, or errors. |
| F-005 | **OPEN — Important** | All four list-page unknown-outcome flows use the store's sticky mutation error as dialog state. When authoritative GET proves the mutation did not apply, reconciliation returns without clearing/resolving that state; confirm therefore reconciles forever instead of allowing an explicit retry. The stale error can also contaminate a later target. See the F-005 finding below. |
| F-006 | **OPEN — Important** | UI-bound handler naming has been repaired to `xuLy...`, but important mutation functions still have one-line comments rather than the required purpose/input/process/output/side-effect and governing-rule documentation. See the F-006 finding below. |
| F-007 | **CLOSED** | Product strings, router metadata, and menu labels inspected through UTF-8-safe reads use Vietnamese diacritics. The earlier mojibake seen in a raw PowerShell display was terminal encoding, not file content. |
| F-008 | **CLOSED** | The template list uses the shared confirmation primitive. The primitive provides dialog semantics, accessible labelling/describing, initial safe-button focus, focus containment, Escape handling, focus restoration, and pending-state locking. Its mounted lifecycle test passes. The lack of page-level interaction breadth is retained under F-003. |
| Compiler warning | **CLOSED** | Form wrappers use explicit `:model-value` and `@update:model-value`; no `v-model="form"` remains. Full tests and build emit no related compiler warning. |

## Findings

### F-003 — Important — The 12 mounted page tests do not meet the required behavior matrix

- **Files:** `FE/src/pages/admin/goi_tap/goi_tap.index.test.js:28` and the other 11 FE3 page test files
- **Evidence/reproduction:** A scoped search finds exactly one `it(...)` in every page test. The focused suite for eight risk-heavy files contains only nine tests. Loading, error, empty, populated, validation-422, double-submit, auth, route/menu, and the full unknown-outcome transitions are not exercised across the 12 pages.
- **Impact:** The implementation can pass all tests while shipping the runtime defects in R1-002 and R1-004 through R1-009. This does not satisfy the repair brief's mounted behavioral matrix or the testing definition of done.
- **Required fix:** Add mounted, interaction-level tests for the required matrix on every affected page, emphasizing actual state transitions and emitted requests rather than filenames or shallow render assertions.

### F-005 — Important — Four unknown-outcome dialogs can become permanently stuck in reconciliation

- **Files:**
  - `FE/src/pages/admin/goi_tap/goi_tap.index.vue:54`
  - `FE/src/pages/admin/dung_cu/dung_cu.index.vue:49`
  - `FE/src/pages/admin/nhom_co/nhom_co.index.vue:46`
  - `FE/src/pages/admin/giao_an_mau/giao_an_mau.index.vue:58`
- **Evidence/reproduction:** Trigger an outcome-unknown mutation, then let the authoritative GET return the previous state. Each reconcile function simply returns. The store mutation error remains `outcomeUnknown`, so the next confirm invokes reconciliation again rather than retrying the mutation. Opening another target can inherit the same stale store error; a successful authoritative match closes the dialog without resetting that shared error either.
- **Impact:** An admin cannot explicitly retry a mutation that the server proves did not apply, and stale uncertainty can be displayed for an unrelated resource.
- **Required fix:** Use local, per-dialog uncertainty state. After authoritative GET proves the previous state, clear the unknown condition and expose an explicit retry path. Reset local state on close/new target; keep it unresolved only for missing/other authoritative state or failed reconciliation. Add two-step mounted tests for all four pages.

### F-006 — Important — Mutation documentation remains below RULE CODE 09

- **Files/examples:**
  - `FE/src/pages/admin/goi_tap/goi_tap.chi_tiet.vue:23`
  - `FE/src/pages/admin/dung_cu/dung_cu.index.vue:26`
  - `FE/src/pages/admin/nhom_co/nhom_co.index.vue:26`
  - `FE/src/pages/admin/giao_an_mau/giao_an_mau.tao_moi.vue:13`
  - `FE/src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.vue:18`
- **Evidence/reproduction:** The handlers use compliant `xuLy...` names, but their docblocks are single-sentence labels such as an update summary. They do not document purpose, input, processing, output, side effects, and relevant rule/contract as required by the repair brief and RULE CODE 09.
- **Impact:** The original quality finding is only partially repaired, and high-risk mutations remain hard to audit against their business contract.
- **Required fix:** Add concise but meaningful docblocks covering the required fields for each important mutation and uncertainty/reconciliation handler.

### F-009 — Important — Initial revision-load retry calls an undefined handler

- **File:** `FE/src/pages/admin/giao_an_mau/giao_an_mau.tao_phien_ban.vue:192`
- **Evidence/reproduction:** The error component binds `@thu-lai="xuLyTaiLai"`, but no `xuLyTaiLai` declaration exists in the component. Make the initial detail request fail, render the error state, and click retry; Vue attempts to invoke an undefined handler and no reload occurs.
- **Impact:** The page's initial-load failure state is not recoverable from the UI.
- **Required fix:** Define `xuLyTaiLai` to run the guarded initial load and add a mounted initial-error/retry test.

### F-010 — Important — Nullable Backend fields are rejected by frontend response validators

- **Files:**
  - `FE/src/services/admin/goi_tap.api.js:37`
  - `FE/src/services/admin/bai_tap.api.js:28`
  - `FE/src/services/admin/giao_an_mau.api.js:27`
- **Evidence/reproduction:** The generic string readers reject `null`, although Backend requests/resources permit nullable package/template descriptions, exercise image/video paths, and day notes. Detail pages spread the DTO into their forms and submit those fields. A normal record with `description: null`, `image_path: null`, `video_path: null`, or `notes: null` therefore throws a client-side contract error before the intended PATCH/POST workflow can complete.
- **Impact:** Common valid Backend records cannot be opened or edited reliably.
- **Required fix:** Model nullable fields explicitly and normalize only where the request contract requires it. Add service and mounted-detail tests using actual nullable DTO shapes.

### F-011 — Important — Package benefit controls can create an invalid, uneditable state

- **File:** `FE/src/components/admin/goi_tap/bo_sua_quyen_loi_goi_tap.vue:27`
- **Evidence/reproduction:** Load AI enabled with a positive limit, then uncheck AI. The limit input becomes disabled but retains the positive value, while the service contract requires `0` when AI is disabled. When AI is enabled, the Backend accepts `null` as unlimited or a positive integer, but the UI has no explicit unlimited control. The frontend validator also permits all benefits disabled and sends the request even though the package rule requires at least one benefit.
- **Impact:** Users can be trapped in a form they cannot correct and encounter avoidable server-side 422 responses.
- **Required fix:** Normalize the limit to `0` when AI is disabled, provide an explicit unlimited choice mapped to `null`, validate at least one benefit before the network request, and cover each transition with mounted and service tests.

### F-012 — Important — Status selects bypass the required sensitive-action confirmation

- **Files:**
  - `FE/src/pages/admin/goi_tap/goi_tap.chi_tiet.vue:125`
  - `FE/src/pages/admin/dung_cu/dung_cu.index.vue:152`
  - `FE/src/pages/admin/nhom_co/nhom_co.index.vue:146`
  - `FE/src/pages/admin/bai_tap/bai_tap.chi_tiet.vue:117`
  - `FE/src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.vue:118`
- **Evidence/reproduction:** Change an active record's status in the generic edit form and submit. The PATCH is issued directly; the shared confirmation dialog used by the list action is bypassed.
- **Impact:** Deactivation can occur without the FE3 context's required confirmation for sensitive actions.
- **Required fix:** Remove status transitions from generic metadata submission or route every real transition through the shared confirmation flow. Test both list and detail entry points.

### F-013 — Important — Frontend request validation diverges from Backend constraints

- **Files:**
  - `FE/src/services/admin/goi_tap.api.js:54`
  - `FE/src/services/admin/goi_tap.api.js:114`
  - `FE/src/services/admin/bai_tap.api.js:68`
- **Evidence/reproduction:** Package price accepts any finite value and the UI permits a minimum of zero, while Backend requires an integer of at least one. Exercise creation omits `instructions` from its required-field list and the textarea is not required, although Backend requires instructions. Existing service coverage even expects a create request without instructions to be sent.
- **Impact:** Forms marked valid by the UI predictably fail with Backend 422 responses.
- **Required fix:** Align service validation and native input constraints to the authoritative Backend request contract. Add negative tests for zero/fractional price and missing instructions that assert no API call occurs.

### F-014 — Important — Authoritative package configuration version is never displayed

- **Files:** `FE/src/pages/admin/goi_tap/goi_tap.chi_tiet.vue` and package editor/list components
- **Evidence/reproduction:** A scoped product/test search finds no rendered `configuration_version`; the only occurrence is a service docblock. Consequently, no mounted test verifies that metadata or benefit writes refresh and show the server's incremented version.
- **Impact:** The explicit FE3-ALL requirement to refresh and display the authoritative configuration version after writes is unmet.
- **Required fix:** Render the server-returned/refetched `configuration_version` in the package detail view, refresh it after both metadata and benefit updates, and assert a version increment in mounted tests.

## Route, menu, and authorization review

The FE3 route and menu inventory is present, uses Vietnamese labels, and preserves role-based route metadata/guards. No FE4 endpoint or scope expansion was found. However, the repair does not provide a mounted route/menu/auth matrix across all required roles and deep-link denial cases; this missing proof is part of F-003.

## Boundary and preservation

- The controller gate reports zero unexpected paths and an empty staged diff.
- All 52 current-manifest hashes match the reviewed files.
- All 659 non-target preservation paths match their baseline hashes.
- The product-target accounting is internally consistent: 42 changed and 9 unchanged targets.
- No hard-delete implementation, FE4 endpoint, unrelated dependency change, or test suppression was found in the reviewed FE3 scope.

## Required next round

Repair the open F-003, F-005, and F-006 findings plus F-009 through F-014, then rerun the complete lint/test/build/static gate. The next package must include mounted regressions that fail on each defect before the fix, especially the four two-step unknown-outcome flows, nullable DTOs, status confirmation from detail forms, initial-load retry, package benefit invariants, exact Backend validation constraints, and authoritative configuration-version refresh.
