# Vue Web Completion Checkpoint

CURRENT_PHASE: FE-6
CURRENT_TASK: FE6-ALL
STATUS: PASS
FE0_PHASE_STATUS: PASS
FE1_PHASE_STATUS: PASS
FE2_PHASE_STATUS: PASS
FE3_PHASE_STATUS: PASS
FE4_PHASE_STATUS: PASS
FE5_PHASE_STATUS: PASS
FE6_PHASE_STATUS: PASS

COMPLETED_PHASES:
- FE-6 — PT Direct + Proposal; FE6-ALL PASS after independent final re-review, with Backend 348 tests and Frontend 855 tests on final code.
- FE-5 — PT Member Workspace; BE-FE5-PREREQ và FE5-ALL đã PASS với Backend 346 tests, Frontend 801 tests và independent final re-review sau consolidated repair.
- FE-4 — Admin Payment & Reconciliation; BE-WEB-T01 prerequisite và FE4-ALL repair round 1 đã PASS với Backend 331 tests, Frontend 729 tests và independent final review.
- FE-3 — Admin Catalog; FE3-ALL và final repair Wave 5 đã PASS với Frontend 684 tests, Backend 329 tests và independent final review.
- FE-0 — Foundation; remediation đã PASS với 200 tests.
- FE-1 — Admin Foundation; remediation 7 finding và full gate đã PASS với 470 tests.
- FE-2 — Admin PT; FE2-ALL và menu remediation round 5 đã PASS với Backend 323 tests và Frontend 572 tests.

COMPLETED_TASKS:
- FE6-ALL
- FE5-ALL
- FE4-ALL
- FE3-ALL
- FE0-T01
- FE0-T02
- FE0-T03
- FE0-T04
- FE0-T05
- FE0-T06
- FE0-T07
- FE0-T08
- FE0-T09
- FE1-T01
- FE1-T02
- FE1-T03
- FE1-T04
- FE1-T05
- FE1-T06
- FE1-T07
- FE1-T08
- FE2-T01
- FE2-T02
- FE2-T03
- FE2-T04
- FE2-T05
- FE2-T06
- FE2-T07
- FE2-T08

REMEDIATED_TASKS:
- FE0-T03 — Axios 401 đã dùng Bearer snapshot; late response không xóa phiên mới.
- FE0-T04 — Restore `/me` đã phân biệt 401/auth-invalid với network/timeout/5xx và có retry.
- FE0-T05 — Guard route actor bắt buộc active actor khớp route; actor null về selector.
- FE0-T06 — Drawer mobile dùng đúng parent open class; focus sau navigation về heading.
- FE0-T07 — Form map/focus 422, countdown 429, dialog focus trap/return và empty table đã sửa.
- FE0-T09 — Full test/lint/build/static/browser gate đã chạy lại và PASS.
- FE1-T08 — Cleanup/race/pagination/422/drawer/filter/status label đã sửa và full gate chạy lại PASS.
- FE2-T08 — Trang phân công PT đã được mở trong menu Admin; route metadata, thứ tự/active state và full FE gate 572 tests đã PASS ở remediation round 5.

EXACT_NEXT_ACTION: FE7-ALL entry-gate preflight — verify BE-WEB-T04/Reverb deployment and dependency compatibility before dispatch.

FE6_COMPLETION_EVIDENCE_2026_09_27:
- ENTRY_GATE: PASS — FE5-ALL checkpoint PASS; PT Direct/Proposal Backend contracts and tests inspected before writer; one aggregate task implemented all three required PT screens.
- FINAL_REPAIR: PASS — owner-authorized dedicated PT-only `GET /api/pt/direct-sessions/history` fixes dual-role PT history without changing Member-first legacy read; official Plan decimal weight and root Proposal preview rest/notes corrected. F-001–F-007 CLOSED.
- INDEPENDENT_TASK_REVIEW: PASS — round-1 task re-review closed F-001–F-004 after the first repair.
- INDEPENDENT_FINAL_REVIEW: PASS — `final-review-after-repair.md` has VERDICT/SPEC/QUALITY PASS after the consolidated final repair. F-008 is a nonblocking Minor finding: two PT history exception messages have garbled Vietnamese text.
- FRONTEND_FINAL_GATE: PASS — focused 12 files/117 tests; full 86 files/855 tests; lint exit 0 with 0 errors/375 warnings; production build exit 0, 200 modules and 641.63 kB JS size advisory; independent reviewer ran 4 focused files/23 tests PASS.
- BACKEND_FINAL_GATE: PASS — focused PT Direct 13 tests/75 assertions and full Backend 348 tests/4,130 assertions, using bundled PHP 8.4.25 and explicit `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_DATABASE=SMART_FITNESS_TEST_DATABASE=smart_fitness_fe5_round2_test`; TestDatabaseGuard passed. Read-only migration status showed M001–M061 Ran. No migration or seed command ran in the repair.
- TEST_SAFETY_LIMITATION: One earlier malformed Backend test launch emitted four environment-assignment errors and was interrupted without PHPUnit or guard output. Its database activity and selected schema are unknown; it is excluded from PASS evidence. The later guarded test runs do not establish the safety of that failed launch.
- SCOPE_AUDIT: PASS — initial product tree was clean; 23 original FE task paths and 12 authorized final repair targets were preserved and compared with before/after snapshots; no unexpected product path, schema, rule, package, or config edit; `git diff --check` PASS. No branch/worktree/commit/push/reset/restore/checkout/stash/clean.
- BROWSER_VISUAL_SMOKE: UNVERIFIED — protected PT pages were not visually exercised in a signed-in browser. The 100-row history caps can leave read-only outcome checks inconclusive; retry preserves the original idempotency key and payload.
- CHECKPOINT_TRANSITION: FE-6/FE6-ALL PASS; next step is FE-7 dependency preflight.

FE5_COMPLETION_EVIDENCE_2026_09_26:
- ENTRY_GATE: PASS — FE0–FE4 checkpoint PASS; BE-FE5-PREREQ thêm bốn GET PT workspace có `auth:api`, `role:PT`, exact current assignment và safe DTO; Task 1 independent review PASS, F-001/F-002 CLOSED.
- SCOPE: PASS — một aggregate FE5-ALL writer triển khai đủ bảy PT screens: hồ sơ self, hội viên đang phân công, chi tiết, tiến độ, Plan chính thức/lịch tương lai, lịch sử bất biến và ghi chú append-only; không thêm FE6 chat/proposal UI.
- AUTHORIZATION_AND_INTEGRITY: PASS — PT resource scope do Backend quyết định; 403/404 xóa dữ liệu nhạy cảm và điều hướng, kể cả history load-more; request generations chặn response A đến muộn sau B/logout/role change; Plan tách Proposal, Session chỉ đọc, notes không blind retry.
- TASK_REVIEW: PASS — Task 2 round-2 independent reviewer đóng R01–R07; focused 169 tests, full FE 794 tests, lint/build/dependency checks PASS tại thời điểm review.
- FINAL_REPAIR: PASS — consolidated final wave đóng FE5-FINAL-001 Important và FE5-FINAL-002/003 Minor trong đúng sáu FE source/test paths, giữ các finding cũ CLOSED.
- INDEPENDENT_FINAL_REVIEW: PASS — `final-review-round-1.md` có VERDICT/SPEC/QUALITY PASS, không còn Critical/Important/Minor finding; `FE5_CHECKPOINT_TRANSITION_PERMITTED: YES`.
- FRONTEND_FINAL_GATE: PASS — trên source cuối, affected 38 tests, exact focused 22 files/176 tests, full FE 79 files/801 tests; lint exit 0, 0 errors/110 formatting warnings; production build 190 modules với chunk-size advisory; `npm ls --depth=0` PASS.
- BACKEND_FINAL_GATE: PASS — trên schema test dùng một lần được chủ dự án phê duyệt `smart_fitness_fe5_round2_test` với đủ bốn testing guards và 61 migrations: DB guard 7/7, related focused 62 tests/644 assertions, full Backend 346 tests/4,104 assertions; Pint, Composer strict validate/audit/platform, route scan PASS.
- SCOPE_AUDIT: PASS — baseline product ban đầu clean; Task 1 đúng tám Backend/contract paths, Task 2 đúng 44 FE allowed targets với snapshot/delta từng vòng; `git diff --check` PASS; không sửa package/lock/config/rules, không commit/push/branch/worktree/reset/restore/checkout/stash/clean.
- BROWSER_VISUAL_SMOKE: UNVERIFIED — chưa có PT credential/session fixture an toàn để chạy bảy protected pages tại 1440/768/390; browser skill cài đặt thiếu runtime rule files bắt buộc. Public login-shell smoke không được coi là protected-page evidence. Unit/router integration và Backend security tests vẫn đạt exit gate browser-if-available của packet.
- RESIDUAL_NON_BLOCKERS: 110 Vue formatting warnings, Vite chunk-size advisory; disposable test schema được giữ lại, schema `smart_fitness_test` cũ từng nhiễm fixture không bị xóa hoặc sửa trong recovery.
- CHECKPOINT_TRANSITION: FE-5/FE5-ALL PASS; next action FE6-ALL theo Vue Plan V2 §FE-6, chưa bắt đầu FE-6.
- COMMIT_CREATED: NO.
- PUSH_PERFORMED: NO.

FE4_COMPLETION_EVIDENCE_2026_09_20:
- ENTRY_GATE: PASS — FE-1/FE-3 checkpoint PASS; BE-WEB-T01 closed BE-FOLLOWUP-01A/01B with safe unlinked events, related abnormal-event reconciliation, Admin authorization, linked branch isolation and Safe DTO redaction.
- BACKEND_GATE: PASS — focused Admin Payment 4/4 tests, 103 assertions; full Backend 331/331 tests, 3,951 assertions; Pint, Composer validation, source-root/DB guard and independent review PASS.
- FE4_SCOPE: PASS — exactly 3 read-only Admin screens/routes: Payment list, Payment detail and reconciliation queues; no financial mutation, client join, entitlement calculation or sensitive raw payload rendering.
- FE4_REPAIR_ROUND_1: PASS — deep Safe DTO projection, detail unmount cleanup, retained-data retry banners, request-race coverage and Visual UI token audit closed F-001..F-005.
- FRONTEND_GATE: PASS — focused repair 36/36, required gate 91/91, full Frontend 63 files/729 tests, lint, production build, dependency inventory, static/security scans and `git diff --check` PASS.
- INDEPENDENT_TASK_REVIEW: PASS — SPEC PASS, QUALITY PASS, no remaining finding.
- INDEPENDENT_FINAL_REVIEW: PASS — complete Backend + Frontend delta, baseline preservation and final-tree evidence verified; no Critical/Important/Minor finding.
- RESIDUAL_NON_BLOCKERS: authenticated browser visual QA unavailable; build has non-failing 523.20 kB chunk warning; unlinked-event visibility remains a single-branch MVP ruling; one transient historical MariaDB deadlock did not recur.
- CHECKPOINT_TRANSITION: FE-4/FE4-ALL PASS; next action is FE5-ALL. FE5 was not started.

BLOCKED_FEATURES:
- CURRENT: Không còn blocker hiện hành thuộc FE-5. BE-FE5-PREREQ và FE5-ALL đã được independent final re-review PASS; các dòng FE3 quota/entry-gate cũ ngay bên dưới chỉ là historical evidence.
- FE3 execution is temporarily blocked by required-model capacity: three Luna Max writer attempts ended during mandatory preflight with zero target changes; current Codex weekly/secondary usage is 100%, `rateLimitReachedType=rate_limit_reached`, no reset credit, reset expected 15/09/2026 12:49:44 ICT. The approved Sol repair plan/brief and 16-target byte-for-byte snapshot are preserved in `.fitness-sdd/fe3-all`; model substitution is prohibited.
- FE3-ALL entry gate failed on 10/09/2026: actual `nhom_co` schema/model/create-update requests/service DTO have no `status`, so Muscle Group cannot be deactivated without hard delete. Backend tests also lack Muscle Group deactivate coverage and Muscle Group PATCH authorization-matrix coverage. Planner verdict is `WAITING_BACKEND_FIX`; no FE3 writer/reviewer was dispatched and no product code changed.
- Không còn blocker thuộc FE-0 sau remediation.
- Không còn blocker source/test thuộc FE-1 sau remediation; browser visual smoke chưa chạy lại do máy hiện không có `agent-browser`, nhưng đây không phải exit gate FE1-T08 trong V2.
- Không còn current-state blocker thuộc FE-2. `FE2-MENU-F-001` đã CLOSED bằng menu remediation round 5; `BE-FOLLOWUP-02`, `BE-FOLLOWUP-03`, `BLOCKER-01` và `BLOCKER-02` vẫn đã đóng bằng evidence FE2-ALL.
- Các blocker Backend/phase sau vẫn giữ nguyên ở `FUTURE_BACKEND_BLOCKERS`; FE-0 PASS không đồng nghĩa toàn bộ Web hoàn tất.

HISTORICAL_EVIDENCE_NOTICE:
- Các claim PASS của FE0-T01..T09 ở phần lịch sử bên dưới mô tả lần chạy trước hậu kiểm.
- Trạng thái đó đã bị thu hồi trước khi sửa source; chỉ `FE0_REMEDIATION_EVIDENCE` và các field đầu file là kết luận hiện hành.
- Các claim PASS của FE1-T01..T08 ở phần lịch sử bên dưới mô tả lần chạy trước hậu kiểm ngày 04/09/2026; chúng đã bị thu hồi và không được dùng thay cho evidence remediation mới.
- Kết luận FE-1 hiện hành phải đọc từ `FE1_REMEDIATION_EVIDENCE_2026_09_04` và các field đầu file; evidence cũ chỉ được giữ làm lịch sử.

BACKEND_API_BLOCKERS:
- Không có blocker từ Backend đối với FE0-T05; forgot/reset đã được đối chiếu với source/test/contract, không sửa Backend.
- Không còn current Backend blocker đối với FE2-T02/T04/T06/T07: invitation recovery/audit, trainer-profile GET và assignment list/detail/history đã có source/test/contract evidence PASS.

FUTURE_BACKEND_BLOCKERS:
- Các blocker Admin PT profile và Admin assignment history đã được đóng trong FE-2; historical entries trong V2/checkpoint vẫn được giữ để truy nguyên.
- PT Member detail, PT current Workout Plan và PT Workout History đã được đóng bởi BE-FE5-PREREQ; dependency phase sau còn Receptionist Member lookup và Receptionist Membership/Gym eligibility. FE-6 Direct/Proposal có entry gate riêng theo Vue Plan V2.
- Payment event visibility và reconciliation filter đã được đóng bởi BE-WEB-T01 với regression/authorization/redaction evidence PASS; invitation recovery và PT onboarding audit snapshot đã được đóng trong FE-2.
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
- FE/src/pages/admin/huan_luyen_vien/huan_luyen_vien.index.vue
- FE/src/pages/admin/huan_luyen_vien/huan_luyen_vien.index.test.js
- FE/src/components/dung_chung/chan_tinh_nang_bi_chan.vue
- FE/src/components/dung_chung/chan_tinh_nang_bi_chan.test.js
- FE/src/pages/admin/huan_luyen_vien/huan_luyen_vien.chi_tiet.vue
- FE/src/pages/admin/huan_luyen_vien/huan_luyen_vien.chi_tiet.test.js
- FE/src/pages/admin/huan_luyen_vien/huan_luyen_vien.tao_moi.vue
- FE/src/pages/admin/huan_luyen_vien/huan_luyen_vien.tao_moi.test.js
- FE/src/pages/admin/phan_cong_pt/phan_cong_pt.index.vue
- FE/src/pages/admin/phan_cong_pt/phan_cong_pt.index.test.js

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
- FE/src/services/tai_khoan.api.js
- FE/src/services/tai_khoan.api.test.js
- FE/src/stores/tai_khoan.store.js
- FE/src/stores/tai_khoan.store.test.js
- FE/src/router/dieu_huong_admin.js
- FE/src/router/dieu_huong_admin.test.js

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
- FE0 foundation ban đầu không tạo domain route; các Admin domain route bổ sung được ghi ở evidence của task tương ứng.
- `/admin/huan-luyen-vien` -> `adminHuanLuyenVien` (Admin PT list, FE2-T01).
- `/admin/huan-luyen-vien/tao-moi` -> `adminTaoHuanLuyenVien` (Admin PT onboarding blocker, FE2-T02; static route trước `:id`).
- `/admin/huan-luyen-vien/:id` -> `adminChiTietHuanLuyenVien` (Admin PT basic detail, FE2-T03).
- `/admin/phan-cong-pt` -> `adminPhanCongPt` (Admin PT assignment blocker, FE2-T05/T06/T07; guarded direct route, không nằm trong menu).

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

