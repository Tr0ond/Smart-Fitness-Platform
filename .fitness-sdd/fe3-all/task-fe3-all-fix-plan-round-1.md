# FE3-ALL consolidated fix plan — round 1

`STATUS: READY_FOR_FIX`
`TASK_ID: FE3-ALL`
`FIX_ROUND: 1`
`OPEN_FINDINGS: F-001, F-002, F-003, F-004, F-005, F-006, F-007, F-008`
`MAPPED_FINDING_COUNT: 8`
`NEEDS_USER_DECISION: NO`
`FULL_CANONICAL_REREAD: CONDITIONAL_NOT_REQUIRED`
`PRODUCT_ALLOW_LIST_COUNT: 51`

## 1. Repair outcome and boundaries

Execute one consolidated repair wave for all eight open findings from `task-fe3-all-review.md`. Preserve the existing FE3 implementation, Backend repair, all pre-existing dirty work, and the original aggregate `FE3-ALL` task boundary. This plan does not reopen product scope and does not authorize a partial repair or separate writers per screen.

Actor and use case remain unchanged: an authenticated `ADMIN` manages Package/benefits, Equipment, Muscle Group, Exercise relations, and Workout Template metadata/revisions through the existing source-authoritative Backend contracts. Backend remains the authorization, validation, transaction, concurrency, audit, and business authority.

No Backend, database, Mobile, FE4+, checkpoint, rule, canonical plan, dependency, package/lock, lint configuration, or shared-foundation file outside the 51 original FE3 targets may change. Do not add a dependency, fake production API, test-only production route, auth bypass, or persistent stale flag. Do not commit, push, branch, create a worktree, reset, restore, checkout, stash, clean, or publish.

## 2. Evidence and scope ruling

- The task review is `FAIL` with eight Important findings, all retained verbatim and mapped below.
- The original boundary contains exactly 51 product targets. Before implementation, 7 existed and 44 were absent; the current manifest contains all 51, with 49 changed and two unchanged (`FE/src/router/index.test.js`, `FE/src/stores/xac_thuc.store.test.js`). Controller gates report zero unexpected paths, empty staged diff, and 666 preservation paths checked.
- The existing full Frontend suite passed 58 files / 604 tests, but all 12 page tests only assert `Component.__file`; therefore the pass does not demonstrate screen behavior.
- An independent read-only ESLint API run against the current tree reported exactly 1,170 warnings and 0 errors. All 1,170 warnings are confined to 16 files already in the 51-target boundary; no warning occurs in an inherited or unrelated file. Rule totals are 597 `vue/max-attributes-per-line`, 550 `vue/singleline-html-element-content-newline`, and 23 `vue/no-template-shadow`. Therefore zero-warning lint is achievable without authorizing any file outside the current 51 targets and without changing ESLint configuration.
- A focused Vitest run for the two Exercise form pages passed 2 files / 2 tests but reproduced the compiler warning: ``v-model` cannot update a `const` reactive binding `form``. The repair must remove its cause in both Exercise create/detail templates, not suppress stderr or relax the compiler/lint gate.
- Browser smoke remains absent. The repair must first attempt executable QA against an already running local authenticated app. If authentication is unavailable, use the deterministic mounted-page evidence defined in section 7 without asking for credentials or creating fake production APIs.

## 3. Canonical escalation record

Concrete implementation conflicts triggered a limited authority check. The following relevant passages were consulted, not the full documents:

- `PROJECT_RULES.md` around M061 (line 1182): inactive pivots keep identifier, role and timestamps; replacement must echo them exactly or fail with `INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE`.
- `PROJECT_RULES.md` RULE CODE 06 (around lines 2112–2139): UI event handlers use descriptive `xuLy...` Vietnamese-no-accent identifiers.
- `docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md` UI/a11y instruction (around lines 667–677), FE3 screen contracts (Sections 23.18–23.29), naming/docblock gate (around lines 1395–1402), FE-3 acceptance (around lines 1454–1467 and 1789–1802), and execution instruction 15 (around line 1953).

