# MUSCLE GROUP DEACTIVATION REPORT

MODULE DA HOAN THANH

1. Muc tieu module

Triển khai vòng đời `nhom_co` với trạng thái `HOAT_DONG`/`NGUNG_SU_DUNG`, deactivation/reactivation bằng PATCH, audit nguyên tử và quan hệ B29 không phá hủy. Phạm vi giữ nguyên API Admin hiện hữu, không hard-delete và không sửa dữ liệu lịch sử.

2. File da tao

- `BE/database/migrations/2026_09_10_000061_m061_them_trang_thai_nhom_co.php`
- `docs/thiet_ke_co_so_du_lieu/MUSCLE_GROUP_DEACTIVATION_REPORT.md`

3. File da sua

- `BE/app/Models/NhomCo.php`
- `BE/app/Http/Requests/Admin/Catalog/CreateMuscleGroupRequest.php`
- `BE/app/Http/Requests/Admin/Catalog/UpdateMuscleGroupRequest.php`
- `BE/app/Services/Admin/ExerciseCatalogAdminService.php`
- `BE/database/seeders/ExerciseDatasetSeeder.php`
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

`docs/VUE_WEB_COMPLETION_CHECKPOINT.md` là thay đổi của controller và không bị chạm bởi repair này.

4. Database lien quan

M061 additive thêm `nhom_co.trang_thai VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL DEFAULT 'HOAT_DONG'` sau `mo_ta`, cùng CHECK `kiem_tra_b26_01` chỉ nhận hai trạng thái. `down()` chỉ bỏ CHECK và cột. B26/B29 không thêm bảng, FK hoặc index; B29 pivot và các hàng lịch sử không bị deactivation sửa/xóa.

5. API da tao/sua

- `GET /api/admin/muscle-groups` trả cả nhóm hoạt động và ngừng sử dụng, DTO có `status`.
- `POST /api/admin/muscle-groups` nhận `status` tùy chọn, mặc định `HOAT_DONG`.
- `PATCH /api/admin/muscle-groups/{muscleGroup}` cập nhật `name`, `description`, `status`; dùng để deactivate/reactivate.
- Không có `DELETE /api/admin/muscle-groups/{id}`; route hiện hữu vẫn trả 405.
- Exercise DTO lồng `muscle_groups[]` trả status của nhóm liên quan.

6. Ham chinh

- `taoNhomCo` — tạo nhóm với status đã chuẩn hóa hoặc mặc định; thực hiện trong transaction có guard Admin.
- `capNhatNhomCo` — khóa actor rồi nhóm, áp dụng phần được gửi; PATCH cùng trạng thái là no-op, chuyển trạng thái thật cập nhật timestamp và ghi audit trước/sau trong cùng transaction.
- `taoBaiTap` — khóa/kiểm tra dụng cụ và nhóm theo ID tăng dần; cấm nhóm inactive trong quan hệ mới rồi tạo các pivot cần thiết.
- `capNhatBaiTap` — khóa actor/bài tập/nhóm liên quan; validate inactive relation trước mọi ghi và đồng bộ riêng từng chiều bằng diff.
- `xacThucThayTheNhomCo` và `dongBoNhomCo` — bảo vệ quan hệ inactive hiện hữu, chỉ thêm/xóa/đổi role ở nhóm active và giữ nguyên row không đổi.

7. Business Rule da xu ly

- Chỉ cho phép `HOAT_DONG` và `NGUNG_SU_DUNG`; danh sách Admin vẫn đọc cả hai.
- Nhóm inactive không được dùng cho quan hệ Exercise mới.
- Quan hệ B29 hiện hữu tới nhóm inactive vẫn readable, giữ nguyên id/role/timestamp và không thể bị bỏ, đổi role hoặc tái sử dụng ở Exercise khác cho tới khi nhóm được reactivate.
- Bỏ `muscle_groups` khỏi PATCH Exercise không ghi B29; scalar/equipment-only update cũng không rewrite B29.
- Sau reactivate, quan hệ mới và thay đổi thông thường được phép.

8. Authorization

Các endpoint giữ middleware hiện hữu `auth:api` và `role:ADMIN`. Service khóa lại actor từ Database và kiểm tra account đang hoạt động cùng role ADMIN trong transaction; không tin actor/branch/system fields từ client.

9. Validation

Request create/update chuẩn hóa status bằng trim/uppercase rồi áp dụng enum chính xác. ID, code, timestamp, branch và actor/authority fields bị prohibited. Relation ID/role được kiểm tra shape, tồn tại, role `CHINH`/`PHU`, trạng thái nhóm; vi phạm inactive relation trả `422 INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE` trước pivot/scalar writes.

10. Transaction / Idempotency

Các mutation dùng transaction retry tối đa 3 lần. Lock order là actor → Exercise khi update → equipment (nếu gửi) → các Muscle Group ID tăng dần; status mutation là actor → Muscle Group. Status transition và audit commit cùng transaction. PATCH cùng target state là state-idempotent no-op, không đổi `ngay_cap_nhat` và không tạo audit trùng.

11. Error Case

- Status ngoài enum hoặc authority field: HTTP 422, không tạo/cập nhật hàng.
- Nhóm không tồn tại: `404 MUSCLE_GROUP_NOT_FOUND`; mã trùng: `409 MUSCLE_GROUP_CONFLICT`.
- Quan hệ nhóm không tồn tại: `422 INVALID_MUSCLE_GROUP`.
- Tạo/thêm/bỏ/đổi role quan hệ inactive: `422 INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE`, toàn bộ transaction rollback.
- DELETE Muscle Group không được định tuyến và trả 405.

