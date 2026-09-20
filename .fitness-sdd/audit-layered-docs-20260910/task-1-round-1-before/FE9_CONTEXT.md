# Phase Context: FE-9 — Final Acceptance & Readiness Gate

> **Task Scope:** `FE9-ALL` (Tổng hợp toàn bộ Phase 9 — Cổng nghiệm thu cuối cùng)  
> **Mục tiêu:** Chứng minh toàn bộ ứng dụng Vue Web vận hành nhất quán xuyên suốt 3 vai trò (Admin, PT, Receptionist), hoàn thành toàn bộ kiểm thử bảo vệ, và lập báo cáo nghiệm thu chính thức.  
> **Canonical Plan Reference:** [docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md](../../docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md) (Section 31: FE-9, Section 38A: `FE9-ALL` & Section 40: Final Web Readiness Gate)

---

## 1. Phạm vi Đánh giá Toàn diện (Audit Scope)

- **Toàn bộ 50 Router Records & 48 Màn hình Nghiệp vụ:**
  - 27 Màn hình Admin (Dashboard, Account, PT Assignment, Packages, Equipment, Muscle Groups, Exercises, Workout Templates, Payments, Reconciliation).
  - 12 Màn hình PT (Profile, Assigned Members, Detail, Progress, Plan, History, Notes, Direct Sessions, Proposals, Chat 1-1).
  - 4 Màn hình Receptionist (Lookup, Membership status, QR check-in).
  - 5 Màn hình Dùng chung (Chọn vai trò, Quên mật khẩu, Đặt lại mật khẩu, 403 Forbidden, 404 Not Found); actor login screens remain in their role totals.
- **Ranh giới Bắt buộc:** Không tạo thêm màn hình hay chức năng mới ngoài danh mục; chỉ sửa lỗi phát sinh từ kết quả audit. Không tự sửa Backend/Mobile, không commit/push.

---

## 2. Danh mục 7 Nhóm Kiểm tra Nghiệm thu (Section 40 Checklist)

### 1. Cross-Role Isolation & Session Cleanup Audit
- Kiểm tra chuyển đổi vai trò: Khi Admin chuyển sang PT hoặc ngược lại, toàn bộ store nghiệp vụ (`tai_khoan`, `hoi_vien_pt`, `tro_chuyen`) phải được xóa trắng.
- Đảm bảo không có hiện tượng rò rỉ dữ liệu (state leakage) giữa các phiên đăng nhập khác nhau trên cùng trình duyệt.
- Thử nghiệm truy cập chéo URL: Đăng nhập tài khoản Lễ tân nhưng gõ URL `/admin/bang-dieu-khien` $\rightarrow$ Bắt buộc chuyển hướng về trang 403.
- Free/Premium không được xuất hiện như role; không tạo `ROLE_FREE`, `ROLE_PREMIUM` hay cờ `isPremium` authority.

### 2. Full Test Suite, 65-row matrix & Staging API Smoke
- Chạy toàn bộ bộ test tự động của Frontend:
  ```bash
  npm run test
  ```
- Đối chiếu đủ 65 capability rows trong capability matrix; mỗi row phải có trạng thái/evidence, không gộp hoặc bỏ qua blocker.
- Required-suite evidence phải ghi rõ pass/fail và mọi test skip phải có lý do được phê duyệt; không dùng mock giả tạo để qua mặt lỗi thật.
- Thực hiện API contract smoke test với Backend staging thực tế. Reverb private channel/WSS smoke test phải thực hiện trên staging; không đánh PASS realtime bằng unit test giả tạo.

### 3. Responsive & Accessibility (a11y) Review
- Áp dụng Visual UI System (Section 7A): Token, spacing, layout, responsive, form/table state và không dùng màu sắc làm tín hiệu duy nhất.
- Kiểm tra hiển thị trên 4 breakpoint: Mobile (<640px), Tablet (640–1023px), Desktop (1024px), Wide (1280px). Không bị vỡ khung, tràn thanh cuộn ngang ngoài ý muốn.
- Kiểm tra điều hướng bàn phím: Toàn bộ form và dialog có thể thao tác bằng phím `Tab`, `Enter`, `Esc`. Focus trap/return, aria-live cho thông báo.
- Đánh giá tính tiếp cận theo Plan V2 Section 21: Thẻ HTML có ngữ nghĩa, quản lý focus rõ ràng, điều hướng bàn phím đầy đủ, thuộc tính ARIA (`aria-busy`, `aria-live`), nhãn hiển thị trực quan và không dùng màu sắc làm tín hiệu trạng thái duy nhất (không tự đặt ngưỡng tính toán số học WCAG AA >4.5:1).

