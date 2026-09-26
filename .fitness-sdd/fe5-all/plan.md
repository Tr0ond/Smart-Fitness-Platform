# FE5-ALL Initial Plan — Không gian làm việc PT

`PLAN_STATUS: READY_WITH_BACKEND_PREREQUISITE`  
`ENTRY_GATE: FAIL_UNTIL_TASK_1_PASS`  
`NEEDS_USER_DECISION: NO`  
`TASK_COUNT: 2`  
`TASK_1: BE-FE5-PREREQ`  
`TASK_2: FE5-ALL`

## 1. Kết quả kiểm tra và thẩm quyền

FE5 vẫn là **một task Frontend tổng hợp duy nhất** gồm đúng bảy route/màn hình. Tuy nhiên writer Frontend chưa được phép bắt đầu vì source, route list, API contract và executable Backend tests hiện tại đều thiếu ba hợp đồng bắt buộc BLOCKER-03/04/05. Vì vậy controller phải chạy tuần tự:

1. `BE-FE5-PREREQ`: bổ sung tối thiểu các API PT-scoped còn thiếu cùng executable auth/resource-scope tests.
2. `FE5-ALL`: một writer duy nhất triển khai toàn bộ bảy màn hình sau khi Task 1 và gate Backend PASS.

Không được tạo blocker page, placeholder, READY-subset writer hoặc dùng endpoint Admin/Member-self thay thế. Không có quyết định nghiệp vụ mới cần hỏi chủ dự án.

Planner đã đọc đầy đủ các instruction/context bắt buộc và kiểm tra source thực tế của Backend lẫn Frontend. Do source hiện tại mâu thuẫn với trạng thái mong muốn trong authority, planner chỉ tra cứu các phần canonical liên quan: `PROJECT_RULES.md` về Q04, Q08, RULE GYM 13/15, RULE CODE 17/19/20 và `VUE_WEB_IMPLEMENTATION_PLAN_V2.md` Section 31, 33, 38A. Kết luận canonical: ba endpoint family phải là PT-scoped, dùng assignment hiện tại theo khoảng nửa mở `[ngay_bat_dau, ngay_ket_thuc)`, trả DTO an toàn, và được bảo vệ bằng test negative; không được dùng Member-self, Admin hoặc Proposal làm nguồn dữ liệu chính thức.

Baseline controller trước scratch artifacts:

- branch `main`;
- product porcelain sạch; baseline working/staged patch rỗng;
- chỉ `.fitness-sdd/fe5-all/` là scratch area;
- mọi thay đổi ngoài exact allow-list phải được bảo toàn nguyên trạng.

## 2. Actor, use case và giới hạn phạm vi

Actor chính: Web actor đã xác thực, role `PT`, có hồ sơ PT hoạt động.

Use case:

- xem/sửa hồ sơ PT của chính mình;
- xem danh sách hội viên đang được phân công hiện tại;
- xem hồ sơ huấn luyện an toàn của hội viên được chọn;
- xem tiến độ đo cơ thể và xu hướng bài tập;
- xem kế hoạch tập **chính thức hiện tại** cùng lịch tương lai;
- xem lịch sử buổi tập đã materialize/hoàn thành ở chế độ chỉ đọc;
- đọc và thêm ghi chú PT theo mô hình append-only.

Scope Frontend đúng bảy route:

- `/pt/ho-so`
- `/pt/hoi-vien`
- `/pt/hoi-vien/:id`
- `/pt/hoi-vien/:id/tien-do`
- `/pt/hoi-vien/:id/ke-hoach-tap`
- `/pt/hoi-vien/:id/lich-su-tap`
- `/pt/hoi-vien/:id/ghi-chu`

Ngoài scope: chat realtime, proposal editor/apply, Member UI, Admin assignment UI, chỉnh sửa Plan/Session, đánh giá y khoa, BMI do client tự suy luận, upload ảnh, hard delete, schema/migration mới, UI framework/icon/motion dependency, dark mode hoặc redesign tím.

## 3. Binding rules và mô hình dữ liệu

