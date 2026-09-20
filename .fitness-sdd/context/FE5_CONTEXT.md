# Phase Context: FE-5 — PT Member Workspace

> **Task Scope:** `FE5-ALL` (Tổng hợp toàn bộ Phase 5 — Không tách subtask)  
> **Mục tiêu:** Xây dựng không gian làm việc của Huấn luyện viên (PT) tuân thủ nghiêm ngặt phạm vi phân công (Exact-assignment scope) và tính bất biến của lịch sử tập luyện.  
> **Canonical Plan Reference:** [docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md](../../docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md) (Section 31: FE-5, Section 38A: `FE5-ALL` & Section 17: PT Screens)

---

## 1. Danh sách Màn hình & Routes Triển khai (7 Màn hình)

1. **Hồ sơ Cá nhân PT (`/pt/ho-so` — route name: `ptHoSo`):**
   - Xem và cập nhật thông tin cá nhân của chính PT (self-only profile qua `GET/PATCH /api/profile/trainer`). Contract được xác nhận ở entry gate của `FE5-ALL`.
2. **Danh sách Học viên Được Phân công (`/pt/hoi-vien` — route name: `ptHoiVien`):**
   - Danh sách các Member đang có phân công hiệu lực (thuộc khoảng thời gian `[ngay_bat_dau, ngay_ket_thuc)` với `ngay_ket_thuc IS NULL` hoặc `now < ngay_ket_thuc`, tối đa 0..1 PT hiệu lực tại một thời điểm, không chồng lấn) với PT hiện tại qua `GET /api/pt/members`. Không có search toàn hệ thống. Contract được xác nhận ở entry gate của `FE5-ALL`.
3. **Chi tiết Học viên (`/pt/hoi-vien/:id` — route name: `ptChiTietHoiVien`):**
   - Thông tin hồ sơ an toàn và phân công của học viên. Chờ contract và Backend test evidence `BLOCKER-03` trước entry gate.
   - Cấm gọi API của Admin hay Member-self làm workaround; thiếu contract là lý do không dispatch, không phải writer acceptance.
4. **Tiến độ Tập luyện (`/pt/hoi-vien/:id/tien-do` — route name: `ptTienDoHoiVien`):**
   - Ba GET PT-scoped: tổng quan tiến độ (overview), chỉ số cơ thể (`chi_so_co_the`), tiến độ bài tập chính (`tien_do_bai_tap`). DTO read-only; không tự ý chẩn đoán y khoa. Contract được xác nhận ở entry gate của `FE5-ALL`.
5. **Kế hoạch Tập luyện Chính thức (`/pt/hoi-vien/:id/ke-hoach-tap` — route name: `ptKeHoachTapHoiVien`):**
   - Xem kế hoạch tập chính thức hiện tại và tương lai của học viên. Read-only; chờ contract và Backend test evidence `BLOCKER-04` trước entry gate.
   - Tuyệt đối không dùng Proposal thay cho Official Plan; không gọi `/api/workout/*` của Member token.
6. **Lịch sử Buổi tập (`/pt/hoi-vien/:id/lich-su-tap` — route name: `ptLichSuTapHoiVien`):**
   - Danh sách và chi tiết các buổi tập trong quá khứ dưới exact assignment; chờ contract và Backend test evidence `BLOCKER-05` trước entry gate.
   - **BẤT BIẾN (RULE GYM 13 & 15):** Completed session luôn read-only; tuyệt đối không có nút chỉnh sửa (edit button), xóa bài tập hay sửa kết quả hiệp tập của buổi tập đã hoàn thành.
7. **Ghi chú Huấn luyện (`/pt/hoi-vien/:id/ghi-chu` — route name: `ptGhiChuHoiVien`):**
   - Danh sách ghi chú chuyên môn qua GET/POST `/api/pt/members/{member}/notes`. Append-only; UI ghi rõ "100 ghi chú gần nhất" cho MVP. Ghi chú không gây side-effect làm sửa đổi kế hoạch hay kết quả phiên tập cũ (Căn cứ theo **Plan V2 Section 17 & Section 31: FE-5**). Contract được xác nhận ở entry gate của `FE5-ALL`.

