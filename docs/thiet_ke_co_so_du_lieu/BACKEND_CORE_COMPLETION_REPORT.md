# BACKEND CORE COMPLETION REPORT

## 1. Scope

Chương trình hoàn thiện Backend Core theo chín phase tuần tự. Logical schema 52 bảng và M001–M060 bị khóa; development database `smart_fitness` chỉ đọc; không triển khai FE/Mobile và không commit/push.

## 2. Baseline

| Thành phần | Giá trị |
| --- | --- |
| PHP | 8.4.25 portable tại `E:\Fitness\.tools\php\php.exe` |
| Laravel | 13.29.0 |
| Database | MariaDB 10.4.32 / InnoDB |
| Test baseline | 211 tests / 3,028 assertions |
| Development DB | `smart_fitness`, read-only |
| Allowed test schema prefix | `smart_fitness_backend_completion_test_20260830_` |

## 3. Phase Summary

| Phase | Status | Note |
| --- | --- | --- |
| 1. Auth + Account / Role Admin | PASS | 51 focused tests / 470 assertions; full Backend 234 / 3,293. |
| 2. Admin Catalog | PASS | 8 focused tests / 179 assertions; affected regression 37 / 507; full Backend 242 / 3,472. |
| 3. PT Workout Proposal | PASS | 13 focused tests / 174 assertions; affected regression 54 / 621; full Backend 255 / 3,646. |
| 4. Body Measurement + Progress | PASS | 7 focused tests / 107 assertions; affected regression 36 / 428; full Backend 262 / 3,753. |
| 5. Basic Dashboard | PASS | 3 focused tests / 55 assertions; affected regression 72 / 995; full Backend 265 / 3,808. |
| 6. Real LLM Provider | PASS | Gemini REST adapter; 60 focused/affected tests / 734 assertions. |
| 7. Chat Outbox Retry | PASS | Automatic scheduler retry; 38 affected tests / 575 assertions. |
| 8. Documentation / CI / Hygiene | PASS | README/API contract/dataset/CI hoàn tất; full configured Pint, Composer và secret hygiene PASS. |
| 9. Final Acceptance | PASS | Cross-module E2E 7 / 196; full Backend 279 / 3,998 hai lần và random seed 20260830. |

## 4. Phase 1 — Auth + Account / Role Admin

Status: **PASS**.

### 4.1 Design record

| Khía cạnh | Quyết định Phase 1 |
| --- | --- |
| Actor | Public chỉ register/forgot/reset; MEMBER tự đăng ký; Account/Role chỉ active ADMIN. |
| Use case | Register MEMBER, login hiện hữu, forgot/reset password, Admin list/search/detail/status và grant/revoke/regrant Role. |
| Business rule | Role chỉ là actor; register không cấp quyền trả phí và không kích hoạt Membership. Regrant cập nhật cùng hàng `(nguoi_dung_id, vai_tro_id)`. |
| Database | Chỉ dùng `nguoi_dung`, `vai_tro`, `phan_quyen_nguoi_dung`, `ho_so_hoi_vien`, `the_truy_cap`, `yeu_cau_dat_lai_mat_khau`, `nhat_ky_he_thong`; không đổi schema/M001–M060. |
| API | REST JSON theo route convention hiện tại; DTO allow-list, pagination; không có hard-delete. |
| Authorization | Custom `AccessTokenGuard` giữ nguyên; middleware và service đều đọc lại active ADMIN/Role từ DB. |
| Validation | Email trim + NFC + lowercase; password mạnh và confirmed; authority fields bị `prohibited`; status/Role chỉ nhận enum schema. |
| Failure case | Duplicate registration trả conflict/validation an toàn; forgot không phân biệt email; reset invalid/expired/used trả cùng lỗi an toàn; không lộ SQLSTATE. |
| Concurrency | Email UNIQUE là chốt cuối; khóa account trước Role assignment để serialize grant/revoke/regrant; actual independent process tests bắt buộc. |
| Idempotency | Grant active và revoke already-revoked là no-op; không thêm audit thành công. Regrant chỉ xảy ra khi hàng đang revoked. |
| Transaction | Register tạo account + MEMBER Role + profile cùng transaction. Reset password + consume token + revoke sessions cùng transaction. Role transition + audit cùng transaction. |
| Test | Focused API/security/boundary/rollback tests, actual-process registration/Role races, affected regression và seeder security regression. |

MVP một chi nhánh: public registration không nhận `chi_nhanh_id`; Backend chọn chi nhánh `CHI_NHANH_MVP` đang hoạt động. Mã Member do server sinh. Reset thành công thu hồi mọi access token chưa thu hồi để phiên cũ không tiếp tục sử dụng mật khẩu đã thay.

### 4.2 Routes

| Method | Path | Actor | Chức năng |
| --- | --- | --- | --- |
| POST | `/api/auth/register` | Public | Tạo duy nhất Account MEMBER + profile. |
| POST | `/api/auth/forgot-password` | Public | Phát hành reset credential an toàn, response chống enumeration. |
| POST | `/api/auth/reset-password` | Public | Consume token một lần và đổi mật khẩu. |
| GET | `/api/admin/accounts` | ADMIN | List/search/filter/pagination Account. |
| GET | `/api/admin/accounts/{account}` | ADMIN | Account detail DTO an toàn. |
| PATCH | `/api/admin/accounts/{account}/status` | ADMIN | `HOAT_DONG` / `BI_KHOA` / `NGUNG_HOAT_DONG`. |
| PUT | `/api/admin/accounts/{account}/roles/{role}` | ADMIN | Grant hoặc regrant idempotent. |
| DELETE | `/api/admin/accounts/{account}/roles/{role}` | ADMIN | Revoke idempotent. |

Custom `AccessTokenGuard` được giữ nguyên. Các public Auth route có limiter theo IP; khóa limiter không chứa email.

### 4.3 Registration

- MEMBER only: **PASS**.
- Authority fields Role/status/branch/member-code/hash: **PROHIBITED**.
- Email: trim + NFC + lowercase; UNIQUE Database là chốt concurrency.
- Password: hash qua Laravel Hash API; plaintext/hash không xuất hiện trong response.
- Atomic: `nguoi_dung` + `phan_quyen_nguoi_dung` MEMBER + `ho_so_hoi_vien` + system audit cùng transaction.
- Membership/usage activation: **NONE**.
- Hai process đăng ký cùng email: một success, một controlled `ACCOUNT_ALREADY_EXISTS`; một Account/profile/Role/audit vật lý.

### 4.4 Forgot / Reset Password

- Known/unknown email: cùng HTTP status và exact response; unknown không tạo credential.
- Credential: 32 random bytes / 64 hex; Database chỉ lưu SHA-256 ở `ma_bam_xac_nhan`.
- Expiry: concrete server timestamp, mặc định 30 phút, interval `[ngay_tao, het_han_luc)`; đúng `het_han_luc` bị từ chối.
- One-time: `da_su_dung_luc`; request mới thu hồi credential cũ chưa dùng.
- Reset thành công: đổi password hash, consume token, thu hồi các reset token còn lại và mọi `the_truy_cap` chưa thu hồi trong cùng transaction.
- Old password và existing Bearer token: **REJECTED**; new password: **ACCEPTED**.
- MAIL DELIVERY: **CONFIGURATION REQUIRED**. Adapter Laravel mail/notification đã triển khai; local/testing dùng array/fake, production cần cấu hình transport, không thêm SMTP secret.

### 4.5 Admin Account / Role

