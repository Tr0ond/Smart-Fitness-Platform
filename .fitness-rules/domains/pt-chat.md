# Domain: Personal Trainer (PT) & Realtime Chat Rules

> **Module Path:** `.fitness-rules/domains/pt-chat.md`  
> **Canonical Reference:** [PROJECT_RULES.md](../../PROJECT_RULES.md) (Phần IV: 14–14.1; Phần VI: RULE GYM 09, 23, 24; Phần IX: 31–33.1; Q04, Q05, Q06, Q13)

---

## 1. Phân công Huấn luyện viên (PT Assignment — Q04)

### Cấu trúc Phân công theo Thời gian
- Quan hệ giữa Member và PT được quản lý thông qua bảng `phan_cong_huan_luyen_vien` (không viết tắt `pt`).
- Bảng bao gồm: `hoi_vien_id`, `huan_luyen_vien_id`, `nguoi_phan_cong_id`, `ngay_bat_dau`, `ngay_ket_thuc`, `ly_do_ket_thuc`, `hoi_vien_dang_phan_cong_id` (cột sinh VIRTUAL: bằng `hoi_vien_id` khi `ngay_ket_thuc IS NULL`; ngược lại NULL).
- **Q04 – Invariant Phân công theo Khoảng Thời gian:**
  - Quản lý quan hệ phụ trách bằng khoảng nửa mở `[ngay_bat_dau, ngay_ket_thuc)`, với `ngay_ket_thuc = NULL` biểu thị khoảng đang mở.
  - Một Member chỉ có **tối đa 0..1 PT hiệu lực** tại bất kỳ thời điểm nào; tuyệt đối không cho phép overlap giữa bất kỳ PT nào cùng một Member.
  - Khi Admin phân công PT mới cho Member, phân công cũ phải được đóng khoảng (`ngay_ket_thuc`) trước hoặc đúng lúc mở khoảng mới trong cùng Database Transaction + audit; lưu giữ toàn bộ bản ghi lịch sử, không ghi đè hay xóa.

---

## 2. Realtime Chat & Quyền Hạn Hội thoại (Q05)

### Section 31 & 32 – Phạm vi & Kiến trúc Realtime Chat
- Hệ thống hỗ trợ kênh chat trực tiếp 1-1 giữa Member và PT phụ trách.
- Backend sử dụng Laravel Reverb (WebSocket) kết hợp Laravel Echo tại Frontend để đồng bộ tin nhắn tức thời.
- **Cấu trúc CSDL:**
  - Hội thoại: `hoi_thoai` (chứa `hoi_vien_id`, `huan_luyen_vien_id`, `phan_cong_huan_luyen_vien_id`). **Không có bảng thành viên hội thoại riêng.**
  - Tin nhắn: `tin_nhan` (chứa nội dung, `client_message_id`, `thu_tu_tin_nhan` / `sequence`).
  - Sự kiện phát: `su_kien_phat_tin_nhan` (outbox event phục vụ broadcast).

### Q05 – Tách biệt Hoàn toàn Quyền ĐỌC LỊCH SỬ và GỬI TIN NHẮN MỚI
Quy tắc phân quyền hội thoại khi có sự thay đổi phân công PT hoặc hết hạn Membership:
1. **Quyền của Member (Hội viên):**
   - **Đọc lịch sử (Read History):** Member có quyền xem lại toàn bộ tin nhắn đã trao đổi trong quá khứ với các PT cũ, kể cả khi phân công với PT đó đã kết thúc hoặc gói tập của Member đã hết hạn (`HET_HAN`).
   - **Gửi tin nhắn mới (Send New Message):** Member chỉ được phép gửi tin nhắn mới khi và chỉ khi:
     - Đang có một phân công `phan_cong_huan_luyen_vien` hiệu lực theo khoảng thời gian `[ngay_bat_dau, ngay_ket_thuc)` với PT tương ứng (tối đa 0..1 PT hiệu lực tại một thời điểm, không chồng lấn).
     - VÀ thỏa mãn điều kiện quyền lợi Chat của Membership:
       1. Kỳ hạn hiện tại đang `DANG_HOAT_DONG` và có cấu hình quyền lợi `cho_phep_tro_chuyen_huan_luyen_vien = 1`;
       2. HOẶC kỳ đầu hợp lệ đang ở trạng thái `CHO_KICH_HOAT` có cấu hình `cho_phep_tro_chuyen_huan_luyen_vien = 1`, khi đó lần gửi tin hợp lệ đầu tiên từ Member sẽ kích hoạt kỳ hạn (`ky_han_hoi_vien.ngay_bat_dau = su_dung_quyen_loi.chap_nhan_luc` theo RULE GYM 02).
