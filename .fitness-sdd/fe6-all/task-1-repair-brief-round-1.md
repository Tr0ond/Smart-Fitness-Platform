# FE6-ALL — repair brief, round 1

STATUS: READY_FOR_FIXER
TASK: one aggregate FE6-ALL; repair only open Important findings F-001 through F-004 from `task-1-review.md`.

## Authority, gate, and scope

- Retain **all** `REQUIRED_CONTEXT`, `CANONICAL_RULE_IDS`, entry-gate evidence, API contracts, and the 24-path `ALLOWED_FILES` list in `E:/Fitness/.fitness-sdd/fe6-all/task-1-brief.md`. Read the review, report, baseline artifacts, `task-1-round-0-before/`, `task-1-round-0-after/`, task delta, and current source before editing. The initial product tree was clean; current files inspected for these findings match the round-0 after snapshot byte-for-byte. The baseline staged/working patches are empty.
- Required business behavior remains Q06 exact-term Backend quota authority and first-use activation, Q13 assignment-only proposal create, Member-only proposal apply, stable UUIDv4/body retry, 409 draft preservation, and 403/404 scope cleanup. Do not alter Backend, API payloads, schemas, routes, stores, services, shared confirmation component, or the 100-row semantics for these repairs.
- Exact intended product files (all within the original allow-list):
  1. `E:/Fitness/FE/src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.tao_moi.vue`
  2. `E:/Fitness/FE/src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.tao_moi.test.js`
  3. `E:/Fitness/FE/src/pages/pt/buoi_huan_luyen/buoi_huan_luyen.index.vue`
  4. `E:/Fitness/FE/src/pages/pt/buoi_huan_luyen/buoi_huan_luyen.index.test.js`
  5. `E:/Fitness/FE/src/components/PT/khung_xem_de_xuat.vue`
  6. `E:/Fitness/FE/src/components/PT/khung_xem_de_xuat.test.js`
- Reuse `E:/Fitness/FE/src/components/dung_chung/hop_thoai_xac_nhan.vue` without editing it. The proposal-list page can keep its route-query open/close contract; the drawer can capture the active preview trigger itself. If implementation truly requires any seventh product file, stop and report that path to the controller before a writer touches it.
- No new context module or canonical section is needed for these four findings. Checked the selected FE6 rules, actual Backend request and structure validator, and Vue plan Sections 23.41/23.43 and the shared component inventory. They agree; no new business decision or canonical escalation arose in this repair plan. The task reviewer previously consulted the narrow Q06/RULE GYM 24/RULE CODE 20 canonical sections, with no conflicting ruling.

## Finding-to-repair map

### F-001 — duplicate day/exercise order after middle remove and add

- **Root cause:** The composer removes an array item with `splice` but `themNgayTap` and `themBaiTap` calculate `order` from array length. When a middle item is removed, that number can already belong to the remaining last item. The Backend `WorkoutPlanService::damBaoCauTruc` rejects duplicate day order or weekday (`DUPLICATE_PLAN_DAY`) and duplicate exercise order (`INVALID_PLAN_EXERCISE`).
- **Change:** In the composer, replace inline removal with named handlers. Keep order unique and within 1..7 for days and 1..50 for exercises after remove/add, preferably by renumbering surviving items by array position after each removal and assigning the next position on add. Do not rewrite existing weekdays on removal. For a newly added day, choose an unused weekday from 2..8 rather than deriving it from array length; maintain the seven-day cap. Keep the official-plan draft copy, user-edited content, and allowed request shape intact.
- **Regression tests:** Starting with three days and three exercises in one day, remove the middle item of each and add a replacement; assert unique day/exercise orders, unique valid weekdays, and a successful submit whose request body contains those valid sequences. Verify remaining day content and weekdays survive removal. Use the real composer/store/service normalization path where practical, not a mock that hides a malformed body.
- **Acceptance evidence:** The captured `taoDeXuatKeHoach` body satisfies the Backend uniqueness constraints after that ordinary edit sequence; no duplicate Vue keys or day weekday collision appears. Existing proposal creation and stable-key tests remain green.

### F-002 — PT direct completion lacks final confirmation