- Account list/search/detail: paginated, allow-list DTO; không trả password/token/reset hash.
- Status mutation: chỉ enum schema, không hard-delete; disable thu hồi session để re-activate không hồi sinh token cũ.
- Role grant: tạo một hàng khi cặp chưa có.
- Role revoke: cập nhật `thu_hoi_luc`.
- Role regrant: cập nhật cùng `id`, giữ `ngay_tao`, thay `cap_luc`/`nguoi_cap_id`/`ngay_cap_nhat`, đặt `thu_hoi_luc = NULL`.
- Retry ở state đích: `UNCHANGED`, không thêm audit thành công.
- Audit: `CAP_VAI_TRO`, `THU_HOI_VAI_TRO`, `CAP_LAI_VAI_TRO` chứa actor, timestamp, before/after và cùng transaction; forced audit failure rollback Role mutation.
- Role revoke có hiệu lực ngay trên Bearer token hiện hữu vì middleware re-read Database.
- Regrant PT không tạo profile/scope và không phục hồi assignment đã kết thúc.

### 4.6 Concurrency

Đã dùng hai PHP 8.4 process và hai Laravel/MariaDB connection thật cho từng case:

| Race | Kết quả |
| --- | --- |
| Cùng email registration | 1 Account/profile/MEMBER Role/audit; loser controlled. |
| Hai grant cùng Role | `GRANTED` + `UNCHANGED`; 1 hàng, 1 audit. |
| Hai revoke cùng Role | `REVOKED` + `UNCHANGED`; 1 logical transition, 1 audit. |
| Revoke/regrant | Final state tuyến tính theo lock order; audit đúng thứ tự, không duplicate row. |

### 4.7 Verification

- Focused Auth/Authorization/Admin/Concurrency/Seeder Security sau Pint: **PASS — 51 tests / 470 assertions**.
- Full Backend regression: **PASS — 234 tests / 3,293 assertions**.
- Phase 1 actual concurrency: **PASS — 4/4 scenarios**.
- Affected Pint: **PASS**.
- PHP syntax lint: **PASS**.
- Composer validate strict: **PASS**.
- Route inventory: 8 routes mới, Admin routes đều có `auth:api` + `role:ADMIN`.
- `git diff --check`: **PASS**.
- Seeder security regression: **PASS**; production guard và revoked-role fail-closed giữ nguyên.

### 4.8 Phase 1 Gate

- MEMBER REGISTRATION = PASS
- PUBLIC ROLE ESCALATION = BLOCKED
- REGISTRATION CONCURRENCY = PASS
- FORGOT PASSWORD = PASS
- ACCOUNT ENUMERATION = BLOCKED
- RESET PASSWORD = PASS
- RESET TOKEN HASHED = PASS
- RESET TOKEN ONE-TIME = PASS
- RESET TOKEN EXPIRY = PASS
- ADMIN ACCOUNT API = PASS
- NON-ADMIN ADMIN-API = BLOCKED
- ROLE GRANT = PASS
- ROLE REVOKE = PASS
- ROLE REGRANT = PASS
- ROLE REGRANT SAME ROW = PASS
- ROLE AUDIT = PASS
- ROLE AUDIT ATOMIC = PASS
- ROLE CONCURRENCY = PASS
- OLD SECURITY FIX = PASS

**PHASE 1 AUTH/ACCOUNT/ROLE = PASS**

## 5. Phase 2 — Admin Catalog

Status: **PASS**.

### 5.1 Design record

| Khía cạnh | Quyết định Phase 2 |
| --- | --- |
| Actor | Chỉ Account đang hoạt động có Role ADMIN hiệu lực được đọc toàn bộ catalog quản trị hoặc mutation. Member/PT/Receptionist bị chặn ở middleware và service revalidation. |
| Use case | Quản lý gói tập + quyền lợi, dụng cụ/nhóm cơ/bài tập và cây giáo án mẫu; chỉ khóa/ngừng sử dụng, không có hard-delete API. |
| Business rule | Không suy quyền từ tên gói. Mọi hàng dụng cụ của bài tập mang semantics AND. Catalog mới chỉ ảnh hưởng lần mua/Plan tạo sau; snapshot kỳ Membership, Plan Version và Workout Session lịch sử không bị rewrite. |
| Database | Dùng nguyên schema `goi_tap`, `quyen_loi_goi_tap`, `dung_cu`, `nhom_co`, `bai_tap`, hai bảng liên kết và cây `giao_an_mau`; không tạo/sửa migration. |
| API | REST JSON allow-list dưới `/api/admin`; member package/template query hiện hữu tiếp tục chỉ thấy trạng thái được phép. Không có DELETE catalog endpoint. |
| Authorization | `auth:api` + `role:ADMIN`; transaction mutation khóa và kiểm tra lại actor ADMIN. Catalog package bị giới hạn theo `chi_nhanh_id` của actor. |
| Validation | Form Request cấm branch/creator/version/FK authority; kiểm tra enum, giới hạn vật lý, quyền lợi AI, ít nhất một quyền trả phí, ID liên kết, thứ tự và reps. Code catalog bất biến sau khi tạo. |
| Failure case | ID không tồn tại/khác scope trả lỗi an toàn; duplicate code trả conflict có mã ổn định; không lộ SQLSTATE. |
| Concurrency | Mutation nhiều bảng khóa actor + catalog parent; quyền lợi, liên kết bài tập và cây giáo án thay đổi trong một transaction. UNIQUE Database là chốt cuối cho code/order. Không tự thêm optimistic-lock contract. |
| Idempotency | CRUD quản trị không nhận idempotency key; request retry là một mutation mới. Update serialize bằng row lock và version nội dung/cấu hình tăng theo mutation nội dung. |
| Transaction | Tạo package + benefits, thay benefit, thay liên kết bài tập và thay toàn bộ cây template là atomic. |
| Test | API happy/invalid/security/mass-assignment, snapshot cũ-mới, AND semantics, history immutability, duplicate/race, fresh-install catalog creation và full regression. |

### 5.2 Scope boundary

- Không seed giá/quyền thương mại chưa được phê duyệt.
- Không tạo OR group cho dụng cụ.
- Không chỉnh `ky_han_hoi_vien`, Plan Version hoặc Workout Session khi Admin sửa catalog.
- Không triển khai FE hoặc API hard-delete.

### 5.3 API và validation

| Nhóm | Routes quản trị |
| --- | --- |
| Membership Plan | `GET/POST /api/admin/packages`, `GET/PATCH /api/admin/packages/{id}` |
| Benefits | `PUT /api/admin/packages/{id}/benefits` |
| Equipment | `GET/POST /api/admin/equipment`, `PATCH /api/admin/equipment/{id}` |
| Muscle Group | `GET/POST /api/admin/muscle-groups`, `PATCH /api/admin/muscle-groups/{id}` |
| Exercise | `GET/POST /api/admin/exercises`, `GET/PATCH /api/admin/exercises/{id}` |
| Workout Template | `GET/POST /api/admin/workout-templates`, `GET/PATCH /api/admin/workout-templates/{id}` |

- Code kỹ thuật được chuẩn hóa uppercase khi tạo và không có contract đổi code sau đó.
- Package quyền AI tuân thủ CHECK vật lý; ít nhất một quyền trả phí phải bật. Chat PT và số buổi PT trực tiếp được cấu hình độc lập.
- Exercise nhận tập `equipment_ids`; mỗi hàng được trả cùng `equipment_semantics = AND`, không có OR group.
- Template yêu cầu số buổi khớp số ngày, thứ tự không trùng, reps hợp lệ và chỉ tham chiếu bài đang hoạt động tại mutation.
- Branch/creator/version/FK authority đều bị cấm trong Form Request; DTO response dùng allow-list.
- Không có route DELETE cho Package, Exercise hoặc Workout Template; state schema là cơ chế ngừng sử dụng.

