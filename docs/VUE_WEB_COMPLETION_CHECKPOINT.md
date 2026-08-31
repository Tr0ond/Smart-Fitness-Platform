# Vue Web Completion Checkpoint

CURRENT_PHASE: FE-0
CURRENT_TASK: FE0-REMEDIATION
STATUS: PASS
FE0_PHASE_STATUS: PASS

COMPLETED_PHASES:
- FE-0 — Foundation; chỉ được khôi phục PASS sau remediation và full gate 200 tests.

COMPLETED_TASKS:
- FE0-T01
- FE0-T02
- FE0-T03
- FE0-T04
- FE0-T05
- FE0-T06
- FE0-T07
- FE0-T08
- FE0-T09

REMEDIATED_TASKS:
- FE0-T03 — Axios 401 đã dùng Bearer snapshot; late response không xóa phiên mới.
- FE0-T04 — Restore `/me` đã phân biệt 401/auth-invalid với network/timeout/5xx và có retry.
- FE0-T05 — Guard route actor bắt buộc active actor khớp route; actor null về selector.
- FE0-T06 — Drawer mobile dùng đúng parent open class; focus sau navigation về heading.
- FE0-T07 — Form map/focus 422, countdown 429, dialog focus trap/return và empty table đã sửa.
- FE0-T09 — Full test/lint/build/static/browser gate đã chạy lại và PASS.

EXACT_NEXT_ACTION: FE1-T01 — Admin navigation/menu/breadcrumb + route meta; chưa bắt đầu trong task remediation này.

BLOCKED_FEATURES:
- Không còn blocker thuộc FE-0 sau remediation.
- Các blocker Backend/phase sau vẫn giữ nguyên ở `FUTURE_BACKEND_BLOCKERS`; FE-0 PASS không đồng nghĩa toàn bộ Web hoàn tất.

HISTORICAL_EVIDENCE_NOTICE:
- Các claim PASS của FE0-T01..T09 ở phần lịch sử bên dưới mô tả lần chạy trước hậu kiểm.
- Trạng thái đó đã bị thu hồi trước khi sửa source; chỉ `FE0_REMEDIATION_EVIDENCE` và các field đầu file là kết luận hiện hành.

BACKEND_API_BLOCKERS:
- Không có blocker từ Backend đối với FE0-T05; forgot/reset đã được đối chiếu với source/test/contract, không sửa Backend.

FUTURE_BACKEND_BLOCKERS:
- Giữ nguyên 7 BACKEND_API_BLOCKER trong V2 cho các phase sau: Admin PT profile, Admin assignment history, PT Member detail, PT current Workout Plan, PT Workout History, Receptionist Member lookup và Receptionist Membership/Gym eligibility.
- Giữ nguyên 4 BACKEND_FIX_IN_PROGRESS trong V2: Payment event visibility, reconciliation filter, invitation recovery, PT onboarding audit snapshot.
- Reverb external probe và các dependency triển khai HTTPS/CORS/WSS/queue/scheduler vẫn là release dependency; không thuộc FE-0 và không được tuyên bố đã hoàn tất.

FILES_CREATED:
- docs/VUE_WEB_COMPLETION_CHECKPOINT.md
- FE/vitest.config.js
- FE/eslint.config.js
- FE/src/app.test.js
- FE/src/utils/loi_api.js
- FE/src/services/api.test.js
- FE/src/utils/loi_api.test.js
- FE/src/services/xac_thuc.api.js
- FE/src/services/xac_thuc.api.test.js
- FE/src/stores/xac_thuc.store.js
- FE/src/stores/xac_thuc.store.test.js
- FE/src/components/xac_thuc/bieu_mau_dang_nhap.vue
- FE/src/pages/admin/dang_nhap/dang_nhap.index.vue
- FE/src/pages/pt/dang_nhap/dang_nhap.index.vue
- FE/src/pages/le_tan/dang_nhap/dang_nhap.index.vue
- FE/src/pages/chung/chon_vai_tro/chon_vai_tro.index.vue
- FE/src/pages/chung/quen_mat_khau/quen_mat_khau.index.vue
- FE/src/pages/chung/dat_lai_mat_khau/dat_lai_mat_khau.index.vue
- FE/src/pages/chung/loi/khong_co_quyen.vue
- FE/src/pages/chung/loi/khong_tim_thay.vue
- FE/src/router/bao_ve_tuyen_duong.js
- FE/src/router/bao_ve_tuyen_duong.test.js
- FE/src/router/index.test.js
- FE/src/pages/xac_thuc.test.js
- FE/src/utils/dieu_phoi_xac_thuc.js
- FE/src/utils/thong_bao_loi.js
- FE/src/components/bo_cuc/khung_ung_dung.vue
- FE/src/components/bo_cuc/thanh_ben_dieu_huong.vue
- FE/src/components/bo_cuc/thanh_tren.vue
- FE/src/layouts/bo_cuc_cong_khai.vue
- FE/src/layouts/bo_cuc_loi.vue
- FE/src/layouts/bo_cuc_admin.vue
- FE/src/layouts/bo_cuc_pt.vue
- FE/src/layouts/bo_cuc_le_tan.vue
- FE/src/layouts/bo_cuc.test.js
- FE/src/components/dung_chung/tieu_de_trang.vue
- FE/src/components/dung_chung/trang_thai_tai_du_lieu.vue
- FE/src/components/dung_chung/trang_thai_trong.vue
- FE/src/components/dung_chung/trang_thai_loi.vue
- FE/src/components/dung_chung/huy_hieu_trang_thai.vue
- FE/src/components/dung_chung/truong_bieu_mau.vue
- FE/src/components/dung_chung/bo_loc_danh_sach.vue
- FE/src/components/dung_chung/bang_du_lieu.vue
- FE/src/components/dung_chung/thanh_phan_trang.vue
- FE/src/components/dung_chung/hop_thoai_xac_nhan.vue
- FE/src/components/dung_chung/vung_thong_bao.vue
- FE/src/components/dung_chung/dung_chung.test.js
- FE/src/utils/khoa_idempotency.js
- FE/src/utils/khoa_idempotency.test.js
- FE/src/composables/su_dung_thao_tac_chong_lap.js
- FE/src/composables/su_dung_thao_tac_chong_lap.test.js
- FE/src/composables/su_dung_bieu_mau.js
- FE/src/utils/phien_dang_nhap.js
- FE/src/utils/phien_dang_nhap.test.js
- FE/src/utils/duong_dan_an_toan.js
- FE/src/utils/duong_dan_an_toan.test.js

FILES_MODIFIED:
- FE/README.md
- FE/package.json
- FE/package-lock.json
- FE/.env.example
- FE/src/services/api.js
- FE/src/services/api.test.js
- FE/src/utils/loi_api.js
- FE/src/utils/loi_api.test.js
- FE/src/utils/thong_bao_loi.js
- FE/src/main.js
- FE/src/router/index.js
- FE/src/router/index.test.js
- FE/src/router/bao_ve_tuyen_duong.js
- FE/src/router/bao_ve_tuyen_duong.test.js
- FE/src/services/xac_thuc.api.js
- FE/src/stores/xac_thuc.store.js
- FE/src/App.vue
- FE/src/assets/main.css
- docs/VUE_WEB_COMPLETION_CHECKPOINT.md
- FE/src/components/xac_thuc/bieu_mau_dang_nhap.vue
- FE/src/components/bo_cuc/khung_ung_dung.vue
- FE/src/components/dung_chung/bang_du_lieu.vue
- FE/src/components/dung_chung/hop_thoai_xac_nhan.vue
- FE/src/components/dung_chung/dung_chung.test.js
- FE/src/pages/chung/quen_mat_khau/quen_mat_khau.index.vue
- FE/src/pages/chung/dat_lai_mat_khau/dat_lai_mat_khau.index.vue
- FE/src/pages/chung/chon_vai_tro/chon_vai_tro.index.vue
- FE/src/pages/xac_thuc.test.js
- FE/src/layouts/bo_cuc.test.js
- FE/src/stores/xac_thuc.store.js
- FE/src/stores/xac_thuc.store.test.js
- FE/src/utils/dieu_phoi_xac_thuc.js
- docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md

