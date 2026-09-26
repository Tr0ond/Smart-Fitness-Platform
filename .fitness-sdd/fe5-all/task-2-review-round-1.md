VERDICT: FAIL
SPEC: FAIL
QUALITY: FAIL

# FE5-ALL Task 2 — Independent round-1 re-review

## Round-0 finding resolution

- **FE5-T2-R01 — CLOSED.** `FE/src/pages/pt/ho_so/ho_so.index.vue:36-44,49-55,67-73,101-109` unwraps the service's Backend `{ data: dto }` body once for GET, PATCH and timeout reconciliation. The page test uses nonempty enveloped fields, preserves the untouched specialty on save and retains the draft without a second PATCH on timeout. `FE/src/services/huan_luyen_vien.api.test.js` verifies the Axios `{ data: { data: dto } }` shape, self URL/body and intact Admin exports.
- **FE5-T2-R02 — CLOSED.** `FE/src/pages/pt/hoi_vien/hoi_vien.chi_tiet.vue:36-39,115-167` renders the safe member coaching and assignment DTO, including nullable end-date fallback. Its revised fixture omits email, phone, status and trainer object, and the 404 redirect regression remains.
- **FE5-T2-R03 — CLOSED for the reported independent-query defect.** `FE/src/stores/hoi_vien_pt.store.js:184-192,309-409` gives overview, body and exercise separate loading/error state and generations; Plan already has its own. `FE/src/pages/pt/tien_do/tien_do.index.vue:153-351` renders per-panel loading/error/empty/data and retries the matching request, including the selected official exercise. Deferred tests cover both overview/body completion orders, separate failures, Plan failure, exercise retry and cleanup.
- **FE5-T2-R04 — CLOSED.** `FE/src/stores/hoi_vien_pt.store.js:552-577` rechecks selected member and note-mutation generation after awaited reconciliation before committing `outcomeUnknown`. Six deferred resolve/reject tests cover member switch, selected-member cleanup and global/auth cleanup; the same-member unknown-outcome test still asserts one POST.

## Findings

- **ID: FE5-T2-R05**
  - **Severity:** Important
  - **File:** `E:/Fitness/FE/src/stores/hoi_vien_pt.store.js`
  - **Line:** 241
  - **Rule:** Task 2 brief §5 and §6 (current assigned list, 403/404 purge, assignment-list loss); Q04 and RULE CODE 17.
  - **Evidence:** After an assigned list has loaded, `daTaiDanhSachHoiVien && !force` returns the cached list without a GET. A member detail/Plan/progress/history/notes 404 calls `xuLyMatPhamVi`, which clears selected-member caches but leaves `danhSachHoiVien` and `daTaiDanhSachHoiVien` intact (`store.js:584-617`). The page redirects to `ptHoiVien`; its mount calls `taiDanhSach()` without `force` (`hoi_vien.index.vue:52-57`), so the old member remains rendered and linked by `v-if="danhSachHoiVien.length > 0"` (`hoi_vien.index.vue:93-119`). A list-level 403/404 likewise retains the old list. This is a deterministic stale-scope path after Backend revocation; the existing assignment-refresh test calls the store directly and does not exercise the redirect/cache path.
  - **Required fix:** Invalidate or remove the affected list entry on scope loss, force an authoritative assigned-list refresh on return, and clear the sensitive list on list-level authorization failure. Add a regression for loaded A list → A detail 404 → list redirect and for a list 403/404 after cached data.

- **ID: FE5-T2-R06**
  - **Severity:** Important
  - **File:** `E:/Fitness/FE/src/pages/pt/ghi_chu/ghi_chu.index.vue`
  - **Line:** 74
  - **Rule:** Task 2 brief §5 and §6 (member-scoped cache isolation, A→B cleanup and append-only note correctness); RULE CODE 17.
  - **Evidence:** `banNhap.noi_dung` is a component-local reactive draft (`line 25`). Navigating directly from `/pt/hoi-vien/A/ghi-chu` to `/pt/hoi-vien/B/ghi-chu` reuses the same unkeyed route component (`FE/src/App.vue:24-29`); the route watcher only reloads notes and switches the store member (`lines 44-52,74-76`). It neither clears nor associates the draft with A. The form then submits that still-visible A draft with the current route ID B (`lines 56-58,128,148`), causing a valid but wrong-member append. The current page tests exercise only one route ID.
  - **Required fix:** Bind the note draft to the selected member or clear it on member-ID change while retaining it for 422/timeout of the same member. Guard submission against a draft whose owner differs from the current route, and test A→B navigation and POST target.

