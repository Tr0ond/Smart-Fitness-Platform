# FE3-ALL ENTRY-GATE BACKEND REPAIR FIX BRIEF — ROUND 1

## Status and scope

- `FIX_PLAN_STATUS: READY_FOR_FIX_IMPLEMENTATION_AND_VERIFICATION`
- `NEEDS_USER_DECISION: NO`
- Map only `FE3-BE-R1-001` and `FE3-BE-R1-001-RESIDUAL-AUDIT-ROLLBACK`.
- Preserve the accepted round-1 Muscle Group implementation. Do not start FE3 Frontend or FE4 and do not edit the controller-owned checkpoint/history/snapshots/review.

## Root cause to exact logic

`BE/database/seeders/ExerciseDatasetSeeder.php:209-218` unconditionally `firstOrCreate()`s dataset B29 relations. `upsertMuscle()` preserves an existing group's status, so an inactive seeded group can reach this loop; the seeder does not lock/re-read its status before recreating an absent pivot. The existing API test reruns seeding against a non-dataset custom inactive group and therefore misses the reachable violation.

Repair the relation loop as follows:

1. If the target pivot already exists, leave the complete row unchanged.
2. If it is missing, lock and re-read the target `NhomCo` inside the existing seeder transaction.
3. Create only when the locked status is exactly `HOAT_DONG`; skip when inactive.
4. Retain create-time role semantics and idempotency.
5. In `upsertMuscle()`, do not save an unchanged existing group, so an unchanged rerun cannot touch inactive group timestamps; still save new rows and real dataset-owned name/description changes, and never assign status.

The group lock serializes the missing-pivot decision with Admin deactivation. Existing pivots require no status gate because M061 explicitly preserves them.

## Audit residual decision

Do not modify `ExerciseCatalogAdminService.php` or audit production source. `capNhatNhomCo()` already saves status/timestamp and creates `NhatKyHeThong` within one transaction. Prove it with a test-only Eloquent `creating` listener for `NhatKyHeThong` that throws a controlled exception on the Muscle Group status action. Call the service directly and assert the group status/timestamps and matching audit count equal their before values. This avoids MariaDB trigger DDL and production seams. If the test fails, stop and request a revised plan rather than editing outside the allow-list.

## Exact writer allow-list

1. `E:\Fitness\BE\database\seeders\ExerciseDatasetSeeder.php`
2. `E:\Fitness\BE\tests\Feature\AdminCatalogApiTest.php`
3. `E:\Fitness\docs\thiet_ke_co_so_du_lieu\MUSCLE_GROUP_DEACTIVATION_REPORT.md`
4. `E:\Fitness\.fitness-sdd\fe3-all\entry-gate-repair-report-round-1.md`

No wildcard and no other source, test, documentation, checkpoint, snapshot, or review path is authorized.

## Focused regressions

### Inactive seeded group rerun

- Select a deterministic seeded group with at least two expected B29 rows.
- Deactivate it through the Admin API.
- Snapshot the full group row and one retained pivot; delete a different expected pivot inside the test rollback transaction.
- Advance the clock, rerun only `ExerciseDatasetSeeder`, and assert:
  - the missing composite relation remains absent;
  - the retained pivot is byte-for-byte equal, including role and timestamps;
  - group status, creation timestamp, and update timestamp are unchanged.

### Audit failure rollback

- Snapshot an active group and relevant audit count.
- Advance the clock and inject a controlled `NhatKyHeThong` create failure through the Eloquent event listener.
- Invoke `ExerciseCatalogAdminService::capNhatNhomCo()` for a real transition.
- Assert the expected failure, unchanged persisted status/timestamps, and no committed audit increment.

Do not delete or weaken existing lifecycle, authorization, schema, or concurrency coverage.

## Reports and acceptance evidence

Update both allowed completion reports, preserving prior observed history while marking the failed review and repair follow-up accurately. Add the seeder to the changed-file list, map `FE3-BE-R1-001` to the final logic/tests, include audit fault-injection evidence, and record only commands/results actually run on the final state.

Use only `E:\Fitness\.tools\php\php.exe` and the guarded MariaDB `smart_fitness_test` database after proving `APP_ENV=testing`, MySQL, and exact configured/current/approved DB-name equality. Required evidence is:

- syntax lint for both changed PHP files;
- each new focused regression PASS;
- `AdminCatalogApiTest` PASS;
- existing `AdminCatalogConcurrencyTest` PASS;
- full Backend suite PASS;
- Pint check PASS;
- `git diff --check` PASS, staged diff empty, and writer changes limited to the four exact paths.

No migration rollback, system/XAMPP PHP, SQLite, production/development DB, external calls, or Git history/worktree mutation is allowed.

After implementation, the controller must generate a new fix-round before/current manifest and exact delta without overwriting round-1 evidence, then dispatch a fresh independent review. FE3 entry-gate revalidation is not permitted until that review passes.

## Blockers and risks

- `NEEDS_USER_DECISION: NO`.
- No current blocker.
- The existing outer seeder transaction will retain acquired group locks until completion; accept this narrow behavior for correctness and do not redesign transaction boundaries in this fix.