2. **Quyền của PT (Huấn luyện viên):**
   - **PT cũ:** Khi phân công kết thúc (thời điểm hiện tại $\ge$ `ngay_ket_thuc`), PT cũ **mất hoàn toàn resource scope**, tuyệt đối không được tiếp tục truy cập, đọc hội thoại hay gửi tin nhắn qua scope cũ. Ngắt kết nối kênh WebSocket, dừng toàn bộ retry/reconnect và rời kênh.
   - **PT mới:** Khi nhận phân công mới với Member, PT mới **không được phép đọc các tin nhắn riêng tư** giữa Member và PT cũ trước đó (trừ các ghi chú chính thức trong `ghi_chu_huan_luyen`).

### RULE GYM 09 & Section 33.1 – Chat PT và Lần đầu Kích hoạt Kỳ
- **RULE GYM 09:** Chat PT và Buổi tập PT là hai quyền độc lập. Gửi tin nhắn chat không làm tiêu hao số buổi tập PT của học viên.
- **Section 33.1 & Kích hoạt Kỳ đầu:** Nếu head term Membership đang `CHO_KICH_HOAT`, đúng một Chat usage loại `TRO_CHUYEN_HUAN_LUYEN` được tạo khi Member message hợp lệ thắng việc kích hoạt; message tham chiếu usage bằng `tin_nhan.su_dung_quyen_loi_id`, và `dang_ky_goi_tap.lan_su_dung_dau_tien_id` trỏ về usage đó.
- Tin Member đến sau khi term đã `DANG_HOAT_DONG`, tin PT chủ động, đọc lịch sử, mở màn hình, subscribe/reconnect, hoặc term đã được kích hoạt bởi AI/QR/PT đều không tạo Chat usage mới. Không có quota/counter theo số tin.
- Message kích hoạt phải kiểm tra exact assignment và snapshot Chat của đúng term; không mở quyền của term `CHO_DEN_LUOT`. Nếu lưu message hoặc outbox fail, rollback message, usage và activation trong cùng transaction. Retry cùng `client_message_id` trả về đúng message/usage cũ, không nhân đôi.
- Hai Member messages hoặc Chat tranh AI/QR/PT phải khóa Member/chain/term, đọc lại trạng thái và chỉ winner tạo usage. Hành động PT, đọc, mở và subscribe tuyệt đối không kích hoạt thay Member.

---

## 3. Xác nhận Buổi tập & Quản lý Buổi PT (PT Sessions — Q06 & RULE GYM 23)

### RULE GYM 23 – Số buổi PT thuộc từng kỳ hạn
- Số buổi tập với PT được cấp theo snapshot của từng kỳ hạn hội viên (`ky_han_hoi_vien.so_buoi_huan_luyen_vien`).
- **Không carry-over:** Buổi PT chưa dùng hết trong kỳ cũ sẽ hết hạn khi kỳ đó kết thúc, không được bảo lưu hay cộng dồn sang kỳ gia hạn sau.
- Cấm trừ trước buổi tập của kỳ tương lai khi đang ở kỳ hiện tại.

