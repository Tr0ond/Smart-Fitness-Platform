# P0 TECHNICAL PREFLIGHT REPORT

> Báo cáo được tạo từ lần chạy P0 MariaDB thực tế. Đây là technical preflight; không phải triển khai Database nghiệp vụ.

## 1. Environment

- **PHP executable:** `E:\Fitness\.tools\php\php.exe`
- **PHP:** `8.4.25` (CLI)
- **Laravel:** `Laravel Framework 13.29.0` (`composer.lock`, `BE/artisan --version`)
- **PHP extensions:** `PDO`, `pdo_mysql`, `mysqlnd`; `composer check-platform-reqs` PASS
- **MariaDB:** `10.4.32-MariaDB`
- **Version comment/vendor:** `mariadb.org binary distribution`
- **Host / Port:** `127.0.0.1:3306` (BE/.env; password không ghi trong report)
- **Engine:** `InnoDB`
- **sql_mode:** `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`
- **Probe SESSION sql_mode (configured):** `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION`
- **Probe SESSION sql_mode (server returned):** `STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION` (MariaDB canonicalized order; same mode set)
- **Charset:** `utf8mb4`
- **Collation:** `utf8mb4_general_ci`
- **Probe connection charset/collation:** `utf8mb4` / `utf8mb4_unicode_ci`
- **Timezone:** session `SYSTEM`, system `Asia/Bangkok`
- **Test schema:** `smart_fitness_preflight_20260828_201141_15b1f4` — tạo riêng, không phải `smart_fitness`, đã cleanup sau probe

Các truy vấn môi trường thực tế: `SELECT VERSION()`, `@@version_comment`, `@@sql_mode`, `@@character_set_server`, `@@collation_server`, `@@time_zone`, `@@system_time_zone`. Không dùng SQLite, MySQL 8.4 hoặc MariaDB phiên bản khác.

**Strict mode được chọn cho probe:** `STRICT_TRANS_TABLES` áp dụng strict cho bảng InnoDB; kết hợp `ERROR_FOR_DIVISION_BY_ZERO`, `NO_ZERO_DATE`, `NO_ZERO_IN_DATE` và `NO_ENGINE_SUBSTITUTION` để chặn coercion/ngày không hợp lệ và không tự thay engine. Chỉ set ở SESSION của connection probe; GLOBAL XAMPP mode không đổi.

## 2. Summary

| Case | Result | Note |
|---|---|---|
| P0-T01 | **PASS** | GLOBAL/server `sql_mode=NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION` được giữ nguyên. SESSION configured exact `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION`; MariaDB trả lại cùng tập mode theo thứ tự canonical `STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION`. Charset server `utf8mb4`, collation server `utf8mb4_general_ci`, timezone `SYSTEM`/`Asia/Bangkok` được ghi. |
| P0-T02 | **PASS** | PASS; explicit RESTRICT được chấp nhận, không cần fallback NO ACTION. |
| P0-T03 | **PASS** | PASS; tuple order/UNIQUE/FK được MariaDB kiểm tra đúng. |
| P0-T04 | **PASS** | `information_schema.COLUMNS` ghi đủ 5 `VIRTUAL GENERATED`, `IS_NULLABLE=YES`, expression đúng; direct generated writes bị reject 1906 dưới strict. |
| P0-T05 | **PASS** | PASS; MariaDB bỏ qua kiểm tra tuple khi có NULL, còn NOT NULL/CHECK cùng hàng chặn phần bắt buộc. |
| P0-T06 | **PASS** | PASS; self-reference và cycle kỹ thuật vẫn được MariaDB cho phép, đúng giới hạn đã nêu. |
| P0-T07 | **PASS** | PASS; không implement activation service. |
| P0-T08 | **PASS** | PASS; toàn bộ DDL/metadata tồn tại, counter/period checks enforce. |
| P0-T09 | **PASS** | PASS. |
| P0-T10 | **PASS** | PASS. |
| P0-T11 | **PASS** | PASS; generated code/date và all tested constraints đúng. |
| P0-T12 | **PASS** | PASS. |
| P0-T13 | **PASS** | PASS; test đã tạo đủ message IDs để chứng minh input tồn tại trước request. |
| P0-T14 | **PASS** | Metadata ghi `van_ban=utf8mb4_unicode_ci`; `ma_ky_thuat`, `thu_dien_tu`, `ma_uuid`, `ma_char=utf8mb4_nopad_bin`. Raw MariaDB binary vẫn nhận case/normalization khác nhau, chứng minh canonicalization phải chạy trước UNIQUE/lookup. |
| P0-T15 | **PASS** | JSON_VALID metadata ghi riêng 15 checks. |
| P0-T16 | **PASS** | PASS cho cả ba migration boundary; mỗi metadata sau failure chỉ có `_fk_01`, sau rollback rỗng, retry có `_fk_01_retry`. |

Machine-readable evidence is retained locally at `.tmp/preflight/results/p0_evidence.json`; it contains no password/secret. The DDL probe is `.tmp/preflight/p0_probe.php`; the full manifest probe is `.tmp/preflight/full_manifest.sql`.

## 3. Detailed Results

### P0-T01

**STATUS:** PASS

**OBJECTIVE:** Xác nhận đúng PHP/Laravel/PDO và MariaDB 10.4.32 thực tế trước mọi DDL.

**ENVIRONMENT:** Môi trường và schema test dùng chung cho toàn bộ case: PHP 8.4.25 portable, Laravel Framework 13.29.0, PDO MySQL/pdo_mysql, MariaDB 10.4.32, InnoDB, schema `smart_fitness_preflight_20260828_201141_15b1f4`. Probe không thay đổi GLOBAL sql_mode; toàn bộ DDL/data chạy với SESSION sql_mode strict `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION` và chỉ dùng dữ liệu giả lập.

**DDL/SCRIPT USED:** Không có DDL nghiệp vụ; truy vấn SELECT thông tin server và artisan/composer kiểm tra phiên bản. Script: `.tmp/preflight/p0_probe.php`.

**VALID TEST CASES:** PHP 8.4.25 tại `.tools/php/php.exe`; pdo_mysql/mysqlnd/PDO có mặt; artisan trả Laravel 13.29.0; server trả `10.4.32-MariaDB`, vendor MariaDB, engine mặc định InnoDB; SESSION sql_mode strict được set trước DDL.

**INVALID TEST CASES:** Không chạy trên SQLite/MySQL/MariaDB khác; không có database ứng dụng nào được dùng.