FE1_T01_EVIDENCE:
- ENTRY_GATE: PASS — trước khi bắt đầu có `CURRENT_PHASE=FE-0`, `CURRENT_TASK=FE0-REMEDIATION`, `STATUS=PASS`, `FE0_PHASE_STATUS=PASS`, `COMPLETED_PHASES` chứa `FE-0`, completed FE0-T01..FE0-T09 và `EXACT_NEXT_ACTION` bắt đầu bằng FE1-T01; không yêu cầu CURRENT_TASK đã là FE1-T01 trước khi start.
- TASK_START_TRANSITION: PASS — đã chuyển `CURRENT_PHASE=FE-1`, `CURRENT_TASK=FE1-T01`, `STATUS=IN_PROGRESS`, giữ `FE0_PHASE_STATUS=PASS`, `COMPLETED_PHASES=[FE-0]` và chưa thêm FE1-T01 vào completed trước khi acceptance PASS.
- FE0_CARRY_FORWARD: PASS — giữ nguyên remediation evidence FE0; không sửa source/evidence lịch sử FE0 ngoài transition/current fields cần thiết.
- ADMIN_NAVIGATION_ARCHITECTURE: PASS — tạo module `dieu_huong_admin.js`; menu là presentation-only, named-route nội bộ, active parent dựa trên route name/matched/related names, không suy quyền Backend.
- ADMIN_MENU_CONFIG: PASS — đúng 4 nhóm FE1: Bảng điều khiển, Tài khoản, Hội viên, Nhân viên lễ tân; thứ tự và route names theo V2.
- ROUTE_META_CONVENTION: PASS — helper meta Admin chuẩn hóa `yeuCauXacThuc=true`, `vaiTro=['ADMIN']`, `boCuc='admin'`; `tinhNang` chỉ readiness/presentation, breadcrumb dùng `duongDanPhanCap`.
- BREADCRUMB_COMPONENT: PASS — tạo `components/bo_cuc/duong_dan_phan_cap.vue`; semantic nav/ol/li, `aria-label=Đường dẫn`, current `aria-current=page`, chỉ đọc route meta, không params/API/store/external href.
- BREADCRUMB_INTEGRATION: PASS — chỉ tích hợp trong Admin shell; ancestor chỉ là RouterLink khi named route đã register, detail dùng nhãn generic từ metadata.
- ACTIVE_MENU_STATE: PASS — route chính, related detail route và route matched đều active đúng parent; không dùng pathname startsWith.
- MISSING_ROUTE_SAFETY: PASS — planned route chưa register bị lọc trước khi render, không tạo RouterLink hỏng; production menu hiện rỗng vì chưa có business route theo scope T01.
- ADMIN_GUARD_REGRESSION: PASS — giữ nguyên guard/actor authority FE0; không thêm bypass, auto-role hoặc client authorization.
- FE0_REMEDIATION_REGRESSION: PASS — full test hiện hành đạt 221/221; evidence remediation FE0 trước đó được giữ nguyên.
- RESPONSIVE_REGRESSION: PASS — breadcrumb wrap/ellipsis dùng token CSS; drawer breakpoint/open-class, overflow và responsive static assertions hiện có vẫn đạt.
- FOCUS_REGRESSION: PASS — giữ route-focus heading và drawer focus behavior hiện có; menu click được kiểm thử đóng drawer sau navigation.
- TARGETED_TESTS: PASS — 5 files, 55 tests.
- FULL_TEST: PASS — `npm run test` từ `FE/`: Vitest 4.1.11, 16 files, 221/221 tests, không skip/todo.
- LINT: PASS — `npm run lint` từ `FE/`: ESLint không lỗi, không warning.
- BUILD: PASS — `npm run build` từ `FE/`: Vite 8.2.2, 120 modules transformed.
- DEPENDENCY: PASS — `npm ls --depth=0` thành công; không thêm package/dependency.
- DIFF_CHECK: PASS — `git diff --check` không báo whitespace error.
- NAMING_SCAN: PASS — file mới và function mới theo tiếng Việt không dấu snake_case/camelCase; chỉ giữ naming framework/legacy đã có.
- DOCBLOCK_SCAN: PASS — helper navigation/meta/breadcrumb quan trọng có docblock meaningful về input/process/result/side effect/security boundary.
- SECURITY_SCAN: PASS — không có localStorage, v-html, sensitive console log, external/raw redirect, secret hoặc token handling mới trong T01.
- SCOPE_AUDIT: PASS — không tạo production business route/page cho T01; không bắt đầu FE1-T02; không sửa BE/Mobile/Database.
- BROWSER_VISUAL_SMOKE: BLOCKED_TOOLING — agent-browser doctor PASS nhưng launch thất bại do CDP response channel closed; không có production Admin business route để smoke screen trong scope T01, còn component/static regression đã PASS.
- FILES_CREATED: `FE/src/router/dieu_huong_admin.js`, `FE/src/router/dieu_huong_admin.test.js`, `FE/src/components/bo_cuc/duong_dan_phan_cap.vue`, `FE/src/components/bo_cuc/duong_dan_phan_cap.test.js`.
- FILES_MODIFIED: `FE/src/layouts/bo_cuc_admin.vue`, `FE/src/components/bo_cuc/khung_ung_dung.vue`, `FE/src/components/bo_cuc/thanh_ben_dieu_huong.vue`, `FE/src/layouts/bo_cuc.test.js`, `FE/src/assets/main.css`, checkpoint này.
- DEPENDENCIES_ADDED: NO.
- PRODUCTION_BUSINESS_ROUTES_CREATED: NO — đúng yêu cầu T01, menu filter route registry và không invent page.
- BUSINESS_API_STORE_SERVICE_CREATED: NO.
- BACKEND_MOBILE_DATABASE: NO changes.
- COMMIT_PUSH: NO commit, NO push.
- TASK_END_TRANSITION: PASS — `CURRENT_PHASE=FE-1`, `CURRENT_TASK=FE1-T01`, `STATUS=PASS`; completed gồm FE0-T01..FE0-T09 và FE1-T01; `FE1_PHASE_STATUS=IN_PROGRESS`; `EXACT_NEXT_ACTION=FE1-T02`.

FE1_T02_EVIDENCE:
- ENTRY_GATE: PASS — trước khi bắt đầu có `CURRENT_PHASE=FE-1`, `CURRENT_TASK=FE1-T01`, `STATUS=PASS`, `FE0_PHASE_STATUS=PASS`, `FE1_PHASE_STATUS=IN_PROGRESS`, `COMPLETED_PHASES` chứa `FE-0`, completed FE0-T01..FE0-T09 và FE1-T01, `EXACT_NEXT_ACTION` bắt đầu bằng FE1-T02; đã chuyển task start sang `CURRENT_TASK=FE1-T02`, `STATUS=IN_PROGRESS` trước khi sửa source.
- FE0_T01_CARRY_FORWARD: PASS — giữ nguyên evidence FE0/T01 và các thay đổi trước đó; chỉ bổ sung T02 route/behavior/test cần thiết, không làm lại FE0-T02.
- BACKEND_CONTRACT_INSPECTION: PASS — đã đọc route, middleware, `AdminDashboardRequest`, controller, `AdminDashboardService`, Backend feature tests và API contract; actual source là authority.
- PLAN_BACKEND_CONTRACT_MISMATCH: NONE — contract thực tế khớp phạm vi V2 đáng kể; không sửa Backend.
- DASHBOARD_ROUTE: PASS — production route `/admin/bang-dieu-khien` tên `adminBangDieuKhien`, component `pages/admin/bang_dieu_khien/bang_dieu_khien.index.vue`.
- ROUTES_CREATED: `/admin/bang-dieu-khien` → `adminBangDieuKhien`; đây là business route đầu tiên của FE-1, không sửa claim lịch sử T01.
- ROUTE_META: PASS — dùng `taoMetaTuyenDuongAdmin`, `yeuCauXacThuc=true`, `vaiTro=['ADMIN']`, `boCuc='admin'`, title và breadcrumb metadata đúng convention.
- BACKEND_REQUEST_CONTRACT: PASS — actual endpoint `GET /api/admin/dashboard`; FE Axios baseURL đã chứa `/api` nên service gọi `/admin/dashboard`; chỉ truyền `from` và `to` khi có đủ cặp `YYYY-MM-DD`, không truyền branch/timezone/metric/idempotency.
- BACKEND_RESPONSE_CONTRACT: PASS — giữ envelope `{ data: { branch, period, accounts, memberships, activity, payments, pt, generated_at } }` và validate required shape trước render.
- BACKEND_METRIC_FIELDS: PASS — đã map đúng 10 nested Backend fields: `active_members_count`, `active_trainers_count`, `active_terms_count`, `awaiting_activation_terms_count`, `check_ins_today_count`, `completed_workouts_today_count`, `completed_workouts_in_period_count`, `successful_in_period_count`, `reconciliation_required_count`, `active_assignments_count`.
- BACKEND_TIMEZONE_PERIOD: PASS — Backend lấy branch/timezone từ active Admin, mặc định 30 ngày theo branch local date, explicit dates là business dates, today metrics do Backend tính, `generated_at` là UTC ISO.
- DASHBOARD_SERVICE: PASS — tạo `taiTongQuanAdmin()` dùng Axios client duy nhất, preserve body response, query allow-list và read-only GET.
- DASHBOARD_PAGE: PASS — page tiếng Việt có title, date filter, period/timezone context, metric grid và shared loading/error states; không chart.
- DASHBOARD_RESPONSE_SHAPE: PASS — missing/invalid required metric fail-safe thành safe error, không invented zero; unknown fields bị bỏ qua.
- DATE_FILTER: PASS — `apDungKhoangNgay()` và `datLaiKhoangNgay()` xử lý cặp rỗng/có đủ, calendar date hợp lệ, thứ tự và inclusive range tối đa 366 ngày; dùng UTC date-only chỉ cho UX day count, không đổi business date sang timestamp.
- DEFAULT_PERIOD_BEHAVIOR: PASS — initial/reset request gửi `{}` để Backend chọn period mặc định; UI hiển thị `period` thực tế Backend trả về.
- TIMEZONE_BOUNDARY: PASS — không có timezone selector, branch selector hoặc client-side timezone semantics; chỉ format `generated_at` theo timezone Backend trả.
- METRIC_ALLOW_LIST: PASS — mapping explicit exact key → nhãn tiếng Việt; không dùng dynamic `Object.entries`.
- NO_REVENUE: PASS — không có card/aggregation doanh thu, amount, currency hoặc derived business metric; fixture có revenue để test không render.
- NO_BRANCH_SELECTOR: PASS — không có dropdown branch và không gửi `branch_id`/`chi_nhanh_id`.
- ZERO_VALUE_BEHAVIOR: PASS — số 0 được render là `0`, không coi là empty/error.
- LOADING_STATE: PASS — page/card dùng `TrangThaiTaiDuLieu`, `aria-busy` và status accessible; không hiển thị stale numeric value trong card đang tải.
- ERROR_422: PASS — map `fieldErrors` cạnh `from`/`to`, giữ metric tốt trước đó và không auto-retry.
- ERROR_401: PASS — giữ Axios/Auth Store remediation hiện hữu; Dashboard không cleanup session hoặc logout riêng.
- ERROR_403: PASS — replace tới route `khongCoQuyen`, không logout và không tự tạo session.
- ERROR_5XX_NETWORK: PASS — hiển thị normalized safe error và cho retry thủ công cùng filter hiện tại; không automatic retry loop.
- ADMIN_ACTIVE_ACTOR: PASS — route vẫn yêu cầu Backend-revalidated ADMIN và active actor khớp; actor null về selector, actor PT không được switch sang Admin.
- ADMIN_HOME_MAPPING: PASS — sau khi route thật tồn tại, ADMIN duy nhất đi `adminBangDieuKhien`; multi-role sau login không auto-priority.
- ADMIN_MENU_ACTIVATION: PASS — menu dùng cùng planned config/filter theo route registry T01; Dashboard tự xuất hiện và active khi route đã register.
- BREADCRUMB: PASS — Dashboard dùng T01 metadata với breadcrumb chỉ `Bảng điều khiển`, không external href/param.
- RESPONSIVE: PASS — metric grid 4/2/1 cột theo desktop/tablet/mobile, filter wrap/stack, dùng CSS tokens và không có horizontal overflow rule mới.
- ACCESSIBILITY: PASS — date inputs có visible labels và shared ARIA error wiring; status/loading semantic; metric text semantic; route heading focus giữ nguyên từ Foundation.
- TARGETED_TESTS: PASS — 8 test files, 96 tests: Dashboard service/page, metric card, Admin menu/router/guard, layout và auth regression.
- FULL_TEST: PASS — `npm run test` từ `FE/`: Vitest 4.1.11, 19 test files, 238/238 tests, không skip/todo.
- LINT: PASS — `npm run lint` từ `FE/`: ESLint không lỗi, không warning.
- BUILD: PASS — `npm run build` từ `FE/`: Vite 8.2.2, 125 modules transformed.
- DEPENDENCY: PASS — `npm ls --depth=0` thành công; không thêm package/dependency.
- DIFF_CHECK: PASS — `git diff --check` không báo whitespace error.
- BROWSER_VISUAL_SMOKE: BLOCKED_TOOLING — `agent-browser doctor --offline --quick` nhận diện Chrome/CLI nhưng `agent-browser open http://127.0.0.1:5174/admin/bang-dieu-khien` thất bại với exact reason `Auto-launch failed: CDP response channel closed`; không cài dependency và không fake browser PASS.
- NAMING_SCAN: PASS — file mới Vietnamese không dấu snake_case; function business Vietnamese không dấu camelCase; không thấy tên prohibited như `fetchDashboard`, `loadDashboard`, `applyDateRange`, `handleDateFilter`, `handleRetry`.
- DOCBLOCK_SCAN: PASS — service/page/helper/dispatcher quan trọng có docblock nêu input, flow, output, side effect và Backend/security boundary phù hợp.
- SECURITY_SCAN: PASS — không có localStorage, `v-html`, sensitive console log, raw Axios error render, secret, auth token display, raw payment/provider data hoặc client authorization bypass.
- SCOPE_AUDIT: PASS — chỉ tạo Dashboard route/page/service/metric/filter/tests và cập nhật T01 integration; không tạo Account, Member, Receptionist, FE1-T03/T04+, chart hay dependency mới.
- FILES_CREATED: `FE/src/services/bang_dieu_khien.api.js`, `FE/src/services/bang_dieu_khien.api.test.js`, `FE/src/components/dung_chung/the_chi_so.vue`, `FE/src/components/dung_chung/the_chi_so.test.js`, `FE/src/pages/admin/bang_dieu_khien/bang_dieu_khien.index.vue`, `FE/src/pages/admin/bang_dieu_khien/bang_dieu_khien.index.test.js`.
- FILES_MODIFIED: `FE/src/router/index.js`, `FE/src/router/index.test.js`, `FE/src/router/dieu_huong_admin.test.js`, `FE/src/router/bao_ve_tuyen_duong.js`, `FE/src/router/bao_ve_tuyen_duong.test.js`, `FE/src/utils/dieu_phoi_xac_thuc.js`, `FE/src/pages/xac_thuc.test.js`, `FE/src/assets/main.css`, `docs/VUE_WEB_COMPLETION_CHECKPOINT.md`.
- DEPENDENCIES_ADDED: NO.
- PRODUCTION_ROUTES_AFTER_T02: chỉ thêm `/admin/bang-dieu-khien`; không có `/admin/tai-khoan`, `/admin/hoi-vien`, `/admin/nhan-vien-le-tan` hoặc FE2/FE3/FE4 route.
- BACKEND_MODIFIED: NO — Backend chỉ được đọc để inspect contract.
- MOBILE_MODIFIED: NO.
- DATABASE_MODIFIED: NO — không migration/DB mutation.
- COMMIT_PUSH: NO commit, NO push.
- CHECKPOINT_UPDATED: PASS — `CURRENT_PHASE=FE-1`, `CURRENT_TASK=FE1-T02`, `STATUS=PASS`; `FE0_PHASE_STATUS=PASS`, `FE1_PHASE_STATUS=IN_PROGRESS`, `COMPLETED_PHASES` chỉ gồm FE-0, completed đã thêm FE1-T02.
- FE1_PHASE_STATUS: IN_PROGRESS — FE-1 chưa hoàn tất, chỉ T02 được đánh dấu PASS.
- TASK_END_TRANSITION: PASS — `EXACT_NEXT_ACTION=FE1-T03 — Account list/search/filter/pagination`; đã dừng sau T02, không bắt đầu T03.

