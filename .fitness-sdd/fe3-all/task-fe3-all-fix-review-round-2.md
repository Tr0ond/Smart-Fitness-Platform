# FE3-ALL Fix Review — Round 2

## Review result

- **VERDICT: FAIL**
- **SPEC: FAIL**
- **QUALITY: FAIL**
- **REVIEW_STATUS: DONE_WITH_CONCERNS**
- **FE3_TASK_GATE: FAIL**
- **FINAL_REVIEW_PERMITTED: NO**

Round 2 repairs the four list-page reconciliation state machines, initial revision retry, nullable request fields, package benefit normalization, package configuration-version refresh, router/menu/auth coverage, and the previously closed regressions. It does not close every binding finding. F-003 and F-006 remain open, F-011 lacks its explicitly required service-boundary proof, F-012 implements an unapproved status entry point on Package and Template detail pages, and F-013 still permits a blank Exercise `instructions` value in a partial update. Five Important findings therefore remain.

## Context and review boundary

The review followed the layered-context process in `AGENTS.md` and the Fitness SDD independent-reviewer contract. It read the mandatory skill and reviewer role contract, `PROJECT_CORE.md`, `RULE_INDEX.md`, `FE3_CONTEXT.md`, the relevant frontend, workout, membership/payment, auth/resource, security, coding, and testing modules, the original FE3 plan/brief/entry revalidation/implementation brief, both prior reports/reviews, and both repair-round plans/briefs/packages. `BE/AGENTS.md` was read before inspecting the authoritative Package, Exercise, and Workout Template request DTOs. No whole canonical document was reread because no new cross-module conflict, ambiguity, or version mismatch required it.

The review inspected the final source and tests, the exact 36-path round-2 boundary, controller gates, manifests, route/menu/auth behavior, and the 12 page suites. Backend source was read-only. No product file or existing report was edited.

## Independent verification

| Check | Result |
|---|---|
| Focused FE3 contract/page/router matrix | PASS; 24 files, 114 tests |
| `rtk npm run test` | PASS; 58 files, 655 tests; no skip/only, unhandled rejection, Vue warning, or compiler warning observed |
| `rtk npm run lint` | PASS; exit 0, zero warnings and zero errors |
| `rtk npm run build` | PASS; Vite transformed 169 modules |
| `rtk npm ls --depth=0` | PASS; dependency tree resolves |
| `rtk git diff --check` | PASS |
| Staged diff | PASS; empty |
| Package/lock delta | PASS; no `FE/package.json` or `FE/package-lock.json` delta |
| Current product manifest | PASS; 36/36 authorized product hashes match |
| Non-target preservation | PASS; 674/674 non-target paths in the 710-row preservation manifest match; the other 36 rows are the authorized round-2 targets |
| Controller gates | PASS for boundary only; zero unexpected paths, 710 preservation rows checked, staged diff empty, and zero post-report product hash mismatches |
| Static scans | No executable catalog DELETE/hard-delete call, FE4 scope, hard-coded API root, secret/token log, `handleXxx`, `v-model="form"`, `__file`, TODO/FIXME, test skip/only, or package/lock change found. Matches for “DELETE/hard-delete” are contract comments only. |

No authenticated local Admin application session was available for a real-browser smoke. The approved mounted Vue/Pinia/router/service evidence was used. Its remaining acceptance gaps are listed below rather than treated as green proof.

## Finding closure matrix