ROUTES_CREATED:
- `/` -> `dieuPhoiTrangGoc` (root dispatcher, không có business page riêng).
- `/chon-vai-tro` -> `chonVaiTro`.
- `/quen-mat-khau` -> `quenMatKhau`.
- `/dat-lai-mat-khau` -> `datLaiMatKhau`.
- `/khong-co-quyen` -> `khongCoQuyen`.
- `/khong-tim-thay` -> `khongTimThay`.
- `/admin/dang-nhap` -> `adminDangNhap`.
- `/pt/dang-nhap` -> `ptDangNhap`.
- `/le-tan/dang-nhap` -> `leTanDangNhap`.
- `/:pathMatch(.*)*` redirect nội bộ tới `khongTimThay`.
- Layout mapping trung tâm: `meta.boCuc` dùng `cong_khai`, `loi`, `admin`, `pt`, `le_tan`; T06 chỉ gắn mapping cho các route auth/error hiện có, không tạo domain route.
- Không tạo domain route, business route hoặc route FE0-T07+.

DEPENDENCIES_ADDED:
- `@vue/test-utils`: 2.4.6
- `eslint`: 10.9.1
- `eslint-plugin-vue`: 10.10.0
- `jsdom`: 29.0.1
- `vitest`: 4.1.11
- Đã kiểm tra compatibility với Node v22.20.0, Vue 3.5.42 và Vite 8.2.2. Chọn jsdom 29.0.1 vì jsdom latest yêu cầu Node `^22.22.2`.
- Không thêm Echo/Pusher, UI framework, Tailwind/Bootstrap, state library, Playwright hoặc Cypress.
- FE0-T03: Không thêm dependency; sử dụng Axios đã có trong baseline.
- FE0-T04: Không thêm dependency; sử dụng Pinia, Axios và Vitest đã có trong baseline.
- FE0-T05: Chưa thêm dependency; sử dụng Vue Router, Pinia, Axios, Vue Test Utils và Vitest đã có.
- FE0-T06: Không thêm dependency; sử dụng Vue Router, Pinia, Vue Test Utils và CSS hiện có.
- FE0-T07: Không thêm dependency; sử dụng Vue, Vue Test Utils, Vitest và CSS hiện có.
- FE0-T08: Không thêm dependency; sử dụng Web Crypto runtime, Vue ref, sessionStorage, Axios client và Vitest hiện có.

PLAN_CONFLICT_RESOLVED:
- Hậu kiểm phát hiện Auth Architecture V2 từng yêu cầu mọi lỗi restore `/me` đều xóa token, trong khi Screen Inventory yêu cầu 5xx cho retry.
- Quyết định owner đã được hợp nhất vào Plan V2: 401/auth-invalid xóa session; network/timeout/5xx giữ token, xóa authority khỏi memory, khóa protected content và cho retry `/me`.
- Auth endpoint/request/response vẫn theo Backend source/test thực tế; không sửa Backend contract.

AUTH_SERVICE:
- File: `FE/src/services/xac_thuc.api.js`.
- Endpoint: `POST /auth/login`, `GET /auth/me`, `POST /auth/logout` qua single Axios client.
- Login payload allow-list đúng Backend: `email`, `password`, `device_name` tùy chọn; không gửi role hoặc field thừa.
- Login body actual: `data.access_token`, `data.token_type = Bearer`, `data.expires_at`, `data.user`.
- `/me` body actual: `data` gồm `id`, `name`, `email`, `status`, `roles`; `/me` là authority cuối cho role.
- Logout là revoke current token; service không navigate, không chứa UI state và để normalized error từ Axios propagate.

AUTH_STORE:
- File: `FE/src/stores/xac_thuc.store.js`; Pinia state gồm token, nguoiDung, vaiTro, vaiTroDangDung, restore/login pending state.
- Login lưu token trước rồi bắt buộc gọi `/auth/me`; 401/auth-invalid clear token, còn network/timeout/5xx chuyển tới selector retry mà không mở protected content.
- Restore không có token thì không gọi `/me`; có token thì revalidate, không dùng cached user làm authority; lỗi tạm thời giữ token/actor trong sessionStorage nhưng xóa user/roles/active actor khỏi memory.
- Role từ `/me` thay thế role cũ; chỉ cho active actor `ADMIN`, `PT`, `RECEPTIONIST` đang có trong response.
- Không tự ưu tiên multi-role; `MEMBER` không bị biến thành Web role; Free/Premium không phải role.

SESSION_STORAGE:
- Keys ổn định: `smart_fitness.auth.token` và `smart_fitness.auth.actor`.
- Chỉ persist token và actor hợp lệ; không persist user cache, password, reset token, API secret hoặc raw login response.
- `FE/src/utils/phien_dang_nhap.js` sở hữu đọc/ghi/xóa hai auth keys; `xoaPhienDangNhap()` reset memory state và gọi cleanup helper, không xóa storage domain khác.

BEARER_INJECTION:
- `FE/src/services/api.js` giữ một Axios instance và dùng token accessor callback đọc token tại thời điểm request.
- Request có token được gắn `Authorization: Bearer <token>`; sau logout token accessor trả null nên request sau không dùng token cũ.
- Không tạo Axios instance thứ hai và không thêm `Idempotency-Key` trong FE0-T04.
- FE0-T08 giữ boundary caller-owned: không có request interceptor inject `Idempotency-Key`; API test chứng minh header caller truyền được giữ nguyên và request không truyền header vẫn không bị inject.

AUTH_401_CLEANUP:
- Axios lưu Bearer snapshot theo từng request; Auth Store chỉ cleanup khi snapshot khớp token phiên hiện tại.
- Current-session 401 chụp actor trước cleanup và điều phối bằng internal route name về login actor; late 401 của token cũ không xóa/navigate phiên mới.
- 403 chỉ trả normalized error và giữ nguyên local session; 404 không logout, không navigate.

AUTH_TESTS:
- Không gọi Backend thật; dùng Vitest mock service và custom Axios adapter.
- Đã test login/me/logout endpoint shape, token memory/sessionStorage, password không persist, restore valid/empty/invalid, logout network/5xx cleanup, Bearer động, token cũ sau logout, 401/403, stale role, active actor, multi-role và MEMBER-only.

TESTS_PASS:
- Command: `npm run test` từ `FE/`.
- Result: Vitest 4.1.11; 5 test files passed, 30 tests passed. Bao gồm smoke test FE0-T02, FE0-T03 environment/Axios/error normalization và FE0-T04 Auth service/store/session/Bearer/cleanup tests.
- FE0-T05 bổ sung 3 test files; regression hiện tại là 8 test files, 65 tests passed.
- FE0-T06 bổ sung layout integration test; regression lịch sử là 9 test files, 84 tests passed (gồm 19 test layout/shell).
- FE0-T07 bổ sung shared component/CSS audit tests; regression hiện tại là 10 test files, 115 tests passed.
- FE0-T08 bổ sung 4 test files và boundary/lifecycle test; cumulative hiện tại là 14 test files, 179 tests passed.
- FE0 remediation: `npm run test` PASS với 14 test files, 200 tests; 21 regression assertions mới bao phủ toàn bộ finding hậu kiểm.

