# MODULE DA HOAN THANH

Status: DONE_WITH_CONCERNS

## 1 Muc tieu module

Hoan thien 12 man hinh Admin Catalog FE3-ALL cho goi tap, dung cu, nhom co, bai tap va giao an mau. Giao dien su dung API that, store danh muc duy nhat, mutation co confirm/loading/error state, Q01 snapshot warning, Q11 equipment AND semantics, M061 inactive muscle-group lifecycle va copy-on-write revision voi optimistic concurrency.

## 2 File da tao

- `FE/src/stores/danh_muc.store.js`
- `FE/src/stores/danh_muc.store.test.js`
- `FE/src/services/goi_tap.api.js`, `goi_tap.api.test.js`
- `FE/src/services/dung_cu.api.js`, `dung_cu.api.test.js`
- `FE/src/services/nhom_co.api.js`, `nhom_co.api.test.js`
- `FE/src/services/bai_tap.api.js`, `bai_tap.api.test.js`
- `FE/src/services/giao_an_mau.api.js`, `giao_an_mau.api.test.js`
- `FE/src/components/danh_muc/bo_sua_quyen_loi_goi_tap.vue`, `.test.js`
- `FE/src/components/danh_muc/bo_chon_quan_he_bai_tap.vue`, `.test.js`
- `FE/src/components/danh_muc/cay_giao_an.vue`, `.test.js`
- `FE/src/components/danh_muc/hop_thoai_xung_dot_giao_an.vue`, `.test.js`
- 12 page Vue files va 12 page test files trong `FE/src/pages/admin/{goi_tap,dung_cu,nhom_co,bai_tap,giao_an_mau}/`.

## 3 File da sua

- `FE/src/router/index.js`: them 12 named Admin Catalog routes, static create/revision routes dung truoc param routes, meta Admin/breadcrumb.
- `FE/src/router/index.test.js`: giu router foundation coverage.
- `FE/src/router/dieu_huong_admin.js`: them 5 muc menu Catalog va related route names.
- `FE/src/router/dieu_huong_admin.test.js`: cap nhat coverage menu Catalog.
- `FE/src/stores/xac_thuc.store.js`: clear catalog state khi logout, actor-role loss/change va current-token 401.
- `FE/src/assets/main.css`: them light staff Catalog layout, form/table/dialog states va responsive breakpoints; pre-existing font import duoc bao toan theo preservation gate.

Khong co file Backend, Database, Mobile, FE4, rule, plan, snapshot hay checkpoint nao bi sua trong pham vi implementation.

## 4 Database lien quan

Khong sua Database. UI doc va gui du lieu cho cac resource `packages`, `equipment`, `muscle_groups`, `exercises`, `workout_templates` va `workout_template_revisions`. M061 status, inactive relation va audit van do Backend transaction/row lock quyet dinh.

## 5 API da tao/sua

- `GET/POST /admin/packages`, `GET/PATCH /admin/packages/{id}`, `PUT /admin/packages/{id}/benefits`.
- `GET/POST /admin/equipment`, `PATCH /admin/equipment/{id}`.
- `GET/POST /admin/muscle-groups`, `PATCH /admin/muscle-groups/{id}`.
- `GET/POST /admin/exercises`, `GET/PATCH /admin/exercises/{id}` voi search/status filter.
- `GET/POST /admin/workout-templates`, `GET/PATCH /admin/workout-templates/{id}`, `POST /admin/workout-templates/{id}/revisions`.

Client chi gui field allow-list theo contract; khong gui branch, creator, snapshot hay version authority ngoai `expected_content_version` cua revision.

## 6 Ham chinh (ten/muc dich/cach)

- Package: `taiDanhSachGoiTap`, `taoGoiTap`, `capNhatGoiTap`, `thayTheQuyenLoiGoiTap`.
- Equipment: `taiDanhSachDungCu`, `taoDungCu`, `capNhatDungCu`.
- Muscle group: `taiDanhSachNhomCo`, `taoNhomCo`, `capNhatNhomCo`.
- Exercise: `taiDanhSachBaiTap`, `taiChiTietBaiTap`, `taoBaiTap`, `capNhatBaiTap`, `taoPayloadQuanHeBaiTap`.
- Template: `taiDanhSachGiaoAnMau`, `taiNenGiaoAnMau`, `taiChiTietGiaoAnMau`, `taoGiaoAnMau`, `capNhatGiaoAnMau`, `taoPhienBanGiaoAnMau`, `xuLyGiaoAnMauDaCu`.
- Store action race guards discard stale reads; option lists use a 30-second short-lived cache and mutation invalidation.

