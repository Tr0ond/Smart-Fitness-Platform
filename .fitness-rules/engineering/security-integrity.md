# Engineering: Security, Transactions & Data Integrity

> **Module Path:** `.fitness-rules/engineering/security-integrity.md`  
> **Canonical Reference:** [PROJECT_RULES.md](../../PROJECT_RULES.md) (Phần XVI: RULE CODE 13–17; RULE CODE 15, 16; Q10, Q12; Concurrency controls)

---

## 1. Nguyên tắc Không Tin cậy Dữ liệu (Zero-Trust Data Principles)

### RULE CODE 13 – Tuyệt đối Không Tin Dữ liệu Frontend (Never Trust FE)
- Frontend (Web/Mobile) chỉ là tầng hiển thị và thu thập thao tác người dùng.
- **Mọi thông số mang tính quyết định nghiệp vụ:**
  - Giá tiền đơn hàng, số tiền thanh toán.
  - Số lượng buổi tập, quyền lợi gói tập, quota AI.
  - Thời hạn gói, mốc thời gian kích hoạt (`ngay_bat_dau`, `ngay_ket_thuc`).
  - ID người dùng thực hiện thao tác.
- **Bắt buộc phải được Backend truy vấn từ cơ sở dữ liệu và tính toán lại 100%.** Cấm nhận các tham số này trực tiếp từ request body của Client mà không qua kiểm tra chéo.

### RULE CODE 14 – Tuyệt đối Không Tin Dữ liệu LLM (Never Trust LLM)
- Đầu ra của mô hình ngôn ngữ lớn (LLM) tiềm ẩn nguy cơ ảo giác (hallucination), trả về ID không tồn tại hoặc thông số vượt ngưỡng an toàn.
- Backend bắt buộc phải bọc một lớp **Rule Engine Validator** để thẩm định toàn bộ dữ liệu trước khi lưu trữ hoặc áp dụng:
  - Kiểm tra tồn tại khóa ngoại (`bai_tap_id`, dụng cụ, nhóm cơ).
  - Kiểm tra các ràng buộc logic: set, rep, min <= max, rest, không trùng lịch, quyền sở hữu tài nguyên.

---

## 2. Giao dịch Cơ sở Dữ liệu & Tính Bất khả phân (Database Transactions)

### RULE CODE 15 – Bắt buộc Xem xét Transaction cho các Nghiệp vụ:
Phải xem xét Database Transaction với đầy đủ danh mục nghiệp vụ sau:
1. **Xử lý Webhook** (payOS callback).
2. **Cấp Membership** (tạo chuỗi `dang_ky_goi_tap` và `ky_han_hoi_vien`).
3. **Gia hạn** (chèn kỳ nối tiếp).
4. **Kích hoạt từ AI/PT/check-in đầu tiên** (chuyển `CHO_KICH_HOAT` sang `DANG_HOAT_DONG`, ghi `su_dung_quyen_loi`).
5. **Chuyển kỳ quyền lợi** (khi kỳ trước hết hạn).
6. **Ghi nhận buổi PT và trừ lượt** (ghi `lich_su_su_dung_huan_luyen_vien` và cập nhật quota).
7. **Áp dụng PT Proposal** (tạo `phien_ban_ke_hoach_tap`, cập nhật lịch) sau khi revalidate source `HUAN_LUYEN_VIEN`, assignment và Proposal context.
8. **QR Redeem** (xác thực `ma_vao_phong_tap` và ghi `lich_su_vao_phong_tap`).
9. **Complete Workout** (chuyển `phien_tap.trang_thai = HOAN_THANH` và chốt kết quả).
10. **Apply AI Proposal** (chuyển proposal thành version kế hoạch chính thức).

Nếu có lỗi xảy ra ở bất kỳ bước nào, toàn bộ giao dịch phải được rollback tự động.

---

## 3. Chống Trùng lặp & Tính Bất biến (Idempotency)

### RULE CODE 16 – Danh mục Nghiệp vụ Bắt buộc Xem xét Idempotency:
1. **payOS Webhook:** Lưu mã đơn cổng thanh toán `orderCode` (ánh xạ cột `ma_don_cong_thanh_toan` theo `TU_DIEN_DU_LIEU.md` Bảng 14, 15, 16 và migration `2026_08_29_000015_m015_tao_lan_thanh_toan.php`). Webhook gửi lặp trả HTTP 200 ngay mà không tạo thêm kỳ hạn trùng (theo RULE GYM 18, `PayOSWebhookController.php` và `PayOSWebhookTest.php`).
2. **Membership Renewal:** Xử lý mua thêm an toàn, không nhân bản kỳ nối tiếp khi gửi lặp.
3. **Membership Activation khi AI/PT/QR đồng thời:** Tuyến tính hóa; chỉ một hành động đầu tiên kích hoạt thành công, các hành động đến sau sử dụng kỳ đã kích hoạt.
4. **Ghi nhận cùng một buổi PT:** Retry hoặc 2 request xác nhận cùng một buổi chỉ tạo một bản ghi và trừ đúng một lượt.
5. **PT Proposal Apply:** Kiểm tra trạng thái nguyên tử (`WHERE id = :id AND trang_thai = 'CHO_XAC_NHAN'` theo `TU_DIEN_DU_LIEU.md` Bảng 50 và migration `2026_08_29_000050_m050_tao_de_xuat_ke_hoach_tap.php`), không apply 2 lần.
6. **QR Redeem:** Mã QR chỉ quét thành công 1 lần duy nhất, quét lại báo lỗi ngay.
7. **Workout Set gửi lại:** Retry gửi set không tạo bản ghi hiệp tập trùng lặp.
8. **Complete Workout:** Buổi tập đã hoàn thành là bất biến, request complete lặp không đổi dữ liệu.
9. **AI Proposal Apply:** Dùng `de_xuat_ke_hoach_tap`, chống double-apply bằng idempotency key và kiểm tra trạng thái nguyên tử.
10. **Chat gửi message nếu client retry:** Nhận diện bằng `client_message_id`, không tạo duplicate tin nhắn.