TESTS_FAIL:
- Không có failure cuối cùng.
- Lịch sử được giữ: lần chạy đầu sau khi sửa guard có 2 test cũ fail vì vẫn kỳ vọng actor `null` được vào route actor; hai test này đã được sửa thành kỳ vọng fail-closed và full run cuối PASS 200/200.
- Lịch sử FE0-T04: một lần chạy đầu bắt 2 lỗi setup test accessor; đã sửa để test đi qua lifecycle registration đúng.

BUILD: PASS
- Command: `npm run build` từ `FE/`.
- FE0-T04 historical result: Vite 8.2.2; 26 modules transformed; build hoàn tất thành công.
- FE0-T06 current result: Vite 8.2.2; 103 modules transformed; build hoàn tất thành công.
- FE0-T07 current result: Vite 8.2.2; 105 modules transformed; build hoàn tất thành công.
- FE0-T08 current result: Vite 8.2.2; 106 modules transformed; build hoàn tất thành công.
- FE0 remediation current result: Vite 8.2.2; 109 modules transformed; production build hoàn tất thành công.

LINT:
- PASS: `npm run lint` từ `FE/`, chạy ESLint trên `src`, `vite.config.js`, `vitest.config.js` và `eslint.config.js`; không có lỗi.
- FE0 remediation: PASS, không có lỗi hoặc warning.

NAMING_SCAN:
- PASS: Source mới dùng snake_case cho `loi_api.js`, `xac_thuc.api.js`, `xac_thuc.store.js` và test tương ứng; `api.js` là service structural exception. Hàm auth/store dùng Vietnamese không dấu camelCase; không đổi legacy route/file.
- FE0-T06: Layout/component filenames dùng Vietnamese không dấu snake_case; hàm shell/event dùng camelCase không dấu; tên component Vue là framework presentation exception.
- FE0-T08: Scan toàn bộ `FE/src` không phát hiện route segment/function name tiếng Anh bị cấm; các tên Web Crypto, Axios, Vue và test fixture được review là technical/framework/test exceptions.

DOCBLOCK_SCAN:
- PASS: Các hàm quan trọng FE0-T03 và FE0-T04 đều có docblock nêu input, flow, output, side effect và business/security rule, gồm `dangNhap`, `taiThongTinNguoiDung`, `khoiPhucPhien`, `dangXuat`, `xoaPhienDangNhap`, actor selection và Bearer/401 callback.
- FE0-T06: Các hàm mở/đóng drawer, route-change cleanup và logout shell có docblock về input, flow, output, side effect, auth boundary và UX.
- FE0-T07: Các hàm presentation quan trọng của status badge, error retry, filter, pagination, dialog và notification mapping có docblock về input, flow, output, side effect và boundary không gọi API.
- FE0-T08: Helper UUID, composable lifecycle, session storage primitives/public API và safe redirect đều có docblock nêu input, process, result, side effect và business/security rule; manual review không thấy hàm quan trọng thiếu mô tả.

SECRET_SCAN:
- PASS: Không có token/password/Authorization logging; password chỉ đi qua request allow-list và không vào Store/sessionStorage. Không dùng localStorage, không có secret trong source/config.
- FE0-T06: Topbar chỉ nhận allow-list name/email/actor label; không render token, expiry, Authorization, role history hoặc business-sensitive placeholder.
- FE0-T07: Shared components không import API/store, không dùng `v-html`, không render raw Axios error và không chứa secret; message chỉ render qua interpolation.
- FE0-T08: Scan production source không có `localStorage`, `v-html`, console sensitive log, raw redirect navigation, hard-coded secret hoặc weak UUID fallback; test-only storage/attack fixtures không đi vào bundle production.

BASELINE_GIT:
- `git status --short`: chỉ có `?? docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md`.
- `git diff --stat`: không có tracked diff.
- `git diff --check`: không có lỗi.
- Không add, commit, push, reset, clean hoặc restore.

CURRENT_GIT_AFTER_FE0_T02:
- `git status --short`: `M FE/package.json`, `M FE/package-lock.json`, cùng các file mới `FE/eslint.config.js`, `FE/vitest.config.js`, `FE/src/app.test.js`, `docs/VUE_WEB_COMPLETION_CHECKPOINT.md` và plan untracked hiện hữu `docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md`.
- `git diff --check`: PASS; không có whitespace error.
- Không sửa `BE/`, `Mobile/` hoặc `Database/`; không add, commit, push, reset, clean hoặc restore.

CURRENT_GIT_AFTER_FE0_T03:
- `git status --short`: các thay đổi FE0-T02 được giữ nguyên; thêm `M FE/.env.example`, `M FE/src/services/api.js`, `FE/src/services/api.test.js`, `FE/src/utils/loi_api.js`, `FE/src/utils/loi_api.test.js` và checkpoint này; không thay đổi Backend/Mobile/Database.
- `git diff --check`: PASS; không có whitespace error.
- Không add, commit, push, reset, clean hoặc restore.

CURRENT_GIT_AFTER_FE0_T04:
- `git status --short`: giữ nguyên toàn bộ thay đổi FE0-T01/T02/T03; thêm `FE/src/services/xac_thuc.api.js`, `FE/src/services/xac_thuc.api.test.js`, `FE/src/stores/xac_thuc.store.js`, `FE/src/stores/xac_thuc.store.test.js` và cập nhật `FE/src/services/api.js` cùng checkpoint này.
- `git diff --stat`: tracked changes chỉ trong FE/package/config/api và docs checkpoint; file mới được ghi nhận ở `FILES_CREATED`.
- `git diff --check`: PASS; không có whitespace error.
- Không sửa `BE/`, `Mobile/` hoặc `Database/`; không add, commit, push, reset, clean hoặc restore.

FRONTEND_RUNTIME:
- Node: v22.20.0; đáp ứng engine `^22.13.0 || >=24.3.0`.
- npm: 10.9.3.
- node_modules: đã tồn tại; không chạy `npm ci` và không thêm dependency.

FRONTEND_DEPENDENCIES:
- Vue 3.5.42
- Vite 8.2.2
- Vue Router 5.3.0
- Pinia 4.0.3
- Axios 1.20.0
- @vitejs/plugin-vue 6.0.8
- @vue/test-utils 2.4.6
- Vitest 4.1.11
- jsdom 29.0.1
- ESLint 10.9.1
- eslint-plugin-vue 10.10.0
- package-lock.json: lockfileVersion 3; package root khớp package.json.

CURRENT_SOURCE_AUDIT:
- Reuse: `src/App.vue`, `src/main.js`, alias trong `vite.config.js`, Axios instance trong `src/services/api.js`, `src/assets/main.css` reset nền và `index.html`.
- FE0-T03: Environment ownership nằm trong `src/services/api.js`; Axios client dùng một instance duy nhất; error normalization tập trung tại `src/utils/loi_api.js`.
- FE0-T04: Auth API nằm trong `src/services/xac_thuc.api.js`; Auth state/session cleanup nằm trong `src/stores/xac_thuc.store.js`; Bearer/401 nối vào Axios bằng callback, không import Router.
- FE0-T05: router foundation, restore-aware guard, public auth route/page ownership và safe internal dispatch đã hoàn tất; không có actor home/business page giả.
- FE0-T06: `App.vue` chọn layout duy nhất qua `route.meta.boCuc`; public/error layouts không có actor shell; Admin/PT/Lễ tân dùng `khung_ung_dung.vue` với nav rỗng, topbar safe identity và drawer responsive.
- LEGACY_PLACEHOLDER: `src/layouts/BoCucChinh.vue`, `src/pages/TrangKhoiTao.vue`, route name `khoi-tao`.
- LEGACY_PLACEHOLDER_HANDLING: Các placeholder cũ không bị xóa và không còn được tham chiếu bởi router T05; không sửa/xóa evidence legacy ngoài scope.
- REMOVE_LATER: Không xác định file nào được phép xóa ở FE0-T01.
- UNKNOWN: Không có.

