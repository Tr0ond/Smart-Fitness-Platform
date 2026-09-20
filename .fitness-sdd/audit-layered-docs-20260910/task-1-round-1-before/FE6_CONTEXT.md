# Phase Context: FE-6 — PT Direct + Proposal

> **Task Scope:** `FE6-ALL` (Tổng hợp toàn bộ Phase 6 — Không tách subtask)  
> **Mục tiêu:** Hoàn thiện hai workflow mutation quan trọng của PT: Hoàn tất buổi tập PT trực tiếp (Idempotency, RULE GYM 02 & Q06) và Đề xuất thay đổi kế hoạch tập luyện (Q13).  
> **Canonical Plan Reference:** [docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md](../../docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md) (Section 31: FE-6, Section 38A: `FE6-ALL` & Section 17: PT Screens)

---

## 1. Danh sách Màn hình & Routes Triển khai (3 Màn hình)

1. **Quản lý Buổi Huấn luyện Trực tiếp (`/pt/hoi-vien/:id/buoi-huan-luyen` — route name: `ptBuoiHuanLuyen`):**
   - Xem lịch sử các buổi tập PT đã hoàn thành (`lich_su_su_dung_huan_luyen_vien`). API trả tối đa 100 bản ghi, client lọc theo member và UI ghi rõ giới hạn.
   - Nút hành động: "Xác nhận hoàn tất buổi tập" (Complete PT Direct Session). `READY` có giới hạn.
2. **Danh sách & Xem trước Đề xuất Kế hoạch (`/pt/hoi-vien/:id/de-xuat` — route name: `ptDeXuatKeHoach`):**
   - Danh sách các đề xuất kế hoạch (`de_xuat_ke_hoach_tap`, stored source `HUAN_LUYEN_VIEN`) của học viên. Tối đa 100 bản ghi; UI có thể dùng nhãn PT nhưng không ghi nhãn đó vào payload.
   - Trạng thái đề xuất (theo `TU_DIEN_DU_LIEU.md` Bảng 50 và migration `2026_08_29_000050_m050_tao_de_xuat_ke_hoach_tap.php`): `CHO_XAC_NHAN`, `DA_TU_CHOI`, `HET_HAN`, `XUNG_DOT`, `DA_AP_DUNG`.
   - Xem trước chi tiết đề xuất qua drawer/modal (`khung_xem_de_xuat.vue`), không tạo thêm route mới ngoài danh mục. `READY`.
3. **Tạo Đề xuất Kế hoạch Mới (`/pt/hoi-vien/:id/de-xuat/tao-moi` — route name: `ptTaoDeXuatKeHoach`):**
   - Trình soạn thảo điều chỉnh exercise, day, hiệp tập (set), số lần lặp (rep) và validated plan structure theo proposal contract đã được xác nhận.
   - Gửi đề xuất sang trạng thái chờ xác nhận (`CHO_XAC_NHAN` theo migration `m050` và `TU_DIEN_DU_LIEU.md` Bảng 50) với thời hạn sống (TTL) 24 giờ. `READY`.

---

## 2. Các File Mã Nguồn Dự kiến (Section 28 & Section 26)

> **Nguyên tắc kiểm tra mã nguồn:** Trước khi tạo bất kỳ file nào, kiểm tra mã nguồn thực tế trong `FE/src/`. Mã nguồn thực tế thắng; không tạo file trùng lặp.

- **Pages (Section 28 exact paths — 3 files):**
  - `src/pages/pt/buoi_huan_luyen/buoi_huan_luyen.index.vue`
  - `src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.index.vue`
  - `src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.tao_moi.vue`
- **Domain Services (Section 26 exact paths — 2 files):**
  - `src/services/buoi_huan_luyen.api.js` (PT direct history/complete)
  - `src/services/de_xuat.api.js` (PT member proposals list/create; stored source is `HUAN_LUYEN_VIEN`)
- **Store (Section 25 — 1 file):**
  - `src/stores/de_xuat.store.js` (Proposal list by member, draft, stable action key, submit state; dọn dẹp khi mất phân công; tuyệt đối không có logic tự apply plan).
- **Components (Section 24):**
  - `src/components/pt/khung_xem_de_xuat.vue`, `src/components/chung/hop_thoai_xac_nhan.vue`, `src/components/chung/huy_hieu_trang_thai.vue`.

---

## 3. Hàm Nghiệp vụ Chính & Quy tắc Bắt buộc

### Hàm nghiệp vụ chính:
- `taiLichSuBuoiHuanLuyen()`
- `hoanTatBuoiHuanLuyen()`, `thuLaiHoanTatBuoiHuanLuyen()`
- `taiDanhSachDeXuat()`, `taoDeXuatKeHoach()`, `xuLyXungDotDeXuat()`

