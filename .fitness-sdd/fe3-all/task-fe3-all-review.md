# FE3-ALL Task Review

VERDICT: FAIL

SPEC: FAIL

QUALITY: FAIL

## Findings

### F-001 — Important — M061 inactive relations can be removed or have their role changed

- File: `E:\Fitness\FE\src\components\danh_muc\bo_chon_quan_he_bai_tap.vue`
- Line: 76
- Rule: `domains/workout.md` M061; `PROJECT_RULES.md` M061; FE3-ALL checklist B requires exact inactive-relation echo, an error on any removal or role change, and no delete path.
- Evidence: An inactive relation that is already selected leaves its checkbox enabled because the disabled expression applies only when the inactive item is not selected. Its role selector is also enabled unless the whole component is disabled. The UI therefore lets an editor remove the relation or change its role, producing a request the Backend must reject. The component test covers only active groups and does not exercise the inactive-relation contract.
- Smallest fix: Lock both the checkbox and role selector for every existing inactive pivot while continuing to submit its unchanged identifier and role; add tests for exact echo, attempted removal, and attempted role change.

### F-002 — Important — Stale template recovery overwrites the draft instead of preserving and reconciling it

- File: `E:\Fitness\FE\src\pages\admin\giao_an_mau\giao_an_mau.tao_phien_ban.vue`
- Line: 18
- Rule: `domains/workout.md` template COW/stale-draft rule; `FE3_CONTEXT.md`; FE3-ALL checklist B requires a 409 flow that keeps the draft, reloads and compares the latest base, and requires explicit reconciliation.
- Evidence: The stale dialog emits `tai-lai` into `tai()`, which fetches the current template and passes it to `nap()`. `nap()` replaces all form metadata, workout days, and `expected_content_version`. No separate draft snapshot or comparison remains, so selecting “Tai ban moi” destroys the content the rule requires the user to preserve and reconcile.
- Smallest fix: Keep the submitted draft in separate state, load the latest base into separate comparison state, show the differences, and update the submission base only after an explicit user reconciliation action; cover this flow with a mounted page test.

### F-003 — Important — The 12 screen tests do not test any screen behavior

- File: `E:\Fitness\FE\src\pages\admin\goi_tap\goi_tap.index.test.js`
- Line: 3
- Rule: `engineering/testing-definition-of-done.md`; `FE3_CONTEXT.md`; FE3-ALL checklist F requires relevant loading, empty, error, success, validation, authorization, stale, double-submit, network-ambiguity, responsive, and keyboard coverage.
- Evidence: This file, and each of the other eleven FE3 page test files, contains only an assertion against `Component.__file`. They never mount a screen or exercise a store/API interaction. Component and service tests do not replace the missing page-flow coverage, the retained router test does not assert FE3 routes/menu order, and no authenticated browser smoke was available. The suite can therefore pass while the functional failures in F-001, F-002, F-005, and F-008 remain undetected.
- Smallest fix: Replace the filename assertions with mounted screen tests for the required states and critical interactions on all 12 screens, add FE3 router/menu ordering and access tests, and execute authenticated browser smoke at the required viewport sizes.

### F-004 — Important — The lint quality gate is not clean

- File: `E:\Fitness\FE\src\components\danh_muc\bo_chon_quan_he_bai_tap.vue`
- Line: 63
- Rule: `engineering/testing-definition-of-done.md` requires lint to be clean; FE3-ALL checklist F and the implementation brief require lint/build/test gates.
- Evidence: The independent `npm run lint` run exited 0 but reported `1170 problems (0 errors, 1170 warnings)`, including warnings in FE3-owned files beginning at this component. Of those warnings, 814 were reported as potentially fixable. A zero process exit does not satisfy the project’s clean-lint definition of done.
- Smallest fix: Correct the warnings in the FE3 target boundary, run the repository-approved formatter/linter remediation for inherited warnings only when ownership is established, and rerun lint until it reports no warnings or errors.

### F-005 — Important — Mutation dialogs close and reload without surfacing failure or ambiguous outcome

- File: `E:\Fitness\FE\src\pages\admin\goi_tap\goi_tap.index.vue`
- Line: 30
- Rule: `engineering/security-integrity.md`; `frontend/vue-core.md`; FE3-ALL checklist D requires correct error normalization and network-ambiguity handling without implying success or inviting unsafe retry.
- Evidence: `xacNhanDoiTrangThai()` awaits the store mutation, ignores its result and error state, reloads the list, and closes the dialog unconditionally. The same pattern exists in the equipment, muscle-group, and template list mutation flows. Store failures, including `outcomeUnknown`, are therefore hidden from the user and the closed dialog implies completion even when the outcome is unknown.
- Smallest fix: Close the dialog only after confirmed success; render normalized mutation errors and `outcomeUnknown` inside the active flow, preserve the user’s context, and offer safe reconciliation/refetch guidance without an automatic retry.

### F-006 — Important — FE event-handler naming and business-flow documentation rules are not followed

