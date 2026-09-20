# Phase Context: FE-0 — Foundation

> Task scope: historical small tasks FE0-T01 through FE0-T09. FE-0 is the shared foundation; it does not create domain screens.
> Canonical plan: [VUE_WEB_IMPLEMENTATION_PLAN_V2.md](../../docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md), Sections 31, 32, 38A and 6A.

## Scope and routes

Goal: create a safe Vue foundation for all actors.

Exact screens/routes: role selection at /chon-vai-tro; forgot password at /quen-mat-khau; reset password at /dat-lai-mat-khau; 403 at /khong-co-quyen; 404 at /khong-tim-thay; Admin login at /admin/dang-nhap; PT login at /pt/dang-nhap; Receptionist login at /le-tan/dang-nhap; root / as a dispatcher; catch-all route. This is 10 router records in FE-0 and no domain workflow.

## Expected file families and minimal modules

- Router modules and guards; five layouts: bo_cuc_cong_khai.vue, bo_cuc_admin.vue, bo_cuc_pt.vue, bo_cuc_le_tan.vue, bo_cuc_loi.vue.
- Shared auth form/field/query/error/toast components and CSS token baseline.
- Auth pages and xac_thuc.store.js; xac_thuc.api.js; one Axios client; error, idempotency, session/token-accessor and safe-internal-path helpers.
- Vitest, Vue Test Utils, jsdom, ESLint and eslint-plugin-vue only when compatibility with the existing runtime/lockfile is verified. No UI framework, second state library, or realtime package in FE-0.

## Main functions and docblocks

Implement or wire dangNhap, taiThongTinNguoiDung, khoiPhucPhien, dieuPhoiTheoVaiTro, dangXuatVaDonDep, chuanHoaLoiApi, taoKhoaIdempotency and laDuongDanNoiBoHopLe. Auth, redirect, current-token 401 cleanup, transient /me retry, error normalization and idempotency lifecycle functions require meaningful docblocks with purpose, input, process, result and side effect.

## Required behavior and tests

- Login, logout and /me use the actual auth contract; role selection is explicit and never invents priority. MEMBER-only accounts receive the Web out-of-scope message.
- Current-token /me 401 clears only the matching session. A late 401 from an old token cannot clear a newer session. Network/timeout/5xx keeps the token in sessionStorage, clears in-memory authority, blocks protected content, and offers retry.
- Test 403, 404, revoked role, safe internal redirect, idempotency helper, no token logging, form/query states, and responsive shell. Use exact API fixtures; do not use a fake authority.

## Entry gate and exit gate

Entry: read auth source and perform dependency compatibility check from plan Section 6A. Exit: auth/router/client/test/build/naming/docblock checks pass; login/me/logout/restore, wrong-role and actor-null behavior, current/late 401, transient /me retry, 403 and 404 are evidenced.

## Blockers and stop conditions

There is no Backend blocker for Foundation. If a dependency is incompatible, stop only installation and record BLOCKED_DEPENDENCY_COMPATIBILITY. If the auth response differs from source or tests, stop integration and escalate; do not guess, add an API, or change Backend.

## Historical task codes

| Task | Definition |
| --- | --- |
| FE0-T01 | Baseline package/lockfile, build, source tree, and environment usage |
| FE0-T02 | Compatible test/lint dependencies, scripts, and config |
| FE0-T03 | Environment, Axios client, and error normalization |
| FE0-T04 | Auth service, xac_thuc store, sessionStorage lifecycle |
| FE0-T05 | Public/protected router, actor login, selector, 403/404 |
| FE0-T06 | Public/Admin/PT/Receptionist/error layouts and responsive shell |
| FE0-T07 | Shared query/form/error/toast/confirm components and CSS tokens |
| FE0-T08 | Idempotency, safe redirect, session helpers, naming/docblock scan |
| FE0-T09 | Full FE-0 gate and checkpoint |

Each historical task records its own evidence; do not turn FE-0 into a new phase-wide task code.