### Quy tắc nghiệp vụ bắt buộc:
0. **Dependency entry gate:** `FE6-ALL` chỉ được dispatch sau khi `FE5-ALL` PASS và proposal/direct-session contracts, tests, cùng stable-key recovery behavior đã được controller xác nhận.
1. **Q06, RULE GYM 02 & Section 33.1 (Hoàn tất Buổi PT Trực tiếp):**
   - **Không được coi hoàn tất buổi tập chỉ đòi hỏi "gói active":** Buổi PT hợp lệ đầu tiên có thể kích hoạt kỳ `CHO_KICH_HOAT` (mốc kích hoạt chuẩn xác `ky_han_hoi_vien.ngay_bat_dau = su_dung_quyen_loi.chap_nhan_luc`, kích hoạt đồng hồ chung cho kỳ hạn hội viên theo RULE GYM 02). Kỳ hạn phải đang hoạt động (`DANG_HOAT_DONG`) HOẶC là kỳ đầu chờ kích hoạt (`CHO_KICH_HOAT`) còn lượt PT.
   - **Backend sở hữu thẩm quyền kiểm tra điều kiện và tính hạn ngạch (no client quota math):** Frontend tuyệt đối không tự làm phép tính quota hay xác định kỳ hạn trên client; Backend quyết định term, quota, assignment, activation và duplicate ledger.
   - **Bảo toàn ngữ nghĩa Q06:** Trừ lượt theo đúng kỳ hiệu lực (exact-term), không hồi tố (no-backdate), không mượn lượt kỳ sau (no-borrow-next-term).
   - **Idempotency bắt buộc:** Client sinh một `Idempotency-Key` (UUIDv4) ổn định theo vòng đời thao tác gửi trên header. Bật trạng thái pending (`aria-busy="true"`) để chống double-click. Nếu gặp lỗi mạng/timeout hoặc kết quả chưa rõ ràng, thực hiện refetch kiểm tra trước khi cho phép retry; retry phải giữ nguyên key và không tạo duplicate ledger UX.
2. **Q13 – Thẩm quyền Tạo Đề xuất Độc lập với Gói của Member:**
   - PT chỉ cần có phân công hiệu lực trong khoảng `[ngay_bat_dau, ngay_ket_thuc)` với Member là đủ điều kiện tiên quyết tạo proposal.
   - Không yêu cầu active Membership, không yêu cầu Chat entitlement, không yêu cầu lượt PT Direct; không kích hoạt Membership và không trừ bất kỳ quota nào. Tuyệt đối không chặn form tạo đề xuất nếu Member đã hết số buổi PT trong gói.
3. **PT Không Có Quyền Apply Proposal (RULE GYM 24):**
   - Trên giao diện Web của PT tuyệt đối **KHÔNG có nút "Áp dụng kế hoạch"** (Apply Plan).
   - Quyền áp dụng thuộc về Member trên Mobile App sau khi xem bản so sánh Diff.
4. **Xử lý Xung đột 409 (Conflict Handling):**
   - Khi xảy ra lỗi 409 (do phiên bản kế hoạch thay đổi, mất phân công, hoặc dữ liệu template bị sửa): Giữ nguyên draft form người dùng đang nhập, hiển thị thông báo xung đột và hướng dẫn refetch, không tự ý ghi đè dữ liệu hoặc tự động retry với key/payload khác.
5. **STOP Conditions (Section 31: FE-6):**
   - Nếu selected Member không còn phân công hiệu lực, xóa store và dừng mutation; không retry conflict tự động.
   - Nếu dependency gate chưa PASS, giữ WAITING_DEPENDENCY và không tiêu thụ writer/reviewer; sau gate một writer triển khai đủ ba màn hình.

---

## 4. Ngữ cảnh Nạp Tối thiểu (Minimal Working Context)

- **Bắt buộc đọc:**
  1. `.fitness-rules/PROJECT_CORE.md`
  2. `.fitness-rules/frontend/vue-core.md`
  3. `.fitness-rules/frontend/visual-ui.md`
  4. `.fitness-rules/domains/pt-chat.md` (Phần Q06 Buổi PT, Q13 Đề xuất PT)
  5. `.fitness-rules/domains/workout.md` (Kế hoạch tập, Buổi tập dự kiến, RULE GYM 24)
  6. `.fitness-rules/domains/membership-payment.md` (RULE GYM 02 Kích hoạt kỳ đầu)
- **DO NOT READ:** Không đọc `ai-proposal.md`.

---

## 5. Tiêu chí Nghiệm thu của Task `FE6-ALL`

- [ ] Hoàn tất buổi tập PT gửi đúng `Idempotency-Key`, chống click trùng lặp.
- [ ] Giao diện mô tả trung thực giới hạn dữ liệu của buổi huấn luyện (tối đa 100 bản ghi gần nhất).
- [ ] Danh sách đề xuất và xem trước hiển thị chính xác các thay đổi (drawer/modal, không invent route).
- [ ] Tạo proposal mới tuân thủ Q13 (chỉ cần phân công hợp lệ, không đòi hỏi Membership/chat/lượt PT) và gửi source `HUAN_LUYEN_VIEN`; PT chỉ là nhãn hiển thị.
- [ ] Không có nút Apply proposal trên Web PT (RULE GYM 24).
- [ ] Xử lý xung đột 409 giữ nguyên bản nháp của PT, hướng dẫn refetch.
- [ ] Khi unknown outcome, refetch/đối soát bằng cùng stable key trước khi retry; không đổi key hoặc payload để né kết quả chưa rõ.
- [ ] Toàn bộ test của FE-6, lint (`npm run lint`), build sạch sẽ (`npm run build`).
