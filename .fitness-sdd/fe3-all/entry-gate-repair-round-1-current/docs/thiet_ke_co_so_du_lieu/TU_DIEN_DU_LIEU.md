# Từ điển dữ liệu — Smart Fitness Platform

**DATABASE DESIGN = APPROVED FOR MARIADB 10.4.32 — Q01–Q13 đã đồng bộ và kiểm tra ngày 29/08/2026. Không có bảng/migration nào được tạo/chạy.** Giữ các quyết định trước về Chat PT/quota buổi, cấp lại Role, Workout từ lịch và usage Chat. Xem [phân tích nghiệp vụ và các quyết định đã chốt](THIET_KE_DATABASE.md).

### Addendum hậu baseline — 10/09/2026 (M061)

Baseline 52 bảng/575 cột ở trên là bằng chứng thiết kế ngày 29/08/2026 và không bị viết lại. Migration additive M061 hiện bổ sung một cột vào B26 (không thêm bảng, FK hay index): `nhom_co.trang_thai VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL DEFAULT 'HOAT_DONG'`, với CHECK `kiem_tra_b26_01` chỉ nhận `HOAT_DONG` hoặc `NGUNG_SU_DUNG`. Dữ liệu B26 trước M061 được backfill `HOAT_DONG`; nhóm ngừng sử dụng vẫn được đọc và giữ các quan hệ B29 lịch sử.

## Quy ước đọc

- Tổng cộng **52 bảng, 575 cột** (kể cả PK/timestamp/cột sinh), 113 tham chiếu FK cột và 31 FK kép bổ sung bảo vệ ownership.
- Mỗi bảng: PK `id BIGINT UNSIGNED AUTO_INCREMENT`; `Không` ở cột NULL nghĩa là NOT NULL. Cột id/_id theo ngoại lệ kỹ thuật trong PROJECT_RULES.
- Kiểu và điều kiện là thiết kế dự kiến, chưa có DDL. CHECK diễn đạt bằng văn bản sẽ được chuyển thành biểu thức phù hợp MariaDB 10.4 khi bước triển khai migration được yêu cầu. Kiểm tra qua bảng hoặc trạng thái cũ thực hiện bằng service/transaction, không bằng CHECK chéo bảng.
- FK đơn mặc định trỏ tới `id` của bảng đích. Tất cả FK dùng RESTRICT khi xóa/sửa khóa; không cascade history. FK kép nêu riêng, UNIQUE tuple ở bảng cha đã liệt kê đủ.
- UNIQUE có NULL cho phép nhiều NULL; đối với FK kép có NULL cần kiểm tra workflow trong Backend. Các UNIQUE chứa id phục vụ FK kép, không phải khóa nghiệp vụ mới.
- Quan hệ `1 → 0..N`: một cha có nhiều con; `1 → 0..1`: FK ở con cũng UNIQUE. FK nullable cho phép một con chưa có cha ở giai đoạn được nêu.
- Giá trị mặc định ghi trong mô tả; các cột bắt buộc khác do Backend cấp khi tạo. Cờ BOOLEAN luôn ràng buộc 0/1; trạng thái luôn giới hạn tập giá trị trong mô tả. `ngay_tao`/`ngay_cap_nhat` do server cấp UTC.
- Có 98 bộ UNIQUE ngoài 52 PK và 48 khai báo index truy vấn bổ sung (chưa khử trùng với UNIQUE/index FK tự có); không coi đây là tổng index vật lý đã tạo.
- Cột sinh (generated) không được client ghi. Danh mục lưu giá trị Việt có dấu bình thường; chỉ tên bảng/cột không dấu.

## Quy ước DBMS vật lý — MariaDB 10.4.32 / InnoDB

