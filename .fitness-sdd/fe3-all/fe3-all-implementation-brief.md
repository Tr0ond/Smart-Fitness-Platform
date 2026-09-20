# FE3-ALL implementation brief — aggregate Admin Catalog

PLAN_STATUS: READY_FOR_IMPLEMENTATION
ENTRY_GATE: PASS
DISPATCH_ALLOWED: YES
TASK_ID: FE3-ALL
TASK_COUNT: 1
EXPECTED_REPORT: `E:\Fitness\.fitness-sdd\fe3-all\task-fe3-all-report.md`

This brief activates the existing Sol plan and `fe3-all-brief.md` after the Backend repair PASS recorded in `entry-gate-revalidation.md`. Implement all checklists A-F as one indivisible writer run. Do not implement a READY subset.

## REQUIRED_CONTEXT

- `E:\Fitness\AGENTS.md`; discover and read any nested AGENTS governing paths before editing.
- `E:\Fitness\.fitness-rules\PROJECT_CORE.md` and `RULE_INDEX.md`.
- `E:\Fitness\.fitness-sdd\context\FE3_CONTEXT.md`.
- `E:\Fitness\.fitness-rules\frontend\vue-core.md` and `frontend\visual-ui.md`.
- `E:\Fitness\.fitness-rules\domains\workout.md` and `domains\membership-payment.md`.
- `E:\Fitness\.fitness-rules\engineering\security-integrity.md`, `engineering\coding-conventions.md`, `engineering\testing-definition-of-done.md`.
- `E:\Fitness\.fitness-sdd\fe3-all\plan.md`, `fe3-all-brief.md`, `entry-gate-revalidation.md` and this brief.
- Inspect actual relevant Backend routes/controllers/requests/services/DTO tests and `E:\Fitness\docs\BACKEND_API_CONTRACT.md` sections before implementing mappings. Actual source and repaired contract are authority for request/response shape.

Canonical IDs: Q01, Q11, Q12, M061, RULE GYM 11-16, RULE CODE 05-11, 13, 17-20. `PROJECT_RULES.md` and the Vue plan remain conditional authorities. Read only relevant sections on conflict, ambiguity, missing coverage or version mismatch and record the trigger/resolution. Do not default to full-document reads or unrelated modules.

## Actor, use case and boundaries

Actor: authenticated ADMIN. Build the complete Admin Catalog UI for packages/benefits, equipment, muscle groups, exercises/relations and workout-template metadata/revisions. Backend remains the only authorization and business authority.

No Backend/Database/Mobile changes, no FE4 payment UI, no hard delete, no client-supplied branch/creator/snapshot/content authority, no blind mutation retry after ambiguous network outcome, no new dependency or lockfile change. Do not edit `docs/VUE_WEB_COMPLETION_CHECKPOINT.md`, rule/plan files, snapshots or review artifacts. Do not commit, push, branch, worktree, reset, restore, checkout, stash, clean or publish.

## Exact allowed files

Only paths listed in `task-fe3-all-targets.txt` plus the expected report may be created or modified. The target list contains existing router/menu/auth/CSS integration files and all services/store/components/pages/tests named by the approved Sol plan. No wildcard.

Preserve every pre-existing change outside declared FE3 hunks using `task-fe3-all-round-1-before`, `task-fe3-all-before-manifest.json`, `task-fe3-all-preservation-manifest.json` and `task-fe3-all-status-before.txt`.

## Acceptance essentials

- Implement all 12 named routes/screens from FE3_CONTEXT and their exact tests.
- Package changes clearly warn that prior purchased-term snapshots do not change; benefits use the actual PUT contract and authoritative configuration version.
- Equipment and Exercise follow their own lifecycle; Muscle Group follows M061. No DELETE calls. Selectors exclude inactive groups for new relations while detail preserves/displays inactive existing pivots and requires exact role echo on replacement.
- Exercise equipment selections explicitly communicate and submit Q11 AND semantics.
- Template metadata PATCH never sends `days`; tree edit creates a revision. On 409 `WORKOUT_TEMPLATE_STALE`, retain draft, refetch current base and require explicit reconciliation.
- Catalog store is the one approved `danh_muc` store, short-lived cache only, invalidated after mutation and cleared on logout, actor-role loss and current-token 401 without a late old-token 401 clearing a newer session.
- Reuse existing Admin shell and shared UI primitives. Light Staff UI only; CSS variables; no external font/framework/library. Semantic controls, visible focus, labels/errors, `aria-busy`, keyboard dialog behavior and responsive layouts at mobile/tablet/desktop.
- Naming: Vietnamese without accents, camelCase business/state functions and `xuLy...` event handlers; framework APIs retain original names. Meaningful docblocks for complex business flows.

## Required verification

Run focused tests for every changed/new service, store, component, page, router/menu and auth cleanup; then full `npm run test`, `npm run lint`, `npm run build`, and `npm ls --depth=0` from `E:\Fitness\FE`. Run static scans for DELETE catalog calls, FE4 scope, hard-coded API root, forbidden dependency/UI framework, secrets/logged tokens, English business identifiers/`handleXxx`, skipped/only tests and TODO/FIXME. Run `git diff --check` and verify staged diff empty and exact allow-list preservation.

Browser smoke is required when the local app can run without new infrastructure: desktop about 1440px, tablet 768px and mobile 390px; check routing/menu, loading/empty/error, forms, 422, 401/403, 409 stale, network ambiguity, focus/dialog/keyboard, overflow and console errors. Record any environment limitation honestly.

Write the exact canonical 15-section report with actual final-state command counts/results, changed/created files, behavior, API/DB impact, auth, validation, transaction/idempotency implications, errors, tests, unfinished work and risks.