**EXPECTED:** Đúng target MariaDB 10.4.32 + InnoDB + PHP/Laravel theo composer.lock.

**ACTUAL:** PASS. GLOBAL/server `sql_mode=NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION` được giữ nguyên. SESSION configured exact `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION`; MariaDB trả lại cùng tập mode theo thứ tự canonical `STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION`. Charset server `utf8mb4`, collation server `utf8mb4_general_ci`, timezone `SYSTEM`/`Asia/Bangkok` được ghi.

**MYSQL ERROR / SQLSTATE:** Không có lỗi ngoài dự kiến.

**METADATA EVIDENCE:** Bằng chứng nguồn: `.tmp/preflight/results/p0_evidence.json`, trường `environment`; `composer.lock` và `BE/artisan --version`.

**CLEANUP RESULT:** Schema test được giữ đến hết case để đọc metadata; sau đó `DROP DATABASE` chính schema do probe tạo trả `ok=true`.

**SCHEMA IMPACT:** Chỉ schema test; không có bảng nghiệp vụ nào trong database project được tạo/sửa.

**DATABASE PROTECTION:** Không dùng `migrate:fresh`, `TRUNCATE`, `DROP smart_fitness`, `foreign_key_checks=0` hoặc `check_constraint_checks=OFF`; không dùng dữ liệu thật.

**BACKEND INVARIANTS STILL REQUIRED:** Laravel connection chính thức phải set cùng tập SESSION sql_mode strict trước migration/request; GLOBAL XAMPP mode không bị sửa trong P0.

### P0-T02

**STATUS:** PASS

**OBJECTIVE:** CHECK + simple FK + `ON DELETE RESTRICT`/`ON UPDATE RESTRICT` và enforcement.

**ENVIRONMENT:** Môi trường và schema test dùng chung cho toàn bộ case: PHP 8.4.25 portable, Laravel Framework 13.29.0, PDO MySQL/pdo_mysql, MariaDB 10.4.32, InnoDB, schema `smart_fitness_preflight_20260828_201141_15b1f4`. Probe không thay đổi GLOBAL sql_mode; toàn bộ DDL/data chạy với SESSION sql_mode strict `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION` và chỉ dùng dữ liệu giả lập.

**DDL/SCRIPT USED:** Tạo `p0_t02_cha`/`p0_t02_con`; CHECK `gia_tri > 0` trước, sau đó attach FK named `khoa_ngoai_p0_t02` với explicit RESTRICT. Script: `.tmp/preflight/p0_probe.php`.

**VALID TEST CASES:** Insert parent/child hợp lệ thành công.

**INVALID TEST CASES:** CHECK insert/update bị mã 4025; giá trị chuỗi `abc` vào INT bị strict type coercion reject; FK cha không tồn tại bị 1452; DELETE/UPDATE khóa cha đang được tham chiếu bị 1451.

**EXPECTED:** Constraint được enforce và RESTRICT chặn thay đổi parent.

**ACTUAL:** PASS; explicit RESTRICT được chấp nhận, không cần fallback NO ACTION.

**MYSQL ERROR / SQLSTATE (expected negative cases):** 4025/23000, 1366/22007, 1452/23000, 1451/23000.

**METADATA EVIDENCE:** SHOW CREATE xác nhận InnoDB, named CHECK và named FK; lỗi đều SQLSTATE 23000.

**CLEANUP RESULT:** Schema test được giữ đến hết case để đọc metadata; sau đó `DROP DATABASE` chính schema do probe tạo trả `ok=true`.

**SCHEMA IMPACT:** Chỉ schema test; không có bảng nghiệp vụ nào trong database project được tạo/sửa.

**DATABASE PROTECTION:** Không dùng `migrate:fresh`, `TRUNCATE`, `DROP smart_fitness`, `foreign_key_checks=0` hoặc `check_constraint_checks=OFF`; không dùng dữ liệu thật.

**BACKEND INVARIANTS STILL REQUIRED:** Không dùng `foreign_key_checks=0`/`check_constraint_checks=OFF`; transaction vẫn chịu trách nhiệm thứ tự thao tác.

### P0-T03

**STATUS:** PASS

**OBJECTIVE:** Composite FK dựa trên parent UNIQUE tuple và CHECK liên quan.

**ENVIRONMENT:** Môi trường và schema test dùng chung cho toàn bộ case: PHP 8.4.25 portable, Laravel Framework 13.29.0, PDO MySQL/pdo_mysql, MariaDB 10.4.32, InnoDB, schema `smart_fitness_preflight_20260828_201141_15b1f4`. Probe không thay đổi GLOBAL sql_mode; toàn bộ DDL/data chạy với SESSION sql_mode strict `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION` và chỉ dùng dữ liệu giả lập.

**DDL/SCRIPT USED:** Tạo parent UNIQUE `(id, hoi_vien_id)`, child CHECK yêu cầu tuple toàn NULL hoặc toàn giá trị, composite FK named `khoa_ngoai_ghep_p0_t03`. Script: `.tmp/preflight/p0_probe.php`.

**VALID TEST CASES:** Tuple đúng và toàn bộ optional NULL được nhận.

**INVALID TEST CASES:** Hai ID tồn tại nhưng ghép sai owner bị 1452; một component NULL bị CHECK 4025; parent DELETE/UPDATE bị 1451.

**EXPECTED:** Ownership tuple được bảo vệ, parent key không thể đổi/xóa khi có child.

**ACTUAL:** PASS; tuple order/UNIQUE/FK được MariaDB kiểm tra đúng.

**MYSQL ERROR / SQLSTATE (expected negative cases):** 1452/23000, 4025/23000, 1451/23000.

**METADATA EVIDENCE:** SHOW CREATE và `information_schema.KEY_COLUMN_USAGE` xác nhận composite columns đúng thứ tự.

**CLEANUP RESULT:** Schema test được giữ đến hết case để đọc metadata; sau đó `DROP DATABASE` chính schema do probe tạo trả `ok=true`.

**SCHEMA IMPACT:** Chỉ schema test; không có bảng nghiệp vụ nào trong database project được tạo/sửa.

**DATABASE PROTECTION:** Không dùng `migrate:fresh`, `TRUNCATE`, `DROP smart_fitness`, `foreign_key_checks=0` hoặc `check_constraint_checks=OFF`; không dùng dữ liệu thật.