FE1_T03_EVIDENCE:
- ENTRY_GATE: PASS — trước khi bắt đầu có `CURRENT_PHASE=FE-1`, `CURRENT_TASK=FE1-T02`, `STATUS=PASS`, `FE0_PHASE_STATUS=PASS`, `FE1_PHASE_STATUS=IN_PROGRESS`, `COMPLETED_PHASES` chứa FE-0, completed FE0-T01..FE0-T09 + FE1-T01 + FE1-T02, `EXACT_NEXT_ACTION` bắt đầu bằng FE1-T03 và baseline 19 test files/238 tests PASS.
- TASK_START_TRANSITION: PASS — đã chuyển `CURRENT_TASK=FE1-T03`, `STATUS=IN_PROGRESS`, giữ FE-0 PASS, FE-1 IN_PROGRESS, completed phases chỉ FE-0 và chưa thêm FE1-T03 trước acceptance.
- BACKEND_CONTRACT_INSPECTION: PASS — đã đọc `BE/routes/api.php`, `ListAccountsRequest`, `AccountController`, `AccountManagementService`, `AdminAccountRoleApiTest` và `docs/BACKEND_API_CONTRACT.md`; source xác nhận GET `/api/admin/accounts`, middleware `auth:api` + `role:ADMIN`, Backend là authority.
- ACCOUNT_LIST_ROUTE: PASS — production route `/admin/tai-khoan` với name `adminTaiKhoan`; không có detail route.
- ACCOUNT_LIST_SERVICE: PASS — `FE/src/services/tai_khoan.api.js` dùng single `ketNoiApi`, function `taiDanhSachTaiKhoan()`, GET-only và giữ envelope Account endpoint.
- ACCOUNT_LIST_STORE: PASS — `FE/src/stores/tai_khoan.store.js` chỉ implement list/filter/pagination/loading/error/retry/cleanup; không có status/role mutation action.
- ACCOUNT_RESPONSE_SHAPE: PASS — giữ `{ data: { items, pagination } }`; store kiểm tra envelope trước khi commit.
- ACCOUNT_DTO_FIELDS: PASS — đối chiếu DTO gồm `id`, `name`, `email`, `phone`, `avatar`, `status`, verification/login timestamps, branch, member/trainer profile, roles và created/updated timestamps; page chỉ render cột allow-list cần thiết.
- FILTER_CONTRACT: PASS — chỉ dùng `search`, `status`, `role`, `page`, `per_page`; `status`/`role` enum exact, `search` max 150, `page` từ 1, `per_page` 1..100.
- SEARCH_BEHAVIOR: PASS — gửi nguyên search text sau trim UX; Backend search name/email/phone/member code/trainer code và numeric ID; không client-side search trên một page.
- STATUS_FILTER: PASS — chỉ render `HOAT_DONG`, `BI_KHOA`, `NGUNG_HOAT_DONG`; nhãn tiếng Việt.
- ROLE_FILTER: PASS — chỉ render `MEMBER`, `PT`, `RECEPTIONIST`, `ADMIN`; không invent FREE/Premium.
- PAGINATION_CONTRACT: PASS — dùng `thanh_phan_trang.vue` và exact `current_page`, `per_page`, `total`, `last_page` từ Backend; không paginate client-side.
- FILTER_PAGE_INVARIANT: PASS — apply/reset về page 1; chuyển page giữ applied filter; retry giữ filter/page.
- REQUEST_RACE_PROTECTION: PASS — store dùng request sequence; response cũ không overwrite response mới; cleanup cũng invalidate request đang bay.
- STORE_CLEANUP: PASS — `xoaDuLieu()` được nối vào logout, actor change và auth authority loss qua `xac_thuc.store.js`; 403 clear list trước redirect; không persist Account data.
- LOADING_STATE: PASS — initial loading dùng `trang_thai_tai_du_lieu.vue`; refresh có status accessible.
- EMPTY_STATE: PASS — successful zero rows hiển thị empty/no-result, không hiển thị empty trước first response hoặc khi initial request lỗi.
- ERROR_422: PASS — field errors map vào search/status/role/page/per_page; giữ prior valid data khi có.
- ERROR_401: PASS — không duplicate cleanup; Axios/Auth infrastructure xử lý current/late 401 theo FE0; page không logout giả.
- ERROR_403: PASS — không logout; clear Account data và điều hướng `khongCoQuyen`.
- ERROR_5XX_NETWORK: PASS — error state an toàn + retry thủ công, không auto-retry loop.
- MENU_ACTIVATION: PASS — menu dùng registry hiện có, sau T03 hiện Bảng điều khiển + Tài khoản; Hội viên/Lễ tân vẫn bị lọc vì route chưa register.
- BREADCRUMB: PASS — route meta dùng `duongDanPhanCap: [{ nhan: 'Tài khoản' }]`, không invent Dashboard ancestor.
- RESPONSIVE: PASS — filter stack theo breakpoint; table scroll trong wrapper; pagination dùng control hiện có; không thêm page overflow.
- ACCESSIBILITY: PASS — label visible, `aria-describedby`/`aria-invalid` cho filter errors, semantic table/caption/scope, text status + color, accessible loading/error/pagination.
- TARGETED_TESTS: PASS — 6 test files, 83 tests: Account service/store/page, Admin router/navigation và Auth cleanup regression.
- FULL_TEST: PASS — `npm run test`: 22 test files, 289 tests PASS.
- LINT: PASS — `npm run lint` PASS, không warning/error.
- BUILD: PASS — `npm run build` PASS, Vite 8.2.2, 131 modules transformed.
- DEPENDENCY: PASS — `npm ls --depth=0` PASS; không thêm dependency.
- DIFF_CHECK: PASS — `git diff --check` PASS.
- BROWSER_VISUAL_SMOKE: BLOCKED_TOOLING — `agent-browser doctor --offline --quick` nhận diện CLI/Chrome nhưng `agent-browser --session fe1-t03 open http://127.0.0.1:5175/admin/tai-khoan` thất bại với exact reason `Auto-launch failed: CDP response channel closed`; đã dừng dev server, không cài dependency và không fake PASS.
- NAMING_SCAN: PASS — file mới `tai_khoan.api.js`, `tai_khoan.store.js`, `tai_khoan.index.vue`/tests; business functions Vietnamese không dấu camelCase; không có prohibited English handler names.
- DOCBLOCK_SCAN: PASS — service, store orchestration/cleanup/filter/pagination/retry và page actions có docblock nêu input, flow, output, side effect và authority/security boundary.
- SECURITY_SCAN: PASS — implementation không dùng localStorage/sessionStorage cho Account, `v-html`, raw error render, secret/token persistence, branch selector, client authorization bypass hay Idempotency-Key.
- SCOPE_AUDIT: PASS — chỉ tạo Account list route/page/service/store/tests, menu/breadcrumb integration và Auth cleanup hook; không tạo detail/mutation/Member/Receptionist/FE2+.
- FILES_CREATED: `FE/src/services/tai_khoan.api.js`, `FE/src/services/tai_khoan.api.test.js`, `FE/src/stores/tai_khoan.store.js`, `FE/src/stores/tai_khoan.store.test.js`, `FE/src/pages/admin/tai_khoan/tai_khoan.index.vue`, `FE/src/pages/admin/tai_khoan/tai_khoan.index.test.js`.
- FILES_MODIFIED: `FE/src/router/index.js`, `FE/src/router/index.test.js`, `FE/src/router/dieu_huong_admin.test.js`, `FE/src/stores/xac_thuc.store.js`, `FE/src/stores/xac_thuc.store.test.js`, `FE/src/assets/main.css`, `docs/VUE_WEB_COMPLETION_CHECKPOINT.md`.
- BACKEND_MODIFIED: NO — Backend chỉ được đọc để inspect contract.
- MOBILE_MODIFIED: NO.
- DATABASE_MODIFIED: NO — không migration/DB mutation.
- COMMIT_PUSH: NO commit, NO push.
- CHECKPOINT_UPDATED: PASS — `CURRENT_PHASE=FE-1`, `CURRENT_TASK=FE1-T03`, `STATUS=PASS`; `FE0_PHASE_STATUS=PASS`, `FE1_PHASE_STATUS=IN_PROGRESS`, `COMPLETED_PHASES` chỉ gồm FE-0, completed đã thêm FE1-T03.
- FE1_PHASE_STATUS: IN_PROGRESS — FE-1 chưa hoàn tất, chỉ các task FE1-T01..FE1-T03 đã PASS.
- TASK_END_TRANSITION: PASS — `EXACT_NEXT_ACTION=FE1-T04 — Account detail + account status mutation`; đã dừng sau T03, không bắt đầu T04.

FE1_T04_EVIDENCE:
- ENTRY_GATE: PASS — trước khi bắt đầu có `CURRENT_PHASE=FE-1`, `CURRENT_TASK=FE1-T03`, `STATUS=PASS`, `FE0_PHASE_STATUS=PASS`, `FE1_PHASE_STATUS=IN_PROGRESS`, completed FE0-T01..FE0-T09 + FE1-T01..FE1-T03 và `EXACT_NEXT_ACTION=FE1-T04`; không yêu cầu CURRENT_TASK đã là T04 trước khi start.
- TASK_START_TRANSITION: PASS — đã chuyển `CURRENT_TASK=FE1-T04`, `STATUS=IN_PROGRESS`, giữ nguyên completed tasks đến T03; không sửa evidence lịch sử T01/T02/T03 và không thêm T04 trước acceptance.
- BACKEND_CONTRACT_INSPECTION: PASS — đã đọc routes/controller/request/service/guard/tests Backend và `docs/BACKEND_API_CONTRACT.md`; xác nhận auth:api + role:ADMIN, DTO/status/error contract và Backend là authority.
- ACCOUNT_DETAIL_ROUTE: PASS — production route `GET /admin/tai-khoan/:id`, name `adminChiTietTaiKhoan`, H1 `Chi tiết tài khoản`, route guard/meta Admin.
- ACCOUNT_DETAIL_SERVICE: PASS — `taiChiTietTaiKhoan()` gọi single Axios client đúng `/admin/accounts/{id}`; id phải positive safe integer, invalid id không phát sinh request.
- ACCOUNT_DETAIL_STORE: PASS — detail state/action có loading/error/cleanup, selected Account, same-id retry và sequence guard; 403/404 không giữ selected data, 5xx/network giữ detail cùng id cho retry đọc.
- ACCOUNT_DETAIL_DTO: PASS — chỉ render safe Backend DTO fields: identity/contact/timestamps, branch reference, member/trainer profile reference và roles; không render password/hash/token/reset/audit/provider payload.
- STATUS_MUTATION_CONTRACT: PASS — `PATCH /admin/accounts/{id}/status` chỉ gửi body `{ status }`, không gửi field thừa và không gửi Idempotency-Key.
- STATUS_ENUM: PASS — UI/service/store chỉ allow-list exact `HOAT_DONG`, `BI_KHOA`, `NGUNG_HOAT_DONG`.
- LAST_ADMIN_BACKEND_AUTHORITY: PASS — UI hiển thị conflict từ `LAST_ACTIVE_ADMIN_PROTECTED`; không tự tính hoặc bypass last-active-admin guard.
- NO_HARD_DELETE: PASS — không thêm nút/API hard-delete; lịch sử và status do Backend quản lý.
- ROLE_READ_ONLY: PASS — detail hiển thị role active và revoked cùng timestamp ở chế độ read-only.
- NO_ROLE_MUTATION: PASS — không implement grant/revoke/regrant, PUT/DELETE roles hoặc profile-conflict UX của T05.
- DETAIL_REQUEST_RACE: PASS — request sequence của detail chặn response cũ ghi đè account mới; A→B được test.
- STATUS_CONFIRMATION: PASS — mọi thay đổi status khác current status mở confirmation trước PATCH; dialog không lộ id/email.
- MUTATION_PENDING: PASS — select/button/dialog controls disabled trong lúc PATCH + refetch; duplicate submit bị chặn.
- SUCCESS_REFETCH: PASS — PATCH thành công không optimistic update; GET detail authoritative trước khi thông báo success.
- LIST_SYNC: PASS — sau GET detail thành công, list refetch bằng filter/page hiện tại; không tự loại row ở client.
- ERROR_422: PASS — giữ detail/current status và map field error `status`, không retry.
- ERROR_409: PASS — giữ exact Backend code/message `LAST_ACTIVE_ADMIN_PROTECTED`, refetch detail/list, không bypass.
- ERROR_401: PASS — không logout hoặc gọi logout lần hai ở page/store; Auth/Axios infrastructure giữ authority flow.
- ERROR_403: PASS — clear selected detail, không logout, điều hướng `khongCoQuyen`.
- ERROR_404: PASS — generic unavailable, không lộ raw id/Backend detail và không retry khi resource unavailable.
- NETWORK_TIMEOUT_UNKNOWN_OUTCOME: PASS — network/5xx sau PATCH được đánh dấu outcome unknown; không blind retry mutation.
- UNKNOWN_OUTCOME_REFETCH: PASS — luôn refetch GET detail để phân loại effective/unknown outcome; nếu GET fail vẫn giữ cảnh báo unknown an toàn.
- MENU_ACTIVE_STATE: PASS — parent `Tài khoản` active qua related route `adminChiTietTaiKhoan`; detail không tạo menu item riêng.
- BREADCRUMB: PASS — metadata render `Tài khoản > Chi tiết tài khoản` bằng `tenTuyenDuong`, không render raw route id.
- LIST_DETAIL_INTEGRATION: PASS — list chỉ render named detail link khi route registered; production route đã registered và test fallback không có link/cột khi thiếu route.
- RESPONSIVE: PASS — detail cards/grid và status form có breakpoint mobile; content không phụ thuộc fixed desktop width.
- ACCESSIBILITY: PASS — H1/semantic sections, visible label, `aria-describedby`/`aria-invalid`, `aria-busy`, live loading/error/success, accessible confirmation dialog/focus behavior.
- TARGETED_TESTS: PASS — 7 test files, 155 tests PASS: API/store/detail page/list page/router/navigation/Auth cleanup.
- FULL_TEST: PASS — `npm run test`: 23 test files, 361 tests PASS.
- LINT: PASS — `npm run lint`: ESLint PASS, không warning/error.
- BUILD: PASS — `npm run build`: Vite 8.2.2, 133 modules transformed.
- DEPENDENCY: PASS — `npm ls --depth=0` PASS; không thêm package/dependency.
- DIFF_CHECK: PASS — `git diff --check` PASS.
- BROWSER_VISUAL_SMOKE: BLOCKED_TOOLING — `agent-browser doctor --offline --quick` PASS về CLI/Chrome, nhưng `agent-browser --session fe1-t04 open http://127.0.0.1:5176/admin/tai-khoan/7` thất bại với exact reason `Auto-launch failed: CDP response channel closed`; retry `--headed` vẫn `CDP response channel closed`, clean-session retry bị treo và đã dừng. Không fake browser PASS.
- NAMING_SCAN: PASS — file/function mới dùng Vietnamese không dấu snake_case/camelCase theo convention; không thêm prohibited English business handler name.
- DOCBLOCK_SCAN: PASS — service/store/page orchestration và cleanup/mutation functions có docblock input/flow/output/side effect/Backend-security boundary.
- SECURITY_SCAN: PASS — không thêm localStorage/sessionStorage cho Account, `v-html`, raw Axios error render, token/secret display, client authorization bypass hoặc role mutation.
- SCOPE_AUDIT: PASS — chỉ hoàn tất FE1-T04 Account detail + status mutation và integration cần thiết; không bắt đầu FE1-T05, không chạm Backend/Mobile/DB/FE2+.
- FILES_CREATED: `FE/src/pages/admin/tai_khoan/tai_khoan.chi_tiet.vue`, `FE/src/pages/admin/tai_khoan/tai_khoan.chi_tiet.test.js`.
- FILES_MODIFIED: `FE/src/services/tai_khoan.api.js`, `FE/src/services/tai_khoan.api.test.js`, `FE/src/stores/tai_khoan.store.js`, `FE/src/stores/tai_khoan.store.test.js`, `FE/src/pages/admin/tai_khoan/tai_khoan.index.vue`, `FE/src/pages/admin/tai_khoan/tai_khoan.index.test.js`, `FE/src/router/index.js`, `FE/src/router/index.test.js`, `FE/src/stores/xac_thuc.store.js`, `FE/src/stores/xac_thuc.store.test.js`, `FE/src/assets/main.css`, `docs/VUE_WEB_COMPLETION_CHECKPOINT.md`.
- BACKEND_MODIFIED: NO — chỉ đọc contract/source/test.
- MOBILE_MODIFIED: NO.
- DATABASE_MODIFIED: NO.
- DEPENDENCIES_ADDED: NO.
- COMMIT_PUSH: NO commit, NO push.
- CHECKPOINT_UPDATED: PASS — `CURRENT_PHASE=FE-1`, `CURRENT_TASK=FE1-T04`, `STATUS=PASS`; completed đã thêm FE1-T04, `FE1_PHASE_STATUS=IN_PROGRESS`, `EXACT_NEXT_ACTION=FE1-T05`.
- TASK_END_TRANSITION: PASS — đã dừng sau FE1-T04; không bắt đầu FE1-T05.

