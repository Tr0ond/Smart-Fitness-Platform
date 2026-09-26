### TASK_PACKET: FE5-ALL

**ROLE:** Implementation Writer  
**WORKFLOW_ROLE:** Implementer/Fixer  
**MODEL:** `gpt-6-luna`  
**REASONING_EFFORT:** `max`  
**MODEL_SELECTION_RULE:** Controller supplies the exact model and effort above; recipient must not substitute them.  
**OBJECTIVE:** Triển khai trong một aggregate writer toàn bộ bảy màn hình PT workspace, dùng duy nhất các PT-scoped contracts đã PASS từ Task 1, giữ Light Staff UI và xử lý đầy đủ auth/resource-scope, race cleanup, validation, failures và tests.

---

#### 1. TÌNH TRẠNG HIỆN TẠI (STATE & CHECKPOINT)

- **CURRENT_CHECKPOINT:** `E:/Fitness/docs/VUE_WEB_COMPLETION_CHECKPOINT.md`; FE0–FE4 PASS, FE5-ALL là next phase.
- **CURRENT_STATE:** PT layout hiện chỉ là shell, router chưa có bảy route FE5, chưa có PT member store/pages/services. Shared Axios/auth/guard/components và Admin services đã tồn tại.
- **TASK_SEQUENCE:** Task 2/2. Controller chỉ dispatch packet này sau khi `BE-FE5-PREREQ`, Backend gates và Task Reviewer Task 1 PASS.
- **AGGREGATE_INVARIANT:** FE5 là đúng một writer task; không tách theo page/checklist, không triển khai subset, không blocker/placeholder page.

#### 2. PHẠM VI CHO PHÉP & CẤM (SCOPE BOUNDARIES)

**ALLOWED_SCOPE:**

- Đúng bảy route/screens: PT self profile, assigned members, selected member detail, progress, official plan/future schedule, immutable workout history, append-only notes.
- PT route registry/layout/breadcrumb/active navigation; role home redirect `PT -> ptHoiVien`.
- New PT services/store/components; extend existing trainer service without breaking Admin calls; wire session cleanup.
- Unit/integration tests, shared CSS additions using existing variables only.

**PROHIBITED_SCOPE:**

- Cấm sửa Backend, checkpoint, canonical docs/rules, package/lock/config, baseline artifacts hoặc file ngoài allow-list.
- Cấm Admin/Member-self endpoint reuse cho PT resources; cấm Proposal-as-Plan; cấm Plan/Session mutation, history edit/delete, note edit/delete.
- Cấm FE6+ chat/proposal UI, upload/media, medical diagnosis, client-computed BMI authority, Membership activation/usage logic.
- Cấm UI/form/icon/motion dependency mới, Google Font, GSAP, Tailwind/Shadcn, dark mode, purple redesign.
- Cấm branch/worktree/commit/push/reset/restore/checkout/stash/clean.

**ALLOWED_FILES:** chỉ các absolute paths sau.

Existing integration files:

- `E:/Fitness/FE/src/router/index.js`
- `E:/Fitness/FE/src/router/index.test.js`
- `E:/Fitness/FE/src/router/bao_ve_tuyen_duong.js`
- `E:/Fitness/FE/src/router/bao_ve_tuyen_duong.test.js`
- `E:/Fitness/FE/src/router/dieu_huong_pt.js`
- `E:/Fitness/FE/src/router/dieu_huong_pt.test.js`
- `E:/Fitness/FE/src/layouts/bo_cuc_pt.vue`
- `E:/Fitness/FE/src/layouts/bo_cuc.test.js`
- `E:/Fitness/FE/src/pages/xac_thuc.test.js`
- `E:/Fitness/FE/src/stores/xac_thuc.store.js`
- `E:/Fitness/FE/src/stores/xac_thuc.store.test.js`
- `E:/Fitness/FE/src/assets/main.css`

Services and store:

- `E:/Fitness/FE/src/services/huan_luyen_vien.api.js`
- `E:/Fitness/FE/src/services/huan_luyen_vien.api.test.js`
- `E:/Fitness/FE/src/services/hoi_vien_pt.api.js`
- `E:/Fitness/FE/src/services/hoi_vien_pt.api.test.js`
- `E:/Fitness/FE/src/services/tien_do.api.js`
- `E:/Fitness/FE/src/services/tien_do.api.test.js`
- `E:/Fitness/FE/src/services/ke_hoach_tap_pt.api.js`
- `E:/Fitness/FE/src/services/ke_hoach_tap_pt.api.test.js`
- `E:/Fitness/FE/src/services/lich_su_tap_pt.api.js`
- `E:/Fitness/FE/src/services/lich_su_tap_pt.api.test.js`
- `E:/Fitness/FE/src/services/ghi_chu.api.js`
- `E:/Fitness/FE/src/services/ghi_chu.api.test.js`
- `E:/Fitness/FE/src/stores/hoi_vien_pt.store.js`
- `E:/Fitness/FE/src/stores/hoi_vien_pt.store.test.js`

Reusable PT components:

- `E:/Fitness/FE/src/components/pt/thanh_dieu_huong_hoi_vien.vue`
- `E:/Fitness/FE/src/components/pt/thanh_dieu_huong_hoi_vien.test.js`
- `E:/Fitness/FE/src/components/pt/the_tien_do.vue`
- `E:/Fitness/FE/src/components/pt/the_tien_do.test.js`

Pages and exact tests:

- `E:/Fitness/FE/src/pages/pt/ho_so/ho_so.index.vue`
- `E:/Fitness/FE/src/pages/pt/ho_so/ho_so.index.test.js`
- `E:/Fitness/FE/src/pages/pt/hoi_vien/hoi_vien.index.vue`
- `E:/Fitness/FE/src/pages/pt/hoi_vien/hoi_vien.index.test.js`
- `E:/Fitness/FE/src/pages/pt/hoi_vien/hoi_vien.chi_tiet.vue`
- `E:/Fitness/FE/src/pages/pt/hoi_vien/hoi_vien.chi_tiet.test.js`
- `E:/Fitness/FE/src/pages/pt/tien_do/tien_do.index.vue`
- `E:/Fitness/FE/src/pages/pt/tien_do/tien_do.index.test.js`
- `E:/Fitness/FE/src/pages/pt/ke_hoach_tap/ke_hoach_tap.index.vue`
- `E:/Fitness/FE/src/pages/pt/ke_hoach_tap/ke_hoach_tap.index.test.js`
- `E:/Fitness/FE/src/pages/pt/lich_su_tap/lich_su_tap.index.vue`
- `E:/Fitness/FE/src/pages/pt/lich_su_tap/lich_su_tap.index.test.js`
- `E:/Fitness/FE/src/pages/pt/ghi_chu/ghi_chu.index.vue`
- `E:/Fitness/FE/src/pages/pt/ghi_chu/ghi_chu.index.test.js`

Report:

- `E:/Fitness/.fitness-sdd/fe5-all/task-2-report.md`

**PROHIBITED_FILES:** mọi file khác, đặc biệt `PROJECT_RULES.md`, Vue plan, Backend, package manifests/lockfiles, checkpoint, AGENTS.md, baseline/snapshot artifacts.

**AUTHORITIES:** `E:/Fitness/PROJECT_RULES.md`, `E:/Fitness/docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md`, applicable `AGENTS.md`, selected `.fitness-rules/*` và source-authoritative Backend contract sau Task 1.

**BASELINE_AND_PREEXISTING:** dùng `baseline-status.txt`, `baseline-working.patch`, `baseline-staged.patch`, controller `baseline-tree/` và `task-2-round-<R>-before/`. Preserve mọi pre-existing/user changes ngoài declared hunks. Không overwrite existing Admin functions trong `huan_luyen_vien.api.js`.

#### 3. TÀI LIỆU CONTEXT BẮT BUỘC (REQUIRED_CONTEXT)

Đọc đầy đủ trước khi sửa:

- `E:/Fitness/AGENTS.md`
- discover/read mọi nested `AGENTS.md` áp dụng cho FE files
- `E:/Fitness/.fitness-rules/PROJECT_CORE.md`
- `E:/Fitness/.fitness-rules/RULE_INDEX.md`
- `E:/Fitness/.fitness-rules/frontend/vue-core.md`
- `E:/Fitness/.fitness-rules/frontend/visual-ui.md`
- `E:/Fitness/.fitness-rules/domains/pt-chat.md`
- `E:/Fitness/.fitness-rules/domains/workout.md`
- `E:/Fitness/.fitness-rules/domains/auth-resource-scope.md`
- `E:/Fitness/.fitness-rules/engineering/coding-conventions.md`
- `E:/Fitness/.fitness-sdd/context/FE5_CONTEXT.md`
- `E:/Fitness/.fitness-sdd/context/TASK_PACKET_TEMPLATE.md`
- `E:/Fitness/.fitness-sdd/fe5-all/plan.md`
- `E:/Fitness/.fitness-sdd/fe5-all/task-1-report.md`
- `E:/Fitness/docs/BACKEND_API_CONTRACT.md` sections for profile/trainer and PT member workspace
- actual source and tests in ALLOWED_FILES plus directly reused shared components/client/error/auth helpers. Actual source wins; inspect before create to avoid duplicate service/component/store.

Canonical escalation đã được planner xử lý cho Q04/Q08, RULE GYM 13/15, RULE CODE 17/19/20 và Vue plan Section 31/33/38A. Nếu source contract sau Task 1 khác plan/task report hoặc có ambiguity mới, dừng và báo controller; không dùng workaround endpoint.

#### 4. QUY TẮC CẦN TUÂN THỦ (CANONICAL_RULE_IDS)

- **RULE GYM:** RULE GYM 13, RULE GYM 15 — completed session/snapshot immutable; history read-only.
- **RULE CODE:** RULE CODE 05–09, RULE CODE 17, RULE CODE 18–20.
- **Q DECISIONS:** Q04 exact assignment; Q08 Workout Tracking independent of Membership.
- **PT binding:** assignment current iff `start <= now < end`, null end open; Backend authoritative.
- **Plan binding:** official current Plan separate from Proposal; no Plan mutation in FE5.
- **Note binding:** append-only, max returned list 100, no edit/delete; POST not advertised idempotent.
- **Visual binding:** Light Staff UI, CSS variables, system font, visible focus, dynamic ARIA, responsive 1440/768/390.

#### 5. MÃ NGUỒN LIÊN QUAN VÀ IMPLEMENTATION CONTRACT (RELEVANT_SOURCE)

##### Router/layout

- Named routes exactly:
  - `/pt/ho-so` -> `ptHoSo`
  - `/pt/hoi-vien` -> `ptHoiVien`
  - `/pt/hoi-vien/:id` -> `ptChiTietHoiVien`
  - `/pt/hoi-vien/:id/tien-do` -> `ptTienDoHoiVien`
  - `/pt/hoi-vien/:id/ke-hoach-tap` -> `ptKeHoachTapHoiVien`
  - `/pt/hoi-vien/:id/lich-su-tap` -> `ptLichSuTapHoiVien`
  - `/pt/hoi-vien/:id/ghi-chu` -> `ptGhiChuHoiVien`
- All meta require auth + role PT; add safe breadcrumbs. Static child paths must resolve correctly and active parent must remain `Hội viên`.
- `dieu_huong_pt.js` supplies `Hồ sơ`, `Hội viên`; `bo_cuc_pt.vue` reuses `KhungUngDung`. Role home map becomes `PT: 'ptHoiVien'`; update guard/login/actor-switch tests.

##### Services/store/session cleanup

- Extend existing `huan_luyen_vien.api.js` using unambiguous self-profile names (for example `taiHoSoCaNhanHuanLuyenVien`, `capNhatHoSoCaNhanHuanLuyenVien`); retain all Admin exports/semantics.
- PT services call only `/api/profile/trainer` and `/api/pt/members...` contracts. Never call `/api/workout/...` Member-self or `/api/admin/...`.
- `hoi_vien_pt.store.js` owns assigned list, selected ID/detail and member-scoped progress/plan/schedule/history/notes caches. Use per-resource request generations and safe cleanup; do not allow response A to commit after selection B, unmount, logout, role change or a newer request.
- Export an idempotent cleanup helper callable by `xac_thuc.store.js` without eager circular initialization. Invoke on logout, valid-current-token 401 and actor/role switch; late 401 from an old token must not clear a newer session.
- For member-scoped `403/404`, store action must atomically invalidate generations and purge selected/sensitive caches, then return an explicit scope-loss result. Page performs `router.replace({ name: 'ptHoiVien' })` to avoid router-store import cycles. Refresh assigned list that no longer contains selected ID uses the same path.
- Validate route `id` as a positive integer before service calls. Do not log token, note/profile body or PII.

