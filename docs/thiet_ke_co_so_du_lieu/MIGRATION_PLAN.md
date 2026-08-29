# MIGRATION PLAN — Smart Fitness Platform

**Ngày lập: 29/08/2026.**

**Trạng thái: MIGRATION PLAN = APPROVED FOR MARIADB TECHNICAL PREFLIGHT — CHƯA TẠO/CHẠY MIGRATION.**

**Ngày cập nhật review: 29/08/2026.** Preflight thực nghiệm trên đúng MariaDB 10.4.32 / InnoDB vẫn là cổng bắt buộc của nhiệm vụ triển khai, chưa thực hiện.

Thiết kế nguồn đã đạt **DATABASE DESIGN = APPROVED FOR MARIADB 10.4.32** sau vòng review chuyển DBMS. Tài liệu này chuyển thiết kế đó thành thứ tự triển khai schema và tiêu chí nghiệm thu; không phê duyệt thêm nghiệp vụ, không phải migration có thể thực thi và không chứng minh Database đã chạy.

## 1. Mục tiêu, nguồn và giới hạn

### 1.1. Nguồn bắt buộc

1. [PROJECT_RULES.md](../../PROJECT_RULES.md): business rules, Q01–Q13, naming, transaction, idempotency và test.
2. [THIET_KE_DATABASE.md](THIET_KE_DATABASE.md): phân tích nghiệp vụ, bất biến và ràng buộc ngoài schema.
3. [TU_DIEN_DU_LIEU.md](TU_DIEN_DU_LIEU.md): **nguồn chính thức của tên bảng/cột, kiểu, NULL, PK/FK/UNIQUE, CHECK và index**.
4. [ERD theo mẫu](smart_fitness_erd_theo_mau.drawio): 9 trang.
5. [ERD chi tiết](smart_fitness_erd.drawio): 60 trang.

Đọc toàn bộ PROJECT_RULES trước nhiệm vụ triển khai tiếp theo. Nếu các nguồn khác nhau khi bắt đầu code, dừng phần bị ảnh hưởng để đối chiếu; không tự lấy kế hoạch này ghi đè từ điển hoặc business rule. Các mã B01–B52 dưới đây là mã tra cứu tài liệu, không phải tên bảng mới.

### 1.2. Phạm vi của lần lập kế hoạch

- Chỉ sửa file MIGRATION_PLAN.md này theo review; không sửa năm tài liệu nguồn đã duyệt và không tạo file mới.
- Không tạo/chạy Laravel migration hoặc SQL/DDL; không thay đổi Database.
- Không tạo/sửa Seeder, Factory, Model, Controller, Service, API, test ứng dụng, BE/FE/Mobile.
- Không cài package, import bài tập hoặc tạo dữ liệu mẫu.
- Không thêm bảng, cột hay quyền ngoài mô hình đã duyệt; không đưa PT Booking, Free Workout, equipment OR, refund hoặc retention workflow vào kế hoạch.

Các mục “tạo”, “gắn”, “chạy”, “kiểm thử” bên dưới đều là công việc của **nhiệm vụ triển khai sau khi được yêu cầu**, chưa được thực hiện ở bước này.

## 2. Baseline và điều kiện trước triển khai

### 2.1. Baseline thiết kế

| Thành phần | Số lượng |
| --- | ---: |
| Bảng nghiệp vụ/kỹ thuật CORE đã duyệt | 52 |
| Cột, gồm PK/timestamp/cột sinh | 575 |
| PK | 52 |
| FK đơn | 113 |
| FK ghép bổ sung | 31 |
| Tổng constraint FK cần có | 144 |
| Bộ UNIQUE ngoài PK | 98 |
| Khai báo index truy vấn bổ sung | 48 |
| Cột sinh VIRTUAL | 5 |
| CHECK nghiệp vụ có tên dự kiến | 83 |
| Trang ERD theo mẫu / chi tiết | 9 / 60 |

“FK ghép” gồm cả bộ hai và ba cột; không đếm mỗi cột thành một FK. Bảng tham chiếu lặp trên ERD không làm tăng số bảng. 48 là số khai báo index truy vấn trong từ điển, không phải tổng index vật lý sau khi cộng PK/UNIQUE/index hỗ trợ FK.

### 2.2. Hiện trạng repository đã kiểm tra

- `BE/composer.json` yêu cầu PHP `^8.4` và Laravel `^13.17`; `BE/composer.lock` khóa `laravel/framework v13.29.0`.
- Application PHP chuẩn là PHP 8.4 portable tại `.tools/php` (không dùng PHP 8.0 đi kèm XAMPP); Database là MariaDB 10.4.32 / InnoDB do XAMPP cung cấp ở cổng 3306. Hai thành phần này độc lập.
- Laravel 13.29.0 có `MariaDbConnection`/`MariaDbGrammar` và `config/database.php` đã có connection `mariadb`; `SchemaBlueprint` hỗ trợ generated/index/FK nhưng không có API CHECK chung. Đây là đối chiếu dependency local, chưa sinh DDL.
- `BE/database/migrations/` mới có `.gitkeep`, chưa có file migration PHP.
- `BE/.env.example` dùng cấu hình Laravel/PDO cho MariaDB, session/cache lưu file và queue sync; không tự thêm bảng session/cache/job từ skeleton.
- Đây là kiểm tra file cấu hình, **không phải xác nhận phiên bản PHP/MariaDB đang chạy hoặc kết nối Database thành công**.
- Không sửa cấu hình hiện tại trong nhiệm vụ lập kế hoạch.

### 2.3. Cổng kiểm tra môi trường cho nhiệm vụ triển khai

- [ ] Có yêu cầu rõ ràng cho phép viết migration; xác định riêng môi trường được phép chạy.
- [ ] Xác nhận PHP và extension PDO MySQL kết nối MariaDB phù hợp với composer.lock; không tự nâng/hạ dependency.
- [ ] Xác nhận đích là **MariaDB 10.4.32, InnoDB**, không suy từ nhãn MySQL trong XAMPP hoặc coi phiên bản khác là tương đương.
- [ ] Chọn Database development/test cô lập; xác nhận host, port, database và tài khoản trước mọi thao tác ghi. Không công bố mật khẩu.
- [ ] Kiểm tra schema đích có trống về 52 bảng dự kiến hay không. Nếu đã có bảng/dữ liệu: dừng luồng khởi tạo, lập kế hoạch chuyển đổi có backup riêng; không DROP hoặc nhận bảng cũ làm đúng schema.
- [ ] Áp đúng charset/collation đã chốt ở 6.1.1–6.1.3 và chế độ SQL strict nhất quán; không để implementation tự chọn lại.
- [ ] Xác nhận timestamp UTC và ngày lịch theo `chi_nhanh.mui_gio`; không lấy timezone máy của từng thành viên làm quy ước dữ liệu.
- [ ] Chỉ một tiến trình chạy migration; chưa cho ứng dụng, queue, webhook hoặc tác vụ nền ghi vào schema đang tạo.
- [ ] Nếu có dữ liệu cần giữ: có backup và kế hoạch khôi phục đã được kiểm chứng trước triển khai.

Laravel có bảng theo dõi migration nội bộ của framework; bảng này không thuộc 52 bảng CORE. Không đổi tên cột framework chỉ để tăng/giảm thống kê nghiệp vụ, không tự thêm bảng xác thực mặc định trùng với `nguoi_dung`, `the_truy_cap` hoặc `yeu_cau_dat_lai_mat_khau`.

### 2.4. Technical compatibility preflight bắt buộc trước khi viết migration thật

**Trạng thái hiện tại: CHƯA CHẠY.** Đây chỉ là kế hoạch probe. Việc duyệt kế hoạch không chứng minh server tương thích và không cho phép chạy Database trong nhiệm vụ review này.

Trong nhiệm vụ triển khai được cho phép sau này, **P0 phải hoàn tất probe trên đúng MariaDB 10.4.32 / InnoDB trước khi tạo bất kỳ file M001–M060 nào**. Dùng schema thử nghiệm riêng có thể hủy, không dùng Database ứng dụng/production và không truy cập dịch vụ thanh toán/AI thật. Probe tối thiểu phải giữ nguyên kiểu, NULL, UNIQUE đích, CHECK, generated và tổ hợp FK liên quan; không “probe” một ví dụ đơn giản hơn rồi suy cả thiết kế chạy được. DDL/fixture thử chỉ được thực hiện khi phạm vi ghi Database đã được cho phép riêng.

| Mã | Phạm vi | Cách kiểm chứng ở nhiệm vụ sau | Tiêu chí PASS / giới hạn phải ghi |
| --- | --- | --- | --- |
| P0-T01 | Môi trường MariaDB 10.4.32 / InnoDB | Ghi exact server version/vendor, engine, sql_mode strict, connection charset/collation và timezone. Đối chiếu PHP/Laravel thực tế với composer.lock. | PASS chỉ trên đúng MariaDB 10.4.32, không SQLite hoặc MariaDB phiên bản khác thay thế; thiếu engine/collation/strict mode thì dừng. |
| P0-T02 | CHECK + FK đơn + explicit RESTRICT | Trong schema probe cô lập, kiểm tra cùng cột có CHECK và FK với ON DELETE RESTRICT/ON UPDATE RESTRICT; thêm CHECK trước rồi attach FK đúng P1/P2; kiểm tra INSERT/UPDATE con hợp lệ/sai, DELETE/UPDATE khóa cha đang được tham chiếu. | CHECK và FK đều ENFORCED; dữ liệu đúng nhận, sai bị chặn, sửa/xóa khóa cha bị từ chối. Nếu syntax lỗi, thử nhánh ngoại lệ ở dưới với cùng dữ liệu, không bỏ CHECK. |
| P0-T03 | Composite FK + CHECK | Tuple cha có UNIQUE đúng thứ tự; con có CHECK trên một/thành phần FK. Kiểm tra tuple đúng, ID tồn tại riêng lẻ nhưng ghép sai owner/kỳ, NULL một phần và cập nhật khóa cha. | Tuple không NULL sai bị FK chặn; tổ hợp NULL sai nghiệp vụ bị CHECK tương ứng chặn; hành vi cha vẫn RESTRICT tương đương. |
| P0-T04 | VIRTUAL generated + UNIQUE | Probe đủ 5 biểu thức ở mục 6.2, đúng kiểu/nullable/collation; INSERT/UPDATE trạng thái nguồn, nhiều giá trị NULL, trùng giá trị non-NULL, thử ghi trực tiếp giá trị khác vào cột sinh. | Generated phản ánh đúng hàng nguồn, UNIQUE giữ scope; NULL lịch sử hợp lệ; không chấp nhận giá trị cột sinh tùy ý; không có NOW() hay FK đặt vào cột sinh. |
| P0-T05 | Nullable composite FK | Probe từng hình thái tuple nullable của 31 FK ghép; so sánh mọi thành phần khác NULL, có một NULL, cả phần tùy chọn NULL. Gắn cùng FK đơn đã duyệt, không bỏ FK đơn. | Ghi rõ trường hợp MariaDB bỏ kiểm tra tuple do NULL; A B17/B18/B37/B46 vẫn chặn cặp/trạng thái sai. Phần quyền/source chưa được CHECK bảo vệ phải có test C, không báo FK đã bảo vệ. |
| P0-T06 | Self-referencing FK | Probe B34.phien_ban_truoc_id (đơn + ghép cùng Plan) và B37.thay_the_buoi_tap_id; cha cũ tồn tại, NULL khởi tạo, ID không tồn tại, version trước thuộc Plan khác; thử xóa/sửa khóa cha. | FK tự tham chiếu gắn được sau CREATE; sai đích/cùng Plan bị chặn theo FK; UNIQUE B37 chặn hai hàng thay cùng lịch. Tự trỏ/vòng nhiều hàng không mặc nhiên được FK chặn, thuộc C15/C16. |
| P0-T07 | B17 dang_ky_goi_tap | Ghép kiem_tra_b17_01/02, các FK đơn + khoa_ngoai_ghep_b17_01 và UNIQUE VIRTUAL. Probe CHO_KICH_HOAT NULL/NULL, DANG_HOAT_DONG mốc/nguồn hợp lệ, cặp lệch NULL ở HUY; thử hai chuỗi chưa khép cùng Member. | Cặp sai/trạng thái sai/nguồn sai Member/chuỗi mở trùng bị chặn đúng constraint. Nguồn phải thuộc kỳ đầu của chính chuỗi vẫn cần C10; không coi composite owner FK là đủ. |
| P0-T08 | B18 ky_han_hoi_vien | Probe đủ 8 CHECK, 4 FK đơn/3 FK ghép và 4 UNIQUE. Ca cặp ngày NULL, lệch NULL, cộng ngày chính xác/microsecond, qua tháng/năm và vượt miền DATETIME; AI không quyền/có quyền giới hạn/unlimited, quota PT độc lập Chat. | Đúng số ngày và quota mới nhận; không để UNKNOWN lọt cặp sai. Đơn/payment/chuỗi sai owner bị FK chặn; thứ tự duy nhất. Ngày tail/nguồn payment/ledger thực vẫn C07/C09/C10/C22. |
| P0-T09 | B33 ke_hoach_tap | Probe trạng thái CHECK + generated UNIQUE; con trỏ NULL khởi tạo, tạo version rồi gắn con trỏ cùng Plan; con trỏ version của Plan khác; lưu trữ trước khi công bố Plan mới. | Một active Plan/Member, nhiều archived; FK kép giữ đúng Plan. Không công bố con trỏ NULL trong workflow C15; không giả CHECK đọc bảng version. |
| P0-T10 | B34 phien_ban_ke_hoach_tap | Probe so_phien_ban >=1, nguồn enum, UNIQUE Plan/số version và Proposal, FK đơn + self FK ghép. Ca cùng Plan/khác Plan, Proposal đã có kết quả; đi đúng chu trình tạo dữ liệu mục 7. | Không trùng version/result; nguồn enum sai hoặc version<1 bị chặn. Snapshot bất biến/tăng version/apply đã xác nhận vẫn phải C04/C15/C24. |
| P0-T11 | B37 buoi_tap_du_kien | Probe cặp giờ cả NULL/cả có, giờ lệch/đảo; 2 generated, mọi UNIQUE, 5 FK đơn + 3 FK ghép. Thử bốn trạng thái giữ slot và HUY/DA_THAY_THE, cùng Member/ngày, cùng mã logic, cùng version/mã, self FK. | Đúng slot ngày/mã, giữ HOAN_THANH/BO_QUA; NULL không lách CHECK giờ; ownership FK đúng. Lịch thay thế cần version mới và không đổi history theo C16, không sửa schema để test dễ hơn. |
| P0-T12 | B45 tin_nhan_tro_ly | Probe nguồn/sequence CHECK, UNIQUE sequence/mã tin, FK hội thoại và request + composite cùng hội thoại. Tạo input chưa gắn request, tạo request B46, rồi phản hồi theo thứ tự mục 7. | Response sai hội thoại bị FK ghép chặn; sequence=0/nguồn lạ bị CHECK chặn; vòng B45/B46 không cần tắt FK hoặc deferred constraints. |
| P0-T13 | B46 yeu_cau_tro_ly | Probe đủ 4 CHECK, 5 FK đơn/4 FK ghép; tất cả trạng thái hạn mức với cặp kỳ/usage NULL và non-NULL; input khác hội thoại, kỳ/usage sai Member, usage sai kỳ; giữ explicit RESTRICT nếu server chấp nhận. | KHONG_AP_DUNG chỉ NULL/NULL; GIU_CHO/DA_TINH/DA_TRA đủ hai FK; tuple sai bị FK chặn. Giữ đầy đủ CHECK khi thử ngoại lệ NO ACTION; quota ledger thực thuộc C22. |
| P0-T14 | Charset/collation và khóa chuỗi | Probe toàn bộ policy mục 6.1.1: text tiếng Việt có dấu; mã khác case/dấu; VARCHAR có trailing space; CHAR(36)/(64)/(3) và padding; email case/diacritics/normalization; metadata của từng cột override/UNIQUE. | Default/overrides đúng tên collation; mã không bị gộp case/dấu; email cùng policy xuyên lookup/UNIQUE. Ghi rõ giới hạn CHAR, không giả NO PAD giữ byte gốc. Xác nhận hiện có 0 FK chuỗi. |
| P0-T15 | API Laravel đã khóa + toàn manifest | Đối chiếu API thực tế, inspect câu lệnh do Schema Builder/grammar của dependency khóa sinh ra; probe minimal schema đầy đủ A/B theo cùng cách tạo/attach dự định, kể cả CHECK phải dùng câu lệnh DDL MariaDB tường minh. Không tạo 60 file migration thật trước PASS. | Đủ 83 CHECK nghiệp vụ có tên ENFORCED, 98 UNIQUE, 144 FK và 5 generated theo manifest; ghi riêng các CHECK JSON_VALID ngầm nếu metadata hiển thị. Không dùng API CHECK giả định, không bỏ constraints khi driver không sinh đủ. |
| P0-T16 | Lỗi từng phần M053–M060 / down | Trong schema thử được phép, gây lỗi có kiểm soát giữa hai bảng nguồn và giữa các statement FK của cùng bảng, tối thiểu ở M055/M057/M059; mô phỏng log chưa kịp ghi nhưng DDL đã thành công; đối chiếu metadata rồi gỡ ngược. | Xác định được bảng nguồn cuối hoàn tất, bảng dở, tên FK đã tồn tại thật; không đánh dấu cả file thành công. Gỡ/retry đúng phần đã xác minh, không xóa CHECK/UNIQUE/table đích hoặc tắt FK. |

#### 2.4.1. Nhánh xử lý CHECK xung đột explicit RESTRICT

1. Thử đúng `ON DELETE RESTRICT` và `ON UPDATE RESTRICT` trước, cùng CHECK đã chốt. Không kết luận lỗi syntax từ tài liệu chung hoặc khả năng Schema Builder; ghi lỗi thực tế trên server.
2. Nếu server/cách biên dịch từ chối tổ hợp explicit này, giữ nguyên CHECK và mọi tuple FK, thử **lược bỏ action để dùng mặc định InnoDB NO ACTION** hoặc viết `NO ACTION` tường minh. Chỉ chấp nhận khi các ca UPDATE/DELETE khóa cha đang có con đều bị từ chối ngay và INSERT/UPDATE con vẫn bị CHECK/FK kiểm tra.
3. Không dùng CASCADE, SET NULL, SET DEFAULT, NOT ENFORCED, tắt FK/CHECK hoặc trigger thay thế để vượt probe. Không chuyển CHECK thành Backend chỉ vì syntax không chạy.
4. Mọi ngoại lệ cần ghi ở **manifest vật lý** theo từng FK, gắn CHECK liên quan và probe evidence; giữ nguyên tên constraint. Nếu không bảo toàn nghĩa hoặc cần đổi type/NULL/tuple, P0 FAIL, dừng viết migration và xin review schema.

