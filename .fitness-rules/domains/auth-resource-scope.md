# Domain: Authentication, Roles & Resource Scope

> **Module Path:** `.fitness-rules/domains/auth-resource-scope.md`  
> **Canonical Reference:** [PROJECT_RULES.md](../../PROJECT_RULES.md) (Phần III: 12; Phần IV: 13–16; Phần V: 17–18; Phần XX: Rule 46; RULE CODE 17)

---

## 1. Hệ thống Actor & Phạm vi Nền tảng

Hệ thống phục vụ mô hình **Đơn chi nhánh (Single Branch)** với 4 Actor chính:

| Actor | Nền tảng sử dụng | Phạm vi trách nhiệm |
|---|---|---|
| **Member** (Hội viên) | **Mobile App** (React Native/Expo) | Đăng ký/đăng nhập, xem catalog bài tập, tập luyện & ghi nhận set/rep (Workout Tracking), xem lịch sử tập, chat với PT được phân công, nhận đề xuất kế hoạch từ AI/PT, quét mã QR vào phòng, mua & gia hạn gói tập qua payOS. |
| **PT** (Personal Trainer) | **Web App** (Vue 3) | Quản lý danh sách học viên được phân công, xem hồ sơ sức khỏe, chat realtime với học viên, viết ghi chú (`ghi_chu_huan_luyen`), tạo đề xuất điều chỉnh kế hoạch tập (`de_xuat_ke_hoach_tap`), hoàn tất buổi tập trực tiếp (`lich_su_su_dung_huan_luyen_vien`). |
| **Receptionist** (Lễ tân) | **Web App** (Vue 3) | Tra cứu Member trong chi nhánh, xem trạng thái Membership và Gym eligibility, quét/xác nhận QR check-in, và hỗ trợ nghiệp vụ hội viên cơ bản. Không có Receptionist dashboard trong Web scope. |
| **Admin** (Quản trị viên) | **Web App** (Vue 3) | Quản lý toàn bộ danh mục bài tập/nhóm cơ/dụng cụ/giáo án mẫu, quản lý các gói tập (`goi_tap`), cấu hình quyền lợi gói, quản lý tài khoản người dùng, phân công PT cho học viên, xem danh sách thanh toán & đối soát read-only. |

---

## 2. Nguyên tắc Phân định Role vs Entitlement

### Quy tắc Vàng: Free/Premium KHÔNG phải là Role
- **4 Role hệ thống chính thức:**
  - `ADMIN`
  - `PT`
  - `RECEPTIONIST`
  - `MEMBER`
- **Tài khoản người dùng (`nguoi_dung`) & Phân quyền (`phan_quyen_nguoi_dung`):**
  - Mỗi tài khoản có trạng thái chính thức: `HOAT_DONG`, `BI_KHOA`, `NGUNG_HOAT_DONG`. Đây là trạng thái account; không dùng nó thay cho trạng thái kỳ Membership.
  - Một người dùng có thể được gán nhiều vai trò trong bảng `phan_quyen_nguoi_dung` (kèm trạng thái hiệu lực).
  - **Quy tắc cấp lại vai trò (Section 17.1 / Rule 46):** Khi cấp lại hoặc thay đổi trạng thái vai trò cho người dùng, hệ thống cập nhật trực tiếp bản ghi hiện có trong bảng `phan_quyen_nguoi_dung` (cập nhật trạng thái, mốc thời gian) và ghi nhận audit log trong cùng Database Transaction. **Tuyệt đối không tạo thêm bảng lịch sử phân quyền vai trò riêng biệt.**
- **Entitlement (Quyền lợi gói trả phí):**
  - Entitlement là quyền sở hữu snapshot của kỳ `ky_han_hoi_vien` đang có hiệu lực theo thời gian. Head term hợp lệ ở `CHO_KICH_HOAT` có thể được dùng để kích hoạt bằng lần paid use đầu tiên; tail term `CHO_DEN_LUOT` chưa đến lượt không được dùng sớm.
  - Tuyệt đối **không tạo role riêng** cho gói tập (ví dụ: cấm tạo role `VIP`, `PREMIUM`, `GOLD`).
  - Hết hạn gói tập thì kỳ hạn chuyển sang `HET_HAN`, **Role của người dùng vẫn là `MEMBER`**, không bị hạ quyền tài khoản.