##### Bảy screens

1. **Hồ sơ PT:** GET/PATCH self only; edit only `gioi_thieu`, `chuyen_mon`; 422 inline field errors, global failures, preserve draft, pending guard, no authority fields.
2. **Hội viên:** current assigned list only; compact scannable rows/cards, assignment dates/status, loading/empty/error/retry.
3. **Chi tiết:** safe coaching profile and current assignment from BLOCKER-03; shared member subnavigation with `aria-current`.
4. **Tiến độ:** fetch overview/body measurements; derive exercise options only from official current Plan exercises, dedupe `exercise_id`, fetch selected exercise progress. If no official Plan/exercise show empty state, never fabricate an ID. Display Backend `bmi/bmi_status` only, no client medical interpretation.
5. **Kế hoạch:** label `Kế hoạch chính thức`; render current version/day/exercise plus server-authoritative `future_schedule`/window from BLOCKER-04. Read-only, no Proposal controls/mutation.
6. **Lịch sử:** cursor list/detail from BLOCKER-05; immutable snapshot, bounded load-more, no edit/delete. List and detail each have loading/empty/error/retry.
7. **Ghi chú:** list newest-first, max 100, append-only composer. Trim/validate `noi_dung`, disable duplicate submit. On timeout/5xx, retain draft, refetch notes to reconcile and communicate unknown outcome; never blind retry non-idempotent POST.

##### State/error/accessibility

- Every independent query implements Loading/Data/Empty/Error retry. `401` delegates shared auth flow; `403/404` selected member purges/redirects; `422` maps fields; network/5xx has actionable retry without stale partial commit.
- Profile/network mutation uncertainty requires refetch before retry. Read requests may be explicitly retried.
- Reuse existing title/loading/empty/error/table/pagination/form/notification/status components. New PT components are only member tabs and progress card.
- Semantic headings/regions/forms/tables/lists, explicit labels/descriptions/errors, keyboard-reachable controls, visible focus, status not color-only, `aria-current` for tabs, suitable `aria-live`/busy/error announcements.
- CSS extends existing variables/classes only. Dense operational layout must fit 1440, 768 and 390 without horizontal page overflow.

#### 5A. DEPENDENCIES & PHASE ENTRY GATE

**DEPENDENCIES:**

- FE0–FE4 remain PASS.
- `BE-FE5-PREREQ` Task Reviewer PASS.
- Actual Backend route list and API contract contain BLOCKER-03/04/05 contracts.
- Task 1 focused/full/Pint/Composer results PASS and exact negative/boundary evidence exists.
- Controller captured current source snapshots for all existing ALLOWED_FILES; no overlapping writer.

**ENTRY_GATE:** If any dependency is absent, route/DTO differs, or only docs/report claims PASS without source/tests, do not code; return to controller. No blocker page or partial task.

**EXPECTED_REPORT:** `E:/Fitness/.fitness-sdd/fe5-all/task-2-report.md` using the canonical 15 sections plus exact evidence.

#### 6. TIÊU CHÍ NGHIỆM THU & TEST (ACCEPTANCE & TESTS)

Acceptance:

- [ ] All seven named routes render under PT layout, are PT-only in presentation, and have correct menu/breadcrumb/active state.
- [ ] Service tests prove exact URL/method/query/body/envelope for self profile and every PT member contract; no Admin/Member-self endpoint.
- [ ] Store tests prove cache isolation, A→B late response rejection, unmount/newer request cleanup, logout/role/valid-token-401 purge, old-token late-401 safety, 403/404 purge and assignment-list loss.
- [ ] All seven screens cover Loading/Data/Empty/Error retry plus relevant 401/403/404/422/network/5xx states.
- [ ] Profile/note validation, draft retention, double-submit prevention and ambiguous mutation recovery are tested.
- [ ] Plan is official/read-only and distinct from Proposal; history snapshot is immutable/read-only; note UI is append-only.
- [ ] Progress exercise choices come from official Plan; no fabricated IDs/BMI diagnosis.
- [ ] Accessibility assertions cover labels/errors/focusable controls/ARIA active and dynamic states; responsive layout reviewed at 1440/768/390.
- [ ] Existing Admin trainer service, auth, guard and layout tests remain green.
- [ ] No dependency/package/lock/config change.

