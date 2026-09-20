# VUE WEB IMPLEMENTATION PLAN

> Ngày lập kế hoạch: 01/09/2026
>
> Phạm vi: Vue Web cho `ADMIN`, `PT`, `RECEPTIONIST`
>
> Trạng thái tài liệu: kế hoạch triển khai, chưa triển khai mã nguồn Frontend
>
> Bản cập nhật V2: chuẩn hóa naming composable sang tiếng Việt, khóa dependency tối thiểu cho Terra, bổ sung Visual UI System và Task Breakdown theo phase. Quyết định điều phối mới gộp mỗi phase FE-3 → FE-9 thành một task triển khai duy nhất để giảm số lượt agent; các hạng mục cũ chỉ còn là acceptance checklist nội bộ, không phải task riêng.

## 1. Executive Summary

Frontend hiện tại mới là skeleton Vue 3: một route `/`, một layout rỗng, một trang khởi tạo, Axios cơ bản và Pinia đã đăng ký nhưng chưa có store. Không có auth, guard, màn hình nghiệp vụ, test, lint hoặc realtime client. Nền tảng có thể giữ lại gồm Vite, Vue Router, Pinia, alias `@`, entrypoint, Axios instance và CSS nền; phần placeholder không mang nghiệp vụ cần được thay theo từng phase, không xóa hoặc tái cấu trúc hàng loạt ngay từ đầu.

Kế hoạch đề xuất SPA Vue 3 dùng Composition API và JavaScript hiện hữu, chia theo actor/domain, một Axios client thống nhất, bảy Pinia store có phạm vi rõ, ba app layout, component dùng chung vừa đủ và service theo domain. Không chuyển Nuxt/SSR, không thêm state library, không dựng design system lớn và không nhân bản Member Web.

Phạm vi lập kế hoạch có **50 router record**, gồm **48 màn hình** và 2 route điều phối không có page riêng (`/` và catch-all). Trong 48 màn hình có 27 Admin, 12 PT, 4 Receptionist và 5 màn hình dùng chung. Ma trận API có **65 capability rows**: 52 `READY`, 4 `BACKEND_FIX_IN_PROGRESS`, 7 `BACKEND_API_BLOCKER`, 1 `DEPLOYMENT_DEPENDENCY`, 1 `OUT_OF_SCOPE`.

Frontend không được coi guard, nút ẩn, trạng thái Membership hay dữ liệu cache là authority. Backend tiếp tục quyết định role, entitlement, resource scope, Payment, Membership, PT assignment, Q05, Q13, idempotency và concurrency. Những màn hình thiếu API phải dừng riêng tính năng đó; không gọi API Admin bằng token Receptionist, không dùng API Member bằng token PT và không tự sửa Backend trong chuỗi triển khai Web.

## 2. Source of Truth

Thứ tự áp dụng khi Terra triển khai:

1. `PROJECT_RULES.md` là Canonical Source of Truth. Nạp qua `.fitness-rules/PROJECT_CORE.md` và các module theo `.fitness-rules/RULE_INDEX.md` (đặc biệt quyết định Membership/PT/Q05/Q13 mới nhất); không bắt buộc đọc toàn bộ file canonical nếu không có xung đột.
2. Route, middleware, controller, request validation, service, event/channel và test Backend thực tế trong `BE/`.
3. `docs/BACKEND_API_CONTRACT.md` để tra contract công khai; nếu lệch source thì source và test hiện tại thắng, sau đó ghi nhận lệch tài liệu.
4. `docs/thiet_ke_co_so_du_lieu/BACKEND_FOLLOW_UP_FIXES.md`, ngày hậu kiểm 31/08/2026, là nguồn trạng thái follow-up mới nhất.
5. `BACKEND_CORE_COMPLETION_REPORT.md`, `BACKEND_CORE_COMPLETION_CHECKPOINT.md` và `BACKEND_REMEDIATION_REPORT.md` là bằng chứng lịch sử, không tự động đồng nghĩa production-ready.
6. Source Frontend và lockfile thực tế trong `FE/`.
7. Tài liệu kế hoạch này cho thứ tự triển khai Web; tài liệu không được dùng để bịa API hoặc thay đổi business rule.

Không tìm thấy file `PRODUCTION_READINESS_CORRECTION_REPORT.md` hoặc checkpoint tương ứng trong repository tại thời điểm audit. Vì vậy, kết luận production readiness trong kế hoạch dựa trên source hiện tại và hậu kiểm 31/08/2026, không suy diễn từ tên báo cáo không tồn tại.

## 3. Current FE Audit

| Hạng mục | Hiện trạng đã kiểm tra | Phân loại / quyết định |
| --- | --- | --- |
| Runtime | Vue khai báo `^3.5.41`, lock `3.5.42`; Vite `8.2.2`; Node `^22.13.0 hoặc >=24.3.0` | Giữ Vue SPA + Vite |
| Build plugin / alias | `@vitejs/plugin-vue` `6.0.8`; alias `@` trỏ `src` | Tái sử dụng nguyên tắc cấu hình hiện tại |
| Router | Vue Router `5.3.0`; một route `/`, tên `khoi-tao` | Giữ router, thay route nghiệp vụ theo phase |
| State | Pinia `4.0.3` đã `app.use(createPinia())`, chưa có store | Tái sử dụng bootstrap; tạo store có chọn lọc |
| HTTP | Axios `1.20.0`; `src/services/api.js` có base URL, timeout 15s, `Accept` | Tái sử dụng ý tưởng single client; bổ sung auth/interceptor/normalization |
| Environment | `VITE_API_BASE_URL`, mặc định local trong client | Bỏ fallback hardcode khỏi production path; validate env khi build/start |
| App shell | `App.vue` chỉ có `RouterView`; `BoCucChinh.vue` chỉ bọc `<main>` | `App.vue` tái sử dụng; layout hiện tại là legacy skeleton |
| Page | `TrangKhoiTao.vue` chỉ thông báo skeleton | `LEGACY`; không có business logic để tái sử dụng |
| CSS | Một file global tối thiểu, chưa có token/theme/component state | Giữ reset nền; bổ sung CSS variables và responsive primitives |
| UI framework | Không có | Không thêm framework trong Foundation; dùng component Vue/CSS gọn |
| Realtime | Không có Laravel Echo hoặc Pusher-compatible client | Chỉ đề xuất thêm ở FE-7 sau phê duyệt dependency |
| Test | Không Vitest, Vue Test Utils, test config hoặc test script | FE-0 bổ sung test tối thiểu; chưa cài trong task lập kế hoạch |
| Lint | Không ESLint config/script | FE-0 được phép bổ sung ESLint + Vue plugin theo danh sách dependency tối thiểu đã khóa tại mục 6A |
| Business pages/components | Không có | Không có phần nghiệp vụ để reuse |

Các file framework `package.json`, `package-lock.json`, `vite.config.js`, `main.js`, `App.vue`, `index.html` giữ tên chính thức. `BoCucChinh.vue`, `TrangKhoiTao.vue` và route `khoi-tao` được ghi nhận là legacy; Terra chỉ thay/di chuyển khi FE-0 có test/build bảo vệ, không rename hàng loạt chỉ để Việt hóa.

## 4. Backend Readiness

Backend hiện có 105 route `/api/*`, custom database Bearer token, role middleware và resource-scope service. Auth không phải Sanctum cookie: login trả raw token đúng một lần, DB giữ SHA-256, `/auth/me` revalidate account/role, logout revoke token hiện tại. Bộ báo cáo gần nhất ghi nhận 301 test / 3.207 assertions PASS; kế hoạch này không chạy lại full Backend suite vì phạm vi chỉ đọc và lập kế hoạch.

Các nhóm đủ để bắt đầu Web gồm Auth, Admin dashboard/account/role, phần lớn catalog, PT self profile, PT assigned-member list, PT progress, PT notes, PT direct, PT proposal, Chat REST và QR check-in. Các giới hạn đã xác minh:

- Admin chưa có GET list/detail cho PT assignment; create/end/reassign có route nhưng màn quản lý không thể reload đáng tin cậy.
- Admin chưa có API đọc đầy đủ trainer profile để prefill detail/edit; account DTO chỉ cho biết profile id/code/status.
- PT chưa có member-profile detail, workout plan và workout history dưới PT resource scope.
- Receptionist chưa có member lookup và Membership status API.
- Payment reconciliation chưa hiển thị đầy đủ event có `lan_thanh_toan_id = null`, và filter chưa xét abnormal event liên kết.
- Trainer invitation retry và onboarding audit snapshot đang trong backlog Backend.
- Chat REST/channel authorization đã có; realtime thật còn phụ thuộc Reverb, queue worker, scheduler, public client config và external verification.
- `BE/config/reverb.php` đang cho `allowed_origins = ['*']`; staging/production phải giới hạn origin Web được phép trước final gate.

Admin Payment Web chỉ đọc. Không có nút đánh dấu thành công, sửa số tiền, cộng Membership, refund hoặc xóa event. Payment thành công chỉ xác lập quyền sở hữu; Membership chỉ kích hoạt bởi lần dùng quyền lợi trả phí hợp lệ đầu tiên theo Backend.

## 5. Actors and Scope

| Actor | Web scope | Không thuộc Web scope |
| --- | --- | --- |
| `ADMIN` | Dashboard; account/role; view Member/PT/Receptionist theo account; PT onboarding/assignment; catalog; Payment/reconciliation read-only | Sửa tay Payment/Membership, refund, hard-delete lịch sử |
| `PT` | Self profile; assigned Members; progress; plan/history read; notes; direct sessions; proposal; chat | Truy cập Member bất kỳ, trực tiếp sửa official plan, sửa completed session |
| `RECEPTIONIST` | Member lookup, Membership status, QR check-in | Admin account/role/catalog, chỉnh Membership, phát hành QR thay Member |
| `MEMBER` | Chỉ liên quan auth/channel contract dùng chung khi cần, không xây portal Web | Toàn bộ Member product flow được giữ ở React Native |

Role hợp lệ là `MEMBER`, `PT`, `RECEPTIONIST`, `ADMIN`. Free/Premium là trạng thái Package/Membership/entitlement, không phải role và không được tạo `ROLE_FREE`, `ROLE_PREMIUM` hoặc một cờ `isPremium` làm authority toàn hệ thống.

## 6. Frontend Naming Convention

### 6.1 Route Naming

- URL page: tiếng Việt không dấu, chữ thường, kebab-case, ví dụ `/admin/bang-dieu-khien`, `/pt/hoi-vien/:id/tien-do`.
- Route name chọn một chuẩn duy nhất: tiếng Việt không dấu camelCase, có prefix actor, ví dụ `adminBangDieuKhien`, `ptTienDoHoiVien`, `leTanQuetMaVaoPhong`.
- Backend API path giữ nguyên contract tiếng Anh, ví dụ `/api/admin/dashboard`; không Việt hóa API path.
- `/`, `/:pathMatch(.*)*` và param syntax là ngoại lệ framework được ghi nhận.

### 6.2 File Naming

- Page/business component/store/service/composable mới: tiếng Việt không dấu snake_case.
- Page dùng hậu tố vai trò: `dang_nhap.index.vue`, `tai_khoan.chi_tiet.vue`, `giao_an_mau.tao_phien_ban.vue`.
- Store: `xac_thuc.store.js`; service: `tai_khoan.api.js`; composable: `su_dung_thao_tac_chong_lap.js`.
- Giữ tên file framework/library theo mục 6.6.

### 6.3 Function Naming

- Hàm nghiệp vụ tiếng Việt không dấu camelCase: `taiDanhSachTaiKhoan()`, `capVaiTro()`, `guiTinNhan()`.
- Tên phải ngắn, diễn đạt ý định nghiệp vụ; không dùng `fetchAccounts()`, `handleSubmit()`, `sendMessage()` cho code mới.
- Getter/computed kỹ thuật đơn giản không cần ép Việt hóa nếu là API framework rõ ràng.

### 6.4 Variable Naming

- Biến nghiệp vụ ưu tiên tiếng Việt không dấu camelCase: `danhSachHoiVien`, `phanCongHienTai`, `khoaIdempotency`.
- Payload/response field từ Backend giữ đúng key contract; chỉ map sang view model khi việc đó giảm lặp hoặc tăng an toàn.
- Không đổi tên biến thư viện như `router`, `route`, `axios`, `error`, `response`, `socketId` chỉ để đáp ứng hình thức.

### 6.5 Comment / Docblock Rules

Hàm quan trọng về auth, role routing, mutation, idempotency, Payment state, Q05 cleanup, Q13, conflict và realtime phải có docblock tiếng Việt không dấu trả lời: mục đích, đầu vào, cách hoạt động, kết quả/thay đổi, side effect và business rule quan trọng. Không thêm comment kể lại từng dòng; computed/getter hiển nhiên không cần docblock dài.

Ví dụ chuẩn:

```js
/**
 * Gui tin nhan PT va giu cung client_message_id khi retry.
 *
 * Dau vao: conversationId, noiDung va khoa cua thao tac hien tai.
 * Cach hoat dong: validate noi dung, gui REST, sau do hop nhat theo message_id/sequence.
 * Ket qua: tra ve tin nhan da duoc Backend chap nhan va cap nhat danh sach cuc bo.
 * Side effect: co the kich hoat dong bo realtime va thay doi pending state.
 * Business Rule: Frontend khong tu cap quyen Chat; mat Q05 scope thi dung retry va roi channel.
 */
async function guiTinNhan(conversationId, noiDung, clientMessageId) {}
```

### 6.6 Framework Exceptions

Không bắt buộc đổi `package.json`, `package-lock.json`, `vite.config.js`, `eslint.config.js`, `main.js`, `App.vue`, `index.html`, environment variables, library names và official APIs như `ref`, `computed`, `watch`, `onMounted`, `defineProps`, `RouterView`, `useRouter`. Legacy được giữ tạm phải có danh sách ngoại lệ, không được dùng làm tiền lệ cho business source mới.

## 6A. Approved Frontend Dependencies

Để Terra không bị dừng ngay ở FE-0 chỉ vì thiếu xác nhận package, kế hoạch V2 khóa trước phạm vi dependency tối thiểu được phép bổ sung. Đây là **giới hạn trên**, không phải yêu cầu phải cài tất cả nếu source hiện tại đã có giải pháp tương đương.

### FE-0 — Test và lint

Terra được phép bổ sung, sau khi kiểm tra compatibility với Node/Vite/Vue/lockfile hiện tại:

- `vitest`
- `@vue/test-utils`
- `jsdom`
- `eslint`
- `eslint-plugin-vue`

### FE-7 — Realtime client

Terra được phép bổ sung, chỉ khi bắt đầu FE-7 và sau khi xác minh compatibility với Backend Reverb hiện tại:

- `laravel-echo`
- `pusher-js`

### Dependency guard

- Không thêm UI framework trong MVP.
- Không thêm state library khác Pinia.
- Không `npm audit fix --force`.
- Không major-upgrade package hiện hữu chỉ để cài dependency mới.
- Không xóa/regen lockfile một cách mù quáng.
- Mọi package/version thực cài phải được ghi vào checkpoint và completion report.
- Nếu package trong danh sách trên không có version tương thích runtime/lockfile, Terra phải ghi `BLOCKED_DEPENDENCY_COMPATIBILITY`, không tự nâng cả stack.
- Package ngoài danh sách này cần một quyết định riêng, trừ dependency transitive do npm resolve tự nhiên.

## 7A. Visual UI System — MVP Default

Mục này khóa baseline giao diện để Terra không phải tự thiết kế mỗi màn hình theo một kiểu khác nhau. Đây là **visual convention**, không phải business rule; mọi màu/kích thước phải đi qua CSS variables để có thể đổi tập trung sau này.

### 7A.1 Nguyên tắc

- Giao diện staff theo hướng sạch, rõ, ưu tiên dữ liệu và thao tác hơn trang trí.
- Không thêm UI framework chỉ để có component đẹp sẵn.
- Không dark mode trong MVP.
- Không animation phức tạp; transition ngắn chỉ dùng cho drawer/dialog/loading khi cần.
- Không dùng màu là tín hiệu duy nhất; status phải có text/icon hoặc label.

### 7A.2 Typography

Dùng system font stack để không phát sinh font package/network dependency:

```css
font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
```

Nếu `Inter` không có sẵn trên máy thì tự fallback system font; không tải Google Fonts trong MVP.

Baseline:

- Body: 14–16px.
- Heading trang: 24–28px, semibold/bold.
- Heading khu vực/card: 16–18px, semibold.
- Metadata/helper: 12–14px.
- Line-height body khoảng 1.5.

### 7A.3 Color tokens

Khởi tạo CSS variables tập trung, ví dụ:

```css
--mau-nen-trang: #ffffff;
--mau-nen-phu: #f8fafc;
--mau-vien: #e2e8f0;
--mau-chu-chinh: #0f172a;
--mau-chu-phu: #64748b;
--mau-chinh: #2563eb;
--mau-chinh-hover: #1d4ed8;
--mau-thanh-cong: #15803d;
--mau-canh-bao: #b45309;
--mau-nguy-hiem: #b91c1c;
--mau-thong-tin: #0369a1;
```

Không hard-code màu status rải rác trong page/component; status mapping tập trung.

### 7A.4 Spacing / radius / shadow

Spacing scale ưu tiên:

`4, 8, 12, 16, 24, 32px`.

Radius:

- Input/button nhỏ: 6px.
- Card/dialog: 8–12px.

Shadow chỉ dùng nhẹ cho dialog/menu nổi; card dữ liệu bình thường ưu tiên border thay vì shadow dày.

### 7A.5 Layout

Desktop staff baseline:

- Sidebar: khoảng 248–264px.
- Topbar: khoảng 56–64px.
- Main content max width: khoảng 1440px.
- Page padding desktop: 24px.
- Page padding mobile/tablet: 16px.
- Form nghiệp vụ chính: max width khoảng 720px khi không cần full-width.
- Login card: khoảng 400–440px.

Không ép mọi table vào max-width nhỏ; màn quản trị bảng dữ liệu được phép dùng toàn vùng content.

### 7A.6 Responsive breakpoints

Không cần framework breakpoint; CSS media query có thể dùng baseline:

- `< 640px`: mobile/narrow.
- `640–1023px`: tablet/small desktop.
- `>= 1024px`: desktop staff.
- `>= 1280px`: wide desktop.

Ở màn hẹp:

- Sidebar chuyển thành drawer.
- Form về một cột.
- Table cho phép horizontal scroll hoặc card fallback khi hợp lý.
- Critical action không bị giấu khỏi keyboard/touch.

### 7A.7 Form control

- Chiều cao control mục tiêu 40–44px.
- Label luôn visible, không dùng placeholder thay label.
- Required/error/help text có layout ổn định, không làm form nhảy mạnh.
- Button primary chỉ một hành động chính mỗi vùng form.
- Mutation pending phải có text trạng thái và `aria-busy`.

### 7A.8 Table

- Row height mục tiêu tối thiểu 44px.
- Header dễ phân biệt nhưng không quá nặng.
- Action column gọn, thao tác nguy hiểm phải confirm.
- Mobile không ép chữ quá nhỏ; ưu tiên scroll ngang.
- Empty/loading/error dùng component chuẩn, không tự chế từng page.

### 7A.9 Icon

Không bắt buộc icon library trong FE-0. Text-first là đủ cho MVP. Nếu sau này cần icon library, phải được thêm như dependency riêng chứ không tự đưa vào scope.

### 7A.10 Visual acceptance

Một phase không được coi là đạt UI nếu:

- màn cùng loại có spacing/button/table khác convention không lý do;
- status chỉ biểu diễn bằng màu;
- form không có label/error rõ;
- desktop chạy nhưng mobile/tablet vỡ layout;
- page thiếu loading/empty/error state;
- màu/kích thước nghiệp vụ bị hard-code rải rác thay vì dùng token.

## 7. Proposed Frontend Architecture

Giữ SPA, JavaScript và Composition API `<script setup>`. Cấu trúc theo responsibility và domain, không theo một store/service khổng lồ:

```text
App.vue
router -> guards -> layouts -> pages
                         |-> components dung chung/domain
pages/composables -> stores (cross-page state only)
pages/stores/composables -> domain services -> Axios client -> Backend
chat store/composable -> REST + Echo/Reverb adapter
```

Nguyên tắc:

- Page điều phối UI; service sở hữu API path; store chỉ giữ session hoặc state cần chia sẻ qua page.
- DTO Backend không bị sửa trực tiếp; form tạo bản nháp, submit payload allow-list.
- Dữ liệu nhạy cảm theo Member/PT được clear khi logout, role loss, Q05 scope loss hoặc đổi actor.
- CSS variables + utility nhỏ + scoped styles; không thêm UI framework trong MVP.
- Mỗi phase có route, test và acceptance gate độc lập; feature bị blocker không làm dừng các feature READY khác.

## 8. Authentication Architecture

### Luồng và lưu token

1. Ba route đăng nhập actor dùng chung `bieu_mau_dang_nhap.vue` và `xac_thuc.api.js`.
2. `POST /api/auth/login`; kiểm tra role trả về chỉ để điều phối UX, không làm authorization authority.
3. Token giữ trong Pinia và `sessionStorage`, không dùng `localStorage`, vì Backend không có refresh token và Web staff nên kết thúc session khi đóng tab/browser. Đây là quyết định Frontend, không phải thay đổi contract.
4. Ngay khi restore, gọi `GET /api/auth/me` và phân loại lỗi theo fail-closed policy:
   - `401` hoặc response `/me` không còn hợp lệ theo contract auth: xóa token/session/domain state và về login phù hợp.
   - network/timeout/`5xx`: giữ token trong `sessionStorage` để có thể thử lại, xóa user/roles/actor khỏi memory đang render, không cho vào protected content, hiển thị lỗi tạm thời và cho người dùng gọi lại `/me`.
   - Không dùng cached user/roles để vượt qua trạng thái restore lỗi; chỉ `/me` thành công mới mở protected content.
5. Axios gắn `Authorization: Bearer <token>` từ một token accessor để tránh circular dependency.
6. `POST /api/auth/logout` best-effort, sau đó luôn clear token, stores nhạy cảm, pending keys và realtime subscriptions.

### Redirect theo role

- Một role Web duy nhất: vào home actor tương ứng.
- Nhiều role Web: vào `/chon-vai-tro`; không tự đặt ưu tiên Admin > PT > Receptionist.
- Chỉ có `MEMBER`: hiển thị thông báo portal Web không hỗ trợ và cho logout; không tạo Member Web.
- `redirect` query chỉ chấp nhận internal named route đã allow-list, không nhận URL ngoài.

### Guard

- `meta.congKhai`: login/forgot/reset/error.
- `meta.yeuCauXacThuc`: cần session đã restore.
- `meta.vaiTro`: danh sách role Web được phép.
- `meta.tinhNang`: dùng để render trạng thái blocker/deployment, không giả quyền Backend.
- 401: clear session và về login; 403: `/khong-co-quyen`; 404 resource: state “không thể truy cập” không tiết lộ tồn tại; account disabled/role revoked được phát hiện lại qua response và `/auth/me`.

## 9. API Client Architecture

`services/api.js` được nâng thành single Axios client hoặc chuyển logic sang `services/ket_noi_api.js` trong FE-0; chỉ một nơi đọc `VITE_API_BASE_URL`. Không hardcode localhost ở component.

Request pipeline:

- `baseURL` bắt buộc từ env cho staging/production; timeout khởi điểm 15 giây và có thể override cho endpoint được phê duyệt.
- `Accept: application/json`; `Content-Type: application/json` khi có JSON body.
- Gắn Bearer token; gắn `Idempotency-Key` chỉ khi caller truyền action key.
- Không log token, request secret, raw webhook payload hoặc response nhạy cảm.

Response pipeline chuẩn hóa về:

```js
{
  httpStatus,
  code,
  message,
  fieldErrors,
  retryAfter,
  isNetworkError,
  originalRequestId
}
```

`code` giữ nguyên nếu Backend trả. Không dựng business code mới trong client. Service trả `data/meta` theo contract thật; không giả tất cả response có cùng pagination shape.

## 10. Error Handling

