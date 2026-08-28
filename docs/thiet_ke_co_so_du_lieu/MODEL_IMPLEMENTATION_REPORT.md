# MODEL IMPLEMENTATION REPORT

## 1. Scope

Đã triển khai lớp Eloquent cho đúng 52 bảng CORE đã có trong schema hiện hành của Smart Fitness Platform. Phạm vi chỉ gồm mapping persistence, casts và quan hệ FK; không triển khai workflow nghiệp vụ, authentication, API, Seeder/Factory hay thay đổi schema/migration.

Nguồn đối chiếu là schema được tạo bởi M001–M060 và `information_schema` trên MariaDB. Tên model, bảng và cột giữ nguyên snake_case tiếng Việt không dấu theo nguồn dữ liệu chính thức.

## 2. Environment

| Thành phần | Giá trị / bằng chứng |
|---|---|
| PHP | 8.4.25 (`E:\Fitness\.tools\php\php.exe`) |
| Laravel | 13.29.0 (`BE/composer.lock`, `artisan`) |
| Database server | MariaDB 10.4.32-MariaDB, `mariadb.org binary distribution` |
| Engine / charset | InnoDB / utf8mb4 |
| Collation | utf8mb4_unicode_ci (database default; technical columns giữ collation theo schema) |
| Session sql_mode | `STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION` |
| Test database | `smart_fitness_model_test_20260829_8f31` (schema cô lập, đã cleanup sau test) |
| Migration setup | M001–M060 chạy thành công trên schema test; không chạy migrate trên `smart_fitness` |

## 3. Model Inventory (Model/Table/PK/Timestamps/Generated/Status)