Resolution: lock and echo inactive M061 relations; preserve draft separately and require explicit stale reconciliation; use mounted behavioral tests, semantic shared dialogs, zero-warning lint, `xuLy...` handlers with meaningful mutation/stale docblocks, and Vietnamese UI copy with diacritics. The layered modules and relevant canonical passages agree. No unresolved business choice or version mismatch remains, so no full canonical reread is needed.

## 4. Direct mapping of every open finding

### F-001 — lock and echo every existing inactive M061 pivot

Root cause: `bo_chon_quan_he_bai_tap.vue` disables only an inactive group that is not selected. A selected inactive relation keeps both its checkbox and role selector enabled, and its handlers do not defensively reject removal/role changes.

Repair logic:

- In `FE/src/components/danh_muc/bo_chon_quan_he_bai_tap.vue`, identify a relation as immutable when the group is inactive and is present in the current model. Disable both checkbox and role selector for that relation.
- Add handler-level guards so a synthetic/programmatic change event cannot remove it or change its role. Continue emitting the complete relation array with the exact inactive `id` and `role`; never omit it from a replacement payload.
- Keep inactive unselected groups unavailable for new relations. Keep active relations editable. Do not add any DELETE path or alter Equipment semantics.
- In the Exercise detail page, keep the loaded inactive relations in the draft and submit the exact echo. Surface Backend `INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE` as a normalized in-flow error if authoritative state changed concurrently.

Focused tests: mount the relation selector with active, inactive-selected, and inactive-unselected groups; assert exact echo, disabled controls, attempted removal no-op, attempted role change no-op, active changes still work, and the detail-page PATCH contains the unchanged inactive relation.

Acceptance: no UI or programmatic component path can mutate an existing inactive pivot, while the outgoing replacement retains its original identifier and role.

### F-002 — preserve the stale revision draft and reconcile explicitly

Root cause: `giao_an_mau.tao_phien_ban.vue::tai()` calls `nap()` after the stale dialog action, replacing metadata, days, and `expected_content_version` with the newest base. There is no independent submitted-draft snapshot or comparison state.

Repair logic:

- At submit time, deep-clone the exact revision draft into a separate `banNhapChoDoiSoat` state before the request.
- On `WORKOUT_TEMPLATE_STALE`, keep `form` and its old `expected_content_version` unchanged. Fetch the newest base into a separate `nenMoiNhat` state; do not call the initial-load `nap()` path.
- Render a clear comparison of old/new content version and changed metadata/tree summaries. The stale dialog remains a shared accessible dialog.
- Provide a distinct explicit reconciliation action. Only that action may copy the newest content version into `form.expected_content_version`; it must retain all user-entered metadata, `new_code`, and day/exercise draft content. It must not resubmit automatically.
- Initial load/retry may still populate the form only before a user draft exists. A failed comparison fetch keeps both draft and conflict visible.

Focused tests: mount the page with a real reactive Pinia store and mocked service boundary; edit metadata/tree, submit, return stale 409, load a different latest base, prove all draft values remain, prove comparison renders, prove expected version changes only after the explicit reconcile action, and prove no second revision request occurs automatically.

Acceptance: stale recovery cannot destroy the draft or silently rebase/resubmit it.

### F-003 — replace filename assertions with real mounted behavior coverage

Root cause: every FE3 page test imports a component and checks `__file`; none mounts a page, drives a store/API result, router action, validation, dialog, or error state. Router/menu tests also omit the full FE3 route/menu/auth matrix.

Repair logic:

- Replace all twelve filename-only tests with mounted tests using existing `@vue/test-utils`, Vitest, Vue Router, and Pinia. Mock only the service boundary or use controlled Pinia actions; do not fake production endpoints or add a dependency.
- Cover each screen's meaningful contract:
  1. Package list: loading/error/empty/data, detail/create navigation, state-dialog success/failure/outcome-unknown reconciliation.
  2. Package create: form payload/benefits, 422 inline error, pending/double-submit lock, successful navigation.
  3. Package detail: load, metadata allow-list, Q01 warning, benefit replacement, errors and authoritative refresh.
  4. Equipment: loading/error/empty/data, create/edit, status confirmation, no-delete copy, ambiguous-outcome reconciliation.
  5. Muscle Group: inactive rows remain visible, create/edit/status, M061 preservation copy, ambiguous-outcome reconciliation.
  6. Exercise list: option loading, search/status and local filters, empty/data, detail/create navigation.
  7. Exercise create: active selector behavior, full Q11 AND payload, 422/pending/double-submit, compiler-warning-free component binding.
  8. Exercise detail: loaded relation mapping, locked inactive echo, exact immutable error, successful refetch, compiler-warning-free binding.
  9. Template list: loading/error/empty/data, detail/create navigation, shared status dialog, failure/outcome-unknown reconciliation, keyboard/focus lifecycle.
  10. Template create: mounted tree edits, structure/field error feedback, pending/double-submit, full-tree payload.
  11. Template detail: read-only tree, metadata-only PATCH with no `days`, 404/422/409 feedback, create-revision navigation.
  12. Template revision: initial base, edit/submit, stale draft preservation/comparison/explicit reconciliation, no automatic retry.
- Extend `router/index.test.js` and `router/dieu_huong_admin.test.js` to assert all 12 exact paths/names, static-before-parameter matching, ADMIN meta/breadcrumbs, five ordered catalog menu entries with diacritics, active-parent mapping, guest redirect, authenticated non-ADMIN denial, and authenticated ADMIN access. Extend the existing auth cleanup test only if needed to prove the already-integrated catalog cleanup; do not change auth behavior unrelated to FE3.

Acceptance: every one of the 12 page test files mounts its page and asserts at least one state transition or interaction; the combined suite covers the required critical states rather than duplicating superficial render assertions.

### F-004 — make the full lint command report zero warnings and zero errors

Root cause: compact one-line templates and repeated slot variable names violate the repository's active Vue recommended rules. Exit code 0 hid 1,170 warnings.

Repair logic:

- Format only the 16 warning-bearing FE3 Vue targets. A scoped ESLint `--fix` run over those exact files is allowed for mechanical formatting; resolve remaining `vue/no-template-shadow` warnings manually with descriptive slot variables such as `idTruong`.
- Do not edit `eslint.config.js`, downgrade rules, add ignores, or run a repository-wide formatter that touches unrelated files.
- Rerun the full repository `npm run lint` and require literal totals of 0 warnings and 0 errors.

Acceptance: the full lint output is clean; Git diff shows no unrelated formatting churn.

### F-005 — keep mutation failures visible and reconcile unknown outcomes safely

Root cause: Package, Equipment, Muscle Group, and Template list status handlers ignore the mutation result/error, always refetch, then close the dialog. This implies success and hides `outcomeUnknown`.

Repair logic:

- For each list status mutation, close the dialog only after the mutation returns confirmed success and the authoritative list refresh completes.
- On a normalized failure, keep the dialog/context open and render the error. On `outcomeUnknown`, explicitly say that the result cannot be determined, do not retry the mutation automatically, and change the primary action to an authoritative reconciliation fetch.
- Reconciliation compares the refreshed item's status with the intended and previous values: intended value confirms success; previous value permits a deliberate later retry; missing/fetch-failed/other value remains unresolved. Never treat a refetch failure as mutation success.
- For Equipment/Muscle Group, bypass the 30-second selector cache during reconciliation. Preserve the target item and intended state until resolution or explicit user close.

Focused tests: for all four flows, assert dialog remains on 422/403/409/network/5xx as applicable; `outcomeUnknown` copy is present; mutation call count stays one until a deliberate retry; reconciliation performs GET only; confirmed authoritative state closes, unresolved state remains.