**BACKEND INVARIANTS STILL REQUIRED:** Các quy tắc liên hàng ngoài tuple (ví dụ nguồn phải thuộc đúng chuỗi) vẫn phải kiểm tra trong transaction.

### P0-T04

**STATUS:** PASS

**OBJECTIVE:** Năm generated column VIRTUAL, nullable, UNIQUE và không cho client áp giá trị tùy ý.

**ENVIRONMENT:** Môi trường và schema test dùng chung cho toàn bộ case: PHP 8.4.25 portable, Laravel Framework 13.29.0, PDO MySQL/pdo_mysql, MariaDB 10.4.32, InnoDB, schema `smart_fitness_preflight_20260828_201141_15b1f4`. Probe không thay đổi GLOBAL sql_mode; toàn bộ DDL/data chạy với SESSION sql_mode strict `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION` và chỉ dùng dữ liệu giả lập.

**DDL/SCRIPT USED:** Probe bốn bảng đại diện cho đúng năm biểu thức B17/B22/B33/B37, mỗi cột `AS (CASE ...) VIRTUAL` và UNIQUE như manifest. Script: `.tmp/preflight/p0_probe.php`.

**VALID TEST CASES:** Nhiều NULL lịch sử; duplicate non-NULL bị 1062; UPDATE trạng thái làm generated value đổi và giải phóng/giữ slot đúng predicate.

**INVALID TEST CASES:** Duplicate generated scope bị 1062. Dưới SESSION strict, cả INSERT và UPDATE trực tiếp với giá trị `999`/`bogus` bị MariaDB từ chối bằng 1906; row hợp lệ được chuẩn bị riêng và vẫn giữ generated value 20.

**EXPECTED:** Metadata phải là VIRTUAL/nullable, expression đúng, UNIQUE giữ scope và không có NOW()/FK vào cột sinh.

**ACTUAL:** PASS. `information_schema.COLUMNS` ghi đủ 5 `VIRTUAL GENERATED`, `IS_NULLABLE=YES`, expression đúng; direct generated writes bị reject 1906 dưới strict.

**MYSQL ERROR / SQLSTATE (expected negative cases):** 1062/23000, 1906/HY000.

**METADATA EVIDENCE:** SHOW CREATE xác nhận: `hoi_vien_chua_ket_thuc_id`, `hoi_vien_dang_phan_cong_id`, `hoi_vien_dang_su_dung_id`, `ma_buoi_con_hieu_luc`, `ngay_tap_con_hieu_luc`; evidence có `GENERATION_EXPRESSION` exact.

**CLEANUP RESULT:** Schema test được giữ đến hết case để đọc metadata; sau đó `DROP DATABASE` chính schema do probe tạo trả `ok=true`.

**SCHEMA IMPACT:** Chỉ schema test; không có bảng nghiệp vụ nào trong database project được tạo/sửa.

**DATABASE PROTECTION:** Không dùng `migrate:fresh`, `TRUNCATE`, `DROP smart_fitness`, `foreign_key_checks=0` hoặc `check_constraint_checks=OFF`; không dùng dữ liệu thật.

**BACKEND INVARIANTS STILL REQUIRED:** Không nhận generated value từ request DTO; luôn đọc giá trị server sinh. MariaDB warning về CHAR/padding được ghi ở T15.

### P0-T05

**STATUS:** PASS

**OBJECTIVE:** Hành vi nullable composite FK của các pattern B17/B18/B37/B46.

**ENVIRONMENT:** Môi trường và schema test dùng chung cho toàn bộ case: PHP 8.4.25 portable, Laravel Framework 13.29.0, PDO MySQL/pdo_mysql, MariaDB 10.4.32, InnoDB, schema `smart_fitness_preflight_20260828_201141_15b1f4`. Probe không thay đổi GLOBAL sql_mode; toàn bộ DDL/data chạy với SESSION sql_mode strict `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION` và chỉ dùng dữ liệu giả lập.

**DDL/SCRIPT USED:** Tạo parent/member/period/usage và bốn child pattern, giữ cả FK đơn lẫn composite FK. Script: `.tmp/preflight/p0_probe.php`.

**VALID TEST CASES:** Tuple đầy đủ đúng; toàn bộ phần optional NULL; B17/B18 có một component NULL được nhận theo semantics MariaDB.

**INVALID TEST CASES:** Sai ownership đầy đủ bị 1452; B37 NOT NULL partial bị 1048; các tuple sai không được đánh dấu là đã bảo vệ khi có NULL.

**EXPECTED:** Ghi rõ composite FK bỏ kiểm tra parent khi bất kỳ component nào NULL.

**ACTUAL:** PASS; MariaDB bỏ qua kiểm tra tuple khi có NULL, còn NOT NULL/CHECK cùng hàng chặn phần bắt buộc.

**MYSQL ERROR / SQLSTATE (expected negative cases):** 1452/23000, 1048/23000.

**METADATA EVIDENCE:** SHOW CREATE/FK metadata của `p0_t05_b17`, `_b18`, `_b37`, `_b46`; SQLSTATE 23000 cho rejection.

**CLEANUP RESULT:** Schema test được giữ đến hết case để đọc metadata; sau đó `DROP DATABASE` chính schema do probe tạo trả `ok=true`.

**SCHEMA IMPACT:** Chỉ schema test; không có bảng nghiệp vụ nào trong database project được tạo/sửa.

**DATABASE PROTECTION:** Không dùng `migrate:fresh`, `TRUNCATE`, `DROP smart_fitness`, `foreign_key_checks=0` hoặc `check_constraint_checks=OFF`; không dùng dữ liệu thật.

**BACKEND INVARIANTS STILL REQUIRED:** Ownership/period invariant khi tuple nullable phải được kiểm tra bởi Backend transaction; không tuyên bố FK bảo vệ quá mức.

### P0-T06

**STATUS:** PASS

**OBJECTIVE:** Self-referencing B34 previous version (đơn + ghép cùng Plan) và B37 replacement.

**ENVIRONMENT:** Môi trường và schema test dùng chung cho toàn bộ case: PHP 8.4.25 portable, Laravel Framework 13.29.0, PDO MySQL/pdo_mysql, MariaDB 10.4.32, InnoDB, schema `smart_fitness_preflight_20260828_201141_15b1f4`. Probe không thay đổi GLOBAL sql_mode; toàn bộ DDL/data chạy với SESSION sql_mode strict `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION` và chỉ dùng dữ liệu giả lập.