### 5.4 Snapshot và history preservation

- Một kỳ đã mua và xác nhận thanh toán được chụp trước khi Admin đổi tên/giá/thời hạn/Gym/AI limit/Chat/PT quota.
- Sau hai mutation catalog, toàn bộ chín trường snapshot kiểm tra trên kỳ cũ khớp bit-for-bit; kỳ mua mới nhận `phien_ban_goi = 3`, giá/thời hạn/quyền mới.
- Template update thay cây catalog atomically nhưng không đổi `bai_tap_trong_ke_hoach`, `phien_tap`, `bai_tap_trong_phien` hoặc `hiep_tap` đã hoàn thành.
- Exercise deactivation giữ hàng `bai_tap`; Plan/Session tiếp tục dùng snapshot đã vật lý hóa.

### 5.5 Concurrency

Các race dùng hai PHP 8.4 process và hai Laravel/MariaDB connection thật:

| Race | Evidence |
| --- | --- |
| Hai benefit update | Parent/package row lock serialize; configuration version nhận 2 rồi 3, final benefit là một payload hoàn chỉnh. |
| Exercise status/update | Cả thay đổi tên và ngừng sử dụng được giữ, content version chỉ tăng cho nội dung; không lost partial update. |
| Hai template mutation | Cả goal và level được giữ, content version nhận 2 rồi 3. |

### 5.6 Verification

- Focused Admin Catalog + actual-process concurrency: **PASS — 8 tests / 179 assertions**.
- Affected Package/Payment/Workout regression sau Pint: **PASS — 37 tests / 507 assertions**.
- Full Backend regression: **PASS — 242 tests / 3,472 assertions**.
- Affected Pint: **PASS**.
- PHP syntax lint và route discovery: **PASS**; 19 Admin Catalog routes, toàn bộ nằm sau `auth:api` + `role:ADMIN`.
- `git diff --check`: **PASS**.
- Test schema sau suite trở về đúng seed baseline; development database counts không đổi.

### 5.7 Phase 2 Gate

- ADMIN MEMBERSHIP PLAN API = PASS
- ADMIN BENEFIT API = PASS
- CATALOG SNAPSHOT IMMUTABILITY = PASS
- ADMIN EXERCISE API = PASS
- EQUIPMENT AND SEMANTICS = PASS
- ADMIN WORKOUT TEMPLATE API = PASS
- HISTORY PRESERVATION = PASS
- NON-ADMIN MUTATION = BLOCKED
- MASS ASSIGNMENT = BLOCKED
- FRESH INSTALL CATALOG CREATION = PASS

**PHASE 2 ADMIN CATALOG = PASS**

## 6. Phase 3 — PT Workout Proposal

Status: **PASS**.

### 6.1 Design record

| Khía cạnh | Quyết định Phase 3 |
| --- | --- |
| Actor | PT đang hoạt động tạo Proposal/ghi chú cho đúng Member thuộc chính phân công hiện tại; chỉ Member sở hữu được preview, xác nhận hoặc từ chối. |
| Use case | Tạo Plan mới hoặc đề xuất đổi template/ngày/bài/sets/reps cho tương lai; Member preview rồi confirm/reject; ghi chú tư vấn lưu riêng. |
| Business rule | Q13: không cần Membership, Chat entitlement hay quota buổi PT. Create/confirm/reject không activation, usage, PT/AI quota hoặc LLM. Proposal bất biến và PT không Apply trực tiếp. |
| Database | Dùng nguyên `de_xuat_ke_hoach_tap`, `ghi_chu_huan_luyen`, `phan_cong_huan_luyen_vien`, Plan/Version/Schedule, `nhat_ky_he_thong`, `yeu_cau_chong_lap`; không đổi M001–M060. |
| API | PT create/list Proposal và append note theo Member; Member list/preview/confirm/reject Proposal, đọc note của chính mình. Không có edit/delete Proposal hoặc note. |
| Authorization | Route role + service revalidation dưới khóa. Backend tự chọn exact assignment, base Plan/version và Member; IDOR che bằng 404. Confirm kiểm tra lại đúng assignment nguồn, không dùng assignment mới để cứu Proposal cũ. |
| Validation | Payload allow-list và cấu trúc chuẩn hóa; server sinh logical UUID, chụp template name, kiểm tra ngày tương lai, bài hoạt động và toàn bộ dụng cụ theo AND. Apply kiểm tra lại hash, schema payload, marker, template, bài và dụng cụ. |
| Failure case | Hết TTL chuyển `HET_HAN`; assignment/base/profile/payload không còn hợp lệ chuyển `XUNG_DOT`; lỗi trả mã an toàn, không lộ SQLSTATE. |
| Concurrency | Thứ tự khóa Member → Proposal/assignment/Plan. Same-Proposal confirm chỉ một mutation; AI/PT cùng base chỉ một winner, loser stale. |
| Idempotency | `yeu_cau_chong_lap` tách scope create/confirm/reject; cùng key+cùng payload replay, khác payload conflict; trạng thái terminal ngăn version/audit lặp. |
| Transaction | Create + audit + idempotency atomic. Confirm gồm revalidation, Plan/version, lịch tương lai, marker, Proposal, audit và idempotency trong một transaction. Reject + audit + idempotency atomic. |
| Test | Q13/no-side-effect, ownership, assignment lifecycle, immutable/TTL/stale, new/existing Plan, history, note, retry, actual-process confirm và AI/PT race. |

### 6.2 Scope boundary

- Không triển khai PT Booking, sửa lịch sử hoàn thành hoặc API PT Apply trực tiếp.
- Ghi chú chỉ append text và liên kết tùy chọn tới Plan/Session cùng Member; không chứa mutation kê tập.
- TTL PT dùng cùng policy server tập trung đang điều khiển Proposal AI; preview/retry không đổi `het_han_luc`.

### 6.3 API và authorization

| Method | Path | Actor | Chức năng |
| --- | --- | --- | --- |
| GET | `/api/pt/members/{member}/proposals` | PT | Danh sách Proposal do chính PT tạo cho Member thuộc phân công hiện tại. |
| POST | `/api/pt/members/{member}/proposals` | PT | Tạo Proposal bất biến cho Plan mới hoặc phiên bản kế tiếp. |
| GET | `/api/pt/members/{member}/notes` | PT | Đọc ghi chú trong đúng phân công hiện tại. |
| POST | `/api/pt/members/{member}/notes` | PT | Append ghi chú tư vấn; Plan/Session tùy chọn phải thuộc cùng Member. |
| GET | `/api/pt/proposals` | MEMBER | Danh sách Proposal của chính Member. |
| GET | `/api/pt/proposals/{proposal}` | MEMBER | Preview Proposal và Plan hiện tại mà không kéo dài TTL. |
| POST | `/api/pt/proposals/{proposal}/confirm` | MEMBER | Xác nhận và Apply qua Workout services dùng chung. |
| POST | `/api/pt/proposals/{proposal}/reject` | MEMBER | Từ chối Proposal terminal, không đổi Plan/lịch. |
| GET | `/api/pt/notes` | MEMBER | Đọc lịch sử ghi chú của chính mình, kể cả sau khi phân công kết thúc. |