InnoDB coi NO ACTION tương đương RESTRICT về việc từ chối hành động cha; đây không phải cơ chế deferred đến COMMIT. Cú pháp mặc định/NO ACTION có thể không xuất hiện nguyên văn trong SHOW CREATE TABLE, nên đối chiếu cả metadata lẫn hành vi thử. [MariaDB — Foreign key constraints](https://mariadb.com/docs/server/architecture/server-constraints/foreign-key-constraints).

#### 2.4.2. Hồ sơ bằng chứng và cổng ra P0

Mỗi case lưu: mã case, phiên bản MariaDB/Laravel/PHP thực tế, sql_mode/engine/charset/collation, bảng và tên constraint, cách biên dịch, dữ liệu valid/invalid đã lọc, kết quả mong đợi/thực tế, mã lỗi, metadata sau thử và kết quả dọn schema thử. Không lưu secret/token thật. P0-T15 phải đối chiếu từng tên A/B với metadata; các ví dụ trong manifest không thay cho thử đủ các biên và nhánh NULL.

Mẫu thông tin **ngoại lệ vật lý** cần ghi vào constraint manifest khi có kết quả (chưa có ngoại lệ đã được kiểm chứng ở nhiệm vụ này):

| FK giữ nguyên tên | Source/target tuple | CHECK giao cột | Action thiết kế | Cú pháp thực tế được chấp nhận | Action từ metadata | Probe parent DELETE/UPDATE + child valid/invalid | Lý do/evidence |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Tên từ 5.2/5.3/5.4 | Đúng tuple từ điển | Tên A tương ứng hoặc không giao | RESTRICT / RESTRICT | Explicit RESTRICT, hoặc default/NO ACTION chỉ nếu cần | Ghi thực tế, không suy từ câu lệnh | Phải giữ hành vi từ chối tương đương | Case, exact server version, lỗi ban đầu và kết quả thử lại |

**Chỉ chuyển từ P0 sang viết P1/P2 khi cả 16 case đạt, mọi ngoại lệ vật lý đã có bằng chứng và baseline không lệch.** Case phát hiện giới hạn vốn thuộc C phải chứng minh đã phân định đúng, không đòi Database tự thực thi C. Không dùng trạng thái phê duyệt tài liệu để bỏ qua cổng này. Hiện tại chỉ có **kế hoạch preflight đầy đủ**, chưa có kết quả PASS/FAIL thực nghiệm.

## 3. Chiến lược triển khai: tạo cấu trúc trước, gắn FK sau

### 3.1. Lý do

Đồ thị hiện tại có ba cụm phụ thuộc vòng:

- `dang_ky_goi_tap` ↔ `su_dung_quyen_loi` ↔ `ky_han_hoi_vien`.
- `ke_hoach_tap` ↔ `phien_ban_ke_hoach_tap` ↔ `de_xuat_ke_hoach_tap`.
- `tin_nhan_tro_ly` ↔ `yeu_cau_tro_ly`.

Ngoài ra có hai FK tự tham chiếu: `phien_ban_ke_hoach_tap.phien_ban_truoc_id` và `buoi_tap_du_kien.thay_the_buoi_tap_id`. Thứ tự B01–B52 còn có tham chiếu tới bảng ở phía sau, ví dụ `dung_cu_hoi_vien` → `dung_cu` và `ghi_chu_huan_luyen` → Plan/Session.

**Phương án đề xuất:** 52 migration tạo bảng + 8 migration gắn FK theo nhóm, tổng **60 file PHP dự kiến cho bước triển khai sau**. Hiện tại không tạo các file này.

### 3.2. Các pha và điểm dừng

| Pha | Phạm vi dự kiến | Điều kiện hoàn tất |
| --- | --- | --- |
| P0 | Preflight môi trường + 16 compatibility probe, đối chiếu manifest A/B/C | Đủ bằng chứng PASS trên MariaDB 10.4.32 và ngoại lệ vật lý nếu có; **trước khi viết file migration thật** |
| P1 | M001–M052: tạo 52 bảng, cột, PK, generated, UNIQUE, index, CHECK cùng hàng | Đủ 575 cột, 5 generated, 98 UNIQUE, 83 CHECK nghiệp vụ dự kiến (ngoài CHECK JSON_VALID ngầm của MariaDB); chưa có FK |
| P2 | M053–M060: gắn FK theo 48 đơn vị source table tại 5.4 | Đủ 144 FK, đúng tuple/kiểu/hành vi RESTRICT; theo dõi tiến độ từng nguồn/constraint |
| P3 | Đối chiếu metadata và kiểm thử schema trên MariaDB cô lập | Đạt checklist mục 10 |
| P4 | Review thay đổi và bàn giao schema | Ghi đúng kết quả thực thi; không tự bắt đầu chức năng |

Tất cả cột FK đã có đúng kiểu và NULL ngay ở P1; chỉ **constraint** được gắn ở P2. Không tạm biến cột NOT NULL thành nullable để vượt phụ thuộc, không đổi FK thành JSON hoặc cột không kiểm soát.

“Gắn FK sau” là thứ tự của các file migration, **không phải deferred constraint lúc commit**. P1 chưa phải schema được phép sử dụng. Không seed/import/cho ứng dụng ghi dữ liệu giữa P1 và P2; không tắt kiểm tra FK để xử lý vòng.

Mỗi migration tạo bảng chỉ phụ trách một bảng. Mỗi migration FK phụ trách các bảng nguồn của một nhóm; trong nhóm đi theo B tăng dần, gắn FK đơn theo thứ tự cột rồi FK ghép theo thứ tự từ điển. Các bảng và UNIQUE đích đều đã tồn tại sau P1.

### 3.3. Cách đặt tên file dự kiến

- Tạo bảng: `<timestamp>_tao_bang_<ten_bang_chinh_thuc>.php`.
- Gắn FK: `<timestamp>_them_khoa_ngoai_<ten_nhom>.php`.
- M001–M060 là mã thứ tự trong kế hoạch, không phải bảng/cột hoặc timestamp thật.
- Khi tạo file sau này, timestamp phải sắp xếp đúng M001 → M060; không tạo nhiều file cùng timestamp rồi dựa vào thứ tự tên vô tình.
- Giữ API framework `up` / `down`, không dịch tên phương thức chính thức.
- Không dùng generator/model để tự suy tên số nhiều hoặc tạo thêm migration phụ thuộc.

Laravel xác định thứ tự migration từ timestamp trong tên file; `down` phải đảo các thay đổi của `up`. [Laravel 13.x — Migrations](https://laravel.com/framework/docs/13.x/migrations).

## 4. P1 — Thứ tự tạo đầy đủ 52 bảng

Mỗi hàng dưới tương ứng một migration tạo bảng dự kiến. Cột FK chỉ là **số ràng buộc phải gắn ở P2**; không gắn FK trong M001–M052. Tên file suy trực tiếp từ bảng theo mục 3.3. Chi tiết từng cột/default/NULL/CHECK lấy từ liên kết từ điển, không sao chép suy đoán từ tên bảng.

| Thứ tự | Bảng / mục từ điển | Cột | FK đơn | FK ghép | UNIQUE | Index bổ sung |
| --- | --- | ---: | ---: | ---: | ---: | ---: |
| M001 | [chi_nhanh](TU_DIEN_DU_LIEU.md#b01) | 9 | 0 | 0 | 1 | 0 |
| M002 | [nguoi_dung](TU_DIEN_DU_LIEU.md#b02) | 12 | 1 | 0 | 1 | 1 |
| M003 | [vai_tro](TU_DIEN_DU_LIEU.md#b03) | 6 | 0 | 0 | 1 | 0 |
| M004 | [phan_quyen_nguoi_dung](TU_DIEN_DU_LIEU.md#b04) | 8 | 3 | 0 | 1 | 1 |
| M005 | [ho_so_hoi_vien](TU_DIEN_DU_LIEU.md#b05) | 13 | 1 | 0 | 2 | 0 |
| M006 | [ho_so_huan_luyen_vien](TU_DIEN_DU_LIEU.md#b06) | 8 | 1 | 0 | 2 | 0 |
| M007 | [ngay_ranh_hoi_vien](TU_DIEN_DU_LIEU.md#b07) | 5 | 1 | 0 | 1 | 0 |
| M008 | [dung_cu_hoi_vien](TU_DIEN_DU_LIEU.md#b08) | 5 | 2 | 0 | 1 | 0 |
| M009 | [chi_so_co_the](TU_DIEN_DU_LIEU.md#b09) | 9 | 1 | 0 | 1 | 1 |
| M010 | [the_truy_cap](TU_DIEN_DU_LIEU.md#b10) | 10 | 1 | 0 | 1 | 1 |
| M011 | [yeu_cau_dat_lai_mat_khau](TU_DIEN_DU_LIEU.md#b11) | 8 | 1 | 0 | 1 | 1 |
| M012 | [goi_tap](TU_DIEN_DU_LIEU.md#b12) | 12 | 2 | 0 | 1 | 1 |
| M013 | [quyen_loi_goi_tap](TU_DIEN_DU_LIEU.md#b13) | 9 | 1 | 0 | 1 | 0 |
| M014 | [don_mua_goi](TU_DIEN_DU_LIEU.md#b14) | 15 | 2 | 0 | 3 | 2 |
| M015 | [lan_thanh_toan](TU_DIEN_DU_LIEU.md#b15) | 18 | 1 | 0 | 5 | 1 |
| M016 | [su_kien_thanh_toan](TU_DIEN_DU_LIEU.md#b16) | 21 | 1 | 0 | 1 | 2 |
| M017 | [dang_ky_goi_tap](TU_DIEN_DU_LIEU.md#b17) | 10 | 3 | 1 | 3 | 1 |
| M018 | [ky_han_hoi_vien](TU_DIEN_DU_LIEU.md#b18) | 24 | 4 | 3 | 4 | 2 |
| M019 | [su_dung_quyen_loi](TU_DIEN_DU_LIEU.md#b19) | 8 | 3 | 1 | 3 | 1 |
| M020 | [ma_vao_phong_tap](TU_DIEN_DU_LIEU.md#b20) | 10 | 2 | 0 | 2 | 1 |
| M021 | [lich_su_vao_phong_tap](TU_DIEN_DU_LIEU.md#b21) | 9 | 6 | 2 | 2 | 2 |
| M022 | [phan_cong_huan_luyen_vien](TU_DIEN_DU_LIEU.md#b22) | 10 | 3 | 0 | 4 | 2 |
| M023 | [lich_su_su_dung_huan_luyen_vien](TU_DIEN_DU_LIEU.md#b23) | 14 | 5 | 2 | 2 | 2 |
| M024 | [ghi_chu_huan_luyen](TU_DIEN_DU_LIEU.md#b24) | 8 | 5 | 3 | 0 | 1 |
| M025 | [dung_cu](TU_DIEN_DU_LIEU.md#b25) | 7 | 0 | 0 | 1 | 0 |
| M026 | [nhom_co](TU_DIEN_DU_LIEU.md#b26) | 6 | 0 | 0 | 1 | 0 |
| M027 | [bai_tap](TU_DIEN_DU_LIEU.md#b27) | 13 | 1 | 0 | 1 | 1 |
| M028 | [bai_tap_dung_cu](TU_DIEN_DU_LIEU.md#b28) | 5 | 2 | 0 | 1 | 0 |
| M029 | [bai_tap_nhom_co](TU_DIEN_DU_LIEU.md#b29) | 6 | 2 | 0 | 1 | 0 |
| M030 | [giao_an_mau](TU_DIEN_DU_LIEU.md#b30) | 12 | 1 | 0 | 1 | 1 |
| M031 | [ngay_trong_giao_an](TU_DIEN_DU_LIEU.md#b31) | 7 | 1 | 0 | 1 | 0 |
| M032 | [bai_tap_trong_giao_an](TU_DIEN_DU_LIEU.md#b32) | 11 | 2 | 0 | 1 | 0 |
| M033 | [ke_hoach_tap](TU_DIEN_DU_LIEU.md#b33) | 10 | 3 | 1 | 4 | 1 |
| M034 | [phien_ban_ke_hoach_tap](TU_DIEN_DU_LIEU.md#b34) | 14 | 5 | 1 | 3 | 1 |
| M035 | [ngay_trong_ke_hoach](TU_DIEN_DU_LIEU.md#b35) | 8 | 1 | 0 | 3 | 0 |
| M036 | [bai_tap_trong_ke_hoach](TU_DIEN_DU_LIEU.md#b36) | 15 | 2 | 0 | 2 | 0 |
| M037 | [buoi_tap_du_kien](TU_DIEN_DU_LIEU.md#b37) | 15 | 5 | 3 | 5 | 2 |
| M038 | [phien_tap](TU_DIEN_DU_LIEU.md#b38) | 13 | 2 | 1 | 4 | 2 |
| M039 | [bai_tap_trong_phien](TU_DIEN_DU_LIEU.md#b39) | 16 | 3 | 0 | 2 | 1 |
| M040 | [hiep_tap](TU_DIEN_DU_LIEU.md#b40) | 11 | 1 | 0 | 2 | 0 |
| M041 | [hoi_thoai](TU_DIEN_DU_LIEU.md#b41) | 9 | 3 | 1 | 2 | 2 |
| M042 | [tin_nhan](TU_DIEN_DU_LIEU.md#b42) | 12 | 6 | 3 | 3 | 1 |
| M043 | [su_kien_phat_tin_nhan](TU_DIEN_DU_LIEU.md#b43) | 9 | 1 | 0 | 1 | 1 |
| M044 | [hoi_thoai_tro_ly](TU_DIEN_DU_LIEU.md#b44) | 7 | 1 | 0 | 1 | 1 |
| M045 | [tin_nhan_tro_ly](TU_DIEN_DU_LIEU.md#b45) | 9 | 2 | 1 | 3 | 1 |
| M046 | [yeu_cau_tro_ly](TU_DIEN_DU_LIEU.md#b46) | 18 | 5 | 4 | 5 | 3 |
| M047 | [bai_tap_ung_vien](TU_DIEN_DU_LIEU.md#b47) | 7 | 2 | 0 | 1 | 0 |
| M048 | [giao_an_ung_vien](TU_DIEN_DU_LIEU.md#b48) | 7 | 2 | 0 | 1 | 0 |
| M049 | [lan_goi_mo_hinh](TU_DIEN_DU_LIEU.md#b49) | 19 | 1 | 0 | 2 | 1 |
| M050 | [de_xuat_ke_hoach_tap](TU_DIEN_DU_LIEU.md#b50) | 25 | 7 | 4 | 1 | 2 |
| M051 | [nhat_ky_he_thong](TU_DIEN_DU_LIEU.md#b51) | 12 | 1 | 0 | 0 | 2 |
| M052 | [yeu_cau_chong_lap](TU_DIEN_DU_LIEU.md#b52) | 11 | 1 | 0 | 1 | 2 |
| **Tổng** | **52 bảng** | **575** | **113** | **31** | **98** | **48** |

Không cộng bảng `migrations` của framework vào tổng 52. Không tái tạo migration mặc định của Laravel cho tài khoản, cache, job hoặc session. Các file auth/schema có mặt trong kế hoạch không đồng nghĩa đã triển khai đăng nhập hoặc Sanctum.

## 5. P2 — Kế hoạch gắn FK

Chỉ bắt đầu khi **toàn bộ M001–M052 đã thành công**. Mỗi nhóm gắn các FK có **bảng nguồn** thuộc nhóm, kể cả bảng đích nằm ở nhóm khác hoặc ở phía sau trong danh mục.

| Thứ tự | Tên phần việc / hậu tố file | Bảng nguồn | FK đơn | FK ghép | Tổng FK |
| --- | --- | --- | ---: | ---: | ---: |
| M053 | `them_khoa_ngoai_tai_khoan` | B01–B11 | 12 | 0 | 12 |
| M054 | `them_khoa_ngoai_goi_tap_thanh_toan` | B12–B19 | 17 | 5 | 22 |
| M055 | `them_khoa_ngoai_huan_luyen_va_vao_phong` | B20–B24 | 21 | 7 | 28 |
| M056 | `them_khoa_ngoai_thu_vien` | B25–B32 | 9 | 0 | 9 |
| M057 | `them_khoa_ngoai_ke_hoach_va_lich_su` | B33–B40 | 22 | 6 | 28 |
| M058 | `them_khoa_ngoai_tro_chuyen` | B41–B43 | 10 | 4 | 14 |
| M059 | `them_khoa_ngoai_tro_ly_va_de_xuat` | B44–B50 | 20 | 9 | 29 |
| M060 | `them_khoa_ngoai_nhat_ky_va_chong_lap` | B51–B52 | 2 | 0 | 2 |
| **Tổng** | **8 file gắn FK dự kiến** | **48 bảng có FK đi ra** | **113** | **31** | **144** |

### 5.1. Quy tắc cho cả 144 FK

- Giữ đủ 113 FK đơn và 31 FK ghép, không bỏ FK đơn vì cho rằng FK ghép đã thay thế.
- FK đơn trỏ đúng `id` của bảng chính thức; đặc biệt `hoi_vien_id` → `ho_so_hoi_vien.id` và `huan_luyen_vien_id` → `ho_so_huan_luyen_vien.id`.
- Giữ thứ tự cột nguồn/đích đúng từng tuple. Bảng đích có PK/UNIQUE **đúng bộ cột và thứ tự** trước khi gắn FK.
- Giữ kiểu, signedness, chiều dài và collation tương thích giữa các cặp cột.
- Mọi FK giữ **hành vi ON DELETE RESTRICT / ON UPDATE RESTRICT**. Ưu tiên explicit RESTRICT; chỉ dùng default/NO ACTION của **InnoDB** khi probe 2.4 chứng minh cần ngoại lệ cú pháp và vẫn từ chối sửa/xóa khóa cha tương đương. Ghi theo từng FK trong manifest vật lý; không bỏ CHECK, không CASCADE/SET NULL.
- Có index hỗ trợ đúng tiền tố và thứ tự ở phía con. Không chỉ kiểm tra “các cột đã có index ở đâu đó”.
- Không tạo FK trên hoặc trỏ tới 5 cột sinh VIRTUAL.
- FK nullable và FK ghép có thành phần NULL không thay thế kiểm tra nguồn, ownership và trạng thái trong transaction.
- Audit có tham chiếu logic đối tượng không được biến thành FK đa hình giả; quan hệ tài chính/quyền/Plan vẫn dùng các FK thật đã duyệt.
- Khi gặp lỗi FK, đối chiếu schema đích/kiểu/UNIQUE/collation và dữ liệu; không dùng tắt kiểm tra FK hoặc xóa dữ liệu để cho migration chạy tiếp.

### 5.2. Manifest 31 FK ghép

Tên constraint dưới đây là tên kỹ thuật **đề xuất cho bước migration**, không đổi tên bảng/cột. Mỗi constraint thuộc file M053–M060 theo bảng nguồn ở trên.

| Tên constraint dự kiến | Bảng/cột nguồn theo đúng thứ tự | Bảng/cột đích theo đúng thứ tự |
| --- | --- | --- |
| `khoa_ngoai_ghep_b17_01` | `dang_ky_goi_tap(lan_su_dung_dau_tien_id, hoi_vien_id)` | `su_dung_quyen_loi(id, hoi_vien_id)` |
| `khoa_ngoai_ghep_b18_01` | `ky_han_hoi_vien(don_mua_goi_id, hoi_vien_id)` | `don_mua_goi(id, hoi_vien_id)` |
| `khoa_ngoai_ghep_b18_02` | `ky_han_hoi_vien(lan_thanh_toan_id, don_mua_goi_id)` | `lan_thanh_toan(id, don_mua_goi_id)` |
| `khoa_ngoai_ghep_b18_03` | `ky_han_hoi_vien(dang_ky_goi_tap_id, hoi_vien_id)` | `dang_ky_goi_tap(id, hoi_vien_id)` |
| `khoa_ngoai_ghep_b19_01` | `su_dung_quyen_loi(ky_han_hoi_vien_id, hoi_vien_id)` | `ky_han_hoi_vien(id, hoi_vien_id)` |
| `khoa_ngoai_ghep_b21_01` | `lich_su_vao_phong_tap(ma_vao_phong_tap_id, hoi_vien_id, chi_nhanh_id)` | `ma_vao_phong_tap(id, hoi_vien_id, chi_nhanh_id)` |
| `khoa_ngoai_ghep_b21_02` | `lich_su_vao_phong_tap(su_dung_quyen_loi_id, hoi_vien_id, ky_han_hoi_vien_id)` | `su_dung_quyen_loi(id, hoi_vien_id, ky_han_hoi_vien_id)` |
| `khoa_ngoai_ghep_b23_01` | `lich_su_su_dung_huan_luyen_vien(phan_cong_huan_luyen_vien_id, hoi_vien_id, huan_luyen_vien_id)` | `phan_cong_huan_luyen_vien(id, hoi_vien_id, huan_luyen_vien_id)` |
| `khoa_ngoai_ghep_b23_02` | `lich_su_su_dung_huan_luyen_vien(su_dung_quyen_loi_id, hoi_vien_id, ky_han_hoi_vien_id)` | `su_dung_quyen_loi(id, hoi_vien_id, ky_han_hoi_vien_id)` |
| `khoa_ngoai_ghep_b24_01` | `ghi_chu_huan_luyen(phan_cong_huan_luyen_vien_id, hoi_vien_id)` | `phan_cong_huan_luyen_vien(id, hoi_vien_id)` |
| `khoa_ngoai_ghep_b24_02` | `ghi_chu_huan_luyen(ke_hoach_tap_id, hoi_vien_id)` | `ke_hoach_tap(id, hoi_vien_id)` |
| `khoa_ngoai_ghep_b24_03` | `ghi_chu_huan_luyen(phien_tap_id, hoi_vien_id)` | `phien_tap(id, hoi_vien_id)` |
| `khoa_ngoai_ghep_b33_01` | `ke_hoach_tap(phien_ban_hien_tai_id, id)` | `phien_ban_ke_hoach_tap(id, ke_hoach_tap_id)` |
| `khoa_ngoai_ghep_b34_01` | `phien_ban_ke_hoach_tap(phien_ban_truoc_id, ke_hoach_tap_id)` | `phien_ban_ke_hoach_tap(id, ke_hoach_tap_id)` |
| `khoa_ngoai_ghep_b37_01` | `buoi_tap_du_kien(ke_hoach_tap_id, hoi_vien_id)` | `ke_hoach_tap(id, hoi_vien_id)` |
| `khoa_ngoai_ghep_b37_02` | `buoi_tap_du_kien(phien_ban_ke_hoach_tap_id, ke_hoach_tap_id)` | `phien_ban_ke_hoach_tap(id, ke_hoach_tap_id)` |
| `khoa_ngoai_ghep_b37_03` | `buoi_tap_du_kien(ngay_trong_ke_hoach_id, phien_ban_ke_hoach_tap_id)` | `ngay_trong_ke_hoach(id, phien_ban_ke_hoach_tap_id)` |
| `khoa_ngoai_ghep_b38_01` | `phien_tap(buoi_tap_du_kien_id, hoi_vien_id)` | `buoi_tap_du_kien(id, hoi_vien_id)` |
| `khoa_ngoai_ghep_b41_01` | `hoi_thoai(phan_cong_huan_luyen_vien_id, hoi_vien_id, huan_luyen_vien_id)` | `phan_cong_huan_luyen_vien(id, hoi_vien_id, huan_luyen_vien_id)` |
| `khoa_ngoai_ghep_b42_01` | `tin_nhan(hoi_thoai_id, phan_cong_huan_luyen_vien_id)` | `hoi_thoai(id, phan_cong_huan_luyen_vien_id)` |
| `khoa_ngoai_ghep_b42_02` | `tin_nhan(phan_cong_huan_luyen_vien_id, hoi_vien_id, huan_luyen_vien_id)` | `phan_cong_huan_luyen_vien(id, hoi_vien_id, huan_luyen_vien_id)` |
| `khoa_ngoai_ghep_b42_03` | `tin_nhan(su_dung_quyen_loi_id, hoi_vien_id)` | `su_dung_quyen_loi(id, hoi_vien_id)` |
| `khoa_ngoai_ghep_b45_01` | `tin_nhan_tro_ly(yeu_cau_tro_ly_id, hoi_thoai_tro_ly_id)` | `yeu_cau_tro_ly(id, hoi_thoai_tro_ly_id)` |
| `khoa_ngoai_ghep_b46_01` | `yeu_cau_tro_ly(hoi_thoai_tro_ly_id, hoi_vien_id)` | `hoi_thoai_tro_ly(id, hoi_vien_id)` |
| `khoa_ngoai_ghep_b46_02` | `yeu_cau_tro_ly(tin_nhan_dau_vao_id, hoi_thoai_tro_ly_id)` | `tin_nhan_tro_ly(id, hoi_thoai_tro_ly_id)` |
| `khoa_ngoai_ghep_b46_03` | `yeu_cau_tro_ly(ky_han_hoi_vien_id, hoi_vien_id)` | `ky_han_hoi_vien(id, hoi_vien_id)` |
| `khoa_ngoai_ghep_b46_04` | `yeu_cau_tro_ly(su_dung_quyen_loi_id, hoi_vien_id, ky_han_hoi_vien_id)` | `su_dung_quyen_loi(id, hoi_vien_id, ky_han_hoi_vien_id)` |
| `khoa_ngoai_ghep_b50_01` | `de_xuat_ke_hoach_tap(phan_cong_huan_luyen_vien_id, hoi_vien_id)` | `phan_cong_huan_luyen_vien(id, hoi_vien_id)` |
| `khoa_ngoai_ghep_b50_02` | `de_xuat_ke_hoach_tap(yeu_cau_tro_ly_id, hoi_vien_id)` | `yeu_cau_tro_ly(id, hoi_vien_id)` |
| `khoa_ngoai_ghep_b50_03` | `de_xuat_ke_hoach_tap(ke_hoach_tap_id, hoi_vien_id)` | `ke_hoach_tap(id, hoi_vien_id)` |
| `khoa_ngoai_ghep_b50_04` | `de_xuat_ke_hoach_tap(phien_ban_co_so_id, ke_hoach_tap_id)` | `phien_ban_ke_hoach_tap(id, ke_hoach_tap_id)` |

### 5.3. Manifest tên constraint và index

Đặt tên tường minh, ổn định, tối đa 64 ký tự; không để framework ghép toàn bộ tên bảng/cột dài vượt giới hạn. Dùng mã bảng Bxx để tra cứu, không viết tắt tên bảng/cột trong schema:

| Loại | Mẫu tên đề xuất | Đánh số |
| --- | --- | --- |
| FK đơn | `khoa_ngoai_don_bNN_XX` | Theo thứ tự các cột có FK ở BNN |
| FK ghép | `khoa_ngoai_ghep_bNN_XX` | Theo manifest 5.2 |
| UNIQUE | `duy_nhat_bNN_XX` | Theo thứ tự các bộ UNIQUE của từ điển |
| Index truy vấn | `chi_muc_bNN_XX` | Theo thứ tự khai báo index bổ sung |
| Index hỗ trợ FK còn thiếu | `chi_muc_tham_chieu_bNN_XX` | Theo tuple chưa được bao phủ |
| CHECK | `kiem_tra_bNN_XX` | Theo đúng 83 hàng A ở 6.3.2 |

Ví dụ `duy_nhat_b33_04` ứng với UNIQUE `ke_hoach_tap(hoi_vien_dang_su_dung_id)`; tên bảng/cột thật vẫn giữ nguyên. PK dùng cơ chế PRIMARY của MariaDB.

Ánh xạ A đã chốt ở 6.3.2; B liệt kê đủ UNIQUE/FK ở 6.3.3, tên FK ở 5.2/5.4. Trước khi viết migration, hoàn thiện ánh xạ index vật lý/statement và điền bằng chứng probe/ngoại lệ vào hồ sơ review; không tự đổi tuple/biểu thức. Không tạo bảng quản lý constraint trong Database.

98 UNIQUE ngoài PK được giữ đủ, kể cả các tuple chứa `id` để làm đích FK ghép. Với 48 index truy vấn, nếu trùng chính xác hoặc đã được PK/UNIQUE/index cùng tiền tố đáp ứng thì ghi rõ ánh xạ bao phủ; không coi số index vật lý bắt buộc là 48. Kiểm tra hướng/thứ tự cột và mục đích truy vấn trước khi hợp nhất; không tự bỏ UNIQUE bảo vệ nghiệp vụ.

### 5.4. Breakdown M053–M060 theo từng source table

Giữ **8 file FK**, vì sau P1 mọi bảng/UNIQUE đích đã tồn tại; chia nhỏ theo source table và theo dõi từng constraint đủ cô lập lỗi mà không cần đổi M001–M060. Tổng vẫn **52 + 8 = 60 file dự kiến**; chưa có lý do kỹ thuật được kiểm chứng để tăng file count.

**48 đơn vị nguồn** dưới đây là các block ALTER/`Schema::table` bên trong 8 file, **không phải 48 file thêm**. B01, B03, B25, B26 không có FK nguồn nên không có block ALTER ở P2. Trong mỗi file: theo B tăng dần, FK đơn theo thứ tự cột từ điển, sau đó FK ghép theo 5.2. Phải đặt tên tường minh cho từng FK.

| Đơn vị trong file | Source table | FK đơn: tên theo thứ tự | FK ghép: tên theo thứ tự | Tổng FK / đơn vị |
| --- | --- | --- | --- | ---: |
| **M053.B02** | `nguoi_dung` | `khoa_ngoai_don_b02_01` | — | 1 |
| **M053.B04** | `phan_quyen_nguoi_dung` | `khoa_ngoai_don_b04_01`<br>`khoa_ngoai_don_b04_02`<br>`khoa_ngoai_don_b04_03` | — | 3 |
| **M053.B05** | `ho_so_hoi_vien` | `khoa_ngoai_don_b05_01` | — | 1 |
| **M053.B06** | `ho_so_huan_luyen_vien` | `khoa_ngoai_don_b06_01` | — | 1 |
| **M053.B07** | `ngay_ranh_hoi_vien` | `khoa_ngoai_don_b07_01` | — | 1 |
| **M053.B08** | `dung_cu_hoi_vien` | `khoa_ngoai_don_b08_01`<br>`khoa_ngoai_don_b08_02` | — | 2 |
| **M053.B09** | `chi_so_co_the` | `khoa_ngoai_don_b09_01` | — | 1 |
| **M053.B10** | `the_truy_cap` | `khoa_ngoai_don_b10_01` | — | 1 |
| **M053.B11** | `yeu_cau_dat_lai_mat_khau` | `khoa_ngoai_don_b11_01` | — | 1 |
| **M054.B12** | `goi_tap` | `khoa_ngoai_don_b12_01`<br>`khoa_ngoai_don_b12_02` | — | 2 |
| **M054.B13** | `quyen_loi_goi_tap` | `khoa_ngoai_don_b13_01` | — | 1 |
| **M054.B14** | `don_mua_goi` | `khoa_ngoai_don_b14_01`<br>`khoa_ngoai_don_b14_02` | — | 2 |
| **M054.B15** | `lan_thanh_toan` | `khoa_ngoai_don_b15_01` | — | 1 |
| **M054.B16** | `su_kien_thanh_toan` | `khoa_ngoai_don_b16_01` | — | 1 |
| **M054.B17** | `dang_ky_goi_tap` | `khoa_ngoai_don_b17_01`<br>`khoa_ngoai_don_b17_02`<br>`khoa_ngoai_don_b17_03` | `khoa_ngoai_ghep_b17_01` | 4 |
| **M054.B18** | `ky_han_hoi_vien` | `khoa_ngoai_don_b18_01`<br>`khoa_ngoai_don_b18_02`<br>`khoa_ngoai_don_b18_03`<br>`khoa_ngoai_don_b18_04` | `khoa_ngoai_ghep_b18_01`<br>`khoa_ngoai_ghep_b18_02`<br>`khoa_ngoai_ghep_b18_03` | 7 |
| **M054.B19** | `su_dung_quyen_loi` | `khoa_ngoai_don_b19_01`<br>`khoa_ngoai_don_b19_02`<br>`khoa_ngoai_don_b19_03` | `khoa_ngoai_ghep_b19_01` | 4 |
| **M055.B20** | `ma_vao_phong_tap` | `khoa_ngoai_don_b20_01`<br>`khoa_ngoai_don_b20_02` | — | 2 |
| **M055.B21** | `lich_su_vao_phong_tap` | `khoa_ngoai_don_b21_01`<br>`khoa_ngoai_don_b21_02`<br>`khoa_ngoai_don_b21_03`<br>`khoa_ngoai_don_b21_04`<br>`khoa_ngoai_don_b21_05`<br>`khoa_ngoai_don_b21_06` | `khoa_ngoai_ghep_b21_01`<br>`khoa_ngoai_ghep_b21_02` | 8 |
| **M055.B22** | `phan_cong_huan_luyen_vien` | `khoa_ngoai_don_b22_01`<br>`khoa_ngoai_don_b22_02`<br>`khoa_ngoai_don_b22_03` | — | 3 |
| **M055.B23** | `lich_su_su_dung_huan_luyen_vien` | `khoa_ngoai_don_b23_01`<br>`khoa_ngoai_don_b23_02`<br>`khoa_ngoai_don_b23_03`<br>`khoa_ngoai_don_b23_04`<br>`khoa_ngoai_don_b23_05` | `khoa_ngoai_ghep_b23_01`<br>`khoa_ngoai_ghep_b23_02` | 7 |
| **M055.B24** | `ghi_chu_huan_luyen` | `khoa_ngoai_don_b24_01`<br>`khoa_ngoai_don_b24_02`<br>`khoa_ngoai_don_b24_03`<br>`khoa_ngoai_don_b24_04`<br>`khoa_ngoai_don_b24_05` | `khoa_ngoai_ghep_b24_01`<br>`khoa_ngoai_ghep_b24_02`<br>`khoa_ngoai_ghep_b24_03` | 8 |
| **M056.B27** | `bai_tap` | `khoa_ngoai_don_b27_01` | — | 1 |
| **M056.B28** | `bai_tap_dung_cu` | `khoa_ngoai_don_b28_01`<br>`khoa_ngoai_don_b28_02` | — | 2 |
| **M056.B29** | `bai_tap_nhom_co` | `khoa_ngoai_don_b29_01`<br>`khoa_ngoai_don_b29_02` | — | 2 |
| **M056.B30** | `giao_an_mau` | `khoa_ngoai_don_b30_01` | — | 1 |
| **M056.B31** | `ngay_trong_giao_an` | `khoa_ngoai_don_b31_01` | — | 1 |
| **M056.B32** | `bai_tap_trong_giao_an` | `khoa_ngoai_don_b32_01`<br>`khoa_ngoai_don_b32_02` | — | 2 |
| **M057.B33** | `ke_hoach_tap` | `khoa_ngoai_don_b33_01`<br>`khoa_ngoai_don_b33_02`<br>`khoa_ngoai_don_b33_03` | `khoa_ngoai_ghep_b33_01` | 4 |
| **M057.B34** | `phien_ban_ke_hoach_tap` | `khoa_ngoai_don_b34_01`<br>`khoa_ngoai_don_b34_02`<br>`khoa_ngoai_don_b34_03`<br>`khoa_ngoai_don_b34_04`<br>`khoa_ngoai_don_b34_05` | `khoa_ngoai_ghep_b34_01` | 6 |
| **M057.B35** | `ngay_trong_ke_hoach` | `khoa_ngoai_don_b35_01` | — | 1 |
| **M057.B36** | `bai_tap_trong_ke_hoach` | `khoa_ngoai_don_b36_01`<br>`khoa_ngoai_don_b36_02` | — | 2 |
| **M057.B37** | `buoi_tap_du_kien` | `khoa_ngoai_don_b37_01`<br>`khoa_ngoai_don_b37_02`<br>`khoa_ngoai_don_b37_03`<br>`khoa_ngoai_don_b37_04`<br>`khoa_ngoai_don_b37_05` | `khoa_ngoai_ghep_b37_01`<br>`khoa_ngoai_ghep_b37_02`<br>`khoa_ngoai_ghep_b37_03` | 8 |
| **M057.B38** | `phien_tap` | `khoa_ngoai_don_b38_01`<br>`khoa_ngoai_don_b38_02` | `khoa_ngoai_ghep_b38_01` | 3 |
| **M057.B39** | `bai_tap_trong_phien` | `khoa_ngoai_don_b39_01`<br>`khoa_ngoai_don_b39_02`<br>`khoa_ngoai_don_b39_03` | — | 3 |
| **M057.B40** | `hiep_tap` | `khoa_ngoai_don_b40_01` | — | 1 |
| **M058.B41** | `hoi_thoai` | `khoa_ngoai_don_b41_01`<br>`khoa_ngoai_don_b41_02`<br>`khoa_ngoai_don_b41_03` | `khoa_ngoai_ghep_b41_01` | 4 |
| **M058.B42** | `tin_nhan` | `khoa_ngoai_don_b42_01`<br>`khoa_ngoai_don_b42_02`<br>`khoa_ngoai_don_b42_03`<br>`khoa_ngoai_don_b42_04`<br>`khoa_ngoai_don_b42_05`<br>`khoa_ngoai_don_b42_06` | `khoa_ngoai_ghep_b42_01`<br>`khoa_ngoai_ghep_b42_02`<br>`khoa_ngoai_ghep_b42_03` | 9 |
| **M058.B43** | `su_kien_phat_tin_nhan` | `khoa_ngoai_don_b43_01` | — | 1 |
| **M059.B44** | `hoi_thoai_tro_ly` | `khoa_ngoai_don_b44_01` | — | 1 |
| **M059.B45** | `tin_nhan_tro_ly` | `khoa_ngoai_don_b45_01`<br>`khoa_ngoai_don_b45_02` | `khoa_ngoai_ghep_b45_01` | 3 |
| **M059.B46** | `yeu_cau_tro_ly` | `khoa_ngoai_don_b46_01`<br>`khoa_ngoai_don_b46_02`<br>`khoa_ngoai_don_b46_03`<br>`khoa_ngoai_don_b46_04`<br>`khoa_ngoai_don_b46_05` | `khoa_ngoai_ghep_b46_01`<br>`khoa_ngoai_ghep_b46_02`<br>`khoa_ngoai_ghep_b46_03`<br>`khoa_ngoai_ghep_b46_04` | 9 |
| **M059.B47** | `bai_tap_ung_vien` | `khoa_ngoai_don_b47_01`<br>`khoa_ngoai_don_b47_02` | — | 2 |
| **M059.B48** | `giao_an_ung_vien` | `khoa_ngoai_don_b48_01`<br>`khoa_ngoai_don_b48_02` | — | 2 |
| **M059.B49** | `lan_goi_mo_hinh` | `khoa_ngoai_don_b49_01` | — | 1 |
| **M059.B50** | `de_xuat_ke_hoach_tap` | `khoa_ngoai_don_b50_01`<br>`khoa_ngoai_don_b50_02`<br>`khoa_ngoai_don_b50_03`<br>`khoa_ngoai_don_b50_04`<br>`khoa_ngoai_don_b50_05`<br>`khoa_ngoai_don_b50_06`<br>`khoa_ngoai_don_b50_07` | `khoa_ngoai_ghep_b50_01`<br>`khoa_ngoai_ghep_b50_02`<br>`khoa_ngoai_ghep_b50_03`<br>`khoa_ngoai_ghep_b50_04` | 11 |
| **M060.B51** | `nhat_ky_he_thong` | `khoa_ngoai_don_b51_01` | — | 1 |
| **M060.B52** | `yeu_cau_chong_lap` | `khoa_ngoai_don_b52_01` | — | 1 |

| File | Số source table | FK đơn | FK ghép | Tổng FK |
| --- | ---: | ---: | ---: | ---: |
| M053 | 9 | 12 | 0 | 12 |
| M054 | 8 | 17 | 5 | 22 |
| M055 | 5 | 21 | 7 | 28 |
| M056 | 6 | 9 | 0 | 9 |
| M057 | 8 | 22 | 6 | 28 |
| M058 | 3 | 10 | 4 | 14 |
| M059 | 7 | 20 | 9 | 29 |
| M060 | 2 | 2 | 0 | 2 |
| **Tổng** | **48** | **113** | **31** | **144** |

#### 5.4.1. Ranh giới ALTER và tiến độ bền vững

- Mỗi source table là một đơn vị riêng, không gom FK của nhiều source table thành một DDL/block không truy được rollback. Một `Schema::table` có thể sinh nhiều statement; **không giả định toàn block hoặc toàn file atomic**. P0-T15 phải inspect các statement thực tế.
- Trước khi chạy đơn vị, biết đầy đủ danh sách FK mong đợi và snapshot metadata của source table. Theo dõi từng statement/tên FK ở mức đủ nhận biết phần đã gắn; chỉ đánh dấu source table hoàn tất khi metadata xác nhận **tất cả** FK của đơn vị đúng tuple/index/action/CHECK.
- Log triển khai bền vững ngoài transaction DDL ghi tối thiểu: mã file, thứ tự/source table đang làm, tên FK/statement đang thử, danh sách FK đã xác minh, source table cuối đã hoàn tất, trạng thái đơn vị dở và lỗi đã lọc. Đây là log/bằng chứng triển khai, **không thêm bảng progress hoặc cột nghiệp vụ**.
- Nếu process chết sau khi DDL thành công nhưng trước khi ghi log, log không phải sự thật cuối cùng: đối chiếu metadata để tái dựng chính xác bảng cuối hoàn tất và phần dở. Không suy “file chưa ở bảng migrations ⇒ chưa gắn FK nào”.
- Không bắt đầu đơn vị tiếp theo nếu đơn vị hiện tại chưa được xác minh hoàn tất. Không bắt exception rồi bỏ qua FK để Laravel đánh dấu file thành công.

#### 5.4.2. Ví dụ failure boundary và thứ tự down

Ví dụ **M055**: B20 → B21 → B22 hoàn tất; ở B23 đã có `khoa_ngoai_don_b23_01` và `_02`, statement tiếp theo lỗi. Ghi bảng cuối hoàn tất là **B22**, bảng dở là **B23**, B24 chưa bắt đầu. Metadata phải xác nhận lại hai FK B23 thực sự có/đúng; không kết luận từ log gửi command. Nếu framework gộp nhiều FK vào một statement, xác định kết quả theo tính atomic của **statement đó** và metadata, không giả từng FK đã thành công.

`down`/khôi phục phần dở phải đảo **M060 → M053**, trong mỗi file đảo **source table**, trong mỗi table đảo **thứ tự tên FK đã thêm** (ghép trước, đơn sau, mỗi loại từ cuối về đầu). Chỉ tháo đúng FK thuộc manifest. Khi phục hồi file chưa hoàn tất, chỉ thao tác phần đã xác minh tồn tại/đúng định nghĩa; không `dropIfExists` mù rồi che drift. Sau tháo, đối chiếu metadata trước khi quay lại nguồn trước đó.

Các file nhiều FK có lộ trình đảo rõ:

- **M055:** B24 → B23 → B22 → B21 → B20; tổng 28 FK.
- **M057:** B40 → B39 → B38 → B37 → B36 → B35 → B34 → B33; tổng 28 FK.
- **M059:** B50 → B49 → B48 → B47 → B46 → B45 → B44; tổng 29 FK.

Không drop bảng/UNIQUE đích, không xóa CHECK và không tắt kiểm tra FK để xử lý lỗi attach. Chưa tháo hết FK P2 thì chưa down bảng P1. Mục 9 quy định riêng môi trường thử nghiệm có thể xóa và môi trường có dữ liệu thật.

## 6. Quy ước chuyển từ điển sang migration

### 6.1. Kiểu dữ liệu, NULL và default

| Thiết kế | Cách giữ đúng khi triển khai |
| --- | --- |
| `id BIGINT UNSIGNED AUTO_INCREMENT` | Một PK mỗi bảng; không đổi sang INT/UUID |
| FK số | BIGINT UNSIGNED, NULL đúng từ điển, không dùng 0 thay NULL |
| `DATETIME(6)` | Giữ microsecond và UTC; không âm thầm đổi thành TIMESTAMP |
| `DATE` / `TIME` | Ngày/giờ lịch địa phương đúng thiết kế; không cho lịch qua nửa đêm ngoài MVP |
| `DECIMAL(15,0)` | Tiền VND chính xác; không FLOAT/DOUBLE |
| DECIMAL cho số đo/tạ | Giữ đúng precision/scale tại từng cột |
| VARCHAR/CHAR/TEXT | Giữ độ dài, Unicode hoặc so sánh mã chính xác theo loại dữ liệu |
| JSON | Giữ JSON; không thêm FK giả vào ID nằm bên trong payload |
| BOOLEAN | Giá trị 0/1; không coi kiểu BOOLEAN tự chặn mọi số khác |
| Timestamp hệ thống | Chỉ tạo `ngay_tao`/`ngay_cap_nhat` ở bảng có liệt kê; không gọi timestamps() để thêm cột tiếng Anh |

Default chỉ lấy từ mô tả trong từ điển, ví dụ các counter mặc định 0. Cột bắt buộc khác do Backend cung cấp; không tự thêm default để che việc thiếu dữ liệu. Không thêm `deleted_at`/soft delete đại trà: Q12 yêu cầu giữ lịch sử bằng nghiệp vụ hiện có, không cấp phép thêm cột.

Charset/collation được chốt tường minh tại 6.1.1–6.1.3; không còn lựa chọn ASCII/binary chung chung để tự quyết khi code. Giữ nguyên kiểu và tên cột trong từ điển.

Laravel Schema Builder có thể hỗ trợ cột sinh và index, nhưng không giả định có một API CHECK chung dùng được cho mọi driver/version. Khi được yêu cầu code, đối chiếu API của dependency đã khóa và dùng cách triển khai MariaDB tường minh nếu cần; **không có PHP/SQL thực thi trong tài liệu này**.

#### 6.1.1. Charset/collation policy vật lý đã chốt

| Phạm vi | Charset / collation bắt buộc | Cách sử dụng |
| --- | --- | --- |
| Database và mặc định của cả 52 bảng InnoDB | `utf8mb4` / `utf8mb4_unicode_ci` | Text nghiệp vụ Unicode: tên, mô tả, hướng dẫn, ghi chú, nội dung Chat; so sánh/tìm kiếm mặc định không phân biệt hoa thường/dấu. Collation không sửa nội dung được lưu. |
| Chuỗi kỹ thuật ở bảng override bên dưới | `utf8mb4` / `utf8mb4_nopad_bin` | Phân biệt case và mã ký tự, NO PAD; không dùng collation mặc định để so token/hash/reference/UUID/mã enum. Giữ nguyên CHAR/VARCHAR và chiều dài từ điển, không đổi sang BINARY/VARBINARY hoặc tự giới hạn Unicode thành ASCII. |
| Riêng `nguoi_dung.thu_dien_tu` | `utf8mb4` / `utf8mb4_nopad_bin` | VARCHAR(254) giữ nguyên; Backend canonicalize trim + NFC + lowercase toàn địa chỉ trước UNIQUE/lookup để không phân biệt case, còn collation binary giữ phân biệt dấu. |
| JSON, số, DATE/TIME/DATETIME và FK số | Không gán collation chuỗi | Giữ đúng kiểu; JSON validation/ID bên trong payload thuộc Backend, không thêm cột hay FK. |

MariaDB 10.4 có `utf8mb4_unicode_ci`, `utf8mb4_nopad_bin` và `utf8mb4_bin`; không có bộ MySQL `utf8mb4_0900_*`. Dùng `utf8mb4_nopad_bin` cho mã để giữ phân biệt case/ký tự và không gộp khoảng trắng cuối; binary collation không đổi kiểu thành BINARY. Email dùng cùng collation sau khi Backend trim, NFC và lowercase toàn địa chỉ để tái tạo semantics case-insensitive nhưng accent-sensitive. [MariaDB — Character sets/collations](https://mariadb.com/docs/server/reference/data-types/string-data-types/character-sets/supported-character-sets-and-collations), [MariaDB — 10.4 differences](https://mariadb.com/docs/release-notes/community-server/about/compatibility-and-differences/incompatibilities-and-feature-differences-between-mariadb-and-mysql-unmaint/incompatibilities-and-feature-differences-between-mariadb-10-4-and-mysql-8).

Backend chuẩn hóa email bằng cùng một đường xử lý khi tạo/cập nhật/tra cứu: loại khoảng trắng ngoài địa chỉ, chuẩn hóa Unicode NFC, lowercase phần domain; không loại dấu, không xóa dấu chấm hoặc `+tag`, không áp alias riêng của nhà cung cấp. Phần local giữ nội dung sau chuẩn hóa; Backend lowercase toàn địa chỉ trước lưu/lookup, còn `utf8mb4_nopad_bin` giữ phân biệt dấu. Không dùng một phép so khác ở truy vấn UNIQUE/lookup. Không cần đổi kiểu lưu hay thêm cột email; **email verification không trở thành điều kiện sử dụng**. Ca `Member@example.com` và `member@example.com` phải xung đột UNIQUE; `tung@example.com` và `túng@example.com` phân biệt dấu. Probe thêm biểu diễn Unicode tương đương sau normalization để mọi writer dùng cùng policy.

#### 6.1.2. Danh sách override chuỗi kỹ thuật

**95 cột** dưới đây đều dùng `utf8mb4_nopad_bin` tường minh, kể cả cột nullable hoặc cột sinh CHAR(36). Ngoài danh sách này và email ở trên, cột chuỗi dùng mặc định `utf8mb4_unicode_ci`. Không gán “binary policy” chung chung rồi để Agent tự chọn khác nhau. Mã catalog cũng là mã kỹ thuật; tên hiển thị tương ứng vẫn là Unicode nghiệp vụ. Cột orderCode dạng BIGINT của payOS không nằm trong danh sách vì không phải chuỗi.

| Bảng | Cột override (tên giữ nguyên) |
| --- | --- |
| `chi_nhanh` | `ma_chi_nhanh`, `mui_gio`, `trang_thai` |
| `nguoi_dung` | `mat_khau_bam`, `trang_thai` |
| `vai_tro` | `ma_vai_tro` |
| `ho_so_hoi_vien` | `ma_hoi_vien` |
| `ho_so_huan_luyen_vien` | `ma_huan_luyen_vien`, `trang_thai` |
| `chi_so_co_the` | `ma_lan_ghi` |
| `the_truy_cap` | `ma_bam_the` |
| `yeu_cau_dat_lai_mat_khau` | `ma_bam_xac_nhan` |
| `goi_tap` | `ma_goi`, `trang_thai` |
| `don_mua_goi` | `ma_don`, `ma_yeu_cau`, `don_vi_tien`, `trang_thai` |
| `lan_thanh_toan` | `ma_kenh_thanh_toan`, `ma_lien_ket_thanh_toan`, `don_vi_tien`, `trang_thai`, `ma_tham_chieu_duoc_chap_nhan`, `ma_loi` |
| `su_kien_thanh_toan` | `ma_kenh_thanh_toan`, `ma_lien_ket_thanh_toan`, `ma_tham_chieu`, `don_vi_tien`, `ma_ket_qua`, `khoa_chong_lap`, `ma_bam_noi_dung`, `trang_thai_xu_ly` |
| `dang_ky_goi_tap` | `trang_thai` |
| `ky_han_hoi_vien` | `trang_thai` |
| `su_dung_quyen_loi` | `loai_su_dung`, `ma_hanh_dong` |
| `ma_vao_phong_tap` | `ma_bam_bi_mat` |
| `lich_su_su_dung_huan_luyen_vien` | `ma_buoi_huan_luyen`, `trang_thai`, `nguon_thao_tac` |
| `dung_cu` | `ma_dung_cu`, `trang_thai` |
| `nhom_co` | `ma_nhom_co` |
| `bai_tap` | `ma_bai_tap`, `trang_thai` |
| `bai_tap_nhom_co` | `vai_tro_nhom_co` |
| `giao_an_mau` | `ma_giao_an`, `trang_thai` |
| `ke_hoach_tap` | `trang_thai`, `ma_lan_tao` |
| `phien_ban_ke_hoach_tap` | `nguon_tao`, `ma_bam_noi_dung` |
| `ngay_trong_ke_hoach` | `ma_ngay_logic` |
| `bai_tap_trong_ke_hoach` | `ma_bai_logic` |
| `buoi_tap_du_kien` | `ma_buoi_logic`, `trang_thai`, `ma_buoi_con_hieu_luc` |
| `phien_tap` | `ma_lan_bat_dau`, `trang_thai`, `ma_lan_hoan_thanh` |
| `bai_tap_trong_phien` | `ma_bai_thuc_hien` |
| `hiep_tap` | `ma_hiep_thuc_hien` |
| `tin_nhan` | `ma_tin_nhan_phia_gui` |
| `su_kien_phat_tin_nhan` | `trang_thai` |
| `hoi_thoai_tro_ly` | `trang_thai` |
| `tin_nhan_tro_ly` | `nguon_tin`, `ma_tin_nhan_phia_gui` |
| `yeu_cau_tro_ly` | `ma_yeu_cau`, `loai_yeu_cau`, `phien_ban_quy_tac`, `trang_thai`, `trang_thai_han_muc`, `ma_loi` |
| `lan_goi_mo_hinh` | `nha_cung_cap`, `ten_mo_hinh`, `ma_yeu_cau_nha_cung_cap`, `phien_ban_mau_lenh`, `phien_ban_cau_truc`, `ma_bam_phan_hoi`, `trang_thai`, `ma_loi` |
| `de_xuat_ke_hoach_tap` | `nguon_de_xuat`, `loai_thay_doi`, `phien_ban_cau_truc`, `ma_bam_noi_dung`, `trang_thai` |
| `nhat_ky_he_thong` | `loai_tac_nhan`, `hanh_dong`, `loai_doi_tuong`, `khoa_tuong_quan`, `ket_qua` |
| `yeu_cau_chong_lap` | `pham_vi`, `khoa_yeu_cau`, `ma_bam_noi_dung`, `trang_thai` |

#### 6.1.3. Tương thích FK, CHAR và nghiệm thu collation

- Đối chiếu hiện tại: **0 FK chuỗi**, cả 113 FK đơn và 31 FK ghép đều dùng cột BIGINT UNSIGNED. Vẫn phải kiểm tra exact type/signedness/NULL theo từng cặp; không vì chưa có FK chuỗi mà bỏ quy tắc tương thích.
- Nếu một cột chuỗi tham gia FK trong thay đổi được duyệt riêng về sau, source/target phải cùng charset/collation, kiểu/chiều dài tương thích; không dùng cast hoặc collation theo session để che mismatch. Trong kế hoạch này không thêm FK chuỗi. [MariaDB — Foreign key constraints](https://mariadb.com/docs/server/architecture/server-constraints/foreign-key-constraints).
- `buoi_tap_du_kien.ma_buoi_con_hieu_luc` và nguồn `ma_buoi_logic` cùng CHAR(36)/`utf8mb4_nopad_bin`; UNIQUE dùng collation của cột. Enum trạng thái/nguồn dùng binary để CHECK tập mã không nhận biến thể case/dấu ngoài đặc tả.
- **CHAR có quy tắc padding/trả chuỗi của kiểu CHAR**; NO PAD không biến CHAR thành vùng lưu byte thô. Backend kiểm tra định dạng/độ dài mã/hash/UUID và không để khoảng trắng thừa thành định danh hợp lệ; không đổi CHAR thành VARBINARY để né probe. Với hash/token cần so sánh bảo mật, dùng cơ chế xác minh phù hợp ở Backend, không lấy collation làm bảo đảm mật mã.
- Khi preflight, xác nhận exact collation của từng cột override, email và generated; thử khác case, dấu, Unicode tương đương, khoảng trắng cuối VARCHAR và CHAR. Không chỉ xem default của database; connection/result charset cũng phải utf8mb4. P0-T14/T15 là cổng bắt buộc trước implementation.
- Đây là chốt policy vật lý; giữ nguyên toàn bộ kiểu/độ dài/nullable/UNIQUE trong từ điển. Không thêm regex, email verification hoặc bảng/cột normalization mới.

#### 6.1.4. JSON trên MariaDB 10.4

Từ điển vẫn ghi kiểu `JSON` vì đó là hợp đồng logic của payload. MariaDB 10.4 lưu kiểu này như `LONGTEXT` với `utf8mb4_bin` và tự thêm `CHECK (JSON_VALID(cot))`; nội dung text được giữ nguyên, không có binary JSON storage như MySQL. Các ID nằm trong JSON không trở thành FK và việc kiểm tra candidate/dụng cụ/ownership vẫn thuộc Backend.

CHECK JSON_VALID là ràng buộc vật lý do kiểu dữ liệu sinh ra, không thuộc **83 CHECK nghiệp vụ có tên** trong manifest A. Khi triển khai, metadata có thể đếm thêm các CHECK JSON_VALID; báo cáo phải tách hai nhóm và không tự thêm chúng vào baseline 83. Không dùng JSON operator chỉ có ở MySQL 8; adapter phải dùng hàm MariaDB tương thích hoặc đọc/ghi payload đã kiểm định ở Backend. [MariaDB JSON](https://mariadb.com/docs/server/reference/data-types/string-data-types/json), [MariaDB JSON_VALID](https://mariadb.com/docs/server/reference/sql-functions/special-functions/json-functions/json_valid).

### 6.2. Năm generated column bắt buộc

Tất cả là **VIRTUAL, nullable**, tạo sau các cột nguồn, không có default do client ghi và không làm FK. UNIQUE nằm trong P1.

| Bảng.cột | Kiểu | Giá trị khi thỏa điều kiện | Trường hợp còn lại | UNIQUE |
| --- | --- | --- | --- | --- |
| `dang_ky_goi_tap.hoi_vien_chua_ket_thuc_id` | BIGINT UNSIGNED | `hoi_vien_id` khi CHO_KICH_HOAT hoặc DANG_HOAT_DONG | NULL | `(hoi_vien_chua_ket_thuc_id)` |
| `phan_cong_huan_luyen_vien.hoi_vien_dang_phan_cong_id` | BIGINT UNSIGNED | `hoi_vien_id` khi `ngay_ket_thuc IS NULL` | NULL | `(hoi_vien_dang_phan_cong_id)` |
| `ke_hoach_tap.hoi_vien_dang_su_dung_id` | BIGINT UNSIGNED | `hoi_vien_id` khi DANG_SU_DUNG | NULL khi LUU_TRU | `(hoi_vien_dang_su_dung_id)` |
| `buoi_tap_du_kien.ma_buoi_con_hieu_luc` | CHAR(36) | `ma_buoi_logic` khi CHUA_TAP/DANG_TAP/HOAN_THANH/BO_QUA | NULL khi HUY/DA_THAY_THE | `(hoi_vien_id, ma_buoi_con_hieu_luc)` |
| `buoi_tap_du_kien.ngay_tap_con_hieu_luc` | DATE | `ngay_tap` với cùng bốn trạng thái giữ slot trên | NULL khi HUY/DA_THAY_THE | `(hoi_vien_id, ngay_tap_con_hieu_luc)` |

Không dùng NOW(), truy vấn bảng khác hoặc tính ngày còn lại trong biểu thức sinh. Generated column chỉ phản ánh dữ liệu hàng, không tự cập nhật theo đồng hồ. UNIQUE nullable cho phép nhiều hàng lịch sử trả NULL. [MariaDB — Generated columns](https://mariadb.com/docs/server/reference/sql-statements/data-definition/create/generated-columns), [MariaDB — UNIQUE indexes](https://mariadb.com/docs/server/reference/sql-statements/data-definition/create/create-table).

`hoi_vien_dang_phan_cong_id` chỉ ngăn nhiều khoảng **chưa có ngày kết thúc**, kể cả khoảng tương lai; không phát hiện mọi overlap. `hoi_vien_chua_ket_thuc_id` cần Backend đóng chuỗi đã hết trước khi mở chuỗi mới. Hai cột của lịch không cho phép xóa lịch hoặc thay ngày lịch sử để né UNIQUE.

### 6.3. CHECK MANIFEST đầy đủ và phân loại A/B/C

**Phạm vi rà soát:** 52/52 bảng, nguyên văn toàn bộ 52 mục “CHECK/điều kiện cùng hàng dự kiến”, mô tả trạng thái/cờ và ghi chú liên quan. A là **83 DB CHECK vật lý dự kiến**; B là **52 nhóm ràng buộc đã có, một nhóm/bảng**, bao phủ 98 UNIQUE, 144 FK và 5 generated; C là **26 nhóm invariant Backend/transaction**. B/C được đếm theo nhóm review, không phải số constraint vật lý hay số câu văn. Không cộng A+B+C thành số ràng buộc Database.

Kiểu dữ liệu, signedness, NOT NULL, PK và default vẫn giữ nguyên cho đủ 575 cột theo mục 4/6.1. Những câu chung “theo kiểu, NOT NULL” là phần nền này, không phải yêu cầu tự tạo CHECK trùng lặp. Trong bảng bao phủ, chúng được ghi rõ **nền kiểu/NULL** bên cạnh A/B/C; không tính thêm một nhóm B hoặc một CHECK chỉ vì có câu chung. Các cờ BOOLEAN phải có CHECK 0/1 riêng, không được nhầm với bảo vệ của kiểu.

#### 6.3.1. Cách đọc và quy tắc chuyển biểu thức

- Mỗi hàng A là một tên CHECK dự kiến ổn định, **chưa được tạo/chạy**. Biểu thức là đặc tả predicate để review, không phải SQL/DDL thực thi. Mọi phần còn lại của ví dụ được giả định hợp lệ; mốc giờ là trên cùng ngày nếu không nêu khác.
- Nguồn “TĐ BNN” dẫn tới bảng chính thức trong từ điển; ghi rõ lấy từ phần CHECK hay mô tả cột. A bao gồm cả tập mã trạng thái nêu trong mô tả, không tự biến danh mục chưa chốt thành enum mới.
- CHECK phải được ENFORCED, không NOT ENFORCED. MariaDB chấp nhận UNKNOWN do NULL, nên cặp NULL/phép kéo theo được diễn đạt tường minh. Không dùng NOW(), subquery, AUTO_INCREMENT hoặc dữ liệu trước UPDATE trong CHECK. [MariaDB — CHECK](https://mariadb.com/docs/server/reference/sql-statements/data-definition/constraint).
- “Liên quan FK” trong A chỉ **cột tham gia predicate đồng thời thuộc FK**. Hai CHECK giao trực tiếp FK là `kiem_tra_b17_02` và `kiem_tra_b46_04`; bảng khác có FK vẫn phải probe cả bảng. Nếu cần ngoại lệ cú pháp RESTRICT, theo mục 2.4/5.1, không bỏ CHECK.
- C phân loại toàn bộ invariant cần nhiều hàng, trạng thái trước, quyền hoặc thời gian/I/O. Một mảnh điều kiện cùng hàng trong C có thể biểu diễn bằng CHECK nhưng không thay được toàn bộ invariant; phần nào đã được từ điển đặt ở transaction thì không tự thêm CHECK mới ở đây.
- Không CHECK cố định TTL 90 giây/24 giờ; không quota tin nhắn; không điều kiện Chat=false ⇒ buổi PT=0; không bắt reps thực tế >0; không thêm ngưỡng y khoa, giới hạn provider hoặc enum mục tiêu/giới tính/kinh nghiệm.

#### 6.3.2. A — Manifest 83 DB CHECK dự kiến

| Constraint dự kiến | Bảng | Cột tham gia | Predicate dự kiến | Nguồn từ điển | Liên quan FK | Valid | Invalid phải từ chối | Ghi chú |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| `kiem_tra_b01_01` | `chi_nhanh` | `trang_thai` | `trang_thai IN ('HOAT_DONG', 'NGUNG_HOAT_DONG')` | [TĐ B01](TU_DIEN_DU_LIEU.md#b01) — Mô tả cột trang_thai; quy ước tập trạng thái/cờ | Không giao cột FK | trang_thai='HOAT_DONG' | trang_thai='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b02_01` | `nguoi_dung` | `trang_thai` | `trang_thai IN ('HOAT_DONG', 'BI_KHOA', 'NGUNG_HOAT_DONG')` | [TĐ B02](TU_DIEN_DU_LIEU.md#b02) — Mô tả cột trang_thai; quy ước tập trạng thái/cờ | Không giao cột FK | trang_thai='HOAT_DONG' | trang_thai='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b03_01` | `vai_tro` | `ma_vai_tro` | `ma_vai_tro IN ('MEMBER', 'PT', 'RECEPTIONIST', 'ADMIN')` | [TĐ B03](TU_DIEN_DU_LIEU.md#b03) — Mô tả cột ma_vai_tro; quy ước tập trạng thái/cờ | Không giao cột FK | ma_vai_tro='MEMBER' | ma_vai_tro='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b04_01` | `phan_quyen_nguoi_dung` | `cap_luc`, `thu_hoi_luc` | `thu_hoi_luc IS NULL OR thu_hoi_luc >= cap_luc` | [TĐ B04](TU_DIEN_DU_LIEU.md#b04) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | cap_luc=09:00, thu_hoi_luc=NULL hoặc 10:00 | cap_luc=10:00, thu_hoi_luc=09:00 | Điều kiện cùng hàng; dữ liệu khác trong ví dụ đã hợp lệ. |
| `kiem_tra_b05_01` | `ho_so_hoi_vien` | `so_ngay_tap_mong_muon`, `thoi_luong_moi_buoi_phut` | `(so_ngay_tap_mong_muon IS NULL OR so_ngay_tap_mong_muon BETWEEN 1 AND 7) AND (thoi_luong_moi_buoi_phut IS NULL OR thoi_luong_moi_buoi_phut > 0)` | [TĐ B05](TU_DIEN_DU_LIEU.md#b05) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | (NULL,NULL); (3,60) | (0,60); (8,60); (3,0) | Điều kiện cùng hàng; dữ liệu khác trong ví dụ đã hợp lệ. |
| `kiem_tra_b06_01` | `ho_so_huan_luyen_vien` | `trang_thai` | `trang_thai IN ('HOAT_DONG', 'NGUNG_NHAN_PHAN_CONG')` | [TĐ B06](TU_DIEN_DU_LIEU.md#b06) — Mô tả cột trang_thai; quy ước tập trạng thái/cờ | Không giao cột FK | trang_thai='HOAT_DONG' | trang_thai='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b07_01` | `ngay_ranh_hoi_vien` | `thu_trong_tuan` | `thu_trong_tuan BETWEEN 2 AND 8` | [TĐ B07](TU_DIEN_DU_LIEU.md#b07) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | 2; 8 | 1; 9 | Điều kiện cùng hàng; dữ liệu khác trong ví dụ đã hợp lệ. |
| `kiem_tra_b09_01` | `chi_so_co_the` | `can_nang_kg`, `chieu_cao_cm`, `vong_eo_cm` | `can_nang_kg > 0 AND chieu_cao_cm > 0 AND (vong_eo_cm IS NULL OR vong_eo_cm > 0)` | [TĐ B09](TU_DIEN_DU_LIEU.md#b09) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | (70,170,NULL); (70,170,80) | (0,170,NULL); (70,0,80); (70,170,0) | Điều kiện cùng hàng; dữ liệu khác trong ví dụ đã hợp lệ. |
| `kiem_tra_b10_01` | `the_truy_cap` | `het_han_luc`, `ngay_tao` | `het_han_luc > ngay_tao` | [TĐ B10](TU_DIEN_DU_LIEU.md#b10) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | ngay_tao=09:00, het_han_luc=10:00 | hai mốc bằng nhau hoặc hạn trước lúc tạo | Chỉ so sánh hai mốc đã lưu; không NOW() và không hard-code TTL. |
| `kiem_tra_b11_01` | `yeu_cau_dat_lai_mat_khau` | `het_han_luc`, `ngay_tao` | `het_han_luc > ngay_tao` | [TĐ B11](TU_DIEN_DU_LIEU.md#b11) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | ngay_tao=09:00, het_han_luc=10:00 | hai mốc bằng nhau hoặc hạn trước lúc tạo | Chỉ so sánh hai mốc đã lưu; không NOW() và không hard-code TTL. |
| `kiem_tra_b12_01` | `goi_tap` | `trang_thai` | `trang_thai IN ('DANG_BAN', 'NGUNG_BAN')` | [TĐ B12](TU_DIEN_DU_LIEU.md#b12) — Mô tả cột trang_thai; quy ước tập trạng thái/cờ | Không giao cột FK | trang_thai='DANG_BAN' | trang_thai='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b12_02` | `goi_tap` | `gia`, `thoi_han_ngay`, `phien_ban_cau_hinh` | `gia > 0 AND thoi_han_ngay > 0 AND phien_ban_cau_hinh >= 1` | [TĐ B12](TU_DIEN_DU_LIEU.md#b12) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | (300000,30,1) | (0,30,1); (300000,0,1); (300000,30,0) | Điều kiện cùng hàng; dữ liệu khác trong ví dụ đã hợp lệ. |
| `kiem_tra_b13_01` | `quyen_loi_goi_tap` | `cho_phep_vao_phong_tap`, `cho_phep_tro_ly_tap_luyen`, `cho_phep_tro_chuyen_huan_luyen_vien` | `cho_phep_vao_phong_tap IN (0,1) AND cho_phep_tro_ly_tap_luyen IN (0,1) AND cho_phep_tro_chuyen_huan_luyen_vien IN (0,1)` | [TĐ B13](TU_DIEN_DU_LIEU.md#b13) — CHECK; mô tả ba cờ | Không giao cột FK | ba cờ thuộc 0/1; (1,1,1) | bất kỳ cờ = 2 hoặc -1 | BOOLEAN không tự giới hạn 0/1; test cả ba cờ. |
| `kiem_tra_b13_02` | `quyen_loi_goi_tap` | `cho_phep_tro_ly_tap_luyen`, `gioi_han_luot_tro_ly` | `(cho_phep_tro_ly_tap_luyen = 0 AND gioi_han_luot_tro_ly IS NOT NULL AND gioi_han_luot_tro_ly = 0) OR (cho_phep_tro_ly_tap_luyen = 1 AND (gioi_han_luot_tro_ly IS NULL OR gioi_han_luot_tro_ly > 0))` | [TĐ B13](TU_DIEN_DU_LIEU.md#b13) — CHECK; B18 kế thừa điều kiện quyền B13 | Không giao cột FK | (0,0); (1,NULL); (1,10) | (0,NULL); (0,10); (1,0) | IS NOT NULL ở nhánh không AI tránh CHECK nhận UNKNOWN. |
| `kiem_tra_b13_03` | `quyen_loi_goi_tap` | `cho_phep_vao_phong_tap`, `cho_phep_tro_ly_tap_luyen`, `cho_phep_tro_chuyen_huan_luyen_vien`, `so_buoi_huan_luyen_vien` | `(cho_phep_vao_phong_tap = 1 OR cho_phep_tro_ly_tap_luyen = 1 OR cho_phep_tro_chuyen_huan_luyen_vien = 1 OR so_buoi_huan_luyen_vien > 0) AND so_buoi_huan_luyen_vien >= 0` | [TĐ B13](TU_DIEN_DU_LIEU.md#b13) — CHECK; B18 kế thừa điều kiện quyền B13 | Không giao cột FK | cờ (0,0,1), buổi=0; cờ (0,0,0), buổi=4 | cờ (0,0,0), buổi=0 | Không thêm phụ thuộc Chat↔buổi; giá trị âm còn bị kiểu UNSIGNED từ chối. |
| `kiem_tra_b14_01` | `don_mua_goi` | `trang_thai` | `trang_thai IN ('CHO_THANH_TOAN', 'DA_THANH_TOAN', 'HET_HAN', 'HUY', 'CAN_DOI_SOAT')` | [TĐ B14](TU_DIEN_DU_LIEU.md#b14) — Mô tả cột trang_thai; quy ước tập trạng thái/cờ | Không giao cột FK | trang_thai='CHO_THANH_TOAN' | trang_thai='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b14_02` | `don_mua_goi` | `so_tien_phai_thu`, `don_vi_tien` | `so_tien_phai_thu > 0 AND don_vi_tien = 'VND'` | [TĐ B14](TU_DIEN_DU_LIEU.md#b14) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | (300000,'VND') | (0,'VND'); (300000,'USD') | Điều kiện cùng hàng; dữ liệu khác trong ví dụ đã hợp lệ. |
| `kiem_tra_b14_03` | `don_mua_goi` | `het_han_thanh_toan_luc`, `chot_gia_luc` | `het_han_thanh_toan_luc > chot_gia_luc` | [TĐ B14](TU_DIEN_DU_LIEU.md#b14) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | chốt 09:00, hạn 10:00 | hạn 09:00 hoặc 08:59 | Điều kiện cùng hàng; dữ liệu khác trong ví dụ đã hợp lệ. |
| `kiem_tra_b15_01` | `lan_thanh_toan` | `trang_thai` | `trang_thai IN ('DANG_TAO', 'CHO_THANH_TOAN', 'THANH_CONG', 'THAT_BAI', 'HUY', 'HET_HAN', 'CAN_DOI_SOAT')` | [TĐ B15](TU_DIEN_DU_LIEU.md#b15) — Mô tả cột trang_thai; quy ước tập trạng thái/cờ | Không giao cột FK | trang_thai='DANG_TAO' | trang_thai='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b15_02` | `lan_thanh_toan` | `so_lan`, `so_tien_yeu_cau`, `so_tien_da_nhan`, `ma_don_cong_thanh_toan` | `so_lan > 0 AND so_tien_yeu_cau > 0 AND (so_tien_da_nhan IS NULL OR so_tien_da_nhan >= 0) AND ma_don_cong_thanh_toan BETWEEN 1 AND 9007199254740991` | [TĐ B15](TU_DIEN_DU_LIEU.md#b15) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | (1,300000,NULL,1); (1,300000,0,9007199254740991) | so_lan=0; yêu cầu=0; đã nhận=-1; mã=0 hoặc 9007199254740992 | Điều kiện cùng hàng; dữ liệu khác trong ví dụ đã hợp lệ. |
| `kiem_tra_b15_03` | `lan_thanh_toan` | `don_vi_tien` | `don_vi_tien = 'VND'` | [TĐ B15](TU_DIEN_DU_LIEU.md#b15) — Mô tả cột don_vi_tien | Không giao cột FK | 'VND' | 'USD' | Không áp điều kiện này cho currency nhận được ở inbox B16: phải giữ dữ liệu bất thường. |
| `kiem_tra_b16_01` | `su_kien_thanh_toan` | `trang_thai_xu_ly` | `trang_thai_xu_ly IN ('CHO_XU_LY', 'DA_XU_LY', 'BI_TU_CHOI', 'CAN_DOI_SOAT', 'CHO_THU_LAI')` | [TĐ B16](TU_DIEN_DU_LIEU.md#b16) — Mô tả cột trang_thai_xu_ly; quy ước tập trạng thái/cờ | Không giao cột FK | trang_thai_xu_ly='CHO_XU_LY' | trang_thai_xu_ly='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b16_02` | `su_kien_thanh_toan` | `so_lan_nhan`, `chu_ky_hop_le` | `so_lan_nhan >= 1 AND chu_ky_hop_le IN (0,1)` | [TĐ B16](TU_DIEN_DU_LIEU.md#b16) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | (1,0); (2,1) | (0,0); (1,2) | Điều kiện cùng hàng; dữ liệu khác trong ví dụ đã hợp lệ. |
| `kiem_tra_b16_03` | `su_kien_thanh_toan` | `chu_ky_hop_le`, `khoa_chong_lap` | `chu_ky_hop_le = 1 OR khoa_chong_lap IS NULL` | [TĐ B16](TU_DIEN_DU_LIEU.md#b16) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | (0,NULL); (1,NULL); (1,hash_hop_le) | (0,hash_hop_le) | Không CHECK việc chữ ký có thật sự hợp lệ; xác minh ở Backend. Hash ví dụ là giá trị 64 ký tự hợp lệ. |
| `kiem_tra_b17_01` | `dang_ky_goi_tap` | `trang_thai` | `trang_thai IN ('CHO_KICH_HOAT', 'DANG_HOAT_DONG', 'HET_HAN', 'HUY')` | [TĐ B17](TU_DIEN_DU_LIEU.md#b17) — Mô tả cột trang_thai; quy ước tập trạng thái/cờ | Không giao cột FK | trang_thai='CHO_KICH_HOAT' | trang_thai='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b17_02` | `dang_ky_goi_tap` | `trang_thai`, `ngay_bat_dau`, `lan_su_dung_dau_tien_id` | `((ngay_bat_dau IS NULL AND lan_su_dung_dau_tien_id IS NULL) OR (ngay_bat_dau IS NOT NULL AND lan_su_dung_dau_tien_id IS NOT NULL)) AND (trang_thai <> 'CHO_KICH_HOAT' OR ngay_bat_dau IS NULL) AND (trang_thai NOT IN ('DANG_HOAT_DONG','HET_HAN') OR ngay_bat_dau IS NOT NULL)` | [TĐ B17](TU_DIEN_DU_LIEU.md#b17) — CHECK; mô tả trang_thai/ngay_bat_dau/lan_su_dung_dau_tien_id | Có: `lan_su_dung_dau_tien_id`; mục 2.4 | CHO_KICH_HOAT + NULL/NULL; DANG_HOAT_DONG + mốc/nguồn đúng; HUY với cặp cùng NULL hoặc cùng có | CHO_KICH_HOAT có nguồn; DANG_HOAT_DONG thiếu mốc; HUY với cặp lệch NULL | Cặp mốc/nguồn nhất quán cả khi HUY; không ép HUY phải đã hoặc chưa kích hoạt. Nguồn hợp lệ và bảo toàn dấu vết qua trạng thái thuộc C10. Probe với FK đơn/ghép. |
| `kiem_tra_b18_01` | `ky_han_hoi_vien` | `trang_thai` | `trang_thai IN ('CHO_THANH_TOAN', 'CHO_KICH_HOAT', 'CHO_DEN_LUOT', 'DANG_HOAT_DONG', 'HET_HAN', 'HUY')` | [TĐ B18](TU_DIEN_DU_LIEU.md#b18) — Mô tả cột trang_thai; quy ước tập trạng thái/cờ | Không giao cột FK | trang_thai='CHO_THANH_TOAN' | trang_thai='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b18_02` | `ky_han_hoi_vien` | `cho_phep_vao_phong_tap`, `cho_phep_tro_ly_tap_luyen`, `cho_phep_tro_chuyen_huan_luyen_vien` | `cho_phep_vao_phong_tap IN (0,1) AND cho_phep_tro_ly_tap_luyen IN (0,1) AND cho_phep_tro_chuyen_huan_luyen_vien IN (0,1)` | [TĐ B18](TU_DIEN_DU_LIEU.md#b18) — CHECK; mô tả ba cờ | Không giao cột FK | ba cờ thuộc 0/1; (1,1,1) | bất kỳ cờ = 2 hoặc -1 | BOOLEAN không tự giới hạn 0/1; test cả ba cờ. |
| `kiem_tra_b18_03` | `ky_han_hoi_vien` | `cho_phep_tro_ly_tap_luyen`, `gioi_han_luot_tro_ly` | `(cho_phep_tro_ly_tap_luyen = 0 AND gioi_han_luot_tro_ly IS NOT NULL AND gioi_han_luot_tro_ly = 0) OR (cho_phep_tro_ly_tap_luyen = 1 AND (gioi_han_luot_tro_ly IS NULL OR gioi_han_luot_tro_ly > 0))` | [TĐ B18](TU_DIEN_DU_LIEU.md#b18) — CHECK; B18 kế thừa điều kiện quyền B13 | Không giao cột FK | (0,0); (1,NULL); (1,10) | (0,NULL); (0,10); (1,0) | IS NOT NULL ở nhánh không AI tránh CHECK nhận UNKNOWN. |
| `kiem_tra_b18_04` | `ky_han_hoi_vien` | `cho_phep_vao_phong_tap`, `cho_phep_tro_ly_tap_luyen`, `cho_phep_tro_chuyen_huan_luyen_vien`, `so_buoi_huan_luyen_vien` | `(cho_phep_vao_phong_tap = 1 OR cho_phep_tro_ly_tap_luyen = 1 OR cho_phep_tro_chuyen_huan_luyen_vien = 1 OR so_buoi_huan_luyen_vien > 0) AND so_buoi_huan_luyen_vien >= 0` | [TĐ B18](TU_DIEN_DU_LIEU.md#b18) — CHECK; B18 kế thừa điều kiện quyền B13 | Không giao cột FK | cờ (0,0,1), buổi=0; cờ (0,0,0), buổi=4 | cờ (0,0,0), buổi=0 | Không thêm phụ thuộc Chat↔buổi; giá trị âm còn bị kiểu UNSIGNED từ chối. |
| `kiem_tra_b18_05` | `ky_han_hoi_vien` | `thoi_han_ngay`, `gia_da_mua`, `so_thu_tu` | `thoi_han_ngay > 0 AND gia_da_mua > 0 AND (so_thu_tu IS NULL OR so_thu_tu > 0)` | [TĐ B18](TU_DIEN_DU_LIEU.md#b18) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | (30,300000,NULL); (30,300000,1) | thời hạn=0; giá=0; thứ tự=0 | Điều kiện cùng hàng; dữ liệu khác trong ví dụ đã hợp lệ. |
| `kiem_tra_b18_06` | `ky_han_hoi_vien` | `ngay_bat_dau`, `ngay_ket_thuc`, `thoi_han_ngay` | `(ngay_bat_dau IS NULL AND ngay_ket_thuc IS NULL) OR (ngay_bat_dau IS NOT NULL AND ngay_ket_thuc IS NOT NULL AND DATE_ADD(ngay_bat_dau, INTERVAL thoi_han_ngay DAY) IS NOT NULL AND ngay_ket_thuc = DATE_ADD(ngay_bat_dau, INTERVAL thoi_han_ngay DAY))` | [TĐ B18](TU_DIEN_DU_LIEU.md#b18) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | cả hai NULL; 2026-09-01 09:00 → 2026-10-01 09:00, 30 ngày | một mốc NULL; cùng ví dụ nhưng kết thúc 2026-09-30 09:00 | IS NOT NULL của kết quả cộng ngày tránh UNKNOWN khi vượt miền DATETIME; probe microsecond/biên ngày. |
| `kiem_tra_b18_07` | `ky_han_hoi_vien` | `so_buoi_huan_luyen_vien_da_dung`, `so_buoi_huan_luyen_vien` | `so_buoi_huan_luyen_vien_da_dung >= 0 AND so_buoi_huan_luyen_vien_da_dung <= so_buoi_huan_luyen_vien` | [TĐ B18](TU_DIEN_DU_LIEU.md#b18) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | đã dùng/tổng = 0/0; 4/4 | đã dùng/tổng = 5/4 | Điều kiện cùng hàng; dữ liệu khác trong ví dụ đã hợp lệ. |
| `kiem_tra_b18_08` | `ky_han_hoi_vien` | `cho_phep_tro_ly_tap_luyen`, `gioi_han_luot_tro_ly`, `so_luot_tro_ly_da_dung`, `so_luot_tro_ly_giu_cho` | `so_luot_tro_ly_da_dung >= 0 AND so_luot_tro_ly_giu_cho >= 0 AND (gioi_han_luot_tro_ly IS NULL OR so_luot_tro_ly_da_dung + so_luot_tro_ly_giu_cho <= gioi_han_luot_tro_ly) AND (cho_phep_tro_ly_tap_luyen = 1 OR (so_luot_tro_ly_da_dung = 0 AND so_luot_tro_ly_giu_cho = 0))` | [TĐ B18](TU_DIEN_DU_LIEU.md#b18) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | AI=1, giới hạn=10, đã dùng/giữ=8/2; AI=0, giới hạn=0, 0/0 | AI=1, giới hạn=10, 9/2; AI=0 mà counter > 0 | Không chứng minh counter khớp request/ledger; việc đó ở C. Phối hợp CHECK cấu hình AI, không kiểm tra quota provider call. |
| `kiem_tra_b19_01` | `su_dung_quyen_loi` | `loai_su_dung` | `loai_su_dung IN ('VAO_PHONG_TAP', 'YEU_CAU_TRO_LY', 'BUOI_HUAN_LUYEN', 'TRO_CHUYEN_HUAN_LUYEN')` | [TĐ B19](TU_DIEN_DU_LIEU.md#b19) — Mô tả cột loai_su_dung; quy ước tập trạng thái/cờ | Không giao cột FK | loai_su_dung='VAO_PHONG_TAP' | loai_su_dung='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b20_01` | `ma_vao_phong_tap` | `het_han_luc`, `phat_hanh_luc` | `het_han_luc > phat_hanh_luc` | [TĐ B20](TU_DIEN_DU_LIEU.md#b20) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | 09:00:00 → 09:01:30 | hai mốc bằng nhau hoặc hạn trước phát hành | Điều kiện cùng hàng; dữ liệu khác trong ví dụ đã hợp lệ. |
| `kiem_tra_b22_01` | `phan_cong_huan_luyen_vien` | `ngay_bat_dau`, `ngay_ket_thuc` | `ngay_ket_thuc IS NULL OR ngay_ket_thuc > ngay_bat_dau` | [TĐ B22](TU_DIEN_DU_LIEU.md#b22) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | [09:00,NULL); [09:00,10:00) | [09:00,09:00); [10:00,09:00) | Overlap giữa các hàng không phải CHECK này. |
| `kiem_tra_b23_01` | `lich_su_su_dung_huan_luyen_vien` | `trang_thai`, `so_luot_su_dung`, `nguon_thao_tac` | `trang_thai = 'HOAN_THANH' AND so_luot_su_dung = 1 AND nguon_thao_tac = 'WEB_HUAN_LUYEN_VIEN'` | [TĐ B23](TU_DIEN_DU_LIEU.md#b23) — CHECK; mô tả nguồn thao tác | Không giao cột FK | (HOAN_THANH,1,WEB_HUAN_LUYEN_VIEN) | HUY; lượt=0 hoặc 2; nguồn khác | Không tự mở quyền Admin/Receptionist ngoài nguồn ledger MVP đã duyệt. |
| `kiem_tra_b23_02` | `lich_su_su_dung_huan_luyen_vien` | `hoan_thanh_luc`, `xac_nhan_luc` | `hoan_thanh_luc <= xac_nhan_luc` | [TĐ B23](TU_DIEN_DU_LIEU.md#b23) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | hoàn thành 09:00, xác nhận 09:00 hoặc 09:05 | hoàn thành 09:05, xác nhận 09:00 | Xác nhận trong đúng kỳ còn hạn phải ở C; không dùng điều kiện này cho phép backdate. |
| `kiem_tra_b25_01` | `dung_cu` | `trang_thai` | `trang_thai IN ('HOAT_DONG', 'NGUNG_SU_DUNG')` | [TĐ B25](TU_DIEN_DU_LIEU.md#b25) — Mô tả cột trang_thai; quy ước tập trạng thái/cờ | Không giao cột FK | trang_thai='HOAT_DONG' | trang_thai='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b27_01` | `bai_tap` | `trang_thai` | `trang_thai IN ('HOAT_DONG', 'NGUNG_SU_DUNG')` | [TĐ B27](TU_DIEN_DU_LIEU.md#b27) — Mô tả cột trang_thai; quy ước tập trạng thái/cờ | Không giao cột FK | trang_thai='HOAT_DONG' | trang_thai='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b27_02` | `bai_tap` | `phien_ban_noi_dung` | `phien_ban_noi_dung >= 1` | [TĐ B27](TU_DIEN_DU_LIEU.md#b27) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | 1 | 0 | Điều kiện cùng hàng; dữ liệu khác trong ví dụ đã hợp lệ. |
| `kiem_tra_b29_01` | `bai_tap_nhom_co` | `vai_tro_nhom_co` | `vai_tro_nhom_co IN ('CHINH', 'PHU')` | [TĐ B29](TU_DIEN_DU_LIEU.md#b29) — Mô tả cột vai_tro_nhom_co; quy ước tập trạng thái/cờ | Không giao cột FK | vai_tro_nhom_co='CHINH' | vai_tro_nhom_co='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b30_01` | `giao_an_mau` | `trang_thai` | `trang_thai IN ('HOAT_DONG', 'NGUNG_SU_DUNG')` | [TĐ B30](TU_DIEN_DU_LIEU.md#b30) — Mô tả cột trang_thai; quy ước tập trạng thái/cờ | Không giao cột FK | trang_thai='HOAT_DONG' | trang_thai='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b30_02` | `giao_an_mau` | `so_buoi_moi_tuan`, `phien_ban_noi_dung` | `so_buoi_moi_tuan BETWEEN 1 AND 7 AND phien_ban_noi_dung >= 1` | [TĐ B30](TU_DIEN_DU_LIEU.md#b30) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | (3,1); (7,2) | (0,1); (8,1); (3,0) | Điều kiện cùng hàng; dữ liệu khác trong ví dụ đã hợp lệ. |
| `kiem_tra_b31_01` | `ngay_trong_giao_an` | `so_thu_tu`, `thoi_luong_du_kien_phut` | `so_thu_tu > 0 AND thoi_luong_du_kien_phut > 0` | [TĐ B31](TU_DIEN_DU_LIEU.md#b31) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | (1,60) | (0,60); (1,0) | Điều kiện cùng hàng; dữ liệu khác trong ví dụ đã hợp lệ. |
| `kiem_tra_b32_01` | `bai_tap_trong_giao_an` | `so_thu_tu`, `so_hiep_muc_tieu`, `so_lan_lap_toi_thieu`, `so_lan_lap_toi_da` | `so_thu_tu > 0 AND so_hiep_muc_tieu > 0 AND so_lan_lap_toi_thieu > 0 AND so_lan_lap_toi_da > 0 AND so_lan_lap_toi_thieu <= so_lan_lap_toi_da` | [TĐ B32](TU_DIEN_DU_LIEU.md#b32) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | thứ tự/hiệp/min/max = 1/3/8/12 | thứ tự=0; hiệp=0; min=0; max=0; min/max=12/8 | Không thêm UNIQUE bài/ngày hoặc yêu cầu thời gian nghỉ > 0. |
| `kiem_tra_b33_01` | `ke_hoach_tap` | `trang_thai` | `trang_thai IN ('DANG_SU_DUNG', 'LUU_TRU')` | [TĐ B33](TU_DIEN_DU_LIEU.md#b33) — Mô tả cột trang_thai; quy ước tập trạng thái/cờ | Không giao cột FK | trang_thai='DANG_SU_DUNG' | trang_thai='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b34_01` | `phien_ban_ke_hoach_tap` | `nguon_tao` | `nguon_tao IN ('HOI_VIEN', 'HUAN_LUYEN_VIEN', 'TRO_LY')` | [TĐ B34](TU_DIEN_DU_LIEU.md#b34) — Mô tả cột nguon_tao; quy ước tập trạng thái/cờ | Không giao cột FK | nguon_tao='HOI_VIEN' | nguon_tao='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b34_02` | `phien_ban_ke_hoach_tap` | `so_phien_ban` | `so_phien_ban >= 1` | [TĐ B34](TU_DIEN_DU_LIEU.md#b34) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | 1; 2 | 0 | Thứ tự tăng và phiên bản trước cùng Plan còn cần FK/transaction. |
| `kiem_tra_b35_01` | `ngay_trong_ke_hoach` | `thu_trong_tuan`, `so_thu_tu`, `thoi_luong_du_kien_phut` | `thu_trong_tuan BETWEEN 2 AND 8 AND so_thu_tu > 0 AND thoi_luong_du_kien_phut > 0` | [TĐ B35](TU_DIEN_DU_LIEU.md#b35) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | (2,1,60); (8,2,45) | thứ=1 hoặc 9; thứ tự=0; thời lượng=0 | Điều kiện cùng hàng; dữ liệu khác trong ví dụ đã hợp lệ. |
| `kiem_tra_b36_01` | `bai_tap_trong_ke_hoach` | `so_thu_tu`, `so_hiep_muc_tieu`, `so_lan_lap_toi_thieu`, `so_lan_lap_toi_da` | `so_thu_tu > 0 AND so_hiep_muc_tieu > 0 AND so_lan_lap_toi_thieu > 0 AND so_lan_lap_toi_da > 0 AND so_lan_lap_toi_thieu <= so_lan_lap_toi_da` | [TĐ B36](TU_DIEN_DU_LIEU.md#b36) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | thứ tự/hiệp/min/max = 1/3/8/12 | thứ tự=0; hiệp=0; min=0; max=0; min/max=12/8 | Không thêm UNIQUE bài/ngày hoặc yêu cầu thời gian nghỉ > 0. |
| `kiem_tra_b36_02` | `bai_tap_trong_ke_hoach` | `khoi_luong_muc_tieu_kg` | `khoi_luong_muc_tieu_kg IS NULL OR khoi_luong_muc_tieu_kg >= 0` | [TĐ B36](TU_DIEN_DU_LIEU.md#b36) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | NULL; 0; 60.50 | -0.01 | Điều kiện cùng hàng; dữ liệu khác trong ví dụ đã hợp lệ. |
| `kiem_tra_b37_01` | `buoi_tap_du_kien` | `trang_thai` | `trang_thai IN ('CHUA_TAP', 'DANG_TAP', 'HOAN_THANH', 'BO_QUA', 'HUY', 'DA_THAY_THE')` | [TĐ B37](TU_DIEN_DU_LIEU.md#b37) — Mô tả cột trang_thai; quy ước tập trạng thái/cờ | Không giao cột FK | trang_thai='CHUA_TAP' | trang_thai='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b37_02` | `buoi_tap_du_kien` | `gio_bat_dau_du_kien`, `gio_ket_thuc_du_kien` | `(gio_bat_dau_du_kien IS NULL AND gio_ket_thuc_du_kien IS NULL) OR (gio_bat_dau_du_kien IS NOT NULL AND gio_ket_thuc_du_kien IS NOT NULL AND gio_bat_dau_du_kien < gio_ket_thuc_du_kien)` | [TĐ B37](TU_DIEN_DU_LIEU.md#b37) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | (NULL,NULL); (09:00,10:00) | một giờ NULL; (10:00,10:00); (23:00,01:00) | Không hỗ trợ qua nửa đêm. TIME là kiểu có cả giá trị duration: Backend vẫn kiểm tra giờ trong ngày; không tự thêm miền giờ mới vào schema. |
| `kiem_tra_b38_01` | `phien_tap` | `trang_thai` | `trang_thai IN ('DANG_TAP', 'HOAN_THANH', 'HUY')` | [TĐ B38](TU_DIEN_DU_LIEU.md#b38) — Mô tả cột trang_thai; quy ước tập trạng thái/cờ | Không giao cột FK | trang_thai='DANG_TAP' | trang_thai='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b38_02` | `phien_tap` | `bat_dau_luc`, `ket_thuc_luc` | `ket_thuc_luc IS NULL OR ket_thuc_luc >= bat_dau_luc` | [TĐ B38](TU_DIEN_DU_LIEU.md#b38) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | 09:00/NULL; 09:00/10:00 | 10:00/09:00 | Điều kiện cùng hàng; dữ liệu khác trong ví dụ đã hợp lệ. |
| `kiem_tra_b38_03` | `phien_tap` | `trang_thai`, `ket_thuc_luc`, `ma_lan_hoan_thanh` | `trang_thai <> 'HOAN_THANH' OR (ket_thuc_luc IS NOT NULL AND ma_lan_hoan_thanh IS NOT NULL)` | [TĐ B38](TU_DIEN_DU_LIEU.md#b38) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | HOAN_THANH + mốc kết thúc + mã 36 ký tự; DANG_TAP + NULL/NULL | HOAN_THANH thiếu mốc hoặc mã | Không chứng minh bất biến kết quả sau Complete; khóa phiên ở C. |
| `kiem_tra_b39_01` | `bai_tap_trong_phien` | `so_thu_tu`, `so_hiep_du_kien`, `so_lan_lap_du_kien_toi_thieu`, `so_lan_lap_du_kien_toi_da` | `so_thu_tu > 0 AND so_hiep_du_kien > 0 AND so_lan_lap_du_kien_toi_thieu > 0 AND so_lan_lap_du_kien_toi_da > 0 AND so_lan_lap_du_kien_toi_thieu <= so_lan_lap_du_kien_toi_da` | [TĐ B39](TU_DIEN_DU_LIEU.md#b39) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | thứ tự/hiệp/min/max = 1/3/8/12 | thứ tự=0; hiệp=0; min=0; max=0; min/max=12/8 | Không thêm UNIQUE bài/ngày hoặc yêu cầu thời gian nghỉ > 0. |
| `kiem_tra_b39_02` | `bai_tap_trong_phien` | `khoi_luong_du_kien_kg` | `khoi_luong_du_kien_kg IS NULL OR khoi_luong_du_kien_kg >= 0` | [TĐ B39](TU_DIEN_DU_LIEU.md#b39) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | NULL; 0; 60.50 | -0.01 | Điều kiện cùng hàng; dữ liệu khác trong ví dụ đã hợp lệ. |
| `kiem_tra_b40_01` | `hiep_tap` | `khoi_luong_kg` | `khoi_luong_kg IS NULL OR khoi_luong_kg >= 0` | [TĐ B40](TU_DIEN_DU_LIEU.md#b40) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | NULL; 0; 60.50 | -0.01 | Điều kiện cùng hàng; dữ liệu khác trong ví dụ đã hợp lệ. |
| `kiem_tra_b40_02` | `hiep_tap` | `so_thu_tu`, `phien_ban_du_lieu` | `so_thu_tu > 0 AND phien_ban_du_lieu >= 1` | [TĐ B40](TU_DIEN_DU_LIEU.md#b40) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | (1,1) | (0,1); (1,0) | so_lan_lap=0 được giữ theo thiết kế, không thêm CHECK reps thực tế > 0. |
| `kiem_tra_b41_01` | `hoi_thoai` | `hoi_vien_doc_den_so`, `huan_luyen_vien_doc_den_so`, `so_thu_tu_cuoi` | `hoi_vien_doc_den_so >= 0 AND huan_luyen_vien_doc_den_so >= 0 AND hoi_vien_doc_den_so <= so_thu_tu_cuoi AND huan_luyen_vien_doc_den_so <= so_thu_tu_cuoi` | [TĐ B41](TU_DIEN_DU_LIEU.md#b41) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | đã đọc Member/PT/cuối = 0/0/0; 8/10/10 | 11/10/10; 10/11/10; giá trị âm | Điều kiện cùng hàng; dữ liệu khác trong ví dụ đã hợp lệ. |
| `kiem_tra_b42_01` | `tin_nhan` | `so_thu_tu` | `so_thu_tu > 0` | [TĐ B42](TU_DIEN_DU_LIEU.md#b42) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | 1 | 0 | Điều kiện cùng hàng; dữ liệu khác trong ví dụ đã hợp lệ. |
| `kiem_tra_b42_02` | `tin_nhan` | `noi_dung` | `CHAR_LENGTH(noi_dung) > 0` | [TĐ B42](TU_DIEN_DU_LIEU.md#b42) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | 'Chào PT' | '' | Không tự thêm giới hạn độ dài hoặc quy tắc trim vào CHECK; kiểm tra nội dung tại Backend. |
| `kiem_tra_b43_01` | `su_kien_phat_tin_nhan` | `trang_thai` | `trang_thai IN ('CHO_PHAT', 'DA_PHAT', 'CHO_THU_LAI')` | [TĐ B43](TU_DIEN_DU_LIEU.md#b43) — Mô tả cột trang_thai; quy ước tập trạng thái/cờ | Không giao cột FK | trang_thai='CHO_PHAT' | trang_thai='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b44_01` | `hoi_thoai_tro_ly` | `trang_thai` | `trang_thai IN ('DANG_MO', 'LUU_TRU')` | [TĐ B44](TU_DIEN_DU_LIEU.md#b44) — Mô tả cột trang_thai; quy ước tập trạng thái/cờ | Không giao cột FK | trang_thai='DANG_MO' | trang_thai='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b45_01` | `tin_nhan_tro_ly` | `nguon_tin` | `nguon_tin IN ('HOI_VIEN', 'TRO_LY')` | [TĐ B45](TU_DIEN_DU_LIEU.md#b45) — Mô tả cột nguon_tin; quy ước tập trạng thái/cờ | Không giao cột FK | nguon_tin='HOI_VIEN' | nguon_tin='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b45_02` | `tin_nhan_tro_ly` | `so_thu_tu` | `so_thu_tu > 0` | [TĐ B45](TU_DIEN_DU_LIEU.md#b45) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | 1 | 0 | Điều kiện cùng hàng; dữ liệu khác trong ví dụ đã hợp lệ. |
| `kiem_tra_b46_01` | `yeu_cau_tro_ly` | `loai_yeu_cau` | `loai_yeu_cau IN ('TAO_KE_HOACH', 'DIEU_CHINH', 'THAY_BAI', 'GIAI_THICH', 'CHUA_XAC_DINH')` | [TĐ B46](TU_DIEN_DU_LIEU.md#b46) — Mô tả cột loai_yeu_cau; quy ước tập trạng thái/cờ | Không giao cột FK | loai_yeu_cau='TAO_KE_HOACH' | loai_yeu_cau='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b46_02` | `yeu_cau_tro_ly` | `trang_thai` | `trang_thai IN ('TIEP_NHAN', 'CAN_BO_SUNG', 'DANG_XU_LY', 'THANH_CONG', 'THAT_BAI', 'BI_TU_CHOI')` | [TĐ B46](TU_DIEN_DU_LIEU.md#b46) — Mô tả cột trang_thai; quy ước tập trạng thái/cờ | Không giao cột FK | trang_thai='TIEP_NHAN' | trang_thai='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b46_03` | `yeu_cau_tro_ly` | `trang_thai_han_muc` | `trang_thai_han_muc IN ('KHONG_AP_DUNG', 'GIU_CHO', 'DA_TINH', 'DA_TRA')` | [TĐ B46](TU_DIEN_DU_LIEU.md#b46) — Mô tả cột trang_thai_han_muc; quy ước tập trạng thái/cờ | Không giao cột FK | trang_thai_han_muc='KHONG_AP_DUNG' | trang_thai_han_muc='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b46_04` | `yeu_cau_tro_ly` | `trang_thai_han_muc`, `ky_han_hoi_vien_id`, `su_dung_quyen_loi_id` | `(trang_thai_han_muc = 'KHONG_AP_DUNG' AND ky_han_hoi_vien_id IS NULL AND su_dung_quyen_loi_id IS NULL) OR (trang_thai_han_muc IN ('GIU_CHO','DA_TINH','DA_TRA') AND ky_han_hoi_vien_id IS NOT NULL AND su_dung_quyen_loi_id IS NOT NULL)` | [TĐ B46](TU_DIEN_DU_LIEU.md#b46) — CHECK/điều kiện cùng hàng dự kiến | Có: `ky_han_hoi_vien_id`, `su_dung_quyen_loi_id`; mục 2.4 | KHONG_AP_DUNG + NULL/NULL; GIU_CHO + kỳ/usage hợp lệ | KHONG_AP_DUNG có FK; GIU_CHO/DA_TINH/DA_TRA thiếu một FK | Probe bắt buộc phối hợp FK đơn và ghép. Không CHECK việc request đủ quyền hoặc counter khớp ledger. |
| `kiem_tra_b49_01` | `lan_goi_mo_hinh` | `trang_thai` | `trang_thai IN ('DANG_GOI', 'THANH_CONG', 'LOI_CAU_TRUC', 'LOI_NGHIEP_VU', 'QUA_HAN', 'THAT_BAI')` | [TĐ B49](TU_DIEN_DU_LIEU.md#b49) — Mô tả cột trang_thai; quy ước tập trạng thái/cờ | Không giao cột FK | trang_thai='DANG_GOI' | trang_thai='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b49_02` | `lan_goi_mo_hinh` | `so_lan`, `bat_dau_luc`, `ket_thuc_luc` | `so_lan > 0 AND (ket_thuc_luc IS NULL OR ket_thuc_luc >= bat_dau_luc)` | [TĐ B49](TU_DIEN_DU_LIEU.md#b49) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | lần=1, 09:00/NULL; lần=2, 09:00/10:00 | lần=0; 10:00/09:00 | Điều kiện cùng hàng; dữ liệu khác trong ví dụ đã hợp lệ. |
| `kiem_tra_b50_01` | `de_xuat_ke_hoach_tap` | `nguon_de_xuat` | `nguon_de_xuat IN ('TRO_LY', 'HUAN_LUYEN_VIEN')` | [TĐ B50](TU_DIEN_DU_LIEU.md#b50) — Mô tả cột nguon_de_xuat; quy ước tập trạng thái/cờ | Không giao cột FK | nguon_de_xuat='TRO_LY' | nguon_de_xuat='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b50_02` | `de_xuat_ke_hoach_tap` | `loai_thay_doi` | `loai_thay_doi IN ('TAO_MOI', 'DIEU_CHINH', 'THAY_BAI')` | [TĐ B50](TU_DIEN_DU_LIEU.md#b50) — Mô tả cột loai_thay_doi; quy ước tập trạng thái/cờ | Không giao cột FK | loai_thay_doi='TAO_MOI' | loai_thay_doi='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b50_03` | `de_xuat_ke_hoach_tap` | `trang_thai` | `trang_thai IN ('CHO_XAC_NHAN', 'DA_TU_CHOI', 'HET_HAN', 'XUNG_DOT', 'DA_AP_DUNG')` | [TĐ B50](TU_DIEN_DU_LIEU.md#b50) — Mô tả cột trang_thai; quy ước tập trạng thái/cờ | Không giao cột FK | trang_thai='CHO_XAC_NHAN' | trang_thai='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b50_04` | `de_xuat_ke_hoach_tap` | `het_han_luc`, `ngay_tao` | `het_han_luc > ngay_tao` | [TĐ B50](TU_DIEN_DU_LIEU.md#b50) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | ngay_tao=09:00, het_han_luc=10:00 | hai mốc bằng nhau hoặc hạn trước lúc tạo | Chỉ so sánh hai mốc đã lưu; không NOW() và không hard-code TTL. |
| `kiem_tra_b51_01` | `nhat_ky_he_thong` | `loai_tac_nhan` | `loai_tac_nhan IN ('NGUOI_DUNG', 'HE_THONG', 'CONG_THANH_TOAN')` | [TĐ B51](TU_DIEN_DU_LIEU.md#b51) — Mô tả cột loai_tac_nhan; quy ước tập trạng thái/cờ | Không giao cột FK | loai_tac_nhan='NGUOI_DUNG' | loai_tac_nhan='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b51_02` | `nhat_ky_he_thong` | `ket_qua` | `ket_qua IN ('THANH_CONG', 'BI_TU_CHOI', 'THAT_BAI')` | [TĐ B51](TU_DIEN_DU_LIEU.md#b51) — Mô tả cột ket_qua; quy ước tập trạng thái/cờ | Không giao cột FK | ket_qua='THANH_CONG' | ket_qua='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b52_01` | `yeu_cau_chong_lap` | `trang_thai` | `trang_thai IN ('DANG_XU_LY', 'DA_HOAN_TAT', 'THAT_BAI')` | [TĐ B52](TU_DIEN_DU_LIEU.md#b52) — Mô tả cột trang_thai; quy ước tập trạng thái/cờ | Không giao cột FK | trang_thai='DANG_XU_LY' | trang_thai='KHONG_HOP_LE' | So sánh mã bằng utf8mb4_nopad_bin; không tự thêm enum cho danh mục chưa chốt. |
| `kiem_tra_b52_02` | `yeu_cau_chong_lap` | `het_han_luc`, `ngay_tao` | `het_han_luc > ngay_tao` | [TĐ B52](TU_DIEN_DU_LIEU.md#b52) — CHECK/điều kiện cùng hàng dự kiến | Không giao cột FK | ngay_tao=09:00, het_han_luc=10:00 | hai mốc bằng nhau hoặc hạn trước lúc tạo | Chỉ so sánh hai mốc đã lưu; không NOW() và không hard-code TTL. |

#### 6.3.3. B — 52 nhóm điều kiện đã được UNIQUE/FK/generated bảo vệ

Mỗi hàng gom **các ràng buộc đã duyệt của đúng một bảng**, không tạo thêm CHECK hoặc constraint ngoài baseline. Cột “UNIQUE” liệt kê đủ 98 bộ, thứ tự tương ứng `duy_nhat_bNN_01`, `_02`… tại mục 5.3. Cột “FK/cột liên quan” liệt kê cột FK đơn và tuple ghép; tên FK đơn/đích tra từ đúng cột trong từ điển, tên/đích FK ghép ở mục 5.2, đơn vị attach ở 5.4. Mọi FK giữ hành vi từ chối sửa/xóa khóa cha có tham chiếu.

UNIQUE nullable cho phép nhiều hàng có thành phần NULL; nullable composite FK không chứng minh tuple toàn vẹn khi một thành phần NULL. A và C bổ sung đúng chỗ, không dùng B để tuyên bố quyền/nghiệp vụ đã được kiểm tra. Kiểu/NOT NULL ở ví dụ B38 là ràng buộc nền đi cùng UNIQUE/FK, không phải một CHECK mới.

| Nhóm / bảng / nguồn | UNIQUE đã có (đúng thứ tự) | FK/cột liên quan | Điều kiện đã bảo vệ và giới hạn | Valid | Invalid bị ràng buộc hiện có từ chối |
| --- | --- | --- | --- | --- | --- |
| **B01** — `chi_nhanh`; [TĐ B01](TU_DIEN_DU_LIEU.md#b01) UNIQUE/FK + ghi chú | `(ma_chi_nhanh)` | Không có FK | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (ma_chi_nhanh) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (ma_chi_nhanh) không chứa NULL; hoặc FK sai đích. |
| **B02** — `nguoi_dung`; [TĐ B02](TU_DIEN_DU_LIEU.md#b02) UNIQUE/FK + ghi chú | `(thu_dien_tu)` | `chi_nhanh_id → chi_nhanh.id` | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (thu_dien_tu) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (thu_dien_tu) không chứa NULL; hoặc FK sai đích. |
| **B03** — `vai_tro`; [TĐ B03](TU_DIEN_DU_LIEU.md#b03) UNIQUE/FK + ghi chú | `(ma_vai_tro)` | Không có FK | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (ma_vai_tro) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (ma_vai_tro) không chứa NULL; hoặc FK sai đích. |
| **B04** — `phan_quyen_nguoi_dung`; [TĐ B04](TU_DIEN_DU_LIEU.md#b04) UNIQUE/FK + ghi chú | `(nguoi_dung_id, vai_tro_id)` | `nguoi_dung_id → nguoi_dung.id`<br>`vai_tro_id → vai_tro.id`<br>`nguoi_cap_id → nguoi_dung.id` | Một hàng hiện tại mỗi cặp người dùng/Role; cấp lại và audit thuộc C02. | Hai hàng có bộ (nguoi_dung_id, vai_tro_id) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (nguoi_dung_id, vai_tro_id) không chứa NULL; hoặc FK sai đích. |
| **B05** — `ho_so_hoi_vien`; [TĐ B05](TU_DIEN_DU_LIEU.md#b05) UNIQUE/FK + ghi chú | `(nguoi_dung_id)`<br>`(ma_hoi_vien)` | `nguoi_dung_id → nguoi_dung.id` | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (nguoi_dung_id) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (nguoi_dung_id) không chứa NULL; hoặc FK sai đích. |
| **B06** — `ho_so_huan_luyen_vien`; [TĐ B06](TU_DIEN_DU_LIEU.md#b06) UNIQUE/FK + ghi chú | `(nguoi_dung_id)`<br>`(ma_huan_luyen_vien)` | `nguoi_dung_id → nguoi_dung.id` | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (nguoi_dung_id) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (nguoi_dung_id) không chứa NULL; hoặc FK sai đích. |
| **B07** — `ngay_ranh_hoi_vien`; [TĐ B07](TU_DIEN_DU_LIEU.md#b07) UNIQUE/FK + ghi chú | `(hoi_vien_id, thu_trong_tuan)` | `hoi_vien_id → ho_so_hoi_vien.id` | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (hoi_vien_id, thu_trong_tuan) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (hoi_vien_id, thu_trong_tuan) không chứa NULL; hoặc FK sai đích. |
| **B08** — `dung_cu_hoi_vien`; [TĐ B08](TU_DIEN_DU_LIEU.md#b08) UNIQUE/FK + ghi chú | `(hoi_vien_id, dung_cu_id)` | `hoi_vien_id → ho_so_hoi_vien.id`<br>`dung_cu_id → dung_cu.id` | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (hoi_vien_id, dung_cu_id) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (hoi_vien_id, dung_cu_id) không chứa NULL; hoặc FK sai đích. |
| **B09** — `chi_so_co_the`; [TĐ B09](TU_DIEN_DU_LIEU.md#b09) UNIQUE/FK + ghi chú | `(hoi_vien_id, ma_lan_ghi)` | `hoi_vien_id → ho_so_hoi_vien.id` | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (hoi_vien_id, ma_lan_ghi) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (hoi_vien_id, ma_lan_ghi) không chứa NULL; hoặc FK sai đích. |
| **B10** — `the_truy_cap`; [TĐ B10](TU_DIEN_DU_LIEU.md#b10) UNIQUE/FK + ghi chú | `(ma_bam_the)` | `nguoi_dung_id → nguoi_dung.id` | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (ma_bam_the) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (ma_bam_the) không chứa NULL; hoặc FK sai đích. |
| **B11** — `yeu_cau_dat_lai_mat_khau`; [TĐ B11](TU_DIEN_DU_LIEU.md#b11) UNIQUE/FK + ghi chú | `(ma_bam_xac_nhan)` | `nguoi_dung_id → nguoi_dung.id` | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (ma_bam_xac_nhan) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (ma_bam_xac_nhan) không chứa NULL; hoặc FK sai đích. |
| **B12** — `goi_tap`; [TĐ B12](TU_DIEN_DU_LIEU.md#b12) UNIQUE/FK + ghi chú | `(chi_nhanh_id, ma_goi)` | `chi_nhanh_id → chi_nhanh.id`<br>`nguoi_tao_id → nguoi_dung.id` | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (chi_nhanh_id, ma_goi) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (chi_nhanh_id, ma_goi) không chứa NULL; hoặc FK sai đích. |
| **B13** — `quyen_loi_goi_tap`; [TĐ B13](TU_DIEN_DU_LIEU.md#b13) UNIQUE/FK + ghi chú | `(goi_tap_id)` | `goi_tap_id → goi_tap.id` | Một cấu hình quyền mỗi gói; cấu hình Chat và số buổi độc lập, xem A B13/C11. | Hai hàng có bộ (goi_tap_id) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (goi_tap_id) không chứa NULL; hoặc FK sai đích. |
| **B14** — `don_mua_goi`; [TĐ B14](TU_DIEN_DU_LIEU.md#b14) UNIQUE/FK + ghi chú | `(ma_don)`<br>`(hoi_vien_id, ma_yeu_cau)`<br>`(id, hoi_vien_id)` | `hoi_vien_id → ho_so_hoi_vien.id`<br>`goi_tap_id → goi_tap.id` | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (ma_don) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (ma_don) không chứa NULL; hoặc FK sai đích. |
| **B15** — `lan_thanh_toan`; [TĐ B15](TU_DIEN_DU_LIEU.md#b15) UNIQUE/FK + ghi chú | `(don_mua_goi_id, so_lan)`<br>`(ma_kenh_thanh_toan, ma_don_cong_thanh_toan)`<br>`(ma_kenh_thanh_toan, ma_lien_ket_thanh_toan)`<br>`(ma_kenh_thanh_toan, ma_tham_chieu_duoc_chap_nhan)`<br>`(id, don_mua_goi_id)` | `don_mua_goi_id → don_mua_goi.id` | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (don_mua_goi_id, so_lan) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (don_mua_goi_id, so_lan) không chứa NULL; hoặc FK sai đích. |
| **B16** — `su_kien_thanh_toan`; [TĐ B16](TU_DIEN_DU_LIEU.md#b16) UNIQUE/FK + ghi chú | `(khoa_chong_lap)` | `lan_thanh_toan_id → lan_thanh_toan.id` | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (khoa_chong_lap) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (khoa_chong_lap) không chứa NULL; hoặc FK sai đích. |
| **B17** — `dang_ky_goi_tap`; [TĐ B17](TU_DIEN_DU_LIEU.md#b17) UNIQUE/FK + ghi chú | `(lan_su_dung_dau_tien_id)`<br>`(hoi_vien_chua_ket_thuc_id)`<br>`(id, hoi_vien_id)` | `hoi_vien_id → ho_so_hoi_vien.id`<br>`chi_nhanh_id → chi_nhanh.id`<br>`lan_su_dung_dau_tien_id → su_dung_quyen_loi.id`<br>`(lan_su_dung_dau_tien_id, hoi_vien_id) → su_dung_quyen_loi(id, hoi_vien_id)` | VIRTUAL + UNIQUE hoi_vien_chua_ket_thuc_id: tối đa một chuỗi chưa khép; nhiều HET_HAN/HUY trả NULL. Không NOW(); đồng hồ/quyền thực ở C10. | Một chuỗi CHO_KICH_HOAT và nhiều chuỗi đã khép cùng Member. | Hai chuỗi CHO_KICH_HOAT/DANG_HOAT_DONG cùng Member. |
| **B18** — `ky_han_hoi_vien`; [TĐ B18](TU_DIEN_DU_LIEU.md#b18) UNIQUE/FK + ghi chú | `(don_mua_goi_id)`<br>`(lan_thanh_toan_id)`<br>`(dang_ky_goi_tap_id, so_thu_tu)`<br>`(id, hoi_vien_id)` | `hoi_vien_id → ho_so_hoi_vien.id`<br>`don_mua_goi_id → don_mua_goi.id`<br>`lan_thanh_toan_id → lan_thanh_toan.id`<br>`dang_ky_goi_tap_id → dang_ky_goi_tap.id`<br>`(don_mua_goi_id, hoi_vien_id) → don_mua_goi(id, hoi_vien_id)`<br>`(lan_thanh_toan_id, don_mua_goi_id) → lan_thanh_toan(id, don_mua_goi_id)`<br>`(dang_ky_goi_tap_id, hoi_vien_id) → dang_ky_goi_tap(id, hoi_vien_id)` | Một kỳ mỗi đơn/lần thanh toán, thứ tự duy nhất trong chuỗi; FK ghép khớp Member/đơn/nguồn thanh toán. NULL giai đoạn chờ không có nghĩa được cấp quyền (C07/C09/C10). | Hai hàng có bộ (don_mua_goi_id) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (don_mua_goi_id) không chứa NULL; hoặc FK sai đích. |
| **B19** — `su_dung_quyen_loi`; [TĐ B19](TU_DIEN_DU_LIEU.md#b19) UNIQUE/FK + ghi chú | `(hoi_vien_id, loai_su_dung, ma_hanh_dong)`<br>`(id, hoi_vien_id, ky_han_hoi_vien_id)`<br>`(id, hoi_vien_id)` | `hoi_vien_id → ho_so_hoi_vien.id`<br>`ky_han_hoi_vien_id → ky_han_hoi_vien.id`<br>`nguoi_thuc_hien_id → nguoi_dung.id`<br>`(ky_han_hoi_vien_id, hoi_vien_id) → ky_han_hoi_vien(id, hoi_vien_id)` | Một hành động retry theo Member/loại/mã; nguồn đúng kỳ và Member. Loại usage đúng domain và activation thuộc C10/C19/C22. | Hai hàng có bộ (hoi_vien_id, loai_su_dung, ma_hanh_dong) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (hoi_vien_id, loai_su_dung, ma_hanh_dong) không chứa NULL; hoặc FK sai đích. |
| **B20** — `ma_vao_phong_tap`; [TĐ B20](TU_DIEN_DU_LIEU.md#b20) UNIQUE/FK + ghi chú | `(ma_bam_bi_mat)`<br>`(id, hoi_vien_id, chi_nhanh_id)` | `hoi_vien_id → ho_so_hoi_vien.id`<br>`chi_nhanh_id → chi_nhanh.id` | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (ma_bam_bi_mat) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (ma_bam_bi_mat) không chứa NULL; hoặc FK sai đích. |
| **B21** — `lich_su_vao_phong_tap`; [TĐ B21](TU_DIEN_DU_LIEU.md#b21) UNIQUE/FK + ghi chú | `(ma_vao_phong_tap_id)`<br>`(su_dung_quyen_loi_id)` | `ma_vao_phong_tap_id → ma_vao_phong_tap.id`<br>`hoi_vien_id → ho_so_hoi_vien.id`<br>`chi_nhanh_id → chi_nhanh.id`<br>`ky_han_hoi_vien_id → ky_han_hoi_vien.id`<br>`su_dung_quyen_loi_id → su_dung_quyen_loi.id`<br>`nguoi_xac_nhan_id → nguoi_dung.id`<br>`(ma_vao_phong_tap_id, hoi_vien_id, chi_nhanh_id) → ma_vao_phong_tap(id, hoi_vien_id, chi_nhanh_id)`<br>`(su_dung_quyen_loi_id, hoi_vien_id, ky_han_hoi_vien_id) → su_dung_quyen_loi(id, hoi_vien_id, ky_han_hoi_vien_id)` | Một check-in mỗi QR và mỗi usage; FK ghép khớp Member/chi nhánh/kỳ. Không UNIQUE Member/ngày. | Hai hàng có bộ (ma_vao_phong_tap_id) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (ma_vao_phong_tap_id) không chứa NULL; hoặc FK sai đích. |
| **B22** — `phan_cong_huan_luyen_vien`; [TĐ B22](TU_DIEN_DU_LIEU.md#b22) UNIQUE/FK + ghi chú | `(hoi_vien_id, huan_luyen_vien_id, ngay_bat_dau)`<br>`(id, hoi_vien_id, huan_luyen_vien_id)`<br>`(id, hoi_vien_id)`<br>`(hoi_vien_dang_phan_cong_id)` | `hoi_vien_id → ho_so_hoi_vien.id`<br>`huan_luyen_vien_id → ho_so_huan_luyen_vien.id`<br>`nguoi_phan_cong_id → nguoi_dung.id` | VIRTUAL + UNIQUE hoi_vien_dang_phan_cong_id chỉ giữ một khoảng có ngay_ket_thuc NULL, kể cả tương lai. Không chống mọi overlap (C12), không dùng NOW(). | Một khoảng mở, các khoảng hữu hạn có tuple khác. | Hai khoảng ngay_ket_thuc=NULL cùng Member. |
| **B23** — `lich_su_su_dung_huan_luyen_vien`; [TĐ B23](TU_DIEN_DU_LIEU.md#b23) UNIQUE/FK + ghi chú | `(hoi_vien_id, huan_luyen_vien_id, ma_buoi_huan_luyen)`<br>`(su_dung_quyen_loi_id)` | `hoi_vien_id → ho_so_hoi_vien.id`<br>`huan_luyen_vien_id → ho_so_huan_luyen_vien.id`<br>`phan_cong_huan_luyen_vien_id → phan_cong_huan_luyen_vien.id`<br>`ky_han_hoi_vien_id → ky_han_hoi_vien.id`<br>`su_dung_quyen_loi_id → su_dung_quyen_loi.id`<br>`(phan_cong_huan_luyen_vien_id, hoi_vien_id, huan_luyen_vien_id) → phan_cong_huan_luyen_vien(id, hoi_vien_id, huan_luyen_vien_id)`<br>`(su_dung_quyen_loi_id, hoi_vien_id, ky_han_hoi_vien_id) → su_dung_quyen_loi(id, hoi_vien_id, ky_han_hoi_vien_id)` | Một ledger mỗi mã buổi trong scope Member/PT; usage không tái dùng; FK ghép giữ phân công/kỳ/Member đúng. | Hai hàng có bộ (hoi_vien_id, huan_luyen_vien_id, ma_buoi_huan_luyen) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (hoi_vien_id, huan_luyen_vien_id, ma_buoi_huan_luyen) không chứa NULL; hoặc FK sai đích. |
| **B24** — `ghi_chu_huan_luyen`; [TĐ B24](TU_DIEN_DU_LIEU.md#b24) UNIQUE/FK + ghi chú | Không có UNIQUE ngoài PK | `hoi_vien_id → ho_so_hoi_vien.id`<br>`phan_cong_huan_luyen_vien_id → phan_cong_huan_luyen_vien.id`<br>`nguoi_tao_id → nguoi_dung.id`<br>`ke_hoach_tap_id → ke_hoach_tap.id`<br>`phien_tap_id → phien_tap.id`<br>`(phan_cong_huan_luyen_vien_id, hoi_vien_id) → phan_cong_huan_luyen_vien(id, hoi_vien_id)`<br>`(ke_hoach_tap_id, hoi_vien_id) → ke_hoach_tap(id, hoi_vien_id)`<br>`(phien_tap_id, hoi_vien_id) → phien_tap(id, hoi_vien_id)` | FK ghép bảo vệ ownership phân công/Plan/phiên khi có liên kết; quyền tư vấn và không sửa history thuộc C04. | hoi_vien_id trỏ hàng tồn tại; NULL chỉ khi từ điển cho phép. | hoi_vien_id khác NULL nhưng không có hàng đích. |
| **B25** — `dung_cu`; [TĐ B25](TU_DIEN_DU_LIEU.md#b25) UNIQUE/FK + ghi chú | `(ma_dung_cu)` | Không có FK | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (ma_dung_cu) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (ma_dung_cu) không chứa NULL; hoặc FK sai đích. |
| **B26** — `nhom_co`; [TĐ B26](TU_DIEN_DU_LIEU.md#b26) UNIQUE/FK + ghi chú | `(ma_nhom_co)` | Không có FK | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (ma_nhom_co) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (ma_nhom_co) không chứa NULL; hoặc FK sai đích. |
| **B27** — `bai_tap`; [TĐ B27](TU_DIEN_DU_LIEU.md#b27) UNIQUE/FK + ghi chú | `(ma_bai_tap)` | `nguoi_tao_id → nguoi_dung.id` | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (ma_bai_tap) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (ma_bai_tap) không chứa NULL; hoặc FK sai đích. |
| **B28** — `bai_tap_dung_cu`; [TĐ B28](TU_DIEN_DU_LIEU.md#b28) UNIQUE/FK + ghi chú | `(bai_tap_id, dung_cu_id)` | `bai_tap_id → bai_tap.id`<br>`dung_cu_id → dung_cu.id` | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (bai_tap_id, dung_cu_id) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (bai_tap_id, dung_cu_id) không chứa NULL; hoặc FK sai đích. |
| **B29** — `bai_tap_nhom_co`; [TĐ B29](TU_DIEN_DU_LIEU.md#b29) UNIQUE/FK + ghi chú | `(bai_tap_id, nhom_co_id)` | `bai_tap_id → bai_tap.id`<br>`nhom_co_id → nhom_co.id` | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (bai_tap_id, nhom_co_id) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (bai_tap_id, nhom_co_id) không chứa NULL; hoặc FK sai đích. |
| **B30** — `giao_an_mau`; [TĐ B30](TU_DIEN_DU_LIEU.md#b30) UNIQUE/FK + ghi chú | `(ma_giao_an)` | `nguoi_tao_id → nguoi_dung.id` | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (ma_giao_an) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (ma_giao_an) không chứa NULL; hoặc FK sai đích. |
| **B31** — `ngay_trong_giao_an`; [TĐ B31](TU_DIEN_DU_LIEU.md#b31) UNIQUE/FK + ghi chú | `(giao_an_mau_id, so_thu_tu)` | `giao_an_mau_id → giao_an_mau.id` | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (giao_an_mau_id, so_thu_tu) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (giao_an_mau_id, so_thu_tu) không chứa NULL; hoặc FK sai đích. |
| **B32** — `bai_tap_trong_giao_an`; [TĐ B32](TU_DIEN_DU_LIEU.md#b32) UNIQUE/FK + ghi chú | `(ngay_trong_giao_an_id, so_thu_tu)` | `ngay_trong_giao_an_id → ngay_trong_giao_an.id`<br>`bai_tap_id → bai_tap.id` | UNIQUE thứ tự trong ngày, không UNIQUE bài/ngày: cùng bài có thể ở hai vị trí. | Hai hàng có bộ (ngay_trong_giao_an_id, so_thu_tu) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (ngay_trong_giao_an_id, so_thu_tu) không chứa NULL; hoặc FK sai đích. |
| **B33** — `ke_hoach_tap`; [TĐ B33](TU_DIEN_DU_LIEU.md#b33) UNIQUE/FK + ghi chú | `(hoi_vien_id, ma_lan_tao)`<br>`(id, hoi_vien_id)`<br>`(phien_ban_hien_tai_id)`<br>`(hoi_vien_dang_su_dung_id)` | `hoi_vien_id → ho_so_hoi_vien.id`<br>`phien_ban_hien_tai_id → phien_ban_ke_hoach_tap.id`<br>`nguoi_tao_id → nguoi_dung.id`<br>`(phien_ban_hien_tai_id, id) → phien_ban_ke_hoach_tap(id, ke_hoach_tap_id)` | VIRTUAL + UNIQUE hoi_vien_dang_su_dung_id giữ tối đa một DANG_SU_DUNG/Member; LUU_TRU trả NULL. FK kép giữ con trỏ version cùng Plan; công bố atomic thuộc C15. | Một DANG_SU_DUNG và nhiều LUU_TRU cùng Member. | Hai DANG_SU_DUNG cùng Member hoặc con trỏ version của Plan khác. |
| **B34** — `phien_ban_ke_hoach_tap`; [TĐ B34](TU_DIEN_DU_LIEU.md#b34) UNIQUE/FK + ghi chú | `(ke_hoach_tap_id, so_phien_ban)`<br>`(de_xuat_ke_hoach_tap_id)`<br>`(id, ke_hoach_tap_id)` | `ke_hoach_tap_id → ke_hoach_tap.id`<br>`phien_ban_truoc_id → phien_ban_ke_hoach_tap.id`<br>`giao_an_mau_id → giao_an_mau.id`<br>`de_xuat_ke_hoach_tap_id → de_xuat_ke_hoach_tap.id`<br>`nguoi_tao_id → nguoi_dung.id`<br>`(phien_ban_truoc_id, ke_hoach_tap_id) → phien_ban_ke_hoach_tap(id, ke_hoach_tap_id)` | Số version duy nhất mỗi Plan, một version kết quả mỗi Proposal; FK kép tự tham chiếu giữ phiên bản trước cùng Plan, không chứng minh là phiên bản cũ hợp lệ (C15). | Hai hàng có bộ (ke_hoach_tap_id, so_phien_ban) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (ke_hoach_tap_id, so_phien_ban) không chứa NULL; hoặc FK sai đích. |
| **B35** — `ngay_trong_ke_hoach`; [TĐ B35](TU_DIEN_DU_LIEU.md#b35) UNIQUE/FK + ghi chú | `(phien_ban_ke_hoach_tap_id, ma_ngay_logic)`<br>`(phien_ban_ke_hoach_tap_id, so_thu_tu)`<br>`(id, phien_ban_ke_hoach_tap_id)` | `phien_ban_ke_hoach_tap_id → phien_ban_ke_hoach_tap.id` | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (phien_ban_ke_hoach_tap_id, ma_ngay_logic) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (phien_ban_ke_hoach_tap_id, ma_ngay_logic) không chứa NULL; hoặc FK sai đích. |
| **B36** — `bai_tap_trong_ke_hoach`; [TĐ B36](TU_DIEN_DU_LIEU.md#b36) UNIQUE/FK + ghi chú | `(ngay_trong_ke_hoach_id, so_thu_tu)`<br>`(ngay_trong_ke_hoach_id, ma_bai_logic)` | `ngay_trong_ke_hoach_id → ngay_trong_ke_hoach.id`<br>`bai_tap_id → bai_tap.id` | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (ngay_trong_ke_hoach_id, so_thu_tu) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (ngay_trong_ke_hoach_id, so_thu_tu) không chứa NULL; hoặc FK sai đích. |
| **B37** — `buoi_tap_du_kien`; [TĐ B37](TU_DIEN_DU_LIEU.md#b37) UNIQUE/FK + ghi chú | `(phien_ban_ke_hoach_tap_id, ma_buoi_logic)`<br>`(thay_the_buoi_tap_id)`<br>`(hoi_vien_id, ma_buoi_con_hieu_luc)`<br>`(id, hoi_vien_id)`<br>`(hoi_vien_id, ngay_tap_con_hieu_luc)` | `hoi_vien_id → ho_so_hoi_vien.id`<br>`ke_hoach_tap_id → ke_hoach_tap.id`<br>`phien_ban_ke_hoach_tap_id → phien_ban_ke_hoach_tap.id`<br>`ngay_trong_ke_hoach_id → ngay_trong_ke_hoach.id`<br>`thay_the_buoi_tap_id → buoi_tap_du_kien.id`<br>`(ke_hoach_tap_id, hoi_vien_id) → ke_hoach_tap(id, hoi_vien_id)`<br>`(phien_ban_ke_hoach_tap_id, ke_hoach_tap_id) → phien_ban_ke_hoach_tap(id, ke_hoach_tap_id)`<br>`(ngay_trong_ke_hoach_id, phien_ban_ke_hoach_tap_id) → ngay_trong_ke_hoach(id, phien_ban_ke_hoach_tap_id)` | Hai VIRTUAL + UNIQUE giữ một mã buổi và một slot ngày mỗi Member ở CHUA_TAP/DANG_TAP/HOAN_THANH/BO_QUA; HUY/DA_THAY_THE trả NULL. UNIQUE version/mã logic và thay_the_buoi_tap_id vẫn giữ; self FK không tự kiểm tra workflow thay lịch (C16). | Một slot giữ chỗ; hàng HUY/DA_THAY_THE lịch sử trả NULL. | Hai mã logic hoặc hai slot ngày hiệu lực cùng Member; hai hàng thay cùng lịch cũ. |
| **B38** — `phien_tap`; [TĐ B38](TU_DIEN_DU_LIEU.md#b38) UNIQUE/FK + ghi chú | `(buoi_tap_du_kien_id)`<br>`(hoi_vien_id, ma_lan_bat_dau)`<br>`(hoi_vien_id, ma_lan_hoan_thanh)`<br>`(id, hoi_vien_id)` | `hoi_vien_id → ho_so_hoi_vien.id`<br>`buoi_tap_du_kien_id → buoi_tap_du_kien.id`<br>`(buoi_tap_du_kien_id, hoi_vien_id) → buoi_tap_du_kien(id, hoi_vien_id)` | NOT NULL + FK + UNIQUE buoi_tap_du_kien_id bắt buộc lịch tồn tại và tối đa một phiên kể cả HUY; không Free Workout. C17 mới chặn restart/đổi trạng thái cũ. | Một lịch đúng Member có một phiên, kể cả phiên HUY. | Phiên thứ hai cho lịch đã có phiên HUY; NULL lịch hoặc lịch sai Member. |
| **B39** — `bai_tap_trong_phien`; [TĐ B39](TU_DIEN_DU_LIEU.md#b39) UNIQUE/FK + ghi chú | `(phien_tap_id, ma_bai_thuc_hien)`<br>`(phien_tap_id, so_thu_tu)` | `phien_tap_id → phien_tap.id`<br>`bai_tap_id → bai_tap.id`<br>`bai_tap_trong_ke_hoach_id → bai_tap_trong_ke_hoach.id` | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (phien_tap_id, ma_bai_thuc_hien) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (phien_tap_id, ma_bai_thuc_hien) không chứa NULL; hoặc FK sai đích. |
| **B40** — `hiep_tap`; [TĐ B40](TU_DIEN_DU_LIEU.md#b40) UNIQUE/FK + ghi chú | `(bai_tap_trong_phien_id, so_thu_tu)`<br>`(bai_tap_trong_phien_id, ma_hiep_thuc_hien)` | `bai_tap_trong_phien_id → bai_tap_trong_phien.id` | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (bai_tap_trong_phien_id, so_thu_tu) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (bai_tap_trong_phien_id, so_thu_tu) không chứa NULL; hoặc FK sai đích. |
| **B41** — `hoi_thoai`; [TĐ B41](TU_DIEN_DU_LIEU.md#b41) UNIQUE/FK + ghi chú | `(phan_cong_huan_luyen_vien_id)`<br>`(id, phan_cong_huan_luyen_vien_id)` | `hoi_vien_id → ho_so_hoi_vien.id`<br>`huan_luyen_vien_id → ho_so_huan_luyen_vien.id`<br>`phan_cong_huan_luyen_vien_id → phan_cong_huan_luyen_vien.id`<br>`(phan_cong_huan_luyen_vien_id, hoi_vien_id, huan_luyen_vien_id) → phan_cong_huan_luyen_vien(id, hoi_vien_id, huan_luyen_vien_id)` | Một hội thoại mỗi lần phân công; FK kép đúng cặp Member/PT, không tái dùng thread cũ cho phân công mới. | Hai lần phân công khác nhau có hội thoại khác nhau. | Hai hội thoại dùng cùng phan_cong_huan_luyen_vien_id. |
| **B42** — `tin_nhan`; [TĐ B42](TU_DIEN_DU_LIEU.md#b42) UNIQUE/FK + ghi chú | `(hoi_thoai_id, so_thu_tu)`<br>`(hoi_thoai_id, nguoi_gui_id, ma_tin_nhan_phia_gui)`<br>`(su_dung_quyen_loi_id)` | `hoi_thoai_id → hoi_thoai.id`<br>`nguoi_gui_id → nguoi_dung.id`<br>`phan_cong_huan_luyen_vien_id → phan_cong_huan_luyen_vien.id`<br>`su_dung_quyen_loi_id → su_dung_quyen_loi.id`<br>`hoi_vien_id → ho_so_hoi_vien.id`<br>`huan_luyen_vien_id → ho_so_huan_luyen_vien.id`<br>`(hoi_thoai_id, phan_cong_huan_luyen_vien_id) → hoi_thoai(id, phan_cong_huan_luyen_vien_id)`<br>`(phan_cong_huan_luyen_vien_id, hoi_vien_id, huan_luyen_vien_id) → phan_cong_huan_luyen_vien(id, hoi_vien_id, huan_luyen_vien_id)`<br>`(su_dung_quyen_loi_id, hoi_vien_id) → su_dung_quyen_loi(id, hoi_vien_id)` | Sequence và mã tin trong scope không trùng; một usage chỉ gắn một tin. FK kép giữ chính lần phân công của hội thoại, chưa chứng minh người gửi/quyền/activation (C18/C19). | Hai hàng có bộ (hoi_thoai_id, so_thu_tu) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (hoi_thoai_id, so_thu_tu) không chứa NULL; hoặc FK sai đích. |
| **B43** — `su_kien_phat_tin_nhan`; [TĐ B43](TU_DIEN_DU_LIEU.md#b43) UNIQUE/FK + ghi chú | `(tin_nhan_id)` | `tin_nhan_id → tin_nhan.id` | Một outbox domain mỗi tin; độ tin cậy phát/retry thuộc C20. | Hai hàng có bộ (tin_nhan_id) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (tin_nhan_id) không chứa NULL; hoặc FK sai đích. |
| **B44** — `hoi_thoai_tro_ly`; [TĐ B44](TU_DIEN_DU_LIEU.md#b44) UNIQUE/FK + ghi chú | `(id, hoi_vien_id)` | `hoi_vien_id → ho_so_hoi_vien.id` | Giữ các bộ UNIQUE đã duyệt và mọi FK của bảng; không tham chiếu hàng không tồn tại. Bộ FK ghép, nếu có, bảo vệ tuple đúng khi các thành phần không NULL. | Hai hàng có bộ (id, hoi_vien_id) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (id, hoi_vien_id) không chứa NULL; hoặc FK sai đích. |
| **B45** — `tin_nhan_tro_ly`; [TĐ B45](TU_DIEN_DU_LIEU.md#b45) UNIQUE/FK + ghi chú | `(hoi_thoai_tro_ly_id, so_thu_tu)`<br>`(hoi_thoai_tro_ly_id, ma_tin_nhan_phia_gui)`<br>`(id, hoi_thoai_tro_ly_id)` | `hoi_thoai_tro_ly_id → hoi_thoai_tro_ly.id`<br>`yeu_cau_tro_ly_id → yeu_cau_tro_ly.id`<br>`(yeu_cau_tro_ly_id, hoi_thoai_tro_ly_id) → yeu_cau_tro_ly(id, hoi_thoai_tro_ly_id)` | Sequence/mã tin không trùng trong hội thoại; FK kép giữ response đúng hội thoại của request; text không phải payload Apply. | Hai hàng có bộ (hoi_thoai_tro_ly_id, so_thu_tu) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (hoi_thoai_tro_ly_id, so_thu_tu) không chứa NULL; hoặc FK sai đích. |
| **B46** — `yeu_cau_tro_ly`; [TĐ B46](TU_DIEN_DU_LIEU.md#b46) UNIQUE/FK + ghi chú | `(hoi_vien_id, ma_yeu_cau)`<br>`(tin_nhan_dau_vao_id)`<br>`(su_dung_quyen_loi_id)`<br>`(id, hoi_thoai_tro_ly_id)`<br>`(id, hoi_vien_id)` | `hoi_vien_id → ho_so_hoi_vien.id`<br>`hoi_thoai_tro_ly_id → hoi_thoai_tro_ly.id`<br>`tin_nhan_dau_vao_id → tin_nhan_tro_ly.id`<br>`ky_han_hoi_vien_id → ky_han_hoi_vien.id`<br>`su_dung_quyen_loi_id → su_dung_quyen_loi.id`<br>`(hoi_thoai_tro_ly_id, hoi_vien_id) → hoi_thoai_tro_ly(id, hoi_vien_id)`<br>`(tin_nhan_dau_vao_id, hoi_thoai_tro_ly_id) → tin_nhan_tro_ly(id, hoi_thoai_tro_ly_id)`<br>`(ky_han_hoi_vien_id, hoi_vien_id) → ky_han_hoi_vien(id, hoi_vien_id)`<br>`(su_dung_quyen_loi_id, hoi_vien_id, ky_han_hoi_vien_id) → su_dung_quyen_loi(id, hoi_vien_id, ky_han_hoi_vien_id)` | Một đầu vào/usage mỗi request và một mã request/Member; FK kép khớp hội thoại/input/Member/kỳ/usage. Cặp NULL có CHECK A B46, quyền/counter ở C22. | Input/usage/kỳ thuộc đúng Member và hội thoại, mã request mới. | Input hội thoại khác; usage sai kỳ; dùng lại cùng input/request key. |
| **B47** — `bai_tap_ung_vien`; [TĐ B47](TU_DIEN_DU_LIEU.md#b47) UNIQUE/FK + ghi chú | `(yeu_cau_tro_ly_id, bai_tap_id)` | `yeu_cau_tro_ly_id → yeu_cau_tro_ly.id`<br>`bai_tap_id → bai_tap.id` | Một candidate bài/request; FK thật tới bài. AND dụng cụ/ID trong JSON vẫn phải kiểm định ở C21. | Hai hàng có bộ (yeu_cau_tro_ly_id, bai_tap_id) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (yeu_cau_tro_ly_id, bai_tap_id) không chứa NULL; hoặc FK sai đích. |
| **B48** — `giao_an_ung_vien`; [TĐ B48](TU_DIEN_DU_LIEU.md#b48) UNIQUE/FK + ghi chú | `(yeu_cau_tro_ly_id, giao_an_mau_id)` | `yeu_cau_tro_ly_id → yeu_cau_tro_ly.id`<br>`giao_an_mau_id → giao_an_mau.id` | Một candidate giáo án/request; FK thật tới giáo án, không chứng minh payload LLM hợp lệ (C21). | Hai hàng có bộ (yeu_cau_tro_ly_id, giao_an_mau_id) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (yeu_cau_tro_ly_id, giao_an_mau_id) không chứa NULL; hoặc FK sai đích. |
| **B49** — `lan_goi_mo_hinh`; [TĐ B49](TU_DIEN_DU_LIEU.md#b49) UNIQUE/FK + ghi chú | `(yeu_cau_tro_ly_id, so_lan)`<br>`(nha_cung_cap, ma_yeu_cau_nha_cung_cap)` | `yeu_cau_tro_ly_id → yeu_cau_tro_ly.id` | Một số lần gọi/request và một mã provider trong scope nhà cung cấp; không tạo quota mỗi provider call. | Hai hàng có bộ (yeu_cau_tro_ly_id, so_lan) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (yeu_cau_tro_ly_id, so_lan) không chứa NULL; hoặc FK sai đích. |
| **B50** — `de_xuat_ke_hoach_tap`; [TĐ B50](TU_DIEN_DU_LIEU.md#b50) UNIQUE/FK + ghi chú | `(yeu_cau_tro_ly_id)` | `hoi_vien_id → ho_so_hoi_vien.id`<br>`nguoi_tao_id → nguoi_dung.id`<br>`phan_cong_huan_luyen_vien_id → phan_cong_huan_luyen_vien.id`<br>`yeu_cau_tro_ly_id → yeu_cau_tro_ly.id`<br>`ke_hoach_tap_id → ke_hoach_tap.id`<br>`phien_ban_co_so_id → phien_ban_ke_hoach_tap.id`<br>`nguoi_quyet_dinh_id → nguoi_dung.id`<br>`(phan_cong_huan_luyen_vien_id, hoi_vien_id) → phan_cong_huan_luyen_vien(id, hoi_vien_id)`<br>`(yeu_cau_tro_ly_id, hoi_vien_id) → yeu_cau_tro_ly(id, hoi_vien_id)`<br>`(ke_hoach_tap_id, hoi_vien_id) → ke_hoach_tap(id, hoi_vien_id)`<br>`(phien_ban_co_so_id, ke_hoach_tap_id) → phien_ban_ke_hoach_tap(id, ke_hoach_tap_id)` | Một Proposal mỗi request AI khi khác NULL; FK kép giữ owner/Plan/base/phân công. B34 giữ kết quả Apply duy nhất; XOR và revalidate thuộc C24. | Hai hàng có bộ (yeu_cau_tro_ly_id) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (yeu_cau_tro_ly_id) không chứa NULL; hoặc FK sai đích. |
| **B51** — `nhat_ky_he_thong`; [TĐ B51](TU_DIEN_DU_LIEU.md#b51) UNIQUE/FK + ghi chú | Không có UNIQUE ngoài PK | `nguoi_thuc_hien_id → nguoi_dung.id` | Chỉ FK thật nguoi_thuc_hien_id; loai_doi_tuong/dinh_danh_doi_tuong không có FK đa hình. | nguoi_thuc_hien_id trỏ hàng tồn tại; NULL chỉ khi từ điển cho phép. | nguoi_thuc_hien_id khác NULL nhưng không có hàng đích. |
| **B52** — `yeu_cau_chong_lap`; [TĐ B52](TU_DIEN_DU_LIEU.md#b52) UNIQUE/FK + ghi chú | `(nguoi_dung_id, pham_vi, khoa_yeu_cau)` | `nguoi_dung_id → nguoi_dung.id` | UNIQUE (nguoi_dung_id, pham_vi, khoa_yeu_cau) giữ scope retry; payload và TTL không thay UNIQUE lâu dài (C26). | Hai hàng có bộ (nguoi_dung_id, pham_vi, khoa_yeu_cau) khác nhau; các FK có đích đúng. | Hai hàng trùng bộ (nguoi_dung_id, pham_vi, khoa_yeu_cau) không chứa NULL; hoặc FK sai đích. |

#### 6.3.4. C — 26 invariant Backend/transaction, không thể thay toàn bộ bằng CHECK

Các mã C01–C26 là mã review, **không phải tên CHECK cần tạo**. Các cột dưới đây chỉ rõ phần dữ liệu tham gia; những dòng nhắc toàn bộ snapshot/cây kết quả bao gồm các cột đã có của bảng, không khai báo cột mới. Valid/Invalid ở C là kết quả Backend phải xử lý; không được báo rằng Database CHECK tự từ chối toàn bộ các ca này.

| Mã / nguồn từ điển | Bảng/cột tham gia | Invariant phải giữ | Valid | Invalid Backend phải từ chối/xử lý | Vì sao không thể thay toàn bộ bằng CHECK |
| --- | --- | --- | --- | --- | --- |
| **C01** — [TĐ B01](TU_DIEN_DU_LIEU.md#b01), [TĐ B02](TU_DIEN_DU_LIEU.md#b02), [TĐ B03](TU_DIEN_DU_LIEU.md#b03), [TĐ B05](TU_DIEN_DU_LIEU.md#b05), [TĐ B06](TU_DIEN_DU_LIEU.md#b06), [TĐ B10](TU_DIEN_DU_LIEU.md#b10), [TĐ B11](TU_DIEN_DU_LIEU.md#b11); CHECK/mô tả/ghi chú của các bảng này | trang_thai; nguoi_dung_id; thu_dien_tu; ma_bam_the; ma_bam_xac_nhan | Xác thực, Role/resource scope và chuẩn hóa đầu vào trước lưu; hash/token không phải mật khẩu/token rõ. Email dùng policy mục 6.1.1, không yêu cầu xác minh email mới. | Đúng chủ tài nguyên, token còn hiệu lực, email đã chuẩn hóa. | ID tồn tại nhưng không có quyền; dùng hash như token rõ; thêm điều kiện phải xác minh email. | FK chỉ chứng minh tồn tại, không đọc session/token/Role hiện thời; không tự chốt taxonomy giới tính, mục tiêu, kinh nghiệm hay ngưỡng y khoa. |
| **C02** — [TĐ B04](TU_DIEN_DU_LIEU.md#b04), [TĐ B51](TU_DIEN_DU_LIEU.md#b51); CHECK/mô tả/ghi chú của các bảng này | nguoi_dung_id; vai_tro_id; cap_luc; thu_hoi_luc; nguoi_cap_id; du_lieu_truoc; du_lieu_sau | Thu hồi/cấp lại UPDATE đúng hàng Role hiện tại; giữ id/ngay_tao, ghi trước/sau và người thao tác trong audit cùng transaction; retry không nhân đôi audit thành công. | Cấp lại cặp cũ, thu_hoi_luc=NULL, audit một lần. | INSERT hàng Role mới hoặc cập nhật Role thành công nhưng thiếu audit. | CHECK B04 chỉ so sánh hai mốc, không biết hàng trước hay audit ở B51. |
| **C03** — [TĐ B05](TU_DIEN_DU_LIEU.md#b05), [TĐ B07](TU_DIEN_DU_LIEU.md#b07), [TĐ B08](TU_DIEN_DU_LIEU.md#b08), [TĐ B27](TU_DIEN_DU_LIEU.md#b27), [TĐ B30](TU_DIEN_DU_LIEU.md#b30), [TĐ B34](TU_DIEN_DU_LIEU.md#b34), [TĐ B38](TU_DIEN_DU_LIEU.md#b38), [TĐ B40](TU_DIEN_DU_LIEU.md#b40); CHECK/mô tả/ghi chú của các bảng này | phien_ban_ho_so; moc_thay_doi_ke_hoach; phien_ban_noi_dung; so_phien_ban; phien_ban_du_lieu | Phiên bản/mốc thay đổi không giảm; thay ngày rảnh/dụng cụ đồng thời tăng phien_ban_ho_so; sửa có expected revision và khóa theo quy tắc domain. | Sửa với revision đúng và tăng mốc liên quan. | Ghi đè từ revision cũ hoặc sửa dụng cụ nhưng giữ mốc hồ sơ. | Cần so sánh phiên bản trước UPDATE và các hàng liên quan; default 1 không tự suy ra một CHECK >=1 ở mọi cột phiên bản. |
| **C04** — [TĐ B02](TU_DIEN_DU_LIEU.md#b02), [TĐ B09](TU_DIEN_DU_LIEU.md#b09), [TĐ B14](TU_DIEN_DU_LIEU.md#b14), [TĐ B15](TU_DIEN_DU_LIEU.md#b15), [TĐ B16](TU_DIEN_DU_LIEU.md#b16), [TĐ B18](TU_DIEN_DU_LIEU.md#b18), [TĐ B19](TU_DIEN_DU_LIEU.md#b19), [TĐ B21](TU_DIEN_DU_LIEU.md#b21), [TĐ B23](TU_DIEN_DU_LIEU.md#b23), [TĐ B24](TU_DIEN_DU_LIEU.md#b24), [TĐ B27](TU_DIEN_DU_LIEU.md#b27), [TĐ B30](TU_DIEN_DU_LIEU.md#b30), [TĐ B34](TU_DIEN_DU_LIEU.md#b34), [TĐ B35](TU_DIEN_DU_LIEU.md#b35), [TĐ B36](TU_DIEN_DU_LIEU.md#b36), [TĐ B37](TU_DIEN_DU_LIEU.md#b37), [TĐ B38](TU_DIEN_DU_LIEU.md#b38), [TĐ B39](TU_DIEN_DU_LIEU.md#b39), [TĐ B40](TU_DIEN_DU_LIEU.md#b40), [TĐ B42](TU_DIEN_DU_LIEU.md#b42), [TĐ B45](TU_DIEN_DU_LIEU.md#b45), [TĐ B46](TU_DIEN_DU_LIEU.md#b46), [TĐ B47](TU_DIEN_DU_LIEU.md#b47), [TĐ B48](TU_DIEN_DU_LIEU.md#b48), [TĐ B49](TU_DIEN_DU_LIEU.md#b49), [TĐ B50](TU_DIEN_DU_LIEU.md#b50), [TĐ B51](TU_DIEN_DU_LIEU.md#b51); CHECK/mô tả/ghi chú của các bảng này | toàn bộ cột snapshot/history; các FK lịch sử | Giữ snapshot/ledger đã công bố và lịch sử Q12; account/catalog khóa/ngừng thay xóa dây chuyền. Phiên HOAN_THANH khóa cả cây kết quả; ghi chú PT không sửa kết quả cũ. | Catalog đổi nhưng snapshot/history giữ nguyên. | Sửa version công bố, xóa ledger hoặc di chuyển FK history sang version mới. | CHECK không đọc phiên bản hàng trước, không cấm DELETE hoặc biết trạng thái phiên cha; RESTRICT cũng không ngăn xóa hàng lá. Không thêm retention/purge/correction workflow. |
| **C05** — [TĐ B10](TU_DIEN_DU_LIEU.md#b10), [TĐ B11](TU_DIEN_DU_LIEU.md#b11), [TĐ B20](TU_DIEN_DU_LIEU.md#b20), [TĐ B50](TU_DIEN_DU_LIEU.md#b50), [TĐ B52](TU_DIEN_DU_LIEU.md#b52); CHECK/mô tả/ghi chú của các bảng này | het_han_luc; ngay_tao; phat_hanh_luc | Kiểm tra hạn theo thời điểm server; Q09 QR mặc định 90 giây, Proposal 24 giờ; tại hạn là hết hạn. Lưu hạn khi phát hành, không kéo dài do đổi cấu hình/preview/retry. | Kiểm tra ngay trước hạn theo quyền hợp lệ. | Chấp nhận tại/sau hạn hoặc reset TTL khi preview. | CHECK chỉ so sánh mốc lưu; không NOW() trong CHECK/generated. TTL của token/idempotency theo policy của chúng, không áp 90 giây/24 giờ đại trà. |
| **C06** — [TĐ B12](TU_DIEN_DU_LIEU.md#b12), [TĐ B13](TU_DIEN_DU_LIEU.md#b13), [TĐ B14](TU_DIEN_DU_LIEU.md#b14), [TĐ B18](TU_DIEN_DU_LIEU.md#b18), [TĐ B27](TU_DIEN_DU_LIEU.md#b27), [TĐ B30](TU_DIEN_DU_LIEU.md#b30), [TĐ B34](TU_DIEN_DU_LIEU.md#b34), [TĐ B35](TU_DIEN_DU_LIEU.md#b35), [TĐ B36](TU_DIEN_DU_LIEU.md#b36), [TĐ B39](TU_DIEN_DU_LIEU.md#b39); CHECK/mô tả/ghi chú của các bảng này | gia; thoi_han_ngay; phien_ban_cau_hinh; phien_ban_goi; gia_da_mua; ten_goi; các cột snapshot | Chốt snapshot giá/gói/quyền ngay transaction tạo đơn và kỳ CHO_THANH_TOAN; trong hạn không đọc lại catalog để đổi đơn cũ. Sao chép template/catalog vào version/history. | Giá catalog mới chỉ áp đơn mới; history đọc snapshot. | Webhook đổi snapshot theo giá hiện tại; sửa giáo án làm đổi Plan cũ. | Đối chiếu nhiều bảng và nội dung cũ; CHECK không chứng minh snapshot đúng nguồn tại lúc chốt. |
| **C07** — [TĐ B14](TU_DIEN_DU_LIEU.md#b14), [TĐ B15](TU_DIEN_DU_LIEU.md#b15), [TĐ B18](TU_DIEN_DU_LIEU.md#b18); CHECK/mô tả/ghi chú của các bảng này | don_mua_goi_id; lan_thanh_toan_id; dang_ky_goi_tap_id; so_thu_tu; mua_luc; trang_thai | Đơn + kỳ snapshot tạo cùng transaction. CHO_THANH_TOAN chưa có payment/chuỗi/thứ tự/mua_luc/ngày hoặc quyền; chỉ gán khi Backend xác nhận thanh toán hợp lệ, cùng transaction. | Kỳ chờ có liên kết đơn nhưng chưa có nguồn thanh toán/quyền; xác nhận hợp lệ rồi gán đồng bộ. | Có hàng payment nhưng chưa xác minh vẫn cấp quyền; cập nhật nửa giao dịch. | Riêng quan hệ NULL/trạng thái có thể viết cùng hàng; toàn bộ invariant xác minh/cấp quyền phải đọc payment/đơn và commit cùng nhau. Giữ workflow transaction theo từ điển, không tự thêm CHECK ngoài manifest A. |
| **C08** — [TĐ B14](TU_DIEN_DU_LIEU.md#b14), [TĐ B15](TU_DIEN_DU_LIEU.md#b15), [TĐ B16](TU_DIEN_DU_LIEU.md#b16), [TĐ B18](TU_DIEN_DU_LIEU.md#b18); CHECK/mô tả/ghi chú của các bảng này | chu_ky_hop_le; khoa_chong_lap; ma_tham_chieu_duoc_chap_nhan; so_tien_da_nhan; trang_thai_xu_ly | Xác minh chữ ký, kênh, orderCode/reference, số tiền, currency và hạn đơn/link; chống lặp nguồn thật. Giữ sự kiện bất thường CAN_DOI_SOAT, payload giả BI_TU_CHOI; không cấp thêm kỳ/hoàn tiền tự động. | Webhook thật đúng nguồn/tiền cấp tối đa một kỳ; khoản sai giữ dấu vết. | Tin client/returnUrl, chữ ký giả hoặc link thứ hai nhận tiền làm cấp thêm ngày. | CHECK không xác minh mật mã hay gateway; inbox phải giữ dữ liệu sai để đối soát, không đặt CHECK currency=VND/tiền dương lên dữ liệu nhận được B16. |
| **C09** — [TĐ B15](TU_DIEN_DU_LIEU.md#b15), [TĐ B16](TU_DIEN_DU_LIEU.md#b16), [TĐ B17](TU_DIEN_DU_LIEU.md#b17), [TĐ B18](TU_DIEN_DU_LIEU.md#b18); CHECK/mô tả/ghi chú của các bảng này | xac_nhan_luc; mua_luc; so_thu_tu; dang_ky_goi_tap_id | Khóa Member/chuỗi, cấp thứ tự theo lần đầu Backend xác nhận hợp lệ; mua_luc bằng xac_nhan_luc của nguồn. Không chèn ngược kỳ đã dùng, retry không đổi mốc/thứ tự; timeout link tra cứu cùng orderCode trước lần thử mới. | Hai webhook cùng mốc được tuần tự hóa bằng khóa và so_thu_tu. | Xếp theo thời điểm client/đơn; retry đổi thứ tự; gọi gateway trong transaction giữ khóa dài. | UNIQUE chỉ chặn trùng số, không chứng minh thứ tự thời gian, nguồn xác minh hoặc giao tiếp gateway. |
| **C10** — [TĐ B17](TU_DIEN_DU_LIEU.md#b17), [TĐ B18](TU_DIEN_DU_LIEU.md#b18), [TĐ B19](TU_DIEN_DU_LIEU.md#b19), [TĐ B20](TU_DIEN_DU_LIEU.md#b20), [TĐ B21](TU_DIEN_DU_LIEU.md#b21), [TĐ B23](TU_DIEN_DU_LIEU.md#b23), [TĐ B42](TU_DIEN_DU_LIEU.md#b42), [TĐ B44](TU_DIEN_DU_LIEU.md#b44), [TĐ B46](TU_DIEN_DU_LIEU.md#b46); CHECK/mô tả/ghi chú của các bảng này | trang_thai; ngay_bat_dau; ngay_ket_thuc; lan_su_dung_dau_tien_id; ky_han_hoi_vien_id; loai_su_dung | Dưới khóa Member/chuỗi/kỳ, lần dùng quyền trả phí hợp lệ đầu tiên kích hoạt một đồng hồ chung. Nguồn thuộc kỳ đầu của chính chuỗi; tail nối [đầu,cuối), không dùng sớm. Tính lại trạng thái theo thời gian trước đóng/mở chuỗi; không lùi activation đã commit. | AI hoặc QR/PT/Chat hợp lệ kích hoạt một lần, các kỳ nối tiếp giữ quyền riêng. | Thanh toán/phát QR/mở hội thoại/đọc lịch sử kích hoạt; nguồn thuộc chuỗi khác; kỳ sau được dùng trước. | Cần thời gian và quan hệ nhiều bảng; A B17 chỉ kiểm tra trạng thái/cặp NULL, không xác nhận nguồn là đầu chuỗi. Hàng HUY vẫn phải bảo toàn dấu vết đã kích hoạt, không tự định nghĩa quy trình hủy/hoàn tiền mới. |
| **C11** — [TĐ B13](TU_DIEN_DU_LIEU.md#b13), [TĐ B18](TU_DIEN_DU_LIEU.md#b18), [TĐ B19](TU_DIEN_DU_LIEU.md#b19), [TĐ B23](TU_DIEN_DU_LIEU.md#b23), [TĐ B33](TU_DIEN_DU_LIEU.md#b33), [TĐ B37](TU_DIEN_DU_LIEU.md#b37), [TĐ B38](TU_DIEN_DU_LIEU.md#b38), [TĐ B39](TU_DIEN_DU_LIEU.md#b39), [TĐ B40](TU_DIEN_DU_LIEU.md#b40), [TĐ B42](TU_DIEN_DU_LIEU.md#b42), [TĐ B50](TU_DIEN_DU_LIEU.md#b50); CHECK/mô tả/ghi chú của các bảng này | cho_phep_tro_chuyen_huan_luyen_vien; so_buoi_huan_luyen_vien; so_buoi_huan_luyen_vien_da_dung; su_dung_quyen_loi_id | Chat là quyền theo kỳ, buổi PT là quota độc lập. Workout cơ bản không cần active Membership; PT Proposal chỉ cần phân công hợp lệ theo Q13. Không thêm quyền Workout, quota message hoặc usage cho các thao tác này. | Chat=true/buổi=0 được Chat; Chat=false/buổi>0 được xác nhận buổi nếu đủ điều kiện; Workout hợp lệ không có gói vẫn chạy. | Trừ buổi do Chat/Proposal, hoặc bắt Workout/Proposal PT mua gói. | A B13/B18 bảo vệ giá trị cấu hình; cách cấp quyền từng luồng qua nhiều bảng phải ở Backend. Đây không phải lệnh cấm các tổ hợp Chat/buổi bằng CHECK. |
| **C12** — [TĐ B22](TU_DIEN_DU_LIEU.md#b22); CHECK/mô tả/ghi chú của các bảng này | hoi_vien_id; huan_luyen_vien_id; ngay_bat_dau; ngay_ket_thuc | Không overlap mọi phân công cùng Member theo [đầu,cuối), NULL=không cận cuối. Khóa hồ sơ Member trước, rồi phân công theo id; đổi PT đóng cũ/mở mới + audit cùng transaction. | Khoảng mới bắt đầu đúng lúc khoảng cũ kết thúc. | Hai khoảng hữu hạn giao nhau dù khác PT; hai request cùng bỏ qua hàng mới. | A chỉ bảo vệ thứ tự hai mốc; generated UNIQUE chỉ chặn hai khoảng mở, không chặn mọi giao khoảng nhiều hàng. |
| **C13** — [TĐ B20](TU_DIEN_DU_LIEU.md#b20), [TĐ B21](TU_DIEN_DU_LIEU.md#b21), [TĐ B17](TU_DIEN_DU_LIEU.md#b17), [TĐ B18](TU_DIEN_DU_LIEU.md#b18), [TĐ B19](TU_DIEN_DU_LIEU.md#b19); CHECK/mô tả/ghi chú của các bảng này | ma_bam_bi_mat; het_han_luc; ma_vao_phong_tap_id; hoi_vien_id; chi_nhanh_id; ky_han_hoi_vien_id | QR không chứa PII/quyền do client quyết định. Khi quét xác minh mã/hạn/chi nhánh/Member, xác định lại kỳ hiện thời và quyền Gym; commit check-in + usage + activation nếu cần. Retry mã đã check-in bị từ chối bằng conflict có kiểm soát. | Mã đúng hạn có quyền Gym; quét lại chỉ có một success/check-in/usage và lần sau bị conflict. | Mã không hợp lệ tạo check-in; gắn cứng kỳ từ lúc phát QR; đặt hạn một check-in/ngày. | FK/UNIQUE chỉ bảo vệ liên kết và một kết quả mỗi mã; quyền, hạn hiện tại và ghi atomic cần transaction. |
| **C14** — [TĐ B18](TU_DIEN_DU_LIEU.md#b18), [TĐ B19](TU_DIEN_DU_LIEU.md#b19), [TĐ B22](TU_DIEN_DU_LIEU.md#b22), [TĐ B23](TU_DIEN_DU_LIEU.md#b23); CHECK/mô tả/ghi chú của các bảng này | phan_cong_huan_luyen_vien_id; ky_han_hoi_vien_id; hoan_thanh_luc; xac_nhan_luc; so_buoi_huan_luyen_vien_da_dung | Xác nhận đúng buổi/đúng kỳ còn hiệu lực hoặc head được phép kích hoạt, đủ quota và chính phân công hợp lệ. Khóa kỳ, ghi ledger + tăng counter + usage/activation + audit cùng transaction. | Buổi hợp lệ dùng lượt cuối đúng một lần. | Backdate dựa hoan_thanh_luc sau khi kỳ đã hết; mượn/carry-over lượt kỳ khác; dùng Chat để cấp buổi. | Phải đọc kỳ/phân công/ledger; A chỉ giữ HOAN_THANH, 1 lượt và thứ tự mốc. Không thêm booking, bảng hoàn lượt hoặc quyền sửa ledger. |
| **C15** — [TĐ B33](TU_DIEN_DU_LIEU.md#b33), [TĐ B34](TU_DIEN_DU_LIEU.md#b34), [TĐ B35](TU_DIEN_DU_LIEU.md#b35), [TĐ B36](TU_DIEN_DU_LIEU.md#b36), [TĐ B50](TU_DIEN_DU_LIEU.md#b50), [TĐ B05](TU_DIEN_DU_LIEU.md#b05); CHECK/mô tả/ghi chú của các bảng này | phien_ban_hien_tai_id; phien_ban_truoc_id; so_phien_ban; de_xuat_ke_hoach_tap_id; moc_thay_doi_ke_hoach | Khóa Member -> Plan theo id; lưu trữ Plan cũ trước công bố Plan mới. Con trỏ NULL chỉ tạm trong transaction tạo version 1; xuất bản đủ version/nội dung/lịch/audit. Version mới không sửa FK history. | Commit Plan + version + con trỏ cùng nhau, nguồn/base đúng. | Công bố Plan thiếu version; con trỏ sai workflow; phiên bản trước tạo vòng hoặc không phải cơ sở hợp lệ. | FK cùng Plan không chứng minh thứ tự/tính vô chu trình, trạng thái công bố hoặc version đã xác nhận; không thể CHECK đọc bảng version. |
| **C16** — [TĐ B33](TU_DIEN_DU_LIEU.md#b33), [TĐ B34](TU_DIEN_DU_LIEU.md#b34), [TĐ B35](TU_DIEN_DU_LIEU.md#b35), [TĐ B37](TU_DIEN_DU_LIEU.md#b37), [TĐ B38](TU_DIEN_DU_LIEU.md#b38); CHECK/mô tả/ghi chú của các bảng này | trang_thai; ngay_tap; ma_buoi_logic; thay_the_buoi_tap_id; phien_ban_ke_hoach_tap_id; buoi_tap_du_kien_id | Thay lịch CHUA_TAP tương lai hoặc lịch của phiên HUY theo Q07; giữ mã logic, link cũ và dùng version mới. Khóa Member/Plan/lịch/phiên; giải phóng slot cũ trước tạo mới; không thay lịch có phiên DANG_TAP/HOAN_THANH. | Phiên HUY giữ lịch cũ, tập lại qua lịch thay thế/version mới hợp lệ. | Restart cùng lịch, tạo Free Workout/lịch giả, đổi ngày/FK history để né UNIQUE. | A giữ cặp giờ cùng ngày; Backend kiểm tra ngữ cảnh lịch và giờ trong ngày. Self FK/UNIQUE không chứng minh lịch cũ được phép thay hay version mới hợp lệ. |
| **C17** — [TĐ B37](TU_DIEN_DU_LIEU.md#b37), [TĐ B38](TU_DIEN_DU_LIEU.md#b38), [TĐ B39](TU_DIEN_DU_LIEU.md#b39), [TĐ B40](TU_DIEN_DU_LIEU.md#b40); CHECK/mô tả/ghi chú của các bảng này | buoi_tap_du_kien_id; trang_thai; phien_ban_du_lieu; ma_lan_hoan_thanh; các cột kết quả | Start/Save Set/Complete đúng ownership/trạng thái/idempotency; khóa phiên cha để save không tranh complete. Phiên HUY không hồi sinh; HOAN_THANH không sửa/xóa cả cây. Reps thực tế 0 được phép. | Save revision đúng trước Complete, hoàn thành một lần. | Sửa set sau Complete; HUY -> DANG_TAP; lấy mục tiêu làm kết quả thực tế. | A/B chỉ chặn dữ liệu cùng hàng/trùng lịch; không biết trạng thái trước hoặc khóa cây con. Không thêm yêu cầu Membership vào Workout. |
| **C18** — [TĐ B22](TU_DIEN_DU_LIEU.md#b22), [TĐ B41](TU_DIEN_DU_LIEU.md#b41), [TĐ B42](TU_DIEN_DU_LIEU.md#b42); CHECK/mô tả/ghi chú của các bảng này | phan_cong_huan_luyen_vien_id; hoi_vien_id; huan_luyen_vien_id; nguoi_gui_id | Member được đọc history của mình sau hết kỳ/đổi PT. Gửi mới cần quyền Chat và đúng lần phân công còn hiệu lực; người gửi thuộc cặp. PT cũ mất scope, PT mới không đọc hội thoại cũ; lần phân công mới có hội thoại riêng. | Member đọc thread cũ nhưng chỉ gửi trong thread/phân công đang hợp lệ. | Mượn phân công mới để gửi vào thread cũ, PT mới đọc thread cũ, người thứ ba gửi. | FK kép bảo vệ cặp/lần phân công tồn tại, không thay được kiểm tra Role, quyền và hiệu lực tại thời điểm gửi. |
| **C19** — [TĐ B17](TU_DIEN_DU_LIEU.md#b17), [TĐ B18](TU_DIEN_DU_LIEU.md#b18), [TĐ B19](TU_DIEN_DU_LIEU.md#b19), [TĐ B42](TU_DIEN_DU_LIEU.md#b42), [TĐ B43](TU_DIEN_DU_LIEU.md#b43); CHECK/mô tả/ghi chú của các bảng này | su_dung_quyen_loi_id; loai_su_dung; nguoi_gui_id; tin_nhan_id | Chỉ tin Member thực sự kích hoạt kỳ đầu đang chờ tạo một usage TRO_CHUYEN_HUAN_LUYEN. Tin sau hoặc kỳ đã chạy do AI/QR/PT không tạo usage; PT gửi/mở/subscribe không kích hoạt. Tin + usage/activation nếu có + outbox cùng transaction. | Hai tin đầu đồng thời chỉ một tin gắn nguồn kích hoạt; retry trả tin cũ. | Usage mỗi message/mỗi kỳ nối tiếp; gắn lại usage vào tin khác; ghi tin mà thiếu outbox. | UNIQUE usage chặn dùng lại cùng hàng, không chứng minh chỉ có một usage Chat đúng nguồn toàn workflow; cần khóa dùng chung với AI/QR/PT. |
| **C20** — [TĐ B41](TU_DIEN_DU_LIEU.md#b41), [TĐ B42](TU_DIEN_DU_LIEU.md#b42), [TĐ B43](TU_DIEN_DU_LIEU.md#b43), [TĐ B44](TU_DIEN_DU_LIEU.md#b44), [TĐ B45](TU_DIEN_DU_LIEU.md#b45); CHECK/mô tả/ghi chú của các bảng này | so_thu_tu; so_thu_tu_cuoi; hoi_vien_doc_den_so; huan_luyen_vien_doc_den_so; trang_thai | Sequence commit được cấp dưới khóa hội thoại; outbox ghi cùng tin và phát sau commit, có thể phát lại nhưng client dedup. | Counter khớp tin đã commit, reconnect nhận lại theo sequence. | Phát trước commit; counter tăng nhưng tin rollback; phát lặp tạo tin DB mới. | A chỉ giới hạn con trỏ/sequence, UNIQUE chặn trùng; tổng tin, thứ tự commit và I/O cần transaction/worker. |
| **C21** — [TĐ B25](TU_DIEN_DU_LIEU.md#b25), [TĐ B27](TU_DIEN_DU_LIEU.md#b27), [TĐ B28](TU_DIEN_DU_LIEU.md#b28), [TĐ B30](TU_DIEN_DU_LIEU.md#b30), [TĐ B44](TU_DIEN_DU_LIEU.md#b44), [TĐ B45](TU_DIEN_DU_LIEU.md#b45), [TĐ B46](TU_DIEN_DU_LIEU.md#b46), [TĐ B47](TU_DIEN_DU_LIEU.md#b47), [TĐ B48](TU_DIEN_DU_LIEU.md#b48), [TĐ B49](TU_DIEN_DU_LIEU.md#b49), [TĐ B50](TU_DIEN_DU_LIEU.md#b50); CHECK/mô tả/ghi chú của các bảng này | du_lieu_da_chot; ngu_canh_da_chot; bai_tap_id; giao_an_mau_id; noi_dung_de_xuat | Rule Engine chọn candidate thật, phù hợp profile/catalog; toàn bộ dụng cụ của bài là AND. Structured output kiểm định schema rồi nghiệp vụ; Apply kiểm tra lại ID/candidate/equipment hiện tại, không parse text Chat để áp dụng. | Bench + Barbell chỉ chọn khi có đủ cả hai; bài không dòng dụng cụ không yêu cầu dụng cụ. | LLM bịa ID, chỉ trùng một dụng cụ, candidate cũ được coi là quyền vĩnh viễn. | FK không nằm trong JSON; CHECK không truy vấn tập candidate/catalog/hồ sơ hoặc đánh giá output. Không thêm nhóm OR, tự kê y khoa hoặc account AI. |
| **C22** — [TĐ B18](TU_DIEN_DU_LIEU.md#b18), [TĐ B19](TU_DIEN_DU_LIEU.md#b19), [TĐ B46](TU_DIEN_DU_LIEU.md#b46); CHECK/mô tả/ghi chú của các bảng này | trang_thai_han_muc; ky_han_hoi_vien_id; su_dung_quyen_loi_id; so_luot_tro_ly_da_dung; so_luot_tro_ly_giu_cho | Q03 xác minh quyền trước; khóa Member -> chuỗi/kỳ -> request, giữ 1 quota cho request hợp lệ cùng usage/activation. KHONG_AP_DUNG chưa quota; GIU_CHO -> DA_TINH chuyển giữ sang dùng; lỗi kỹ thuật -> DA_TRA trả đúng một lần; không dùng kỳ tương lai. | Retry cùng request không tăng lượt; lỗi trả quota nhưng giữ activation đã commit. | Counter lệch request, trả hai lần, hồi sinh DA_TRA hoặc reset đồng hồ khi provider lỗi. | A B46 giữ cặp NULL/trạng thái, A B18 giữ trần counter; tổng ledger/request, trạng thái trước và quyền cần transaction. |
| **C23** — [TĐ B46](TU_DIEN_DU_LIEU.md#b46), [TĐ B49](TU_DIEN_DU_LIEU.md#b49); CHECK/mô tả/ghi chú của các bảng này | yeu_cau_tro_ly_id; so_lan; trang_thai; ket_qua_cau_truc; ma_bam_phan_hoi | Mọi lần gọi/retry thuộc cùng request, provider chỉ gọi sau commit; lưu trace đã lọc, không API key/full health profile/JSON lỗi thành Plan. Kết thúc kỹ thuật trả quota theo C22, phản hồi trễ không ghi đè request kết thúc. | Retry tăng so_lan, giữ request và quota cũ. | Provider call tạo lượt mới; output chưa kiểm định được áp dụng; response trễ hồi sinh request. | A kiểm tra lần gọi/mốc, B chống trùng; tính hợp lệ output, trạng thái cha và I/O không là CHECK. |
| **C24** — [TĐ B22](TU_DIEN_DU_LIEU.md#b22), [TĐ B33](TU_DIEN_DU_LIEU.md#b33), [TĐ B34](TU_DIEN_DU_LIEU.md#b34), [TĐ B37](TU_DIEN_DU_LIEU.md#b37), [TĐ B46](TU_DIEN_DU_LIEU.md#b46), [TĐ B50](TU_DIEN_DU_LIEU.md#b50), [TĐ B05](TU_DIEN_DU_LIEU.md#b05); CHECK/mô tả/ghi chú của các bảng này | nguon_de_xuat; phan_cong_huan_luyen_vien_id; yeu_cau_tro_ly_id; ke_hoach_tap_id; phien_ban_co_so_id; nguoi_quyet_dinh_id; phien_ban_ho_so_co_so; moc_thay_doi_ke_hoach_co_so | Nguồn XOR đúng PT/AI, ownership và cặp Plan/base hợp lệ; TAO_MOI không có Plan/base. Member confirm/reject; revalidate TTL/trạng thái/base/profile/mốc/schedule/phân công nguồn dưới khóa. Apply tạo version + audit atomic; phân công nguồn kết thúc thì XUNG_DOT dù được phân công lại. | PT Proposal đúng phân công không cần Chat/quota/gói; Member xác nhận đúng bản đã preview. | PT tự Apply; vừa nguồn PT vừa AI; sai base/Member; phân công nguồn hết hiệu lực vẫn Apply. | NULL/XOR riêng lẻ có thể biểu diễn cùng hàng, nhưng toàn bộ kiểm định nguồn/quyền/base phải đọc bảng khác. Từ điển B50 quy định FK kép + transaction: giữ cách đó, không tự thêm CHECK nguồn ngoài A. JSON/fingerprint bất biến không được client thay khi confirm. |
| **C25** — [TĐ B04](TU_DIEN_DU_LIEU.md#b04), [TĐ B51](TU_DIEN_DU_LIEU.md#b51); CHECK/mô tả/ghi chú của các bảng này | nguoi_thuc_hien_id; loai_tac_nhan; loai_doi_tuong; dinh_danh_doi_tuong; du_lieu_truoc; du_lieu_sau | Audit thành công ghi cùng domain transaction, đúng actor/đối tượng/trước-sau và whitelist; tham chiếu đa hình chỉ để trace, không FK giả. Lịch sử cấp/thu hồi/cấp lại Role đọc audit. | Domain + audit cùng commit, không secret trong payload. | Domain commit thiếu audit; log token/key hoặc giả FK đa hình được DB bảo vệ. | CHECK không kiểm tra đối tượng thuộc bảng động hay tác vụ đã commit; Q12 giữ audit, không thêm lịch sử Role riêng. |
| **C26** — [TĐ B09](TU_DIEN_DU_LIEU.md#b09), [TĐ B14](TU_DIEN_DU_LIEU.md#b14), [TĐ B15](TU_DIEN_DU_LIEU.md#b15), [TĐ B16](TU_DIEN_DU_LIEU.md#b16), [TĐ B19](TU_DIEN_DU_LIEU.md#b19), [TĐ B21](TU_DIEN_DU_LIEU.md#b21), [TĐ B23](TU_DIEN_DU_LIEU.md#b23), [TĐ B33](TU_DIEN_DU_LIEU.md#b33), [TĐ B38](TU_DIEN_DU_LIEU.md#b38), [TĐ B39](TU_DIEN_DU_LIEU.md#b39), [TĐ B40](TU_DIEN_DU_LIEU.md#b40), [TĐ B42](TU_DIEN_DU_LIEU.md#b42), [TĐ B45](TU_DIEN_DU_LIEU.md#b45), [TĐ B46](TU_DIEN_DU_LIEU.md#b46), [TĐ B49](TU_DIEN_DU_LIEU.md#b49), [TĐ B50](TU_DIEN_DU_LIEU.md#b50), [TĐ B52](TU_DIEN_DU_LIEU.md#b52); CHECK/mô tả/ghi chú của các bảng này | các mã retry; khoa_yeu_cau; pham_vi; ma_bam_noi_dung; trang_thai | Idempotency đúng scope, khóa ổn định và payload khớp; cùng key khác nội dung báo xung đột. TTL B52 không thay UNIQUE nghiệp vụ lâu dài; webhook dùng inbox/reference riêng. | Cùng key/payload trả kết quả đã ghi; key B52 hết hạn vẫn không ghi lại buổi/kỳ/version. | Cùng key đổi nội dung, bỏ UNIQUE domain khi TTL hết hoặc coi webhook là request user. | UNIQUE chỉ chặn trùng tuple, không so payload cũ/trạng thái xử lý/side effect; cần transaction và xử lý retry. |

#### 6.3.5. Bảng chứng minh bao phủ đủ 52 mục CHECK của từ điển

Cột thứ hai giữ nguyên văn phần CHECK để không bỏ sót điều kiện mô tả bằng lời. A liệt kê **mọi CHECK của bảng**, gồm các enum/cờ bổ sung từ mô tả cột; B truy vết ràng buộc có sẵn; C là các invariant liên quan cần transaction. Với dòng “nền kiểu/NULL”, giữ đúng kiểu/NULL của từng cột ở từ điển và baseline, không tự sinh CHECK chung. Nội dung phủ định phạm vi (không NOW(), không qua nửa đêm, Chat độc lập…) được giải thích ở A/B/C tương ứng, không biến thành nghiệp vụ mới.

| Bảng / nguồn | Nguyên văn “CHECK/điều kiện cùng hàng dự kiến” | Phân loại A: tên CHECK | B và nền kiểu/NULL | C liên quan |
| --- | --- | --- | --- | --- |
| [TĐ B01](TU_DIEN_DU_LIEU.md#b01) — `chi_nhanh` | Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt. | `kiem_tra_b01_01` | **B01**; nền kiểu/NULL theo toàn bộ cột của bảng | **C01** |
| [TĐ B02](TU_DIEN_DU_LIEU.md#b02) — `nguoi_dung` | Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt. | `kiem_tra_b02_01` | **B02**; nền kiểu/NULL theo toàn bộ cột của bảng | **C01**, **C04** |
| [TĐ B03](TU_DIEN_DU_LIEU.md#b03) — `vai_tro` | Chỉ bốn mã vai trò CORE; không có FREE/PREMIUM. | `kiem_tra_b03_01` | **B03**; nền kiểu/NULL theo toàn bộ cột của bảng | **C01** |
| [TĐ B04](TU_DIEN_DU_LIEU.md#b04) — `phan_quyen_nguoi_dung` | thu_hoi_luc NULL hoặc >= cap_luc. | `kiem_tra_b04_01` | **B04**; nền kiểu/NULL theo toàn bộ cột của bảng | **C02**, **C25** |
| [TĐ B05](TU_DIEN_DU_LIEU.md#b05) — `ho_so_hoi_vien` | so_ngay_tap_mong_muon NULL hoặc trong 1..7. thoi_luong_moi_buoi_phut NULL hoặc > 0. | `kiem_tra_b05_01` | **B05**; nền kiểu/NULL theo toàn bộ cột của bảng | **C01**, **C03**, **C15**, **C24** |
| [TĐ B06](TU_DIEN_DU_LIEU.md#b06) — `ho_so_huan_luyen_vien` | Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt. | `kiem_tra_b06_01` | **B06**; nền kiểu/NULL theo toàn bộ cột của bảng | **C01** |
| [TĐ B07](TU_DIEN_DU_LIEU.md#b07) — `ngay_ranh_hoi_vien` | thu_trong_tuan trong 2..8. | `kiem_tra_b07_01` | **B07**; nền kiểu/NULL theo toàn bộ cột của bảng | **C03** |
| [TĐ B08](TU_DIEN_DU_LIEU.md#b08) — `dung_cu_hoi_vien` | Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt. | Không có CHECK riêng; không suy enum/cờ chưa chốt | **B08**; nền kiểu/NULL theo toàn bộ cột của bảng | **C03** |
| [TĐ B09](TU_DIEN_DU_LIEU.md#b09) — `chi_so_co_the` | Cân nặng, chiều cao và vòng eo nếu có phải > 0. | `kiem_tra_b09_01` | **B09**; nền kiểu/NULL theo toàn bộ cột của bảng | **C04**, **C26** |
| [TĐ B10](TU_DIEN_DU_LIEU.md#b10) — `the_truy_cap` | het_han_luc > ngay_tao. | `kiem_tra_b10_01` | **B10**; nền kiểu/NULL theo toàn bộ cột của bảng | **C01**, **C05** |
| [TĐ B11](TU_DIEN_DU_LIEU.md#b11) — `yeu_cau_dat_lai_mat_khau` | het_han_luc > ngay_tao. | `kiem_tra_b11_01` | **B11**; nền kiểu/NULL theo toàn bộ cột của bảng | **C01**, **C05** |
| [TĐ B12](TU_DIEN_DU_LIEU.md#b12) — `goi_tap` | gia > 0; thoi_han_ngay > 0; phien_ban_cau_hinh >= 1. | `kiem_tra_b12_01`<br>`kiem_tra_b12_02` | **B12**; nền kiểu/NULL theo toàn bộ cột của bảng | **C06** |
| [TĐ B13](TU_DIEN_DU_LIEU.md#b13) — `quyen_loi_goi_tap` | Các cờ chỉ 0/1; không có AI thì giới hạn=0; có AI thì giới hạn NULL hoặc > 0. so_buoi_huan_luyen_vien >= 0, độc lập cờ Chat. Ít nhất một quyền có thời hạn: gym hoặc AI hoặc Chat PT được bật, hoặc tổng buổi PT > 0. Không đặt điều kiện Chat=false kéo theo tổng buổi=0. | `kiem_tra_b13_01`<br>`kiem_tra_b13_02`<br>`kiem_tra_b13_03` | **B13**; nền kiểu/NULL theo toàn bộ cột của bảng | **C06**, **C11** |
| [TĐ B14](TU_DIEN_DU_LIEU.md#b14) — `don_mua_goi` | so_tien_phai_thu > 0; don_vi_tien=VND; hạn > mốc chốt giá. | `kiem_tra_b14_01`<br>`kiem_tra_b14_02`<br>`kiem_tra_b14_03` | **B14**; nền kiểu/NULL theo toàn bộ cột của bảng | **C04**, **C06**, **C07**, **C08**, **C26** |
| [TĐ B15](TU_DIEN_DU_LIEU.md#b15) — `lan_thanh_toan` | so_lan > 0; số tiền không âm và tiền yêu cầu > 0. Giới hạn nội bộ ma_don_cong_thanh_toan trong 1..9007199254740991 để truyền JSON an toàn. | `kiem_tra_b15_01`<br>`kiem_tra_b15_02`<br>`kiem_tra_b15_03` | **B15**; nền kiểu/NULL theo toàn bộ cột của bảng | **C04**, **C07**, **C08**, **C09**, **C26** |
| [TĐ B16](TU_DIEN_DU_LIEU.md#b16) — `su_kien_thanh_toan` | so_lan_nhan >= 1; chu_ky_hop_le chỉ 0/1. Chữ ký sai => khoa_chong_lap IS NULL, tránh chiếm khóa của webhook thật. | `kiem_tra_b16_01`<br>`kiem_tra_b16_02`<br>`kiem_tra_b16_03` | **B16**; nền kiểu/NULL theo toàn bộ cột của bảng | **C04**, **C08**, **C09**, **C26** |
| [TĐ B17](TU_DIEN_DU_LIEU.md#b17) — `dang_ky_goi_tap` | Không dùng NOW() trong cột sinh. Chờ kích hoạt: ngày bắt đầu và nguồn kích hoạt đều NULL; đã kích hoạt: cả hai có giá trị. | `kiem_tra_b17_01`<br>`kiem_tra_b17_02` | **B17**; nền kiểu/NULL theo toàn bộ cột của bảng | **C09**, **C10**, **C13**, **C19** |
| [TĐ B18](TU_DIEN_DU_LIEU.md#b18) — `ky_han_hoi_vien` | thoi_han_ngay > 0; gia_da_mua > 0; so_thu_tu NULL hoặc > 0. Cặp ngày cùng NULL hoặc cùng có giá trị, với ngay_ket_thuc đúng bằng ngay_bat_dau cộng thoi_han_ngay ngày. 0 <= so_buoi_huan_luyen_vien_da_dung <= so_buoi_huan_luyen_vien. Có giới hạn AI thì đã dùng + giữ chỗ <= giới hạn; không có quyền AI thì giới hạn/counter AI bằng 0. Các cờ chỉ 0/1; cùng điều kiện quyền như cấu hình gói. Cờ Chat độc lập tổng/đã dùng buổi PT; không có counter tin nhắn. | `kiem_tra_b18_01`<br>`kiem_tra_b18_02`<br>`kiem_tra_b18_03`<br>`kiem_tra_b18_04`<br>`kiem_tra_b18_05`<br>`kiem_tra_b18_06`<br>`kiem_tra_b18_07`<br>`kiem_tra_b18_08` | **B18**; nền kiểu/NULL theo toàn bộ cột của bảng | **C04**, **C06**, **C07**, **C08**, **C09**, **C10**, **C11**, **C13**, **C14**, **C19**, **C22** |
| [TĐ B19](TU_DIEN_DU_LIEU.md#b19) — `su_dung_quyen_loi` | Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng ràng buộc nghiệp vụ đã được chốt. | `kiem_tra_b19_01` | **B19**; nền kiểu/NULL theo toàn bộ cột của bảng | **C04**, **C10**, **C11**, **C13**, **C14**, **C19**, **C22**, **C26** |
| [TĐ B20](TU_DIEN_DU_LIEU.md#b20) — `ma_vao_phong_tap` | het_han_luc > phat_hanh_luc. | `kiem_tra_b20_01` | **B20**; nền kiểu/NULL theo toàn bộ cột của bảng | **C05**, **C10**, **C13** |
| [TĐ B21](TU_DIEN_DU_LIEU.md#b21) — `lich_su_vao_phong_tap` | Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt. | Không có CHECK riêng; không suy enum/cờ chưa chốt | **B21**; nền kiểu/NULL theo toàn bộ cột của bảng | **C04**, **C10**, **C13**, **C26** |
| [TĐ B22](TU_DIEN_DU_LIEU.md#b22) — `phan_cong_huan_luyen_vien` | ngay_ket_thuc NULL hoặc > ngay_bat_dau. | `kiem_tra_b22_01` | **B22**; nền kiểu/NULL theo toàn bộ cột của bảng | **C12**, **C14**, **C18**, **C24** |
| [TĐ B23](TU_DIEN_DU_LIEU.md#b23) — `lich_su_su_dung_huan_luyen_vien` | trang_thai=HOAN_THANH; so_luot_su_dung=1; hoan_thanh_luc <= xac_nhan_luc. | `kiem_tra_b23_01`<br>`kiem_tra_b23_02` | **B23**; nền kiểu/NULL theo toàn bộ cột của bảng | **C04**, **C10**, **C11**, **C14**, **C26** |
| [TĐ B24](TU_DIEN_DU_LIEU.md#b24) — `ghi_chu_huan_luyen` | Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt. | Không có CHECK riêng; không suy enum/cờ chưa chốt | **B24**; nền kiểu/NULL theo toàn bộ cột của bảng | **C04** |
| [TĐ B25](TU_DIEN_DU_LIEU.md#b25) — `dung_cu` | Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt. | `kiem_tra_b25_01` | **B25**; nền kiểu/NULL theo toàn bộ cột của bảng | **C21** |
| [TĐ B26](TU_DIEN_DU_LIEU.md#b26) — `nhom_co` | Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt. | Không có CHECK riêng; không suy enum/cờ chưa chốt | **B26**; nền kiểu/NULL theo toàn bộ cột của bảng | Không bổ sung invariant riêng từ mục CHECK |
| [TĐ B27](TU_DIEN_DU_LIEU.md#b27) — `bai_tap` | phien_ban_noi_dung >= 1. | `kiem_tra_b27_01`<br>`kiem_tra_b27_02` | **B27**; nền kiểu/NULL theo toàn bộ cột của bảng | **C03**, **C04**, **C06**, **C21** |
| [TĐ B28](TU_DIEN_DU_LIEU.md#b28) — `bai_tap_dung_cu` | Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt. | Không có CHECK riêng; không suy enum/cờ chưa chốt | **B28**; nền kiểu/NULL theo toàn bộ cột của bảng | **C21** |
| [TĐ B29](TU_DIEN_DU_LIEU.md#b29) — `bai_tap_nhom_co` | vai_tro_nhom_co thuộc CHINH/PHU. | `kiem_tra_b29_01` | **B29**; nền kiểu/NULL theo toàn bộ cột của bảng | Không bổ sung invariant riêng từ mục CHECK |
| [TĐ B30](TU_DIEN_DU_LIEU.md#b30) — `giao_an_mau` | so_buoi_moi_tuan trong 1..7; phien_ban_noi_dung >= 1. | `kiem_tra_b30_01`<br>`kiem_tra_b30_02` | **B30**; nền kiểu/NULL theo toàn bộ cột của bảng | **C03**, **C04**, **C06**, **C21** |
| [TĐ B31](TU_DIEN_DU_LIEU.md#b31) — `ngay_trong_giao_an` | so_thu_tu > 0; thoi_luong_du_kien_phut > 0. | `kiem_tra_b31_01` | **B31**; nền kiểu/NULL theo toàn bộ cột của bảng | Không bổ sung invariant riêng từ mục CHECK |
| [TĐ B32](TU_DIEN_DU_LIEU.md#b32) — `bai_tap_trong_giao_an` | Thứ tự/số hiệp/reps > 0; reps tối thiểu <= tối đa. | `kiem_tra_b32_01` | **B32**; nền kiểu/NULL theo toàn bộ cột của bảng | Không bổ sung invariant riêng từ mục CHECK |
| [TĐ B33](TU_DIEN_DU_LIEU.md#b33) — `ke_hoach_tap` | Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt. | `kiem_tra_b33_01` | **B33**; nền kiểu/NULL theo toàn bộ cột của bảng | **C11**, **C15**, **C16**, **C24**, **C26** |
| [TĐ B34](TU_DIEN_DU_LIEU.md#b34) — `phien_ban_ke_hoach_tap` | so_phien_ban >= 1. | `kiem_tra_b34_01`<br>`kiem_tra_b34_02` | **B34**; nền kiểu/NULL theo toàn bộ cột của bảng | **C03**, **C04**, **C06**, **C15**, **C16**, **C24** |
| [TĐ B35](TU_DIEN_DU_LIEU.md#b35) — `ngay_trong_ke_hoach` | thu_trong_tuan trong 2..8; thứ tự và thời lượng > 0. | `kiem_tra_b35_01` | **B35**; nền kiểu/NULL theo toàn bộ cột của bảng | **C04**, **C06**, **C15**, **C16** |
| [TĐ B36](TU_DIEN_DU_LIEU.md#b36) — `bai_tap_trong_ke_hoach` | Thứ tự/số hiệp/reps > 0; tối thiểu <= tối đa; khối lượng NULL hoặc >= 0. | `kiem_tra_b36_01`<br>`kiem_tra_b36_02` | **B36**; nền kiểu/NULL theo toàn bộ cột của bảng | **C04**, **C06**, **C15** |
| [TĐ B37](TU_DIEN_DU_LIEU.md#b37) — `buoi_tap_du_kien` | Cặp giờ cùng NULL hoặc cùng có và bắt đầu < kết thúc; buổi qua nửa đêm chưa thuộc thiết kế MVP. | `kiem_tra_b37_01`<br>`kiem_tra_b37_02` | **B37**; nền kiểu/NULL theo toàn bộ cột của bảng | **C04**, **C11**, **C16**, **C17**, **C24** |
| [TĐ B38](TU_DIEN_DU_LIEU.md#b38) — `phien_tap` | ket_thuc_luc NULL hoặc >= bat_dau_luc; HOAN_THANH => kết thúc và mã hoàn thành không NULL. | `kiem_tra_b38_01`<br>`kiem_tra_b38_02`<br>`kiem_tra_b38_03` | **B38**; nền kiểu/NULL theo toàn bộ cột của bảng | **C03**, **C04**, **C11**, **C16**, **C17**, **C26** |
| [TĐ B39](TU_DIEN_DU_LIEU.md#b39) — `bai_tap_trong_phien` | Thứ tự/số hiệp/reps > 0; reps tối thiểu <= tối đa; khối lượng NULL hoặc >= 0. | `kiem_tra_b39_01`<br>`kiem_tra_b39_02` | **B39**; nền kiểu/NULL theo toàn bộ cột của bảng | **C04**, **C06**, **C11**, **C17**, **C26** |
| [TĐ B40](TU_DIEN_DU_LIEU.md#b40) — `hiep_tap` | so_thu_tu > 0; phien_ban_du_lieu >= 1; khối lượng NULL hoặc >= 0. | `kiem_tra_b40_01`<br>`kiem_tra_b40_02` | **B40**; nền kiểu/NULL theo toàn bộ cột của bảng | **C03**, **C04**, **C11**, **C17**, **C26** |
| [TĐ B41](TU_DIEN_DU_LIEU.md#b41) — `hoi_thoai` | Con trỏ đã đọc không âm và <= so_thu_tu_cuoi. | `kiem_tra_b41_01` | **B41**; nền kiểu/NULL theo toàn bộ cột của bảng | **C18**, **C20** |
| [TĐ B42](TU_DIEN_DU_LIEU.md#b42) — `tin_nhan` | so_thu_tu > 0; nội dung không rỗng. | `kiem_tra_b42_01`<br>`kiem_tra_b42_02` | **B42**; nền kiểu/NULL theo toàn bộ cột của bảng | **C04**, **C10**, **C11**, **C18**, **C19**, **C20**, **C26** |
| [TĐ B43](TU_DIEN_DU_LIEU.md#b43) — `su_kien_phat_tin_nhan` | Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt. | `kiem_tra_b43_01` | **B43**; nền kiểu/NULL theo toàn bộ cột của bảng | **C19**, **C20** |
| [TĐ B44](TU_DIEN_DU_LIEU.md#b44) — `hoi_thoai_tro_ly` | Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt. | `kiem_tra_b44_01` | **B44**; nền kiểu/NULL theo toàn bộ cột của bảng | **C10**, **C20**, **C21** |
| [TĐ B45](TU_DIEN_DU_LIEU.md#b45) — `tin_nhan_tro_ly` | nguon_tin trong HOI_VIEN/TRO_LY; so_thu_tu > 0. | `kiem_tra_b45_01`<br>`kiem_tra_b45_02` | **B45**; nền kiểu/NULL theo toàn bộ cột của bảng | **C04**, **C20**, **C21**, **C26** |
| [TĐ B46](TU_DIEN_DU_LIEU.md#b46) — `yeu_cau_tro_ly` | trang_thai_han_muc chỉ KHONG_AP_DUNG/GIU_CHO/DA_TINH/DA_TRA. KHONG_AP_DUNG: ky_han_hoi_vien_id và su_dung_quyen_loi_id cùng NULL; trạng thái hạn mức khác: cả hai NOT NULL. Các điều kiện này chỉ dùng cột cùng hàng; kiểm tra quyền/counter dùng transaction. | `kiem_tra_b46_01`<br>`kiem_tra_b46_02`<br>`kiem_tra_b46_03`<br>`kiem_tra_b46_04` | **B46**; nền kiểu/NULL theo toàn bộ cột của bảng | **C04**, **C10**, **C21**, **C22**, **C23**, **C24**, **C26** |
| [TĐ B47](TU_DIEN_DU_LIEU.md#b47) — `bai_tap_ung_vien` | Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt. | Không có CHECK riêng; không suy enum/cờ chưa chốt | **B47**; nền kiểu/NULL theo toàn bộ cột của bảng | **C04**, **C21** |
| [TĐ B48](TU_DIEN_DU_LIEU.md#b48) — `giao_an_ung_vien` | Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng các ràng buộc nghiệp vụ đã được chốt. | Không có CHECK riêng; không suy enum/cờ chưa chốt | **B48**; nền kiểu/NULL theo toàn bộ cột của bảng | **C04**, **C21** |
| [TĐ B49](TU_DIEN_DU_LIEU.md#b49) — `lan_goi_mo_hinh` | so_lan > 0; kết thúc NULL hoặc >= bắt đầu. | `kiem_tra_b49_01`<br>`kiem_tra_b49_02` | **B49**; nền kiểu/NULL theo toàn bộ cột của bảng | **C04**, **C21**, **C23**, **C26** |
| [TĐ B50](TU_DIEN_DU_LIEU.md#b50) — `de_xuat_ke_hoach_tap` | nguon_de_xuat thuộc TRO_LY/HUAN_LUYEN_VIEN. het_han_luc > ngay_tao. | `kiem_tra_b50_01`<br>`kiem_tra_b50_02`<br>`kiem_tra_b50_03`<br>`kiem_tra_b50_04` | **B50**; nền kiểu/NULL theo toàn bộ cột của bảng | **C04**, **C05**, **C11**, **C15**, **C21**, **C24**, **C26** |
| [TĐ B51](TU_DIEN_DU_LIEU.md#b51) — `nhat_ky_he_thong` | Theo kiểu, NOT NULL, tập trạng thái/cờ nêu trong mô tả; chỉ áp dụng ràng buộc nghiệp vụ đã được chốt. | `kiem_tra_b51_01`<br>`kiem_tra_b51_02` | **B51**; nền kiểu/NULL theo toàn bộ cột của bảng | **C02**, **C04**, **C25** |
| [TĐ B52](TU_DIEN_DU_LIEU.md#b52) — `yeu_cau_chong_lap` | het_han_luc > ngay_tao. | `kiem_tra_b52_01`<br>`kiem_tra_b52_02` | **B52**; nền kiểu/NULL theo toàn bộ cột của bảng | **C05**, **C26** |

**Đối chiếu bao phủ:** A có 83 tên duy nhất ở 45 bảng; 7 bảng không có CHECK riêng là B08, B21, B24, B26, B28, B47, B48. Chúng vẫn có nền kiểu/NULL và nhóm B tương ứng, không bị bỏ qua. Có 32 tập enum nhiều giá trị từ mô tả cột; các giá trị đơn như VND/HOAN_THANH/WEB_HUAN_LUYEN_VIEN và các cờ 0/1 được giữ trong các predicate A tương ứng. B13/B18 giữ đầy đủ quyền độc lập, B17 giữ cặp nguồn/mốc và quy tắc generated không NOW(), B46 tách NULL/quota-state khỏi kiểm tra quyền/counter C22.

Đây là số **dự kiến trong kế hoạch** đã chốt để implementation, chưa phải số CHECK thực tế trên server. Nếu probe phát hiện predicate cần đổi cách viết, phải cập nhật manifest/bằng chứng mà giữ nghĩa; nếu không giữ được nghĩa hoặc cần đổi schema thì dừng trước implementation để review lại.

## 7. Phụ thuộc vòng: tạo schema khác với ghi dữ liệu nghiệp vụ

P1/P2 giải quyết **thứ tự tạo schema**. Khi các FK đã hoạt động, Backend sau này vẫn cần thứ tự INSERT/UPDATE hợp lệ; MariaDB không đợi đến commit mới kiểm tra FK.

### 7.1. Chuỗi Membership, kỳ và usage

- `dang_ky_goi_tap.lan_su_dung_dau_tien_id` nullable, trỏ `su_dung_quyen_loi`.
- `su_dung_quyen_loi.ky_han_hoi_vien_id` NOT NULL, trỏ kỳ.
- `ky_han_hoi_vien.dang_ky_goi_tap_id` nullable trước xác nhận thanh toán, sau đó trỏ chuỗi.

Luồng dữ liệu dự kiến: tạo đơn + kỳ CHO_THANH_TOAN; xác nhận thanh toán hợp lệ gắn kỳ vào chuỗi có con trỏ usage NULL; khi dùng quyền lợi hợp lệ thì tạo usage, gán nguồn chuỗi và mốc kỳ cùng transaction. Không tạo usage giả để thỏa FK, không kích hoạt khi tạo đơn hoặc thanh toán.

### 7.2. Plan, Version và Proposal

- Tạo Plan với `phien_ban_hien_tai_id = NULL` trong transaction.
- Proposal tạo mới đã có trước khi Apply và không có Plan/base version; Proposal chỉnh sửa tham chiếu Plan/version cơ sở đã tồn tại.
- Sau Member confirm và revalidate: tạo version trỏ Plan, trỏ Proposal khi có nguồn Proposal; tạo cây ngày/bài/lịch phù hợp; cập nhật con trỏ Plan và trạng thái áp dụng cùng transaction.
- Không công bố Plan thiếu version; không tự gán Plan/base version cơ sở vào Proposal TAO_MOI để né vòng.
- Version kết quả truy qua `phien_ban_ke_hoach_tap.de_xuat_ke_hoach_tap_id UNIQUE`; không thêm cột kết quả ngoài từ điển.
- `phien_ban_truoc_id` trỏ version trước đã có, đúng cùng Plan; không sửa version lịch sử.

### 7.3. Tin nhắn AI và request

- Tạo tin đầu vào trong đúng `hoi_thoai_tro_ly`, `tin_nhan_tro_ly.yeu_cau_tro_ly_id` tạm NULL.
- Tạo `yeu_cau_tro_ly` tham chiếu tin qua `tin_nhan_dau_vao_id NOT NULL`.
- Gắn tin đầu vào với request vừa tạo trong cùng transaction; không sửa nội dung tin lịch sử.
- Kiểm tra cặp FK ghép đảm bảo cả tin và request cùng hội thoại. Quota/activation chỉ ghi khi request đủ điều kiện, provider gọi sau commit.
- Không làm `tin_nhan_dau_vao_id` nullable hoặc tạo thêm bảng để phá vòng.

### 7.4. Lịch thay thế tự tham chiếu

Tạo hàng thay thế sau khi lịch gốc đã tồn tại, cùng Member/mã buổi logic và version mới đúng workflow. Giữ `thay_the_buoi_tap_id` và UNIQUE của nó; không trỏ vòng, không đổi FK phiên cũ. Lịch gốc HUY/DA_THAY_THE giữ lịch sử; phiên HUY vẫn là phiên duy nhất trên lịch gốc.

Các trình tự trên là ghi chú thiết kế cho service/transaction tương lai, **không phải tác vụ dữ liệu chạy trong migration**.

## 8. Phân định schema và nghiệp vụ

| Bất biến | Migration/schema bảo vệ | Backend/test nghiệp vụ vẫn bắt buộc |
| --- | --- | --- |
| Một kỳ mỗi đơn, payment retry | UNIQUE đơn/payment/reference, FK đúng nguồn | Chữ ký, số tiền, snapshot Q01, thứ tự xác nhận Q02 và đối soát Q10 |
| Kích hoạt một đồng hồ | Chuỗi/kỳ/usage, nguồn UNIQUE, NULL/kiểu đúng | Dùng quyền đầu tiên hợp lệ; nối kỳ, không quyền tương lai |
| Quota AI | Counter, trạng thái hạn mức, nguồn request/usage UNIQUE | Giữ/tính/trả một lần, retry provider, lỗi không lùi activation Q03 |
| Member 0..1 PT hiện thời | UNIQUE khoảng mở, index thời gian, FK | Khóa Member kể cả chưa có phân công; kiểm tra mọi overlap Q04 |
| Chat lịch sử | Hội thoại theo lần phân công, FK ghép và tin bất biến về thiết kế | Member đọc history, gửi kiểm tra quyền hiện thời; PT mới không đọc thread cũ Q05 |
| Buổi PT | Ledger + UNIQUE mã buổi/usage, counter theo kỳ | Xác nhận đúng PT/kỳ còn hiệu lực, không backdate/mượn kỳ Q06 |
| Plan và lịch | UNIQUE cột sinh Active Plan, Member/ngày, mã buổi/version | Chuyển Plan/lịch trong transaction, giữ trạng thái và history Q07 |
| Phiên HUY | FK lịch NOT NULL UNIQUE giữ nguyên | Không hồi sinh phiên; tạo lịch thay thế hợp lệ rồi Start Q07 |
| Workout miễn phí | Không thêm FK kỳ/entitlement mới vào Workout | Start/Save/Complete không đòi Membership; vẫn ownership/trạng thái Q08 |
| TTL | Lưu mốc cụ thể, hạn sau mốc phát hành/tạo | Policy tập trung QR 90 giây, Proposal 24 giờ; kiểm tra tại thời điểm dùng Q09 |
| Equipment AND | Bảng liên kết bài/dụng cụ UNIQUE | Candidate/Apply yêu cầu đủ mọi dụng cụ Q11 |
| Lịch sử | RESTRICT, snapshot và cấu trúc liên kết | Không hard-delete/history rewrite trên các đường ghi Q12 |
| PT Proposal | Nguồn PT/AI, FK assignment/version, UNIQUE kết quả | Chỉ cần assignment, không Chat/quota/Membership; confirm/revalidate Q13 |
| Realtime/idempotency | Sequence, client ID, outbox và khóa chống lặp | Ghi tin/outbox cùng transaction, broadcast sau commit, xử lý reconnect |
| Session hoàn thành | FK, revision và schema kết quả riêng | Khóa phiên cha khi Save/Complete, cấm mọi đường sửa cây đã hoàn thành |

Thứ tự khóa theo thiết kế: **hội viên → đơn/lần thanh toán → chuỗi/kỳ → phân công → Plan/Proposal → lịch/phiên → request AI → hội thoại**, bỏ qua miền không tham gia, hàng cùng loại theo id tăng. Không đảo thứ tự, không giữ transaction khi gọi payOS/LLM/Reverb. Retry deadlock có giới hạn và giữ nguyên idempotency key.

Migration không thay thế các service/policy/test này. Không thêm trigger, cron, stored procedure hoặc bảng mới để tuyên bố đã bảo vệ đầy đủ nghiệp vụ.

## 9. Rollback và xử lý lỗi

### 9.1. Down trên Database thử nghiệm được phép xóa

Chỉ diễn tập rollback phá hủy trên Database cô lập, đã xác nhận không có dữ liệu cần giữ.

1. Dừng mọi writer.
2. Đảo M060 → M053: gỡ **đúng constraint FK do từng file thêm**, đảo source table rồi đảo tên FK trong từng table theo 5.4; gồm FK ghép/tự tham chiếu. Với file dở phải đối chiếu metadata trước, không giả định framework tự gọi down cho file chưa ghi thành công.
3. Xác nhận không còn FK của bộ migration trước khi drop bảng. Không gỡ index/UNIQUE đích trong lúc còn FK sử dụng.
4. Đảo M052 → M001: xóa bảng theo thứ tự ngược. PK/UNIQUE/generated/CHECK/index của bảng mất theo bảng.
5. Không xóa bảng theo dõi migration của framework thủ công; không can thiệp migration/bảng ngoài bộ này.
6. Chạy lại toàn bộ bộ migration và đối chiếu schema mới với baseline khi nhiệm vụ triển khai cho phép.

Đảo pha FK trước sẽ phá các vòng trước khi drop, không cần tắt kiểm tra FK. Nếu chỉ rollback một phần nhóm FK, schema chưa được phép phục vụ ứng dụng.

### 9.2. Lỗi giữa chừng

MariaDB 10.4 commit DDL theo từng statement; không giả transaction bao trọn file migration hoặc block Schema::table có nhiều statement. Bọc transaction không hoàn tác mọi DDL đã thành công, vì atomic DDL đầy đủ được cải thiện từ MariaDB 10.6. [MariaDB — Atomic DDL](https://mariadb.com/docs/server/reference/sql-statements/data-definition/atomic-ddl).

1. Dừng chuỗi và mọi writer; giữ log đã lọc. Ghi exact migration, source table, statement/constraint đang thử, **source table cuối hoàn tất**, các FK đã xác minh và đơn vị đang dở theo 5.4.
2. Đối chiếu bảng theo dõi migration với metadata thật: bảng/cột/UNIQUE/CHECK/FK, tuple nguồn/đích, index, action. File chưa được đánh dấu thành công vẫn có thể đã gắn FK. Nếu log bị mất sau commit DDL, tái dựng tiến độ từ metadata, không đoán từ lần gửi command cuối.
3. Lập danh sách tên đã có đúng, còn thiếu và khác định nghĩa. Không retry mù; không dùng hasTable/hasColumn/“constraint đã có” để bỏ qua định nghĩa sai, không tự đánh dấu cả file đã chạy.
4. Trên schema thử được phép xóa: phục hồi đúng phần đã xác minh theo thứ tự đảo source table/constraint ở 5.4, rồi chạy lại từ trạng thái đã biết. Chỉ dọn scope probe/migration được phép; không động dữ liệu ngoài phạm vi.
5. Trên môi trường có dữ liệu cần giữ: không drop/reset. Dừng để lập phương án sửa tiến hoặc khôi phục đã có backup và quyền riêng; tránh chạy down phá hủy chỉ để làm sạch dấu vết lỗi.
6. Sau phục hồi/retry, xác nhận đủ 144 FK, 83 CHECK nghiệp vụ dự kiến và các ràng buộc nền; ghi riêng CHECK JSON_VALID ngầm của MariaDB. Không đổi CHECK/action/quyền để làm file chạy qua. P0-T16/P3 phải diễn tập lỗi giữa bảng và giữa statement trong một bảng.
7. Giữ 8 file FK/60 file tổng. Nếu kết quả probe sau này chứng minh cần tách file, phải review và cập nhật đồng bộ toàn bộ Mxxx/count trước implementation; đây chưa phải quyết định cho phép tự tách hoặc thay schema.

### 9.3. Sau khi có dữ liệu thật

Q12 không cho hard-delete lịch sử. `down` phá hủy chỉ là công cụ thử nghiệm schema, **không phải workflow hủy Membership/Payment/Workout/Chat**. Khi đã có dữ liệu thật, ưu tiên migration sửa tiến có review và kế hoạch phục hồi; không sửa nội dung migration đã triển khai chung.

Không đưa `migrate:fresh`, `refresh`, `reset`, DROP/TRUNCATE hoặc tắt FK vào quy trình vận hành mặc định. Mọi thao tác có nguy cơ mất dữ liệu cần phạm vi và sự cho phép riêng.

## 10. Kế hoạch kiểm tra khi triển khai

**Tất cả checkbox trong mục này là việc chưa thực hiện.** Đây không phải báo cáo test đã PASS trên Database.

### 10.1. Kiểm tra tĩnh trước chạy

- [ ] Đủ danh mục M001–M060 theo thứ tự; không có file nghiệp vụ ngoài phạm vi.
- [ ] Đủ 52 bảng/575 cột, đúng tên Việt không dấu, snake_case; không phát sinh cột timestamp tiếng Anh hoặc soft delete.
- [ ] Mỗi cột khớp type/NULL/default; 5 generated có biểu thức/UNIQUE đúng, không ghi trực tiếp.
- [ ] Có 98 UNIQUE, 113 FK đơn và 31 FK ghép; đích FK có đúng PK/UNIQUE.
- [ ] Tên constraint tường minh, duy nhất đúng phạm vi, không quá 64 ký tự.
- [ ] Mỗi khai báo index có ánh xạ vật lý hoặc bằng chứng index bao phủ; không còn FK thiếu index đúng tiền tố.
- [ ] Đã hoàn thành P0-T01–P0-T16 **trước khi viết migration thật**, có bằng chứng/ngoại lệ vật lý theo 2.4; không lấy review tài liệu thay kết quả probe.
- [ ] Đủ 83 CHECK A có tên/predicate/test biên; truy vết 52 mục CHECK, 52 nhóm B và 26 nhóm C. Không có CHECK chéo bảng/NOW()/AUTO_INCREMENT.
- [ ] Default utf8mb4_unicode_ci, 95 override utf8mb4_nopad_bin và email utf8mb4_nopad_bin đúng manifest; không thay kiểu/cột.
- [ ] Mọi FK giữ hành vi RESTRICT; ngoại lệ default/NO ACTION (nếu có) được probe/ghi manifest; không bỏ CHECK, không thêm seed/dataset/logic/token/API key.
- [ ] Review `down` theo 48 đơn vị source table: ngược file, ngược nguồn, ngược constraint; có tiến độ bền vững và quy trình metadata cho file dở.

### 10.2. Kiểm thử schema trên MariaDB 10.4.32 cô lập

- [ ] Chạy toàn bộ migration trên schema trắng được phép; không bỏ qua migration lỗi.
- [ ] Chạy lại khi không còn migration pending: không đổi schema, không thêm bảng/cột/ràng buộc.
- [ ] Đối chiếu metadata thực tế của bảng, cột, default, collation, generated expression, PK/UNIQUE/FK/CHECK và index với từ điển.
- [ ] Đếm 52 bảng CORE riêng với bảng framework; 575 cột, 5 generated, 144 FK, 98 UNIQUE ngoài PK.
- [ ] Mỗi FK từ chối ID không tồn tại; FK ghép từ chối ghép sai Member/Order/Period/Plan/Version/Conversation/Assignment.
- [ ] FK nullable cho phép đúng giai đoạn chưa hình thành liên kết; test NULL không lách CHECK bắt buộc cặp.
- [ ] UNIQUE từ chối bản ghi trùng, nhưng cho phép nhiều NULL hợp lệ theo thiết kế.
- [ ] Hai Plan DANG_SU_DUNG cùng Member bị chặn; nhiều Plan LUU_TRU vẫn được.
- [ ] Hai phân công cận cuối NULL cùng Member bị chặn; không lấy test này làm bằng chứng chống mọi overlap.
- [ ] Hai lịch giữ slot cùng ngày bị chặn; HUY/DA_THAY_THE nhường slot; HOAN_THANH/BO_QUA vẫn giữ slot.
- [ ] Phiên HUY vẫn chiếm UNIQUE lịch; không INSERT phiên thứ hai vào cùng lịch.
- [ ] Generated trả đúng giá trị khi trạng thái đổi; không cho client ghi giá trị tùy ý.
- [ ] B17/B18/B33/B34/B37/B45/B46 chạy được cả tổ hợp CHECK/generated/FK theo P0, kể cả hành vi cha RESTRICT và các nhánh NULL sai.
- [ ] Đối chiếu đủ 83 CHECK nghiệp vụ ENFORCED; ghi riêng CHECK JSON_VALID ngầm; 95 cột `utf8mb4_nopad_bin`, email cùng policy canonical hóa Backend và nền `utf8mb4_unicode_ci` không lệch; mã enum sai case không qua CHECK.
- [ ] Xóa/sửa khóa cha có history bị RESTRICT; không có cascade ẩn.
- [ ] Các chu trình ở mục 7 ghi được theo thứ tự hợp lệ, rollback dữ liệu không để bản ghi công bố dở.
- [ ] Diễn tập partial failure trong M055/M057/M059, kể cả log chưa ghi kịp sau DDL; xác định chính xác nguồn cuối hoàn tất/phần dở từ metadata.
- [ ] Diễn tập down toàn bộ trên schema thử nghiệm theo source table/constraint; chạy up lại cho kết quả cấu trúc tương đương.
- [ ] Lưu bằng chứng test và các khác biệt metadata; chỉ báo PASS cho kiểm tra đã chạy.

Test schema được phép dùng fixture tối thiểu trong môi trường thử nghiệm khi nhiệm vụ triển khai cho phép; không đồng nghĩa tạo Seeder sản phẩm hoặc import Exercise dataset.

### 10.3. Kiểm thử nghiệp vụ khi có Backend tương ứng

Không lấy việc “migration chạy được” thay cho các test Q01–Q13. Dùng ca trong PROJECT_RULES mục 48–54.2 và THIET_KE_DATABASE mục 14, tối thiểu:

- Q01/Q02/Q10: catalog thay sau tạo đơn; webhook lệch thứ tự/lặp; hai link nhận tiền; một đơn chỉ cấp một kỳ.
- Q03: request giữ 1 quota, provider retry không thêm; trả quota đúng một lần; activation đã commit giữ nguyên.
- Q04: hai Admin phân công đồng thời khi Member chưa có PT; khoảng hữu hạn overlap và đổi PT đúng ranh giới.
- Q05: Member đọc history sau hết gói/đổi PT, gửi mới bị từ chối đúng quyền; PT mới không đọc thread cũ.
- Q06: xác nhận PT muộn không backdate, không mượn kỳ khác hoặc carry-over.
- Q07/Q08: một Active Plan/một lịch mỗi ngày; phiên HUY dùng lịch thay thế; Workout vẫn Start/Save/Complete khi không có active Membership.
- Q09: QR 90 giây, Proposal 24 giờ theo policy; đúng hạn bị từ chối, preview/retry không kéo dài.
- Q11/Q12: equipment là AND; không mất history khi khóa/ngừng account/catalog.
- Q13: PT Proposal không cần Chat/quota/Membership; assignment nguồn kết thúc trước confirm thì không Apply.
- Save Set tranh Complete, AI/PT cùng base version, Chat/AI/QR tranh activation và retry outbox đều giữ dữ liệu đúng.

## 11. Ma trận truy vết Q01–Q13

Đây là quyết định đã duyệt cần được bảo toàn; không mở lại để chọn nghiệp vụ khác.

| Quyết định | Nội dung phải giữ | Nguồn đối chiếu |
| --- | --- | --- |
| Q01 | Snapshot giá/gói/quyền lúc tạo đơn; giữ trong hạn đơn/link, không đổi theo catalog. | PROJECT_RULES 8.1; mục 4–5; B12–B15/B18. |
| Q02 | Xếp thứ tự kỳ theo Backend lần đầu xác nhận thanh toán hợp lệ dưới khóa; không chèn ngược. | PROJECT_RULES 8.1; mục 4.4/5; B15/B18. |
| Q03 | Một request AI hợp lệ = một lượt; retry provider không thêm; lỗi kỹ thuật trả quota, giữ activation. | PROJECT_RULES 34.1; mục 10.2; B18/B19/B46/B49. |
| Q04 | Member 0..1 PT hiệu lực; không overlap mọi PT, giữ lịch sử. | PROJECT_RULES 14.1; mục 7.1; B22. |
| Q05 | Member đọc Chat cũ; mất quyền/phân công không gửi; PT mới không đọc thread cũ. | PROJECT_RULES 33/33.1; mục 9; B22/B41/B42. |
| Q06 | Buổi PT xác nhận khi đúng kỳ còn hiệu lực/quota; quá hạn không backdate/mượn lượt. | PROJECT_RULES GYM 23; mục 7.2; B23. |
| Q07 | Một Active Plan, một lịch giữ slot/ngày; phiên HUY không restart lịch cũ; lịch thay thế/version mới. | PROJECT_RULES 22/24/25.1; mục 8.4–8.5; B33/B37/B38. |
| Q08 | Workout cơ bản không cần active Membership; vẫn bắt đầu từ lịch hợp lệ. | PROJECT_RULES 25; mục 8.6; B33/B37–B40. |
| Q09 | QR mặc định 90 giây, Proposal AI/PT 24 giờ; cấu hình tập trung, lưu hạn cụ thể. | PROJECT_RULES GYM 25/43.1; mục 6/10.4; B20/B50. |
| Q10 | Khoản bất thường CAN_DOI_SOAT; giữ dấu vết, một đơn một kỳ; không refund tự động. | PROJECT_RULES 8.1; mục 5.3; B14–B16/B18. |
| Q11 | Dụng cụ bài tập AND; không nhóm OR trong MVP. | PROJECT_RULES 19; mục 10.3/12; B25/B27/B28/B47. |
| Q12 | Không hard-delete history; account/catalog ngừng/khóa, không cascade; retention nâng cao sau MVP. | PROJECT_RULES 6.1/46.1; mục 3/12; quy ước từ điển và B51. |
| Q13 | PT Proposal chỉ cần phân công hợp lệ; không Chat/quota/Membership; revalidate khi confirm. | PROJECT_RULES GYM 24; mục 7.3/10.4; B22/B50. |

## 12. Tiêu chí bàn giao và trạng thái hiện tại

### 12.1. Sau nhiệm vụ triển khai migration tương lai

Chỉ nghiệm thu schema khi đã có:

- Danh sách file migration thực tế và thứ tự, manifest constraint/index/CHECK có thể đối chiếu.
- Báo cáo MariaDB/PHP/Laravel thực tế cùng môi trường test không chứa secret.
- Metadata khớp từ điển, test schema và rollback/up lại đã chạy, lỗi từng phần được xem xét.
- Danh sách rõ các invariant vẫn cần service/transaction; không tuyên bố toàn bộ nghiệp vụ hoàn thành.
- Không thay đổi business rule hoặc mở rộng 52 bảng ngoài review.
- Không tự tiếp tục viết module Authentication, Membership, payOS, Workout, Chat hoặc AI sau khi hoàn tất schema.

### 12.2. Kết quả review kế hoạch và baseline giữ nguyên

- Đã đọc toàn bộ PROJECT_RULES, đối chiếu 52 bảng và toàn bộ mục CHECK/mô tả liên quan trong từ điển hiện hành; chỉ cập nhật DBMS/collation/JSON notes, không đổi business rule hoặc schema logic.
- Manifest A chốt **83 DB CHECK dự kiến**, tên/cột/predicate/nguồn/FK/valid/invalid/ghi chú đầy đủ. B gồm **52 nhóm theo bảng**, bao phủ 98 UNIQUE, 144 FK, 5 generated; C gồm **26 invariant Backend/transaction**. B/C không đếm thành CHECK mới.
- Đã bổ sung **16 technical preflight cases** trong P0, bắt buộc trên MariaDB 10.4.32 trước khi viết migration thật; có nhánh default/NO ACTION nếu cần mà giữ CHECK và hành vi RESTRICT. **Chưa chạy probe, chưa có bằng chứng MariaDB PASS.**
- Giữ **60 file dự kiến = 52 create + 8 attach FK**, không thay Mxxx; 48 source-table units có manifest và quy tắc tiến độ/partial failure/down ngược.
- Chốt utf8mb4: default `utf8mb4_unicode_ci`, **95 cột kỹ thuật** `utf8mb4_nopad_bin`, email `utf8mb4_nopad_bin`; 0 FK chuỗi trong baseline hiện tại.
- Bảng baseline tại 2.1, thứ tự/đếm tại mục 4, 31 FK ghép tại 5.2, 5 generated tại 6.2 và **Q01–Q13 tại mục 11 không thay đổi**. MariaDB có thể hiển thị thêm CHECK JSON_VALID do kiểu JSON; không tính chúng vào 83 CHECK nghiệp vụ.

| Baseline cuối sau review | Số lượng giữ nguyên |
| --- | ---: |
| Bảng CORE | 52 |
| Cột | 575 |
| FK đơn | 113 |
| FK ghép | 31 |
| FK tổng | 144 |
| UNIQUE ngoài PK | 98 |
| Khai báo index truy vấn | 48 |
| Generated VIRTUAL | 5 |
| CHECK nghiệp vụ dự kiến | 83 |
| Migration dự kiến | 60 (52 + 8), chưa tạo |

### 12.3. Trạng thái và ranh giới phê duyệt

Không còn blocker **migration planning** trong phạm vi review này: preflight plan, CHECK manifest/coverage, partial failure và charset/collation đã rõ, baseline không lệch. Vì vậy kế hoạch được chuyển sang trạng thái dưới đây. Nếu preflight thực nghiệm về sau thất bại và không thể giữ nghĩa/schema bằng nhánh đã quy định, phải dừng implementation để review lại; không âm thầm bỏ ràng buộc.

**DATABASE DESIGN = APPROVED FOR MARIADB 10.4.32**

**MIGRATION PLAN = APPROVED FOR MARIADB TECHNICAL PREFLIGHT**

**TECHNICAL PREFLIGHT EXECUTION = CHƯA THỰC HIỆN**

**MIGRATION IMPLEMENTATION = CHƯA BẮT ĐẦU**

Lần review này chỉ đồng bộ DBMS mục tiêu và ghi nhận tương thích kỹ thuật trong các tài liệu được nêu ở phạm vi; không tạo file migration PHP hoặc file mới, không chạy Database/SQL/DDL/migration/probe, không cài package, không sửa BE/FE/Mobile. “APPROVED” ở đây chỉ là kế hoạch sẵn sàng cho MariaDB technical preflight; không phải P0 PASS, Database hoặc chức năng đã được triển khai/kiểm thử.
