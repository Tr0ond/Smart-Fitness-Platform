# FE3-ALL fix brief — round 1

`STATUS: READY_FOR_FIX`
`TASK_ID: FE3-ALL`
`FIX_ROUND: 1`
`MAPPED_FINDINGS: F-001, F-002, F-003, F-004, F-005, F-006, F-007, F-008`
`PRODUCT_ALLOW_LIST_COUNT: 51`
`NEEDS_USER_DECISION: NO`
`EXPECTED_REPORT: E:\Fitness\.fitness-sdd\fe3-all\task-fe3-all-report.md`

Implement one consolidated repair for every open finding in `task-fe3-all-review.md`. Do not split the repair, dismiss a finding, redesign unrelated code, or edit outside the exact boundary below.

## Required context

Read completely before editing:

- `E:\Fitness\AGENTS.md`; discover deeper `AGENTS.md` files and read any governing an edited path. `BE\AGENTS.md` is discovered but does not govern this Frontend-only repair.
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
- `E:\Fitness\.fitness-sdd\fe3-all\plan.md`, `fe3-all-brief.md`, `entry-gate-revalidation.md`, `fe3-all-implementation-brief.md`, `task-fe3-all-report.md`, `task-fe3-all-review.md`, `task-fe3-all-review-package.md`, `task-fe3-all-fix-plan-round-1.md`, and this brief.
- Preservation/boundary evidence: `task-fe3-all-before-manifest.json`, `task-fe3-all-current-manifest.json`, `task-fe3-all-round-1-exact-delta.patch`, `task-fe3-all-controller-gates.json`, `task-fe3-all-targets.txt`, and controller-provided round-1-fix before snapshot/package when available.

Binding IDs: Q01, Q11, Q12, M061, RULE GYM 11–16, RULE CODE 05–11, 13, 17–20. `PROJECT_RULES.md` and `docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md` remain conditional authorities. The Fix Planner already resolved the concrete conflicts through the relevant M061, RULE CODE 06, FE3, a11y, diacritic, naming, and docblock passages; do not reread either full document. Consult another precise section only if a new conflict, ambiguity, missing coverage, or version mismatch appears, and record why.

## Required repair by finding

- **F-001:** In `bo_chon_quan_he_bai_tap.vue`, disable and handler-guard both removal and role changes for every selected inactive relation. Continue exact `id`/`role` echo in replacement payloads; inactive unselected groups remain unavailable. Add mounted component/detail-page tests for exact echo, attempted removal and attempted role change.
- **F-002:** In `giao_an_mau.tao_phien_ban.vue`, snapshot the submitted draft separately. Fetch a stale latest base into separate comparison state without calling the initial form-population path. Show version/metadata/tree differences. Update only the base version after an explicit reconcile action, keep every draft field/day/exercise, and never auto-resubmit. Update the stale dialog and mounted page tests.
- **F-003:** Replace all twelve `Component.__file` tests with mounted behavior tests. Cover the screen matrix in the Fix Plan: real loading/empty/error/data, mutations, validation, routing, Q01, Q11, M061, metadata-only PATCH, stale recovery, double-submit and ambiguous outcomes. Extend router/menu/auth tests for all 12 routes, route ordering/meta, five ordered catalog menu items/active parents, guest redirect, non-ADMIN denial and ADMIN access.
- **F-004:** Clear all 1,170 warnings within the existing 16 FE3 Vue targets. A scoped ESLint fix over only those targets may handle mechanical formatting; manually resolve template-shadow names. Do not edit lint config or unrelated files. Final full lint must show 0 warnings and 0 errors.
- **F-005:** Package, Equipment, Muscle Group and Template status dialogs close only after confirmed success plus authoritative refresh. Failure remains visible in the open flow. `outcomeUnknown` must show explicit uncertainty, perform no automatic mutation retry, and offer GET reconciliation. Compare intended/previous/current authoritative status; bypass option cache for Equipment/Muscle Group; remain unresolved when GET cannot confirm.
- **F-006:** Rename every FE3 UI-bound handler to descriptive `xuLy...` Vietnamese-no-accent camelCase. Replace inline business event expressions where needed. Add meaningful docblocks to important mutation, snapshot, no-hard-delete, M061 relation, metadata-only PATCH, COW/stale and ambiguous-outcome functions.
- **F-007:** Correct all user-visible FE3 page/component text, service/store fallback messages, router meta/breadcrumbs and menu labels to Vietnamese with diacritics. Keep identifiers, filenames, route names/paths, payload keys and official status/error codes unchanged. Update semantic copy assertions.
- **F-008:** Replace the custom Template list dialog with `components/dung_chung/hop_thoai_xac_nhan.vue`. Use its modal name/description, focus trap, initial focus, Escape, and focus-return lifecycle. Mount the real page/primitive and test accessible name, Tab/Shift+Tab wrap, Escape close, focus return, pending lock and failure/outcomeUnknown content.
- **Compiler warning:** In both Exercise create/detail pages, replace child `v-model="form"` with explicit `:model-value` plus a named `xuLy...` update handler that merges emitted relation data into the reactive object. Do not use `let form` or warning suppression. Final focused/full test output must not contain the const-binding compiler warning.