---

## 3. Phân quyền theo Tài nguyên (Resource-Scope Authorization — RULE CODE 17)

### Nguyên tắc Thực thi:
1. **Kiểm tra quyền sở hữu (Ownership Check):**
   - Không chỉ kiểm tra role `MEMBER` ở middleware; mọi truy vấn dữ liệu cá nhân (profile, workout session, workout plan, progress, health metric, payment history, notifications) phải gắn điều kiện:
     $$\text{WHERE } nguoi\_dung\_id = current\_user\_id$$
   - Khi truy cập chi tiết một resource bằng ID (ví dụ: `GET /api/workout/plans/current` hoặc một route PT-scoped trong contract), Backend bắt buộc phải xác thực resource đó thuộc về chủ thể hiện tại hoặc nằm trong assignment scope. Nếu không khớp, trả về **HTTP 403 Forbidden** (hoặc 404 để bảo mật).
2. **Kiểm tra phạm vi PT (PT Scope Check — Q04 & Q13):**
   - PT chỉ có quyền đọc hồ sơ/progress/Plan/History, đọc hoặc gửi Chat trong đúng resource scope khi và chỉ khi đang có bản ghi phân công `phan_cong_huan_luyen_vien` hiệu lực theo khoảng thời gian `[ngay_bat_dau, ngay_ket_thuc)`. Q05 tách read history của Member khỏi send mới: PT cũ mất đọc/gửi/subscribe sau khi assignment kết thúc; Member vẫn đọc lịch sử cũ.
   - **Q04:** Member chỉ có tối đa 0..1 phân công PT hiệu lực tại bất kỳ thời điểm nào; tuyệt đối cấm overlap giữa bất kỳ PT nào cùng Member. Khi đổi PT, phân công cũ phải được đóng khoảng (`ngay_ket_thuc`) trước hoặc đúng lúc mở khoảng mới trong cùng transaction + audit.
   - **Q13:** Quyền tạo Proposal của PT chỉ cần PT đã xác thực và có quan hệ phân công hiệu lực `[ngay_bat_dau, ngay_ket_thuc)` với Member; không phụ thuộc vào việc Member còn lượt PT trong gói hay không, không yêu cầu active Membership hay Chat entitlement.
3. **Phân quyền Lễ tân (Receptionist Scope):**
   - Phạm vi nghiệp vụ của Lễ tân bao gồm: (1) Tra cứu hội viên (lookup) trong chi nhánh phụ trách; (2) Xem trạng thái Membership/Gym eligibility do Backend tính; (3) Quét và xác nhận mã QR Check-in; (4) Hỗ trợ nghiệp vụ hội viên cơ bản tại quầy tiếp đón.
   - Tuyệt đối không tạo Receptionist dashboard; không rút gọn vai trò Lễ tân về chỉ mỗi quét QR/lịch sử; cấm truy cập dữ liệu quản trị danh mục/giá gói hoặc can thiệp kế hoạch tập luyện/thanh toán.
4. **Phân quyền Quản trị (Admin Scope):**
   - Chỉ Account đang hoạt động có vai trò `ADMIN` hiệu lực mới được phép truy cập/mutation trên các catalog quản trị (bài tập, nhóm cơ, dụng cụ, gói tập) và danh sách thanh toán/đối soát.

---

## 4. Cơ chế Xác thực & Session (Database Bearer Token)

- **Authentication Protocol:** Database Bearer Token tùy biến (custom database Bearer token, không phải Sanctum cookie/token). Login trả raw token đúng một lần, DB lưu băm SHA-256 (`the_truy_cap`), `/api/auth/me` revalidate tài khoản/vai trò, logout thu hồi token.
- **Token Format:** `Authorization: Bearer <token>` gắn trên mọi request có xác thực.
- **Xử lý mã lỗi chuẩn:**
   - `401 Unauthorized`: Token không hợp lệ, hết hạn, hoặc bị thu hồi $\rightarrow$ Frontend xóa token và điều hướng về trang Login.
   - `403 Forbidden`: Token hợp lệ nhưng tài khoản không có quyền thao tác trên tài nguyên tương ứng $\rightarrow$ Hiển thị thông báo cấm quyền, không tự ý đăng xuất người dùng.