1. Q04/RULE CODE 17: PT chỉ được truy cập resource của hội viên khi có assignment hiện tại thỏa `ngay_bat_dau <= now` và `ngay_ket_thuc IS NULL OR now < ngay_ket_thuc`. Khoảng là nửa mở; assignment tương lai, đã kết thúc, PT cũ, PT khác và hội viên chưa phân công đều không hợp lệ.
2. Frontend guard chỉ là presentation. Mỗi Backend request phải tự xác thực actor/role/resource scope. Phản hồi out-of-scope member-scoped được che bằng `404`; unauthenticated là `401`, wrong role/profile authority là `403` theo contract hiện hữu.
3. Q08: Workout Tracking là quyền cơ bản của Member, không phụ thuộc Membership có hiệu lực. Các PT read endpoint không được kích hoạt kỳ, tiêu quyền lợi hoặc yêu cầu gói còn hạn.
4. Official Workout Plan tách biệt hoàn toàn khỏi Proposal. FE5 chỉ đọc Plan chính thức; proposal chưa được Member xác nhận không được hiển thị như Plan.
5. RULE GYM 13/15: Workout Session đã hoàn thành và snapshot set/exercise là bất biến. FE5 history chỉ đọc, không có edit/delete/rewrite.
6. Ghi chú PT là append-only. POST note không được thay đổi Plan, Session, progress measurement hay lịch sử; không expose sửa/xóa.
7. PT self profile chỉ sửa trường được contract cho phép (`gioi_thieu`, `chuyen_mon`); không gửi mã PT, status, user/branch/assignment authority.
8. DB reads của FE5 đi qua `nguoi_dung`, `ho_so_huan_luyen_vien`, `ho_so_hoi_vien`, `phan_cong_huan_luyen_vien`, progress measurement/session aggregate, `ke_hoach_tap`/version/day/exercise, `buoi_tap_du_kien`, `phien_tap` snapshots và `ghi_chu_huan_luyen_vien`. FE5 không trực tiếp viết DB; write duy nhất là hồ sơ self và append-only note qua Backend service có transaction/audit hiện hữu.
9. Naming/comment FE dùng tiếng Việt không dấu camelCase; DB giữ snake_case. Không tự đổi Business Rule.

## 4. Entry-gate audit từ source thực tế

### Contract đã tồn tại

- `GET/PATCH /api/profile/trainer`: PT self profile, DTO allow-list và role guard đã có executable tests.
- `GET /api/pt/members`: danh sách assignment hiện tại; service lọc đúng start-inclusive/end-exclusive.
- `GET /api/pt/members/{member}/progress/overview|body|exercises/{exercise}`: progress read contract dùng current exact assignment và có một phần negative tests.
- `GET/POST /api/pt/members/{member}/notes`: current exact assignment, note append-only, create nằm trong transaction và ghi audit; list tối đa 100 ghi chú của assignment hiện tại.
- Existing Member-self Workout Plan/Schedule/Session query services đã có DTO an toàn có thể tái sử dụng ở tầng service, nhưng route của chúng được bảo vệ bằng role `MEMBER` và không thể dùng trực tiếp cho PT.

### Contract còn thiếu — gate FAIL

Executable `artisan route:list --path=api/pt/members --json`, `BE/routes/api.php`, controllers/services, `docs/BACKEND_API_CONTRACT.md` và tests hiện tại đều không có:

- BLOCKER-03: `GET /api/pt/members/{member}`.
- BLOCKER-04: `GET /api/pt/members/{member}/workout/plans/current` với Plan chính thức hiện tại và lịch tương lai chính thức.
- BLOCKER-05: `GET /api/pt/members/{member}/workout/sessions` và `GET /api/pt/members/{member}/workout/sessions/{session}`.

Không có executable test chứng minh đầy đủ auth, exact assignment interval và negative matrix old/foreign/unassigned/future/ended assignment cho ba contract trên. Checkpoint/report không thay thế source/test evidence.

## 5. Task 1 — Backend prerequisite tối thiểu

Task 1 chỉ bổ sung read-only PT workspace contract, không thêm migration/model/Business Rule:

- `GET /api/pt/members/{member}` trả đúng safe coaching profile allow-list và assignment hiện tại; không trả email, điện thoại, password, token, audit internals hay dữ liệu liên hệ không cần thiết.
- `GET /api/pt/members/{member}/workout/plans/current` trả `{ plan, future_schedule, schedule_window }`; `plan` tái sử dụng safe official Plan DTO hiện hữu hoặc `null`; `future_schedule` tái sử dụng safe schedule DTO cho cửa sổ server-authoritative tối đa 92 ngày từ ngày hiện tại theo project business timezone đã dùng bởi Workout service. Không dùng Proposal và không phụ thuộc Membership.
- `GET /api/pt/members/{member}/workout/sessions?before_id&limit` trả bounded cursor list DTO như Member-self contract.
- `GET /api/pt/members/{member}/workout/sessions/{session}` trả immutable snapshot detail và phải kiểm tra session thuộc đúng hội viên đã authorize.

Thiết kế tối thiểu: một `PtMemberWorkspaceController` và `PtMemberWorkspaceService` dùng `PtAssignmentScopeService` để khóa/xác thực actor, Member và assignment hiện tại trong transaction đọc ngắn; mở các query method nhận `HoSoHoiVien` trên Plan/Schedule/Session services để tái dùng mapper/DTO hiện hữu. Member-self methods phải tiếp tục hoạt động và delegate về cùng query path. Read-only calls không cần idempotency key và không tạo audit; scope được chốt tại thời điểm request. Không có write hoặc activation side effect.

