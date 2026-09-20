# Domain: Exercise & Workout Rules

> **Module Path:** `.fitness-rules/domains/workout.md`  
> **Canonical Reference:** [PROJECT_RULES.md](../../PROJECT_RULES.md) (Phần VII: 19–29; Phần VIII: 30; Phần XX: Rule 47; RULE GYM 11–16; Q07A–C, Q08, Q11, Q12)

---

## 1. Thư viện Bài tập, Dụng cụ & Giáo án Mẫu (Catalog & Templates)

### Danh mục Bài tập & Nhóm cơ (Section 19)
- Thư viện bài tập bao gồm 5 bảng chuẩn:
  - Bài tập: `bai_tap`
  - Nhóm cơ: `nhom_co`
  - Dụng cụ: `dung_cu` (không dùng `dung_cu_tap`)
  - Bảng liên kết bài tập – nhóm cơ: `bai_tap_nhom_co` (phân loại nhóm cơ chính `CHINH`, nhóm cơ phụ `PHU`)
  - Bảng liên kết bài tập – dụng cụ: `bai_tap_dung_cu`
- Equipment và Exercise dùng lifecycle/status của resource và API contract tương ứng; deactivation không hard-delete, không rewrite history và không được suy ra từ lifecycle Muscle Group. Không gán một cờ `hoat_dong` chung cho mọi catalog.

### M061 — Muscle Group lifecycle

- `nhom_co.trang_thai` chỉ nhận `HOAT_DONG` hoặc `NGUNG_SU_DUNG`, mặc định `HOAT_DONG`. GET trả cả inactive; Admin chỉ dùng GET/POST/PATCH, không có DELETE.
- Chỉ nhóm active mới được dùng để tạo `bai_tap_nhom_co` mới. Ngừng sử dụng không xóa/sửa pivot hiện hữu hoặc history; giữ nguyên id, vai trò `CHINH`/`PHU`, `ngay_tao`, `ngay_cap_nhat` và vẫn đọc được kèm status.
- Khi PATCH Exercise có replacement `muscle_groups`, mọi pivot inactive hiện hữu phải được gửi lại đúng vai trò. Bỏ field `muscle_groups` chỉ cập nhật các chiều khác. Thêm, bỏ, hoặc đổi vai trò của relation inactive bị từ chối nguyên tử với `INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE`.
- PATCH Muscle Group cùng trạng thái sau chuẩn hóa là no-op, không đổi timestamp/audit. Chuyển trạng thái thật ghi một audit `CAP_NHAT_TRANG_THAI_NHOM_CO` trong cùng transaction; khóa hàng và revalidate để serialize race status/relation.

### Q11 – Quan hệ Bài tập và Dụng cụ mang nghĩa AND
- Quan hệ bài tập – dụng cụ được quản lý qua bảng trung gian `bai_tap_dung_cu`.
- Mọi hàng `bai_tap_dung_cu` của một bài mang **ngữ nghĩa AND bắt buộc**:
  - *Ví dụ:* Một bài tập Bench Press yêu cầu cả Ghế tập (Bench) và Đòn tạ (Barbell) $\rightarrow$ Phòng tập hoặc người dùng phải có đủ **cả 2 dụng cụ** thì mới được coi là đủ điều kiện thực hiện.
  - Thiếu bất kỳ một dụng cụ nào trong danh sách đều bị hệ thống coi là không đủ điều kiện (không tự suy diễn quan hệ OR).

### Giáo án Mẫu & Copy-on-Write (Workout Templates — Section 20)
- Giáo án mẫu của Admin: `giao_an_mau`, `ngay_trong_giao_an`, `bai_tap_trong_giao_an`.
- **Cơ chế Copy-on-write:** Khi Member hoặc PT sử dụng giáo án mẫu, hệ thống sao chép (clone) dữ liệu sang kế hoạch riêng của Member (`ke_hoach_tap` / `phien_ban_ke_hoach_tap`).
- **`WORKOUT_TEMPLATE_STALE` recovery:** Đây là kết quả 409 optimistic-concurrency khi tạo revision với `expected_content_version` không còn khớp. Client giữ draft, tải lại base và cho người dùng so sánh; đây không phải cờ persistent gắn vào Plan của Member.
- Copy-on-write và bảo toàn version/history của Plan là invariant độc lập: cập nhật Template không sửa đè Plan/Session đã sao chép.

---

## 2. Kế hoạch Tập luyện & Phiên bản (Plans & Versions)