- PT/Member khác hoặc PT cũ nhận response che IDOR; payload không được tự gán Member, assignment, base Plan/version, trạng thái, hash, creator hoặc TTL.
- Create và confirm đọc lại PT Role/profile, toàn bộ phân công hiện tại và đúng hàng phân công nguồn dưới khóa. Phân công PT mới không phục hồi quyền cho Proposal của PT cũ.
- Q13 được kiểm chứng với Member chưa có Membership, không có Chat PT và quota buổi PT trực tiếp bằng 0.

### 6.4 Proposal lifecycle và Apply

- Proposal dùng payload chuẩn `pt-workout-proposal-v1`; logical UUID, template snapshot và SHA-256 integrity do server tạo.
- TTL mặc định 24 giờ theo interval `[ngay_tao, het_han_luc)`: trước boundary một microsecond còn hợp lệ; đúng boundary chuyển `HET_HAN` và bị chặn.
- Thay đổi assignment nguồn, base Plan/version, marker hồ sơ/Plan, hash/cấu trúc, template, bài tập hoặc bất kỳ dụng cụ bắt buộc theo semantics AND làm Proposal chuyển `XUNG_DOT` và không Apply.
- Không có Plan: confirm tạo Plan + Version đầu tiên. Đã có Plan: confirm tạo phiên bản kế tiếp; phiên bản cũ không bị sửa.
- `WorkoutPlanService`, `WorkoutScheduleService` và `WorkoutPlanQueryService` được tái sử dụng; không sao chép business logic Workout.
- Chỉ lịch tương lai thay thế được ở trạng thái `CHUA_TAP` được sinh lại. `DANG_TAP`, `HOAN_THANH`, `BO_QUA`, Session hoàn thành, bài tập trong Session và hiệp tập giữ nguyên.
- Confirm/reject terminal và `yeu_cau_chong_lap` bảo đảm retry cùng key/payload không tạo thêm version, lịch hoặc audit. Khác payload trên cùng key bị conflict.
- Create/confirm/reject không gọi LLM, không tạo `su_dung_quyen_loi`, không giữ/trừ AI quota, không trừ buổi PT và không kích hoạt Membership.

### 6.5 Audit, rollback và concurrency

- Create, confirm, reject, conflict/expiry transition và ghi chú đều có audit actor/entity/before-after phù hợp.
- Forced late audit failure rollback toàn bộ Proposal/Plan/Version/Schedule/marker/idempotency; không để mutation một phần.
- Các race sau dùng hai PHP 8.4 process và hai Laravel/MariaDB connection thật:

| Race | Kết quả |
| --- | --- |
| Hai confirm cùng Proposal | Một business mutation; request còn lại replay cùng kết quả, không duplicate version/lịch/audit. |
| Hai PT Proposal cùng base | Một winner; loser chuyển stale/`XUNG_DOT`. |
| AI Proposal và PT Proposal cùng base | Một winner; loser stale/`XUNG_DOT`; không lost update. |

### 6.6 Verification

- Focused PT Proposal API + actual-process concurrency: **PASS — 13 tests / 174 assertions**.
- Affected PT Proposal, AI Apply, Workout, PT assignment/direct regression: **PASS — 54 tests / 621 assertions**.
- Full Backend regression trên `smart_fitness_backend_completion_test_20260830_p1_a31`: **PASS — 255 tests / 3,646 assertions**.
- Affected Pint `--test`: **PASS**.
- PHP syntax lint cho toàn bộ file Phase 3: **PASS**.
- Route discovery: **PASS**; 9 route Phase 3, đúng middleware Role.
- `git diff --check`: **PASS**.
- Sau suite, schema test trở về seed baseline: 60 migrations, 8 users, 8 Role assignments, 1.324 exercises; Proposal/Plan/Version/Schedule/idempotency/audit tạm đều bằng 0.
- Development DB read-only có cùng các count kiểm chứng và không có dữ liệu Phase 3 phát sinh.

### 6.7 Phase 3 Gate

- PT PROPOSAL CREATE = PASS
- PT PROPOSAL PREVIEW = PASS
- MEMBER CONFIRM = PASS
- MEMBER REJECT = PASS
- Q13 = PASS
- ASSIGNMENT REVALIDATION = PASS
- STALE BASE / TTL VALIDATION = PASS
- PROPOSAL IMMUTABILITY = PASS
- IDEMPOTENCY = PASS
- ACTUAL-PROCESS CONCURRENCY = PASS
- AUDIT ATOMICITY = PASS
- MEMBERSHIP ACTIVATION = NOT PERFORMED
- PT QUOTA = NOT CONSUMED
- AI QUOTA / LLM = NOT CONSUMED / NOT CALLED
- WORKOUT HISTORY = IMMUTABLE

**PHASE 3 PT WORKOUT PROPOSAL = PASS**

## 7. Phase 4 — Body Measurement + Progress Tracking

Status: **PASS**.

### 7.1 Design record

| Khía cạnh | Quyết định Phase 4 |
| --- | --- |
| Actor | MEMBER đang hoạt động tạo/đọc số đo và Progress của chính mình. PT đang hoạt động chỉ đọc Progress của Member thuộc đúng phân công hiện tại. Không mở quyền cho Admin/Receptionist. |
| Use case | Append số đo cơ thể, xem history/latest, BMI dẫn xuất, tổng quan số buổi/tần suất/weight trend và lịch sử sets/reps/weight theo Exercise. |
| Business rule | Progress độc lập Membership; không activation/usage/quota. Chỉ Session `HOAN_THANH` là nguồn Workout Progress bất biến. Không chẩn đoán hoặc phân tích khoa học thể thao nâng cao. |
| Database | Dùng nguyên `chi_so_co_the`, `ho_so_hoi_vien`, `phien_tap`, `bai_tap_trong_phien`, `hiep_tap`, `bai_tap`, `phan_cong_huan_luyen_vien`; không thêm bảng/cột/migration. Không lưu BMI trùng. |
| API | Member: `POST/GET /api/progress/body`, `GET /api/progress/body/latest`, `GET /api/progress/overview`, `GET /api/progress/exercises/{exercise}`. PT: ba read endpoint tương ứng dưới `/api/pt/members/{member}/progress`. |
| Authorization | Member ID luôn lấy từ principal. PT route revalidate active Account/Role/profile và interval phân công `[start,end)`; PT cũ/foreign bị che bằng 404. |
| Validation | Chỉ nhận fields có nguồn schema: measured time, weight, height, optional waist/note và stable entry UUID. Cấm authority/BMI/system fields. Period `from/to` theo business date, tối đa 366 ngày; list/Exercise result có limit. |
| Failure case | Không có số đo trả `latest = null`, `bmi = null`, trạng thái unavailable; không đoán chiều cao. Exercise tồn tại nhưng chưa có history trả danh sách rỗng. Lỗi ownership không lộ tài nguyên hoặc SQLSTATE. |
| Concurrency | Create khóa Member trước khi đọc/ghi; UNIQUE `(hoi_vien_id, ma_lan_ghi)` là chốt cuối. Read dùng aggregate/query có giới hạn, không khóa history. |
| Idempotency | `entry_id` ánh xạ `ma_lan_ghi`: cùng ID và canonical payload replay cùng hàng; cùng ID khác payload trả conflict; không thêm audit thành công trùng. |
| Transaction | Measurement + audit cùng transaction. Progress hoàn toàn read-only, không sửa Profile/Workout/Membership. |
| Test | Measurement create/history/latest/idempotency, BMI/missing height, completed count/frequency/exercise sets-reps-weight, no/expired Membership, current/old/foreign PT, IDOR, no side effects và history preservation. |

