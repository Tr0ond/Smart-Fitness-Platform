# FE3-ALL — Báo cáo hoàn tất

Status: DONE

TASK_ID: FE3-ALL

FIX_ROUND: 1

## 1. Mục tiêu module

Hoàn thiện và sửa đủ 12 màn hình Admin Catalog của FE3-ALL cho gói tập, dụng cụ, nhóm cơ, bài tập và giáo án mẫu. Bản sửa vòng 1 đóng F-001 đến F-008 cùng cảnh báo compiler của hai form bài tập, giữ đúng API contract, quyền Admin, quy tắc Q01/Q11/M061, copy-on-write và xử lý kết quả mutation không xác định.

## 2. Phạm vi file

51 product target paths trong task-fe3-all-targets.txt đều hiện diện. Có 42 target thay đổi so với snapshot trước sửa vòng 1; 9 target còn lại được giữ nguyên. Báo cáo này là file workflow duy nhất được cập nhật ngoài product allow-list.

## 3. File đã tạo và đã sửa

- Router/menu/auth: đăng ký đủ 12 route Admin Catalog, thứ tự route tĩnh trước route tham số, meta/breadcrumb, năm nhóm menu, active parent và cleanup catalog khi mất quyền.
- Store/service: store danh mục dùng cache ngắn hạn có invalidation, guard đọc cũ, chuẩn hóa lỗi và đánh dấu outcomeUnknown; năm service giữ payload allow-list và thông báo tiếng Việt.
- Component: bộ quyền lợi gói, bộ quan hệ bài tập, cây giáo án và dialog xung đột; dialog xung đột dùng primitive dialog dùng chung.
- Page và test: 12 Vue page cùng 12 test page được mount và tương tác; các luồng status, Q01, Q11, M061, metadata-only PATCH và stale revision đều có bằng chứng.
- Repair delta so với task-fe3-all-fix-round-1-before-manifest.json chỉ nằm trong 42 path thuộc exact 51-target allow-list. Preservation manifest 710 path và các hunk bẩn có trước được giữ nguyên.

Không sửa Backend, Database, Mobile, FE4, rule, plan, snapshot, checkpoint, cấu hình lint, dependency hay lockfile.

## 4. Database liên quan

Không sửa Database. UI làm việc với các resource packages, equipment, muscle_groups, exercises, workout_templates và workout_template_revisions. M061, trạng thái inactive, audit và khóa giao dịch vẫn do Backend quyết định.

## 5. API đã dùng

- Gói tập: GET/POST /admin/packages, GET/PATCH /admin/packages/{id}, PUT /admin/packages/{id}/benefits.
- Dụng cụ: GET/POST /admin/equipment, PATCH /admin/equipment/{id}.
- Nhóm cơ: GET/POST /admin/muscle-groups, PATCH /admin/muscle-groups/{id}.
- Bài tập: GET/POST /admin/exercises, GET/PATCH /admin/exercises/{id}.
- Giáo án mẫu: GET/POST /admin/workout-templates, GET/PATCH /admin/workout-templates/{id}, POST /admin/workout-templates/{id}/revisions.

Client chỉ gửi field được contract cho phép. Revision chỉ gửi expected_content_version do form đang dùng; client không tự gửi branch, creator, snapshot hay quyền authority khác.

## 6. Hàm chính

Store/service giữ các luồng tải danh sách, tải chi tiết, tạo, cập nhật, thay thế quyền lợi, tạo quan hệ, tạo giáo án và tạo phiên bản. Các handler UI đã đổi sang tên xuLy... tiếng Việt không dấu camelCase. Hàm mutation, M061, metadata-only PATCH, copy-on-write/stale, snapshot và ambiguous outcome có docblock nêu mục đích, đầu vào, kết quả, side effect và quy tắc liên quan.

## 7. Business rule

- Q01 hiển thị cảnh báo khi sửa quyền lợi; thay đổi quyền lợi chỉ áp dụng cho lượt mua mới.
- Q11 giữ đủ nhiều dụng cụ theo semantics AND và gửi đầy đủ equipment_ids.
- M061 khóa checkbox và role của mọi quan hệ nhóm cơ inactive đã tồn tại, chặn cả thao tác bằng handler, giữ nguyên id/role trong replacement payload; nhóm inactive chưa được chọn không thể chọn mới.
- Dụng cụ và bài tập không có hard delete. Dialog chỉ thực hiện đổi trạng thái sau xác nhận.
- Detail giáo án chỉ PATCH metadata/status, không gửi days. Revision gửi toàn bộ cây days theo copy-on-write.
- Khi stale 409, bản nháp đã gửi được snapshot riêng, nền mới nhất được tải riêng để so sánh, và chỉ thao tác đối soát tường minh mới cập nhật expected_content_version; không tự gửi lại.

## 8. Authorization

Đủ 12 route yêu cầu xác thực, vai trò ADMIN và layout admin. Router test đã kiểm tra guest redirect, user không phải ADMIN bị từ chối và ADMIN được truy cập. Axios tiếp tục dùng interceptor auth hiện tại; menu chỉ là presentation.