| Tình huống | Backend code đã quan sát / nguồn | Hành vi Frontend |
| --- | --- | --- |
| 401 | `message: Chưa xác thực.` hoặc token hết hạn/revoked | Chỉ cleanup nếu 401 thuộc đúng Bearer token của phiên hiện tại; hủy realtime, clear toàn bộ session/domain state, đưa về login actor, giữ internal return route an toàn. Late 401 của token cũ không được xóa phiên mới. |
| 403 | Role middleware hoặc quyền không đủ | Đưa tới `/khong-co-quyen`; không retry tự động, không chỉ ẩn nút |
| 404 | Resource scope được conceal, resource không tồn tại | Hiển thị “Không thể truy cập dữ liệu này”; với Q05 phải rời channel và clear conversation |
| 409 idempotency | `IDEMPOTENCY_CONFLICT`, `IDEMPOTENCY_IN_PROGRESS`, `CHAT_IDEMPOTENCY_CONFLICT` | Không đổi key khi retry cùng action; conflict payload thì buộc người dùng tạo thao tác mới sau khi xem lại dữ liệu |
| 409 stale | `WORKOUT_TEMPLATE_STALE`, `PT_PLAN_STALE`, `PT_TEMPLATE_STALE`, `PT_PROPOSAL_CONTEXT_STALE`, `PROPOSAL_ASSIGNMENT_CONFLICT` | Giữ draft, tải lại source hiện tại, cho người dùng so sánh; không overwrite âm thầm |
| 409 domain | `ROLE_CONFLICT`, `ASSIGNMENT_CONFLICT`, `PACKAGE_CONFLICT`, `MEMBERSHIP_STATE_CONFLICT` | Hiển thị thông báo an toàn và refetch resource liên quan |
| 422 | Laravel validation; có thể có `INVALID_WORKOUT_TEMPLATE_STRUCTURE` | Map lỗi field, focus field đầu, vùng tổng lỗi có `aria-live`; không mất draft |
| 429 | Throttle login/forgot/reset | Tôn trọng `Retry-After` nếu có, khóa submit có countdown; không loop retry |
| 5xx | Lỗi server/queue | Thông báo chung, giữ form; riêng restore `/me` giữ token nhưng khóa protected content và cho retry. Chỉ cho retry mutation khi semantics/idempotency an toàn. |
| Network/timeout | Không có HTTP response | Riêng restore `/me` giữ token nhưng khóa protected content và cho retry. Với mutation, giữ action key, đánh dấu kết quả chưa rõ, refetch trước khi user tạo action mới nếu API có query. |

Không hiển thị stack trace, SQL, raw provider error hoặc raw webhook event cho người dùng.

## 11. Idempotency Strategy

Utility `taoKhoaIdempotency()` dùng `crypto.randomUUID()`. `su_dung_thao_tac_chong_lap.js` giữ key theo một action instance: tạo một lần khi user bắt đầu submit, dùng lại qua timeout/network retry, xóa sau terminal success hoặc khi user chủ động bỏ action; action mới có key mới.

| Luồng Web | Cơ chế thật | Kế hoạch FE |
| --- | --- | --- |
| Admin tạo/onboard PT | Header `Idempotency-Key` UUID | Bắt buộc stable key; không đổi khi Axios retry |
| PT hoàn tất buổi trực tiếp | Header `Idempotency-Key` UUID | Stable key + disable submit + refetch history sau response không rõ |
| PT tạo Proposal | Header `Idempotency-Key` UUID | Stable key; 409 stale/assignment conflict dừng và refetch |
| Chat gửi tin | Body `client_message_id` UUID | Tạo trước optimistic row, retry cùng id; reconcile theo server message id/sequence |
| Template revision | `expected_content_version`, không phải idempotency header | Optimistic concurrency; stale thì reload/compare, không auto-submit lại |
| Role/status/catalog/assignment | Không có header idempotency chung | Disable pending; refetch sau timeout. Role target-state có no-op Backend; assignment thiếu GET là blocker cho recovery đầy đủ |
| QR check-in | `qr_token` single-use, Backend trả controlled conflict | Không auto-retry mù; scan mới là action mới, cùng token chỉ submit lại khi user xác nhận |

Frontend disabled button chỉ là UX, không được mô tả như concurrency protection.

## 12. Router Architecture

Tổng cộng 50 router record: 48 record có page và 2 record điều phối. Route tĩnh `tao-moi` phải khai báo trước `:id`.

```text
/                              -> dieuPhoiTrangGoc (redirect, khong co page)
/chon-vai-tro                  -> chonVaiTro
/quen-mat-khau                 -> quenMatKhau
/dat-lai-mat-khau              -> datLaiMatKhau
/khong-co-quyen                -> khongCoQuyen
/khong-tim-thay                -> khongTimThay

/admin/dang-nhap
/admin/bang-dieu-khien
/admin/tai-khoan
/admin/tai-khoan/:id
/admin/hoi-vien
/admin/hoi-vien/:id
/admin/huan-luyen-vien
/admin/huan-luyen-vien/tao-moi
/admin/huan-luyen-vien/:id
/admin/nhan-vien-le-tan
/admin/nhan-vien-le-tan/:id
/admin/phan-cong-pt
/admin/goi-tap
/admin/goi-tap/tao-moi
/admin/goi-tap/:id
/admin/dung-cu
/admin/nhom-co
/admin/bai-tap
/admin/bai-tap/tao-moi
/admin/bai-tap/:id
/admin/giao-an-mau
/admin/giao-an-mau/tao-moi
/admin/giao-an-mau/:id
/admin/giao-an-mau/:id/tao-phien-ban
/admin/thanh-toan
/admin/thanh-toan/:id
/admin/doi-soat-thanh-toan

/pt/dang-nhap
/pt/ho-so
/pt/hoi-vien
/pt/hoi-vien/:id
/pt/hoi-vien/:id/tien-do
/pt/hoi-vien/:id/ke-hoach-tap
/pt/hoi-vien/:id/lich-su-tap
/pt/hoi-vien/:id/ghi-chu
/pt/hoi-vien/:id/buoi-huan-luyen
/pt/hoi-vien/:id/de-xuat
/pt/hoi-vien/:id/de-xuat/tao-moi
/pt/tro-chuyen

/le-tan/dang-nhap
/le-tan/tra-cuu-hoi-vien
/le-tan/hoi-vien/:id/trang-thai-hoi-vien
/le-tan/quet-ma-vao-phong

/:pathMatch(.*)*                -> redirect /khong-tim-thay
```

Các page blocker vẫn có thể khai báo route ở phase của actor nhưng chỉ render `chan_tinh_nang_bi_chan.vue`, không dựng fake data hoặc gọi sai API. Khuyến nghị chỉ đưa route blocker vào navigation sau khi Backend status thành READY để demo không dẫn vào luồng cụt.

## 13. Layout Architecture

- `bo_cuc_cong_khai.vue`: login/forgot/reset, không navigation nghiệp vụ.
- `bo_cuc_admin.vue`: sidebar Admin, top bar, actor badge, account menu, breadcrumbs, responsive drawer.
- `bo_cuc_pt.vue`: member workspace navigation, selected-member context, chat badge nhưng không lưu entitlement authority.
- `bo_cuc_le_tan.vue`: ba thao tác tối thiểu: tra cứu, Membership, QR.
- `bo_cuc_loi.vue`: 403/404.

`khung_ung_dung.vue` cung cấp shell dùng chung nhưng menu được cấu hình allow-list theo actor. Khi role thay đổi, guard và `/auth/me` quyết định lại navigation. Layout không render nội dung nhạy cảm trước khi auth restore hoàn tất.

## 14. Pinia Store Plan

Chỉ đề xuất 7 store:

| Store | State / actions chính | Persistence | Cleanup / dữ liệu nhạy cảm |
| --- | --- | --- | --- |
| `xac_thuc.store.js` | token, user, roles, restore error/retry, login, me, logout, chọn actor | Token + actor trong `sessionStorage`; user có thể cache cùng session | Clear 401/logout/role loss; network/timeout/5xx của `/me` giữ token nhưng xóa authority khỏi memory; không log token |
| `tai_khoan.store.js` | Admin list filters/pagination, selected account, refetch after role/status | Không persist | Clear logout/đổi actor; không giữ password/reset token |
| `danh_muc.store.js` | Equipment/muscle options và catalog cache ngắn | Không persist | Invalidate sau mutation; không dùng cache làm authority |
| `thanh_toan.store.js` | Read-only filters, pagination, selected Payment/event | Không persist | Clear logout; không chứa raw payload/signature |
| `hoi_vien_pt.store.js` | Assigned Members, selected Member id, progress cache | Không persist | Clear ngay khi assignment scope mất/Q05/logout |
| `de_xuat.store.js` | Proposal list, draft, stable action key, statuses | Draft chỉ trong memory; có thể session draft sau review bảo mật | Clear khi assignment mất; không tự apply plan |
| `tro_chuyen.store.js` | Conversations, messages by id/sequence, pending sends, connection state | Không persist message content | Leave channels + clear on Q05/logout/role loss |

PT profile, receptionist lookup và form catalog để page/composable quản lý local state; không tạo giant store hoặc store cho mọi page.

## 15. Shared Components

| File | Mục đích | Dùng ở đâu |
| --- | --- | --- |
| `khung_ung_dung.vue` | Shell responsive dùng chung | Ba actor layouts |
| `thanh_ben_dieu_huong.vue` | Menu keyboard-accessible | Admin/PT/Receptionist |
| `thanh_tren.vue` | Actor/user/logout | Authenticated layouts |
| `duong_dan_phan_cap.vue` | Breadcrumb từ route meta | Detail pages |
| `tieu_de_trang.vue` | Title, description, actions | Mọi business page |
| `bang_du_lieu.vue` | Table semantic, loading skeleton slot | List pages |
| `thanh_phan_trang.vue` | Server pagination | Account/Payment lists |
| `bo_loc_danh_sach.vue` | Filter/search form, submit rõ ràng | Admin/PT lists |
| `huy_hieu_trang_thai.vue` | Status text + color + icon | Account/catalog/payment/proposal |
| `trang_thai_tai_du_lieu.vue` | Skeleton/spinner có accessible label | Mọi screen |
| `trang_thai_trong.vue` | Empty state có next action an toàn | Lists |
| `trang_thai_loi.vue` | Error + retry khi phù hợp | Query states |
| `hop_thoai_xac_nhan.vue` | Confirm mutation nguy hiểm | Role/status/direct completion |
| `truong_bieu_mau.vue` | Label/help/error/aria mapping | Forms |
| `vung_thong_bao.vue` | Toast/announcement | Mutation result |
| `the_chi_so.vue` | Dashboard/progress metric | Admin/PT |
| `cay_giao_an.vue` | View/edit draft tree; không hard-delete history | Template pages |
| `dong_tin_nhan.vue` | Message row keyed by server id/sequence | Chat |
| `khung_nhap_tin_nhan.vue` | Send/pending/retry UX | Chat |
| `bo_nhap_ma_qr.vue` | Camera/manual token adapter | Receptionist QR |
| `chan_tinh_nang_bi_chan.vue` | Hiển thị blocker có mã và next action | Route chưa có Backend API |

Chỉ tách component khi có lặp thật hoặc boundary nghiệp vụ rõ. Không xây component registry hay theme engine ngoài nhu cầu MVP.

## 16. Admin Screens

### 16.1 Đăng nhập

`/admin/dang-nhap` dùng form auth chung nhưng context `ADMIN`. Sau login phải xác nhận role `ADMIN`; account nhiều role đi qua `/chon-vai-tro`, không invent priority. API `POST /api/auth/login`, `GET /api/auth/me`, `POST /api/auth/logout`: `READY`.

### 16.2 Bảng điều khiển

`/admin/bang-dieu-khien` chỉ hiển thị metric thật từ `GET /api/admin/dashboard`: account active Member/PT, Membership active/awaiting, check-in hôm nay, workout hoàn tất, Payment success/cần đối soát và active PT assignment. Không thêm doanh thu vì API không cung cấp. Hỗ trợ `from/to` theo validation Backend: `READY`.

### 16.3 Tài khoản / Role

`/admin/tai-khoan` và `/:id` dùng account list/detail, status PATCH, role PUT/DELETE. UI thể hiện active/revoked role, khóa submit, confirm thu hồi/quyền Admin, xử lý `ROLE_CONFLICT` và `TRAINER_PROFILE_REQUIRED`. Nút ẩn chỉ là UX; Backend last-admin guard và authorization là authority: `READY`.

### 16.4 Hội viên

`/admin/hoi-vien` và `/:id` là view theo actor trên `GET /api/admin/accounts?role=MEMBER`, không tạo CRUD Member mới và không gọi PT API. DTO account đủ cho MVP account/role/code cơ bản, không giả Membership detail: `READY` với scope account management.

### 16.5 Huấn luyện viên

Ba screen: list, tạo/onboard và detail. List dựa account filter `role=PT`: `READY`. Tạo/onboard dùng hai POST thật nhưng invitation retry và audit snapshot đang sửa: `BACKEND_FIX_IN_PROGRESS`. Detail account/role cơ bản dùng account API; phần tải/prefill đầy đủ trainer profile bị `BACKEND_API_BLOCKER` vì thiếu GET Admin trainer profile. Không hiển thị resend-invitation nếu chưa có route.

### 16.6 Nhân viên lễ tân

`/admin/nhan-vien-le-tan` và `/:id` dùng Account/Role API với `role=RECEPTIONIST`. Không invent receptionist-profile vì schema/API không có. Tạo/cấp role có thể thực hiện trên account hiện hữu qua role endpoint nếu phù hợp use case đã duyệt: `READY`.

### 16.7 Gói tập

List, tạo và detail/edit dùng `/api/admin/packages`, PATCH metadata, PUT benefits. Quyền lợi là catalog data, không suy theo tên “VIP”. UI phải cảnh báo rằng sửa catalog chỉ ảnh hưởng lần mua mới; Membership term snapshot cũ không đổi: `READY`.

### 16.8 Dụng cụ

Một màn hình list/create/edit metadata dùng `/api/admin/equipment`. Không mở rộng sang bảo trì, serial, kho tài sản hoặc booking: `READY`.

### 16.9 Nhóm cơ

Một màn hình list/create/edit qua `/api/admin/muscle-groups`, phản ánh đúng active state và conflict code: `READY`.

### 16.10 Bài tập

List, create, detail/edit qua `/api/admin/exercises`. Form chọn dụng cụ với semantics AND, nhóm cơ theo role Backend; không tạo OR group. Deactivate qua metadata update, không hard-delete: `READY`.

### 16.11 Giáo án mẫu

List, create, detail và tạo revision. Metadata PATCH không nhận cây ngày/bài; nội dung thay đổi qua POST revisions với `expected_content_version`. 409 `WORKOUT_TEMPLATE_STALE` phải giữ draft và reload/compare; không sửa/xóa cây lịch sử: `READY`.

### 16.12 Thanh toán

List và detail chỉ đọc qua `/api/admin/payments` và `/{payment}` theo branch Admin. Không thêm mutation tài chính, không coi return URL là Payment truth và không coi Payment success là Membership active: `READY` cho dữ liệu Payment đã liên kết.

### 16.13 Đối soát

`/admin/doi-soat-thanh-toan` gộp queue Payment và event table. UI chỉ đọc. Việc hiển thị event chưa gắn Payment và đưa Payment có abnormal event vào filter đang thiếu trong service hiện tại: `BACKEND_FIX_IN_PROGRESS`; FE-4 chỉ pass sau fix + Backend tests.

## 17. PT Screens

### 17.1 Đăng nhập

`/pt/dang-nhap` dùng form auth chung, yêu cầu role `PT`, xử lý profile inactive/missing theo Backend và không cho role PT tự suy ra Member scope: `READY`.

### 17.2 Hội viên được phân công

`/pt/hoi-vien` dùng `GET /api/pt/members`, chỉ danh sách exact assignments của PT hiện tại. Không có search toàn hệ thống. Page `/pt/ho-so` dùng GET/PATCH `/api/profile/trainer` cho self profile: cả hai `READY`.

### 17.3 Chi tiết Hội viên

`/pt/hoi-vien/:id` cần thông tin hồ sơ an toàn và assignment context. List hiện chỉ trả id/code/name và thông tin phân công, không đủ detail: `BACKEND_API_BLOCKER`. Shell không được ghép dữ liệu qua Admin hoặc Member-self endpoint.

### 17.4 Tiến độ

`/pt/hoi-vien/:id/tien-do` dùng ba GET PT-scoped: overview, body và exercise progress. Hiển thị weight/BMI/session/frequency/sets-reps-weight đúng DTO; không chẩn đoán y khoa hoặc tự đặt BMI category: `READY`.

### 17.5 Kế hoạch tập

`/pt/hoi-vien/:id/ke-hoach-tap` là official current/future plan read-only. Các route `/api/workout/*` hiện `MEMBER` only; PT không được gọi thay Member: `BACKEND_API_BLOCKER`.

### 17.6 Lịch sử tập

`/pt/hoi-vien/:id/lich-su-tap` cần session list/detail dưới exact current assignment. Member-self routes không dùng được: `BACKEND_API_BLOCKER`. Completed session luôn read-only khi contract được bổ sung.

### 17.7 Ghi chú

`/pt/hoi-vien/:id/ghi-chu` dùng GET/POST PT notes. Ghi chú append-only theo UI, không sửa Plan hoặc completed Session. API trả tối đa 100 bản ghi nên UI ghi rõ “100 ghi chú gần nhất” cho MVP: `READY`.

### 17.8 Buổi huấn luyện

`/pt/hoi-vien/:id/buoi-huan-luyen` dùng history và complete. Backend quyết định term, quota, assignment, activation và duplicate; Frontend không tính lượt authoritatively. Stable `Idempotency-Key` bắt buộc. History hiện tối đa 100 dòng không filter/pagination; MVP lọc client theo member và ghi rõ giới hạn, backlog API pagination là rủi ro: `READY` có giới hạn.

### 17.9 Đề xuất kế hoạch

Hai screen list và tạo Proposal. GET/POST theo assigned Member; list tối đa 100 và đã chứa content nên preview/detail dùng drawer/modal, không invent PT proposal-detail route. Q13: chỉ cần current valid assignment; không cần active Membership, Chat entitlement, PT Direct quota; không kích hoạt Membership hoặc trừ bất kỳ quota; PT không trực tiếp apply official plan. Stable idempotency và 409 conflict/stale handling: `READY`.

### 17.10 Trò chuyện realtime

`/pt/tro-chuyen` dùng conversation/message REST hiện có: `READY`; private Reverb subscription là `DEPLOYMENT_DEPENDENCY`. Q05 bắt buộc old PT không read/send/subscribe/reconnect, phải leave channel và clear sensitive state khi scope mất.

## 18. Receptionist Screens

### 18.1 Đăng nhập

`/le-tan/dang-nhap` dùng auth chung, yêu cầu `RECEPTIONIST` hoặc chọn actor nếu account nhiều role: `READY`.

### 18.2 Tra cứu Hội viên

`/le-tan/tra-cuu-hoi-vien` cần search allow-list theo code/name/phone phù hợp, branch/scope Backend. Không có route hiện tại; Account Admin route không phải workaround: `BACKEND_API_BLOCKER`.

### 18.3 Kiểm tra Membership

`/le-tan/hoi-vien/:id/trang-thai-hoi-vien` cần trạng thái kỳ/quyền vào phòng an toàn cho Receptionist. `/api/membership` là Member-self only: `BACKEND_API_BLOCKER`. FE không tính ngày hay suy quyền từ Payment.

### 18.4 QR Check-in

`/le-tan/quet-ma-vao-phong` gửi `qr_token` đến `POST /api/gym/check-in`. Camera/manual input chỉ là adapter; Backend xác thực QR 90 giây, one-time, Membership, account/role và branch. Hiển thị safe controlled outcome, không tự xác nhận QR hợp lệ: `READY`.

## 19. Realtime Architecture

FE hiện không có realtime package. Ở FE-7, Terra được phép thêm phiên bản tương thích với lockfile của `laravel-echo` và `pusher-js` theo mục 6A; không cài trong phase lập kế hoạch này.

Luồng đề xuất:

1. Tải conversations và page message mới nhất bằng REST trước.
2. Lấy conversation hiện tại bằng `POST /api/pt/chat/conversations/current` nếu cần; không tự dựng id.
3. Echo auth qua `POST /api/broadcasting/auth` với Bearer token. Client public config chỉ gồm `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME`; tuyệt đối không có app secret.
4. Subscribe private channel logic `pt.conversation.{conversationId}` (wire prefix private do Echo xử lý), nghe event `.pt.chat.message.sent`.
5. Event payload thật: `conversation_id`, `message_id`, `sequence`, `sender_id`, `sender_type`, `content`, `sent_at`.
6. Gửi REST với stable `client_message_id`; pending row được reconcile bằng server `message_id` và `sequence`, không bằng text/time.
7. Dedupe bằng `message_id`, sắp xếp bằng `sequence`. Nếu phát hiện gap hoặc reconnect, GET page mới nhất; dùng `before_sequence` lùi dần đến sequence đã biết nếu gap lớn rồi merge. API không có `after_sequence`, nên không được mô tả một endpoint catch-up không tồn tại.
8. Khi reconnect: auth lại, subscribe, REST latest/catch-up, merge rồi mới xóa trạng thái reconnecting.
9. 401: logout cleanup. 403/404, assignment biến mất khỏi `/pt/members` hoặc send trả scope loss: stop retry, leave private channel, xóa active conversation/member-sensitive cache và điều hướng về assigned list.
10. Khi đổi conversation, role, actor hoặc logout: unsubscribe channel cũ và hủy listener/timer. Không để old PT tự reconnect.

Reverb REST fallback bảo đảm tin đã commit vẫn xem được khi broadcast fail. `realtime_delivery` trong send response là trạng thái delivery, không phải business success của message.

## 20. Frontend Security

- Không `v-html` với content người dùng; render text bằng binding mặc định.
- Không đưa Gemini key, payOS secret/checksum, Reverb secret/app id server, DB credential hoặc Backend credential vào `VITE_*`.
- Không log token, Authorization header, QR token, message content hàng loạt, Payment raw payload/signature.
- Token chỉ trong memory + `sessionStorage`; CSP, dependency audit và tránh XSS quan trọng hơn việc che token bằng obfuscation.
- Guard/menu/disabled button là UX boundary; mọi request vẫn để Backend xác thực role, entitlement và resource scope.
- Chỉ gửi payload allow-list; không tin `price`, `status`, `remaining quota`, `membership active`, role hoặc ownership từ editable form.
- Không phân biệt 404 concealed resource với nonexistent resource trên UI.
- Q05 clear store/subscription ngay khi scope mất; không giữ dữ liệu Member cũ trong tab.
- Payment page không có mutation authority; webhook Backend là Payment truth.
- Completed workout/session không có edit control; draft proposal không phải official plan.
- Redirect sau login chỉ dùng internal route name allow-list; không open redirect.
- Staging/production phải giới hạn Reverb `allowed_origins`, dùng HTTPS/WSS và exact Web origin.

## 21. Accessibility / UX

- Mọi input có label, help/error liên kết `aria-describedby`; lỗi tổng có `aria-live`.
- Keyboard dùng được cho sidebar, table action, dialog và QR manual entry; focus visible không bị xóa.
- Dialog giữ focus, Escape/close hợp lý và trả focus về trigger; destructive/important action có tên cụ thể.
- Button pending có text trạng thái, `disabled` và `aria-busy`; không chỉ đổi màu.
- Badge luôn có text/icon, không truyền trạng thái chỉ bằng màu.
- Loading/empty/error có component rõ; không blank page trong lúc fetch.
- Mobile-width hỗ trợ drawer, table scroll/card fallback và form một cột; desktop tối ưu nghiệp vụ staff.
- Sau navigation/mutation lỗi, focus đến heading hoặc field lỗi đầu tiên.
- QR camera có fallback nhập/dán token; lỗi permission camera có hướng dẫn ngắn.
- UI label tiếng Việt có dấu; mã kỹ thuật chỉ hiện trong chi tiết hỗ trợ khi hữu ích, không đẩy clutter nội bộ cho người dùng thường.