## Invariants that must remain unchanged

- No catalog DELETE or history/relation destruction. M061 inactive pivots remain exact and Backend remains final authority.
- Q11 equipment relations retain AND semantics and full `equipment_ids` payload.
- Package changes retain the Q01 prior-purchase snapshot warning and never calculate entitlement client-side.
- Template metadata PATCH sends no `days`; tree changes use COW revisions with `expected_content_version`.
- No blind retry after ambiguous catalog mutation; no new idempotency key is invented.
- Existing auth cleanup remains current-token safe; a late old-token 401 cannot clear a newer session.
- No Backend/DB/FE4/checkpoint/rule/plan/dependency change and no loss of pre-existing dirty hunks.

## Exact product allow-list

Exactly these 51 paths may be modified:

```text
FE/src/router/index.js
FE/src/router/index.test.js
FE/src/router/dieu_huong_admin.js
FE/src/router/dieu_huong_admin.test.js
FE/src/stores/xac_thuc.store.js
FE/src/stores/xac_thuc.store.test.js
FE/src/assets/main.css
FE/src/stores/danh_muc.store.js
FE/src/stores/danh_muc.store.test.js
FE/src/services/goi_tap.api.js
FE/src/services/goi_tap.api.test.js
FE/src/services/dung_cu.api.js
FE/src/services/dung_cu.api.test.js
FE/src/services/nhom_co.api.js
FE/src/services/nhom_co.api.test.js
FE/src/services/bai_tap.api.js
FE/src/services/bai_tap.api.test.js
FE/src/services/giao_an_mau.api.js
FE/src/services/giao_an_mau.api.test.js
FE/src/components/danh_muc/bo_sua_quyen_loi_goi_tap.vue
FE/src/components/danh_muc/bo_sua_quyen_loi_goi_tap.test.js
FE/src/components/danh_muc/bo_chon_quan_he_bai_tap.vue
FE/src/components/danh_muc/bo_chon_quan_he_bai_tap.test.js
FE/src/components/danh_muc/cay_giao_an.vue
FE/src/components/danh_muc/cay_giao_an.test.js
FE/src/components/danh_muc/hop_thoai_xung_dot_giao_an.vue
FE/src/components/danh_muc/hop_thoai_xung_dot_giao_an.test.js
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
FE/src/pages/admin/bai_tap/bai_tap.index.vue
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

Append actual round-1 evidence to `E:\Fitness\.fitness-sdd\fe3-all\task-fe3-all-report.md`; this report is the only additional writable workflow artifact and is excluded from the 51-product-path count. Touch only files actually needed. No new source/test file is authorized.

## Required final verification

From `E:\Fitness\FE`, run and record:

1. Focused tests for all changed services/store/components/pages plus router/menu/auth. All 12 page tests must mount and interact.
2. Full `npm run test`: at least the prior 58 files / 604 tests plus additions, all pass, no skip/only and no compiler warning.
3. Full `npm run lint`: exactly 0 warnings and 0 errors.
4. `npm run build`: PASS after the final change.
5. `npm ls --depth=0`: PASS, dependency tree unchanged.
6. Static scans for catalog DELETE, FE4 scope, hard-coded API roots, forbidden dependencies/frameworks/fonts, secrets/token logs, `handleXxx`, non-`xuLy...` FE3 handlers, `__file` page tests, TODO/FIXME, skip/only, and const-binding `v-model="form"`.
7. `git diff --check`; staged diff empty; exact 51-target delta and preservation manifests show zero unexpected path and no lost pre-existing work.

Attempt browser smoke on an existing authenticated local app at 1440/768/390. If auth is unavailable, do not ask for credentials: record the limitation and provide deterministic mounted viewport/router/Pinia/service-boundary evidence for all 12 pages plus keyboard/focus tests and responsive CSS inspection, without a new dependency or production auth/API bypass.

Return `DONE` only when F-001…F-008 and the compiler warning are all closed. Otherwise return `DONE_WITH_CONCERNS` or `BLOCKED` with the exact unresolved finding and evidence.