FE1_T05_EVIDENCE:
- ENTRY_GATE: PASS — trước khi bắt đầu có CURRENT_PHASE=FE-1, CURRENT_TASK=FE1-T04, STATUS=PASS, FE0_PHASE_STATUS=PASS, FE1_PHASE_STATUS=IN_PROGRESS, completed FE0-T01..FE0-T09 + FE1-T01..FE1-T04 và EXACT_NEXT_ACTION=FE1-T05; đã chuyển task start sang CURRENT_TASK=FE1-T05, STATUS=IN_PROGRESS trước khi sửa source.
- FE0_FE1_T01_T04_CARRY_FORWARD: PASS — giữ nguyên evidence/source contract của FE0 và FE1-T01..T04; chỉ mở rộng account service/store/detail page/test cho role mutation; không làm lại T04 và không sửa Backend/Mobile/Database.
- GIT_BASELINE: PASS — worktree dirty do các task FE0/FE1 trước thuộc cùng chuỗi; không reset/restore/clean, không commit và không push; T05 chỉ audit diff liên quan trước khi kết luận.
- BACKEND_ROLE_CONTRACT_INSPECTION: PASS — đã đọc BE/routes/api.php, AccountController, ManageAccountRoleRequest, RoleManagementService, AdminActorGuard, AdminAccountRoleApiTest và docs/BACKEND_API_CONTRACT.md; actual Backend là authority.
- BACKEND_PUT_ROLE_CONTRACT: PASS — PUT /api/admin/accounts/{id}/roles/{MEMBER|PT|RECEPTIONIST|ADMIN} qua auth:api + role:ADMIN, request body không có field cần gửi; response envelope { data: roleResult }.
- BACKEND_DELETE_ROLE_CONTRACT: PASS — DELETE /api/admin/accounts/{id}/roles/{MEMBER|PT|RECEPTIONIST|ADMIN} cùng middleware, không body; revoke là UPDATE thu_hoi_luc, không hard-delete.
- ROLE_ENUM: PASS — exact allow-list MEMBER, PT, RECEPTIONIST, ADMIN; không thêm Free/Premium/OWNER.
- ROLE_DTO_STATE_SEMANTICS: PASS — role detail giữ assignment_id, code, name, active, granted_at, revoked_at; active và revoked cùng assignment history được render.
- ROLE_GRANT_SEMANTICS: PASS — absent assignment dùng PUT và Backend transition GRANTED; không tạo profile/account từ Frontend.
- ROLE_REGRANT_SEMANTICS: PASS — revoked assignment dùng cùng PUT, Backend transition REGRANTED, giữ cùng row/assignment history.
- ROLE_REVOKE_SEMANTICS: PASS — active assignment dùng DELETE, Backend transition REVOKED, update timestamp/audit ở transaction.
- TARGET_STATE_NOOP: PASS — active grant và revoked revoke bị chặn là local target-state no-op; không phát sinh mutation ngoài trạng thái cần thay đổi; Backend vẫn xử lý retry no-op UNCHANGED.
- BACKEND_RESULT_CODES: PASS — service/store giữ GRANTED, REGRANTED, REVOKED, UNCHANGED; success copy được map theo transition thật.
- BACKEND_CONFLICT_CODES: PASS — giữ exact ROLE_CONFLICT, TRAINER_PROFILE_REQUIRED, LAST_ACTIVE_ADMIN_PROTECTED; không invent code khác cho Backend response.
- LAST_ADMIN_ROLE_GUARD: PASS — UI chỉ cảnh báo và hiển thị Backend conflict; không đếm Admin hoặc bypass AdminActorGuard.
- TRAINER_PROFILE_REQUIRED: PASS — PT grant conflict hiển thị controlled copy; không gọi trainer-profile endpoint và không có nút onboarding giả.
- ROLE_SERVICE_FUNCTIONS: PASS — capVaiTro() dùng PUT và thuHoiVaiTro() dùng DELETE, validate id/role trước request, không body/header thừa.
- ACCOUNT_STORE_ROLE_EXPANSION: PASS — tai_khoan.store.js có role mutation state/actions/sequence, dùng chung selected detail/list và Auth cleanup.
- ROLE_UI: PASS — detail có role label tiếng Việt, active/revoked timestamps, select chỉ cho role absent và action theo state.
- ACTIVE_ROLE_ACTIONS: PASS — active role chỉ hiện Thu hồi vai trò; không hiện grant/regrant cho cùng active role.
- REVOKED_ROLE_ACTIONS: PASS — revoked role chỉ hiện Cấp lại vai trò; không hiện revoke lần nữa.
- GRANT_ACTION: PASS — role absent chọn từ exact enum và mở confirmation trước PUT.
- REGRANT_ACTION: PASS — revoked role mở confirmation Cấp lại vai trò trước PUT.
- REVOKE_CONFIRMATION: PASS — mọi revoke đi qua shared confirmation dialog, không lộ id/email trong dialog.
- ADMIN_REVOKE_WARNING: PASS — revoke ADMIN dùng danger presentation và cảnh báo last-active-admin do Backend bảo vệ.
- PENDING_PROTECTION: PASS — status/role controls và confirmation buttons disabled trong mutation + refetch; duplicate submit bị chặn bằng Store state.
- NO_OPTIMISTIC_ROLE_STATE: PASS — role không append/remove local trước response; detail GET authoritative sau mutation.
- SUCCESS_REFETCH: PASS — PUT/DELETE success phải GET detail trước khi commit result/success message.
- LIST_SYNCHRONIZATION: PASS — sau detail authoritative, list refetch bằng filter/page hiện tại; không tự loại row ở client.
- ROLE_CONFLICT_UX: PASS — 409 ROLE_CONFLICT refetch detail/list, giữ code exact và thông báo kiểm soát.
- TRAINER_PROFILE_REQUIRED_UX: PASS — 409 profile-required refetch detail/list, giữ role hiện tại và giải thích onboarding không thuộc màn hình.
- LAST_ADMIN_CONFLICT_UX: PASS — 409 last-admin refetch detail/list, không cập nhật optimistic và giữ quyền Backend quyết định.
- CURRENT_ADMIN_AUTHORITY_LOSS: PASS — nếu Admin tự revoke và GET sau mutation trả 403, Store giữ 403/clear selected để page đi khongCoQuyen; không coi là success giả.
- NETWORK_TIMEOUT_UNKNOWN_OUTCOME: PASS — network/timeout/5xx sau role mutation không blind retry; Store đánh dấu outcome unknown và cho phép user kiểm tra lại.
- UNKNOWN_OUTCOME_REFETCH: PASS — unknown outcome luôn refetch detail; effective state được xác nhận, old/GET fail giữ warning unknown an toàn.
- ERROR_422: PASS — giữ selected detail/current roles, map normalized error và không refetch mutation outcome.
- ERROR_401_REGRESSION: PASS — role 401 giữ normalized error, không logout lần hai; Auth/Axios infrastructure vẫn sở hữu cleanup token.
- ERROR_403: PASS — clear selected role detail, không logout; page điều hướng khongCoQuyen.
- ERROR_404: PASS — resource/assignment unavailable không lộ raw id; Backend 404 mutation clear selected, local absent revoke được chặn an toàn.
- T04_STATUS_REGRESSION: PASS — status PATCH confirmation/pending/refetch/list sync/409/401/403/404/unknown vẫn PASS trong full suite.
- AUDIT_BOUNDARY: PASS — Frontend không ghi audit; Backend RoleManagementService ghi audit cùng transaction.
- IDEMPOTENCY_BOUNDARY: PASS — PUT/DELETE role không gửi Idempotency-Key theo contract; retry semantics do target-state/no-op và Backend transaction, không thêm client key.
- RESPONSIVE: PASS — role action rows/form stack ở breakpoint mobile, không thêm fixed-width/page overflow rule; CSS dùng token/layout hiện hữu.
- ACCESSIBILITY: PASS — visible labels, native select/button, aria-busy, aria-invalid/describedby, live error/success và shared dialog focus behavior.
- TARGETED_TESTS: PASS — 3 files, 156 tests: role service contract, store mutation/concurrency/error/cleanup và detail UI/conflict/accessibility regression.
- FULL_TEST: PASS — npm test từ FE/: Vitest 4.1.11, 23 test files, 410/410 tests, không skip/todo.
- LINT: PASS — npm run lint từ FE/: ESLint không lỗi, không warning.
- BUILD: PASS — npm run build từ FE/: Vite 8.2.2, 133 modules transformed.
- NPM_LS: PASS — npm ls --depth=0; dependency tree khớp lockfile, không thêm package.
- GIT_DIFF_CHECK: PASS — git diff --check không báo whitespace error.
- BROWSER_VISUAL_SMOKE: BLOCKED_TOOLING — agent-browser doctor --offline --quick PASS về CLI/Chrome, nhưng agent-browser --session fe1-t05 open http://127.0.0.1:5176/admin/tai-khoan/7 thất bại với exact reason Auto-launch failed: CDP response channel closed; đã đóng browser session/dừng Vite, không fake browser PASS.
- PRODUCTION_ROUTES_AFTER_T05: PASS — không tạo route mới; chỉ giữ route /admin/tai-khoan/:id của T04; không có Member/Receptionist page, FE1-T06, FE2+ route.
- FILES_CREATED: NONE — T05 tái sử dụng service/store/detail page/test đã có, không tạo file production mới.
- FILES_MODIFIED: FE/src/services/tai_khoan.api.js, FE/src/services/tai_khoan.api.test.js, FE/src/stores/tai_khoan.store.js, FE/src/stores/tai_khoan.store.test.js, FE/src/pages/admin/tai_khoan/tai_khoan.chi_tiet.vue, FE/src/pages/admin/tai_khoan/tai_khoan.chi_tiet.test.js, FE/src/assets/main.css, docs/VUE_WEB_COMPLETION_CHECKPOINT.md.
- DEPENDENCIES_ADDED: NO.
- NAMING_SCAN: PASS — file/function mới hoặc mở rộng theo Vietnamese không dấu snake_case/camelCase; không thêm prohibited English business handler.
- DOCBLOCK_SCAN: PASS — service/store/page mutation/orchestration functions có mô tả input/process/result/side effect/Backend-security boundary.
- SECURITY_SCAN: PASS — không thêm local/session persistence, v-html, raw Axios error, secret/token display, client authorization bypass, trainer-profile fake API hoặc idempotency header.
- SCOPE_AUDIT: PASS — chỉ role grant/revoke/regrant + conflict UX trên Account detail và regression tests/CSS; không bắt đầu FE1-T06.
- PLAN_BACKEND_MISMATCH: NONE — contract actual khớp V2; không sửa Backend.
- BACKEND_MODIFIED: NO.
- MOBILE_MODIFIED: NO.
- DATABASE_MODIFIED: NO.
- COMMIT_CREATED: NO.
- PUSH_PERFORMED: NO.
- CHECKPOINT_UPDATED: PASS — CURRENT_PHASE=FE-1, CURRENT_TASK=FE1-T05, STATUS=PASS; completed đã thêm FE1-T05, FE0_PHASE_STATUS=PASS, FE1_PHASE_STATUS=IN_PROGRESS, COMPLETED_PHASES chỉ gồm FE-0.
- FE1_PHASE_STATUS: IN_PROGRESS — FE-1 chưa hoàn tất; FE1-T01..T05 đã PASS, các task sau chưa bắt đầu.
- TASK_END_TRANSITION: PASS — giữ CURRENT_TASK=FE1-T05, chuyển STATUS=PASS, cập nhật EXACT_NEXT_ACTION=FE1-T06; đã dừng và không bắt đầu FE1-T06.

============================================================
FE1_T06_EVIDENCE
============================================================