## 22. API Readiness Matrix

Quy ước Backend Status chỉ dùng đúng năm giá trị được duyệt. Frontend Status là trạng thái kế hoạch, không phải kết quả triển khai. Ma trận có 65 capability rows; count cuối bảng phải được giữ đồng bộ khi contract thay đổi.

| # | Feature | Actor | Frontend URL | Page File | Backend Route | HTTP Method | Authentication | Role | Entitlement | Resource Scope | Idempotency | Realtime | Backend Status | Frontend Status |
| ---: | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | Restore current session | Chung | Mọi route bảo vệ | `khong_co_page` | `/api/auth/me` | GET | Bearer | Active role bất kỳ | Không | Current token user | Không | Không | READY | PLANNED |
| 2 | Chọn portal cho multi-role | Chung | `/chon-vai-tro` | `pages/chung/chon_vai_tro/chon_vai_tro.index.vue` | Dùng roles từ `/api/auth/login` + `/api/auth/me` | GET data đã có | Bearer | ADMIN/PT/RECEPTIONIST | Không | Current user roles | Không | Không | READY | PLANNED |
| 3 | Đăng nhập Admin | ADMIN | `/admin/dang-nhap` | `pages/admin/dang_nhap/dang_nhap.index.vue` | `/api/auth/login` | POST | Public | ADMIN sau login | Không | Account hiện tại | Không | Không | READY | PLANNED |
| 4 | Đăng nhập PT | PT | `/pt/dang-nhap` | `pages/pt/dang_nhap/dang_nhap.index.vue` | `/api/auth/login` | POST | Public | PT sau login | Không | Account hiện tại | Không | Không | READY | PLANNED |
| 5 | Đăng nhập Receptionist | RECEPTIONIST | `/le-tan/dang-nhap` | `pages/le_tan/dang_nhap/dang_nhap.index.vue` | `/api/auth/login` | POST | Public | RECEPTIONIST sau login | Không | Account hiện tại | Không | Không | READY | PLANNED |
| 6 | Quên mật khẩu | Chung | `/quen-mat-khau` | `pages/chung/quen_mat_khau/quen_mat_khau.index.vue` | `/api/auth/forgot-password` | POST | Public | — | Không | Generic, chống email enumeration | Không | Không | READY | PLANNED |
| 7 | Đặt lại mật khẩu | Chung | `/dat-lai-mat-khau` | `pages/chung/dat_lai_mat_khau/dat_lai_mat_khau.index.vue` | `/api/auth/reset-password` | POST | Public | — | Valid reset token | Không | Không | READY | PLANNED |
| 8 | Đăng xuất | Chung | Mọi layout | `khong_co_page` | `/api/auth/logout` | POST | Bearer | Active role bất kỳ | Không | Current token only | Không | Cleanup realtime | READY | PLANNED |
| 9 | Dashboard | ADMIN | `/admin/bang-dieu-khien` | `pages/admin/bang_dieu_khien/bang_dieu_khien.index.vue` | `/api/admin/dashboard` | GET | Bearer | ADMIN | Không | Admin branch, bounded dates | Không | Không | READY | PLANNED |
| 10 | Danh sách/search tài khoản | ADMIN | `/admin/tai-khoan` | `pages/admin/tai_khoan/tai_khoan.index.vue` | `/api/admin/accounts` | GET | Bearer | ADMIN | Không | Admin branch; whitelist filter | Không | Không | READY | PLANNED |
| 11 | Chi tiết tài khoản | ADMIN | `/admin/tai-khoan/:id` | `pages/admin/tai_khoan/tai_khoan.chi_tiet.vue` | `/api/admin/accounts/{account}` | GET | Bearer | ADMIN | Không | Admin branch account | Không | Không | READY | PLANNED |
| 12 | Cập nhật trạng thái account | ADMIN | `/admin/tai-khoan/:id` | `pages/admin/tai_khoan/tai_khoan.chi_tiet.vue` | `/api/admin/accounts/{account}/status` | PATCH | Bearer | ADMIN | Không | Account + last-admin guard | Không; refetch khi timeout | Không | READY | PLANNED |
| 13 | Cấp/cấp lại role | ADMIN | `/admin/tai-khoan/:id` | `pages/admin/tai_khoan/tai_khoan.chi_tiet.vue` | `/api/admin/accounts/{account}/roles/{role}` | PUT | Bearer | ADMIN | Không | Account/role; PT cần profile | Target-state no-op; không header | Không | READY | PLANNED |
| 14 | Thu hồi role | ADMIN | `/admin/tai-khoan/:id` | `pages/admin/tai_khoan/tai_khoan.chi_tiet.vue` | `/api/admin/accounts/{account}/roles/{role}` | DELETE | Bearer | ADMIN | Không | Account/role + last-admin guard | Target-state no-op; không header | Không | READY | PLANNED |
| 15 | Danh sách Hội viên theo account | ADMIN | `/admin/hoi-vien` | `pages/admin/hoi_vien/hoi_vien.index.vue` | `/api/admin/accounts?role=MEMBER` | GET | Bearer | ADMIN | Không | Admin branch | Không | Không | READY | PLANNED |
| 16 | Chi tiết Hội viên cơ bản | ADMIN | `/admin/hoi-vien/:id` | `pages/admin/hoi_vien/hoi_vien.chi_tiet.vue` | `/api/admin/accounts/{account}` | GET | Bearer | ADMIN | Không | Admin branch; account DTO | Không | Không | READY | PLANNED |
| 17 | Danh sách PT | ADMIN | `/admin/huan-luyen-vien` | `pages/admin/huan_luyen_vien/huan_luyen_vien.index.vue` | `/api/admin/accounts?role=PT` | GET | Bearer | ADMIN | Không | Admin branch | Không | Không | READY | PLANNED |
| 18 | Tạo account + profile + PT role | ADMIN | `/admin/huan-luyen-vien/tao-moi` | `pages/admin/huan_luyen_vien/huan_luyen_vien.tao_moi.vue` | `/api/admin/trainers` | POST | Bearer | ADMIN | Không | New trainer in Admin branch | Header `Idempotency-Key` | Queue invitation | BACKEND_FIX_IN_PROGRESS | WAITING_BACKEND_FIX |
| 19 | Onboard account hiện hữu thành PT | ADMIN | `/admin/huan-luyen-vien/tao-moi` | `pages/admin/huan_luyen_vien/huan_luyen_vien.tao_moi.vue` | `/api/admin/accounts/{account}/trainer-profile` | POST | Bearer | ADMIN | Không | Existing account in branch | Header `Idempotency-Key` | Queue invitation | BACKEND_FIX_IN_PROGRESS | WAITING_BACKEND_FIX |
| 20 | Đọc/prefill trainer profile cho Admin | ADMIN | `/admin/huan-luyen-vien/:id` | `pages/admin/huan_luyen_vien/huan_luyen_vien.chi_tiet.vue` | **Thiếu**; tối thiểu `GET /api/admin/accounts/{account}/trainer-profile` | GET | Bearer | ADMIN | Không | Admin branch trainer account | Không | Không | BACKEND_API_BLOCKER | BLOCKED |
| 21 | Danh sách Receptionist | ADMIN | `/admin/nhan-vien-le-tan` | `pages/admin/nhan_vien_le_tan/nhan_vien_le_tan.index.vue` | `/api/admin/accounts?role=RECEPTIONIST` | GET | Bearer | ADMIN | Không | Admin branch | Không | Không | READY | PLANNED |
| 22 | Chi tiết Receptionist | ADMIN | `/admin/nhan-vien-le-tan/:id` | `pages/admin/nhan_vien_le_tan/nhan_vien_le_tan.chi_tiet.vue` | `/api/admin/accounts/{account}` | GET | Bearer | ADMIN | Không | Admin branch; không profile riêng | Không | Không | READY | PLANNED |
| 23 | Tạo PT assignment | ADMIN | `/admin/phan-cong-pt` | `pages/admin/phan_cong_pt/phan_cong_pt.index.vue` | `/api/pt/assignments` | POST | Bearer | ADMIN | Không | Member/PT + non-overlap interval | Không; refetch cần query API | Không | READY | PLANNED_AFTER_BLOCKER |
| 24 | List/detail/history PT assignment | ADMIN | `/admin/phan-cong-pt` | `pages/admin/phan_cong_pt/phan_cong_pt.index.vue` | **Thiếu**; tối thiểu `GET /api/admin/pt/assignments` và `/{assignment}` | GET | Bearer | ADMIN | Không | Admin branch + member/PT filters | Không | Không | BACKEND_API_BLOCKER | BLOCKED |
| 25 | Kết thúc PT assignment | ADMIN | `/admin/phan-cong-pt` | `pages/admin/phan_cong_pt/phan_cong_pt.index.vue` | `/api/pt/assignments/{assignment}/end` | PATCH | Bearer | ADMIN | Không | Exact assignment; server time | Không; refetch sau mutation | Q05 scope effect | READY | PLANNED_AFTER_BLOCKER |
| 26 | Phân công lại PT | ADMIN | `/admin/phan-cong-pt` | `pages/admin/phan_cong_pt/phan_cong_pt.index.vue` | `/api/pt/assignments/{assignment}/reassign` | POST | Bearer | ADMIN | Không | Exact old/new interval, one current PT | Không; refetch sau mutation | Q05 scope effect | READY | PLANNED_AFTER_BLOCKER |
| 27 | Đọc Package catalog | ADMIN | `/admin/goi-tap`, `/:id` | `pages/admin/goi_tap/goi_tap.index.vue`; `goi_tap.chi_tiet.vue` | `/api/admin/packages`, `/{package}` | GET | Bearer | ADMIN | Không | Admin catalog | Không | Không | READY | PLANNED |
| 28 | Tạo Package | ADMIN | `/admin/goi-tap/tao-moi` | `pages/admin/goi_tap/goi_tap.tao_moi.vue` | `/api/admin/packages` | POST | Bearer | ADMIN | Không | Catalog | Không; code conflict controlled | Không | READY | PLANNED |
| 29 | Sửa metadata Package | ADMIN | `/admin/goi-tap/:id` | `pages/admin/goi_tap/goi_tap.chi_tiet.vue` | `/api/admin/packages/{package}` | PATCH | Bearer | ADMIN | Không | Future catalog only | Không; refetch | Không | READY | PLANNED |
| 30 | Sửa Package benefits | ADMIN | `/admin/goi-tap/:id` | `pages/admin/goi_tap/goi_tap.chi_tiet.vue` | `/api/admin/packages/{package}/benefits` | PUT | Bearer | ADMIN | Không | Future catalog; old term snapshot immutable | Không; refetch | Không | READY | PLANNED |
| 31 | Đọc Equipment | ADMIN | `/admin/dung-cu` | `pages/admin/dung_cu/dung_cu.index.vue` | `/api/admin/equipment` | GET | Bearer | ADMIN | Không | Metadata catalog | Không | Không | READY | PLANNED |
| 32 | Tạo Equipment | ADMIN | `/admin/dung-cu` | `pages/admin/dung_cu/dung_cu.index.vue` | `/api/admin/equipment` | POST | Bearer | ADMIN | Không | Metadata catalog | Không; conflict controlled | Không | READY | PLANNED |
| 33 | Sửa/deactivate Equipment | ADMIN | `/admin/dung-cu` | `pages/admin/dung_cu/dung_cu.index.vue` | `/api/admin/equipment/{equipment}` | PATCH | Bearer | ADMIN | Không | Metadata catalog; no hard-delete | Không; refetch | Không | READY | PLANNED |
| 34 | Đọc Muscle Group | ADMIN | `/admin/nhom-co` | `pages/admin/nhom_co/nhom_co.index.vue` | `/api/admin/muscle-groups` | GET | Bearer | ADMIN | Không | Catalog | Không | Không | READY | PLANNED |
| 35 | Tạo Muscle Group | ADMIN | `/admin/nhom-co` | `pages/admin/nhom_co/nhom_co.index.vue` | `/api/admin/muscle-groups` | POST | Bearer | ADMIN | Không | Catalog | Không; conflict controlled | Không | READY | PLANNED |
| 36 | Sửa/deactivate Muscle Group | ADMIN | `/admin/nhom-co` | `pages/admin/nhom_co/nhom_co.index.vue` | `/api/admin/muscle-groups/{muscleGroup}` | PATCH | Bearer | ADMIN | Không | Catalog; no hard-delete | Không; refetch | Không | READY | PLANNED |
| 37 | Đọc Exercise list/detail | ADMIN | `/admin/bai-tap`, `/:id` | `pages/admin/bai_tap/bai_tap.index.vue`; `bai_tap.chi_tiet.vue` | `/api/admin/exercises`, `/{exercise}` | GET | Bearer | ADMIN | Không | Admin catalog; max 500 list | Không | Không | READY | PLANNED |
| 38 | Tạo Exercise | ADMIN | `/admin/bai-tap/tao-moi` | `pages/admin/bai_tap/bai_tap.tao_moi.vue` | `/api/admin/exercises` | POST | Bearer | ADMIN | Không | Active relations; equipment AND | Không; conflict controlled | Không | READY | PLANNED |
| 39 | Sửa/deactivate Exercise | ADMIN | `/admin/bai-tap/:id` | `pages/admin/bai_tap/bai_tap.chi_tiet.vue` | `/api/admin/exercises/{exercise}` | PATCH | Bearer | ADMIN | Không | Future catalog; no history rewrite | Không; refetch | Không | READY | PLANNED |
| 40 | Đọc Workout Template | ADMIN | `/admin/giao-an-mau`, `/:id` | `pages/admin/giao_an_mau/giao_an_mau.index.vue`; `giao_an_mau.chi_tiet.vue` | `/api/admin/workout-templates`, `/{workoutTemplate}` | GET | Bearer | ADMIN | Không | Admin catalog | Không | Không | READY | PLANNED |
| 41 | Tạo Workout Template | ADMIN | `/admin/giao-an-mau/tao-moi` | `pages/admin/giao_an_mau/giao_an_mau.tao_moi.vue` | `/api/admin/workout-templates` | POST | Bearer | ADMIN | Không | Valid active exercises/tree | Không; conflict controlled | Không | READY | PLANNED |
| 42 | Sửa metadata/deactivate Template | ADMIN | `/admin/giao-an-mau/:id` | `pages/admin/giao_an_mau/giao_an_mau.chi_tiet.vue` | `/api/admin/workout-templates/{workoutTemplate}` | PATCH | Bearer | ADMIN | Không | Metadata only; no `days` | Không; refetch | Không | READY | PLANNED |
| 43 | Tạo Template revision | ADMIN | `/admin/giao-an-mau/:id/tao-phien-ban` | `pages/admin/giao_an_mau/giao_an_mau.tao_phien_ban.vue` | `/api/admin/workout-templates/{workoutTemplate}/revisions` | POST | Bearer | ADMIN | Không | Copy-on-write, active exercise | `expected_content_version` | Không | READY | PLANNED |
| 44 | Danh sách Payment | ADMIN | `/admin/thanh-toan` | `pages/admin/thanh_toan/thanh_toan.index.vue` | `/api/admin/payments` | GET | Bearer | ADMIN | Không | Admin branch; read-only | Không | Không | READY | PLANNED |
| 45 | Chi tiết Payment | ADMIN | `/admin/thanh-toan/:id` | `pages/admin/thanh_toan/thanh_toan.chi_tiet.vue` | `/api/admin/payments/{payment}` | GET | Bearer | ADMIN | Không | Admin branch; safe DTO | Không | Không | READY | PLANNED |
| 46 | Payment events kể cả unlinked | ADMIN | `/admin/doi-soat-thanh-toan` | `pages/admin/doi_soat_thanh_toan/doi_soat_thanh_toan.index.vue` | `/api/admin/payment-events` | GET | Bearer | ADMIN | Không | Branch-safe; `payment_id` có thể null | Không | Không | BACKEND_FIX_IN_PROGRESS | WAITING_BACKEND_FIX |
| 47 | Reconciliation filter gồm abnormal event | ADMIN | `/admin/doi-soat-thanh-toan` | `pages/admin/doi_soat_thanh_toan/doi_soat_thanh_toan.index.vue` | `/api/admin/payments?reconciliation_required=1` | GET | Bearer | ADMIN | Không | Admin branch; read-only | Không | Không | BACKEND_FIX_IN_PROGRESS | WAITING_BACKEND_FIX |
| 48 | PT self profile read/update | PT | `/pt/ho-so` | `pages/pt/ho_so/ho_so.index.vue` | `/api/profile/trainer` | GET/PATCH | Bearer | PT | Không | Own trainer profile safe fields | Không; refetch after PATCH | Không | READY | PLANNED |
| 49 | Assigned Member list | PT | `/pt/hoi-vien` | `pages/pt/hoi_vien/hoi_vien.index.vue` | `/api/pt/members` | GET | Bearer | PT | Không | Exact active assignment scope | Không | Không | READY | PLANNED |
| 50 | Assigned Member profile detail | PT | `/pt/hoi-vien/:id` | `pages/pt/hoi_vien/hoi_vien.chi_tiet.vue` | **Thiếu**; tối thiểu `GET /api/pt/members/{member}` | GET | Bearer | PT | Không | Exact current assignment; safe fields | Không | Không | BACKEND_API_BLOCKER | BLOCKED |
| 51 | PT xem Progress | PT | `/pt/hoi-vien/:id/tien-do` | `pages/pt/tien_do/tien_do.index.vue` | `/api/pt/members/{member}/progress/overview`, `/body`, `/exercises/{exercise}` | GET | Bearer | PT | Không cần Membership active để đọc history | Exact current assignment | Không | Không | READY | PLANNED |
| 52 | PT xem official current/future Plan | PT | `/pt/hoi-vien/:id/ke-hoach-tap` | `pages/pt/ke_hoach_tap/ke_hoach_tap.index.vue` | **Thiếu**; tối thiểu `GET /api/pt/members/{member}/workout/plans/current` | GET | Bearer | PT | Workout không phụ thuộc Membership | Exact current assignment | Không | Không | BACKEND_API_BLOCKER | BLOCKED |
| 53 | PT xem completed Workout History | PT | `/pt/hoi-vien/:id/lich-su-tap` | `pages/pt/lich_su_tap/lich_su_tap.index.vue` | **Thiếu**; tối thiểu GET sessions list/detail dưới `/api/pt/members/{member}/workout` | GET | Bearer | PT | Không cần Membership active để đọc history | Exact current assignment; completed immutable | Không | Không | BACKEND_API_BLOCKER | BLOCKED |
| 54 | PT xem/thêm Notes | PT | `/pt/hoi-vien/:id/ghi-chu` | `pages/pt/ghi_chu/ghi_chu.index.vue` | `/api/pt/members/{member}/notes` | GET/POST | Bearer | PT | Không | Exact current assignment; max 100 | Không | Không | READY | PLANNED |
| 55 | PT direct-session history | PT | `/pt/hoi-vien/:id/buoi-huan-luyen` | `pages/pt/buoi_huan_luyen/buoi_huan_luyen.index.vue` | `/api/pt/direct-sessions` | GET | Bearer | PT | History read theo actor | Own history; max 100, client filter MVP | Không | Không | READY | PLANNED_WITH_LIMIT |
| 56 | PT xác nhận direct session | PT | `/pt/hoi-vien/:id/buoi-huan-luyen` | `pages/pt/buoi_huan_luyen/buoi_huan_luyen.index.vue` | `/api/pt/direct-sessions/complete` | POST | Bearer | PT | Current/activatable paid PT term + remaining direct quota | Exact active assignment | Header `Idempotency-Key` | Không | READY | PLANNED |
| 57 | PT Proposal list/preview | PT | `/pt/hoi-vien/:id/de-xuat` | `pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.index.vue` | `/api/pt/members/{member}/proposals` | GET | Bearer | PT | Q13: không Membership/Chat/direct quota | Exact current assignment; max 100 | Không | Không | READY | PLANNED |
| 58 | PT tạo Proposal | PT | `/pt/hoi-vien/:id/de-xuat/tao-moi` | `pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.tao_moi.vue` | `/api/pt/members/{member}/proposals` | POST | Bearer | PT | Q13: valid assignment only | Exact current assignment + base context | Header `Idempotency-Key` | Không | READY | PLANNED |
| 59 | Chat conversation/history REST | PT | `/pt/tro-chuyen` | `pages/pt/tro_chuyen/tro_chuyen.index.vue` | `/api/pt/chat/conversations`, `/current`, `/{conversation}`, `/messages` | GET/POST | Bearer | PT | Chat read/send rules theo term; historical scope theo Q05 | Exact conversation assignment; old PT denied | Không | REST fallback | READY | PLANNED |
| 60 | Gửi Chat message REST | PT | `/pt/tro-chuyen` | `pages/pt/tro_chuyen/tro_chuyen.index.vue` | `/api/pt/chat/conversations/{conversation}/messages` | POST | Bearer | PT | Chat entitlement; PT send không tự kích hoạt Membership | Exact current conversation assignment | Body `client_message_id` | Outbox publishes event | READY | PLANNED |
| 61 | Private realtime Chat | PT | `/pt/tro-chuyen` | `pages/pt/tro_chuyen/tro_chuyen.index.vue` | `/api/broadcasting/auth`; channel `pt.conversation.{id}` | POST + WS/WSS | Bearer | PT | Chat/channel rules | Q05 exact assignment; old PT denied | Dedupe by message id/sequence | Có | DEPLOYMENT_DEPENDENCY | WAITING_DEPLOYMENT |
| 62 | Receptionist tra cứu Member | RECEPTIONIST | `/le-tan/tra-cuu-hoi-vien` | `pages/le_tan/tra_cuu_hoi_vien/tra_cuu_hoi_vien.index.vue` | **Thiếu**; tối thiểu `GET /api/receptionist/members` | GET | Bearer | RECEPTIONIST | Không | Branch + safe allow-list | Không | Không | BACKEND_API_BLOCKER | BLOCKED |
| 63 | Receptionist xem Membership status | RECEPTIONIST | `/le-tan/hoi-vien/:id/trang-thai-hoi-vien` | `pages/le_tan/trang_thai_hoi_vien/trang_thai_hoi_vien.chi_tiet.vue` | **Thiếu**; tối thiểu `GET /api/receptionist/members/{member}/membership` | GET | Bearer | RECEPTIONIST | Backend-calculated current Gym eligibility | Branch/member safe view | Không | Không | BACKEND_API_BLOCKER | BLOCKED |
| 64 | QR check-in | RECEPTIONIST | `/le-tan/quet-ma-vao-phong` | `pages/le_tan/quet_ma_vao_phong/quet_ma_vao_phong.index.vue` | `/api/gym/check-in` | POST | Bearer | RECEPTIONIST (Backend cũng cho ADMIN) | Valid/activatable Gym entitlement | QR/member/branch revalidated | QR token one-time; controlled replay | Không | READY | PLANNED |
| 65 | Member Web portal | MEMBER | Không tạo | `khong_tao` | Member APIs hiện có dành Mobile | Nhiều | Bearer | MEMBER | Theo từng capability | Member self | Theo API | Có thể có | OUT_OF_SCOPE | OUT_OF_SCOPE |

| Tổng Backend Status | Số capability |
| --- | ---: |
| READY | 52 |
| BACKEND_FIX_IN_PROGRESS | 4 |
| BACKEND_API_BLOCKER | 7 |
| DEPLOYMENT_DEPENDENCY | 1 |
| OUT_OF_SCOPE | 1 |
| **Tổng** | **65** |

## 23. Screen Inventory

Mỗi record dưới đây chứa đủ 18 thuộc tính bắt buộc. “Docblock” chỉ liệt kê hàm quan trọng; các helper trình bày đơn giản không cần comment hình thức.

### 23.1 Chọn vai trò

