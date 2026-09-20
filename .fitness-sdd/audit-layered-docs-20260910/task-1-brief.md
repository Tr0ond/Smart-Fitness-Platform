# Task 1 Brief — Synchronize the Complete Layered Context System

## Objective

Make the layered documentation a semantically faithful, self-consistent minimal-context representation of the current working-tree `PROJECT_RULES.md` and `docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md`. This is one atomic documentation task. Do not change product behavior or either authority.

## Allowed paths

Only the exact targets below may be created or edited:

1. `E:\Fitness\.fitness-rules\PROJECT_CORE.md`
2. `E:\Fitness\.fitness-rules\RULE_INDEX.md`
3. `E:\Fitness\.fitness-rules\domains\ai-proposal.md`
4. `E:\Fitness\.fitness-rules\domains\auth-resource-scope.md`
5. `E:\Fitness\.fitness-rules\domains\database.md`
6. `E:\Fitness\.fitness-rules\domains\membership-payment.md`
7. `E:\Fitness\.fitness-rules\domains\pt-chat.md`
8. `E:\Fitness\.fitness-rules\domains\workout.md`
9. `E:\Fitness\.fitness-rules\engineering\coding-conventions.md`
10. `E:\Fitness\.fitness-rules\engineering\security-integrity.md`
11. `E:\Fitness\.fitness-rules\engineering\testing-definition-of-done.md`
12. `E:\Fitness\.fitness-rules\frontend\vue-core.md`
13. `E:\Fitness\.fitness-rules\frontend\visual-ui.md`
14. `E:\Fitness\.fitness-sdd\context\FE0_CONTEXT.md` (new)
15. `E:\Fitness\.fitness-sdd\context\FE1_CONTEXT.md` (new)
16. `E:\Fitness\.fitness-sdd\context\FE2_CONTEXT.md` (new)
17. `E:\Fitness\.fitness-sdd\context\FE3_CONTEXT.md`
18. `E:\Fitness\.fitness-sdd\context\FE4_CONTEXT.md`
19. `E:\Fitness\.fitness-sdd\context\FE5_CONTEXT.md`
20. `E:\Fitness\.fitness-sdd\context\FE6_CONTEXT.md`
21. `E:\Fitness\.fitness-sdd\context\FE7_CONTEXT.md`
22. `E:\Fitness\.fitness-sdd\context\FE8_CONTEXT.md`
23. `E:\Fitness\.fitness-sdd\context\FE9_CONTEXT.md`
24. `E:\Fitness\.fitness-sdd\context\TASK_PACKET_TEMPLATE.md`

No other file is declared. In particular, do not edit `PROJECT_RULES.md`, the Vue plan, any `AGENTS.md`, `BE/**`, `FE/**`, Mobile, `docs/**`, checkpoint/report files, or `.fitness-sdd/audit-layered-docs-20260910/**` artifacts. Use `apply_patch` for every text edit. Do not commit, push, reset, restore, checkout, stash, clean, or dispatch subagents.

## Required edits by target

### Core and routing

- `PROJECT_CORE.md`
  - Narrow Q08 to viewing owned Plan/Schedule and Start/Save Set/Complete from an owned valid existing schedule; explicitly keep Free Workout outside MVP. Remove wording that grants schedule creation.
  - Stop calling all usage-ledger rows append-only/immutable; distinguish immutable history identity from allowed canonical status transitions such as AI quota reservation/finalization/refund.
  - Add a concise M061 core invariant: `nhom_co.trang_thai` values/default, GET includes inactive, no DELETE, inactive existing pivot preservation, replacement-payload rule, `INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE`, same-state no-op, atomic audit, and race serialization.
  - Where proposal stored values are named, use `TRO_LY` / `HUAN_LUYEN_VIEN`, while allowing AI/PT as display/domain labels.
  - Preserve the layered escalation policy but state that an explicit workflow/role contract may require a full canonical read.

- `RULE_INDEX.md`
  - Bump/update its version metadata to reflect this synchronized revision without inventing a business-rule version.
  - Add complete routing rows for FE-0 Foundation, FE-1 Admin Foundation, and FE-2 Admin PT, referencing the three new context files.
  - Make all phase-context paths unambiguous repository-relative paths (or correct relative links from `.fitness-rules/`); no pseudo-link may resolve under `.fitness-rules/.fitness-sdd`.
  - Route M061 catalog work through `workout.md` plus `security-integrity.md`; mention the exact new invariant/error code.
  - Remove provider-specific “Gemini Flash” wording from the AI manifest; canonical AI is provider-agnostic.
  - State the FE-3→FE-9 one-phase/one-task entry-gate rule and prevent the index from authorizing READY-subset writers.
  - Keep authoritative counts exactly 65 capabilities, 50 router records, 48 screens, 7 blockers, 4 fix rows wherever counts are included.