| ID | Status | Independent evidence |
|---|---|---|
| F-001 | **CLOSED — preserved** | Inactive M061 relations remain locked and the Exercise detail test preserves exact id/role echo. Focused and full suites pass. |
| F-002 | **CLOSED — preserved** | Stale Template drafts remain separate from the latest base; explicit reconciliation changes the expected version without automatic POST. |
| F-003 | **OPEN — Important** | The 12 page files now contain multiple mounted cases, but the required matrix is still materially incomplete. Create suites omit server 422 behavior; Template detail lacks initial load/retry, 422, and pending coverage; Template revision has only stale and initial-retry cases and omits valid success, nullable payload, 422, and pending/double-submit cases. See Finding 1. |
| F-004 | **CLOSED — preserved** | Full lint exits 0 with zero warnings/errors. |
| F-005 | **CLOSED** | Package, Equipment, Muscle Group, and Template list pages use local per-target phases. Unknown confirm reconciles by GET only; intended closes, previous exposes deliberate retry, and missing/other/failed remains unresolved. Equipment/Muscle reconciliation bypasses cache and dialog state resets on close/new target. |
| F-006 | **OPEN — Important** | Most new docblocks are meaningful, but required Template base-load and stale classifier functions still omit the mandated six-part semantic contract. See Finding 2. |
| F-007 | **CLOSED — preserved** | UTF-8 source inspection shows Vietnamese visible copy; identifiers/status/error codes remain canonical. |
| F-008 | **CLOSED — preserved** | Shared dialog lifecycle coverage still verifies accessible name/description, safe initial focus, Tab containment, Escape, pending lock, and focus return. |
| Compiler warning | **CLOSED — preserved** | No const-reactive `v-model="form"` remains; tests/build emit no related warning. |
| F-009 | **CLOSED** | `xuLyTaiLai` exists and reruns guarded GET only. Its mounted case proves first GET failure, second GET success/hydration, and zero revision POSTs. |
| F-010 | **CLOSED** | Package/Template descriptions, Exercise image/video paths, and Template exercise notes preserve `null`; required strings reject null/blank where implemented. Mounted nullable DTO and exact service payload checks pass. |
| F-011 | **OPEN — Important (required proof incomplete)** | Runtime normalization/invariants are implemented, but service tests do not independently call Package create and benefit PUT with all-disabled data, and do not cover the required gym-only/chat-only/direct-only/limited-AI shapes. See Finding 3. |
| F-012 | **OPEN — Important** | Metadata PATCHes omit status and Equipment/Muscle edit status is read-only. Exercise has the required dedicated confirmation. Package and Template detail pages, however, add their own transition buttons/dialogs although the binding design says their list buttons remain the transition entry points. See Finding 4. |
| F-013 | **OPEN — Important** | Package bounds and Exercise create validation are repaired, but Exercise partial update still accepts blank `instructions` and can issue a predictably invalid PATCH. See Finding 5. |
| F-014 | **CLOSED** | Package detail renders Backend `configuration_version`, refreshes after metadata and benefit mutations, proves `1 → 2 → 3`, and retains the last confirmed version with a visible read error on failed refresh. |

## Actionable findings

### 1. F-003 — Important — The required 12-page behavioral matrix is still incomplete

- **Files/lines:**
  - `FE/src/pages/admin/giao_an_mau/giao_an_mau.tao_moi.test.js:36`
  - `FE/src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.test.js:33`
  - `FE/src/pages/admin/giao_an_mau/giao_an_mau.tao_phien_ban.test.js:37`
  - `FE/src/pages/admin/goi_tap/goi_tap.tao_moi.test.js:35`
  - `FE/src/pages/admin/bai_tap/bai_tap.tao_moi.test.js:39`
- **Reproduction/evidence:** The Template create suite has only success, client session validation, and pending cases; it never injects/render-checks a server 422. Template detail has only nullable metadata/status navigation and refresh-failure cases; it lacks initial loading/error/retry, 422, and pending lock. Template revision has exactly two cases—stale reconciliation and initial GET retry—and never proves a valid copy-on-write payload/success navigation, nullable description/notes payload, ordinary 422, or pending double-submit. Package and Exercise create suites likewise contain no rejected server-422 case. A scoped 422 search across those suites finds none.
- **Impact:** All 655 tests can remain green while required failure handling, exact request construction, and concurrency locks on core create/detail/revision paths regress. This directly misses the round-2 acceptance matrix and testing definition of done.
- **Required fix:** Add mounted interaction tests for each missing row from the binding matrix. At minimum, cover server 422 field rendering on all create forms; initial load/error/retry, 422, pending and authoritative refresh on details; and valid nullable COW success, exact payload/navigation, 422 and double-submit on Template revision. Assert rendered state, call counts, payloads, disabled state, and navigation.

### 2. F-006 — Important — Two required Template service docblocks remain incomplete

- **File/lines:** `FE/src/services/giao_an_mau.api.js:128` and `FE/src/services/giao_an_mau.api.js:193`
- **Reproduction/evidence:** `taiNenGiaoAnMau` has three short lines that mention a base GET but omit a distinct process/result contract and governing COW/version rule. `xuLyGiaoAnMauDaCu` has two lines and does not document its input, classification process, returned shape, side effects, or optimistic-concurrency contract in the six meanings required by the round-2 plan. These functions are specifically part of “Template ... COW ... stale comparison”.
- **Impact:** The high-risk stale/revision boundary remains below RULE CODE 09 and cannot be audited from the service contract alone.
- **Required fix:** Replace each short comment with a concise semantic docblock covering purpose, input, processing, result, side effects, and governing COW/`expected_content_version` rule. Keep comments behavioral and avoid token-count tests.

