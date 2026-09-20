# FE3-ALL entry-gate Backend repair plan — round 1

## Planning verdict

- `REPAIR_PLAN_STATUS`: `READY_FOR_IMPLEMENTATION_AND_VERIFICATION`
- Business decision status: complete. The owner-approved contract is sufficient to implement without another product decision.
- Writer dispatch status: the exact implementation below is bounded and may be dispatched as one Backend repair. FE3-ALL may re-enter only after implementation, tests, and independent review pass.
- Runtime evidence: the authoritative project CLI is `E:\Fitness\.tools\php\php.exe`; direct verification reports PHP 8.4.25, boots Laravel Framework 13.29.0, returns the expected Muscle Group routes, and passes Composer platform requirements. XAMPP supplies MariaDB only; its PHP 8.0 binary and system PHP must not be used.
- `NEEDS_USER_DECISION`: `NO`. No business, runtime, or authority decision remains.

## Evidence and scope boundary

The planner read the complete applicable rule set and FE3 control artifacts before inspecting code: root and Backend `AGENTS.md`, all of `PROJECT_RULES.md`, `docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md`, `docs/VUE_WEB_COMPLETION_CHECKPOINT.md`, `docs/BACKEND_API_CONTRACT.md`, and all existing `.fitness-sdd/fe3-all` artifacts. No deeper `AGENTS.md` exists under the Backend paths in scope.

Current source evidence:

- `nhom_co` is created by M026 with no `trang_thai` column or CHECK.
- `NhomCo`, `CreateMuscleGroupRequest`, `UpdateMuscleGroupRequest`, service mapping, and DTO omit status.
- Admin routes already expose authenticated ADMIN-only `GET`, `POST`, and `PATCH` endpoints and expose no `DELETE`; the controller already delegates atomically to `ExerciseCatalogAdminService`.
- `AdminCatalogApiTest` creates a group but has no list/edit/deactivate contract and omits Muscle Group `PATCH` from the auth matrix.
- Existing exercise relation validation proves existence only; it does not reject an inactive group.
- Existing relation replacement deletes and recreates pivot rows. That is incompatible with the newly approved requirement that an existing inactive relation remain untouched/readable.
- Existing AI candidate logic reads exercise-muscle relations without filtering the group. This is correct for history/readability and must remain unchanged.
- Current worktree is `main`; `docs/VUE_WEB_COMPLETION_CHECKPOINT.md` already has an unrelated/owner-controller modification and `.fitness-sdd/` is untracked. Preserve both. Staged diff is empty and `git diff --check` is clean.

This is one entry-gate Backend repair inside the single consolidated `FE3-ALL` task. Do not split it into package/equipment/muscle/exercise/template tasks. Do not implement FE3 UI, do not start FE4, and do not modify FE product source.

## Binding contract

### Actor and use cases

- Actor: authenticated, active user with a current `ADMIN` role. Muscle Group is global catalog data and has no branch ownership column; therefore branch scoping is not invented.
- Admin can list all Muscle Groups, including inactive groups, so old catalog rows and existing relations remain inspectable.
- Admin can create a Muscle Group with optional status; omitted status defaults to `HOAT_DONG`.
- Admin can patch name, description, and/or status. Code and authority/system fields remain immutable/prohibited.
- Admin deactivates by PATCHing `status: NGUNG_SU_DUNG`; there is no hard-delete API.
- Admin may reactivate by PATCHing `status: HOAT_DONG`.

### State and selection semantics

- Allowed persisted/API states are exactly `HOAT_DONG` and `NGUNG_SU_DUNG`.
- A group in `NGUNG_SU_DUNG` is excluded from every new Exercise-to-Muscle-Group selection.
- Backend validation is authoritative: callers cannot create a new pivot to an inactive group even if a stale/malicious client submits its ID.
- Deactivation never updates/deletes `bai_tap_nhom_co`, `bai_tap`, template, plan/version, workout session/history, AI request/candidate, or other historical rows.
- Existing Exercise-to-Muscle-Group relations to an inactive group remain returned by Exercise list/detail, including the group's status, and their existing pivot row ID/timestamps/role remain unchanged.
- When an Exercise relation payload is submitted after a related group became inactive, that inactive relation must be supplied unchanged. Adding it to another exercise, changing its `CHINH/PHU` role, or omitting/removing it through full-replacement semantics fails atomically with `422 INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE`.
- Updating only Exercise scalar fields or only equipment must not rewrite Muscle Group pivots.
- Once reactivated, the group becomes eligible for new relations and ordinary relation changes again.