**DDL/SCRIPT USED:** Tạo `p0_t06_version` với simple/self composite FK; `p0_t06_schedule` với self FK và UNIQUE replacement. Script: `.tmp/preflight/p0_probe.php`.

**VALID TEST CASES:** NULL khởi tạo, previous đúng Plan và replacement hợp lệ.

**INVALID TEST CASES:** Previous ID không tồn tại hoặc khác Plan bị 1452; delete parent đang referenced bị 1451; hai replacement cùng target bị 1062.

**EXPECTED:** Self FK attach được, giữ đúng Plan và restrict parent.

**ACTUAL:** PASS; self-reference và cycle kỹ thuật vẫn được MariaDB cho phép, đúng giới hạn đã nêu.

**MYSQL ERROR / SQLSTATE (expected negative cases):** 1452/23000, 1451/23000, 1062/23000.

**METADATA EVIDENCE:** SHOW CREATE cho thấy cả `fk_p0_t06_prev` và `fk_p0_t06_prev_same`, cùng `uq_p0_t06_repl`.

**CLEANUP RESULT:** Schema test được giữ đến hết case để đọc metadata; sau đó `DROP DATABASE` chính schema do probe tạo trả `ok=true`.

**SCHEMA IMPACT:** Chỉ schema test; không có bảng nghiệp vụ nào trong database project được tạo/sửa.

**DATABASE PROTECTION:** Không dùng `migrate:fresh`, `TRUNCATE`, `DROP smart_fitness`, `foreign_key_checks=0` hoặc `check_constraint_checks=OFF`; không dùng dữ liệu thật.

**BACKEND INVARIANTS STILL REQUIRED:** Cấm self/cycle, kiểm tra replacement cùng version/trạng thái và thứ tự cập nhật trong transaction.

### P0-T07

**STATUS:** PASS

**OBJECTIVE:** B17 `dang_ky_goi_tap`: CHECK, generated open-chain UNIQUE, FK đơn/ghép.

**ENVIRONMENT:** Môi trường và schema test dùng chung cho toàn bộ case: PHP 8.4.25 portable, Laravel Framework 13.29.0, PDO MySQL/pdo_mysql, MariaDB 10.4.32, InnoDB, schema `smart_fitness_preflight_20260828_201141_15b1f4`. Probe không thay đổi GLOBAL sql_mode; toàn bộ DDL/data chạy với SESSION sql_mode strict `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION` và chỉ dùng dữ liệu giả lập.

**DDL/SCRIPT USED:** Schema gần B17 gồm hai CHECK, generated `hoi_vien_chua_ket_thuc_id`, UNIQUE và FK member/branch/usage + owner tuple. Script: `.tmp/preflight/p0_probe.php`.

**VALID TEST CASES:** `CHO_KICH_HOAT` NULL/NULL; chuyển `DANG_HOAT_DONG` có mốc/nguồn; `HUY`/`HET_HAN` đóng chuỗi; tạo chuỗi mới sau khi đóng.

**INVALID TEST CASES:** Hai chuỗi mở cùng member bị 1062; source sai member bị 1452; cặp lệch NULL/trạng thái sai bị 4025.

**EXPECTED:** Chỉ một chuỗi chưa kết thúc mỗi member và activation pair hợp lệ.

**ACTUAL:** PASS; không implement activation service.

**MYSQL ERROR / SQLSTATE (expected negative cases):** 1062/23000, 1452/23000, 4025/23000.

**METADATA EVIDENCE:** SHOW CREATE `p0_t07_chain` xác nhận CHECK/generation/UNIQUE/FK names.

**CLEANUP RESULT:** Schema test được giữ đến hết case để đọc metadata; sau đó `DROP DATABASE` chính schema do probe tạo trả `ok=true`.

**SCHEMA IMPACT:** Chỉ schema test; không có bảng nghiệp vụ nào trong database project được tạo/sửa.

**DATABASE PROTECTION:** Không dùng `migrate:fresh`, `TRUNCATE`, `DROP smart_fitness`, `foreign_key_checks=0` hoặc `check_constraint_checks=OFF`; không dùng dữ liệu thật.

**BACKEND INVARIANTS STILL REQUIRED:** Nguồn/mốc phải thuộc kỳ đầu của chính chuỗi (C10), không suy ra chỉ từ owner FK.

### P0-T08

**STATUS:** PASS

**OBJECTIVE:** B18 `ky_han_hoi_vien`: đủ 8 CHECK, 4 FK đơn, 3 FK ghép, 4 UNIQUE.

**ENVIRONMENT:** Môi trường và schema test dùng chung cho toàn bộ case: PHP 8.4.25 portable, Laravel Framework 13.29.0, PDO MySQL/pdo_mysql, MariaDB 10.4.32, InnoDB, schema `smart_fitness_preflight_20260828_201141_15b1f4`. Probe không thay đổi GLOBAL sql_mode; toàn bộ DDL/data chạy với SESSION sql_mode strict `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION` và chỉ dùng dữ liệu giả lập.

**DDL/SCRIPT USED:** Schema gần B18 giữ cột ngày DATETIME(6), quota PT/AI, quyền Chat độc lập, FK/UNIQUE đúng manifest. Script: `.tmp/preflight/p0_probe.php`.

**VALID TEST CASES:** Counter hợp lệ, PT used 0/total, AI limited/unlimited, `NULL/NULL`, ngày qua tháng với microseconds và `DATE_ADD` đúng.

**INVALID TEST CASES:** Lệch ngày/giờ, `not-a-date` vào DATETIME(6), used > total, AI disabled nhưng counter >0, vượt limit, owner/FK sai bị 1048/1292/4025/1452 tùy nhánh.

**EXPECTED:** Không lọt UNKNOWN trong 8 CHECK; tuple owner/payment/chain đúng.

**ACTUAL:** PASS; toàn bộ DDL/metadata tồn tại, counter/period checks enforce.

**MYSQL ERROR / SQLSTATE (expected negative cases):** 1048/23000, 1452/23000, 4025/23000, 1292/22007.

**METADATA EVIDENCE:** SHOW CREATE `p0_t08_period` có `ck_p0_t08_01`…`08`, 4 UNIQUE và các FK đơn/ghép.

**CLEANUP RESULT:** Schema test được giữ đến hết case để đọc metadata; sau đó `DROP DATABASE` chính schema do probe tạo trả `ok=true`.

**SCHEMA IMPACT:** Chỉ schema test; không có bảng nghiệp vụ nào trong database project được tạo/sửa.