- **Root cause:** Form submit currently invokes `xacNhanHoanTat` immediately, so no explicit acknowledgment precedes a quota-consuming irreversible POST.
- **Change:** On initial form submit, open the existing `hop_thoai_xac_nhan.vue`; issue POST only on its confirm event. Display the selected Member name (fallback to Member ID) and the current assignment ID from verified profile context, and state that successful completion consumes one PT direct session from the Backend-selected eligible term and may activate the first term. Do not display a client-calculated remaining quota or require an already active Membership. Cancel/Escape must close without mutation and preserve the note. On confirm, recheck current Member/assignment and the pending guard, then call the existing completion action with the same note semantics. Keep unknown-outcome refetch and same-body/same-key retry intact; the initial confirmation covers that same logical action. Close/disable the dialog coherently during pending, scope loss, Member switch, and terminal response.
- **Regression tests:** Form submit opens the dialog without a service call or generated action key; cancel and Escape leave POST at zero and retain notes; confirm calls once with verified assignment and note; double confirmation during pending does not call twice; unknown-outcome retry still uses the original key/body and does not create a new action. Check dialog Member/assignment text and warning.
- **Acceptance evidence:** A direct-session POST occurs only after explicit confirmation and retains the existing Q06/idempotency behavior. Shared dialog keyboard handling is reused unchanged.

### F-003 — nested 422 errors are only raw-path summary text

- **Root cause:** `cacLoiTruong` flattens `fieldErrors` into `VungThongBao`; controls have no field-bound message, `aria-invalid`, or `aria-describedby` target. Laravel paths such as `plan.days.0.exercises.0.max_reps` cannot identify the control visually or to assistive technology.
- **Change:** In the composer, map existing `loiTaoDeXuat.fieldErrors` keys to the exact top-level, plan, day, and exercise inputs already rendered. For each affected control, show its message beside the control with a unique stable DOM id; set `aria-invalid="true"` and include that id in `aria-describedby`, preserving any existing help id. Keep an accessible overall error summary and preserve the draft. Handle representative nested paths with the current array indexes, including `plan.days.N.exercises.M.max_reps`; avoid raw path as the only explanation. When structural add/remove changes indexes, prevent a stale server error from being attached to a different row.
- **Regression tests:** Mock a 422 with `plan.days.0.exercises.0.max_reps` (and one day or top-level field), submit, then assert the draft remains, the precise input has `aria-invalid`, its `aria-describedby` resolves to visible error text, and the overall summary remains available. Test structure edits do not leave a prior indexed error on a different field.
- **Acceptance evidence:** An affected input and its error are programmatically associated; the nested message is visible at that field and summary remains accessible. No 409/unknown-outcome behavior changes.

### F-004 — proposal preview modal lacks keyboard focus lifecycle

- **Root cause:** `khung_xem_de_xuat.vue` only toggles a teleported overlay. The preview trigger keeps focus outside; an Escape listener on the overlay cannot receive that key. Tab can leave the modal and close does not return focus.
- **Change:** In the preview component, capture the invoking focused element when opening, move focus into the dialog after render (close control or focusable dialog container), trap Tab/Shift+Tab among enabled controls while open, and handle Escape from the active modal context. On close/unmount, remove listeners and restore focus to the captured trigger when still connected. Handle a missing/disconnected trigger safely. Preserve the existing `dong` event and route-query close behavior; no new preview route or PT apply action.
- **Regression tests:** Mount with Teleport behavior exercised, focus an external trigger, open the drawer, assert focus moves inside, Tab and Shift+Tab cycle rather than escape, Escape emits close, and closing restores focus to that trigger. Verify listener cleanup and safe behavior if the trigger is removed before close.
- **Acceptance evidence:** Keyboard-only preview open, traversal, close, and return work; content/status preview tests remain green.

## Focused verification and report

1. Before the writer, controller captures `task-1-round-1-before/` for these six exact target files. Afterward compare against that snapshot and the original baseline for unexpected paths; no pre-existing product hunk is authorized for removal.
2. From `E:/Fitness/FE`, run `rtk npm run test -- src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.tao_moi.test.js src/pages/pt/buoi_huan_luyen/buoi_huan_luyen.index.test.js src/components/PT/khung_xem_de_xuat.test.js src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.index.test.js`. Then run the full FE tests, `rtk npm run lint`, `rtk npm run build`, and `rtk git diff --check` on the final repair state. Report exact exit codes, counts, skips, environment, and remaining warnings.
3. Append **round 1** implementation, changed files, focused and full evidence, and any concern to `E:/Fitness/.fitness-sdd/fe6-all/task-1-report.md`. Leave the four findings open for an independent fresh re-review; a passing test suite alone does not close the gate.
