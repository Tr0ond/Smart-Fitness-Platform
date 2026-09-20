# Phase Context: FE-3 — Admin Catalog

> **Task Scope:** `FE3-ALL` (Tổng hợp toàn bộ Phase 3 — Không tách subtask)  
> **Mục tiêu:** Hoàn thiện catalog có mutation an toàn, làm rõ snapshot đơn hàng và copy-on-write giáo án mẫu.  
> **Canonical Plan Reference:** [docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md](../../docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md) (Section 23, 26, 28; Section 31: FE-3 & Section 38A: `FE3-ALL`)

---

## 1. Danh sách Màn hình & Routes Triển khai (12 Màn hình)

- **Gói tập (Packages — 3 màn hình):**
  - `/admin/goi-tap`: Danh sách gói tập (`adminGoiTap`).
  - `/admin/goi-tap/tao-moi`: Tạo mới gói tập (`adminTaoGoiTap`).
  - `/admin/goi-tap/:id`: Chi tiết & thay thế quyền lợi gói tập (`adminChiTietGoiTap`).
- **Dụng cụ (Equipment — 1 màn hình):**
  - `/admin/dung-cu`: Quản lý danh mục dụng cụ (`adminDungCu`), thêm mới, sửa tên, đổi trạng thái hoạt động (deactivate). Cấm hard-delete.
- **Nhóm cơ (Muscle Group — 1 màn hình):**
  - `/admin/nhom-co`: Quản lý danh mục nhóm cơ (`adminNhomCo`), thêm mới, sửa metadata, chuyển `trang_thai` giữa `HOAT_DONG` và `NGUNG_SU_DUNG`.
- **Bài tập (Exercises — 3 màn hình):**
  - `/admin/bai-tap`: Danh sách bài tập (`adminBaiTap`), tìm kiếm, lọc theo nhóm cơ/dụng cụ/độ khó.
  - `/admin/bai-tap/tao-moi`: Form tạo bài tập (`adminTaoBaiTap`).
  - `/admin/bai-tap/:id`: Chi tiết/chỉnh sửa bài tập (`adminChiTietBaiTap`), liên kết nhóm cơ chính/phụ và dụng cụ yêu cầu.
- **Giáo án Mẫu (Workout Templates — 4 màn hình):**
  - `/admin/giao-an-mau`: Danh sách giáo án mẫu (`adminGiaoAnMau`).
  - `/admin/giao-an-mau/tao-moi`: Form tạo khung giáo án mẫu (`adminTaoGiaoAnMau`).
  - `/admin/giao-an-mau/:id`: Chi tiết thông tin metadata giáo án mẫu (`adminChiTietGiaoAnMau`).
  - `/admin/giao-an-mau/:id/tao-phien-ban`: Trình soạn thảo cây giáo án tạo phiên bản mới (`adminTaoPhienBanGiaoAnMau`).

---

## 2. Các File Mã Nguồn Dự kiến (Expected Files — Section 28 File Inventory)

> **Nguyên tắc Kiểm tra Mã nguồn Thực tế:** Trước khi tạo bất kỳ file nào, bắt buộc kiểm tra mã nguồn thực tế trong `FE/src/`. Nếu file đã tồn tại theo cấu trúc chuẩn, tái sử dụng và kiểm thử, tuyệt đối không tạo file trùng lặp.

- **Pages (12 files theo Section 28):**
  - `src/pages/admin/goi_tap/goi_tap.index.vue`
  - `src/pages/admin/goi_tap/goi_tap.tao_moi.vue`
  - `src/pages/admin/goi_tap/goi_tap.chi_tiet.vue`
  - `src/pages/admin/dung_cu/dung_cu.index.vue`
  - `src/pages/admin/nhom_co/nhom_co.index.vue`
  - `src/pages/admin/bai_tap/bai_tap.index.vue`
  - `src/pages/admin/bai_tap/bai_tap.tao_moi.vue`
  - `src/pages/admin/bai_tap/bai_tap.chi_tiet.vue`
  - `src/pages/admin/giao_an_mau/giao_an_mau.index.vue`
  - `src/pages/admin/giao_an_mau/giao_an_mau.tao_moi.vue`
  - `src/pages/admin/giao_an_mau/giao_an_mau.chi_tiet.vue`
  - `src/pages/admin/giao_an_mau/giao_an_mau.tao_phien_ban.vue`
- **Domain Services (5 files theo Section 26):**
  - `src/services/goi_tap.api.js` (Packages & benefits)
  - `src/services/dung_cu.api.js` (Equipment)
  - `src/services/nhom_co.api.js` (Muscle groups)
  - `src/services/bai_tap.api.js` (Exercises)
  - `src/services/giao_an_mau.api.js` (Templates & revisions)
- **Store (1 file theo Section 25):**
  - `src/stores/danh_muc.store.js` (Cache ngắn hạn options dụng cụ/nhóm cơ; invalidate sau mutation; không dùng cache làm authority).
- **Components (Section 15, 24):**
  - `src/components/danh_muc/cay_giao_an.vue` (Cây giáo án mẫu)
  - `src/components/danh_muc/bo_chon_quan_he_bai_tap.vue`
  - `src/components/danh_muc/bo_sua_quyen_loi_goi_tap.vue`
  - Dialog xử lý 409 revision conflict.

