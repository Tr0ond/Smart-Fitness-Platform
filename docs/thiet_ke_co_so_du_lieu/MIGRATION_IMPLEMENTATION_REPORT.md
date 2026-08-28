# MIGRATION IMPLEMENTATION REPORT

## 1. Scope and guardrails

This implementation creates only the official Laravel migrations M001–M060 described by `docs/thiet_ke_co_so_du_lieu/MIGRATION_PLAN.md`. No Model, Seeder, Factory, Controller, Service, API, authentication, business workflow, dataset, trigger, procedure, or extra table/column was added. The application database `smart_fitness` was not used or modified. All tests used the disposable schema `smart_fitness_migration_test_20260829`.

The logical schema, business rules, naming, nullability, keys, checks, generated expressions, and FK actions were taken from the approved manifest generated from `TU_DIEN_DU_LIEU.md`; no schema redesign was made.

## 2. Environment

| Item | Verified value |
|---|---|
| PHP | 8.4.25 CLI (`.tools/php/php.exe`) |
| PHP MySQL support | PDO, pdo_mysql, mysqlnd loaded; Composer platform requirements passed |
| Laravel | Framework 13.29.0 (composer.lock / `artisan --version`) |
| Database server | 10.4.32-MariaDB; version comment `mariadb.org binary distribution` |
| Storage engine | InnoDB (server default and all 52 CORE tables) |
| Global sql_mode | `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION` (unchanged) |
| Laravel SESSION sql_mode | `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION` |
| MariaDB canonical SESSION sql_mode | `STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION` |
| Server charset/collation | `utf8mb4` / `utf8mb4_general_ci` |
| Laravel connection charset/collation | `utf8mb4` / `utf8mb4_unicode_ci` |
| Probe connection charset/collation | `utf8mb4` / `utf8mb4_unicode_ci` |
| System/session timezone | `Asia/Bangkok` / server session default; no migration changed timezone |
| Test schema | `smart_fitness_migration_test_20260829` (isolated, disposable) |

`BE/config/database.php` now supplies the exact five-mode `modes` array for both `mysql` and `mariadb` connections. Laravel's connector applies it with `SET SESSION`; GLOBAL sql_mode was never changed.

## 3. Migration files created

The following 60 files were created under `BE/database/migrations/`, in lexical execution order:

| Range | Purpose | Count |
|---|---|---:|
| M001–M052 | Create the 52 CORE tables, including PK, UNIQUE, query indexes, generated VIRTUAL columns and named business CHECK constraints; no FK is attached in this phase | 52 |
| M053 | Attach account/role/profile FKs (B02–B11) | 12 |
| M054 | Attach package/payment/membership FKs (B12–B19) | 22 |
| M055 | Attach trainer/access FKs (B20–B24) | 28 |
| M056 | Attach exercise-library FKs (B25–B32) | 9 |
| M057 | Attach plan/workout/history FKs (B33–B40) | 28 |
| M058 | Attach PT conversation FKs (B41–B43) | 14 |
| M059 | Attach assistant/proposal FKs (B44–B50) | 29 |
| M060 | Attach audit/idempotency FKs (B51–B52) | 2 |

### Full file list

