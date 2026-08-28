# SEEDER IMPLEMENTATION REPORT

## 1. Scope

Đã triển khai lớp Seeder phát triển cho dữ liệu danh mục, tài khoản demo và
Exercise Library. Không thay đổi M001–M060, logical schema, Q01–Q13, Model,
Controller, Service, API, FE hoặc Mobile. Không tạo dữ liệu giao dịch, quyền
Membership, QR, Workout execution, PT workflow, Chat, AI hay audit lịch sử.

## 2. Environment

| Hạng mục | Giá trị đã kiểm tra |
|---|---|
| PHP | 8.4.25 CLI (`.tools/php/php.exe`) |
| Laravel | Framework 13.29.0 (`composer.lock`, `artisan`) |
| Database | MariaDB 10.4.32-MariaDB |
| Vendor | `mariadb.org binary distribution` |
| Engine | InnoDB |
| SESSION sql_mode | `STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION` qua Laravel connection |
| Database charset/collation | `utf8mb4` / `utf8mb4_unicode_ci` |
| Timezone | `SYSTEM`, system time zone `Asia/Bangkok` |
| Schema test | `smart_fitness_seeder_test_20260829_a7f3` (đã cleanup sau kiểm tra) |
| Database phát triển | `smart_fitness` (đã seed sau khi test cô lập PASS) |

## 3. Seeder Inventory

| Seeder | Trách nhiệm | Kết quả |
|---|---|---|
| `ChiNhanhSeeder` | Chi nhánh MVP | 1 hàng |
| `VaiTroSeeder` | Bốn vai trò chính thức | 4 hàng |
| `DemoNguoiDungSeeder` | Tài khoản, phân quyền và hồ sơ demo | 8 tài khoản, 8 role assignments, 4 hồ sơ hội viên, 2 hồ sơ PT |
| `GoiTapSeeder` | Catalog gói tập | No-op: chưa có catalog chính thức được duyệt |
| `QuyenLoiGoiTapSeeder` | Quyền lợi catalog | No-op cùng lý do, không tạo quyền lợi mồ côi |
| `ExerciseDatasetSeeder` | Parser, danh mục, bài tập, quan hệ và media | Đã import đầy đủ dataset hiện tại |
| `DatabaseSeeder` | Điều phối thứ tự seed | Đã gọi các seeder theo dependency FK |

`ExerciseDatasetSeeder` có thể chạy riêng bằng:

```text
php artisan db:seed --class=ExerciseDatasetSeeder
```

## 4. Master Data

- `chi_nhanh`: một chi nhánh `CHI_NHANH_MVP`, trạng thái `HOAT_DONG`.
- `vai_tro`: đúng bốn mã `MEMBER`, `PT`, `RECEPTIONIST`, `ADMIN`.
- Không hard-code quyền theo tên gói. `goi_tap` và `quyen_loi_goi_tap` để trống
  đến khi chủ dự án duyệt catalog cụ thể; BASIC/ONLINE/PLUS/VIP trong Rules chỉ
  là tên minh họa.
- `dung_cu` và `nhom_co` được suy ra và chuẩn hóa từ dataset, không phải tài
  sản gym.

## 5. External Exercise Dataset

Nguồn được kiểm tra trực tiếp tại `.tmp/exercises-dataset`, không tự tải trong
runtime Seeder. Revision đã dùng:

```text
7455efae41b330c265e7cd4b78dfa848e7ce5ebd
```

`data/exercises.json` có 1.324 bản ghi. Parser dùng tên và hướng dẫn English
(fallback sang ngôn ngữ đầu tiên nếu bản ghi thiếu English), đồng thời lưu
`external_source`, `external_id`, `source_commit`, các trường phân loại,
`media_id` và attribution trong `bai_tap.thong_tin_bo_sung` JSON. Không tạo cột
external ID hoặc bảng dịch mới.