12. Test Case

- API status default/explicit/invalid, list hai trạng thái, deactivate/reactivate, no-op timestamp/audit, audit before/after, prohibited fields và no DELETE.
- Đọc DTO quan hệ inactive, giữ byte-for-byte pivot, replacement unchanged được phép, removal/role-change rollback, inactive create bị chặn, reactivation cho phép thay đổi, seeder rerun không hồi sinh inactive.
- Schema/model metadata: type, NOT NULL, default, collation, ordinal, named CHECK, fillable và direct invalid CHECK.
- Hai process race giữa Admin khác nhau cho name/status và giữa deactivation với quan hệ mới.

13. Test Result

Runtime và DB proof: `E:\Fitness\.tools\php\php.exe` PHP 8.4.25; `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_DATABASE=smart_fitness_test`, `DATABASE()=smart_fitness_test`, `SMART_FITNESS_TEST_DATABASE=smart_fitness_test`.

| Check | Result |
| --- | --- |
| `& 'E:\\Fitness\\.tools\\php\\php.exe' artisan about` | PASS — Laravel 13.29.0, testing, mysql |
| `& 'E:\Fitness\.tools\php\php.exe' 'E:\Fitness\.tools\composer\composer.phar' check-platform-reqs` | PASS |
| `& 'E:\Fitness\.tools\php\php.exe' artisan route:list --path=api/admin/muscle-groups --json` | PASS — GET/POST/PATCH, `auth:api` + `role:ADMIN`, no DELETE |
| Guarded `artisan migrate --database=mysql --force`; `artisan migrate:rollback --database=mysql --step=1 --force`; `artisan migrate --database=mysql --force` | PASS — B26/B29 50/3903 before, 50/3903 after down; after up `varchar(30)`, NOT NULL, default `'HOAT_DONG'`, `utf8mb4_nopad_bin`, ordinal 5, CHECK `kiem_tra_b26_01` |
| `& 'E:\Fitness\.tools\php\php.exe' artisan test --filter=AdminCatalogApiTest` | PASS — 11 tests, 232 assertions (final repair state) |
| `& 'E:\Fitness\.tools\php\php.exe' artisan test --filter=AdminCatalogConcurrencyTest` | PASS — 3 tests, 50 assertions (final repair state) |
| `& 'E:\Fitness\.tools\php\php.exe' artisan test` | PASS — 329 tests, 3,905 assertions (final repair state) |
| `& 'E:\Fitness\.tools\php\php.exe' vendor\bin\pint --test` | PASS |
| PHP syntax lint (8 affected PHP files) | PASS |
| Drawio XML parse and `git diff --check` | PASS; staged diff empty |

14. Phan chua hoan thanh

Không còn product-code item trong allow-list cần triển khai. Independent Sol/controller review, cập nhật checkpoint và FE3-ALL re-entry là các bước bàn giao bên ngoài writer; checkpoint không được sửa trong module này. M061 down chỉ được phép trên schema test cô lập, không phải production rollback.

15. Rui ro con lai

## Repair round 1 follow-up — FE3-BE-R1-001

The independent round-1 review found that an inactive seeded muscle group could receive a missing `bai_tap_nhom_co` pivot during a dataset rerun. `ExerciseDatasetSeeder` now preserves existing pivots unchanged, locks and re-reads a missing target `NhomCo` inside the existing transaction, and inserts only when the locked status is `HOAT_DONG`. `upsertMuscle` skips an unchanged existing group so an idempotent rerun cannot advance `ngay_cap_nhat`. The repair also adds a test-only `NhatKyHeThong` create failure and proves that a Muscle Group status transition rolls back its status, timestamps, and audit row together.

Final repair verification used only `E:\Fitness\.tools\php\php.exe` 8.4.25 with `APP_ENV=testing`, `DB_CONNECTION=mysql`, and configured/current/approved database `smart_fitness_test` verified by `TestDatabaseGuard`.

| Final repair command/check | Result |
| --- | --- |
| `artisan test --filter=test_dataset_seeder_skips_missing_relation_for_inactive_seeded_group_and_preserves_history` | PASS — 1 test, 12 assertions |
| `artisan test --filter=test_muscle_group_status_and_audit_roll_back_together_when_audit_fails` | PASS — 1 test, 7 assertions |
| `artisan test --filter=AdminCatalogApiTest` | PASS — 11 tests, 232 assertions |
| `artisan test --filter=AdminCatalogConcurrencyTest` | PASS — 3 tests, 50 assertions |
| `artisan test` | PASS — 329 tests, 3,905 assertions |
| `vendor\\bin\\pint --test` | PASS |
| PHP syntax lint for `ExerciseDatasetSeeder.php` and `AdminCatalogApiTest.php` | PASS |

The first Pint run after the repair reported only import normalization in `AdminCatalogApiTest`; that import was corrected with `apply_patch`, and the final Pint run passed. Independent controller review and FE3 entry-gate revalidation remain pending handoff steps.

- Client cũ sẽ thấy thêm nhóm inactive trong list và phải lọc `status` khi dựng selector; Backend vẫn là guard cuối.
- Rollback M061 làm mất bit trạng thái của nhóm, nên chỉ dùng cho schema thử nghiệm/disposable.
- Quan hệ inactive được giữ readable chủ ý; mọi đường ghi ngoài service này phải tiếp tục tôn trọng invariant và không hard-delete lịch sử.