### API and failure contract

- `GET /api/admin/muscle-groups`: `200`, includes active and inactive rows in deterministic existing code order. Each DTO includes `id`, `code`, `name`, `description`, `status`.
- `POST /api/admin/muscle-groups`: `201`; accepts optional `status`; omission yields `HOAT_DONG`.
- `PATCH /api/admin/muscle-groups/{muscleGroup}`: `200`; accepts partial `name`, `description`, `status`; identical normalized target state is a successful no-op.
- `DELETE /api/admin/muscle-groups/{id}`: remains absent/`405`.
- Unauthenticated: `401`; authenticated non-ADMIN or revoked/inactive ADMIN: `403`.
- Missing target: existing `404 MUSCLE_GROUP_NOT_FOUND`.
- Duplicate code: existing `409 MUSCLE_GROUP_CONFLICT`.
- Invalid status or prohibited `id`, `code`, timestamps, actor/branch fields: `422` FormRequest failure with no write.
- Unknown relation ID: existing `422 INVALID_MUSCLE_GROUP`.
- New/changed/removed inactive relation: `422 INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE` with the entire Exercise transaction rolled back.

## Exact product allow-list

No product file outside this list may change. Existing modifications must be preserved; particularly, the Backend writer must not touch `docs/VUE_WEB_COMPLETION_CHECKPOINT.md`.

### Backend implementation and tests

1. `BE/database/migrations/2026_09_10_000061_m061_them_trang_thai_nhom_co.php` — new additive M061 only.
2. `BE/app/Models/NhomCo.php`
3. `BE/app/Http/Requests/Admin/Catalog/CreateMuscleGroupRequest.php`
4. `BE/app/Http/Requests/Admin/Catalog/UpdateMuscleGroupRequest.php`
5. `BE/app/Services/Admin/ExerciseCatalogAdminService.php`
6. `BE/tests/Feature/AdminCatalogApiTest.php`
7. `BE/tests/Feature/AdminCatalogConcurrencyTest.php`
8. `BE/tests/Support/run_admin_catalog_action.php`

### Authoritative/current documentation

9. `PROJECT_RULES.md`
10. `docs/BACKEND_API_CONTRACT.md`
11. `docs/thiet_ke_co_so_du_lieu/TU_DIEN_DU_LIEU.md`
12. `docs/thiet_ke_co_so_du_lieu/THIET_KE_DATABASE.md`
13. `docs/thiet_ke_co_so_du_lieu/MIGRATION_PLAN.md`
14. `docs/thiet_ke_co_so_du_lieu/smart_fitness_erd.drawio`
15. `docs/thiet_ke_co_so_du_lieu/smart_fitness_erd_theo_mau.drawio`
16. `docs/thiet_ke_co_so_du_lieu/MUSCLE_GROUP_DEACTIVATION_REPORT.md` — new completion report using the 15-section format in Part XXI of `PROJECT_RULES.md`.

All 16 paths are necessary. The schema/model/request/service/API and concurrency paths implement and prove the contract. `PROJECT_RULES.md` must record the owner-approved business change under the root `AGENTS.md` consistency rule. The data dictionary is the official source of the new column name, and RULE CODE 04 explicitly requires it to agree with both ERDs. `THIET_KE_DATABASE.md` and `MIGRATION_PLAN.md` require a narrow dated post-baseline addendum so their baseline counts are not mistaken for current M061 state. The API contract and Part XXI completion report are the current consumer and evidence documents. `ModelImplementationTest.php` is deliberately excluded: its schema assertions fit in `AdminCatalogApiTest.php`, so opening a third test class is unnecessary.