**DATABASE PROTECTION:** Không dùng `migrate:fresh`, `TRUNCATE`, `DROP smart_fitness`, `foreign_key_checks=0` hoặc `check_constraint_checks=OFF`; không dùng dữ liệu thật.

**BACKEND INVARIANTS STILL REQUIRED:** Đối soát counter với ledger, nguồn payment và ngày theo timezone chi nhánh vẫn thuộc Backend/C07/C09/C10/C22.

### P0-T09

**STATUS:** PASS

**OBJECTIVE:** B33 `ke_hoach_tap`: active generated UNIQUE, archived plan và current version composite FK.

**ENVIRONMENT:** Môi trường và schema test dùng chung cho toàn bộ case: PHP 8.4.25 portable, Laravel Framework 13.29.0, PDO MySQL/pdo_mysql, MariaDB 10.4.32, InnoDB, schema `smart_fitness_preflight_20260828_201141_15b1f4`. Probe không thay đổi GLOBAL sql_mode; toàn bộ DDL/data chạy với SESSION sql_mode strict `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION` và chỉ dùng dữ liệu giả lập.

**DDL/SCRIPT USED:** Tạo plan status CHECK, generated `hoi_vien_dang_su_dung_id`, UNIQUE active/member và FK current version đơn + ghép. Script: `.tmp/preflight/p0_probe.php`.

**VALID TEST CASES:** Plan `DANG_SU_DUNG`, nhiều `LUU_TRU`, current version NULL ban đầu, attach version cùng Plan.

**INVALID TEST CASES:** Hai active cùng member bị 1062; version Plan khác bị 1452.

**EXPECTED:** Một active Plan/member; FK kép giữ đúng Plan.

**ACTUAL:** PASS.

**MYSQL ERROR / SQLSTATE (expected negative cases):** 1062/23000, 1452/23000.

**METADATA EVIDENCE:** SHOW CREATE `p0_t09_plan` xác nhận generated/UNIQUE/CHECK/FK.

**CLEANUP RESULT:** Schema test được giữ đến hết case để đọc metadata; sau đó `DROP DATABASE` chính schema do probe tạo trả `ok=true`.

**SCHEMA IMPACT:** Chỉ schema test; không có bảng nghiệp vụ nào trong database project được tạo/sửa.

**DATABASE PROTECTION:** Không dùng `migrate:fresh`, `TRUNCATE`, `DROP smart_fitness`, `foreign_key_checks=0` hoặc `check_constraint_checks=OFF`; không dùng dữ liệu thật.

**BACKEND INVARIANTS STILL REQUIRED:** Không công bố Plan khi pointer NULL; publish/version snapshot cần transaction/invariant C15.

### P0-T10

**STATUS:** PASS

**OBJECTIVE:** B34 `phien_ban_ke_hoach_tap`: version number/source/proposal uniqueness và previous cùng Plan.

**ENVIRONMENT:** Môi trường và schema test dùng chung cho toàn bộ case: PHP 8.4.25 portable, Laravel Framework 13.29.0, PDO MySQL/pdo_mysql, MariaDB 10.4.32, InnoDB, schema `smart_fitness_preflight_20260828_201141_15b1f4`. Probe không thay đổi GLOBAL sql_mode; toàn bộ DDL/data chạy với SESSION sql_mode strict `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION` và chỉ dùng dữ liệu giả lập.

**DDL/SCRIPT USED:** Tạo CHECK `nguon_tao`, `so_phien_ban>=1`, UNIQUE Plan/version, UNIQUE proposal, simple/self composite FK. Script: `.tmp/preflight/p0_probe.php`.

**VALID TEST CASES:** Version 1 và version 2 previous đúng Plan.

**INVALID TEST CASES:** Trùng Plan/version hoặc Proposal bị 1062; source/number sai bị 4025; previous khác Plan/nonexistent bị 1452.

**EXPECTED:** Version/proposal unique và reference đúng Plan.

**ACTUAL:** PASS.

**MYSQL ERROR / SQLSTATE (expected negative cases):** 1062/23000, 4025/23000, 1452/23000.

**METADATA EVIDENCE:** SHOW CREATE `p0_t10_version` xác nhận named CHECK/UNIQUE/FK.

**CLEANUP RESULT:** Schema test được giữ đến hết case để đọc metadata; sau đó `DROP DATABASE` chính schema do probe tạo trả `ok=true`.

**SCHEMA IMPACT:** Chỉ schema test; không có bảng nghiệp vụ nào trong database project được tạo/sửa.

**DATABASE PROTECTION:** Không dùng `migrate:fresh`, `TRUNCATE`, `DROP smart_fitness`, `foreign_key_checks=0` hoặc `check_constraint_checks=OFF`; không dùng dữ liệu thật.

**BACKEND INVARIANTS STILL REQUIRED:** Snapshot bất biến, tăng version và apply proposal vẫn phải được service/transaction bảo đảm.

### P0-T11

**STATUS:** PASS

**OBJECTIVE:** B37 `buoi_tap_du_kien`: giờ, generated slot, replacement, 5 FK đơn/3 FK ghép và UNIQUE.

**ENVIRONMENT:** Môi trường và schema test dùng chung cho toàn bộ case: PHP 8.4.25 portable, Laravel Framework 13.29.0, PDO MySQL/pdo_mysql, MariaDB 10.4.32, InnoDB, schema `smart_fitness_preflight_20260828_201141_15b1f4`. Probe không thay đổi GLOBAL sql_mode; toàn bộ DDL/data chạy với SESSION sql_mode strict `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION` và chỉ dùng dữ liệu giả lập.

**DDL/SCRIPT USED:** Schema gần B37 với hai generated VIRTUAL, time CHECK, 5 UNIQUE, 5 simple FK, 3 composite FK và self replacement. Script: `.tmp/preflight/p0_probe.php`.

**VALID TEST CASES:** Cả giờ NULL hoặc start<end; CHUA_TAP/DANG_TAP/HOAN_THANH/BO_QUA giữ slot; HUY/DA_THAY_THE trả generated NULL và cho replacement.

**INVALID TEST CASES:** Một giờ hoặc start>=end bị 4025; cùng member/ngày hoặc mã logic/replacement trùng bị 1062; owner/version/day sai bị FK chặn.

**EXPECTED:** Slot logic hoạt động đúng, HOAN_THANH/BO_QUA vẫn chiếm slot.

**ACTUAL:** PASS; generated code/date và all tested constraints đúng.

**MYSQL ERROR / SQLSTATE (expected negative cases):** 1062/23000, 4025/23000, 1452/23000.