```text
2026_08_29_000001_m001_tao_chi_nhanh.php
2026_08_29_000002_m002_tao_nguoi_dung.php
2026_08_29_000003_m003_tao_vai_tro.php
2026_08_29_000004_m004_tao_phan_quyen_nguoi_dung.php
2026_08_29_000005_m005_tao_ho_so_hoi_vien.php
2026_08_29_000006_m006_tao_ho_so_huan_luyen_vien.php
2026_08_29_000007_m007_tao_ngay_ranh_hoi_vien.php
2026_08_29_000008_m008_tao_dung_cu_hoi_vien.php
2026_08_29_000009_m009_tao_chi_so_co_the.php
2026_08_29_000010_m010_tao_the_truy_cap.php
2026_08_29_000011_m011_tao_yeu_cau_dat_lai_mat_khau.php
2026_08_29_000012_m012_tao_goi_tap.php
2026_08_29_000013_m013_tao_quyen_loi_goi_tap.php
2026_08_29_000014_m014_tao_don_mua_goi.php
2026_08_29_000015_m015_tao_lan_thanh_toan.php
2026_08_29_000016_m016_tao_su_kien_thanh_toan.php
2026_08_29_000017_m017_tao_dang_ky_goi_tap.php
2026_08_29_000018_m018_tao_ky_han_hoi_vien.php
2026_08_29_000019_m019_tao_su_dung_quyen_loi.php
2026_08_29_000020_m020_tao_ma_vao_phong_tap.php
2026_08_29_000021_m021_tao_lich_su_vao_phong_tap.php
2026_08_29_000022_m022_tao_phan_cong_huan_luyen_vien.php
2026_08_29_000023_m023_tao_lich_su_su_dung_huan_luyen_vien.php
2026_08_29_000024_m024_tao_ghi_chu_huan_luyen.php
2026_08_29_000025_m025_tao_dung_cu.php
2026_08_29_000026_m026_tao_nhom_co.php
2026_08_29_000027_m027_tao_bai_tap.php
2026_08_29_000028_m028_tao_bai_tap_dung_cu.php
2026_08_29_000029_m029_tao_bai_tap_nhom_co.php
2026_08_29_000030_m030_tao_giao_an_mau.php
2026_08_29_000031_m031_tao_ngay_trong_giao_an.php
2026_08_29_000032_m032_tao_bai_tap_trong_giao_an.php
2026_08_29_000033_m033_tao_ke_hoach_tap.php
2026_08_29_000034_m034_tao_phien_ban_ke_hoach_tap.php
2026_08_29_000035_m035_tao_ngay_trong_ke_hoach.php
2026_08_29_000036_m036_tao_bai_tap_trong_ke_hoach.php
2026_08_29_000037_m037_tao_buoi_tap_du_kien.php
2026_08_29_000038_m038_tao_phien_tap.php
2026_08_29_000039_m039_tao_bai_tap_trong_phien.php
2026_08_29_000040_m040_tao_hiep_tap.php
2026_08_29_000041_m041_tao_hoi_thoai.php
2026_08_29_000042_m042_tao_tin_nhan.php
2026_08_29_000043_m043_tao_su_kien_phat_tin_nhan.php
2026_08_29_000044_m044_tao_hoi_thoai_tro_ly.php
2026_08_29_000045_m045_tao_tin_nhan_tro_ly.php
2026_08_29_000046_m046_tao_yeu_cau_tro_ly.php
2026_08_29_000047_m047_tao_bai_tap_ung_vien.php
2026_08_29_000048_m048_tao_giao_an_ung_vien.php
2026_08_29_000049_m049_tao_lan_goi_mo_hinh.php
2026_08_29_000050_m050_tao_de_xuat_ke_hoach_tap.php
2026_08_29_000051_m051_tao_nhat_ky_he_thong.php
2026_08_29_000052_m052_tao_yeu_cau_chong_lap.php
2026_08_29_000053_m053_them_khoa_ngoai_tai_khoan.php
2026_08_29_000054_m054_them_khoa_ngoai_goi_tap_thanh_toan.php
2026_08_29_000055_m055_them_khoa_ngoai_huan_luyen_va_vao_phong.php
2026_08_29_000056_m056_them_khoa_ngoai_thu_vien.php
2026_08_29_000057_m057_them_khoa_ngoai_ke_hoach_va_lich_su.php
2026_08_29_000058_m058_them_khoa_ngoai_tro_chuyen.php
2026_08_29_000059_m059_them_khoa_ngoai_tro_ly_va_de_xuat.php
2026_08_29_000060_m060_them_khoa_ngoai_nhat_ky_va_chong_lap.php
```


Every migration is a self-contained anonymous Laravel migration. M001–M052 use exact raw `DB::statement` CREATE TABLE DDL so MariaDB named CHECK and generated-column syntax is preserved. M053–M060 execute each exact ALTER TABLE statement separately, making partial DDL observable and reversible. Every FK uses `ON DELETE RESTRICT ON UPDATE RESTRICT`.