## 7 Business Rule

- Q01 warning hien tren package detail/editor; benefits thay doi chi ap dung cho luot mua moi, snapshot ky cu khong doi.
- Q11 relation selector cho phep nhieu equipment va hien ro AND semantics; payload luon la `equipment_ids` day du.
- Equipment va exercise khong co hard-delete. Muscle group hien ca inactive, chi cho chon moi group `HOAT_DONG`, giu inactive relation da co va gui role dang hien.
- Template detail PATCH chi gui metadata/status, khong gui `days`. Revision gui cay days day du theo copy-on-write va giu ban nhap khi stale.

## 8 Authorization

Tat ca 12 routes dung `yeuCauXacThuc: true`, `vaiTro: ['ADMIN']`, `boCuc: 'admin'`. Axios dung interceptor auth hien tai; Backend van la noi quyet dinh `auth:api` va `role:ADMIN`. Menu chi la presentation va duoc loc theo route registry.

## 9 Validation

Service validate positive IDs, status enum, required metadata, numeric ranges, benefits rule (AI off limit 0; AI on null/positive), unique relation IDs/roles, tree day/exercise order, reps min/max, active-form shape va `sessions_per_week === days.length`. Form dung native labels, `aria-invalid`, error text, disabled submit va visible focus/dialog semantics.

## 10 Transaction / Idempotency

Client khong them Idempotency-Key cho catalog mutation theo contract. Submit button/dialog duoc disable khi pending; khong blind retry. Network/5xx mutation duoc danh dau `outcomeUnknown` de nguoi dung doi soat. Backend giu transaction, audit va concurrency invariants cho M061/revision.

## 11 Error Case

List/detail co loading, empty, safe error va manual retry states. 403/404/422/5xx/network errors khong hien raw Axios data. Revision `WORKOUT_TEMPLATE_STALE` 409 mo dialog xung dot, giu local draft va chi tai lai khi nguoi dung chon. Backend `INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE` duoc truyen an toan vao field/message state.

## 12 Test Case

- API endpoint/payload allow-list, no-delete, Q01 benefit validation, Q11 relation arrays, tree validation, no-days PATCH, expected version/stale classification.
- Store stale-read race, cleanup invalidation, short-lived option cache va ambiguous mutation handling.
- Shared benefit/relation/tree/conflict components: controls, emissions, role/AND display, dialog action.
- Router/menu/auth cleanup coverage va 12 page registration smoke tests.

## 13 Test Result

- Focused FE3 + router/auth: **25 test files, 73 tests passed** (`npm run test -- --run ...`).
- Full frontend suite: **58 test files, 604 tests passed** (`npm run test`).
- `npm run lint`: **exit 0**; 1,170 existing/style warnings reported by ESLint Vue formatting rules, 0 errors.
- `npm run build`: **exit 0**, Vite production bundle generated.
- `npm ls --depth=0`: **exit 0**, dependency tree valid and unchanged.
- Static scans: **pass** for catalog DELETE calls, hard-coded API roots, secret/token logging, forbidden dependencies, `handleXxx`, TODO/FIXME and skipped/only tests.
- `git diff --check`: **pass**; staged diff: **empty**.
- Exact target existence check: **all 51 allow-list paths present**; pre-existing dirty files/hunks preserved.
- Browser smoke 1440/768/390: **not run** because no existing authenticated local app session was available in this implementation run; no new browser infrastructure was introduced.

## 14 Phan chua hoan thanh

Khong con production screen/API/store target nao thieu. Browser smoke voi session that va lint warning cleanup remain follow-up work; lint warnings khong lam command fail.

## 15 Rui ro con lai

- Browser-level responsive/authenticated smoke is unverified in this run.
- ESLint emits many formatting warnings because new compact Vue templates follow the existing command's non-failing warning policy.
- The preserved pre-existing external font import remains in `main.css` to satisfy the snapshot/preservation baseline; review may decide whether a later dedicated visual-system task should migrate it.