| Model | Table | PK | Timestamps | Generated columns | Status |
|---|---|---|---|---|---|
| `ChiNhanh` | `chi_nhanh` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `NguoiDung` | `nguoi_dung` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `VaiTro` | `vai_tro` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `PhanQuyenNguoiDung` | `phan_quyen_nguoi_dung` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `HoSoHoiVien` | `ho_so_hoi_vien` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `HoSoHuanLuyenVien` | `ho_so_huan_luyen_vien` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `NgayRanhHoiVien` | `ngay_ranh_hoi_vien` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `DungCuHoiVien` | `dung_cu_hoi_vien` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `ChiSoCoThe` | `chi_so_co_the` | `id` | `khong_dung` | — | PASS |
| `TheTruyCap` | `the_truy_cap` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `YeuCauDatLaiMatKhau` | `yeu_cau_dat_lai_mat_khau` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `GoiTap` | `goi_tap` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `QuyenLoiGoiTap` | `quyen_loi_goi_tap` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `DonMuaGoi` | `don_mua_goi` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `LanThanhToan` | `lan_thanh_toan` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `SuKienThanhToan` | `su_kien_thanh_toan` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `DangKyGoiTap` | `dang_ky_goi_tap` | `id` | `ngay_tao/ngay_cap_nhat` | `hoi_vien_chua_ket_thuc_id` | PASS |
| `KyHanHoiVien` | `ky_han_hoi_vien` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `SuDungQuyenLoi` | `su_dung_quyen_loi` | `id` | `khong_dung` | — | PASS |
| `MaVaoPhongTap` | `ma_vao_phong_tap` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `LichSuVaoPhongTap` | `lich_su_vao_phong_tap` | `id` | `khong_dung` | — | PASS |
| `PhanCongHuanLuyenVien` | `phan_cong_huan_luyen_vien` | `id` | `ngay_tao/ngay_cap_nhat` | `hoi_vien_dang_phan_cong_id` | PASS |
| `LichSuSuDungHuanLuyenVien` | `lich_su_su_dung_huan_luyen_vien` | `id` | `khong_dung` | — | PASS |
| `GhiChuHuanLuyen` | `ghi_chu_huan_luyen` | `id` | `khong_dung` | — | PASS |
| `DungCu` | `dung_cu` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `NhomCo` | `nhom_co` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `BaiTap` | `bai_tap` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `BaiTapDungCu` | `bai_tap_dung_cu` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `BaiTapNhomCo` | `bai_tap_nhom_co` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `GiaoAnMau` | `giao_an_mau` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `NgayTrongGiaoAn` | `ngay_trong_giao_an` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `BaiTapTrongGiaoAn` | `bai_tap_trong_giao_an` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `KeHoachTap` | `ke_hoach_tap` | `id` | `ngay_tao/ngay_cap_nhat` | `hoi_vien_dang_su_dung_id` | PASS |
| `PhienBanKeHoachTap` | `phien_ban_ke_hoach_tap` | `id` | `khong_dung` | — | PASS |
| `NgayTrongKeHoach` | `ngay_trong_ke_hoach` | `id` | `khong_dung` | — | PASS |
| `BaiTapTrongKeHoach` | `bai_tap_trong_ke_hoach` | `id` | `khong_dung` | — | PASS |
| `BuoiTapDuKien` | `buoi_tap_du_kien` | `id` | `ngay_tao/ngay_cap_nhat` | `ma_buoi_con_hieu_luc`, `ngay_tap_con_hieu_luc` | PASS |
| `PhienTap` | `phien_tap` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `BaiTapTrongPhien` | `bai_tap_trong_phien` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `HiepTap` | `hiep_tap` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `HoiThoai` | `hoi_thoai` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `TinNhan` | `tin_nhan` | `id` | `khong_dung` | — | PASS |
| `SuKienPhatTinNhan` | `su_kien_phat_tin_nhan` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `HoiThoaiTroLy` | `hoi_thoai_tro_ly` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `TinNhanTroLy` | `tin_nhan_tro_ly` | `id` | `khong_dung` | — | PASS |
| `YeuCauTroLy` | `yeu_cau_tro_ly` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `BaiTapUngVien` | `bai_tap_ung_vien` | `id` | `khong_dung` | — | PASS |
| `GiaoAnUngVien` | `giao_an_ung_vien` | `id` | `khong_dung` | — | PASS |
| `LanGoiMoHinh` | `lan_goi_mo_hinh` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `DeXuatKeHoachTap` | `de_xuat_ke_hoach_tap` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |
| `NhatKyHeThong` | `nhat_ky_he_thong` | `id` | `khong_dung` | — | PASS |
| `YeuCauChongLap` | `yeu_cau_chong_lap` | `id` | `ngay_tao/ngay_cap_nhat` | — | PASS |

Tất cả model dùng khóa `id`, `$incrementing = true`, `$keyType = 'int'` theo metadata thực tế.

## 4. Cast Summary

Có **413 cast entries** trên 52 model; mỗi key cast đều tồn tại trong bảng tương ứng.

- JSON: 15 cột, cast `array`: `the_truy_cap.pham_vi_truy_cap`, `su_kien_thanh_toan.du_lieu_da_loc`, `bai_tap.thong_tin_bo_sung`, `bai_tap_trong_ke_hoach.dung_cu_yeu_cau`, `bai_tap_trong_phien.dung_cu_su_dung`, `yeu_cau_tro_ly.yeu_cau_chuan_hoa`, `yeu_cau_tro_ly.ngu_canh_da_chot`, `bai_tap_ung_vien.du_lieu_da_chot`, `giao_an_ung_vien.du_lieu_da_chot`, `lan_goi_mo_hinh.ket_qua_cau_truc`, `lan_goi_mo_hinh.ket_qua_kiem_tra`, `de_xuat_ke_hoach_tap.noi_dung_de_xuat`, `nhat_ky_he_thong.du_lieu_truoc`, `nhat_ky_he_thong.du_lieu_sau`, `yeu_cau_chong_lap.ket_qua_da_loc`.
- DATETIME(6): 143 cột, cast `datetime`; các model có timestamp dùng `$dateFormat = 'Y-m-d H:i:s.u'` để giữ microseconds.
- DATE: 4 cột vật lý được cast `date` (cột DATE generated không đưa vào cast).
- BOOLEAN: 7 cột `tinyint(1)` cast `boolean`.
- Số nguyên: 232 cột cast `integer`.
- DECIMAL: 12 cột cast theo scale vật lý (`decimal:0` hoặc `decimal:2`).