- Từ điển này là nguồn tên và mô hình logic: giữ nguyên 52 bảng, 575 cột, PK/FK/UNIQUE, 5 cột sinh và các CHECK nghiệp vụ đã duyệt. Không thêm bảng/cột chỉ vì đổi DBMS.
- Các cột khai báo `JSON` vẫn giữ kiểu logic `JSON`. Trên MariaDB 10.4, kiểu này được lưu như `LONGTEXT` với `utf8mb4_bin` và có kiểm tra `JSON_VALID` ngầm; đó là khác biệt vật lý so với binary JSON, không phải CHECK nghiệp vụ mới. Migration/P0 phải tách các CHECK JSON tự sinh khỏi 83 CHECK nghiệp vụ trong manifest.
- `DATETIME(6)`, số unsigned, DECIMAL, DATE và TIME giữ nguyên. Cột sinh VIRTUAL chỉ dựa trên dữ liệu cùng hàng, có thể đánh index/UNIQUE; không làm FK và không dùng NOW()/subquery.
- Charset mặc định và override kỹ thuật được ghi trong [MIGRATION_PLAN.md](MIGRATION_PLAN.md): `utf8mb4_unicode_ci` cho text nghiệp vụ, `utf8mb4_nopad_bin` cho chuỗi kỹ thuật và email sau khi Backend trim/NFC/lowercase. Không còn tham chiếu `utf8mb4_0900_*` trong thiết kế hoạt động.
- MariaDB InnoDB thực thi CHECK/FK/UNIQUE ở mức statement. FK ghép có thành phần NULL được phép và không kiểm tra quan hệ cha; ownership, overlap, quota, trạng thái trước và bất biến lịch sử vẫn là service/transaction.
- Review chuyển DBMS và nguồn kỹ thuật nằm tại [THIET_KE_DATABASE.md](THIET_KE_DATABASE.md#31-review-chuyển-dbms-mysql--mariadb). Chưa chạy P0, SQL/DDL hoặc migration trong vòng review này.

## Ràng buộc bổ sung từ Q01–Q13

- Q04: `phan_cong_huan_luyen_vien.hoi_vien_dang_phan_cong_id` + UNIQUE chỉ ngăn hai khoảng mở. Overlap của mọi khoảng cùng Member vẫn kiểm tra dưới khóa hội viên, dùng khoảng nửa mở; chi tiết B22 và mục 7 của phân tích.
- Q07A: `ke_hoach_tap.hoi_vien_dang_su_dung_id` + UNIQUE ngăn hai Plan DANG_SU_DUNG; LUU_TRU trả NULL.
- Q07B: `buoi_tap_du_kien.ngay_tap_con_hieu_luc` + UNIQUE cùng Member giữ một slot/ngày. CHUA_TAP/DANG_TAP/HOAN_THANH/BO_QUA giữ slot; HUY/DA_THAY_THE trả NULL. Không xóa history.
- Q05: thêm `hoi_thoai.phan_cong_huan_luyen_vien_id` NOT NULL UNIQUE; một hội thoại của một lần phân công. Thay UNIQUE cặp Member/PT bằng UNIQUE phân công; FK kép hội thoại–phân công và tin–hội thoại bảo vệ đúng lần phân công. Không mở lại hội thoại của phân công đã kết thúc.
- Ba cột sinh mới dùng VIRTUAL, chỉ đọc cột cùng hàng, không dùng NOW()/subquery và không là FK. Hai cột sinh đã có (`dang_ky_goi_tap.hoi_vien_chua_ket_thuc_id`, `buoi_tap_du_kien.ma_buoi_con_hieu_luc`) giữ nguyên ý nghĩa, ghi rõ VIRTUAL. Tổng cộng 5 cột sinh.
- UNIQUE nullable cho phép giữ nhiều hàng lịch sử NULL; CHECK không kiểm tra overlap nhiều hàng. Đây là thiết kế logic giữ nguyên khi chuyển sang MariaDB 10.4.32, chưa kiểm chứng bằng DDL trong vòng review này. [MariaDB generated columns](https://mariadb.com/docs/server/reference/sql-statements/data-definition/create/generated-columns), [MariaDB constraints](https://mariadb.com/docs/server/reference/sql-statements/data-definition/constraint).
- Q12 áp dụng mọi bảng lịch sử: không hard-delete, không cascade account/catalog làm mất dữ liệu. Không đặt số năm retention hoặc tự triển khai purge/anonymization.
- Thay đổi thuần cấu trúc: 4 cột thêm, 1 FK đơn thêm, 1 FK kép thêm ròng, 3 UNIQUE thêm ròng; không đổi tên 52 bảng hay thêm bảng.

## Danh mục bảng

| STT | Bảng | Nhóm | Mục đích |
| --- | --- | --- | --- |
| 1 | [chi_nhanh](#b01) | Tài khoản, hồ sơ và phân quyền | Chi nhánh phòng gym; MVP chỉ sử dụng một chi nhánh. |
| 2 | [nguoi_dung](#b02) | Tài khoản, hồ sơ và phân quyền | Tài khoản chung của bốn actor; không lưu cờ Premium. |
| 3 | [vai_tro](#b03) | Tài khoản, hồ sơ và phân quyền | Danh mục bốn vai trò, tách biệt quyền lợi gói. |
| 4 | [phan_quyen_nguoi_dung](#b04) | Tài khoản, hồ sơ và phân quyền | Gán vai trò cho tài khoản; authorization chi tiết vẫn kiểm tra resource. |
| 5 | [ho_so_hoi_vien](#b05) | Tài khoản, hồ sơ và phân quyền | Hồ sơ Member, mục tiêu, kinh nghiệm và mốc chống đề xuất lỗi thời. |
| 6 | [ho_so_huan_luyen_vien](#b06) | Tài khoản, hồ sơ và phân quyền | Hồ sơ PT, không phải nhân sự/tiền lương. |
| 7 | [ngay_ranh_hoi_vien](#b07) | Thể chất và điều kiện tập | Các thứ trong tuần hội viên có thể tập. |
| 8 | [dung_cu_hoi_vien](#b08) | Thể chất và điều kiện tập | Dụng cụ hội viên có thể sử dụng để chọn bài phù hợp. |
| 9 | [chi_so_co_the](#b09) | Thể chất và điều kiện tập | Lịch sử số đo phục vụ Progress, độc lập Membership. |
| 10 | [the_truy_cap](#b10) | Thông tin xác thực | Token truy cập Mobile được băm; thiết kế xác thực, chưa triển khai Sanctum. |
| 11 | [yeu_cau_dat_lai_mat_khau](#b11) | Thông tin xác thực | Yêu cầu quên mật khẩu, dùng một lần và hết hạn. |
| 12 | [goi_tap](#b12) | Gói tập, đơn mua và payOS | Danh mục gói đang bán; thay đổi chỉ tác động các lần mua sau. |
| 13 | [quyen_loi_goi_tap](#b13) | Gói tập, đơn mua và payOS | Cấu hình quyền lợi hiện tại của gói; quan hệ 1:1 thay vì EAV. |
| 14 | [don_mua_goi](#b14) | Gói tập, đơn mua và payOS | Đơn một gói/một kỳ, nguồn thanh toán và lịch sử mua/gia hạn. |
| 15 | [lan_thanh_toan](#b15) | Gói tập, đơn mua và payOS | Mỗi lần tạo liên kết thanh toán payOS; một đơn có thể có nhiều lần thử. |
| 16 | [su_kien_thanh_toan](#b16) | Gói tập, đơn mua và payOS | Inbox webhook với xác thực, chống lặp và chẩn đoán đối soát. |
| 17 | [dang_ky_goi_tap](#b17) | Membership và kỳ quyền lợi | Một chuỗi Membership liên tục gồm nhiều kỳ đã mua; mua lại sau toàn bộ chuỗi hết hạn tạo chuỗi mới. |
| 18 | [ky_han_hoi_vien](#b18) | Membership và kỳ quyền lợi | Một kỳ quyền lợi có snapshot bất biến; kể cả mua hai lần cùng gói vẫn là hai hàng. |
| 19 | [su_dung_quyen_loi](#b19) | Membership và kỳ quyền lợi | Dấu vết một hành động trả phí được chấp nhận; nguồn kích hoạt và liên kết QR/PT/Chat/AI. |
| 20 | [ma_vao_phong_tap](#b20) | QR và check-in | Token QR động dùng một lần; tách QR check-in khỏi QR thanh toán payOS. |
| 21 | [lich_su_vao_phong_tap](#b21) | QR và check-in | Check-in thành công, đúng một lần cho mỗi QR. |
| 22 | [phan_cong_huan_luyen_vien](#b22) | PT và sử dụng lượt | Lịch sử quan hệ phụ trách PT–Member theo khoảng hiệu lực. |
| 23 | [lich_su_su_dung_huan_luyen_vien](#b23) | PT và sử dụng lượt | Ledger buổi PT hoàn thành; nguồn đối chiếu số lượt đã dùng của đúng kỳ. |
| 24 | [ghi_chu_huan_luyen](#b24) | PT và sử dụng lượt | Ghi chú tư vấn độc lập, không thay thế kế hoạch hoặc kết quả tập. |
| 25 | [dung_cu](#b25) | Thư viện bài tập | Danh mục loại dụng cụ tập, không quản lý máy/tài sản. |
| 26 | [nhom_co](#b26) | Thư viện bài tập | Danh mục nhóm cơ dùng lọc và phân loại bài tập. |
| 27 | [bai_tap](#b27) | Thư viện bài tập | Thư viện bài tập đã được quản trị, nguồn hợp lệ duy nhất cho AI/PT. |
| 28 | [bai_tap_dung_cu](#b28) | Thư viện bài tập | Dụng cụ cần cho một bài tập, quan hệ nhiều–nhiều. |
| 29 | [bai_tap_nhom_co](#b29) | Thư viện bài tập | Các nhóm cơ tham gia một bài tập. |
| 30 | [giao_an_mau](#b30) | Giáo án mẫu | Giáo án mẫu có thể được chọn hoặc dùng làm candidate. |
| 31 | [ngay_trong_giao_an](#b31) | Giáo án mẫu | Ngày/buổi theo thứ tự trong giáo án mẫu. |
| 32 | [bai_tap_trong_giao_an](#b32) | Giáo án mẫu | Bài và mục tiêu kê tập của giáo án mẫu. |
| 33 | [ke_hoach_tap](#b33) | Kế hoạch, phiên bản và lịch dự kiến | Định danh kế hoạch cá nhân; không chứa kết quả tập. |
| 34 | [phien_ban_ke_hoach_tap](#b34) | Kế hoạch, phiên bản và lịch dự kiến | Snapshot kế hoạch đã được chấp thuận, bất biến và có nguồn. |
| 35 | [ngay_trong_ke_hoach](#b35) | Kế hoạch, phiên bản và lịch dự kiến | Ngày kê tập thuộc một snapshot phiên bản. |
| 36 | [bai_tap_trong_ke_hoach](#b36) | Kế hoạch, phiên bản và lịch dự kiến | Đơn kê bài tập của một ngày/version, tách kết quả thực tế. |
| 37 | [buoi_tap_du_kien](#b37) | Kế hoạch, phiên bản và lịch dự kiến | Lịch cụ thể theo ngày, gắn đúng version/ngày kê; thay lịch bằng hàng mới có liên kết thay thế. |
| 38 | [phien_tap](#b38) | Workout History | Workout Session thực tế; HOAN_THANH là mốc khóa toàn bộ kết quả và các bảng con. |
| 39 | [bai_tap_trong_phien](#b39) | Workout History | Bài thực tế của một phiên, sao chép đơn kê lúc bắt đầu và giữ snapshot độc lập. |
| 40 | [hiep_tap](#b40) | Workout History | Kết quả thực tế từng set, chống gửi lặp và cập nhật cũ ghi đè. |
| 41 | [hoi_thoai](#b41) | Realtime Chat | Hội thoại 1–1 Member/PT của một lần phân công; giữ lịch sử đọc sau khi quan hệ kết thúc. |
| 42 | [tin_nhan](#b42) | Realtime Chat | Tin nhắn text bất biến, có sequence để reconnect không mất tin. |
| 43 | [su_kien_phat_tin_nhan](#b43) | Realtime Chat | Transactional outbox cho Reverb, tránh commit tin nhưng mất broadcast. |
| 44 | [hoi_thoai_tro_ly](#b44) | AI và kiểm định | Ngữ cảnh trao đổi Member–AI, tách hội thoại PT. |
| 45 | [tin_nhan_tro_ly](#b45) | AI và kiểm định | Lịch sử trao đổi AI đã được chuẩn hóa, không phải dữ liệu Plan chính thức. |
| 46 | [yeu_cau_tro_ly](#b46) | AI và kiểm định | Một yêu cầu nghiệp vụ AI của Member, có quyền/quota/context/candidate và nhiều lần gọi provider. |
| 47 | [bai_tap_ung_vien](#b47) | AI và kiểm định | Tập candidate bài tập được Rule Engine cho phép tại thời điểm request. |
| 48 | [giao_an_ung_vien](#b48) | AI và kiểm định | Tập giáo án hợp lệ do Rule Engine lựa chọn. |
| 49 | [lan_goi_mo_hinh](#b49) | AI và kiểm định | Audit kỹ thuật từng lần gọi LLM, tách request/quota và Proposal. |
| 50 | [de_xuat_ke_hoach_tap](#b50) | Proposal dùng chung AI/PT | Proposal chung AI/PT; chỉ trở thành Plan Version sau Member xác nhận và Backend revalidate. |
| 51 | [nhat_ky_he_thong](#b51) | Audit và chống xử lý lặp | Audit append-only cho các thao tác quan trọng, không làm nguồn số dư/quyền lợi. |
| 52 | [yeu_cau_chong_lap](#b52) | Audit và chống xử lý lặp | Idempotency cho request đã đăng nhập: cùng khóa/cùng nội dung trả lại kết quả, khác nội dung báo xung đột. |

<a id="b01"></a>

## 01. `chi_nhanh`

**Mục đích:** Chi nhánh phòng gym; MVP chỉ sử dụng một chi nhánh.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `ma_chi_nhanh` | VARCHAR(30) | Không | UNIQUE | Mã quản trị ổn định. |
| `ten_chi_nhanh` | VARCHAR(150) | Không | — | Tên hiển thị. |
| `dia_chi` | VARCHAR(255) | Không | — | Địa chỉ. |
| `so_dien_thoai` | VARCHAR(20) | Có | — | Số liên hệ, không dùng kiểu số. |
| `mui_gio` | VARCHAR(50) | Không | — | Đề xuất Asia/Ho_Chi_Minh. |
| `trang_thai` | VARCHAR(30) | Không | — | HOAT_DONG / NGUNG_HOAT_DONG. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(ma_chi_nhanh)`

**FK đơn:** Không có.

**CHECK/điều kiện cùng hàng dự kiến:** Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt.

**Index truy vấn bổ sung:** Chưa cần ngoài PK/UNIQUE và index FK. Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** Không có FK đi ra.

**Được tham chiếu bởi:** `nguoi_dung` qua `chi_nhanh_id` [0..N]; `goi_tap` qua `chi_nhanh_id` [0..N]; `dang_ky_goi_tap` qua `chi_nhanh_id` [0..N]; `ma_vao_phong_tap` qua `chi_nhanh_id` [0..N]; `lich_su_vao_phong_tap` qua `chi_nhanh_id` [0..N].

**Bảo toàn và lưu ý:**

<a id="b02"></a>

## 02. `nguoi_dung`

**Mục đích:** Tài khoản chung của bốn actor; không lưu cờ Premium.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `chi_nhanh_id` | BIGINT UNSIGNED | Không | FK → `chi_nhanh.id` | Chi nhánh quản lý tài khoản trong MVP. |
| `ho_ten` | VARCHAR(150) | Không | — | Tên người dùng. |
| `thu_dien_tu` | VARCHAR(254) | Không | UNIQUE | Chuẩn hóa trước khi lưu; định danh đăng nhập đề xuất. |
| `so_dien_thoai` | VARCHAR(20) | Có | — | Liên hệ; không mặc định là định danh duy nhất. |
| `mat_khau_bam` | VARCHAR(255) | Không | — | Hash một chiều, không lưu mật khẩu rõ. |
| `anh_dai_dien` | VARCHAR(500) | Có | — | Đường dẫn ảnh, không lưu ảnh nhị phân. |
| `xac_minh_thu_luc` | DATETIME(6) | Có | — | Dành cho xác minh email nếu chọn. |
| `trang_thai` | VARCHAR(30) | Không | — | HOAT_DONG / BI_KHOA / NGUNG_HOAT_DONG. |
| `dang_nhap_gan_nhat_luc` | DATETIME(6) | Có | — | Metadata đăng nhập. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(thu_dien_tu)`

**FK đơn:** `chi_nhanh_id` → `chi_nhanh(id)`

**CHECK/điều kiện cùng hàng dự kiến:** Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt.

**Index truy vấn bổ sung:** `(chi_nhanh_id, trang_thai)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `chi_nhanh` 1 → 0..N `nguoi_dung` qua `chi_nhanh_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** `phan_quyen_nguoi_dung` qua `nguoi_dung_id` [0..N]; `phan_quyen_nguoi_dung` qua `nguoi_cap_id` [0..N]; `ho_so_hoi_vien` qua `nguoi_dung_id` [0..1]; `ho_so_huan_luyen_vien` qua `nguoi_dung_id` [0..1]; `the_truy_cap` qua `nguoi_dung_id` [0..N]; `yeu_cau_dat_lai_mat_khau` qua `nguoi_dung_id` [0..N]; `goi_tap` qua `nguoi_tao_id` [0..N]; `su_dung_quyen_loi` qua `nguoi_thuc_hien_id` [0..N]; `lich_su_vao_phong_tap` qua `nguoi_xac_nhan_id` [0..N]; `phan_cong_huan_luyen_vien` qua `nguoi_phan_cong_id` [0..N]; `ghi_chu_huan_luyen` qua `nguoi_tao_id` [0..N]; `bai_tap` qua `nguoi_tao_id` [0..N]; `giao_an_mau` qua `nguoi_tao_id` [0..N]; `ke_hoach_tap` qua `nguoi_tao_id` [0..N]; `phien_ban_ke_hoach_tap` qua `nguoi_tao_id` [0..N]; `tin_nhan` qua `nguoi_gui_id` [0..N]; `de_xuat_ke_hoach_tap` qua `nguoi_tao_id` [0..N]; `de_xuat_ke_hoach_tap` qua `nguoi_quyet_dinh_id` [0..N]; `nhat_ky_he_thong` qua `nguoi_thuc_hien_id` [0..N]; `yeu_cau_chong_lap` qua `nguoi_dung_id` [0..N].

**Bảo toàn và lưu ý:**Khóa/ngừng tài khoản thay vì xóa dây chuyền lịch sử. Không tự mặc định email xác minh là điều kiện sử dụng nếu chưa được duyệt.

<a id="b03"></a>

## 03. `vai_tro`

**Mục đích:** Danh mục bốn vai trò, tách biệt quyền lợi gói.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `ma_vai_tro` | VARCHAR(30) | Không | UNIQUE | MEMBER / PT / RECEPTIONIST / ADMIN: giá trị mã theo đặc tả. |
| `ten_vai_tro` | VARCHAR(100) | Không | — | Tên tiếng Việt hiển thị. |
| `mo_ta` | VARCHAR(255) | Có | — | Phạm vi trách nhiệm. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(ma_vai_tro)`

**FK đơn:** Không có.

**CHECK/điều kiện cùng hàng dự kiến:** Chỉ bốn mã vai trò CORE; không có FREE/PREMIUM.

**Index truy vấn bổ sung:** Chưa cần ngoài PK/UNIQUE và index FK. Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** Không có FK đi ra.

**Được tham chiếu bởi:** `phan_quyen_nguoi_dung` qua `vai_tro_id` [0..N].

**Bảo toàn và lưu ý:**

<a id="b04"></a>

## 04. `phan_quyen_nguoi_dung`

**Mục đích:** Gán vai trò cho tài khoản; authorization chi tiết vẫn kiểm tra resource.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `nguoi_dung_id` | BIGINT UNSIGNED | Không | FK → `nguoi_dung.id` | Tài khoản nhận vai trò. |
| `vai_tro_id` | BIGINT UNSIGNED | Không | FK → `vai_tro.id` | Vai trò được gán. |
| `nguoi_cap_id` | BIGINT UNSIGNED | Có | FK → `nguoi_dung.id` | Admin của lần cấp/cấp lại gần nhất; NULL chỉ dành bootstrap hệ thống. |
| `cap_luc` | DATETIME(6) | Không | — | Thời điểm cấp/cấp lại gần nhất; cập nhật khi khôi phục Role trên hàng cũ. |
| `thu_hoi_luc` | DATETIME(6) | Có | — | NULL khi còn hiệu lực. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(nguoi_dung_id, vai_tro_id)`

**FK đơn:** `nguoi_dung_id` → `nguoi_dung(id)`; `vai_tro_id` → `vai_tro(id)`; `nguoi_cap_id` → `nguoi_dung(id)`

**CHECK/điều kiện cùng hàng dự kiến:** thu_hoi_luc NULL hoặc >= cap_luc.

**Index truy vấn bổ sung:** `(vai_tro_id, thu_hoi_luc)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `nguoi_dung` 1 → 0..N `phan_quyen_nguoi_dung` qua `nguoi_dung_id` (mỗi con bắt buộc có cha); `vai_tro` 1 → 0..N `phan_quyen_nguoi_dung` qua `vai_tro_id` (mỗi con bắt buộc có cha); `nguoi_dung` 1 → 0..N `phan_quyen_nguoi_dung` qua `nguoi_cap_id` (con có thể chưa tham chiếu cha).

**Được tham chiếu bởi:** Chưa có bảng con tham chiếu trong MVP.

**Bảo toàn và lưu ý:**ĐÃ CHỐT: một hàng hiện tại cho mỗi cặp người dùng/vai trò. Thu hồi gán thu_hoi_luc; cấp lại UPDATE hàng cũ, gán cap_luc và nguoi_cap_id mới, thu_hoi_luc=NULL; giữ id/ngay_tao, cập nhật ngay_cap_nhat. Cấp/thu hồi/cấp lại lưu người thao tác, mốc thời gian và trước/sau trong nhat_ky_he_thong cùng transaction; lịch sử đọc từ audit, không suy từ cap_luc đã cập nhật. Retry không nhân đôi hàng/audit thành công. Không thêm bảng lịch sử Role hoặc UI permission chi tiết.

<a id="b05"></a>

## 05. `ho_so_hoi_vien`

**Mục đích:** Hồ sơ Member, mục tiêu, kinh nghiệm và mốc chống đề xuất lỗi thời.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `nguoi_dung_id` | BIGINT UNSIGNED | Không | UNIQUE; FK → `nguoi_dung.id` | Một tài khoản có tối đa một hồ sơ hội viên. |
| `ma_hoi_vien` | VARCHAR(30) | Không | UNIQUE | Mã tra cứu tại quầy. |
| `ngay_sinh` | DATE | Có | — | Thông tin cá nhân. |
| `gioi_tinh` | VARCHAR(30) | Có | — | Tự khai, không suy đoán. |
| `muc_tieu_tap_luyen` | VARCHAR(100) | Có | — | Danh mục mục tiêu do nghiệp vụ duyệt. |
| `kinh_nghiem_tap_luyen` | VARCHAR(50) | Có | — | Mức kinh nghiệm. |
| `so_ngay_tap_mong_muon` | TINYINT UNSIGNED | Có | — | Số ngày/tuần. |
| `thoi_luong_moi_buoi_phut` | SMALLINT UNSIGNED | Có | — | Thời lượng mong muốn. |
| `phien_ban_ho_so` | INT UNSIGNED | Không | — | Mặc định 1; tăng khi điều kiện tập thay đổi. |
| `moc_thay_doi_ke_hoach` | INT UNSIGNED | Không | — | Mặc định 0; tăng mỗi lần áp dụng thay đổi kế hoạch. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(nguoi_dung_id)`; `(ma_hoi_vien)`

**FK đơn:** `nguoi_dung_id` → `nguoi_dung(id)`

**CHECK/điều kiện cùng hàng dự kiến:** so_ngay_tap_mong_muon NULL hoặc trong 1..7. thoi_luong_moi_buoi_phut NULL hoặc > 0.

**Index truy vấn bổ sung:** Chưa cần ngoài PK/UNIQUE và index FK. Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `nguoi_dung` 1 → 0..1 `ho_so_hoi_vien` qua `nguoi_dung_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** `ngay_ranh_hoi_vien` qua `hoi_vien_id` [0..N]; `dung_cu_hoi_vien` qua `hoi_vien_id` [0..N]; `chi_so_co_the` qua `hoi_vien_id` [0..N]; `don_mua_goi` qua `hoi_vien_id` [0..N]; `dang_ky_goi_tap` qua `hoi_vien_id` [0..N]; `ky_han_hoi_vien` qua `hoi_vien_id` [0..N]; `su_dung_quyen_loi` qua `hoi_vien_id` [0..N]; `ma_vao_phong_tap` qua `hoi_vien_id` [0..N]; `lich_su_vao_phong_tap` qua `hoi_vien_id` [0..N]; `phan_cong_huan_luyen_vien` qua `hoi_vien_id` [0..N]; `lich_su_su_dung_huan_luyen_vien` qua `hoi_vien_id` [0..N]; `ghi_chu_huan_luyen` qua `hoi_vien_id` [0..N]; `ke_hoach_tap` qua `hoi_vien_id` [0..N]; `buoi_tap_du_kien` qua `hoi_vien_id` [0..N]; `phien_tap` qua `hoi_vien_id` [0..N]; `hoi_thoai` qua `hoi_vien_id` [0..N]; `tin_nhan` qua `hoi_vien_id` [0..N]; `hoi_thoai_tro_ly` qua `hoi_vien_id` [0..N]; `yeu_cau_tro_ly` qua `hoi_vien_id` [0..N]; `de_xuat_ke_hoach_tap` qua `hoi_vien_id` [0..N].

**Bảo toàn và lưu ý:**Ngày rảnh và dụng cụ nằm ở bảng con. Không lưu cân nặng mới nhất làm nguồn duy nhất; dùng chi_so_co_the. Các mốc phiên bản không giảm được kiểm tra trong transaction khi cập nhật so với giá trị trước đó; đây không phải CHECK cùng hàng có thể tự đọc phiên bản cũ.

<a id="b06"></a>

## 06. `ho_so_huan_luyen_vien`

**Mục đích:** Hồ sơ PT, không phải nhân sự/tiền lương.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `nguoi_dung_id` | BIGINT UNSIGNED | Không | UNIQUE; FK → `nguoi_dung.id` | Tài khoản có vai trò PT. |
| `ma_huan_luyen_vien` | VARCHAR(30) | Không | UNIQUE | Mã PT. |
| `gioi_thieu` | TEXT | Có | — | Giới thiệu. |
| `chuyen_mon` | VARCHAR(255) | Có | — | Mô tả chuyên môn trong phạm vi tập luyện. |
| `trang_thai` | VARCHAR(30) | Không | — | HOAT_DONG / NGUNG_NHAN_PHAN_CONG. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(nguoi_dung_id)`; `(ma_huan_luyen_vien)`

**FK đơn:** `nguoi_dung_id` → `nguoi_dung(id)`

**CHECK/điều kiện cùng hàng dự kiến:** Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt.

**Index truy vấn bổ sung:** Chưa cần ngoài PK/UNIQUE và index FK. Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `nguoi_dung` 1 → 0..1 `ho_so_huan_luyen_vien` qua `nguoi_dung_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** `phan_cong_huan_luyen_vien` qua `huan_luyen_vien_id` [0..N]; `lich_su_su_dung_huan_luyen_vien` qua `huan_luyen_vien_id` [0..N]; `hoi_thoai` qua `huan_luyen_vien_id` [0..N]; `tin_nhan` qua `huan_luyen_vien_id` [0..N].

**Bảo toàn và lưu ý:**

<a id="b07"></a>

## 07. `ngay_ranh_hoi_vien`

**Mục đích:** Các thứ trong tuần hội viên có thể tập.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `hoi_vien_id` | BIGINT UNSIGNED | Không | FK → `ho_so_hoi_vien.id` | Chủ sở hữu. |
| `thu_trong_tuan` | TINYINT UNSIGNED | Không | — | Quy ước 2=thứ Hai,...,8=Chủ nhật. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(hoi_vien_id, thu_trong_tuan)`

**FK đơn:** `hoi_vien_id` → `ho_so_hoi_vien(id)`

**CHECK/điều kiện cùng hàng dự kiến:** thu_trong_tuan trong 2..8.

**Index truy vấn bổ sung:** Chưa cần ngoài PK/UNIQUE và index FK. Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `ho_so_hoi_vien` 1 → 0..N `ngay_ranh_hoi_vien` qua `hoi_vien_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** Chưa có bảng con tham chiếu trong MVP.

**Bảo toàn và lưu ý:**Thay đổi đồng thời tăng phien_ban_ho_so của hội viên.

<a id="b08"></a>

## 08. `dung_cu_hoi_vien`

**Mục đích:** Dụng cụ hội viên có thể sử dụng để chọn bài phù hợp.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `hoi_vien_id` | BIGINT UNSIGNED | Không | FK → `ho_so_hoi_vien.id` | Chủ sở hữu. |
| `dung_cu_id` | BIGINT UNSIGNED | Không | FK → `dung_cu.id` | Loại dụng cụ, không phải tài sản gym. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(hoi_vien_id, dung_cu_id)`

**FK đơn:** `hoi_vien_id` → `ho_so_hoi_vien(id)`; `dung_cu_id` → `dung_cu(id)`

**CHECK/điều kiện cùng hàng dự kiến:** Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt.

**Index truy vấn bổ sung:** Chưa cần ngoài PK/UNIQUE và index FK. Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `ho_so_hoi_vien` 1 → 0..N `dung_cu_hoi_vien` qua `hoi_vien_id` (mỗi con bắt buộc có cha); `dung_cu` 1 → 0..N `dung_cu_hoi_vien` qua `dung_cu_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** Chưa có bảng con tham chiếu trong MVP.

**Bảo toàn và lưu ý:**Thay đổi đồng thời tăng phien_ban_ho_so.

<a id="b09"></a>

## 09. `chi_so_co_the`

**Mục đích:** Lịch sử số đo phục vụ Progress, độc lập Membership.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `hoi_vien_id` | BIGINT UNSIGNED | Không | FK → `ho_so_hoi_vien.id` | Người được đo. |
| `do_luc` | DATETIME(6) | Không | — | Thời điểm đo thực tế. |
| `can_nang_kg` | DECIMAL(6,2) | Không | — | Cân nặng. |
| `chieu_cao_cm` | DECIMAL(5,2) | Không | — | Chiều cao tại lần đo, tái hiện BMI cũ. |
| `vong_eo_cm` | DECIMAL(5,2) | Có | — | Số đo tùy chọn. |
| `ghi_chu` | VARCHAR(500) | Có | — | Ghi chú tự khai. |
| `ma_lan_ghi` | CHAR(36) | Không | — | Định danh ổn định khi client gửi lại. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |

**UNIQUE:** `(hoi_vien_id, ma_lan_ghi)`

**FK đơn:** `hoi_vien_id` → `ho_so_hoi_vien(id)`

**CHECK/điều kiện cùng hàng dự kiến:** Cân nặng, chiều cao và vòng eo nếu có phải > 0.

**Index truy vấn bổ sung:** `(hoi_vien_id, do_luc, id)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `ho_so_hoi_vien` 1 → 0..N `chi_so_co_the` qua `hoi_vien_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** Chưa có bảng con tham chiếu trong MVP.

**Bảo toàn và lưu ý:**Snapshot/ledger append-only; không sửa/xóa hàng đã công bố. BMI tính từ chính chiều cao/cân nặng của lần đo; không dùng chiều cao hồ sơ hiện tại để sửa diễn giải lịch sử.

<a id="b10"></a>

## 10. `the_truy_cap`

**Mục đích:** Token truy cập Mobile được băm; thiết kế xác thực, chưa triển khai Sanctum.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `nguoi_dung_id` | BIGINT UNSIGNED | Không | FK → `nguoi_dung.id` | Chủ token. |
| `ten_thiet_bi` | VARCHAR(150) | Không | — | Tên hiển thị phiên Mobile. |
| `ma_bam_the` | CHAR(64) | Không | UNIQUE | SHA-256 của token ngẫu nhiên; không lưu token rõ. |
| `pham_vi_truy_cap` | JSON | Không | — | Danh sách phạm vi; không thay thế Role/Entitlement. |
| `su_dung_gan_nhat_luc` | DATETIME(6) | Có | — | Lần dùng gần nhất. |
| `het_han_luc` | DATETIME(6) | Không | — | Thời điểm hết hạn. |
| `thu_hoi_luc` | DATETIME(6) | Có | — | Thu hồi khi đăng xuất/khóa tài khoản. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(ma_bam_the)`

**FK đơn:** `nguoi_dung_id` → `nguoi_dung(id)`

**CHECK/điều kiện cùng hàng dự kiến:** het_han_luc > ngay_tao.

**Index truy vấn bổ sung:** `(nguoi_dung_id, thu_hoi_luc, het_han_luc)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `nguoi_dung` 1 → 0..N `the_truy_cap` qua `nguoi_dung_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** Chưa có bảng con tham chiếu trong MVP.

**Bảo toàn và lưu ý:**Nếu dùng Sanctum cần custom model/adapter ánh xạ tên cột tiếng Việt; không giả định chỉ đổi tên bảng là đủ. Web session tiếp tục dùng file, chưa cần bảng session.

<a id="b11"></a>

## 11. `yeu_cau_dat_lai_mat_khau`

**Mục đích:** Yêu cầu quên mật khẩu, dùng một lần và hết hạn.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `nguoi_dung_id` | BIGINT UNSIGNED | Không | FK → `nguoi_dung.id` | Tài khoản yêu cầu. |
| `ma_bam_xac_nhan` | CHAR(64) | Không | UNIQUE | Hash token ngẫu nhiên gửi ngoài băng. |
| `het_han_luc` | DATETIME(6) | Không | — | Hết hạn ngắn theo chính sách được duyệt. |
| `da_su_dung_luc` | DATETIME(6) | Có | — | Chống dùng lại. |
| `thu_hoi_luc` | DATETIME(6) | Có | — | Vô hiệu khi thay thế/yêu cầu hoàn tất. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(ma_bam_xac_nhan)`

**FK đơn:** `nguoi_dung_id` → `nguoi_dung(id)`

**CHECK/điều kiện cùng hàng dự kiến:** het_han_luc > ngay_tao.

**Index truy vấn bổ sung:** `(nguoi_dung_id, het_han_luc)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `nguoi_dung` 1 → 0..N `yeu_cau_dat_lai_mat_khau` qua `nguoi_dung_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** Chưa có bảng con tham chiếu trong MVP.

**Bảo toàn và lưu ý:**Không lưu token rõ hoặc trả thông tin giúp dò email; giới hạn tần suất xử lý tại Backend.

<a id="b12"></a>

## 12. `goi_tap`

**Mục đích:** Danh mục gói đang bán; thay đổi chỉ tác động các lần mua sau.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `chi_nhanh_id` | BIGINT UNSIGNED | Không | FK → `chi_nhanh.id` | Chi nhánh bán gói. |
| `ma_goi` | VARCHAR(30) | Không | — | Mã ổn định, không dùng tên để cấp quyền. |
| `ten_goi` | VARCHAR(150) | Không | — | Tên hiển thị. |
| `gia` | DECIMAL(15,0) | Không | — | Số tiền VND, không dùng FLOAT. |
| `thoi_han_ngay` | SMALLINT UNSIGNED | Không | — | Số ngày dùng một đồng hồ chung. |
| `mo_ta` | TEXT | Có | — | Mô tả. |
| `trang_thai` | VARCHAR(30) | Không | — | DANG_BAN / NGUNG_BAN. |
| `phien_ban_cau_hinh` | INT UNSIGNED | Không | — | Tăng mỗi lần đổi giá/thời hạn/quyền lợi. |
| `nguoi_tao_id` | BIGINT UNSIGNED | Không | FK → `nguoi_dung.id` | Admin tạo. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(chi_nhanh_id, ma_goi)`

**FK đơn:** `chi_nhanh_id` → `chi_nhanh(id)`; `nguoi_tao_id` → `nguoi_dung(id)`

**CHECK/điều kiện cùng hàng dự kiến:** gia > 0; thoi_han_ngay > 0; phien_ban_cau_hinh >= 1.

**Index truy vấn bổ sung:** `(chi_nhanh_id, trang_thai)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `chi_nhanh` 1 → 0..N `goi_tap` qua `chi_nhanh_id` (mỗi con bắt buộc có cha); `nguoi_dung` 1 → 0..N `goi_tap` qua `nguoi_tao_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** `quyen_loi_goi_tap` qua `goi_tap_id` [0..1]; `don_mua_goi` qua `goi_tap_id` [0..N].

**Bảo toàn và lưu ý:**Q08: Workout cơ bản không cần mua gói; không đưa gói miễn phí hoặc quyền Workout vào luồng thanh toán trả phí. Q01: sửa giá/quyền chỉ áp dụng đơn mới; đơn cũ còn trong hạn dùng snapshot đã chốt.

<a id="b13"></a>

## 13. `quyen_loi_goi_tap`

**Mục đích:** Cấu hình quyền lợi hiện tại của gói; quan hệ 1:1 thay vì EAV.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `goi_tap_id` | BIGINT UNSIGNED | Không | UNIQUE; FK → `goi_tap.id` | Một bản cấu hình quyền cho mỗi gói. |
| `cho_phep_vao_phong_tap` | BOOLEAN | Không | — | Quyền gym. |
| `cho_phep_tro_ly_tap_luyen` | BOOLEAN | Không | — | Quyền AI. |
| `gioi_han_luot_tro_ly` | INT UNSIGNED | Có | — | NULL=không giới hạn; 0 khi không có AI. |
| `cho_phep_tro_chuyen_huan_luyen_vien` | BOOLEAN | Không | — | Chỉ quyền Chat PT trong kỳ, độc lập số buổi trực tiếp; không quota message. |
| `so_buoi_huan_luyen_vien` | SMALLINT UNSIGNED | Không | — | Tổng buổi PT trực tiếp 1-1 cấp mỗi kỳ; 0 nghĩa là không có buổi. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(goi_tap_id)`

**FK đơn:** `goi_tap_id` → `goi_tap(id)`

**CHECK/điều kiện cùng hàng dự kiến:** Các cờ chỉ 0/1; không có AI thì giới hạn=0; có AI thì giới hạn NULL hoặc > 0. so_buoi_huan_luyen_vien >= 0, độc lập cờ Chat. Ít nhất một quyền có thời hạn: gym hoặc AI hoặc Chat PT được bật, hoặc tổng buổi PT > 0. Không đặt điều kiện Chat=false kéo theo tổng buổi=0.

**Index truy vấn bổ sung:** Chưa cần ngoài PK/UNIQUE và index FK. Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `goi_tap` 1 → 0..1 `quyen_loi_goi_tap` qua `goi_tap_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** Chưa có bảng con tham chiếu trong MVP.

**Bảo toàn và lưu ý:**ĐÃ CHỐT: Chat PT và quota buổi độc lập. ONLINE có Chat=true và số buổi=0 là hợp lệ; hết lượt buổi không tắt Chat nếu cờ Chat/kỳ/phân công vẫn hợp lệ. Tắt Chat không cấm buổi trực tiếp còn lượt. Không thêm boolean quyền buổi, đồng hồ riêng hoặc hard-code tên gói. Khi mua phải snapshot cả hai trường vào kỳ. Q08 không thêm quyền Workout. Q13 không dùng cờ Chat/quota buổi để cấp quyền PT Proposal; chỉ cần phân công hợp lệ. Q03 hạn mức AI tính theo request nghiệp vụ.

<a id="b14"></a>

## 14. `don_mua_goi`

**Mục đích:** Đơn một gói/một kỳ, nguồn thanh toán và lịch sử mua/gia hạn.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `hoi_vien_id` | BIGINT UNSIGNED | Không | FK → `ho_so_hoi_vien.id` | Người mua. |
| `goi_tap_id` | BIGINT UNSIGNED | Không | FK → `goi_tap.id` | Gói tham chiếu gốc. |
| `ma_don` | VARCHAR(40) | Không | UNIQUE | Mã nội bộ tra cứu. |
| `ma_yeu_cau` | CHAR(36) | Không | — | Chống tạo hai đơn khi retry. |
| `so_tien_phai_thu` | DECIMAL(15,0) | Không | — | Giá đã chốt, không đọc lại giá catalog lúc webhook. |
| `don_vi_tien` | CHAR(3) | Không | — | VND. |
| `trang_thai` | VARCHAR(30) | Không | — | CHO_THANH_TOAN / DA_THANH_TOAN / HET_HAN / HUY / CAN_DOI_SOAT. |
| `chot_gia_luc` | DATETIME(6) | Không | — | Mốc tạo snapshot. |
| `het_han_thanh_toan_luc` | DATETIME(6) | Không | — | Hạn giữ snapshot của đơn. Link không được hết hạn muộn hơn mốc này; không kéo dài báo giá bằng cách tạo lại link. |
| `thanh_toan_luc` | DATETIME(6) | Có | — | Thời điểm xác nhận thanh toán hợp lệ. |
| `huy_luc` | DATETIME(6) | Có | — | Nếu đơn bị hủy. |
| `ly_do_huy` | VARCHAR(500) | Có | — | Không tự động hoàn tiền. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(ma_don)`; `(hoi_vien_id, ma_yeu_cau)`; `(id, hoi_vien_id)`

**FK đơn:** `hoi_vien_id` → `ho_so_hoi_vien(id)`; `goi_tap_id` → `goi_tap(id)`

**CHECK/điều kiện cùng hàng dự kiến:** so_tien_phai_thu > 0; don_vi_tien=VND; hạn > mốc chốt giá.

**Index truy vấn bổ sung:** `(hoi_vien_id, ngay_tao, id)`; `(trang_thai, het_han_thanh_toan_luc)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `ho_so_hoi_vien` 1 → 0..N `don_mua_goi` qua `hoi_vien_id` (mỗi con bắt buộc có cha); `goi_tap` 1 → 0..N `don_mua_goi` qua `goi_tap_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** `lan_thanh_toan` qua `don_mua_goi_id` [0..N]; `ky_han_hoi_vien` qua `don_mua_goi_id` [0..1].

**Bảo toàn và lưu ý:**Q01 ĐÃ CHỐT: tạo đơn và ky_han_hoi_vien CHO_THANH_TOAN cùng transaction để snapshot giá, gói, thời hạn/quyền lợi; chưa có chuỗi/thứ tự/quyền sử dụng. Snapshot giữ nguyên trong hạn đơn/link, Admin đổi catalog không tác động đơn cũ còn hiệu lực; mua mới dùng catalog mới. Không tự gia hạn giữ giá quá het_han_thanh_toan_luc; snapshot vẫn được lưu lịch sử sau hạn, không có nghĩa tiếp tục được thanh toán/cấp quyền. Q10: sai tiền, đơn hết hạn/hủy, không khớp hoặc hai link nhận tiền phải lưu CAN_DOI_SOAT tại sự kiện/lần thử liên quan; tối đa một kỳ được cấp mỗi đơn. Không tự hoàn tiền, xóa giao dịch hoặc cấp thêm ngày. Q12 giữ lịch sử đơn và snapshot.

<a id="b15"></a>

## 15. `lan_thanh_toan`

**Mục đích:** Mỗi lần tạo liên kết thanh toán payOS; một đơn có thể có nhiều lần thử.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `don_mua_goi_id` | BIGINT UNSIGNED | Không | FK → `don_mua_goi.id` | Đơn cần thanh toán. |
| `so_lan` | SMALLINT UNSIGNED | Không | — | Thứ tự thử trong đơn. |
| `ma_kenh_thanh_toan` | VARCHAR(100) | Không | — | Định danh kênh merchant, không phải khóa bí mật. |
| `ma_don_cong_thanh_toan` | BIGINT UNSIGNED | Không | — | Ánh xạ orderCode; cấp phía Backend. |
| `ma_lien_ket_thanh_toan` | VARCHAR(100) | Có | — | Ánh xạ paymentLinkId, NULL khi chưa nhận phản hồi. |
| `duong_dan_thanh_toan` | VARCHAR(1000) | Có | — | checkoutUrl. |
| `so_tien_yeu_cau` | DECIMAL(15,0) | Không | — | Số tiền yêu cầu của đơn. |
| `don_vi_tien` | CHAR(3) | Không | — | VND. |
| `trang_thai` | VARCHAR(30) | Không | — | DANG_TAO / CHO_THANH_TOAN / THANH_CONG / THAT_BAI / HUY / HET_HAN / CAN_DOI_SOAT. |
| `ma_tham_chieu_duoc_chap_nhan` | VARCHAR(150) | Có | — | reference của giao dịch xác minh được chấp nhận cho lần thử. |
| `so_tien_da_nhan` | DECIMAL(15,0) | Có | — | Tiền nhận đã được xác minh. |
| `thanh_toan_luc` | DATETIME(6) | Có | — | Thời điểm ngân hàng/gateway; lưu UTC sau chuẩn hóa. |
| `xac_nhan_luc` | DATETIME(6) | Có | — | Q02: mốc Backend lần đầu xác nhận thanh toán hợp lệ dưới khóa Member/chuỗi; chỉ ghi một lần, retry không thay đổi. Mốc xếp thứ tự, khác thời điểm ngân hàng. |
| `het_han_luc` | DATETIME(6) | Không | — | Hạn link cụ thể; không muộn hơn don_mua_goi.het_han_thanh_toan_luc (Backend kiểm tra chéo bảng). |
| `ma_loi` | VARCHAR(100) | Có | — | Mã lỗi được chuẩn hóa. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(don_mua_goi_id, so_lan)`; `(ma_kenh_thanh_toan, ma_don_cong_thanh_toan)`; `(ma_kenh_thanh_toan, ma_lien_ket_thanh_toan)`; `(ma_kenh_thanh_toan, ma_tham_chieu_duoc_chap_nhan)`; `(id, don_mua_goi_id)`

**FK đơn:** `don_mua_goi_id` → `don_mua_goi(id)`

**CHECK/điều kiện cùng hàng dự kiến:** so_lan > 0; số tiền không âm và tiền yêu cầu > 0. Giới hạn nội bộ ma_don_cong_thanh_toan trong 1..9007199254740991 để truyền JSON an toàn.

**Index truy vấn bổ sung:** `(don_mua_goi_id, trang_thai)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `don_mua_goi` 1 → 0..N `lan_thanh_toan` qua `don_mua_goi_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** `su_kien_thanh_toan` qua `lan_thanh_toan_id` [0..N]; `ky_han_hoi_vien` qua `lan_thanh_toan_id` [0..1].

**Bảo toàn và lưu ý:**Không gọi gateway trong transaction giữ khóa dài. Timeout tạo link phải tra cứu cùng orderCode trước khi tạo lần thử mới. Giao dịch hợp lệ ở lần thử khác không được cấp thêm kỳ cho cùng đơn; chuyển đối soát, không tự hoàn tiền. Q01: giá/link trong hạn đơn dùng snapshot chốt lúc tạo; tạo lại link không kéo dài hạn giữ giá của đơn. Q02: xac_nhan_luc ghi dưới khóa hội viên khi lần đầu xác nhận hợp lệ, đồng thời cấp so_thu_tu kỳ; nếu mốc trùng, thứ tự được tuần tự hóa bởi khóa và so_thu_tu. Q10: link thứ hai nhận tiền hoặc tiền sai/trễ/không khớp -> CAN_DOI_SOAT, không làm mất nguồn thanh toán đã cấp kỳ hợp lệ. Không sửa một thanh toán đã chấp nhận thành chưa từng thành công chỉ vì có khoản bất thường khác.

<a id="b16"></a>

## 16. `su_kien_thanh_toan`

**Mục đích:** Inbox webhook với xác thực, chống lặp và chẩn đoán đối soát.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `lan_thanh_toan_id` | BIGINT UNSIGNED | Có | FK → `lan_thanh_toan.id` | NULL khi chưa tìm thấy đơn/lần thử. |
| `ma_kenh_thanh_toan` | VARCHAR(100) | Không | — | Scope của kênh nhận webhook. |
| `ma_don_cong_thanh_toan` | BIGINT UNSIGNED | Có | — | orderCode nhận được. |
| `ma_lien_ket_thanh_toan` | VARCHAR(100) | Có | — | paymentLinkId nhận được. |
| `ma_tham_chieu` | VARCHAR(150) | Có | — | reference; không tự nhận làm FK. |
| `so_tien` | DECIMAL(15,0) | Có | — | amount nhận được. |
| `don_vi_tien` | CHAR(3) | Có | — | currency nhận được. |
| `ma_ket_qua` | VARCHAR(30) | Có | — | Mã trạng thái gateway. |
| `chu_ky_hop_le` | BOOLEAN | Không | — | Mặc định 0; chỉ Backend xác minh. |
| `khoa_chong_lap` | CHAR(64) | Có | UNIQUE | Hash bộ dữ liệu chuẩn hóa có kênh; chỉ gán SAU xác minh chữ ký. |
| `ma_bam_noi_dung` | CHAR(64) | Không | — | Dấu vết payload không chứa khóa bí mật. |
| `du_lieu_da_loc` | JSON | Có | — | Whitelist trường cần đối soát; che thông tin ngân hàng không cần thiết. |
| `trang_thai_xu_ly` | VARCHAR(30) | Không | — | CHO_XU_LY / DA_XU_LY / BI_TU_CHOI / CAN_DOI_SOAT / CHO_THU_LAI. |
| `so_lan_nhan` | INT UNSIGNED | Không | — | Số lần nhận cùng sự kiện xác thực; mặc định 1. |
| `nhan_dau_luc` | DATETIME(6) | Không | — | Lần nhận đầu. |
| `nhan_cuoi_luc` | DATETIME(6) | Không | — | Lần nhận gần nhất. |
| `xu_ly_luc` | DATETIME(6) | Có | — | Khi hoàn tất xử lý. |
| `ly_do` | VARCHAR(500) | Có | — | Lý do từ chối/đối soát, không log secret. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(khoa_chong_lap)`

**FK đơn:** `lan_thanh_toan_id` → `lan_thanh_toan(id)`

**CHECK/điều kiện cùng hàng dự kiến:** so_lan_nhan >= 1; chu_ky_hop_le chỉ 0/1. Chữ ký sai => khoa_chong_lap IS NULL, tránh chiếm khóa của webhook thật.

**Index truy vấn bổ sung:** `(trang_thai_xu_ly, nhan_dau_luc)`; `(lan_thanh_toan_id, nhan_dau_luc)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `lan_thanh_toan` 1 → 0..N `su_kien_thanh_toan` qua `lan_thanh_toan_id` (con có thể chưa tham chiếu cha).

**Được tham chiếu bởi:** Chưa có bảng con tham chiếu trong MVP.

**Bảo toàn và lưu ý:**Webhook trùng có thể tăng số lần nhận nhưng không cấp quyền lại. Không dùng duy nhất hash payload để bảo đảm thanh toán đúng một lần: vẫn cần khóa đơn, unique reference và unique kỳ/đơn. Payload sai JSON chỉ lưu hash/lỗi, không làm dữ liệu nghiệp vụ. Q10 ĐÃ CHỐT: lưu mọi sự kiện bất thường với ly_do; khoản sai tiền, trễ sau hạn/hủy, không khớp hoặc link thứ hai nhận tiền -> CAN_DOI_SOAT, không cấp kỳ hoặc hoàn tiền tự động. Không bỏ qua webhook. Payload không xác thực giữ BI_TU_CHOI và dấu vết, không gán khóa chống lặp đáng tin cậy; không coi dữ liệu giả là tiền đã nhận. Retry sự kiện đã xử lý không đổi thứ tự/mốc Q02. Q12 không hard-delete inbox/event.

<a id="b17"></a>

## 17. `dang_ky_goi_tap`

**Mục đích:** Một chuỗi Membership liên tục gồm nhiều kỳ đã mua; mua lại sau toàn bộ chuỗi hết hạn tạo chuỗi mới.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `hoi_vien_id` | BIGINT UNSIGNED | Không | FK → `ho_so_hoi_vien.id` | Chủ chuỗi. |
| `chi_nhanh_id` | BIGINT UNSIGNED | Không | FK → `chi_nhanh.id` | Chi nhánh dịch vụ. |
| `trang_thai` | VARCHAR(30) | Không | — | CHO_KICH_HOAT / DANG_HOAT_DONG / HET_HAN / HUY. |
| `lan_su_dung_dau_tien_id` | BIGINT UNSIGNED | Có | UNIQUE; FK → `su_dung_quyen_loi.id` | Hành động trả phí hợp lệ kích hoạt chuỗi. |
| `ngay_bat_dau` | DATETIME(6) | Có | — | NULL đến lần sử dụng đầu tiên. |
| `ket_thuc_ghi_nhan_luc` | DATETIME(6) | Có | — | Mốc ghi nhận chuỗi đã khép lại; không phải nguồn tính quyền. |
| `hoi_vien_chua_ket_thuc_id` | BIGINT UNSIGNED | Có | UNIQUE | Cột sinh VIRTUAL: hoi_vien_id khi trạng thái CHO_KICH_HOAT/DANG_HOAT_DONG, ngược lại NULL; không dùng NOW(). |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(lan_su_dung_dau_tien_id)`; `(hoi_vien_chua_ket_thuc_id)`; `(id, hoi_vien_id)`

**FK đơn:** `hoi_vien_id` → `ho_so_hoi_vien(id)`; `chi_nhanh_id` → `chi_nhanh(id)`; `lan_su_dung_dau_tien_id` → `su_dung_quyen_loi(id)`

**FK kép bảo vệ dữ liệu cùng chủ/cùng nguồn:**

- `(lan_su_dung_dau_tien_id, hoi_vien_id)` → `su_dung_quyen_loi(id, hoi_vien_id)`.

**CHECK/điều kiện cùng hàng dự kiến:** Không dùng NOW() trong cột sinh. Chờ kích hoạt: ngày bắt đầu và nguồn kích hoạt đều NULL; đã kích hoạt: cả hai có giá trị.

**Index truy vấn bổ sung:** `(hoi_vien_id, ngay_tao, id)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `ho_so_hoi_vien` 1 → 0..N `dang_ky_goi_tap` qua `hoi_vien_id` (mỗi con bắt buộc có cha); `chi_nhanh` 1 → 0..N `dang_ky_goi_tap` qua `chi_nhanh_id` (mỗi con bắt buộc có cha); `su_dung_quyen_loi` 1 → 0..1 `dang_ky_goi_tap` qua `lan_su_dung_dau_tien_id` (con có thể chưa tham chiếu cha).

**Được tham chiếu bởi:** `ky_han_hoi_vien` qua `dang_ky_goi_tap_id` [0..N].

**Bảo toàn và lưu ý:**Unique cột sinh bảo vệ tối đa một chuỗi chưa khép. Trạng thái chỉ là projection: khi thao tác phải tính lại theo kỳ/thời gian dưới khóa hội viên và đóng chuỗi đã hết trước khi tạo chuỗi mới. Tổng ngày còn lại tính từ các kỳ, không lưu bộ đếm giảm hằng ngày.

<a id="b18"></a>

## 18. `ky_han_hoi_vien`

**Mục đích:** Một kỳ quyền lợi có snapshot bất biến; kể cả mua hai lần cùng gói vẫn là hai hàng.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `hoi_vien_id` | BIGINT UNSIGNED | Không | FK → `ho_so_hoi_vien.id` | Chủ kỳ. |
| `don_mua_goi_id` | BIGINT UNSIGNED | Không | UNIQUE; FK → `don_mua_goi.id` | Đơn nguồn, đúng một kỳ mỗi đơn. |
| `lan_thanh_toan_id` | BIGINT UNSIGNED | Có | UNIQUE; FK → `lan_thanh_toan.id` | Lần thanh toán cấp kỳ, chỉ gán sau xác minh. |
| `dang_ky_goi_tap_id` | BIGINT UNSIGNED | Có | FK → `dang_ky_goi_tap.id` | Chuỗi được xếp sau thanh toán. |
| `so_thu_tu` | INT UNSIGNED | Có | — | Q02: thứ tự được cấp khi Backend lần đầu xác nhận thanh toán hợp lệ dưới khóa Member/chuỗi; không theo thời điểm tạo đơn hoặc client. |
| `trang_thai` | VARCHAR(30) | Không | — | CHO_THANH_TOAN / CHO_KICH_HOAT / CHO_DEN_LUOT / DANG_HOAT_DONG / HET_HAN / HUY. |
| `ten_goi` | VARCHAR(150) | Không | — | Snapshot tên gói lúc chốt đơn. |
| `phien_ban_goi` | INT UNSIGNED | Không | — | Phiên bản catalog đã mua. |
| `gia_da_mua` | DECIMAL(15,0) | Không | — | Snapshot giá. |
| `thoi_han_ngay` | SMALLINT UNSIGNED | Không | — | Snapshot số ngày. |
| `cho_phep_vao_phong_tap` | BOOLEAN | Không | — | Snapshot quyền gym. |
| `cho_phep_tro_ly_tap_luyen` | BOOLEAN | Không | — | Snapshot quyền AI. |
| `gioi_han_luot_tro_ly` | INT UNSIGNED | Có | — | Snapshot hạn mức mỗi kỳ; NULL=không giới hạn. |
| `cho_phep_tro_chuyen_huan_luyen_vien` | BOOLEAN | Không | — | Snapshot quyền Chat PT, độc lập quota buổi; không giới hạn số message. |
| `so_buoi_huan_luyen_vien` | SMALLINT UNSIGNED | Không | — | Snapshot tổng buổi PT trực tiếp 1-1 được cấp; không suy quyền Chat từ số này. |
| `so_buoi_huan_luyen_vien_da_dung` | SMALLINT UNSIGNED | Không | — | Counter đối chiếu ledger, mặc định 0. |
| `so_luot_tro_ly_da_dung` | INT UNSIGNED | Không | — | Q03: số request nghiệp vụ AI hợp lệ đã tính; mỗi request 1 lượt, không theo message/provider call; mặc định 0. |
| `so_luot_tro_ly_giu_cho` | INT UNSIGNED | Không | — | Giữ hạn mức cho request AI đang xử lý, mặc định 0. |
| `mua_luc` | DATETIME(6) | Có | — | Q02: mốc Backend xác nhận hợp lệ lần đầu, cùng mốc lan_thanh_toan.xac_nhan_luc; không phải mốc kích hoạt hoặc thời điểm ngân hàng. |
| `ngay_bat_dau` | DATETIME(6) | Có | — | Ranh giới đầu kỳ, UTC. |
| `ngay_ket_thuc` | DATETIME(6) | Có | — | Ranh giới loại trừ cuối kỳ, UTC. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(don_mua_goi_id)`; `(lan_thanh_toan_id)`; `(dang_ky_goi_tap_id, so_thu_tu)`; `(id, hoi_vien_id)`

**FK đơn:** `hoi_vien_id` → `ho_so_hoi_vien(id)`; `don_mua_goi_id` → `don_mua_goi(id)`; `lan_thanh_toan_id` → `lan_thanh_toan(id)`; `dang_ky_goi_tap_id` → `dang_ky_goi_tap(id)`

**FK kép bảo vệ dữ liệu cùng chủ/cùng nguồn:**

- `(don_mua_goi_id, hoi_vien_id)` → `don_mua_goi(id, hoi_vien_id)`.
- `(lan_thanh_toan_id, don_mua_goi_id)` → `lan_thanh_toan(id, don_mua_goi_id)`.
- `(dang_ky_goi_tap_id, hoi_vien_id)` → `dang_ky_goi_tap(id, hoi_vien_id)`.

**CHECK/điều kiện cùng hàng dự kiến:** thoi_han_ngay > 0; gia_da_mua > 0; so_thu_tu NULL hoặc > 0. Cặp ngày cùng NULL hoặc cùng có giá trị, với ngay_ket_thuc đúng bằng ngay_bat_dau cộng thoi_han_ngay ngày. 0 <= so_buoi_huan_luyen_vien_da_dung <= so_buoi_huan_luyen_vien. Có giới hạn AI thì đã dùng + giữ chỗ <= giới hạn; không có quyền AI thì giới hạn/counter AI bằng 0. Các cờ chỉ 0/1; cùng điều kiện quyền như cấu hình gói. Cờ Chat độc lập tổng/đã dùng buổi PT; không có counter tin nhắn.

**Index truy vấn bổ sung:** `(hoi_vien_id, ngay_bat_dau, ngay_ket_thuc)`; `(dang_ky_goi_tap_id, so_thu_tu)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `ho_so_hoi_vien` 1 → 0..N `ky_han_hoi_vien` qua `hoi_vien_id` (mỗi con bắt buộc có cha); `don_mua_goi` 1 → 0..1 `ky_han_hoi_vien` qua `don_mua_goi_id` (mỗi con bắt buộc có cha); `lan_thanh_toan` 1 → 0..1 `ky_han_hoi_vien` qua `lan_thanh_toan_id` (con có thể chưa tham chiếu cha); `dang_ky_goi_tap` 1 → 0..N `ky_han_hoi_vien` qua `dang_ky_goi_tap_id` (con có thể chưa tham chiếu cha).

**Được tham chiếu bởi:** `su_dung_quyen_loi` qua `ky_han_hoi_vien_id` [0..N]; `lich_su_vao_phong_tap` qua `ky_han_hoi_vien_id` [0..N]; `lich_su_su_dung_huan_luyen_vien` qua `ky_han_hoi_vien_id` [0..N]; `yeu_cau_tro_ly` qua `ky_han_hoi_vien_id` [0..N].

**Bảo toàn và lưu ý:**CHO_THANH_TOAN: payment/chuỗi/thứ tự/mua_luc/ngày đều NULL, không cấp quyền. Được thanh toán: gán payment, chuỗi, thứ tự, mua_luc trong cùng transaction. Head chưa chạy => CHO_KICH_HOAT; tail => CHO_DEN_LUOT. Thời hạn nối tiếp là [bắt đầu,kết thúc); sau activation tính ngày cho tất cả tail đã mua. Snapshot không sửa kể cả Admin đổi catalog. Q01: snapshot chốt ngay khi tạo đơn và giữ đến hạn đơn/link, không đọc lại catalog để thay quyền lúc webhook. Q02: khóa hội viên/chuỗi và cấp thứ tự theo lần đầu Backend xác nhận hợp lệ; không chèn ngược kỳ đã dùng, không dựa returnUrl/frontend. Q03: quota giữ/tính/trả theo một yeu_cau_tro_ly hợp lệ; trả quota do lỗi kỹ thuật không reset ngày/nguồn activation. Q08/Q13: Workout Tracking và Proposal PT không cần kỳ này, không trừ lượt/kích hoạt.

<a id="b19"></a>

## 19. `su_dung_quyen_loi`

**Mục đích:** Dấu vết một hành động trả phí được chấp nhận; nguồn kích hoạt và liên kết QR/PT/Chat/AI.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `hoi_vien_id` | BIGINT UNSIGNED | Không | FK → `ho_so_hoi_vien.id` | Người hưởng quyền. |
| `ky_han_hoi_vien_id` | BIGINT UNSIGNED | Không | FK → `ky_han_hoi_vien.id` | Kỳ thực sự được phép sử dụng. |
| `nguoi_thuc_hien_id` | BIGINT UNSIGNED | Không | FK → `nguoi_dung.id` | Member/PT/Receptionist tùy luồng. |
| `loai_su_dung` | VARCHAR(40) | Không | — | VAO_PHONG_TAP / YEU_CAU_TRO_LY / BUOI_HUAN_LUYEN / TRO_CHUYEN_HUAN_LUYEN. |
| `ma_hanh_dong` | CHAR(36) | Không | — | Định danh ổn định của hành động nghiệp vụ. |
| `chap_nhan_luc` | DATETIME(6) | Không | — | Mốc Backend dùng để kiểm tra và kích hoạt. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |

**UNIQUE:** `(hoi_vien_id, loai_su_dung, ma_hanh_dong)`; `(id, hoi_vien_id, ky_han_hoi_vien_id)`; `(id, hoi_vien_id)`

**FK đơn:** `hoi_vien_id` → `ho_so_hoi_vien(id)`; `ky_han_hoi_vien_id` → `ky_han_hoi_vien(id)`; `nguoi_thuc_hien_id` → `nguoi_dung(id)`

**FK kép bảo vệ dữ liệu cùng chủ/cùng nguồn:**

- `(ky_han_hoi_vien_id, hoi_vien_id)` → `ky_han_hoi_vien(id, hoi_vien_id)`.

**CHECK/điều kiện cùng hàng dự kiến:** Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng ràng buộc nghiệp vụ đã được chốt.

**Index truy vấn bổ sung:** `(ky_han_hoi_vien_id, loai_su_dung, chap_nhan_luc)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `ho_so_hoi_vien` 1 → 0..N `su_dung_quyen_loi` qua `hoi_vien_id` (mỗi con bắt buộc có cha); `ky_han_hoi_vien` 1 → 0..N `su_dung_quyen_loi` qua `ky_han_hoi_vien_id` (mỗi con bắt buộc có cha); `nguoi_dung` 1 → 0..N `su_dung_quyen_loi` qua `nguoi_thuc_hien_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** `dang_ky_goi_tap` qua `lan_su_dung_dau_tien_id` [0..1]; `lich_su_vao_phong_tap` qua `su_dung_quyen_loi_id` [0..1]; `lich_su_su_dung_huan_luyen_vien` qua `su_dung_quyen_loi_id` [0..1]; `tin_nhan` qua `su_dung_quyen_loi_id` [0..1]; `yeu_cau_tro_ly` qua `su_dung_quyen_loi_id` [0..1].

**Bảo toàn và lưu ý:**Snapshot/ledger append-only; không sửa/xóa hàng đã công bố. Snapshot/ledger append-only; không sửa/xóa hàng đã công bố. Chỉ ghi sau khi đủ quyền, ownership, quota và điều kiện luồng. Bản ghi domain tham chiếu hàng này bằng FK UNIQUE; không tạo cho việc chỉ mở màn hình/đọc lịch sử. Nguồn kích hoạt chuỗi phải thuộc kỳ thứ nhất của chính chuỗi, kiểm tra trong transaction. Riêng TRO_CHUYEN_HUAN_LUYEN chỉ ghi cho tin Member thực sự kích hoạt kỳ đầu đang chờ. Chat khi kỳ đã hoạt động, dù là tin Chat đầu tiên sau AI/QR/buổi PT, không ghi thêm usage. Không tạo usage cho từng tin hoặc từng kỳ nối tiếp; dấu vết tin nằm ở tin_nhan. Không áp ngoại lệ này cho request AI tính quota hoặc ledger buổi PT.

<a id="b20"></a>

## 20. `ma_vao_phong_tap`

**Mục đích:** Token QR động dùng một lần; tách QR check-in khỏi QR thanh toán payOS.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `hoi_vien_id` | BIGINT UNSIGNED | Không | FK → `ho_so_hoi_vien.id` | Chủ QR. |
| `chi_nhanh_id` | BIGINT UNSIGNED | Không | FK → `chi_nhanh.id` | Nơi được quét. |
| `ma_bam_bi_mat` | CHAR(64) | Không | UNIQUE | Hash token ngẫu nhiên đủ entropy; QR mang token rõ, DB chỉ giữ hash. |
| `phat_hanh_luc` | DATETIME(6) | Không | — | Mốc phát hành. |
| `het_han_luc` | DATETIME(6) | Không | — | Q09: hạn server ghi cụ thể; mặc định phat_hanh_luc + 90 giây. TTL lấy từ cấu hình/policy tập trung, không hard-code rải rác. |
| `thu_hoi_luc` | DATETIME(6) | Có | — | Vô hiệu QR nếu cần. |
| `da_su_dung_luc` | DATETIME(6) | Có | — | Cập nhật khi redemption thành công. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(ma_bam_bi_mat)`; `(id, hoi_vien_id, chi_nhanh_id)`

**FK đơn:** `hoi_vien_id` → `ho_so_hoi_vien(id)`; `chi_nhanh_id` → `chi_nhanh(id)`

**CHECK/điều kiện cùng hàng dự kiến:** het_han_luc > phat_hanh_luc.

**Index truy vấn bổ sung:** `(hoi_vien_id, het_han_luc)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `ho_so_hoi_vien` 1 → 0..N `ma_vao_phong_tap` qua `hoi_vien_id` (mỗi con bắt buộc có cha); `chi_nhanh` 1 → 0..N `ma_vao_phong_tap` qua `chi_nhanh_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** `lich_su_vao_phong_tap` qua `ma_vao_phong_tap_id` [0..1].

**Bảo toàn và lưu ý:**Phát hành QR không kích hoạt gói. Không gắn cứng vào kỳ tại lúc phát hành: lúc quét xác định lại kỳ hiệu lực để xử lý đúng chuyển kỳ. Không chứa PII hoặc ngày hết hạn Membership do client quyết định. Q09 ĐÃ CHỐT: TTL mặc định 90 giây; hợp lệ khi thời điểm kiểm tra < het_han_luc, đúng hạn cũng bị từ chối. Thay cấu hình không tính lại hạn của mã đã phát hành.

<a id="b21"></a>

## 21. `lich_su_vao_phong_tap`

**Mục đích:** Check-in thành công, đúng một lần cho mỗi QR.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `ma_vao_phong_tap_id` | BIGINT UNSIGNED | Không | UNIQUE; FK → `ma_vao_phong_tap.id` | QR đã được tiêu thụ. |
| `hoi_vien_id` | BIGINT UNSIGNED | Không | FK → `ho_so_hoi_vien.id` | Member được check-in. |
| `chi_nhanh_id` | BIGINT UNSIGNED | Không | FK → `chi_nhanh.id` | Chi nhánh thực tế. |
| `ky_han_hoi_vien_id` | BIGINT UNSIGNED | Không | FK → `ky_han_hoi_vien.id` | Kỳ cấp quyền gym tại thời điểm quét. |
| `su_dung_quyen_loi_id` | BIGINT UNSIGNED | Không | UNIQUE; FK → `su_dung_quyen_loi.id` | Sự kiện trả phí cùng transaction. |
| `nguoi_xac_nhan_id` | BIGINT UNSIGNED | Không | FK → `nguoi_dung.id` | Receptionist hợp lệ. |
| `vao_phong_luc` | DATETIME(6) | Không | — | Thời điểm Backend xác nhận. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |

**UNIQUE:** `(ma_vao_phong_tap_id)`; `(su_dung_quyen_loi_id)`

**FK đơn:** `ma_vao_phong_tap_id` → `ma_vao_phong_tap(id)`; `hoi_vien_id` → `ho_so_hoi_vien(id)`; `chi_nhanh_id` → `chi_nhanh(id)`; `ky_han_hoi_vien_id` → `ky_han_hoi_vien(id)`; `su_dung_quyen_loi_id` → `su_dung_quyen_loi(id)`; `nguoi_xac_nhan_id` → `nguoi_dung(id)`

**FK kép bảo vệ dữ liệu cùng chủ/cùng nguồn:**

- `(ma_vao_phong_tap_id, hoi_vien_id, chi_nhanh_id)` → `ma_vao_phong_tap(id, hoi_vien_id, chi_nhanh_id)`.
- `(su_dung_quyen_loi_id, hoi_vien_id, ky_han_hoi_vien_id)` → `su_dung_quyen_loi(id, hoi_vien_id, ky_han_hoi_vien_id)`.

**CHECK/điều kiện cùng hàng dự kiến:** Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt.

**Index truy vấn bổ sung:** `(hoi_vien_id, vao_phong_luc, id)`; `(chi_nhanh_id, vao_phong_luc)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `ma_vao_phong_tap` 1 → 0..1 `lich_su_vao_phong_tap` qua `ma_vao_phong_tap_id` (mỗi con bắt buộc có cha); `ho_so_hoi_vien` 1 → 0..N `lich_su_vao_phong_tap` qua `hoi_vien_id` (mỗi con bắt buộc có cha); `chi_nhanh` 1 → 0..N `lich_su_vao_phong_tap` qua `chi_nhanh_id` (mỗi con bắt buộc có cha); `ky_han_hoi_vien` 1 → 0..N `lich_su_vao_phong_tap` qua `ky_han_hoi_vien_id` (mỗi con bắt buộc có cha); `su_dung_quyen_loi` 1 → 0..1 `lich_su_vao_phong_tap` qua `su_dung_quyen_loi_id` (mỗi con bắt buộc có cha); `nguoi_dung` 1 → 0..N `lich_su_vao_phong_tap` qua `nguoi_xac_nhan_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** Chưa có bảng con tham chiếu trong MVP.

**Bảo toàn và lưu ý:**Snapshot/ledger append-only; không sửa/xóa hàng đã công bố. Không thêm UNIQUE theo hội viên/ngày vì chưa có quy tắc chỉ được check-in một lần mỗi ngày. Quét lại QR đã check-in bị từ chối bằng conflict có kiểm soát; không trả success lần hai và không tạo thêm check-in/usage. QR không hợp lệ chỉ ghi audit khi catalog audit tương ứng được phê duyệt, không tạo check-in.

<a id="b22"></a>

## 22. `phan_cong_huan_luyen_vien`

**Mục đích:** Lịch sử quan hệ phụ trách PT–Member theo khoảng hiệu lực.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `hoi_vien_id` | BIGINT UNSIGNED | Không | FK → `ho_so_hoi_vien.id` | Member được phụ trách. |
| `huan_luyen_vien_id` | BIGINT UNSIGNED | Không | FK → `ho_so_huan_luyen_vien.id` | PT phụ trách. |
| `nguoi_phan_cong_id` | BIGINT UNSIGNED | Không | FK → `nguoi_dung.id` | Admin phân công. |
| `ngay_bat_dau` | DATETIME(6) | Không | — | Bắt đầu quan hệ. |
| `ngay_ket_thuc` | DATETIME(6) | Có | — | NULL khi chưa kết thúc. |
| `ly_do_ket_thuc` | VARCHAR(500) | Có | — | Giữ lịch sử chuyển PT. |
| `hoi_vien_dang_phan_cong_id` | BIGINT UNSIGNED | Có | UNIQUE | Cột sinh VIRTUAL: bằng hoi_vien_id khi ngay_ket_thuc IS NULL; ngược lại NULL. UNIQUE chỉ bảo vệ tối đa một khoảng phân công mở, không tự bảo vệ mọi overlap theo thời gian. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(hoi_vien_id, huan_luyen_vien_id, ngay_bat_dau)`; `(id, hoi_vien_id, huan_luyen_vien_id)`; `(id, hoi_vien_id)`; `(hoi_vien_dang_phan_cong_id)`

**FK đơn:** `hoi_vien_id` → `ho_so_hoi_vien(id)`; `huan_luyen_vien_id` → `ho_so_huan_luyen_vien(id)`; `nguoi_phan_cong_id` → `nguoi_dung(id)`

**CHECK/điều kiện cùng hàng dự kiến:** ngay_ket_thuc NULL hoặc > ngay_bat_dau.

**Index truy vấn bổ sung:** `(hoi_vien_id, ngay_bat_dau, ngay_ket_thuc)`; `(huan_luyen_vien_id, ngay_bat_dau, ngay_ket_thuc)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `ho_so_hoi_vien` 1 → 0..N `phan_cong_huan_luyen_vien` qua `hoi_vien_id` (mỗi con bắt buộc có cha); `ho_so_huan_luyen_vien` 1 → 0..N `phan_cong_huan_luyen_vien` qua `huan_luyen_vien_id` (mỗi con bắt buộc có cha); `nguoi_dung` 1 → 0..N `phan_cong_huan_luyen_vien` qua `nguoi_phan_cong_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** `lich_su_su_dung_huan_luyen_vien` qua `phan_cong_huan_luyen_vien_id` [0..N]; `ghi_chu_huan_luyen` qua `phan_cong_huan_luyen_vien_id` [0..N]; `hoi_thoai` qua `phan_cong_huan_luyen_vien_id` [0..1]; `tin_nhan` qua `phan_cong_huan_luyen_vien_id` [0..N]; `de_xuat_ke_hoach_tap` qua `phan_cong_huan_luyen_vien_id` [0..N].

**Bảo toàn và lưu ý:**Q04 ĐÃ CHỐT: mỗi Member có 0..1 PT hiệu lực tại một thời điểm, nhưng có nhiều phân công lịch sử. Không cho overlap giữa BẤT KỲ PT nào cùng Member, dùng khoảng [ngay_bat_dau, ngay_ket_thuc), NULL là không có cận cuối. Khóa ho_so_hoi_vien trước rồi các phân công theo id; đọc lại toàn bộ khoảng liên quan, loại hàng đang sửa và kiểm tra giao khoảng trước khi ghi. Index (hoi_vien_id, ngay_bat_dau, ngay_ket_thuc) hỗ trợ truy vấn. Đổi PT đóng khoảng cũ trước hoặc đúng lúc mở khoảng mới trong cùng transaction + audit; giữ hàng cũ. UNIQUE cột sinh chỉ ngăn hai hàng có ngày kết thúc NULL, kể cả hàng mở ở tương lai; không phát hiện overlap hai khoảng hữu hạn hoặc khoảng mở với khoảng hữu hạn. Không có CHECK chéo hàng và không dùng NOW() trong cột sinh. Q05: hội thoại tham chiếu đúng lần phân công; phân công kết thúc làm hội thoại cũ chỉ đọc cho Member. Không gắn phân công vào một kỳ Membership; Q13 Proposal chỉ cần phân công hợp lệ, còn Chat/buổi trực tiếp kiểm tra quyền kỳ riêng.

<a id="b23"></a>

## 23. `lich_su_su_dung_huan_luyen_vien`

**Mục đích:** Ledger buổi PT hoàn thành; nguồn đối chiếu số lượt đã dùng của đúng kỳ.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `hoi_vien_id` | BIGINT UNSIGNED | Không | FK → `ho_so_hoi_vien.id` | Người sử dụng. |
| `huan_luyen_vien_id` | BIGINT UNSIGNED | Không | FK → `ho_so_huan_luyen_vien.id` | PT xác nhận. |
| `phan_cong_huan_luyen_vien_id` | BIGINT UNSIGNED | Không | FK → `phan_cong_huan_luyen_vien.id` | Quan hệ được xác minh. |
| `ky_han_hoi_vien_id` | BIGINT UNSIGNED | Không | FK → `ky_han_hoi_vien.id` | Kỳ bị trừ một lượt. |
| `su_dung_quyen_loi_id` | BIGINT UNSIGNED | Không | UNIQUE; FK → `su_dung_quyen_loi.id` | Sự kiện dùng quyền; duy nhất. |
| `ma_buoi_huan_luyen` | CHAR(36) | Không | — | Định danh buổi ổn định trước khi gửi xác nhận, không tạo lại khi retry. |
| `trang_thai` | VARCHAR(30) | Không | — | Chỉ HOAN_THANH trong ledger MVP. |
| `so_luot_su_dung` | TINYINT UNSIGNED | Không | — | Luôn bằng 1. |
| `hoan_thanh_luc` | DATETIME(6) | Không | — | Thời điểm buổi hoàn thành. |
| `xac_nhan_luc` | DATETIME(6) | Không | — | Thời điểm server ghi nhận. |
| `nguon_thao_tac` | VARCHAR(40) | Không | — | WEB_HUAN_LUYEN_VIEN. |
| `ghi_chu` | VARCHAR(1000) | Có | — | Nội dung buổi, không chỉnh kết quả Workout. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |

**UNIQUE:** `(hoi_vien_id, huan_luyen_vien_id, ma_buoi_huan_luyen)`; `(su_dung_quyen_loi_id)`

**FK đơn:** `hoi_vien_id` → `ho_so_hoi_vien(id)`; `huan_luyen_vien_id` → `ho_so_huan_luyen_vien(id)`; `phan_cong_huan_luyen_vien_id` → `phan_cong_huan_luyen_vien(id)`; `ky_han_hoi_vien_id` → `ky_han_hoi_vien(id)`; `su_dung_quyen_loi_id` → `su_dung_quyen_loi(id)`

**FK kép bảo vệ dữ liệu cùng chủ/cùng nguồn:**

- `(phan_cong_huan_luyen_vien_id, hoi_vien_id, huan_luyen_vien_id)` → `phan_cong_huan_luyen_vien(id, hoi_vien_id, huan_luyen_vien_id)`.
- `(su_dung_quyen_loi_id, hoi_vien_id, ky_han_hoi_vien_id)` → `su_dung_quyen_loi(id, hoi_vien_id, ky_han_hoi_vien_id)`.

**CHECK/điều kiện cùng hàng dự kiến:** trang_thai=HOAN_THANH; so_luot_su_dung=1; hoan_thanh_luc <= xac_nhan_luc.

**Index truy vấn bổ sung:** `(ky_han_hoi_vien_id, xac_nhan_luc)`; `(huan_luyen_vien_id, xac_nhan_luc)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `ho_so_hoi_vien` 1 → 0..N `lich_su_su_dung_huan_luyen_vien` qua `hoi_vien_id` (mỗi con bắt buộc có cha); `ho_so_huan_luyen_vien` 1 → 0..N `lich_su_su_dung_huan_luyen_vien` qua `huan_luyen_vien_id` (mỗi con bắt buộc có cha); `phan_cong_huan_luyen_vien` 1 → 0..N `lich_su_su_dung_huan_luyen_vien` qua `phan_cong_huan_luyen_vien_id` (mỗi con bắt buộc có cha); `ky_han_hoi_vien` 1 → 0..N `lich_su_su_dung_huan_luyen_vien` qua `ky_han_hoi_vien_id` (mỗi con bắt buộc có cha); `su_dung_quyen_loi` 1 → 0..1 `lich_su_su_dung_huan_luyen_vien` qua `su_dung_quyen_loi_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** Chưa có bảng con tham chiếu trong MVP.

**Bảo toàn và lưu ý:**Snapshot/ledger append-only; không sửa/xóa hàng đã công bố. Snapshot/ledger append-only; không sửa/xóa hàng đã công bố. Không cần booking để tạo ledger. Hai request dùng lượt cuối khóa cùng kỳ. Ledger + counter + activation + audit ghi một transaction. Không tạo bảng chuyển lượt/hoàn lượt/điều chỉnh trong MVP. Buổi PT trực tiếp không đồng nhất với phien_tap. Xác nhận cần snapshot tổng buổi > 0 và đã dùng < tổng; không kiểm tra cờ Chat để cấp/trừ buổi. Gói chỉ Chat với tổng buổi=0 không được ghi ledger buổi PT. Q06 ĐÃ CHỐT: chỉ xác nhận hợp lệ khi đúng kỳ còn hiệu lực (hoặc head được phép kích hoạt), còn quota và phân công hợp lệ. Nếu kỳ của buổi đã hết trước xác nhận thì từ chối; hoan_thanh_luc không được dùng để backdate activation/trừ kỳ cũ hoặc mượn kỳ mới. Đối chiếu buổi với đúng kỳ, không chọn kỳ khác chỉ vì còn lượt. Q13 tạo/apply Proposal không tạo ledger này. Q12 không hard-delete; correction Admin là Future Development.

<a id="b24"></a>

## 24. `ghi_chu_huan_luyen`

**Mục đích:** Ghi chú tư vấn độc lập, không thay thế kế hoạch hoặc kết quả tập.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `hoi_vien_id` | BIGINT UNSIGNED | Không | FK → `ho_so_hoi_vien.id` | Người nhận tư vấn. |
| `phan_cong_huan_luyen_vien_id` | BIGINT UNSIGNED | Không | FK → `phan_cong_huan_luyen_vien.id` | Quan hệ cho phép ghi chú. |
| `nguoi_tao_id` | BIGINT UNSIGNED | Không | FK → `nguoi_dung.id` | Tài khoản PT. |
| `ke_hoach_tap_id` | BIGINT UNSIGNED | Có | FK → `ke_hoach_tap.id` | Ngữ cảnh kế hoạch tùy chọn. |
| `phien_tap_id` | BIGINT UNSIGNED | Có | FK → `phien_tap.id` | Ngữ cảnh lịch sử tùy chọn, chỉ tham chiếu. |
| `noi_dung` | TEXT | Không | — | Nội dung tư vấn. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |

**UNIQUE:** Không có ngoài PK; không đặt UNIQUE tùy tiện cho dữ liệu lặp hợp lệ.

**FK đơn:** `hoi_vien_id` → `ho_so_hoi_vien(id)`; `phan_cong_huan_luyen_vien_id` → `phan_cong_huan_luyen_vien(id)`; `nguoi_tao_id` → `nguoi_dung(id)`; `ke_hoach_tap_id` → `ke_hoach_tap(id)`; `phien_tap_id` → `phien_tap(id)`

**FK kép bảo vệ dữ liệu cùng chủ/cùng nguồn:**

- `(phan_cong_huan_luyen_vien_id, hoi_vien_id)` → `phan_cong_huan_luyen_vien(id, hoi_vien_id)`.
- `(ke_hoach_tap_id, hoi_vien_id)` → `ke_hoach_tap(id, hoi_vien_id)`.
- `(phien_tap_id, hoi_vien_id)` → `phien_tap(id, hoi_vien_id)`.

**CHECK/điều kiện cùng hàng dự kiến:** Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt.

**Index truy vấn bổ sung:** `(hoi_vien_id, ngay_tao, id)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `ho_so_hoi_vien` 1 → 0..N `ghi_chu_huan_luyen` qua `hoi_vien_id` (mỗi con bắt buộc có cha); `phan_cong_huan_luyen_vien` 1 → 0..N `ghi_chu_huan_luyen` qua `phan_cong_huan_luyen_vien_id` (mỗi con bắt buộc có cha); `nguoi_dung` 1 → 0..N `ghi_chu_huan_luyen` qua `nguoi_tao_id` (mỗi con bắt buộc có cha); `ke_hoach_tap` 1 → 0..N `ghi_chu_huan_luyen` qua `ke_hoach_tap_id` (con có thể chưa tham chiếu cha); `phien_tap` 1 → 0..N `ghi_chu_huan_luyen` qua `phien_tap_id` (con có thể chưa tham chiếu cha).

**Được tham chiếu bởi:** Chưa có bảng con tham chiếu trong MVP.

**Bảo toàn và lưu ý:**Snapshot/ledger append-only; không sửa/xóa hàng đã công bố. Đề xuất append-only cho audit. Ghi chú về một phiên đã hoàn thành không được cập nhật bất cứ cột kết quả nào của phiên.

<a id="b25"></a>

## 25. `dung_cu`

**Mục đích:** Danh mục loại dụng cụ tập, không quản lý máy/tài sản.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `ma_dung_cu` | VARCHAR(40) | Không | UNIQUE | Mã danh mục. |
| `ten_dung_cu` | VARCHAR(150) | Không | — | Tên dụng cụ. |
| `mo_ta` | TEXT | Có | — | Mô tả. |
| `trang_thai` | VARCHAR(30) | Không | — | HOAT_DONG / NGUNG_SU_DUNG. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(ma_dung_cu)`

**FK đơn:** Không có.

**CHECK/điều kiện cùng hàng dự kiến:** Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt.

**Index truy vấn bổ sung:** Chưa cần ngoài PK/UNIQUE và index FK. Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** Không có FK đi ra.

**Được tham chiếu bởi:** `dung_cu_hoi_vien` qua `dung_cu_id` [0..N]; `bai_tap_dung_cu` qua `dung_cu_id` [0..N].

**Bảo toàn và lưu ý:** Q11: quan hệ bài–dụng cụ có nghĩa AND; biến thể dụng cụ khác là bài/biến thể bài riêng, không thêm nhóm OR.

<a id="b26"></a>

## 26. `nhom_co`

**Mục đích:** Danh mục nhóm cơ dùng lọc và phân loại bài tập.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `ma_nhom_co` | VARCHAR(40) | Không | UNIQUE | Mã nhóm cơ. |
| `ten_nhom_co` | VARCHAR(150) | Không | — | Tên nhóm cơ. |
| `mo_ta` | VARCHAR(500) | Có | — | Mô tả. |
| `trang_thai` | VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin | Không | DEFAULT `HOAT_DONG` | `HOAT_DONG` / `NGUNG_SU_DUNG`; CHECK `kiem_tra_b26_01`. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(ma_nhom_co)`

**FK đơn:** Không có.

**CHECK/điều kiện cùng hàng dự kiến:** `kiem_tra_b26_01`: `trang_thai IN ('HOAT_DONG', 'NGUNG_SU_DUNG')`.

**Index truy vấn bổ sung:** Chưa cần ngoài PK/UNIQUE và index FK. Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** Không có FK đi ra.

**Được tham chiếu bởi:** `bai_tap_nhom_co` qua `nhom_co_id` [0..N].

**Bảo toàn và lưu ý:** M061 chỉ thêm trạng thái, không hard-delete nhóm cơ và không xóa/sửa `bai_tap_nhom_co` khi chuyển sang `NGUNG_SU_DUNG`. Danh sách quản trị vẫn đọc cả hai trạng thái; Backend chỉ cho tạo quan hệ B29 mới với nhóm `HOAT_DONG`. Quan hệ B29 hiện hữu tới nhóm ngừng sử dụng vẫn đọc được và phải giữ nguyên hàng, vai trò và timestamp cho tới khi nhóm được kích hoạt lại. PATCH cùng trạng thái đã chuẩn hóa là no-op, không đổi `ngay_cap_nhat` và không tạo audit trùng.

<a id="b27"></a>

## 27. `bai_tap`

**Mục đích:** Thư viện bài tập đã được quản trị, nguồn hợp lệ duy nhất cho AI/PT.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `ma_bai_tap` | VARCHAR(40) | Không | UNIQUE | Mã ổn định. |
| `ten_bai_tap` | VARCHAR(200) | Không | — | Tên. |
| `do_kho` | VARCHAR(30) | Không | — | Mức khó được chuẩn hóa. |
| `huong_dan` | TEXT | Không | — | Cách thực hiện. |
| `duong_dan_hinh_anh` | VARCHAR(1000) | Có | — | Ảnh hướng dẫn. |
| `duong_dan_video` | VARCHAR(1000) | Có | — | Video hướng dẫn. |
| `thong_tin_bo_sung` | JSON | Có | — | Metadata đã kiểm định, không thay FK nhóm cơ/dụng cụ. |
| `phien_ban_noi_dung` | INT UNSIGNED | Không | — | Tăng khi nội dung thay đổi. |
| `trang_thai` | VARCHAR(30) | Không | — | HOAT_DONG / NGUNG_SU_DUNG. |
| `nguoi_tao_id` | BIGINT UNSIGNED | Không | FK → `nguoi_dung.id` | Admin quản trị. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(ma_bai_tap)`

**FK đơn:** `nguoi_tao_id` → `nguoi_dung(id)`

**CHECK/điều kiện cùng hàng dự kiến:** phien_ban_noi_dung >= 1.

**Index truy vấn bổ sung:** `(trang_thai, do_kho)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `nguoi_dung` 1 → 0..N `bai_tap` qua `nguoi_tao_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** `bai_tap_dung_cu` qua `bai_tap_id` [0..N]; `bai_tap_nhom_co` qua `bai_tap_id` [0..N]; `bai_tap_trong_giao_an` qua `bai_tap_id` [0..N]; `bai_tap_trong_ke_hoach` qua `bai_tap_id` [0..N]; `bai_tap_trong_phien` qua `bai_tap_id` [0..N]; `bai_tap_ung_vien` qua `bai_tap_id` [0..N].

**Bảo toàn và lưu ý:**Không hard-delete khi đã được tham chiếu. History giữ snapshot tên/hướng dẫn, nên đổi catalog không sửa lịch sử. Q11: metadata và liên kết dụng cụ phải diễn giải AND; dụng cụ thay thế thuộc bài/biến thể bài khác, không nhóm OR.

<a id="b28"></a>

## 28. `bai_tap_dung_cu`

**Mục đích:** Dụng cụ cần cho một bài tập, quan hệ nhiều–nhiều.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `bai_tap_id` | BIGINT UNSIGNED | Không | FK → `bai_tap.id` | Bài tập. |
| `dung_cu_id` | BIGINT UNSIGNED | Không | FK → `dung_cu.id` | Dụng cụ cần thiết. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(bai_tap_id, dung_cu_id)`

**FK đơn:** `bai_tap_id` → `bai_tap(id)`; `dung_cu_id` → `dung_cu(id)`

**CHECK/điều kiện cùng hàng dự kiến:** Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt.

**Index truy vấn bổ sung:** Chưa cần ngoài PK/UNIQUE và index FK. Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `bai_tap` 1 → 0..N `bai_tap_dung_cu` qua `bai_tap_id` (mỗi con bắt buộc có cha); `dung_cu` 1 → 0..N `bai_tap_dung_cu` qua `dung_cu_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** Chưa có bảng con tham chiếu trong MVP.

**Bảo toàn và lưu ý:**Q11 ĐÃ CHỐT: mọi hàng dụng cụ của cùng bài đều bắt buộc (AND). Bench + Barbell cần cả hai; bài không có hàng không yêu cầu dụng cụ. Biến thể dùng dụng cụ khác quản lý như bài/biến thể bài khác trong thư viện; không thêm nhóm OR ở MVP. Candidate selection và Apply đều kiểm tra đủ tập dụng cụ, không chọn một dụng cụ bất kỳ. Không quản lý tài sản.

<a id="b29"></a>

## 29. `bai_tap_nhom_co`

**Mục đích:** Các nhóm cơ tham gia một bài tập.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `bai_tap_id` | BIGINT UNSIGNED | Không | FK → `bai_tap.id` | Bài tập. |
| `nhom_co_id` | BIGINT UNSIGNED | Không | FK → `nhom_co.id` | Nhóm cơ. |
| `vai_tro_nhom_co` | VARCHAR(20) | Không | — | CHINH / PHU. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(bai_tap_id, nhom_co_id)`

**FK đơn:** `bai_tap_id` → `bai_tap(id)`; `nhom_co_id` → `nhom_co(id)`

**CHECK/điều kiện cùng hàng dự kiến:** vai_tro_nhom_co thuộc CHINH/PHU.

**Index truy vấn bổ sung:** Chưa cần ngoài PK/UNIQUE và index FK. Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `bai_tap` 1 → 0..N `bai_tap_nhom_co` qua `bai_tap_id` (mỗi con bắt buộc có cha); `nhom_co` 1 → 0..N `bai_tap_nhom_co` qua `nhom_co_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** Chưa có bảng con tham chiếu trong MVP.

**Bảo toàn và lưu ý:**

<a id="b30"></a>

## 30. `giao_an_mau`

**Mục đích:** Giáo án mẫu có thể được chọn hoặc dùng làm candidate.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `ma_giao_an` | VARCHAR(40) | Không | UNIQUE | Mã ổn định. |
| `ten_giao_an` | VARCHAR(150) | Không | — | Tên giáo án. |
| `mo_ta` | TEXT | Có | — | Mô tả. |
| `muc_tieu` | VARCHAR(100) | Không | — | Mục tiêu. |
| `trinh_do` | VARCHAR(50) | Không | — | Kinh nghiệm phù hợp. |
| `so_buoi_moi_tuan` | TINYINT UNSIGNED | Không | — | Số buổi. |
| `phien_ban_noi_dung` | INT UNSIGNED | Không | — | Phiên bản nội dung catalog. |
| `trang_thai` | VARCHAR(30) | Không | — | HOAT_DONG / NGUNG_SU_DUNG. |
| `nguoi_tao_id` | BIGINT UNSIGNED | Không | FK → `nguoi_dung.id` | Admin quản trị. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(ma_giao_an)`

**FK đơn:** `nguoi_tao_id` → `nguoi_dung(id)`

**CHECK/điều kiện cùng hàng dự kiến:** so_buoi_moi_tuan trong 1..7; phien_ban_noi_dung >= 1.

**Index truy vấn bổ sung:** `(trang_thai, muc_tieu, trinh_do)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `nguoi_dung` 1 → 0..N `giao_an_mau` qua `nguoi_tao_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** `ngay_trong_giao_an` qua `giao_an_mau_id` [0..N]; `phien_ban_ke_hoach_tap` qua `giao_an_mau_id` [0..N]; `giao_an_ung_vien` qua `giao_an_mau_id` [0..N].

**Bảo toàn và lưu ý:**Khi dùng tạo Plan phải sao chép nội dung, không render kế hoạch/history bằng giáo án hiện tại.

<a id="b31"></a>

## 31. `ngay_trong_giao_an`

**Mục đích:** Ngày/buổi theo thứ tự trong giáo án mẫu.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `giao_an_mau_id` | BIGINT UNSIGNED | Không | FK → `giao_an_mau.id` | Giáo án cha. |
| `so_thu_tu` | TINYINT UNSIGNED | Không | — | Thứ tự ngày trong mẫu, chưa phải ngày lịch. |
| `ten_ngay` | VARCHAR(150) | Không | — | Ví dụ Push Day. |
| `thoi_luong_du_kien_phut` | SMALLINT UNSIGNED | Không | — | Thời lượng dự kiến. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(giao_an_mau_id, so_thu_tu)`

**FK đơn:** `giao_an_mau_id` → `giao_an_mau(id)`

**CHECK/điều kiện cùng hàng dự kiến:** so_thu_tu > 0; thoi_luong_du_kien_phut > 0.

**Index truy vấn bổ sung:** Chưa cần ngoài PK/UNIQUE và index FK. Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `giao_an_mau` 1 → 0..N `ngay_trong_giao_an` qua `giao_an_mau_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** `bai_tap_trong_giao_an` qua `ngay_trong_giao_an_id` [0..N].

**Bảo toàn và lưu ý:**

<a id="b32"></a>

## 32. `bai_tap_trong_giao_an`

**Mục đích:** Bài và mục tiêu kê tập của giáo án mẫu.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `ngay_trong_giao_an_id` | BIGINT UNSIGNED | Không | FK → `ngay_trong_giao_an.id` | Ngày mẫu. |
| `bai_tap_id` | BIGINT UNSIGNED | Không | FK → `bai_tap.id` | Bài được chọn. |
| `so_thu_tu` | SMALLINT UNSIGNED | Không | — | Thứ tự thực hiện. |
| `so_hiep_muc_tieu` | SMALLINT UNSIGNED | Không | — | Số hiệp. |
| `so_lan_lap_toi_thieu` | SMALLINT UNSIGNED | Không | — | Cận dưới reps. |
| `so_lan_lap_toi_da` | SMALLINT UNSIGNED | Không | — | Cận trên reps. |
| `thoi_gian_nghi_giay` | SMALLINT UNSIGNED | Không | — | Thời gian nghỉ mục tiêu. |
| `ghi_chu` | VARCHAR(1000) | Có | — | Ghi chú kê tập. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(ngay_trong_giao_an_id, so_thu_tu)`

**FK đơn:** `ngay_trong_giao_an_id` → `ngay_trong_giao_an(id)`; `bai_tap_id` → `bai_tap(id)`

**CHECK/điều kiện cùng hàng dự kiến:** Thứ tự/số hiệp/reps > 0; reps tối thiểu <= tối đa.

**Index truy vấn bổ sung:** Chưa cần ngoài PK/UNIQUE và index FK. Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `ngay_trong_giao_an` 1 → 0..N `bai_tap_trong_giao_an` qua `ngay_trong_giao_an_id` (mỗi con bắt buộc có cha); `bai_tap` 1 → 0..N `bai_tap_trong_giao_an` qua `bai_tap_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** Chưa có bảng con tham chiếu trong MVP.

**Bảo toàn và lưu ý:**Không unique bài trong ngày vì một bài có thể xuất hiện ở hai vị trí được kê rõ ràng.

<a id="b33"></a>

## 33. `ke_hoach_tap`

**Mục đích:** Định danh kế hoạch cá nhân; không chứa kết quả tập.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `hoi_vien_id` | BIGINT UNSIGNED | Không | FK → `ho_so_hoi_vien.id` | Chủ kế hoạch. |
| `ten_ke_hoach` | VARCHAR(150) | Không | — | Tên hiển thị. |
| `trang_thai` | VARCHAR(30) | Không | — | DANG_SU_DUNG / LUU_TRU. |
| `phien_ban_hien_tai_id` | BIGINT UNSIGNED | Có | UNIQUE; FK → `phien_ban_ke_hoach_tap.id` | Con trỏ phiên bản chính thức hiện tại. |
| `nguoi_tao_id` | BIGINT UNSIGNED | Không | FK → `nguoi_dung.id` | Người thao tác tạo chính thức. |
| `ma_lan_tao` | CHAR(36) | Không | — | Chống tạo kế hoạch hai lần. |
| `hoi_vien_dang_su_dung_id` | BIGINT UNSIGNED | Có | UNIQUE | Cột sinh VIRTUAL: bằng hoi_vien_id khi trang_thai=DANG_SU_DUNG; LUU_TRU trả NULL. UNIQUE bảo vệ tối đa một Active Plan/Member, vẫn giữ mọi Plan lưu trữ. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(hoi_vien_id, ma_lan_tao)`; `(id, hoi_vien_id)`; `(phien_ban_hien_tai_id)`; `(hoi_vien_dang_su_dung_id)`

**FK đơn:** `hoi_vien_id` → `ho_so_hoi_vien(id)`; `phien_ban_hien_tai_id` → `phien_ban_ke_hoach_tap(id)`; `nguoi_tao_id` → `nguoi_dung(id)`

**FK kép bảo vệ dữ liệu cùng chủ/cùng nguồn:**

- `(phien_ban_hien_tai_id, id)` → `phien_ban_ke_hoach_tap(id, ke_hoach_tap_id)`.

**CHECK/điều kiện cùng hàng dự kiến:** Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt.

**Index truy vấn bổ sung:** `(hoi_vien_id, trang_thai)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `ho_so_hoi_vien` 1 → 0..N `ke_hoach_tap` qua `hoi_vien_id` (mỗi con bắt buộc có cha); `phien_ban_ke_hoach_tap` 1 → 0..1 `ke_hoach_tap` qua `phien_ban_hien_tai_id` (con có thể chưa tham chiếu cha); `nguoi_dung` 1 → 0..N `ke_hoach_tap` qua `nguoi_tao_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** `ghi_chu_huan_luyen` qua `ke_hoach_tap_id` [0..N]; `phien_ban_ke_hoach_tap` qua `ke_hoach_tap_id` [0..N]; `buoi_tap_du_kien` qua `ke_hoach_tap_id` [0..N]; `de_xuat_ke_hoach_tap` qua `ke_hoach_tap_id` [0..N].

**Bảo toàn và lưu ý:**Q07A ĐÃ CHỐT: tối đa một DANG_SU_DUNG mỗi Member; UNIQUE hoi_vien_dang_su_dung_id là hàng rào cuối, các Plan LUU_TRU trả NULL và giữ toàn bộ history. Khóa hội viên -> Plan theo id; lưu trữ Plan cũ trước khi công bố Plan mới, cùng transaction với version/con trỏ, lịch hợp lệ và audit. Không chuyển lịch/history cũ sang Plan mới hoặc xóa để né UNIQUE. Con trỏ phien_ban_hien_tai_id NULL chỉ trong transaction khởi tạo trước version 1; không công bố Plan thiếu version. Q08: Plan/Workout của Member không yêu cầu Membership đang hoạt động; vẫn kiểm tra ownership và workflow.

<a id="b34"></a>

## 34. `phien_ban_ke_hoach_tap`

**Mục đích:** Snapshot kế hoạch đã được chấp thuận, bất biến và có nguồn.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `ke_hoach_tap_id` | BIGINT UNSIGNED | Không | FK → `ke_hoach_tap.id` | Kế hoạch cha. |
| `so_phien_ban` | INT UNSIGNED | Không | — | Số phiên bản tăng dần. |
| `phien_ban_truoc_id` | BIGINT UNSIGNED | Có | FK → `phien_ban_ke_hoach_tap.id` | Phiên bản cơ sở cùng kế hoạch. |
| `giao_an_mau_id` | BIGINT UNSIGNED | Có | FK → `giao_an_mau.id` | Nguồn giáo án, chỉ để truy vết. |
| `ten_giao_an_da_chon` | VARCHAR(150) | Có | — | Snapshot tên nguồn. |
| `de_xuat_ke_hoach_tap_id` | BIGINT UNSIGNED | Có | UNIQUE; FK → `de_xuat_ke_hoach_tap.id` | Proposal đã áp dụng; NULL nếu Member tự chọn template hợp lệ. |
| `nguon_tao` | VARCHAR(30) | Không | — | HOI_VIEN / HUAN_LUYEN_VIEN / TRO_LY. |
| `nguoi_tao_id` | BIGINT UNSIGNED | Không | FK → `nguoi_dung.id` | Người thực hiện/khởi xướng, AI không có tài khoản người giả. |
| `muc_tieu` | VARCHAR(100) | Không | — | Snapshot mục tiêu. |
| `ap_dung_tu_ngay` | DATE | Không | — | Ngày bắt đầu tác động lịch tương lai. |
| `ly_do_thay_doi` | TEXT | Có | — | Giải thích thay đổi. |
| `ma_bam_noi_dung` | CHAR(64) | Không | — | Dấu vết nội dung snapshot. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |

**UNIQUE:** `(ke_hoach_tap_id, so_phien_ban)`; `(de_xuat_ke_hoach_tap_id)`; `(id, ke_hoach_tap_id)`

**FK đơn:** `ke_hoach_tap_id` → `ke_hoach_tap(id)`; `phien_ban_truoc_id` → `phien_ban_ke_hoach_tap(id)`; `giao_an_mau_id` → `giao_an_mau(id)`; `de_xuat_ke_hoach_tap_id` → `de_xuat_ke_hoach_tap(id)`; `nguoi_tao_id` → `nguoi_dung(id)`

**FK kép bảo vệ dữ liệu cùng chủ/cùng nguồn:**

- `(phien_ban_truoc_id, ke_hoach_tap_id)` → `phien_ban_ke_hoach_tap(id, ke_hoach_tap_id)`.

**CHECK/điều kiện cùng hàng dự kiến:** so_phien_ban >= 1.

**Index truy vấn bổ sung:** `(ke_hoach_tap_id, ngay_tao)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `ke_hoach_tap` 1 → 0..N `phien_ban_ke_hoach_tap` qua `ke_hoach_tap_id` (mỗi con bắt buộc có cha); `phien_ban_ke_hoach_tap` 1 → 0..N `phien_ban_ke_hoach_tap` qua `phien_ban_truoc_id` (con có thể chưa tham chiếu cha); `giao_an_mau` 1 → 0..N `phien_ban_ke_hoach_tap` qua `giao_an_mau_id` (con có thể chưa tham chiếu cha); `de_xuat_ke_hoach_tap` 1 → 0..1 `phien_ban_ke_hoach_tap` qua `de_xuat_ke_hoach_tap_id` (con có thể chưa tham chiếu cha); `nguoi_dung` 1 → 0..N `phien_ban_ke_hoach_tap` qua `nguoi_tao_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** `ke_hoach_tap` qua `phien_ban_hien_tai_id` [0..1]; `phien_ban_ke_hoach_tap` qua `phien_ban_truoc_id` [0..N]; `ngay_trong_ke_hoach` qua `phien_ban_ke_hoach_tap_id` [0..N]; `buoi_tap_du_kien` qua `phien_ban_ke_hoach_tap_id` [0..N]; `de_xuat_ke_hoach_tap` qua `phien_ban_co_so_id` [0..N].

**Bảo toàn và lưu ý:**Snapshot/ledger append-only; không sửa/xóa hàng đã công bố. Không UPDATE/DELETE nội dung version đã công bố. Version mới không chuyển FK của session cũ. Template sửa không kéo theo sửa version.

<a id="b35"></a>

## 35. `ngay_trong_ke_hoach`

**Mục đích:** Ngày kê tập thuộc một snapshot phiên bản.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `phien_ban_ke_hoach_tap_id` | BIGINT UNSIGNED | Không | FK → `phien_ban_ke_hoach_tap.id` | Version sở hữu. |
| `ma_ngay_logic` | CHAR(36) | Không | — | Định danh ngày ổn định để so sánh các version. |
| `so_thu_tu` | TINYINT UNSIGNED | Không | — | Thứ tự. |
| `thu_trong_tuan` | TINYINT UNSIGNED | Không | — | 2=thứ Hai,...,8=Chủ nhật. |
| `ten_ngay` | VARCHAR(150) | Không | — | Tên hiển thị. |
| `thoi_luong_du_kien_phut` | SMALLINT UNSIGNED | Không | — | Thời lượng. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |

**UNIQUE:** `(phien_ban_ke_hoach_tap_id, ma_ngay_logic)`; `(phien_ban_ke_hoach_tap_id, so_thu_tu)`; `(id, phien_ban_ke_hoach_tap_id)`

**FK đơn:** `phien_ban_ke_hoach_tap_id` → `phien_ban_ke_hoach_tap(id)`

**CHECK/điều kiện cùng hàng dự kiến:** thu_trong_tuan trong 2..8; thứ tự và thời lượng > 0.

**Index truy vấn bổ sung:** Chưa cần ngoài PK/UNIQUE và index FK. Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `phien_ban_ke_hoach_tap` 1 → 0..N `ngay_trong_ke_hoach` qua `phien_ban_ke_hoach_tap_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** `bai_tap_trong_ke_hoach` qua `ngay_trong_ke_hoach_id` [0..N]; `buoi_tap_du_kien` qua `ngay_trong_ke_hoach_id` [0..N].

**Bảo toàn và lưu ý:**Snapshot/ledger append-only; không sửa/xóa hàng đã công bố. Một version có thể giữ cấu trúc khác version trước; không ghi đè hàng cũ.

<a id="b36"></a>

## 36. `bai_tap_trong_ke_hoach`

**Mục đích:** Đơn kê bài tập của một ngày/version, tách kết quả thực tế.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `ngay_trong_ke_hoach_id` | BIGINT UNSIGNED | Không | FK → `ngay_trong_ke_hoach.id` | Ngày kê tập. |
| `bai_tap_id` | BIGINT UNSIGNED | Không | FK → `bai_tap.id` | Bài trong catalog. |
| `ma_bai_logic` | CHAR(36) | Không | — | Mã vị trí kê tập để so sánh version. |
| `so_thu_tu` | SMALLINT UNSIGNED | Không | — | Thứ tự. |
| `ten_bai_tap` | VARCHAR(200) | Không | — | Snapshot tên. |
| `huong_dan` | TEXT | Không | — | Snapshot hướng dẫn. |
| `dung_cu_yeu_cau` | JSON | Không | — | Snapshot các mã/tên dụng cụ. |
| `so_hiep_muc_tieu` | SMALLINT UNSIGNED | Không | — | Số hiệp kê. |
| `so_lan_lap_toi_thieu` | SMALLINT UNSIGNED | Không | — | Reps tối thiểu. |
| `so_lan_lap_toi_da` | SMALLINT UNSIGNED | Không | — | Reps tối đa. |
| `khoi_luong_muc_tieu_kg` | DECIMAL(7,2) | Có | — | Tạ mục tiêu nếu được kê, không phải tạ thực tế. |
| `thoi_gian_nghi_giay` | SMALLINT UNSIGNED | Không | — | Nghỉ mục tiêu. |
| `ghi_chu` | VARCHAR(1000) | Có | — | Ghi chú kê tập đã được chấp thuận. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |

**UNIQUE:** `(ngay_trong_ke_hoach_id, so_thu_tu)`; `(ngay_trong_ke_hoach_id, ma_bai_logic)`

**FK đơn:** `ngay_trong_ke_hoach_id` → `ngay_trong_ke_hoach(id)`; `bai_tap_id` → `bai_tap(id)`

**CHECK/điều kiện cùng hàng dự kiến:** Thứ tự/số hiệp/reps > 0; tối thiểu <= tối đa; khối lượng NULL hoặc >= 0.

**Index truy vấn bổ sung:** Chưa cần ngoài PK/UNIQUE và index FK. Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `ngay_trong_ke_hoach` 1 → 0..N `bai_tap_trong_ke_hoach` qua `ngay_trong_ke_hoach_id` (mỗi con bắt buộc có cha); `bai_tap` 1 → 0..N `bai_tap_trong_ke_hoach` qua `bai_tap_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** `bai_tap_trong_phien` qua `bai_tap_trong_ke_hoach_id` [0..N].

**Bảo toàn và lưu ý:**Snapshot/ledger append-only; không sửa/xóa hàng đã công bố. Snapshot giúp tái hiện đơn kê ngay cả khi catalog đổi. Ghi chú PT tự do nằm ở ghi_chu_huan_luyen, không dùng cột này để bỏ qua Proposal.

<a id="b37"></a>

## 37. `buoi_tap_du_kien`

**Mục đích:** Lịch cụ thể theo ngày, gắn đúng version/ngày kê; thay lịch bằng hàng mới có liên kết thay thế.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `hoi_vien_id` | BIGINT UNSIGNED | Không | FK → `ho_so_hoi_vien.id` | Chủ lịch. |
| `ke_hoach_tap_id` | BIGINT UNSIGNED | Không | FK → `ke_hoach_tap.id` | Kế hoạch. |
| `phien_ban_ke_hoach_tap_id` | BIGINT UNSIGNED | Không | FK → `phien_ban_ke_hoach_tap.id` | Snapshot được lên lịch. |
| `ngay_trong_ke_hoach_id` | BIGINT UNSIGNED | Không | FK → `ngay_trong_ke_hoach.id` | Nội dung kê của buổi. |
| `ma_buoi_logic` | CHAR(36) | Không | — | Định danh cùng một buổi xuyên lần đổi lịch. |
| `ngay_tap` | DATE | Không | — | Ngày địa phương tại chi nhánh. |
| `gio_bat_dau_du_kien` | TIME | Có | — | Nếu chưa hẹn giờ thì NULL. |
| `gio_ket_thuc_du_kien` | TIME | Có | — | Đi cùng giờ bắt đầu. |
| `trang_thai` | VARCHAR(30) | Không | — | CHUA_TAP / DANG_TAP / HOAN_THANH / BO_QUA / HUY / DA_THAY_THE. |
| `thay_the_buoi_tap_id` | BIGINT UNSIGNED | Có | UNIQUE; FK → `buoi_tap_du_kien.id` | Hàng lịch cũ được thay thế bởi hàng này. |
| `ma_buoi_con_hieu_luc` | CHAR(36) | Có | — | Cột sinh VIRTUAL: bằng ma_buoi_logic khi CHUA_TAP/DANG_TAP/HOAN_THANH/BO_QUA; HUY/DA_THAY_THE trả NULL. Giữ cơ chế một phiên bản lịch còn hiệu lực cho mỗi mã buổi. |
| `ngay_tap_con_hieu_luc` | DATE | Có | — | Cột sinh VIRTUAL: bằng ngay_tap khi trạng thái CHUA_TAP/DANG_TAP/HOAN_THANH/BO_QUA; HUY/DA_THAY_THE trả NULL. UNIQUE (hoi_vien_id, ngay_tap_con_hieu_luc) giữ tối đa một lịch có giá trị mỗi Member/ngày. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(phien_ban_ke_hoach_tap_id, ma_buoi_logic)`; `(thay_the_buoi_tap_id)`; `(hoi_vien_id, ma_buoi_con_hieu_luc)`; `(id, hoi_vien_id)`; `(hoi_vien_id, ngay_tap_con_hieu_luc)`

**FK đơn:** `hoi_vien_id` → `ho_so_hoi_vien(id)`; `ke_hoach_tap_id` → `ke_hoach_tap(id)`; `phien_ban_ke_hoach_tap_id` → `phien_ban_ke_hoach_tap(id)`; `ngay_trong_ke_hoach_id` → `ngay_trong_ke_hoach(id)`; `thay_the_buoi_tap_id` → `buoi_tap_du_kien(id)`

**FK kép bảo vệ dữ liệu cùng chủ/cùng nguồn:**

- `(ke_hoach_tap_id, hoi_vien_id)` → `ke_hoach_tap(id, hoi_vien_id)`.
- `(phien_ban_ke_hoach_tap_id, ke_hoach_tap_id)` → `phien_ban_ke_hoach_tap(id, ke_hoach_tap_id)`.
- `(ngay_trong_ke_hoach_id, phien_ban_ke_hoach_tap_id)` → `ngay_trong_ke_hoach(id, phien_ban_ke_hoach_tap_id)`.

**CHECK/điều kiện cùng hàng dự kiến:** Cặp giờ cùng NULL hoặc cùng có và bắt đầu < kết thúc; buổi qua nửa đêm chưa thuộc thiết kế MVP.

**Index truy vấn bổ sung:** `(hoi_vien_id, ngay_tap, trang_thai)`; `(ke_hoach_tap_id, phien_ban_ke_hoach_tap_id)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `ho_so_hoi_vien` 1 → 0..N `buoi_tap_du_kien` qua `hoi_vien_id` (mỗi con bắt buộc có cha); `ke_hoach_tap` 1 → 0..N `buoi_tap_du_kien` qua `ke_hoach_tap_id` (mỗi con bắt buộc có cha); `phien_ban_ke_hoach_tap` 1 → 0..N `buoi_tap_du_kien` qua `phien_ban_ke_hoach_tap_id` (mỗi con bắt buộc có cha); `ngay_trong_ke_hoach` 1 → 0..N `buoi_tap_du_kien` qua `ngay_trong_ke_hoach_id` (mỗi con bắt buộc có cha); `buoi_tap_du_kien` 1 → 0..1 `buoi_tap_du_kien` qua `thay_the_buoi_tap_id` (con có thể chưa tham chiếu cha).

**Được tham chiếu bởi:** `buoi_tap_du_kien` qua `thay_the_buoi_tap_id` [0..1]; `phien_tap` qua `buoi_tap_du_kien_id` [0..1].

**Bảo toàn và lưu ý:**Q07B ĐÃ CHỐT: mỗi Member tối đa một lịch có giá trị/ngày. CHUA_TAP, DANG_TAP, HOAN_THANH, BO_QUA giữ slot; HUY và DA_THAY_THE giải phóng slot nhưng giữ hàng lịch sử. Không sửa ngày/nội dung/FK để làm mất lịch cũ. Khóa hội viên -> Plan -> lịch/phiên liên quan theo id; đánh dấu hàng cũ trước khi tạo hàng thay thế cùng transaction, UNIQUE ngày và mã buổi là hàng rào cuối. Giữ ma_buoi_logic, thay_the_buoi_tap_id và UNIQUE (phien_ban_ke_hoach_tap_id, ma_buoi_logic). Vì UNIQUE này, lịch thay thế cùng mã buổi phải thuộc version mới theo workflow đã xác nhận, không tạo lại trong cùng version. Chỉ thay lịch CHUA_TAP tương lai hoặc lịch có phiên HUY theo Q07C; không thay lịch có phiên DANG_TAP/HOAN_THANH. Với phiên HUY, giữ phiên và FK lịch cũ; lịch cũ chuyển HUY/DA_THAY_THE theo bước hủy/thay, lịch mới phải hợp lệ về ngày, version, ownership và slot. Không tạo lịch giả hoặc Free Workout. Q08: Start từ lịch hợp lệ không yêu cầu active Membership.

<a id="b38"></a>

## 38. `phien_tap`

**Mục đích:** Workout Session thực tế; HOAN_THANH là mốc khóa toàn bộ kết quả và các bảng con.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `hoi_vien_id` | BIGINT UNSIGNED | Không | FK → `ho_so_hoi_vien.id` | Người tập. |
| `buoi_tap_du_kien_id` | BIGINT UNSIGNED | Không | UNIQUE; FK → `buoi_tap_du_kien.id` | Bắt buộc là lịch có sẵn thuộc Plan/Schedule của Member; không có Free Workout ngoài lịch. |
| `ma_lan_bat_dau` | CHAR(36) | Không | — | Idempotency khởi tạo. |
| `ten_buoi_tap` | VARCHAR(150) | Không | — | Snapshot tên buổi. |
| `bat_dau_luc` | DATETIME(6) | Không | — | Bắt đầu thực tế. |
| `ket_thuc_luc` | DATETIME(6) | Có | — | Kết thúc thực tế. |
| `trang_thai` | VARCHAR(30) | Không | — | DANG_TAP / HOAN_THANH / HUY. |
| `ghi_chu` | VARCHAR(1000) | Có | — | Ghi chú kết quả trước khi khóa. |
| `phien_ban_du_lieu` | INT UNSIGNED | Không | — | Optimistic revision, mặc định 1. |
| `ma_lan_hoan_thanh` | CHAR(36) | Có | — | Khóa ổn định khi complete/retry. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(buoi_tap_du_kien_id)`; `(hoi_vien_id, ma_lan_bat_dau)`; `(hoi_vien_id, ma_lan_hoan_thanh)`; `(id, hoi_vien_id)`

**FK đơn:** `hoi_vien_id` → `ho_so_hoi_vien(id)`; `buoi_tap_du_kien_id` → `buoi_tap_du_kien(id)`

**FK kép bảo vệ dữ liệu cùng chủ/cùng nguồn:**

- `(buoi_tap_du_kien_id, hoi_vien_id)` → `buoi_tap_du_kien(id, hoi_vien_id)`.

**CHECK/điều kiện cùng hàng dự kiến:** ket_thuc_luc NULL hoặc >= bat_dau_luc; HOAN_THANH => kết thúc và mã hoàn thành không NULL.

**Index truy vấn bổ sung:** `(hoi_vien_id, bat_dau_luc, id)`; `(hoi_vien_id, trang_thai)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `ho_so_hoi_vien` 1 → 0..N `phien_tap` qua `hoi_vien_id` (mỗi con bắt buộc có cha); `buoi_tap_du_kien` 1 → 0..1 `phien_tap` qua `buoi_tap_du_kien_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** `ghi_chu_huan_luyen` qua `phien_tap_id` [0..N]; `bai_tap_trong_phien` qua `phien_tap_id` [0..N].

**Bảo toàn và lưu ý:**Q07C/D ĐÃ CHỐT: buoi_tap_du_kien_id NOT NULL UNIQUE, một lịch tối đa một phiên kể cả phiên HUY. Không restart/chuyển HUY về DANG_TAP, không thêm phiên thứ hai hoặc xóa phiên HUY để thử lại. Nếu tập lại, dùng buoi_tap_du_kien thay thế theo workflow schedule/version hợp lệ, giữ phiên/FK cũ; không tạo Free Workout hay lịch giả. Q08: Start/Save Set/Complete không yêu cầu Membership còn hiệu lực, không tạo usage trả phí hoặc kích hoạt kỳ; vẫn xác thực Member, ownership, Plan/Schedule/trạng thái, idempotency và concurrency. Mọi ghi set/complete khóa hội viên/phiên cha; HOAN_THANH cấm sửa/xóa cả cây kết quả. Q12: giữ lịch sử cả phiên HUY và HOAN_THANH.

<a id="b39"></a>

## 39. `bai_tap_trong_phien`

**Mục đích:** Bài thực tế của một phiên, sao chép đơn kê lúc bắt đầu và giữ snapshot độc lập.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `phien_tap_id` | BIGINT UNSIGNED | Không | FK → `phien_tap.id` | Phiên cha. |
| `bai_tap_id` | BIGINT UNSIGNED | Không | FK → `bai_tap.id` | ID cho thống kê xuyên thời gian. |
| `bai_tap_trong_ke_hoach_id` | BIGINT UNSIGNED | Có | FK → `bai_tap_trong_ke_hoach.id` | Nguồn kê nếu có. |
| `ma_bai_thuc_hien` | CHAR(36) | Không | — | Mã client/server ổn định khi retry. |
| `so_thu_tu` | SMALLINT UNSIGNED | Không | — | Thứ tự thực tế. |
| `ten_bai_tap` | VARCHAR(200) | Không | — | Snapshot tên lúc bắt đầu. |
| `huong_dan` | TEXT | Không | — | Snapshot hướng dẫn. |
| `dung_cu_su_dung` | JSON | Không | — | Snapshot dụng cụ. |
| `so_hiep_du_kien` | SMALLINT UNSIGNED | Không | — | Snapshot mục tiêu, không phải số hiệp thực hiện. |
| `so_lan_lap_du_kien_toi_thieu` | SMALLINT UNSIGNED | Không | — | Mục tiêu thấp. |
| `so_lan_lap_du_kien_toi_da` | SMALLINT UNSIGNED | Không | — | Mục tiêu cao. |
| `khoi_luong_du_kien_kg` | DECIMAL(7,2) | Có | — | Tạ mục tiêu. |
| `thoi_gian_nghi_du_kien_giay` | SMALLINT UNSIGNED | Không | — | Nghỉ mục tiêu. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(phien_tap_id, ma_bai_thuc_hien)`; `(phien_tap_id, so_thu_tu)`

**FK đơn:** `phien_tap_id` → `phien_tap(id)`; `bai_tap_id` → `bai_tap(id)`; `bai_tap_trong_ke_hoach_id` → `bai_tap_trong_ke_hoach(id)`

**CHECK/điều kiện cùng hàng dự kiến:** Thứ tự/số hiệp/reps > 0; reps tối thiểu <= tối đa; khối lượng NULL hoặc >= 0.

**Index truy vấn bổ sung:** `(bai_tap_id, phien_tap_id)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `phien_tap` 1 → 0..N `bai_tap_trong_phien` qua `phien_tap_id` (mỗi con bắt buộc có cha); `bai_tap` 1 → 0..N `bai_tap_trong_phien` qua `bai_tap_id` (mỗi con bắt buộc có cha); `bai_tap_trong_ke_hoach` 1 → 0..N `bai_tap_trong_phien` qua `bai_tap_trong_ke_hoach_id` (con có thể chưa tham chiếu cha).

**Được tham chiếu bởi:** `hiep_tap` qua `bai_tap_trong_phien_id` [0..N].

**Bảo toàn và lưu ý:**Mọi thay đổi bị khóa theo phiên cha khi hoàn thành. Kết quả thật nằm ở hiep_tap, không tự biến mục tiêu thành kết quả.

<a id="b40"></a>

## 40. `hiep_tap`

**Mục đích:** Kết quả thực tế từng set, chống gửi lặp và cập nhật cũ ghi đè.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `bai_tap_trong_phien_id` | BIGINT UNSIGNED | Không | FK → `bai_tap_trong_phien.id` | Bài thực tế. |
| `so_thu_tu` | SMALLINT UNSIGNED | Không | — | Thứ tự set. |
| `ma_hiep_thuc_hien` | CHAR(36) | Không | — | ID ổn định mỗi set. |
| `so_lan_lap` | SMALLINT UNSIGNED | Không | — | Reps thực tế. |
| `khoi_luong_kg` | DECIMAL(7,2) | Có | — | Tạ thực tế; NULL khi không áp dụng, 0 khi không thêm tạ. |
| `thoi_gian_nghi_thuc_te_giay` | SMALLINT UNSIGNED | Có | — | Nếu có ghi nhận timer. |
| `hoan_thanh_luc` | DATETIME(6) | Không | — | Mốc thực hiện. |
| `phien_ban_du_lieu` | INT UNSIGNED | Không | — | Tăng khi sửa set trước khi phiên hoàn thành. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(bai_tap_trong_phien_id, so_thu_tu)`; `(bai_tap_trong_phien_id, ma_hiep_thuc_hien)`

**FK đơn:** `bai_tap_trong_phien_id` → `bai_tap_trong_phien(id)`

**CHECK/điều kiện cùng hàng dự kiến:** so_thu_tu > 0; phien_ban_du_lieu >= 1; khối lượng NULL hoặc >= 0.

**Index truy vấn bổ sung:** Chưa cần ngoài PK/UNIQUE và index FK. Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `bai_tap_trong_phien` 1 → 0..N `hiep_tap` qua `bai_tap_trong_phien_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** Chưa có bảng con tham chiếu trong MVP.

**Bảo toàn và lưu ý:**Reps 0 có thể phản ánh không thực hiện được, cần chính sách validation UI; không tự áp ngưỡng y khoa. Request sửa dùng idempotency + expected revision; complete không chạy đồng thời bỏ qua khóa phiên.

<a id="b41"></a>

## 41. `hoi_thoai`

**Mục đích:** Hội thoại 1–1 Member/PT của một lần phân công; giữ lịch sử đọc sau khi quan hệ kết thúc.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `hoi_vien_id` | BIGINT UNSIGNED | Không | FK → `ho_so_hoi_vien.id` | Member của cặp. |
| `huan_luyen_vien_id` | BIGINT UNSIGNED | Không | FK → `ho_so_huan_luyen_vien.id` | PT của cặp. |
| `phan_cong_huan_luyen_vien_id` | BIGINT UNSIGNED | Không | UNIQUE; FK → `phan_cong_huan_luyen_vien.id` | Lần phân công sở hữu hội thoại; UNIQUE, tối đa một hội thoại mỗi phân công. Không đổi FK khi đổi PT hoặc cấp lại phân công. |
| `so_thu_tu_cuoi` | BIGINT UNSIGNED | Không | — | Counter tin đã commit; mặc định 0, tăng dưới khóa hội thoại. |
| `hoi_vien_doc_den_so` | BIGINT UNSIGNED | Không | — | Con trỏ đã đọc cơ bản, mặc định 0. |
| `huan_luyen_vien_doc_den_so` | BIGINT UNSIGNED | Không | — | Con trỏ đã đọc cơ bản, mặc định 0. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(phan_cong_huan_luyen_vien_id)`; `(id, phan_cong_huan_luyen_vien_id)`

**FK đơn:** `hoi_vien_id` → `ho_so_hoi_vien(id)`; `huan_luyen_vien_id` → `ho_so_huan_luyen_vien(id)`; `phan_cong_huan_luyen_vien_id` → `phan_cong_huan_luyen_vien(id)`

**FK kép bảo vệ dữ liệu cùng chủ/cùng nguồn:**

- `(phan_cong_huan_luyen_vien_id, hoi_vien_id, huan_luyen_vien_id)` → `phan_cong_huan_luyen_vien(id, hoi_vien_id, huan_luyen_vien_id)`.

**CHECK/điều kiện cùng hàng dự kiến:** Con trỏ đã đọc không âm và <= so_thu_tu_cuoi.

**Index truy vấn bổ sung:** `(hoi_vien_id, ngay_cap_nhat)`; `(huan_luyen_vien_id, ngay_cap_nhat)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `ho_so_hoi_vien` 1 → 0..N `hoi_thoai` qua `hoi_vien_id` (mỗi con bắt buộc có cha); `ho_so_huan_luyen_vien` 1 → 0..N `hoi_thoai` qua `huan_luyen_vien_id` (mỗi con bắt buộc có cha); `phan_cong_huan_luyen_vien` 1 → 0..1 `hoi_thoai` qua `phan_cong_huan_luyen_vien_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** `tin_nhan` qua `hoi_thoai_id` [0..N].

**Bảo toàn và lưu ý:**Q05 ĐÃ CHỐT: phân biệt READ HISTORY với SEND NEW MESSAGE. Member đọc lịch sử của mình dù hết Membership hoặc phân công kết thúc. Gửi mới cần cờ Chat của kỳ hợp lệ và chính lần phân công của hội thoại còn hiệu lực; PT cũ không được gửi hoặc tiếp tục truy cập theo resource scope đã mất. Đổi PT dùng hội thoại của phân công mới; PT mới không đọc hội thoại cũ. FK phân công + UNIQUE thay UNIQUE cặp Member/PT để hội thoại đã kết thúc không mở lại khi Member quay lại PT cũ: lần phân công mới có hội thoại mới, không đổi chủ hội thoại cũ. Đây là hệ quả bảo toàn hội thoại lịch sử, không thêm bảng/group chat. Không DELETE/sửa nội dung tin; hết kỳ chỉ khóa gửi, không khóa Member đọc lịch sử. Read status là cột hỗ trợ tùy chọn.

<a id="b42"></a>

## 42. `tin_nhan`

**Mục đích:** Tin nhắn text bất biến, có sequence để reconnect không mất tin.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `hoi_thoai_id` | BIGINT UNSIGNED | Không | FK → `hoi_thoai.id` | Hội thoại. |
| `so_thu_tu` | BIGINT UNSIGNED | Không | — | Sequence commit theo hội thoại. |
| `nguoi_gui_id` | BIGINT UNSIGNED | Không | FK → `nguoi_dung.id` | Một trong hai người của cặp. |
| `phan_cong_huan_luyen_vien_id` | BIGINT UNSIGNED | Không | FK → `phan_cong_huan_luyen_vien.id` | Quan hệ hợp lệ tại lúc gửi. |
| `su_dung_quyen_loi_id` | BIGINT UNSIGNED | Có | UNIQUE; FK → `su_dung_quyen_loi.id` | Chỉ khác NULL ở tin Member thực sự kích hoạt kỳ, loại TRO_CHUYEN_HUAN_LUYEN. Tin sau, kỳ đã kích hoạt trước đó và tin PT: NULL. |
| `ma_tin_nhan_phia_gui` | CHAR(36) | Không | — | Client giữ nguyên khi retry. |
| `noi_dung` | TEXT | Không | — | Chỉ text, giới hạn độ dài tại Backend. |
| `gui_luc` | DATETIME(6) | Không | — | Thời điểm server. |
| `hoi_vien_id` | BIGINT UNSIGNED | Không | FK → `ho_so_hoi_vien.id` | Lặp có chủ đích để FK kép bảo vệ cặp hội thoại/phân công. |
| `huan_luyen_vien_id` | BIGINT UNSIGNED | Không | FK → `ho_so_huan_luyen_vien.id` | PT của hội thoại, không phải lúc nào cũng là người gửi. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |

**UNIQUE:** `(hoi_thoai_id, so_thu_tu)`; `(hoi_thoai_id, nguoi_gui_id, ma_tin_nhan_phia_gui)`; `(su_dung_quyen_loi_id)`

**FK đơn:** `hoi_thoai_id` → `hoi_thoai(id)`; `nguoi_gui_id` → `nguoi_dung(id)`; `phan_cong_huan_luyen_vien_id` → `phan_cong_huan_luyen_vien(id)`; `su_dung_quyen_loi_id` → `su_dung_quyen_loi(id)`; `hoi_vien_id` → `ho_so_hoi_vien(id)`; `huan_luyen_vien_id` → `ho_so_huan_luyen_vien(id)`

**FK kép bảo vệ dữ liệu cùng chủ/cùng nguồn:**

- `(hoi_thoai_id, phan_cong_huan_luyen_vien_id)` → `hoi_thoai(id, phan_cong_huan_luyen_vien_id)`.
- `(phan_cong_huan_luyen_vien_id, hoi_vien_id, huan_luyen_vien_id)` → `phan_cong_huan_luyen_vien(id, hoi_vien_id, huan_luyen_vien_id)`.
- `(su_dung_quyen_loi_id, hoi_vien_id)` → `su_dung_quyen_loi(id, hoi_vien_id)`.

**CHECK/điều kiện cùng hàng dự kiến:** so_thu_tu > 0; nội dung không rỗng.

**Index truy vấn bổ sung:** `(hoi_thoai_id, so_thu_tu)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `hoi_thoai` 1 → 0..N `tin_nhan` qua `hoi_thoai_id` (mỗi con bắt buộc có cha); `nguoi_dung` 1 → 0..N `tin_nhan` qua `nguoi_gui_id` (mỗi con bắt buộc có cha); `phan_cong_huan_luyen_vien` 1 → 0..N `tin_nhan` qua `phan_cong_huan_luyen_vien_id` (mỗi con bắt buộc có cha); `su_dung_quyen_loi` 1 → 0..1 `tin_nhan` qua `su_dung_quyen_loi_id` (con có thể chưa tham chiếu cha); `ho_so_hoi_vien` 1 → 0..N `tin_nhan` qua `hoi_vien_id` (mỗi con bắt buộc có cha); `ho_so_huan_luyen_vien` 1 → 0..N `tin_nhan` qua `huan_luyen_vien_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** `su_kien_phat_tin_nhan` qua `tin_nhan_id` [0..1].

**Bảo toàn và lưu ý:**Snapshot/ledger append-only; không sửa/xóa hàng đã công bố. Snapshot/ledger append-only; không sửa/xóa hàng đã công bố. Không UPDATE/DELETE nội dung trong MVP. Retry cùng mã nhưng khác nội dung phải báo xung đột. Chỉ tin Member kích hoạt kỳ mới tạo một usage Chat; tin sau không tạo usage, không tái dùng FK kích hoạt và không trừ buổi. Mỗi lần GỬI tin vẫn kiểm tra cờ Chat/phân công/kỳ; đọc lịch sử của Member không bị điều kiện Membership/phân công hiện thời chặn. Khóa hội viên/chuỗi/kỳ chung với AI/QR/buổi PT; hai tin đầu đồng thời chỉ một tin được gắn nguồn kích hoạt. Tin + usage/activation nếu có + outbox commit/rollback cùng nhau. Retry tin kích hoạt trả lại bản ghi cũ. PT chủ động gửi hoặc mở/đọc/subscribe không kích hoạt. Q05: FK kép tới hoi_thoai bảo đảm tin dùng đúng lần phân công của hội thoại; FK kép tới phân công tiếp tục bảo vệ cặp Member/PT. Phân công hết hiệu lực thì không gửi mới vào hội thoại đó; Member vẫn đọc history, PT mới không được đọc. Q12 không hard-delete tin khi hết kỳ/đổi PT.

<a id="b43"></a>

## 43. `su_kien_phat_tin_nhan`

**Mục đích:** Transactional outbox cho Reverb, tránh commit tin nhưng mất broadcast.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `tin_nhan_id` | BIGINT UNSIGNED | Không | UNIQUE; FK → `tin_nhan.id` | Tin đã lưu. |
| `trang_thai` | VARCHAR(30) | Không | — | CHO_PHAT / DA_PHAT / CHO_THU_LAI. |
| `so_lan_thu` | INT UNSIGNED | Không | — | Mặc định 0. |
| `thu_lai_luc` | DATETIME(6) | Có | — | Mốc retry. |
| `phat_luc` | DATETIME(6) | Có | — | Lần phát thành công. |
| `loi_gan_nhat` | VARCHAR(500) | Có | — | Lỗi kỹ thuật đã lọc. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(tin_nhan_id)`

**FK đơn:** `tin_nhan_id` → `tin_nhan(id)`

**CHECK/điều kiện cùng hàng dự kiến:** Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt.

**Index truy vấn bổ sung:** `(trang_thai, thu_lai_luc)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `tin_nhan` 1 → 0..1 `su_kien_phat_tin_nhan` qua `tin_nhan_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** Chưa có bảng con tham chiếu trong MVP.

**Bảo toàn và lưu ý:**Ghi cùng transaction tin_nhan; phát sau commit. Có thể phát ít nhất một lần, client dedup bằng ID/sequence. Đây là độ tin cậy của Chat CORE, không phải module Notification mới.

<a id="b44"></a>

## 44. `hoi_thoai_tro_ly`

**Mục đích:** Ngữ cảnh trao đổi Member–AI, tách hội thoại PT.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `hoi_vien_id` | BIGINT UNSIGNED | Không | FK → `ho_so_hoi_vien.id` | Chủ hội thoại. |
| `tieu_de` | VARCHAR(200) | Có | — | Tiêu đề. |
| `trang_thai` | VARCHAR(30) | Không | — | DANG_MO / LUU_TRU. |
| `so_thu_tu_cuoi` | BIGINT UNSIGNED | Không | — | Sequence tin đã commit, mặc định 0. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(id, hoi_vien_id)`

**FK đơn:** `hoi_vien_id` → `ho_so_hoi_vien(id)`

**CHECK/điều kiện cùng hàng dự kiến:** Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt.

**Index truy vấn bổ sung:** `(hoi_vien_id, ngay_cap_nhat)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `ho_so_hoi_vien` 1 → 0..N `hoi_thoai_tro_ly` qua `hoi_vien_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** `tin_nhan_tro_ly` qua `hoi_thoai_tro_ly_id` [0..N]; `yeu_cau_tro_ly` qua `hoi_thoai_tro_ly_id` [0..N].

**Bảo toàn và lưu ý:**Không có tài khoản/vai trò AI. Mở hội thoại hay đọc tin cũ không được tự kích hoạt.

<a id="b45"></a>

## 45. `tin_nhan_tro_ly`

**Mục đích:** Lịch sử trao đổi AI đã được chuẩn hóa, không phải dữ liệu Plan chính thức.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `hoi_thoai_tro_ly_id` | BIGINT UNSIGNED | Không | FK → `hoi_thoai_tro_ly.id` | Hội thoại sở hữu. |
| `so_thu_tu` | BIGINT UNSIGNED | Không | — | Thứ tự tin. |
| `nguon_tin` | VARCHAR(30) | Không | — | HOI_VIEN / TRO_LY. |
| `ma_tin_nhan_phia_gui` | CHAR(36) | Không | — | Mã ổn định do client/server sinh. |
| `yeu_cau_tro_ly_id` | BIGINT UNSIGNED | Có | FK → `yeu_cau_tro_ly.id` | Request sinh phản hồi; tin đầu vào tham chiếu ngược từ request. |
| `noi_dung` | TEXT | Không | — | Text đã kiểm tra scope/an toàn hiển thị. |
| `gui_luc` | DATETIME(6) | Không | — | Thời điểm server. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |

**UNIQUE:** `(hoi_thoai_tro_ly_id, so_thu_tu)`; `(hoi_thoai_tro_ly_id, ma_tin_nhan_phia_gui)`; `(id, hoi_thoai_tro_ly_id)`

**FK đơn:** `hoi_thoai_tro_ly_id` → `hoi_thoai_tro_ly(id)`; `yeu_cau_tro_ly_id` → `yeu_cau_tro_ly(id)`

**FK kép bảo vệ dữ liệu cùng chủ/cùng nguồn:**

- `(yeu_cau_tro_ly_id, hoi_thoai_tro_ly_id)` → `yeu_cau_tro_ly(id, hoi_thoai_tro_ly_id)`.

**CHECK/điều kiện cùng hàng dự kiến:** nguon_tin trong HOI_VIEN/TRO_LY; so_thu_tu > 0.

**Index truy vấn bổ sung:** `(hoi_thoai_tro_ly_id, so_thu_tu)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `hoi_thoai_tro_ly` 1 → 0..N `tin_nhan_tro_ly` qua `hoi_thoai_tro_ly_id` (mỗi con bắt buộc có cha); `yeu_cau_tro_ly` 1 → 0..N `tin_nhan_tro_ly` qua `yeu_cau_tro_ly_id` (con có thể chưa tham chiếu cha).

**Được tham chiếu bởi:** `yeu_cau_tro_ly` qua `tin_nhan_dau_vao_id` [0..1].

**Bảo toàn và lưu ý:**Snapshot/ledger append-only; không sửa/xóa hàng đã công bố. Không lấy text này parse ngược để áp dụng kế hoạch. Structured output hợp lệ và Proposal lưu riêng.

<a id="b46"></a>

## 46. `yeu_cau_tro_ly`

**Mục đích:** Một yêu cầu nghiệp vụ AI của Member, có quyền/quota/context/candidate và nhiều lần gọi provider.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `hoi_vien_id` | BIGINT UNSIGNED | Không | FK → `ho_so_hoi_vien.id` | Người yêu cầu. |
| `hoi_thoai_tro_ly_id` | BIGINT UNSIGNED | Không | FK → `hoi_thoai_tro_ly.id` | Hội thoại. |
| `tin_nhan_dau_vao_id` | BIGINT UNSIGNED | Không | UNIQUE; FK → `tin_nhan_tro_ly.id` | Tin yêu cầu duy nhất. |
| `ky_han_hoi_vien_id` | BIGINT UNSIGNED | Có | FK → `ky_han_hoi_vien.id` | Kỳ chịu quota; NULL nếu bị từ chối trước cấp quyền. |
| `su_dung_quyen_loi_id` | BIGINT UNSIGNED | Có | UNIQUE; FK → `su_dung_quyen_loi.id` | Hành động trả phí được chấp nhận. |
| `ma_yeu_cau` | CHAR(36) | Không | — | Idempotency nghiệp vụ. |
| `loai_yeu_cau` | VARCHAR(40) | Không | — | TAO_KE_HOACH / DIEU_CHINH / THAY_BAI / GIAI_THICH / CHUA_XAC_DINH. |
| `yeu_cau_chuan_hoa` | JSON | Có | — | Yêu cầu sau normalize và kiểm định. |
| `ngu_canh_da_chot` | JSON | Có | — | Snapshot mục tiêu, lịch rảnh, dụng cụ và phiên bản nguồn tối thiểu cần thiết. |
| `phien_ban_quy_tac` | VARCHAR(50) | Có | — | Bản Rule Engine áp dụng. |
| `trang_thai` | VARCHAR(30) | Không | — | TIEP_NHAN / CAN_BO_SUNG / DANG_XU_LY / THANH_CONG / THAT_BAI / BI_TU_CHOI. |
| `trang_thai_han_muc` | VARCHAR(30) | Không | — | KHONG_AP_DUNG / GIU_CHO / DA_TINH / DA_TRA. |
| `bat_dau_xu_ly_luc` | DATETIME(6) | Có | — | Mốc xử lý. |
| `hoan_tat_luc` | DATETIME(6) | Có | — | Mốc hoàn tất. |
| `ma_loi` | VARCHAR(100) | Có | — | Lỗi chuẩn hóa, không chứa secret. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(hoi_vien_id, ma_yeu_cau)`; `(tin_nhan_dau_vao_id)`; `(su_dung_quyen_loi_id)`; `(id, hoi_thoai_tro_ly_id)`; `(id, hoi_vien_id)`

**FK đơn:** `hoi_vien_id` → `ho_so_hoi_vien(id)`; `hoi_thoai_tro_ly_id` → `hoi_thoai_tro_ly(id)`; `tin_nhan_dau_vao_id` → `tin_nhan_tro_ly(id)`; `ky_han_hoi_vien_id` → `ky_han_hoi_vien(id)`; `su_dung_quyen_loi_id` → `su_dung_quyen_loi(id)`

**FK kép bảo vệ dữ liệu cùng chủ/cùng nguồn:**

- `(hoi_thoai_tro_ly_id, hoi_vien_id)` → `hoi_thoai_tro_ly(id, hoi_vien_id)`.
- `(tin_nhan_dau_vao_id, hoi_thoai_tro_ly_id)` → `tin_nhan_tro_ly(id, hoi_thoai_tro_ly_id)`.
- `(ky_han_hoi_vien_id, hoi_vien_id)` → `ky_han_hoi_vien(id, hoi_vien_id)`.
- `(su_dung_quyen_loi_id, hoi_vien_id, ky_han_hoi_vien_id)` → `su_dung_quyen_loi(id, hoi_vien_id, ky_han_hoi_vien_id)`.

**CHECK/điều kiện cùng hàng dự kiến:** trang_thai_han_muc chỉ KHONG_AP_DUNG/GIU_CHO/DA_TINH/DA_TRA. KHONG_AP_DUNG: ky_han_hoi_vien_id và su_dung_quyen_loi_id cùng NULL; trạng thái hạn mức khác: cả hai NOT NULL. Các điều kiện này chỉ dùng cột cùng hàng; kiểm tra quyền/counter dùng transaction.

**Index truy vấn bổ sung:** `(ky_han_hoi_vien_id, trang_thai_han_muc)`; `(hoi_vien_id, ngay_tao, id)`; `(trang_thai, bat_dau_xu_ly_luc)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `ho_so_hoi_vien` 1 → 0..N `yeu_cau_tro_ly` qua `hoi_vien_id` (mỗi con bắt buộc có cha); `hoi_thoai_tro_ly` 1 → 0..N `yeu_cau_tro_ly` qua `hoi_thoai_tro_ly_id` (mỗi con bắt buộc có cha); `tin_nhan_tro_ly` 1 → 0..1 `yeu_cau_tro_ly` qua `tin_nhan_dau_vao_id` (mỗi con bắt buộc có cha); `ky_han_hoi_vien` 1 → 0..N `yeu_cau_tro_ly` qua `ky_han_hoi_vien_id` (con có thể chưa tham chiếu cha); `su_dung_quyen_loi` 1 → 0..1 `yeu_cau_tro_ly` qua `su_dung_quyen_loi_id` (con có thể chưa tham chiếu cha).

**Được tham chiếu bởi:** `tin_nhan_tro_ly` qua `yeu_cau_tro_ly_id` [0..N]; `bai_tap_ung_vien` qua `yeu_cau_tro_ly_id` [0..N]; `giao_an_ung_vien` qua `yeu_cau_tro_ly_id` [0..N]; `lan_goi_mo_hinh` qua `yeu_cau_tro_ly_id` [0..N]; `de_xuat_ke_hoach_tap` qua `yeu_cau_tro_ly_id` [0..1].

**Bảo toàn và lưu ý:**Q03 ĐÃ CHỐT: một request nghiệp vụ HỢP LỆ = một lượt. Xác minh quyền AI và điều kiện trước; khóa hội viên -> chuỗi/kỳ -> request theo id, revalidate, giữ 1 quota (GIU_CHO), ghi domain/usage và activation nếu cần trong một transaction; chỉ gọi provider sau commit. Chưa đủ điều kiện/thiếu dữ liệu trước chấp nhận: KHONG_AP_DUNG, không quota/usage/activation. Provider retry thuộc cùng request, không tạo quota mới. GIU_CHO -> DA_TINH: giảm giữ chỗ 1, tăng đã dùng 1 khi có kết quả nghiệp vụ; GIU_CHO -> DA_TRA khi lỗi kỹ thuật kết thúc request: giảm giữ chỗ 1, không tăng đã dùng. Nếu lượt đã tính rồi xác định lỗi kỹ thuật thì DA_TINH -> DA_TRA giảm đã dùng đúng 1, lưu audit; dùng khóa và trạng thái chống trả hai lần. Phản hồi kỹ thuật trễ/retry không được hồi sinh request DA_TRA hoặc ghi đè trạng thái kết thúc. Hỏi bổ sung trước chấp nhận không tính lượt; sau khi đã chấp nhận hợp lệ, không đếm từng message bổ sung/provider retry như lượt mới. Lỗi provider sau commit không xóa usage hoặc lùi đồng hồ Membership đã kích hoạt. Không dùng quyền kỳ tương lai. Q12 giữ request và nguồn kể cả lỗi.

<a id="b47"></a>

## 47. `bai_tap_ung_vien`

**Mục đích:** Tập candidate bài tập được Rule Engine cho phép tại thời điểm request.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `yeu_cau_tro_ly_id` | BIGINT UNSIGNED | Không | FK → `yeu_cau_tro_ly.id` | Request sở hữu tập candidate. |
| `bai_tap_id` | BIGINT UNSIGNED | Không | FK → `bai_tap.id` | Bài thực, có FK. |
| `phien_ban_noi_dung` | INT UNSIGNED | Không | — | Bản catalog lúc lọc. |
| `du_lieu_da_chot` | JSON | Không | — | Metadata/dụng cụ hợp lệ đã gửi vào ngữ cảnh. |
| `ly_do_phu_hop` | VARCHAR(500) | Có | — | Lý do lựa chọn của Rule Engine. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |

**UNIQUE:** `(yeu_cau_tro_ly_id, bai_tap_id)`

**FK đơn:** `yeu_cau_tro_ly_id` → `yeu_cau_tro_ly(id)`; `bai_tap_id` → `bai_tap(id)`

**CHECK/điều kiện cùng hàng dự kiến:** Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt.

**Index truy vấn bổ sung:** Chưa cần ngoài PK/UNIQUE và index FK. Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `yeu_cau_tro_ly` 1 → 0..N `bai_tap_ung_vien` qua `yeu_cau_tro_ly_id` (mỗi con bắt buộc có cha); `bai_tap` 1 → 0..N `bai_tap_ung_vien` qua `bai_tap_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** Chưa có bảng con tham chiếu trong MVP.

**Bảo toàn và lưu ý:**Snapshot/ledger append-only; không sửa/xóa hàng đã công bố. LLM chỉ chọn trong tập này. Khi Apply vẫn kiểm tra catalog hiện tại và điều kiện Member, không coi candidate cũ là giấy phép vĩnh viễn. Q11: tập dụng cụ yêu cầu của bài phải nằm trọn trong dụng cụ phù hợp của Member (AND), không dùng điều kiện có một dụng cụ trùng.

<a id="b48"></a>

## 48. `giao_an_ung_vien`

**Mục đích:** Tập giáo án hợp lệ do Rule Engine lựa chọn.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `yeu_cau_tro_ly_id` | BIGINT UNSIGNED | Không | FK → `yeu_cau_tro_ly.id` | Request. |
| `giao_an_mau_id` | BIGINT UNSIGNED | Không | FK → `giao_an_mau.id` | Giáo án thực. |
| `phien_ban_noi_dung` | INT UNSIGNED | Không | — | Bản catalog. |
| `du_lieu_da_chot` | JSON | Không | — | Snapshot cấu trúc/điều kiện lọc. |
| `ly_do_phu_hop` | VARCHAR(500) | Có | — | Giải thích. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |

**UNIQUE:** `(yeu_cau_tro_ly_id, giao_an_mau_id)`

**FK đơn:** `yeu_cau_tro_ly_id` → `yeu_cau_tro_ly(id)`; `giao_an_mau_id` → `giao_an_mau(id)`

**CHECK/điều kiện cùng hàng dự kiến:** Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt.

**Index truy vấn bổ sung:** Chưa cần ngoài PK/UNIQUE và index FK. Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `yeu_cau_tro_ly` 1 → 0..N `giao_an_ung_vien` qua `yeu_cau_tro_ly_id` (mỗi con bắt buộc có cha); `giao_an_mau` 1 → 0..N `giao_an_ung_vien` qua `giao_an_mau_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** Chưa có bảng con tham chiếu trong MVP.

**Bảo toàn và lưu ý:**Snapshot/ledger append-only; không sửa/xóa hàng đã công bố. Không để LLM tự bịa ID giáo án.

<a id="b49"></a>

## 49. `lan_goi_mo_hinh`

**Mục đích:** Audit kỹ thuật từng lần gọi LLM, tách request/quota và Proposal.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `yeu_cau_tro_ly_id` | BIGINT UNSIGNED | Không | FK → `yeu_cau_tro_ly.id` | Request cha. |
| `so_lan` | SMALLINT UNSIGNED | Không | — | Số lần gọi/retry. |
| `nha_cung_cap` | VARCHAR(100) | Không | — | Tên provider. |
| `ten_mo_hinh` | VARCHAR(150) | Không | — | Model đã dùng. |
| `ma_yeu_cau_nha_cung_cap` | VARCHAR(200) | Có | — | ID provider nếu có. |
| `phien_ban_mau_lenh` | VARCHAR(50) | Không | — | Phiên bản prompt/template kỹ thuật. |
| `phien_ban_cau_truc` | VARCHAR(50) | Không | — | Phiên bản schema output. |
| `ma_bam_phan_hoi` | CHAR(64) | Có | — | Hash phục vụ audit, không dùng để áp dụng. |
| `ket_qua_cau_truc` | JSON | Có | — | Chỉ lưu nội dung đã qua schema validation; chưa đồng nghĩa hợp lệ nghiệp vụ. |
| `ket_qua_kiem_tra` | JSON | Có | — | Các lỗi candidate/equipment/quyền/giới hạn đã chuẩn hóa. |
| `so_don_vi_dau_vao` | INT UNSIGNED | Có | — | Token usage provider báo nếu có. |
| `so_don_vi_dau_ra` | INT UNSIGNED | Có | — | Token usage đầu ra. |
| `trang_thai` | VARCHAR(30) | Không | — | DANG_GOI / THANH_CONG / LOI_CAU_TRUC / LOI_NGHIEP_VU / QUA_HAN / THAT_BAI. |
| `bat_dau_luc` | DATETIME(6) | Không | — | Bắt đầu. |
| `ket_thuc_luc` | DATETIME(6) | Có | — | Kết thúc. |
| `ma_loi` | VARCHAR(100) | Có | — | Mã lỗi kỹ thuật đã lọc. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(yeu_cau_tro_ly_id, so_lan)`; `(nha_cung_cap, ma_yeu_cau_nha_cung_cap)`

**FK đơn:** `yeu_cau_tro_ly_id` → `yeu_cau_tro_ly(id)`

**CHECK/điều kiện cùng hàng dự kiến:** so_lan > 0; kết thúc NULL hoặc >= bắt đầu.

**Index truy vấn bổ sung:** `(yeu_cau_tro_ly_id, trang_thai)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `yeu_cau_tro_ly` 1 → 0..N `lan_goi_mo_hinh` qua `yeu_cau_tro_ly_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** Chưa có bảng con tham chiếu trong MVP.

**Bảo toàn và lưu ý:**Không lưu API key, toàn bộ dữ liệu sức khỏe hoặc response JSON lỗi dưới dạng kế hoạch. Chỉ Proposal qua cả schema và business validation mới chờ xác nhận. Q03: mỗi retry tăng so_lan trong cùng yeu_cau_tro_ly, không thêm quota. Provider lỗi kỹ thuật được xử lý kết thúc request và trả quota đúng một lần; không rollback activation đã commit. Lưu mọi lần gọi/lỗi, không hard-delete theo Q12.

<a id="b50"></a>

## 50. `de_xuat_ke_hoach_tap`

**Mục đích:** Proposal chung AI/PT; chỉ trở thành Plan Version sau Member xác nhận và Backend revalidate.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `hoi_vien_id` | BIGINT UNSIGNED | Không | FK → `ho_so_hoi_vien.id` | Chủ đề xuất. |
| `nguon_de_xuat` | VARCHAR(30) | Không | — | TRO_LY / HUAN_LUYEN_VIEN. |
| `nguoi_tao_id` | BIGINT UNSIGNED | Không | FK → `nguoi_dung.id` | PT tạo hoặc Member khởi xướng request AI. |
| `phan_cong_huan_luyen_vien_id` | BIGINT UNSIGNED | Có | FK → `phan_cong_huan_luyen_vien.id` | Bắt buộc nguồn PT; NULL nguồn AI. |
| `yeu_cau_tro_ly_id` | BIGINT UNSIGNED | Có | UNIQUE; FK → `yeu_cau_tro_ly.id` | Bắt buộc nguồn AI; NULL nguồn PT. |
| `ke_hoach_tap_id` | BIGINT UNSIGNED | Có | FK → `ke_hoach_tap.id` | NULL khi đề xuất tạo kế hoạch mới. |
| `phien_ban_co_so_id` | BIGINT UNSIGNED | Có | FK → `phien_ban_ke_hoach_tap.id` | Version được dùng làm cơ sở; NULL khi tạo mới. |
| `moc_thay_doi_ke_hoach_co_so` | INT UNSIGNED | Không | — | Mốc hội viên lúc tạo, bảo vệ cả proposal tạo mới. |
| `phien_ban_ho_so_co_so` | INT UNSIGNED | Không | — | Điều kiện tập lúc tạo. |
| `loai_thay_doi` | VARCHAR(30) | Không | — | TAO_MOI / DIEU_CHINH / THAY_BAI. |
| `tieu_de` | VARCHAR(200) | Không | — | Nội dung preview. |
| `giai_thich` | TEXT | Không | — | Giải thích/khác biệt cho Member. |
| `noi_dung_de_xuat` | JSON | Không | — | Payload chuẩn hóa, kiểm định, lưu phía server và bất biến. |
| `phien_ban_cau_truc` | VARCHAR(50) | Không | — | Schema payload. |
| `ma_bam_noi_dung` | CHAR(64) | Không | — | Fingerprint nội dung đã preview. |
| `ap_dung_tu_ngay` | DATE | Không | — | Giới hạn thay đổi lịch tương lai. |
| `trang_thai` | VARCHAR(30) | Không | — | CHO_XAC_NHAN / DA_TU_CHOI / HET_HAN / XUNG_DOT / DA_AP_DUNG. |
| `het_han_luc` | DATETIME(6) | Không | — | Q09: mốc cụ thể do server cấp; mặc định ngay_tao + 24 giờ cho cả AI/PT, lấy TTL từ cấu hình/policy tập trung. |
| `nguoi_quyet_dinh_id` | BIGINT UNSIGNED | Có | FK → `nguoi_dung.id` | Phải là tài khoản Member sở hữu. |
| `quyet_dinh_luc` | DATETIME(6) | Có | — | Xác nhận hoặc từ chối. |
| `ap_dung_luc` | DATETIME(6) | Có | — | Commit áp dụng. |
| `ly_do_ket_thuc` | VARCHAR(1000) | Có | — | Từ chối/hết hạn/xung đột. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(yeu_cau_tro_ly_id)`

**FK đơn:** `hoi_vien_id` → `ho_so_hoi_vien(id)`; `nguoi_tao_id` → `nguoi_dung(id)`; `phan_cong_huan_luyen_vien_id` → `phan_cong_huan_luyen_vien(id)`; `yeu_cau_tro_ly_id` → `yeu_cau_tro_ly(id)`; `ke_hoach_tap_id` → `ke_hoach_tap(id)`; `phien_ban_co_so_id` → `phien_ban_ke_hoach_tap(id)`; `nguoi_quyet_dinh_id` → `nguoi_dung(id)`

**FK kép bảo vệ dữ liệu cùng chủ/cùng nguồn:**

- `(phan_cong_huan_luyen_vien_id, hoi_vien_id)` → `phan_cong_huan_luyen_vien(id, hoi_vien_id)`.
- `(yeu_cau_tro_ly_id, hoi_vien_id)` → `yeu_cau_tro_ly(id, hoi_vien_id)`.
- `(ke_hoach_tap_id, hoi_vien_id)` → `ke_hoach_tap(id, hoi_vien_id)`.
- `(phien_ban_co_so_id, ke_hoach_tap_id)` → `phien_ban_ke_hoach_tap(id, ke_hoach_tap_id)`.

**CHECK/điều kiện cùng hàng dự kiến:** nguon_de_xuat thuộc TRO_LY/HUAN_LUYEN_VIEN. het_han_luc > ngay_tao.

**Index truy vấn bổ sung:** `(hoi_vien_id, trang_thai, ngay_tao)`; `(ke_hoach_tap_id, trang_thai)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `ho_so_hoi_vien` 1 → 0..N `de_xuat_ke_hoach_tap` qua `hoi_vien_id` (mỗi con bắt buộc có cha); `nguoi_dung` 1 → 0..N `de_xuat_ke_hoach_tap` qua `nguoi_tao_id` (mỗi con bắt buộc có cha); `phan_cong_huan_luyen_vien` 1 → 0..N `de_xuat_ke_hoach_tap` qua `phan_cong_huan_luyen_vien_id` (con có thể chưa tham chiếu cha); `yeu_cau_tro_ly` 1 → 0..1 `de_xuat_ke_hoach_tap` qua `yeu_cau_tro_ly_id` (con có thể chưa tham chiếu cha); `ke_hoach_tap` 1 → 0..N `de_xuat_ke_hoach_tap` qua `ke_hoach_tap_id` (con có thể chưa tham chiếu cha); `phien_ban_ke_hoach_tap` 1 → 0..N `de_xuat_ke_hoach_tap` qua `phien_ban_co_so_id` (con có thể chưa tham chiếu cha); `nguoi_dung` 1 → 0..N `de_xuat_ke_hoach_tap` qua `nguoi_quyet_dinh_id` (con có thể chưa tham chiếu cha).

**Được tham chiếu bởi:** `phien_ban_ke_hoach_tap` qua `de_xuat_ke_hoach_tap_id` [0..1].

**Bảo toàn và lưu ý:**Nguồn XOR, ownership và cặp Plan/version kiểm tra bằng FK kép + transaction; không có CHECK chéo bảng. Kết quả truy từ phien_ban_ke_hoach_tap.de_xuat_ke_hoach_tap_id UNIQUE. AI/PT dùng chung bảng; PT không có lan_goi_mo_hinh. Q13 ĐÃ CHỐT: PT tạo Proposal chỉ cần authenticated PT có chính phan_cong_huan_luyen_vien hợp lệ với Member; không cần Chat, tổng/còn quota buổi hoặc active Membership. Tạo/apply Proposal PT không trừ lượt, không usage Chat/buổi và không kích hoạt Membership. Preview, Member confirm/reject, ownership, base version, mốc hồ sơ/kế hoạch, future schedule, TTL/trạng thái và phân công phải được revalidate dưới khóa; Apply thành công tạo version và audit cùng transaction. Nếu phân công nguồn đã kết thúc trước confirm, chuyển XUNG_DOT và không Apply, kể cả PT được phân công lại bằng hàng khác. Q09 TTL mặc định 24 giờ; thời điểm kiểm tra >= het_han_luc là hết hạn, không reset TTL khi preview/retry. JSON không có FK nội tại nên kiểm tra từng ID/candidate/dụng cụ AND trước Apply. Q07 kiểm tra 1 Active Plan và 1 lịch/ngày, xử lý lịch thay thế có version mới. Q12 không hard-delete Proposal.

<a id="b51"></a>

## 51. `nhat_ky_he_thong`

**Mục đích:** Audit append-only cho các thao tác quan trọng, không làm nguồn số dư/quyền lợi.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `nguoi_thuc_hien_id` | BIGINT UNSIGNED | Có | FK → `nguoi_dung.id` | NULL cho webhook/job hệ thống. |
| `loai_tac_nhan` | VARCHAR(30) | Không | — | NGUOI_DUNG / HE_THONG / CONG_THANH_TOAN. |
| `hanh_dong` | VARCHAR(100) | Không | — | Mã hành động có nghĩa. |
| `loai_doi_tuong` | VARCHAR(100) | Không | — | Tên thực thể được tác động. |
| `dinh_danh_doi_tuong` | BIGINT UNSIGNED | Có | — | ID đối tượng để truy vết, không giả định có FK đa hình. |
| `khoa_tuong_quan` | CHAR(36) | Không | — | Trace xuyên request/webhook. |
| `du_lieu_truoc` | JSON | Có | — | Whitelist thay đổi cần audit. |
| `du_lieu_sau` | JSON | Có | — | Whitelist, không token/key/PII thừa. |
| `ket_qua` | VARCHAR(30) | Không | — | THANH_CONG / BI_TU_CHOI / THAT_BAI. |
| `thuc_hien_luc` | DATETIME(6) | Không | — | Thời điểm server. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |

**UNIQUE:** Không có ngoài PK; không đặt UNIQUE tùy tiện cho dữ liệu lặp hợp lệ.

**FK đơn:** `nguoi_thuc_hien_id` → `nguoi_dung(id)`

**CHECK/điều kiện cùng hàng dự kiến:** Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng ràng buộc nghiệp vụ đã được chốt.

**Index truy vấn bổ sung:** `(loai_doi_tuong, dinh_danh_doi_tuong, thuc_hien_luc)`; `(khoa_tuong_quan)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `nguoi_dung` 1 → 0..N `nhat_ky_he_thong` qua `nguoi_thuc_hien_id` (con có thể chưa tham chiếu cha).

**Được tham chiếu bởi:** Chưa có bảng con tham chiếu trong MVP.

**Bảo toàn và lưu ý:**Snapshot/ledger append-only; không sửa/xóa hàng đã công bố. Snapshot/ledger append-only; không sửa/xóa hàng đã công bố. FK đa hình không được MariaDB bảo đảm; chỉ dùng ở audit. Quan hệ tài chính/quyền/phiên bản chính vẫn phải là FK thật. Audit thay đổi thành công ghi cùng transaction với domain. Với Role: lưu cấp/thu hồi/cấp lại, người thao tác, thời điểm và trước/sau; tham chiếu logic id hàng phan_quyen_nguoi_dung cùng cặp người dùng/vai trò. Đây là lịch sử Role của MVP, không tạo bảng lịch sử riêng. Q12 ĐÃ CHỐT: không hard-delete audit và lịch sử tài chính/quyền/PT/Workout/Chat/AI trong MVP. Khóa/ngừng account/catalog, không cascade xóa lịch sử. Không tự đặt số năm retention; anonymization, legal retention và purge/archive tự động là Future Development/vận hành sau MVP.

<a id="b52"></a>

## 52. `yeu_cau_chong_lap`

**Mục đích:** Idempotency cho request đã đăng nhập: cùng khóa/cùng nội dung trả lại kết quả, khác nội dung báo xung đột.

**Khóa chính:** `id` — BIGINT UNSIGNED, tự tăng.

| Cột | Kiểu dự kiến | NULL | Khóa / tham chiếu | Ý nghĩa |
| --- | --- | --- | --- | --- |
| `id` | BIGINT UNSIGNED | Không | PK | Khóa chính tự tăng. |
| `nguoi_dung_id` | BIGINT UNSIGNED | Không | FK → `nguoi_dung.id` | Scope người gọi. |
| `pham_vi` | VARCHAR(100) | Không | — | Tên nghiệp vụ/đường xử lý. |
| `khoa_yeu_cau` | CHAR(36) | Không | — | Khóa client giữ nguyên khi retry. |
| `ma_bam_noi_dung` | CHAR(64) | Không | — | Hash request đã chuẩn hóa. |
| `trang_thai` | VARCHAR(30) | Không | — | DANG_XU_LY / DA_HOAN_TAT / THAT_BAI. |
| `ma_phan_hoi` | SMALLINT UNSIGNED | Có | — | Mã HTTP đã trả. |
| `ket_qua_da_loc` | JSON | Có | — | ID/kết quả tối thiểu, không lưu secret/response đăng nhập. |
| `het_han_luc` | DATETIME(6) | Không | — | TTL lưu khóa, phải đủ cửa sổ retry nghiệp vụ. |
| `ngay_tao` | DATETIME(6) | Không | — | Thời điểm tạo, UTC. |
| `ngay_cap_nhat` | DATETIME(6) | Không | — | Thời điểm cập nhật, UTC. |

**UNIQUE:** `(nguoi_dung_id, pham_vi, khoa_yeu_cau)`

**FK đơn:** `nguoi_dung_id` → `nguoi_dung(id)`

**CHECK/điều kiện cùng hàng dự kiến:** het_han_luc > ngay_tao.

**Index truy vấn bổ sung:** `(trang_thai, ngay_tao)`; `(het_han_luc)` Index FK đơn/ghép được bổ sung nếu chưa có index bao phủ tiền tố.

**Quan hệ đi ra:** `nguoi_dung` 1 → 0..N `yeu_cau_chong_lap` qua `nguoi_dung_id` (mỗi con bắt buộc có cha).

**Được tham chiếu bởi:** Chưa có bảng con tham chiếu trong MVP.

**Bảo toàn và lưu ý:**Không thay thế unique nghiệp vụ lâu dài; hết TTL vẫn không cấp kỳ/apply/ghi buổi PT hai lần. Webhook dùng cơ chế su_kien_thanh_toan và unique reference, không giả làm request người dùng.

## Phần không phải bảng mới

Không có bảng `workout_history`, `progress`, `dashboard` hay `is_premium`. History đọc từ phien_tap/bai_tap_trong_phien/hiep_tap; Progress từ kết quả thực tế và chi_so_co_the; Dashboard tổng hợp đơn/giao dịch/kỳ/check-in. Không thêm bảng Booking, carry-over, refund, tài sản, file chat, nhóm chat hoặc nhóm dụng cụ OR.

Tên 52 bảng và các cột là tên chính thức dùng trong PROJECT_RULES và cả hai ERD. Các cột bổ sung ở B22/B33/B37/B41 là hệ quả trực tiếp của Q04/Q05/Q07, không đổi naming convention. Q08 không thêm entitlement Workout; Q13 không thêm quyền/quota để PT tạo Proposal.