Stored Proposal source chỉ nhận `TRO_LY` hoặc `HUAN_LUYEN_VIEN`; AI/PT là display labels. Proposal status và confirm-time conflict phải dùng enum/status chính thức, không tự thêm provider hoặc trạng thái.

---

## 4. Xử lý Đồng thời & Cạnh tranh Dữ liệu (Concurrency & Locking Semantics)

Tuân thủ ngữ nghĩa khóa tương ứng theo từng nghiệp vụ trong canonical:
1. **Khóa Hội viên / Chuỗi / Kỳ (Member Lock Scope):**
   - Áp dụng khi kích hoạt Membership đồng thời từ nhiều nguồn (AI, PT, QR), xử lý webhook thanh toán nối tiếp, hoặc trừ quota request AI. Đọc lại trạng thái sau khóa; chỉ thao tác thắng mới ghi nguồn kích hoạt.
2. **Bảo vệ Hạn mức Buổi PT khi Đồng thời (PT Quota Concurrency):**
   - Khi hai buổi khác nhau cùng tranh lượt cuối hoặc hai request xác nhận cùng lúc: Transaction phải bảo vệ số lượt còn lại, không cho phép số lượt vượt quá tổng đã cấp theo snapshot của kỳ (`so_buoi_huan_luyen_vien`), tuyệt đối không để số lượt âm.
3. **M061 — khóa hàng và audit Muscle Group:**
   - Khóa hàng `nhom_co` trước khi đọc lại `trang_thai` và quan hệ `bai_tap_nhom_co`. Nhóm chỉ nhận `HOAT_DONG`/`NGUNG_SU_DUNG`, GET vẫn gồm inactive, không DELETE.
   - Quan hệ mới chỉ nhận nhóm active. Relation inactive hiện hữu không được sửa/xóa/đổi vai trò; replacement `muscle_groups` thiếu relation inactive trả `INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE` và rollback nguyên tử. Bỏ field relation là không cập nhật relation.
   - Cùng trạng thái sau chuẩn hóa là no-op không audit/timestamp; transition thật ghi đúng một `CAP_NHAT_TRANG_THAI_NHOM_CO` trong cùng transaction. Khóa/đọc lại tuyến tính hóa race status/relation. Không áp lifecycle này cho Equipment.
4. **Q02 & Thứ tự Thanh toán:**
   - Khi nhận webhook lệch thứ tự: Cấp thứ tự theo thời điểm Backend lần đầu xác nhận thanh toán hợp lệ dưới khóa Member; không chèn ngược chuỗi đã dùng, retry không làm đảo thứ tự.
5. **Q10 & Xử lý Sai số Thanh toán, Chữ ký không hợp lệ & Đối soát:**
   - Payload không xác thực (chữ ký số sai hoặc không hợp lệ): Backend từ chối ngay lập tức (reject) và lưu dấu vết audit; tuyệt đối không được coi là tiền hợp lệ và không chuyển tự động vào luồng xử lý nạp tiền.
   - Khoản thanh toán bất thường đã xác thực (sai số tiền, đơn hủy/hết hạn, thông tin thanh toán không khớp): Chuyển trạng thái `CAN_DOI_SOAT`, cô lập không tự động kích hoạt tài nguyên; tuyệt đối không cấp kỳ kép, không cộng thêm ngày, không hoàn tiền tự ý và không xóa giao dịch.

## 5. Chat activation transaction

Khi một Member message thắng việc kích hoạt head term, cùng transaction phải ghi message, đúng một `su_dung_quyen_loi` loại `TRO_CHUYEN_HUAN_LUYEN`, cập nhật mốc Membership và tạo outbox event. Nếu message hoặc outbox fail, rollback toàn bộ activation/usage/message; không để term đã kích hoạt mà thiếu tin hoặc ngược lại. Retry cùng `client_message_id` đọc và trả lại message/usage cũ. Tin tiếp theo, tin PT, read/open/subscribe và term đã active không tạo Chat usage mới.