### Q06 – Điều kiện PT Hoàn tất Buổi tập (Complete PT Direct)
- Khi hoàn tất buổi tập PT trực tiếp:
  - Ghi nhận lịch sử sử dụng vào bảng `lich_su_su_dung_huan_luyen_vien`.
  - **Điều kiện hợp lệ của Q06:**
    1. PT phải thực hiện xác nhận hợp lệ khi đúng kỳ của buổi còn hiệu lực và còn quota buổi tập (hoặc kỳ đầu được phép kích hoạt theo rule chung). Trừ đúng 1 lượt.
    2. Nếu kỳ hạn đã hết hạn trước khi xác nhận, hành động xác nhận sẽ bị Backend **từ chối hoàn toàn**; không backdate thời điểm hoàn thành để trừ vào kỳ cũ, không mượn lượt của kỳ sau.
    3. Member tự gửi request xác nhận hoàn thành bị Backend từ chối. Không yêu cầu Member phải đồng xác nhận (co-confirmation).
  - Bắt buộc sử dụng `Idempotency-Key` ổn định và cơ chế pending guard phía Client để ngăn duplicate ledger khi click nhiều lần hoặc timeout mạng.

---

## 4. Đề xuất Kế hoạch của PT (PT Proposal — Q13 & RULE GYM 24)

### RULE GYM 24 – PT Đề xuất Thay đổi Kế hoạch qua Proposal
- Khi PT muốn điều chỉnh bài tập, đổi số rep/set, hoặc sắp xếp lại lịch tập cho học viên:
  - PT không được tự ý ghi đè trực tiếp lên kế hoạch chính thức (`ke_hoach_tap`) của học viên.
  - PT bắt buộc phải tạo bản ghi đề xuất `de_xuat_ke_hoach_tap` với `nguon_de_xuat = HUAN_LUYEN_VIEN`; PT là nhãn hiển thị, không phải stored enum.
  - Trạng thái ban đầu của đề xuất là `CHO_XAC_NHAN` (xác thực tại `TU_DIEN_DU_LIEU.md` Bảng 50: `de_xuat_ke_hoach_tap`, dòng 1947 và migration `2026_08_29_000050_m050_tao_de_xuat_ke_hoach_tap.php`). Thời hạn hiệu lực là **24 giờ** (Q09).
  - Chỉ khi **Member bấm nút chấp thuận (Approve)** trên ứng dụng Mobile, kế hoạch mới chính thức được cập nhật.

### Q13 – Thẩm quyền Tạo Proposal của PT Độc lập với Gói tập của Member
- Điều kiện duy nhất để PT có quyền tạo `de_xuat_ke_hoach_tap` cho Member:
  - PT đã xác thực tài khoản.
  - Đang có bản ghi `phan_cong_huan_luyen_vien` hiệu lực theo khoảng thời gian `[ngay_bat_dau, ngay_ket_thuc)` với Member.
- Quyền tạo đề xuất của PT **hoàn toàn độc lập** với việc Member còn hay hết buổi tập PT trong gói. Dù Member chỉ mua gói tự tập (hoặc đã dùng hết số buổi PT), miễn là phân công PT vẫn còn hiệu lực hỗ trợ chuyên môn, PT vẫn được phép gửi đề xuất điều chỉnh kế hoạch tập luyện cho Member; Proposal không cần active Membership, Chat entitlement hay quota, không trừ lượt và không kích hoạt Membership (Q13).

### Confirm-time PT Proposal checks

Khi Member xác nhận một PT Proposal, Backend khóa Proposal, assignment nguồn, Member, Plan/Version và future schedule rồi revalidate source assignment vẫn hiệu lực, ownership, base version/context, future schedule, TTL 24 giờ và trạng thái `CHO_XAC_NHAN`. Không dùng một assignment mới để cứu Proposal của assignment cũ.

- Nếu assignment kết thúc, base context đổi, hết TTL, đã xử lý hoặc có xung đột, không ghi đè Plan; chuyển Proposal sang terminal `XUNG_DOT` hoặc trạng thái kết thúc canonical phù hợp và giữ lịch sử.
- Nếu hợp lệ, một transaction idempotent tạo version/lịch cần thiết, cập nhật trạng thái Proposal, ghi audit và bảo toàn lịch sử. Retry hoặc confirm đồng thời chỉ apply một lần.
- PT Proposal không yêu cầu Membership active, quyền Chat, quota trực tiếp hay quota trợ lý; tạo và apply không tạo Chat/direct usage và không kích hoạt Membership.
