# Domain: Database Schema & Data Integrity Rules

> **Module Path:** `.fitness-rules/domains/database.md`  
> **Canonical Reference:** [PROJECT_RULES.md](../../PROJECT_RULES.md) (Phần II: 6–6.1; Phần XIII: RULE CODE 01–04; Q12)  
> **Từ điển dữ liệu chính thức:** [TU_DIEN_DU_LIEU.md](../../docs/thiet_ke_co_so_du_lieu/TU_DIEN_DU_LIEU.md)

---

## 1. Tiêu chuẩn Kỹ thuật Database

- **Hệ quản trị CSDL (DBMS):** MariaDB 10.4.32 / InnoDB (mục tiêu tương đương kiểm thử preflight chính thức; loại bỏ các chỉ định MariaDB 10.4.32+ hoặc MySQL 8.0+).
- **Storage Engine:** Bắt buộc `InnoDB` cho toàn bộ 52 bảng để đảm bảo hỗ trợ ACID Transaction và Foreign Key Constraints.
- **Character Set & Collation:** Bắt buộc `utf8mb4` và `utf8mb4_unicode_ci` (hỗ trợ tiếng Việt có dấu và emoji tin nhắn chat).

---

## 2. Quy chuẩn Đặt tên CSDL (Database Naming Rules)

### RULE CODE 01 – Database dùng tiếng Việt không dấu, snake_case
- Toàn bộ tên bảng (`table_name`), tên cột (`column_name`), tên chỉ mục (`index_name`), và khóa ngoại (`foreign_key`) phải viết bằng **tiếng Việt không dấu, chữ thường, phân cách bằng dấu gạch dưới (`snake_case`)**.
- *Đúng:* `nguoi_dung`, `ky_han_hoi_vien`, `bai_tap`, `buoi_tap_du_kien`.
- *Sai:* `users`, `memberships`, `tbl_exercise`, `user_id`.

### RULE CODE 02 – Không viết tắt trong Database
- Tên cột và tên bảng phải được đặt đầy đủ, tường minh, không viết tắt gây mơ hồ:
  - *Đúng:* `so_dien_thoai`, `ngay_sinh`, `ngay_bat_dau`, `thoi_han_ngay`, `khoi_luong_kg`.
  - *Sai:* `sdt`, `dob`, `start_date`, `duration`, `weight`.

### RULE CODE 03 – Khóa ngoại (Foreign Key) rõ nghĩa
- Dùng đúng tên FK đã có trong từ điển dữ liệu. Ví dụ `hoi_vien_id` tham chiếu `ho_so_hoi_vien.id`, `huan_luyen_vien_id` tham chiếu `ho_so_huan_luyen_vien.id`; không tự đổi FK chỉ để ghép với toàn bộ tên bảng đích.
- *Đúng:* `nguoi_dung_id`, `hoi_vien_id`, `goi_tap_id`, `bai_tap_id`, `ke_hoach_tap_id`, `chi_nhanh_id`, `phan_cong_huan_luyen_vien_id`.
- *Không dùng:* `uid`, `mid`, `eid`, `pid`, `fk_user`, `user_ref`, `parent_id`, `id_nguoi_dung`.

---

## 3. Danh mục Chính thức 52 Bảng Chuẩn Hệ thống (RULE CODE 04)

Danh mục **52 bảng** chuẩn theo [TU_DIEN_DU_LIEU.md](../../docs/thiet_ke_co_so_du_lieu/TU_DIEN_DU_LIEU.md) và tên bảng chính thức trong `PROJECT_RULES.md`:

1. `chi_nhanh`
2. `nguoi_dung`
3. `vai_tro`
4. `phan_quyen_nguoi_dung`
5. `ho_so_hoi_vien`
6. `ho_so_huan_luyen_vien`
7. `ngay_ranh_hoi_vien`
8. `dung_cu_hoi_vien`
9. `chi_so_co_the`
10. `the_truy_cap`
11. `yeu_cau_dat_lai_mat_khau`
12. `goi_tap`
13. `quyen_loi_goi_tap`
14. `don_mua_goi`
15. `lan_thanh_toan`
16. `su_kien_thanh_toan`
17. `dang_ky_goi_tap`
18. `ky_han_hoi_vien`
19. `su_dung_quyen_loi`
20. `ma_vao_phong_tap`
21. `lich_su_vao_phong_tap`
22. `phan_cong_huan_luyen_vien`
23. `lich_su_su_dung_huan_luyen_vien`
24. `ghi_chu_huan_luyen`
25. `dung_cu`
26. `nhom_co`
27. `bai_tap`
28. `bai_tap_dung_cu`
29. `bai_tap_nhom_co`
30. `giao_an_mau`
31. `ngay_trong_giao_an`
32. `bai_tap_trong_giao_an`
33. `ke_hoach_tap`
34. `phien_ban_ke_hoach_tap`
35. `ngay_trong_ke_hoach`
36. `bai_tap_trong_ke_hoach`
37. `buoi_tap_du_kien`
38. `phien_tap`
39. `bai_tap_trong_phien`
40. `hiep_tap`
41. `hoi_thoai`
42. `tin_nhan`
43. `su_kien_phat_tin_nhan`
44. `hoi_thoai_tro_ly`
45. `tin_nhan_tro_ly`
46. `yeu_cau_tro_ly`
47. `bai_tap_ung_vien`
48. `giao_an_ung_vien`
49. `lan_goi_mo_hinh`
50. `de_xuat_ke_hoach_tap`
51. `nhat_ky_he_thong`
52. `yeu_cau_chong_lap`

---

## 4. Bảng Ánh xạ Khái niệm Dễ Nhầm (RULE CODE 04 Canonical Mapping)

| Khái niệm nghiệp vụ | Bảng / cột chính thức bắt buộc dùng | Lưu ý chống nhầm lẫn |
|---|---|---|
| **Membership / Chuỗi gói & Kỳ hạn** | `dang_ky_goi_tap` là chuỗi mua; `ky_han_hoi_vien` là từng kỳ hạn; `su_dung_quyen_loi` ghi nhận lượt dùng. | Không nhầm lẫn giữa chuỗi (`dang_ky_goi_tap`) và kỳ cụ thể (`ky_han_hoi_vien`). |
| **Cấu hình & Snapshot quyền lợi** | `quyen_loi_goi_tap` và `ky_han_hoi_vien` cùng dùng 5 cột:<br>- `cho_phep_vao_phong_tap`<br>- `cho_phep_tro_ly_tap_luyen`<br>- `gioi_han_luot_tro_ly`<br>- `cho_phep_tro_chuyen_huan_luyen_vien`<br>- `so_buoi_huan_luyen_vien` | Không tự ý dùng tên tiếng Anh (`gym_access`, `pt_sessions`...). |
| **Đơn mua & Thanh toán payOS** | `don_mua_goi`, `lan_thanh_toan`, `su_kien_thanh_toan`. | Ba bảng riêng biệt, không gộp thành 1. |
| **QR vào phòng & Check-in** | `ma_vao_phong_tap`, `lich_su_vao_phong_tap`. | Không nhầm với mã QR thanh toán payOS. |
| **Hồ sơ & Phân công PT** | `ho_so_huan_luyen_vien`, `phan_cong_huan_luyen_vien`. | Tên bảng viết đầy đủ `huan_luyen_vien`, không viết tắt `pt`. |
| **Chat PT 1–1** | `hoi_thoai` (có `hoi_vien_id`, `huan_luyen_vien_id`, `phan_cong_huan_luyen_vien_id`); tin nhắn ở `tin_nhan`, outbox ở `su_kien_phat_tin_nhan`. | **Không có bảng thành viên hội thoại riêng.** |
| **Hội thoại & Yêu cầu AI** | `hoi_thoai_tro_ly`, `tin_nhan_tro_ly`, `yeu_cau_tro_ly`, `lan_goi_mo_hinh`. | Một yêu cầu nghiệp vụ (`yeu_cau_tro_ly`) không đồng nghĩa với một lần gọi mô hình (`lan_goi_mo_hinh`). |
| **Proposal Kế hoạch** | Dùng chung một bảng `de_xuat_ke_hoach_tap`, phân biệt bằng cột `nguon_de_xuat` (`TRO_LY` hoặc `HUAN_LUYEN_VIEN`). AI/PT chỉ là nhãn hiển thị/domain label. | **Không tách riêng bảng Proposal cho AI.** |
| **Workout Plan & Lịch sử tập** | `phien_ban_ke_hoach_tap` lưu phiên bản kế hoạch; `phien_tap`, `bai_tap_trong_phien`, `hiep_tap` lưu thực tế.<br>Mốc phiên tập: `phien_tap.bat_dau_luc` / `phien_tap.ket_thuc_luc`. | Buổi tập thực tế lưu ở `hiep_tap` (không phải `set_tap`). `phien_tap.buoi_tap_du_kien_id` là `NOT NULL`. |