ENVIRONMENT_HANDLING:
- `VITE_API_BASE_URL` chỉ được đọc tại `src/services/api.js` qua `layCauHinhMoiTruong`; không có duplicate ownership.
- `development`/`test` cho phép fallback có kiểm soát tới `http://127.0.0.1:8000/api`; `production`/`staging` bắt buộc URL hợp lệ và reject localhost, protocol không hợp lệ hoặc credential trong URL.
- `FE/.env.example` được đánh dấu chỉ dành cho development; không có `.env` tracked/hiện hữu, không phát hiện secret hoặc log secret.

TEST_LINT_REALTIME_STATUS:
- Test: Vitest + Vue Test Utils + jsdom đã chạy 5 files/30 tests PASS; script deterministic `vitest run`.
- FE0-T05 historical regression: 8 files/65 tests PASS.
- FE0-T06 historical regression: 9 files/84 tests PASS; layout/shell test riêng 19/19 PASS.
- FE0-T07 current regression: 10 files/115 tests PASS; shared component test riêng 31/31 PASS.
- FE0-T08 targeted regression: 5 files/70 tests PASS; full current regression: 14 files/179 tests PASS.
- Lint: ESLint flat config + eslint-plugin-vue đã chạy PASS trên source/config.
- FE0-T06 lint: `npm run lint` PASS, không warning; build PASS với 103 modules transformed.
- FE0-T07 lint: `npm run lint` PASS, không lỗi và không warning.
- Realtime: chưa có Laravel Echo, Pusher-compatible client hoặc WebSocket source.

SCOPE_LOCK_CHECK:
- FE0-T05 đã hoàn tất riêng router/guard/public auth pages và tests; evidence lịch sử giữ nguyên, không làm lại T05.
- FE0-T06 chỉ triển khai layout/shell baseline, responsive drawer, route meta mapping và layout tests; không triển khai Visual UI System đầy đủ, shared query/form/toast/confirm hoặc business feature.
- FE0-T07 chỉ triển khai shared presentation components, CSS tokens/Visual UI System và refactor visual cho Foundation auth pages; không triển khai business page, API/store, route mới hoặc entitlement logic.
- FE0-T08 chỉ triển khai idempotency UUID/composable, session helper + Store refactor, safe redirect helper, test boundary và scan; không thêm endpoint, business UI, route mới hoặc global Idempotency-Key interceptor.
- FE0-T03 không thêm auth/token/session/idempotency, không navigate/logout/retry tự động, không tạo business UI.
- FE0-T04 chỉ triển khai Auth service/store/session lifecycle/Bearer/401 cleanup; không tạo router, page, role navigation, idempotency helper hoặc business UI.
- Không sửa Backend, Mobile hoặc Database; không thêm dependency ngoài danh sách mục 6A.

HISTORICAL_EXACT_NEXT_ACTION_AFTER_FE0_T08: FE0-T09 — Full FE-0 gate và checkpoint; không bắt đầu trong FE0-T08.

FE0-T05_EVIDENCE:
- ENTRY_GATE: PASS; trước khi bắt đầu đã có CURRENT_PHASE=FE-0, CURRENT_TASK=FE0-T04, STATUS=PASS, completed T01-T04 và EXACT_NEXT_ACTION=FE0-T05. Không yêu cầu CURRENT_TASK=T05 trước khi start.
- TASK_START_TRANSITION: Đã cập nhật CURRENT_TASK=FE0-T05, STATUS=IN_PROGRESS và giữ completed T01-T04 trước khi sửa source T05.
- ROUTER_FOUNDATION: `FE/src/router/index.js` dùng Vue Router history, route names/paths đúng owner, meta chỉ dùng `congKhai`, `yeuCauXacThuc`, `vaiTro`, `tinhNang`.
- ROUTER_GUARD: `FE/src/router/bao_ve_tuyen_duong.js` restore một lần trước protected decision; unauth protected về chooser, wrong role về 403, stale actor về chooser; guard không logout/không retry.
- ROOT_DISPATCH: root không render business page; multi-role không priority, exactly-one chỉ về home nếu mapping đã đăng ký (hiện chưa có), còn lại về chooser; unauth/MEMBER-only không invent destination.
- SELECTOR: chỉ lọc ADMIN/PT/RECEPTIONIST từ Auth Store; MEMBER không phải Web actor; chonVaiTroDangDung là action duy nhất để lưu actor context; không API mutation/không auto-priority.
- ACTOR_LOGIN: Admin/PT/Lễ tân dùng shared `bieu_mau_dang_nhap.vue`; login chỉ gửi email/password, role matching một role có thể chọn, multi-role/mismatch/member-only về chooser; không gửi role cho Backend.
- FORGOT_PASSWORD: gọi `POST /auth/forgot-password` với `{email}`; success luôn dùng thông báo generic enumeration-safe, không lưu credential và không retry.
- RESET_PASSWORD: source Backend thực tế yêu cầu `{token,password,password_confirmation}`; UI chỉ chấp nhận query token lowercase hex 64 ký tự, không lưu/log token, success clear password và replace về neutral auth entry.
- ERROR_SCREENS: 403 không logout; 404/catch-all generic, không lộ path detail và không loop.
- AUTH_ERROR_UI: 401/422/429/5xx/network hiển thị từ normalized error của T03; không raw error/config/token/trace.
- TESTS: router routes/catch-all, restore race, auth gate/role/stale actor/403/no logout/root priority/member-only và actor/forgot/reset UI/error flows đã được test; tổng 65 tests PASS.
- STATIC_AUDIT: `git diff --check` PASS; không có localStorage, raw redirect query, console logging, Idempotency-Key hoặc secret trong implementation T05. Baseline docblock T04 vẫn nhắc FREE/Premium như giá trị bị cấm, không phải role được sử dụng.
- SCOPE_AUDIT: không thay đổi `BE/`, `Mobile/` hoặc `Database/`; không thêm dependency; không commit/push.
- TASK_END_TRANSITION: CURRENT_TASK=FE0-T05, STATUS=PASS, COMPLETED_TASKS đã gồm T01-T05 và EXACT_NEXT_ACTION=FE0-T06.