## 9. Validation, accessibility và responsive

Service kiểm tra id dương, enum trạng thái, metadata bắt buộc, giới hạn số, quyền lợi AI, quan hệ trùng lặp, role, thứ tự ngày/bài tập và sessions_per_week. Form dùng label native, aria-invalid, lỗi theo field, khóa submit khi đang gửi và focus hiển thị. Dialog dùng tên/mô tả accessible, aria-modal, focus ban đầu, vòng Tab/Shift+Tab, Escape, focus return và khóa nút khi pending. CSS giữ bố cục staff sáng, bảng có vùng cuộn và breakpoint cho mobile/tablet/desktop.

## 10. Transaction, idempotency và ambiguous outcome

Client không thêm Idempotency-Key ngoài contract và không retry mù. Submit/mutation dialog khóa trong lúc pending. Network hoặc 5xx mutation được đánh dấu outcomeUnknown; flow hiển thị kết quả chưa xác định và chỉ cho GET đối soát thủ công.

## 11. Error case

Danh sách và chi tiết có loading, empty, error an toàn và retry thủ công. 403/404/422/5xx/network không hiển thị raw Axios data. Bốn dialog status của gói tập, dụng cụ, nhóm cơ và giáo án chỉ đóng sau mutation thành công cùng GET authoritative xác nhận trạng thái dự kiến; lỗi và trạng thái chưa xác định vẫn ở dialog. Revision stale mở dialog xung đột, giữ draft, hiển thị khác biệt metadata/tree/version và không auto-resubmit.

## 12. Mapping F-001 đến F-008 và test case

- F-001: selector quan hệ khóa và guard removal/role change của inactive M061, đồng thời echo id/role; component và detail-page test kiểm tra thao tác bị chặn và payload.
- F-002: revision snapshot draft riêng, tải nền mới vào state so sánh riêng, hiển thị khác biệt và reconcile explicit; mounted test xác nhận draft/version được giữ và không có POST thứ hai.
- F-003: cả 12 page test đều mount component thật và tương tác với router/store/service boundary; router/menu/auth test bao phủ 12 route, route order/meta, năm menu, guest, non-ADMIN và ADMIN.
- F-004: lint toàn dự án kết thúc với 0 warning và 0 error; cảnh báo const-binding v-model đã được loại bỏ.
- F-005: package/equipment/muscle-group/template status flow so sánh intended/previous/current sau refresh authoritative; outcomeUnknown chỉ GET reconcile, không retry mutation.
- F-006: UI-bound handler trong FE3 dùng tên xuLy...; docblock mutation, snapshot, M061, metadata-only, copy-on-write/stale và unknown outcome đã có.
- F-007: copy hiển thị, service/store fallback, router meta/breadcrumb và menu dùng tiếng Việt có dấu; technical identifiers, route path/name, payload key và official error code giữ nguyên.
- F-008: dialog xung đột dùng hop_thoai_xac_nhan.vue dùng chung; test mounted kiểm tra accessible name/description, focus ban đầu, Tab/Shift+Tab, Escape, focus return, pending lock và nội dung lỗi/unknown.

## 13. Kết quả kiểm thử và quality gate

- Focused FE3 matrix: 24 test files, 57 tests passed. Matrix gồm router/menu, store, 5 service tests, 4 shared component tests và 12 mounted page tests.
- Full frontend: 58 test files, 607 tests passed; không có test skip/only và không có Vue compiler warning.
- npm run lint: PASS, exit 0, 0 warning, 0 error.
- npm run build: PASS, exit 0, Vite build hoàn tất với 169 modules.
- npm ls --depth=0: PASS; dependency tree hợp lệ và không thay đổi.
- Static scans: PASS cho catalog DELETE/hard delete, hard-coded API root, secret/token logging, dependency/font/framework cấm, handler không theo xuLy..., v-model="form", __file page assertions, TODO/FIXME và test skip/only.
- git diff --check: PASS; staged diff rỗng.
- Allow-list/preservation: PASS; đủ 51/51 target, repair delta 42 path đều thuộc allow-list, 0 path ngoài allow-list, preservation manifest không mất hunk có trước.
- Deterministic mounted QA: PASS cho 12 page mount/transition, router/Pinia/service boundary, status/stale/M061/Q01/Q11 flow, responsive CSS inspection và dialog keyboard/focus lifecycle.

## 14. Phần chưa hoàn thành

Không còn finding F-001 đến F-008 hoặc compiler warning nào mở trong phạm vi FE3-ALL. Browser smoke với session Admin đã xác thực ở ba viewport chưa thực hiện được vì không có local authenticated app session sẵn có; bằng chứng mounted deterministic được ghi ở mục 13 theo chiến lược được brief phê duyệt.

## 15. Rủi ro còn lại

Giới hạn duy nhất là chưa có bằng chứng browser-level với session thật ở 1440/768/390. Không có unresolved production finding, lint warning, test failure, build failure, dependency change hoặc allow-list violation.
