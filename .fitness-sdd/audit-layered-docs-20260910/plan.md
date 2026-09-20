# Initial Plan — Audit and Synchronize Layered Documentation

## Status and authority

- Status: `READY_FOR_IMPLEMENTATION`
- Repository: `E:\Fitness`
- Authoritative working-tree inputs: `E:\Fitness\PROJECT_RULES.md` and `E:\Fitness\docs\VUE_WEB_IMPLEMENTATION_PLAN_V2.md`.
- Preservation baseline: `baseline-status.txt`, `baseline-working.patch`, `baseline-staged.patch`, and `baseline-tree/` in this directory. Both authorities and the entire layered tree were pre-existing dirty/untracked user work; they must not be rewritten outside this plan's declared layered targets.
- Applicable instructions read: `C:\Users\xtung\.codex\RTK.md`, `E:\Fitness\AGENTS.md`, and the Fitness SDD Initial Planner contract. `E:\Fitness\BE\AGENTS.md` was discovered but does not govern the documentation paths in scope.
- Scope classification: documentation synchronization only. No product behavior, database, API, authorization, or business-rule change is authorized.

## Audit result

The layered documentation is structurally useful, all explicit Markdown file links resolve, and the top-level Vue counts currently agree with the authoritative plan: 65 capability rows, 50 router records, 48 screens, 7 `BACKEND_API_BLOCKER` rows, and 4 `BACKEND_FIX_IN_PROGRESS` rows. It is not yet safe as a delegated implementation context because several condensed statements alter semantics, omit decisive edge cases, or preserve an older phase-execution policy.

Highest-risk drift:

1. Canonical M061 is absent or contradicted by `hoat_dong = 0`; the current rule is `nhom_co.trang_thai` in `HOAT_DONG | NGUNG_SU_DUNG`, GET includes inactive rows, inactive existing pivots are immutable, new inactive relations are rejected with `INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE`, same-state PATCH is a no-op, real transitions audit atomically, and status/relation races are serialized.
2. Proposal-source enums are internally contradictory (`AI`, `PT`, and `TRO_LY`). Official stored values are `TRO_LY` and `HUAN_LUYEN_VIEN`; UI labels may remain AI/PT.
3. Membership renewal text assigns `CHO_KICH_HOAT` to a tail term, contradicting the separate queued-term lifecycle (`CHO_DEN_LUOT`) and the rule that only a new head waits for first use.
4. Q08 is broadened into permission to create schedules/free workouts. Canonical scope is start/save/complete from an owned valid existing `buoi_tap_du_kien`; free workout remains Future Development.
5. `WORKOUT_TEMPLATE_STALE` is described as a persistent notification flag caused by template updates. In the plan it is a 409 optimistic-concurrency outcome for revision creation using `expected_content_version`; existing Member plans are independently protected by copy-on-write/history.
6. `FE5_CONTEXT.md` and `FE8_CONTEXT.md` still instruct a writer to build READY subsets and blocker placeholders. The newer batching decision in the Vue plan permits exactly one `FE*-ALL` writer only after the whole phase entry gate opens for FE-3 through FE-9.
7. Auth, testing, and task-packet summaries contain unsafe shortcuts: wrong account-status enums, a nonexistent `/api/v1/member/workouts/...` example, incomplete 401 restore/late-response handling, a four-section completion report instead of the canonical 15 sections, and a hardcoded `FULL_CANONICAL_REREAD: NO` that cannot represent escalation/role-contract requirements.
8. Routing is incomplete for FE-0 through FE-2, and phase-context references in `RULE_INDEX.md` are formatted as if they were relative to the repository root although the index lives in `.fitness-rules/`.

## Actor/use case and non-functional boundaries

- Actor: Controller, planner, implementer, fixer, and reviewer consuming layered context; indirectly ADMIN/PT/RECEPTIONIST/MEMBER behavior described by those documents.
- Use case: load a minimal, accurate context packet without silently changing canonical behavior or using stale phase/API status.
- Business rules: all Q01–Q13, RULE GYM 01–25, RULE CODE 01–20, M061, and the Vue-plan batching/readiness decisions remain unchanged.
- Database/API: documentation may correct official names, enums, routes, status codes, and contracts only; it must not add a column, endpoint, or capability.
- Authorization/validation/failure: preserve Backend authority, resource ownership/exact assignment, 401/403/404 concealment, 409/422 handling, blocker stop conditions, and no cross-role workaround.
- Transaction/concurrency/idempotency/audit: restore omitted canonical requirements, especially activation races, webhook/reconciliation, PT usage, proposal apply, QR single-use, chat retry/outbox, M061 serialization, and same-state audit no-op.
- Tests: documentation checks are link resolution, reference/count/status scans, contradiction scans, and a line-by-line semantic review against both authorities. No product test suite is required because no product code changes are in scope.

## One-task implementation plan

Execute exactly one documentation task from `task-1-brief.md`. It may modify only `.fitness-rules/**` and `.fitness-sdd/context/**`, including creation of the three missing phase contexts for FE-0, FE-1, and FE-2. It must use `apply_patch`, preserve all unrelated pre-existing work, and must not edit the two authorities, Backend, Frontend, Mobile, database design, checkpoint, or baseline artifacts.

The writer must first re-read the current authority sections relevant to every edit, then make the smallest coherent changes. The reviewer must verify semantic parity rather than accepting matching keywords. A single task is appropriate because the files form one coupled routing system: changing the index, task-packet protocol, or phase gate without its referenced module/context would leave an unsafe mixed version.

## Verification commands

Run from `E:\Fitness` after the documentation edit:

```powershell
rtk git diff -- .fitness-rules .fitness-sdd/context
rtk rg -n --hidden "hoat_dong = 0|nguon_de_xuat = 'AI'|nguon_de_xuat = 'PT'|/api/v1/member/workouts|components/dung_chung|FULL_CANONICAL_REREAD:\s*NO|\[inherit \| flash \| pro\]" .fitness-rules .fitness-sdd/context
rtk rg -n --hidden "65 capability|50 router|48 (màn|screen)|BACKEND_API_BLOCKER|BACKEND_FIX_IN_PROGRESS|FE[3-9]-ALL|INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE|HUAN_LUYEN_VIEN|TRO_LY" .fitness-rules .fitness-sdd/context
```

Also run a read-only relative Markdown-link resolver over both scoped trees; expected result is zero missing targets. Manually verify every `FE*-ALL` entry/exit gate and every official enum/API example against the current authorities because regex alone cannot prove semantic correctness.

## Decision

`NEEDS_USER_DECISION`: none. The current authorities and their referenced official schema/source resolve the observed enum and phase-boundary questions without inventing a new business rule.

## Residual risks

- The canonical and Vue plan are themselves dirty user work, so synchronization must use their current bytes and never HEAD versions.
- Hardcoded source line numbers in layered docs will drift again; replace them with stable section/table identifiers where possible.
- Backend readiness may change later. This task mirrors the authoritative current plan counts/statuses; it does not authorize reclassifying capabilities from source inspection.