---

## 2. Các File Mã Nguồn Dự kiến (Section 28 & Section 26)

> **Nguyên tắc kiểm tra mã nguồn:** Trước khi tạo bất kỳ file nào, kiểm tra mã nguồn thực tế trong `FE/src/`. Mã nguồn thực tế thắng; không tạo file trùng lặp.

- **Layout & Pages (Section 28 exact paths — 8 files):**
  - `src/layouts/bo_cuc_pt.vue`
  - `src/pages/pt/ho_so/ho_so.index.vue`
  - `src/pages/pt/hoi_vien/hoi_vien.index.vue`
  - `src/pages/pt/hoi_vien/hoi_vien.chi_tiet.vue`
  - `src/pages/pt/tien_do/tien_do.index.vue`
  - `src/pages/pt/ke_hoach_tap/ke_hoach_tap.index.vue`
  - `src/pages/pt/lich_su_tap/lich_su_tap.index.vue`
  - `src/pages/pt/ghi_chu/ghi_chu.index.vue`
- **Domain Services (Section 26 exact paths — 6 files):**
  - `src/services/huan_luyen_vien.api.js` (PT self profile GET/PATCH; contract verified at entry gate)
  - `src/services/hoi_vien_pt.api.js` (assigned list and member detail; BLOCKER-03 contract verified at entry gate)
  - `src/services/tien_do.api.js` (PT member progress routes; contract verified at entry gate)
  - `src/services/ke_hoach_tap_pt.api.js` (PT-scoped current plan; BLOCKER-04 contract verified at entry gate)
  - `src/services/lich_su_tap_pt.api.js` (PT-scoped sessions; BLOCKER-05 contract verified at entry gate)
  - `src/services/ghi_chu.api.js` (PT member notes GET/POST; contract verified at entry gate)
- **Store (Section 25 — 1 file):**
  - `src/stores/hoi_vien_pt.store.js` (assignments, selected member id, progress cache; dọn dẹp sạch khi mất phân công, 401, 403, 404 hoặc logout).
- **Components (Section 24):**
  - `src/components/pt/thanh_dieu_huong_hoi_vien.vue`, `src/components/pt/the_tien_do.vue`, `src/components/chung/chan_tinh_nang_bi_chan.vue`.

---

## 3. Hàm Nghiệp vụ Chính & Ràng buộc Bắt buộc

### Hàm nghiệp vụ chính:
- `taiHoSoHuanLuyenVien()`, `capNhatHoSoHuanLuyenVien()`
- `taiDanhSachHoiVienDuocPhanCong()`, `chonHoiVien()`, `xacMinhHoiVienConTrongPhamVi()`
- `taiTienDoHoiVien()`, `taiChiSoCoThe()`, `taiTienDoBaiTap()`
- `taiDanhSachGhiChu()`, `themGhiChuHuanLuyen()`
- Các hàm detail, plan, history (`taiChiTietHoiVien()`, `taiKeHoachTapHoiVien()`, `taiLichSuTapHoiVien()`) chỉ triển khai sau khi entry gate xác nhận contract chính thức và Backend tests; trước gate không dispatch writer và không dùng placeholder runtime.