FE0-T06_EVIDENCE:
- ENTRY_GATE: PASS; trước khi bắt đầu có CURRENT_PHASE=FE-0, CURRENT_TASK=FE0-T05, STATUS=PASS, completed T01-T05 và EXACT_NEXT_ACTION=FE0-T06. Không yêu cầu CURRENT_TASK=T06 trước khi start.
- TASK_START_TRANSITION: Đã cập nhật CURRENT_TASK=FE0-T06, STATUS=IN_PROGRESS và giữ nguyên completed T01-T05 trước khi sửa source T06.
- LAYOUT_ARCHITECTURE: `FE/src/App.vue` là cơ chế RouterView -> layout động -> page; mapping duy nhất dùng `route.meta.boCuc`. Layout không chứa auth/business API logic.
- ROUTE_LAYOUT_MAPPING: Auth login, forgot/reset và selector dùng `cong_khai`; 403/404/catch-all dùng `loi`; root không có business layout và catch-all vẫn redirect nội bộ tới 404.
- PUBLIC_LAYOUT: `bo_cuc_cong_khai.vue` có main/card responsive, không sidebar/business menu, không account/token context.
- ERROR_LAYOUT: `bo_cuc_loi.vue` có main/card và chỉ chứa nội dung lỗi/action trung tính; không auto logout, API hoặc actor navigation.
- SHARED_APP_SHELL: `khung_ung_dung.vue` dùng header/nav/main, topbar, sidebar/drawer, overlay và slot; Admin/PT/Lễ tân chỉ truyền actor label + nav rỗng, không fake route/menu.
- ACTOR_SHELL: `bo_cuc_admin.vue`, `bo_cuc_pt.vue`, `bo_cuc_le_tan.vue` dùng shell chung; label chỉ hiển thị khi active actor còn khớp roles đã revalidate trong Auth Store.
- SIDEBAR_RESPONSIVE: `thanh_ben_dieu_huong.vue` chỉ render mục layout truyền vào và route resolve hợp lệ; empty nav ổn định, native link/button keyboard-accessible, drawer đóng khi chọn/route/logout/desktop resize.
- TOPBAR: `thanh_tren.vue` hiển thị actor label, name/email an toàn, logout và nút mobile có `aria-expanded`/`aria-controls`; không đọc/render token, expiry, role history hoặc business data.
- LOGOUT_SHELL_INTEGRATION: topbar phát event; shell gọi `store.dangXuat()` duy nhất, luôn đóng drawer và `router.replace({ name: 'chonVaiTro' })`; local cleanup/remote-failure behavior thuộc T04 Store.
- RESTORE_AND_ROLE_LOSS: restore pending chỉ hiện placeholder; slot/sidebar/topbar bị ẩn trước restore hoặc khi active actor mất khỏi Store, không giữ identity/menu stale sau 401 cleanup.
- RESPONSIVE_BASELINE: CSS variables gồm sidebar 256px, topbar 60px, content max 1440px, padding 24/16px; media audit cho `<1024` drawer, `<640` narrow layout; chống overflow và ellipsis identity dài. Đây là baseline layout, chưa phải Visual UI System T07.
- LAYOUT_TESTS: `FE/src/layouts/bo_cuc.test.js` kiểm tra 9 route layout, public/error isolation, 3 actor labels/safe identity/empty nav, restore pending, drawer ARIA/route close, logout lifecycle, 401 stale cleanup và CSS static audit.
- TESTS: `npm run test` từ `FE/` PASS — 9 test files, 84 tests; targeted T06 PASS — 19/19.
- LINT: `npm run lint` từ `FE/` PASS — không lỗi, không warning.
- BUILD: `npm run build` từ `FE/` PASS — Vite 8.2.2, 103 modules transformed.
- DIFF_CHECK: `git diff --check` PASS.
- NAMING_SCAN: filenames T06 dùng Vietnamese không dấu snake_case; functions/event handlers dùng Vietnamese không dấu camelCase; component symbols là Vue framework presentation exception.
- DOCBLOCK_SCAN: shell open/close/navigation cleanup/logout functions có docblock meaningful về input, flow, output, side effect, auth boundary và UX.
- SECRET_SCAN: static source scan không có secret; không localStorage, không log token/password/Authorization; T06 topbar chỉ allow-list name/email/actor label.
- LEGACY: `src/layouts/BoCucChinh.vue`, `src/pages/TrangKhoiTao.vue` và route legacy không được dùng/xóa; giữ nguyên vì ngoài trách nhiệm T06.
- SCOPE_AUDIT: chỉ sửa FE layout/App/router CSS/test và checkpoint; không sửa BE/Mobile/Database, không thêm dependency, không tạo business route/page/API, không bắt đầu T07.
- FILES_CREATED: 5 layout/component files + 1 layout test file được ghi ở FILES_CREATED phía trên.
- FILES_MODIFIED: `FE/src/App.vue`, `FE/src/assets/main.css`, `FE/src/router/index.js`, checkpoint; thay đổi T01-T05 khác được giữ nguyên.
- CHECKPOINT_UPDATED: CURRENT_PHASE=FE-0, CURRENT_TASK=FE0-T06, STATUS=PASS; COMPLETED_TASKS gồm T01-T06.
- TASK_END_TRANSITION: T06 acceptance criteria PASS; không đánh dấu FE-0 hoàn tất.

FE0-T07_EVIDENCE:
- ENTRY_GATE: PASS; trước khi bắt đầu có CURRENT_PHASE=FE-0, CURRENT_TASK=FE0-T06, STATUS=PASS, completed T01-T06 và EXACT_NEXT_ACTION=FE0-T07. Không yêu cầu CURRENT_TASK=T07 trước khi start.
- TASK_START_TRANSITION: Đã cập nhật CURRENT_TASK=FE0-T07, STATUS=IN_PROGRESS và giữ nguyên completed T01-T06 trước khi sửa source T07.
- VISUAL_UI_SYSTEM: PASS; triển khai hệ presentation trung tính, nền sáng, typography system stack, không dark mode, gradient, glassmorphism, UI framework hoặc animation phức tạp.
- CSS_TOKENS: PASS; `FE/src/assets/main.css` tập trung token màu, typography, spacing, radius, shadow, layout, form control, z-index và motion; component/page CSS không tạo token rời.
- TYPOGRAPHY: PASS; body dùng `Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif`; token cỡ chữ gồm body 16px, helper 14px, meta 12px và page title 28px; không có `@import` font.
- COLOR_TOKENS: PASS; giữ baseline V2 gồm `#ffffff`, `#f8fafc`, `#e2e8f0`, `#0f172a`, `#64748b`, `#2563eb`, `#1d4ed8`, `#15803d`, `#b45309`, `#b91c1c`, `#0369a1`; status dùng text + semantic class, không phụ thuộc màu đơn độc.
- SPACING_TOKENS: PASS; token 4/8/12/16/24/32px; radius 6/10/12px; shadow nhẹ tập trung tại `--mau-bong-nhe`.
- FORM_BASELINE: PASS; input/select/textarea tối thiểu 44px, border/radius/focus token hóa, visible label, native required giữ nguyên và form pending có `aria-busy`.
- TABLE_BASELINE: PASS; bảng semantic có caption/header scope, target row tối thiểu 44px, wrapper `overflow-x: auto` và không có sorting/API ngầm.
- PAGE_HEADER_COMPONENT: PASS; `TieuDeTrang` render semantic `header`/`h1`, mô tả tùy chọn và named slot `hanhDong`.
- LOADING_COMPONENT: PASS; `TrangThaiTaiDuLieu` có role status, aria-live polite, aria-busy true và visible loading text.
- EMPTY_COMPONENT: PASS; `TrangThaiTrong` có title/description và action slot tùy chọn, không chứa query/API logic.
- ERROR_COMPONENT: PASS; `TrangThaiLoi` hiển thị thông báo an toàn, role alert, retry chỉ phát event `thuLai`, chặn duplicate khi pending.
- STATUS_BADGE_COMPONENT: PASS; `HuyHieuTrangThai` map status generic về semantic class, unknown fallback trung tính và luôn hiển thị nhãn text.
- FORM_FIELD_COMPONENT: PASS; `TruongBieuMau` liên kết label/control, map `aria-describedby`/`aria-invalid`, visible required marker, help/error text.
- FILTER_COMPONENT: PASS; `BoLocDanhSach` sở hữu form presentation, phát `apDung`/`datLai`, chặn submit/reset lặp khi pending, không mutate URL/API.
- TABLE_COMPONENT: PASS; `BangDuLieu` hỗ trợ loading/empty/error, abstraction cột/hàng và slot `tieuDeCot`/`hang`, không biết query hoặc authorization.
- PAGINATION_COMPONENT: PASS; `ThanhPhanTrang` clamp page, disable boundary/pending, phát `chuyenTrang` đúng số trang, không tự tạo request hoặc URL.
- CONFIRM_DIALOG_COMPONENT: PASS; `HopThoaiXacNhan` có dialog semantics, focus cơ bản, Escape/cancel/confirm events, max-width responsive và chặn confirm duplicate khi pending.
- NOTIFICATION_COMPONENT: PASS; `VungThongBao` hỗ trợ success/error/info/warning, role status/alert, aria-live polite và interpolation an toàn.
- ACCESSIBILITY_BASELINE: PASS; focus-visible, native keyboard controls, visible labels, ARIA semantics, aria-busy pending, semantic table/dialog và reduced-motion baseline được kiểm tra.
- FOUNDATION_VISUAL_REFACTOR: PASS; login, forgot-password, reset-password dùng shared field/notification/button presentation; selector dùng shared button classes; payload, Auth Store, API contract và route logic không đổi.
- COMPONENT_TESTS: PASS; thêm 31 test T07 bao phủ 11 shared components, CSS tokens, responsive/accessibility static audit và dependency/XSS boundary audit.
- CUMULATIVE_TEST_SUMMARY: FE0-T04 historical 30 tests; FE0-T05 historical cumulative 65; FE0-T06 historical cumulative 84; FE0-T07 current cumulative 115 tests.
- FILES_CREATED: 11 shared Vue components và `FE/src/components/dung_chung/dung_chung.test.js`; danh sách đầy đủ đã được thêm vào FILES_CREATED phía trên.
- FILES_MODIFIED: `FE/src/assets/main.css`, `FE/src/components/xac_thuc/bieu_mau_dang_nhap.vue`, forgot/reset/selector pages và checkpoint; không sửa API/store/router business behavior.
- TESTS_PASS: `npm run test` từ `FE/` PASS — 10 test files, 115 tests.
- LINT: `npm run lint` từ `FE/` PASS — không lỗi, không warning.
- BUILD: `npm run build` từ `FE/` PASS — Vite 8.2.2, 105 modules transformed.
- NAMING_SCAN: PASS; shared Vue filenames dùng Vietnamese không dấu snake_case; handler/helper dùng Vietnamese không dấu camelCase; component symbols là Vue framework presentation exception.
- DOCBLOCK_SCAN: PASS; helper/handler quan trọng trong shared components có docblock nêu input, flow, output, side effect và API/business boundary.
- SECRET_SCAN: PASS; không có secret, token/password logging, `v-html`, raw Axios error, API/store import hoặc localStorage mới trong T07.
- RESPONSIVE_AUDIT: PASS; giữ sidebar 256px, topbar 60px, content max 1440px, padding 24/16px; breakpoint 1023/639px, mobile table scroll, dialog max-width viewport, focus/reduced-motion audit.
- DIFF_CHECK: PASS; `git diff --check` không có whitespace error.
- SCOPE_AUDIT: PASS; chỉ thay đổi FE shared UI/Foundation presentation/CSS/test và checkpoint; không tạo business route/page, không bắt đầu FE0-T08.
- BACKEND_MOBILE_DATABASE: PASS; không có status/diff trong `BE/`, `Mobile/` hoặc `Database/`.
- COMMIT_PUSH: Không commit, không push theo yêu cầu.
- TASK_END_TRANSITION: CURRENT_PHASE=FE-0, CURRENT_TASK=FE0-T07, STATUS=PASS; COMPLETED_TASKS gồm T01-T07 và EXACT_NEXT_ACTION chuyển sang FE0-T08.

