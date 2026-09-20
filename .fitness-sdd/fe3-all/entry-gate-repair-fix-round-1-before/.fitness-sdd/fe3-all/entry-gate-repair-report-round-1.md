# FE3-ALL ENTRY-GATE BACKEND REPAIR REPORT — ROUND 1

MODULE DA HOAN THANH

1. Muc tieu module

Hoàn thành một repair nguyên tử cho Muscle Group entry-gate Backend: M061 status schema, Admin lifecycle API, audit/no-op, inactive-relation immutability, dimension-aware sync, deterministic locking, concurrency proof và tài liệu authoritative. Không tách FE3, không chạm FE/FE4 và không sửa checkpoint controller-owned.

2. File da tao

- `BE/database/migrations/2026_09_10_000061_m061_them_trang_thai_nhom_co.php`
- `docs/thiet_ke_co_so_du_lieu/MUSCLE_GROUP_DEACTIVATION_REPORT.md`
- `.fitness-sdd/fe3-all/entry-gate-repair-report-round-1.md`

3. File da sua

- `BE/app/Models/NhomCo.php`
- `BE/app/Http/Requests/Admin/Catalog/CreateMuscleGroupRequest.php`
- `BE/app/Http/Requests/Admin/Catalog/UpdateMuscleGroupRequest.php`
- `BE/app/Services/Admin/ExerciseCatalogAdminService.php`
- `BE/tests/Feature/AdminCatalogApiTest.php`
- `BE/tests/Feature/AdminCatalogConcurrencyTest.php`
- `BE/tests/Support/run_admin_catalog_action.php`
- `PROJECT_RULES.md`
- `docs/BACKEND_API_CONTRACT.md`
- `docs/thiet_ke_co_so_du_lieu/TU_DIEN_DU_LIEU.md`
- `docs/thiet_ke_co_so_du_lieu/THIET_KE_DATABASE.md`
- `docs/thiet_ke_co_so_du_lieu/MIGRATION_PLAN.md`
- `docs/thiet_ke_co_so_du_lieu/smart_fitness_erd.drawio`
- `docs/thiet_ke_co_so_du_lieu/smart_fitness_erd_theo_mau.drawio`

Existing `docs/VUE_WEB_COMPLETION_CHECKPOINT.md` modification was preserved and not edited by this repair.

4. Database lien quan

Only the proven disposable MariaDB schema `smart_fitness_test` was used. M061 adds B26 `trang_thai` with binary `utf8mb4_nopad_bin` collation, default `HOAT_DONG` and named `kiem_tra_b26_01`; no new table/FK/index and no B29/history rewrite.

5. API da tao/sua

Existing Admin catalog routes now expose status-aware Muscle Group behavior: `GET /api/admin/muscle-groups`, `POST /api/admin/muscle-groups`, and `PATCH /api/admin/muscle-groups/{muscleGroup}`. There remains no DELETE route. Exercise list/detail nested Muscle Group DTOs expose `status`.

6. Ham chinh

- `taoNhomCo`, `capNhatNhomCo` — persist normalized status, enforce Admin actor lock, implement no-op/transition audit.
- `taoBaiTap`, `capNhatBaiTap` — validate relation state under transaction and separate equipment/Muscle Group dimensions.
- `khoaVaXacThucNhomCo`, `xacThucThayTheNhomCo` — deterministic ID locks and inactive-relation checks before writes.
- `dongBoDungCu`, `dongBoNhomCo` — preserve unchanged pivot rows and modify only actual dimension diffs.
- `run_admin_catalog_action.php` — supports `muscle_group` process action for the real-process race tests.

7. Business Rule da xu ly

Persisted states are exactly `HOAT_DONG` and `NGUNG_SU_DUNG`. Admin list includes both; status PATCH is the only deactivate/reactivate operation. New Exercise relations require active groups. Existing inactive B29 relations remain readable and byte-for-byte stable, must be resubmitted unchanged during replacement, and become mutable only after reactivation. Omitted relation dimensions are untouched. No hard-delete or historical rewrite is introduced.