**METADATA EVIDENCE:** SHOW CREATE `p0_t11_schedule` và generated metadata; errors 4025/1062/1452 được lưu.

**CLEANUP RESULT:** Schema test được giữ đến hết case để đọc metadata; sau đó `DROP DATABASE` chính schema do probe tạo trả `ok=true`.

**SCHEMA IMPACT:** Chỉ schema test; không có bảng nghiệp vụ nào trong database project được tạo/sửa.

**DATABASE PROTECTION:** Không dùng `migrate:fresh`, `TRUNCATE`, `DROP smart_fitness`, `foreign_key_checks=0` hoặc `check_constraint_checks=OFF`; không dùng dữ liệu thật.

**BACKEND INVARIANTS STILL REQUIRED:** Không cho self replacement, kiểm tra replacement ở version mới và giữ workout history bất biến.

### P0-T12

**STATUS:** PASS

**OBJECTIVE:** B45 `tin_nhan_tro_ly`: cycle input → request → response, sequence/source/client UNIQUE và FK conversation.

**ENVIRONMENT:** Môi trường và schema test dùng chung cho toàn bộ case: PHP 8.4.25 portable, Laravel Framework 13.29.0, PDO MySQL/pdo_mysql, MariaDB 10.4.32, InnoDB, schema `smart_fitness_preflight_20260828_201141_15b1f4`. Probe không thay đổi GLOBAL sql_mode; toàn bộ DDL/data chạy với SESSION sql_mode strict `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION` và chỉ dùng dữ liệu giả lập.

**DDL/SCRIPT USED:** Tạo conversation, request trước FK input; message có CHECK source/sequence, UNIQUE sequence/client và FK request đơn + ghép; attach request input FK sau. Script: `.tmp/preflight/p0_probe.php`.

**VALID TEST CASES:** Tạo input chưa gắn request, tạo request, link input rồi tạo response.

**INVALID TEST CASES:** Sequence/client trùng bị 1062; source lạ bị 4025; conversation sai bị 1452.

**EXPECTED:** Vòng B45/B46 tạo được mà không deferred FK/tắt kiểm tra.

**ACTUAL:** PASS.

**MYSQL ERROR / SQLSTATE (expected negative cases):** 4025/23000, 1062/23000, 1452/23000.

**METADATA EVIDENCE:** SHOW CREATE `p0_t12_message`/`p0_t12_request` xác nhận sequence/client/conversation composite.

**CLEANUP RESULT:** Schema test được giữ đến hết case để đọc metadata; sau đó `DROP DATABASE` chính schema do probe tạo trả `ok=true`.

**SCHEMA IMPACT:** Chỉ schema test; không có bảng nghiệp vụ nào trong database project được tạo/sửa.

**DATABASE PROTECTION:** Không dùng `migrate:fresh`, `TRUNCATE`, `DROP smart_fitness`, `foreign_key_checks=0` hoặc `check_constraint_checks=OFF`; không dùng dữ liệu thật.

**BACKEND INVARIANTS STILL REQUIRED:** Idempotency client message và thứ tự sequence phải được xử lý trong transaction/service.

### P0-T13

**STATUS:** PASS

**OBJECTIVE:** B46 `yeu_cau_tro_ly`: 4 CHECK, 5 FK đơn, 4 FK ghép và quota states.

**ENVIRONMENT:** Môi trường và schema test dùng chung cho toàn bộ case: PHP 8.4.25 portable, Laravel Framework 13.29.0, PDO MySQL/pdo_mysql, MariaDB 10.4.32, InnoDB, schema `smart_fitness_preflight_20260828_201141_15b1f4`. Probe không thay đổi GLOBAL sql_mode; toàn bộ DDL/data chạy với SESSION sql_mode strict `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION` và chỉ dùng dữ liệu giả lập.

**DDL/SCRIPT USED:** Schema request có 4 CHECK (`loai_yeu_cau`, `trang_thai`, quota status/pair), UNIQUE input/usage và đủ FK conversation/member/period/usage + owner tuples. Script: `.tmp/preflight/p0_probe.php`.

**VALID TEST CASES:** `KHONG_AP_DUNG` với period/usage NULL; `GIU_CHO`, `DA_TINH`, `DA_TRA` với cặp non-NULL đúng owner/kỳ; các enum hợp lệ của loại/trạng thái yêu cầu.

**INVALID TEST CASES:** Enum/pair/status sai bị 4025; member/conversation/input mismatch bị 1452; nullable interaction không bỏ qua CHECK.

**EXPECTED:** Ba trạng thái quota phải có cả period và usage; KHONG_AP_DUNG phải NULL/NULL.

**ACTUAL:** PASS; test đã tạo đủ message IDs để chứng minh input tồn tại trước request.

**MYSQL ERROR / SQLSTATE (expected negative cases):** 4025/23000, 1452/23000.

**METADATA EVIDENCE:** SHOW CREATE `p0_t13_request` xác nhận đủ `ck_p0_t13_type`, `ck_p0_t13_state`, `ck_p0_t13_status`, `ck_p0_t13_pair`; FK/UNIQUE names và SQLSTATE được lưu trong evidence.

**CLEANUP RESULT:** Schema test được giữ đến hết case để đọc metadata; sau đó `DROP DATABASE` chính schema do probe tạo trả `ok=true`.

**SCHEMA IMPACT:** Chỉ schema test; không có bảng nghiệp vụ nào trong database project được tạo/sửa.

**DATABASE PROTECTION:** Không dùng `migrate:fresh`, `TRUNCATE`, `DROP smart_fitness`, `foreign_key_checks=0` hoặc `check_constraint_checks=OFF`; không dùng dữ liệu thật.

**BACKEND INVARIANTS STILL REQUIRED:** Quota reservation/settlement và ledger/counter đối soát vẫn là invariant C22, không do FK tự đảm bảo.

### P0-T14

**STATUS:** PASS

**OBJECTIVE:** Charset/collation default và override kỹ thuật/email trên MariaDB.

**ENVIRONMENT:** Môi trường và schema test dùng chung cho toàn bộ case: PHP 8.4.25 portable, Laravel Framework 13.29.0, PDO MySQL/pdo_mysql, MariaDB 10.4.32, InnoDB, schema `smart_fitness_preflight_20260828_201141_15b1f4`. Probe không thay đổi GLOBAL sql_mode; toàn bộ DDL/data chạy với SESSION sql_mode strict `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION` và chỉ dùng dữ liệu giả lập.