### Q07A – Tối đa Một Kế hoạch đang sử dụng
- Mỗi Member chỉ có **tối đa 1 bản ghi** `ke_hoach_tap` ở trạng thái `trang_thai = DANG_SU_DUNG` tại bất kỳ thời điểm nào.
- Khi áp dụng kế hoạch mới (do AI đề xuất hoặc PT chỉ định sau khi Member duyệt):
  - Kế hoạch cũ tự động chuyển trạng thái sang `LUU_TRU`.
  - Không bao giờ cho phép tồn tại đồng thời 2 kế hoạch cùng active cho một hội viên.

### Phân biệt Kế hoạch, Phiên bản và Lịch tập (Section 21–24)
- **Workout Plan (`ke_hoach_tap`):** Khung chương trình tổng thể theo giai đoạn (ví dụ: Kế hoạch tăng cơ 8 tuần).
- **Workout Plan Version (`phien_ban_ke_hoach_tap`):** Khi kế hoạch được điều chỉnh giữa chừng, tạo version mới để lưu vết lịch sử, không ghi đè version cũ.
- **Ngày trong Kế hoạch (`ngay_trong_ke_hoach`):** Cấu trúc từng ngày tập của phiên bản kế hoạch.
- **Bài tập trong Kế hoạch (`bai_tap_trong_ke_hoach`):** Danh sách bài tập, số set, rep, mức tạ dự kiến.
- **Buổi tập dự kiến (`buoi_tap_du_kien`):** Lịch tập chi tiết cho từng ngày cụ thể theo lịch thực tế (`ngay_tap`, `trang_thai`).

### Q07B – Tối đa Một Buổi tập dự kiến giữ slot/ngày
- **Tập trạng thái giữ slot ngày:** Các trạng thái `CHUA_TAP`, `DANG_TAP`, `HOAN_THANH`, `BO_QUA` giữ slot của ngày tập (`ngay_tap_con_hieu_luc = ngay_tap`). Mỗi Member chỉ có tối đa **1 buổi tập dự kiến** giữ slot cho mỗi `ngay_tap` (ràng buộc `(hoi_vien_id, ngay_tap_con_hieu_luc) UNIQUE` theo `TU_DIEN_DU_LIEU.md` Bảng 37: `buoi_tap_du_kien`, dòng 1431, 1434, 1456).
- **Tập trạng thái giải phóng slot:** Các trạng thái `HUY` và `DA_THAY_THE` trả về `ngay_tap_con_hieu_luc = NULL` để giải phóng slot ngày trong khi vẫn bảo toàn đầy đủ bản ghi lịch sử, không xóa dữ liệu cũ.
- Cấm tạo trùng lịch 2 buổi tập trong cùng một ngày nếu buổi hiện tại chưa được giải phóng slot bằng `HUY` hoặc `DA_THAY_THE`.

### RULE GYM 16 – Kế hoạch tương lai được phép thay đổi
- Các buổi tập trong tương lai (`buoi_tap_du_kien`) đang ở trạng thái `CHUA_TAP` **được phép chỉnh sửa** bài tập, set, rep hoặc chuyển ngày khi có yêu cầu hợp lệ từ Member hoặc qua Proposal của AI/PT.

---

## 3. Thực thi Phiên tập & Tính Bất biến (Workout Session & Immutability)

### Section 28 – Kế hoạch và Thực tế là hai dòng dữ liệu độc lập
- Dữ liệu dự kiến (`buoi_tap_du_kien`, `bai_tap_trong_ke_hoach`) đại diện cho **kế hoạch đặt ra**.
- Dữ liệu thực tế (`phien_tap`, `bai_tap_trong_phien`, `hiep_tap`) đại diện cho **những gì Member thực sự nâng được trong phòng gym**.
- Khi bắt đầu tập: Tạo `phien_tap` (`trang_thai = DANG_TAP`, `bat_dau_luc = NOW()`).
- Trong lúc tập: Member log từng set thực tế vào `hiep_tap` (`khoi_luong_kg`, `so_rep_thuc_te`, `thu_tu_hiep`, `loai_hiep`, `rpe`).
- Khi kết thúc: chuyển `phien_tap.trang_thai = HOAN_THANH` và chốt các kết quả thực tế theo contract. Không tự invent cột hoặc side effect như tổng calo/volume nếu authority không yêu cầu.

### Rule 47 (Section 46.1) – Tập Tự do là Future Development
- Hiện tại, mọi phiên tập (`phien_tap`) bắt buộc phải gắn với một buổi tập dự kiến:
  $$\text{phien\_tap.buoi\_tap\_du\_kien\_id IS NOT NULL}$$