- ENTRY_GATE: PASS — trước khi bắt đầu có CURRENT_PHASE=FE-1, STATUS=PASS của FE1-T05, COMPLETED_TASKS gồm FE0-T01..FE0-T09 và FE1-T01..FE1-T05, EXACT_NEXT_ACTION=FE1-T06; không yêu cầu CURRENT_TASK đã là FE1-T06 trước khi start.
- TASK_START_TRANSITION: PASS — đã chuyển CURRENT_TASK=FE1-T06, STATUS=IN_PROGRESS trước khi sửa source; không thêm FE1-T06 vào COMPLETED_TASKS trong lúc triển khai.
- BACKEND_MEMBER_ACCOUNT_CONTRACT: PASS — tái sử dụng GET /api/admin/accounts và GET /api/admin/accounts/{account}; Admin auth/role contract, allow-list và DTO đã đối chiếu với Backend source/contract.
- MEMBER_LIST_ROUTE: PASS — /admin/hoi-vien, route name adminHoiVien, page hoi_vien.index.vue.
- MEMBER_DETAIL_ROUTE: PASS — /admin/hoi-vien/:id, route name adminChiTietHoiVien, page hoi_vien.chi_tiet.vue.
- FIXED_MEMBER_ROLE: PASS — service wrapper luôn ép role=MEMBER; không có role selector hoặc branch selector.
- MEMBER_LIST_QUERY: PASS — chỉ truyền search, status, page, per_page; role/branch từ caller bị loại bỏ.
- MEMBER_DETAIL_QUERY: PASS — detail dùng Account detail endpoint, không thêm query ngoài id.
- ACCOUNT_STORE_REUSE: PASS — dùng tai_khoan.api.js và tai_khoan.store.js; không tạo hoi_vien.api.js hoặc hoi_vien.store.js.
- MEMBER_STATE_ISOLATION: PASS — state/action Member tách khỏi Account list/detail/mutation state hiện có.
- SEARCH: PASS — lọc theo search được áp dụng và reset đúng scope.
- STATUS_FILTER: PASS — chỉ dùng status Backend hỗ trợ; không suy diễn filter nghiệp vụ khác.
- PAGINATION: PASS — current_page, per_page, total, last_page và chuyển trang được xử lý trong scoped Member state.
- FILTER_RESET: PASS — reset draft/applied filter và tải lại từ trang đầu.
- MEMBER_DTO_FIELDS: PASS — chỉ hiển thị các field Account/member cơ bản được Backend cung cấp.
- MEMBER_PROFILE_STATE: PASS — member_profile null được xử lý an toàn, không crash và có text fallback.
- NO_MEMBERSHIP_INFERENCE: PASS — không gọi/hiển thị Membership hoặc entitlement.
- NO_ENTITLEMENT_INFERENCE: PASS — không suy diễn quyền lợi trả phí từ Account/member DTO.
- NO_PT_API: PASS — không gọi PT/trainer API và không hiển thị trainer_profile.
- NO_MEMBER_SELF_API: PASS — không gọi member-self API.
- READ_ONLY_BOUNDARY: PASS — không thêm create, status PATCH, role PUT/DELETE hay mutation trên hai page.
- LOADING: PASS — list/detail có trạng thái tải ban đầu và tải lại.
- EMPTY: PASS — phân biệt “Chưa có Hội viên.” và “Không tìm thấy Hội viên phù hợp.”.
- ERROR_422: PASS — hiển thị lỗi validation, không retry tự động và không hiển thị raw unsafe payload.
- ERROR_401: PASS — normalized error được hiển thị; không tự logout trong page.
- ERROR_403: PASS — xóa scoped Member state và chuyển named route khongCoQuyen.
- ERROR_404: PASS — detail fail-closed với thông báo an toàn, không hiển thị raw backend message.
- ERROR_5XX_NETWORK: PASS — hiển thị lỗi và cho phép retry thủ công.
- REQUEST_RACE_LIST: PASS — sequence guard chống response cũ ghi đè list mới.
- REQUEST_RACE_DETAIL: PASS — sequence guard chống response detail cũ ghi đè id hiện tại.
- STORE_CLEANUP: PASS — xoaDuLieuHoiVien và Auth cleanup xóa đúng scoped state, giữ nguyên Account state khi cleanup scoped.
- MENU_ACTIVATION: PASS — menu Hội viên hoạt động sau khi route adminHoiVien đăng ký; detail không hiện như menu item.
- BREADCRUMB: PASS — detail có breadcrumb link named route về adminHoiVien.
- RESPONSIVE: PASS — CSS có breakpoint cho layout/table ở <=1023px và <=639px, không thêm fixed-width overflow.
- ACCESSIBILITY: PASS — dùng shared loading/error/table/form components và named links; có trạng thái/label cần thiết.
- T03_T05_REGRESSION: PASS — full suite giữ nguyên các kiểm thử Account T03-T05.
- TARGETED_TESTS: PASS — 6 test files, 165 tests.
- FULL_TEST: PASS — npm run test: 25 test files, 438 tests.
- LINT: PASS — npm run lint không warning/error.
- BUILD: PASS — npm run build thành công, Vite 8.2.2, 135 modules transformed.
- DEPENDENCY: PASS — npm ls --depth=0 tại FE; không có dependency mới.
- DIFF_CHECK: PASS — git diff --check không có lỗi.
- BROWSER_VISUAL_SMOKE: BLOCKED_TOOLING — agent-browser doctor PASS nhưng mở http://127.0.0.1:5177/admin/hoi-vien thất bại với “Auto-launch failed: CDP response channel closed”; không phải source-code failure.
- NAMING_SCAN: PASS — không phát hiện prohibited business handler names trong T06 pages; các route/framework/API string là ngoại lệ hợp lệ.
- DOCBLOCK_SCAN: PASS — service/store/page orchestration và business actions có docblock theo boundary đã yêu cầu.
- SECURITY_SCAN: PASS — không thêm v-html, local/session persistence, raw secret/token display, Authorization/Bearer handling, hoặc client authorization bypass.
- SCOPE_AUDIT: PASS — chỉ FE1-T06; không bắt đầu FE1-T07, FE2+, Backend, Mobile, Database.
- FILES_CREATED: FE/src/pages/admin/hoi_vien/hoi_vien.index.vue; FE/src/pages/admin/hoi_vien/hoi_vien.index.test.js; FE/src/pages/admin/hoi_vien/hoi_vien.chi_tiet.vue; FE/src/pages/admin/hoi_vien/hoi_vien.chi_tiet.test.js.
- FILES_MODIFIED: FE/src/services/tai_khoan.api.js; FE/src/services/tai_khoan.api.test.js; FE/src/stores/tai_khoan.store.js; FE/src/stores/tai_khoan.store.test.js; FE/src/router/index.js; FE/src/router/index.test.js; FE/src/router/dieu_huong_admin.test.js; FE/src/assets/main.css; docs/VUE_WEB_COMPLETION_CHECKPOINT.md.
- BACKEND_MODIFIED: NO.
- MOBILE_MODIFIED: NO.
- DATABASE_MODIFIED: NO.
- DEPENDENCIES_ADDED: NO.
- COMMIT_CREATED: NO.
- PUSH_PERFORMED: NO.
- CHECKPOINT_UPDATED: PASS — CURRENT_PHASE=FE-1, CURRENT_TASK=FE1-T06, STATUS=PASS; completed đã thêm FE1-T06; EXACT_NEXT_ACTION=FE1-T07.
- FE1_PHASE_STATUS: IN_PROGRESS — FE-1 chưa hoàn tất; FE1-T01..T06 đã PASS, các task sau chưa bắt đầu.
- TASK_END_TRANSITION: PASS — giữ CURRENT_TASK=FE1-T06, chuyển STATUS=PASS, cập nhật EXACT_NEXT_ACTION=FE1-T07; đã dừng trước FE1-T07.

============================================================
FE1_T07_EVIDENCE
============================================================

- ENTRY_GATE: PASS — trước khi bắt đầu có CURRENT_PHASE=FE-1, STATUS=PASS của FE1-T06, COMPLETED_TASKS gồm FE0-T01..FE0-T09 và FE1-T01..FE1-T06, EXACT_NEXT_ACTION=FE1-T07; không yêu cầu CURRENT_TASK đã là FE1-T07 trước khi start.
- TASK_START_TRANSITION: PASS — đã chuyển CURRENT_TASK=FE1-T07, STATUS=IN_PROGRESS trước khi sửa source; giữ nguyên completed đến khi toàn bộ T07 acceptance pass.
- FE0_FE1_T01_T06_CARRY_FORWARD: PASS — giữ nguyên source/evidence T01-T06; không làm lại T02, không sửa evidence lịch sử và không bắt đầu T08.
- GIT_BASELINE: PASS — worktree dirty do các task trước thuộc cùng chuỗi; không reset/restore/clean, không commit và không push.
- BACKEND_RECEPTIONIST_CONTRACT: PASS — đã đối chiếu routes, ListAccountsRequest, AccountController, AccountManagementService, RoleManagementService, AdminActorGuard, AdminAccountRoleApiTest và BACKEND_API_CONTRACT.md; Receptionist dùng generic Admin Account contract.
- BACKEND_RECEPTIONIST_LIST_CONTRACT: PASS — GET /api/admin/accounts với allow-list search/status/role/page/per_page, middleware auth:api + role:ADMIN và DTO Account an toàn.
- BACKEND_ACCOUNT_DETAIL_CONTRACT: PASS — GET /api/admin/accounts/{account}, không có endpoint/profile riêng cho Receptionist; FE chỉ render DTO Account được Backend trả.
- BACKEND_STATUS_CONTRACT: PASS — PATCH /api/admin/accounts/{account}/status, body exact `{ status }`, status enum HOAT_DONG/BI_KHOA/NGUNG_HOAT_DONG; no-op/transaction/last-admin do Backend quyết định.
- BACKEND_RECEPTIONIST_ROLE_CONTRACT: PASS — DELETE /api/admin/accounts/{id}/roles/RECEPTIONIST, không body/header; revoke assignment history và audit do Backend transaction.
- RECEPTIONIST_ROLE_FILTER_SEMANTICS: PASS — role=RECEPTIONIST của list là active assignment only (`whereNull(thu_hoi_luc)`); Store còn fail-closed detail bằng `active === true`, không dùng assignment history revoked làm scope active.
- RECEPTIONIST_LIST_ROUTE: PASS — `/admin/nhan-vien-le-tan`, named route `adminNhanVienLeTan`, file `FE/src/pages/admin/nhan_vien_le_tan/nhan_vien_le_tan.index.vue`.
- RECEPTIONIST_DETAIL_ROUTE: PASS — `/admin/nhan-vien-le-tan/:id`, named route `adminChiTietNhanVienLeTan`, file `FE/src/pages/admin/nhan_vien_le_tan/nhan_vien_le_tan.chi_tiet.vue`.
- ROUTE_META: PASS — Admin auth/role meta, Vietnamese title, Receptionist breadcrumb; list is navigation item and detail is not.
- FIXED_RECEPTIONIST_ROLE: PASS — service wrapper always sends role=RECEPTIONIST; no role selector, branch selector or caller override.
- LIST_QUERY: PASS — only search/status/page/per_page are forwarded; role, branch and authority fields are removed at service/store boundary.
- DETAIL_QUERY: PASS — detail wrapper calls `/admin/accounts/{id}` only, with no role query or profile endpoint.
- ACCOUNT_STORE_REUSE: PASS — reused `tai_khoan.api.js` and `tai_khoan.store.js`; no `nhan_vien_le_tan.api.js` or `nhan_vien_le_tan.store.js`.
- RECEPTIONIST_STATE_ISOLATION: PASS — list/detail/filter/loading/error/mutation state is separate from Account and Member state; tests verify scoped cleanup and mutations do not overwrite other views.
- NO_RECEPTIONIST_PROFILE: PASS — no shift, desk/counter, employee code or invented Receptionist profile is displayed or requested; only Account + active role.
- SEARCH: PASS — search input is Vietnamese, maxlength 150, applied via fixed-role GET and scoped reset.
- STATUS_FILTER: PASS — exact Backend status enum only: HOAT_DONG, BI_KHOA, NGUNG_HOAT_DONG.
- PAGINATION: PASS — server pagination stores current_page/per_page/total/last_page and rejects out-of-range page without client-side total inference.
- FILTER_RESET: PASS — reset clears draft/applied scoped filter and reloads page 1 while preserving fixed role.
- LIST_FIELDS: PASS — list renders safe Account fields id/name/email/phone/branch/status and fixed Receptionist role badge; no profile secret/raw payload.
- DETAIL_DTO: PASS — detail renders safe name/email/phone/avatar-independent identity, account status, branch, email verification, login, created/updated timestamps and role state; no password/token/audit/raw auth.
- DETAIL_ORIENTATION: PASS — detail commits only DTO with active RECEPTIONIST role; missing/revoked role becomes generic 404/unavailable and selected scoped data is cleared.
- STATUS_MUTATION: PASS — scoped wrapper reuses generic Account PATCH semantics, exact `{ status }`, no optimistic update, detail authoritative refetch then list refetch.
- STATUS_CONFIRMATION: PASS — status change requires shared HopThoaiXacNhan confirmation; pending controls are disabled and dialog contains no sensitive id/email.
- STATUS_SUCCESS_REFETCH: PASS — successful PATCH refetches scoped detail then current Receptionist list before success announcement.
- STATUS_UNKNOWN_OUTCOME: PASS — timeout/network/5xx performs GET reconciliation without blind PATCH retry; matching authoritative status becomes confirmed, otherwise unknown warning is preserved.
- RECEPTIONIST_ROLE_REVOKE: PASS — scoped action always calls DELETE role `RECEPTIONIST`; no body and no Idempotency-Key.
- ROLE_REVOKE_CONFIRMATION: PASS — shared confirmation text is `Thu hồi vai trò Nhân viên lễ tân`; action is danger-styled and pending-protected.
- ROLE_REVOKE_SUCCESS: PASS — DELETE result is validated, detail/list are refetched authoritatively, and success result/message is stored only after reconciliation.
- POST_REVOKE_SCOPE_EXIT: PASS — when detail no longer has active Receptionist role, selected scoped detail is cleared and page navigates to named `adminNhanVienLeTan`; no stale active screen remains.
- NO_REGRANT_DUPLICATION: PASS — Receptionist detail has no grant/regrant control and no duplicate role-management UI.
- NO_GRANT_DUPLICATION: PASS — no Receptionist-specific PUT/grant action is exposed; generic Account/T05 grant remains outside this page.
- NO_IDEMPOTENCY_KEY: PASS — status/role API calls and tests show no Idempotency-Key injection; DELETE is exact path-only contract.
- ERROR_422: PASS — validation is normalized, field/status error is shown, no automatic retry and selected DTO is preserved.
- ERROR_401: PASS — normalized 401 is shown without page-level logout; Auth/Axios owns session cleanup.
- ERROR_403: PASS — scoped Receptionist state is cleared and named `khongCoQuyen` navigation occurs, without fake success/logout duplication.
- ERROR_404: PASS — resource/orientation unavailable renders generic `Không thể truy cập dữ liệu này.` without raw id/message and no retry control.
- ERROR_409: PASS — exact Backend code, including `LAST_ACTIVE_ADMIN_PROTECTED` when returned, is retained after authoritative detail/list refetch; no client Admin count.
- ERROR_5XX_NETWORK: PASS — list/detail and mutations expose retry/reconciliation state for 5xx/network; no blind mutation retry.
- REQUEST_RACE_LIST: PASS — Receptionist list sequence guard ensures stale response cannot overwrite newer filter/page.
- REQUEST_RACE_DETAIL: PASS — Receptionist detail sequence guard ensures A→B navigation keeps only response B.
- STORE_CLEANUP: PASS — `xoaDuLieuNhanVienLeTan` clears Receptionist data/sequences; Auth cleanup through `xoaDuLieu` clears it while scoped cleanup preserves Account/Member state.
- MENU_ACTIVATION: PASS — existing admin menu automatically activates the fourth registered item `Nhân viên lễ tân`; detail stays related route only.
- BREADCRUMB: PASS — list/detail route metadata uses `Nhân viên lễ tân` and named parent route `adminNhanVienLeTan`.
- T03_T06_REGRESSION: PASS — full suite preserves Account/API/Auth/T03-T05 and Member/T06 behavior.
- RESPONSIVE: PASS — T07 list/table uses scroll container and detail/filter/form grids stack at <=1023px/<=639px without page fixed-width overflow.
- ACCESSIBILITY: PASS — shared title/filter/table/pagination/status/error/dialog/notification components, visible labels, aria-busy/invalid/describedby/live regions and named links are used.
- TARGETED_TESTS: PASS — 6 files, 163 tests PASS, including API wrapper, scoped store/race/mutation, list/detail page and router/menu.
- FULL_TEST: PASS — `npm run test` from FE: Vitest 4.1.11, 27 test files, 455 tests PASS.
- LINT: PASS — `npm run lint` from FE: ESLint PASS, no warnings/errors.
- BUILD: PASS — `npm run build` from FE: Vite 8.2.2, 137 modules transformed successfully.
- DEPENDENCY: PASS — `npm ls --depth=0` PASS; no dependency added and package files unchanged by T07.
- DIFF_CHECK: PASS — `git diff --check` PASS.
- BROWSER_VISUAL_SMOKE: BLOCKED_TOOLING — `agent-browser doctor --offline --quick` PASS, but `agent-browser --session fe1-t07 open http://127.0.0.1:5178/admin/nhan-vien-le-tan` failed with exact `Auto-launch failed: CDP response channel closed`; browser session and Vite process were closed/stopped, no visual PASS was claimed.
- NAMING_SCAN: PASS — new T07 files/functions use Vietnamese no-accent snake_case/camelCase conventions; required handlers include `taiDanhSachNhanVienLeTan`, `apDungBoLocNhanVienLeTan`, `datLaiBoLocNhanVienLeTan`, `chuyenTrangNhanVienLeTan`, `taiChiTietNhanVienLeTan`, `capNhatTrangThaiNhanVienLeTan`, `thuHoiVaiTroLeTan`, `xuLyMoChiTietNhanVienLeTan`, `thuLaiDanhSachNhanVienLeTan`, `thuLaiChiTietNhanVienLeTan`; no prohibited English business handler introduced.
- DOCBLOCK_SCAN: PASS — service/store/page business functions document input, flow, result, side effect and Backend/security boundary.
- SECURITY_SCAN: PASS — no `localStorage`, `v-html`, sensitive console logging, raw token/password/audit rendering, client authorization bypass or secret introduced in T07 production pages.
- SCOPE_AUDIT: PASS — only FE1-T07 Receptionist Account-oriented list/detail, router/menu integration, tests/CSS/checkpoint; no FE1-T08, FE2+, Backend, Mobile or Database work.
- PLAN_BACKEND_MISMATCH: NONE — actual Backend contract matches implementation; no Backend change was needed.
- BACKEND_MODIFIED: NO.
- MOBILE_MODIFIED: NO.
- DATABASE_MODIFIED: NO.
- COMMIT_CREATED: NO.
- PUSH_PERFORMED: NO.
- CHECKPOINT_UPDATED: PASS — top checkpoint is `CURRENT_PHASE=FE-1`, `CURRENT_TASK=FE1-T07`, `STATUS=PASS`; completed includes FE1-T07 and `EXACT_NEXT_ACTION=FE1-T08 — Full FE-1 gate`; historical T06 evidence was not changed.
- FE1_PHASE_STATUS: IN_PROGRESS — FE-1 is not complete; FE1-T01..T07 PASS, FE1-T08 remains next.
- TASK_END_TRANSITION: PASS — kept CURRENT_TASK=FE1-T07, changed STATUS to PASS, added FE1-T07 to completed and stopped before FE1-T08.

