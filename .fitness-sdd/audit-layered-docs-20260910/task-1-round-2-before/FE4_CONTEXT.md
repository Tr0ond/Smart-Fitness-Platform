# Phase Context: FE-4 — Admin Payment & Reconciliation

> **Task Scope:** `FE4-ALL` (Tổng hợp toàn bộ Phase 4 — Không tách subtask)  
> **Mục tiêu:** Cung cấp giao diện quản lý Thanh toán và Đối soát read-only với độ minh bạch đầy đủ, tuyệt đối không trao thẩm quyền mutation cho Admin Web theo đúng phạm vi xác định trong Vue Plan.  
> **Canonical Plan Reference:** [docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md](../../docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md) (Section 4, 5, 23, 26, 28; Section 31: FE-4 & Section 38A: `FE4-ALL`)

---

## 1. Danh sách Màn hình & Routes Triển khai (3 Màn hình)

1. **Danh sách Thanh toán (`/admin/thanh-toan`):**
   - Route name: `adminThanhToan`.
   - Xem danh sách đơn mua gói (`don_mua_goi`) và các lần thanh toán (`lan_thanh_toan`).
   - Bộ lọc chỉ dùng các trạng thái, khoảng thời gian và phương thức mà Payment API contract hiện tại trả về/cho phép; không tự thêm enum hoặc bộ lọc từ suy đoán tài liệu cũ.
   - Phân trang server-side (`thanh_phan_trang.vue`).
2. **Chi tiết Thanh toán (`/admin/thanh-toan/:id`):**
   - Route name: `adminChiTietThanhToan`.
   - Hiển thị chi tiết giao dịch qua **Safe DTO**: Mã đơn hàng, tên gói, giá tiền, thời hạn, thông tin khách hàng, lịch sử attempt thanh toán.
   - Lịch sử sự kiện thanh toán liên quan (`su_kien_thanh_toan`).
3. **Đối soát Thanh toán (`/admin/doi-soat-thanh-toan`):**
   - Route name: `adminDoiSoatThanhToan`.
   - Danh sách các giao dịch bất thường cần đối soát (Q10): Khách chuyển sai số tiền, thanh toán khi đơn đã hủy/hết hạn, webhook không khớp đơn.
   - Hiển thị các sự kiện thanh toán chưa liên kết (`lan_thanh_toan_id = null`) và các đơn thanh toán thành công có cảnh báo bất thường theo contract đã sửa.

---

## 2. Các File Mã Nguồn Dự kiến (Expected Files — Section 28 File Inventory)

> **Nguyên tắc Kiểm tra Mã nguồn Thực tế:** Trước khi tạo bất kỳ file nào, bắt buộc kiểm tra mã nguồn thực tế trong `FE/src/`. Nếu file đã tồn tại theo cấu trúc chuẩn, tái sử dụng và kiểm thử, tuyệt đối không tạo file trùng lặp.

- **Pages (3 files theo Section 28):**
  - `src/pages/admin/thanh_toan/thanh_toan.index.vue`
  - `src/pages/admin/thanh_toan/thanh_toan.chi_tiet.vue`
  - `src/pages/admin/doi_soat_thanh_toan/doi_soat_thanh_toan.index.vue`
- **Domain Service (1 file theo Section 26):**
  - `src/services/thanh_toan.api.js` (Admin Payment/detail/events/reconciliation)
- **Store (1 file theo Section 25):**
  - `src/stores/thanh_toan.store.js` (Lưu read-only filters, pagination, selected payment/event; clear khi logout, không chứa raw payload/signature).
- **Components:**
  - `src/components/chung/` (filter, table, pagination, badge, query states)

---

## 3. Hàm Nghiệp vụ Chính & Ràng buộc Phạm vi (Scope Constraints)

### Hàm nghiệp vụ chính (RULE CODE 05, 06):
- `taiDanhSachThanhToan()`
- `taiChiTietThanhToan()`
- `taiDanhSachCanDoiSoat()`
- `taiSuKienThanhToan()`