## 4. P1 result — CORE tables

**STATUS: PASS**

`php artisan migrate --database=mariadb --force` completed M001 through M052 without errors. Metadata after the second successful up matched the approved manifest:

| Manifest item | Expected | Actual | Result |
|---|---:|---:|---|
| CORE tables | 52 | 52 | PASS |
| Columns | 575 | 575 | PASS |
| Primary keys | 52 | 52 | PASS |
| UNIQUE constraints (outside PK) | 98 | 98 | PASS |
| Declared `chi_muc_*` indexes | 48 | 48 | PASS |
| Generated VIRTUAL columns | 5 | 5 | PASS |
| Named business CHECK constraints | 83 | 83 | PASS |
| MariaDB implicit JSON_VALID CHECKs | separate | 15 | informational |

The 15 JSON_VALID checks are MariaDB's physical representation of JSON validation and are not additional business checks. All tables report `ENGINE=InnoDB` and `utf8mb4_unicode_ci`.

## 5. P2 result — foreign keys

**STATUS: PASS**

Metadata comparison against the 144 names in the approved manifest passed exactly: 113 simple FKs plus 31 composite FKs. All 144 report `RESTRICT/RESTRICT` in `information_schema.referential_constraints`. No FK was added during M001–M052; all were attached by M053–M060 in the planned groups.

## 6. Critical schema evidence

`SHOW CREATE TABLE` and `information_schema` were inspected after re-up:

| Table | Generated | UNIQUE | Business CHECK | FK in SHOW CREATE | Engine/collation |
|---|---:|---:|---:|---:|---|
| `dang_ky_goi_tap` | 1 | 3 | 2 | 4 | InnoDB / utf8mb4_unicode_ci |
| `ky_han_hoi_vien` | 0 | 4 | 8 | 7 | InnoDB / utf8mb4_unicode_ci |
| `ke_hoach_tap` | 1 | 4 | 1 | 4 | InnoDB / utf8mb4_unicode_ci |
| `phien_ban_ke_hoach_tap` | 0 | 3 | 2 | 6 | InnoDB / utf8mb4_unicode_ci |
| `buoi_tap_du_kien` | 2 | 5 | 2 | 8 | InnoDB / utf8mb4_unicode_ci |
| `tin_nhan_tro_ly` | 0 | 3 | 2 | 3 | InnoDB / utf8mb4_unicode_ci |
| `yeu_cau_tro_ly` | 0 | 5 | 4 | 9 | InnoDB / utf8mb4_unicode_ci |

The five generated columns are VIRTUAL and their `CASE` expressions match the approved manifest. MariaDB exposed the expected generated expressions in `information_schema.columns`; direct client writes produced driver error 1906 and were rejected by the smoke test.

## 7. Up migration test

**MIGRATION UP TEST: PASS**

A fresh isolated schema was created, then Laravel ran all 60 files under the exact SESSION strict mode. `migrate:status` reported every M001–M060 as `Ran` in batch 1. No production schema was selected.

## 8. Physical negative smoke tests

All 13 tests passed under the same strict session. Each invalid statement was executed in a transaction and rolled back.