CURRENT_GIT_AFTER_FE0-T07:
- `git status --short`: giữ nguyên toàn bộ thay đổi FE0-T01/T02/T03/T04/T05/T06; thêm 11 shared components, shared component tests, Foundation visual refactor, CSS token system và checkpoint T07.
- `git diff --check`: PASS; không có whitespace error.
- Không sửa `BE/`, `Mobile/` hoặc `Database/`; không add, commit, push, reset, clean hoặc restore.

HISTORICAL_EXACT_NEXT_ACTION_AFTER_FE0_T07: FE0-T08 — Idempotency, safe redirect, session helpers, naming/docblock scan.

CURRENT_GIT_AFTER_FE0_T08:
- `git status --short`: giữ nguyên toàn bộ thay đổi FE0-T01/T02/T03/T04/T05/T06/T07; thêm helper idempotency, composable stable key, session helper, safe redirect tests và refactor Auth Store/API test boundary.
- `git diff --check`: PASS; không có whitespace error.
- Chỉ có thay đổi trong `FE/` và checkpoint; không sửa `BE/`, `Mobile/` hoặc `Database/`.
- Không add, commit, push, reset, clean hoặc restore.

FE0-T08_EVIDENCE:
- ENTRY_GATE: PASS; trước khi bắt đầu có CURRENT_PHASE=FE-0, CURRENT_TASK=FE0-T07, STATUS=PASS, completed T01-T07 và EXACT_NEXT_ACTION bắt đầu bằng FE0-T08. Không yêu cầu CURRENT_TASK=T08 trước khi start.
- TASK_START_TRANSITION: Đã cập nhật CURRENT_TASK=FE0-T08, STATUS=IN_PROGRESS và giữ nguyên completed T01-T07 trước khi sửa source.
- IDEMPOTENCY_UTILITY: PASS; `FE/src/utils/khoa_idempotency.js` tạo key bằng `globalThis.crypto.randomUUID()`, fail có kiểm soát khi API thiếu/kết quả rỗng, không dùng Math.random/Date.now/fallback và không gọi Backend.
- UUID_GENERATION: PASS; test mock chứng minh caller dùng đúng `crypto.randomUUID`; không có weak random source trong implementation.
- STABLE_ACTION_KEY: PASS; `suDungThaoTacChongLap()` tạo một key cho một logical action, trả lại cùng key qua retry và expose lifecycle `batDauThaoTac`, `layKhoaHienTai`, `ketThucThaoTac`, `huyThaoTac`.
- IDEMPOTENCY_LIFECYCLE_TESTS: PASS; lifecycle tests bao phủ first start, repeated start, getter không tạo key, network/timeout retry giữ key, terminal/cancel clear, action mới tạo key mới, generator failure và instance isolation.
- SESSION_HELPER: PASS; `phien_dang_nhap.js` sở hữu đúng hai auth keys, normalize string, lưu token/actor primitive, clear riêng từng key, preserve unrelated session key, không clear toàn tab và báo lỗi storage an toàn không chứa raw secret.
- SESSION_STORE_REFACTOR: PASS; Auth Store dùng helper cho login, restore, actor selection, revoked actor và full cleanup; re-export `KHOA_TOKEN_PHIEN`/`KHOA_ACTOR_PHIEN` để giữ tương thích test/API hiện hữu; login/me/logout/401/multi-role/MEMBER semantics không đổi.
- SESSION_OWNERSHIP: PASS; helper không đọc/ghi localStorage, password, reset token, user DTO hoặc business cache; không thêm dependency.
- SAFE_REDIRECT: PASS; `laDuongDanNoiBoHopLe()` chỉ nhận named route exact trong explicit allow-list, reject input không hợp lệ, URL/scheme/path/query/fragment/percent/backslash trick và optional router existence check fail-closed.
- REDIRECT_ALLOW_LIST: PASS; allow-list route name explicit, không lấy toàn bộ runtime route list làm authority, không thêm fake production route và không tích hợp raw redirect query.
- OPEN_REDIRECT_TESTS: PASS; test http/https, protocol-relative, slash/backslash, javascript/data/mailto/ftp, blank/null/undefined/array/object, unknown route, encoded suspicious value, router mismatch/throw và allow-listed fixture.
- AXIOS_HEADER_BOUNDARY: PASS; request interceptor hiện hữu chỉ xử lý Bearer; caller-provided `Idempotency-Key` được giữ nguyên và request không truyền key không bị global inject.
- NAMING_SCAN: PASS; automated candidate scan toàn bộ `FE/src` không phát hiện route segment/function name bị cấm hoặc tên file T08 sai quy ước.
- NAMING_MANUAL_REVIEW: PASS; tên mới là Vietnamese không dấu snake_case/camelCase; `randomUUID`, `sessionStorage`, Axios/Vue symbols và test API là technical/framework exceptions.
- NAMING_MANUAL_EXCEPTIONS: `src/layouts/BoCucChinh.vue`, `src/pages/TrangKhoiTao.vue` và route `khoi-tao` là LEGACY_EXCEPTION đã giữ nguyên; không rename/delete.
- DOCBLOCK_SCAN: PASS; tất cả helper lifecycle/storage/redirect quan trọng có docblock tiếng Việt không dấu.
- DOCBLOCK_MANUAL_REVIEW: PASS; docblock nêu purpose, input, process, result, side effect, stable-key/timeout retry hoặc security rule phù hợp; không thấy hàm T08 quan trọng thiếu mô tả.
- SECRET_SCAN: PASS; production source không có hard-coded secret, weak UUID fallback, sensitive console log, raw Authorization/token log hoặc credential persistence mới.
- SECURITY_SCAN: PASS; production source không có localStorage, v-html, raw redirect navigation, sessionStorage toàn bộ clear hoặc global Idempotency-Key injection; error message không chứa token/password.
- EXCEPTION_REVIEW: PASS; localStorage và password/Authorization values chỉ nằm trong test fixtures/legacy regression; `getRoutes` chỉ nằm trong test chứng minh không được sử dụng; không đi vào production bundle.
- TEST_COUNT_GUARD: PASS; baseline trước T08 là 10 test files/115 tests; sau T08 là 14 test files/179 tests, không giảm coverage/count.
- CUMULATIVE_TEST_SUMMARY: FE0-T04 historical 30; FE0-T05 cumulative 65; FE0-T06 cumulative 84; FE0-T07 cumulative 115; FE0-T08 current cumulative 179 tests.
- FILES_CREATED: 8 T08 files — idempotency utility/test, stable-key composable/test, session helper/test và safe redirect utility/test; đã ghi vào FILES_CREATED phía trên.
- FILES_MODIFIED: `FE/src/stores/xac_thuc.store.js`, `FE/src/services/api.test.js` và checkpoint; không sửa Backend/Mobile/Database.
- TESTS_PASS: `npm run test` từ `FE/` PASS — Vitest 4.1.11, 14 test files, 179 tests; targeted T08 PASS — 5 files, 70 tests.
- LINT: `npm run lint` từ `FE/` PASS — ESLint không lỗi, không warning.
- BUILD: `npm run build` từ `FE/` PASS — Vite 8.2.2, 106 modules transformed.
- DIFF_CHECK: `git diff --check` PASS.
- PLAN_SOURCE_MISMATCH: Không có mismatch; triển khai đúng T08 plan/checkpoint, không bắt đầu T09.
- BACKEND_MOBILE_DATABASE: PASS; không có status/diff thay đổi trong `BE/`, `Mobile/` hoặc `Database/`.
- DEPENDENCIES: Không thêm package/dependency trong T08.
- COMMIT_PUSH: Không commit, không push theo yêu cầu.
- TASK_END_TRANSITION: CURRENT_PHASE=FE-0, CURRENT_TASK=FE0-T08, STATUS=PASS; COMPLETED_TASKS đã gồm T01-T08; không đánh dấu FE-0 hoàn tất.

