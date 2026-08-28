# DEVELOPMENT DATABASE DEPLOYMENT REPORT

## 1. Connection Decision

| Item | Result |
|---|---|
| Laravel `DB_CONNECTION` used | `mysql` |
| Laravel driver path | PDO MySQL (`pdo_mysql`, client API `mysqlnd 8.4.25`) |
| Actual database server | MariaDB 10.4.32 (`mariadb.org binary distribution`) |
| Storage engine | InnoDB |
| Host / port | `127.0.0.1:3306` |
| PHP | 8.4.25 (`.tools/php/php.exe`) |
| Laravel | Framework 13.29.0 |
| SESSION `sql_mode` | `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION` (MariaDB canonicalized order) |
| Global `sql_mode` | Unchanged: `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION` |
| Compatibility result | PASS |

The server was not changed to Oracle MySQL. `BE/config/database.php` keeps `utf8mb4` / `utf8mb4_unicode_ci` and the exact five SESSION modes for the `mysql` connection. `.env` already contained `DB_CONNECTION=mysql` and `DB_DATABASE=smart_fitness`; no secret was changed or printed.

## 2. MySQL Connection Verification (Phase A)

Test schema: `smart_fitness_mysql_verify_20260829` (isolated, created only for this deployment test; `utf8mb4_unicode_ci`).

The Laravel connection `mysql` returned:

```text
DATABASE(): smart_fitness_mysql_verify_20260829
VERSION(): 10.4.32-MariaDB
version_comment: mariadb.org binary distribution
SESSION sql_mode: STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION
character_set_connection: utf8mb4
collation_connection: utf8mb4_unicode_ci
```

Migration command used in Phase A:

```text
php artisan migrate
```

No `--database=mariadb`, `--force`, `migrate:fresh`, `migrate:refresh`, or foreign-key bypass was used.

| Phase A check | Result |
|---|---|
| M001–M060 up | PASS |
| `migrate:rollback --step=60` | PASS; CORE table count became 0 |
| M001–M060 re-up | PASS |
| Final metadata | PASS |
| Test schema cleanup | PASS |

## 3. Phase A Final Metadata

Metadata was read from `information_schema` and `SHOW CREATE TABLE` after the re-up:

| Item | Expected | Actual |
|---|---:|---:|
| CORE tables | 52 | 52 |
| Columns | 575 | 575 |
| Primary keys | 52 | 52 |
| Simple FKs | 113 | 113 |
| Composite FKs | 31 | 31 |
| Total FKs | 144 | 144 |
| UNIQUE outside PK | 98 | 98 |
| Query-index declarations | 48 | 48 |
| VIRTUAL generated columns | 5 | 5 |
| Named business CHECK | 83 | 83 |
| MariaDB JSON_VALID checks | 15 | 15 |
| Technical `utf8mb4_nopad_bin` overrides | 95 | 95 |
| Email `nguoi_dung.thu_dien_tu` | `utf8mb4_nopad_bin` | PASS |
| FK actions | RESTRICT / RESTRICT | PASS |

All seven critical tables (`dang_ky_goi_tap`, `ky_han_hoi_vien`, `ke_hoach_tap`, `phien_ban_ke_hoach_tap`, `buoi_tap_du_kien`, `tin_nhan_tro_ly`, `yeu_cau_tro_ly`) matched their approved generated/CHECK/UNIQUE/FK/collation structures. The raw `nopad_bin` count is 96 because the 95 manifest overrides and the separately specified email column are both included.

## 4. Development Database Before Deployment (Phase B)

| Item | Result |
|---|---|
| Database | `smart_fitness` |
| Already existed | YES |
| Created manually | YES (existing Navicat database) |
| Charset | `utf8mb4` |
| Collation | `utf8mb4_unicode_ci` |
| Tables before deployment | 0 |
| Existing migrations table before deployment | NO |
| Existing business data | NONE |
| Safe to migrate | YES |

The database was inspected read-only before deployment. It was not recreated, dropped, truncated, or refreshed.

## 5. Development Migration (Phase C)

Command used:

```text
php artisan migrate
```

Connection: `DB_CONNECTION=mysql`, database `smart_fitness`.

M001–M060: **PASS**. Laravel reported every migration from M001 through M060 as completed. The `migrations` table contains 60 rows. No test or seed data was inserted into `smart_fitness`.

## 6. Final Metadata — `smart_fitness`

The final database metadata is:

| Item | Actual |
|---|---:|
| CORE tables | 52 |
| Columns | 575 |
| Primary keys | 52 |
| Simple FKs | 113 |
| Composite FKs | 31 |
| Total FKs | 144 |
| UNIQUE outside PK | 98 |
| Query-index declarations | 48 |
| VIRTUAL generated columns | 5 |
| Named business CHECK | 83 |
| MariaDB JSON_VALID checks | 15 |
| Technical `utf8mb4_nopad_bin` overrides | 95 |
| `nguoi_dung.thu_dien_tu` collation | `utf8mb4_nopad_bin` |
| FK actions | `ON DELETE RESTRICT`, `ON UPDATE RESTRICT` |

A read-only row check found all 52 CORE tables empty. Only the Laravel `migrations` repository contains its 60 migration records.

## 7. Data and Scope

- Seeded: **NO**.
- Business data: **NONE**.
- Exercise dataset: **NOT imported**.
- Models: **NOT created**.
- Authentication: **NOT implemented**.
- Membership, payment, QR, Workout, PT, Chat, AI: **NOT implemented**.
- `smart_fitness` was not rolled back after successful deployment.

## 8. Cleanup

The Phase A schema `smart_fitness_mysql_verify_20260829` was dropped after up/rollback/re-up and metadata verification. A follow-up query confirmed it no longer exists. `smart_fitness` was retained with its 52 empty CORE tables and 60 migration records.

## 9. Files Changed

This deployment task changed no migration, schema-design, rule, Q01–Q13, Model, Seeder, Factory, Controller, Service, API, FE, Mobile, or `.env` file. The report was added:

- `docs/thiet_ke_co_so_du_lieu/DEVELOPMENT_DATABASE_DEPLOYMENT_REPORT.md`

The working tree also contains the existing implementation set from the earlier migration task: `BE/config/database.php` and the uncommitted M001–M060 files. They were not modified in this deployment review.

## 10. Final Gate

```text
MYSQL CONNECTION COMPATIBILITY = PASS
DEVELOPMENT DATABASE DEPLOYMENT = PASS
DB_CONNECTION = mysql
ACTUAL DBMS = MariaDB 10.4.32
smart_fitness = MIGRATED
M001-M060 = APPLIED
DATABASE METADATA = VERIFIED
SEED DATA = NOT YET CREATED
MODELS = NOT YET CREATED
AUTH = NOT YET IMPLEMENTED
DATABASE = READY FOR MODEL IMPLEMENTATION
```

## 11. Recommended Next Step

Begin the next explicitly approved development task with Models and database-backed tests. Do not seed or implement business workflows until that scope is requested.