8. Authorization

GET/POST/PATCH remain behind `auth:api` and `role:ADMIN`; service mutations lock and re-check the active ADMIN actor. Branch/actor/system fields are not client authority. Route inspection confirmed the controller remains the existing thin delegate.

9. Validation

Create/update requests trim and uppercase status before exact enum validation. IDs, code, timestamps, branch and actor/authority fields are prohibited. Unknown relation IDs fail `INVALID_MUSCLE_GROUP`; inactive new/omitted/removed/role-changed relation fails `INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE` atomically.

10. Transaction / Idempotency

Retrying transactions protect every multi-step catalog mutation. Lock order is actor, Exercise when applicable, then sorted referenced group IDs. A real status transition creates exactly one same-transaction audit with before/after, actor, target, UUID correlation and UTC timestamps. A normalized same-state PATCH is a true no-op without timestamp/audit duplication.

11. Error Case

The repair preserves 401/403 middleware behavior, 404 missing Muscle Group, 409 duplicate code, 422 request/prohibited/inactive-relation errors and 405 absent DELETE. Failed inactive replacement is validated before scalar/equipment/pivot writes, so no partial Exercise mutation remains.

12. Test Case

The focused API class covers status lifecycle, DTOs, audit, no-op, relation/history preservation, dimension omission/diff, reactivation, seeder rerun, schema metadata/CHECK and authority fields. The concurrency class covers two distinct Admin actors racing name/status and deactivation racing a new relation; process helper supports the Muscle Group action. Route, syntax, XML and migration rollback/re-up checks were also run.

13. Test Result

All commands used the project portable `E:\Fitness\.tools\php\php.exe` (PHP 8.4.25), with DB proof `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_DATABASE=smart_fitness_test`, `DATABASE()=smart_fitness_test`, exact `SMART_FITNESS_TEST_DATABASE=smart_fitness_test`.

| Command/check | Observed result |
| --- | --- |
| `& 'E:\\Fitness\\.tools\\php\\php.exe' artisan about` | PASS — Laravel 13.29.0, testing, mysql |
| `& 'E:\Fitness\.tools\php\php.exe' 'E:\Fitness\.tools\composer\composer.phar' check-platform-reqs` | PASS |
| `& 'E:\Fitness\.tools\php\php.exe' artisan route:list --path=api/admin/muscle-groups --json` | PASS — GET/POST/PATCH with `auth:api` + `role:ADMIN`; no DELETE |
| Guarded `artisan migrate --database=mysql --force`; `artisan migrate:rollback --database=mysql --step=1 --force`; `artisan migrate --database=mysql --force` + metadata/count probe | PASS — B26/B29 stayed 50/3903; final type/default/collation/ordinal/CHECK exact |
| `& 'E:\Fitness\.tools\php\php.exe' artisan test --filter=AdminCatalogApiTest` | PASS — 9 tests, 213 assertions |
| `& 'E:\Fitness\.tools\php\php.exe' artisan test --filter=AdminCatalogConcurrencyTest` | PASS — 3 tests, 50 assertions |
| `& 'E:\Fitness\.tools\php\php.exe' artisan test` after final migration up | PASS — 327 tests, 3,888 assertions |
| `& 'E:\Fitness\.tools\php\php.exe' vendor\bin\pint --test` | PASS |
| PHP lint, Drawio XML parse, `git diff --check` | PASS; staged diff empty |

14. Phan chua hoan thanh

Implementation and required local gates are complete. The parent/controller must still perform its independent diff review and, only after accepting this evidence, update the controller-owned checkpoint and re-enter the consolidated FE3-ALL gate. This writer did not perform those external handoff mutations.

15. Rui ro con lai

M061 down discards status and is restricted to disposable test schemas. Legacy clients must filter inactive list rows before new selections. Any future writer touching B29 must retain the same inactive-relation and no-hard-delete invariants. No development/production schema was selected or mutated.
