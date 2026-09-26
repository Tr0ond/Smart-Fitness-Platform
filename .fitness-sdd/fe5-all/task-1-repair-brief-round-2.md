# BE-FE5-PREREQ — Repair Brief Round 2

`STATUS: BLOCKED_USER_ACTION`  
`OPEN_FINDINGS: F-002`  
`CLOSED_FINDINGS: F-001`  
`ROUND: 2`  
`REPAIR_TYPE: WORKFLOW_TEST_ENVIRONMENT_RECOVERY_ONLY`  
`LUNA_FIXER_REQUIRED: NO`  
`FULL_CANONICAL_REREAD: CONDITIONAL`  
`CANONICAL_ESCALATION: NONE`

## 1. Phân loại và kết luận

`F-002` là **workflow/test-environment incident**, không phải code defect hoặc test defect đã được chứng minh.

- Focused suite `PtMemberWorkspaceApiTest` và related regression suite đã PASS độc lập; reviewer Round 1 đã xác minh trực tiếp toàn bộ `F-001-A` đến `F-001-G`.
- Full Backend suite của fixer từng PASS `346 tests / 4,104 assertions / 0 failures / 0 skips` trước khi schema bị contamination.
- Full Backend suite độc lập sau đó FAIL `337 passed / 9 failed / 346 tests / 4,060 assertions`; nhóm lỗi đều biểu hiện dữ liệu fixture còn sót trong schema dùng chung, không tập trung vào Task 1 source/test.
- Read-only investigation Round 2 xác nhận `smart_fitness_test` đã đủ `61` migrations nhưng hiện có `chi_nhanh=5`, `nguoi_dung=12`, `goi_tap=2`, `don_mua_goi=2`, `bai_tap=1326`, trong khi deterministic baseline được repository ghi nhận là `chi_nhanh=1`, `nguoi_dung=8`, `goi_tap=0`, `don_mua_goi=0`, `bai_tap=1324`.
- Không còn PHP/Artisan test process và không còn MariaDB connection gắn với `smart_fitness_test`; contamination là persisted state, không phải một suite vẫn đang chạy.
- Schema test khác duy nhất đang tồn tại là `smart_fitness_fe2_test`; schema này có `60` migrations và thiếu `2026_09_10_000061_m061_them_trang_thai_nhom_co`, nên không phải target hợp lệ để tái chạy gate hiện tại.

Không có safe in-scope recovery nào vừa giữ nguyên source/test/data vừa tạo lại clean full-suite evidence. Chạy lại suite trên `smart_fitness_test` không loại bỏ persisted contamination; dùng `smart_fitness_fe2_test` tạo evidence trên schema version sai. Xóa row, truncate/drop/recreate schema, `migrate:fresh` hoặc migrate schema cũ đều là DB mutation ngoài quyền của Fix Planner.

## 2. Finding mapping — F-002

### Finding nguyên văn cần giải quyết

- ID: `F-002`
- Severity: `Important`
- Rule: `task-1-brief.md` Sections 5A/6; RULE CODE 19/20; `testing-definition-of-done.md` Sections 1.3/54.3.
- Required outcome: khôi phục hoặc provision một isolated test schema sạch bằng procedure được owner/controller phê duyệt, rồi chạy đúng một full Backend suite tuần tự với đủ bốn environment guards và ghi nhận PASS sạch, không sửa assertion hoặc xóa dữ liệu để ép gate xanh.

### Root cause

Test harness dùng một schema MariaDB chia sẻ giữa các PHPUnit process:

- `BE/tests/TestCase.php` chạy `db:seed --force` đúng một lần cho mỗi PHPUnit process;
- các feature tests thông thường bắt đầu transaction trong `setUp()` và rollback trong `tearDown()`;
- concurrency helpers mở child process/connection trên cùng exact schema;
- không có suite-level reset hoặc automatic provisioning schema mới.

