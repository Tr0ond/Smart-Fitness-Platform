# Phase Context: FE-8 — Receptionist Portal

> **Task Scope:** `FE8-ALL` (Tổng hợp toàn bộ Phase 8 — Không tách subtask)  
> **Mục tiêu:** Xây dựng cổng thao tác tối thiểu cho Lễ tân (Receptionist), bảo toàn thẩm quyền kiểm tra tính hợp lệ Membership và check-in QR tại Backend.  
> **Canonical Plan Reference:** [docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md](../../docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md) (Section 31: FE-8, Section 38A: `FE8-ALL` & Section 18: Receptionist Screens)

---

## 1. Danh sách Màn hình & Routes Triển khai (3 Màn hình)

1. **Tra cứu Hội viên (`/le-tan/tra-cuu-hoi-vien` — route name: `leTanTraCuuHoiVien`):**
   - Tìm kiếm hội viên theo số điện thoại hoặc mã hội viên thuộc chi nhánh phụ trách; contract và Backend tests của `BLOCKER-06` phải READY trước entry gate.
   - Không có contract thì không dispatch writer và không dùng Account Admin route làm workaround; không biến blocker placeholder thành writer acceptance.
2. **Trạng thái Gói & Quyền Vào phòng (`/le-tan/hoi-vien/:id/trang-thai-hoi-vien` — route name: `leTanTrangThaiHoiVien`):**
   - Xem tình trạng Membership của hội viên do Backend trả về (`DANG_HOAT_DONG`, `HET_HAN`, `CHO_KICH_HOAT`).
   - Contract và Backend tests của `BLOCKER-07` phải READY trước entry gate. Tuyệt đối cấm Client tự tính toán trạng thái dựa trên ngày tháng hoặc lịch sử thanh toán; không gọi `/api/membership` của Member token.
3. **Quét Mã Vào Phòng Tập (`/le-tan/quet-ma-vao-phong` — route name: `leTanQuetMaVaoPhong`):**
   - Màn hình tiếp đón chính: Cho phép nhập mã QR thủ công (bằng máy quét mã vạch USB / bàn phím) hoặc quét qua Camera thiết bị nếu trình duyệt hỗ trợ. `READY`.
   - Gửi `qr_token` đến `POST /api/gym/check-in`. Hiển thị kết quả an toàn do Backend xác nhận: hợp lệ chào mừng, mã hết hạn, gói chờ kích hoạt, gói đã hết hạn, hoặc mã đã sử dụng.

---

## 2. Các File Mã Nguồn Dự kiến (Section 28 & Section 26)

> **Nguyên tắc kiểm tra mã nguồn:** Trước khi tạo bất kỳ file nào, kiểm tra mã nguồn thực tế trong `FE/src/`. Mã nguồn thực tế thắng; không tạo file trùng lặp.

- **Layout & Pages (Section 28 exact paths — 4 files):**
  - `src/layouts/bo_cuc_le_tan.vue`
  - `src/pages/le_tan/tra_cuu_hoi_vien/tra_cuu_hoi_vien.index.vue`
  - `src/pages/le_tan/trang_thai_hoi_vien/trang_thai_hoi_vien.chi_tiet.vue`
  - `src/pages/le_tan/quet_ma_vao_phong/quet_ma_vao_phong.index.vue`
- **Domain Service (Section 26 exact path — 1 file):**
  - `src/services/le_tan.api.js` (Lookup/membership: BLOCKER-06/07; QR check-in: READY)
- **Components (Section 24):**
  - `src/components/le_tan/bo_nhap_ma_qr.vue` (Adapter camera + manual input), `src/components/le_tan/the_ket_qua_check_in.vue`, `src/components/chung/chan_tinh_nang_bi_chan.vue`.

---

## 3. Hàm Nghiệp vụ Chính & Ràng buộc Bắt buộc

### Hàm nghiệp vụ chính:
- `traCuuHoiVien()` (chỉ sau khi có API)
- `taiTrangThaiHoiVien()` (chỉ sau khi có API)
- `docMaQr()`, `xacNhanVaoPhong()`, `dienGiaiKetQuaCheckIn()`

### Quy tắc nghiệp vụ bắt buộc:
1. **Backend Quyết định Quyền Vào Phòng (RULE CODE 13):**
   - Tính hợp lệ của Membership (quyền Gym `cho_phep_vao_phong_tap = 1`, còn hạn) do Backend xác thực độc lập. Giao diện chỉ hiển thị đúng kết luận do Backend trả về, không tự tính số ngày còn lại hoặc suy quyền từ lịch sử thanh toán. RULE GYM 03 cam kết tối thiểu 5 trạng thái Membership (`CHO_THANH_TOAN`, `CHO_KICH_HOAT`, `DANG_HOAT_DONG`, `HET_HAN`, `HUY`); trạng thái `CHO_DEN_LUOT` được định nghĩa chính thức tại `TU_DIEN_DU_LIEU.md` Bảng 19 và migration `2026_08_29_000018_m018_tao_ky_han_hoi_vien.php` cho kỳ nối tiếp (tuyệt đối không khẳng định cố định đúng 6 trạng thái và không bịa trạng thái `TAM_DUNG`).