### 7.2 Metric definitions

- BMI = `weight_kg / (height_cm / 100)^2`, làm tròn hai chữ số chỉ ở response; không lưu và không phân loại sức khỏe.
- Completed sessions và Exercise history chỉ đếm `phien_tap.trang_thai = HOAN_THANH` trong period business-date có giới hạn.
- Tần suất là `completed_sessions * 7 / inclusive_period_days`; đồng thời trả số ngày có ít nhất một Session hoàn thành để định nghĩa không mơ hồ.
- Weight trend là chuỗi số đo thật theo `do_luc`; không nội suy hoặc dự báo.

### 7.3 API và validation

| Method | Path | Actor | Chức năng |
| --- | --- | --- | --- |
| POST | `/api/progress/body` | MEMBER | Append số đo cơ thể, idempotent theo `entry_id`. |
| GET | `/api/progress/body` | MEMBER | History theo `measured_at DESC, id DESC`, cursor ghép có giới hạn. |
| GET | `/api/progress/body/latest` | MEMBER | Số đo thực tế mới nhất hoặc `null`. |
| GET | `/api/progress/overview` | MEMBER | Số buổi, tần suất, latest body và weight trend trong period. |
| GET | `/api/progress/exercises/{exercise}` | MEMBER | Lịch sử Exercise từ Session hoàn thành và các set thật. |
| GET | `/api/pt/members/{member}/progress/overview` | PT | Tổng quan Member đang được phân công. |
| GET | `/api/pt/members/{member}/progress/body` | PT | Body history Member đang được phân công. |
| GET | `/api/pt/members/{member}/progress/exercises/{exercise}` | PT | Exercise history Member đang được phân công. |

- Measurement chỉ nhận `measured_at`, `weight_kg`, `height_cm`, optional `waist_cm`, `notes` và UUID `entry_id`; Member/Account/BMI/system timestamps bị cấm.
- Giới hạn số học theo khả năng DECIMAL vật lý và CHECK `> 0`; không thêm trường đo hoặc phân loại sức khỏe ngoài schema.
- Cursor body dùng cặp `before_measured_at/before_id`, đúng index `(hoi_vien_id, do_luc, id)`; list tối đa 100 hàng.
- Progress period mặc định 30 ngày và tối đa 366 ngày; Exercise history tối đa 100 lần thực hiện.

### 7.4 Data integrity và security

- Hai lần gửi cùng `entry_id` và canonical payload trả cùng hàng với `replayed = true`; khác payload trả `BODY_MEASUREMENT_IDEMPOTENCY_CONFLICT`.
- Measurement + audit lọc dữ liệu nhạy cảm nằm trong cùng transaction; forced late audit failure rollback số đo. Retry không tạo audit thành công trùng.
- BMI được tính lại từ từng snapshot chiều cao/cân nặng và không có cột lưu. Chưa có measurement trả `latest_body_measurement = null`, `bmi = null` theo trạng thái unavailable.
- Member không có route nhận Member ID. PT Role/profile/phân công được revalidate mỗi request; foreign PT, PT cũ và Member gọi PT route đều bị chặn.
- Query Progress không đọc/ghi Membership. Không Membership hoặc kỳ `HET_HAN` vẫn đọc được, không activation, usage, PT quota hay AI quota.
- Chỉ `HOAN_THANH` được tổng hợp; `DANG_TAP` bị loại. Các truy vấn không đổi Session/Exercise/Set snapshot.

### 7.5 Verification

- Focused Body Measurement/Progress/PT access: **PASS — 7 tests / 107 assertions**.
- Affected Progress/Workout/PT Assignment/PT Proposal regression: **PASS — 36 tests / 428 assertions**.
- Full Backend rerun trên schema cô lập: **PASS — 262 tests / 3,753 assertions**.
- Một lần full đầu tiên gặp PT Chat boundary 401 không tái hiện; case riêng **1 / 23**, toàn file PT Chat **19 / 398**, và full rerun đều PASS. Không sửa Chat để che nhiễu.
- Affected Pint `--test`, PHP syntax lint, route discovery và `git diff --check`: **PASS**.
- 8 route Phase 4 có đúng middleware `role:MEMBER` hoặc `role:PT`; không có Admin/Receptionist access.
- Schema test sau suite trở về seed baseline; `chi_so_co_the`, `phien_tap`, `bai_tap_trong_phien`, `hiep_tap`, `nhat_ky_he_thong` đều 0.

### 7.6 Phase 4 Gate

- BODY MEASUREMENT = IMPLEMENTED
- WEIGHT TRACKING = PASS
- BMI = PASS
- WORKOUT PROGRESS = PASS
- EXERCISE PROGRESS = PASS
- MEMBERSHIP INDEPENDENCE = PASS
- PT RESOURCE AUTHORIZATION = PASS
- HISTORY PRESERVATION = PASS

**PHASE 4 BODY MEASUREMENT + PROGRESS TRACKING = PASS**

## 8. Phase 5 — Basic Dashboard

Status: **PASS**.

### 8.1 Design record

| Khía cạnh | Quyết định Phase 5 |
| --- | --- |
| Actor | Chỉ Account `HOAT_DONG` có Role ADMIN hiệu lực. MEMBER/PT/RECEPTIONIST bị chặn. |
| Use case | Một endpoint tổng quan cơ bản cho Member/PT, Membership, check-in, Workout, payment và phân công PT. |
| Business rule | MVP một chi nhánh; mọi aggregate scope theo `chi_nhanh_id` của Admin. Không phân tích đa chi nhánh, không gọi lifecycle mutation và không tạo metric doanh thu. |
| Database | Chỉ aggregate từ Account/Role/Profile, `ky_han_hoi_vien`, `dang_ky_goi_tap`, `lich_su_vao_phong_tap`, Workout history, payment/order và PT assignment. Không migration/index mới. |
| API | `GET /api/admin/dashboard?from=YYYY-MM-DD&to=YYYY-MM-DD`, mặc định 30 ngày, tối đa 366 ngày. |
| Authorization | Route `auth:api` + `role:ADMIN`; service dùng lại `AdminActorGuard` để đọc lại trạng thái/Role và lấy đúng branch/timezone. |
| Validation | `from/to` phải cùng có, đúng thứ tự và period tối đa 366 ngày. Không nhận branch ID hoặc metric selector từ client. |
| Failure case | Admin thiếu branch/timezone hợp lệ trả lỗi kiểm soát. Không có dữ liệu trả số 0; không lộ SQLSTATE hoặc raw entity. |
| Concurrency | Dashboard là snapshot read-only tại từng statement; không khóa bảng hoặc ghi dữ liệu. Trạng thái đồng thời có thể thay đổi giữa aggregate và được gắn `generated_at`. |
| Idempotency | Không áp dụng cho GET read-only. |
| Transaction | Không transaction mutation; không gọi entitlement/lifecycle/provider. Mọi metric dùng COUNT/aggregate SQL có bounds. |
| Test | Metric definitions, branch isolation, timezone/day boundary, period bounds, ADMIN/non-ADMIN, no sensitive fields, no mutation và query-count/N+1 guard. |

### 8.2 Metric definitions

