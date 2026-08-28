# TEST ISOLATION REPORT

## 1. Problem

Backend tests were not safe to run against a populated MariaDB schema. `ModelImplementationTest` inserted fixed primary-key values (`id = 1` and `id = 2`) and asserted `findOrFail(1/2)`. When the test ran after the seeders, the first insert could fail with MariaDB error `1062 Duplicate entry '1' for key 'PRIMARY'`.

The PHPUnit defaults also forced `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:` even though the Feature tests explicitly exercise the MariaDB schema. `SeederImplementationTest` skipped when the database was not isolated, which could hide an unsafe configuration.

## 2. Root Cause

- `BE/tests/Feature/ModelImplementationTest.php` used explicit auto-increment primary keys in the relationship graph and hard-coded foreign-key values based on those IDs.
- Relationship assertions looked up fixed IDs instead of the IDs returned by the insert operation.
- The test configuration did not describe the real backend test dependency: the suite requires MariaDB/InnoDB and the already migrated CORE schema.
- Seeder assertions assumed that an external command had seeded the schema before the suite started.

A repository-wide search of `BE/tests` after the fix found no remaining explicit fixture `id => ...` inserts or `findOrFail(<literal>)` calls.

## 3. Changes

Only test infrastructure was changed:

1. `ModelImplementationTest::seedRepresentativeGraph()` now accepts a logical fixture alias separately from each row, omits `id` from every INSERT, resolves all fixture foreign-key aliases to the generated IDs, and returns the generated-ID map. Assertions use that map.
2. The model test keeps its existing transaction and rolls it back in `tearDown()`. It now requires a database matching `smart_fitness_*test`.
3. `SeederImplementationTest` now fails fast instead of skipping when the configured database is not an isolated `smart_fitness_*test` database. If a freshly migrated isolated schema has no exercise rows, the test provisions the normal `DatabaseSeeder` baseline in that schema.
4. `BE/phpunit.xml` now defaults Feature tests to `mysql` and `smart_fitness_test`; `DB_DATABASE` can still be overridden by the isolated-schema runner. No password, absolute path, or production database is configured.

No migration, model, seeder implementation, logical schema, API, authentication, or business workflow was changed.

## 4. Isolation Strategy

- MariaDB 10.4.32, InnoDB, was used for all verification. Laravel loaded the project session mode `STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION` (the same strict mode configured by the application). No global XAMPP setting was changed.
- Two disposable schemas were used: `smart_fitness_full_suite_test_20260829_f1d8` and `smart_fitness_empty_suite_test_20260829_c2a9`.
- Each schema was created separately, migrated with the existing M001–M060 files, and never used by the application development database.
- The first schema was seeded once before focused tests. The second started empty; the test suite provisioned its baseline itself, proving it does not depend on a prior test or command.
- The model relationship graph uses generated IDs and a local alias map. Its database transaction is rolled back after each test, so fixture rows cannot leak into another test.
- Seeder tests read the isolated baseline; the missing-media test uses its own transaction and rolls it back. The idempotency test verifies a second seed has zero row delta.

## 5. Test Results

Runtime evidence:

- PHP: 8.4.25 (`E:\Fitness\.tools\php\php.exe`)
- Laravel: 13.29.0
- MariaDB: 10.4.32-MariaDB, vendor `mariadb.org binary distribution`
- Engine: InnoDB
- Schema migrations: existing M001–M060, all completed successfully in both disposable schemas

Focused tests on `smart_fitness_full_suite_test_20260829_f1d8`:

- `ModelImplementationTest`: 5 tests, 558 assertions, PASS
- `SeederImplementationTest`: 9 tests, 63 assertions, PASS

Full-suite verification:

| Run | Schema state | Command | Result |
|---|---|---|---|
| 1 | Seeded isolated schema | `php artisan test` | 17 tests, 625 assertions, PASS |
| 2 | Same schema, immediate repeat | `php artisan test` | 17 tests, 625 assertions, PASS |
| 3 | Same schema, randomized order seed 20260829 | `php artisan test --order-by=random --random-order-seed=20260829` | 17 tests, 625 assertions, PASS |
| 4 | Fresh migrated schema; suite self-seeded | `php artisan test` | 17 tests, 625 assertions, PASS |
| 5 | Fresh schema, immediate repeat | `php artisan test` | 17 tests, 625 assertions, PASS |
| 6 | Fresh schema, randomized order seed 20260829 | `php artisan test --order-by=random --random-order-seed=20260829` | 17 tests, 625 assertions, PASS |

`git diff --check` also passed.

## 6. Order Independence

- **Model → Seeder:** PASS. On the fresh `smart_fitness_empty_suite_test_20260829_c2a9` schema, the normal suite ran with the model graph before Seeder tests; all 17 tests passed and the Seeder test provisioned its own baseline.
- **Seeder → Model:** PASS. On `smart_fitness_order_test_20260829_d3b7`, `SeederImplementationTest` (9 tests, 63 assertions) ran first and `ModelImplementationTest` (5 tests, 558 assertions) ran second; both passed.
- The suite also passed twice consecutively on each isolated schema and with PHPUnit randomized order. No order-dependent failure was observed.

## 7. Development Database Safety

The `smart_fitness` database was never migrated, seeded, updated, deleted, truncated, or used by PHPUnit. Read-only counts before and after verification were unchanged, including: `chi_nhanh=1`, `vai_tro=4`, `nguoi_dung=8`, `ho_so_hoi_vien=4`, `ho_so_huan_luyen_vien=2`, `dung_cu=28`, `nhom_co=50`, `bai_tap=1324`, `bai_tap_dung_cu=1324`, and `bai_tap_nhom_co=3903`; operational/history tables remained empty.

The tests do not disable foreign-key checks and do not run `migrate:fresh` or destructive statements against any non-test schema. Existing development exercise media and `.tmp/exercises-dataset` were preserved.

## 8. Test Schema Cleanup

Both disposable schemas were dropped with an exact-name `DROP DATABASE IF EXISTS` command after all tests completed. A follow-up `information_schema.schemata` query returned zero rows for each schema: `CLEANUP_PASS`.

Temporary PHP inspection scripts were removed from `.tmp`. No test-only media was created; the suite reused the existing ignored exercise media required by the seeder tests.

## 9. Files Changed

Changes made for this task:

- `BE/phpunit.xml`
- `BE/tests/Feature/ModelImplementationTest.php`
- `BE/tests/Feature/SeederImplementationTest.php`
- `docs/thiet_ke_co_so_du_lieu/TEST_ISOLATION_REPORT.md`

The working tree also contains uncommitted seeder and dataset files from the preceding seeder task; they were preserved and not modified as part of this isolation fix. No migration PHP file, `PROJECT_RULES.md`, database design, data dictionary, ERD, Model, Controller, Service, FE, or Mobile file was changed.

## 10. Final Gate

All required isolation checks passed: model fixture isolation, seeder isolation, generated-ID usage, transaction rollback, two consecutive full-suite runs, randomized order, development-database safety, and disposable-schema cleanup.

MODEL TEST ISOLATION = PASS

SEEDER TEST ISOLATION = PASS

NO HARDCODED AUTO-INCREMENT FIXTURE COLLISION = PASS

TEST ORDER INDEPENDENCE = PASS

FULL BACKEND TEST SUITE = PASS

TEST DATABASE CLEANUP = PASS

smart_fitness = UNCHANGED

TEST ISOLATION = PASS

BACKEND TEST SUITE = STABLE

DATABASE = READY FOR AUTHENTICATION IMPLEMENTATION

Authentication and other business functionality were not started in this task.