- **ID: FE5-T2-R07**
  - **Severity:** Important
  - **File:** `E:/Fitness/FE/src/stores/hoi_vien_pt.store.js`
  - **Line:** 485
  - **Rule:** Task 2 brief §5 and §6 (history list/detail Loading/Data/Empty/Error retry, no stale partial detail); RULE GYM 13/15.
  - **Evidence:** After session A detail succeeds, selecting session B sets `dangTaiChiTietPhien` and clears only `loiChiTietPhien` (`store.js:484-486`); it never clears or keys the visible `chiTietPhien`. The page changes `phienDangXem` to B (`lich_su_tap.index.vue:59-63`) but renders any non-null old detail even while B is loading or after B fails (`lines 153-204`). Thus A's snapshot remains visible beside B's loading/error state. The history page tests cover one successful detail and an empty list, not two sessions with a failing second detail.
  - **Required fix:** Clear or key visible detail by requested session ID at the start of each new detail request; keep late-generation rejection and member-scope purge. Add a deferred A→B detail test and a B-error/retry test proving A's snapshot is hidden during B loading and failure.

## Specification and quality assessment

The four reported repair defects are closed, and the seven named PT routes, PT-only route metadata/navigation, official Plan source, read-only history operations, append-only note endpoint, profile allow-list, Backend envelope boundary, and separate progress query retries are present. The three Important findings above still violate original Task 2 acceptance, so both SPEC and QUALITY fail. No Backend or canonical conflict required escalation: the selected rules and source contracts resolve these cases.

## Test evidence

- Independently ran the five affected files: **5 passed files, 33 passed tests**, exit 0.
- Independently ran the exact Task 2 focused command from `task-2-brief.md`: **22 passed files, 156 passed tests**, exit 0.
- Independently ran `rtk proxy npm run test` from `E:/Fitness/FE`: **79 passed files, 781 passed tests**, exit 0; no skips reported.
- `rtk proxy npm run lint`: exit 0, **0 errors, 110 warnings** (107 auto-fixable, existing FE5 Vue formatting warnings).
- `rtk proxy npm run build`: exit 0, 190 modules transformed; generated JS 581.00 kB and a >500 kB chunk advisory.
- `rtk proxy npm ls --depth=0`: exit 0, top-level dependency tree resolved; no invalid/extraneous package reported.
- Static `rtk rg` scan of PT pages/services/store found no `/api/admin`, Member-self `/api/workout`, Proposal endpoint, DELETE, console logging, forbidden UI/font/motion dependency, dark or purple styling pattern.
- `rtk git diff --check`: exit 0, no output. `rtk git status --short --branch`: branch `main`, expected Task 1 Backend and Task 2 FE working changes plus workflow scratch; no staged files or unrelated product change observed.

## Scope integrity

Original baseline status was clean and both baseline patches are zero bytes. Round-0 Task 2 before/after comparison found **42 changed of 44 allowed paths**; two allowed files remained unchanged. Round-1 before/after byte comparison found **exactly nine changed paths**, all listed in `task-2-repair-review-package-round-1.md`; the other 35 are identical. All 44 live allowed files match `task-2-round-1-after/` byte for byte, and all round-1 before files match `task-2-round-0-after/`. Current status retains the Task 1 Backend/contract delta and no package, lock, config, rules, checkpoint, baseline or snapshot edit attributable to this repair. Windows resolves `components/pt` to the repository's canonical `components/PT` directory.

## Residual risks and Task 2 → Final Review gate

- Authenticated browser smoke of the seven protected pages at 1440×900, 768×1024 and 390×844 remains unverified because no safe credential/session fixture was available. Existing public login-shell evidence does not cover protected focus, responsive overflow, retry and scope-loss behavior.
- Existing lint warnings and the Vite bundle-size advisory are nonblocking quality debt; no dependency change is implicated.
- **Gate CLOSED — FAIL.** FE5-ALL Task 2 must repair FE5-T2-R05/R06/R07 and obtain a fresh independent Task Reviewer PASS before whole-phase Final Review. The green automation does not exercise the three deterministic state transitions above. No product source or test was edited in this review.