- Active Member Account: account hoạt động + Role MEMBER chưa thu hồi + hồ sơ Member, thuộc branch Admin.
- Active PT Account: account hoạt động + Role PT chưa thu hồi + hồ sơ PT `HOAT_DONG`, thuộc branch Admin.
- Membership: số kỳ `DANG_HOAT_DONG` và `CHO_KICH_HOAT`, scope qua chuỗi `dang_ky_goi_tap.chi_nhanh_id`.
- Check-in today: `vao_phong_luc` trong `[local midnight, next local midnight)` của branch.
- Completed Workout today/in period: Session `HOAN_THANH`, theo `ket_thuc_luc` và branch của Member.
- Successful payment in period: lần thanh toán `THANH_CONG` theo `xac_nhan_luc`; reconciliation now: lần thanh toán đang `CAN_DOI_SOAT`. Không cộng tiền.
- Active PT assignment: interval `[ngay_bat_dau, ngay_ket_thuc)` chứa `generated_at`, theo branch của Member.

### 8.3 API, security và performance

- `GET /api/admin/dashboard` là endpoint duy nhất; middleware `auth:api` + `role:ADMIN`, sau đó `AdminActorGuard` đọc lại active Role từ Database.
- `branch_id` lấy từ actor và bị cấm trong query. Tài khoản/profile/Membership/Workout/payment/assignment ở branch khác không được cộng.
- Default period là 30 ngày local; explicit period tối đa 366 ngày. Local midnight được đổi sang UTC trước truy vấn nên boundary `Asia/Ho_Chi_Minh` không lệch ngày.
- Endpoint chỉ trả ID branch, timezone, period và các số đếm; không trả Account/email, nội dung Chat, AI prompt, auth data, checkout URL, raw provider payload hoặc số tiền nhận.
- Mỗi metric là aggregate SQL hữu hạn. Focused test ghi nhận tổng query dưới guard 25 statement, không tăng theo số hàng và không N+1.
- GET không thay đổi bất kỳ nguồn Dashboard; snapshot hash của 11 bảng trước/sau response khớp.

### 8.4 Verification

- Focused Admin Dashboard: **PASS — 3 tests / 55 assertions**.
- Dashboard + Progress combined: **PASS — 10 tests / 162 assertions**.
- Affected Admin/Account/Catalog/Membership/Payment/Gym/Workout/Progress regression: **PASS — 72 tests / 995 assertions**.
- Full Backend regression: **PASS — 265 tests / 3,808 assertions**.
- Affected Pint `--test`, PHP syntax lint, route discovery và `git diff --check`: **PASS**.
- Timezone boundary kiểm chứng: dữ liệu lúc 16:59 UTC thuộc ngày local trước; tại 17:01 UTC `today` chuyển sang ngày local kế tiếp trong khi period count vẫn giữ.
- Schema test trở về seed baseline; development DB chỉ đọc và counts khớp.

### 8.5 Phase 5 Gate

- ADMIN BASIC DASHBOARD = PASS
- ADMIN AUTHORIZATION = PASS
- NON-ADMIN = BLOCKED
- TIMEZONE = PASS
- QUERY BOUNDS = PASS
- NO SENSITIVE DATA = PASS

**PHASE 5 BASIC DASHBOARD = PASS**

## 9. Phase 6 — Real LLM Provider / AI Runtime

Status: **PASS**.

### 9.1 Provider decision and official API verification

Chủ dự án đã chọn chính thức `GEMINI`; blocker `PROVIDER_SELECTION_REQUIRED` được gỡ. Tài liệu Google AI chính thức được kiểm tra ngày 2026-08-31:

- Endpoint: `POST https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent`.
- Authentication: header Backend-only `x-goog-api-key`; key không nằm trong URL, response, log hoặc client config.
- Structured output: `generationConfig.responseFormat.text` với `mimeType=application/json` và JSON Schema.
- Response text: `candidates[].content.parts[].text`; usage từ `usageMetadata`.
- Model mặc định: `gemini-3.1-flash-lite`, model ổn định hướng tới tốc độ, lưu lượng và chi phí thấp; luôn cấu hình qua `AI_MODEL`.
- Sources: `https://ai.google.dev/api`, `https://ai.google.dev/gemini-api/docs/generate-content/text-generation`, `https://ai.google.dev/gemini-api/docs/generate-content/structured-output`, `https://ai.google.dev/gemini-api/docs/models`.

### 9.2 Phase 6 design record

| Khía cạnh | Quyết định Phase 6 |
| --- | --- |
| Actor | Chỉ luồng AI MEMBER hiện hữu gọi provider sau entitlement, candidate preflight và quota reservation. |
| Use case | Workout Planning/Adjustment/Replacement có structured output; Apply dùng Proposal đã lưu và không gọi Gemini. |
| Business rule | Gemini chỉ chọn/sắp xếp candidate Backend cấp. `AiStructuredOutputValidator`, candidate/equipment revalidation và Apply validator vẫn là authority. |
| Database | Giữ nguyên 52 bảng và M001–M060. Adapter không ghi DB; orchestration hiện hữu ghi request/call/proposal/quota. |
| API | Không thêm public endpoint. `AI_PROVIDER=gemini` chọn adapter; `unavailable` giữ fail-closed hiện hữu; provider khác bị từ chối. |
| Authorization | API key chỉ đọc từ Backend environment. Context allow-list không chứa account, token, payment, DB secret hoặc Membership ledger. |
| Validation | JSON Schema ở transport và canonical Backend validator ở domain. Provider output luôn là untrusted input. |
| Failure case | Missing key, timeout/network, 400, 401/403, 429, 5xx, malformed/missing/refused output đều map sang lỗi an toàn; không trả raw provider response. |
| Concurrency | Giữ nguyên Q03 actual-process tests; một business request chỉ reserve một lượt. Apply concurrency không liên quan provider. |
| Idempotency | Idempotency hiện hữu tái sử dụng request kết thúc; provider failure replay không gọi Gemini lần hai. |
| Transaction | Network call xảy ra sau transaction reservation/activation commit và trước transaction finalize/refund. Không giữ row lock khi chờ Gemini. |
| Test | `Http::fake` contract tests, focused AI/quota/proposal/apply/workout/membership regression; không tự gọi mạng thật. |

### 9.3 Implementation and verification

- `GeminiWorkoutAiProvider` dùng Laravel HTTP client, không thêm SDK/dependency; model/base URL/timeout/output tokens đều qua config.
- `AI_PROVIDER=gemini` bind Gemini; `unavailable` bind adapter fail-closed cũ; provider lạ ném lỗi an toàn `AI_PROVIDER_UNSUPPORTED`.
- Missing key/model/base URL không hợp lệ dừng với `AI_PROVIDER_CONFIGURATION_MISSING`; không có silent fake fallback.
- Header `x-goog-api-key` dùng placeholder test; không có `Authorization`, key query-string, log hoặc client exposure.
- Prompt chia system rule và context JSON; chỉ profile tập luyện, ngày rảnh, dụng cụ và candidate bounded được gửi. Account/Auth/Payment/Membership ledger/current Plan ID không được gửi.
- Structured JSON được yêu cầu bằng JSON Schema canonical, parse không qua regex/code fence, sau đó vẫn qua `AiStructuredOutputValidator` và revalidation candidate/equipment AND.
- Timeout, DNS/network, 400, 401/403, 429, 500/502/503/504, malformed JSON, missing/empty content và safety block đều map lỗi an toàn; raw provider body không xuất hiện trong exception/API.
- Network call vẫn nằm sau transaction reserve/activation commit. Success chuyển quota `GIU_CHO → DA_TINH`; terminal technical failure chuyển `GIU_CHO → DA_TRA` đúng một lần và không rollback Membership clock.
- Apply test thay binding sang Gemini và bật `Http::preventStrayRequests`: hai lần Apply gửi **0 HTTP request**, không charge quota/usage/activation lần hai.
- Gemini unit contract: **PASS — 6 tests / 56 assertions**.
- Gemini + AI Request/Quota/Proposal/Apply/Workout/Membership affected regression, gồm actual-process quota và Apply concurrency: **PASS — 60 tests / 734 assertions**.
- Phase 6 affected Pint `--test`, PHP syntax lint và `git diff --check`: **PASS**.
- Migration/schema changes: **NONE**. Composer dependency changes: **NONE**.
- Local `.env` không có tên biến `GEMINI_API_KEY`; **REAL GEMINI NETWORK SMOKE = NOT PERFORMED**, đúng policy và không phải lỗi Phase 6.