Focused commands from `E:/Fitness/FE` (all shell invocations retain RTK prefix):

```powershell
rtk proxy npm run test -- src/services/huan_luyen_vien.api.test.js src/services/hoi_vien_pt.api.test.js src/services/tien_do.api.test.js src/services/ke_hoach_tap_pt.api.test.js src/services/lich_su_tap_pt.api.test.js src/services/ghi_chu.api.test.js src/stores/hoi_vien_pt.store.test.js src/stores/xac_thuc.store.test.js src/router/dieu_huong_pt.test.js src/router/index.test.js src/router/bao_ve_tuyen_duong.test.js src/layouts/bo_cuc.test.js src/pages/xac_thuc.test.js src/components/pt/thanh_dieu_huong_hoi_vien.test.js src/components/pt/the_tien_do.test.js src/pages/pt/ho_so/ho_so.index.test.js src/pages/pt/hoi_vien/hoi_vien.index.test.js src/pages/pt/hoi_vien/hoi_vien.chi_tiet.test.js src/pages/pt/tien_do/tien_do.index.test.js src/pages/pt/ke_hoach_tap/ke_hoach_tap.index.test.js src/pages/pt/lich_su_tap/lich_su_tap.index.test.js src/pages/pt/ghi_chu/ghi_chu.index.test.js
rtk proxy npm run test
rtk proxy npm run lint
rtk proxy npm run build
rtk proxy npm ls --depth=0
```

Final repository checks from `E:/Fitness`:

```powershell
rtk git diff --check
rtk git status --short --branch
rtk git diff -- FE/src
```

Because Task 1 changed Backend, controller/final verification must also rerun guarded Backend focused/full suite, Pint and Composer checks from Task 1. Browser smoke, if the controlled browser surface is available, must verify 1440x900, 768x1024, 390x844; keyboard/focus, no horizontal overflow, loading/empty/error/retry, scope-loss redirect and no sensitive stale data.

Static scans must prove no `DELETE`, Plan/Session mutation, Proposal endpoint, `/api/workout` Member-self call, `/api/admin` call, authority-field injection, token/PII logging, prohibited dependency/font/motion/dark/purple styling or FE6+ route.

Report exact test counts/results/skips, build/lint/npm tree, browser evidence or explicit unavailability, and `git diff --check/status`. Không sửa test/contract để che failure.

#### 7. ĐỊNH DẠNG BÁO CÁO HOÀN THÀNH (CANONICAL 15-SECTION REPORT)

```markdown
### Báo cáo Hoàn thành Nhiệm vụ: FE5-ALL
1. Muc tieu module
2. File da tao
3. File da sua
4. Database lien quan
5. API da tao/sua
6. Ham chinh
   - ten ham
   - muc dich
   - cach hoat dong
7. Business Rule da xu ly
8. Authorization
9. Validation
10. Transaction / Idempotency
11. Error Case
12. Test Case
13. Test Result
14. Phan chua hoan thanh
15. Rui ro con lai
```

Sau 15 mục, thêm actor/use case, concurrency/audit, exact commands/results, browser/static evidence, baseline preservation, concerns và dependency-gate evidence. Writer không cập nhật checkpoint. Chỉ controller-authorized role được cập nhật checkpoint sau Task Reviewer + Final Reviewer PASS.

#### 8. ĐIỀU KIỆN LEO THANG (ESCALATE WHEN)

- Thiếu/sai route hoặc DTO BLOCKER-03/04/05 so với source/test Task 1.
- Cần file ngoài ALLOWED_FILES, dependency/config/package/lock change hoặc Backend change.
- Assignment/error semantics không đủ để purge/redirect an toàn; request race không thể giải quyết bằng patterns hiện hữu.
- Có ambiguity Business Rule về Plan/Proposal, history immutability, notes hoặc progress.
- Có pre-existing/user change overlap hunk.

**FULL_CANONICAL_REREAD:** CONDITIONAL — chỉ tra đúng phần authority khi conflict/ambiguity/version mismatch; dừng báo controller thay vì tự mở rộng scope.
