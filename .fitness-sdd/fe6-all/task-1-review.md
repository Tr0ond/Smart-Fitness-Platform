VERDICT: FAIL
SPEC: FAIL
QUALITY: FAIL

FINDINGS
- ID: F-001
  Severity: Important
  File: E:/Fitness/FE/src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.tao_moi.vue
  Line: 107
  Rule: FE6-ALL validated plan structure; Vue plan Section 23.43; Backend WorkoutPlanService::damBaoCauTruc.
  Evidence: themNgayTap and themBaiTap derive order from current array length, while the remove buttons at lines 311 and 370 splice without renumbering. With three days/exercises, removing the middle item and adding one assigns order 3 to a new item while the old order 3 remains. The FE service accepts the duplicate; Backend rejects duplicate day order with DUPLICATE_PLAN_DAY and duplicate exercise order with INVALID_PLAN_EXERCISE (422). This ordinary editing sequence cannot produce a valid proposal.
  Required fix: Keep day/exercise order unique after removal and addition (renumber or choose the next unused order), preserve weekday uniqueness for days, and add composer regression tests for middle remove/add and valid submission.
- ID: F-002
  Severity: Important
  File: E:/Fitness/FE/src/pages/pt/buoi_huan_luyen/buoi_huan_luyen.index.vue
  Line: 133
  Rule: Vue plan Section 23.41 requires a confirm dialog and quota warning for PT direct completion; visual-ui.md Section 5 requires confirmation for a consequential mutation.
  Evidence: Submitting the form calls xacNhanHoanTat directly. The page has no confirmation dialog or explicit final acknowledgment before the Backend records an irreversible completed PT session and consumes one term-specific direct session. The existing shared hop_thoai_xac_nhan component is not used.
  Required fix: Present a confirmation dialog identifying the selected Member/assignment and quota-consuming action before POST, retain the pending/idempotency behavior, and test cancel versus confirm.
- ID: F-003
  Severity: Important
  File: E:/Fitness/FE/src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.tao_moi.vue
  Line: 383
  Rule: task-1-brief.md requires nested 422 field-error mapping; visual-ui.md Section 6 requires input-linked error/help text with aria-describedby.
  Evidence: Laravel fieldErrors are flattened into a VungThongBao summary containing raw paths such as plan.days.0.exercises.0.max_reps. None of the proposal inputs renders its own error, aria-invalid, or aria-describedby link. The existing composer tests do not exercise a nested 422 response. Users cannot locate the faulty control from the form, and the required field-level error state is absent.
  Required fix: Render and associate nested 422 errors with the affected controls while retaining an accessible summary, and add a component test for a representative nested error.
- ID: F-004
  Severity: Important
  File: E:/Fitness/FE/src/components/PT/khung_xem_de_xuat.vue
  Line: 30
  Rule: visual-ui.md Section 6 requires dialog keyboard focus trap, return, and Escape behavior.
  Evidence: Opening the teleported preview only toggles v-if. It does not move focus into the dialog, trap Tab, or restore focus to the preview trigger on close. The Escape listener is attached inside the overlay, so Escape from the still-focused trigger outside it will not close the dialog. The preview test only checks text and omits keyboard/focus behavior.
  Required fix: Move focus into the drawer when opened, trap focus while modal, close on Escape from its active focus context, restore focus to the invoking button, and test keyboard operation.

TEST EVIDENCE
- Independently ran `rtk npm run test -- src/services/buoi_huan_luyen.api.test.js src/services/de_xuat.api.test.js src/stores/de_xuat.store.test.js src/stores/hoi_vien_pt.store.test.js src/pages/pt/buoi_huan_luyen/buoi_huan_luyen.index.test.js src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.index.test.js src/pages/pt/de_xuat_ke_hoach_tap/de_xuat_ke_hoach_tap.tao_moi.test.js src/components/PT/khung_xem_de_xuat.test.js` from E:/Fitness/FE: 8 files, 63 tests passed, exit 0.
- Independently ran `rtk npm run test` from E:/Fitness/FE: 86 files, 846 tests passed, exit 0, no skips reported.
- Independently ran `rtk npm run lint`: exit 0, 0 errors, 293 warnings; and `rtk npm run build`: exit 0, 200 modules transformed, 631.89 kB generated JS, chunk-size warning.
- Independently ran `rtk git diff --check`: exit 0. Inspected live source, tracked diff, new files, baseline status/untracked inventory, baseline-tree, round-0 before absent markers and after snapshot, task delta and review package. Initial product tree was clean; 23 changed product paths match the 24-path allow-list (14 new, 9 modified, one unchanged), with no unexpected product path or pre-existing product hunk to preserve. The named baseline-working.patch and baseline-staged.patch are absent; their intended contents were empty given the recorded clean tracked baseline.
- Inspected actual Backend routes, FormRequest shape, PT service DTOs, workout structure validator and relevant Vue plan sections. The Controller supplied isolated Backend test evidence (24 tests/180 assertions on smart_fitness_fe5_round2_test); I did not rerun database tests or select a default database.
- Consulted PROJECT_RULES.md Q06/RULE GYM 24 and RULE CODE 20 sections on escalation for code versus documented behavior; they confirm Backend quota authority, Member-only plan apply, and that a passing UI/test suite alone is insufficient. No additional business decision is needed for the findings.

RESIDUAL RISKS
- GET history/proposal lists cap at 100 and omit idempotency keys, so read-only reconciliation remains inconclusive; the current same-key retry design correctly preserves Backend authority.
- The 631.89 kB build chunk and 293 lint warnings are nonblocking quality debt.
