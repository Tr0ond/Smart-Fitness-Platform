# Thiết kế Database/ERD — Smart Fitness Platform

**Trạng thái: DATABASE DESIGN = APPROVED FOR MARIADB 10.4.32.** Cập nhật kiểm tra: 29/08/2026.

Q01–Q13 đã đồng bộ, không còn blocker thiết kế thuộc các quyết định này. Đây chỉ là phê duyệt thiết kế để lập kế hoạch migration, **chưa tạo/chạy migration**.

Thiết kế đã được đối chiếu toàn bộ [PROJECT_RULES.md](../../PROJECT_RULES.md), từ điển, hai ERD và cập nhật theo toàn bộ Q01–Q13 đã được chủ dự án chốt, đồng thời giữ các quyết định trước đó. Lần cập nhật này chỉ sửa quy tắc, tài liệu thiết kế và ERD; không sửa source BE/FE/Mobile, không tạo/chạy SQL, migration hoặc seed.

### Addendum hậu baseline — 10/09/2026 (M061)

Các số liệu và trạng thái “chưa tạo/chạy migration” ở phần baseline ngày 29/08/2026 vẫn được giữ nguyên như bằng chứng lịch sử. Sau khi được phép triển khai, M061 là thay đổi additive duy nhất cho B26: thêm `nhom_co.trang_thai VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_nopad_bin NOT NULL DEFAULT 'HOAT_DONG'` và CHECK có tên `kiem_tra_b26_01` (`HOAT_DONG`/`NGUNG_SU_DUNG`), không thêm bảng, FK hoặc index. Nhóm ngừng sử dụng được giữ để đọc; B29 hiện hữu không bị xóa/sửa, còn quan hệ mới chỉ nhận nhóm đang hoạt động. Down M061 chỉ dành cho schema thử nghiệm được phép vì sẽ bỏ trạng thái đã ghi.

## 1. Kết quả bàn giao và cách đọc

- [Từ điển dữ liệu đầy đủ](TU_DIEN_DU_LIEU.md): 52 bảng, tất cả cột, kiểu, nullability, PK/FK/UNIQUE, CHECK dự kiến, index và quan hệ.
- [ERD theo mẫu được chọn](smart_fitness_erd_theo_mau.drawio): 9 trang, gồm toàn bộ 52 bảng và 8 trang theo nhóm; PK/FK riêng, giữ tên Việt không dấu. Bảng xám chỉ là tham chiếu, không phải bảng mới.
- [ERD chi tiết có kiểu dữ liệu](smart_fitness_erd.drawio): các trang tổng quan theo luồng và một trang chi tiết cho mỗi bảng. Các hộp tham chiếu ở bên phải trang chi tiết là cùng bảng xuất hiện ở trang khác, không phải bảng mới.
- Bản này là mô hình vật lý ở bước thiết kế, không phải bằng chứng đã tạo/chạy Database.
- Q01–Q13 là quyết định chính thức, xem mục 12. Chỉ các chức năng Future Development ngoài phạm vi mới chưa triển khai.

### Bốn quyết định đã chốt trước vòng Q01–Q13

| Quyết định chính thức | Tác động thiết kế |
| --- | --- |
| Chat PT độc lập số buổi PT | Cờ `cho_phep_tro_chuyen_huan_luyen_vien BOOLEAN` và `so_buoi_huan_luyen_vien SMALLINT UNSIGNED` lưu riêng ở catalog và snapshot kỳ. ONLINE có Chat nhưng 0 buổi là hợp lệ; không thêm cờ quyền buổi. |
| Thu hồi/cấp lại Role | Giữ UNIQUE cặp; UPDATE hàng cũ khi cấp lại, lịch sử nằm ở audit cùng transaction. |
| Workout chỉ từ lịch | Giữ FK `phien_tap.buoi_tap_du_kien_id` NOT NULL; không có Free Workout ngoài lịch trong MVP. |
| Chat chỉ ghi usage khi kích hoạt | Tin Member thực sự kích hoạt kỳ mới có usage Chat; các tin sau hoặc kỳ đã kích hoạt bằng luồng khác có FK usage NULL, không quota message. |

Các quyết định trên tiếp tục có hiệu lực. Vòng Q01–Q13 bổ sung điều kiện về giá, thứ tự kỳ, quota, PT, Chat, Workout, TTL, đối soát, equipment và retention tại các mục tương ứng.

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

