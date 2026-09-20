# Phase Context: FE-1 — Admin Foundation

> Task scope: historical small tasks FE1-T01 through FE1-T08.
> Canonical plan: [VUE_WEB_IMPLEMENTATION_PLAN_V2.md](../../docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md), Sections 31, 32 and 38A.

## Scope, routes, and expected files

Goal: provide the Admin account-oriented portal needed to demonstrate authorization.

Exact screens/routes: Dashboard at /admin/bang-dieu-khien; Account list/detail at /admin/tai-khoan and /admin/tai-khoan/:id; Member list/detail at /admin/hoi-vien and /admin/hoi-vien/:id; Receptionist list/detail at /admin/nhan-vien-le-tan and /admin/nhan-vien-le-tan/:id. Use the Admin layout, route modules/guards, account/dashboard services, tai_khoan.store.js, and shared table/filter/pagination/status/confirm/query components. File families follow the plan inventory: pages/admin, layouts/bo_cuc_admin.vue, services/bang_dieu_khien.api.js and tai_khoan.api.js, stores/tai_khoan.store.js, and shared components.

## Minimal modules, functions, and docblocks

Use auth plus account stores; do not create a store for every page. Main functions are taiTongQuanAdmin, apDungKhoangNgay, taiDanhSachTaiKhoan, apDungBoLocTaiKhoan, taiChiTietTaiKhoan, capNhatTrangThaiTaiKhoan, capVaiTro, thuHoiVaiTro, taiDanhSachHoiVien, taiDanhSachNhanVienLeTan, and their fixed-role view functions. Date/branch query, role/status mutation, last-admin/profile conflict, and filtered actor-view functions require meaningful docblocks. Frontend must not choose branch or treat guard/cache as authorization.

## Tests and acceptance

Test Admin guard, exact dashboard metrics without invented revenue, whitelisted filters/pagination, loading/empty/error states, role grant/revoke/regrant, TRAINER_PROFILE_REQUIRED, last-admin conflict, account status, and fixed MEMBER/RECEPTIONIST views. Re-fetch after mutation or an unknown outcome; do not call PT or Member-self APIs. Run the proportionate test, lint, build, naming and docblock checks.

## Entry/exit gates, blockers, and stops

Entry: FE-0 PASS. Exit: Admin navigation/guard, dashboard, account list/detail/status/role UX, Member and Receptionist account views pass with evidence. No Backend blocker is in the account-oriented MVP scope. If the account DTO or route no longer contains fields used by a page, stop that page, read the actual contract, and do not invent a workaround.

## Historical task codes

| Task | Definition |
| --- | --- |
| FE1-T01 | Admin navigation, menu, breadcrumb, and route meta |
| FE1-T02 | Dashboard, bounded from/to filter, and metric cards |
| FE1-T03 | Account list/search/filter/pagination |
| FE1-T04 | Account detail and account-status mutation |
| FE1-T05 | Role grant/revoke/regrant and last-admin/profile conflicts |
| FE1-T06 | Member account-oriented list/detail |
| FE1-T07 | Receptionist account-oriented list/detail |
| FE1-T08 | Full FE-1 gate |

These are historical small tasks from the plan; they do not authorize a new FE1-ALL task.