Tests mới phải chứng minh:

- unauthenticated `401`, wrong role/invalid PT authority `403`;
- current PT success;
- old PT, other/foreign PT, unassigned, future assignment, ended/expired assignment đều concealed `404`;
- chính xác start boundary được phép và end boundary bị từ chối bằng frozen server time;
- session detail không thể cross-member;
- safe DTO allow-list, bounded `limit`, invalid query `422`;
- official Plan khác Proposal; response không kích hoạt Membership/quyền lợi và chỉ đọc;
- completed history snapshot không bị mutation;
- regression cho self profile, assigned list, progress và notes, bao gồm current/old/foreign/unassigned/ended và note append-only/no Plan-Session mutation.

Task 1 phải PASS focused/full Backend tests trên guarded MariaDB `smart_fitness_*test*`, Pint, Composer validate/audit/platform check, route scan và diff checks trước khi Task 2 được dispatch.

## 6. Task 2 — một aggregate FE5-ALL writer

### Router, layout và navigation

- Đăng ký đúng bảy named routes: `ptHoSo`, `ptHoiVien`, `ptChiTietHoiVien`, `ptTienDoHoiVien`, `ptKeHoachTapHoiVien`, `ptLichSuTapHoiVien`, `ptGhiChuHoiVien` dưới `bo_cuc_pt.vue`, meta auth/role PT và breadcrumbs an toàn.
- Thêm registry `dieu_huong_pt.js`; menu chính chỉ cần `Hồ sơ`, `Hội viên`, nhưng các route con phải active đúng parent.
- Cập nhật role home map `PT -> ptHoiVien`, login/actor-switch tests và PT layout. Không duplicate shared route/auth mechanisms.

### API và state

- Mở rộng `huan_luyen_vien.api.js` hiện hữu bằng tên self-profile rõ ràng; không tạo service trùng và không phá các hàm Admin hiện có.
- Tạo các service tách theo domain: member/detail, progress, official plan/history, notes; mỗi service chỉ unwrap envelope theo convention hiện tại và giữ lỗi cho store/page xử lý.
- Tạo một `hoi_vien_pt.store.js` cho assigned list, selected member, detail, progress, plan/schedule, history cursor/detail và notes. State/cache member-scoped phải keyed hoặc được reset atomically khi member đổi.
- Mỗi resource dùng request generation/AbortController-compatible cleanup theo pattern hiện có: response muộn của member A không được commit sau khi điều hướng sang B, logout, role loss hoặc unmount.
- Khi member-scoped request trả `403/404`, invalidate request generations, xóa selected member và mọi sensitive cache, refetch/giữ list an toàn rồi redirect `/pt/hoi-vien`. Nếu refresh list không còn selected ID, xử lý tương tự. `401` đi qua shared auth cleanup và không được xóa session mới do late response.
- Auth store phải gọi cleanup helper của PT member store khi logout, valid-current-token `401`, actor/role switch. Không import cycle.

### Bảy màn hình

1. Hồ sơ PT: đọc/sửa self profile, 422 field mapping, draft được giữ khi lỗi, double-submit guard.
2. Hội viên: danh sách assignment hiện tại, loading/empty/error/retry, không show dữ liệu PT khác.
3. Chi tiết: safe coaching profile + assignment, điều hướng rõ sang tiến độ/kế hoạch/lịch sử/ghi chú.
4. Tiến độ: overview + body measurements; exercise selector lấy duy nhất từ exercises trong official current Plan, dedupe theo `exercise_id`, rồi gọi progress exercise endpoint. Nếu không có Plan/exercise thì hiển thị empty state, không fabricate ID. BMI/status chỉ hiển thị giá trị Backend trả về, không chẩn đoán.
5. Kế hoạch tập: nhãn rõ `Kế hoạch chính thức`, current version/day/exercise và future schedule; không proposal controls, không mutation.
6. Lịch sử tập: cursor list, detail immutable snapshot, không edit/delete; loading/empty/error/retry ở list và detail.
7. Ghi chú: newest-first tối đa 100, append-only composer, `noi_dung` trim/validation, pending disable. Vì POST notes không quảng cáo idempotency, timeout/5xx không được blind retry; giữ draft, refetch list để reconcile và báo outcome chưa chắc chắn.

### UI/accessibility/responsive

Giữ Light Staff UI, CSS variables, system font và shared components hiện có. Workspace phải dense nhưng dễ quét, semantic landmarks/headings/table/list/form, label/error liên kết, visible focus, active tab có `aria-current`, loading/error dùng dynamic ARIA phù hợp, không chỉ dùng màu để truyền trạng thái. Kiểm tra 1440/768/390, không overflow ngang, hành động chính vẫn truy cập bằng bàn phím. Không thêm dependency, Google Font, GSAP, dark mode hoặc purple redesign.