| Test | Expected protection | Actual MariaDB evidence |
|---|---|---|
| Invalid `chi_nhanh.trang_thai` | CHECK | 4025, `kiem_tra_b01_01` |
| Missing `nguoi_dung.chi_nhanh_id` | simple FK | 1452, `khoa_ngoai_don_b02_01` |
| Delete branch with child user | RESTRICT | 1451, `khoa_ngoai_don_b02_01` |
| String `'abc'` into BIGINT under strict mode | strict type validation | 1366 / SQLSTATE 22007 |
| Client writes B17 generated column | generated column protection | 1906, `hoi_vien_chua_ket_thuc_id` |
| Two unfinished B17 enrollments for one member | generated UNIQUE | 1062, `duy_nhat_b17_02` |
| B18 PT used > total | quota CHECK | 4025, `kiem_tra_b18_07` |
| B18 disabled AI with nonzero counter | quota CHECK | 4025, `kiem_tra_b18_08` |
| Two active B33 plans for one member | generated UNIQUE | 1062, `duy_nhat_b33_04` |
| B34 previous version from another plan | composite self-FK | 1452, `khoa_ngoai_ghep_b34_01` |
| B37 start time >= end time | schedule CHECK | 4025, `kiem_tra_b37_02` |
| Duplicate B45 conversation sequence | UNIQUE sequence | 1062, `duy_nhat_b45_01` |
| B46 quota state without period/usage refs | quota-state CHECK | 4025, `kiem_tra_b46_04` |

Database constraints cover the physical invariants above. Cross-record workflows, quota counter reconciliation with ledgers, Membership activation transactions, and authorization remain Backend responsibilities and were not implemented here.

## 9. Rollback test

**MIGRATION ROLLBACK TEST: PASS**

`php artisan migrate:rollback --database=mariadb --step=60 --force` completed M060 back through M001. The 52 CORE table count became **0**; only Laravel's empty `migrations` repository table remained. M053–M060 `down()` methods removed the exact FK names in reverse order before table drops.

## 10. Re-up test

**MIGRATION RE-UP TEST: PASS**

The same isolated schema was migrated a second time from the empty CORE state. All M001–M060 completed successfully. Post-re-up metadata again matched 52/575/52/144/98/48/5/83 and `migrations` contained 60 rows.

## 11. Strict-mode and MariaDB warnings

The exact configured mode is:

```text
STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION
```

MariaDB returns the same set in canonical order. The original technical preflight recorded nine non-fatal MariaDB warnings (codes 1901/1105) concerning indexing a generated `CHAR` expression and `PAD_CHAR_TO_FULL_LENGTH`. The generated columns remained VIRTUAL, nullable, UNIQUE, and behaviorally correct; no DDL error or metadata mismatch occurred. The warning is preserved as a deployment note and was not bypassed by changing the approved schema.

The metadata query counted 108 physical non-unique index names because InnoDB may add/support indexes for foreign keys. The approved query-index declaration count remains exactly 48; this physical implementation detail does not alter the logical manifest.

## 12. Laravel DDL findings

- Raw `DB::statement` preserves exact MariaDB generated-column and named CHECK expressions.
- Named UNIQUE and named query indexes are present in metadata.
- Named simple, composite, and self-referencing FKs are present with exact names and RESTRICT actions.
- Laravel 13.29.0 connects successfully using the MariaDB driver and applies the configured SESSION modes, charset and collation before migrations.
- No official migration file was placed outside `BE/database/migrations`.

## 13. Partial-DDL / retry safety

M053–M060 issue one ALTER statement per FK rather than hiding the group in one opaque script. If a later statement fails, the already-created constraint names can be inspected through `information_schema.referential_constraints` and `SHOW CREATE TABLE`; the corresponding `down()` drops exact names in reverse order. Laravel's migration repository records a migration only after its `up()` returns, so a partial group is not treated as fully applied. No partial failure was induced in the successful run; this is the implemented recovery behavior.

## 14. Cleanup and source impact

The isolated schema `smart_fitness_migration_test_20260829` was dropped after the re-up metadata and smoke evidence were captured. No `smart_fitness` data or schema was touched. Temporary probe scripts and JSON evidence remain under ignored `.tmp/` only.

Files in the implementation set are:

- `BE/config/database.php` — exact connection SESSION `modes` for `mysql` and `mariadb`.
- `BE/database/migrations/2026_08_29_000001_m001_tao_chi_nhanh.php` through `2026_08_29_000060_m060_them_khoa_ngoai_nhat_ky_va_chong_lap.php` — official migrations.
- This report.

For this additional-review task, the 60 migration files and `BE/config/database.php` were left unchanged; only this report was updated.

