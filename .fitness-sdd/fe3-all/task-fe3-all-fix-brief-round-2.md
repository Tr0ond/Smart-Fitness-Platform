# FE3-ALL fix brief — round 2

`STATUS: READY_FOR_FIX`  
`TASK_ID: FE3-ALL`  
`FIX_ROUND: 2`  
`MAPPED_FINDINGS: F-003, F-005, F-006, F-009, F-010, F-011, F-012, F-013, F-014`  
`PRESERVE_CLOSED: F-001, F-002, F-004, F-007, F-008, VUE_COMPILER_WARNING`  
`PRODUCT_ALLOW_LIST_COUNT: 36`  
`PRODUCT_ADDITION_COUNT: 0`  
`NEEDS_USER_DECISION: NO`  
`EXPECTED_REPORT: E:\Fitness\.fitness-sdd\fe3-all\task-fe3-all-report.md`

Implement one consolidated repair for all nine open Important findings from `task-fe3-all-fix-review-round-1.md`. Do not split the repair, dismiss a finding, redesign unrelated code, or edit outside the exact boundary below.

## Required context

Read completely before editing:

- `E:\Fitness\AGENTS.md`; discover any deeper `AGENTS.md` governing inspected/edited paths. Read `E:\Fitness\BE\AGENTS.md` before inspecting Backend request/service source; Backend remains read-only.
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
- `E:\Fitness\.fitness-sdd\fe3-all\plan.md`, `fe3-all-brief.md`, `entry-gate-revalidation.md`, `fe3-all-implementation-brief.md`, `task-fe3-all-report.md`, `task-fe3-all-review.md`, `task-fe3-all-fix-plan-round-1.md`, `task-fe3-all-fix-brief-round-1.md`, `task-fe3-all-fix-review-package-round-1.md`, `task-fe3-all-fix-review-round-1.md`, `task-fe3-all-fix-plan-round-2.md`, and this brief.
- Controller-provided round-2 before snapshot, current/delta package, original baseline, target manifest, and preservation evidence before writing.
- Inspect the actual relevant Package/Exercise/Workout Template Backend request and service DTO code only as needed to keep constraints exact. Do not edit it.

Binding IDs: Q01, Q11, Q12, M061, RULE GYM 11–16, RULE CODE 05–11, 13, 17–20. `PROJECT_RULES.md` and `docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md` remain conditional authorities. The round-1 limited escalation already resolved M061, handler naming, a11y, and visible-copy conflicts. Do not reread either full document. Consult only a precise passage if a new conflict, ambiguity, missing rule, or version mismatch appears and report the trigger/resolution.

## Required repair by finding

- **F-003 — meaningful page matrix:** Expand all 12 mounted page tests. Lists prove loading, error/retry, empty, data, navigation/filtering, and status flows where applicable. Creates prove client validation/no request, 422, pending/double-submit, exact payload, and success navigation. Details/revision prove initial loading/error/retry, valid nullable DTOs, metadata/relation/tree contracts, 422, pending lock, authoritative refresh, and navigation. Router tests table-drive all 12 exact paths/names/meta/breadcrumbs, static-before-param matching, guest/PT/Receptionist denial, and Admin list/deep-link access. Menu tests prove five ordered items and active parents for every create/detail/revision route. Use rendered state, interactions, API/store calls, and payloads; no shallow/file-count substitute.
- **F-005 — four two-step unknown outcomes:** In Package, Equipment, Muscle Group, and Template list pages, replace sticky store-driven dialog mode with local per-target state. First unknown PATCH -> GET-only reconcile. Intended state closes; previous state clears uncertainty and exposes an explicit retry; only the next click sends PATCH two. Missing/other/failed GET remains unresolved. Reset local error/phase on close/new target. Equipment/Muscle GET bypasses cache. Add the full two-step regression to all four page tests plus intended, unresolved, and stale-target-reset branches.
- **F-006 — RULE CODE 09:** Add concise meaningful docblocks for the generic store mutation/error contract; all important catalog service mutations; Package benefit/configuration flows; four status/reconciliation state machines; Equipment/Muscle metadata versus status; Exercise relation/status mutations; and Template create/metadata/COW/initial-retry/stale flows. Each covers purpose, input, process, output, side effect, and rule/contract. Do not add obvious comments or brittle comment-count tests.
- **F-009 — initial retry:** Define `xuLyTaiLai` in `giao_an_mau.tao_phien_ban.vue` to rerun guarded initial GET only. It must not POST, destroy a stale draft, or use comparison data as initial hydration. Test first GET failure -> click retry -> second GET success/form hydration; POST count zero.
- **F-010 — nullable fields:** Add explicit nullable string normalization only for Package/Template `description`, Exercise `image_path`/`video_path`, and Template exercise `notes`. Preserve `null` in exact request payloads; required strings still reject null/blank. Add service tests and mounted Package/Exercise/Template detail/revision tests with real nullable DTO shapes.
- **F-011 — benefit invariants:** AI off immediately emits limit `0`; AI on offers explicit limited versus unlimited choice; unlimited emits `null`; limited emits a positive integer. All-disabled benefits show an accessible inline error and emit no `luu`. Service create and benefit PUT independently reject all-disabled with no network call. Test gym-only, chat-only, direct-only, unlimited-AI, limited-AI, and transition paths. Keep Q01 snapshot copy.
- **F-012 — sensitive status confirmation:** Existing-record metadata submits never include status. Package/Template detail show status read-only and use their confirmed list actions for transition. Equipment/Muscle edit mode shows status read-only; initial status remains selectable only for create, and row actions stay confirmed. Exercise detail separates metadata save from a dedicated shared-dialog status-only PATCH followed by authoritative GET; cancel sends nothing and failed/unknown results remain visible without blind retry. Test metadata payload exclusion and no mutation before confirmation for all five cited entry points.
- **F-013 — request validation:** Package price must be a safe integer `1..999999999999999`; align inputs to `min=1`, `step=1`, and max, and retain integer duration `1..65535`. Exercise create requires non-blank `instructions`; mark the field required and map its inline error. Service and mounted tests reject zero/fractional price and missing/blank instructions with no request, while valid boundary values reach the API. Preserve authority allow-lists and Q11.
- **F-014 — configuration version:** Package detail visibly renders Backend `configuration_version`. Never increment locally. After metadata PATCH and benefit PUT, GET detail and render the returned value. Test version `1 -> 2 -> 3`; a failed refresh keeps the last confirmed value and exposes an error rather than claiming an update.