Acceptance: no failed or ambiguous mutation looks successful and no blind retry occurs.

### F-006 — enforce `xuLy...` event handlers and meaningful business docblocks

Root cause: FE3 pages/components use event handlers such as `moChiTiet`, `moDoiTrangThai`, `dongDoiTrangThai`, `xacNhanDoiTrangThai`, `luu`, `huy`, `chonItem`, `apDung`, `themNgay`, and `xoaNgay`; important mutation/stale flows lack required purpose/input/process/output/side-effect/rule documentation.

Repair logic:

- Rename every FE3 handler bound to `@click`, `@submit`, `@change`, dialog events, filter events, and router actions to a descriptive Vietnamese-no-accent camelCase name beginning `xuLy...`.
- Keep non-handler helpers/fetchers descriptive (`tai...`, `nap...`, `lay...`, `tao...`). Replace inline business mutations/navigation expressions with named `xuLy...` functions when they are event handlers.
- Add concise meaningful docblocks to Package snapshot/benefit mutations, no-hard-delete status mutations, Exercise relation payload/immutable handling, Template metadata-only PATCH, revision COW/stale reconciliation, and ambiguous-outcome reconciliation. Include purpose, input, process, result, side effect, and binding rule where material.
- Do not translate Vue/Pinia/Router/Axios APIs.

Acceptance: static/manual scan finds no FE3 UI event handler lacking `xuLy...`; important functions have meaningful docblocks without obvious-comment noise.

### F-007 — use Vietnamese diacritics in all user-visible FE3 copy

Root cause: the initial implementation applied the identifier convention to visible copy. Page headings, actions, warnings, states, router meta/breadcrumbs, menu labels, component text, and normalized user-facing messages are pervasively accentless.

Repair logic:

- Correct visible text in all FE3 pages/components, router metadata/breadcrumbs, five Admin menu entries, and any FE3 service/store fallback message that reaches the user.
- Keep code identifiers, route paths/names, API keys, payload fields, status/error codes, and filenames unchanged and accentless where required.
- Update semantic text assertions. Include explicit assertions for `Gói tập`, `Dụng cụ`, `Nhóm cơ`, `Bài tập`, `Giáo án mẫu`, `Tạo mới`, `Tải lại/Đối soát`, Q01/M061/Q11 warnings, mutation ambiguity, and stale reconciliation.

Acceptance: manual scan of rendered literals and router/menu data finds professional Vietnamese copy with correct diacritics; tests distinguish copy from technical identifiers.

### F-008 — use the shared accessible dialog for Template status/detail flow

Root cause: `giao_an_mau.index.vue` hand-builds a `role="dialog"` container without accessible title/description association, focus trap, initial focus, Escape handling, or focus return.

Repair logic:

- Replace the hand-built dialog with the existing `FE/src/components/dung_chung/hop_thoai_xac_nhan.vue`; do not modify that shared primitive unless a new independently proven defect requires separate authorization.
- Supply a unique dialog id, title, description, pending state, labels with diacritics, and named `xuLy...` handlers. Reuse the primitive's `aria-modal`, `aria-labelledby`, `aria-describedby`, initial cancel focus, Tab/Shift+Tab trap, Escape close, and trigger-focus restoration.
- Combine this with F-005 so errors and reconciliation guidance remain inside the active accessible flow.

Focused tests: mount the real page without stubbing the shared primitive; open from its trigger; assert role/modal/name/description; assert initial focus; exercise Tab and Shift+Tab wrap; press Escape and assert close plus focus return; test pending blocks close/confirm; test outcome-unknown reconciliation semantics.

Acceptance: the Template flow has the same keyboard/focus lifecycle as the shared dialog and no custom dialog markup remains.

### Reviewer compiler warning — mandatory cleanup within this repair