### Quy tắc nghiệp vụ bắt buộc:
1. **RULE CODE 17 (Resource Scope PT):** PT chỉ có quyền truy cập học viên có phân công hiệu lực trong khoảng `[ngay_bat_dau, ngay_ket_thuc)`. Khi gặp lỗi `403 Forbidden` hoặc `404 Not Found` (do thay đổi phân công giữa chừng), hệ thống phải dọn sạch `selectedMember` trong store và điều hướng về `/pt/hoi-vien`.
2. **RULE GYM 13 & 15 (Bất biến Lịch sử):** RULE GYM 15 quy định nghiêm ngặt: **PT không được sửa Workout Session đã hoàn thành**. Tuyệt đối không hiển thị bất kỳ nút chỉnh sửa (edit button) nào trên màn hình lịch sử buổi tập đã hoàn thành.
3. **Notes No-Side-Effect (Plan V2 Section 17 & Section 31: FE-5):** Thêm ghi chú huấn luyện là hành động bổ sung nhận xét chuyên môn (append-only), không làm biến đổi kế hoạch tập hoặc kết quả phiên tập cũ.
4. **Xử lý Blocker Backend trung thực (BLOCKER-03, 04, 05):** Trước entry gate, dừng dispatch vì thiếu contract/test evidence; sau gate chỉ dùng endpoint PT-scoped chính thức, cấm gọi chéo route `/api/profile/member` hay `/api/workout/*` của Member token.
5. **STOP Conditions (Section 31: FE-5):** Chỉ dispatch `FE5-ALL` sau khi `FE-0 PASS`; capability/contract của cả ba BLOCKER-03/04/05 có thể ở trạng thái `READY`, nhưng Backend authorization/resource-scope regression tests cho từng blocker phải có evidence `PASS`. Khi đã dispatch, một writer triển khai đủ cả bảy màn hình; không chấp nhận `TinhNangChuaSanSang`, zero-request blocker page hoặc `PARTIALLY_READY` như tiêu chí hoàn tất.

---

## 4. Ngữ cảnh Nạp Tối thiểu (Minimal Working Context)

- **Bắt buộc đọc:**
  1. `.fitness-rules/PROJECT_CORE.md`
  2. `.fitness-rules/frontend/vue-core.md`
  3. `.fitness-rules/frontend/visual-ui.md`
  4. `.fitness-rules/domains/pt-chat.md` (Phần Phân công PT, Ghi chú huấn luyện)
  5. `.fitness-rules/domains/workout.md` (Phần Tính Bất biến RULE GYM 13, 15)
  6. `.fitness-rules/domains/auth-resource-scope.md` (RULE CODE 17)
- **DO NOT READ:** Không đọc `ai-proposal.md`, `membership-payment.md`.

### Entry gate của phase

- Controller phải cung cấp evidence cho `FE-0 PASS`, BLOCKER-03, BLOCKER-04 và BLOCKER-05: contract PT-scoped chính thức/capability có thể `READY`, nhưng Backend authentication/authorization và exact-current-assignment resource-scope regression tests của từng blocker phải có evidence `PASS`; evidence âm tính phải bao gồm từ chối hoặc che giấu PT cũ/foreign/unassigned tùy trường hợp, và phạm vi exact-assignment đã được xác nhận.
- Khi thiếu bất kỳ evidence nào, trạng thái là WAITING_DEPENDENCY và không tiêu thụ writer/reviewer. Không coi một subset màn hình READY là quyền dispatch.

---

## 5. Tiêu chí Nghiệm thu của Task `FE5-ALL`

- [ ] PT layout và guard kiểm tra vai trò PT hoạt động chuẩn xác.
- [ ] Hồ sơ cá nhân PT chỉ cho phép xem/sửa thông tin của chính mình qua `/api/profile/trainer`.
- [ ] Danh sách học viên chỉ hiển thị các Member được phân công hợp lệ qua `/api/pt/members`.
- [ ] Màn hình tiến độ và ghi chú hiển thị trung thực theo API thật; không fake dữ liệu; ghi chú append-only và không có side effect.
- [ ] Lịch sử buổi tập hoàn thành không có nút chỉnh sửa (RULE GYM 13, 15).
- [ ] Sau entry gate, ba màn hình Chi tiết, Kế hoạch, Lịch sử gọi đúng contract PT-scoped; không có request trái quyền hoặc placeholder runtime.
- [ ] Khi phân công bị kết thúc (403/404): Store tự động xóa dữ liệu học viên đã chọn và chuyển hướng.
- [ ] `FE5-ALL` được nghiệm thu như một task tổng hợp duy nhất sau khi `FE-0 PASS`; cả ba dependency contracts/capabilities có thể `READY`, nhưng các Backend authorization/resource-scope regressions bắt buộc của BLOCKER-03/04/05 phải có evidence `PASS`.
- [ ] Toàn bộ test của FE-5, lint (`npm run lint`), build sạch sẽ (`npm run build`).
