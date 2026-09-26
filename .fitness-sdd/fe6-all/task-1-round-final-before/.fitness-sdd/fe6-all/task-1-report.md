MODULE DA HOAN THANH

1. Muc tieu module
   - Implement FE6-ALL for PT direct-session history/completion, proposal history/preview, and proposal creation, while preserving current-assignment scope and retry safety.

2. File da tao
   - `FE/src/components/PT/khung_xem_de_xuat.vue`
   - `FE/src/components/PT/khung_xem_de_xuat.test.js`
   - `FE/src/pages/pt/buoi_huan_luyen/buoi_huan_luyen.index.vue`
   - `FE/src/pages/pt/buoi_huan_luyen/buoi_huan_luyen.index.test.js`
   - `FE/src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.index.vue`
   - `FE/src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.index.test.js`
   - `FE/src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.tao_moi.vue`
   - `FE/src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.tao_moi.test.js`
   - `FE/src/services/buoi_huan_luyen.api.js`
   - `FE/src/services/buoi_huan_luyen.api.test.js`
   - `FE/src/services/de_xuat.api.js`
   - `FE/src/services/de_xuat.api.test.js`
   - `FE/src/stores/de_xuat.store.js`
   - `FE/src/stores/de_xuat.store.test.js`

3. File da sua
   - `FE/src/components/PT/thanh_dieu_huong_hoi_vien.vue`
   - `FE/src/components/PT/thanh_dieu_huong_hoi_vien.test.js`
   - `FE/src/router/dieu_huong_pt.js`
   - `FE/src/router/dieu_huong_pt.test.js`
   - `FE/src/router/index.js`
   - `FE/src/router/index.test.js`
   - `FE/src/stores/hoi_vien_pt.store.js`
   - `FE/src/stores/hoi_vien_pt.store.test.js`
   - `FE/src/stores/xac_thuc.store.test.js`

4. Database lien quan
   - None. No database, schema, migration, or Backend file changed.

5. API da tao/sua
   - Added FE service calls for `GET /api/pt/direct-sessions` and `POST /api/pt/direct-sessions/complete`.
   - Added FE service calls for `GET /api/pt/members/{member}/proposals` and `POST /api/pt/members/{member}/proposals`.
   - Requests use the verified Backend response shapes and `Idempotency-Key` UUIDv4 header. Direct completion sends only `assignment_id` and optional `notes`; proposal creation sends only the allowed proposal body fields.

6. Ham chinh
   - Direct-session API/service validation builds the allow-listed body, checks UUIDv4, and enforces the 1,000-character notes limit.
   - Direct-session store actions load latest history, filter by route Member, freeze/reuse the completion body and key, reconcile replay, and invalidate late responses when Member or auth scope changes.
   - Proposal API/service validation checks allowed top-level and nested fields, value bounds, UUIDv4, and response envelopes.
   - Proposal store actions load current-assignment proposals, freeze a logical action body/key, refetch before same-key retry after an unknown outcome, preserve draft on conflict, and clear scoped state on logout, Member change, or authorization loss.
   - The three PT screens provide direct-session completion/history, proposal list/preview/status, and proposal composition from official Plan context. The PT navigation exposes the new routes and omits tabs whose named routes are not registered.

7. Business Rule da xu ly
   - Direct completion uses the assignment ID obtained from the current Member detail; the Backend remains responsible for the applicable term, quota, and single usage decrement.
   - Both histories explain their latest-100 limits. An empty filtered direct-history list does not claim lifetime absence.
   - Proposal creation depends on current assignment only; it has no Membership, chat, AI, or PT-quota gate or side effect (Q13). PT has no apply action; the Member decides in Mobile.
   - Proposals display the supported statuses and preview the list DTO. An adjustment uses the official Plan context; `TAO_MOI` is used when no active Plan exists.