- Tính năng tập tự do (Free Workout không cần lịch trước) là kế hoạch phát triển tương lai (**FUTURE DEVELOPMENT**). Hiện tại cấm gửi `buoi_tap_du_kien_id = null`.

### RULE GYM 13 – Buổi tập đã hoàn thành là BẤT BIẾN (Immutable)
- Một khi `phien_tap` đã chuyển sang `trang_thai = HOAN_THANH`:
  - **TUYỆT ĐỐI CẤM** sửa đổi, cập nhật số set, rep, mức tạ, hoặc xóa bài tập trong phiên tập đó.
  - Toàn bộ dữ liệu của phiên tập trở thành **bất biến vĩnh viễn** để bảo toàn tính toàn vẹn của lịch sử tập luyện và tính toán quá tải lũy tiến (Progressive Overload).

### RULE GYM 14 & 15 – Cấm AI và PT sửa buổi tập đã hoàn thành
- **RULE GYM 14:** AI chỉ được đọc lịch sử các buổi tập đã hoàn thành để phân tích và đề xuất điều chỉnh tương lai; cấm can thiệp vào bất kỳ buổi tập nào trong quá khứ.
- **RULE GYM 15:** PT không được sửa bài tập hay kết quả set của học viên. PT có thể ghi `ghi_chu_huan_luyen` theo contract; ghi chú không được mutate Plan hoặc Workout history.

### Q07C – Phiên tập đã hủy và tập lại
- Khi một phiên tập bị hủy (`phien_tap.trang_thai = HUY`), lịch cũ không được phép `Start` lại trên chính lịch/phiên đó.
- Hệ thống giữ nguyên record phiên hủy và FK `phien_tap.buoi_tap_du_kien_id` để audit (Q12). Nếu muốn tập lại, Member phải dùng workflow schedule/version đã có để tạo/chọn một lịch thay thế hợp lệ, đúng ownership và slot; không tạo lịch giả, không đổi FK cũ.

---

## 4. Quyền Tập luyện Cơ bản & Bảo toàn Lịch sử

### Q08 – Workout Tracking là Chức năng Cơ bản của Member
- Member được xem Plan/Schedule của mình và Start, Save Set, Complete từ một `buoi_tap_du_kien` hiện có, hợp lệ, thuộc đúng Plan/Version và đủ điều kiện.
- Q08 không cấp quyền tạo schedule trực tiếp, tạo session không có schedule, hoặc Free Workout. Free Workout ngoài lịch là FUTURE DEVELOPMENT; `phien_tap.buoi_tap_du_kien_id` luôn NOT NULL.
- Không có gói hoặc Membership `HET_HAN` vẫn được thực hiện các thao tác Q08 và xem history. Backend vẫn kiểm tra ownership, status, validation, concurrency và idempotency; không tạo paid usage/activation.

### RULE GYM 11 & 12 – Lịch sử Tập luyện Tồn tại Độc lập
- **RULE GYM 11:** Khi Membership hết hạn, hội viên vẫn truy cập và xem đầy đủ toàn bộ lịch sử các buổi tập trong quá khứ.
- **RULE GYM 12:** Lịch sử tập luyện gắn liền vĩnh viễn với tài khoản của người dùng, không bao giờ bị phụ thuộc, ẩn đi hay xóa bỏ theo chu kỳ gói tập.

### Q12 & Section 29 – Bảo toàn Lịch sử Dữ liệu (Audit Trail)
- **Không hard-delete dữ liệu lịch sử quan trọng:** Tuyệt đối cấm các câu lệnh `DELETE FROM phien_tap`, `DELETE FROM bai_tap_trong_phien`, hoặc `DELETE FROM hiep_tap` trong cơ sở dữ liệu production.
- **Bảo toàn ngữ nghĩa lịch sử theo từng miền nghiệp vụ:** Giữ các bản ghi buổi tập dự kiến `HUY` và `DA_THAY_THE`, phiên tập `HUY`, cùng toàn bộ phiên tập hoàn thành (`HOAN_THANH`) bất biến theo đúng canonical domain rules. Lifecycle vô hiệu hóa của Equipment/Exercise và M061 của Muscle Group là các quy tắc riêng; không áp đặt cờ hoặc cơ chế soft-delete phổ quát lên mọi bảng workout.
- PT notes là dữ liệu tư vấn riêng; chỉ nêu rằng note không được mutate Plan hoặc Session/history, không mặc định gắn note vào một completed Session.