Không có cast cho cột không tồn tại; không dùng enum PHP, accessor/mutator hay package ngoài.

## 5. Relationship Summary

| Loại quan hệ | Số lượng |
|---|---:|
| `belongsTo` | 113 |
| `hasOne` | 19 |
| `hasMany` | 94 |
| `belongsToMany` | 0 |
| Tổng method quan hệ | 226 |

- 113 FK đơn được biểu diễn bằng `belongsTo` với tên cột FK và owner key tường minh.
- Các inverse `hasOne/hasMany` được suy ra từ tính duy nhất của FK vật lý (19 một-một, 94 một-nhiều).
- Quan hệ tự tham chiếu (phiên bản trước/thay thế buổi) có method riêng, không dùng cascade hay logic ẩn.
- Pivot/đối tượng trung gian giữ model riêng (`bai_tap_dung_cu`, `bai_tap_nhom_co`, ...); không tạo many-to-many giả làm mất dữ liệu trung gian.

## 6. Composite FK Limitations

Eloquent/Laravel chuẩn không hỗ trợ composite primary key hoặc composite foreign-key relation bằng `belongsTo`/`hasMany`. Vì vậy không dùng package composite-key và không giả vờ ánh xạ một cặp cột bằng một quan hệ đơn.

Database vẫn thực thi đầy đủ 31 composite FK của schema. Model chỉ cung cấp các navigation relation cho thành phần FK đơn khi chúng hữu ích; tính đúng tuple (member/period/plan/version/conversation/assignment) vẫn do FK composite và transaction ở tầng backend bảo vệ. Các workflow cần truy vấn theo tuple phải dùng query conditions trên đủ các cột, không dựa vào relation đơn để bỏ qua ownership.

## 7. Generated Column Protection

Năm cột generated VIRTUAL được loại khỏi `$fillable` và không có setter/mutator:

- `dang_ky_goi_tap.hoi_vien_chua_ket_thuc_id`
- `phan_cong_huan_luyen_vien.hoi_vien_dang_phan_cong_id`
- `ke_hoach_tap.hoi_vien_dang_su_dung_id`
- `buoi_tap_du_kien.ma_buoi_con_hieu_luc`
- `buoi_tap_du_kien.ngay_tap_con_hieu_luc`

Model tests xác nhận `isFillable()` trả `false` cho từng cột. Giá trị do MariaDB tính; không ghi đè từ mass assignment.

## 8. Model Tests

Đã chạy `php artisan test --filter=ModelImplementationTest` với `DB_CONNECTION=mysql` trỏ vào schema cô lập:

- 5 ModelImplementationTest tests, 558 assertions: **PASS**. Full BE suite: 8 tests, 562 assertions: **PASS**.
- Inventory 52 class/table, PK, incrementing/keyType, timestamps, casts, table existence: PASS.
- Critical relation return types cho Account/Profile, Membership/Payment, Gym, PT, Workout/History, Chat, AI, Audit/Idempotency: PASS.
- Database-backed graph load qua Eloquent trên MariaDB: PASS.
- Dữ liệu test nằm trong transaction và được rollback; không ghi `smart_fitness`.

## 9. Database Safety

- Schema test được tạo riêng, chạy M001–M060, sau đó đã drop; không dùng `migrate:fresh` hoặc DROP/TRUNCATE `smart_fitness`.
- `smart_fitness` được kiểm tra chỉ đọc sau test: 52 bảng CORE, 60 migration rows và không có business rows.
- Không có Seeder/Factory/Auth/Controller/Service/API/FE/Mobile được thêm trong phạm vi này.

## 10. Files Changed

- 52 file model mới trong `BE/app/Models/`.
- `BE/tests/Feature/ModelImplementationTest.php`.
- `docs/thiet_ke_co_so_du_lieu/MODEL_IMPLEMENTATION_REPORT.md`.