## 7. Failure, concurrency, transaction, idempotency, audit

- `401`: shared authentication flow; store cleanup chỉ tác động đúng session/request generation hiện tại.
- `403/404` member-scoped: coi assignment/resource authority không còn hợp lệ, purge cache + redirect; không giữ dữ liệu hội viên trên màn hình.
- `422`: profile/note giữ draft, render lỗi field/global; read query invalid phải fail an toàn.
- Network/5xx read: giữ safe stale-independent UI state, hiển thị retry có chủ đích; không commit partial response.
- Network/5xx mutation: không auto-repeat profile/note. Với note phải refetch/reconcile vì operation không idempotent; với profile refetch trước khi người dùng thử lại.
- Concurrency: Backend scope check dùng server time và transaction/lock ngắn; Frontend request generations bảo vệ route A→B, unmount, logout/role switch. Assignment có thể kết thúc trong khi user đang xem; request kế tiếp 404 phải purge ngay.
- Audit: Backend hiện có audit cho note create và profile update theo contract hiện hữu; read-only workspace không tạo audit. Frontend logging không chứa token, note/profile payload hoặc PII.

## 8. Exact task ordering và dependency gates

### Gate trước Task 1

- FE0–FE4 vẫn PASS; checkpoint vẫn nêu FE5-ALL là next phase.
- Controller snapshot toàn bộ existing allowed files vào baseline/task-round-before và bảo toàn mọi user change.
- Guarded isolated MariaDB test DB đã được provision: `smart_fitness_test`, tên khớp `smart_fitness_*test*`.

### Gate Task 1 -> Task 2

Task 2 chỉ được dispatch khi:

- route list có đủ bốn PT workspace routes mới (detail, current plan/future schedule, sessions list, session detail);
- `docs/BACKEND_API_CONTRACT.md` phản ánh source/test contract;
- focused blocker/regression tests PASS;
- full Backend suite PASS;
- Pint, Composer validate/audit/platform checks PASS;
- negative matrix và half-open boundary có evidence thật trong `task-1-report.md`;
- reviewer Task 1 PASS; không có out-of-scope diff.

### Gate hoàn tất phase

- Task 2 focused tests + full FE tests + lint + build + `npm ls --depth=0` PASS.
- Backend focused/full/Pint/Composer remains green vì Task 1 đã thay đổi Backend.
- browser smoke nếu surface khả dụng, gồm 1440/768/390, keyboard/focus, loading/empty/error/retry và assignment-loss redirect.
- `git diff --check` sạch; status/diff chỉ chứa declared files + reports/scratch artifacts được controller cho phép.
- Task Reviewer của Task 2 PASS và Final Reviewer độc lập PASS toàn phase.
- Chỉ sau hai PASS trên controller-authorized role mới cập nhật `docs/VUE_WEB_COMPLETION_CHECKPOINT.md`. Next phase phải được đọc lại từ canonical/checkpoint authority, không đoán trước.

## 9. Verification matrix

Backend focused: exact assignment boundary, role/auth, four routes, DTO allow-list, cross-member history, official-vs-proposal, no Membership activation, immutable history, existing profile/list/progress/notes regressions. Backend full: toàn suite, Pint và Composer health.

Frontend focused: every new service URL/method/params/envelope; route order/meta/menu/active parent; store request races, logout/role switch cleanup, 403/404 purge/redirect, cursor/cache isolation; all seven page states; profile/note validation and ambiguous mutation outcomes; read-only official plan/history; accessibility attributes and responsive class behavior. Frontend full: repository test, lint, build and dependency tree.

Static scans:

- không gọi Admin/Member-self Workout endpoint từ PT services;
- không có `DELETE`/edit history/Plan mutation/Proposal-as-Plan;
- không có client assignment/branch/user authority fields;
- không có token/PII logging;
- không có UI framework/icon/motion/font dependency mới;
- không có blocker/placeholder page hoặc FE6+ scope.

## 10. Risks còn lại

- Assignment có thể mất hiệu lực giữa các request; cả Backend point-in-time authorization và FE purge-on-403/404 đều bắt buộc.
- Note POST không idempotent; blind retry có thể tạo duplicate note.
- Current Plan và future schedule phải cùng official authority; mapper duplication hoặc Proposal reuse có thể làm sai nghĩa nghiệp vụ.
- History detail phải bind đồng thời session và selected member; chỉ authorize member mà không check ownership sẽ gây cross-member leak.
- `huan_luyen_vien.api.js` đang phục vụ Admin flows; rename/overwrite thiếu kiểm soát có thể regression FE1.
- Store cache rộng có nguy cơ rò dữ liệu giữa hai hội viên hoặc sau logout; request-generation tests là exit gate.
- FE5 surface lớn; vẫn phải giữ đúng một aggregate writer, không chia thành partial implementations.