- **Tên / URL / route / file / actor:** Chọn vai trò; `/chon-vai-tro`; `chonVaiTro`; `pages/chung/chon_vai_tro/chon_vai_tro.index.vue`; Chung.
- **Purpose / Backend API / readiness:** Chọn portal khi account có nhiều role Web; `GET /api/auth/me`; `READY`.
- **Components / Pinia:** `tieu_de_trang.vue`, `trang_thai_tai_du_lieu.vue`; `xac_thuc.store.js`.
- **Main functions / required docblocks:** `taiVaiTroHienTai()`, `chonCongViec()`, `dieuPhoiTheoVaiTro()`; docblock cho `dieuPhoiTheoVaiTro()` vì cấm invent role priority và open redirect.
- **Loading / empty / error:** Skeleton khi restore; empty nếu chỉ có MEMBER với giải thích Web out-of-scope; 401 về login, 5xx cho retry.
- **Mutation / idempotency / business rules:** Chỉ đổi actor context local, không API mutation, không idempotency; Free/Premium không phải role, Backend roles là authority.

### 23.2 Quên mật khẩu

- **Tên / URL / route / file / actor:** Quên mật khẩu; `/quen-mat-khau`; `quenMatKhau`; `pages/chung/quen_mat_khau/quen_mat_khau.index.vue`; Chung.
- **Purpose / API / readiness:** Yêu cầu email đặt lại; `POST /api/auth/forgot-password`; `READY`.
- **Components / Pinia:** `truong_bieu_mau.vue`, `vung_thong_bao.vue`; không cần store.
- **Functions / docblocks:** `guiYeuCauDatLaiMatKhau()`; docblock vì response phải chống email enumeration.
- **Loading / empty / error:** Button pending; không empty; 422 field error, 429 countdown, 5xx generic.
- **Mutation / idempotency / rules:** Disable submit, không auto-retry, không header idempotency; luôn hiển thị kết quả chung, không tiết lộ email tồn tại.

### 23.3 Đặt lại mật khẩu

- **Tên / URL / route / file / actor:** Đặt lại mật khẩu; `/dat-lai-mat-khau`; `datLaiMatKhau`; `pages/chung/dat_lai_mat_khau/dat_lai_mat_khau.index.vue`; Chung.
- **Purpose / API / readiness:** Đổi mật khẩu bằng token; `POST /api/auth/reset-password`; `READY`.
- **Components / Pinia:** `truong_bieu_mau.vue`, `vung_thong_bao.vue`; không store.
- **Functions / docblocks:** `datLaiMatKhau()`, `kiemTraThamSoDatLai()`; docblock cho submit và không log token.
- **Loading / empty / error:** Pending; token thiếu là empty/invalid state; 422/429/5xx rõ ràng.
- **Mutation / idempotency / rules:** Một submit một lần, không auto-retry; không idempotency header; sau thành công xóa token khỏi URL state và về actor login.

### 23.4 Không có quyền

- **Tên / URL / route / file / actor:** Không có quyền; `/khong-co-quyen`; `khongCoQuyen`; `pages/chung/loi/khong_co_quyen.vue`; Chung.
- **Purpose / API / readiness:** UX cho 403; không API riêng; `READY`.
- **Components / Pinia:** `trang_thai_loi.vue`; `xac_thuc.store.js` để chọn home an toàn.
- **Functions / docblocks:** `veTrangPhuHop()`; docblock nếu dùng role routing.
- **Loading / empty / error:** Không loading/empty; bản thân là error state 403.
- **Mutation / idempotency / rules:** Không mutation/idempotency; không gợi ý resource có tồn tại, guard không thay Backend authorization.

### 23.5 Không tìm thấy

- **Tên / URL / route / file / actor:** Không tìm thấy; `/khong-tim-thay`; `khongTimThay`; `pages/chung/loi/khong_tim_thay.vue`; Chung.
- **Purpose / API / readiness:** Đích catch-all và resource unavailable an toàn; không API riêng; `READY`.
- **Components / Pinia:** `trang_thai_loi.vue`; tùy chọn `xac_thuc.store.js`.
- **Functions / docblocks:** `veTrangPhuHop()`; dùng chung docblock role routing.
- **Loading / empty / error:** Không loading/empty; bản thân là 404 state.
- **Mutation / idempotency / rules:** Không mutation/idempotency; 404 concealed không được phân biệt IDOR/nonexistent.

### 23.6 Đăng nhập Admin

- **Tên / URL / route / file / actor:** Đăng nhập Admin; `/admin/dang-nhap`; `adminDangNhap`; `pages/admin/dang_nhap/dang_nhap.index.vue`; ADMIN.
- **Purpose / API / readiness:** Tạo session Admin; `POST /api/auth/login`, `GET /api/auth/me`; `READY`.
- **Components / Pinia:** `bieu_mau_dang_nhap.vue`, `truong_bieu_mau.vue`; `xac_thuc.store.js`.
- **Functions / docblocks:** `dangNhap()`, `taiThongTinNguoiDung()`, `xuLyDangNhapAdmin()`; docblock cả ba auth/redirect functions.
- **Loading / empty / error:** Pending restore/submit; không empty; generic credential error, 422, 429, 5xx.
- **Mutation / idempotency / rules:** Disable submit, không auto-retry login, không idempotency; phải có role ADMIN hoặc chọn role, không ưu tiên role tự đặt.

### 23.7 Bảng điều khiển Admin

- **Tên / URL / route / file / actor:** Bảng điều khiển; `/admin/bang-dieu-khien`; `adminBangDieuKhien`; `pages/admin/bang_dieu_khien/bang_dieu_khien.index.vue`; ADMIN.
- **Purpose / API / readiness:** Hiển thị metric thực; `GET /api/admin/dashboard`; `READY`.
- **Components / Pinia:** `the_chi_so.vue`, `tieu_de_trang.vue`, query-state components; auth store, query local.
- **Functions / docblocks:** `taiTongQuanAdmin()`, `apDungKhoangNgay()`; docblock cho bounded date/timezone mapping.
- **Loading / empty / error:** Metric skeleton; zero values là valid empty; 422 filter, 403, 5xx retry.
- **Mutation / idempotency / rules:** Chỉ read, không idempotency; không invent revenue, không client-select branch.

### 23.8 Danh sách tài khoản

- **Tên / URL / route / file / actor:** Danh sách tài khoản; `/admin/tai-khoan`; `adminTaiKhoan`; `pages/admin/tai_khoan/tai_khoan.index.vue`; ADMIN.
- **Purpose / API / readiness:** Search/filter/paginate account; `GET /api/admin/accounts`; `READY`.
- **Components / Pinia:** table, pagination, filter, badge, query states; `tai_khoan.store.js`.
- **Functions / docblocks:** `taiDanhSachTaiKhoan()`, `apDungBoLocTaiKhoan()`, `moChiTietTaiKhoan()`; docblock cho fetch/filter contract.
- **Loading / empty / error:** Table skeleton; empty theo filter; 422 whitelist filter, 403, 5xx.
- **Mutation / idempotency / rules:** Không mutation; query không idempotency key; branch scope và role state từ Backend.

### 23.9 Chi tiết tài khoản

- **Tên / URL / route / file / actor:** Chi tiết tài khoản; `/admin/tai-khoan/:id`; `adminChiTietTaiKhoan`; `pages/admin/tai_khoan/tai_khoan.chi_tiet.vue`; ADMIN.
- **Purpose / API / readiness:** Xem account, đổi status và role; GET/PATCH/PUT/DELETE account routes; `READY`.
- **Components / Pinia:** badge, confirm dialog, form, error state; `tai_khoan.store.js`.
- **Functions / docblocks:** `taiChiTietTaiKhoan()`, `capNhatTrangThaiTaiKhoan()`, `capVaiTro()`, `thuHoiVaiTro()`, `xuLyXungDotVaiTro()`; docblock cho mọi mutation.
- **Loading / empty / error:** Detail skeleton; 404 unavailable; 409 role/last-admin/profile conflict, 422, 403, 5xx.
- **Mutation / idempotency / rules:** Disable per action, refetch after outcome/timeout; target-state role no-op nhưng không header idempotency; PT role cần profile, không xóa account.

### 23.10 Danh sách Hội viên Admin

- **Tên / URL / route / file / actor:** Danh sách Hội viên; `/admin/hoi-vien`; `adminHoiVien`; `pages/admin/hoi_vien/hoi_vien.index.vue`; ADMIN.
- **Purpose / API / readiness:** Member-oriented account view; `GET /api/admin/accounts?role=MEMBER`; `READY`.
- **Components / Pinia:** filter, table, pagination, badge; `tai_khoan.store.js`.
- **Functions / docblocks:** `taiDanhSachHoiVien()`, `apDungBoLocHoiVien()`; docblock cho fixed role filter.
- **Loading / empty / error:** Table skeleton; no Member/filter empty; 422/403/5xx.
- **Mutation / idempotency / rules:** Read-only list; không idempotency; không gọi PT API hoặc giả Membership.

### 23.11 Chi tiết Hội viên Admin

- **Tên / URL / route / file / actor:** Chi tiết Hội viên; `/admin/hoi-vien/:id`; `adminChiTietHoiVien`; `pages/admin/hoi_vien/hoi_vien.chi_tiet.vue`; ADMIN.
- **Purpose / API / readiness:** Account/role/member code cơ bản; `GET /api/admin/accounts/{account}`; `READY` theo scope account.
- **Components / Pinia:** detail card, badge, error state; `tai_khoan.store.js`.
- **Functions / docblocks:** `taiChiTietHoiVien()`; docblock nêu rõ không phải Membership detail.
- **Loading / empty / error:** Skeleton; 404 unavailable; 403/5xx.
- **Mutation / idempotency / rules:** Không mutation; không idempotency; không suy Membership/entitlement từ role MEMBER.

### 23.12 Danh sách Huấn luyện viên

- **Tên / URL / route / file / actor:** Danh sách Huấn luyện viên; `/admin/huan-luyen-vien`; `adminHuanLuyenVien`; `pages/admin/huan_luyen_vien/huan_luyen_vien.index.vue`; ADMIN.
- **Purpose / API / readiness:** PT account/profile-state list; `GET /api/admin/accounts?role=PT`; `READY`.
- **Components / Pinia:** table/filter/pagination/badge; `tai_khoan.store.js`.
- **Functions / docblocks:** `taiDanhSachHuanLuyenVien()`, `phanLoaiTrangThaiHoSoPt()`; docblock cho mapping profile/role state.
- **Loading / empty / error:** Skeleton; no PT; 422/403/5xx.
- **Mutation / idempotency / rules:** Read list; không idempotency; PT role không đồng nghĩa Member scope.

### 23.13 Tạo / onboarding Huấn luyện viên

- **Tên / URL / route / file / actor:** Tạo Huấn luyện viên; `/admin/huan-luyen-vien/tao-moi`; `adminTaoHuanLuyenVien`; `pages/admin/huan_luyen_vien/huan_luyen_vien.tao_moi.vue`; ADMIN.
- **Purpose / API / readiness:** Tạo trainer mới hoặc onboard account hiện hữu; hai POST onboarding; `BACKEND_FIX_IN_PROGRESS`.
- **Components / Pinia:** form, mode selector, warning/blocked banner; auth/account store để refresh.
- **Functions / docblocks:** `taoHuanLuyenVien()`, `onboardTaiKhoanThanhPt()`, `taoKhoaIdempotency()`, `xuLyTrangThaiLoiMoi()`; docblock mọi function.
- **Loading / empty / error:** Form bootstrap; account search empty; 409 conflict/in-progress, 422, queue 5xx.
- **Mutation / idempotency / rules:** Stable header key, disable, giữ draft/key khi timeout; không báo invitation “đã gửi” trước trạng thái Backend đáng tin, không invent resend.

### 23.14 Chi tiết Huấn luyện viên

- **Tên / URL / route / file / actor:** Chi tiết Huấn luyện viên; `/admin/huan-luyen-vien/:id`; `adminChiTietHuanLuyenVien`; `pages/admin/huan_luyen_vien/huan_luyen_vien.chi_tiet.vue`; ADMIN.
- **Purpose / API / readiness:** Account/role + full trainer profile; account GET có nhưng trainer profile GET thiếu; `BACKEND_API_BLOCKER`.
- **Components / Pinia:** `chan_tinh_nang_bi_chan.vue`, account detail/card; `tai_khoan.store.js`.
- **Functions / docblocks:** `taiChiTietHuanLuyenVien()`, tương lai `taiHoSoHuanLuyenVien()`; docblock resource/branch scope.
- **Loading / empty / error:** Account skeleton; profile unavailable blocker; 403/404/5xx.
- **Mutation / idempotency / rules:** Không dựng edit form trước API read; không idempotency; profile và role là hai trạng thái riêng.

### 23.15 Danh sách Nhân viên lễ tân Admin

- **Tên / URL / route / file / actor:** Danh sách Nhân viên lễ tân; `/admin/nhan-vien-le-tan`; `adminNhanVienLeTan`; `pages/admin/nhan_vien_le_tan/nhan_vien_le_tan.index.vue`; ADMIN.
- **Purpose / API / readiness:** Filter account RECEPTIONIST; `GET /api/admin/accounts?role=RECEPTIONIST`; `READY`.
- **Components / Pinia:** table/filter/pagination/badge; `tai_khoan.store.js`.
- **Functions / docblocks:** `taiDanhSachNhanVienLeTan()`; docblock cho fixed role filter.
- **Loading / empty / error:** Skeleton; no Receptionist; 422/403/5xx.
- **Mutation / idempotency / rules:** Read list; không idempotency; không invent receptionist profile.

### 23.16 Chi tiết Nhân viên lễ tân Admin

- **Tên / URL / route / file / actor:** Chi tiết Nhân viên lễ tân; `/admin/nhan-vien-le-tan/:id`; `adminChiTietNhanVienLeTan`; `pages/admin/nhan_vien_le_tan/nhan_vien_le_tan.chi_tiet.vue`; ADMIN.
- **Purpose / API / readiness:** Account/role management; GET và role/status routes; `READY`.
- **Components / Pinia:** detail card, role badge, confirm dialog; `tai_khoan.store.js`.
- **Functions / docblocks:** `taiChiTietNhanVienLeTan()`, `capNhatTrangThaiNhanVienLeTan()`, `thuHoiVaiTroLeTan()`; mutation functions có docblock.
- **Loading / empty / error:** Skeleton; 404; 409/422/403/5xx.
- **Mutation / idempotency / rules:** Disable/refetch, không header idempotency; role removal có hiệu lực ngay qua Backend.

### 23.17 Quản lý phân công PT

- **Tên / URL / route / file / actor:** Quản lý phân công PT; `/admin/phan-cong-pt`; `adminPhanCongPt`; `pages/admin/phan_cong_pt/phan_cong_pt.index.vue`; ADMIN.
- **Purpose / API / readiness:** List/history, create/end/reassign; mutation routes có, read routes thiếu; `BACKEND_API_BLOCKER` cho screen hoàn chỉnh.
- **Components / Pinia:** blocked banner, future table/form/confirm; account store cho selectors, không store assignment trước contract.
- **Functions / docblocks:** tương lai `taiDanhSachPhanCongPt()`, `taoPhanCongPt()`, `ketThucPhanCongPt()`, `phanCongLaiPt()`; tất cả có docblock interval/Q05.
- **Loading / empty / error:** Blocker hiện tại; sau API có skeleton/no history; 409 assignment conflict, 422/403/404/5xx.
- **Mutation / idempotency / rules:** Không triển khai workaround; API mutation không có key nên disable + refetch bắt buộc; one current PT, half-open interval, Q05 cleanup do Backend/channel.

### 23.18 Danh sách Gói tập

- **Tên / URL / route / file / actor:** Danh sách Gói tập; `/admin/goi-tap`; `adminGoiTap`; `pages/admin/goi_tap/goi_tap.index.vue`; ADMIN.
- **Purpose / API / readiness:** Xem catalog Package; `GET /api/admin/packages`; `READY`.
- **Components / Pinia:** table/filter/badge/query states; local hoặc `danh_muc.store.js`.
- **Functions / docblocks:** `taiDanhSachGoiTap()`; docblock snapshot caveat khi điều hướng edit.
- **Loading / empty / error:** Skeleton; catalog empty; 403/5xx.
- **Mutation / idempotency / rules:** Read; không idempotency; package name không quyết định entitlement.

### 23.19 Tạo Gói tập

- **Tên / URL / route / file / actor:** Tạo Gói tập; `/admin/goi-tap/tao-moi`; `adminTaoGoiTap`; `pages/admin/goi_tap/goi_tap.tao_moi.vue`; ADMIN.
- **Purpose / API / readiness:** Tạo Package metadata/benefits theo contract; POST package, sau đó PUT benefits nếu workflow cần; `READY`.
- **Components / Pinia:** form/benefit editor/confirm; danh mục local.
- **Functions / docblocks:** `taoGoiTap()`, `capNhatQuyenLoiGoiTap()`, `xuLyTaoGoiTapNhieuBuoc()`; docblock transaction boundary FE và recovery.
- **Loading / empty / error:** Form loading options; no benefit options; 409 package conflict, 422, 403, 5xx.
- **Mutation / idempotency / rules:** Không header key; disable từng step, refetch theo code nếu timeout; không gộp quyền theo tên VIP.

### 23.20 Chi tiết / chỉnh sửa Gói tập

- **Tên / URL / route / file / actor:** Chi tiết Gói tập; `/admin/goi-tap/:id`; `adminChiTietGoiTap`; `pages/admin/goi_tap/goi_tap.chi_tiet.vue`; ADMIN.
- **Purpose / API / readiness:** GET detail, PATCH metadata, PUT benefits; `READY`.
- **Components / Pinia:** form, benefits editor, status badge, warning; `danh_muc.store.js` tùy cache.
- **Functions / docblocks:** `taiChiTietGoiTap()`, `capNhatGoiTap()`, `thayTheQuyenLoiGoiTap()`; mutations có docblock snapshot.
- **Loading / empty / error:** Skeleton; 404; 409/422/403/5xx.
- **Mutation / idempotency / rules:** Disable/refetch; không header key; catalog update không sửa term snapshot đã mua.

### 23.21 Quản lý Dụng cụ

- **Tên / URL / route / file / actor:** Quản lý Dụng cụ; `/admin/dung-cu`; `adminDungCu`; `pages/admin/dung_cu/dung_cu.index.vue`; ADMIN.
- **Purpose / API / readiness:** List/create/edit/deactivate metadata; Equipment routes; `READY`.
- **Components / Pinia:** table, inline form/dialog, badge; `danh_muc.store.js`.
- **Functions / docblocks:** `taiDanhSachDungCu()`, `taoDungCu()`, `capNhatDungCu()`; mutations có docblock no-hard-delete.
- **Loading / empty / error:** Skeleton; empty catalog; 409 conflict, 422/403/5xx.
- **Mutation / idempotency / rules:** Disable/refetch; không header key; không mở rộng asset maintenance/inventory.

### 23.22 Quản lý Nhóm cơ

- **Tên / URL / route / file / actor:** Quản lý Nhóm cơ; `/admin/nhom-co`; `adminNhomCo`; `pages/admin/nhom_co/nhom_co.index.vue`; ADMIN.
- **Purpose / API / readiness:** List/create/edit/deactivate; Muscle Group routes; `READY`.
- **Components / Pinia:** table, form/dialog, badge; `danh_muc.store.js`.
- **Functions / docblocks:** `taiDanhSachNhomCo()`, `taoNhomCo()`, `capNhatNhomCo()`; mutation docblocks.
- **Loading / empty / error:** Skeleton; empty; 409/422/403/5xx.
- **Mutation / idempotency / rules:** Disable/refetch; không header key; giữ code/status Backend.

### 23.23 Danh sách Bài tập

- **Tên / URL / route / file / actor:** Danh sách Bài tập; `/admin/bai-tap`; `adminBaiTap`; `pages/admin/bai_tap/bai_tap.index.vue`; ADMIN.
- **Purpose / API / readiness:** List/filter exercise; `GET /api/admin/exercises`; `READY`.
- **Components / Pinia:** filter/table/badge/query states; `danh_muc.store.js`.
- **Functions / docblocks:** `taiDanhSachBaiTap()`, `apDungBoLocBaiTap()`; fetch/filter docblock, ghi giới hạn max 500.
- **Loading / empty / error:** Skeleton; filter empty; 422/403/5xx.
- **Mutation / idempotency / rules:** Read; không idempotency; không client-side coi inactive item hợp lệ cho template mới.

### 23.24 Tạo Bài tập

- **Tên / URL / route / file / actor:** Tạo Bài tập; `/admin/bai-tap/tao-moi`; `adminTaoBaiTap`; `pages/admin/bai_tap/bai_tap.tao_moi.vue`; ADMIN.
- **Purpose / API / readiness:** Create exercise + equipment/muscle relations; `POST /api/admin/exercises`; `READY`.
- **Components / Pinia:** form, multi-select, relation editor; `danh_muc.store.js`.
- **Functions / docblocks:** `taiLuaChonDanhMuc()`, `taoBaiTap()`, `taoPayloadQuanHeBaiTap()`; docblock equipment AND/allow-list.
- **Loading / empty / error:** Options skeleton; empty active options handled; 409 conflict, 422 structure, 403/5xx.
- **Mutation / idempotency / rules:** Disable/refetch; không header key; equipment relations là AND, no OR group.

### 23.25 Chi tiết / chỉnh sửa Bài tập

- **Tên / URL / route / file / actor:** Chi tiết Bài tập; `/admin/bai-tap/:id`; `adminChiTietBaiTap`; `pages/admin/bai_tap/bai_tap.chi_tiet.vue`; ADMIN.
- **Purpose / API / readiness:** GET/PATCH exercise; `READY`.
- **Components / Pinia:** form, relations, badge, query states; `danh_muc.store.js`.
- **Functions / docblocks:** `taiChiTietBaiTap()`, `capNhatBaiTap()`, `voHieuHoaBaiTap()`; mutations có docblock history/catalog effect.
- **Loading / empty / error:** Skeleton; 404; 409/422/403/5xx.
- **Mutation / idempotency / rules:** Disable/refetch; không header key; deactivate, không hard-delete hoặc rewrite history.

### 23.26 Danh sách Giáo án mẫu

- **Tên / URL / route / file / actor:** Danh sách Giáo án mẫu; `/admin/giao-an-mau`; `adminGiaoAnMau`; `pages/admin/giao_an_mau/giao_an_mau.index.vue`; ADMIN.
- **Purpose / API / readiness:** List template versions/metadata; `GET /api/admin/workout-templates`; `READY`.
- **Components / Pinia:** table/filter/badge; local query + danh mục options.
- **Functions / docblocks:** `taiDanhSachGiaoAnMau()`; docblock version/status mapping.
- **Loading / empty / error:** Skeleton; no template; 403/5xx.
- **Mutation / idempotency / rules:** Read; không idempotency; inactive không xóa history.

### 23.27 Tạo Giáo án mẫu

- **Tên / URL / route / file / actor:** Tạo Giáo án mẫu; `/admin/giao-an-mau/tao-moi`; `adminTaoGiaoAnMau`; `pages/admin/giao_an_mau/giao_an_mau.tao_moi.vue`; ADMIN.
- **Purpose / API / readiness:** Create metadata + initial tree đúng contract; `POST /api/admin/workout-templates`; `READY`.
- **Components / Pinia:** form, `cay_giao_an.vue`, exercise selector; `danh_muc.store.js`.
- **Functions / docblocks:** `taoGiaoAnMau()`, `kiemTraCayGiaoAnNhap()`, `taoPayloadCayGiaoAn()`; docblock tree semantics.
- **Loading / empty / error:** Exercise options skeleton; empty day invalid state; 409/422 invalid structure/exercise, 403/5xx.
- **Mutation / idempotency / rules:** Disable, preserve draft; không header key; server validates authority/active exercises.

### 23.28 Chi tiết Giáo án mẫu

- **Tên / URL / route / file / actor:** Chi tiết Giáo án mẫu; `/admin/giao-an-mau/:id`; `adminChiTietGiaoAnMau`; `pages/admin/giao_an_mau/giao_an_mau.chi_tiet.vue`; ADMIN.
- **Purpose / API / readiness:** Read tree, edit metadata/deactivate; GET/PATCH template; `READY`.
- **Components / Pinia:** tree read view, metadata form, badge; local/danh mục.
- **Functions / docblocks:** `taiChiTietGiaoAnMau()`, `capNhatThongTinGiaoAnMau()`; docblock cấm gửi `days` qua PATCH.
- **Loading / empty / error:** Skeleton; 404; 409/422/403/5xx.
- **Mutation / idempotency / rules:** PATCH metadata only, refetch; không key; content history bất biến.