---

## 5. Bảo toàn Lịch sử Dữ liệu (Audit Trail — Q12)

- Trong MVP không hard-delete lịch sử quan trọng: đơn mua gói, lần/sự kiện thanh toán, chuỗi/kỳ/snapshot Membership, sử dụng quyền lợi, check-in, phân công/lượt PT, Plan/Version/Schedule, phiên/bài/hiệp tập, tin nhắn chat, request AI/provider/Proposal và audit nhật ký hệ thống.
- **Bảo toàn ngữ nghĩa lịch sử theo từng domain cụ thể (không biến thành công thức soft-delete/trạng thái chung tùy tiện):**
  - Giữ cả lịch tập `HUY` và `DA_THAY_THE`, giữ phiên tập `HUY` để audit.
  - Giữ lịch sử phân công PT đã kết thúc qua khoảng thời gian `[ngay_bat_dau, ngay_ket_thuc)`.
  - Snapshot, history identity, FK và audit được bảo toàn. Ledger/request có thể chuyển qua trạng thái canonical (ví dụ giữ chỗ quota → đã tính/đã trả hoặc request không áp dụng) trong transaction; bảo toàn lịch sử không cấm các transition này.
  - Tài khoản hoặc catalog danh mục bị khóa/ngừng sử dụng theo quy tắc riêng; tuyệt đối không cascade xóa lịch sử liên quan.
- Tuyệt đối không tự ý bịa thêm cơ chế soft-delete phổ quát hoặc áp đặt trạng thái lưu trữ (như biến `LUU_TRU` thành công thức xóa mềm dùng chung) cho các bảng không có quy định này trong từ điển dữ liệu.

## 6. M061 — Muscle Group mapping

- `nhom_co.trang_thai` chỉ nhận `HOAT_DONG` hoặc `NGUNG_SU_DUNG`, mặc định `HOAT_DONG`. GET catalog gồm cả inactive; Admin dùng GET/POST/PATCH và không có DELETE.
- Tạo `bai_tap_nhom_co` mới chỉ nhận nhóm active. Khi nhóm inactive, pivot hiện hữu và history không bị xóa/sửa; giữ nguyên id, vai trò, `ngay_tao`, `ngay_cap_nhat` và vẫn đọc được cùng status.
- PATCH Exercise thay thế `muscle_groups` phải gửi lại mọi pivot inactive hiện hữu với đúng vai trò; bỏ field này là không cập nhật quan hệ. Bất kỳ thêm/bỏ/đổi vai trò quan hệ inactive đều fail nguyên tử với `INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE`.
- PATCH nhóm cùng trạng thái sau chuẩn hóa là no-op (không timestamp/audit). Chuyển trạng thái thật ghi audit action `CAP_NHAT_TRANG_THAI_NHOM_CO` cùng transaction; khóa hàng và đọc lại để serialize race status/relation.
