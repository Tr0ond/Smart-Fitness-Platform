# Phase Context: FE-7 — PT Realtime Chat

> **Task Scope:** `FE7-ALL` (Tổng hợp toàn bộ Phase 7 — Không tách subtask)  
> **Mục tiêu:** Xây dựng hệ thống Chat 1-1 giữa PT và Hội viên với kiến trúc REST-first, WebSocket resilient và tuân thủ tuyệt đối quy tắc phân quyền Q05.  
> **Canonical Plan Reference:** [docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md](../../docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md) (Section 31: FE-7, Section 38A: `FE7-ALL`, Section 19: Realtime Architecture & Section 26)

---

## 1. Danh sách Màn hình & Routes Triển khai (1 Màn hình)

- **Trò chuyện với Hội viên (`/pt/tro-chuyen` — route name: `ptTroChuyen`):**
  - Cột bên trái: Danh sách các hội thoại (`hoi_thoai`) với các học viên đang được phân công (`danh_sach_hoi_thoai.vue`).
  - Khung chính: Lịch sử tin nhắn (`tin_nhan`), chỉ số sequence, trạng thái gửi (đang gửi, đã gửi, lỗi) (`danh_sach_tin_nhan.vue`, `dong_tin_nhan.vue`).
  - Khung soạn thảo: Nhập nội dung tin nhắn, nút gửi tin, phím tắt `Enter` (`khung_nhap_tin_nhan.vue`).
  - Banner trạng thái kết nối: Thông báo khi mất kết nối WebSocket và đang thử kết nối lại (`thong_bao_mat_ket_noi.vue`).

---

## 2. Các File Mã Nguồn Dự kiến (Section 28, 26, 27)

> **Nguyên tắc kiểm tra mã nguồn:** Trước khi tạo bất kỳ file nào, kiểm tra mã nguồn thực tế trong `FE/src/`. Mã nguồn thực tế thắng; không tạo file trùng lặp.

- **Page & Components (Section 28 exact paths — 6 files):**
  - `src/pages/pt/tro_chuyen/tro_chuyen.index.vue`
  - `src/components/tro_chuyen/danh_sach_hoi_thoai.vue`
  - `src/components/tro_chuyen/danh_sach_tin_nhan.vue`
  - `src/components/tro_chuyen/dong_tin_nhan.vue`
  - `src/components/tro_chuyen/khung_nhap_tin_nhan.vue`
  - `src/components/tro_chuyen/thong_bao_mat_ket_noi.vue`
- **Domain Services (Section 26 exact paths — 2 files):**
  - `src/services/tro_chuyen.api.js` (Chat REST endpoints: conversations, messages, current conversation, send message: READY)
  - `src/services/phat_song_tro_chuyen.js` (Echo init, private channel auth qua `/api/broadcasting/auth`, subscribe, leave: DEPLOYMENT_DEPENDENCY)
- **Composable (Section 27 exact path — 1 file):**
  - `src/composables/su_dung_kenh_tro_chuyen.js` (subscribe, leave, reconnect, catch-up, sequence gap handling)
- **Store (Section 25 — 1 file):**
  - `src/stores/tro_chuyen.store.js` (conversations, messages maps/order, pending sends, channel/connection state; dọn sạch nhạy cảm khi logout hoặc mất phân công).
- **Thư viện Cài đặt (Section 6A):**
  - `laravel-echo`, `pusher-js`. Không cài thêm package nào khác.

---

## 3. Hàm Nghiệp vụ Chính & Ràng buộc Kỹ thuật

### Hàm nghiệp vụ chính:
- `taiDanhSachHoiThoai()`, `taiTinNhanTheoHoiThoai()`, `layHoiThoaiHienTai()`
- `guiTinNhan()`, `thuLaiGuiTinNhan()`
- `dangKyKenhRieng()`, `roiKenhKhiMatPhamVi()`
- `hopNhatTinNhanTheoSequence()`
- `dongBoSauKetNoiLai()`

### Ràng buộc kỹ thuật & Quy tắc nghiệp vụ bắt buộc:
1. **REST-First Resilience:**
   - Khi WebSocket hoặc Reverb server bị sập/chưa triển khai: Ứng dụng **vẫn phải tải được lịch sử tin nhắn và gửi tin nhắn bình thường thông qua REST API**. WebSocket chỉ đóng vai trò tăng tốc độ cập nhật tin nhắn đến (push notification). Trạng thái `realtime_delivery` trong send response là trạng thái delivery, không phải business success của message.
2. **Kênh Private & Tên Sự kiện Chuẩn Xác (Section 19):**
   - Kênh logic: `pt.conversation.{conversationId}` (tiền tố `private-` trên đường truyền do thư viện Echo tự động xử lý khi gọi `Echo.private(...)`, **tuyệt đối không dùng tên tự chế `private-chat.{hoi_thoai_id}`**).
   - Sự kiện lắng nghe: `.pt.chat.message.sent`.
   - Event payload chuẩn: `conversation_id`, `message_id`, `sequence`, `sender_id`, `sender_type`, `content`, `sent_at`.
