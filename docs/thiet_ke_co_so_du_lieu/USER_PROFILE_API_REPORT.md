# USER / PROFILE API REPORT

## 1. Scope

Đã triển khai lớp nền API hồ sơ tự sở hữu cho user đã xác thực: aggregate
current profile, cập nhật an toàn tài khoản, hồ sơ Member, hồ sơ PT, lịch rảnh
Member và bộ dụng cụ Member. Không tạo/sửa migration, logical schema, Model,
Seeder, Factory, FE hoặc Mobile; không triển khai Membership, Package, Payment,
QR, Workout, PT assignment/session, Chat hay AI.

## 2. Environment

| Hạng mục | Kết quả đã kiểm tra |
|---|---|
| PHP | 8.4.25 (`.tools/php/php.exe`) |
| Laravel | Framework 13.29.0 |
| DB_CONNECTION | `mysql` |
| DBMS | MariaDB 10.4.32, vendor `mariadb.org binary distribution` |
| Engine | InnoDB |
| SESSION sql_mode | `STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION` |
| Charset / collation connection | `utf8mb4` / `utf8mb4_unicode_ci` |
| Test schema | `smart_fitness_profile_test_20260829_a93c` (đã cleanup) |
| Schema baseline | M001–M060; 52 CORE tables + Laravel `migrations` |

## 3. Profile Architecture

- **Current-user endpoint:** `GET /api/profile` lấy principal từ custom
  `the_truy_cap` guard, trả account allow-list, active roles và chỉ những hồ sơ
  tương ứng vừa có role hiệu lực vừa có hàng profile. User nhiều role nhận được
  cả `member` và `trainer` khi cả hai hồ sơ tồn tại.
- **Current account update:** `PATCH /api/profile` chỉ ánh xạ `name`, `phone`,
  `avatar_url`. Email, branch, trạng thái, password/hash và các mốc hệ thống
  không được đọc từ payload đã validate.
- **Member profile:** IMPLEMENTED. Truy vấn duy nhất bằng
  `ho_so_hoi_vien.nguoi_dung_id = authenticated user id`; cập nhật trong
  transaction, khóa hàng và tăng `phien_ban_ho_so` đúng một lần khi dữ liệu
  thực sự đổi.
- **PT profile:** IMPLEMENTED. Truy vấn duy nhất bằng
  `ho_so_huan_luyen_vien.nguoi_dung_id = authenticated user id`; PT chỉ tự sửa
  giới thiệu và chuyên môn. Mã PT và trạng thái nhận phân công vẫn do quản trị.
- **Availability:** IMPLEMENTED theo `ngay_ranh_hoi_vien`. PUT là replace-set,
  cho phép mảng rỗng, sắp xếp ổn định, idempotent và tăng profile version khi
  tập hợp thực sự đổi.
- **Equipment preference:** IMPLEMENTED theo `dung_cu_hoi_vien`. PUT là
  replace-set; mọi ID phải tồn tại trong master `dung_cu`, API không tạo/sửa
  master data và không tự bịa policy loại bỏ dụng cụ `NGUNG_SU_DUNG` đã có.
- **Body metrics:** DEFERRED. `chi_so_co_the` là lịch sử append-only nên không
  được sửa hoặc xóa qua Profile API; thuộc BODY METRICS / PROGRESS API sau.

API dùng tên trường ngoài bằng English nhất quán với Auth API; service ánh xạ
tường minh sang tên cột tiếng Việt không dấu. Nếu role hợp lệ nhưng thiếu hồ sơ,
endpoint role-specific trả 404 có kiểm soát và không tự tạo hồ sơ ngầm.

## 4. Field Access Matrix