**DDL/SCRIPT USED:** Tạo bảng mẫu với default `utf8mb4_unicode_ci`, kỹ thuật/email/UUID/CHAR `utf8mb4_nopad_bin` và UNIQUE. Script: `.tmp/preflight/p0_probe.php`.

**VALID TEST CASES:** Tiếng Việt có dấu, mã khác case và trailing VARCHAR, UUID/hash/reference, CHAR(36)/(3), `Member@example.com`, email khác dấu và biểu diễn Unicode phân rã được thử trên cột binary.

**INVALID TEST CASES:** Email exact duplicate bị UNIQUE (1062); mã technical phân biệt case/trailing theo `nopad_bin`. Hai email chỉ xung đột sau khi Backend canonicalize.

**EXPECTED:** Metadata khớp policy; email semantics case-insensitive phải đạt nhờ trim + NFC + lowercase ở Backend, accent-sensitive ở DB.

**ACTUAL:** PASS. Metadata ghi `van_ban=utf8mb4_unicode_ci`; `ma_ky_thuat`, `thu_dien_tu`, `ma_uuid`, `ma_char=utf8mb4_nopad_bin`. Raw MariaDB binary vẫn nhận case/normalization khác nhau, chứng minh canonicalization phải chạy trước UNIQUE/lookup.

**MYSQL ERROR / SQLSTATE (expected negative cases):** 1062/23000.

**METADATA EVIDENCE:** Evidence có từng cột trong `information_schema.COLUMNS`; xác nhận không có FK chuỗi trong full manifest.

**CLEANUP RESULT:** Schema test được giữ đến hết case để đọc metadata; sau đó `DROP DATABASE` chính schema do probe tạo trả `ok=true`.

**SCHEMA IMPACT:** Chỉ schema test; không có bảng nghiệp vụ nào trong database project được tạo/sửa.

**DATABASE PROTECTION:** Không dùng `migrate:fresh`, `TRUNCATE`, `DROP smart_fitness`, `foreign_key_checks=0` hoặc `check_constraint_checks=OFF`; không dùng dữ liệu thật.

**BACKEND INVARIANTS STILL REQUIRED:** Mọi writer/lookup phải canonicalize trim + NFC + lowercase toàn email trước UNIQUE; không đổi CHAR thành BINARY.

### P0-T15

**STATUS:** PASS

**OBJECTIVE:** Laravel 13.29.0/MariaDB DDL compatibility và full official manifest.

**ENVIRONMENT:** Môi trường và schema test dùng chung cho toàn bộ case: PHP 8.4.25 portable, Laravel Framework 13.29.0, PDO MySQL/pdo_mysql, MariaDB 10.4.32, InnoDB, schema `smart_fitness_preflight_20260828_201141_15b1f4`. Probe không thay đổi GLOBAL sql_mode; toàn bộ DDL/data chạy với SESSION sql_mode strict `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION` và chỉ dùng dữ liệu giả lập.

**DDL/SCRIPT USED:** Nạp `.tmp/preflight/full_manifest.sql` sinh từ TU_DIEN_DU_LIEU/MIGRATION_PLAN vào cùng schema test; inspect SHOW CREATE và information_schema. Script: `.tmp/preflight/p0_probe.php`.

**VALID TEST CASES:** 52 CORE tables, 575 columns, 144 FK (113 simple + 31 composite), 98 UNIQUE, 83 named business CHECK, 5 generated, 48 query indexes được tạo.

**INVALID TEST CASES:** Không có DDL error; full manifest errors = 0. MariaDB phát warning 1901/1105 lặp lại khi generated CHAR `ma_buoi_con_hieu_luc` được ALTER FK, nhưng metadata và hành vi T04/T11 vẫn đúng.

**EXPECTED:** Counts exact baseline; JSON_VALID implicit checks tách khỏi 83 business CHECK.

**ACTUAL:** PASS với physical warning đã ghi, không thay đổi schema để né warning. JSON_VALID metadata ghi riêng 15 checks.

**MYSQL ERROR / SQLSTATE:** Không có lỗi ngoài dự kiến.

**METADATA EVIDENCE:** Actual counts: `tables=52`, `columns=575`, `fk_constraints=144`, `unique_constraints=98`, `business_checks=83`, `generated_columns=5`, `query_indexes=48`; SHOW CREATE mẫu `chi_nhanh`, `ky_han_hoi_vien`, `buoi_tap_du_kien`.

**CLEANUP RESULT:** Schema test được giữ đến hết case để đọc metadata; sau đó `DROP DATABASE` chính schema do probe tạo trả `ok=true`.

**SCHEMA IMPACT:** Chỉ schema test; không có bảng nghiệp vụ nào trong database project được tạo/sửa.

**DATABASE PROTECTION:** Không dùng `migrate:fresh`, `TRUNCATE`, `DROP smart_fitness`, `foreign_key_checks=0` hoặc `check_constraint_checks=OFF`; không dùng dữ liệu thật.

**BACKEND INVARIANTS STILL REQUIRED:** Laravel Blueprint/Grammar hỗ trợ table/index/generated/FK; CHECK có tên nên migration tương lai dùng `DB::statement` raw MariaDB sau khi Schema Builder tạo cột/index/FK. Không tạo migration trong P0.

### P0-T16

**STATUS:** PASS

**OBJECTIVE:** Partial DDL failure và metadata-led rollback/retry tại các boundary M055/M057/M059.

**ENVIRONMENT:** Môi trường và schema test dùng chung cho toàn bộ case: PHP 8.4.25 portable, Laravel Framework 13.29.0, PDO MySQL/pdo_mysql, MariaDB 10.4.32, InnoDB, schema `smart_fitness_preflight_20260828_201141_15b1f4`. Probe không thay đổi GLOBAL sql_mode; toàn bộ DDL/data chạy với SESSION sql_mode strict `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION` và chỉ dùng dữ liệu giả lập.

**DDL/SCRIPT USED:** Mỗi boundary tạo parent/source, attach FK đầu tiên, chạy ALTER thứ hai cố tình tham chiếu `missing_column` (error 1072), rồi đọc information_schema. Script: `.tmp/preflight/p0_probe.php`.

**VALID TEST CASES:** FK đầu tiên tồn tại sau lỗi; DROP FK theo metadata thành công; metadata rỗng sau rollback; retry FK thành công; source/parent DROP thành công cho cả M055/M057/M059.

