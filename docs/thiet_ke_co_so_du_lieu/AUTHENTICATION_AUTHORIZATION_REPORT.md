# AUTHENTICATION & AUTHORIZATION REPORT

## 1. Scope

Đã triển khai lớp nền xác thực và phân quyền cho Laravel REST API: đăng nhập,
Bearer token, current user, đăng xuất current token và middleware role dùng
lại. Không triển khai Membership, Payment/payOS, QR, Workout, PT, Chat, AI,
public registration, quản lý role hoặc password-reset workflow. Không tạo hay
sửa migration/schema, không thêm package auth và không sửa FE/Mobile.

## 2. Environment

| Hạng mục | Kết quả đã kiểm tra |
|---|---|
| PHP | 8.4.25 (`.tools/php/php.exe`) |
| Laravel | Framework 13.29.0 |
| DB_CONNECTION | `mysql` |
| DBMS | MariaDB 10.4.32, vendor `mariadb.org binary distribution` |
| Engine | InnoDB |
| SESSION sql_mode | `STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION` |
| Charset/collation connection | `utf8mb4` / `utf8mb4_unicode_ci` |
| Test schema | `smart_fitness_auth_test_20260829_a41c` (đã cleanup) |
| Schema baseline | M001–M060, 52 CORE tables + Laravel `migrations` |

## 3. Authentication Architecture

- **User model:** `NguoiDung` implement Laravel `AuthenticatableContract` bằng
  trait framework, ánh xạ password sang `mat_khau_bam`, không thêm
  `MustVerifyEmail`, `HasApiTokens`, remember-token hoặc 2FA. Mapping, fillable,
  casts, timestamps và relationships cũ được giữ; password hash được hidden.
- **Guard:** custom request guard `AccessTokenGuard`. Mỗi request mới xóa user
  cache rồi gọi `AuthenticationService`, phù hợp cả test kernel và worker sống
  lâu; không tin user/token từ request trước.
- **Token table:** `the_truy_cap`, đúng schema đã duyệt.
- **Token storage:** chỉ lưu hash, tên thiết bị, phạm vi `['api']`, mốc dùng gần
  nhất, hết hạn, thu hồi và timestamps. Raw token chỉ xuất hiện trong response
  phát hành.
- **Token hashing:** raw token là 32 byte từ `random_bytes` mã hóa thành 64 ký
  tự hex; lookup hash dùng SHA-256, đúng `CHAR(64)`.
- **Expiry:** mặc định 43.200 phút (30 ngày), cấu hình bằng
  `AUTH_TOKEN_LIFETIME_MINUTES`. Đây là **IMPLEMENTATION POLICY — NOT BUSINESS
  ENTITLEMENT** và không liên hệ thời hạn Membership.
- **Revocation:** update `thu_hoi_luc`; không xóa hàng lịch sử.
- **Sanctum used:** **NO**. Composer không có Sanctum và schema chính thức đã có
  `the_truy_cap`; dùng Sanctum mặc định sẽ cần bảng `personal_access_tokens`
  trái với schema khóa.

## 4. Endpoints

| Method | Path | Auth required | Purpose |
|---|---|---:|---|
| POST | `/api/auth/login` | No | Xác minh credentials và phát hành token |
| GET | `/api/auth/me` | Bearer | Trả identity và active roles an toàn |
| POST | `/api/auth/logout` | Bearer | Thu hồi đúng current token |

Không tạo endpoint debug/business để kiểm tra role. Các route role trong test
được đăng ký động và không nằm trong `routes/api.php`.

## 5. Login Flow

1. `LoginRequest` canonicalize email rồi validate payload.
2. Canonical email dùng trim, Unicode NFC qua Symfony polyfill hiện có và
   lowercase toàn địa chỉ trước lookup trên `nguoi_dung.thu_dien_tu`.
3. Mật khẩu được kiểm tra bằng `Hash::check`; response không phân biệt email
   không tồn tại và mật khẩu sai.
4. Chỉ tài khoản `HOAT_DONG` có ít nhất một role chính thức chưa thu hồi được
   phát hành token. User/password/state/role được đọc lại dưới transaction và
   khóa user trước ghi token.
5. Lưu SHA-256 hash vào `the_truy_cap`, cập nhật
   `dang_nhap_gan_nhat_luc`, trả raw token đúng một lần.