Nguồn không có trường độ khó chuẩn cho Smart Fitness; Seeder ghi
`CHUA_XAC_DINH` để tránh bịa mức độ và chờ Admin phân loại.

## 6. Exercise Mapping

- `ma_bai_tap`: mã ổn định `EX_<external_id>`.
- `dung_cu`: 28 giá trị equipment duy nhất, mã `DC_<slug>`.
- `nhom_co`: 50 giá trị duy nhất từ `target`, `muscle_group` và
  `secondary_muscles`, mã `NC_<slug>`.
- `bai_tap_dung_cu`: một liên kết equipment cho mỗi bài theo dataset hiện tại.
- `bai_tap_nhom_co`: target/primary được gán `CHINH`, các cơ bổ trợ được gán
  `PHU`; mọi hàng đều dùng giá trị đã được CHECK cho phép.
- Dedupe dùng trim, ASCII/case normalization để nhận diện; mã lưu trữ vẫn
  giữ tên nguồn hiển thị.

## 7. Exercise Library Counts

Đếm trên schema test sau M001–M060 và seed:

| Bảng | Số hàng |
|---|---:|
| `dung_cu` | 28 |
| `nhom_co` | 50 |
| `bai_tap` | 1.324 |
| `bai_tap_dung_cu` | 1.324 |
| `bai_tap_nhom_co` | 3.903 |

JSON của 1.324 bài đều hợp lệ; `external_id` là duy nhất; không ghi vào năm
cột generated của schema.

## 8. Media Import

- Đã copy 1.324 thumbnail `.jpg` vào
  `BE/storage/app/public/exercises/images`.
- Đã copy 1.324 animation `.gif` vào
  `BE/storage/app/public/exercises/videos`.
- Đường dẫn lưu trong `duong_dan_hinh_anh` và `duong_dan_video` là đường dẫn
  tương đối ổn định dưới Laravel public storage; không lưu BLOB/base64,
  không resize/transcode và không di chuyển file nguồn.
- Kiểm thử fixture thiếu media: bài vẫn được import, chỉ đường dẫn media bị
  bỏ trống. Lần import thực tế hiện tại thiếu `0` file.
- `BE/public/storage` đã tạo symlink thành công. Binary media bị loại khỏi Git
  bằng `BE/storage/app/public/exercises/.gitignore`.

## 9. License / Attribution

Đã kiểm tra `README.md`, `LICENSE` và `NOTICE.md` của nguồn. Dataset, cấu trúc,
tooling và instruction text thuộc MIT License của nguồn. Thumbnail/GIF thuộc
Gym visual, không nằm trong MIT; mỗi record giữ attribution
`© Gym visual — https://gymvisual.com/`. Điều khoản và giới hạn 180×180 được
ghi tại [THIRD_PARTY_EXERCISE_DATASET.md](../THIRD_PARTY_EXERCISE_DATASET.md).

## 10. Idempotency

- Chạy `DatabaseSeeder` lần đầu và chạy lại trên schema test: không phát sinh
  duplicate theo các khóa ổn định.
- Chạy riêng `ExerciseDatasetSeeder` lần hai: số hàng giữ nguyên
  `1/4/8/8/4/2/28/50/1.324/1.324/3.903` theo các bảng trên.
- Catalog source-owned được cập nhật khi cùng mã tồn tại; `trang_thai`, người
  tạo và metadata mở rộng của bản ghi hiện hữu được bảo toàn/merge để không
  ghi đè tùy tiện dữ liệu quản trị.

## 11. Tests

- `php -l` cho toàn bộ 7 seeder: PASS.
- `SeederImplementationTest`: **9 tests, 54 assertions PASS** trên schema
  MariaDB cô lập đã seed. Bao phủ inventory, master/demo, parser/counts,
  relations, media, missing media, idempotency, generated-column exclusion,
  UNIQUE và CHECK role.
- `ModelImplementationTest`: **5 tests, 558 assertions PASS** trên schema
  MariaDB cô lập sạch riêng (`smart_fitness_model_test_20260829_b9e2`).
