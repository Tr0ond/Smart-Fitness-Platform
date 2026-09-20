# FE3-ALL entry-gate Backend repair brief — round 1

## Status

- `REPAIR_PLAN_STATUS`: `READY_FOR_IMPLEMENTATION_AND_VERIFICATION`
- `NEEDS_USER_DECISION`: `NO`
- The Muscle Group business contract is fully decided and implementable. No additional product choice is required.
- Required runtime is verified: `E:\Fitness\.tools\php\php.exe` reports PHP 8.4.25, boots Laravel 13.29.0, exposes the expected Muscle Group routes, and passes Composer platform requirements. XAMPP supplies MariaDB only; never use its PHP 8.0 binary or system PHP.
- The repair may proceed now. Do not mark it PASS or re-enter FE3-ALL until implementation, tests, and independent review satisfy every exit gate.

## Writer mission

Implement one bounded Backend entry-gate repair for the existing consolidated FE3-ALL flow. Do not split FE3, implement FE pages, start FE4, or modify unrelated catalog behavior.

The binding behavior is:

- `nhom_co.trang_thai` has exactly `HOAT_DONG` and `NGUNG_SU_DUNG`; default/backfill is `HOAT_DONG`.
- Admin management list returns both states; DTOs include `status`.
- Deactivation/reactivation is PATCH only. No hard delete.
- Inactive groups cannot be used for any new Exercise relation.
- A group becoming inactive does not delete or rewrite existing `bai_tap_nhom_co` or any catalog/history data.
- Existing inactive relations remain readable in Exercise DTOs, including status, and are immutable until reactivation: they cannot be added elsewhere, omitted/removed, or have role changed.
- Scalar/equipment-only Exercise updates do not touch Muscle Group pivots.
- Same-state PATCH is a true no-op and creates no duplicate audit.
- Actual status transition writes one before/after `nhat_ky_he_thong` row in the same transaction.

## Exact allow-list

Only these product paths may change:

```text
BE/database/migrations/2026_09_10_000061_m061_them_trang_thai_nhom_co.php
BE/app/Models/NhomCo.php
BE/app/Http/Requests/Admin/Catalog/CreateMuscleGroupRequest.php
BE/app/Http/Requests/Admin/Catalog/UpdateMuscleGroupRequest.php
BE/app/Services/Admin/ExerciseCatalogAdminService.php
BE/tests/Feature/AdminCatalogApiTest.php
BE/tests/Feature/AdminCatalogConcurrencyTest.php
BE/tests/Support/run_admin_catalog_action.php
PROJECT_RULES.md
docs/BACKEND_API_CONTRACT.md
docs/thiet_ke_co_so_du_lieu/TU_DIEN_DU_LIEU.md
docs/thiet_ke_co_so_du_lieu/THIET_KE_DATABASE.md
docs/thiet_ke_co_so_du_lieu/MIGRATION_PLAN.md
docs/thiet_ke_co_so_du_lieu/smart_fitness_erd.drawio
docs/thiet_ke_co_so_du_lieu/smart_fitness_erd_theo_mau.drawio
docs/thiet_ke_co_so_du_lieu/MUSCLE_GROUP_DEACTIVATION_REPORT.md
```

These 16 paths are the strict minimum: schema/model/request/service/API and concurrency implementation; the current API contract and completion evidence; and authoritative consistency. `PROJECT_RULES.md` must record the owner-approved rule, while its RULE CODE 04 requires the official data dictionary and both ERDs to agree on the new B26 column. `THIET_KE_DATABASE.md` and `MIGRATION_PLAN.md` receive only a dated M061 addendum, preserving historical M001-M060 evidence. Schema/model assertions stay in `AdminCatalogApiTest.php`, so `ModelImplementationTest.php` is not in scope.

Do not touch the pre-existing modified `docs/VUE_WEB_COMPLETION_CHECKPOINT.md`. Do not change routes/controller, Exercise requests, seeder, AI candidate engine, M001-M060, FE, Mobile, environment files, Composer files, or lockfiles.

## Implementation checklist