## 6. Token Lifecycle

- **Issue:** mỗi lần login hợp lệ tạo token ngẫu nhiên độc lập; nhiều thiết bị
  được phép và hai lần login tạo hai hash khác nhau.
- **Authenticate:** bắt buộc header chính xác `Bearer <64 lowercase hex>`, hash
  SHA-256, kiểm tra hàng token tồn tại, `thu_hoi_luc IS NULL`, thời điểm hiện tại
  nhỏ hơn `het_han_luc` và account vẫn `HOAT_DONG`; sau đó cập nhật
  `su_dung_gan_nhat_luc`.
- **Expire:** tại đúng `het_han_luc` hoặc sau đó trả 401.
- **Revoke:** logout ghi `thu_hoi_luc` cho current token; token cũ trả 401, token
  của thiết bị khác vẫn hợp lệ.

## 7. Authorization

Roles chính thức: `MEMBER`, `PT`, `RECEPTIONIST`, `ADMIN`.

Middleware dùng cú pháp `role:ADMIN` hoặc `role:ADMIN,RECEPTIONIST`. Nó đọc
`phan_quyen_nguoi_dung` và `vai_tro` trên từng request; role chỉ active khi
`thu_hoi_luc IS NULL`. Có một role khớp là đủ cho user nhiều role.
Unauthenticated trả 401; authenticated nhưng không có role phù hợp trả 403.

Thu hồi role không thu hồi token: cùng token vẫn gọi `/api/auth/me` được nhưng
route yêu cầu role đổi sang 403. Cấp lại update cùng assignment row và
authorization có hiệu lực trở lại, không insert cặp trùng.

## 8. Membership Isolation

| Hành động auth | Kích hoạt Membership |
|---|---:|
| Login | NO |
| GET `/api/auth/me` | NO |
| Logout | NO |

Test database-backed tạo `dang_ky_goi_tap` ở `CHO_KICH_HOAT`, gọi đủ ba hành
động rồi xác nhận `trang_thai` không đổi, `lan_su_dung_dau_tien_id` và
`ngay_bat_dau` vẫn NULL, không tạo `su_dung_quyen_loi`.

## 9. Security

- Raw token stored: **NO**; DB chứa đúng `hash('sha256', raw_token)`.
- Password exposed: **NO**; `mat_khau_bam` hidden và response dùng allow-list.
- Token/reset/internal hash exposed: **NO**.
- Generic invalid-credential response: **PASS**, cùng HTTP 401 và cùng message.
- Account state enforced lúc login và mỗi protected request: **PASS**.
- Missing, malformed, unknown, expired và revoked token: **401**.
- Login throttle: mặc định 5 lần/phút theo IP, cấu hình bằng
  `AUTH_LOGIN_RATE_LIMIT_PER_MINUTE`; khóa không dùng email nên không tiết lộ
  sự tồn tại account.
- Không log password, raw token hoặc token hash. Không gọi provider ngoài.

## 10. Tests

| Test gate | Kết quả |
|---|---|
| Authentication tests | 15 tests, 121 assertions, PASS |
| Authorization tests | 9 tests, 52 assertions, PASS |
| Auth + Authorization combined | 24 tests, 173 assertions, PASS |
| Existing full backend suite — run 1 | 41 tests, 798 assertions, PASS |
| Existing full backend suite — run 2 | 41 tests, 798 assertions, PASS |
| Random order seed `20260829` | 41 tests, 798 assertions, PASS |
| Laravel Pint trên file thay đổi | PASS |
| `git diff --check` | PASS |

Test bao phủ email canonicalization/NFC, generic credential error, malformed
payload, cả `BI_KHOA` và `NGUNG_HOAT_DONG`, user không có active role, token
entropy/hash, `/me`, last-used, account bị khóa sau issue, expiry, revocation,
logout current token, hai thiết bị, throttle, bốn role, wrong role, multiple
roles, revoke và re-grant.

Trong vòng chạy đầu, test phát hiện guard framework cache user giữa nhiều
request trong cùng process; `AccessTokenGuard` đã xóa cache khi đổi request.
Regression tiếp theo phát hiện fixture role va chạm baseline Seeder; fixture đã
được sửa để dùng role hiện có hoặc tạo khi schema sạch. Toàn bộ các run cuối
cùng ở bảng trên đều PASS liên tiếp và order-independent.