============================================================
FE1_FINAL_ROUTE_MATRIX
============================================================

- adminBangDieuKhien: PASS
- adminTaiKhoan: PASS
- adminChiTietTaiKhoan: PASS
- adminHoiVien: PASS
- adminChiTietHoiVien: PASS
- adminNhanVienLeTan: PASS
- adminChiTietNhanVienLeTan: PASS
- NO_FE2_ROUTES: PASS

============================================================
FE1_FINAL_ACCEPTANCE
============================================================

- T01_ADMIN_NAVIGATION=PASS
- T02_DASHBOARD=PASS
- T03_ACCOUNT_LIST=PASS
- T04_ACCOUNT_DETAIL_STATUS=PASS
- T05_ROLE_MANAGEMENT=PASS
- T06_MEMBER_ACCOUNT_VIEW=PASS
- T07_RECEPTIONIST_ACCOUNT_VIEW=PASS
- AUTH_REGRESSION=PASS
- ROUTER_GUARD=PASS
- MENU=PASS
- BREADCRUMB=PASS
- DASHBOARD_CONTRACT=PASS
- ACCOUNT_QUERY=PASS
- ACCOUNT_MUTATIONS=PASS
- ROLE_MUTATIONS=PASS
- MEMBER_SCOPE=PASS
- RECEPTIONIST_SCOPE=PASS
- REQUEST_RACE=PASS
- STORE_CLEANUP=PASS
- ERROR_HANDLING=PASS
- SECURITY=PASS
- NAMING=PASS
- DOCBLOCK=PASS
- UI_LANGUAGE=PASS
- ACCESSIBILITY=PASS
- RESPONSIVE=PASS
- DEPENDENCY=PASS
- TEST=PASS
- LINT=PASS
- BUILD=PASS
- DIFF_CHECK=PASS
- SCOPE=PASS

============================================================
FE1_T08_EVIDENCE
============================================================

- ENTRY_GATE: PASS — CURRENT_PHASE=FE-1, status trước task của FE1-T07=PASS, COMPLETED_TASKS gồm FE0-T01..FE0-T09 và FE1-T01..FE1-T07, EXACT_NEXT_ACTION=FE1-T08; không yêu cầu CURRENT_TASK đã là FE1-T08 trước khi bắt đầu.
- TASK_START_TRANSITION: PASS — đã chuyển CURRENT_TASK=FE1-T08, STATUS=IN_PROGRESS trước final audit; giữ nguyên completed tasks cho đến khi toàn bộ acceptance criteria PASS.
- SOURCE_AUDIT: PASS — actual FE source, router, menu, services, stores, pages, tests và Backend routes đã được đối chiếu với PROJECT_RULES.md và VUE_WEB_IMPLEMENTATION_PLAN_V2.md.
- CHECKPOINT_AUDIT: PASS — checkpoint hiện hành nhất quán với FE-1 final gate; không sửa evidence lịch sử FE0 hoặc FE1-T01..T07.
- FE1_ROUTE_INVENTORY: PASS — bảy named Admin routes của FE-1 có production page tương ứng và route meta/guard hợp lệ.
- NO_FE2_ROUTE_AUDIT: PASS — không có route production thuộc FE2/FE3/FE4 trong FE router hoặc Admin menu.
- T01_NAVIGATION_GATE: PASS — Admin navigation, route meta, layout, active menu và breadcrumb hoạt động theo named route identity.
- T02_DASHBOARD_GATE: PASS — dashboard dùng GET /admin/dashboard, allow-list DTO, period validation, loading/empty/error/retry và không suy diễn revenue/branch authority.
- T03_ACCOUNT_LIST_GATE: PASS — Account list dùng query allow-list search/status/role/page/per_page, server pagination và sequence guard.
- T04_ACCOUNT_DETAIL_STATUS_GATE: PASS — detail dùng Account DTO; status PATCH exact `{ status }`, confirmation, authoritative refetch và safe error states.
- T05_ROLE_MANAGEMENT_GATE: PASS — role PUT/DELETE exact endpoint/body contract, confirmation, no idempotency header và authoritative reconciliation.
- T06_MEMBER_GATE: PASS — Member là fixed role=MEMBER Account view, read-only, không gọi Member-self/PT/Membership/Workout API.
- T07_RECEPTIONIST_GATE: PASS — Receptionist là fixed role=RECEPTIONIST Account view, active-role semantics, revoke/scope-exit và không có grant/regrant/profile suy diễn.
- AUTH_REGRESSION: PASS — auth restore, late old-token 401, transient /me failure và role-loss cleanup giữ đúng fail-closed behavior.
- GUARD_REGRESSION: PASS — actor phải khớp role route; actor null về selector; không auto-priority multi-role và không logout trùng.
- MENU_GATE: PASS — production Admin menu đúng bốn item FE-1, không chứa FE2+.
- BREADCRUMB_GATE: PASS — breadcrumb chỉ đi qua named registered ancestor routes, không gọi API/store và detail links về list đúng scope.
- STORE_STATE_ISOLATION: PASS — Account, Member và Receptionist state/filter/detail/mutation state tách biệt; scoped refetch không ghi đè scope khác.
- STORE_CLEANUP: PASS — logout/role-loss và scoped cleanup xóa đúng state, không để stale selected detail hoặc mutation state.
- REQUEST_RACE: PASS — list/detail sequence guards ngăn response cũ ghi đè filter/page/id mới.
- UNKNOWN_MUTATION_OUTCOME: PASS — timeout/network/5xx của mutation reconciliation bằng GET, không blind retry và không báo success giả.
- ERROR_HANDLING: PASS — 401/403/404/409/422/5xx/network được normalized và hiển thị safe; retry chỉ ở read/reconciliation phù hợp.
- API_CLIENT: PASS — một Axios client, Bearer request snapshot, normalized error và không global Idempotency-Key.
- API_ENDPOINT_AUDIT: PASS — endpoint/method/body/query của FE-1 khớp `BE/routes/api.php`; không cần sửa Backend.
- BRANCH_AUTHORITY: PASS — không thêm branch selector hoặc client branch authorization; Backend vẫn là authority.
- ROLE_SEMANTICS: PASS — dùng exact Backend status enum và fixed MEMBER/RECEPTIONIST active assignment semantics.
- MEMBER_BOUNDARY: PASS — không hiển thị Membership/entitlement/trainer profile hoặc dữ liệu ngoài Account/member DTO.
- RECEPTIONIST_BOUNDARY: PASS — không hiển thị shift/desk/employee profile và không tạo Receptionist-specific contract ngoài Account.
- FE2_BOUNDARY: PASS — không triển khai PT list hay bất kỳ FE2 route/API/page nào; dừng trước FE2-T01.
- NAMING_SCAN: PASS — production FE-1 functions/handlers tuân thủ Vietnamese no-accent camelCase; file names snake_case; không có prohibited business handler.
- DOCBLOCK_SCAN: PASS — service/store/page orchestration và business actions có docblock về input, flow, result, side effect và boundary.
- UI_LANGUAGE_SCAN: PASS — UI strings FE-1 là tiếng Việt; không thêm text placeholder tiếng Anh trong production scope.
- SECURITY_SCAN: PASS — không có localStorage, v-html, raw token/password/audit rendering, sensitive logging, secret hoặc client authorization bypass.
- ACCESSIBILITY_AUDIT: PASS — semantic headings/tables/forms, labels, aria states, live regions, dialog focus trap/return và keyboard drawer behavior.
- RESPONSIVE_AUDIT: PASS — layout/table/filter/detail responsive tại breakpoints <=1023px và <=639px, không thêm fixed-width page overflow.
- TEST_INVENTORY: PASS — 27 test files, không có skip/todo/fixme trong test files; coverage gồm routes, menu, pages, stores, services và auth regression.
- FULL_TEST: PASS — `npm run test` tại FE: Vitest 4.1.11, 27 test files, 455 tests PASS.
- LINT: PASS — `npm run lint` tại FE PASS, không warning/error.
- BUILD: PASS — `npm run build` tại FE PASS, Vite 8.2.2, 137 modules transformed.
- DEPENDENCY: PASS — `npm ls --depth=0` PASS; package/dependency set không đổi.
- DIFF_CHECK: PASS — `git diff --check` PASS.
- BROWSER_VISUAL_SMOKE: BLOCKED_TOOLING — `agent-browser doctor --offline --quick` PASS nhưng mở `http://127.0.0.1:5179/admin/bang-dieu-khien` thất bại với exact `Auto-launch failed: CDP response channel closed`; browser session đã close và Vite PID 23068 đã stop; theo acceptance policy không hạ verdict khi automated/static/build gates PASS.
- README_AUDIT: PASS — đã sửa claim stale trong `FE/README.md` để phản ánh FE-0 + FE-1 Admin Foundation và nêu rõ FE2+ chưa triển khai; không thay đổi source scope.
- BACKEND_MOBILE_DATABASE_SCOPE: PASS — BE git status/diff không đổi; Mobile git status/diff không đổi; không có thư mục Database trong workspace (`Test-Path Database=False`) và không có Database change.
- GIT_SCOPE: PASS — chỉ giữ các thay đổi FE/docs thuộc chuỗi FE-0/FE-1; không reset/restore/clean và không chạm FE2/Backend/Mobile/Database.
- FILES_CREATED: NONE trong FE1-T08; các file FE-1 đã được ghi nhận ở evidence T01..T07.
- FILES_MODIFIED: `FE/README.md` — sửa documentation stale; `docs/VUE_WEB_COMPLETION_CHECKPOINT.md` — cập nhật final gate và evidence T08.
- FINDINGS: stale scope statement trong README; browser visual smoke bị chặn bởi CDP tooling.
- FINDINGS_FIXED: PASS — README đã phản ánh đúng phạm vi hiện hành; browser limitation được ghi nhận trung thực, không sửa source để né lỗi tooling.
- REMAINING_BLOCKERS: browser visual smoke BLOCKED_TOOLING; giữ nguyên 7 BACKEND_API_BLOCKER, 4 BACKEND_FIX_IN_PROGRESS và Reverb/deployment dependencies (HTTPS/CORS/WSS/queue/scheduler) cho phase sau.
- FINAL_VERDICT: PASS — FE-1 Admin Foundation đạt full acceptance gate; FE-0 và FE-1 complete, FE2 chưa bắt đầu.

============================================================
FE1_REMEDIATION_START_2026_09_04
============================================================

- TASK_START_TRANSITION: PASS — đã đổi CURRENT_TASK=FE1-REMEDIATION, STATUS=IN_PROGRESS và FE1_PHASE_STATUS=IN_PROGRESS trước khi sửa source.
- PREVIOUS_FE1_PASS_REVOKED: PASS — đã bỏ FE-1 khỏi COMPLETED_PHASES và FE1-T08 khỏi COMPLETED_TASKS; evidence cũ được giữ nguyên như lịch sử, không dùng làm kết luận hiện hành.
- REMEDIATION_SCOPE: 7 finding hậu kiểm về cleanup phiên, route/mutation race, pagination overflow, focus/retry 422, mobile drawer focus, filter draft/applied và trainer status label.
- FE2_SCOPE: NOT_STARTED — không tạo route/page/API FE2 trong đợt sửa này.

============================================================
FE1_REMEDIATION_EVIDENCE_2026_09_04
============================================================