### Domain modules

- `domains/ai-proposal.md`
  - Resolve the contradictory source enums: stored AI source is `TRO_LY`; PT is `HUAN_LUYEN_VIEN`. Never use stored `'AI'`/`'PT'`.
  - Keep provider choice generic and restore omitted confirm-time revalidation of ownership, base version/context, TTL/status under lock, transaction, idempotency, history preservation, and audit.
  - Preserve Q03's one-business-request quota reservation/finalization/refund lifecycle and late/retry safety; do not imply every message/provider call consumes a turn.

- `domains/auth-resource-scope.md`
  - Remove Receptionist dashboard from scope and make the actor table match the Vue plan: lookup, Membership status/eligibility, QR, and basic support only.
  - Replace wrong account states `KHOA`/`CHO_KICH_HOAT` with official `HOAT_DONG`, `BI_KHOA`, `NGUNG_HOAT_DONG`; keep Membership states separate.
  - Replace the nonexistent `/api/v1/member/workouts/{id}` example with a canonical/current generic resource example or a real current route.
  - Describe PT read/send scope with Q05 precision and Admin/Receptionist least privilege without inventing endpoints.
  - Do not reduce entitlement to only `DANG_HOAT_DONG`; acknowledge the valid activatable head-term path and queued-term prohibition.

- `domains/database.md`
  - Replace proposal source values `AI`/`PT` with `TRO_LY`/`HUAN_LUYEN_VIEN`.
  - Add M061 to the official mapping without changing the 52-table count: `nhom_co.trang_thai`, audit action, inactive-pivot rules, and no DELETE.
  - Remove brittle canonical line-number references; use stable sections/table names.
  - Clarify that history preservation does not prohibit canonical state transitions on ledger/request rows.

- `domains/membership-payment.md`
  - Fix tail-term lifecycle: an active-chain purchase is queued (`CHO_DEN_LUOT` in the official schema), not a new head `CHO_KICH_HOAT`; only the first term of a not-yet-started/new chain waits for first paid use.
  - Explain that time-based entitlement resolution at term boundaries is authoritative and must not depend solely on a background status updater.
  - Retain exact first-use triggers, common clock, no future-term borrowing, Q01/Q02/Q10, QR one-use/90-second server policy, and Payment Admin read-only boundaries.
  - Avoid prescribing an HTTP success response or formula not stated by the authority unless explicitly labeled as verified current API behavior.

- `domains/pt-chat.md`
  - Use official stored proposal source `HUAN_LUYEN_VIEN`.
  - Restore the full Section 33.1 distinction: exactly one Chat usage only when the Member message wins activation; later Member messages, PT messages, reads, opens, subscribes, and already-activated terms create no Chat usage; first-message/outbox failure rolls back; retries return the same message/usage.
  - Restore confirm-time PT Proposal checks: source assignment still valid, base version/ownership/future schedule/TTL/status under lock, conflict to terminal state, idempotent single apply, no Membership/Chat/direct-quota prerequisite or side effect.

- `domains/workout.md`
  - Replace every generic `hoat_dong = 0` catalog statement with the correct per-resource lifecycle; for Muscle Group reproduce M061 exactly and do not conflate it with Equipment/Exercise.
  - Correct `WORKOUT_TEMPLATE_STALE`: it is a 409 revision optimistic-concurrency result tied to `expected_content_version`, not a persistent flag added to Member plans when a template changes. Keep copy-on-write/history as a separate invariant.
  - Remove invented implementation fields/side effects not in the authorities (for example automatic calorie/volume totals) and correct section references.
  - Narrow Q08 to Start from an existing valid owned schedule; do not grant free workout or direct schedule creation. For retry after a canceled session, require a valid replacement schedule/version workflow and preserve the old session/FK.
  - Do not say PT notes are attached to a completed session unless the official contract says so; state only that notes cannot mutate Plan/history.

### Engineering and frontend modules

- `engineering/coding-conventions.md`
  - Replace unsafe FE naming examples such as `kichHoatGoiTap()` and `tinhSoNgayConLai()` that suggest client authority with neutral read/render/request examples.
  - Match canonical docblock scope: important business functions require meaningful purpose/input/process/output/side-effect documentation; do not require full PHPDoc on every Controller/Model/Request/Policy.
  - Preserve framework exceptions and naming conventions from the Vue plan.

- `engineering/security-integrity.md`
  - Add M061's row-lock/atomic audit/no-op/race requirements precisely, without assuming the same lifecycle for Equipment.
  - Use official proposal source/status names and preserve all ten canonical idempotency categories.
  - Make the Chat activation transaction include message, one activation usage, and outbox rollback/retry semantics.