### 23.29 Tạo phiên bản Giáo án mẫu

- **Tên / URL / route / file / actor:** Tạo phiên bản Giáo án mẫu; `/admin/giao-an-mau/:id/tao-phien-ban`; `adminTaoPhienBanGiaoAnMau`; `pages/admin/giao_an_mau/giao_an_mau.tao_phien_ban.vue`; ADMIN.
- **Purpose / API / readiness:** Copy-on-write content revision; POST revisions; `READY`.
- **Components / Pinia:** tree editor, stale dialog, form errors; `danh_muc.store.js`.
- **Functions / docblocks:** `taiNenGiaoAnMau()`, `taoPhienBanGiaoAnMau()`, `xuLyGiaoAnMauDaCu()`; tất cả có docblock.
- **Loading / empty / error:** Base skeleton; missing template 404; `WORKOUT_TEMPLATE_STALE`, invalid structure/exercise, 422/5xx.
- **Mutation / idempotency / rules:** Stable `expected_content_version` của loaded base, không auto-retry stale; giữ draft/compare; không hard-delete old tree.

### 23.30 Danh sách Thanh toán

- **Tên / URL / route / file / actor:** Danh sách Thanh toán; `/admin/thanh-toan`; `adminThanhToan`; `pages/admin/thanh_toan/thanh_toan.index.vue`; ADMIN.
- **Purpose / API / readiness:** Filter/paginate Payment read-only; `GET /api/admin/payments`; `READY`.
- **Components / Pinia:** filter/table/pagination/status badge; `thanh_toan.store.js`.
- **Functions / docblocks:** `taiDanhSachThanhToan()`, `apDungBoLocThanhToan()`; docblock Payment vs Membership state.
- **Loading / empty / error:** Skeleton; empty filter; 422/403/5xx.
- **Mutation / idempotency / rules:** Read-only, không key; webhook Backend là truth, không thêm tài chính mutation.

### 23.31 Chi tiết Thanh toán

- **Tên / URL / route / file / actor:** Chi tiết Thanh toán; `/admin/thanh-toan/:id`; `adminChiTietThanhToan`; `pages/admin/thanh_toan/thanh_toan.chi_tiet.vue`; ADMIN.
- **Purpose / API / readiness:** Xem Payment/order/member/term/safe events; `GET /api/admin/payments/{payment}`; `READY`.
- **Components / Pinia:** detail sections, status badge, event table; `thanh_toan.store.js`.
- **Functions / docblocks:** `taiChiTietThanhToan()`, `dienGiaiTrangThaiMembership()`; docblock tránh suy activation.
- **Loading / empty / error:** Skeleton; 404; 403/5xx.
- **Mutation / idempotency / rules:** Read-only, không key; không raw payload/signature, Payment success không đồng nghĩa Membership active.

### 23.32 Đối soát Thanh toán

- **Tên / URL / route / file / actor:** Đối soát Thanh toán; `/admin/doi-soat-thanh-toan`; `adminDoiSoatThanhToan`; `pages/admin/doi_soat_thanh_toan/doi_soat_thanh_toan.index.vue`; ADMIN.
- **Purpose / API / readiness:** Queue Payment/event `CAN_DOI_SOAT`; payments filter + payment-events; `BACKEND_FIX_IN_PROGRESS`.
- **Components / Pinia:** filter/table/tabs/badge/fix banner; `thanh_toan.store.js`.
- **Functions / docblocks:** `taiDanhSachCanDoiSoat()`, `taiSuKienThanhToan()`, `hopNhatDauVetDoiSoat()`; docblock unlinked event/branch/redaction.
- **Loading / empty / error:** Independent skeletons; empty chỉ sau fix đủ visibility; 422/403/5xx.
- **Mutation / idempotency / rules:** Read-only, không key; không nút success/refund/Membership; không đánh PASS đến khi null-payment event và abnormal filter có test.

### 23.33 Đăng nhập PT

- **Tên / URL / route / file / actor:** Đăng nhập PT; `/pt/dang-nhap`; `ptDangNhap`; `pages/pt/dang_nhap/dang_nhap.index.vue`; PT.
- **Purpose / API / readiness:** Tạo PT session; login + me; `READY`.
- **Components / Pinia:** shared login form/fields; `xac_thuc.store.js`.
- **Functions / docblocks:** `dangNhap()`, `taiThongTinNguoiDung()`, `xuLyDangNhapPt()`; auth/role redirect docblocks.
- **Loading / empty / error:** Restore/submit pending; no empty; credential generic, 422/429/5xx.
- **Mutation / idempotency / rules:** Disable, không auto-retry/idempotency; PT role không tự cấp Member scope, profile inactive vẫn do Backend chặn.

### 23.34 Hồ sơ Huấn luyện viên của tôi

- **Tên / URL / route / file / actor:** Hồ sơ Huấn luyện viên của tôi; `/pt/ho-so`; `ptHoSo`; `pages/pt/ho_so/ho_so.index.vue`; PT.
- **Purpose / API / readiness:** GET/PATCH safe own trainer fields; `/api/profile/trainer`; `READY`.
- **Components / Pinia:** form/fields/query states; auth store, form local.
- **Functions / docblocks:** `taiHoSoHuanLuyenVien()`, `capNhatHoSoHuanLuyenVien()`; mutation docblock nêu own-only/managed fields bị cấm.
- **Loading / empty / error:** Skeleton; missing profile là controlled 404; 422/403/5xx.
- **Mutation / idempotency / rules:** Disable/refetch, không key; không cho đổi code/status/role/other PT id.

### 23.35 Hội viên được phân công

- **Tên / URL / route / file / actor:** Hội viên được phân công; `/pt/hoi-vien`; `ptHoiVien`; `pages/pt/hoi_vien/hoi_vien.index.vue`; PT.
- **Purpose / API / readiness:** List exact assigned Members; `GET /api/pt/members`; `READY`.
- **Components / Pinia:** cards/table, empty/query states; `hoi_vien_pt.store.js`.
- **Functions / docblocks:** `taiDanhSachHoiVienDuocPhanCong()`, `chonHoiVien()`; docblock resource-scope cleanup.
- **Loading / empty / error:** Skeleton; no assignment empty; 401/403/5xx.
- **Mutation / idempotency / rules:** Read-only; không key; không arbitrary member search, clear selected Member nếu không còn trong response.

### 23.36 Chi tiết Hội viên của PT

- **Tên / URL / route / file / actor:** Chi tiết Hội viên; `/pt/hoi-vien/:id`; `ptChiTietHoiVien`; `pages/pt/hoi_vien/hoi_vien.chi_tiet.vue`; PT.
- **Purpose / API / readiness:** Safe profile + assignment workspace; API detail thiếu; `BACKEND_API_BLOCKER`.
- **Components / Pinia:** blocked state, member workspace tabs; `hoi_vien_pt.store.js` chỉ giữ list context.
- **Functions / docblocks:** tương lai `taiChiTietHoiVienDuocPhanCong()`, `xacMinhHoiVienConTrongPhamVi()`; Q05/resource cleanup docblock.
- **Loading / empty / error:** Blocker hiện tại; sau API skeleton/no resource; 403/404 clear context, 5xx retry.
- **Mutation / idempotency / rules:** Không mutation/key; không ghép Admin/Member-self API, exact current assignment only.

### 23.37 Tiến độ Hội viên

- **Tên / URL / route / file / actor:** Tiến độ Hội viên; `/pt/hoi-vien/:id/tien-do`; `ptTienDoHoiVien`; `pages/pt/tien_do/tien_do.index.vue`; PT.
- **Purpose / API / readiness:** Overview/body/exercise progress; ba PT progress GET; `READY`.
- **Components / Pinia:** metric cards, body table, exercise selector/chart tối giản; `hoi_vien_pt.store.js`.
- **Functions / docblocks:** `taiTongQuanTienDo()`, `taiLichSuChiSoCoThe()`, `taiTienDoBaiTap()`; docblock scope và dữ liệu immutable source.
- **Loading / empty / error:** Independent skeletons; no measurement/session states; 403/404 clear selected Member, 422/5xx.
- **Mutation / idempotency / rules:** Read-only/key không có; không chẩn đoán y khoa/BMI category tự đặt.

### 23.38 Kế hoạch tập Hội viên

- **Tên / URL / route / file / actor:** Kế hoạch tập Hội viên; `/pt/hoi-vien/:id/ke-hoach-tap`; `ptKeHoachTapHoiVien`; `pages/pt/ke_hoach_tap/ke_hoach_tap.index.vue`; PT.
- **Purpose / API / readiness:** Read official current/future plan; PT-scoped route thiếu; `BACKEND_API_BLOCKER`.
- **Components / Pinia:** blocked state, future plan tree; `hoi_vien_pt.store.js`.
- **Functions / docblocks:** tương lai `taiKeHoachTapHienTaiCuaHoiVien()`; docblock exact assignment/no membership requirement.
- **Loading / empty / error:** Blocker; sau API skeleton/no active plan; 403/404 cleanup, 5xx.
- **Mutation / idempotency / rules:** Không mutation/key; official plan khác Proposal, không gọi Member-self API.

### 23.39 Lịch sử tập Hội viên

- **Tên / URL / route / file / actor:** Lịch sử tập Hội viên; `/pt/hoi-vien/:id/lich-su-tap`; `ptLichSuTapHoiVien`; `pages/pt/lich_su_tap/lich_su_tap.index.vue`; PT.
- **Purpose / API / readiness:** Read session list/detail; PT-scoped routes thiếu; `BACKEND_API_BLOCKER`.
- **Components / Pinia:** blocked state, future table/detail drawer; `hoi_vien_pt.store.js`.
- **Functions / docblocks:** tương lai `taiLichSuTapHoiVien()`, `taiChiTietBuoiTapDaHoanThanh()`; immutable-history docblocks.
- **Loading / empty / error:** Blocker; sau API paginated skeleton/no history; 403/404 cleanup, 5xx.
- **Mutation / idempotency / rules:** Tuyệt đối read-only, không key; completed Session bất biến, Workout read không phụ thuộc Membership active.

### 23.40 Ghi chú Huấn luyện

- **Tên / URL / route / file / actor:** Ghi chú Huấn luyện; `/pt/hoi-vien/:id/ghi-chu`; `ptGhiChuHoiVien`; `pages/pt/ghi_chu/ghi_chu.index.vue`; PT.
- **Purpose / API / readiness:** View/append note; GET/POST notes; `READY`.
- **Components / Pinia:** note list, form, query states; selected Member store, notes local.
- **Functions / docblocks:** `taiGhiChuHuanLuyen()`, `themGhiChuHuanLuyen()`; mutation docblock append-only/no plan effect.
- **Loading / empty / error:** Skeleton; no notes; 422/403/404 scope cleanup/5xx.
- **Mutation / idempotency / rules:** Disable submit, no key, refetch; giới hạn 100 gần nhất, note không mutate Plan/completed Session.

### 23.41 Buổi huấn luyện trực tiếp

- **Tên / URL / route / file / actor:** Buổi huấn luyện trực tiếp; `/pt/hoi-vien/:id/buoi-huan-luyen`; `ptBuoiHuanLuyen`; `pages/pt/buoi_huan_luyen/buoi_huan_luyen.index.vue`; PT.
- **Purpose / API / readiness:** View own history và confirm complete; GET/POST direct sessions; `READY` có giới hạn history.
- **Components / Pinia:** history table, confirm dialog, quota warning; selected Member store, action local.
- **Functions / docblocks:** `taiLichSuBuoiHuanLuyen()`, `hoanTatBuoiHuanLuyen()`, `thuLaiHoanTatBuoiHuanLuyen()`; docblock term/quota/idempotency.
- **Loading / empty / error:** Skeleton; no latest-100 match; 409 idempotency/membership, 422/403/404/5xx.
- **Mutation / idempotency / rules:** Stable header key, disable, same-key retry + refetch; Backend chooses term/quota/activation, FE never backdates or borrows future term.

### 23.42 Danh sách Đề xuất kế hoạch

- **Tên / URL / route / file / actor:** Đề xuất kế hoạch; `/pt/hoi-vien/:id/de-xuat`; `ptDeXuatKeHoach`; `pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.index.vue`; PT.
- **Purpose / API / readiness:** List/preview content/status; `GET /api/pt/members/{member}/proposals`; `READY`.
- **Components / Pinia:** list/table, status badge, preview drawer; `de_xuat.store.js`, selected Member store.
- **Functions / docblocks:** `taiDanhSachDeXuat()`, `moXemTruocDeXuat()`, `dienGiaiTrangThaiDeXuat()`; Q13/status docblocks.
- **Loading / empty / error:** Skeleton; no proposal; 403/404 clear, 5xx; expired/conflict are data states.
- **Mutation / idempotency / rules:** Read/preview only, no key; max 100, không invent PT detail endpoint, PT không confirm/apply.

### 23.43 Tạo Đề xuất kế hoạch

- **Tên / URL / route / file / actor:** Tạo Đề xuất kế hoạch; `/pt/hoi-vien/:id/de-xuat/tao-moi`; `ptTaoDeXuatKeHoach`; `pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.tao_moi.vue`; PT.
- **Purpose / API / readiness:** Tạo immutable Proposal preview; `POST /api/pt/members/{member}/proposals`; `READY`.
- **Components / Pinia:** proposal form/tree, conflict banner, field errors; `de_xuat.store.js`, selected Member store.
- **Functions / docblocks:** `taoDeXuatKeHoach()`, `taoPayloadDeXuat()`, `xuLyXungDotDeXuat()`, `thuLaiTaoDeXuat()`; tất cả có docblock.
- **Loading / empty / error:** Option/base loading; no active plan handled theo loại; 409 assignment/plan/template/context, 422/403/404/5xx.
- **Mutation / idempotency / rules:** Stable header key; timeout retry same key; Q13 valid assignment only, không Membership/Chat/direct/AI quota, không activation và không official mutation.

### 23.44 Trò chuyện với Hội viên

- **Tên / URL / route / file / actor:** Trò chuyện với Hội viên; `/pt/tro-chuyen`; `ptTroChuyen`; `pages/pt/tro_chuyen/tro_chuyen.index.vue`; PT.
- **Purpose / API / readiness:** Conversation list/history/send/realtime; Chat REST `READY`, realtime `DEPLOYMENT_DEPENDENCY`.
- **Components / Pinia:** conversation list, message list/row, composer, reconnect banner; `tro_chuyen.store.js`, auth/Member stores.
- **Functions / docblocks:** `taiDanhSachHoiThoai()`, `taiTinNhan()`, `guiTinNhan()`, `dangKyKenhRieng()`, `dongBoSauKetNoiLai()`, `roiKenhKhiMatPhamVi()`, `hopNhatTinNhanTheoSequence()`; tất cả cần docblock.
- **Loading / empty / error:** Conversation/message skeleton riêng; no conversation; network reconnect, 401 logout, 403/404 Q05 cleanup, 409 chat idempotency.
- **Mutation / idempotency / rules:** Stable `client_message_id`, optimistic pending + server reconcile/dedupe; Q05 old PT không read/send/subscribe/reconnect; REST truth, leave/clear on scope loss.

### 23.45 Đăng nhập Nhân viên lễ tân

- **Tên / URL / route / file / actor:** Đăng nhập Nhân viên lễ tân; `/le-tan/dang-nhap`; `leTanDangNhap`; `pages/le_tan/dang_nhap/dang_nhap.index.vue`; RECEPTIONIST.
- **Purpose / API / readiness:** Tạo Receptionist session; login + me; `READY`.
- **Components / Pinia:** shared login form; `xac_thuc.store.js`.
- **Functions / docblocks:** `dangNhap()`, `taiThongTinNguoiDung()`, `xuLyDangNhapLeTan()`; auth/role redirect docblocks.
- **Loading / empty / error:** Restore/submit; no empty; generic credential, 422/429/5xx.
- **Mutation / idempotency / rules:** Disable, no auto-retry/key; không dùng Admin API nếu chỉ có RECEPTIONIST.

### 23.46 Tra cứu Hội viên

- **Tên / URL / route / file / actor:** Tra cứu Hội viên; `/le-tan/tra-cuu-hoi-vien`; `leTanTraCuuHoiVien`; `pages/le_tan/tra_cuu_hoi_vien/tra_cuu_hoi_vien.index.vue`; RECEPTIONIST.
- **Purpose / API / readiness:** Search Member safe fields; Receptionist API thiếu; `BACKEND_API_BLOCKER`.
- **Components / Pinia:** blocked state, future search/table; page-local state.
- **Functions / docblocks:** tương lai `traCuuHoiVien()`, `chonHoiVienCanHoTro()`; docblock privacy/branch/search normalization.
- **Loading / empty / error:** Blocker; sau API search pending/no result; 403/422/5xx.
- **Mutation / idempotency / rules:** Read-only/no key; không gọi `/api/admin/accounts`, không search toàn dữ liệu vượt branch.

### 23.47 Kiểm tra trạng thái Hội viên

- **Tên / URL / route / file / actor:** Kiểm tra trạng thái Hội viên; `/le-tan/hoi-vien/:id/trang-thai-hoi-vien`; `leTanTrangThaiHoiVien`; `pages/le_tan/trang_thai_hoi_vien/trang_thai_hoi_vien.chi_tiet.vue`; RECEPTIONIST.
- **Purpose / API / readiness:** Hiển thị Membership/Gym eligibility Backend-calculated; API thiếu; `BACKEND_API_BLOCKER`.
- **Components / Pinia:** blocked state, future status card/badge; page-local state.
- **Functions / docblocks:** tương lai `taiTrangThaiHoiVien()`, `dienGiaiQuyenVaoPhong()`; docblock Payment != activation và Backend authority.
- **Loading / empty / error:** Blocker; sau API skeleton/no current term; 403/404/5xx.
- **Mutation / idempotency / rules:** Read-only/no key; không tính ngày, không activate, không suy quyền từ Payment success.

### 23.48 Quét mã vào phòng

- **Tên / URL / route / file / actor:** Quét mã vào phòng; `/le-tan/quet-ma-vao-phong`; `leTanQuetMaVaoPhong`; `pages/le_tan/quet_ma_vao_phong/quet_ma_vao_phong.index.vue`; RECEPTIONIST.
- **Purpose / API / readiness:** Submit QR token để check-in; `POST /api/gym/check-in`; `READY`.
- **Components / Pinia:** `bo_nhap_ma_qr.vue`, result card, announcement; local state + auth store.
- **Functions / docblocks:** `docMaQr()`, `xacNhanVaoPhong()`, `dienGiaiKetQuaCheckIn()`, `datLaiLuotQuet()`; check-in/security docblocks.
- **Loading / empty / error:** Camera/manual waiting state; no token; controlled expired/used/invalid/no entitlement, 422/403/409/5xx.
- **Mutation / idempotency / rules:** Khóa submit token đang xử lý, không auto-retry mù; no header key, QR one-time Backend guard; success không cho FE sửa Membership hoặc timestamps.

## 24. Component Inventory

| Nhóm | Component files | Trách nhiệm / test chính |
| --- | --- | --- |
| Shell | `khung_ung_dung.vue`, `thanh_ben_dieu_huong.vue`, `thanh_tren.vue`, `duong_dan_phan_cap.vue` | Navigation theo route meta, responsive, keyboard, actor switch/logout; không tự authorize |
| Page structure | `tieu_de_trang.vue`, `the_chi_so.vue` | Heading hierarchy, actions, metric zero/loading/error |
| Data | `bang_du_lieu.vue`, `thanh_phan_trang.vue`, `bo_loc_danh_sach.vue`, `huy_hieu_trang_thai.vue` | Semantic table, server pagination, filter submit, status label không chỉ màu |
| Query state | `trang_thai_tai_du_lieu.vue`, `trang_thai_trong.vue`, `trang_thai_loi.vue`, `chan_tinh_nang_bi_chan.vue` | Mọi page có loading/empty/error; blocker không fake API |
| Mutation/form | `hop_thoai_xac_nhan.vue`, `truong_bieu_mau.vue`, `vung_thong_bao.vue` | Focus trap/return, pending, field errors, aria-live |
| Auth | `bieu_mau_dang_nhap.vue` | Logic form dùng chung, actor context qua props; không duplicate auth service |
| Catalog | `cay_giao_an.vue`, `bo_chon_quan_he_bai_tap.vue`, `bo_sua_quyen_loi_goi_tap.vue` | Draft-only edit, equipment AND, immutable published history |
| PT | `thanh_dieu_huong_hoi_vien.vue`, `the_tien_do.vue`, `khung_xem_de_xuat.vue` | Member context exact assignment, progress data, Q13 preview |
| Chat | `danh_sach_hoi_thoai.vue`, `danh_sach_tin_nhan.vue`, `dong_tin_nhan.vue`, `khung_nhap_tin_nhan.vue`, `thong_bao_mat_ket_noi.vue` | Dedupe/sequence/pending/retry/Q05 cleanup |
| Receptionist | `bo_nhap_ma_qr.vue`, `the_ket_qua_check_in.vue` | Camera fallback, safe outcome, không local QR validation authority |

Business component chỉ tạo khi có hai nơi dùng hoặc boundary đủ rõ. Page-specific form nhỏ ở lại page để tránh component hóa hình thức.

## 25. Store Inventory

| Store | State tối thiểu | Actions tối thiểu | Không được làm |
| --- | --- | --- | --- |
| `xac_thuc.store.js` | `token`, `nguoiDung`, `vaiTro`, `congViecDangChon`, `daKhoiPhuc` | `dangNhap`, `taiThongTinHienTai`, `khoiPhucPhien`, `chonCongViec`, `dangXuat`, `xoaPhien` | Không làm Backend authorization; không localStorage dài hạn |
| `tai_khoan.store.js` | filters/page/items/meta/selected account | `taiDanhSachTaiKhoan`, `taiChiTietTaiKhoan`, `lamMoiTaiKhoan`, `xoaDuLieu` | Không giữ form password/invitation token |
| `danh_muc.store.js` | equipment/muscles/options + timestamps | `taiDungCu`, `taiNhomCo`, `lamMoiDanhMuc`, `voHieuCache` | Không coi cache là active-state authority khi submit |
| `thanh_toan.store.js` | Payment/event filters, pages, selected detail | `taiThanhToan`, `taiChiTiet`, `taiSuKien`, `xoaDuLieu` | Không có financial mutation; không raw webhook payload |
| `hoi_vien_pt.store.js` | assignments, selected member id, progress cache | `taiHoiVienDuocPhanCong`, `chonHoiVien`, `xacMinhPhamVi`, `xoaDuLieuNhayCam` | Không arbitrary Member cache; không giữ sau scope loss |
| `de_xuat.store.js` | list by member, draft, action key, submit state | `taiDeXuat`, `luuBanNhap`, `taoDeXuat`, `xoaBanNhap` | Không trực tiếp apply official plan; không tính entitlement |
| `tro_chuyen.store.js` | conversations, messages maps/order, pending, channel/connection state | `taiHoiThoai`, `taiTinNhan`, `guiTinNhan`, `nhanTinNhan`, `dongBoLai`, `roiTatCaKenh`, `xoaDuLieuNhayCam` | Không persist content; không reconnect sau Q05 loss |

Store persistence adapter chỉ áp dụng cho auth token/actor trong `sessionStorage`; mọi store còn lại memory-only. Test phải chứng minh cleanup chạy ở logout, 401, role loss và Q05 scope loss.

## 26. Service / API Inventory

