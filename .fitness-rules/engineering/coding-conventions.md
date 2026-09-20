# Engineering: Coding Conventions & Documentation Standards

> **Module Path:** `.fitness-rules/engineering/coding-conventions.md`  
> **Canonical Reference:** [PROJECT_RULES.md](../../PROJECT_RULES.md) (Phần XIV: RULE CODE 05–08; Phần XV: RULE CODE 09–12; Phần XVI: RULE CODE 18)

---

## 1. Quy chuẩn Đặt tên Frontend (Frontend Naming Rules)

### RULE CODE 05 – Tên hàm nghiệp vụ FE bằng tiếng Việt không dấu (camelCase)
- Tên các hàm xử lý logic nghiệp vụ, tính toán, gọi API hoặc định dạng dữ liệu trên Frontend phải viết bằng tiếng Việt không dấu theo chuẩn `camelCase`:
  - *Đúng:* `taiDanhSachBaiTap()`, `hienThiTrangThaiGoiTap()`, `guiYeuCauHoanTatBuoiTap()`, `xuLyXacNhanDeXuat()`.
  - *Sai:* `getExerciseList()`, `activatePackage()`, `calculateRemainingDays()`.

### RULE CODE 06 – Event Handler cũng bằng tiếng Việt không dấu
- Tất cả hàm xử lý sự kiện giao diện (UI Event Handlers: click, submit, change, modal open) phải dùng tiếng Việt không dấu:
  - *Đúng:* `xuLyDangNhap()`, `xuLyGuiBieuMau()`, `xuLyNhanNut()`, `xuLyThanhToanThanhCong()`, `xuLyXacNhanDeXuatAI()`.
  - *Cấm dùng (KHÔNG):* `handleLogin()`, `handleSubmit()`, `handleClick()`, `onSuccess()`, hoặc bất kỳ biến thể tiền tố `handleXxx` nào.
  - Tên hàm phải mô tả rõ nghiệp vụ (ưu tiên `xuLyXacNhanDeXuatAI()` hơn `xuLyClick()`).

### RULE CODE 07 – Biến trạng thái nghiệp vụ ưu tiên tiếng Việt
- Các biến `ref`, `reactive`, `computed` chứa trạng thái hoặc dữ liệu nghiệp vụ chính của màn hình ưu tiên sử dụng tiếng Việt không dấu (camelCase):
  - *Đúng:* `danhSachBaiTap`, `trangThaiKichHoat`, `tongSoTien`, `hoSoHocVien`, `dangTaiDuLieu`.
  - *Sai:* `exerciseList`, `isActivated`, `totalAmount`, `studentProfile`, `isLoading`.

### RULE CODE 08 – Không dịch tên API của Framework & Thư viện
- Giữ nguyên 100% tên hàm, composable, component và hook nguyên bản của Framework (Vue 3, Pinia, Vue Router, Axios):
  - *Đúng:* `ref()`, `computed()`, `onMounted()`, `watch()`, `defineProps()`, `defineEmits()`, `useRouter()`, `useRoute()`, `createPinia()`, `axios.get()`.
  - *Cấm:* Tự chế ra các hàm bọc không cần thiết như `thamChieu()` thay cho `ref()` hay `theoDoi()` thay cho `watch()`.

---

## 2. Tiêu chuẩn Chú thích & Tài liệu mã nguồn (Comment Rules)

### RULE CODE 09 – Hàm nghiệp vụ quan trọng có Docblock meaningful
- Các hàm quan trọng về auth, role routing, mutation, Payment state, Membership activation, idempotency, Q05/Q13, conflict và realtime phải có docblock meaningful nêu:
  1. **Mục đích:** Hàm làm gì trong bức tranh nghiệp vụ.
  2. **Input:** Dữ liệu và ownership/scope cần thiết.
  3. **Process:** Các bước validation hoặc phối hợp API quan trọng.
  4. **Output:** Kết quả hoặc state được cập nhật.
  5. **Side effect:** Audit, cache, retry, subscription, hoặc thay đổi dữ liệu nếu có.
- Không yêu cầu full PHPDoc trên mọi Controller, Model, Request hoặc Policy. Các helper hiển nhiên và getter kỹ thuật có thể dùng mô tả ngắn; framework/API names giữ nguyên.
  ```javascript
  /**
   * Hiển thị trạng thái kỳ Membership từ dữ liệu Backend.
   * Input: snapshot trạng thái/điều kiện do Backend trả về.
   * Process: map nhãn hiển thị, không tính hoặc kích hoạt entitlement ở client.
   * Output: nhãn an toàn cho UI; side effect: không có.
   */
  export function hienThiTrangThaiMembership(snapshot) { ... }
  ```

### RULE CODE 10 – Không viết Comment hiển nhiên, vô nghĩa
- Tuyệt đối cấm các comment rác lặp lại câu lệnh code:
  - *Cấm:* `// gán biến i = 0`, `// gọi API`, `// return kết quả`, `// đóng thẻ div`.
  - Chỉ viết comment giải thích **TẠI SAO** làm như vậy (Why) và các quyết định nghiệp vụ không hiển nhiên.

### RULE CODE 11 – Hàm phức tạp phải chú thích từng bước (Step-by-step)
- Các luồng xử lý nhiều bước (thanh toán, kích hoạt gói, quét QR check-in, apply proposal) bắt buộc phải chia rõ từng bước:
  ```php
  // Bước 1: Validate payload và kiểm tra token xác thực
  // Bước 2: Bắt đầu Transaction và khóa dòng (SELECT FOR UPDATE)
  // Bước 3: Kiểm tra điều kiện hiệu lực gói và snapshot quyền lợi
  // Bước 4: Cập nhật trạng thái và ghi log lịch sử
  // Bước 5: Commit transaction và trả về kết quả chuẩn
  ```

### RULE CODE 12 – Backend Docblock cho nghiệp vụ quan trọng
- Backend phải mô tả meaningful cho các nghiệp vụ quan trọng như Webhook, cấp/kích hoạt Membership, PT session, Proposal, QR, Workout completion, AI validation/apply và Chat authorization. Nội dung gồm mục tiêu, input, process/validation, output, side effect, transaction/idempotency khi có. Không biến yêu cầu này thành full PHPDoc trên mọi Controller, Service, Request, Policy hoặc Model.

---

## 3. Kỷ luật Phạm vi & Kiểm soát Mã nguồn (Scope Discipline)

### RULE CODE 18 – Không tự ý thêm chức năng ngoài Scope
- Triển khai chính xác và đầy đủ các chức năng nằm trong Task Packet và Phase Context được giao.
- Không tự tiện sáng tạo thêm các tính năng ngoài lề (ví dụ: tự ý thêm tính năng Chat nhóm, tự ý làm thanh toán ví điện tử khác ngoài payOS, tự ý làm push notification phức tạp) làm loãng kiến trúc và lãng phí token.