This is review evidence attached to the open gate even though it has no separate finding ID. Replace component `v-model="form"` in both `bai_tap.tao_moi.vue` and `bai_tap.chi_tiet.vue` with explicit `:model-value` plus a named `xuLy...` update handler that merges the emitted relation value into the reactive object. Do not change `form` to a mutable `let` merely to silence the compiler. Focused and full test output must contain no compiler warning about a const reactive binding.

## 5. Implementation order

1. Fix M061 selector guards/echo and the two Exercise component bindings.
2. Implement separate stale draft/latest-base/comparison/reconciliation state.
3. Implement safe mutation outcome state machines for the four list dialogs and replace the Template custom dialog.
4. Rename handlers, add business docblocks, and correct visible Vietnamese copy across the 51-target boundary.
5. Replace all twelve page tests and extend router/menu/auth/component tests.
6. Apply scoped lint formatting to the 16 known FE3 Vue files, resolve template-shadow warnings, then run the final verification matrix.

## 6. Exact product repair allow-list — 51 paths

The product allow-list is exactly the original `task-fe3-all-targets.txt`; no target is added. The fixer may modify only these paths when required by F-001…F-008:

1. `FE/src/router/index.js`
2. `FE/src/router/index.test.js`
3. `FE/src/router/dieu_huong_admin.js`
4. `FE/src/router/dieu_huong_admin.test.js`
5. `FE/src/stores/xac_thuc.store.js`
6. `FE/src/stores/xac_thuc.store.test.js`
7. `FE/src/assets/main.css`
8. `FE/src/stores/danh_muc.store.js`
9. `FE/src/stores/danh_muc.store.test.js`
10. `FE/src/services/goi_tap.api.js`
11. `FE/src/services/goi_tap.api.test.js`
12. `FE/src/services/dung_cu.api.js`
13. `FE/src/services/dung_cu.api.test.js`
14. `FE/src/services/nhom_co.api.js`
15. `FE/src/services/nhom_co.api.test.js`
16. `FE/src/services/bai_tap.api.js`
17. `FE/src/services/bai_tap.api.test.js`
18. `FE/src/services/giao_an_mau.api.js`
19. `FE/src/services/giao_an_mau.api.test.js`
20. `FE/src/components/danh_muc/bo_sua_quyen_loi_goi_tap.vue`
21. `FE/src/components/danh_muc/bo_sua_quyen_loi_goi_tap.test.js`
22. `FE/src/components/danh_muc/bo_chon_quan_he_bai_tap.vue`
23. `FE/src/components/danh_muc/bo_chon_quan_he_bai_tap.test.js`
24. `FE/src/components/danh_muc/cay_giao_an.vue`
25. `FE/src/components/danh_muc/cay_giao_an.test.js`
26. `FE/src/components/danh_muc/hop_thoai_xung_dot_giao_an.vue`
27. `FE/src/components/danh_muc/hop_thoai_xung_dot_giao_an.test.js`
28. `FE/src/pages/admin/goi_tap/goi_tap.index.vue`
29. `FE/src/pages/admin/goi_tap/goi_tap.index.test.js`
30. `FE/src/pages/admin/goi_tap/goi_tap.tao_moi.vue`
31. `FE/src/pages/admin/goi_tap/goi_tap.tao_moi.test.js`
32. `FE/src/pages/admin/goi_tap/goi_tap.chi_tiet.vue`
33. `FE/src/pages/admin/goi_tap/goi_tap.chi_tiet.test.js`
34. `FE/src/pages/admin/dung_cu/dung_cu.index.vue`
35. `FE/src/pages/admin/dung_cu/dung_cu.index.test.js`
36. `FE/src/pages/admin/nhom_co/nhom_co.index.vue`
37. `FE/src/pages/admin/nhom_co/nhom_co.index.test.js`
38. `FE/src/pages/admin/bai_tap/bai_tap.index.vue`
39. `FE/src/pages/admin/bai_tap/bai_tap.index.test.js`
40. `FE/src/pages/admin/bai_tap/bai_tap.tao_moi.vue`
41. `FE/src/pages/admin/bai_tap/bai_tap.tao_moi.test.js`
42. `FE/src/pages/admin/bai_tap/bai_tap.chi_tiet.vue`
43. `FE/src/pages/admin/bai_tap/bai_tap.chi_tiet.test.js`
44. `FE/src/pages/admin/giao_an_mau/giao_an_mau.index.vue`
45. `FE/src/pages/admin/giao_an_mau/giao_an_mau.index.test.js`
46. `FE/src/pages/admin/giao_an_mau/giao_an_mau.tao_moi.vue`
47. `FE/src/pages/admin/giao_an_mau/giao_an_mau.tao_moi.test.js`
48. `FE/src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.vue`
49. `FE/src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.test.js`
50. `FE/src/pages/admin/giao_an_mau/giao_an_mau.tao_phien_ban.vue`
51. `FE/src/pages/admin/giao_an_mau/giao_an_mau.tao_phien_ban.test.js`