8. Authorization
   - Routes stay under existing PT route protection. FE checks current assignment context before loading or mutating; Backend authorization remains authoritative for resource scope.
   - 403/404 assignment or resource loss clears proposal/direct-session state and redirects away. Logout/actor-scope cleanup also invalidates pending writes.

9. Validation
   - FE services validate UUIDv4, allow-listed keys, IDs, date and string limits, day/exercise count and nested numeric ranges before sending.
   - The direct-session note is capped at 1,000 characters. Proposal 422 nested field errors are surfaced in the composer.

10. Transaction / Idempotency
   - No client transaction. For each logical POST, FE freezes the exact body with one UUIDv4 key and disables duplicate submission while pending.
   - For network/5xx unknown outcomes, FE refetches first. Because the capped list omits the request key and absence is inconclusive, retry reuses the original body and key; replayed IDs reconcile the result without presenting a second completion.
   - Scope/request generations prevent late responses from repopulating state after a Member switch, logout, or assignment loss.

11. Error Case
   - Network/5xx unknown outcomes retain the original action for safe retry after refetch.
   - 409 preserves the proposal draft and shows conflict/refetch guidance. 422 shows field errors. 401 follows shared auth handling; 403/404 clear the affected scope.
   - Invalid response envelopes are handled as load errors. A failed refetch does not claim that an ambiguous write was resolved.

12. Test Case
   - API URL, UUID header, exact payload allow-lists and nested validation; history and proposal latest-100 behavior; assignment-derived IDs; status/preview display; Q13 without quota gates or an apply control; 422/409/403/404 behavior; pending/double-submit; network outcome refetch and same-key/body retry; replay reconciliation; late response and auth/Member cleanup; navigation with full and partial route registries.

13. Test Result
   - `rtk npm run test -- src/services/buoi_huan_luyen.api.test.js src/services/de_xuat.api.test.js src/stores/de_xuat.store.test.js src/pages/pt/buoi_huan_luyen/buoi_huan_luyen.index.test.js src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.index.test.js src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.tao_moi.test.js` — PASS, 6 files / 34 tests.
   - `rtk npm run test -- src/router/index.test.js src/router/dieu_huong_pt.test.js src/components/PT/thanh_dieu_huong_hoi_vien.test.js src/components/PT/khung_xem_de_xuat.test.js src/stores/hoi_vien_pt.store.test.js src/stores/xac_thuc.store.test.js src/services/buoi_huan_luyen.api.test.js src/services/de_xuat.api.test.js src/stores/de_xuat.store.test.js src/pages/pt/buoi_huan_luyen/buoi_huan_luyen.index.test.js src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.index.test.js src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.tao_moi.test.js` — PASS, 12 files / 108 tests.
   - `rtk npm run test` — PASS, 86 files / 846 tests.
   - `rtk npm run test -- src/components/PT/thanh_dieu_huong_hoi_vien.test.js src/pages/pt/ke_hoach_tap/ke_hoach_tap.index.test.js src/pages/pt/hoi_vien/hoi_vien.chi_tiet.test.js src/pages/pt/ghi_chu/ghi_chu.index.test.js src/pages/pt/tien_do/tien_do.index.test.js src/pages/pt/lich_su_tap/lich_su_tap.index.test.js src/pages/pt/hoi_vien/hoi_vien.index.test.js` — PASS, 7 files / 32 tests.
   - `rtk npm run lint` — exit 0, 0 errors and 293 warnings.
   - `rtk npm run build` — exit 0; Vite 8.2.2 transformed 200 modules; generated JS bundle 631.89 kB.
   - `rtk git diff --check` — PASS.
   - `rtk rg -n -P "ghp_[A-Za-z0-9]{30,}|sk-[A-Za-z0-9]{20,}|-----BEGIN (RSA|OPENSSH|EC) PRIVATE KEY-----|Bearer [A-Za-z0-9._-]{24,}" src/pages/pt/buoi_huan_luyen/buoi_huan_luyen.index.vue src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.index.vue src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.tao_moi.vue src/services/buoi_huan_luyen.api.js src/services/de_xuat.api.js src/stores/de_xuat.store.js src/components/PT/khung_xem_de_xuat.vue` — no matches (ripgrep exit 1 means no matches).

