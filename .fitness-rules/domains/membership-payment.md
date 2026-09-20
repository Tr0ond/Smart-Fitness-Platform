# Domain: Membership & Payment Rules

> **Module Path:** `.fitness-rules/domains/membership-payment.md`  
> **Canonical Reference:** [PROJECT_RULES.md](../../PROJECT_RULES.md) (Phần II: 8–8.1; Phần VI: RULE GYM 01–10, 17–23, 25; Q01, Q02, Q06, Q09, Q10)

---

## 1. Bản chất Kích hoạt & Kỳ hạn (Activation & Periods)

### RULE GYM 01 – Thanh toán chưa bắt đầu tính thời hạn
- Khi thanh toán thành công, hệ thống chỉ ghi nhận **quyền sở hữu** kỳ hạn hội viên.
- Trạng thái ban đầu của kỳ đầu khi chưa có chuỗi đang hoạt động là `CHO_KICH_HOAT`.
- Hai trường thời gian: `ngay_bat_dau = null`, `ngay_ket_thuc = null`.

### RULE GYM 17 – Payment và Activation là hai sự kiện khác nhau
- Mốc xác nhận thanh toán (`lan_thanh_toan.xac_nhan_luc` / `ky_han_hoi_vien.mua_luc`) $\ne$ `ngay_bat_dau`. Tuyệt đối không đánh đồng sự kiện thanh toán với sự kiện kích hoạt gói.
- Hội viên có thể mua gói trước nhiều ngày nhưng chưa kích hoạt sử dụng ngay.

### RULE GYM 02 – Lần sử dụng quyền lợi đầu tiên kích hoạt Membership
- Lần đầu tiên phát sinh một trong các hành động hợp lệ sau sẽ kích hoạt kỳ đầu tiên:
  1. **Quét mã QR vào phòng tập** thành công tại quầy Lễ tân (sử dụng quyền Gym).
  2. **Gửi request AI tư vấn/lên lịch** thành công được Backend chấp nhận (sử dụng quyền AI).
  3. **Xác nhận buổi tập PT hoàn thành** hoặc **tin nhắn Member hợp lệ gửi tới PT** (sử dụng quyền PT).
- **Mốc kích hoạt canonical:**
  $$\text{ky\_han\_hoi\_vien.ngay\_bat\_dau} = \text{su\_dung\_quyen\_loi.chap\_nhan\_luc}$$
  $$\text{ky\_han\_hoi\_vien.ngay\_ket\_thuc} = \text{ky\_han\_hoi\_vien.ngay\_bat\_dau} + \text{ky\_han\_hoi\_vien.thoi\_han\_ngay}$$
  *(Tuyệt đối không dùng generic `NOW()` hay client timestamp).*
- **Phân biệt quyền kích hoạt từ Chat:** Tin nhắn do Member gửi đi hợp lệ có thể kích hoạt kỳ `CHO_KICH_HOAT`. Hành động PT chủ động gửi tin, đọc lịch sử, subscribe kênh hoặc mở màn hình chat **TUYỆT ĐỐI KHÔNG kích hoạt kỳ hạn** của Member.
- Trạng thái chuyển từ `CHO_KICH_HOAT` sang `DANG_HOAT_DONG`.

### RULE GYM 03 – Các Trạng thái Chuẩn của Membership
- **RULE GYM 03 cam kết tối thiểu 5 trạng thái:** `CHO_THANH_TOAN`, `CHO_KICH_HOAT`, `DANG_HOAT_DONG`, `HET_HAN`, `HUY`.
- **Trạng thái `CHO_DEN_LUOT`:** Được định nghĩa và ràng buộc chính thức tại `TU_DIEN_DU_LIEU.md` (Bảng 19: `ky_han_hoi_vien`, dòng 721, 758) và migration `2026_08_29_000018_m018_tao_ky_han_hoi_vien.php` (ràng buộc CHECK `kiem_tra_b18_01`), áp dụng cho kỳ hạn nối tiếp (tail) trong chuỗi đang chờ đến lượt sau khi kỳ đầu được kích hoạt.
  - `CHO_THANH_TOAN`: Đã tạo đơn, chưa thanh toán thành công; chưa cấp quyền.
  - `CHO_KICH_HOAT`: Đã thanh toán, kỳ đầu đang chờ lần sử dụng quyền lợi đầu tiên.
  - `CHO_DEN_LUOT`: Đã thanh toán, kỳ nối tiếp sau (tail) đang chờ đến lượt khi kỳ trước kết thúc.
  - `DANG_HOAT_DONG`: Đang trong thời hạn hiệu lực `[ngay_bat_dau, ngay_ket_thuc)`.
  - `HET_HAN`: Đã qua mốc `ngay_ket_thuc`.
  - `HUY`: Bị hủy do Admin xử lý vi phạm hoặc điều chỉnh đặc biệt.