- `engineering/testing-definition-of-done.md`
  - Expand the currently abbreviated tests to retain the meaningful canonical cases from sections 48–54.2, including role regrant/audit, Q04/Q12/M061, payment abnormal ordering, Membership activation/queues, QR boundary/replay, scheduled Workout/Q08, AI quota/provider retry, Chat activation/Q05, PT quota races/Q06, and PT Proposal Q13/conflicts.
  - Replace the nonexistent `/api/v1/member/workouts/...` example.
  - Replace the four-section completion template with the exact 15-section PHẦN XXI report, while allowing phase-specific evidence fields in addition.
  - Do not state that a phase is complete merely because all tests that happened to run passed; require the proportionate required suite and final-state evidence.

- `frontend/vue-core.md`
  - Restore the authoritative auth edge cases: `/me` 401 clears only the matching current token; late 401 from an old token cannot clear a new session; network/timeout/5xx keeps token in `sessionStorage` but clears in-memory authority, blocks protected content, and offers retry.
  - Use a token accessor to avoid circular store/client coupling.
  - Preserve the seven-store plan as the approved architecture, but do not turn it into authorization authority.
  - Expand realtime summary to the actual channel/event/public config, REST-first send semantics, message-id/sequence merge, `before_sequence` catch-up, and Q05 leave/clear/stop-reconnect behavior.

- `frontend/visual-ui.md`
  - Keep visual tokens and counts aligned with Section 7A; qualify hard-coded sizing prohibition so it targets duplicated design/business tokens, not every one-off CSS measurement.
  - Ensure query states, keyboard/focus, form labels/errors, `aria-busy`/`aria-live`, and responsive acceptance match the final gate without inventing a new design system or dependency.

### Phase contexts and task packet

- Create `FE0_CONTEXT.md`, `FE1_CONTEXT.md`, and `FE2_CONTEXT.md` from the exact phase/task definitions in Vue-plan Sections 31 and 38A. Each must include scope/routes, exact expected file families, required minimal modules, functions/docblocks, tests, entry/exit gates, blockers/stop conditions, and the historical small-task codes. Do not invent screens, APIs, dependencies, or a phase-wide task code for FE-0→FE-2.

- `FE3_CONTEXT.md`
  - Replace `hoat_dong = 0` with official catalog status semantics and add the entire M061 replacement/no-op/audit/concurrency acceptance set, including `INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE`.
  - Correct function names to the plan inventory (`taoDungCu`, `capNhatDungCu`, `taoNhomCo`, `capNhatNhomCo`) and list exact component paths already named by the plan (`bo_chon_quan_he_bai_tap.vue`, `bo_sua_quyen_loi_goi_tap.vue`, `cay_giao_an.vue`). Do not invent a stale flag or unnamed endpoint.
  - Clarify 409 `WORKOUT_TEMPLATE_STALE`/`expected_content_version` semantics.

- `FE4_CONTEXT.md`
  - Correct `src/components/dung_chung/` to the authoritative `src/components/chung/` structure.
  - Keep the whole-phase entry gate on BE-FOLLOWUP-01A/01B, safe DTO/read-only limits, unlinked events, abnormal-event filter, and exact three screens.
  - Do not add payment filters/status values that the current plan/API contract does not guarantee.

- `FE5_CONTEXT.md`
  - Replace the stale READY-subset/blocker-placeholder execution policy. Current pre-entry status is waiting on BLOCKER-03/04/05; `FE5-ALL` is dispatched only after all three contracts plus Backend tests are ready, and then all seven screens must be implemented in one task.
  - Remove acceptance of `TinhNangChuaSanSang`, zero-request blocker pages, or `PARTIALLY_READY` as an exit for the writer. Retain them only as reasons not to dispatch.
  - Preserve exact-assignment 403/404 cleanup, official Plan/history boundaries, and no Member/Admin API workaround.

- `FE6_CONTEXT.md`
  - Replace stored proposal source `'PT'` with `HUAN_LUYEN_VIEN` and keep PT as display label.
  - Remove target-weight editing unless present in the verified current proposal contract; authoritative phase scope is exercise/day/sets/reps and validated plan structure.
  - State dependency `FE5-ALL PASS`, stable-key unknown-outcome recovery, Q06 exact-term behavior, and Q13 non-prerequisites/non-effects.

- `FE7_CONTEXT.md`
  - Make pre-dispatch gates explicit: `FE5-ALL PASS`, compatible approved dependencies, and Reverb/BE-FOLLOWUP-04 deployment readiness. If absent, retain `WAITING_DEPLOYMENT` and do not consume a writer/reviewer.
  - Preserve exact channel/event/public config, REST fallback, stable `client_message_id`, message-id/sequence dedupe, `before_sequence` catch-up, and Q05 cleanup.

