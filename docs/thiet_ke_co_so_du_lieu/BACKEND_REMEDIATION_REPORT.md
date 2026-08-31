# BACKEND REMEDIATION REPORT

## MODULE DA HOAN THANH

### 1. Muc tieu module

Khắc phục các hạng mục `TEST-DB-001`, `ADMIN-BOOTSTRAP-001`, `PT-ONBOARDING-001`, `ADMIN-PAYMENT-001`, `TEMPLATE-HISTORY-001`, `AUTH-RESET-001` và phần code/configuration của `DEPLOY-CONFIG-001`.

Không tạo migration mới, không sửa logical schema hoặc business rule, không chạy destructive command trên database development `smart_fitness`.

### 2. File da tao

- `BE/tests/Support/TestDatabaseGuard.php` và test unit cho guard.
- Artisan commands `smart-fitness:bootstrap-admin` và `smart-fitness:preflight`.
- `BootstrapAdminService`, `TrainerOnboardingService`, `AdminPaymentQueryService`.
- Controller, Form Request và feature test cho onboarding PT và Admin Payment query.
- Job `ProcessPasswordResetRequest`.
- Request tạo revision giáo án mẫu.

### 3. File da sua

- Cấu hình PHPUnit, CI và `BE/README.md` cho database test cô lập.
- Services/Controllers/Routes Auth, Admin Role/Account, Catalog, Payment và Trainer Profile.
- `BE/tests/TestCase.php`: seed baseline idempotent một lần cho mỗi PHPUnit process, sau `TestDatabaseGuard`.
- Feature/concurrency tests liên quan và `.env.example`.

### 4. Database lien quan

Không có migration hay thay đổi schema.

- Test safety dùng duy nhất `smart_fitness_test`, yêu cầu `APP_ENV=testing`, `DB_DATABASE` và `SMART_FITNESS_TEST_DATABASE` khớp chính xác, tên có prefix `smart_fitness_` và chứa `test`.
- Các workflow dùng bảng hiện hữu: `nguoi_dung`, `vai_tro`, `phan_quyen_nguoi_dung`, `ho_so_huan_luyen_vien`, `nhat_ky_he_thong`, `don_mua_goi`, `lan_thanh_toan`, `su_kien_thanh_toan`, `ky_han_hoi_vien`, `giao_an_mau`, `ngay_trong_giao_an`, `bai_tap_trong_giao_an`, `yeu_cau_dat_lai_mat_khau` và `yeu_cau_chong_lap`.

### 5. API da tao/sua

- `POST /api/admin/trainers` — tạo account + hồ sơ PT + role PT, yêu cầu `Idempotency-Key`.
- `POST /api/admin/accounts/{account}/trainer-profile` — onboard account có sẵn thành PT.
- `GET /api/admin/payments`, `GET /api/admin/payments/{payment}`, `GET /api/admin/payment-events` — chỉ đọc, theo chi nhánh Admin.
- `POST /api/admin/workout-templates/{workoutTemplate}/revisions` — copy-on-write revision giáo án mẫu.
- API cấp role PT trả `TRAINER_PROFILE_REQUIRED` nếu account chưa có hồ sơ PT.
- Forgot Password giữ response chung và chỉ dispatch job; HTTP request không lookup account hoặc gửi mail.

### 6. Ham chinh

- `TestDatabaseGuard::damBaoDatabaseHienTai()` — chặn test/child process chạm database development.
- `BootstrapAdminService::bootstrap()` — tạo đúng một Admin đầu tiên trong transaction, audit và queue password setup sau commit.
- `AdminActorGuard::damBaoKhongVoHieuHoaAdminHoatDongCuoiCung()` — khóa role ADMIN và bảo vệ Admin hoạt động cuối cùng.
- `TrainerOnboardingService` — tạo/cập nhật account, profile, role và audit nguyên tử.
- `AdminPaymentQueryService` — truy vấn Payment/Event chỉ đọc, lọc, phân trang và che dữ liệu nhạy cảm.
- `WorkoutTemplateCatalogAdminService::taoPhienBanMoi()` — tạo cây giáo án mới, dừng giáo án cũ, không hard-delete cây lịch sử.
- `PasswordResetService::yeuCau()` và `ProcessPasswordResetRequest::handle()` — tách request HTTP khỏi lookup/token/mail, chỉ lưu SHA-256 token.
- `SmartFitnessPreflightCommand` — kiểm tra fail-closed DB, Queue, Cache, Scheduler, Gemini và Reverb; không in secret.