- Tuyệt đối không dùng một boolean duy nhất để mô tả toàn bộ vòng đời; không sử dụng trạng thái không tồn tại như `TAM_DUNG`.

### RULE GYM 08 – Chỉ Backend được kích hoạt Membership
- Tuyệt đối cấm Client (Mobile/Web) gửi timestamp kích hoạt lên server.
- Mọi logic kích hoạt phải do Backend tự động xác thực và cập nhật trong database transaction khi có event sử dụng quyền lợi đầu tiên.

### RULE GYM 10 – Membership Plan có cấu hình quyền lợi
- Mỗi gói tập (`goi_tap`) được cấu hình rõ ràng các quyền lợi thành phần trong bảng `quyen_loi_goi_tap`:
  - `cho_phep_vao_phong_tap` (quyền vào phòng gym)
  - `cho_phep_tro_ly_tap_luyen` (quyền sử dụng trợ lý AI)
  - `gioi_han_luot_tro_ly` (hạn ngạch request AI được cấu hình theo gói, snapshot theo từng kỳ trong `ky_han_hoi_vien`; `NULL` = không giới hạn; không tự gán chu kỳ "mỗi ngày" nếu cấu hình không quy định)
  - `cho_phep_tro_chuyen_huan_luyen_vien` (quyền chat 1-1 với PT)
  - `so_buoi_huan_luyen_vien` (số buổi tập trực tiếp cùng PT)

### RULE GYM 20 – Không dùng Cron giảm số ngày còn lại
- Tuyệt đối không dùng Cron Job chạy hàng đêm để trừ dần cột `so_ngay_con_lai`.
- Quyền còn hiệu lực được Backend phân giải động từ `ngay_bat_dau`, `ngay_ket_thuc` và thời điểm kiểm tra theo contract hiện hành; không tự thêm công thức hiển thị hoặc một bộ đếm client.

---

## 2. Chuỗi Kỳ hạn & Gia hạn (Chaining & Renewal Rules)

### RULE GYM 04 – Gia hạn phải giữ thời gian còn dư và tách quyền lợi
- Gia hạn khi kỳ hiện tại đang hoạt động phải bảo toàn 100% thời gian còn dư của kỳ hiện tại.
- Backend tạo một bản ghi `ky_han_hoi_vien` mới trong trạng thái queued `CHO_DEN_LUOT`; không dùng `CHO_KICH_HOAT` cho tail của một active chain.
- Thời gian được cộng nối tiếp, nhưng quyền lợi của kỳ mới chỉ có hiệu lực sau khi kỳ hiện tại kết thúc.

### RULE GYM 05 – Gia hạn khi đang hoạt động
- Nếu kỳ hiện tại còn hiệu lực, lần mua mới tạo một kỳ nối tiếp sau kỳ cuối đã mua trong chuỗi:
  $$\text{ky\_moi.ngay\_bat\_dau} = \text{ky\_truoc.ngay\_ket\_thuc}$$
  $$\text{ky\_moi.ngay\_ket\_thuc} = \text{ky\_moi.ngay\_bat\_dau} + \text{ky\_moi.thoi\_han\_ngay}$$
  *(Tuyệt đối không cộng thêm 1 giây vào mốc bắt đầu).*
- Khi kỳ trước kết thúc (`HET_HAN`), kỳ tiếp theo trong chuỗi tự động chuyển trạng thái sang `DANG_HOAT_DONG`.
- Quyền lợi của kỳ sau không có hiệu lực trước khi kỳ sau chính thức bắt đầu.
- Việc chuyển kỳ và entitlement ở ranh giới thời gian phải do Backend resolve theo thời điểm thực tế, không phụ thuộc duy nhất vào một background status updater.

### RULE GYM 06 – Mua thêm gói khi chưa kích hoạt kỳ trước
- Mua thêm nhiều kỳ khi chưa kích hoạt kỳ nào: kỳ đầu của chain ở `CHO_KICH_HOAT`; các kỳ tail tiếp theo ở `CHO_DEN_LUOT` và chỉ xếp hàng.
- Khi kỳ đầu tiên được kích hoạt, mốc kết thúc của kỳ đầu sẽ là mốc bắt đầu của kỳ thứ hai, đảm bảo thời hạn nối tiếp liền mạch.