- File: `E:\Fitness\FE\src\pages\admin\goi_tap\goi_tap.index.vue`
- Line: 24
- Rule: `engineering/coding-conventions.md`; `PROJECT_RULES.md` RULE CODE 06; `docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md` FE conventions require `xuLy...` event handlers and meaningful docblocks for important business functions.
- Evidence: FE3 event handlers are named `moChiTiet`, `moDoiTrangThai`, `dongDoiTrangThai`, `xacNhanDoiTrangThai`, `luu`, `huy`, `chonItem`, `apDung`, and similar names across the 12 screens rather than the required `xuLy...` prefix. Important mutation and stale-conflict functions also lack the required business docblocks.
- Smallest fix: Rename FE3 UI handlers to the prescribed `xuLy...` convention and add concise docblocks to the mutation, authorization-sensitive, and stale-reconciliation functions.

### F-007 — Important — Visible FE3 copy omits required Vietnamese diacritics

- File: `E:\Fitness\FE\src\pages\admin\goi_tap\goi_tap.index.vue`
- Line: 42
- Rule: `docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md` UI instruction 15 requires Vietnamese labels with diacritics; `frontend/visual-ui.md` requires clear, professional Vietnamese interface copy.
- Evidence: Representative visible strings include `Goi tap`, `Tao moi`, and `Tai lai`; the same accentless copy is pervasive across the FE3 screens and navigation. The accentless convention applies to code identifiers, not user-facing Vietnamese text.
- Smallest fix: Replace user-visible FE3 labels, messages, headings, and actions with correct Vietnamese diacritics while retaining accentless camelCase identifiers in code; update semantic-copy assertions and snapshots.

### F-008 — Important — The template detail dialog lacks the required dialog accessibility behavior

- File: `E:\Fitness\FE\src\pages\admin\giao_an_mau\giao_an_mau.index.vue`
- Line: 24
- Rule: `frontend/visual-ui.md`; FE3-ALL checklist E requires semantic dialog behavior, labels, keyboard use, focus trap, initial focus, Escape close, and focus return.
- Evidence: The template-detail flow hand-builds a `div` with `role="dialog"` rather than using the shared dialog primitive. It has no accessible-name association, modal semantics, focus trap, initial-focus handling, Escape handling, or trigger-focus restoration. The filename-only page test provides no keyboard or focus coverage.
- Smallest fix: Render the detail flow through the shared accessible dialog primitive, associate its title and description, implement the required keyboard/focus lifecycle, and add mounted keyboard/focus tests.

## Test and review evidence

- Independent full Frontend test run: PASS — 58 files and 604 tests passed. The compiler also warned that a `v-model` attempted to update a const reactive binding named `form`; this warning is additional cleanup evidence but is not counted as a separate finding.
- Independent Frontend lint run: FAIL against the project DoD — 1,170 warnings, 0 errors, exit code 0; 814 warnings were reported as potentially fixable.
- Writer final-state build evidence inspected: PASS (`vite build`). The build was not rerun because this reviewer’s only authorized write is this review artifact and a build rewrites `dist`.
- Writer dependency evidence inspected: PASS (`npm ls --depth=0`), with no new dependency declared in the FE3 delta.
- Browser smoke: NOT RUN because the writer had no authenticated session. This is material because the twelve page tests do not provide equivalent screen, responsive, keyboard, or focus coverage.
- Boundary/preservation package independently inspected: exact target count 51; 49 changed targets and two intentionally unchanged targets (`router/index.test.js` and `stores/xac_thuc.store.test.js`); zero unexpected paths; staged diff empty; all 666 pre-existing preservation paths checked; before manifest contained 7 existing and 44 absent target paths, while the current manifest contains all 51. `git diff --check` produced no findings.
- Backend repair PASS evidence and the relevant Backend routes, request validation, catalog service, and API contract were inspected. The Backend protects M061, template metadata PATCH, content COW, resource authorization, and no-hard-delete boundaries; the FE findings above do not authorize a Backend or database change.
- Canonical reread trigger: concrete conflicts were found between the implementation and the layered rules for M061, stale-draft handling, handler naming/docblocks, and visible Vietnamese copy. Only the relevant `PROJECT_RULES.md` M061/RULE CODE 06 passages and `docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md` FE3/conventions passages were consulted. They resolve the conflicts in favor of exact inactive-pivot echo, draft-preserving explicit reconciliation, `xuLy...` handlers with important-function docblocks, and Vietnamese UI labels with diacritics; no broader canonical reread was needed.

## Residual risks

- Authenticated role/resource-boundary behavior, 401/403 transitions, actual menu visibility, keyboard focus behavior, and responsive layouts at 390/768/1440 remain unverified in a real browser.
- The full unit suite passing does not reduce the identified UI-flow risk because the FE3 page tests do not execute the pages.
- Boundary and preservation evidence is strong and passed; no preservation, hard-delete, FE4, dependency, or unexpected-path violation was found.

FE3_TASK_GATE: FAIL

FINAL_REVIEW_PERMITTED: NO
