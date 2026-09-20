# Phase Context: FE-2 — Admin PT

> Task scope: historical small tasks FE2-T01 through FE2-T08.
> Canonical plan: [VUE_WEB_IMPLEMENTATION_PLAN_V2.md](../../docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md), Sections 31, 32 and 38A.

## Scope, routes, and expected files

Goal: manage the distinction between PT account, profile, role, and assignment without inventing invitation or assignment history.

Exact screens/routes: PT list at /admin/huan-luyen-vien; create/onboarding at /admin/huan-luyen-vien/tao-moi; detail at /admin/huan-luyen-vien/:id; assignment at /admin/phan-cong-pt. Expected families are the Admin/PT pages and layout, huan_luyen_vien.api.js and phan_cong_pt.api.js, account/auth stores, onboarding/status form components, table/badge/blocker/confirm components. Do not create an assignment store until an approved GET contract exists.

## Minimal modules, functions, and docblocks

Main functions are taiDanhSachHuanLuyenVien, taoHuanLuyenVien, onboardTaiKhoanThanhPt, taiChiTietHuanLuyenVien, and, only after the matching GET contract, taiDanhSachPhanCongPt, taoPhanCongPt, ketThucPhanCongPt, and phanCongLaiPt. Document stable-key lifecycle, invitation semantics, account/profile/role distinction, half-open assignment interval, Q05 effect, timeout recovery, and exact resource scope.

## Tests, gates, and blockers

Test PT list, stable idempotency for onboarding, conflict/in-progress states, no false QUEUED result, profile/role distinction, blocker state with no fake request, and, after contracts, assignment half-open interval, overlap conflict, refetch and Q05 cleanup. Entry requires FE-1 account primitives. READY subset may be prepared, but a writer must not be dispatched for unresolved blocked flows as if the phase were complete. Full trainer profile is BLOCKER-01; assignment list/detail/history is BLOCKER-02. BE-FOLLOWUP-02 invitation recovery and BE-FOLLOWUP-03 onboarding audit snapshot are required for onboarding completion.

Stop onboarding if the two Backend fixes are not merged/tested; stop detail/assignment query if its API is missing. Do not add resend invitation, fake history, client joins, or a query workaround. Exit evidence must distinguish READY work from unresolved blockers and include proportionate test/lint/build/naming/docblock results.

## Historical task codes

| Task | Definition |
| --- | --- |
| FE2-T01 | PT list from Admin account filter |
| FE2-T02 | Create/onboard PT with stable Idempotency-Key |
| FE2-T03 | PT detail account/role summary |
| FE2-T04 | Full trainer-profile prefill/edit after BLOCKER-01 |
| FE2-T05 | Assignment shell and honest blocked state |
| FE2-T06 | Assignment list/detail/filter after BLOCKER-02 |
| FE2-T07 | Create/end/reassign with timeout/refetch/Q05 warning |
| FE2-T08 | Full FE-2 gate |

These are historical small tasks from the plan; do not invent a phase-wide FE2-ALL code.