Một cặp full-suite process từng chạy chồng lấn/bị abort đã để lại committed fixture rows ngoài vòng rollback bình thường. Khi process kết thúc bất thường, `tearDown()` hoặc cleanup theo test không thể bảo đảm chạy. `TestDatabaseGuard` chỉ xác minh đúng schema test; guard không chứng minh schema sạch.

### Exact files/logic to change

Không có product source, test source, assertion, migration, seeder hoặc config nào được phép hay cần sửa cho `F-002`.

- Product/test write set: **none**.
- Chỉ có workflow artifact này được tạo: `E:/Fitness/.fitness-sdd/fe5-all/task-1-repair-brief-round-2.md`.
- Không append `task-1-report.md` trước khi có observed full-suite result mới.

### Constraints phải giữ nguyên

- Giữ `F-001: CLOSED`; không mở lại nếu không có chứng cứ mới.
- Không sửa/nới assertion, DB guard, error contract hoặc business rule.
- Không xóa row, truncate, drop, rollback, `migrate:fresh`, hoặc tái sử dụng schema lịch sử chưa được xác minh sạch.
- Không chạy song song hai Artisan/PHPUnit suites trên cùng schema.
- Không dùng `smart_fitness`, schema có dữ liệu cần giữ, hoặc schema không khớp `smart_fitness_*test*`.
- Không branch/worktree/commit/push/reset/restore/checkout/stash/clean.

## 3. BLOCKED_USER_ACTION — action tối thiểu cần owner/controller phê duyệt

Owner/controller cần **phê duyệt hoặc tự provision đúng một disposable schema mới**, đề xuất exact name:

`smart_fitness_fe5_round2_test`

Schema phải mới hoàn toàn, dùng `utf8mb4` / `utf8mb4_unicode_ci`, được migrate tới M061 và seed deterministic baseline trước khi chạy gate. Đây là lựa chọn an toàn hơn việc xóa selective rows trong `smart_fitness_test`, vì không cần suy đoán ownership của các row bị giữ lại và không chạm schema development.

Không được dùng tên đã tồn tại với `CREATE DATABASE IF NOT EXISTS`; creation phải fail nếu exact name đã có để ngăn tái sử dụng một schema không rõ trạng thái.

### Exact sequential commands — chỉ chạy sau explicit owner approval

Từ `E:/Fitness`:

```powershell
rtk proxy "C:/xampp/mysql/bin/mysql.exe" -u root -e "CREATE DATABASE smart_fitness_fe5_round2_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Từ `E:/Fitness/BE`, chạy migrate và seed tuần tự với cùng bốn guards:

```powershell
rtk proxy powershell -NoProfile -Command '& { $env:APP_ENV="testing"; $env:DB_CONNECTION="mysql"; $env:DB_DATABASE="smart_fitness_fe5_round2_test"; $env:SMART_FITNESS_TEST_DATABASE="smart_fitness_fe5_round2_test"; & "E:/Fitness/.tools/php/php.exe" artisan migrate --force }'
rtk proxy powershell -NoProfile -Command '& { $env:APP_ENV="testing"; $env:DB_CONNECTION="mysql"; $env:DB_DATABASE="smart_fitness_fe5_round2_test"; $env:SMART_FITNESS_TEST_DATABASE="smart_fitness_fe5_round2_test"; & "E:/Fitness/.tools/php/php.exe" artisan db:seed --force }'
```

Sau khi xác nhận không có PHP/Artisan suite nào khác chạy, chạy **đúng một** full Backend suite tuần tự:

```powershell
rtk proxy powershell -NoProfile -Command '& { $env:APP_ENV="testing"; $env:DB_CONNECTION="mysql"; $env:DB_DATABASE="smart_fitness_fe5_round2_test"; $env:SMART_FITNESS_TEST_DATABASE="smart_fitness_fe5_round2_test"; & "E:/Fitness/.tools/php/php.exe" artisan test }'
```

Không chạy focused/related/full đồng thời. Không drop disposable schema trong cùng recovery wave; cleanup chỉ được thực hiện sau khi evidence đã được review và có phê duyệt destructive action riêng.

Nếu owner không muốn cấp quyền `CREATE DATABASE`, action tối thiểu tương đương là owner tự tạo và migrate/seed exact disposable schema trên, sau đó bàn giao exact schema name cho fresh reviewer chạy command full suite.

## 4. Acceptance evidence cho F-002

`F-002` chỉ được đóng khi fresh Sol re-reviewer quan sát và ghi lại đầy đủ:

1. exact disposable schema name và evidence tên khớp `smart_fitness_*test*`;
2. `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_DATABASE` và `SMART_FITNESS_TEST_DATABASE` đều trỏ đúng exact schema;
3. schema có đầy đủ M001–M061 trước test;
4. không có full/focused/related suite khác chạy chồng lấn trên schema;
5. đúng một `artisan test` full Backend command PASS với exact test/assertion/failure/skip counts và zero unapproved skips;
6. no product/test/source delta phát sinh từ recovery;
7. `F-001` vẫn CLOSED và Task 1 gate chỉ mở sau fresh review verdict PASS.

Nếu full suite trên disposable clean schema vẫn fail, reviewer phải ghi exact failing tests/output và phân loại lại từ evidence mới. Không tự động quy lỗi cho Task 1, không sửa assertion và không dùng schema contaminated để đối chiếu.

## 5. Luna fixer decision và workflow tiếp theo

`LUNA_FIXER_REQUIRED: NO`.

Không có code/test repair hợp lệ để giao Luna. Dispatch Luna trong trạng thái này sẽ tạo một repair giả cho environmental contamination và có nguy cơ làm yếu test guard/expectation.

Workflow tiếp theo:

1. Controller chờ explicit owner approval/provisioning cho disposable schema.
2. Sau provisioning, controller dispatch fresh Sol High re-reviewer với repair brief này và `task-1-review-round-1.md`.
3. Fresh reviewer chạy đúng một full Backend suite tuần tự trên exact disposable schema và viết Round 2 review artifact.
4. Chỉ khi full suite PASS và scope integrity vẫn sạch mới đóng `F-002` và mở gate `BE-FE5-PREREQ -> FE5-ALL`.

## 6. Read-only evidence đã thực hiện trong Round 2

- `rtk git status --short --branch`: branch vẫn `main`; product delta chỉ là declared Task 1 paths và workflow scratch directory.
- Windows process inspection: `NO_PHP_PROCESSES`; không có Artisan/PHPUnit/ParaTest process đang chạy.
- MariaDB `INFORMATION_SCHEMA.PROCESSLIST`: không có connection gắn với `smart_fitness_test` hoặc `smart_fitness_fe2_test` tại thời điểm kiểm tra.
- MariaDB schema inventory: chỉ có `smart_fitness_test` và `smart_fitness_fe2_test` khớp `smart_fitness%test%`.
- `smart_fitness_test`: `53` tables, `61` migrations; persisted counts nêu tại Section 1 chứng minh contamination.
- `smart_fitness_fe2_test`: `53` tables, `60` migrations; missing M061 nên không phải fallback hợp lệ.
- Không chạy test suite, migration, seeder, DDL, DELETE/TRUNCATE/DROP/CREATE hoặc bất kỳ DB mutation nào trong Round 2.

## 7. Genuine blocker

`BLOCKER: OWNER_APPROVAL_REQUIRED_FOR_DISPOSABLE_TEST_SCHEMA_PROVISIONING`

Fix Planner không được phép provision/migrate/seed schema mới trong round này. Cho đến khi owner/controller cấp quyền hoặc tự thực hiện action tối thiểu ở Section 3, không thể tạo full-suite PASS evidence hợp lệ và Task 1 gate phải tiếp tục đóng.