- ENTRY_GATE: PASS — đã đọc toàn bộ `PROJECT_RULES.md` và `VUE_WEB_IMPLEMENTATION_PLAN_V2.md`; không dùng lại evidence PASS cũ để thay cho source/test audit.
- FINDING_01_GLOBAL_CLEANUP: PASS — `tai_khoan.store.xoaDuLieu()` nay xóa cả Account, Member và Receptionist; logout, đổi actor và current-session 401 đều có regression test xác nhận không còn state Receptionist.
- FINDING_02_ROUTE_MUTATION_RACE: PASS — cleanup detail tăng sequence mutation; status/role generic và status/revoke Receptionist kiểm tra sequence sau mutation, detail refetch và list refetch nên thao tác route A không thể ghi success/error/pending hoặc khởi tạo refetch vào route B.
- FINDING_03_PAGINATION_OVERFLOW: PASS — response có `current_page > last_page` chỉ được dùng để xác định corrective GET; Store gọi lại đúng một lần tại `last_page`, giữ nguyên applied filters và chỉ commit response cuối thỏa `current_page <= last_page` cho cả Account/Member/Receptionist.
- FINDING_04_ERROR_422_RETRY: PASS — list Account/Member/Receptionist và Dashboard map field errors, focus field lỗi đầu hoặc summary fallback; mutation status/role focus control tương ứng; Retry read chỉ hiện cho network/5xx hoặc lỗi response contract được kiểm soát, không hiện cho 422.
- FINDING_05_MOBILE_DRAWER_A11Y: PASS — drawer mobile đóng dùng `inert`/`aria-hidden`; khi mở cô lập topbar/content, focus control đầu, trap Tab/Shift+Tab, Escape/overlay/close trả focus về nút menu; route navigation đóng drawer không cướp focus của trang mới.
- FINDING_06_FILTER_STATE: PASS — draft list được hydrate từ applied Store filter khi mount; empty copy chỉ dựa trên filter đã apply, nên draft chưa submit không làm thay đổi thông báo dữ liệu rỗng.
- FINDING_07_TRAINER_STATUS_LABEL: PASS — `HOAT_DONG` và `NGUNG_NHAN_PHAN_CONG` được map sang nhãn tiếng Việt; enum lạ fail-safe thành `Không xác định`, không render mã raw.
- UI_LANGUAGE_FOLLOWUP: PASS — nhãn trang trí sidebar còn sót `OPERATIONS / 01` đã đổi thành `VẬN HÀNH / 01`.
- TARGETED_STORE_AUTH_TESTS: PASS — 2 files, 100 tests; gồm global cleanup, logout/actor-switch/401, corrective pagination và bốn mutation route-race cases.
- TARGETED_PAGE_LAYOUT_TESTS: PASS — 8 files, 111 tests; gồm focus 422, retry policy, applied-filter copy/hydration, trainer status mapping và keyboard drawer.
- FULL_TEST: PASS — `npm run test` tại `FE/`: Vitest 4.1.11, 27 test files, 470/470 tests PASS.
- LINT: PASS — `npm run lint` tại `FE/`: ESLint PASS, không warning/error.
- BUILD: PASS — `npm run build` tại `FE/`: Vite 8.2.2, 137 modules transformed; JS 296.60 kB (gzip 88.80 kB), CSS 48.44 kB (gzip 8.18 kB).
- DEPENDENCY: PASS — `npm ls --depth=0` PASS; không cài thêm dependency trong remediation.
- NAMING_SCAN: PASS — route/file/function nghiệp vụ giữ Vietnamese-no-accent convention; không có route segment hoặc handler tiếng Anh bị cấm; UI literal còn sót đã được chuẩn hóa.
- DOCBLOCK_SCAN: PASS — helper corrective pagination/focus và các cleanup/mutation quan trọng nêu purpose, input, flow, result, side effect cùng concurrency/security rule.
- SECURITY_SCAN: PASS — không có `v-html`, `localStorage`, secret/sensitive console log, raw token/password hoặc client-side authorization bypass trong source FE-1 remediation.
- TEST_MARKER_SCAN: PASS — không có `skip`, `todo`, `only`, `FIXME` hoặc `TODO` trong test/source FE-1 đã rà.
- SCOPE_AUDIT: PASS — không tạo route/page/API FE2, không sửa Backend, Mobile hoặc Database; chỉ sửa FE-0/FE-1 shared primitive, FE-1 pages/store/tests và checkpoint.
- DIFF_CHECK: PASS — `git diff --check` không phát hiện whitespace error.
- BROWSER_VISUAL_SMOKE: BLOCKED_TOOLING — máy hiện không có lệnh `agent-browser`, nên không chạy lại visual smoke và không tuyên bố browser PASS. Exit gate `FE1-T08` trong V2 là test/lint/build/naming/docblock; component tests vẫn xác nhận hành vi responsive/a11y liên quan.
- COMMIT_CREATED: NO.
- PUSH_PERFORMED: NO.
- CHECKPOINT_UPDATED: PASS — `STATUS=PASS`, `FE1_PHASE_STATUS=PASS`, `FE1-T08` và `FE-1` đã được khôi phục vào completed; `FE2_PHASE_STATUS=NOT_STARTED`.
- FINAL_VERDICT: PASS — bảy finding hậu kiểm đã được sửa và full FE-1 gate đạt yêu cầu; được phép bắt đầu `FE2-T01 — PT list từ Admin account filter`.

============================================================
FE2_T01_EVIDENCE_2026_09_05
============================================================

- ENTRY_GATE: PASS — đã đọc toàn bộ `PROJECT_RULES.md`, root `AGENTS.md`, brief FE2-T01 và các baseline artifact; baseline product worktree sạch, workflow `.fitness-sdd` được giữ nguyên.
- BASELINE_AUDIT: PASS — các file declared target hiện có khớp byte/hash với baseline trước task; hai page target mới ở trạng thái absent trước khi triển khai.
- API_CONTRACT: PASS — thêm wrapper `taiDanhSachHuanLuyenVien` trên GET `/api/admin/accounts`, luôn ép `role=PT`; chỉ chuyển `search/status/page/per_page`, loại role/branch/authority do caller truyền.
- STORE_SCOPE: PASS — thêm state/filter/pagination/loading/error/first-load/request-sequence riêng cho PT; mọi item phải là Account hợp lệ, có active role PT và `trainer_profile` an toàn hoặc null.
- PAGINATION: PASS — dùng server pagination và corrective request một lần khi page vượt `last_page`, giữ nguyên applied filter và không tự suy diễn tổng.
- FILTER_STATE: PASS — search/status là filter duy nhất; draft hydrate từ applied filter, reset về page 1 và retry dùng cùng applied filter/page.
- REQUEST_RACE: PASS — sequence guard ngăn response cũ hoặc response sau cleanup ghi đè PT list/state.
- CLEANUP: PASS — `xoaDuLieuHuanLuyenVien` và global `xoaDuLieu()` invalidate request, reset PT scope, không xóa nhầm Account/Member/Receptionist scope.
- ROUTE: PASS — route `/admin/huan-luyen-vien`, named `adminHuanLuyenVien`, Admin metadata và breadcrumb `Huấn luyện viên`.
- MENU: PASS — đúng một menu item `Huấn luyện viên`, nằm giữa Hội viên và Nhân viên lễ tân; không thêm menu Phân công PT.
- PAGE_BOUNDARY: PASS — trang read-only dùng Account/PT DTO an toàn; không tạo profile/onboarding/assignment/membership/chat/quota/invitation API, form, store hoặc control.
- SAFE_RENDERING: PASS — code PT, phone, branch và profile đều có fallback; profile status map exact `HOAT_DONG`, `NGUNG_NHAN_PHAN_CONG`, null và unknown, không render raw enum lạ.
- ERROR_HANDLING: PASS — 422 focus lỗi đầu và không retry; 403 clear scoped state rồi chuyển named `khongCoQuyen`; 401 để shared Auth xử lý; network/5xx/controlled response shape có retry thủ công; refresh lỗi vẫn giữ rows tốt.
- ACCESSIBILITY_RESPONSIVE: PASS — dùng shared title/filter/table/loading/error/pagination components, labels/live state/focus target; CSS có narrow-screen table overflow có chủ đích và breakpoint <=1023px/<=639px.
- TARGETED_TESTS: PASS — tại `E:\Fitness\FE`, lệnh `npm run test -- src/services/tai_khoan.api.test.js src/stores/tai_khoan.store.test.js src/pages/admin/huan_luyen_vien/huan_luyen_vien.index.test.js src/router/index.test.js src/router/dieu_huong_admin.test.js`; 5 test files, 186/186 tests PASS.
- FULL_TEST: PASS — tại `E:\Fitness\FE`, lệnh `npm run test`; 28 test files, 492/492 tests PASS.
- LINT: PASS — tại `E:\Fitness\FE`, lệnh `npm run lint`; ESLint PASS, không warning/error.
- BUILD: PASS — tại `E:\Fitness\FE`, lệnh `npm run build`; Vite 8.2.2, 138 modules transformed, build thành công.
- DIFF_CHECK: PASS — tại `E:\Fitness`, lệnh `git diff --check` không có lỗi.
- DEPENDENCY: PASS — không thêm dependency hoặc thay đổi package/lockfile.
- NAMING_DOCBLOCK: PASS — function/handler nghiệp vụ mới theo Vietnamese no-accent convention và có docblock về purpose/input/flow/output/side effect/boundary.
- SECURITY: PASS — không thêm raw secret/token/payload rendering, v-html, persistence hoặc client-side authorization bypass.
- BROWSER_VISUAL_SMOKE: NOT_RUN — không thuộc focused exit gate của brief; không tuyên bố browser PASS.
- SCOPE_AUDIT: PASS — chỉ sửa các FE/test/CSS/router/checkpoint path đã khai báo và report artifact; không có Backend/Mobile/Database change, commit, push hoặc dependency change.
- FILES_CREATED: `FE/src/pages/admin/huan_luyen_vien/huan_luyen_vien.index.vue`; `FE/src/pages/admin/huan_luyen_vien/huan_luyen_vien.index.test.js`.
- FILES_MODIFIED: `FE/src/services/tai_khoan.api.js`; `FE/src/services/tai_khoan.api.test.js`; `FE/src/stores/tai_khoan.store.js`; `FE/src/stores/tai_khoan.store.test.js`; `FE/src/router/index.js`; `FE/src/router/index.test.js`; `FE/src/router/dieu_huong_admin.js`; `FE/src/router/dieu_huong_admin.test.js`; `FE/src/assets/main.css`; `docs/VUE_WEB_COMPLETION_CHECKPOINT.md`.
- REMAINING_WORK: `FE2-T03 — PT detail account/role summary` là next action; các phần PT profile đầy đủ/onboarding/assignment vẫn chờ task hoặc Backend blocker theo kế hoạch.
- COMMIT_CREATED: NO.
- PUSH_PERFORMED: NO.
- CHECKPOINT_UPDATED: PASS — `CURRENT_PHASE=FE-2`, `CURRENT_TASK=FE2-T01`, `STATUS=PASS`, `FE2_PHASE_STATUS=IN_PROGRESS`, completed đã thêm `FE2-T01` và next action chuyển sang `FE2-T03`.
- FINAL_VERDICT: PASS — FE2-T01 Admin PT list đạt focused/full test, lint, build và diff gates; FE2 vẫn đang triển khai, chưa tuyên bố hoàn tất phase.

============================================================
FE2_T03_T04_EVIDENCE_2026_09_05
============================================================

- ENTRY_GATE: PASS — đã đọc toàn bộ `E:\Fitness\PROJECT_RULES.md`, root `AGENTS.md`, `task-2-brief.md`, `plan.md`, `task-1-review.md`; đã kiểm tra `baseline-status.txt`, `baseline-tree` và `task-2-round-0-before`. Không inspect Backend nên không đọc/chạm `BE\AGENTS.md` hoặc Backend source.
- BASELINE_AUDIT: PASS — product baseline ban đầu clean; các file có trong snapshot ngay trước Task 2 khớp trước khi sửa, các file Task 2 mới ở trạng thái absent; hunk/hành vi Task 1 được giữ nguyên.
- API_CONTRACT: PASS — thêm `taiChiTietHuanLuyenVien(taiKhoanId)` delegate duy nhất tới Account detail wrapper, safe-ID validation và đúng một GET `/admin/accounts/{id}`, không query ngoài contract.
- STORE_SCOPE: PASS — thêm state/action PT detail tách biệt; chỉ commit Account DTO hợp lệ có active role `PT` và `trainer_profile` null/object an toàn; cleanup global, route-change, 403/404/orientation mismatch và request supersession đều clear/invalidate đúng scope.
- DETAIL_ROUTE: PASS — đăng ký `/admin/huan-luyen-vien/:id`, named `adminChiTietHuanLuyenVien`, Admin meta, breadcrumb quay về `adminHuanLuyenVien`; menu PT hiện hữu nhận related detail route để active parent.
- SAFE_RENDERING: PASS — detail chỉ hiển thị allow-list Account, trạng thái Account riêng, active PT role/timestamp và tối thiểu `trainer_profile.id/code/status`; null profile, status lạ, optional contact/branch/timestamps có fallback an toàn; không render secret/assignment payload.
- BLOCKER_01: PASS — `ChanTinhNangBiChan` là component reusable, controlled title/description/code/next-action text hoặc slot, interpolation an toàn, `role=status`/live labeling; full profile introduction/specialties prefill/edit được nêu rõ là chưa sẵn sàng vì Admin trainer-profile GET còn thiếu.
- NO_MUTATION_BOUNDARY: PASS — không tạo/call Admin trainer-profile GET, PT-self profile GET, trainer-profile POST, assignment service/store/form, onboarding, resend invitation hoặc Backend change; detail không có edit/save/assignment controls.
- ERROR_HANDLING: PASS — invalid ID và 404/orientation hiển thị unavailable generic không lộ route ID/backend message; 403 clear PT scope rồi redirect named `khongCoQuyen`; 401 không page-logout; network/5xx cho retry thủ công cùng ID và giữ detail cùng ID khi phù hợp.
- REQUEST_RACE: PASS — A→B detail chỉ commit B; unmount/cleanup tăng sequence để pending response không ghi state.
- TARGETED_TESTS: PASS — tại `E:\Fitness\FE`, lệnh `npm run test -- src/components/dung_chung/chan_tinh_nang_bi_chan.test.js src/services/tai_khoan.api.test.js src/stores/tai_khoan.store.test.js src/pages/admin/huan_luyen_vien/huan_luyen_vien.chi_tiet.test.js src/router/index.test.js src/router/dieu_huong_admin.test.js`; 6 test files, 195/195 tests PASS.
- FULL_TEST: PASS — tại `E:\Fitness\FE`, lệnh `npm run test`; 30 test files, 512/512 tests PASS.
- LINT: PASS — tại `E:\Fitness\FE`, lệnh `npm run lint`; ESLint PASS, 0 errors và 0 warnings sau khi format component.
- BUILD: PASS — tại `E:\Fitness\FE`, lệnh `npm run build`; Vite 8.2.2, 140 modules transformed, build thành công.
- DEPENDENCY: PASS — tại `E:\Fitness\FE`, lệnh `npm ls --depth=0`; dependency tree hợp lệ, không thêm/chỉnh package hoặc lockfile.
- STATIC_ENDPOINT_SCAN: PASS — tại `E:\Fitness`, lệnh `rg -n "(/admin/trainers|trainer-profile|/pt/assignments)" FE/src --glob "!*.test.js"` không trả về match production (exit code 1 là kết quả no-match dự kiến).
- STATIC_SAFETY_SCAN: REVIEWED — scan `localStorage|v-html|console.(log|debug|info)` chỉ bắt chữ `localStorage` trong docblock cũ `FE/src/stores/tai_khoan.store.js:476`; không có executable use và không có match trong production files mới của Task 2.
- DIFF_CHECK: PASS — tại `E:\Fitness`, lệnh `git diff --check` không có whitespace error.
- SCOPE_AUDIT: PASS — Task 2 chỉ tạo 4 product/test files, sửa các service/store/router/test/CSS/checkpoint paths đã khai báo và report artifact; không Backend/Mobile/Database change, commit, push, merge, reset, restore, checkout, stash, clean hoặc publish.
- FILES_CREATED: `FE/src/components/dung_chung/chan_tinh_nang_bi_chan.vue`; `FE/src/components/dung_chung/chan_tinh_nang_bi_chan.test.js`; `FE/src/pages/admin/huan_luyen_vien/huan_luyen_vien.chi_tiet.vue`; `FE/src/pages/admin/huan_luyen_vien/huan_luyen_vien.chi_tiet.test.js`.
- FILES_MODIFIED: `FE/src/services/tai_khoan.api.js`; `FE/src/services/tai_khoan.api.test.js`; `FE/src/stores/tai_khoan.store.js`; `FE/src/stores/tai_khoan.store.test.js`; `FE/src/router/index.js`; `FE/src/router/index.test.js`; `FE/src/router/dieu_huong_admin.test.js`; `FE/src/assets/main.css`; `docs/VUE_WEB_COMPLETION_CHECKPOINT.md`.
- REMAINING_WORK: `FE2-T04` vẫn BLOCKED với `BLOCKER-01` cho full trainer-profile prefill/edit; FE2-T02/T06/T07 vẫn chờ Backend/task phụ thuộc. Không thêm các task này vào completed.
- COMMIT_CREATED: NO.
- PUSH_PERFORMED: NO.
- CHECKPOINT_UPDATED: PASS — `CURRENT_TASK=FE2-T03`, `STATUS=PASS`, `FE2_PHASE_STATUS=IN_PROGRESS`, completed chỉ thêm `FE2-T03`, next action chuyển sang Task 3 blocker screens và `FE2-T04` vẫn bị giữ rõ trong blocked features.
- FINAL_VERDICT: PASS_WITH_EXPLICIT_BLOCKER — FE2-T03 basic PT detail hoàn tất; FE2-T04 được thể hiện đúng bằng blocker `BLOCKER-01`, không bị tuyên bố hoàn tất hay gọi API thiếu contract.