### 7. Business Rule da xu ly

- Role PT không đồng nghĩa có scope Member hoặc hồ sơ PT; onboarding tạo hồ sơ trước khi cấp/regrant PT.
- Cấp lại role dùng lại cùng hàng `phan_quyen_nguoi_dung`; audit nằm trong cùng transaction.
- Không thể khóa/ngừng tài khoản hoặc thu hồi role của Admin hoạt động cuối cùng.
- Payment bất thường `CAN_DOI_SOAT` chỉ được truy vấn bởi Admin, không có API sửa tay/refund.
- Giáo án catalog quan trọng dùng copy-on-write; Plan/Session/history cũ giữ nguyên.
- Forgot Password không phân biệt known/unknown email ở HTTP response và raw token không được lưu/log/queue.

### 8. Authorization

- Onboarding PT và truy vấn Payment/Event chỉ qua `auth:api` + role `ADMIN`.
- Payment/Event bị scope theo `chi_nhanh_id` của Admin; event không gắn payment bị loại khỏi kết quả để không lộ dữ liệu chi nhánh khác.
- PT mới có role nhưng chưa phân công vẫn không truy cập Member; regrant không hồi sinh phân công cũ.

### 9. Validation

- Guard từ chối `smart_fitness`, tên không có `test`, `APP_ENV` khác `testing` và DB config/current/approved không khớp.
- Trainer request validate email, trạng thái, profile và server-generated trainer code.
- Revision cần `expected_content_version`, code mới, cấu trúc ngày/bài hợp lệ và Exercise đang hoạt động.
- Payment query whitelist filter/sort/pagination; response không chứa webhook signature, raw payload hoặc secret.
- Preflight kiểm tra môi trường production, `APP_DEBUG=false`, queue không `sync`, Reverb/Gemini config có mặt và không in giá trị secret.

### 10. Transaction / Idempotency

- Bootstrap Admin, last-admin guard, PT onboarding, role audit, template revision và password reset worker dùng transaction theo workflow.
- Bootstrap khóa role ADMIN; test hai process xác nhận chỉ một command tạo Admin.
- Onboarding PT dùng `Idempotency-Key`; cùng payload replay, payload khác conflict.
- Revision template kiểm tra optimistic content version dưới lock; hai revision đồng thời chỉ một thành công.
- Payment query chỉ đọc, không tạo Payment/kỳ Membership/usage.

### 11. Error Case

- Bootstrap lần hai với Admin khác bị từ chối có kiểm soát; cùng Admin trả `UNCHANGED`.
- Direct PT grant thiếu profile trả `409 TRAINER_PROFILE_REQUIRED`.
- Input trùng/inactive/audit failure trong onboarding rollback toàn bộ account/profile/role/audit.
- Revision stale trả conflict; PATCH không nhận `days` nên không còn đường hard-delete cây cũ.
- Queue dispatch Password Reset lỗi trả thông báo chung `503`, không phụ thuộc email tồn tại.
- Preflight production thiếu cấu hình trả exit code khác 0 với tên cấu hình lỗi, không lộ secret.

### 12. Test Case

- Test database guard local/CI, environment và mismatch rejection; child process concurrency kế thừa cả hai biến database.
- Bootstrap command, retry, hai process đồng thời, rollback audit và last-admin protection.
- PT onboarding authorization, validation, idempotency, rollback, regrant và scope Member.
- Payment reconciliation, branch isolation, filters, pagination, read-only response và payload/signature redaction.
- Template revision giữ cây cũ/Workout history và actual-process revision race.
- Password Reset known/unknown response, queue job, hash-only token, token invalidation.
- Preflight command fail-closed và không leak secret.
- Full suite theo thứ tự chuẩn và random seed `20260830`.

### 13. Test Result