Git status xác nhận các file mới chính xác:

- `BE/app/Models/BaiTap.php`
- `BE/app/Models/BaiTapDungCu.php`
- `BE/app/Models/BaiTapNhomCo.php`
- `BE/app/Models/BaiTapTrongGiaoAn.php`
- `BE/app/Models/BaiTapTrongKeHoach.php`
- `BE/app/Models/BaiTapTrongPhien.php`
- `BE/app/Models/BaiTapUngVien.php`
- `BE/app/Models/BuoiTapDuKien.php`
- `BE/app/Models/ChiNhanh.php`
- `BE/app/Models/ChiSoCoThe.php`
- `BE/app/Models/DangKyGoiTap.php`
- `BE/app/Models/DeXuatKeHoachTap.php`
- `BE/app/Models/DonMuaGoi.php`
- `BE/app/Models/DungCu.php`
- `BE/app/Models/DungCuHoiVien.php`
- `BE/app/Models/GhiChuHuanLuyen.php`
- `BE/app/Models/GiaoAnMau.php`
- `BE/app/Models/GiaoAnUngVien.php`
- `BE/app/Models/GoiTap.php`
- `BE/app/Models/HiepTap.php`
- `BE/app/Models/HoSoHoiVien.php`
- `BE/app/Models/HoSoHuanLuyenVien.php`
- `BE/app/Models/HoiThoai.php`
- `BE/app/Models/HoiThoaiTroLy.php`
- `BE/app/Models/KeHoachTap.php`
- `BE/app/Models/KyHanHoiVien.php`
- `BE/app/Models/LanGoiMoHinh.php`
- `BE/app/Models/LanThanhToan.php`
- `BE/app/Models/LichSuSuDungHuanLuyenVien.php`
- `BE/app/Models/LichSuVaoPhongTap.php`
- `BE/app/Models/MaVaoPhongTap.php`
- `BE/app/Models/NgayRanhHoiVien.php`
- `BE/app/Models/NgayTrongGiaoAn.php`
- `BE/app/Models/NgayTrongKeHoach.php`
- `BE/app/Models/NguoiDung.php`
- `BE/app/Models/NhatKyHeThong.php`
- `BE/app/Models/NhomCo.php`
- `BE/app/Models/PhanCongHuanLuyenVien.php`
- `BE/app/Models/PhanQuyenNguoiDung.php`
- `BE/app/Models/PhienBanKeHoachTap.php`
- `BE/app/Models/PhienTap.php`
- `BE/app/Models/QuyenLoiGoiTap.php`
- `BE/app/Models/SuDungQuyenLoi.php`
- `BE/app/Models/SuKienPhatTinNhan.php`
- `BE/app/Models/SuKienThanhToan.php`
- `BE/app/Models/TheTruyCap.php`
- `BE/app/Models/TinNhan.php`
- `BE/app/Models/TinNhanTroLy.php`
- `BE/app/Models/VaiTro.php`
- `BE/app/Models/YeuCauChongLap.php`
- `BE/app/Models/YeuCauDatLaiMatKhau.php`
- `BE/app/Models/YeuCauTroLy.php`
- `BE/tests/Feature/ModelImplementationTest.php`
- `docs/thiet_ke_co_so_du_lieu/MODEL_IMPLEMENTATION_REPORT.md`

Không sửa migration, schema design, data dictionary, ERD, Q01–Q13 hoặc các workflow nghiệp vụ.

## 11. Deviations

Không có deviation logic/schema. Composite FK không được fake trong Eloquent; đây là giới hạn kỹ thuật chuẩn đã ghi rõ ở mục 6. Không thêm package hay dependency.

## 12. Final Gate

MODEL IMPLEMENTATION = PASS
52 CORE MODELS = IMPLEMENTED
MODEL DATABASE TESTS = PASS
DATABASE = READY FOR SEEDER IMPLEMENTATION

NEXT RECOMMENDED STEP: SEEDER DESIGN / IMPLEMENTATION