| Table | Field | Readable | Self writable | Admin managed | Workflow managed |
|---|---|---:|---:|---:|---:|
| `nguoi_dung` | `id` | Yes | No | No | System |
| `nguoi_dung` | `chi_nhanh_id` | Yes | No | Yes | No |
| `nguoi_dung` | `ho_ten` | Yes | Yes | No | No |
| `nguoi_dung` | `thu_dien_tu` | Yes | No | No | Deferred email-change workflow |
| `nguoi_dung` | `so_dien_thoai` | Yes | Yes | No | No |
| `nguoi_dung` | `anh_dai_dien` | Yes | Yes | No | No |
| `nguoi_dung` | `mat_khau_bam` | **No** | No | No | Auth/password workflow |
| `nguoi_dung` | `xac_minh_thu_luc` | No | No | No | Verification workflow |
| `nguoi_dung` | `trang_thai` | Yes | No | Yes | No |
| `nguoi_dung` | `dang_nhap_gan_nhat_luc` | No | No | No | Auth workflow |
| `ho_so_hoi_vien` | `id`, `ma_hoi_vien` | Yes | No | No | System/provisioning |
| `ho_so_hoi_vien` | `ngay_sinh`, `gioi_tinh` | Yes | Yes | No | No |
| `ho_so_hoi_vien` | `muc_tieu_tap_luyen`, `kinh_nghiem_tap_luyen` | Yes | Yes | No | No |
| `ho_so_hoi_vien` | `so_ngay_tap_mong_muon`, `thoi_luong_moi_buoi_phut` | Yes | Yes | No | No |
| `ho_so_hoi_vien` | `phien_ban_ho_so` | Yes | No | No | Profile transaction |
| `ho_so_hoi_vien` | `moc_thay_doi_ke_hoach` | Yes | No | No | Workout Plan workflow |
| `ngay_ranh_hoi_vien` | `thu_trong_tuan` | Yes | Yes, replace-set | No | Profile transaction |
| `dung_cu_hoi_vien` | `dung_cu_id` | Yes | Yes, replace-set | No | Profile transaction |
| `dung_cu` | code/name/status master | Yes when selected | No | Yes | Catalog workflow |
| `chi_so_co_the` | all measurement/history fields | No in this module | No | No | Future append-only progress API |
| `ho_so_huan_luyen_vien` | `id`, `ma_huan_luyen_vien` | Yes | No | No | System/provisioning |
| `ho_so_huan_luyen_vien` | `gioi_thieu`, `chuyen_mon` | Yes | Yes | No | No |
| `ho_so_huan_luyen_vien` | `trang_thai` | Yes | No | Yes | PT administration |
| all profile tables | timestamps / owner FK | Selected timestamp only | No | No | System |

Unknown/system payload fields are ignored because Controllers consume only
`FormRequest::validated()` and Services use explicit field maps. Tests prove
that attempted changes to owner IDs, profile IDs/codes, account/PT status,
branch, password hash, version and plan marker do not reach the database.

## 5. Endpoints

| Method | Path | Required auth | Required role | Ownership rule | Purpose |
|---|---|---:|---|---|---|
| GET | `/api/profile` | Bearer | Any active authenticated account | Current principal | Role-aware aggregate |
| PATCH | `/api/profile` | Bearer | Any active authenticated account | Current principal | Safe current account update |
| GET | `/api/profile/member` | Bearer | MEMBER active | Profile FK equals principal ID | Read own Member profile |
| PATCH | `/api/profile/member` | Bearer | MEMBER active | Profile FK equals principal ID | Update own Member profile |
| GET | `/api/profile/member/availability` | Bearer | MEMBER active | Member profile from principal | Read own available days |
| PUT | `/api/profile/member/availability` | Bearer | MEMBER active | Member profile from principal | Replace own available days |
| GET | `/api/profile/member/equipment` | Bearer | MEMBER active | Member profile from principal | Read own equipment set |
| PUT | `/api/profile/member/equipment` | Bearer | MEMBER active | Member profile from principal | Replace own equipment set |
| GET | `/api/profile/trainer` | Bearer | PT active | Profile FK equals principal ID | Read own PT profile |
| PATCH | `/api/profile/trainer` | Bearer | PT active | Profile FK equals principal ID | Update safe own PT fields |

Không có route nhận user/profile ID, public PT directory, admin arbitrary user
patch hoặc body-metrics mutation.

## 6. Ownership / IDOR

- **Current-user ID source:** authenticated principal từ Bearer token.
- **Member ownership:** `ho_so_hoi_vien.nguoi_dung_id` phải bằng principal ID;
  lịch rảnh và dụng cụ dùng `ho_so_hoi_vien.id` vừa tìm được ở server.
- **PT ownership:** `ho_so_huan_luyen_vien.nguoi_dung_id` phải bằng principal ID.
- **Arbitrary client owner ID trusted:** **NO**.
- **Role enforcement:** middleware `role:MEMBER` / `role:PT` đọc role active từ
  database trên mỗi request; role bị thu hồi trả 403.