3. **Chống trùng lặp & Xử lý Sequence Gap (Catch-up):**
   - Mỗi tin nhắn gửi đi sinh một mã `client_message_id` duy nhất và ổn định. Pending row được đối soát bằng `message_id` và `sequence` server trả về.
   - Dedupe bằng `message_id`, sắp xếp bằng `sequence`.
   - **Xử lý hổng sequence (Gap) hoặc sau reconnect:** Gọi GET page mới nhất qua REST; nếu phát hiện khoảng cách sequence lớn, dùng `before_sequence` lùi dần về sequence đã biết rồi hợp nhất (merge). **Tuyệt đối không bịa ra tham số `after_sequence`** (Backend không có endpoint này).
4. **Q05 & Phạm vi Phân công PT (Resource Scope PT):**
   - PT chỉ được quyền truy cập hội thoại với học viên có phân công hiệu lực trong khoảng `[ngay_bat_dau, ngay_ket_thuc)` (tối đa 0..1 PT hiệu lực, không chồng lấn).
   - **Khi phân công kết thúc (Q05 Scope-loss) hoặc gặp lỗi 403/404:** Lập tức `leave()` khỏi kênh WebSocket, hủy cơ chế auto-reconnect, dừng retry gửi tin, xóa dữ liệu nhạy cảm của học viên đó khỏi store, và điều hướng về `/pt/hoi-vien`.
   - **PT cũ mất hoàn toàn resource scope, không được tiếp tục đọc, gửi hay nhận tin qua phạm vi cũ.**
   - **Học viên (Member) vẫn giữ toàn bộ lịch sử trò chuyện cũ với PT trước đó.**
   - **Kích hoạt Membership qua Chat (RULE GYM 02, 09):** Nếu kỳ hạn của Member đang là `CHO_KICH_HOAT`, khi Member gửi tin nhắn chat đầu tiên hợp lệ (gói có quyền chat PT), Backend sẽ kích hoạt kỳ hạn (`ky_han_hoi_vien.ngay_bat_dau = su_dung_quyen_loi.chap_nhan_luc`). Ngược lại, việc PT chủ động gửi tin nhắn hoặc thực hiện thao tác KHÔNG kích hoạt kỳ hạn của Member.
5. **Bảo vệ Secret & Staging Smoke Gate:**
   - Bundle build phía client chỉ chứa public config (`VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME`); tuyệt đối không đưa Reverb secret key vào Frontend.
   - Phải có authorized staging WSS smoke test PASS; nếu môi trường Reverb chưa sẵn sàng, toàn task giữ `WAITING_DEPLOYMENT`, không đánh PASS bằng unit test giả tạo.
 
### Pre-dispatch gate của `FE7-ALL`

- Chỉ dispatch một task `FE7-ALL` sau khi `FE5-ALL PASS`, các dependency tương thích đã được phê duyệt, và Reverb/BE-FOLLOWUP-04 có deployment-readiness evidence.
- Nếu thiếu một điều kiện, giữ `WAITING_DEPLOYMENT` hoặc `WAITING_DEPENDENCY` và không tiêu thụ writer/reviewer; không triển khai một subset để thay thế entry gate.

---

## 4. Ngữ cảnh Nạp Tối thiểu (Minimal Working Context)

- **Bắt buộc đọc:**
  1. `.fitness-rules/PROJECT_CORE.md`
  2. `.fitness-rules/frontend/vue-core.md` (Phần Realtime WebSocket)
  3. `.fitness-rules/frontend/visual-ui.md`
  4. `.fitness-rules/domains/pt-chat.md` (Phần Q05 Realtime Chat)
  5. `.fitness-rules/domains/auth-resource-scope.md` (RULE CODE 17)
- **DO NOT READ:** Không đọc `workout.md`, `membership-payment.md`, `ai-proposal.md`.

---

## 5. Tiêu chí Nghiệm thu của Task `FE7-ALL`

- [ ] Cài đặt chính xác `laravel-echo` và `pusher-js` theo phê duyệt của Section 6A.
- [ ] Giao diện chat tải lịch sử mượt mà qua REST API khi WebSocket chưa bật.
- [ ] Gửi tin nhắn hiển thị hàng pending tức thì với `client_message_id`, đối soát bằng `message_id` + `sequence`.
- [ ] Lắng nghe kênh `pt.conversation.{conversationId}`, event `.pt.chat.message.sent`, merge tin nhắn không bị trùng lặp.
- [ ] Bù lấp sequence gap bằng `before_sequence` qua REST, không dùng `after_sequence` tưởng tượng.
- [ ] Khi mất quyền phân công (Q05): Tự động rời kênh, xóa store, dừng reconnect; PT cũ không tiếp tục truy cập; Member giữ lịch sử.
- [ ] Không có secret key nào bị lộ trong bundle build (`npm run build`).
- [ ] Nếu staging Reverb chưa sẵn sàng: Task giữ trạng thái `WAITING_DEPLOYMENT`, không đánh PASS bằng unit test giả tạo.
