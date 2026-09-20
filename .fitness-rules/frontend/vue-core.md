# Frontend: Vue 3 Core Architecture & Standards

> **Module Path:** `.fitness-rules/frontend/vue-core.md`  
> **Canonical Reference:** [docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md](../../docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md) (Sections 1, 3, 6, 6A, 7, 7A, 8, 9, 10, 11, 12, 13, 14, 15, 19, 20)

---

## 1. Công nghệ & Giới hạn Thư viện (Tech Stack & Approved Dependencies)

- **Framework:** Vue 3 SPA (Composition API với `<script setup>`).
- **Ngôn ngữ:** JavaScript hiện hữu (không chuyển sang TypeScript làm gián đoạn foundation).
- **Build Tool:** Vite (alias `@` trỏ vào `src`).
- **Styling:** Scoped CSS sử dụng CSS variables tập trung theo [visual-ui.md](visual-ui.md). **Tuyệt đối KHÔNG thêm UI Framework (Tailwind CSS, Shadcn-vue, Vuetify...) trong MVP.**
- **Icon:** Text-first là mặc định cho MVP. Không bắt buộc icon library trong FE-0; không tự ý cài đặt gói icon ngoài scope.
- **Danh mục Thư viện Phê duyệt Khóa trước (Section 6A - Approved Dependencies):**
  - **FE-0 (Test & Lint):** `vitest`, `@vue/test-utils`, `jsdom`, `eslint`, `eslint-plugin-vue`.
  - **FE-7 (Realtime Chat):** `laravel-echo`, `pusher-js`.
  - **Cấm:** Không cài đặt thêm bất kỳ UI framework, state library nào khác ngoài Pinia. Không chạy `npm audit fix --force`.

---

## 2. Quy chuẩn Đặt tên Frontend (RULE CODE 05–08 & Section 6)

1. **URL Page (Section 6.1):**
   - Tiếng Việt không dấu, chữ thường, `kebab-case`: `/admin/bang-dieu-khien`, `/pt/hoi-vien/:id/tien-do`.
2. **Route Name (Section 6.1):**
   - Tiếng Việt không dấu, `camelCase`, có tiền tố vai trò (actor prefix): `adminBangDieuKhien`, `ptTienDoHoiVien`, `leTanQuetMaVaoPhong`.
3. **Tên File (Section 6.2):**
   - Tiếng Việt không dấu, `snake_case`, dùng hậu tố vai trò: `dang_nhap.index.vue`, `tai_khoan.chi_tiet.vue`, `giao_an_mau.tao_phien_ban.vue`.
   - Store: `xac_thuc.store.js`; service: `tai_khoan.api.js`; composable: `su_dung_thao_tac_chong_lap.js`.
4. **Hàm nghiệp vụ (RULE CODE 05):**
   - Viết bằng tiếng Việt không dấu `camelCase`: `taiDanhSachBaiTap()`, `hienThiTrangThaiGoiTap()`, `guiYeuCauHoanTatBuoiTap()`. Frontend không tự kích hoạt gói hoặc tính entitlement.
5. **Event Handler (RULE CODE 06):**
   - Đặt theo định dạng `xuLy<HanhDong>` bằng tiếng Việt không dấu: `xuLyLuuBaiTap()`, `xuLyMoModal()`, `xuLyXoaDong()`. Cấm dùng `handleXxx`, `handleClick()`, `handleSubmit()`.
6. **Biến trạng thái nghiệp vụ (RULE CODE 07):**
   - Biến `ref`/`computed` lưu trạng thái nghiệp vụ: `danhSachBaiTap`, `dangTaiDuLieu`, `thongBaoLoi`, `hoSoHocVien`.
7. **Giữ nguyên API Framework (RULE CODE 08):**
   - Không tự dịch các hàm cốt lõi của Vue/Pinia/Vite: `ref`, `computed`, `watch`, `onMounted`, `v-model`.

---

## 3. Kiến trúc Bố cục & Pinia Stores (Layouts & Stores)

### 5 Bố cục Giao diện (Section 13)
1. `bo_cuc_cong_khai.vue`: Đăng nhập, quên/đặt lại mật khẩu.
2. `bo_cuc_admin.vue`: Sidebar Admin, topbar, breadcrumb, drawer responsive.
3. `bo_cuc_pt.vue`: Quản lý học viên, context học viên đang chọn, badge chat.
4. `bo_cuc_le_tan.vue`: Ba thao tác tối thiểu (tra cứu, Membership, quét QR).
5. `bo_cuc_loi.vue`: Trang 403 cấm quyền và 404 không tìm thấy.
- Shell dùng chung: `khung_ung_dung.vue` hỗ trợ menu allow-list theo actor.

