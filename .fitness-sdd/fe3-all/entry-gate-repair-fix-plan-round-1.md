# FE3-ALL ENTRY-GATE BACKEND REPAIR FIX PLAN — ROUND 1

## 1. Planning status

- `FIX_PLAN_STATUS: READY_FOR_FIX_IMPLEMENTATION_AND_VERIFICATION`
- `NEEDS_USER_DECISION: NO`
- This plan repairs only `FE3-BE-R1-001` and the audit-rollback residual test requested by the round-1 reviewer.
- It does not reopen or split the FE3 catalog scope, start FE3 Frontend, or authorize FE4.

## 2. Binding authority and preserved state

The fix remains governed by `AGENTS.md`, `PROJECT_RULES.md` (especially M061 at line 1182 and its test rule), `BE/AGENTS.md`, the FE3-ALL plan/brief, the original repair plan/brief, and `entry-gate-repair-review-round-1.md`. The accepted round-1 implementation is the starting state, not work to redo.

The controller verified before this plan that:

- branch is `main` and staged diff is empty;
- `git diff --check` has no whitespace error (only the already-recorded Drawio line-ending warnings);
- all 16 original repair targets match `entry-gate-repair-round-1-current-manifest.json` and all 16 before snapshots match `entry-gate-repair-round-1-snapshot-manifest.json`;
- the pre-existing controller-owned `docs/VUE_WEB_COMPLETION_CHECKPOINT.md` remains outside this fix and must not be edited;
- the original plan, brief, before/current snapshots, exact delta, review package, failed review, reports, and checkpoint history are preservation evidence and must not be overwritten.

## 3. Finding map

### FE3-BE-R1-001 — forbidden B29 creation from dataset seeder

Root cause:

- `BE/database/seeders/ExerciseDatasetSeeder.php:209-218` calls `BaiTapNhomCo::firstOrCreate()` for every dataset relation.
- `upsertMuscle()` at lines 243-248 intentionally does not assign `trang_thai`, so an existing seeded group can remain `NGUNG_SU_DUNG`; however, the later relation loop neither locks/re-reads that group nor checks its status before inserting a missing pivot.
- Therefore, if an expected dataset pivot is absent while the seeded group is inactive, a rerun creates a new B29 relation forbidden by M061. The relation insert is also outside the row-lock serialization point used by deactivation.
- The existing test at `BE/tests/Feature/AdminCatalogApiTest.php:340-341` reruns seeding only against a custom inactive group not referenced by the dataset and asserts only status, so it cannot expose the defect.
- An unchanged existing `NhomCo` is currently always saved by `upsertMuscle()`. Eloquent timestamps such updates; a regression that advances the clock would therefore observe an otherwise idempotent rerun touching the inactive group's `ngay_cap_nhat`. The fix must avoid that no-op save while retaining real dataset name/description updates.

Required production behavior:

1. Preserve an already-existing `(bai_tap_id, nhom_co_id)` B29 row byte-for-byte: no role rewrite and no timestamp rewrite, regardless of the referenced group's current status.
2. Only for a missing target pivot, acquire `lockForUpdate()` on the target `NhomCo`, re-read the persisted row, and insert only when `trang_thai === 'HOAT_DONG'`.
3. If the locked group is `NGUNG_SU_DUNG`, skip the missing relation without reactivating the group, throwing away existing relations, or changing unrelated records. This is an idempotent catalog import, not an Admin API validation response.
4. Keep the existing outer seeder transaction. The group row lock is the serialization point shared with `ExerciseCatalogAdminService::capNhatNhomCo()`: relation-first may commit before deactivation, while deactivation-first makes the later seeder lock observe inactive and skip. No state may commit as "inactive plus relation newly created after deactivation".
5. In `upsertMuscle()`, save a new row or a row whose dataset-owned name/description is actually dirty; do not save an unchanged existing row merely to obtain its ID. Never assign or normalize an existing row's status in the seeder.

Implementation shape (behavioral pseudocode, not a required identifier choice):