============================================================
FE2_T05_T02_T06_T07_EVIDENCE_2026_09_05
============================================================

- ENTRY_GATE: PASS — đã đọc toàn bộ `E:\Fitness\PROJECT_RULES.md`, root `E:\Fitness\AGENTS.md`, `task-3-brief.md`, `plan.md`, `task-1-review.md`, `task-2-review.md`; đã kiểm tra baseline artifacts/tree và `task-3-round-0-before`. Không inspect Backend nên `BE\AGENTS.md` không thuộc scope áp dụng.
- BASELINE_AUDIT: PASS — mọi file pre-existing trong snapshot Task 3 byte-identical trước khi sửa; bốn target page/test mới có absent marker; hunk và hành vi Task 1/2 được giữ nguyên.
- ONBOARDING_BLOCKERS: PASS — tạo màn hình chỉ trình bày `BE-FOLLOWUP-02` về recovery/truthful invitation status và `BE-FOLLOWUP-03` về role/profile audit before/after snapshots cùng rollback/retry evidence; không có dữ liệu giả, field hay control onboarding.
- ASSIGNMENT_BLOCKER: PASS — tạo màn hình trạng thái unavailable `BLOCKER-02`; giải thích create/end/reassign chỉ mở sau Admin GET list/detail/history có initial load và unknown-outcome refetch; không render row/history/selector/control hay gọi mutation.
- ROUTES: PASS — thêm `/admin/huan-luyen-vien/tao-moi` với name `adminTaoHuanLuyenVien` trước `/:id`, và `/admin/phan-cong-pt` với name `adminPhanCongPt`; cả hai dùng Admin meta/breadcrumb, assignment không hiển thị trong menu.
- MENU: PASS — giữ đúng năm mục Dashboard, Account, Member, Trainer, Receptionist; route `adminTaoHuanLuyenVien` liên quan parent PT để active context, `adminPhanCongPt` không thuộc cấu hình menu.
- ZERO_CALL_BOUNDARY: PASS — hai page chỉ import `TieuDeTrang`/`ChanTinhNangBiChan`; raw-source assertions loại `/api/`, `ketNoiApi`, `fetch(`, service/store import, form, submit và event/mutation handler; mounted tests mock Axios methods và ghi nhận zero call.
- TARGETED_TESTS: PASS — tại `E:\Fitness\FE`, `npm run test -- src/pages/admin/huan_luyen_vien/huan_luyen_vien.tao_moi.test.js src/pages/admin/phan_cong_pt/phan_cong_pt.index.test.js src/router/index.test.js src/router/dieu_huong_admin.test.js`; 4 test files, 25/25 tests.
- FULL_TEST: PASS — tại `E:\Fitness\FE`, `npm run test`; 32 test files, 518/518 tests.
- LINT: PASS — tại `E:\Fitness\FE`, `npm run lint`; không lỗi hoặc warning.
- BUILD: PASS — tại `E:\Fitness\FE`, `npm run build`; Vite 8.2.2, 142 modules transformed.
- STATIC_ENDPOINT_SCAN: PASS — tại `E:\Fitness`, `rg -n "(/admin/trainers|trainer-profile|/pt/assignments)" FE/src --glob "!*.test.js"` không có match production; exit code 1 là no-match dự kiến.
- DIFF_CHECK: PASS — tại `E:\Fitness`, `git diff --check` không có lỗi.
- SCOPE_AUDIT: PASS — chỉ sửa bốn page/test mới, router/index.js, router/index.test.js, router/dieu_huong_admin.test.js, CSS và checkpoint; không sửa Backend/Mobile/Database, không thêm dependency, không commit/push/merge/reset/restore/checkout/stash/clean/publish.
- FILES_CREATED: `FE/src/pages/admin/huan_luyen_vien/huan_luyen_vien.tao_moi.vue`; `FE/src/pages/admin/huan_luyen_vien/huan_luyen_vien.tao_moi.test.js`; `FE/src/pages/admin/phan_cong_pt/phan_cong_pt.index.vue`; `FE/src/pages/admin/phan_cong_pt/phan_cong_pt.index.test.js`.
- FILES_MODIFIED: `FE/src/router/index.js`; `FE/src/router/index.test.js`; `FE/src/router/dieu_huong_admin.test.js`; `FE/src/assets/main.css`; `docs/VUE_WEB_COMPLETION_CHECKPOINT.md`.
- REMAINING_WORK: FE2-T02 vẫn BLOCKED bởi `BE-FOLLOWUP-02/03`; FE2-T04 vẫn BLOCKED bởi `BLOCKER-01`; FE2-T06/T07 vẫn BLOCKED bởi `BLOCKER-02`; FE2-T08 partial verification theo Task 4 là task tiếp theo và chưa hoàn tất. Chỉ FE2-T05 được thêm vào completed tasks.
- CHECKPOINT_UPDATED: PASS — `CURRENT_TASK=FE2-T05`, `STATUS=PASS`, `FE2_PHASE_STATUS=IN_PROGRESS`, completed thêm `FE2-T05`; `EXACT_NEXT_ACTION` chuyển sang FE2-T08 / Task 4 partial full gate, không gỡ các blocker T02/T04/T06/T07 và chưa đánh dấu T08 hoàn tất.
- FINAL_VERDICT: PASS_WITH_EXPLICIT_BLOCKERS — FE2-T05 blocker shell và route representation hoàn tất; T02/T04/T06/T07 vẫn được giữ blocked; FE2-T08 chỉ được bắt đầu qua partial gate của Task 4, chưa PASS hoặc hoàn tất, và FE2 phase chưa được tuyên bố hoàn tất.

============================================================
FE2_T08_PARTIAL_GATE_EVIDENCE_2026_09_05
============================================================

- ENTRY_GATE: PASS — đã đọc đầy đủ PROJECT_RULES.md, AGENTS.md áp dụng, plan.md, task-4-brief.md, các report/review T01-T03, progress.md, baseline artifacts/tree và task-4-round-0-before; đã đọc BE/AGENTS.md trước khi chạy evidence Backend.
- SOURCE_AUDIT: PASS — kiểm tra trực tiếp router, service, store, bốn route/page FE2, test tương ứng, shared blocker/accessibility components và CSS; reports chỉ được dùng làm evidence lịch sử.
- ROUTES: PASS — đủ bốn route `/admin/huan-luyen-vien`, `/admin/huan-luyen-vien/tao-moi`, `/admin/huan-luyen-vien/:id`, `/admin/phan-cong-pt`; route static `tao-moi` đứng trước `:id`, có test resolve không rơi vào detail.
- MENU: PASS — chỉ thêm đúng một mục `Huấn luyện viên`; `Tạo mới` và `Phân công PT` không hiện trong Admin menu.
- ACCOUNT_READ_BOUNDARY: PASS — PT list dùng fixed-role GET `/admin/accounts`; PT detail dùng đúng GET `/admin/accounts/{id}` và Store xác nhận active PT role/DTO an toàn; không có call `/admin/trainers`, `trainer-profile` hoặc `/pt/assignments` trong production source.
- BLOCKED_BOUNDARY: PASS — onboarding và assignment chỉ render blocker `BE-FOLLOWUP-02/03` và `BLOCKER-02`; không có form, API call, mutation control, row/history/selector giả hoặc invitation success claim.
- STORE_CLEANUP_ISOLATION: PASS — Account, Member, Receptionist và Trainer có state/sequence/cleanup scoped; global `xoaDuLieu()` gọi cleanup của cả bốn scope; logout/actor-loss cleanup và request-race regression nằm trong test suite.
- UI_A11Y_RESPONSIVE_SECURITY: PASS — naming/docblocks quan trọng, nhãn tiếng Việt, labels/ARIA/live states/focus, semantic table, fallback status, table overflow và breakpoint <=1023px/<=639px được audit; không có executable `v-html`, localStorage hoặc sensitive console logging trong phạm vi FE2.
- FOCUSED_TEST: PASS — tại `E:\Fitness\FE`, lệnh focused gồm 9 files (Account API/store, router/menu, blocker component, bốn FE2 pages) đạt 9/9 files và 212/212 tests.
- FULL_TEST: PASS — tại `E:\Fitness\FE`, `npm run test` đạt 32/32 files và 518/518 tests; Vitest 4.1.11.
- LINT: PASS — tại `E:\Fitness\FE`, `npm run lint` exit 0, không lỗi/warning.
- BUILD: PASS — tại `E:\Fitness\FE`, `npm run build` exit 0; Vite 8.2.2, 142 modules transformed.
- DEPENDENCY: PASS — `npm ls --depth=0` exit 0; dependency tree hiện hữu khớp package.json, không thêm dependency.
- TEST_MARKER_SCAN: REVIEWED — literal brief command exit 0 nhưng match 9 từ tự nhiên trong tên test (`only`, `todo`, `test-only`); guarded scan cho `describe/it/test.skip|only` và `TODO|FIXME` exit 1/no match, nên không có test bị disable hoặc marker TODO/FIXME.
- DIFF_STATUS: PASS — `git diff --check` exit 0; `git status --short` chỉ phản ánh product changes đã có từ T01-T03, checkpoint và workflow artifact; Task 4 chỉ ghi checkpoint/report, không sửa product source.
- BLOCKED_ENDPOINT_SCAN: PASS — exact FE production scan exit 1/no match cho `/admin/trainers`, `trainer-profile`, `/pt/assignments`.
- SECURITY_SCAN: REVIEWED — exact scan chỉ match `FE/src/stores/tai_khoan.store.js:476` trong docblock mô tả không dùng localStorage; không có executable use, `v-html` hoặc console log nhạy cảm.
- BACKEND_ROUTE_EVIDENCE: PASS — từ `E:\Fitness\BE`, repository runtime `E:\Fitness\.tools\php\php.exe` báo PHP 8.4.25; `& 'E:\Fitness\.tools\php\php.exe' artisan route:list --path=api/admin --json` và `& 'E:\Fitness\.tools\php\php.exe' artisan route:list --path=api/pt/assignments --json` đều exit 0. Relevant Admin routes có GET accounts, GET account detail, POST account trainer-profile và POST trainers nhưng không có trainer-profile GET, xác nhận `BLOCKER-01`. Assignment routes chỉ có POST create, PATCH end và POST reassign; không có GET list/detail/history, xác nhận `BLOCKER-02`.
- SCOPE: PASS — không sửa FE product source/tests/CSS/router, Backend, Mobile, Database, package/dependency; không commit/push/merge/reset/restore/checkout/stash/clean/publish.
- CHECKPOINT_UPDATED: PARTIALLY_READY — CURRENT_PHASE=FE-2, CURRENT_TASK=FE2-T08, STATUS=PARTIALLY_READY, FE2_PHASE_STATUS=PARTIALLY_READY; COMPLETED_TASKS vẫn chỉ gồm FE2-T01, FE2-T03, FE2-T05; FE2-T02/T04/T06/T07 blockers được giữ nguyên; không thêm T08 hoặc FE-2 vào completed.
- FINAL_VERDICT: PARTIALLY_READY — implemented T01/T03/T05 behavior has no open Important/Critical finding in the supplied reviews and final FE checks pass; route evidence is verified, but full FE2 cannot close because BE-FOLLOWUP-02/03 remain prerequisites, `BLOCKER-01` is confirmed by the missing trainer-profile GET, and `BLOCKER-02` is confirmed by the missing assignment GET list/detail/history. FE3 is forbidden in this FE-2-only workflow.

============================================================
FE2_COMPLETION_EVIDENCE_2026_09_09
============================================================

- CONSOLIDATED_SCOPE: PASS — Tasks 1-3 historical PASS evidence preserved; Task 4 `FE2-ALL` executed as one consolidated task covering former Tasks 4-8, with consolidated repair waves only.
- TASK_REVIEWS: PASS — Task 1 round 2, Task 2 round 3, Task 3 round 1 and Task 4 round 3 official reviews PASS; all earlier findings are closed.
- FINAL_REPAIR: PASS — one consolidated round-4 Sol plan and fresh Luna Max wave closed `FE2-FINAL-F-001` route/unmount ownership and `FE2-FINAL-F-002` assignment authorization/audit acceptance evidence.
- FINAL_REVIEW: PASS — `final-review-round-4.md` records `VERDICT: PASS`, `SPEC: PASS`, `QUALITY: PASS`, `FE2_CHECKPOINT_TRANSITION_PERMITTED: YES`, `OPEN_CRITICAL: 0`, `OPEN_IMPORTANT: 0`.
- BACKEND_FINAL_GATE: PASS — repository PHP only; guarded MariaDB `smart_fitness_fe2_test`; assignment API 14/14 tests with 218 assertions; assignment/concurrency/final integration 19/19 with 351 assertions; full Backend 323/323 with 3,794 assertions.
- DATABASE_PRESERVATION: PASS — MariaDB 10.4.32/InnoDB and post-suite cardinality exactly `chi_nhanh=1`, `nguoi_dung=8`, `phan_quyen_nguoi_dung=8`, `ho_so_hoi_vien=4`, `ho_so_huan_luyen_vien=2`; no production database, SQLite, migration, reset, seed, drop or truncate.
- FRONTEND_FINAL_GATE: PASS — focused trainer-profile 17/17; full Frontend 36/36 files and 571/571 tests; ESLint zero warnings/errors; production build 146 modules; dependency inventory PASS.
- QUALITY_AND_CONTRACT: PASS — Pint, Composer strict validation/audit/platform requirements, seven Admin Account routes, exactly five PT assignment routes, Backend contract and static scans PASS.
- SNAPSHOT_SCOPE: PASS — final Task 4 cumulative snapshot contains exactly 32 paths and matches live; round-4 delta is exactly six authorized paths, report prefix preserved, no non-allow-list mismatch, `git diff --check` PASS.
- BLOCKER_CLOSURE: PASS — current `BE-FOLLOWUP-02`, `BE-FOLLOWUP-03`, `BLOCKER-01`, `BLOCKER-02` claims are closed by new Backend/FE/test evidence; dated historical blocker evidence above remains unchanged and is not current truth.
- TASK_COMPLETION: PASS — `COMPLETED_TASKS` now includes FE2-T01 through FE2-T08; `COMPLETED_PHASES` now includes FE-2.
- PHASE_TRANSITION: PASS — top-level `STATUS=PASS` and `FE2_PHASE_STATUS=PASS`; `EXACT_NEXT_ACTION=FE3-ALL`.
- FINAL_REPORT: PASS — `.fitness-sdd/fe2-backend-completion/final-report.md` contains the exact 15 PHẦN XXI sections and final code-state evidence.
- STOP_BOUNDARY: PASS — FE3 was not started, scaffolded, edited or tested; workflow stops after FE2 as explicitly requested.
- GIT_BOUNDARY: PASS — no commit, push, merge, branch/worktree, reset, restore, checkout, stash or clean operation was performed.