### RULE GYM 07 – Mua lại sau khi chuỗi kỳ đã hết hạn
- Nếu toàn bộ các kỳ trước đã `HET_HAN`, việc mua gói mới bắt đầu một chuỗi kỳ hoàn toàn mới.
- Kỳ mới ở trạng thái `CHO_KICH_HOAT`, và chỉ bắt đầu tính giờ khi phát sinh lần dùng quyền lợi tiếp theo (theo RULE GYM 02).

### RULE GYM 21 – Một đồng hồ chung duy nhất
- Trong cùng một kỳ hạn hội viên, Gym, AI và PT dùng **chung một đồng hồ thời gian** (`ngay_bat_dau` và `ngay_ket_thuc`).
- Không tách riêng đồng hồ thời hạn Gym 30 ngày và PT 45 ngày trong cùng một gói.

### RULE GYM 22 – Quyền lợi theo kỳ, không dùng trước kỳ tương lai
- Mỗi kỳ hạn có hạn mức quyền lợi riêng (snapshot).
- Khi đang ở kỳ hiện tại, hội viên **cấm sử dụng trước** quyền lợi (buổi PT, quota AI) của kỳ tiếp theo, dù kỳ tiếp theo đã thanh toán tiền.

### RULE GYM 23 – Số buổi PT thuộc từng kỳ Membership
- Số buổi tập với PT được cấp theo từng kỳ hạn.
- **Không carry-over:** Số buổi PT chưa dùng hết khi kỳ hạn kết thúc sẽ tự động hết hạn, không được cộng dồn sang kỳ gia hạn tiếp theo.
- **Q06:** PT phải xác nhận hợp lệ khi đúng kỳ của buổi còn hiệu lực và còn quota (hoặc kỳ đầu được phép kích hoạt theo rule chung). Kỳ đã hết trước xác nhận thì từ chối, không dùng thời điểm hoàn thành để backdate/trừ vào kỳ cũ, không mượn kỳ sau hoặc chọn kỳ khác còn lượt. Member tự gửi request xác nhận hoàn thành bị từ chối.

---

## 3. Snapshot, Đơn hàng & Cổng thanh toán (payOS)

### Q01 – Snapshot đơn hàng và quyền lợi tại thời điểm mua
- Khi tạo đơn mua (`don_mua_goi`), hệ thống chốt giá, thời hạn (`thoi_han_ngay`) và snapshot toàn bộ 5 cấu hình quyền lợi gói (`cho_phep_vao_phong_tap`, `cho_phep_tro_ly_tap_luyen`, `gioi_han_luot_tro_ly`, `cho_phep_tro_chuyen_huan_luyen_vien`, `so_buoi_huan_luyen_vien`) vào `ky_han_hoi_vien`.
- Khi Admin chỉnh sửa giá hoặc quyền lợi của gói gốc (`goi_tap`), các kỳ hạn đã mua trước đó **không bị ảnh hưởng** (hiển thị rõ cảnh báo này trên Web Admin Catalog).

### Q02 – Thứ tự xếp chuỗi kỳ theo xác nhận thanh toán hợp lệ
- Thứ tự mua dùng để xếp kỳ là thứ tự Backend **lần đầu xác nhận thanh toán hợp lệ**, được ghi dưới khóa Member/chuỗi và cùng Database Transaction cấp kỳ.
- Dùng chính xác: `lan_thanh_toan.xac_nhan_luc`, `ky_han_hoi_vien.mua_luc` và `so_thu_tu`. Nếu mốc thời gian trùng nhau thì thứ tự khóa/cấp `so_thu_tu` quyết định.
- Cơ chế retry không làm thay đổi mốc/thứ tự đã cấp. Tuyệt đối không dựa vào thứ tự tạo đơn, thời điểm client/returnUrl hoặc chèn ngược kỳ vào chuỗi đã bắt đầu/đã sử dụng.

### RULE GYM 18 – Webhook payOS phải Idempotent
- Webhook nhận thông báo thanh toán từ payOS phải được xử lý an toàn:
  - Nếu payOS gửi lại cùng một webhook nhiều lần cho cùng một mã giao dịch (`orderCode`), Backend phải nhận biết giao dịch đã xử lý và kết thúc theo contract idempotent mà không tạo thêm kỳ hạn trùng lặp.
  - Bắt buộc kiểm tra chữ ký số (`signature`) của payOS trước khi cập nhật dữ liệu.