2. **Q09 & RULE GYM 25 – Thẩm quyền Xác thực Mã QR & TTL:**
   - Mã QR có TTL mặc định 90 giây theo chính sách, **nhưng cấu hình server / trường `het_han_luc` do Backend xác thực là cơ quan thẩm quyền duy nhất**.
   - **Frontend TUYỆT ĐỐI KHÔNG tự hardcode 90 giây để quyết định mã hợp lệ hay hết hạn trên client.** Frontend gửi `qr_token` đến Backend (`POST /api/gym/check-in`) và render kết quả do Backend trả về.
   - Mỗi mã QR chỉ được sử dụng đúng một lần duy nhất (Backend ghi nhận timestamp `da_su_dung_luc` theo `TU_DIEN_DU_LIEU.md` Bảng 21 & migration `m020`, và ràng buộc `ma_vao_phong_tap_id UNIQUE` trong bảng `lich_su_vao_phong_tap` theo `TU_DIEN_DU_LIEU.md` Bảng 22 & migration `m021`; không có enum `DA_SU_DUNG`). Lần quét thứ 2 bị Backend từ chối với lỗi mã đã sử dụng; UI xử lý safe outcome rõ ràng.
   - Chống quét đúp / gửi lặp: Bật cờ pending (`aria-busy="true"`), khóa nút khi đang gửi request; không gửi header `Idempotency-Key` hay `X-Idempotency-Key` cho QR check-in (tính đơn nhất được bảo vệ bởi chính bản thân `qr_token` và kiểm soát xung đột tại Backend).
3. **Camera Fallback:**
   - Tính năng quét bằng Camera chỉ là adapter phụ trợ khi trình duyệt cấp quyền (`navigator.mediaDevices`). Nếu không có camera hoặc người dùng từ chối cấp quyền, ô nhập mã thủ công vẫn luôn hoạt động bình thường, không làm gián đoạn nghiệp vụ tại quầy.
4. **Cô lập Quyền Lễ tân & Blocker Handling (BLOCKER-06, 07):**
   - Tuyệt đối không gọi API của Admin (xem doanh thu, sửa gói) hay API Member-self làm giải pháp tạm thời.
   - **STOP Conditions (Section 31: FE-8):** Chỉ dispatch `FE8-ALL` sau khi cả `BLOCKER-06` và `BLOCKER-07` có contract chính thức cùng Backend test evidence READY. Trước gate, thiếu API là lý do không dispatch; sau gate một writer triển khai đủ cả ba màn hình và shell.

---

## 4. Ngữ cảnh Nạp Tối thiểu (Minimal Working Context)

- **Bắt buộc đọc:**
  1. `.fitness-rules/PROJECT_CORE.md`
  2. `.fitness-rules/frontend/vue-core.md`
  3. `.fitness-rules/frontend/visual-ui.md`
  4. `.fitness-rules/domains/membership-payment.md` (Phần RULE GYM 25, Q09 Check-in QR, Trạng thái CHO_KICH_HOAT)
  5. `.fitness-rules/domains/auth-resource-scope.md` (Phần Quyền Lễ tân, Least Privilege)
- **DO NOT READ:** Không đọc `workout.md`, `pt-chat.md`, `ai-proposal.md`.

---

## 5. Tiêu chí Nghiệm thu của Task `FE8-ALL`

- [ ] Bố cục Lễ tân (`bo_cuc_le_tan.vue`) gọn gàng, đúng 3 chức năng chính.
- [ ] Màn hình quét mã xử lý mượt mà cả 2 trường hợp: Nhập mã thủ công và quét camera.
- [ ] Chống quét đúp / replay: Khóa nút khi đang gửi request (`aria-busy="true"`).
- [ ] Render kết quả check-in an toàn theo thẩm quyền xác thực của Backend; không hardcode logic 90s trên client.
- [ ] Cô lập hoàn toàn quyền Lễ tân, không gọi API Admin hay Member-self làm workaround.
- [ ] Hai màn hình Tra cứu và Trạng thái gọi đúng contract Lễ tân sau entry gate; không có placeholder runtime hoặc workaround Admin/Member-self.
- [ ] `FE8-ALL` được nghiệm thu như một task tổng hợp duy nhất sau khi cả hai contract và Backend tests đã READY.
- [ ] Toàn bộ test của FE-8, lint (`npm run lint`), build sạch sẽ (`npm run build`).