- MariaDB **10.4.32**, InnoDB, `utf8mb4`; đây là đích DBMS kỹ thuật chính thức. XAMPP cung cấp server phát triển ở cổng 3306; chưa tạo/chạy migration hoặc P0 trong vòng review này.
- Bảng/cột tiếng Việt không dấu, snake_case, không viết tắt nghiệp vụ. `id` và hậu tố `_id` giữ theo ví dụ được phép tại RULE CODE 03; các từ như `thu_dien_tu`, `huan_luyen_vien`, `tro_ly`, `vao_phong_tap` thay tên tiếng Anh/viết tắt. Không đổi tên trường của API bên thứ ba; chỉ ánh xạ khi nhập/xuất.
- PK `id BIGINT UNSIGNED` tự tăng. Khóa gửi ra API/QR/idempotency có thể là mã ngẫu nhiên riêng; ID khó đoán không thay Authorization.
- Tất cả FK số phải cùng kiểu với PK; mỗi FK có index bắt đầu bằng cột FK nếu chưa được PK/UNIQUE/index ghép bao phủ.
- `DATETIME(6)` lưu UTC cho thời điểm; `DATE` lưu ngày lịch tập tại múi giờ chi nhánh. Chuỗi Membership dùng khoảng nửa mở **[ngay_bat_dau, ngay_ket_thuc)**; cộng số ngày từ mốc kích hoạt, không inclusive cả hai đầu.
- Tiền VND: `DECIMAL(15,0)`; cân nặng/tạ: `DECIMAL`, không FLOAT. Số điện thoại là chuỗi.
- `VARCHAR` trạng thái + CHECK tập giá trị khi triển khai; không chỉ BOOLEAN. Tên/giá trị trạng thái nội bộ có thể tiếng Việt không dấu, mã actor MEMBER/PT/RECEPTIONIST/ADMIN giữ theo đặc tả.
- Mã/hash/token/idempotency/reference ngoài hệ thống dùng `utf8mb4_nopad_bin` phân biệt chính xác; tên/mô tả dùng Unicode. Email chuẩn hóa theo chính sách đăng nhập rồi UNIQUE.
- Policy MariaDB: mặc định `utf8mb4_unicode_ci`; 95 chuỗi kỹ thuật và email dùng `utf8mb4_nopad_bin` sau khi Backend trim, chuẩn hóa NFC và lowercase toàn địa chỉ email. Không dùng `utf8mb4_0900_*` của MySQL 8; giữ nguyên kiểu/độ dài cột.
- `ngay_tao`, `ngay_cap_nhat` được liệt kê rõ ở từng bảng. Ledger/snapshot append-only không có `ngay_cap_nhat`. Các bảng con Workout dù có cột cập nhật vẫn bị khóa khi phiên cha hoàn thành.
- Mặc định **ON DELETE RESTRICT, ON UPDATE RESTRICT**; không cascade xóa giao dịch, kỳ, snapshot, phiên bản, phiên tập, hiệp hoặc chat. Khóa/ngừng sử dụng catalog/account thay vì xóa vật lý. Q12: không hard-delete lịch sử trong MVP; anonymization, thời hạn pháp lý và purge/archive tự động thuộc Future Development/vận hành sau MVP, không tự đặt số năm.
- Cột FK nullable dùng khi quan hệ chưa hình thành hoặc có nguồn tùy chọn; không dùng 0 làm giả NULL. Các cặp nullable phải được kiểm tra cùng nhau trong service; composite FK bị bỏ qua khi có phần NULL không tự bảo đảm workflow.
- UNIQUE cho phép nhiều NULL nên dùng được với liên kết chưa phát sinh. Cột sinh chỉ dựa vào dữ liệu của hàng, không dựa NOW(); trạng thái theo thời gian vẫn phải được tính lại khi kiểm tra quyền. [MariaDB: UNIQUE và NULL](https://mariadb.com/docs/server/reference/sql-statements/data-definition/create/create-table)
- CHECK chỉ kiểm tra điều kiện trong một hàng, không bảo vệ chồng khoảng giữa nhiều kỳ, ownership qua nhiều bảng, tổng ledger, tính bất biến theo trạng thái cũ hay quan hệ phân công hiện thời. Dùng FK kép và transaction cho những việc đó; không mô tả CHECK chéo bảng như ràng buộc khả thi. [MariaDB: constraints](https://mariadb.com/docs/server/reference/sql-statements/data-definition/constraint)
- Ràng buộc hiển thị dạng các bộ cột trong từ điển; khi triển khai đặt tên tường minh tối đa 64 ký tự, không để tên tự sinh dài vượt giới hạn.
- Cặp FK vòng như Plan ↔ current Version hoặc chuỗi ↔ hành động kích hoạt cần thứ tự ghi: tạo cha với con trỏ NULL, tạo con, cập nhật con trỏ trong **một transaction**. Không dựa vào deferred constraint hoặc tắt FK.

## 3.1. Review chuyển DBMS MySQL → MariaDB

Review này chỉ đối chiếu khả năng biểu diễn vật lý trên **MariaDB 10.4.32 / InnoDB** với mô hình logic đã duyệt. Không đổi Q01–Q13, tên bảng/cột, số lượng bảng/cột, kiểu/NULL, PK/FK/UNIQUE, năm cột sinh hoặc bố cục ERD. P0 kỹ thuật và migration thật vẫn là bước riêng; chưa chạy trong vòng review này.

| Hạng mục | Kết quả | Kết luận và điều kiện triển khai |
| --- | --- | --- |
| CHECK có tên, CHECK cùng hàng, NULL/biểu thức | PASS | MariaDB thực thi CHECK trước INSERT/UPDATE và hỗ trợ `CONSTRAINT ten CHECK (bieu_thuc)`. Giữ toàn bộ 83 CHECK nghiệp vụ đã lập; không dùng `check_constraint_checks=OFF`. |
| FK đơn, ghép, nullable, tự tham chiếu, RESTRICT | PASS | InnoDB hỗ trợ các dạng FK này; cột cha phải có PK/UNIQUE và cột con phải có index tương thích. `NO ACTION` tương đương `RESTRICT`, không deferred. Tuple ghép có thành phần NULL sẽ không được kiểm tra quan hệ cha, nên invariant ownership vẫn ở service/transaction. |
| VIRTUAL generated + UNIQUE (5 cột) | PASS có điều kiện | MariaDB hỗ trợ VIRTUAL generated và index/UNIQUE trên cột sinh. Các biểu thức CASE chỉ đọc cùng hàng, không dùng NOW()/subquery và không làm FK nên phù hợp. P0 phải xác nhận thứ tự cú pháp, kiểu BIGINT/CHAR/DATE, nhiều NULL, cập nhật trạng thái và strict mode. |
| UNIQUE nullable/composite | PASS | Nhiều NULL được phép; giá trị non-NULL trùng bị từ chối. Giữ các UNIQUE dùng để bảo vệ chuỗi, Plan, slot và mã kỹ thuật. |
| Kiểu số, DECIMAL, DATE/TIME, DATETIME(6) | PASS | BIGINT/INT/SMALLINT/TINYINT unsigned, DECIMAL, DATE, TIME và DATETIME(6) đều giữ được theo từ điển; DATETIME(6) giữ microsecond. |
| JSON | ADJUST vật lý, không đổi logic | MariaDB biểu diễn `JSON` như `LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin` và tự thêm kiểm tra `JSON_VALID`. Đây không phải binary JSON như MySQL; giữ kiểu JSON trong từ điển, không thêm bảng/cột/FK. P0 phải tách 83 CHECK nghiệp vụ khỏi các CHECK JSON_VALID ngầm trong metadata. |
| Charset/collation | ADJUST vật lý | MariaDB 10.4 không có bộ `utf8mb4_0900_*` của MySQL 8. Dùng `utf8mb4_unicode_ci` cho mặc định nghiệp vụ, `utf8mb4_nopad_bin` cho 95 chuỗi kỹ thuật và email; email được trim, NFC và lowercase toàn địa chỉ ở Backend trước UNIQUE/lookup để giữ semantics không phân biệt hoa thường nhưng phân biệt dấu. |
| Laravel 13 / PHP 8.4 | PASS có điều kiện | `composer.lock` khóa Laravel 13.29.0 và `composer.json` yêu cầu PHP ^8.4. Laravel có `MariaDbConnection`, MariaDB Schema Grammar, Blueprint cho generated/index/FK; Blueprint không có API CHECK chung nên CHECK phải dùng câu lệnh DB có tên khi viết migration. P0-T15 phải đối chiếu DDL/metadata thực tế. |
| Atomic/partial DDL | ADJUST quy trình | MariaDB 10.4 chưa có đầy đủ atomic DDL như các bản mới; không giả transaction bao trọn file migration. Giữ kế hoạch M055/M057/M059, log từng statement, đọc metadata và retry/down theo phần đã xác minh. |

Nguồn kỹ thuật chính: [MariaDB constraints](https://mariadb.com/docs/server/reference/sql-statements/data-definition/constraint), [foreign keys](https://mariadb.com/docs/server/architecture/server-constraints/foreign-key-constraints), [generated columns](https://mariadb.com/docs/server/reference/sql-statements/data-definition/create/generated-columns), [JSON](https://mariadb.com/docs/server/reference/data-types/string-data-types/json), [JSON_VALID](https://mariadb.com/docs/server/reference/sql-functions/special-functions/json-functions/json_valid), [DATETIME](https://mariadb.com/docs/server/reference/data-types/date-and-time-data-types/datetime), [charset/collation](https://mariadb.com/docs/server/reference/data-types/string-data-types/character-sets/supported-character-sets-and-collations), [FOR UPDATE](https://mariadb.com/docs/server/reference/sql-statements/data-manipulation/selecting-data/for-update).

Đối chiếu local Laravel: `BE/vendor/laravel/framework/src/Illuminate/Database/MariaDbConnection.php`, `MariaDbGrammar.php`, `SchemaBlueprint.php` và `config/database.php` có connection `mariadb`; Blueprint có `virtualAs`/`storedAs`, index và FK nhưng không có phương thức CHECK chung. Đây là bằng chứng mã nguồn dependency đã khóa, không thay thế P0-T15.

**Blocker:** Không phát hiện blocker làm thay đổi mô hình logic trong review tài liệu. Các điểm ADJUST là quy ước vật lý/cách sinh DDL và phải được chứng minh ở P0; nếu P0 không giữ được semantics, dừng và yêu cầu review thiết kế, không tự bỏ ràng buộc.

## 4. Membership: không gộp chuỗi, kỳ và giao dịch

### 4.1. Ý nghĩa các bảng

`goi_tap` + `quyen_loi_goi_tap` là catalog đang bán, được Admin thay đổi. `don_mua_goi` là cam kết mua một gói. `ky_han_hoi_vien` giữ nguyên tên/phiên bản/giá/thời hạn và quyền lợi đã chốt. Một `dang_ky_goi_tap` gom các kỳ nối tiếp trong cùng chuỗi; hội viên có thể có nhiều chuỗi lịch sử nhưng tối đa một chuỗi chưa khép.

Khuyến nghị không thêm bảng snapshot quyền lợi kỳ riêng: số quyền CORE hữu hạn và snapshot được đọc cùng thời gian kỳ nên lưu các cột có kiểu trực tiếp trong kỳ. Một snapshot không phải bản tham chiếu động tới quyền catalog. Cờ Chat và tổng buổi PT trực tiếp phải được chụp riêng; thay catalog hoặc gia hạn không đổi quyền của kỳ cũ.

### 4.2. Vòng đời

1. Tạo đơn + snapshot kỳ `CHO_THANH_TOAN`. Tất cả nguồn cấp quyền, chuỗi, thứ tự, mốc mua và mốc sử dụng còn NULL.
2. Webhook hợp lệ: khóa hội viên/đơn, kiểm tra đã cấp chưa, ghi Payment, gán kỳ vào chuỗi và cấp thứ tự đúng một lần theo mốc Backend lần đầu xác nhận hợp lệ (Q02).
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

UNIQUE chuỗi đang mở bảo vệ một chuỗi chưa khép; UNIQUE (chuỗi, thứ tự), kỳ/đơn và kỳ/payment chống cấp trùng. **Không có UNIQUE đơn giản nào tự ngăn hai khoảng thời gian chồng lấn**; việc cấp thứ tự, nối thời gian và kiểm tra overlap nằm trong transaction dưới khóa hội viên. MariaDB InnoDB hỗ trợ `SELECT ... FOR UPDATE` trong transaction.

**Q02 đã chốt:** thứ tự mua là thứ tự Backend lần đầu xác nhận thanh toán hợp lệ dưới khóa hội viên/chuỗi. Ghi `lan_thanh_toan.xac_nhan_luc`, `ky_han_hoi_vien.mua_luc` và cấp `so_thu_tu` trong một transaction. Mốc trùng vẫn được tuần tự hóa bằng khóa/so_thu_tu; retry trả lại mốc/thứ tự cũ. Không theo thời điểm tạo đơn, request FE, returnUrl hoặc thời điểm ngân hàng; không chèn ngược kỳ vào phần chuỗi đã bắt đầu/đã dùng.

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

### 5.3. Snapshot và đối soát đã chốt — Q01/Q10

- Q01: tạo đơn và kỳ CHO_THANH_TOAN cùng transaction, đọc catalog nhất quán để chụp giá/gói/thời hạn/quyền. Đơn trong hạn giữ nguyên snapshot; Admin sửa catalog chỉ tác động đơn mới.
- `don_mua_goi.het_han_thanh_toan_luc` là hạn giữ giá; `lan_thanh_toan.het_han_luc` không được muộn hơn. Tạo link lại không gia hạn báo giá. Snapshot vẫn giữ lịch sử sau hạn nhưng không tiếp tục cấp quyền từ báo giá hết hiệu lực.
- Q10: sai tiền, trả sau hạn/hủy, không khớp hoặc hai link cùng nhận tiền -> sự kiện/lần thử bất thường CAN_DOI_SOAT, giữ dữ liệu/ly_do. Payload không xác thực -> BI_TU_CHOI + dấu vết, không tin là tiền đã nhận.
- Nếu đơn đã có kỳ hợp lệ, giữ nguyên nguồn cấp đó; khoản bất thường thứ hai không làm mất tính hợp lệ của lịch sử đã cấp. Một đơn tối đa một kỳ; không cấp kép, không cộng thêm ngày, không tự hoàn tiền/xóa/bỏ qua webhook.
- Q02 theo mục 4.4. Refund workflow thuộc ngoài CORE.

## 6. QR check-in

QR phòng tập là token ngẫu nhiên riêng, không phải QR payOS. DB lưu hash, hội viên, chi nhánh, TTL, thu hồi và thời điểm đã dùng. Phát hành QR chỉ tạo mã, **không kích hoạt Membership**.

Khi Receptionist quét: tra token/hash, xác minh nhân viên/chi nhánh, ownership, hạn dùng và chưa tiêu thụ; khóa hội viên/kỳ rồi QR theo thứ tự thống nhất; kiểm tra quyền gym của kỳ tại thời điểm quét; ghi hành động, activation nếu cần, check-in và tiêu thụ QR cùng transaction. UNIQUE QR trong history chống hai nhân viên quét đồng thời tạo hai lượt.

Kỳ được chọn ở lúc quét, không đóng băng ở lúc tạo QR, để QR phát hành gần ranh giới kỳ vẫn được xét đúng. Không tin ngày Membership/ID nhân viên do client gửi. Không thêm giới hạn một check-in/ngày hoặc UNIQUE chỉ một QR còn hạn vì các quyết định hiện tại không yêu cầu chúng. **Q09 đã chốt:** TTL mặc định 90 giây từ phat_hanh_luc, lấy từ cấu hình/policy server tập trung và ghi het_han_luc cụ thể. Thời điểm kiểm tra >= het_han_luc bị từ chối; sửa cấu hình không kéo dài hạn QR đã phát hành.

## 7. PT: phân công, quyền, lượt và Proposal

`phan_cong_huan_luyen_vien` lưu khoảng thời gian phụ trách, không gắn cứng vào một kỳ. Phân công không tự cấp Chat/buổi trực tiếp; hai dịch vụ đó cần quyền kỳ riêng. Riêng PT Proposal chỉ cần phân công hợp lệ theo Q13.

**ĐÃ CHỐT:** Chat dùng cờ `cho_phep_tro_chuyen_huan_luyen_vien` trong kỳ; buổi PT trực tiếp dùng `so_buoi_huan_luyen_vien > 0` và còn lượt. Hai quyền độc lập: Chat với 0 buổi/hết lượt vẫn hợp lệ; có buổi không tự mở Chat. Cả hai đều cần phân công và đúng kỳ; không cần thêm boolean quyền buổi.

MVP chỉ ghi ledger **buổi PT trực tiếp 1-1 đã hoàn thành do PT phụ trách xác nhận**. Không có booking, không trừ do chat, đặt lịch hoặc hủy. Hàng ledger gắn đúng Member/PT/phân công/kỳ/hành động; `ma_buoi_huan_luyen` là định danh buổi ổn định, không sinh lại theo từng lần bấm.

Transaction khóa kỳ, kiểm tra còn lượt, ghi ledger và tăng counter đúng 1. Hai buổi khác nhau tranh lượt cuối chỉ một buổi được chấp nhận; retry cùng buổi trả bản ghi cũ. Tổng counter phải đối chiếu được bằng SUM ledger, không sửa một số dư không có dấu vết. Lượt dư hết hiệu lực theo kỳ, không carry-over.

Buổi PT trực tiếp và Workout Session là hai khái niệm, không bắt buộc mỗi ledger buổi PT phải có một phiên tập trong App. Tư vấn bằng Chat không phải buổi trực tiếp và không trừ quota buổi. Không lấy số phiên tập hoặc số tin nhắn làm số lượt PT.

Ghi chú tư vấn lưu ở bảng riêng, được tham chiếu Plan/Session nhưng không đổi dữ liệu kê tập/kết quả. Mọi thay đổi template/ngày/bài/sets/reps và cả tạo Plan mới bởi PT phải có Proposal + Member xác nhận. Khi Apply PT Proposal kiểm tra lại chính phân công nguồn, ownership, TTL/trạng thái và version; không yêu cầu quyền Chat/quota/active Membership (Q13).

### 7.1. Một PT hiệu lực và bảo vệ khoảng — Q04

Invariant: mỗi Member có 0..1 PT tại một thời điểm, nhiều hàng phân công lịch sử. Mọi khoảng của cùng Member, kể cả khác PT, không được giao nhau; dùng [bắt đầu, kết thúc), NULL là vô hạn.

Thứ tự ghi: khóa hàng `ho_so_hoi_vien` trước (kể cả Member chưa có phân công), rồi đọc/khóa các phân công liên quan theo id. Với hai khoảng A/B, overlap khi A bắt đầu trước cận cuối B và B bắt đầu trước cận cuối A; cận cuối NULL là vô hạn. Loại chính hàng đang sửa khỏi phép so sánh. Không dùng consistent read cũ hoặc SKIP LOCKED để bỏ qua phân công đang được giao dịch khác xử lý.

Đổi PT trong một transaction: chốt cận cuối hàng cũ, kiểm tra mọi khoảng còn lại, thêm khoảng mới và audit. A kết thúc 15:00/B bắt đầu 15:00 hợp lệ. Index `(hoi_vien_id, ngay_bat_dau, ngay_ket_thuc)` hỗ trợ truy vấn; index theo PT vẫn giữ.

Cột sinh VIRTUAL `hoi_vien_dang_phan_cong_id` bằng hoi_vien_id khi ngay_ket_thuc NULL, ngược lại NULL; UNIQUE chỉ bảo vệ tối đa một hàng mở (kể cả khoảng mở tương lai). Nó không phát hiện giao hai khoảng hữu hạn hoặc khoảng hữu hạn với khoảng mở, nên transaction vẫn bắt buộc. Không thêm FK cho cột sinh, không CHECK chéo hàng/NOW(). Cardinality FK lịch sử vẫn 1:N; 0..1 là số phân công hiệu lực tại một thời điểm, không đổi cạnh history thành 1:1.

### 7.2. Xác nhận buổi muộn — Q06

Phải còn đúng kỳ/quota/phân công khi PT xác nhận hợp lệ (hoặc head được phép kích hoạt). Kỳ của buổi hết trước xác nhận -> từ chối, không backdate từ hoan_thanh_luc, không trừ kỳ cũ hoặc mượn kỳ sau. Correction Admin thuộc Future Development; không tự tạo workflow điều chỉnh.

### 7.3. PT Proposal — Q13

PT có chính phân công hợp lệ tại thời điểm tạo là đủ điều kiện quyền nghiệp vụ; không cần Chat, tổng buổi > 0, lượt còn lại hoặc active Membership. Tạo/apply Proposal không trừ lượt, không usage Chat/buổi và không kích hoạt kỳ.

Vẫn Preview, Member confirm/reject, revalidate chính phân công nguồn, ownership, base version/mốc hồ sơ/kế hoạch, TTL/trạng thái, future schedule. Apply thành công tạo version + audit trong transaction. Phân công nguồn kết thúc trước confirm -> XUNG_DOT, không Apply; một phân công mới không khôi phục Proposal cũ.

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

**ĐÃ CHỐT:** Member chỉ Start từ `buoi_tap_du_kien` đã có trong Plan/Schedule của chính mình và đủ điều kiện bắt đầu. FK lịch ở `phien_tap` giữ NOT NULL; không nhận danh sách bài tự do để tạo phiên hoặc tạo lịch giả. Free Workout ngoài lịch là FUTURE DEVELOPMENT. **Q08 đã chốt:** Workout Tracking là chức năng cơ bản, không yêu cầu active Membership.

Start idempotent trên lịch; mỗi bài/hiệp có ID ổn định. Save Set khóa phiên cha và kiểm tra DANG_TAP, expected revision + khóa request; dữ liệu request cũ không ghi đè set đã sửa mới hơn.

Complete khóa cùng phiên, xác minh ownership và toàn bộ set, chuyển HOAN_THANH cùng mốc kết thúc. Sau đó mọi add/update/delete bài/hiệp, đổi ngày, sửa reps/tạ/ghi chú kết quả đều bị từ chối. Không cho AI/PT/Admin đi vòng qua cùng cơ chế. Việc chỉ có FK/CHECK trong ERD **không tự làm các hàng bất biến**; service/policy và quyền ghi DB phải được thiết kế để mọi đường ghi tuân thủ khóa phiên.

Progress tính từ các phiên HOAN_THANH và hiệp thực tế, Body Measurement theo từng thời điểm. Không lấy 3×10 trong Plan giả làm ba hiệp thực hiện 10 reps. Membership hết hạn/mua gói mới không xóa/reset dữ liệu.

### 8.4. Active Plan và slot ngày — Q07A/B

- `ke_hoach_tap.hoi_vien_dang_su_dung_id`: VIRTUAL bằng hoi_vien_id khi DANG_SU_DUNG, LUU_TRU trả NULL. UNIQUE bảo vệ một Active Plan; Plan lưu trữ vẫn giữ lịch sử.
- `buoi_tap_du_kien.ngay_tap_con_hieu_luc`: VIRTUAL bằng ngay_tap khi CHUA_TAP/DANG_TAP/HOAN_THANH/BO_QUA; HUY/DA_THAY_THE trả NULL. UNIQUE `(hoi_vien_id, ngay_tap_con_hieu_luc)` bảo vệ một lịch còn giá trị mỗi Member/ngày, kể cả lịch thuộc Plan khác.
- HOAN_THANH/BO_QUA giữ slot để không sinh hai lịch có giá trị cho cùng ngày; chỉ lịch HUY/DA_THAY_THE giải phóng. Không xóa lịch hoặc đổi ngày của hàng cũ để né constraint.
- Khóa hội viên -> các Plan/lịch/phiên liên quan theo thứ tự thống nhất; cập nhật trạng thái hàng cũ trước khi tạo hàng mới trong cùng transaction. Giữ current version và kiểm tra xung đột; rollback toàn bộ nếu vi phạm.
- Các cột chỉ phụ thuộc cùng hàng, không dùng thời gian hiện tại; UNIQUE là hàng rào cuối. Giữ các FK/version/mã buổi hiện có.

### 8.5. Phiên HUY và lịch thay thế — Q07C/D

Giữ `phien_tap.buoi_tap_du_kien_id NOT NULL UNIQUE`; phiên HUY vẫn chiếm liên kết duy nhất với lịch gốc. Không xóa phiên hoặc chuyển HUY về DANG_TAP, không tạo phiên thứ hai trên lịch cũ.

Nếu tập lại: workflow schedule/version tạo lịch thay thế hợp lệ, giữ ma_buoi_logic và thay_the_buoi_tap_id; lịch cũ HUY/DA_THAY_THE giữ nguyên ngày/nội dung/FK và phiên HUY. Vì UNIQUE `(phien_ban_ke_hoach_tap_id, ma_buoi_logic)` vẫn giữ, lịch mới cùng mã buổi phải ở **version mới**, theo workflow thay đổi đã xác nhận; không INSERT lại trong cùng version. Giải phóng slot cũ trước, kiểm tra ngày/ownership/slot mới rồi Start từ lịch mới. Đây là ngoại lệ thay lịch có phiên HUY, không cho thay lịch có phiên DANG_TAP/HOAN_THANH. Free Workout vẫn ngoài MVP.

### 8.6. Workout không phải entitlement trả phí — Q08

Member không có gói hoặc Membership hết hạn vẫn xem Exercise Library, Plan/Schedule của mình, Start từ lịch hợp lệ, ghi Sets/Reps/Weight, Complete, xem History/Progress. Không thêm cờ quyền Workout hoặc buộc các thao tác này có kỳ hoạt động; không tạo usage/activation. Vẫn kiểm tra xác thực, ownership, Plan/Schedule/trạng thái, idempotency, concurrency và history immutability. Gym/AI/Chat PT/buổi trực tiếp giữ điều kiện trả phí riêng.

## 9. Realtime Chat

Hội thoại 1–1 thuộc một **lần phân công** Member/PT, UNIQUE `phan_cong_huan_luyen_vien_id`; không có group/member list. Thêm FK phân công trong hoi_thoai để hội thoại đã kết thúc luôn là lịch sử đọc, kể cả Member quay lại PT cũ bằng phân công mới. Đây là thay đổi tối thiểu trực tiếp từ Q05; chỉ UNIQUE cặp không thể tạo hội thoại mới khi quay lại cùng PT mà không mở lại thread lịch sử. Mỗi lần gửi lưu đúng phân công của hội thoại; FK kép mới bảo vệ điều này. Đọc/gửi/subscribe/reconnect đều kiểm tra Backend, nhưng READ HISTORY khác SEND NEW MESSAGE.

Mỗi hội thoại có sequence cấp dưới khóa trong transaction lưu tin. Không dựa vào timestamp client hay global auto-increment để kết luận tất cả tin nhỏ hơn cursor đã commit. Reconnect xin tin có sequence lớn hơn cursor; pagination cũ dùng sequence nhỏ hơn, luôn trong đúng hội thoại.

UNIQUE (hội thoại, người gửi, mã tin phía gửi) chống duplicate; cùng mã khác nội dung báo conflict. Tin và outbox commit cùng nhau, broadcast sau commit. Retry broadcast có thể trùng, client dedup theo message ID/sequence. Lưu DB bảo đảm reconnect lấy lại được tin nếu WebSocket bỏ lỡ.

**ĐÃ CHỐT:** gửi Chat đọc cờ `cho_phep_tro_chuyen_huan_luyen_vien` của đúng kỳ và phân công, không đọc số buổi còn lại để mở quyền. Không quota/counter số message.

- Kỳ đầu đang CHO_KICH_HOAT và Member gửi tin hợp lệ: ghi một usage TRO_CHUYEN_HUAN_LUYEN, nguồn kích hoạt chuỗi, mốc thời gian, tin và outbox cùng transaction. Tin đó có FK usage khác NULL.
- Kỳ đã hoạt động: vẫn kiểm tra cờ Chat/kỳ/phân công mỗi lần, lưu tin/outbox; không thêm usage, FK usage của tin mới là NULL. Không cùng tham chiếu usage kích hoạt cũ vì FK này UNIQUE.
- Nếu AI/QR/buổi PT đã kích hoạt trước, kể cả tin Chat đầu tiên cũng không tạo usage. Kỳ nối tiếp tự chạy không cần một usage Chat mới.
- Hai tin đầu đồng thời hoặc Chat tranh activation với AI/QR: cùng khóa hội viên/chuỗi/kỳ, kiểm tra lại sau khóa; chỉ thao tác thắng tạo nguồn activation. Retry trả lại tin/usage cũ.
- Lưu tin/outbox thất bại phải rollback usage/activation của chính transaction đó. Không thể có Membership bị kích hoạt bởi một tin chưa lưu.
- PT chủ động gửi/đọc/subscribe hoặc Member chỉ mở trang không kích hoạt. Chat không trừ buổi trực tiếp; lịch sử tin nằm ở tin_nhan, không nhân đôi thành usage cho mỗi message.

**Q05 đã chốt:**

| Thao tác | Điều kiện |
| --- | --- |
| Member đọc history của mình | Ownership; không đòi active Membership hoặc phân công còn hiệu lực. |
| Member/PT gửi mới | Cờ Chat/kỳ hợp lệ và chính phân công của hội thoại còn hiệu lực; không quota buổi. |
| PT cũ sau kết thúc phân công | Không gửi và không dùng resource scope đã mất để tiếp tục truy cập. |
| PT mới | Chỉ hội thoại của phân công mới; không đọc thread riêng của PT cũ. |

Đổi PT dùng hội thoại riêng gắn phân công mới. Không đổi FK/chủ hội thoại cũ, không DELETE/sửa message. Hết kỳ thì không tiếp tục gửi kể cả trả lời công việc đang dở; Member vẫn đọc. Server revalidate quyền đọc khi subscribe/reconnect/broadcast, ngăn phát tới PT mất scope; không dùng quyền gửi trả phí để khóa history của Member.

## 10. Hybrid AI và Proposal

### 10.1. Tách dữ liệu AI

- Hội thoại/tin nhắn: trải nghiệm chat, không phải nguồn Plan.
- Request: một ý định nghiệp vụ, nguồn quyền/quota và ngữ cảnh.
- Candidate bài/giáo án: ID thật + snapshot hợp lệ được Rule Engine chọn.
- Lần gọi mô hình: provider/model/prompt/schema/version, số lần thử, token usage/lỗi.
- Proposal chung: chỉ sinh sau validation, chưa sửa dữ liệu chính thức.
- Version kết quả: chỉ xuất hiện sau Member xác nhận thành công.

Một request có nhiều lần gọi provider nhưng không vì retry mà bị trừ nhiều lượt. AI chỉ dùng planning/adjustment/replacement/explanation; không thêm y khoa, dinh dưỡng điều trị hoặc supplement.

### 10.2. Quota và kích hoạt — Q03

Một request nghiệp vụ AI hợp lệ = một lượt, không phải từng message/provider call. Xác minh quyền AI và request đủ điều kiện; khóa hội viên -> chuỗi/kỳ -> request, giữ một quota, ghi request/domain/usage, kích hoạt nếu là lần dùng đầu tiên; commit trước khi gọi provider.

- Trước chấp nhận: thiếu điều kiện/thông tin hoặc bị từ chối -> KHONG_AP_DUNG, không quota/usage/activation.
- Chấp nhận: GIU_CHO và counter giữ chỗ +1; mỗi request chỉ một nguồn quota.
- Có kết quả nghiệp vụ: GIU_CHO -> DA_TINH, giữ chỗ -1, đã dùng +1.
- Lỗi kỹ thuật kết thúc request: GIU_CHO -> DA_TRA, giữ chỗ -1; nếu lượt đã tính rồi xác định lỗi kỹ thuật thì DA_TINH -> DA_TRA, đã dùng -1 và audit. Không trả hai lần.
- Provider retry nằm trong cùng request/giữ chỗ; không cấp quota mới. Callback trễ không hồi sinh request đã kết thúc/DA_TRA. Hỏi bổ sung trước chấp nhận không tính lượt; message bổ sung/provider retry của cùng request không phải lượt mới.
- Mỗi lần chuyển trạng thái hạn mức khóa cùng kỳ/request, cập nhật counter + trạng thái + audit trong một transaction; giữ giới hạn đã dùng + giữ chỗ và không âm.
- Nếu request hợp lệ đã kích hoạt Membership trước khi provider lỗi thì giữ usage/nguồn và đồng hồ đã commit; hoàn quota không phải rollback activation.

Request có hạn mức phải có cả FK kỳ và usage; KHONG_AP_DUNG thì cả hai NULL (CHECK cùng hàng). Quota không giới hạn vẫn ghi request/usage để truy vết, không dùng từng provider retry làm đơn vị dịch vụ.

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

PT mất chính phân công nguồn trước confirm: XUNG_DOT, không Apply theo Q13; không kiểm tra Chat/quota/active Membership cho nguồn PT. Nguồn AI tiếp tục kiểm tra quyền/dữ liệu AI theo rule hiện tại, không áp điều kiện AI sang PT. Member từ chối: không thay Plan. Explanation hoặc hỏi bổ sung không sinh version và không cần Proposal áp dụng dữ liệu.

**Q09:** TTL mặc định cả AI/PT Proposal là 24 giờ từ ngay_tao, server configuration/policy tập trung, ghi het_han_luc cụ thể. Confirm tại hoặc sau hạn bị từ chối; Preview/retry không gia hạn hoặc sửa payload cũ.

## 11. Authorization, audit, transaction và index

| Luồng | Actor/scope cần kiểm tra | Dữ liệu khóa/ghi nguyên tử | Hàng rào lặp |
| --- | --- | --- | --- |
| Webhook cấp kỳ | Chữ ký + kênh + đơn/tiền/tiền tệ | Hội viên, đơn, lần thử, chuỗi, kỳ, audit | Reference, kỳ/đơn, kỳ/payment |
| Kích hoạt | Đúng Member/quyền kỳ + điều kiện từng luồng | Hội viên, chuỗi/kỳ, hành động, domain | Nguồn kích hoạt chuỗi, mã hành động |
| QR redeem | Receptionist/chi nhánh + QR/Member | Hội viên/kỳ, QR, check-in, audit | UNIQUE QR trong history |
| Ghi buổi PT | PT phụ trách + đúng kỳ + còn lượt | Hội viên/kỳ, ledger/counter, audit | ID buổi + UNIQUE hành động |
| Save Set/Complete | Chủ phiên + lịch hợp lệ + trạng thái/revision; không Membership (Q08) | Hội viên/phiên/bài/hiệp liên quan | ID set, idempotency, trạng thái hoàn thành |
| Apply AI/PT | Chủ đề xuất + version; PT: chính phân công nguồn, không entitlement kỳ (Q13); AI: quyền tương ứng | Hội viên, Plan, Proposal, version/cây/lịch, audit | UNIQUE version/proposal + expected version |
| Gửi chat | Hai bên hợp lệ + chính phân công của hội thoại + cờ Chat/kỳ | Hội viên/kỳ nếu dùng quyền, hội thoại, tin, outbox | Cặp thread/sender/client message ID |
| AI request/quota | Member + head/kỳ/quyền + hạn mức | Hội viên/kỳ, giữ quota, request, hành động | Request ID; provider attempt riêng |

Khóa hội viên luôn trước các miền liên quan. Thứ tự chung: hội viên -> đơn/lần thanh toán (nếu có) -> chuỗi/kỳ -> phân công -> Plan/Proposal -> lịch/phiên -> request AI -> hội thoại; bỏ qua miền không tham gia, hàng cùng loại theo id tăng. Dữ liệu con/counter/ledger/audit ghi sau khi đã khóa các cha liên quan. Gán/đổi PT khóa cùng hội viên với gửi Chat, xác nhận buổi và Apply Proposal; đổi Plan/lịch khóa cùng hội viên với Start. Không có luồng ngược thứ tự để vượt kiểm tra. Transaction ngắn, retry deadlock có giới hạn bằng cùng idempotency key. Quyền có thể đổi trong lúc request chạy nên phải revalidate dưới khóa, không chỉ preflight.

Chỉ mục quan trọng: kỳ theo Member và khoảng thời gian; chuỗi/thứ tự kỳ; order/status; inbox/status; phân công theo PT/Member và khoảng hiệu lực; lịch theo Member/ngày; Session theo Member/ngày/trạng thái; hiệp theo bài trong phiên; tin theo conversation/sequence; Proposal theo Member/status; request AI theo kỳ/quota state. Unique reference/hash/token dùng so sánh chính xác.

Audit là append-only và chỉ chứa dữ liệu cần thiết. Ledger PT, kỳ nguồn thanh toán, nguồn kích hoạt và version/proposal đều có FK thật, không chỉ một audit đa hình. Dashboard/progress là đọc/tổng hợp, không có workflow ghi số dư.

### Cấp lại Role và lịch sử audit

Giữ UNIQUE `(nguoi_dung_id, vai_tro_id)`; hàng phân quyền là trạng thái hiện tại. Thu hồi gán thu_hoi_luc. Cấp lại UPDATE cùng id, cap_luc/nguoi_cap_id mới và thu_hoi_luc=NULL; giữ ngay_tao, cập nhật ngay_cap_nhat. Cấp/thu hồi/cấp lại và audit trước/sau phải commit/rollback cùng transaction, retry không tạo audit trùng. Lịch sử Role đọc từ nhat_ky_he_thong; không thêm bảng lịch sử Role.

## 12. Các quyết định đã chốt Q01–Q13

| Mã | Quyết định chính thức | Nơi đồng bộ |
| --- | --- | --- |
| Q01: **PASS** | Snapshot giá/gói/quyền lúc tạo đơn; giữ trong hạn đơn/link, không đổi theo catalog. | PROJECT_RULES 8.1; mục 4–5; B12–B15/B18. |
| Q02: **PASS** | Xếp thứ tự kỳ theo Backend lần đầu xác nhận thanh toán hợp lệ dưới khóa; không chèn ngược. | PROJECT_RULES 8.1; mục 4.4/5; B15/B18. |
| Q03: **PASS** | Một request AI hợp lệ = một lượt; retry provider không thêm; lỗi kỹ thuật trả quota, giữ activation. | PROJECT_RULES 34.1; mục 10.2; B18/B19/B46/B49. |
| Q04: **PASS** | Member 0..1 PT hiệu lực; không overlap mọi PT, giữ lịch sử. | PROJECT_RULES 14.1; mục 7.1; B22. |
| Q05: **PASS** | Member đọc Chat cũ; mất quyền/phân công không gửi; PT mới không đọc thread cũ. | PROJECT_RULES 33/33.1; mục 9; B22/B41/B42. |
| Q06: **PASS** | Buổi PT xác nhận khi đúng kỳ còn hiệu lực/quota; quá hạn không backdate/mượn lượt. | PROJECT_RULES GYM 23; mục 7.2; B23. |
| Q07: **PASS** | Một Active Plan, một lịch giữ slot/ngày; phiên HUY không restart lịch cũ; lịch thay thế/version mới. | PROJECT_RULES 22/24/25.1; mục 8.4–8.5; B33/B37/B38. |
| Q08: **PASS** | Workout cơ bản không cần active Membership; vẫn bắt đầu từ lịch hợp lệ. | PROJECT_RULES 25; mục 8.6; B33/B37–B40. |
| Q09: **PASS** | QR mặc định 90 giây, Proposal AI/PT 24 giờ; cấu hình tập trung, lưu hạn cụ thể. | PROJECT_RULES GYM 25/43.1; mục 6/10.4; B20/B50. |
| Q10: **PASS** | Khoản bất thường CAN_DOI_SOAT; giữ dấu vết, một đơn một kỳ; không refund tự động. | PROJECT_RULES 8.1; mục 5.3; B14–B16/B18. |
| Q11: **PASS** | Dụng cụ bài tập AND; không nhóm OR trong MVP. | PROJECT_RULES 19; mục 10.3/12; B25/B27/B28/B47. |
| Q12: **PASS** | Không hard-delete history; account/catalog ngừng/khóa, không cascade; retention nâng cao sau MVP. | PROJECT_RULES 6.1/46.1; mục 3/12; quy ước từ điển và B51. |
| Q13: **PASS** | PT Proposal chỉ cần phân công hợp lệ; không Chat/quota/Membership; revalidate khi confirm. | PROJECT_RULES GYM 24; mục 7.3/10.4; B22/B50. |

Không còn Q01–Q13 mở. Đây là quyết định thiết kế, không phải lệnh tạo/chạy migration. Q11 diễn giải toàn bộ quan hệ bài–dụng cụ theo AND: cần cả Bench và Barbell nếu có hai hàng; thay dụng cụ bằng bài/biến thể bài khác, không thêm bảng OR.

### Thay đổi cấu trúc tối thiểu

- B22 thêm `hoi_vien_dang_phan_cong_id BIGINT UNSIGNED NULL` VIRTUAL + UNIQUE, chỉ ngăn hai khoảng mở; temporal invariant vẫn bằng transaction.
- B33 thêm `hoi_vien_dang_su_dung_id BIGINT UNSIGNED NULL` VIRTUAL + UNIQUE để bảo vệ một Active Plan.
- B37 thêm `ngay_tap_con_hieu_luc DATE NULL` VIRTUAL + UNIQUE (hoi_vien_id, ngay_tap_con_hieu_luc); giữ slot cho CHUA_TAP/DANG_TAP/HOAN_THANH/BO_QUA, trả NULL cho HUY/DA_THAY_THE.
- B41 thêm `phan_cong_huan_luyen_vien_id BIGINT UNSIGNED NOT NULL`, FK tới B22 và UNIQUE. Thay hai UNIQUE cũ bằng UNIQUE phân công và UNIQUE (id, phan_cong_huan_luyen_vien_id). Thêm FK kép (phân công, Member, PT) tới B22.
- B42 thay FK kép tới hội thoại từ (hội thoại, Member, PT) thành (hội thoại, phân công), trỏ UNIQUE tương ứng ở B41; FK kép tới phân công vẫn bảo vệ Member/PT. Không thể gửi tin dùng phân công khác với hội thoại.
- Giữ tên 52 bảng, 2 cột sinh đã có, toàn bộ FK khác. Ròng: +4 cột, +1 FK đơn, +1 FK kép, +3 UNIQUE; 48 khai báo index truy vấn bổ sung giữ nguyên.
- Cột sinh chỉ dùng điều kiện cùng hàng, không NOW()/subquery, không thêm FK cho cột sinh. VIRTUAL có thể đánh index; UNIQUE nullable giữ các hàng lịch sử NULL. Chưa chạy DDL kiểm chứng trên MariaDB trong vòng review này. [MariaDB generated columns](https://mariadb.com/docs/server/reference/sql-statements/data-definition/create/generated-columns).

**Thống kê:** 52 bảng, 575 cột, 113 FK đơn, 31 FK kép, 144 FK tổng, 98 bộ UNIQUE ngoài PK, 48 khai báo index truy vấn bổ sung, 5 cột sinh (3 mới), 83 CHECK nghiệp vụ dự kiến. Các index bổ sung có thể trùng tiền tố/index UNIQUE; số 48 không phải tổng index vật lý. Hai ERD giữ 9 và 60 trang.

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
| Mục 31–33.1 | Chat theo lần phân công, tách đọc history/gửi mới; cờ riêng, usage chỉ khi kích hoạt, sequence/outbox. |
| Mục 34–43 | Request/candidate/provider/validation/Proposal/confirm/revalidate, chống apply lại. |
| CODE 01–04 | Tên bảng/cột Việt không dấu chính thức theo từ điển và hai ERD, không dùng tên ví dụ cũ. |
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
12. Chat reconnect/retry/out-of-order broadcast → không mất/nhân tin; PT mất scope không đọc/gửi/nhận trái quyền, Member vẫn đọc history của mình theo Q05.
13. FK ghép sai Member–Order–Period, Plan–Version–Day, Conversation–Assignment → bị ràng buộc từ chối.
14. Hết Membership/mua gói khác → vẫn đọc đúng Workout/Payment/Body Measurement history.
15. ONLINE Chat=true, 0 buổi → chat được, ghi buổi trực tiếp bị từ chối; hết lượt không tắt Chat. Chat=false nhưng còn buổi → không chat, vẫn có thể ghi buổi trực tiếp hợp lệ.
16. Thu hồi rồi cấp lại Role → cùng id/cặp UNIQUE, cap_luc mới, thu_hoi_luc=NULL; audit giữ đủ hai sự kiện, retry không trùng.
17. Start không có lịch, lịch khác chủ hoặc không đủ điều kiện → không tạo phiên/lịch giả; FK lịch NOT NULL.
18. Tin Member kích hoạt + 20 tin sau → 21 tin, một usage Chat; nếu AI/QR/buổi PT kích hoạt trước → không thêm usage Chat.
19. Hai tin đầu đồng thời hoặc Chat/AI/QR tranh activation → một nguồn; lỗi tin/outbox rollback activation cùng transaction; mỗi tin sau vẫn revalidate quyền.

### Ca kiểm thử bổ sung Q01–Q13

| Quyết định | Tình huống | Kết quả mong đợi |
| --- | --- | --- |
| Q01 | Admin sửa catalog sau tạo đơn; đơn/link hết hạn | Đơn còn hạn giữ snapshot; đơn mới dùng catalog mới; không kéo dài giữ giá. |
| Q02 | Webhook lệch thứ tự, cùng mốc, retry | Thứ tự xác nhận Backend dưới khóa, so_thu_tu duy nhất; không chèn ngược hoặc đổi mốc. |
| Q03 | Request hợp lệ, provider retry/lỗi kỹ thuật, retry trả quota | Giữ 1; không thêm lượt; trả 1 đúng một lần; counter không âm. |
| Q03 | Provider lỗi sau activation; request bị từ chối trước chấp nhận | Activation đã commit giữ nguyên; request bị từ chối không quota/usage/activation. |
| Q04 | Hai Admin gán hai PT khác nhau đồng thời; Member chưa có PT | Chỉ một khoảng được chấp nhận; 0 PT hợp lệ. |
| Q04 | Đổi PT cùng ranh giới; overlap khoảng hữu hạn khác PT | Cùng ranh giới hợp lệ; overlap bị transaction từ chối dù UNIQUE khoảng mở không bắt được. |
| Q05 | Đổi PT/hết Membership rồi đọc Chat cũ; gửi mới | Member đọc được; gửi PT cũ/hết quyền bị từ chối; lịch sử còn nguyên. |
| Q05 | PT mới đọc thread cũ; PT cũ gửi; quay lại PT cũ | Không đọc/gửi trái scope; phân công lại có hội thoại mới, không mở thread cũ. |
| Q06 | PT xác nhận khi kỳ của buổi đã hết | Từ chối; không backdate/mượn kỳ sau/correction tự động. |
| Q07 | Hai Plan DANG_SU_DUNG cùng Member; chuyển Plan | UNIQUE chặn hai Plan; LUU_TRU vẫn giữ nhiều lịch sử. |
| Q07 | Hai lịch giữ slot cùng Member/ngày, kể cả khác Plan | Bị chặn; HUY/DA_THAY_THE giữ hàng nhưng nhường slot; HOAN_THANH/BO_QUA vẫn giữ slot. |
| Q07 | Phiên HUY Start lại lịch cũ; tạo lịch thay thế rồi Start | Lịch cũ không có phiên thứ hai; version/lịch thay thế hợp lệ Start được, FK phiên cũ không đổi. |
| Q08 | Không có gói/HET_HAN: Start scheduled Workout, Save Set, Complete | Được phép nếu hợp lệ; không activation/usage; các quyền Gym/AI/PT trả phí vẫn kiểm tra riêng. |
| Q09 | QR tại/quá 90 giây mặc định; Proposal AI/PT tại/quá 24 giờ | Hết hạn; ngay trước hạn vẫn kiểm tra các điều kiện khác; preview không reset hạn. |
| Q10 | Sai tiền, trả muộn/hủy, không khớp, hai link nhận tiền | CAN_DOI_SOAT + dấu vết, tối đa một kỳ, không refund/cộng ngày kép. |
| Q11 | Bài có Bench và Barbell, Member thiếu một dụng cụ | Không hợp lệ (AND), không tự chọn OR. |
| Q12 | Xóa account/catalog hoặc dọn history | Không cascade/hard-delete lịch sử; khóa/ngừng sử dụng giữ tham chiếu. |
| Q13 | PT Proposal không Chat, 0/hết quota buổi, không Membership | Tạo/confirm hợp lệ khi chính phân công nguồn còn hiệu lực; không quota/usage/activation. |
| Q13 | Phân công nguồn kết thúc trước confirm, kể cả phân công lại | XUNG_DOT, không Apply; Preview/confirm/base version vẫn bắt buộc. |

Đây là test thiết kế cho bước triển khai sau này; chưa thực thi test nghiệp vụ hoặc concurrency trên Database.

## 15. Bất biến workflow cần kiểm tra ngoài FK

- Kỳ CHO_THANH_TOAN không được có payment nguồn, chuỗi, thứ tự hoặc ngày sử dụng; kỳ đã cấp phải có payment thành công thuộc đúng đơn và cùng hội viên. Snapshot của đơn/kỳ không đổi sau khi chốt.
- Hành động kích hoạt phải thuộc kỳ số 1 của chính chuỗi; loại hành động phải khớp domain record (QR, buổi PT, tin nhắn Member hoặc request AI), cùng Member/kỳ và cùng mốc server. FK kép ngăn sai chủ nhưng không tự chứng minh đúng loại hành động hoặc kỳ đầu.
- Proposal nguồn TRO_LY phải có request AI và không có phân công PT; nguồn HUAN_LUYEN_VIEN phải có phân công và không có request AI. Tạo mới không có Plan/version cơ sở; điều chỉnh/thay bài phải có cả hai. Người tạo/quyết định phải khớp actor được phép, không chỉ tồn tại trong nguoi_dung.
- Phiên bản hiện tại/trước và phiên bản của Proposal phải thuộc cùng Plan. Chỉ một version được tạo từ một Proposal; DA_AP_DUNG phải có version kết quả và mốc quyết định/áp dụng. Không chuyển lại trạng thái chờ để dùng lại xác nhận.
- Request AI được giữ/tính quota phải có đúng kỳ và hành động trả phí; request bị từ chối trước quyền không được tạo nguồn kích hoạt. Counter phải đối chiếu được với request/ledger, không chỉ thỏa giới hạn số học.
- Tin nhắn mới phải do một trong hai người của hội thoại gửi; chính phân công của hội thoại phải cùng cặp và còn hiệu lực. Điều kiện gửi không chặn Member đọc history sau hết kỳ/đổi PT. su_dung_quyen_loi_id chỉ gắn khi tin Member thực sự kích hoạt kỳ đầu đang chờ; mọi tin sau, tin khi kỳ đã chạy và tin PT đều NULL. Không lấy số buổi làm quyền Chat, không tạo usage Chat cho từng tin.
- Lịch thay thế phải cùng Member và cùng mã buổi logic, thuộc version mới; chỉ lịch CHUA_TAP tương lai hoặc lịch có phiên HUY theo Q07C được thay. Lịch/phiên HUY giữ lại, không Start phiên thứ hai trên lịch cũ. Bài trong phiên lấy nguồn kê từ đúng lịch/version của phiên; không ghép bài của Plan khác. Khi phiên hoàn thành, cả cây kết quả và mốc thời gian là bất biến.

Các điều kiện trên được kiểm tra lại trong transaction hoặc policy/service tương ứng; chưa viết trigger, SQL, model hay test thực thi ở bước thiết kế này.

## 16. Kết luận review

Mô hình bảo toàn bốn loại lịch sử độc lập: mua/quyền lợi theo kỳ; sử dụng dịch vụ; kế hoạch/version đã xác nhận; kết quả thực tế. Q01–Q13 đã được đưa vào thiết kế; PT/AI không có đường ghi tắt vào Plan chính thức/history hoàn thành.

Các kiểm tra mục 17 đã PASS; review tài liệu không phát hiện blocker làm thay đổi mô hình và thiết kế đạt mức phù hợp với MariaDB 10.4.32. Đây chỉ là phê duyệt tương thích để chuẩn bị technical preflight; không có migration, SQL/DDL, bảng, seed, model, API hoặc chức năng nào được tạo/chạy trong nhiệm vụ này.

## 17. Kiểm tra tài liệu và phạm vi thay đổi

### Kết quả kiểm tra ngày 29/08/2026

| Kiểm tra | Kết quả |
| --- | --- |
| PROJECT_RULES ↔ THIET_KE_DATABASE | PASS: Q01–Q13 có quy tắc, phân tích và ca kiểm thử; không còn điều kiện Membership cho Workout/PT Proposal. |
| TU_DIEN_DU_LIEU ↔ ERD theo mẫu | PASS: 52 thực thể, 575 cột, kiểu/NULL/PK/FK/UNIQUE/cột sinh và FK kép khớp. Mỗi bảng xuất hiện ở tổng thể và trang nhóm, không tính hai lần. |
| TU_DIEN_DU_LIEU ↔ ERD chi tiết | PASS: 52 trang bảng đủ cột/kiểu/NULL/PK/FK/UNIQUE/cột sinh; 113 đường FK đơn và 31 bộ FK kép trong ghi chú/metadata. |
| XML parse, ID và liên kết | PASS: cả hai XML hợp lệ, không trùng ID trong một trang, không có source/target/parent đứt. |
| Số trang | PASS: 9 trang ERD theo mẫu; 60 trang ERD chi tiết. |
| UNIQUE đích FK kép, kiểu và thứ tự cột | PASS: tất cả 31 FK kép có bộ UNIQUE/PK đích đúng thứ tự, kiểu tương thích. |
| Hình học bố cục | PASS: kiểm tra tọa độ không thấy bảng/hộp tham chiếu/ghi chú chồng nhau. Không thay thế kiểm tra ảnh. |
| Q01–Q13 còn ghi là mở | 0 trong bộ tài liệu thiết kế hiện hành. |
| Phạm vi source | PASS: chỉ cập nhật tài liệu/quy tắc, ghi chú ERD và README/P0 lịch sử; không sửa mã BE/FE/Mobile, không tạo migration PHP. |

### Phân loại kết quả tìm kiếm

Đã tìm toàn repo đang được quản lý theo ignore và kiểm tra cả value/tooltip của mọi trang ERD. Các nhắc Q01–Q13 hiện là quyết định đã chốt hoặc ca kiểm thử. Ghi chú B02 không tự bắt buộc xác minh email là lựa chọn xác thực ngoài Q01–Q13, không phải quyết định Q còn mở. Bản sao trước chỉnh sửa và dữ liệu kiểm tra trong `.tmp/` chỉ là tệp làm việc/lịch sử, không phải nguồn quy tắc hiện hành.

Đã loại tên bảng/cột ví dụ cũ khỏi PROJECT_RULES và bộ thiết kế. Mốc phiên tập dùng bat_dau_luc/ket_thuc_luc; các công thức/JSON minh họa được phân biệt với schema chính thức.

### Giới hạn kiểm tra và phạm vi

- Đây là đối chiếu tĩnh tài liệu/XML và kiểm tra biểu thức khoảng/slot bằng dữ liệu minh họa. **Chưa chạy DDL, test nghiệp vụ hoặc test concurrency trên MariaDB.**
- Đã đối chiếu đặc tính generated/UNIQUE/NULL, CHECK/FK/JSON và collation với tài liệu MariaDB; các câu mô tả so với trạng thái cũ được đặt ở transaction, không giả là CHECK cùng hàng.
- Draw.io MCP tải được sơ đồ 9 trang; xuất PNG trang Chat bị timeout. Không bàn giao PNG và không khẳng định đã kiểm tra ảnh của mọi trang.
- Không sửa BE/FE/Mobile; không tạo SQL/DDL, migration, seed, Model, Controller/Service/API; không cài package, import dataset hoặc chạy lệnh làm thay đổi Database.
- Có công cụ/dữ liệu kiểm tra tạm trong `.tmp/`, ngoài source ứng dụng; không phải migration hoặc chức năng sản phẩm.

**DATABASE DESIGN = APPROVED FOR MARIADB 10.4.32**

Bước này dừng ở tài liệu. Việc tạo/chạy migration chỉ thực hiện trong nhiệm vụ triển khai được yêu cầu tiếp theo.