### Q10 – Xử lý Sai số Thanh toán, Chữ ký không hợp lệ & Đối soát
- **Payload không xác thực (chữ ký số sai hoặc không hợp lệ):** Backend **từ chối ngay lập tức (reject)** và lưu dấu vết audit; **tuyệt đối không được coi là tiền hợp lệ** và không được chuyển tự động vào luồng xử lý nạp tiền.
- **Khoản thanh toán bất thường đã xác thực:** Khách chuyển thiếu/thừa tiền, thanh toán sau khi link hết hạn/hủy, hoặc thông tin thanh toán không khớp $\rightarrow$ Ghi nhận trạng thái `CAN_DOI_SOAT` tại sự kiện/lần thanh toán liên quan. **Tuyệt đối không cấp kỳ kép, không cộng thêm ngày, không tự ý hoàn tiền, không xóa giao dịch, và không bỏ qua webhook.**

### RULE GYM 19 – Lưu lịch sử mua và snapshot từng kỳ
- Mọi giao dịch mua mới, gia hạn đều phải ghi nhận lịch sử vào 3 bảng thanh toán riêng biệt: `don_mua_goi` (đơn mua), `lan_thanh_toan` (lần thanh toán / attempt), `su_kien_thanh_toan` (sự kiện thanh toán / webhook) và tạo kỳ trong `ky_han_hoi_vien`. Cấm ghi đè hoặc xóa record kỳ hạn cũ.
- Lượt dùng quyền lợi thực tế được log vào bảng `su_dung_quyen_loi`.
- **Admin Payment Web là READ-ONLY:** Tuyệt đối không cung cấp nút đánh dấu thành công, sửa số tiền, cộng Membership, refund hay xóa event.

---

## 4. Quyền lợi Độc lập & Hạn Check-in QR

### RULE GYM 09 – Chat PT và Buổi PT là hai quyền độc lập
- Gói tập có thể cấu hình có quyền Chat PT (`cho_phep_tro_chuyen_huan_luyen_vien = 1`) nhưng không có buổi PT trực tiếp (`so_buoi_huan_luyen_vien = 0`), hoặc ngược lại.
- Hành động gửi tin nhắn chat trao đổi với PT **tuyệt đối không tự động trừ lượt buổi tập PT**.

### RULE GYM 25 & Q09 – Thẩm quyền Hạn hiệu lực mã QR vào phòng tập
- Mã QR check-in do Member tạo trên Mobile lưu vào bảng `ma_vao_phong_tap`. Thời gian sống (TTL) mặc định là **90 giây** theo policy/cấu hình tập trung của hệ thống (`het_han_luc = phat_hanh_luc + 90s`).
- **Trường `het_han_luc` do server xác thực là cơ quan thẩm quyền duy nhất:** Mã hợp lệ khi thời điểm kiểm tra tại Backend $<$ `het_han_luc`; đúng hạn hoặc quá hạn đều bị từ chối. Client tuyệt đối không tự tính toán tính hợp lệ.
- **Tiêu thụ một lần (Single-use):** Khi check-in thành công, hệ thống ghi nhận vào bảng `lich_su_vao_phong_tap` (ràng buộc `ma_vao_phong_tap_id UNIQUE` theo `TU_DIEN_DU_LIEU.md` Bảng 22, dòng 843, 852 và migration `2026_08_29_000021_m021_tao_lich_su_vao_phong_tap.php`) và cập nhật mốc `da_su_dung_luc` trên `ma_vao_phong_tap` (theo `TU_DIEN_DU_LIEU.md` Bảng 21, dòng 814 và migration `2026_08_29_000020_m020_tao_ma_vao_phong_tap.php`). Quét lại mã QR đã tiêu thụ bị từ chối bằng controlled conflict; không tạo thêm check-in hay usage mới (không dùng enum trạng thái tự chế `DA_SU_DUNG`).
- Backend kiểm tra điều kiện tại thời điểm quét: mã QR chưa tiêu thụ, chưa thu hồi, còn trong hạn server + kỳ hạn hội viên có quyền Gym đang `DANG_HOAT_DONG`, hoặc là head term hợp lệ ở `CHO_KICH_HOAT` đang được phép kích hoạt. QR không được chọn kỳ `CHO_DEN_LUOT` hoặc mượn entitlement tương lai.