Test tạo Member A/B và PT A/B với user ID khác profile ID, gửi thêm owner/profile
ID của B trong payload của A, rồi xác nhận chỉ hàng A đổi và toàn bộ hàng B giữ
nguyên. Vì API `my profile` không nhận resource ID, không tồn tại lookup nhánh
client-controlled có thể gây IDOR.

## 7. Validation

| Request | Allow-list và rule chính |
|---|---|
| `UpdateProfileRequest` | name required-if-present/max 150; phone nullable/max 20; avatar path nullable/max 500 |
| `UpdateMemberProfileRequest` | birth date `Y-m-d`, không ở tương lai; schema string lengths 30/100/50; desired days 1–7; duration 1–65535 |
| `UpdateTrainerProfileRequest` | introduction nullable/max 16.383 ký tự để nằm an toàn trong giới hạn byte `TEXT` utf8mb4; specialties nullable/max 255 |
| `ReplaceAvailabilityRequest` | key phải present; array tối đa 7; mỗi ngày integer, distinct, 2–8 |
| `ReplaceEquipmentRequest` | key phải present; mỗi ID integer, distinct và tồn tại trong `dung_cu` |

Từ điển/schema không chốt closed enum cho giới tính, mục tiêu hay kinh nghiệm,
vì vậy API không tự bịa enum; các trường này được bảo vệ bằng type/length vật
lý. Enum quản trị thật (`nguoi_dung.trang_thai`, PT `trang_thai`) không nằm
trong write allow-list. Validation 422 diễn ra trước transaction; Service còn
kiểm tra lại sự tồn tại của dụng cụ trong transaction để chặn race với master.

## 8. Membership Isolation

| Hành động | Kích hoạt Membership |
|---|---:|
| Profile aggregate/read | NO |
| Current account update | NO |
| Member/PT profile update | NO |
| Availability replace | NO |
| Equipment replace | NO |

Test giữ `dang_ky_goi_tap` ở `CHO_KICH_HOAT`, xác nhận
`lan_su_dung_dau_tien_id` và `ngay_bat_dau` vẫn NULL, đồng thời không tạo hàng
`su_dung_quyen_loi`.

## 9. Security

- Password exposed: **NO**.
- Token/hash/reset fields exposed: **NO**.
- Mass assignment: **PROTECTED** bằng validated input + explicit mapping.
- IDOR: **PASS**.
- Role authorization và revoked-role behavior: **PASS**.
- Missing profile: controlled 404; không auto-create.
- Replace-set concurrency: profile row `SELECT ... FOR UPDATE`, delete/insert và
  version bump trong cùng transaction; identical set là no-op.
- Client không thể sửa email, account state, role, branch, Member/PT code,
  profile version/marker hoặc PT availability state.

## 10. Tests

| Gate | Kết quả |
|---|---|
| `ProfileApiTest` | 5 tests: auth, aggregate, safe update, validation, Membership isolation — PASS |
| `MemberProfileTest` | 4 tests: read/update, validation, roles/missing, A/B IDOR — PASS |
| `TrainerProfileTest` | 4 tests: read/update, roles/missing, A/B IDOR, validation — PASS |
| `MemberPreferencesTest` | 5 tests: availability/equipment replace, empty set, idempotency, rollback, A/B ownership — PASS |
| Focused profile suite | **18 tests, 221 assertions, PASS** |
| IDOR tests | Member A/B, PT A/B, preference A/B — PASS |
| Mass-assignment tests | account, Member, PT and preference owner payloads — PASS |
| Full backend suite — run 1 | **59 tests, 1.019 assertions, PASS** |
| Full backend suite — run 2 | **59 tests, 1.019 assertions, PASS** |
| Random order seed `20260829` | **59 tests, 1.019 assertions, PASS** |
| Laravel Pint | PASS |
| `git diff --check` | PASS |

## 11. Database Safety

- `smart_fitness` modified by tests: **NO**. Mọi migrate/test đều nhận explicit
  `DB_DATABASE=smart_fitness_profile_test_20260829_a93c`.
