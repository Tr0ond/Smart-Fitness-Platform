# FE3-ALL cumulative final review

`FINAL_VERDICT: FAIL`  
`FE3_PHASE_GATE: FAIL`  
`SPEC: FAIL`  
`QUALITY: FAIL`  
`OPEN_CRITICAL: 0`  
`OPEN_IMPORTANT: 3`  
`OPEN_MINOR: 1`  
`CHECKPOINT_UPDATE_PERMITTED: NO`  
`EXACT_NEXT_ACTION: FE3-ALL FINAL REPAIR WAVE 5 — close FINAL-F-001 through FINAL-F-004, add the missing regression coverage, rerun the full mandatory gates, then obtain a fresh independent final review. Do not start FE4-ALL.`

## Scope and review basis

This review inspected the cumulative final tree against the original 51-path FE3-ALL boundary, the Backend entry-gate repair and revalidation, all four Frontend repair waves, and the final-review package. The implementation and tests were read directly; report totals were used only as navigation aids. Relevant actual Backend requests/services/DTOs and the M061 migration/model/service/seeder were cross-checked against the Frontend services and screens.

The concrete ambiguity discovered in the workout-template UI triggered a targeted canonical lookup in `docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md`; lines 1001, 1020, 1464 and 1801 confirm the required initial full tree, tree editor, invalid/inactive-exercise coverage, and copy-on-write revision flow. No unrelated canonical sections were loaded.

Previously repaired findings F-001 through F-015 remain closed in the final tree, including the round-4 required-instructions validation and corrected template-detail lifecycle copy. Those closures do not cover the new final findings below.

## Open findings

### FINAL-F-001 — Important — The workout-template tree cannot display or edit the full exercise prescription

`FE/src/components/danh_muc/cay_giao_an.vue:44` creates every new exercise with hard-coded `rest_seconds: 60` and `notes: ''`, but the rendered exercise row only exposes the exercise selector and inputs for `target_sets`, `min_reps`, and `max_reps` at lines 108-153. There is no control or read-only rendering for `rest_seconds` or `notes` anywhere in the component.

This is an actual contract omission. `BE/app/Http/Requests/Admin/Catalog/CreateWorkoutTemplateRequest.php:32-39` and `CreateWorkoutTemplateRevisionRequest.php:35-42` define those fields in each full-tree item, while `BE/app/Services/Admin/WorkoutTemplateCatalogAdminService.php:347-353` returns them in detail. The FE service also serializes them at `FE/src/services/giao_an_mau.api.js:61-74`. Consequently an Admin cannot inspect these values in detail, cannot author a non-60-second rest period, and cannot create or revise exercise notes through the required tree editor. Existing service tests prove serialization of fixture values, but `FE/src/components/danh_muc/cay_giao_an.test.js:5-11` only adds a day and cannot detect the missing controls.

Required repair: render both fields in read-only detail and provide bounded `rest_seconds` plus nullable `notes` editing in create/revision flows; add component/page tests that change them and assert the emitted/submitted full tree.

### FINAL-F-002 — Important — Retired exercises disappear from historical template detail and the revision draft

The Backend detail DTO deliberately supplies `exercise_name` for every stored tree item at `BE/app/Services/Admin/WorkoutTemplateCatalogAdminService.php:339-353`. The Frontend never references `exercise_name` (`FE/src` has zero references). Instead, `cay_giao_an.vue:108-123` resolves the displayed exercise exclusively through `exerciseOptions`. Both consumers load only active exercises: `giao_an_mau.chi_tiet.vue:59` and `giao_an_mau.tao_phien_ban.vue:182` call `taiDanhSachBaiTap({ status: 'HOAT_DONG' })`.

When an exercise used by an existing template is retired, its ID has no matching option. The disabled detail tree therefore loses the exercise identity, and the copied revision draft does not tell the Admin which retired exercise must be replaced. This violates the required ordered historical tree rendering and the Q12/history-preservation behavior even though the Backend rows remain intact. No template component/page test covers a detail DTO whose stored exercise is absent from the active selector.

Required repair: preserve the DTO `exercise_name` in the rendered tree and merge retained inactive/current selections into display options without making them selectable for new relations. Add detail and revision tests for a retired exercise, including explicit replacement before a revision is submitted.

### FINAL-F-003 — Important — A valid nullable Exercise metadata value is silently rewritten