| File | Routes thực tế / trách nhiệm | Status đặc biệt |
| --- | --- | --- |
| `services/api.js` hoặc `ket_noi_api.js` | Axios instance, token injection, normalized error | FE-0 refactor có test; không duplicate client |
| `services/xac_thuc.api.js` | `/auth/login`, `/me`, `/logout`, forgot/reset | READY |
| `services/bang_dieu_khien.api.js` | `/admin/dashboard` | READY |
| `services/tai_khoan.api.js` | Admin accounts/status/roles | READY |
| `services/huan_luyen_vien.api.js` | Admin trainer create/onboard; PT self profile | Onboarding FIX; self READY; Admin read profile BLOCKER |
| `services/phan_cong_pt.api.js` | assignment create/end/reassign; future GET | Mutation READY; query BLOCKER |
| `services/goi_tap.api.js` | Admin packages + benefits | READY |
| `services/dung_cu.api.js` | Admin equipment | READY |
| `services/nhom_co.api.js` | Admin muscle groups | READY |
| `services/bai_tap.api.js` | Admin exercises | READY |
| `services/giao_an_mau.api.js` | Admin templates + revisions | READY; optimistic version |
| `services/thanh_toan.api.js` | Admin Payment/detail/events/reconciliation | Read basics READY; reconciliation FIX |
| `services/hoi_vien_pt.api.js` | PT assigned list; future member detail | List READY; detail BLOCKER |
| `services/tien_do.api.js` | PT member progress routes | READY |
| `services/ke_hoach_tap_pt.api.js` | Future PT-scoped current plan | BLOCKER; không gọi Member route |
| `services/lich_su_tap_pt.api.js` | Future PT-scoped sessions | BLOCKER |
| `services/ghi_chu.api.js` | PT member notes | READY |
| `services/buoi_huan_luyen.api.js` | PT direct history/complete | READY có list limit |
| `services/de_xuat.api.js` | PT member proposals list/create | READY |
| `services/tro_chuyen.api.js` | Chat conversations/messages/current/send | READY |
| `services/phat_song_tro_chuyen.js` | Echo init/auth/subscribe/leave | DEPLOYMENT_DEPENDENCY; tạo ở FE-7 |
| `services/le_tan.api.js` | Future lookup/membership + current QR check-in | Lookup/Membership BLOCKER; QR READY |

Service blocker có method stub chỉ khi cần type/interface test, nhưng method phải throw controlled `TinhNangChuaSanSang`, không gọi endpoint tưởng tượng. Ưu tiên chưa tạo method cho đến khi contract được duyệt.

## 27. Composable / Helper Inventory

| File | API dự kiến | Docblock / rule |
| --- | --- | --- |
| `composables/su_dung_xac_thuc.js` | `dangNhapTheoActor`, `dangXuatVaDonDep`, `yeuCauVaiTro` | Auth/session/role routing bắt buộc docblock |
| `composables/su_dung_tai_du_lieu.js` | `thucThiTaiDuLieu`, state loading/data/error | Helper generic có thể dùng thuật ngữ framework; không giấu domain error |
| `composables/su_dung_bieu_mau.js` | field errors, dirty, reset | Trivial helpers không cần docblock dài |
| `composables/su_dung_phan_trang.js` | page/perPage/meta/query sync | Validate query, không đưa invalid filter sang Backend |
| `composables/su_dung_thao_tac_chong_lap.js` | `batDauThaoTac`, `layKhoaHienTai`, `ketThucThaoTac`, `huyThaoTac` | Stable-key lifecycle bắt buộc docblock/test |
| `composables/su_dung_kenh_tro_chuyen.js` | subscribe/leave/reconnect/catch-up | Q05, sequence, cleanup docblock/test bắt buộc |
| `composables/su_dung_xu_ly_loi_api.js` | map normalized error sang field/page/toast | Không invent code; 401/403/404/409 policy có test |
| `utils/khoa_idempotency.js` | `taoKhoaIdempotency()` | `crypto.randomUUID`, no fallback yếu |
| `utils/phien_dang_nhap.js` | token accessor/sessionStorage allow-list | Không log, clear atomically |
| `utils/duong_dan_an_toan.js` | `laDuongDanNoiBoHopLe()` | Chống open redirect |
| `utils/dinh_dang.js` | tiền/ngày/giờ theo locale Việt Nam | Display only, không tính business date/amount |
| `utils/trang_thai_nghiep_vu.js` | label/map status được biết từ Backend | Unknown status có fallback an toàn, không coerce |

## 28. Proposed File Structure

Giữ `src/pages` hiện có để tránh restructure không cần thiết; `pages` là structural folder legacy/architecture, các folder/file nghiệp vụ mới tuân thủ tiếng Việt không dấu. Cây dưới đây là target cuối, không phải danh sách phải tạo trong FE-0.

```text
FE/
├─ package.json
├─ package-lock.json
├─ vite.config.js
├─ eslint.config.js                         # FE-0, neu dependency duoc phe duyet
├─ vitest.config.js                         # FE-0
└─ src/
   ├─ main.js
   ├─ App.vue
   ├─ assets/
   │  └─ main.css
   ├─ router/
   │  ├─ index.js
   │  ├─ dinh_tuyen_admin.js
   │  ├─ dinh_tuyen_pt.js
   │  ├─ dinh_tuyen_le_tan.js
   │  └─ bao_ve_tuyen_duong.js
   ├─ layouts/
   │  ├─ bo_cuc_cong_khai.vue
   │  ├─ bo_cuc_admin.vue
   │  ├─ bo_cuc_pt.vue
   │  ├─ bo_cuc_le_tan.vue
   │  └─ bo_cuc_loi.vue
   ├─ components/
   │  ├─ chung/                             # shell, table, form, query states
   │  ├─ xac_thuc/bieu_mau_dang_nhap.vue
   │  ├─ danh_muc/                          # cay_giao_an, quan_he, quyen_loi
   │  ├─ pt/                                # member navigation, progress, proposal
   │  ├─ tro_chuyen/                        # conversation/message/composer
   │  └─ le_tan/                            # QR input/result
   ├─ pages/
   │  ├─ chung/
   │  │  ├─ chon_vai_tro/chon_vai_tro.index.vue
   │  │  ├─ quen_mat_khau/quen_mat_khau.index.vue
   │  │  ├─ dat_lai_mat_khau/dat_lai_mat_khau.index.vue
   │  │  └─ loi/{khong_co_quyen.vue,khong_tim_thay.vue}
   │  ├─ admin/
   │  │  ├─ dang_nhap/dang_nhap.index.vue
   │  │  ├─ bang_dieu_khien/bang_dieu_khien.index.vue
   │  │  ├─ tai_khoan/{tai_khoan.index.vue,tai_khoan.chi_tiet.vue}
   │  │  ├─ hoi_vien/{hoi_vien.index.vue,hoi_vien.chi_tiet.vue}
   │  │  ├─ huan_luyen_vien/{huan_luyen_vien.index.vue,huan_luyen_vien.tao_moi.vue,huan_luyen_vien.chi_tiet.vue}
   │  │  ├─ nhan_vien_le_tan/{nhan_vien_le_tan.index.vue,nhan_vien_le_tan.chi_tiet.vue}
   │  │  ├─ phan_cong_pt/phan_cong_pt.index.vue
   │  │  ├─ goi_tap/{goi_tap.index.vue,goi_tap.tao_moi.vue,goi_tap.chi_tiet.vue}
   │  │  ├─ dung_cu/dung_cu.index.vue
   │  │  ├─ nhom_co/nhom_co.index.vue
   │  │  ├─ bai_tap/{bai_tap.index.vue,bai_tap.tao_moi.vue,bai_tap.chi_tiet.vue}
   │  │  ├─ giao_an_mau/{giao_an_mau.index.vue,giao_an_mau.tao_moi.vue,giao_an_mau.chi_tiet.vue,giao_an_mau.tao_phien_ban.vue}
   │  │  ├─ thanh_toan/{thanh_toan.index.vue,thanh_toan.chi_tiet.vue}
   │  │  └─ doi_soat_thanh_toan/doi_soat_thanh_toan.index.vue
   │  ├─ pt/
   │  │  ├─ dang_nhap/dang_nhap.index.vue
   │  │  ├─ ho_so/ho_so.index.vue
   │  │  ├─ hoi_vien/{hoi_vien.index.vue,hoi_vien.chi_tiet.vue}
   │  │  ├─ tien_do/tien_do.index.vue
   │  │  ├─ ke_hoach_tap/ke_hoach_tap.index.vue
   │  │  ├─ lich_su_tap/lich_su_tap.index.vue
   │  │  ├─ ghi_chu/ghi_chu.index.vue
   │  │  ├─ buoi_huan_luyen/buoi_huan_luyen.index.vue
   │  │  ├─ de_xuat_ke_hoach_tap/{de_xuat_ke_hoach_tap.index.vue,de_xuat_ke_hoach_tap.tao_moi.vue}
   │  │  └─ tro_chuyen/tro_chuyen.index.vue
   │  └─ le_tan/
   │     ├─ dang_nhap/dang_nhap.index.vue
   │     ├─ tra_cuu_hoi_vien/tra_cuu_hoi_vien.index.vue
   │     ├─ trang_thai_hoi_vien/trang_thai_hoi_vien.chi_tiet.vue
   │     └─ quet_ma_vao_phong/quet_ma_vao_phong.index.vue
   ├─ stores/
   │  ├─ xac_thuc.store.js
   │  ├─ tai_khoan.store.js
   │  ├─ danh_muc.store.js
   │  ├─ thanh_toan.store.js
   │  ├─ hoi_vien_pt.store.js
   │  ├─ de_xuat.store.js
   │  └─ tro_chuyen.store.js
   ├─ services/                            # cac file tai muc 26
   ├─ composables/                         # cac file tai muc 27
   └─ utils/                               # idempotency/session/path/format/status
```

## 29. Testing Strategy

### Dependency impact

FE hiện chưa có test stack. Danh sách dependency tối thiểu đã được khóa cho task triển khai tại mục 6A: FE-0 dùng `vitest`, `@vue/test-utils`, `jsdom`, `eslint`, `eslint-plugin-vue`. Không bắt buộc Playwright/Cypress trong MVP. Echo/Pusher chỉ thêm FE-7. Terra phải ghi chính xác package/version thực cài trong checkpoint; kế hoạch này không cài package.

### Test layers

1. **Unit:** normalized errors, safe redirect, status mapping, idempotency lifecycle, stores cleanup, message merge/dedupe/sequence.
2. **Component:** form field errors, pending buttons, table states, confirm focus, template stale dialog, QR outcomes, proposal Q13 copy.
3. **Router/store integration:** unauthenticated, wrong role, multi-role selection, 401 clear, 403 page, 404 concealed, role revoke.
4. **Service contract fixtures:** mock Axios response shapes thật, gồm non-uniform `data/meta`; không snapshot fake DTO không có trong Backend.
5. **Realtime adapter tests:** subscribe/auth header, event mapping, duplicate event, out-of-order sequence, reconnect REST catch-up, leave/logout, Q05 assignment loss.

### Coverage bắt buộc theo nghiệp vụ

- Auth login/me/logout, session restore; 401/auth-invalid cleanup; network/timeout/5xx giữ token nhưng khóa protected content và retry `/me`; late 401 không xóa phiên mới.
- Admin account filter, status, grant/revoke/regrant, `TRAINER_PROFILE_REQUIRED`, last-admin conflict UX.
- PT onboarding stable idempotency và trạng thái invitation không được báo sai.
- Payment list/detail read-only, reconciliation visibility sau Backend fix, không có mutation control.
- Package snapshot copy; Exercise equipment AND; Template copy-on-write + 409 stale.
- PT assigned Member scope; 403/404 clear selected state.
- PT Progress read-only; Plan/History route blocker không gọi API sai; completed history không có edit controls.
- Q13 Proposal không yêu cầu/hiển thị Membership/Chat/direct quota như prerequisite; same-key retry.
- PT Direct stable key, timeout/refetch, Backend quota authority.
- Chat subscribe/send/dedupe/reconnect/catch-up/Q05 cleanup.
- Receptionist không gọi Admin route; QR success/expired/used/invalid/no entitlement.

### Commands/gate dự kiến

`npm run test`, `npm run lint`, `npm run build` phải tồn tại sau FE-0 và chạy từ `FE/`. Nếu dependency trong mục 6A không tương thích runtime/lockfile, FE-0 không được báo PASS test/lint; ghi `BLOCKED_DEPENDENCY_COMPATIBILITY`, không tạo script giả.

## 30. Naming / Documentation Quality Gate

Mỗi phase scan các file mới/sửa, không chỉ final phase:

- Route paths: không có `/admin/dashboard`, `/admin/accounts`, `/pt/chat` hoặc business segment tiếng Anh; ngoại lệ root, param syntax và Backend URL string.
- Route names: actor-prefixed Vietnamese-no-accent camelCase.
- Page/business component/store/service/composable files: Vietnamese-no-accent snake_case; framework exceptions ghi ở 6.6.
- Business function/event handler/variable: tiếng Việt không dấu camelCase, rõ ý định; cấm `fetchAccounts`, `handleLogin`, `handleSubmit`, `sendMessage` trong code team-authored mới.
- UI literals: tiếng Việt có dấu; thuật ngữ `ADMIN`, `PT`, `RECEPTIONIST`, API/status code có thể giữ chính thức.
- Important functions: docblock đủ purpose/input/process/result/side effect/business rule.
- Không comment vô ích, không ép đổi official Vue/Axios/Echo APIs.

Đề xuất script scan chỉ báo cáo candidate để review, vì regex không hiểu ngữ nghĩa. Gate cuối gồm review thủ công danh sách ngoại lệ. Một phase fail nếu có violation mới không có lý do hoặc hàm auth/idempotency/Payment/Q05/Q13/mutation/realtime thiếu docblock meaningful.

## 31. Implementation Phases

### FE-0 Foundation

- **Goal:** Tạo nền an toàn cho mọi actor mà chưa triển khai domain screen.
- **Scope / exact screens:** Chọn vai trò, quên/đặt lại mật khẩu, 403, 404 và ba màn đăng nhập actor.
- **Routes:** `/`, 5 shared page routes, 3 login routes và catch-all; tổng 10 record trong phase.
- **Expected files:** router modules/guard, 5 layouts, auth page/components, `xac_thuc.store.js`, `xac_thuc.api.js`, API client, error/idempotency/session/safe-path helpers, query-state components, CSS tokens, test/lint configs theo dependency mục 6A.
- **Main functions:** `dangNhap`, `taiThongTinNguoiDung`, `khoiPhucPhien`, `dieuPhoiTheoVaiTro`, `dangXuatVaDonDep`, `chuanHoaLoiApi`, `taoKhoaIdempotency`, `laDuongDanNoiBoHopLe`.
- **Required docblocks:** Mọi hàm auth/redirect/401 cleanup/error normalization/idempotency lifecycle.
- **Backend APIs:** auth login/me/logout/forgot/reset.
- **Stores / components:** auth store; public/error layouts, login form, fields, query/error/toast components.
- **Tests:** Auth success/failure, multi-role actor bắt buộc, MEMBER-only Web message, restore lỗi tạm thời + retry, current/late 401, 403, revoked role, safe redirect, idempotency helper, no token logs.
- **Acceptance criteria:** Login/logout/me PASS; wrong-role/actor-null navigation PASS; current-session 401 clears state và về login actor, late 401 không xóa phiên mới; network/timeout/5xx `/me` giữ token nhưng khóa protected content và cho retry; 403/404 render; UI labels có dấu; build/test/lint/naming/docblock scans PASS.
- **Backend blockers:** Không có cho Foundation.
- **STOP conditions:** Nếu một dependency trong danh sách mục 6A không tương thích lockfile/runtime thì dừng riêng bước cài đặt, ghi `BLOCKED_DEPENDENCY_COMPATIBILITY`; auth response khác source thì dừng integration, không đoán.
- **Relative effort:** **L**.

### FE-1 Admin Foundation

- **Goal:** Có portal Admin đọc/điều hành account cơ bản, đủ cho demo authorization.
- **Scope / exact screens:** Dashboard, account list/detail, Member list/detail, Receptionist list/detail.
- **Routes:** `/admin/bang-dieu-khien`, `/admin/tai-khoan`, `/:id`, `/admin/hoi-vien`, `/:id`, `/admin/nhan-vien-le-tan`, `/:id`.
- **Expected files:** Admin layout/routes; dashboard/account/member/reception pages; `tai_khoan.store.js`; dashboard/account services; table/filter/pagination/status/confirm components.
- **Main functions:** `taiTongQuanAdmin`, `taiDanhSachTaiKhoan`, `taiChiTietTaiKhoan`, `capNhatTrangThaiTaiKhoan`, `capVaiTro`, `thuHoiVaiTro`, fixed-role list functions.
- **Required docblocks:** Date/branch query, role/status mutations, last-admin/profile conflict handling, filtered actor views.
- **Backend APIs:** Admin dashboard; accounts list/detail/status/roles.
- **Stores / components:** auth + account stores; Admin shell, table/filter/pagination/badge/confirm/query states.
- **Tests:** Admin guard, dashboard exact metric labels, filters, role grant/revoke/regrant UX, `TRAINER_PROFILE_REQUIRED`, last-admin conflict, Member/Receptionist fixed-role filtering.
- **Acceptance criteria:** Navigation/CRUD-state UX PASS; no client-selected branch; no Member/PT API misuse; refetch after mutations; build/test/naming/docblocks PASS.
- **Backend blockers:** Không có trong scope account-oriented MVP.
- **STOP conditions:** Nếu account DTO không còn fields đã dùng hoặc Backend route đổi, dừng affected page và đối chiếu contract.
- **Relative effort:** **L**.

### FE-2 Admin PT

- **Goal:** Quản lý PT đúng phân biệt account/profile/role/assignment, không giả invitation hay assignment history.
- **Scope / exact screens:** PT list, tạo/onboarding, PT detail, quản lý assignment.
- **Routes:** `/admin/huan-luyen-vien`, `/tao-moi`, `/:id`, `/admin/phan-cong-pt`.
- **Expected files:** PT/assignment pages, trainer/assignment services, onboarding form/status components; dùng account store, chưa tạo assignment store nếu GET contract chưa có.
- **Main functions:** `taiDanhSachHuanLuyenVien`, `taoHuanLuyenVien`, `onboardTaiKhoanThanhPt`, `taiChiTietHuanLuyenVien`; sau blocker mới có list/create/end/reassign assignment functions.
- **Required docblocks:** Stable key, invitation semantics, role/profile distinction, interval/Q05 effect, timeout recovery.
- **Backend APIs:** Admin trainer POSTs; account filter/detail; assignment POST/PATCH; hai GET contract còn thiếu.
- **Stores / components:** account/auth stores; forms, table, badge, blocker state, confirm dialog.
- **Tests:** Stable idempotency, conflict/in-progress, không báo QUEUED sai; PT list; blocker không gọi fake API; sau contract test assignment half-open/conflict/refetch.
- **Acceptance criteria:** READY part PASS; onboarding chỉ PASS sau BE follow-up 02/03; full PT detail/assignment gate chỉ PASS sau blockers 01/02 của mục 33.
- **Backend blockers:** Admin trainer profile GET; Admin assignment list/detail. Backend fixes invitation retry/audit snapshot.
- **STOP conditions:** Dừng riêng onboarding nếu fixes chưa merge; dừng detail/assignment query nếu API thiếu; không thêm resend-invitation hoặc query workaround.
- **Relative effort:** **L**.

### FE-3 Admin Catalog

- **Goal:** Hoàn thiện catalog có mutation an toàn và làm rõ snapshot/copy-on-write.
- **Scope / exact screens:** Package 3, Equipment 1, Muscle Group 1, Exercise 3, Workout Template 4; tổng 12 screens.
- **Routes:** Toàn bộ `/admin/goi-tap`, `/dung-cu`, `/nhom-co`, `/bai-tap`, `/giao-an-mau` trong inventory.
- **Expected files:** 12 pages; 5 domain services; `danh_muc.store.js`; benefit/relation/template-tree components.
- **Main functions:** List/create/update cho từng catalog; `taoPayloadQuanHeBaiTap`, `thayTheQuyenLoiGoiTap`, `taoPhienBanGiaoAnMau`, `xuLyGiaoAnMauDaCu`.
- **Required docblocks:** Package snapshot, equipment AND, deactivate/no-hard-delete, template copy-on-write, expected version/stale recovery.
- **Backend APIs:** Admin packages/benefits/equipment/muscle-groups/exercises/workout-templates/revisions.
- **Stores / components:** catalog store + local forms; data components, benefits editor, relations selector, template tree/stale dialog.
- **Tests:** Package conflict/snapshot copy; equipment/muscle/exercise conflicts; equipment AND payload; template invalid structure/inactive exercise; 409 stale keeps draft; no `days` in PATCH.
- **Acceptance criteria:** Catalog list/create/edit/deactivate PASS; historical warning displayed; no hard-delete; build/test/naming/docblocks PASS.
- **Backend blockers:** Không có; list không phân trang là scalability risk, không blocker MVP.
- **STOP conditions:** Nếu tree request/response lệch actual controller DTO, dừng template form và cập nhật fixture từ source.
- **Relative effort:** **XL**.

### FE-4 Admin Payment

- **Goal:** Cung cấp Payment/reconciliation read-only với visibility đầy đủ, không trao mutation authority.
- **Scope / exact screens:** Payment list, detail, reconciliation/events.
- **Routes:** `/admin/thanh-toan`, `/:id`, `/admin/doi-soat-thanh-toan`.
- **Expected files:** 3 pages, `thanh_toan.api.js`, `thanh_toan.store.js`, Payment filter/detail/event components.
- **Main functions:** `taiDanhSachThanhToan`, `taiChiTietThanhToan`, `taiDanhSachCanDoiSoat`, `taiSuKienThanhToan`, `hopNhatDauVetDoiSoat`.
- **Required docblocks:** Payment vs Membership, branch scope, unlinked event, redaction, read-only authority.
- **Backend APIs:** Admin Payment list/detail/events and reconciliation filter.
- **Stores / components:** Payment/auth stores; table/filter/pagination/badges/query states.
- **Tests:** Read-only controls, safe DTO, branch/404 UX, null-payment event fixture, successful Payment + abnormal event appears in reconciliation, no raw payload/signature.
- **Acceptance criteria:** Chỉ bắt đầu full phase khi BE-FOLLOWUP-01 READY; all visibility tests PASS; không có button success/refund/edit/Membership; build/naming/docblock PASS.
- **Backend blockers:** Không phải missing API; là `BACKEND_FIX_IN_PROGRESS` ở service hiện có.
- **STOP conditions:** Nếu fix chưa merge/test, không đánh reconciliation complete và không dùng client join để bù event bị Backend loại.
- **Relative effort:** **M**.

### FE-5 PT Member Workspace

- **Goal:** Tạo workspace PT đúng exact-assignment scope và read-only history boundary.
- **Scope / exact screens:** PT self profile, assigned Members, Member detail, Progress, Plan, History, Notes.
- **Routes:** `/pt/ho-so`, `/pt/hoi-vien`, `/:id`, `/tien-do`, `/ke-hoach-tap`, `/lich-su-tap`, `/ghi-chu`.
- **Expected files:** PT layout/routes, 7 pages, `hoi_vien_pt.store.js`, PT member/progress/plan/history/note services, member navigation/progress components.
- **Main functions:** `taiHoSoHuanLuyenVien`, `taiDanhSachHoiVienDuocPhanCong`, `xacMinhHoiVienConTrongPhamVi`, progress loaders, `themGhiChuHuanLuyen`; detail/plan/history functions chỉ sau API.
- **Required docblocks:** Resource scope/cleanup, progress meaning, notes no-side-effect, official Plan vs Proposal, completed history immutable.
- **Backend APIs:** PT self profile, assigned list, progress, notes READY; member detail/plan/history missing.
- **Stores / components:** auth + selected Member stores; PT shell, tabs, metrics, tables, blocker/query states.
- **Tests:** PT role guard, assigned-list only, selection removed on refetch, 403/404 cleanup, progress empty states, note append, blocker pages send zero wrong requests, no history edit controls.
- **Acceptance criteria:** READY subset PASS; full phase remains PARTIAL/BLOCKED until three PT read APIs exist; no fake profile/plan/history.
- **Backend blockers:** PT Member detail, PT current Plan, PT session history.
- **STOP conditions:** Dừng từng blocked screen; không gọi `/api/profile/member` hoặc `/api/workout/*` với PT token.
- **Relative effort:** **L**.

### FE-6 PT Direct + Proposal