## Closed behavior that must not regress

- F-001 inactive M061 relations stay locked and are echoed with exact id/role.
- F-002 stale Template draft stays separate from latest base; reconciliation changes only the expected version and never auto-POSTs.
- F-004 full lint remains zero warnings/errors.
- F-007 user-visible text retains Vietnamese diacritics while identifiers/status/error codes stay canonical.
- F-008 shared dialog retains accessible name/description, initial focus, Tab/Shift+Tab containment, Escape, pending lock, and focus return.
- The compiler warning remains absent; never restore `v-model="form"` on const reactive objects.
- No catalog DELETE/history destruction, no M061 or Q11 weakening, no Package snapshot/entitlement calculation, no Template `days` in metadata PATCH, no blind retry/idempotency invention, and no current-token/late-401 auth regression.

## Exact product allow-list

Only these 36 paths, all already inside the original 51-target boundary, may be modified:

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

Append the round-2 implementation and exact final-state evidence to `E:\Fitness\.fitness-sdd\fe3-all\task-fe3-all-report.md`; this report is the only additional writable workflow artifact and is excluded from the product count. No new source/test file is authorized.

## Required verification

From `E:\Fitness\FE`, run and record:

1. Focused Vitest for the three modified contract service suites, benefit component, four two-step unknown pages, five sensitive-status forms/entry points, revision initial retry/stale flow, Package version refresh, all 12 page tests, router/menu/auth, and preserved M061/shared-dialog tests.
2. Full `rtk npm run test`: all tests pass with no skips/only, unhandled rejection, Vue warning, or compiler warning.
3. Full `rtk npm run lint`: exactly 0 warnings and 0 errors.
4. `rtk npm run build` after the last change: PASS.
5. `rtk npm ls --depth=0`: PASS with no package/lock delta.
6. Static scans for DELETE/destructive relation calls, FE4 scope, hard-coded API roots, dependencies/framework/fonts, secrets/token logs, `handleXxx`, bound handlers outside `xuLy...`, shallow `__file` tests, skip/only, TODO/FIXME, `v-model="form"`, and existing-record metadata payloads containing status.
7. `rtk git diff --check`; staged diff empty; controller round-2 exact-delta and preservation package reports zero unexpected path and no lost pre-existing hunk.

Attempt browser smoke only if an authenticated local app already exists. Otherwise record the limitation and use the approved deterministic mounted page/router/Pinia/service-boundary evidence; do not request credentials or add an auth/API bypass.

Return `DONE` only when every mapped finding is closed and every preservation/final gate is green. Otherwise return `DONE_WITH_CONCERNS` or `BLOCKED` with exact unresolved evidence.