```text
if target pivot exists:
    continue without update

lockedGroup = re-read target group with lockForUpdate
if lockedGroup is absent or lockedGroup.status != HOAT_DONG:
    continue without insert

firstOrCreate target pivot using the existing role-on-create behavior
```

The final `firstOrCreate` remains useful for idempotency after the locked status decision; it must not be reached for an inactive group.

### FE3-BE-R1-001-RESIDUAL-AUDIT-ROLLBACK — executable rollback proof

The reviewer found a test-evidence gap, not a demonstrated production defect. `ExerciseCatalogAdminService::capNhatNhomCo()` already updates `nhom_co` and creates `NhatKyHeThong` inside the same `DB::transaction` (`ExerciseCatalogAdminService.php:112-156`). No service or audit-model source change is planned.

Add a focused test-only Eloquent `creating` listener for `NhatKyHeThong` that throws a controlled `RuntimeException` during `CAP_NHAT_TRANG_THAI_NHOM_CO`. Call `ExerciseCatalogAdminService::capNhatNhomCo()` directly, observe the controlled failure, then assert the Muscle Group's persisted status and timestamps equal the before snapshot and the matching audit count did not increase. This repository already uses the same event-listener fault-injection pattern in other feature tests. A database trigger is unnecessary and would introduce test-schema DDL/implicit-commit cleanup risk.

If this focused test disproves the current source-level transaction assumption, stop: do not expand the allow-list or edit the service without a revised fix plan.

## 4. Exact writer allow-list

The implementation writer may create or modify exactly these four paths, with no wildcard:

1. `E:\Fitness\BE\database\seeders\ExerciseDatasetSeeder.php`
2. `E:\Fitness\BE\tests\Feature\AdminCatalogApiTest.php`
3. `E:\Fitness\docs\thiet_ke_co_so_du_lieu\MUSCLE_GROUP_DEACTIVATION_REPORT.md`
4. `E:\Fitness\.fitness-sdd\fe3-all\entry-gate-repair-report-round-1.md`

Explicit exclusions include `ExerciseCatalogAdminService.php`, `NhatKyHeThong.php`, migrations, requests, models, concurrency test/helper, authoritative rule/API/database design documents, Drawio files, `docs/VUE_WEB_COMPLETION_CHECKPOINT.md`, every original snapshot/review artifact, all FE source, and all FE4 source.

## 5. Focused regression design

### A. Seeder missing-pivot regression

Add a dedicated test in `AdminCatalogApiTest.php`; do not weaken the existing lifecycle test.

1. Use the guarded, already-seeded `smart_fitness_test` database and select a deterministic seeded Muscle Group with at least two existing B29 rows.
2. Deactivate that seeded group through the existing Admin PATCH path so the normal lock/audit behavior is exercised.
3. Save the full group row and a full B29 row that will remain present. Remove a different expected seeded pivot inside the test's enclosing rollback transaction.
4. Advance the test clock before the rerun so accidental timestamp touching cannot hide behind equal timestamps.
5. Invoke only `ExerciseDatasetSeeder` (`db:seed --class=Database\\Seeders\\ExerciseDatasetSeeder --force`, or the exact equivalent using the class constant); do not rerun unrelated seeders.
6. Assert the deliberately absent `(bai_tap_id, nhom_co_id)` still does not exist.
7. Assert the preserved B29 row is exactly equal to its before snapshot, including ID, role, `ngay_tao`, and `ngay_cap_nhat`.
8. Assert the inactive group's `trang_thai`, `ngay_tao`, and `ngay_cap_nhat` are exactly unchanged.

This test must fail against the current unconditional relation loop and pass only after the missing-pivot status guard is effective.

### B. Audit-write failure regression

Add a separate focused test in `AdminCatalogApiTest.php`:

1. Create an active group and an Admin actor using existing helpers; snapshot the full persisted row and matching audit count.
2. Advance the clock.
3. Register a test-only listener on `eloquent.creating: App\\Models\\NhatKyHeThong` that throws only for `CAP_NHAT_TRANG_THAI_NHOM_CO`.
4. Call `app(ExerciseCatalogAdminService::class)->capNhatNhomCo(...)` for a real transition and assert the controlled exception.
5. Assert the group row, specifically `trang_thai`, `ngay_tao`, and `ngay_cap_nhat`, equals the before snapshot; assert no matching audit row was committed.