---

## 3. Hàm Nghiệp vụ Chính & Docblocks Bắt buộc

### Hàm nghiệp vụ chính (RULE CODE 05, 06):
- `taiDanhSachGoiTap()`, `taoGoiTap()`, `capNhatGoiTap()`, `thayTheQuyenLoiGoiTap()`
- `taiDanhSachDungCu()`, `taoDungCu()`, `capNhatDungCu()`
- `taiDanhSachNhomCo()`, `taoNhomCo()`, `capNhatNhomCo()`
- `taiDanhSachBaiTap()`, `taoBaiTap()`, `capNhatBaiTap()`, `taoPayloadQuanHeBaiTap()`
- `taiDanhSachGiaoAnMau()`, `taoPhienBanGiaoAnMau()`, `xuLyGiaoAnMauDaCu()`

### Required Docblocks & Quy tắc nghiệp vụ bắt buộc:
1. **Q01 & Package Snapshot:** Khi chỉnh sửa quyền lợi gói tập, giao diện phải hiển thị rõ cảnh báo: Việc thay đổi quyền lợi chỉ áp dụng cho các lượt mua mới; các kỳ hạn hội viên đã mua trước đó giữ nguyên quyền lợi snapshot không đổi.
2. **Q11 & Equipment AND Semantics:** Quan hệ `bai_tap_dung_cu` mang ngữ nghĩa AND bắt buộc. Form bài tập phải cho phép chọn nhiều dụng cụ và payload gửi danh sách đầy đủ.
3. **Catalog lifecycle (Q12):** Equipment/Exercise follow their own approved metadata/status lifecycle and never hard-delete or rewrite history. Muscle Group follows M061 exactly: `trang_thai` is `HOAT_DONG`/`NGUNG_SU_DUNG`, default `HOAT_DONG`; GET includes inactive; no DELETE; new relations require active groups; existing inactive pivots are preserved.
4. **M061 relation and audit acceptance:** Exercise replacement `muscle_groups` must echo every inactive relation with the exact role or fail atomically with `INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE`; omitted `muscle_groups` leaves pivots untouched. Same-state Group PATCH is no-op without timestamp/audit. A real transition writes one `CAP_NHAT_TRANG_THAI_NHOM_CO` audit in the same transaction; row locks serialize status/relation races. Do not apply this lifecycle to Equipment.
5. **Copy-on-write & stale revision:** Template revisions are copy-on-write. `WORKOUT_TEMPLATE_STALE` is only a 409 optimistic-concurrency result when `expected_content_version` is stale; keep the draft, reload/compare, and do not add a persistent Member-plan flag.
5. **No Days in PATCH:** Endpoint PATCH metadata giáo án mẫu không được gửi cấu trúc `days`.

---

## 4. Ngữ cảnh Nạp Tối thiểu (Minimal Working Context)

- **Bắt buộc đọc:**
  1. `.fitness-rules/PROJECT_CORE.md`
  2. `.fitness-rules/frontend/vue-core.md`
  3. `.fitness-rules/frontend/visual-ui.md`
  4. `.fitness-rules/domains/workout.md` (Phần Catalog, Q11, Template)
  5. `.fitness-rules/domains/membership-payment.md` (Phần Q01 Snapshot)
- **DO NOT READ:** Không đọc `pt-chat.md`, `ai-proposal.md`.

---

## 5. Trạng thái Backend & STOP Conditions

- **Backend APIs:** Tất cả API Admin packages, benefits, equipment, muscle-groups, exercises, workout-templates, revisions đều ở trạng thái `READY`. Danh sách không phân trang là scalability risk, không phải blocker MVP.
- **STOP Conditions:** Nếu tree request/response lệch actual controller DTO, dừng template form và cập nhật fixture từ source Backend `BE/`; không invent endpoint, stale flag, or field.

---

## 6. Tiêu chí Nghiệm thu của Task `FE3-ALL`

- [ ] Toàn bộ 12 màn hình catalog hoạt động với dữ liệu thật, không fake API.
- [ ] List/create/edit/deactivate an toàn, có confirm dialog cho thao tác nhạy cảm.
- [ ] Hiển thị đầy đủ cảnh báo snapshot đơn hàng theo Q01.
- [ ] Payload liên kết bài tập - dụng cụ tuân thủ nghiêm ngặt Q11.
- [ ] M061 list/create/update/no-delete, status enum/default, inactive-pivot preservation, replacement omission/echo rule, exact error, same-state no-op, atomic audit, and race serialization are covered.
- [ ] Template tree editor validate cấu trúc, xử lý 409 stale giữ draft.
- [ ] Toàn bộ test của FE-3, lint sạch sẽ (`npm run lint`), build thành công (`npm run build`).
- [ ] Docblocks đầy đủ theo RULE CODE 09–12; toàn bộ hàm dùng camelCase tiếng Việt không dấu (RULE CODE 05), event handlers dùng `xuLy...` (RULE CODE 06, cấm `handleXxx`).