### Ràng buộc phạm vi & Quy tắc nghiệp vụ bắt buộc:
1. **Admin Payment Web là READ-ONLY (Vue Plan Sections 4, 5 & 31):**
   - Giao diện Admin chỉ có quyền đọc dữ liệu đối soát. Tuyệt đối **CẤM** tạo các nút: "Đánh dấu thành công", "Sửa số tiền", "Hoàn tiền (Refund)", "Kích hoạt Membership thủ công", hoặc "Xóa sự kiện thanh toán".
   - Payment thành công chỉ xác lập quyền sở hữu; Membership chỉ kích hoạt bởi lần dùng quyền lợi trả phí hợp lệ đầu tiên theo Backend (RULE GYM 01, 02).
2. **Bảo mật Dữ liệu Nhạy cảm (No Secrets in FE):**
   - Tuyệt đối không render raw payload chứa secret key, checksum hash, hoặc `signature` của payOS lên giao diện. Chỉ hiển thị dữ liệu an toàn qua Safe DTO.
3. **Q10 & Trạng thái Cần Đối soát & Chữ ký không hợp lệ:**
   - Webhook/payload không xác thực (sai chữ ký số hoặc không hợp lệ): Backend từ chối ngay lập tức (reject) và lưu dấu vết audit; tuyệt đối không được coi là tiền hợp lệ.
   - Khoản thanh toán bất thường đã xác thực (sai số tiền, đơn hủy/hết hạn, không khớp đơn): Hiển thị rõ nhãn cảnh báo `CAN_DOI_SOAT` trong hàng đợi đối soát, cô lập và không tự động kích hoạt tài nguyên.

---

## 4. Ngữ cảnh Nạp Tối thiểu (Minimal Working Context)

- **Bắt buộc đọc:**
  1. `.fitness-rules/PROJECT_CORE.md`
  2. `.fitness-rules/frontend/vue-core.md`
  3. `.fitness-rules/frontend/visual-ui.md`
  4. `.fitness-rules/domains/membership-payment.md` (Phần Thanh toán, Q01, Q02, Q10, RULE GYM 18, 19)
- **DO NOT READ:** Không đọc `workout.md`, `pt-chat.md`, `ai-proposal.md`.

---

## 5. Trạng thái Backend & STOP Conditions

- **Backend APIs:** Read basics ở trạng thái `READY`. Reconciliation ở trạng thái `BACKEND_FIX_IN_PROGRESS` (BE-FOLLOWUP-01A/01B).
- **STOP Conditions / entry gate:** Chỉ dispatch `FE4-ALL` sau khi `FE-1 PASS` và BE-FOLLOWUP-01A/01B có contract cùng test evidence `READY`. Nếu chưa đủ, không tạo writer cho reconciliation, không đánh phase complete và không dùng client join để bù event bị Backend loại.

---

## 6. Tiêu chí Nghiệm thu của Task `FE4-ALL`

- [ ] Entry gate xác nhận `FE-1 PASS` và BE-FOLLOWUP-01A/01B đã READY bằng contract và test evidence trước khi bắt đầu `FE4-ALL`.
- [ ] Hiển thị đầy đủ danh sách thanh toán, bộ lọc, phân trang hoạt động chính xác qua Safe DTO.
- [ ] Reconciliation list hiển thị đúng unlinked payment events và abnormal events.
- [ ] Tuyệt đối không có bất kỳ nút mutation (sửa/xóa/hoàn tiền/kích hoạt) nào trên giao diện.
- [ ] Toàn bộ test của FE-4 (visibility & security tests) PASS.
- [ ] Lint sạch sẽ (`npm run lint`), build thành công (`npm run build`).
- [ ] Docblocks đầy đủ; hàm dùng camelCase tiếng Việt (RULE CODE 05), event handlers dùng `xuLy...` (RULE CODE 06, cấm `handleXxx`).