The fixer may also append round-1 evidence to `E:\Fitness\.fitness-sdd\fe3-all\task-fe3-all-report.md`; that workflow report is separate from the 51-product-path count. No other `.fitness-sdd` artifact is writable by the fixer.

## 7. Verification and executable UI evidence

Run commands from `E:\Fitness\FE` after the last code change and record exact final counts/results:

1. Meaningful focused Vitest runs covering the relation selector, stale dialog/page, four mutation-dialog pages, all 12 mounted page files, router/menu/auth cleanup, store outcomeUnknown, and relevant service payload/error normalization.
2. Full `npm run test`. The final suite must include at least the existing 58 files / 604 tests and all new tests; 100% pass, no skips/only, and no Vue compiler warning.
3. Full `npm run lint`; required result is 0 warnings and 0 errors. Do not infer PASS from exit code alone.
4. `npm run build` after tests/lint; required success from the final tree.
5. `npm ls --depth=0`; required success and no dependency/lockfile delta.
6. Static scans: no catalog DELETE/destructive relation call; no FE4/payment scope; no hard-coded API root; no forbidden dependency/UI framework/external font addition; no secret/token logging; no `handleXxx`; no FE3 event handler without `xuLy...`; no filename-only `__file` page assertion; no skipped/only tests or TODO/FIXME; no `v-model="form"` const-binding pattern; UI labels with diacritics reviewed separately from identifiers/status codes.
7. `git diff --check`, staged diff empty, exact target-delta check, unexpected-path count zero, and byte/hash preservation of all pre-existing paths/hunks.

Executable browser strategy:

- If an existing local API/app and authenticated Admin session are available, run real browser smoke at about 1440, 768, and 390 CSS pixels. Verify all 12 routes and five menu entries; loading/empty/error/data; create/edit/status flows; 422; 401/403; stale 409; network ambiguity; dialog name/focus/Tab/Shift+Tab/Escape/focus return; horizontal overflow; and console errors. Do not mutate valuable production data; use the existing local development/test environment.
- If auth is unavailable, do not request credentials unless no deterministic alternative exists. Use the mounted Vitest harness with real page components, real Pinia/router integration, mocked service boundary, controlled current actor, and viewport changes at 390/768/1440. Assert route/auth/menu behavior, semantic DOM, responsive classes/regions, dialog keyboard/focus, and mutation/stale state transitions. Pair this with build output and a static CSS inspection for mobile one-column/table overflow rules. This is the approved deterministic component-level substitute; it adds no dependency, production auth bypass, or fake production API.

## 8. Exit gate and blockers

Round 1 is ready for one Luna Max fixer. Re-review must map and close F-001 through F-008 individually. PASS requires all eight findings resolved, the compiler warning absent, focused/full tests green, lint literally clean, build/dependency/static/preservation gates green, and executable browser or deterministic mounted evidence recorded from the final tree.

Current blocker: none. No material business decision is unresolved.