### 4. Naming & Docblock Quality Gate
- Quét toàn bộ mã nguồn:
  - Tất cả các hàm nghiệp vụ tuân thủ `camelCase` tiếng Việt không dấu (RULE CODE 05).
  - **RULE CODE 06 (Strict Event Handler Naming):** Tất cả các hàm xử lý sự kiện giao diện (event handlers) bắt buộc sử dụng tiền tố **`xuLy<HanhDong>`**. **TUYỆT ĐỐI KHÔNG chấp nhận `handle<HanhDong>`** (loại bỏ hoàn toàn biến thể `handle`).
  - Mọi hàm quan trọng có docblock giải thích mục đích, rule tham chiếu và tham số (RULE CODE 09–12).

### 5. Production Build & Secret Scan
- Chạy lệnh kiểm tra lint và build chính thức:
  ```bash
  npm run lint
  npm run build
  ```
- Bundle build sạch sẽ trong thư mục `dist/`, không có lỗi hay cảnh báo nghiêm trọng.
- Quét mã nguồn và bundle output: Tuyệt đối không chứa secret key (Reverb secret, Gemini key, payOS secret/checksum), token nhạy cảm, webhook signature, hay mật khẩu mẫu.

### 6. Quản lý Blocker & Tính Trung thực
- Toàn bộ 7 `BACKEND_API_BLOCKER` và 4 `BACKEND_FIX_IN_PROGRESS` phải được phản ánh trung thực:
  - Nếu tất cả 7 blockers, 4 fix rows và realtime deployment gate đã được giải quyết bằng evidence $\rightarrow$ Web được đánh dấu `COMPLETE`.
  - **Nếu vẫn còn blocker nhưng phần đã kiểm chứng vẫn bàn giao được $\rightarrow$ trạng thái tối đa là `PARTIALLY_READY`; nếu dependency bắt buộc chặn việc kiểm thử/triển khai cốt lõi $\rightarrow$ `BLOCKED`.** Tuyệt đối không tự ý đánh tráo trạng thái thành `COMPLETE` có điều kiện.
- Bảng readiness phải nêu riêng từng blocker trong 7 rows và từng fix trong 4 rows, kèm owner/dependency, contract/test evidence hoặc lý do còn mở.
- Realtime deployment gate là điều kiện độc lập; không đánh dấu `COMPLETE` chỉ vì REST và unit tests đã pass.

### 7. Lập Biên bản Hoàn thành (Completion Report)
- Soạn thảo báo cáo nghiệm thu theo mẫu chuẩn đã nhúng sẵn trong `TASK_PACKET_TEMPLATE.md` (hoặc đối chiếu `engineering/testing-definition-of-done.md`).
- Báo cáo chi tiết số lượng test pass, danh sách màn hình hoàn tất, và danh sách các điểm tồn đọng kỹ thuật kèm bằng chứng rõ ràng.
- Evidence artifact bắt buộc: 65-row capability matrix; 50 router/48-screen inventory; 7-blocker/4-fix ledger; cross-role/session-cleanup results; a11y/responsive review; naming/docblock scan; lint/test/build output; secret scan; staging API và Reverb WSS smoke evidence; final status và remaining risks.

---

## 3. Ngữ cảnh Nạp Tối thiểu (Minimal Working Context)

- **Bắt buộc đọc:**
  1. `.fitness-rules/PROJECT_CORE.md`
  2. `.fitness-rules/engineering/testing-definition-of-done.md` (Definition of Done & Báo cáo Phần XXI)
  3. `.fitness-rules/engineering/security-integrity.md`
  4. `.fitness-rules/frontend/visual-ui.md` (Tiêu chí nghiệm thu UI)
- **DO NOT READ:** Không đọc các file chi tiết domain nếu không cần đối chiếu rule cụ thể.

---

## 4. Tiêu chí Đóng Cổng Final Web Readiness Gate

- [ ] 100% các tiêu chí trong mục 40 của Plan V2 được kiểm tra và có bằng chứng (evidence).
- [ ] Không có vi phạm kiến trúc, bảo mật hoặc naming convention nào (RULE CODE 05, 06 `xuLy<HanhDong>`, 09-12).
- [ ] Production build (`npm run build`) và lint (`npm run lint`) vượt qua thành công; bundle không chứa secret.
- [ ] Trạng thái nghiệm thu trung thực: `COMPLETE` nếu 100% blockers/fixes/deployment gate đã giải quyết; `PARTIALLY_READY` nếu còn blocker/fix nhưng có thể tiếp tục phần đã chứng minh; `BLOCKED` nếu một dependency bắt buộc ngăn việc kiểm thử/triển khai cốt lõi.
- [ ] Báo cáo nghiệm thu hoàn chỉnh theo mẫu chuẩn với evidence đầy đủ.
- [ ] Toàn bộ hệ thống Web sẵn sàng cho việc bàn giao và tích hợp toàn diện.