### 9.4 Phase 6 Gate

- AI PROVIDER = GEMINI
- GEMINI ADAPTER = IMPLEMENTED
- GEMINI CONFIG = IMPLEMENTED
- GEMINI KEY BACKEND ONLY = PASS
- GEMINI SECRET COMMITTED = NO
- MODEL CONFIGURABLE = PASS
- GEMINI MODEL SELECTED = `gemini-3.1-flash-lite`
- WHY = stable, low-latency, cost-effective, high-volume and structured-output capable
- CURRENT GEMINI API VERIFIED = PASS
- STRUCTURED OUTPUT = PASS
- CANONICAL AI SCHEMA = PRESERVED
- LLM OUTPUT = UNTRUSTED
- CANDIDATE AUTHORITY = BACKEND
- UNKNOWN EXERCISE / OUTSIDE CANDIDATE = BLOCKED
- EQUIPMENT REVALIDATION = PASS
- TIMEOUT / 429 / 5XX / MALFORMED OUTPUT = CONTROLLED
- MISSING CONFIG = FAIL CLOSED
- PROVIDER CALL OUTSIDE LONG TRANSACTION = PASS
- AI QUOTA SUCCESS = EXACT ONCE
- AI PROVIDER FAILURE REFUND = EXACT ONCE
- MEMBERSHIP ACTIVATION SEMANTICS = PASS
- AI PROPOSAL IMMUTABILITY = PASS
- AI APPLY GEMINI CALLS = 0
- AI APPLY SECOND QUOTA = 0
- DATABASE SCHEMA CHANGE = NONE
- FOCUSED AI TESTS / AFFECTED REGRESSION = PASS

**PHASE 6 REAL LLM PROVIDER / AI RUNTIME = PASS**

## 10. Phase 7 — PT Chat Outbox Retry Hardening

Status: **PASS**.

### 10.1 Design record

| Khía cạnh | Quyết định Phase 7 |
| --- | --- |
| Actor | Scheduler/CLI nội bộ, không thêm public retry API và không nhận actor/participant từ client. |
| Use case | Phát lại outbox `CHO_THU_LAI` đến hạn; đồng thời thu hồi khoảng trống crash bằng cách xử lý `CHO_PHAT` chưa được phát. |
| Business rule | Message/usage/activation đã commit là source of truth. Retry chỉ phát event từ message đã lưu, không reauthorize sender cũ, không tạo message/usage và không kích hoạt Membership. Q05 API/channel scope giữ nguyên. |
| Database | Chỉ dùng `su_kien_phat_tin_nhan` và index `(trang_thai, thu_lai_luc)` hiện hữu; không migration/trạng thái mới. |
| API | Không thêm route HTTP. Command nội bộ chạy batch bounded và scheduler mỗi phút với overlap guard. |
| Authorization | Không có endpoint user-facing. Event được dựng lại từ exact persisted message/conversation tuple. |
| Validation | Chỉ `CHO_PHAT` hoặc `CHO_THU_LAI` đến hạn; `DA_PHAT` là no-op. Batch/delay được clamp từ server config. |
| Failure case | Transport failure giữ `CHO_THU_LAI`, tăng attempt, đặt exponential backoff có cap và lưu safe error code, không raw exception. |
| Concurrency | MariaDB advisory lock theo message serialize hai worker mà không mở transaction/row lock lúc gọi transport; client vẫn dedupe theo stable `message_id` trong mô hình at-least-once. |
| Idempotency | `DA_PHAT` retry là no-op; một message vẫn có đúng một outbox row nhờ UNIQUE `tin_nhan_id`. |
| Transaction | Broadcast tiếp tục chạy ở transaction level 0 sau business commit; advisory lock không mở lại transaction hoặc khóa Membership/assignment/message graph. |
| Test | Initial failure→automatic success, due/future selection, backoff, no side effects/Q05 regression và two-process same-outbox race. |

### 10.2 Implementation and verification

- `PtChatDeliveryService::phatDangCho()` chọn batch bounded gồm `CHO_THU_LAI` đã đến hạn và `CHO_PHAT` còn sót do crash-gap.
- Command `pt-chat:retry-outbox {--limit=}` được đăng ký và scheduler chạy mỗi phút với `withoutOverlapping`.
- Failure giữ `CHO_THU_LAI`, tăng `so_lan_thu`, đặt exponential backoff theo server config với cap; `loi_gan_nhat` chỉ lưu `REALTIME_DELIVERY_FAILED`, không lưu raw exception/credential.
- Retry chỉ dựng `PtChatMessageSent` từ message đã persist. Không gọi entitlement/activation, không tạo `tin_nhan`, `su_dung_quyen_loi` hoặc outbox mới.
- MariaDB advisory lock theo `tin_nhan_id` serialize concurrent workers nhưng callback transport vẫn quan sát business commit tại transaction level 0.
- Actual two-process race cùng outbox: hai caller đều kết thúc `DA_PHAT`, marker transport có đúng 1 dispatch, `so_lan_thu` từ 1 lên 2, message/usage/Membership không đổi.
- Q05 regression giữ nguyên: old PT/new PT bị chặn với conversation cũ; Member history vẫn đọc được.
- Focused commit/retry/concurrency: **PASS — 6 tests / 45 assertions**.
- Chat/Channel/Auth/Assignment/Membership affected regression: **PASS — 38 tests / 575 assertions**.
- Command discovery, scheduler inventory, affected Pint, PHP lint và `git diff --check`: **PASS**.
- Migration/schema changes: **NONE**.

### 10.3 Delivery semantics

Delivery là **at-least-once**. Advisory lock ngăn hai worker đang chạy đồng thời phát trùng sau một lần thành công đã ghi ledger. Crash đúng khoảng sau khi transport nhận event nhưng trước khi `DA_PHAT` được lưu vẫn có thể tạo event lặp; client phải dedupe bằng stable `message_id`. Business message không bị nhân đôi nhờ UNIQUE hiện hữu và retry không gọi lại send workflow.

### 10.4 Phase 7 Gate

- AUTOMATIC `CHO_THU_LAI` RETRY = IMPLEMENTED
- CRASH-GAP `CHO_PHAT` RECOVERY = IMPLEMENTED
- BOUNDED BATCH / BACKOFF = PASS
- SAFE ERROR PERSISTENCE = PASS
- CONCURRENT RETRY DISPATCH = EXACTLY ONE IN VERIFIED RACE
- DUPLICATE BUSINESS MESSAGE = NONE
- NEW MEMBERSHIP USAGE = NONE
- MEMBERSHIP ACTIVATION DURING RETRY = NONE
- PT/AI QUOTA CHANGE = NONE
- Q05 OLD PT BLOCK = PRESERVED
- DATABASE SCHEMA CHANGE = NONE
- FOCUSED / AFFECTED TESTS = PASS