Exercise metadata is nullable in both actual requests (`BE/app/Http/Requests/Admin/Catalog/CreateExerciseRequest.php:33`, `UpdateExerciseRequest.php:24`), the database column is nullable (`BE/database/migrations/2026_08_29_000027_m027_tao_bai_tap.php:20`), and the detail DTO returns the stored value unchanged (`BE/app/Services/Admin/ExerciseCatalogAdminService.php:512-522`). The Frontend contract diverges in two places: `FE/src/services/bai_tap.api.js:79-81` rejects `metadata: null`, and `FE/src/pages/admin/bai_tap/bai_tap.chi_tiet.vue:37` coerces a returned `null` to `{}`. The normal metadata save always includes that coerced object at line 76.

Thus an Admin editing an unrelated field on a valid row with `metadata = null` silently changes the field to an empty JSON object. This is a DTO-mapping failure and an unintended mutation, and the nullable DTO regression tests only cover image/video paths.

Required repair: preserve `null` end to end, or omit metadata until the Admin actually changes it, while retaining object validation for non-null values. Add service and detail-page tests for a `null` DTO and an unrelated update.

### FINAL-F-004 — Minor — The package-create screen exposes a save button whose event has no consumer

`FE/src/components/danh_muc/bo_sua_quyen_loi_goi_tap.vue:182-189` renders a `type="button"` labelled `Lưu quyền lợi` and emits `luu` at line 101. The create page mounts that component at `FE/src/pages/admin/goi_tap/goi_tap.tao_moi.vue:179-182` without an `@luu` listener. Clicking the visible control does nothing; only the separate outer submit button at lines 201-205 creates the package. Existing create tests submit the form directly and do not exercise this dead action.

Required repair: hide the component-level save action in create mode or bind it to the create submission, then cover the visible-button behavior.

## Independent gate evidence

- Backend database guard: exact PHP binary `E:\Fitness\.tools\php\php.exe`, PHP 8.4.25, `APP_ENV=testing`, MySQL, and configured/current/approved database all resolved to `smart_fitness_test`.
- `artisan test --filter=AdminCatalogApiTest`: PASS, 11 tests / 232 assertions.
- `artisan test --filter=AdminCatalogConcurrencyTest`: PASS, 3 tests / 50 assertions.
- Full Backend `artisan test`: PASS, 329 tests / 3,907 assertions.
- Frontend `npm run test`: PASS, 58 files / 675 tests.
- Frontend `npm run lint`: PASS with exit 0 and no diagnostics.
- Frontend `npm run build`: PASS, Vite 8.2.2, 169 modules transformed.
- Frontend `npm ls --depth=0`: PASS with the expected dependency tree.
- `git diff --check`: PASS. Staged diff: empty.

Green execution gates do not override the source-proven missing behaviors, and the current tests do not exercise the four cases above.

## Boundary, repair-wave, and Backend-entry preservation

- The original before manifest contains exactly 51 authorized targets; all 51 exist in the final tree. Direct hash comparison to that original baseline finds 50 changed and one unchanged (`FE/src/stores/xac_thuc.store.test.js`). The additional router-test change is inside the original allow-list and belongs to an authorized repair wave.
- Round-4 preservation was independently recomputed from its 710-row manifest: 706 paths remain byte-identical; the only four changed product paths are the four authorized round-4 targets (`giao_an_mau.chi_tiet.vue`, its test, `bai_tap.api.js`, and its test); zero paths are missing or unexpected. Its five-row current manifest also matches the final files.
- The earlier repair-wave exact deltas, target manifests, controller gates, and final source were inspected together. No repair reverted the M061 behavior, prior FE3 fixes, or pre-existing dirty work.
- Current Backend source preserves the entry repair: inactive Muscle Group relations remain readable and immutable under the exact echo rule, lifecycle writes stay transactional/audited, and the independently rerun Admin catalog/API/concurrency/full suites are green.

## Residual limitations

No authenticated local Admin browser session was available, so no real-browser end-to-end smoke was executed. Mounted page/component tests, service tests, static contract tracing, and full build/lint were available. This limitation is not the reason for FAIL: FINAL-F-001 through FINAL-F-004 are directly established by the final source.

The FE3 phase cannot pass while FINAL-F-001, FINAL-F-002, and FINAL-F-003 remain open. The completion checkpoint must remain unchanged, and FE4-ALL is not permitted to start.