- Kiểm tra read-only sau test trên `smart_fitness`: `migrations=60`,
  `nguoi_dung=8`, `the_truy_cap=0`, `dang_ky_goi_tap=0`,
  `su_dung_quyen_loi=0`, khớp baseline Auth trước module.
- Không chạy migrate/fresh/rollback/truncate/drop trên `smart_fitness`; không
  tắt FK/CHECK/UNIQUE hoặc strict mode.
- Test schema cleanup: `DROP` exact test name; truy vấn
  `information_schema.SCHEMATA` trả `0`. **PASS**.
- Probe scripts tạm trong `.tmp` đã xóa; không lưu password/raw token/secret.

## 12. Deferred Scope

- **Body metrics:** deferred sang append-only BODY METRICS / PROGRESS API.
- **Password change:** deferred sang Auth/password workflow có current-password
  verification, token policy và audit hoàn chỉnh.
- **Email change:** deferred vì tài liệu chưa chốt verification/uniqueness
  workflow cho self-service email.
- **Admin user management:** deferred; không có arbitrary-user update.
- **PT public directory:** deferred; không mở endpoint public/list/detail.
- **Role management:** DEFERRED.
- **Membership/Package:** NOT IMPLEMENTED.
- Payment/payOS, QR, Workout, PT assignment/session, Chat, AI: NOT IMPLEMENTED.

## 13. Files Changed

Modified:

- `BE/routes/api.php`

Created:

- `BE/app/Http/Controllers/Api/Profile/ProfileController.php`
- `BE/app/Http/Controllers/Api/Profile/MemberProfileController.php`
- `BE/app/Http/Controllers/Api/Profile/TrainerProfileController.php`
- `BE/app/Http/Requests/Profile/UpdateProfileRequest.php`
- `BE/app/Http/Requests/Profile/UpdateMemberProfileRequest.php`
- `BE/app/Http/Requests/Profile/UpdateTrainerProfileRequest.php`
- `BE/app/Http/Requests/Profile/ReplaceAvailabilityRequest.php`
- `BE/app/Http/Requests/Profile/ReplaceEquipmentRequest.php`
- `BE/app/Services/ProfileService.php`
- `BE/app/Services/MemberProfileService.php`
- `BE/app/Services/TrainerProfileService.php`
- `BE/tests/Concerns/CreatesProfileFixtures.php`
- `BE/tests/Feature/ProfileApiTest.php`
- `BE/tests/Feature/MemberProfileTest.php`
- `BE/tests/Feature/TrainerProfileTest.php`
- `BE/tests/Feature/MemberPreferencesTest.php`
- `docs/thiet_ke_co_so_du_lieu/USER_PROFILE_API_REPORT.md`

Không sửa migration, logical schema, Model, Seeder/Factory, Auth contract hiện
có, FE hoặc Mobile.

## 14. Deviations

NONE. Availability và equipment được triển khai vì schema chính thức có
`ngay_ranh_hoi_vien` và `dung_cu_hoi_vien`. Body metrics được hoãn đúng rule
append-only. Không thêm field, bảng, package hay business workflow.

## 15. Final Gate

```text
CURRENT PROFILE READ = PASS
SAFE PROFILE UPDATE = PASS

MEMBER PROFILE = PASS
PT PROFILE = PASS
MEMBER AVAILABILITY = PASS
MEMBER EQUIPMENT PREFERENCES = PASS

ROLE AUTHORIZATION = PASS
OWNERSHIP AUTHORIZATION = PASS
IDOR PROTECTION = PASS

MASS ASSIGNMENT PROTECTION = PASS
VALIDATION = PASS

MEMBERSHIP NON-ACTIVATION = PASS

PROFILE TESTS = PASS
FULL BACKEND TEST SUITE = PASS
TEST ORDER INDEPENDENCE = PASS

TEST DATABASE CLEANUP = PASS
smart_fitness = SAFE

USER PROFILE API IMPLEMENTATION = PASS
CURRENT USER PROFILE = IMPLEMENTED
MEMBER PROFILE API = IMPLEMENTED
PT PROFILE API = IMPLEMENTED
OWNERSHIP / IDOR PROTECTION = IMPLEMENTED
DATABASE = READY FOR MEMBERSHIP / PACKAGE MODULE IMPLEMENTATION
```

NEXT RECOMMENDED STEP: MEMBERSHIP + PACKAGE FOUNDATION.