### 7 Pinia Store Duy nhất (Section 14)
- Tuyệt đối không tạo giant store hoặc store cho từng page. Chỉ có 7 store có phạm vi rõ:
  1. `xac_thuc.store.js`: token, user, roles, restore error/retry, login, me, logout, switch actor. Persist `token` + `actor` vào `sessionStorage`.
  2. `tai_khoan.store.js`: Admin account list filter, pagination, selected account.
  3. `danh_muc.store.js`: Catalog options (equipment, muscle) và cache ngắn hạn. Invalidate sau mutation; không dùng cache làm authority.
  4. `thanh_toan.store.js`: Read-only filters, pagination, selected payment/event. Không chứa raw payload/signature.
  5. `hoi_vien_pt.store.js`: Assigned Members, selected Member ID, progress cache. Clear ngay khi mất phân công hoặc logout.
  6. `de_xuat.store.js`: Proposal list, draft memory, stable action key.
  7. `tro_chuyen.store.js`: Conversations, messages by id/sequence, pending sends, connection state. Không persist nội dung tin nhắn.

---

## 4. API Client, Idempotency & Xử lý Lỗi (Sections 9, 10, 11)

### Axios Client Thống nhất
- Base URL: `import.meta.env.VITE_API_BASE_URL` (không hard-code fallback trong production). Timeout: 15,000 ms.
- Auth Header: `Authorization: Bearer <token>` từ một token accessor/session helper (ví dụ `phien_dang_nhap.js`), không import store trực tiếp vào client để tránh circular coupling.
- **Idempotency Header (Section 11):** Gửi header `Idempotency-Key` (UUIDv4) ổn định theo vòng đời thao tác cho các mutation nhạy cảm:
  - Admin tạo / onboard PT (`taoHuanLuyenVien`, `onboardTaiKhoanThanhPt`).
  - Hoàn tất buổi tập PT (`hoanTatBuoiHuanLuyen`).
  - Tạo đề xuất kế hoạch PT (`taoDeXuatKeHoach`).
  *(Lưu ý theo Section 11: Check-in QR lễ tân dùng cơ chế `qr_token` single-use, Backend trả controlled conflict, không auto-retry mù; không yêu cầu `Idempotency-Key`).*

- **401 Unauthorized:** Chỉ dọn token/session nếu response thuộc đúng token hiện tại. 401 của request dùng token cũ đến muộn không được xóa phiên mới; request/client phải gắn token snapshot hoặc request identity để kiểm tra. Khi 401 đúng phiên, dọn authority/domain/realtime và điều hướng về login actor hiện tại.

- **403 Forbidden:** Hiển thị trang cấm quyền hoặc banner cấm quyền tài nguyên; không tự ý đăng xuất người dùng.
- **409 Conflict:** Hiển thị xung đột dữ liệu (Stale version, Assignment conflict). Giữ nguyên draft người dùng đang nhập, refetch dữ liệu mới để đối chiếu, cấm tự ý ghi đè.
- **422 Unprocessable Entity:** Trích xuất mảng `errors` từ response Laravel và map inline vào các trường input của form.
- **Network/timeout/5xx của `/me`:** Giữ token trong `sessionStorage` để retry, xóa authority/user/role khỏi memory, khóa protected content và hiển thị retry; cached authority không được mở route. Các lỗi mutation giữ stable key khi semantics cho phép.
- **500 / Network Error khác:** Hiển thị toast lỗi hệ thống thân thiện, giữ nút Retry khi hợp lý.

---

## 5. Realtime WebSocket Adapter (FE-7 — Section 19)

- Chỉ tích hợp ở Phase FE-7 sau khi cài `laravel-echo` và `pusher-js`.
- Không đưa Reverb secret vào client bundle.
- Public config duy nhất: `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME`; không bundle secret.
- Kênh logic là `pt.conversation.{conversationId}`, Echo private xử lý prefix wire; event là `.pt.chat.message.sent` với payload conversation_id, message_id, sequence, sender_id, sender_type, content, sent_at.
- Gửi là REST-first với stable `client_message_id`; pending row reconcile bằng server `message_id` và `sequence`, dedupe theo message_id và order theo sequence. Gap/reconnect tải page mới nhất và lùi bằng `before_sequence`; không invent `after_sequence`.
- Luôn đảm bảo **REST-first fallback**: Khi WebSocket hoặc server Reverb gặp sự cố mạng, ứng dụng vẫn tải lịch sử và gửi tin nhắn qua REST API. Khi Q05 scope loss/403/404, leave channel, clear conversation/member-sensitive state, stop retry/reconnect và điều hướng về assigned list.