14. Phan chua hoan thanh
   - None for FE6-ALL acceptance. The Controller's supplied Backend contract/test gate was used; no Backend tests or database commands were run in this FE-only task.

15. Rui ro con lai
   - ESLint passes with 293 warnings (0 errors), mainly Vue formatting warnings.
   - The production JavaScript bundle is 631.89 kB before gzip; build succeeds, but bundle splitting may be considered in a separately scoped task.

Gate va preservation evidence
- Entry gate supplied by Controller: FE5-ALL PASS; direct/proposal routes, requests, services and tests verified; targeted Backend tests 24/24 with 180 assertions, exit 0, isolated `smart_fitness_fe5_round2_test`, PHP 8.4.25; M001-M061 ran.
- Baseline status contained only `.fitness-sdd/fe6-all/plan.md` and `task-1-brief.md`; no pre-existing product-code changes. The provided `task-1-round-0-before/` snapshot marks new paths absent and captures existing allow-listed paths. Final product changes are confined to the task allow-list; the report is the sole permitted workflow artifact outside it. No Backend, rule, schema, package, configuration, or checkpoint file changed.
- Required RTK/project/FE6 layered context and applicable PT, auth-scope, security, coding, visual, and testing modules were read. No canonical escalation was needed; no business-rule ambiguity was introduced.

Repair round 1 — F-001 through F-004
- Changed only the six authorized repair targets:
  - `FE/src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.tao_moi.vue`
  - `FE/src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.tao_moi.test.js`
  - `FE/src/pages/pt/buoi_huan_luyen/buoi_huan_luyen.index.vue`
  - `FE/src/pages/pt/buoi_huan_luyen/buoi_huan_luyen.index.test.js`
  - `FE/src/components/PT/khung_xem_de_xuat.vue`
  - `FE/src/components/PT/khung_xem_de_xuat.test.js`
- F-001: removal renumbers day/exercise order while preserving surviving weekdays and values; additions choose unused order/weekday values. Structural edits clear stale indexed 422 errors. Regression submits a real normalized payload after middle remove/add and verifies unique order and weekday values.
- F-002: the existing confirmation dialog now gates the direct-session POST, names the captured Member/assignment, warns that Backend-selected eligible-term quota is consumed and first use may activate a term, and rechecks captured Member/assignment before POST. Cancel/Escape retain notes and send nothing; pending disables dialog actions; unknown-outcome retry keeps the existing frozen key/body flow.
- F-003: top-level, plan, day, and exercise field errors render beside their controls with `aria-invalid` and `aria-describedby`; the overall alert summary remains. Structure changes clear 422 errors so an old array index cannot attach to another row.
- F-004: proposal preview focuses its close control, traps Tab and Shift+Tab, closes on Escape from the window focus context, restores focus to a connected trigger, and removes the key listener on unmount. The backdrop is excluded from tab order.
- `rtk npm run test -- src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.tao_moi.test.js src/pages/pt/buoi_huan_luyen/buoi_huan_luyen.index.test.js src/components/PT/khung_xem_de_xuat.test.js src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.index.test.js` — PASS, 4 files / 23 tests, exit 0.
- Final repair state: `rtk npm run test` — PASS, 86 files / 852 tests, exit 0, no skips reported; `rtk npm run lint` — exit 0, 0 errors / 375 warnings; `rtk npm run build` — exit 0, 200 modules, JS bundle 641.25 kB, Vite chunk-size warning; `rtk git diff --check` — exit 0.
- Remaining concerns: lint warnings are nonblocking; the production JS bundle exceeds 500 kB. No Backend or database command was run.