- Không dùng SQLite, không chạy test trên database ứng dụng.

## 12. Database Safety

- Đã tạo schema test mới, chạy M001–M060 và cleanup sau kiểm tra; không dùng
  `migrate:fresh`/`refresh`/`DROP`/`TRUNCATE` trên `smart_fitness`.
- `smart_fitness` được kiểm tra read-only trước seed: 52 CORE tables, 60
  migration rows và không có dữ liệu domain. Sau khi test cô lập PASS mới chạy
  `php artisan db:seed --force` trên `smart_fitness`.
- Không thay đổi global `sql_mode`, không tắt foreign keys và không thay đổi
  migration/schema.

## 13. Operational Tables

Các bảng giao dịch, Membership, quyền sử dụng, QR, PT history, Workout,
Realtime Chat, AI/Proposal, audit và idempotency vẫn có **0 hàng** sau seed.
Không tạo payment, subscription, check-in, session, message, AI request hay
history giả.

## 14. Files Changed

- `BE/database/seeders/DatabaseSeeder.php`
- `BE/database/seeders/ChiNhanhSeeder.php`
- `BE/database/seeders/VaiTroSeeder.php`
- `BE/database/seeders/GoiTapSeeder.php`
- `BE/database/seeders/QuyenLoiGoiTapSeeder.php`
- `BE/database/seeders/DemoNguoiDungSeeder.php`
- `BE/database/seeders/ExerciseDatasetSeeder.php`
- `BE/tests/Feature/SeederImplementationTest.php`
- `BE/.env.example` (tài liệu hóa đường dẫn dataset tùy chọn)
- Existing `BE/storage/app/public/.gitignore` (already ignores generated media)
- `docs/THIRD_PARTY_EXERCISE_DATASET.md`
- `docs/thiet_ke_co_so_du_lieu/SEEDER_IMPLEMENTATION_REPORT.md`

Không có migration PHP, không sửa `PROJECT_RULES.md`, Data Dictionary, Database
Design, ERD, Model, FE hoặc Mobile. `.tmp/exercises-dataset` vẫn là thư mục
nguồn bị ignore, không nằm trong thay đổi Git.

## 15. Deviations

1. Catalog `goi_tap`/`quyen_loi_goi_tap` chưa seed vì tài liệu chính thức chỉ
   chốt cấu trúc và ví dụ tên, chưa chốt một catalog giá/quyền để dùng làm dữ
   liệu phát triển. Hai seeder vẫn tồn tại ở dạng no-op có chủ đích.
2. Dataset không cung cấp độ khó theo từ điển Smart Fitness; giá trị tạm
   `CHUA_XAC_DINH` được ghi để Admin phân loại sau.
3. Media được tích hợp vào thư mục storage local nhưng binary bị ignore, nên
   môi trường khác phải có source dataset và chạy Seeder để tái tạo media.
4. Chạy toàn bộ test suite trên schema đã seed làm fixture cũ của
   `ModelImplementationTest` đụng khóa `chi_nhanh.id=1`; test model đã được
   chạy PASS trên schema sạch riêng như mục 11. Đây là giới hạn fixture test,
   không phải lỗi schema/seeder.

## 16. Final Gate

Các gate seed master/demo, parser/mapping, quan hệ, JSON, media, idempotency,
constraint và database safety đều PASS trên MariaDB 10.4.32/InnoDB. Database
phát triển đã được seed; các bảng vận hành vẫn rỗng.

```text
SEEDER IMPLEMENTATION = PASS
DEVELOPMENT SEED DATA = CREATED
EXERCISE DATASET = IMPORTED
EXERCISE MEDIA = INTEGRATED
DATABASE = READY FOR AUTHENTICATION IMPLEMENTATION
```

NEXT RECOMMENDED STEP: AUTHENTICATION + AUTHORIZATION FOUNDATION
