# Đề xuất Database/ERD — Smart Fitness Platform

**Trạng thái: BẢN THIẾT KẾ ĐỂ REVIEW, CHƯA ĐƯỢC DUYỆT TRIỂN KHAI.** Ngày lập: 28/08/2026.

Thiết kế đã được đối chiếu toàn bộ [PROJECT_RULES.md](../../PROJECT_RULES.md) và cập nhật theo bốn quyết định mới của chủ dự án. Lần cập nhật này chỉ sửa quy tắc, tài liệu thiết kế và ERD; không sửa source BE/FE/Mobile, không tạo/chạy SQL, migration hoặc seed.

## 1. Kết quả bàn giao và cách đọc

- [Từ điển dữ liệu đầy đủ](TU_DIEN_DU_LIEU.md): 52 bảng, tất cả cột, kiểu, nullability, PK/FK/UNIQUE, CHECK dự kiến, index và quan hệ.
- [ERD theo mẫu được chọn](smart_fitness_erd_theo_mau.drawio): 9 trang, gồm toàn bộ 52 bảng và 8 trang theo nhóm; PK/FK riêng, giữ tên Việt không dấu. Bảng xám chỉ là tham chiếu, không phải bảng mới.
- [ERD chi tiết có kiểu dữ liệu](smart_fitness_erd.drawio): các trang tổng quan theo luồng và một trang chi tiết cho mỗi bảng. Các hộp tham chiếu ở bên phải trang chi tiết là cùng bảng xuất hiện ở trang khác, không phải bảng mới.
- Bản này là **đề xuất mô hình vật lý**, không phải bằng chứng đã tạo/chạy Database.
- Các mục ghi **CẦN CHỐT** là điều kiện thiết kế còn thiếu trong đặc tả. Không tự biến đề xuất ở đây thành Business Rule chính thức.

### Bốn quyết định đã chốt ở lần review này

| Quyết định chính thức | Tác động thiết kế |
| --- | --- |
| Chat PT độc lập số buổi PT | Cờ `cho_phep_tro_chuyen_huan_luyen_vien BOOLEAN` và `so_buoi_huan_luyen_vien SMALLINT UNSIGNED` lưu riêng ở catalog và snapshot kỳ. ONLINE có Chat nhưng 0 buổi là hợp lệ; không thêm cờ quyền buổi. |
| Thu hồi/cấp lại Role | Giữ UNIQUE cặp; UPDATE hàng cũ khi cấp lại, lịch sử nằm ở audit cùng transaction. |
| Workout chỉ từ lịch | Giữ FK `phien_tap.buoi_tap_du_kien_id` NOT NULL; không có Free Workout ngoài lịch trong MVP. |
| Chat chỉ ghi usage khi kích hoạt | Tin Member thực sự kích hoạt kỳ mới có usage Chat; các tin sau hoặc kỳ đã kích hoạt bằng luồng khác có FK usage NULL, không quota message. |

Đây là các quyết định đã được đưa vào PROJECT_RULES; không đồng nghĩa toàn bộ mô hình hoặc các câu hỏi còn lại đã được duyệt.

## 2. Phạm vi và cách phân rã

Chỉ thiết kế CORE: tài khoản, phân quyền, hồ sơ, thể chất, gói và kỳ quyền lợi, payOS, QR, PT, bài tập/giáo án/kế hoạch/phiên bản/lịch/phiên tập/hiệp, chat, AI và Proposal, audit/idempotency. Có `chi_nhanh` nhưng chỉ vận hành một chi nhánh. Không có Free Workout ngoài lịch, PT Booking, bảo trì/tài sản, HR/lương, chuyển nhượng/hoàn tiền/carry-over, medical/nutrition AI, group/file/voice/video chat hoặc BI.

52 bảng gồm cả bảng liên kết, snapshot và bảng kỹ thuật bảo vệ CORE; không phải 52 module. Progress/Dashboard là truy vấn hoặc dữ liệu dẫn xuất, không cần thêm bảng kết quả tổng hợp ở MVP.

### Các quyết định cấu trúc chính

| Vấn đề | Đề xuất |
| --- | --- |
| Vai trò và quyền trả phí | Role xác định actor; quyền dịch vụ đọc snapshot **đúng kỳ**, kèm ownership/phân công PT. Không có `is_premium`. |
| Membership | `dang_ky_goi_tap` = một chuỗi liên tục; `ky_han_hoi_vien` = từng kỳ có snapshot riêng. |
| Đơn chưa trả tiền | Tạo kỳ dự kiến `CHO_THANH_TOAN` cùng đơn để giữ báo giá; chưa thuộc chuỗi, chưa có thứ tự, không cấp quyền. Đây là lựa chọn lưu trữ, không kích hoạt Membership. |
| Kích hoạt | Một `su_dung_quyen_loi` hợp lệ là nguồn kích hoạt duy nhất của chuỗi; AI/PT/QR dùng chung khóa và đồng hồ. |
| Chat PT và hạn mức buổi | Cờ Chat theo thời hạn độc lập tổng buổi trực tiếp + counter + ledger; chat không trừ lượt và không đếm message. |
| Plan và History | Version/ngày/bài kê tập tách với phiên/bài thực tế/hiệp. Catalog, Template, Plan đều không làm đổi History. |
| AI/PT Proposal | Một `de_xuat_ke_hoach_tap` dùng chung workflow, phân biệt nguồn, người tạo và nguồn dữ liệu. PT không giả thành lần gọi LLM. |
| Realtime | DB là nguồn lịch sử. Sequence theo hội thoại + outbox + client dedup; WebSocket chỉ truyền sự kiện. |
| Định danh/kiểu | Theo phần 3; không dùng tên PT/AI/QR viết tắt trong tên bảng/cột mới. |

## 3. Quy ước vật lý đề xuất

