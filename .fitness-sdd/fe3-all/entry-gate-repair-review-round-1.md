VERDICT: FAIL
SPEC: FAIL
QUALITY: FAIL

FINDINGS

- ID: FE3-BE-R1-001
  Severity: Important
  File/line: E:\Fitness\BE\database\seeders\ExerciseDatasetSeeder.php:215
  Rule: E:\Fitness\PROJECT_RULES.md:1182 — Backend may create a new `bai_tap_nhom_co` relation only when the Muscle Group is `HOAT_DONG`; inactive existing relations remain readable and immutable.
  Evidence: `upsertMuscle()` deliberately preserves an existing group's state (`ExerciseDatasetSeeder.php:243-248`), but the later loop unconditionally calls `BaiTapNhomCo::firstOrCreate()` (`ExerciseDatasetSeeder.php:209-218`) without reading or locking `nhom_co.trang_thai`. Therefore a dataset record can create a missing B29 pivot to a group that is already `NGUNG_SU_DUNG`. A concrete reachable case is an inactive seeded group whose relation to one seeded/new exercise is absent: rerunning the seeder inserts that forbidden relation. The new API test only reruns the seeder and asserts that the group remains inactive (`E:\Fitness\BE\tests\Feature\AdminCatalogApiTest.php:340-341`); it never makes an expected pivot absent and proves that the pivot is not recreated, so all reported focused/full suites can pass while this invariant is violated. This also makes the completion claims in `E:\Fitness\docs\thiet_ke_co_so_du_lieu\MUSCLE_GROUP_DEACTIVATION_REPORT.md:53-59,81-107` incomplete. The issue is additionally concurrency-relevant because the seeder transaction does not lock the Muscle Group row before the pivot insert, so it is outside the service's deactivation-versus-relation serialization point.
  Smallest fix: amend the repair allow-list to include `ExerciseDatasetSeeder.php`; when a target pivot already exists, preserve it unchanged, but before creating a missing pivot lock/re-read the Muscle Group and create only if its state is `HOAT_DONG` (so the result serializes with deactivation). Add a regression that removes/omits one expected pivot for an inactive seeded group, reruns the seeder, and asserts that no new pivot is created and existing inactive pivots/status/timestamps remain unchanged. Update the two completion reports and regenerate the review snapshot after the fix.

TEST EVIDENCE

- Independently verified branch `main`, empty staged diff, `git diff --check` PASS (only CRLF conversion warnings for the two Drawio files), and current product status: exactly the 16 repair allow-list paths plus the pre-existing controller-owned `E:\Fitness\docs\VUE_WEB_COMPLETION_CHECKPOINT.md`; no unexpected product path.
- Verified SHA-256 evidence named by the package: before manifest `7F6555D4D5A6B1B6252E410FBC507A14DE157E987FEF139043F72C269E2DCD12`, current manifest `F9C320FC135AB24108466ED6FD778275E6511736E29B323BB437B4E7E4A7F3D5`, exact delta `E9C944AC588E98A8F364365033FE5A720D491E4E31B13B637FDD27F31BC7A856`, current working patch `28BE9A25B0ACBCDFACBF9EEDAC59F33BD8F601126A56AB0A12280D4364F7A5C1`; all 16 live targets match the current manifest and all 16 before snapshots match the before manifest.
- Verified the live checkpoint diff matches `entry-gate-repair-round-1-checkpoint-current.patch` exactly; no repair damage to controller history/checkpoint.
- Independently parsed both changed Drawio files as XML and inspected their delta; B26 status/default/CHECK notes are synchronized without unrelated-page regeneration.
- Runtime independently proved as `E:\Fitness\.tools\php\php.exe`, PHP 8.4.25. Both executed test classes invoke `TestDatabaseGuard`, which requires exact `APP_ENV=testing`, MySQL, and configured/current/approved database equality to `smart_fitness_test` before seeding or child-process work.
- `E:\Fitness\.tools\php\php.exe artisan test --filter=AdminCatalogApiTest`: PASS — 9 tests, 213 assertions.
- `E:\Fitness\.tools\php\php.exe artisan test --filter=AdminCatalogConcurrencyTest`: PASS — 3 tests, 50 assertions.
- No destructive rollback was run by this review. Writer-reported migration round-trip, full-suite 327/3,888 and Pint results were inspected as evidence but were not treated as authority or rerun.

RESIDUAL RISKS

- The focused status test proves a successful audit row and same-state no-op behavior, while same-transaction source placement supports rollback atomicity; it does not inject an audit-write failure. Add such a fault-injection assertion during the required repair to make the rollback claim executable rather than source-only.
- M061 `down()` necessarily discards persisted status and remains suitable only for an explicitly guarded disposable test schema, never routine development/production rollback.

BACKEND_REPAIR_GATE: FAIL
FE3_ENTRY_REVALIDATION_PERMITTED: NO