| Check | Result |
| --- | --- |
| PHPUnit full run | PASS — 301 tests, 3,207 assertions |
| PHPUnit random order `20260830` | PASS — 301 tests, 3,207 assertions |
| PHPUnit notices/risky | PASS — 0 notice, 0 risky trong hai run cuối |
| Pint `--test` | PASS |
| PHP syntax lint (affected files) | PASS |
| Composer validate `--strict` | PASS |
| Composer audit `--locked --format=plain` | PASS — no advisory |
| `git diff --check` | PASS |
| Source secret pattern scan | PASS — 0 Gemini/OpenAI/payOS/private-key match |
| Test schema cleanup | PASS — `smart_fitness_test`; users=0, payments=0, audits=0 |

`migrate:fresh --force` chỉ được chạy sau guard trên `smart_fitness_test`; database development `smart_fitness` không bị migrate, seed hoặc cleanup.

### 14. Phan chua hoan thanh

- **DEPLOY-CONFIG-001 production verification:** command đã implement và test fail-closed, nhưng chưa chạy `smart-fitness:preflight --no-network`/`--external` trên một production-like hoặc staging environment có Queue worker, Scheduler, Reverb và Gemini được cấp quyền. Không giả lập PASS khi chưa có môi trường đó.
- **RELEASE-GIT-001:** worktree còn nhiều file nguồn/docs/CI đã modified hoặc untracked từ các module trước và trong chuỗi công việc. Final inspection có 105 tracked file trong `git diff --name-only`, 104 untracked file theo `git ls-files --others --exclude-standard`; `git status --short` hiển thị 165 dòng vì gộp một số thư mục untracked. Theo yêu cầu không commit/push, task này không stage, không xóa và không chia commit. Release/handoff Git chưa sẵn sàng cho đến khi owner phân loại, review, stage và commit theo từng module.

### 15. Rui ro con lai

- Production cần cấu hình thật Queue (không `sync`), Scheduler, Reverb và Gemini trước khi deploy; external smoke test chỉ được chạy trên staging khi có quyền.
- `QUEUE_CONNECTION=database` cần bảng jobs thích hợp hoặc dùng Redis; command preflight sẽ fail nếu backend queue không vận hành được.
- Báo cáo không thay thế review từng thay đổi tồn tại sẵn trong worktree. Không có secret pattern phổ biến trong source/docs được quét, nhưng vẫn phải review diff trước commit.

## FINAL GATE

```text
BACKEND REMEDIATION IMPLEMENTATION = PASS
TEST-DB-001 = PASS
ADMIN-BOOTSTRAP-001 = PASS
PT-ONBOARDING-001 = PASS
ADMIN-PAYMENT-001 = PASS
TEMPLATE-HISTORY-001 = PASS
AUTH-RESET-001 = PASS
DEPLOY-CONFIG-001 CODE = PASS
DEPLOY-CONFIG-001 PRODUCTION SMOKE = PENDING ENVIRONMENT
RELEASE-GIT-001 = NOT READY
```

Không có migration mới, Model nghiệp vụ mới, Seeder mới, API refund/correction hoặc thay đổi logical schema trong module remediation này.

## HẬU KIỂM NGÀY 31/08/2026

FINAL GATE ở trên ghi nhận kết quả của vòng triển khai và bộ test tại thời điểm báo cáo; không thay thế kết luận production readiness.

Vòng hậu kiểm tiếp theo xác nhận 301/301 test vẫn pass nhưng phát hiện các đường lỗi chưa được bao phủ đầy đủ: event Payment không có `lan_thanh_toan_id` chưa hiện cho Admin, retry invitation sau Queue failure, snapshot audit onboarding PT và Reverb external probe còn lỏng. Production Queue/Reverb/Gemini và catalog ban đầu cũng chưa được xác minh trên staging thật.

Danh sách ưu tiên, cách sửa, test và output mong đợi được duy trì tại [BACKEND_FOLLOW_UP_FIXES.md](BACKEND_FOLLOW_UP_FIXES.md). Tài liệu đó là nguồn trạng thái follow-up mới nhất; không dùng nhãn PASS cũ để tuyên bố production-ready.

Theo yêu cầu Git tiếp theo của chủ dự án, mã nguồn Backend/CI/test đã được lưu tại commit `7446219` với thông điệp tiếng Việt. README, báo cáo và backlog follow-up được lưu trong commit tài liệu chứa phần hậu kiểm này. Sau commit tài liệu phải dùng `git status --short` để xác nhận không còn thay đổi chưa ghi nhận; không dùng thống kê worktree cũ tại mục 14 làm trạng thái Git hiện tại.