## 11. Database Safety

- `smart_fitness` modified by tests: **NO**. Kiểm tra read-only cuối task cho
  thấy 60 migration rows, 8 demo users và 0 token, đúng baseline trước module
  Auth; mọi lệnh migrate/test tích hợp đều trỏ rõ schema test.
- Test schema: `smart_fitness_auth_test_20260829_a41c`.
- Sau test, `the_truy_cap=0`, `dang_ky_goi_tap=0`,
  `su_dung_quyen_loi=0` vì fixture auth rollback; baseline Seeder không tạo
  workflow data.
- Cleanup: schema đã `DROP` theo exact name; truy vấn
  `information_schema.SCHEMATA` trả 0. **PASS**.
- Không tắt FK/CHECK/UNIQUE/strict mode; không dùng SQLite; không chạy
  `migrate:fresh`/rollback/truncate trên database phát triển.

## 12. Files Changed

Modified:

- `BE/.env.example`
- `BE/app/Models/NguoiDung.php`
- `BE/app/Providers/AppServiceProvider.php`
- `BE/bootstrap/app.php`
- `BE/config/auth.php`
- `BE/routes/api.php`

Created:

- `BE/app/Auth/AccessTokenGuard.php`
- `BE/app/Http/Controllers/Api/Auth/AuthController.php`
- `BE/app/Http/Middleware/EnsureRole.php`
- `BE/app/Http/Requests/Auth/LoginRequest.php`
- `BE/app/Services/AuthenticationService.php`
- `BE/app/Support/EmailCanonicalizer.php`
- `BE/tests/Concerns/CreatesAuthenticationFixtures.php`
- `BE/tests/Feature/AuthenticationTest.php`
- `BE/tests/Feature/AuthorizationTest.php`
- `docs/thiet_ke_co_so_du_lieu/AUTHENTICATION_AUTHORIZATION_REPORT.md`

Không có migration, Model khác, Seeder, Controller/Service nghiệp vụ, FE hoặc
Mobile bị sửa.

## 13. Deferred Scope

- Public registration: **OUT OF SCOPE**.
- Password reset: **PASSWORD RESET TABLE = READY**;
  **PASSWORD RESET WORKFLOW = DEFERRED** vì chưa có delivery/confirmation
  contract hoàn chỉnh.
- Role management API và audit catalog cấp/thu hồi: **DEFERRED**.
- Logout all: **OUT OF SCOPE**; chỉ current token được thu hồi.
- Membership/Payment/QR/Workout/PT/Chat/AI: **NOT IMPLEMENTED**.

## 14. Deviations

Không có deviation logical schema hoặc business rule. Các giá trị 30 ngày cho
token, 5 login/phút, scope `api` và tên thiết bị mặc định `API client` là policy
kỹ thuật cấu hình được để nền tảng hoạt động; chúng không phải quyền lợi hay
thời hạn Membership.

## 15. Final Gate

```text
LOGIN = PASS
TOKEN ISSUANCE = PASS
TOKEN AUTHENTICATION = PASS
TOKEN EXPIRY = PASS
TOKEN REVOCATION = PASS
LOGOUT = PASS
CURRENT USER = PASS
EMAIL NORMALIZATION = PASS
PASSWORD SECURITY = PASS
ROLE AUTHORIZATION = PASS
REVOKED ROLE = PASS
MULTIPLE ROLE SUPPORT = PASS
MEMBERSHIP NON-ACTIVATION = PASS
AUTH SECURITY TESTS = PASS
AUTHORIZATION TESTS = PASS
FULL BACKEND TEST SUITE = PASS
TEST ORDER INDEPENDENCE = PASS
TEST DATABASE CLEANUP = PASS
smart_fitness = SAFE

AUTHENTICATION IMPLEMENTATION = PASS
AUTHORIZATION FOUNDATION = PASS
LOGIN/LOGOUT/ME = IMPLEMENTED
TOKEN AUTHENTICATION = IMPLEMENTED
ROLE AUTHORIZATION = IMPLEMENTED
MEMBERSHIP ACTIVATION = NOT TRIGGERED BY AUTH
DATABASE = READY FOR USER/PROFILE API IMPLEMENTATION
```

NEXT RECOMMENDED STEP: USER / PROFILE API FOUNDATION.