The two `.fitness-sdd/fe3-all/entry-gate-repair-*.md` files are workflow artifacts, not writer product scope.

### Explicitly excluded/no-change files

- `BE/routes/api.php` and `BE/app/Http/Controllers/Api/Admin/ExerciseCatalogController.php`: no implementation change. Reinspect and prove the existing GET/POST/PATCH + middleware contract after the repair.
- `CreateExerciseRequest.php` and `UpdateExerciseRequest.php`: shape validation remains; active/immutable relation semantics require locked Database state and belong in the service transaction.
- `ExerciseDatasetSeeder.php`: do not add a write that reactivates existing rows. The DB default covers newly inserted rows; rerunning the current `firstOrNew` seeder must preserve a previously inactive group.
- `AiCandidateRuleEngine.php`: do not filter historical/current relations by group status. Exercise eligibility remains governed by `bai_tap.trang_thai`; inactive group metadata remains readable.
- Existing M001-M060 files, FE source, Mobile source, `.env*`, Composer files, package locks, checkpoint/controller artifacts, and all files outside the exact allow-list.

## Implementation instructions

### 1. M061 migration, default/backfill, and rollback safety

- Add `nhom_co.trang_thai VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL DEFAULT 'HOAT_DONG'` after `mo_ta`.
- Add named CHECK `kiem_tra_b26_01` limiting values exactly to `HOAT_DONG`, `NGUNG_SU_DUNG`.
- Use explicit MariaDB DDL consistent with existing named constraints. Do not mutate M026.
- The NOT NULL default must make the additive migration backfill every pre-M061 row to `HOAT_DONG`. The service must still write the default explicitly for normal API creates; the DB default protects seeder/legacy inserts.
- Do not add an index: the management endpoint returns both states, relationship validation locks by primary key, and B26 is a small catalog. An unproven status index is scope expansion.
- `down()` drops only `kiem_tra_b26_01` and `trang_thai`; it must not touch B29 pivots or any table/row. Document that rolling down after real deactivation loses the state bit and therefore is only an emergency/disposable-schema operation, not a routine production rollback.
- Verify fresh-up, seeded-up, down-one/up-one, exact default/collation/nullability/CHECK metadata, and preservation of B26/B29 row counts. Never execute rollback against development/production.

### 2. Model and request boundary

- Add `trang_thai` to `NhomCo::$fillable`; no new relation/model/table.
- Create request: optional `status`, string, `Rule::in(['HOAT_DONG', 'NGUNG_SU_DUNG'])`; normalize provided status with trim/uppercase. Keep code normalization and prohibited system fields.
- Update request: optional status with the same enum/normalization. Keep code, ID, timestamps, branch/actor/relationship authority fields prohibited.
- Preserve the existing rule that a PATCH must contain at least one allowed field. Do not make `status` client-required for creation.

### 3. Service, DTO, transaction, audit, and idempotency

- `taoNhomCo`: persist normalized status or `HOAT_DONG` and return it.
- `capNhatNhomCo`: within the existing retrying DB transaction, lock active ADMIN actor first and the Muscle Group row second. Apply only submitted mutable fields.
- Treat an identical normalized PATCH as a no-op: do not change `ngay_cap_nhat` and do not emit another audit row.
- When status actually changes, write one `nhat_ky_he_thong` row in the same transaction with actor, action `CAP_NHAT_TRANG_THAI_NHOM_CO`, target type `NHOM_CO`, target ID, unique correlation key, before/after status, success result, and UTC timestamps. Failure rolls back both catalog and audit. This provides operational audit without a new history table or idempotency-key API.
- `duLieuNhomCo` adds `status`; nested Exercise `muscle_groups[]` also adds `status` so inactive existing relations remain understandable.
- Deactivation itself performs no pivot/history write. Tests must compare pivot primary key, role, `ngay_tao`, and `ngay_cap_nhat` before/after.

### 4. Locked relation validation and non-destructive synchronization