### 3. F-011 — Important — Required benefit service-boundary tests are not present

- **File/line:** `FE/src/services/goi_tap.api.test.js:45`
- **Reproduction/evidence:** The single invariant case calls only `taoPayloadQuyenLoiGoiTap(...)`. It never invokes `taoGoiTap(...)` or `thayTheQuyenLoiGoiTap(...)` with the all-disabled shape; `expect(put).not.toHaveBeenCalled()` therefore passes without exercising PUT at all. The suite also lacks the binding positive cases for gym-only, chat-only, direct-session-only, and limited-AI. Component tests cover AI transitions and an accessible all-disabled error but do not replace the required service boundary proof.
- **Impact:** The claimed independent create/PUT enforcement and the complete benefit contract are not protected against wiring regressions.
- **Required fix:** Call both public service mutations with all-disabled benefits and assert normalized 422 plus zero POST/PUT. Add valid gym-only, chat-only, direct-only, unlimited-AI, and limited-AI calls with exact payload assertions.

### 4. F-012 — Important — Package and Template detail pages add status transitions outside the approved list entry points

- **Files/lines:**
  - `FE/src/pages/admin/goi_tap/goi_tap.chi_tiet.vue:100` and `:341`
  - `FE/src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.vue:60` and `:290`
  - Tests encode the divergence at `FE/src/pages/admin/goi_tap/goi_tap.chi_tiet.test.js:90` and `FE/src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.test.js:46`.
- **Reproduction/evidence:** Open either detail page. Although status is rendered read-only, the page also renders a local status action and confirmation flow that issues a status-only PATCH. The binding round-2 design explicitly requires Package/Template detail metadata forms to show status read-only and states that their existing list-page buttons remain the confirmed transition entry points.
- **Impact:** The implementation expands the sensitive-action surface beyond the reviewed design and duplicates reconciliation state machines, increasing inconsistent lifecycle behavior risk.
- **Required fix:** Remove Package/Template detail status actions and their local dialog/state-machine code. Retain the read-only status display and metadata exclusion; keep transition/reconciliation in the already tested list entry points. Replace the two detail status tests with assertions that no detail transition control exists and metadata submission excludes status.

### 5. F-013 — Important — Exercise PATCH accepts blank `instructions` contrary to Backend `sometimes|required`

- **Files/lines:**
  - `FE/src/services/bai_tap.api.js:66-74`
  - `FE/src/services/bai_tap.api.test.js:56`
  - Authoritative read-only contract: `BE/app/Http/Requests/Admin/Catalog/UpdateExerciseRequest.php:21`
- **Reproduction/evidence:** `taoPayloadBaiTap(value, { partial: true })` sets `required: false` for every string. `layChuoi('   ', ..., { required: false })` trims and returns `''`, so `capNhatBaiTap(21, { instructions: '   ' })` reaches Axios. Backend declares `instructions` as `sometimes|required|string`, so the request predictably returns 422. The test checks update `null` only and never checks update blank; the detail mounted suite also never submits blank instructions.
- **Impact:** Client and Backend validation still diverge on an existing-record path explicitly named by the repair plan, producing avoidable 422 responses and violating the no-invalid-PATCH acceptance case.
- **Required fix:** When `instructions` is present in a partial payload, reject null, blank, and non-string values before Axios while allowing omission. Add direct service tests for update null/blank/object and a mounted detail test proving blank instructions issue no PATCH; keep a valid instructions PATCH case.

## Route, menu, authorization, and regression review

All 12 exact routes, Admin metadata, breadcrumbs, static-before-parameter resolution, guest redirect, PT/Receptionist denial, Admin deep-link access, five ordered menu entries, and active child parents are covered and pass. No FE4 route or payment scope was added.

The four list unknown-outcome machines satisfy their core two-step behavior, including per-target reset and cache bypass where required. M061 exact echo/locking, Q11 AND semantics, Q01 snapshot copy, absence of catalog deletion, shared-dialog accessibility, current-token auth behavior, stale-draft separation, and the Vue compiler-warning fix remain intact in the reviewed tree.

## Boundary conclusion

Boundary and preservation gates are green: all 36 authorized product targets match the controller current manifest, all 674 non-target preservation rows match, the controller reports zero unexpected paths, and the staged diff is empty. These checks establish scope integrity but do not override the five Important spec/quality findings above.

`FE3_TASK_GATE: FAIL`

`FINAL_REVIEW_PERMITTED: NO`