**PHASE 7 PT CHAT OUTBOX RETRY HARDENING = PASS**

## 11. Phase 8 — Documentation / CI / Hygiene

Status: **PASS**.

### 11.1 Documentation and reproducibility

- `README.md`, `BE/README.md` và `BE/AGENTS.md` phản ánh Backend core hiện hành, runtime Gemini/Reverb/Scheduler và quy tắc test trên schema cô lập.
- `docs/BACKEND_API_CONTRACT.md` ghi contract REST theo actor cho Auth, Admin, Profile, Package, Membership, Payment, Gym, PT/Chat, Gemini, Workout và Progress.
- `docs/THIRD_PARTY_EXERCISE_DATASET.md` khóa commit `7455efae41b330c265e7cd4b78dfa848e7ce5ebd`, lệnh checkout/import tái lập và expected count 1.324 bài tập.
- Không có tài liệu nào đưa credential thật vào repository.

### 11.2 CI and quality evidence

- `.github/workflows/backend.yml` dùng PHP 8.4 và service `mariadb:10.4.32`, tạo `smart_fitness_ci_test`, checkout exact Exercise Dataset commit, migrate/seed/test, Pint, Composer validate/audit/platform và secret hygiene.
- CI ép `AI_PROVIDER=unavailable`; Gemini contract test tự dùng fake HTTP, vì vậy pipeline không gọi provider thật.
- `BE/pint.json` dùng Laravel preset và chỉ loại `database/migrations`: M001–M060 là raw DDL đã khóa, không được style-mutated trong task này. Toàn bộ PHP source/test/seed còn lại nằm trong full configured Pint scope.
- Full configured Pint `--test`: **PASS**.
- PHP syntax lint sau cùng: **PASS — 377 files**.
- Model/Seeder regression sau lần format toàn bộ: **PASS — 14 tests / 621 assertions**.
- Composer `validate --strict`: **PASS**.
- Composer `audit --locked --no-interaction`: **PASS — no security vulnerability advisories**.
- Composer `check-platform-reqs`: **PASS trên PHP 8.4.25**.
- Secret hygiene, migration lock và workflow required-manifest check: **PASS**.
- `git diff --check`: **PASS**.
- GitHub Actions workflow đã được static-inspect tại local; GitHub-hosted job chưa được tuyên bố là đã chạy khi chưa commit/push.

### 11.3 Phase 8 Gate

- DOCUMENTATION = CURRENT
- BACKEND API CONTRACT = DOCUMENTED
- DATASET REPRODUCIBILITY = PINNED
- GITHUB ACTIONS = CONFIGURED
- FULL CONFIGURED PINT = PASS
- COMPOSER VALIDATE / AUDIT / PLATFORM = PASS
- SECRET HYGIENE = PASS
- M001–M060 MUTATION = NONE

**PHASE 8 DOCUMENTATION / CI / HYGIENE = PASS**

## 12. Phase 9 — Final Full Backend Acceptance

Status: **PASS**.

### 12.1 Cross-module acceptance

- Member/Gemini: Register → Login → Package → Payment → Gemini adapter với fake HTTP transport → Proposal → Apply → Workout → Progress: **PASS**.
- PT: Assignment → Chat → PT Direct → PT Proposal → Member Confirm → Workout → Member/PT Progress: **PASS**.
- Admin: Account → Role grant/revoke → Package Catalog → Dashboard: **PASS**.
- Full integration folder: **PASS — 7 tests / 196 assertions**, trong đó ba acceptance scenario mới là **3 / 75**.
- Gemini fake transport nhận đúng một request khi tạo Proposal; Apply không gửi HTTP lần hai.

### 12.2 Security regression

- Q05 old-PT scope, private-channel authorization, Seeder guard/idempotency, IDOR, generated-column mass assignment, forgot-password enumeration và Gemini configuration/secret: **PASS**.
- Security-focused regression: **PASS — 84 tests / 1,592 assertions**.
- Không có real Gemini/payOS network call trong automated acceptance.

### 12.3 Full Backend regression

| Run | Command | Result |
| --- | --- | --- |
| 1 | `php artisan test` | PASS — 279 tests / 3,998 assertions |
| 2 | `php artisan test` | PASS — 279 tests / 3,998 assertions |
| Random | `php artisan test --order-by=random --random-order-seed=20260830` | PASS — 279 tests / 3,998 assertions |

Baseline trước Phase 6 là 265 tests / 3.808 assertions; final count tăng lên 279 / 3.998.

### 12.4 Phase 9 Gate

- CROSS-MODULE E2E = PASS
- GEMINI RUNTIME FINAL AUDIT = PASS
- BACKEND SECURITY REGRESSION = PASS
- FULL BACKEND RUN 1 = PASS
- FULL BACKEND RUN 2 = PASS
- RANDOM ORDER SEED 20260830 = PASS
- QUALITY FINAL = PASS

**PHASE 9 FINAL FULL BACKEND ACCEPTANCE = PASS**

## 13. Development Database Safety

- `smart_fitness` mutation: **FORBIDDEN**.
- Baseline read-only: 52 business tables, 60 migration rows, 11 business tables đang có dữ liệu.
- Sau Phase 1, development counts khớp tuyệt đối baseline: 8 users, 8 Role assignments, 1.324 exercises và 60 migration rows; không có mutation.
- Current isolated schema: `smart_fitness_backend_completion_test_20260830_p1_a31`, 60 migrations; sau Phase 3 trở về đúng seed baseline 8 users / 8 Role assignments / 1.324 exercises, không còn Proposal, Plan, Version, Schedule, idempotency hoặc audit tạm.
- Development DB được đối chiếu chỉ đọc sau Phase 3: 60 migrations, 8 users, 8 Role assignments, 1.324 exercises; các bảng Proposal/Plan/Version/Schedule/idempotency/audit đều bằng 0.
- Sau Phase 4, schema test và development DB cùng được đối chiếu: 60 migrations, 8 users, 8 Role assignments, 1.324 exercises; các bảng Body Measurement/Workout history/audit kiểm tra đều bằng 0. Development DB chỉ được SELECT.
- Sau Phase 5, schema test và development DB tiếp tục cùng baseline; Membership term/check-in/Workout/payment/PT assignment tạm đều bằng 0. Dashboard không thực hiện mutation.
- Trước và sau Phase 9, toàn bộ 53 table counts của `smart_fitness` có cùng SHA-256 `ab878be7478e759428b26caf4a7503058d23b8228d29923003d39911662b664a`; development DB chỉ được `SELECT`.
- Schema test trước cleanup cũng trở về đúng fingerprint trên. Chỉ schema `smart_fitness_backend_completion_test_20260830_p1_a31` được drop; số schema còn lại theo prefix task = **0**.
- Temp barrier/test output và task-owned PHP/Reverb/queue process còn lại: **NONE**.

## 14. Final Gate

FULL SMART FITNESS BACKEND = COMPLETE

BACKEND CORE REQUIREMENTS = IMPLEMENTED

REAL AI PROVIDER = GEMINI

BACKEND SECURITY = PASS

BACKEND INTEGRATION = PASS

BACKEND = READY FOR VUE WEB INTEGRATION

BACKEND = READY FOR REACT NATIVE INTEGRATION