- Validate Exercise relation state inside the existing transaction after actor/exercise locks and while locking referenced `NhomCo` rows in ascending ID order.
- For Exercise create, every referenced group must exist and be `HOAT_DONG`; inactive IDs fail atomically.
- For Exercise update, load current Muscle Group pivot rows before validating a submitted replacement list.
- Partition relation sync by dimension. If `muscle_groups` is absent, do not validate, delete, recreate, or timestamp any Muscle Group pivot. If `equipment_ids` is absent, likewise do not touch equipment pivots.
- Compare submitted inactive relations to the current pivot map. Every current inactive relation must be present with exactly the current role. Any newly referenced inactive group, omitted current inactive group, or changed role fails with `INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE` before any pivot mutation.
- Synchronize only actual active-group differences. Preserve unchanged pivot records rather than delete/recreate the complete set. Keep existing uniqueness and `CHINH/PHU` rules.
- Lock ordering for races: actor, Exercise (when updating), then sorted Muscle Group IDs. The status mutation uses actor then Muscle Group. With different admins, the shared group row is the serialization point.
- Race result contract: if relation commit wins first, later deactivation succeeds and preserves that pivot; if deactivation wins first, the attempted new relation fails 422. No partial Exercise or orphaned relation is allowed.

### 5. Documentation synchronization

- Add the owner-approved Muscle Group rule to `PROJECT_RULES.md` in the Exercise Library/catalog + Q12/test areas; do not alter Membership/PT decisions.
- Expand `BACKEND_API_CONTRACT.md` with request/response status fields, list-all management semantics, active-only new relations, immutable/readable inactive existing relations, failure codes, and no DELETE.
- Update B26 in the data dictionary with the exact column/default/enum/CHECK and preservation note.
- Add a clearly dated post-baseline M061 addendum to `THIET_KE_DATABASE.md` and `MIGRATION_PLAN.md`; do not rewrite historical M001-M060 claims as if M061 existed in 2026-08-29 evidence.
- Synchronize the B26 column and constraint note in both official ERDs. Do not regenerate unrelated pages or reorder XML globally.
- The new completion report must distinguish observed tests from planned/unrun tests and explicitly name the project-portable PHP runtime used.

## Required tests and exact commands

### Required automated assertions

`AdminCatalogApiTest`:

- POST without status returns/persists `HOAT_DONG`; explicit allowed status is normalized/persisted; invalid status and authority fields return 422 with no row.
- GET list includes both statuses and the complete DTO.
- PATCH deactivates/reactivates without deleting the group or pivot; repeated identical PATCH keeps update timestamp and audit count unchanged.
- Actual status transition emits exactly one atomic before/after audit row.
- DELETE remains 405.
- Add Muscle Group PATCH to unauthenticated and MEMBER/PT/RECEPTIONIST matrices; include inactive/revoked ADMIN fail-closed coverage if not already shared by middleware tests.
- New Exercise create with an inactive group fails `INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE`; no Exercise/pivot remains.
- Existing related group can be deactivated; Exercise detail/list still returns the relation with status; scalar/equipment-only Exercise patch preserves exact B29 row identity/timestamps.
- Submitted replacement cannot remove/change-role/reuse the inactive group and rolls back all scalar/equipment changes. Reactivation permits later ordinary relation changes.
- Seeder rerun does not reactivate an inactive existing group.

Additional schema/model assertions in `AdminCatalogApiTest`:

- B26 exposes `trang_thai`, the model fillable matches it, metadata is NOT NULL/default `HOAT_DONG`/binary collation, and `kiem_tra_b26_01` rejects invalid direct writes.
- Pre-existing seeded Muscle Groups are active after M061.

`AdminCatalogConcurrencyTest` plus process helper:

- Add a `muscle_group` service action. Use two distinct active ADMIN actors to prove the group row, not one actor mutex, serializes concurrent name/status patches; final state contains both changes and exactly one transition audit.
- Add a deactivation-versus-new-relation process race. Accept only the two linearizable outcomes described above; assert final group inactive, no partial Exercise, no orphan, and any committed old pivot remains byte-for-byte intact.