- **Goal:** Hoàn thiện hai workflow PT mutation có idempotency/Q13 rõ.
- **Scope / exact screens:** Direct sessions, Proposal list/preview, Proposal create.
- **Routes:** `/pt/hoi-vien/:id/buoi-huan-luyen`, `/de-xuat`, `/de-xuat/tao-moi`.
- **Expected files:** 3 pages, direct/proposal services, `de_xuat.store.js`, confirm/preview/tree components.
- **Main functions:** `taiLichSuBuoiHuanLuyen`, `hoanTatBuoiHuanLuyen`, `thuLaiHoanTatBuoiHuanLuyen`, `taiDanhSachDeXuat`, `taoDeXuatKeHoach`, `xuLyXungDotDeXuat`.
- **Required docblocks:** Stable key lifecycle, Backend quota/activation authority, Q13 prerequisites/non-effects, stale/assignment conflict.
- **Backend APIs:** PT direct GET/complete; PT member proposals GET/POST.
- **Stores / components:** selected Member + proposal stores; confirm dialog, status badge, proposal preview/form.
- **Tests:** Same-key retry, timeout/refetch, no client quota math; Proposal max-100 notice, same-key replay, Q13 copy, 409 assignment/plan/template/context, no official apply control.
- **Acceptance criteria:** PT Direct and Proposal PASS; completed direct ledger not duplicated; Q13 demonstrable; build/test/naming/docblocks PASS.
- **Backend blockers:** Không có cho MVP; direct/proposal list pagination là limitation/risk.
- **STOP conditions:** Nếu selected Member không còn assignment, clear/dừng mutation; không retry conflict với key/payload khác tự động.
- **Relative effort:** **L**.

### FE-7 PT Realtime Chat

- **Goal:** Chat REST-first, realtime resilient và Q05-safe.
- **Scope / exact screens:** Trò chuyện với Hội viên.
- **Routes:** `/pt/tro-chuyen`.
- **Expected files:** Chat page/components, `tro_chuyen.store.js`, REST service, realtime adapter, channel composable; package changes cho Echo/Pusher theo dependency mục 6A.
- **Main functions:** Conversation/message loaders, `guiTinNhan`, `dangKyKenhRieng`, `hopNhatTinNhanTheoSequence`, `dongBoSauKetNoiLai`, `roiKenhKhiMatPhamVi`, logout cleanup.
- **Required docblocks:** Tất cả hàm send/idempotency/subscription/dedupe/reconnect/catch-up/Q05.
- **Backend APIs:** Chat REST; `/api/broadcasting/auth`; channel/event contract thật.
- **Stores / components:** auth/Member/chat stores; conversation list, message list/row, composer, reconnect banner.
- **Tests:** Auth header, REST initial load, stable `client_message_id`, duplicate/out-of-order events, sequence gap via latest + `before_sequence`, reconnect race, leave on switch/logout, 401, Q05 403/404/assignment loss.
- **Acceptance criteria:** REST works khi Reverb down; realtime staging smoke PASS; old PT không read/send/subscribe/reconnect; no secret in bundle; build/test/naming/docblocks PASS.
- **Backend blockers:** Không missing API; Reverb external preflight fix và deployment dependency còn lại.
- **STOP conditions:** Chỉ cài đúng dependency được phép tại mục 6A; nếu version tương thích không xác định được thì dừng và ghi blocker. Không đánh realtime PASS nếu chỉ mock/unit; external server/origin/TLS/auth fail thì giữ REST fallback và phase chưa complete.
- **Relative effort:** **XL**.

### FE-8 Receptionist

- **Goal:** Portal Receptionist tối thiểu, QR authority ở Backend.
- **Scope / exact screens:** Member lookup, Membership status, QR check-in; login đã có từ FE-0.
- **Routes:** `/le-tan/tra-cuu-hoi-vien`, `/le-tan/hoi-vien/:id/trang-thai-hoi-vien`, `/le-tan/quet-ma-vao-phong`.
- **Expected files:** Receptionist layout/routes, 3 pages, `le_tan.api.js`, QR input/result components.
- **Main functions:** Future `traCuuHoiVien`, `taiTrangThaiHoiVien`; current `docMaQr`, `xacNhanVaoPhong`, `dienGiaiKetQuaCheckIn`.
- **Required docblocks:** Privacy/branch lookup, Membership Backend authority, QR token handling/replay.
- **Backend APIs:** QR check-in READY; lookup/Membership missing.
- **Stores / components:** auth store + page-local state; Receptionist shell, blocker state, QR components.
- **Tests:** Receptionist guard, never call Admin API, blocker makes no request, manual/camera fallback, safe QR outcomes, pending/double-click, 403/409/422/5xx.
- **Acceptance criteria:** QR flow PASS; full phase blocked until lookup/Membership APIs READY; no local eligibility calculation.
- **Backend blockers:** Receptionist lookup và Membership status.
- **STOP conditions:** Dừng hai screen thiếu API; không dùng account Admin or Member-self routes.
- **Relative effort:** **M**.

### FE-9 Final Acceptance

- **Goal:** Chứng minh Web nhất quán xuyên actor và đóng tài liệu bàn giao.
- **Scope / exact screens:** Toàn bộ 48 screen/50 router records đã đủ điều kiện; blocker còn lại phải được ghi rõ, không đổi thành PASS.
- **Routes / files:** Audit toàn router/tree/source; tạo future checkpoint/report theo mục 39 khi thực sự triển khai.
- **Main functions / docblocks:** Audit mọi important function; không tạo business function mới trừ fix acceptance.
- **Backend APIs / stores / components:** Contract smoke theo status; cleanup toàn store/layout/component.
- **Tests:** Full unit/component/router integration; staging API smoke; Reverb smoke nếu dependency sẵn; responsive/a11y keyboard; build/lint/naming/docblock/secret scan.
- **Acceptance criteria:** Không unresolved critical violation; count route/screen/matrix cập nhật; READY flows PASS; blockers chính xác; build artifacts không có secret; completion report evidence-based.
- **Backend blockers:** Final Web `COMPLETE` chỉ khi 7 blockers, 4 fix rows và realtime deployment gate đã được giải quyết; nếu không, status là `PARTIALLY_READY`, không “complete có điều kiện”.
- **STOP conditions:** Không commit/push; không sửa BE/Mobile; bất kỳ gate fail nào giữ phase incomplete.
- **Relative effort:** **L**.

## 32. Phase Gates

| Phase | Entry gate | Exit gate | Có thể chạy song song |
| --- | --- | --- | --- |
| FE-0 | Đọc auth source + dependency compatibility check mục 6A | Auth/router/client/test/build/naming PASS | Không; là nền bắt buộc |
| FE-1 | FE-0 PASS | Admin dashboard/account/member/reception account views PASS | Sau FE-0 có thể chuẩn bị fixture FE-3 |
| FE-2 | FE-1 account primitives PASS | READY subset PASS; full gate chờ fixes + 2 APIs | Backend xử lý blockers/fixes song song FE-3 |
| FE-3 | FE-0 client + FE-1 Admin shell | 12 catalog screens + stale/snapshot tests PASS | Song song Backend FE-2/FE-4 fixes |
| FE-4 | BE-FOLLOWUP-01 tests PASS | Payment/reconciliation visibility/read-only PASS | Có thể chuẩn bị UI fixture nhưng không declare complete trước fix |
| FE-5 | FE-0 + PT layout/auth; actual PT contracts | READY subset PASS, blocked screens đúng policy | Backend thêm 3 PT read APIs song song FE-6 |
| FE-6 | Assigned Member context FE-5 PASS | Direct/Q13 Proposal/idempotency PASS | Có thể song song FE-3/FE-4 sau Foundation |
| FE-7 | FE-5 Member context + dependency compatibility/config readiness | REST + staging realtime + Q05 PASS | Reverb deployment/preflight làm song song frontend unit work |
| FE-8 | FE-0 Receptionist auth | QR PASS; full gate chờ 2 APIs | Backend Receptionist APIs song song FE-3/FE-6 |
| FE-9 | Các phase không còn gate giả PASS | Full test/build/lint/a11y/naming/docblock/security/report PASS | Không; integration gate cuối |

Mỗi phase update `docs/VUE_WEB_COMPLETION_CHECKPOINT.md` sau khi file này được tạo ở lúc triển khai, ghi cả test fail/blocker; không ghi PASS chỉ vì page render.

## 33. Backend API Blockers

### BLOCKER-01 — Admin đọc đầy đủ hồ sơ PT

- **Actor / use case / screen:** ADMIN; xem/prefill profile PT; Chi tiết Huấn luyện viên.
- **Missing Backend API:** `GET /api/admin/accounts/{account}/trainer-profile` (đề xuất tối thiểu).
- **Existing APIs checked:** Account list/detail chỉ trả trainer profile id/code/status; `POST /api/admin/accounts/{account}/trainer-profile` có thể upsert nhưng không tải introduction/specialties; `/api/profile/trainer` là PT self-only.
- **Why insufficient:** Không thể prefill/edit an toàn hoặc phân biệt giá trị hiện tại; POST mù có nguy cơ overwrite.
- **Minimal proposed contract:** Auth ADMIN, branch-scoped account có trainer profile; response allow-list `{account_id, trainer_profile_id, trainer_code, status, introduction, specialties, updated_at}`. 404 conceal ngoài branch; không trả secret/audit internals. Có thể tiếp tục dùng POST hiện hữu cho update nếu request contract đã hỗ trợ.
- **Business Rule source:** PT role không đồng nghĩa có PT profile; Admin branch/resource scope; onboarding/profile mutation phải audit.

### BLOCKER-02 — Admin truy vấn lịch sử phân công PT

- **Actor / use case / screen:** ADMIN; reload/list/filter/detail để create/end/reassign; Quản lý phân công PT.
- **Missing Backend API:** `GET /api/admin/pt/assignments` và `GET /api/admin/pt/assignments/{assignment}`.
- **Existing APIs checked:** Chỉ có POST `/api/pt/assignments`, PATCH `/{assignment}/end`, POST `/{assignment}/reassign`; PT GET `/api/pt/members` và Member GET `/api/pt/assignment` không có Admin/history scope.
- **Why insufficient:** Sau navigation/timeout Admin không có source-of-truth id/status để recover mutation hoặc xem history; không được dùng PT token/scope.
- **Minimal proposed contract:** Auth ADMIN; filters `member_id`, `trainer_id`, `current`, `page`, `per_page`; branch-scoped, ordered; DTO `{id, member{id,code,name}, trainer{id,code,name,status}, starts_at, ends_at, reason, is_current, created_at}`; detail cùng shape. Không client-select branch.
- **Business Rule source:** Một Member có 0..1 PT hiệu lực, nhiều history; khoảng `[start,end)`, không overlap; reassign giữ history; Q05 gắn conversation với exact assignment.

### BLOCKER-03 — PT đọc chi tiết Member được phân công

- **Actor / use case / screen:** PT; xem safe profile trước coaching; Chi tiết Hội viên.
- **Missing Backend API:** `GET /api/pt/members/{member}`.
- **Existing APIs checked:** `/api/pt/members` chỉ trả assignment + id/code/name; `/api/profile/member` là Member self; Admin account API role ADMIN.
- **Why insufficient:** Workspace không có mục tiêu/hồ sơ an toàn cần cho PT; ghép API khác phá role/resource scope.
- **Minimal proposed contract:** Auth PT, exact current assignment; safe allow-list theo dữ liệu coaching đã được duyệt, tối thiểu member `{id,code,name}` + assignment `{id,starts_at,ends_at}` và các profile fields Backend xác nhận được phép. 404 conceal old/foreign PT; không trả password/contact nhạy cảm không cần thiết.
- **Business Rule source:** Role PT không cấp arbitrary Member access; exact assignment/resource scope là authority.

### BLOCKER-04 — PT đọc kế hoạch tập hiện tại của Member

- **Actor / use case / screen:** PT; tư vấn dựa official current/future Plan; Kế hoạch tập Hội viên.
- **Missing Backend API:** Tối thiểu `GET /api/pt/members/{member}/workout/plans/current`.
- **Existing APIs checked:** `/api/workout/plans/current`, `/plans/{plan}`, `/schedule` đều `MEMBER` only; Proposal list có content đề xuất nhưng không phải official plan.
- **Why insufficient:** Không thể chứng minh official Plan hoặc future schedule; dùng Proposal làm Plan vi phạm workflow.
- **Minimal proposed contract:** Auth PT + exact current assignment; read-only DTO tái dùng Workout Plan/version/day/exercise shape đã an toàn; có future schedule nếu screen cần, hoặc endpoint schedule riêng. Không cần active Membership để đọc Workout; 404 conceal foreign/old assignment.
- **Business Rule source:** Workout tracking không phụ thuộc Membership; official Plan khác Proposal; PT Proposal không trực tiếp mutate Plan.

### BLOCKER-05 — PT đọc Workout History của Member

- **Actor / use case / screen:** PT; xem completed sessions và set results; Lịch sử tập Hội viên.
- **Missing Backend API:** Tối thiểu `GET /api/pt/members/{member}/workout/sessions?before_id&limit` và `GET /api/pt/members/{member}/workout/sessions/{session}`.
- **Existing APIs checked:** `/api/workout/sessions` và `/{session}` là Member self-only; Progress aggregates không thay thế session detail.
- **Why insufficient:** Aggregate không chứng minh từng completed immutable session; không được gọi Member route với PT token.
- **Minimal proposed contract:** Auth PT + exact current assignment, bounded cursor pagination; read-only snapshot DTO, completed/in-progress states theo rule đã duyệt; conceal foreign scope. Không cung cấp mutation route cho PT.
- **Business Rule source:** Completed Workout Session bất biến; Workout History khác future Plan; read history không phụ thuộc active Membership.

### BLOCKER-06 — Receptionist tra cứu Member

- **Actor / use case / screen:** RECEPTIONIST; tìm Member để hỗ trợ/check Membership; Tra cứu Hội viên.
- **Missing Backend API:** `GET /api/receptionist/members?search=&page=&per_page=`.
- **Existing APIs checked:** `/api/admin/accounts` là ADMIN only; `/api/pt/members` là PT assignment scope; `/api/profile/member` là self.
- **Why insufficient:** Không có route hợp lệ cho Receptionist; dùng Admin API là privilege escalation workaround.
- **Minimal proposed contract:** Auth RECEPTIONIST, branch-scoped, min search length/rate limit phù hợp; safe DTO `{id,member_code,name,masked_or_approved_contact,account_status}`; pagination; không trả roles/audit/password/payment.
- **Business Rule source:** Receptionist chỉ basic Member support/QR; branch và least privilege.

### BLOCKER-07 — Receptionist kiểm tra Membership/Gym eligibility

- **Actor / use case / screen:** RECEPTIONIST; xem trạng thái trước hỗ trợ check-in; Kiểm tra trạng thái Hội viên.
- **Missing Backend API:** `GET /api/receptionist/members/{member}/membership`.
- **Existing APIs checked:** `/api/membership` là MEMBER self; dashboard chỉ aggregate; QR check-in nhận token và không phải query member status.
- **Why insufficient:** Frontend không được tự tính trạng thái từ Payment/date hoặc dùng Member token.
- **Minimal proposed contract:** Auth RECEPTIONIST + branch/member scope; Backend trả current term/status, Gym eligibility, effective start/end nếu được phép, remaining display data cần thiết và reason code an toàn. Không trả Payment secrets, không mutation/activate endpoint.
- **Business Rule source:** Payment ownership khác activation; first valid paid use activates common clock; Backend là authority cho term/order/eligibility.

Terra phải dừng **tính năng bị blocker**, ghi checkpoint và tiếp tục feature READY không phụ thuộc. Chỉ một task Backend được owner cho phép riêng mới được triển khai các contract đề xuất trên.

## 34. Backend Fixes In Progress

Nguồn mới nhất: `BACKEND_FOLLOW_UP_FIXES.md` hậu kiểm 31/08/2026. Chưa mục nào dưới đây được kế hoạch Web tự coi là hoàn thành.

| Fix | Hiện trạng source đã xác minh | Tác động Web / output Backend cần trước khi FE pass |
| --- | --- | --- |
| BE-FOLLOWUP-01A Payment event visibility | `danhSachSuKien()` loại `lan_thanh_toan_id = NULL` | `/api/admin/payment-events` trả event unknown/invalid đã lọc an toàn với `payment_id=null`, vẫn branch-safe, không raw payload/signature; tests PASS |
| BE-FOLLOWUP-01B Reconciliation filter | Filter chỉ xét Payment/order state, chưa xét related event `CAN_DOI_SOAT` | Payment success có abnormal event vẫn xuất hiện với `reconciliation_required=1`; tests PASS |
| BE-FOLLOWUP-02 Invitation recovery | Account/role/profile commit trước queue; retry replay/UNCHANGED không enqueue lại; response có thể báo QUEUED sớm | Retry/recovery đáng tin, không duplicate account/profile/role/audit; UI chỉ hiển thị trạng thái thật. Áp dụng PT onboarding; Bootstrap Admin là vận hành ngoài screen nhưng vẫn cần production fix |
| BE-FOLLOWUP-03 PT onboarding audit snapshot | Role/profile audit before/after đang null/thiếu | Grant/regrant/profile change có before/after allow-list, cùng transaction; forced audit failure rollback; không duplicate success audit |
| BE-FOLLOWUP-04 Reverb external probe | Probe HTTP `<500` chưa chứng minh WebSocket/auth | Handshake/protocol/auth probe thật PASS trên staging, không in key/secret; failure fail-closed |

Ma trận có 4 rows `BACKEND_FIX_IN_PROGRESS`: hai onboarding capabilities và hai Payment visibility capabilities. Reverb probe được phân loại ở screen là `DEPLOYMENT_DEPENDENCY` vì REST API/channel code đã có nhưng môi trường thật chưa được chứng minh.

## 35. Deployment Dependencies

| Dependency | Cần cho | Gate |
| --- | --- | --- |
| API base URL HTTPS | Mọi Web API | `VITE_API_BASE_URL` đúng environment; không fallback localhost trong production build |
| CORS/exact Web origin | Axios + broadcasting auth | Origin staging/production allow-list; credential mode phù hợp Bearer; preflight PASS |
| Reverb server + WSS | PT realtime Chat | Public key/host/port/scheme; không secret; WebSocket handshake + private auth PASS |
| Reverb allowed origins | PT realtime security | Thay wildcard `*` bằng exact trusted origins ở production; smoke từ origin khác bị từ chối |
| Queue worker | Chat outbox delivery, password invitation/reset | Supervisor running, non-`sync` production, failed job monitoring; retry test |
| Scheduler | Outbox retry/operational jobs | Supervisor/cron running; heartbeat/last-run preflight PASS |
| Backend Reverb external preflight | Release evidence | BE-FOLLOWUP-04 hoàn thành và chạy authorized staging external check |
| TLS/domain/proxy | Token + WSS transport | HTTPS/WSS, proxy headers/ports/path đúng, mixed content không có |
| Initial production catalog/config | Admin catalog/demo | Safe seed/import được owner phê duyệt; không dùng demo seeder production |
| Gemini credentials/provider | Member Mobile AI, không phải Web core này | Backend-only; không đưa vào Web bundle; production platform vẫn cần nếu release toàn hệ thống |

Không dùng `DEPLOYMENT_DEPENDENCY` để che missing route. Bảy missing contract vẫn là `BACKEND_API_BLOCKER` dù staging có đầy đủ dịch vụ.

## 36. Risks

| Risk | Mức | Mitigation / evidence cần |
| --- | --- | --- |
| FE lockfile dùng Vue Router 5 trong khi ecosystem/examples thường khác | Cao | Viết tests theo actual installed API, không copy cấu hình Router 4; build sớm ở FE-0 |
| Chưa có test/lint stack, việc thêm dependency ảnh hưởng lockfile | Cao | Chỉ dùng danh sách mục 6A; compatibility check trước cài; ghi version/evidence checkpoint |
| Token trong browser chịu XSS risk | Cao | sessionStorage, CSP, no `v-html`, dependency audit, no token logging, short/Backend expiry |
| Reverb wildcard origin + environment chưa verified | Cao | Exact origins, WSS, external handshake/auth smoke; không mark realtime complete bằng mock |
| Q05 stale cache/subscription sau reassignment | Cao | Central cleanup, assignment refetch, 403/404 handling, leave/reconnect tests |
| Payment reconciliation UI che mất abnormal events do Backend query | Cao | Chờ BE-FOLLOWUP-01; không client-side compensate hoặc declare empty queue safe |
| Onboarding báo invitation sai sau queue failure | Cao | Chờ recovery contract; copy trung tính; same-key retry; không invent resend |
| Missing PT/Receptionist/Admin assignment APIs cản thesis flows | Cao | Ưu tiên Backend contracts trên critical path; route blocker riêng, no workaround |
| Admin catalog lists all/max 500, PT notes/proposals/direct max 100 | Trung bình | Ghi giới hạn MVP, tránh infinite client assumptions; backlog pagination, monitor payload |
| Non-uniform response shapes | Trung bình | Service-specific adapters + contract fixtures; không universal unwrap sai |
| Multi-role redirect mơ hồ | Trung bình | Explicit role selector; không priority; test switch/logout/guard |
| Frontend tự suy Membership/Payment/quota | Cao | Central status labels, read-only Payment, Backend result only, tests for misleading copy |
| Naming rule làm code khó đọc nếu áp máy móc | Trung bình | Official framework exceptions + manual review; names ngắn, business-specific |
| Blocker placeholder bị hiểu là feature hoàn thành | Trung bình | `chan_tinh_nang_bi_chan.vue`, status/count checkpoint, navigation feature flag |

## 37. Critical Path

```text
PROJECT_RULES + actual contract
        -> FE-0 auth/client/router/test foundation
        -> FE-1 Admin shell/account primitives
        -> FE-3 Admin catalog --------------------------┐
        -> Backend Payment fix -> FE-4 -----------------|
        -> Backend PT APIs/fixes -> FE-2 + FE-5 --------|-> FE-9 final acceptance
        -> FE-5 assigned-member context -> FE-6 --------|
        -> Reverb deploy/preflight + FE-5 -> FE-7 ------|
        -> Backend Receptionist APIs -> FE-8 -----------┘
```

Ưu tiên Backend critical: Payment reconciliation visibility; Admin assignment query; PT Member/Plan/History reads; Receptionist lookup/Membership; invitation recovery/audit; Reverb preflight/deployment. FE-3 và phần READY của FE-6 có thể tiến trong lúc Backend blockers được xử lý. FE-7 phụ thuộc cả FE-5 context cleanup và Reverb environment.

## 38. Relative Effort

| Phase | Effort | Lý do chính | Backend/realtime dependence |
| --- | --- | --- | --- |
| FE-0 | L | Auth/client/router/layout/test stack từ skeleton | Auth READY; dependency compatibility |
| FE-1 | L | 7 Admin screens + role/status conflict UX | READY |
| FE-2 | L | Onboarding/idempotency/profile/assignment complexity | 2 blockers + 2 fixes |
| FE-3 | XL | 12 screens, tree editor, relations, snapshot/stale | READY |
| FE-4 | M | 3 read-only screens nhưng reconciliation semantics nhạy cảm | Payment fix bắt buộc |
| FE-5 | L | 7 PT screens, resource cleanup, mixed readiness | 3 blockers |
| FE-6 | L | Stable idempotency + Q13 conflict workflow | READY, list limits |
| FE-7 | XL | REST/realtime merge, sequence, reconnect, Q05 | Reverb deployment/preflight |
| FE-8 | M | QR + 2 core screens bị thiếu API | 2 blockers |
| FE-9 | L | Cross-role integration, a11y/security/naming/report | Tất cả gates |

Không quy đổi person-day vì chưa có team size, UX design hoàn chỉnh và lịch Backend fix.

## 38A. Task Breakdown for Terra

FE-0 → FE-2 giữ nguyên task code nhỏ vì đã được triển khai/checkpoint theo cấu trúc đó. Từ FE-3 → FE-9, mỗi phase là **một task tổng hợp duy nhất** (`FE3-ALL` … `FE9-ALL`). Các nhóm chức năng bên trong chỉ là acceptance checklist để bảo toàn phạm vi và test coverage; không được tạo brief, writer, reviewer hoặc checkpoint transition riêng cho từng mục checklist.