- MySQL **8.4 LTS**, InnoDB, `utf8mb4`; đây là đích thiết kế, chưa cài/chuyển phiên bản MySQL trên máy.
- Bảng/cột tiếng Việt không dấu, snake_case, không viết tắt nghiệp vụ. `id` và hậu tố `_id` giữ theo ví dụ được phép tại RULE CODE 03; các từ như `thu_dien_tu`, `huan_luyen_vien`, `tro_ly`, `vao_phong_tap` thay tên tiếng Anh/viết tắt. Không đổi tên trường của API bên thứ ba; chỉ ánh xạ khi nhập/xuất.
- PK `id BIGINT UNSIGNED` tự tăng. Khóa gửi ra API/QR/idempotency có thể là mã ngẫu nhiên riêng; ID khó đoán không thay Authorization.
- Tất cả FK số phải cùng kiểu với PK; mỗi FK có index bắt đầu bằng cột FK nếu chưa được PK/UNIQUE/index ghép bao phủ.
- `DATETIME(6)` lưu UTC cho thời điểm; `DATE` lưu ngày lịch tập tại múi giờ chi nhánh. Chuỗi Membership dùng khoảng nửa mở **[ngay_bat_dau, ngay_ket_thuc)**; cộng số ngày từ mốc kích hoạt, không inclusive cả hai đầu.
- Tiền VND: `DECIMAL(15,0)`; cân nặng/tạ: `DECIMAL`, không FLOAT. Số điện thoại là chuỗi.
- `VARCHAR` trạng thái + CHECK tập giá trị khi triển khai; không chỉ BOOLEAN. Tên/giá trị trạng thái nội bộ có thể tiếng Việt không dấu, mã actor MEMBER/PT/RECEPTIONIST/ADMIN giữ theo đặc tả.
- Mã/hash/token/idempotency/reference ngoài hệ thống dùng collation ASCII/binary phân biệt chính xác; tên/mô tả dùng Unicode. Email chuẩn hóa theo chính sách đăng nhập rồi UNIQUE.
- `ngay_tao`, `ngay_cap_nhat` được liệt kê rõ ở từng bảng. Ledger/snapshot append-only không có `ngay_cap_nhat`. Các bảng con Workout dù có cột cập nhật vẫn bị khóa khi phiên cha hoàn thành.
- Mặc định **ON DELETE RESTRICT, ON UPDATE RESTRICT**; không cascade xóa giao dịch, kỳ, snapshot, phiên bản, phiên tập, hiệp hoặc chat. Khóa/ngừng sử dụng catalog/account thay vì xóa vật lý. Quy trình xóa/anonymize và thời hạn retention cần chốt riêng.
- Cột FK nullable dùng khi quan hệ chưa hình thành hoặc có nguồn tùy chọn; không dùng 0 làm giả NULL. Các cặp nullable phải được kiểm tra cùng nhau trong service; composite FK bị bỏ qua khi có phần NULL không tự bảo đảm workflow.
- UNIQUE cho phép nhiều NULL nên dùng được với liên kết chưa phát sinh. Cột sinh chỉ dựa vào dữ liệu của hàng, không dựa NOW(); trạng thái theo thời gian vẫn phải được tính lại khi kiểm tra quyền. [MySQL: UNIQUE index](https://dev.mysql.com/doc/refman/8.4/en/create-index.html)
- CHECK chỉ kiểm tra điều kiện trong một hàng, không bảo vệ chồng khoảng giữa nhiều kỳ, ownership qua nhiều bảng, tổng ledger, tính bất biến theo trạng thái cũ hay quan hệ phân công hiện thời. Dùng FK kép và transaction cho những việc đó; không mô tả CHECK chéo bảng như ràng buộc khả thi. [MySQL: CHECK constraints](https://dev.mysql.com/doc/refman/8.4/en/create-table-check-constraints.html)
- Ràng buộc hiển thị dạng các bộ cột trong từ điển; khi triển khai đặt tên tường minh tối đa 64 ký tự, không để tên tự sinh dài vượt giới hạn.
- Cặp FK vòng như Plan ↔ current Version hoặc chuỗi ↔ hành động kích hoạt cần thứ tự ghi: tạo cha với con trỏ NULL, tạo con, cập nhật con trỏ trong **một transaction**. Không dựa vào deferred constraint hoặc tắt FK.

## 4. Membership: không gộp chuỗi, kỳ và giao dịch

### 4.1. Ý nghĩa các bảng

`goi_tap` + `quyen_loi_goi_tap` là catalog đang bán, được Admin thay đổi. `don_mua_goi` là cam kết mua một gói. `ky_han_hoi_vien` giữ nguyên tên/phiên bản/giá/thời hạn và quyền lợi đã chốt. Một `dang_ky_goi_tap` gom các kỳ nối tiếp trong cùng chuỗi; hội viên có thể có nhiều chuỗi lịch sử nhưng tối đa một chuỗi chưa khép.

Khuyến nghị không thêm bảng snapshot quyền lợi kỳ riêng: số quyền CORE hữu hạn và snapshot được đọc cùng thời gian kỳ nên lưu các cột có kiểu trực tiếp trong kỳ. Một snapshot không phải bản tham chiếu động tới quyền catalog. Cờ Chat và tổng buổi PT trực tiếp phải được chụp riêng; thay catalog hoặc gia hạn không đổi quyền của kỳ cũ.

### 4.2. Vòng đời

1. Tạo đơn + snapshot kỳ `CHO_THANH_TOAN`. Tất cả nguồn cấp quyền, chuỗi, thứ tự, mốc mua và mốc sử dụng còn NULL.
2. Webhook hợp lệ: khóa hội viên/đơn, kiểm tra đã cấp chưa, ghi Payment, gán kỳ vào chuỗi và cấp thứ tự đúng một lần.
3. Chuỗi chưa kích hoạt: head `CHO_KICH_HOAT`, tail `CHO_DEN_LUOT`; các kỳ đã trả tiền vẫn chưa có ngày.
4. Hành động trả phí đầu tiên phải thuộc **head**. Kích hoạt head tại mốc server, tính liên tiếp ngày cho tất cả tail đã mua. Các cờ trạng thái tail không cấp quyền trước thời điểm.
5. Gia hạn trong khi chuỗi còn thời gian: nối sau kỳ cuối, bao gồm các kỳ tail đã xếp. Không kéo dài bộ quyền của kỳ hiện tại.
6. Đến ranh giới kỳ: quyền chuyển tự động theo thời gian; không đợi cron, check-in hoặc request AI/PT lần nữa.
7. Toàn bộ chuỗi hết hạn và không còn kỳ nối tiếp: lần mua mới tạo chuỗi mới chờ kích hoạt, không cộng về ngày hết hạn cũ.

Trạng thái `CHO_THANH_TOAN` và `HUY` được giữ trong vòng đời. `CHO_DEN_LUOT` là trạng thái bổ sung để phân biệt kỳ tail đã mua với head chờ lần dùng đầu tiên. Không triển khai hủy chuỗi đã trả tiền/hoàn tiền chỉ vì có enum `HUY`.

### 4.3. Điều kiện thời gian và quyền

Kỳ hiệu lực thỏa mãn: có payment hợp lệ, thuộc hội viên/chi nhánh, không bị hủy, và `ngay_bat_dau <= thoi_diem_kiem_tra < ngay_ket_thuc`. Nếu không có kỳ hiệu lực thì chỉ được xét head thực sự chờ kích hoạt; không tìm kỳ nào đó trong tail có cờ quyền phù hợp.

Ví dụ BASIC còn 10 ngày, mua PLUS 30 ngày: 10 ngày đầu chỉ quyền BASIC; sau đó 30 ngày PLUS. AI ở PLUS không thể mở ngay khi BASIC không có AI. Nếu PLUS có bốn buổi PT, kỳ PLUS bắt đầu với bốn buổi dù kỳ trước còn hai buổi chưa dùng.

### 4.4. Kích hoạt đồng thời và gia hạn đồng thời

Mọi luồng dùng chung khóa theo hội viên, sau đó chuỗi/kỳ cần thiết. Xác minh người gọi, quyền, kỳ đầu, quota/phân công/QR trước khi tạo hành động. Với hành động kích hoạt, ghi `su_dung_quyen_loi`, nguồn chuỗi, ngày các kỳ và domain record cùng transaction. Luồng đến sau đọc lại mốc đã commit, không cộng ngày lại. Riêng Chat khi kỳ đã hoạt động không tạo usage mới; QR/buổi PT/request AI vẫn giữ dấu vết theo nghiệp vụ tương ứng.

UNIQUE chuỗi đang mở bảo vệ một chuỗi chưa khép; UNIQUE (chuỗi, thứ tự), kỳ/đơn và kỳ/payment chống cấp trùng. **Không có UNIQUE đơn giản nào tự ngăn hai khoảng thời gian chồng lấn**; việc cấp thứ tự, nối thời gian và kiểm tra overlap nằm trong transaction dưới khóa hội viên. [MySQL: locking reads](https://dev.mysql.com/doc/refman/8.4/en/innodb-locking-reads.html)

Thứ tự mua đối với webhook về đảo thứ tự chưa được định nghĩa tuyệt đối trong rules. Đề xuất khóa hội viên và lấy thứ tự khi Backend lần đầu xác nhận thanh toán hợp lệ; không chèn ngược một kỳ vào chuỗi đã sử dụng. Nếu muốn ưu tiên thời điểm ngân hàng hoặc thời điểm tạo đơn, phải chốt quy tắc đối soát riêng trước triển khai.

## 5. payOS và dữ liệu thanh toán

### 5.1. Ánh xạ dữ liệu ngoài hệ thống

| Trường payOS | Cột nội bộ |
| --- | --- |
| orderCode | ma_don_cong_thanh_toan |
| paymentLinkId | ma_lien_ket_thanh_toan |
| reference | ma_tham_chieu / ma_tham_chieu_duoc_chap_nhan |
| amount / currency | so_tien / don_vi_tien |
| checkoutUrl | duong_dan_thanh_toan |
| transactionDateTime | thanh_toan_luc sau chuẩn hóa timezone |
| signature | Xác minh Backend; chỉ lưu kết quả/hash phù hợp, không lưu checksum key |

Đây là ánh xạ theo [payOS API](https://payos.vn/docs/api/) và [webhook payOS](https://payos.vn/docs/du-lieu-tra-ve/webhook/), không sửa key của payload bên ngoài.

### 5.2. Xử lý an toàn

- Tạo lần thử với orderCode ổn định; gọi gateway ngoài transaction. Nếu timeout, truy vấn lại cùng mã trước khi tạo mã/lần thử mới.
- Webhook xác minh chữ ký và kiểm tra orderCode, paymentLinkId, kênh merchant, số tiền, tiền tệ, trạng thái thành công và đơn tương ứng. Payload đúng chữ ký nhưng sai số tiền vẫn không cấp quyền.
- `khoa_chong_lap` của inbox chỉ được gán **sau** xác thực. Payload giả không được chiếm khóa khiến webhook thật bị bỏ qua.
- Khi xác nhận: khóa hội viên → đơn → lần thử → kỳ; nếu kỳ đã được cấp thì trả kết quả xử lý cũ. UNIQUE kỳ/đơn và reference là hàng rào cuối.
- Hai link/lần thử của cùng đơn cùng nhận tiền: lưu cả sự kiện/giao dịch cần đối soát, chỉ có **một kỳ được cấp**. Không tự cộng thêm thời hạn và không tự hoàn tiền.
- returnUrl, client báo thành công hoặc hủy chỉ phục vụ UX; không là nguồn xác nhận/cancel tài chính cuối cùng.
- Sai chữ ký, không tìm đơn, sai tiền, trả tiền trễ/sau hủy, thiếu reference, lỗi xác định thời gian hoặc sự kiện lỗi phải có trạng thái/ly_do rõ. Khoản bất thường giữ `CAN_DOI_SOAT`, không mất dấu.
- Một webhook được xử lý 10 lần vẫn chỉ cấp một kỳ. Nếu lỗi DB sau xác minh, rollback domain và cho retry inbox; chỉ báo đã xử lý thành công khi transaction đã commit.
- Không gọi LLM/Reverb/payOS trong transaction dài; không lưu API/checksum key trong bảng.

**CẦN CHỐT:** giá được giữ bao lâu cho đơn chờ; cách xếp hàng khi webhook đảo thứ tự; xử lý trả tiền sau hạn/hủy. Đề xuất giữ nguyên giá snapshot đến hạn đơn và cách ly khoản bất thường để đối soát, chưa thêm workflow refund.

## 6. QR check-in

QR phòng tập là token ngẫu nhiên riêng, không phải QR payOS. DB lưu hash, hội viên, chi nhánh, TTL, thu hồi và thời điểm đã dùng. Phát hành QR chỉ tạo mã, **không kích hoạt Membership**.

Khi Receptionist quét: tra token/hash, xác minh nhân viên/chi nhánh, ownership, hạn dùng và chưa tiêu thụ; khóa hội viên/kỳ rồi QR theo thứ tự thống nhất; kiểm tra quyền gym của kỳ tại thời điểm quét; ghi hành động, activation nếu cần, check-in và tiêu thụ QR cùng transaction. UNIQUE QR trong history chống hai nhân viên quét đồng thời tạo hai lượt.

Kỳ được chọn ở lúc quét, không đóng băng ở lúc tạo QR, để QR phát hành gần ranh giới kỳ vẫn được xét đúng. Không tin ngày Membership/ID nhân viên do client gửi. Không áp đặt giới hạn một check-in/ngày nếu chưa được chốt. TTL cụ thể và xử lý nhiều QR cùng còn hạn là tham số cần duyệt.

## 7. PT: phân công, quyền, lượt và Proposal

`phan_cong_huan_luyen_vien` lưu khoảng thời gian phụ trách, không gắn cứng vào một kỳ. Có phân công không tự có quyền PT; có quyền PT không tự có phân công.

**ĐÃ CHỐT:** Chat dùng cờ `cho_phep_tro_chuyen_huan_luyen_vien` trong kỳ; buổi PT trực tiếp dùng `so_buoi_huan_luyen_vien > 0` và còn lượt. Hai quyền độc lập: Chat với 0 buổi/hết lượt vẫn hợp lệ; có buổi không tự mở Chat. Cả hai đều cần phân công và đúng kỳ; không cần thêm boolean quyền buổi.

MVP chỉ ghi ledger **buổi PT trực tiếp 1-1 đã hoàn thành do PT phụ trách xác nhận**. Không có booking, không trừ do chat, đặt lịch hoặc hủy. Hàng ledger gắn đúng Member/PT/phân công/kỳ/hành động; `ma_buoi_huan_luyen` là định danh buổi ổn định, không sinh lại theo từng lần bấm.

Transaction khóa kỳ, kiểm tra còn lượt, ghi ledger và tăng counter đúng 1. Hai buổi khác nhau tranh lượt cuối chỉ một buổi được chấp nhận; retry cùng buổi trả bản ghi cũ. Tổng counter phải đối chiếu được bằng SUM ledger, không sửa một số dư không có dấu vết. Lượt dư hết hiệu lực theo kỳ, không carry-over.

Buổi PT trực tiếp và Workout Session là hai khái niệm, không bắt buộc mỗi ledger buổi PT phải có một phiên tập trong App. Tư vấn bằng Chat không phải buổi trực tiếp và không trừ quota buổi. Không lấy số phiên tập hoặc số tin nhắn làm số lượt PT.

Ghi chú tư vấn lưu ở bảng riêng, được tham chiếu Plan/Session nhưng không đổi dữ liệu kê tập/kết quả. Mọi thay đổi template/ngày/bài/sets/reps và cả tạo Plan mới bởi PT phải có Proposal + Member xác nhận. Khi Apply kiểm tra lại phân công hiện thời, quyền theo kỳ và version.

**CẦN CHỐT:** một hay nhiều PT cùng phụ trách một Member; xác nhận muộn buổi PT đã diễn ra ở kỳ trước; quyền nào cấp thao tác PT Proposal sau khi tách Chat và buổi (Q13). Chat với 0 buổi/hết lượt đã chốt, không còn là câu hỏi mở. Đề xuất không mượn lượt kỳ sau, không backdate activation/ledger để vượt hạn; hỗ trợ điều chỉnh của Admin/Receptionist không thuộc CORE khi chưa cấp quyền rõ.

## 8. Workout Template, Plan Version, Scheduled Workout và History

### 8.1. Ba tầng tách biệt

1. **Catalog/template:** mẫu và metadata được Admin sửa.
2. **Đơn kê chính thức:** Plan → Version → Ngày → Bài kê, snapshot bất biến.
3. **Thực tế:** Lịch cụ thể → Phiên tập → Bài thực tế → Hiệp, lưu kết quả thật.

Chọn template là sao chép sang version. Khi bắt đầu phiên, tiếp tục sao chép tên/hướng dẫn/dụng cụ và mục tiêu sang bài trong phiên. Giữ FK bài tập để thống kê nhưng hiển thị lịch sử bằng snapshot, không bằng tên/mục tiêu catalog hiện tại.

### 8.2. Version và lịch cụ thể

Không UPDATE nội dung version cũ. Apply tạo version mới, sao chép cây kê mới và đổi con trỏ hiện tại. Những lịch tương lai CHUA_TAP bị tác động được đánh dấu DA_THAY_THE, tạo hàng lịch mới cùng `ma_buoi_logic` và có FK tới hàng cũ. Bỏ một buổi thì đánh dấu HUY với audit; không xóa hàng.

Các lịch đã hoàn thành hoặc đang tập giữ version/ngày/đơn kê gốc. Ngay cả khi Plan hiện tại đã là V3, Session của V1 vẫn truy đúng V1. Khi chuyển ngày buổi tương lai, hàng cũ vẫn tái hiện được ngày cũ, không chỉ còn cây version mà mất lịch sử lịch hẹn.

Buổi mới của một version phải cùng Plan/Member, ngày kê phải thuộc đúng version: dùng FK kép để ngăn ghép một version hoặc ngày của người khác.

### 8.3. Workout Mode và khóa History

**ĐÃ CHỐT:** Member chỉ Start từ `buoi_tap_du_kien` đã có trong Plan/Schedule của chính mình và đủ điều kiện bắt đầu. FK lịch ở `phien_tap` giữ NOT NULL; không nhận danh sách bài tự do để tạo phiên hoặc tạo lịch giả. Free Workout ngoài lịch là FUTURE DEVELOPMENT. Điều này khác câu hỏi Workout có lịch có cần trả phí hay không (Q08).

Start idempotent trên lịch; mỗi bài/hiệp có ID ổn định. Save Set khóa phiên cha và kiểm tra DANG_TAP, expected revision + khóa request; dữ liệu request cũ không ghi đè set đã sửa mới hơn.

Complete khóa cùng phiên, xác minh ownership và toàn bộ set, chuyển HOAN_THANH cùng mốc kết thúc. Sau đó mọi add/update/delete bài/hiệp, đổi ngày, sửa reps/tạ/ghi chú kết quả đều bị từ chối. Không cho AI/PT/Admin đi vòng qua cùng cơ chế. Việc chỉ có FK/CHECK trong ERD **không tự làm các hàng bất biến**; service/policy và quyền ghi DB phải được thiết kế để mọi đường ghi tuân thủ khóa phiên.

Progress tính từ các phiên HOAN_THANH và hiệp thực tế, Body Measurement theo từng thời điểm. Không lấy 3×10 trong Plan giả làm ba hiệp thực hiện 10 reps. Membership hết hạn/mua gói mới không xóa/reset dữ liệu.

**CẦN CHỐT:** số Plan hoạt động đồng thời; có nhiều buổi/ngày không; bắt đầu lại phiên đã HUY; quyền tạo/ghi Workout khi không có kỳ còn hiệu lực. Thiết kế không tự cấp quyền mới; chỉ chắc chắn quyền xem lịch sử cá nhân vẫn giữ theo GYM 11–12.

## 9. Realtime Chat

Hội thoại là cặp Member/PT, UNIQUE cặp; không tạo cấu trúc group/member list đa năng. Mỗi lần gửi lưu quan hệ phân công hợp lệ tại thời điểm gửi. Đọc/gửi/subscribe/reconnect đều kiểm tra Backend; chỉ biết conversation ID không đủ quyền.

Mỗi hội thoại có sequence cấp dưới khóa trong transaction lưu tin. Không dựa vào timestamp client hay global auto-increment để kết luận tất cả tin nhỏ hơn cursor đã commit. Reconnect xin tin có sequence lớn hơn cursor; pagination cũ dùng sequence nhỏ hơn, luôn trong đúng hội thoại.

UNIQUE (hội thoại, người gửi, mã tin phía gửi) chống duplicate; cùng mã khác nội dung báo conflict. Tin và outbox commit cùng nhau, broadcast sau commit. Retry broadcast có thể trùng, client dedup theo message ID/sequence. Lưu DB bảo đảm reconnect lấy lại được tin nếu WebSocket bỏ lỡ.

**ĐÃ CHỐT:** gửi Chat đọc cờ `cho_phep_tro_chuyen_huan_luyen_vien` của đúng kỳ và phân công, không đọc số buổi còn lại để mở quyền. Không quota/counter số message.

- Kỳ đầu đang CHO_KICH_HOAT và Member gửi tin hợp lệ: ghi một usage TRO_CHUYEN_HUAN_LUYEN, nguồn kích hoạt chuỗi, mốc thời gian, tin và outbox cùng transaction. Tin đó có FK usage khác NULL.
- Kỳ đã hoạt động: vẫn kiểm tra cờ Chat/kỳ/phân công mỗi lần, lưu tin/outbox; không thêm usage, FK usage của tin mới là NULL. Không cùng tham chiếu usage kích hoạt cũ vì FK này UNIQUE.
- Nếu AI/QR/buổi PT đã kích hoạt trước, kể cả tin Chat đầu tiên cũng không tạo usage. Kỳ nối tiếp tự chạy không cần một usage Chat mới.
- Hai tin đầu đồng thời hoặc Chat tranh activation với AI/QR: cùng khóa hội viên/chuỗi/kỳ, kiểm tra lại sau khóa; chỉ thao tác thắng tạo nguồn activation. Retry trả lại tin/usage cũ.
- Lưu tin/outbox thất bại phải rollback usage/activation của chính transaction đó. Không thể có Membership bị kích hoạt bởi một tin chưa lưu.
- PT chủ động gửi/đọc/subscribe hoặc Member chỉ mở trang không kích hoạt. Chat không trừ buổi trực tiếp; lịch sử tin nằm ở tin_nhan, không nhân đôi thành usage cho mỗi message.

Không gửi trước tới một cuộc trò chuyện mà Member chưa có quyền dùng; cách trả lời công việc đang dở khi vừa hết kỳ cần review.

**CẦN CHỐT:** Member có được đọc chat PT cũ khi hết kỳ/đổi PT không; PT mới có được đọc hội thoại của PT cũ không (mặc định không suy rộng quyền). Khi mất phân công, server phải kiểm tra lại từng thao tác và ngăn phát nội dung tới kết nối không còn được phép; không chỉ kiểm tra một lần lúc subscribe.

## 10. Hybrid AI và Proposal

### 10.1. Tách dữ liệu AI

- Hội thoại/tin nhắn: trải nghiệm chat, không phải nguồn Plan.
- Request: một ý định nghiệp vụ, nguồn quyền/quota và ngữ cảnh.
- Candidate bài/giáo án: ID thật + snapshot hợp lệ được Rule Engine chọn.
- Lần gọi mô hình: provider/model/prompt/schema/version, số lần thử, token usage/lỗi.
- Proposal chung: chỉ sinh sau validation, chưa sửa dữ liệu chính thức.
- Version kết quả: chỉ xuất hiện sau Member xác nhận thành công.

Một request có nhiều lần gọi provider nhưng không vì retry mà bị trừ nhiều lượt. AI chỉ dùng planning/adjustment/replacement/explanation; không thêm y khoa, dinh dưỡng điều trị hoặc supplement.

### 10.2. Quota và kích hoạt

Đề xuất đặt quyền/quota trước khi gọi provider: xác minh kỳ/head/quyền AI, giữ chỗ một lượt dưới khóa kỳ, ghi hành động hợp lệ và kích hoạt nếu cần, commit rồi mới gọi LLM. Request bị từ chối do không có quyền/candidate/điều kiện cần thiết không kích hoạt.

Khi provider lỗi sau khi request hợp lệ đã được chấp nhận, đề xuất giải phóng quota nhưng không tự lùi lại đồng hồ đã kích hoạt. Retry cùng request dùng cùng mốc, không cộng ngày. **Đây là chính sách đề xuất, chưa chốt**; cần quyết định tiêu chí một lượt (request, lượt chat, Proposal), lỗi/chờ bổ sung, và thời điểm trả/giữ quota. Không đếm từng provider retry như một lượt dịch vụ.

### 10.3. Structured output và validation

Dữ liệu áp dụng được lưu ở `noi_dung_de_xuat JSON`, có schema version và hash nội dung đã preview. Schema nội bộ đề xuất gồm:

- loại thao tác và mốc hiệu lực;
- ID Plan/version cơ sở hoặc đánh dấu tạo mới;
- snapshot mục tiêu và nguồn giáo án;
- danh sách ngày kê (mã logic, thứ, tên, thời lượng);
- danh sách bài (ID thật, vị trí, sets, reps min/max, tạ mục tiêu nếu có, rest);
- danh sách lịch tương lai bị tác động với ID/mã buổi logic, thao tác thay/giữ/bỏ, ngày/giờ mới;
- giải thích/khác biệt cho người dùng.

Đây là hợp đồng dữ liệu, chưa viết JSON Schema hoặc code. Tên key nội bộ cũng tiếng Việt không dấu; key provider giữ đúng hợp đồng adapter.

JSON không có FK tới bài/ngày/lịch bên trong nên validation là bắt buộc: schema, ID tồn tại, nằm trong candidate, dụng cụ phù hợp, min/max reps, số hiệp/rest/ngày/thời lượng, lịch trùng, sở hữu, nguồn PT/AI và version. Trước Apply kiểm tra lại với catalog/điều kiện hiện tại. Dữ liệu JSON lỗi không được lưu thành Proposal; metadata lỗi/hash ở log kỹ thuật không phải đơn kê.

### 10.4. Xác nhận và xử lý xung đột

Frontend chỉ gửi ID Proposal và định danh xác nhận; không gửi lại payload AI để Backend tin dùng. Kiểm tra người xác nhận đúng Member, Proposal còn chờ/chưa hết hạn, nguồn còn hợp lệ, version cơ sở/mốc thay đổi kế hoạch và điều kiện tập.

Khóa hội viên → Plan/Proposal liên quan theo thứ tự quy định; kiểm tra lại sau khi đã khóa. Nếu hợp lệ: tạo version + cây ngày/bài + thay lịch tương lai, tăng mốc kế hoạch, đổi con trỏ, đánh dấu DA_AP_DUNG và audit trong một transaction. UNIQUE nguồn Proposal ở Version bảo vệ chỉ một kết quả. Confirm lặp trả lại version đã tạo.

AI/PT cùng xuất phát từ V2: Proposal A tạo V3; Proposal B không được ghi đè V3 bằng nội dung dựa V2. Chuyển XUNG_DOT và yêu cầu preview/xác nhận bản mới. Không sửa payload proposal đang chờ rồi tái dùng xác nhận cũ.

PT mất phân công trước confirm: từ chối. Member từ chối: không thay Plan. Explanation hoặc hỏi bổ sung không sinh version và không cần Proposal áp dụng dữ liệu.

## 11. Authorization, audit, transaction và index

| Luồng | Actor/scope cần kiểm tra | Dữ liệu khóa/ghi nguyên tử | Hàng rào lặp |
| --- | --- | --- | --- |
| Webhook cấp kỳ | Chữ ký + kênh + đơn/tiền/tiền tệ | Hội viên, đơn, lần thử, chuỗi, kỳ, audit | Reference, kỳ/đơn, kỳ/payment |
| Kích hoạt | Đúng Member/quyền kỳ + điều kiện từng luồng | Hội viên, chuỗi/kỳ, hành động, domain | Nguồn kích hoạt chuỗi, mã hành động |
| QR redeem | Receptionist/chi nhánh + QR/Member | Hội viên/kỳ, QR, check-in, audit | UNIQUE QR trong history |
| Ghi buổi PT | PT phụ trách + đúng kỳ + còn lượt | Hội viên/kỳ, ledger/counter, audit | ID buổi + UNIQUE hành động |
| Save Set/Complete | Chủ phiên + trạng thái + revision | Hội viên/phiên/bài/hiệp liên quan | ID set, idempotency, trạng thái hoàn thành |
| Apply AI/PT | Chủ đề xuất + nguồn quyền + version | Hội viên, Plan, Proposal, version/cây/lịch, audit | UNIQUE version/proposal + expected version |
| Gửi chat | Hai bên hợp lệ + phân công + quyền kỳ | Hội viên/kỳ nếu dùng quyền, hội thoại, tin, outbox | Cặp thread/sender/client message ID |
| AI request/quota | Member + head/kỳ/quyền + hạn mức | Hội viên/kỳ, giữ quota, request, hành động | Request ID; provider attempt riêng |

Khóa theo một thứ tự thống nhất cho các hàng cùng loại; tránh luồng QR khóa kỳ trước hội viên trong khi AI làm ngược. Transaction ngắn, retry deadlock có giới hạn bằng cùng idempotency key. Quyền có thể đổi trong lúc request chạy nên phải revalidate dưới khóa, không chỉ preflight.

Chỉ mục quan trọng: kỳ theo Member và khoảng thời gian; chuỗi/thứ tự kỳ; order/status; inbox/status; phân công theo PT/Member và khoảng hiệu lực; lịch theo Member/ngày; Session theo Member/ngày/trạng thái; hiệp theo bài trong phiên; tin theo conversation/sequence; Proposal theo Member/status; request AI theo kỳ/quota state. Unique reference/hash/token dùng so sánh chính xác.

Audit là append-only và chỉ chứa dữ liệu cần thiết. Ledger PT, kỳ nguồn thanh toán, nguồn kích hoạt và version/proposal đều có FK thật, không chỉ một audit đa hình. Dashboard/progress là đọc/tổng hợp, không có workflow ghi số dư.

### Cấp lại Role và lịch sử audit

Giữ UNIQUE `(nguoi_dung_id, vai_tro_id)`; hàng phân quyền là trạng thái hiện tại. Thu hồi gán thu_hoi_luc. Cấp lại UPDATE cùng id, cap_luc/nguoi_cap_id mới và thu_hoi_luc=NULL; giữ ngay_tao, cập nhật ngay_cap_nhat. Cấp/thu hồi/cấp lại và audit trước/sau phải commit/rollback cùng transaction, retry không tạo audit trùng. Lịch sử Role đọc từ nhat_ky_he_thong; không thêm bảng lịch sử Role.

## 12. Các điểm cần chủ dự án chốt trước migration

| Mã | Điểm còn thiếu | Đề xuất để review, chưa là rule |
| --- | --- | --- |
| Q01 | Giá đơn đang chờ khi Admin sửa gói; hạn giữ giá | Snapshot lúc tạo đơn, giữ đến hạn thanh toán; không đổi giá/quyền của đơn cũ trong hạn. |
| Q02 | “Thứ tự mua” khi webhook lệch thứ tự hoặc đến muộn | Thứ tự Backend lần đầu xác nhận hợp lệ dưới khóa; không chèn ngược kỳ đã dùng. |
| Q03 | Lượt AI, reset, lỗi provider, hỏi bổ sung | Tính theo request dịch vụ, quota mỗi kỳ; giữ chỗ trước gọi; lỗi kỹ thuật trả quota; không reset đồng hồ. |
| Q04 | Một hay nhiều PT đồng thời | Schema hỗ trợ nhiều; không cho trùng khoảng cùng cặp. Chốt trước khi thêm UNIQUE một PT/Member nếu cần. |
| Q05 | Chat PT khi hết Membership/đổi PT | Chat với 0 buổi/hết lượt đã chốt là được phép khi cờ Chat/kỳ/phân công hợp lệ. Chỉ còn cần chốt đọc lịch sử và trả lời qua ranh giới kỳ/đổi PT. |
| Q06 | Buổi PT xác nhận sau khi kỳ cũ hết hạn | Mặc định từ chối cấp/trừ bằng kỳ khác; không backdate hoặc tự thêm correction workflow. |
| Q07 | Số Plan đang dùng; số buổi/ngày; phiên HUY | Không áp UNIQUE một Plan/ngày khi chưa chốt; đề xuất một phiên cho một lịch trong MVP. |
| Q08 | Quyền tạo/ghi Workout có lịch khi không có kỳ còn hiệu lực | Chưa suy ra quyền ghi/tạo mới; quyền đọc History hết hạn giữ nguyên. Free Workout ngoài lịch đã loại khỏi MVP, không phải câu hỏi miễn phí/trả phí này. |
| Q09 | QR TTL, nhiều mã đồng thời; Proposal TTL | TTL là cấu hình server; có thể đề xuất QR 90 giây, Proposal 24 giờ nhưng chưa hard-code vào thiết kế được duyệt. |
| Q10 | Tiền trễ/sai/đơn hủy/hai link cùng được trả | CAN_DOI_SOAT, không mất dấu tiền, không cấp kỳ kép và không tự hoàn tiền. |
| Q11 | Dụng cụ bắt buộc hay thay thế | Mỗi hàng dụng cụ là AND; nếu cần OR phải mô hình nhóm thay thế sau khi chốt. |
| Q12 | Retention dữ liệu cá nhân/chat/provider/audit | Chốt thời hạn và quy trình trước vận hành; không tự hard-delete dữ liệu lịch sử. |
| Q13 | Entitlement cho PT tạo/áp dụng Proposal sau khi tách Chat và buổi | Cần chốt quyền Chat, quyền buổi hay một trong hai cho workflow Proposal. Không tự gán quyền này từ cờ Chat hoặc tự trừ buổi vì tạo Proposal; giữ yêu cầu phân công, Member xác nhận và version. |

Các điểm này không ngăn việc review mô hình, nhưng phải được quyết định trước khi code luồng liên quan. Không chọn ngầm bằng migration.

## 13. Ma trận đối chiếu PROJECT_RULES

| Quy tắc | Nơi thể hiện |
| --- | --- |
| GYM 01–03, 07, 17 | Đơn + kỳ chờ + chuỗi mới + trạng thái đầy đủ; Payment khác Activation. |
| GYM 04–06, 19, 20, 22 | Snapshot từng kỳ, thứ tự nối tiếp, khoảng nửa mở, nguồn payment, không giảm ngày bằng cron. |
| GYM 08, 21 | su_dung_quyen_loi và một nguồn kích hoạt; khóa hội viên, không nhiều đồng hồ. |
| GYM 09–10 | Chat PT và quota buổi trực tiếp độc lập, snapshot riêng; không suy quyền theo tên gói. |
| GYM 11–12 | History/Progress/Body Measurement gắn Member, không bị cascade hoặc reset theo Membership. |
| GYM 13–16 | Version/lịch riêng, snapshot kết quả, khóa cây phiên HOAN_THANH. |
| GYM 18 | Inbox webhook, UNIQUE reference/kỳ/đơn, transaction cấp quyền. |
| GYM 23 | Ledger PT + counter theo kỳ + ID buổi + phân công; không booking/carry-over. |
| GYM 24 | Proposal PT, nguồn/người tạo, Member quyết định, version mới, ghi chú riêng. |
| Mục 17.1 | UNIQUE Role giữ nguyên; cấp lại UPDATE hàng hiện tại, audit lịch sử cùng transaction. |
| Mục 19–29, 46.1 | Thư viện → template → plan/version → lịch bắt buộc → phiên → bài → hiệp; Free Workout thuộc FUTURE DEVELOPMENT. |
| Mục 30, 44 | Progress/dashboard từ truy vấn dữ liệu lịch sử và giao dịch hợp lệ. |
| Mục 31–33.1 | Chat 1–1 theo cờ riêng, không quota tin; usage chỉ khi tin Member kích hoạt, sequence/outbox/authorization hiện thời. |
| Mục 34–43 | Request/candidate/provider/validation/Proposal/confirm/revalidate, chống apply lại. |
| CODE 01–04 | Toàn bộ tên bảng/cột Việt không dấu, FK rõ nghĩa; tên chính thức chờ duyệt. |
| CODE 13–17 | Không tin client/LLM, FK kép, transaction/idempotency/resource scope. |
| Mục 45–47 | Chỉ CORE; không tự thêm Booking/HR/assets/multi-branch operation/medical AI. |

## 14. Các tình huống phải kiểm thử khi triển khai sau này

Chưa chạy các test nghiệp vụ dưới đây vì chưa có schema/code; đây là tiêu chí nghiệm thu thiết kế:

1. Webhook 10 lần → đúng một kỳ; hai lần thanh toán cùng đơn → không cấp kép.
2. AI/PT/QR đồng thời trên head → một mốc activation; request bị từ chối không kích hoạt.
3. Chưa kích hoạt mua 30 + 60 ngày → hai snapshot, một hàng đợi; không dùng quyền tail.
4. Hai gia hạn đồng thời → không trùng thứ tự/không overlap; chuyển kỳ đúng biên đến microsecond.
5. BASIC còn 10 ngày + PLUS → không mở quyền PLUS sớm; chuỗi hết rồi mua mới vẫn chờ.
6. Hai PT confirmations tranh lượt cuối → không âm; retry cùng buổi → không trừ hai lần; chat → không trừ buổi.
7. Hai Receptionist dùng cùng QR → một check-in; QR sai/expired → không ghi quyền/activation.
8. Sửa Template/Exercise/Plan → History và version cũ giữ nguyên.
9. Save Set cùng lúc Complete → không có set được ghi sau phiên HOAN_THANH; retry không nhân set.
10. Hai Proposal cùng version → chỉ một thắng; mất phân công/hết TTL/khác chủ → từ chối.
11. LLM trả bài ngoài candidate, JSON lỗi, sai dụng cụ/reps/lịch → không có Proposal hợp lệ/Plan tự áp dụng.
12. Chat reconnect/retry/out-of-order broadcast → không mất/nhân tin; mất scope → không đọc/gửi/nhận dữ liệu trái quyền.
13. FK ghép sai Member–Order–Period, Plan–Version–Day, Conversation–Assignment → bị ràng buộc từ chối.
14. Hết Membership/mua gói khác → vẫn đọc đúng Workout/Payment/Body Measurement history.
15. ONLINE Chat=true, 0 buổi → chat được, ghi buổi trực tiếp bị từ chối; hết lượt không tắt Chat. Chat=false nhưng còn buổi → không chat, vẫn có thể ghi buổi trực tiếp hợp lệ.
16. Thu hồi rồi cấp lại Role → cùng id/cặp UNIQUE, cap_luc mới, thu_hoi_luc=NULL; audit giữ đủ hai sự kiện, retry không trùng.
17. Start không có lịch, lịch khác chủ hoặc không đủ điều kiện → không tạo phiên/lịch giả; FK lịch NOT NULL.
18. Tin Member kích hoạt + 20 tin sau → 21 tin, một usage Chat; nếu AI/QR/buổi PT kích hoạt trước → không thêm usage Chat.
19. Hai tin đầu đồng thời hoặc Chat/AI/QR tranh activation → một nguồn; lỗi tin/outbox rollback activation cùng transaction; mỗi tin sau vẫn revalidate quyền.

## 15. Bất biến workflow cần kiểm tra ngoài FK

- Kỳ CHO_THANH_TOAN không được có payment nguồn, chuỗi, thứ tự hoặc ngày sử dụng; kỳ đã cấp phải có payment thành công thuộc đúng đơn và cùng hội viên. Snapshot của đơn/kỳ không đổi sau khi chốt.
- Hành động kích hoạt phải thuộc kỳ số 1 của chính chuỗi; loại hành động phải khớp domain record (QR, buổi PT, tin nhắn Member hoặc request AI), cùng Member/kỳ và cùng mốc server. FK kép ngăn sai chủ nhưng không tự chứng minh đúng loại hành động hoặc kỳ đầu.
- Proposal nguồn TRO_LY phải có request AI và không có phân công PT; nguồn HUAN_LUYEN_VIEN phải có phân công và không có request AI. Tạo mới không có Plan/version cơ sở; điều chỉnh/thay bài phải có cả hai. Người tạo/quyết định phải khớp actor được phép, không chỉ tồn tại trong nguoi_dung.
- Phiên bản hiện tại/trước và phiên bản của Proposal phải thuộc cùng Plan. Chỉ một version được tạo từ một Proposal; DA_AP_DUNG phải có version kết quả và mốc quyết định/áp dụng. Không chuyển lại trạng thái chờ để dùng lại xác nhận.
- Request AI được giữ/tính quota phải có đúng kỳ và hành động trả phí; request bị từ chối trước quyền không được tạo nguồn kích hoạt. Counter phải đối chiếu được với request/ledger, không chỉ thỏa giới hạn số học.
- Tin nhắn phải do một trong hai người của hội thoại gửi; phân công phải cùng cặp và còn hiệu lực. su_dung_quyen_loi_id chỉ gắn khi tin Member thực sự kích hoạt kỳ đầu đang chờ; mọi tin sau, tin khi kỳ đã chạy và tin PT đều NULL. Không lấy số buổi làm quyền Chat, không tạo usage Chat cho từng tin.
- Lịch thay thế phải cùng Member và cùng mã buổi logic; chỉ lịch chưa bắt đầu được thay. Bài trong phiên lấy nguồn kê từ đúng lịch/version của phiên; không ghép bài của Plan khác. Khi phiên hoàn thành, cả cây kết quả và mốc thời gian là bất biến.

Các điều kiện trên được kiểm tra lại trong transaction hoặc policy/service tương ứng; chưa viết trigger, SQL, model hay test thực thi ở bước thiết kế này.

## 16. Kết luận review

Mô hình bảo toàn bốn loại lịch sử độc lập: **mua và quyền lợi theo kỳ; dùng dịch vụ; kế hoạch được phê duyệt theo phiên bản; kết quả thực tế**. AI và PT không có đường ghi tắt vào kết quả/Plan chính thức. Các ràng buộc trong từ điển là đề xuất để review, chưa phải migration đã chạy.

Chỉ sau khi chủ dự án duyệt mô hình và các Q01–Q13 liên quan mới phân tách thứ tự migration, model/adapter, API và test. Bước hiện tại không tạo migration, bảng, API hay chức năng.

## 17. Kiểm tra tài liệu và phạm vi thay đổi

- Bộ thiết kế giữ 52 bảng, 571 cột, 112 FK đơn và 30 FK kép; chỉ đổi tên/ý nghĩa cờ Chat ở catalog và snapshot, không thêm bảng/quota message.
- UNIQUE cặp Role giữ nguyên; FK lịch ở phiên tập vẫn NOT NULL; FK usage ở tin nhắn vẫn nullable + UNIQUE.
- Quyết định và ca kiểm thử thiết kế được đồng bộ trong PROJECT_RULES, từ điển và hai bản ERD. Bản theo mẫu có 9 trang, bản chi tiết có 60 trang.
- Không có migration, SQL/seed, API hoặc code chức năng được tạo/chạy trong lần cập nhật này. Các ca nghiệp vụ ở mục 14 là tiêu chí triển khai sau này, không phải test runtime đã chạy.
- Các Q còn mở chưa được biến thành quyền mặc định hoặc migration. Đặc biệt Q13 cần chốt trước khi triển khai entitlement PT Proposal.

### Kết quả kiểm tra sau cập nhật bốn quyết định

- PASS: XML của cả hai ERD parse được, đủ 52 bảng/571 cột/112 FK đơn; kiểu, NULL và đích FK khớp từ điển. 30 FK kép vẫn có UNIQUE đích phù hợp.
- PASS: chỉ thay cờ Chat ở hai bảng quyền lợi/kỳ; UNIQUE Role giữ nguyên, FK lịch NOT NULL, FK usage Chat nullable và UNIQUE. Không còn tên cờ PT gộp trong quy tắc/tài liệu/ERD hiện hành.
- PASS: bảng không chồng nhau, ghi chú mới không đè bảng, tên trường vừa ô; 139 tệp gốc ngoài PROJECT_RULES được phép cập nhật vẫn giữ SHA-256 cũ; số migration PHP bằng 0.
- Giới hạn preview lần này: xuất PNG qua Draw.io MCP gặp timeout hoặc trả dữ liệu sai định dạng. Không bàn giao PNG và không khẳng định đã kiểm tra ảnh của mọi trang sửa đổi. Các file .drawio được lưu thành công và kiểm tra cấu trúc; đây không phải test runtime nghiệp vụ.
