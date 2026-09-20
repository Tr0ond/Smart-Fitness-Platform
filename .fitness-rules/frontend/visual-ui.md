# Frontend: Visual UI System — MVP Default

> **Module Path:** `.fitness-rules/frontend/visual-ui.md`  
> **Canonical Reference:** [docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md](../../docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md) (Section 7A: Visual UI System — MVP Default)

---

## 1. Nguyên tắc Thiết kế Cốt lõi (Section 7A.1)

- **Giao diện Staff chuyên nghiệp:** Thiết kế theo hướng sạch sẽ, sáng rõ, ưu tiên hiển thị dữ liệu bảng biểu và tốc độ thao tác của nhân viên hơn trang trí.
- **Không thêm UI Framework:** Không cài Tailwind CSS, Shadcn-vue hay component library cồng kềnh trong MVP.
- **KHÔNG Dark Mode trong MVP:** Mặc định sử dụng giao diện nền sáng (Light Staff UI) chuẩn mực.
- **Không Animation phức tạp:** Chỉ sử dụng transition ngắn cho drawer, dialog và trạng thái tải (loading) khi thật sự cần thiết.
- **Không dùng màu làm tín hiệu duy nhất:** Mọi trạng thái nghiệp vụ phải có nhãn văn bản (text label) hoặc icon đi kèm, không dựa đơn thuần vào màu sắc.

---

## 2. Bảng Biến Màu Tập trung (CSS Variables — Section 7A.3)

Mọi màu sắc phải được định nghĩa qua CSS variables tập trung, không hard-code mã màu hex rải rác trong component:

```css
:root {
  --mau-nen-trang: #ffffff;
  --mau-nen-phu: #f8fafc;
  --mau-vien: #e2e8f0;
  --mau-chu-chinh: #0f172a;
  --mau-chu-phu: #64748b;
  --mau-chinh: #2563eb;
  --mau-chinh-hover: #1d4ed8;
  --mau-thanh-cong: #15803d;
  --mau-canh-bao: #b45309;
  --mau-nguy-hiem: #b91c1c;
  --mau-thong-tin: #0369a1;
}
```

---

## 3. Kiểu chữ & Khoảng cách (Typography & Spacing — Sections 7A.2, 7A.4)

### Typography Stack
- Font hệ thống mặc định (không tải external Google Fonts):
  ```css
  font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  ```
- **Kích thước chuẩn:**
  - Body: 14–16px, line-height ~1.5.
  - Heading trang lớn: 24–28px, semibold/bold.
  - Heading khu vực / card: 16–18px, semibold.
  - Metadata / helper text: 12–14px.

### Thang đo Spacing & Bo góc (Spacing / Radius / Shadow)
- **Spacing scale:** `4, 8, 12, 16, 24, 32px`.
- **Border radius:**
  - Input, Button nhỏ: `6px`.
  - Card, Dialog: `8–12px`.
- **Shadow:** Chỉ dùng hiệu ứng bóng mờ nhẹ cho Dialog / Menu nổi; các Card dữ liệu thông thường ưu tiên dùng đường viền (`border: 1px solid var(--mau-vien)`).

---

## 4. Bố cục & Điểm ngắt Responsive (Layout & Breakpoints — Sections 7A.5, 7A.6)

### Thông số Bố cục Desktop Staff:
- Chiều rộng Sidebar: `248–264px`.
- Chiều cao Topbar: `56–64px`.
- Độ rộng tối đa vùng nội dung (`max-width`): `1440px`.
- Padding trang trên Desktop: `24px` (Mobile/Tablet: `16px`).
- Form nghiệp vụ chính: `max-width: 720px` (khi không cần full-width).
- Khung thẻ đăng nhập: `400–440px`.
- Bảng dữ liệu quản trị: Được phép co giãn hết toàn bộ vùng content khả dụng.

### Breakpoints Responsive:
- `< 640px` (Mobile / Màn hẹp): Sidebar chuyển thành Drawer, Form co về 1 cột, Bảng dữ liệu hỗ trợ cuộn ngang (`overflow-x: auto`).
- `640–1023px`: Tablet / Small Desktop.
- `>= 1024px`: Desktop Staff chuẩn.
- `>= 1280px`: Wide Desktop.

---

## 5. Quy chuẩn Linh kiện & Biểu tượng (Components & Icons — Sections 7A.7–7A.9)

1. **Form Control:**
   - Chiều cao mục tiêu: `40–44px`.
   - Label luôn hiển thị rõ ràng, không dùng placeholder thay cho label.
   - Thao tác mutation đang xử lý phải có trạng thái text và thuộc tính `aria-busy="true"`.
2. **Bảng dữ liệu (Tables):**
   - Chiều cao mỗi dòng (Row height) tối thiểu `44px`.
   - Cột hành động gọn gàng; thao tác nguy hiểm (xóa, hủy, hạ quyền) bắt buộc có hộp thoại xác nhận (`hop_thoai_xac_nhan.vue`).
   - Luôn sử dụng component chuẩn cho trạng thái tải (`trang_thai_tai_du_lieu.vue`), trống (`trang_thai_trong.vue`), và lỗi (`trang_thai_loi.vue`).
3. **Biểu tượng (Icons):**
   - **Text-first là mặc định cho MVP.** Không bắt buộc cài thư viện icon trong FE-0; không tự ý thêm icon package ngoài danh sách phê duyệt.

---

## 6. Trạng thái truy vấn, bàn phím và responsive

- Mọi query có loading, empty, error/retry và data state rõ ràng; không blank page khi fetch.
- Input có label hiển thị, help/error liên kết `aria-describedby`; lỗi tổng dùng `aria-live`. Mutation pending có text, `disabled` và `aria-busy`.
- Sidebar, table, dialog, form và QR manual input dùng được bằng keyboard; focus visible, focus trap/return và Escape/Enter hợp lý.
- Mobile/tablet giữ drawer, form một cột và table scroll/card fallback; không tự đặt một design system hoặc dependency mới.

## 7. Tiêu chí Đạt chuẩn Giao diện (Visual Acceptance — Section 7A.10)

Một màn hình hoặc Phase **bị đánh trượt nghiệm thu UI** nếu vi phạm một trong các điều sau:
- Màn hình cùng loại có spacing, button, table lệch chuẩn không có lý do.
- Trạng thái nghiệp vụ chỉ được biểu diễn bằng màu sắc mà không có text.
- Form nhập liệu thiếu label hoặc thiếu hiển thị thông báo lỗi 422 rõ ràng.
- Chạy được trên Desktop nhưng bị tràn ngang hoặc vỡ layout trên Mobile/Tablet.
- Trang thiếu 3 trạng thái cơ bản: Loading, Empty state, và Error retry.
- Lặp lại hard-code màu, spacing, radius hoặc kích thước mang nghĩa token/design/business thay vì dùng CSS variables chuẩn. Một measurement one-off cần thiết cho layout riêng không tự động là vi phạm, miễn không bịa token hay sao chép giá trị có nghĩa nghiệp vụ.