### Test environment preconditions

- Every PHP/Laravel/Composer/Pint command below must invoke exactly `E:\Fitness\.tools\php\php.exe`.
- Before any Artisan DB command, prove `APP_ENV=testing`, `DB_CONNECTION=mysql`, and exact equality `DB_DATABASE == SMART_FITNESS_TEST_DATABASE`; the name must match the repository test-safety convention (`smart_fitness_*test*`). Abort otherwise.
- Use only an isolated disposable MariaDB test schema. Never select `smart_fitness`, any production/development schema, SQLite, or system `php`.
- No real PayOS/Gemini/network call.

Run from `E:\Fitness\BE`:

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

On a separately identified disposable schema only, additionally run M061 rollback/re-up verification with the same binary:

```powershell
& 'E:\Fitness\.tools\php\php.exe' artisan migrate --database=mysql --force
& 'E:\Fitness\.tools\php\php.exe' artisan migrate:rollback --database=mysql --step=1 --force
& 'E:\Fitness\.tools\php\php.exe' artisan migrate --database=mysql --force
```

Before and after rollback/re-up, query metadata and B26/B29 counts from a test case or `artisan tinker --execute` invoked by the same XAMPP binary; never use a second PHP executable. A full suite pass is mandatory after the final up.

Repository gates from `E:\Fitness`:

```powershell
git status --short
git diff --check
git diff --cached --name-only
git diff --name-only
```

Staged diff must remain empty. Changed product paths must be a subset of the exact allow-list plus the pre-existing controller-owned checkpoint modification. The writer must not commit, branch, stash, reset, restore, checkout, clean, push, merge, or create a worktree.

## Acceptance and FE3 controller re-entry

Repair acceptance requires all of the following:

1. Exact allow-list only; unrelated existing changes preserved; `git diff --check` passes and staged diff remains empty.
2. M061 metadata/default/backfill/CHECK/down/up verified only in disposable MariaDB; no development/production DB mutation.
3. All targeted tests, concurrency processes, full Backend suite, and Pint pass with exactly `E:\Fitness\.tools\php\php.exe` (verified PHP 8.4.25).
4. GET/POST/PATCH routes remain behind `auth:api` + `role:ADMIN`; no DELETE route. Controller remains a thin delegate with correct 200/201/errors.
5. API rejects inactive new/changed/removed relations and preserves old inactive pivots/history/DTO readability.
6. Concurrency proves a linearizable status/relation outcome and atomic audit; retry/no-op is non-duplicating.
7. Authoritative docs, both ERDs, and the 15-section completion report match source and executed evidence.
8. Independent reviewer re-reads the diff and executed output; no claim is accepted from UI behavior alone.

Only after all eight pass may the controller update the checkpoint from `WAITING_BACKEND_FIX`, rerun the FE3-ALL entry gate, and dispatch one consolidated FE3-ALL writer. Backend repair work itself must not edit the checkpoint. FE3 implementation remains all 12 approved screens as one task; FE4 remains out of scope.

## Blockers and risks

- **Runtime blocker:** none. The official portable PHP 8.4.25 runtime and installed platform requirements are verified; using XAMPP/system PHP would be a gate failure.
- **Database safety:** M061 is additive, but its down path discards the status value. Rollback is permitted only on an explicitly guarded disposable test schema.
- **Legacy-client risk:** list now returns inactive rows. Clients must filter `status === HOAT_DONG` for new selectors; Backend validation remains the final guard.
- **Relation-sync risk:** the current delete/recreate implementation can silently rewrite inactive pivots. The dimension-aware diff synchronization and row-identity assertions are mandatory, not optional cleanup.
- **Documentation risk:** adding a new official B26 column without updating the data dictionary and both ERDs would violate RULE CODE 04; changing historical M001-M060 claims wholesale would also be inaccurate. Use a dated M061 addendum.
- **No remaining product ambiguity:** audit action/code, failure code, immutable inactive relation behavior, reactivation, defaults, transaction order, and acceptance behavior are specified above.