HISTORICAL_EXACT_NEXT_ACTION_BEFORE_FE0_T09: FE0-T09 — Full FE-0 gate và checkpoint; không bắt đầu trong FE0-T08.

CURRENT_GIT_AFTER_FE0_T09:
- `git status --short`: các thay đổi FE0-T01 đến FE0-T08 được giữ nguyên; checkpoint được cập nhật cho FE0-T09; plan V2 vẫn là file untracked đã tồn tại từ trước; không có thay đổi trong `BE/`, `Mobile/` hoặc `Database/`.
- `git diff --check`: PASS; không có whitespace error trong tracked diff.
- Không tạo file source mới, không sửa defect source, không thêm dependency, không commit, không push, không reset, không clean hoặc restore.

FE0_FINAL_ACCEPTANCE:
- T01_BASELINE: PASS — runtime/tree/legacy baseline và scope được audit; không có business route/page/API của các phase sau.
- T02_TEST_LINT: PASS — Vitest, Vue Test Utils, jsdom, ESLint và eslint-plugin-vue được cấu hình bằng script thực tế.
- T03_API_CLIENT: PASS — một Axios instance; env ownership tập trung; production/staging không fallback localhost; normalized error và status coverage hoạt động.
- T04_AUTH: PASS — login/me/logout, restore, sessionStorage, Bearer, 401 cleanup, 403 không logout, multi-role/MEMBER/password/reset security khớp contract.
- T05_ROUTER_AUTH_PAGES: PASS — 10 route record Foundation, guard restore-aware, role isolation, catch-all và auth pages đúng scope.
- T06_LAYOUT: PASS — App mapping đúng một layout; đủ public/error/Admin/PT/Lễ tân; shell dùng nav rỗng, không fake business navigation.
- T07_VISUAL_SHARED_UI: PASS — CSS tokens, nền sáng, 11 shared components, Foundation refactor và accessibility baseline đạt yêu cầu.
- T08_IDEMPOTENCY_SECURITY_HELPERS: PASS — UUID stable-key lifecycle, session helper, safe named-route redirect và caller-owned Idempotency-Key boundary đạt yêu cầu.
- AUTH_GATE: PASS — Auth authority vẫn ở Backend; FE chỉ revalidate và UX-gate.
- ROUTER_GATE: PASS — không có raw redirect, không auto-priority role, MEMBER không được biến thành Web actor.
- SECURITY_GATE: PASS — không có localStorage, v-html, toàn bộ sessionStorage clear, sensitive console log, hard-coded secret hoặc weak random trong production source.
- NAMING_GATE: PASS — automated scan không có prohibited route/function name; tên mới đúng quy ước, ngoại lệ framework/legacy được ghi nhận.
- DOCBLOCK_GATE: PASS — helper/service/store/guard/composable và handler quan trọng có docblock meaningful.
- A11Y_BASELINE_GATE: PASS — semantic form/table/dialog/status, visible label, focus-visible, keyboard/ARIA/reduced-motion được audit tĩnh; không tuyên bố pixel-perfect/browser visual QA.
- TEST_GATE: PASS — 14 test files, 179 tests; không có skip/todo test.
- LINT_GATE: PASS — `npm run lint` từ `FE/`, không lỗi và không warning.
- BUILD_GATE: PASS — `npm run build` từ `FE/`, Vite 8.2.2, 106 modules transformed.
- DIFF_GATE: PASS — `git diff --check`.
- DEPENDENCY_GATE: PASS — npm tree khớp package/lock, chỉ dùng dependency trong danh sách FE-0; không thêm dependency trong T09.
- SCOPE_GATE: PASS — chỉ audit và cập nhật checkpoint; không sửa Backend/Mobile/Database, không bắt đầu FE1 và không commit/push.