- `FE8_CONTEXT.md`
  - Replace the stale instruction to build QR plus two blocker pages. Pre-entry status waits for BLOCKER-06/07; dispatch `FE8-ALL` only after both contracts/tests are ready, then implement all three screens plus shell in one task.
  - Remove blocker-placeholder/partial exit acceptance from the writer brief; retain missing APIs solely as no-dispatch stop conditions.
  - Keep Backend-only eligibility and QR authority, manual fallback, and no Admin/Member-self workaround.

- `FE9_CONTEXT.md`
  - Keep exact 50-record/48-screen, 27 Admin/12 PT/4 Receptionist/5 shared counts. Name the five shared pages correctly: role selection, forgot password, reset password, 403, and 404; actor logins remain in actor totals.
  - Replace the abbreviated seven-group proxy with a faithful checklist covering every Section 40 readiness item, including 65-row matrix status, 7 blockers, 4 fixes, realtime deployment, cross-role cleanup, a11y/responsive, naming/docblocks/secrets, and exact evidence artifacts.
  - Preserve `COMPLETE`, `PARTIALLY_READY`, and `BLOCKED` distinctions; do not require an unexplained zero-skip rule beyond the authoritative required-suite evidence.

- `TASK_PACKET_TEMPLATE.md`
  - Replace stale `[inherit | flash | pro]` routing with controller-supplied explicit workflow role/model fields compatible with the current Fitness SDD plan; an implementer packet must not silently choose its own model.
  - Make canonical reread conditional: default minimal layered context, but `YES` when the controller/role contract requires it or escalation occurs. Remove the unconditional `FULL_CANONICAL_REREAD: NO`.
  - Add exact applicable `AGENTS.md`, authority paths, current baseline/pre-existing-change paths, allowed files, prohibited files, dependencies, expected report path, and phase entry-gate evidence.
  - Encode the one-task-per-phase rule for FE-3→FE-9 and historical small task codes for FE-0→FE-2.
  - Replace the compact four-part report with the canonical 15-section completion report plus exact test commands/results, concerns, and remaining work.

## Acceptance checks

1. Every file under `.fitness-rules/` and `.fitness-sdd/context/` is reread after editing; all Markdown relative links resolve.
2. No diff exists outside the 24 declared targets, and baseline/current dirty hunks in the authorities and all unrelated files are unchanged byte-for-byte.
3. Forbidden stale strings are absent except in explicitly labeled anti-examples: `hoat_dong = 0` for Muscle Group, stored source `'AI'`/`'PT'`, `/api/v1/member/workouts`, `components/dung_chung`, `[inherit | flash | pro]`, and unconditional `FULL_CANONICAL_REREAD: NO`.
4. M061 appears consistently in `PROJECT_CORE.md`, `RULE_INDEX.md`, `database.md`, `workout.md`, `security-integrity.md`, `testing-definition-of-done.md`, and `FE3_CONTEXT.md`, with all canonical behaviors and the exact error/audit codes.
5. Membership head/tail status text never assigns `CHO_KICH_HOAT` to a queued active-chain tail; no future entitlement is usable early.
6. Q08 never authorizes free workout or unscheduled Start; canceled-session retry preserves old history/FK and uses a valid replacement schedule/version.
7. All stored proposal sources are `TRO_LY` or `HUAN_LUYEN_VIEN`; UI labels AI/PT are clearly labels only. Proposal statuses and confirm-time conflict behavior are consistent.
8. `WORKOUT_TEMPLATE_STALE` is only a 409 optimistic-concurrency path around `expected_content_version`, separate from copy-on-write Member-plan history.
9. FE-0/1/2 have routable context files; FE-3→FE-9 each remain one aggregate task; FE5/FE8 writers are not dispatched before all entry blockers open; FE7 is not dispatched before deployment readiness.
10. Counts exactly match the plan: 65 capability rows, 50 router records, 48 screens = 27 Admin + 12 PT + 4 Receptionist + 5 shared; 7 blockers; 4 fix rows.
11. Auth summaries contain current-token/late-401 and transient `/me` failure behavior; no cross-role or cached-authority bypass is introduced.
12. The testing module and Task Packet expose the canonical 15-section completion report and require actual final-state evidence.
13. The final diff changes documentation only and contains no new API, screen, database column, enum, business rule, product dependency, or code implementation.

## Verification evidence to record

- Exact `git diff -- .fitness-rules .fitness-sdd/context` changed-path list.
- Output of stale-string, count/status, enum, M061, and phase-gate scans.
- Markdown-link resolver result (`0` missing).
- Manual parity checklist result for all 13 existing layered rule files, all 8 existing context files, and the 3 new context files.

## User decision

`NEEDS_USER_DECISION`: none.