1. Add M061 with a binary-collated `VARCHAR(30) NOT NULL DEFAULT 'HOAT_DONG'`, named CHECK `kiem_tra_b26_01`, and a down path that drops only that CHECK/column. No new index/table/FK.
2. Add model fillable and create/update request enum normalization. Status is optional on create and patch; system/authority fields stay prohibited.
3. Add status to standalone and nested Exercise Muscle Group DTOs.
4. Patch service under existing retrying transactions and lock order: actor, Exercise when applicable, sorted Muscle Group IDs.
5. On actual status transition, write audit action `CAP_NHAT_TRANG_THAI_NHOM_CO`, target `NHOM_CO`, before/after state. Same state changes neither timestamp nor audit count.
6. Replace whole-set delete/recreate behavior with dimension-aware diff sync. Absent `muscle_groups` means zero B29 writes. Preserve unchanged rows. Reject add/remove/role-change involving inactive groups with `422 INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE` before all writes.
7. Keep route/controller as-is, then prove GET/POST/PATCH ADMIN middleware and absent DELETE during re-entry verification.
8. Sync the official rule/API/database docs and both ERDs. Add only a dated post-baseline M061 addendum to historical design/migration documents.
9. Produce the new 15-section completion report with only actually executed evidence.

## Mandatory tests

- API: default/explicit/invalid statuses, list both states, deactivate/reactivate, true no-op, atomic audit, no DELETE, 401/403 PATCH coverage, prohibited fields.
- Relations/history: inactive cannot be newly related; old pivot is readable and byte-for-byte preserved; scalar/equipment update does not touch it; remove/role-change fails atomically; reactivation restores eligibility; seeder rerun does not reactivate it.
- Schema/model: column/default/nullability/collation/CHECK/fillable and pre-existing-row backfill.
- Concurrency: two different ADMIN actors race name/status; deactivate races new relation. Only linearizable outcomes are accepted, with no partial Exercise/orphan and no duplicate audit.
- Regression: targeted classes, full Backend suite, Pint, route inspection, and git hygiene.

Run from `E:\Fitness\BE`, using exactly the verified project-portable binary:

```powershell
& 'E:\Fitness\.tools\php\php.exe' -v
& 'E:\Fitness\.tools\php\php.exe' artisan about
& 'E:\Fitness\.tools\php\php.exe' 'E:\Fitness\.tools\composer\composer.phar' check-platform-reqs
& 'E:\Fitness\.tools\php\php.exe' artisan route:list --path=api/admin/muscle-groups --json
& 'E:\Fitness\.tools\php\php.exe' artisan migrate:status --database=mysql
& 'E:\Fitness\.tools\php\php.exe' artisan test --filter=AdminCatalogApiTest
& 'E:\Fitness\.tools\php\php.exe' artisan test --filter=AdminCatalogConcurrencyTest
& 'E:\Fitness\.tools\php\php.exe' artisan test
& 'E:\Fitness\.tools\php\php.exe' vendor\bin\pint --test
```

M061 up/down/up is also mandatory on an explicitly disposable `smart_fitness_*test*` MariaDB schema with `APP_ENV=testing` and exact `DB_DATABASE == SMART_FITNESS_TEST_DATABASE`. Never use `smart_fitness`, SQLite, production/development data, system PHP, or real external services.

## Exit and controller re-entry

The repair exits only after exact allow-list validation, clean `git diff --check`, empty staged diff, guarded M061 up/down/up, all targeted/full tests and Pint passing under the mandated runtime, independent diff review, and source/docs/ERD agreement.

Then—and only then—the FE controller may update its checkpoint, rerun the FE3-ALL entry gate, and dispatch the one consolidated FE3-ALL writer. This Backend repair must not update that checkpoint itself.

## Risks/blockers

- No current hard blocker. Use of XAMPP/system PHP instead of the verified project portable runtime is a gate failure.
- M061 down loses status and is test/emergency-only.
- Whole-set pivot replacement is an identified destructive risk and must be removed for the Muscle Group dimension.
- Historical M001-M060 reports must remain historical; document M061 as an addendum rather than falsifying prior evidence.