FE0_T09_EVIDENCE:
- ENTRY_GATE: PASS — trước khi bắt đầu có `CURRENT_PHASE=FE-0`, `CURRENT_TASK=FE0-T08`, `STATUS=PASS`, completed T01-T08 và `EXACT_NEXT_ACTION` bắt đầu bằng FE0-T09; đã chuyển task start thành `CURRENT_TASK=FE0-T09`, `STATUS=IN_PROGRESS` trước audit.
- SOURCE_AUDIT: PASS — audit toàn bộ inventory `FE/src`: 59 files gồm 45 production files và 14 test files; đã kiểm tra App/main/assets/router/services/stores/utils/composables/layouts/components/pages cùng package/config/env.
- ROUTE_INVENTORY: PASS — 10 route records Foundation: `/`, `/chon-vai-tro`, `/quen-mat-khau`, `/dat-lai-mat-khau`, `/khong-co-quyen`, `/khong-tim-thay`, `/admin/dang-nhap`, `/pt/dang-nhap`, `/le-tan/dang-nhap` và catch-all; không có route business FE1+.
- DOMAIN_ROUTE_SCOPE_AUDIT: PASS — production FE chỉ có Auth/Foundation endpoints `/auth/login`, `/auth/me`, `/auth/logout`, `/auth/forgot-password`, `/auth/reset-password`; không có Admin dashboard/accounts, PT chat hoặc business API.
- TEST_INVENTORY: PASS — 14 test files; scan không có `skip`, `todo`, `TODO` hoặc `FIXME` trong test inventory.
- FULL_TEST: PASS — `npm run test` từ `FE/`: Vitest 4.1.11, 14 test files, 179 tests.
- LINT: PASS — `npm run lint` từ `FE/`: ESLint không lỗi, không warning.
- BUILD: PASS — `npm run build` từ `FE/`: Vite 8.2.2, 106 modules transformed.
- DIFF_CHECK: PASS — `git diff --check`.
- NAMING: PASS — filename/function/route candidate scan không có violation; `App.vue`, `main.js`, Axios/Vue symbols và legacy placeholders là exception đã ghi nhận.
- DOCBLOCK: PASS — manual/static review không thấy helper hoặc function quan trọng của FE-0 thiếu mô tả purpose/input/process/result/side effect/security boundary.
- SECURITY: PASS — production scan không có secret, weak UUID fallback, sensitive log, credential persistence, raw redirect, XSS sink hoặc global idempotency injection; test fixtures không được tính là production behavior.
- DEPENDENCY: PASS — `npm ls --depth=0` thành công; Vue 3.5.42, Vite 8.2.2, Vue Router 5.3.0, Pinia 4.0.3, Axios 1.20.0, Vitest 4.1.11, jsdom 29.0.1 và ESLint stack đúng lockfile.
- A11Y_VISUAL: PASS — CSS/shared component static audit đạt baseline responsive tại 1023/639px, semantic/ARIA/focus/reduced-motion; không chạy browser nên không claim pixel-perfect.
- LEGACY: PASS — `src/layouts/BoCucChinh.vue`, `src/pages/TrangKhoiTao.vue` và route name `khoi-tao` chỉ là legacy documentation/inventory exception, không được router T05 tham chiếu và không bị xóa.
- PLAN_CONSISTENCY: PASS — đọc và đối chiếu toàn bộ `docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md`; V2 không tham chiếu nhầm V1; FE0-T09 là full gate đúng task plan.
- CHECKPOINT_CONSISTENCY: PASS — current task/status/completed tasks/FE0 phase status/next action và evidence phản ánh đúng trạng thái; không sửa lịch sử T01-T08.
- SCOPE: PASS — không có status/diff trong `BE/`, `Mobile/`, `Database/`; không bắt đầu FE1-T01; không commit/push.
- FINAL_VERDICT: PASS — toàn bộ acceptance gate của FE-0 PASS; chỉ xác nhận Foundation PASS, không xác nhận Web hoàn tất toàn hệ thống.

CHECKPOINT_UPDATED: PASS — `CURRENT_PHASE=FE-0`, `CURRENT_TASK=FE0-T09`, `STATUS=PASS`, completed T01-T09, `FE0_PHASE_STATUS=PASS`.
HISTORICAL_EXACT_NEXT_ACTION_AFTER_REVOKED_FE0_T09: FE1-T01 — Admin navigation/menu/breadcrumb + route meta

FE0_REMEDIATION_EVIDENCE:
- START_TRANSITION: PASS — trước khi sửa source đã đổi `STATUS` và `FE0_PHASE_STATUS` từ `PASS` thành `NEEDS_CHANGES`, bổ sung `COMPLETED_PHASES`, mở lại T03/T04/T05/T06/T07/T09 và chặn FE1-T01.
- PLAN_CONFLICT: RESOLVED — Plan V2 hiện thống nhất 401/auth-invalid clear session; network/timeout/5xx giữ token, xóa authority khỏi memory, khóa protected content và cho retry `/me`.
- MULTI_ROLE_GUARD: PASS — route có `meta.vaiTro` bắt buộc `vaiTroDangDung` tồn tại, còn trong roles `/me` và khớp route; actor null về `chonVaiTro`, actor khác route về 403.
- RESTORE_POLICY: PASS — transient `/me` giữ token + stored actor nhưng xóa `nguoiDung`, `vaiTro`, active actor khỏi memory; selector hiển thị lỗi an toàn và nút retry; retry thành công khôi phục authority.
- CURRENT_401: PASS — request interceptor lưu Bearer snapshot; chỉ 401 có snapshot khớp current token mới cleanup. Actor được chụp trước cleanup và router replace về login actor allow-list.
- LATE_401: PASS — regression test request chậm của token cũ trả 401 sau khi token mới đã được cài; token/user phiên mới vẫn nguyên vẹn và không bị điều phối sai.
- FORM_422: PASS — login/forgot/reset map `fieldErrors` vào `TruongBieuMau`, gắn `aria-invalid`/description và focus field lỗi đầu theo thứ tự form mà không làm mất draft.
- FORM_429: PASS — normalize `Retry-After` dạng giây hoặc HTTP-date; form khóa submit và hiển thị countdown, không auto-retry.
- DRAWER: PASS — `khung-ung-dung--mo` được gắn đúng vào shell, sidebar transform và overlay cùng hoạt động; overlay color đi qua CSS token.
- DIALOG: PASS — confirm dialog trap Tab/Shift+Tab, xử lý Escape, `aria-describedby`, focus ban đầu và trả focus về trigger khi đóng/unmount.
- TABLE_EMPTY: PASS — custom row slot không còn được coi là bằng chứng có dữ liệu; `hang=[]` luôn render empty state.
- NAVIGATION_FOCUS: PASS — router focus heading sau render; browser smoke xác nhận active element là `H1` ở selector và PT login, không còn là `BODY`.
- CSS_FOLLOW_UP: PASS — required marker dùng đúng class, dialog mobile padding áp dụng cho `__hop`, overlay colors dùng tokens.
- README_CHECKPOINT: PASS — FE README phản ánh Foundation thật; checkpoint chỉ còn một `EXACT_NEXT_ACTION` hiện hành và có field `COMPLETED_PHASES`.
- TARGETED_TEST: PASS — 7 files, 122 tests cho auth/router/form/shared/layout/error normalization.
- FULL_TEST: PASS — `npm run test`: Vitest 4.1.11, 14 files, 200/200 tests; không có skip/todo.
- LINT: PASS — `npm run lint`, không lỗi hoặc warning.
- BUILD: PASS — `npm run build`: Vite 8.2.2, 109 modules transformed; bundle production tạo thành công.
- DEPENDENCY: PASS — `npm ls --depth=0`; dependency tree khớp package/lockfile, không thêm package trong remediation.
- DIFF_SECURITY: PASS — `git diff --check`; source scan không có localStorage, `v-html`, sensitive console log, weak random hoặc global Idempotency-Key injection mới.
- BROWSER_QA: PASS — in-app browser tại 1280×720 và 375×812; mobile `clientWidth=scrollWidth=375`, focus sau navigation nằm trên heading, runtime chỉ có Vite connect debug và không có console error.
- SCOPE: PASS — không sửa `BE/`, `Mobile/` hoặc `Database/`; không tạo route/API nghiệp vụ FE1; không commit/push trong task này.
- FINAL_VERDICT: PASS — các finding hậu kiểm đã được đóng bằng code + regression evidence; FE-0 đủ gate để bắt đầu FE1-T01, nhưng FE1 chưa được triển khai trong task này.

CHECKPOINT_CURRENT_VERDICT: PASS — `CURRENT_PHASE=FE-0`, `CURRENT_TASK=FE0-REMEDIATION`, `STATUS=PASS`, `FE0_PHASE_STATUS=PASS`, `COMPLETED_PHASES=[FE-0]`.