Khi dùng workflow Fitness SDD cho một task `FE*-ALL`, routing tối thiểu là: một Sol High initial plan/brief tổng hợp → một Luna Max implementation/test cho toàn phase → một fresh Sol High task review → một fresh Sol High final review. Nếu có blocking finding, dùng một consolidated fix plan và một lượt Luna fix cho toàn bộ finding đang mở rồi review lại; không tách repair theo từng màn hình. Minor finding không tự mở repair loop nếu workflow hiện hành không yêu cầu.

### Quy tắc thực thi task

Mỗi task phải:

1. Đọc source/contract liên quan ngay trước khi code.
2. Ghi `IN_PROGRESS` vào checkpoint trước thay đổi lớn.
3. Chỉ sửa file thuộc scope task hoặc dependency nền đã nêu.
4. Chạy targeted test/build phù hợp trước khi đánh `PASS`.
5. Ghi file tạo/sửa + test command + kết quả vào checkpoint.
6. Với FE-0 → FE-2, nếu gặp `BACKEND_API_BLOCKER`, ghi blocker và chuyển sang task READY khác được Phase Gates cho phép. Với FE-3 → FE-9, chỉ dispatch task `FE*-ALL` khi toàn bộ entry gate của phase đã mở; nếu dependency chưa sẵn sàng thì giữ nguyên `WAITING_BACKEND_FIX`, `WAITING_DEPLOYMENT` hoặc `BLOCKED`, không tách phần READY thành task khác và không tiêu tốn writer/reviewer cho một phase chưa thể hoàn tất.
7. Không commit/push.
8. Với FE-3 → FE-9, checkpoint dùng đúng một transition `IN_PROGRESS` và một kết luận `PASS`/`BLOCKED` cho task `FE*-ALL`; không đánh PASS từng mục checklist.

### FE-0 — Foundation

| Task | Nội dung | Phụ thuộc | Done khi |
| --- | --- | --- | --- |
| `FE0-T01` | Chụp baseline FE: package/lockfile, build hiện tại, tree source, env usage | Không | Baseline + checkpoint có evidence |
| `FE0-T02` | Cài test/lint dependency tối thiểu mục 6A, tạo script/config | T01 | `npm run test`, `npm run lint` chạy được tối thiểu smoke |
| `FE0-T03` | Chuẩn hóa env + Axios client + error normalization | T01 | Không hardcode production localhost; unit test error client PASS |
| `FE0-T04` | Auth service + `xac_thuc.store.js` + sessionStorage lifecycle | T03 | login/me/logout/restore + cleanup tests PASS |
| `FE0-T05` | Router public/protected, actor login routes, multi-role selector, 403/404 | T04 | Router/guard integration PASS |
| `FE0-T06` | Layout chung + Admin/PT/Lễ tân/public/error shell baseline | T05 | Route render đúng layout, responsive baseline PASS |
| `FE0-T07` | Shared query/form/error/toast/confirm components + CSS tokens/Visual UI System | T06 | Component smoke + a11y baseline PASS |
| `FE0-T08` | Idempotency, safe redirect, session helpers, naming/docblock scan | T03-T07 | Unit tests + naming/docblock scan PASS |
| `FE0-T09` | Full FE-0 gate và checkpoint | T01-T08 | test/lint/build PASS; FE-0 = PASS |

### FE-1 — Admin Foundation

| Task | Nội dung | Phụ thuộc | Done khi |
| --- | --- | --- | --- |
| `FE1-T01` | Admin navigation/menu/breadcrumb + route meta | FE-0 | Admin shell/guard PASS |
| `FE1-T02` | Dashboard page + from/to filter + metric cards | T01 | Exact API metrics, no invented revenue, tests PASS |
| `FE1-T03` | Account list/search/filter/pagination | T01 | List/filter/error states PASS |
| `FE1-T04` | Account detail + account status mutation | T03 | 409/422/timeout/refetch UX PASS |
| `FE1-T05` | Role grant/revoke/regrant + last-admin/profile conflict UX | T04 | Role regression component/integration PASS |
| `FE1-T06` | Member account-oriented list/detail | T03 | Không gọi PT/Member-self API; READY flow PASS |
| `FE1-T07` | Receptionist account-oriented list/detail | T03 | Không invent profile; READY flow PASS |
| `FE1-T08` | Full FE-1 gate | T01-T07 | test/lint/build/naming/docblock PASS |

### FE-2 — Admin PT

| Task | Nội dung | Phụ thuộc | Done khi |
| --- | --- | --- | --- |
| `FE2-T01` | PT list từ Admin account filter | FE-1 | PT list READY PASS |
| `FE2-T02` | UI create/onboard PT + stable Idempotency-Key | T01 + BE follow-up 02/03 | Không báo invitation sai; tests PASS |
| `FE2-T03` | PT detail account/role summary | T01 | READY subset render đúng |
| `FE2-T04` | Full trainer-profile prefill/edit | BLOCKER-01 | Chỉ chạy khi API READY; integration PASS |
| `FE2-T05` | Assignment screen shell + blocker state | FE-1 | Không fake API, không fake history |
| `FE2-T06` | Assignment list/detail/filter | BLOCKER-02 | GET source-of-truth PASS |
| `FE2-T07` | Create/end/reassign assignment + timeout/refetch/Q05 warning | T06 | Mutation + conflict/history PASS |
| `FE2-T08` | Full FE-2 gate | T01-T07 | READY + resolved blocker flows PASS |

### FE-3 — Admin Catalog

| Task | Nội dung | Phụ thuộc | Done khi |
| --- | --- | --- | --- |
| `FE3-ALL` | Toàn bộ Admin Catalog: Package, benefits, Equipment, Muscle Group, Exercise, Workout Template và full gate | FE-1 PASS; actual catalog contracts READY | Toàn bộ checklist FE-3, focused tests và full test/lint/build/naming/docblock gate PASS; task/final review PASS |

**Acceptance checklist của `FE3-ALL` — không phải subtask:**

- Package list/create/detail/metadata; Package benefits replace và cảnh báo snapshot kỳ Membership cũ không đổi.
- Equipment list/create/edit/deactivate; không hard-delete, không mở rộng Equipment Maintenance/Asset Management.
- Muscle Group list/create/edit/deactivate và catalog conflict UX.
- Exercise list/search/detail/create/edit/deactivate; relation payload đúng contract và Equipment semantics AND theo Q11.
- Workout Template list/detail/metadata-only PATCH, create + tree editor, structure validation, revision copy-on-write và `WORKOUT_TEMPLATE_STALE` recovery giữ draft, không xóa history.
- Full FE-3 gate: targeted/full tests, lint, production build, naming/docblock/secret/contract scan và evidence report.

### FE-4 — Admin Payment

| Task | Nội dung | Phụ thuộc | Done khi |
| --- | --- | --- | --- |
| `FE4-ALL` | Toàn bộ Admin Payment và reconciliation read-only, gồm full gate | FE-1 PASS; BE-FOLLOWUP-01A/01B tests PASS | Toàn bộ checklist FE-4, visibility/security tests và full test/lint/build gate PASS; task/final review PASS |

**Acceptance checklist của `FE4-ALL` — không phải subtask:**

- Payment list, filters, pagination và detail bằng safe DTO.
- Reconciliation list hiển thị unlinked Payment events và Payment success có abnormal event theo contract đã sửa.
- Không lộ secret/raw payload/signature; không có controls success/refund/edit/Membership.
- Full FE-4 gate: targeted/full tests, lint, production build, naming/docblock/secret/contract scan và evidence report.

### FE-5 — PT Member Workspace

| Task | Nội dung | Phụ thuộc | Done khi |
| --- | --- | --- | --- |
| `FE5-ALL` | Toàn bộ PT Member Workspace: shell/profile, assigned context, Member detail, Progress, Plan, History, Notes, cleanup và full gate | FE-0 PASS; BLOCKER-03/04/05 đã có contract + Backend tests PASS | Toàn bộ checklist FE-5 và exact-scope regression/full gate PASS; task/final review PASS |

**Acceptance checklist của `FE5-ALL` — không phải subtask:**

- PT layout/navigation/guard và self-only profile read/update.
- Assigned Member list, selected-member context và exact assignment cleanup.
- Safe Member detail qua PT-scoped API; không gọi Admin/Member-self workaround.
- Progress overview/body/exercise read-only với loading/empty/error states.
- Official current/future Workout Plan read-only; không dùng Proposal thay official Plan.
- Workout History list/detail read-only; completed Session không có edit control.
- PT Notes append/view; không side effect lên Plan hoặc completed Session.
- 403/404/assignment-loss regression xóa selected Member và dữ liệu nhạy cảm.
- Full FE-5 gate: targeted/full tests, lint, production build, naming/docblock/secret/contract scan và evidence report.

### FE-6 — PT Direct + Proposal

| Task | Nội dung | Phụ thuộc | Done khi |
| --- | --- | --- | --- |
| `FE6-ALL` | Toàn bộ PT Direct + Proposal, idempotency/Q13/conflict và full gate | FE5-ALL PASS; actual Direct/Proposal contracts READY | Toàn bộ checklist FE-6, Q13/idempotency tests và full test/lint/build gate PASS; task/final review PASS |

**Acceptance checklist của `FE6-ALL` — không phải subtask:**

- PT Direct history với giới hạn dữ liệu được mô tả trung thực.
- Complete PT Direct dùng stable `Idempotency-Key`, pending guard, timeout/unknown-outcome refetch và không tạo duplicate ledger UX.
- Proposal list + preview và create với stable key.
- Q13 copy đúng: valid assignment là prerequisite; không yêu cầu Membership/Chat/direct quota và không tạo side effect tương ứng.
- 409 stale/assignment/template/context conflict giữ draft, refetch và không overwrite tự động.
- Full FE-6 gate: targeted/full tests, lint, production build, naming/docblock/secret/contract scan và evidence report.

### FE-7 — PT Realtime Chat

| Task | Nội dung | Phụ thuộc | Done khi |
| --- | --- | --- | --- |
| `FE7-ALL` | Toàn bộ PT Realtime Chat REST-first + Echo/Reverb, Q05, staging smoke và full gate | FE5-ALL PASS; dependency compatibility PASS; BE-FOLLOWUP-04/Reverb deployment ready | Toàn bộ checklist FE-7, authorized staging WSS/private-channel smoke và full test/lint/build gate PASS; task/final review PASS |

**Acceptance checklist của `FE7-ALL` — không phải subtask:**

- Cài đúng Echo/Pusher dependency mục 6A sau compatibility check; realtime config adapter không đưa secret vào bundle.
- Conversation/history REST loading và REST-only fallback hoạt động khi Reverb down.
- Send message có pending row và stable `client_message_id`; retry/dedupe đúng.
- Private channel auth/subscribe/unsubscribe theo contract thật.
- Merge theo `message_id` + `sequence`; xử lý duplicate/out-of-order/gap.
- Reconnect + REST catch-up bằng endpoint thật; không invent `after_sequence`.
- Q05 scope-loss cleanup dừng retry/reconnect, leave channel và xóa dữ liệu old PT.
- Authorized staging WSS/private-channel smoke PASS; nếu môi trường chưa sẵn sàng thì toàn task giữ `WAITING_DEPLOYMENT`, không đánh PASS bằng mock/unit.
- Full FE-7 gate: targeted/full tests, lint, production build, naming/docblock/secret/contract scan và evidence report.

### FE-8 — Receptionist

| Task | Nội dung | Phụ thuộc | Done khi |
| --- | --- | --- | --- |
| `FE8-ALL` | Toàn bộ Receptionist portal: shell, lookup, Membership/Gym eligibility, QR, camera fallback và full gate | FE-0 PASS; BLOCKER-06/07 đã có contract + Backend tests PASS | Toàn bộ checklist FE-8, role/API isolation tests và full test/lint/build gate PASS; task/final review PASS |

**Acceptance checklist của `FE8-ALL` — không phải subtask:**

- Receptionist shell/navigation/guard và role isolation.
- Branch-safe Member lookup qua Receptionist API.
- Membership/Gym eligibility do Backend tính; FE không suy từ Payment/ngày.
- QR Check-in manual input/result với safe outcome, pending/double-click/replay handling.
- Camera adapter chỉ khi browser capability phù hợp; manual fallback luôn còn và permission failure không khóa nghiệp vụ.
- Không gọi Admin hoặc Member-self API làm workaround.
- Full FE-8 gate: targeted/full tests, lint, production build, naming/docblock/secret/contract scan và evidence report.

### FE-9 — Final Acceptance

| Task | Nội dung | Phụ thuộc | Done khi |
| --- | --- | --- | --- |
| `FE9-ALL` | Toàn bộ Final Acceptance: cross-role audit, full tests, a11y/responsive/security/contract scans, build, checkpoint/report và final readiness gate | FE-0 → FE-8 PASS; mọi Backend blocker/fix/deployment gate bắt buộc đã resolved | Tất cả checklist mục 40 có evidence PASS; checkpoint/report trung thực; task/final review PASS; Web mới được đánh `COMPLETE` |

**Acceptance checklist của `FE9-ALL` — không phải subtask:**

- Cross-role route/navigation/session/domain-state cleanup audit; không leakage actor hoặc sensitive state.
- Full unit/component/router/service/realtime tests và staging API smoke phù hợp.
- Responsive + keyboard/focus/form/a11y review theo Visual UI System.
- Naming + docblock + secret + route/API contract scan và manual exception review.
- Production build + bundle secret/config check.
- Cập nhật checkpoint và tạo completion report đầy đủ evidence.
- Final readiness gate chỉ PASS/`COMPLETE` khi toàn bộ blockers, Backend fixes và deployment dependencies bắt buộc đã resolved.

### Backend tasks chạy song song — KHÔNG giao cho Terra FE nếu chưa có ủy quyền Backend

| Task | Nội dung | Ảnh hưởng FE |
| --- | --- | --- |
| `BE-WEB-T01` | Fix Payment unlinked events + reconciliation abnormal-event filter | Mở entry gate `FE4-ALL` |
| `BE-WEB-T02` | Fix invitation recovery cho Bootstrap/PT onboarding | Mở FE2-T02 đầy đủ |
| `BE-WEB-T03` | Fix PT onboarding audit before/after | Mở FE2-T02 đầy đủ |
| `BE-WEB-T04` | Fix Reverb external probe + staging config | Mở entry gate `FE7-ALL` |
| `BE-WEB-T05` | Admin GET trainer profile | Mở FE2-T04 |
| `BE-WEB-T06` | Admin GET PT assignment list/detail/history | Mở FE2-T06/T07 |
| `BE-WEB-T07` | PT GET assigned Member detail | Mở entry gate `FE5-ALL` |
| `BE-WEB-T08` | PT GET current Workout Plan | Mở entry gate `FE5-ALL` |
| `BE-WEB-T09` | PT GET Workout History/session detail | Mở entry gate `FE5-ALL` |
| `BE-WEB-T10` | Receptionist Member lookup | Mở entry gate `FE8-ALL` |
| `BE-WEB-T11` | Receptionist Membership/Gym eligibility | Mở entry gate `FE8-ALL` |

### Recommended Terra / Fitness SDD batching

FE-0 → FE-2 giữ batch lịch sử theo task nhỏ. FE-3 → FE-9 mỗi batch là một phase-task tổng hợp; không tạo thêm batch con:

```text
Batch 1  -> FE0-T01 .. FE0-T09
Batch 2  -> FE1-T01 .. FE1-T08
Batch 3  -> FE2 READY subset + resolved blockers
Batch 4  -> FE3-ALL                      (chỉ sau FE-2 PASS)
Batch 5  -> FE4-ALL                      (chỉ sau BE-WEB-T01 PASS)
Batch 6  -> FE5-ALL                      (chỉ sau BE-WEB-T07/08/09 PASS)
Batch 7  -> FE6-ALL                      (sau FE5-ALL PASS)
Batch 8  -> FE7-ALL                      (sau FE5-ALL + Reverb/deployment gate)
Batch 9  -> FE8-ALL                      (chỉ sau BE-WEB-T10/11 PASS)
Batch 10 -> FE9-ALL                      (sau FE-0 → FE-8 PASS)
```

Số batch giữ nhãn lịch sử để dễ đối chiếu roadmap; execution thực tế phải theo Phase Gates và checkpoint hiện hành. Mỗi batch kết thúc bằng checkpoint. Batch kế tiếp phải đọc checkpoint trước khi code. Với `FE3-ALL` → `FE9-ALL`, một batch chỉ có một brief/report/review package cho toàn phase.

## 39. Terra Execution Instructions

### Chỉ thị bắt buộc cho GPT-5.6 Terra

1. Đọc `.fitness-rules/PROJECT_CORE.md` và các module quy tắc tương ứng qua `.fitness-rules/RULE_INDEX.md`. Không mặc định đọc toàn bộ `PROJECT_RULES.md` trừ khi gặp xung đột hoặc điều kiện leo thang.
2. Đọc file Phase Context tương ứng trong `.fitness-sdd/context/` (ví dụ: `FE3_CONTEXT.md`) và Task Packet được giao; không mặc định đọc toàn bộ 2,000 dòng của `VUE_WEB_IMPLEMENTATION_PLAN_V2.md`.
3. Đọc contract/source Backend thực tế trước mỗi phase.
4. Triển khai đúng thứ tự FE-0 đến FE-9; chỉ song song theo Phase Gates đã ghi.
5. Không sửa Backend nếu chưa có task/ủy quyền riêng.
6. Không sửa Mobile.
7. Không invent API.
8. Không thay đổi business rule.
9. Frontend page URL dùng tiếng Việt không dấu kebab-case.
10. Business filename dùng tiếng Việt không dấu snake_case.
11. Business function dùng tiếng Việt không dấu camelCase.
12. Event handler dùng tiếng Việt không dấu camelCase.
13. Business variable ưu tiên tiếng Việt không dấu camelCase.
14. Important business function bắt buộc có docblock meaningful.
15. UI label dùng tiếng Việt **có dấu**.
16. Framework API giữ tên chính thức.
17. Backend API path giữ đúng contract thực tế.
18. Chạy test sau mỗi phase.
19. Chạy build sau phase liên quan.
20. Cập nhật checkpoint sau mỗi phase.
21. Dừng riêng feature có `BACKEND_API_BLOCKER`; không workaround.
22. Không commit.
23. Không push.
24. Composable/helper do nhóm tạo không dùng prefix tiếng Anh `use_`; dùng tên như `su_dung_xac_thuc.js` và hàm `suDungXacThuc()` nếu cần export composable.
25. Tuân thủ Visual UI System mục 7A; không tự chọn một design language khác cho từng phase.
26. Được phép cài đúng dependency trong mục 6A sau compatibility check; package ngoài danh sách phải dừng và ghi blocker.
27. Thực thi theo task code ở mục 38A: FE-0 → FE-2 giữ task code lịch sử; FE-3 → FE-9 dùng đúng một task `FE*-ALL` cho mỗi phase. Chỉ cập nhật checkpoint theo task/batch được định nghĩa, không tự tách checklist thành task mới.

Ngoài 27 chỉ thị trên: không báo phase PASS nếu chỉ render UI; không chạy destructive DB command; không đưa secret vào Web; khi source khác kế hoạch phải dừng phần ảnh hưởng, ghi evidence và cập nhật plan/checkpoint được owner duyệt.

### Future checkpoint plan

Khi bắt đầu triển khai FE-0, Terra tạo `docs/VUE_WEB_COMPLETION_CHECKPOINT.md` (không tạo trong task planning hiện tại) với các field bắt buộc và cập nhật sau mỗi phase:

```text
CURRENT_PHASE
COMPLETED_PHASES
BLOCKED_FEATURES
BACKEND_API_BLOCKERS
FILES_CREATED
FILES_MODIFIED
ROUTES_CREATED
TESTS_PASS
TESTS_FAIL
BUILD
LINT
NAMING_SCAN
DOCBLOCK_SCAN
EXACT_NEXT_ACTION
```

Mỗi field cần evidence command/result thật; `TESTS_FAIL` không được xóa lịch sử bằng cách chỉ ghi run cuối nếu failure chưa được giải thích. `EXACT_NEXT_ACTION` là một hành động cụ thể tiếp theo, không phải “tiếp tục làm FE”.

### Future completion report plan

Chỉ ở FE-9, sau khi có evidence, Terra tạo `docs/VUE_WEB_COMPLETION_REPORT.md` với đúng các phần:

1. Scope
2. Architecture
3. Routes
4. Screens
5. Files Created
6. Files Modified
7. Main Functions
8. API Integration
9. Authorization UX
10. Idempotency
11. Realtime
12. Q05
13. Q13
14. Error Handling
15. Tests
16. Build
17. Naming Audit
18. Docblock Audit
19. Backend Blockers
20. Remaining Work
21. Final Gate

Báo cáo phải phân biệt `COMPLETE`, `PARTIALLY_READY`, `BLOCKED`, không chuyển blocker/deployment dependency thành “đã xong” vì component đã tồn tại.

## 40. Final Web Readiness Gate

Web chỉ được đánh dấu `COMPLETE` khi tất cả điều kiện dưới đây PASS bằng evidence:

- [ ] 50 router records/48 screens đã được đối chiếu inventory; route blocker chỉ được mở khi API READY.
- [ ] Auth login/me/logout/restore, multi-role selector, account/role revoke và 401/403/404 cleanup PASS.
- [ ] Không có Member Web scope creep; Free/Premium không xuất hiện như role.
- [ ] Bảy `BACKEND_API_BLOCKER` đã có contract, implementation, Backend authorization/resource-scope tests và FE integration PASS; nếu còn bất kỳ blocker nào thì tối đa `PARTIALLY_READY`.
- [ ] Bốn matrix rows `BACKEND_FIX_IN_PROGRESS` đã có regression tests và FE behavior PASS.
- [ ] Admin Payment/reconciliation hoàn toàn read-only, hiển thị unlinked/abnormal events, không lộ payload/signature.
- [ ] Package snapshot, Exercise equipment AND, Template copy-on-write/409 stale được test.
- [ ] PT exact assignment scope, Plan vs History, completed Session immutability và Notes no-side-effect được test.
- [ ] Q13 Proposal không Membership/Chat/direct/AI quota prerequisite hoặc side effect; stable idempotency/conflict UX PASS.
- [ ] PT Direct stable key và Backend quota/term authority PASS.
- [ ] Chat REST fallback, `client_message_id`, dedupe/sequence/reconnect/catch-up và Q05 old-PT cleanup PASS.
- [ ] Reverb WSS/private auth/exact origin/queue/scheduler staging smoke PASS; không secret trong bundle.
- [ ] Receptionist lookup/Membership/QR dùng đúng role APIs; không Admin/Member-self workaround; QR Backend authority PASS.
- [ ] Mọi screen có loading/empty/error; mutation pending/timeout/retry/409 behavior rõ.
- [ ] Keyboard/focus/form label/error announcement/responsive baseline PASS.
- [ ] `npm run test`, `npm run lint`, `npm run build` PASS trên Node engine lockfile hỗ trợ.
- [ ] Naming scan, docblock scan, secret scan và manual exception review PASS.
- [ ] Visual UI System mục 7A được áp dụng nhất quán: token, spacing, layout, responsive, form/table state và không dùng màu làm tín hiệu duy nhất.
- [ ] Task Breakdown mục 38A có trạng thái/evidence tương ứng trong checkpoint; với FE-3 → FE-9, từng `FE*-ALL` có một evidence package đầy đủ và các checklist nội bộ không bị bỏ qua bằng cách chỉ đánh phase PASS.
- [ ] Completion checkpoint/report phản ánh đúng file, route, test fail, blockers và deployment state.
- [ ] Không sửa BE/Mobile ngoài ủy quyền, không invent API/business rule, không commit, không push trong chuỗi Terra được giao.

Trạng thái tại thời điểm lập kế hoạch: **NOT IMPLEMENTED / PARTIALLY BACKEND-READY**. Phase đầu tiên Terra phải thực hiện là **FE-0 Foundation**; không bắt đầu bằng page catalog hoặc chat.