**INVALID TEST CASES:** ALTER attach lỗi có SQLSTATE 42000/code 1072; không giả migrations table là DDL atomic.

**EXPECTED:** Xác định chính xác phần đã chạy/dở, rollback đúng constraint, retry không mù.

**ACTUAL:** PASS cho cả ba migration boundary; mỗi metadata sau failure chỉ có `_fk_01`, sau rollback rỗng, retry có `_fk_01_retry`.

**MYSQL ERROR / SQLSTATE (expected negative cases):** 1072/42000.

**METADATA EVIDENCE:** `information_schema.KEY_COLUMN_USAGE` và `REFERENTIAL_CONSTRAINTS` được đọc trước/sau rollback/retry.

**CLEANUP RESULT:** Schema test được giữ đến hết case để đọc metadata; sau đó `DROP DATABASE` chính schema do probe tạo trả `ok=true`.

**SCHEMA IMPACT:** Chỉ schema test; không có bảng nghiệp vụ nào trong database project được tạo/sửa.

**DATABASE PROTECTION:** Không dùng `migrate:fresh`, `TRUNCATE`, `DROP smart_fitness`, `foreign_key_checks=0` hoặc `check_constraint_checks=OFF`; không dùng dữ liệu thật.

**BACKEND INVARIANTS STILL REQUIRED:** Migration runner cần log/inspect metadata và down theo phần đã xác minh; không DROP bảng ngoài schema probe.

## 4. MariaDB-Specific Findings

- **CHECK:** MariaDB 10.4 enforce named CHECK; negative cases trả code 4025/SQLSTATE 23000. `JSON_VALID` implicit checks của JSON được tách khỏi 83 CHECK nghiệp vụ (metadata ghi 15 check JSON_VALID).
- **FK:** 144 FK của manifest được tạo; explicit `RESTRICT` được chấp nhận và hành vi parent DELETE/UPDATE bị chặn. Không cần fallback NO ACTION.
- **Composite FK / nullable:** Tuple đầy đủ sai bị 1452; khi bất kỳ component nullable là NULL, MariaDB bỏ qua parent lookup của composite FK. NOT NULL/CHECK cùng hàng và Backend phải xử lý phần còn lại.
- **Self FK:** B34/B37 attach được; FK không tự cấm self-reference/cycle, nên workflow transaction phải cấm.
- **Generated / UNIQUE:** Đủ 5 VIRTUAL generated, nullable và indexed UNIQUE; client supplied values bị MariaDB bỏ qua và server vẫn trả expression value.
- **DATETIME(6):** `DATE_ADD` đúng ngày và giữ microseconds; cặp NULL/NULL hợp lệ, cặp lệch bị CHECK.
- **Collation:** Server default `utf8mb4_general_ci`; schema/table default probe `utf8mb4_unicode_ci`; technical/email overrides `utf8mb4_nopad_bin`. Email case-insensitive là trách nhiệm canonicalization trim + NFC + lowercase của Backend.
- **Partial DDL:** MariaDB không được giả định file migration atomic; metadata sau lỗi M055/M057/M059 chỉ ra FK đã chạy, rollback/retry đã được chứng minh.
- **Physical warning:** Full manifest dưới SESSION strict tạo warning 1901 và 1105 liên quan expression CHAR `ma_buoi_con_hieu_luc`/`PAD_CHAR_TO_FULL_LENGTH` khi ALTER FK. Không có DDL error; SHOW CREATE, `information_schema` và hành vi generated vẫn đúng. Đây là cảnh báo cần ghi nhận khi triển khai, không phải lý do tự bỏ generated/FK.

## 5. Laravel Findings

- **MariaDB connection:** Laravel 13.29.0 dùng connection MySQL/MariaDB và PDO MySQL; platform requirements PASS.
- **Generated DDL:** Blueprint/Grammar có thể tạo `virtualAs`, index/UNIQUE và FK; full raw manifest chứng minh server metadata đúng 5 generated.
- **CHECK implementation:** Không giả định có generic Schema Builder CHECK API; migration tương lai sẽ dùng `DB::statement` với tên CHECK MariaDB sau khi tạo bảng/cột.
- **FK implementation:** Named simple/composite/self FK với explicit `RESTRICT` chạy được; thứ tự M001–M060 phải tuân dependency manifest.
- **Indexes/collation:** Named UNIQUE/query indexes và column collations được kiểm tra bằng `information_schema`, không chỉ dựa exit code.
- **Schema Builder limitation:** Probe này không tạo migration PHP và không coi `database/migrations` là bằng chứng DDL. `full_manifest.sql` là script tạm được sinh từ từ điển/kế hoạch.

## 6. Physical Adjustments Required

NONE. Không cần đổi CHECK, FK, UNIQUE, NULLability, generated expression hoặc action để đạt PASS. Cảnh báo CHAR/padding đã được giữ nguyên và ghi nhận.

## 7. Logical Schema Changes Required

NONE. Không thêm bảng/cột, không đổi business rule, không sửa PROJECT_RULES, Data Dictionary, Database Design hoặc ERD.

## 8. Cleanup

- **Probe database:** `smart_fitness_preflight_20260828_201141_15b1f4` — `DROP DATABASE` trả `ok=true`.
- **Temporary files:** `.tmp/preflight/p0_probe.php`, `.tmp/preflight/full_manifest.sql`, `.tmp/preflight/results/p0_evidence.json` và generator là file probe local, không đặt trong `BE/database/migrations/`; không chứa secret.
- **Official migrations created/run:** NONE; `BE/database/migrations` không có PHP migration mới.
- **Production/project database modified:** NO. Không truy cập/cập nhật `smart_fitness` hoặc dữ liệu thật.

## 9. Final Gate

**P0 TECHNICAL PREFLIGHT = PASS**

DATABASE DESIGN = APPROVED FOR MARIADB 10.4.32

MIGRATION PLAN = READY FOR IMPLEMENTATION

M001–M060 = READY TO CREATE

Nhưng M001–M060 không được tạo trong nhiệm vụ P0 này. Toàn bộ probe đã chạy với SESSION sql_mode strict exact ở trên; khi triển khai chính thức Laravel connection phải giữ cùng mode set/collation. Warning CHAR/padding được giữ nguyên và ghi nhận, không đổi schema để né probe.

## Git status at completion

Các thay đổi trong working tree được kiểm tra bằng `git status --short`; phạm vi của nhiệm vụ này chỉ cập nhật report P0. Các thay đổi tài liệu đã tồn tại từ trước được giữ nguyên.