Do not add a production dependency seam merely for this test and do not create a MariaDB trigger.

## 6. Report repair

Update both allowed reports without deleting their round-1 historical evidence:

- add `ExerciseDatasetSeeder.php` to the actual changed-file inventory;
- identify `FE3-BE-R1-001` as repaired and describe the missing-pivot lock/status behavior;
- record the two new focused regressions, including audit-failure rollback;
- replace any current completion claim that seeder rerun coverage was sufficient with the stronger absent-pivot evidence;
- append the commands and results actually observed after the final implementation state; do not retain stale test counts as if they were the new run;
- keep the PHẦN XXI structure in `MUSCLE_GROUP_DEACTIVATION_REPORT.md` and keep controller review/checkpoint work under unfinished handoff until independent review passes.

## 7. Safe execution and verification sequence

Before any PHP/DB command, prove all of the following in the same process context:

- executable is exactly `E:\Fitness\.tools\php\php.exe`;
- `APP_ENV=testing`;
- `DB_CONNECTION=mysql`;
- configured `DB_DATABASE`, `DATABASE()`, and `SMART_FITNESS_TEST_DATABASE` are exactly `smart_fitness_test` and satisfy `TestDatabaseGuard`.

Then run, from `E:\Fitness\BE`, using only the portable executable:

1. PHP syntax lint for the changed seeder and test.
2. Focused PHPUnit method/filter for the missing-pivot regression.
3. Focused PHPUnit method/filter for the audit-failure rollback regression.
4. `artisan test --filter=AdminCatalogApiTest`.
5. `artisan test --filter=AdminCatalogConcurrencyTest` to ensure the existing serialization proof remains green, even though that file is not edited.
6. Full `artisan test` after the final edit.
7. `vendor\bin\pint --test` after the final edit.
8. Repository `git diff --check`, staged-diff-empty check, and exact changed-path comparison to the four-path allow-list plus the controller's pre-existing changes/artifacts.

Do not run migration rollback, SQLite, system/XAMPP PHP, external calls, production/development DB commands, or any commit/push/branch/worktree/reset/restore/checkout/stash/clean operation.

## 8. Snapshot and independent review handoff

Before writer dispatch, the controller should capture exact before bytes/hashes for the four allow-list paths and current Git status/staged state. After the writer finishes, create new fix-round current copies, manifest, exact before/current delta, working/status/staged evidence, and a new review package. Do not overwrite the original round-1 before/current snapshots or failed review.

A fresh Sol High reviewer must inspect the live four-path delta and independently rerun at least the two focused tests plus `AdminCatalogApiTest`. The Backend repair gate remains failed, and FE3 entry-gate revalidation remains prohibited, until that reviewer records PASS. The controller alone owns any subsequent checkpoint update and FE3-ALL re-entry.

## 9. Acceptance criteria

- `FE3-BE-R1-001` is closed by source behavior and the absent-pivot regression.
- Existing inactive pivots and the inactive group's status/timestamps remain unchanged on an unchanged seeder rerun.
- A missing dataset pivot is not created for an inactive group.
- Missing-pivot creation for an active group remains supported and is serialized by the group row lock.
- Audit-write failure is injected without production source changes and demonstrably rolls back status/timestamp with no audit commit.
- Only the four allow-list paths are changed by the implementation writer; all prior round-1 work, history, checkpoint, snapshots, and reports remain preserved except the two explicitly allowed report updates.
- All required guarded focused/full/style/diff gates pass with actual results recorded.

## 10. Blockers and risks

- No current planning blocker and no user decision is required.
- Residual operational risk: the seeder holds row locks until its existing outer transaction completes; this is accepted for the narrow correctness repair and must not be redesigned in this round.
- M061 rollback remains forbidden outside an explicitly disposable guarded test schema and is not part of this repair.
- If test fixtures no longer contain a seeded group with two expected pivots, the test may derive two relations from the dataset fixture rather than hard-code IDs, but it must remain deterministic and must not broaden the file allow-list.