No PROJECT_RULES, database design, dictionary, ERD, Model, Seeder, Factory, Controller, Service, API, FE, Mobile, or production `.env` file was changed.

## Additional implementation verification

These checks were run after creating a fresh isolated MariaDB 10.4.32 schema, then running the existing M001–M060 migrations on that schema. The Laravel/MariaDB connection used the exact SESSION mode `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION`; MariaDB returned the same set in canonical order. The schema was `smart_fitness_migration_verify_20260829` and was dropped after the checks.

### 1. B37 duplicate effective schedule slot

**Expected:** Two `buoi_tap_du_kien` rows with the same `hoi_vien_id` and `ngay_tap`, both in a status that keeps the slot, must conflict on generated `ngay_tap_con_hieu_luc` / `duy_nhat_b37_05`. `HUY` and `DA_THAY_THE` must make both generated slot columns `NULL`, allowing a replacement slot.

**Actual:** The first `CHUA_TAP` row generated `ngay_tap_con_hieu_luc = 2026-09-01` and `ma_buoi_con_hieu_luc` equal to its logic UUID. The second effective row was rejected with SQLSTATE `23000`, MariaDB driver error `1062`, and constraint `duy_nhat_b37_05` (`Duplicate entry '1-2026-09-01'`). After changing the first row to `HUY`, both generated columns were `NULL` and a new effective row for the same member/date inserted successfully. After changing that row to `DA_THAY_THE`, both generated columns were again `NULL` and a further replacement row inserted successfully.

### 2. Invalid DATETIME(6)

**Expected:** Inserting `not-a-date` into an official `DATETIME(6)` column under strict SESSION mode must be rejected.

**Actual:** `chi_nhanh.ngay_tao` rejected the value with MariaDB driver error `1292`, SQLSTATE `22007` (`Incorrect datetime value`). No invalid row was stored.

### 3. Invalid JSON

**Expected:** Invalid JSON in an official JSON column must be rejected by MariaDB's physical JSON validation constraint. This must remain separate from the 83 named business CHECK constraints.

**Actual:** Inserting `{"bad":}` into `bai_tap.thong_tin_bo_sung` was rejected with driver error `4025`, SQLSTATE `23000`. `information_schema` and `SHOW CREATE TABLE` exposed the physical constraint name `thong_tin_bo_sung` with check clause `json_valid(thong_tin_bo_sung)`. This is one of the 15 MariaDB JSON_VALID checks and is not counted in the 83 business CHECK total.

### 4. Collation manifest

**Expected:** The 95-column override list in the approved `MIGRATION_PLAN.md` must match `information_schema.COLUMNS` exactly with `utf8mb4_nopad_bin`; `nguoi_dung.thu_dien_tu` is the separately specified email column and must use the same collation. All table defaults and business text columns use `utf8mb4_unicode_ci`.

**Actual:** The manifest parser found 95 expected override columns. Metadata found exactly 95 matching technical columns after excluding the separately specified email column; extra columns = none, missing columns = none, wrong collations = none. The raw `utf8mb4_nopad_bin` total is 96 because it correctly includes `nguoi_dung.thu_dien_tu` in addition to the 95 manifest overrides. The email column is `utf8mb4_nopad_bin`; all 52 CORE table defaults are `utf8mb4_unicode_ci`; no other UTF-8 collation was present outside the allowed `utf8mb4_bin` JSON columns.

### Additional verification cleanup

The test schema `smart_fitness_migration_verify_20260829` was dropped successfully. A follow-up metadata query found zero schemas with that name. `smart_fitness` was not migrated or modified. The machine-readable evidence is retained under ignored `.tmp/` only.

## 15. Final gate

All implementation tests and manifest checks passed:

```text
MIGRATION IMPLEMENTATION = PASS
DATABASE SCHEMA = READY FOR DEVELOPMENT DATABASE DEPLOYMENT
```

`smart_fitness` remains **NOT YET MIGRATED**. This report authorizes creating/using the migration set in a development database in a subsequent task; it does not seed data or implement business functionality.

