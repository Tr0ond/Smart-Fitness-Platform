# Task 1 Round 2 Repair Brief — Layered Documentation

## Status

`READY_FOR_MINIMAL_SCOPED_REPAIR_WITH_GENUINE_EVIDENCE_BLOCKER`

- Open findings mapped in this round: `LAYERED-DOCS-001`, `LAYERED-DOCS-004`.
- `LAYERED-DOCS-004` is repairable through wording-only changes in exactly three Phase Context files.
- `LAYERED-DOCS-001` remains a genuine preservation-evidence blocker. No documentation edit available in the current workspace can close it honestly.
- This brief does not reopen findings `002`, `003`, `005`, `006`, or `007`; the round-1 reviewer closed them and reported no new Critical/Important finding.

## Fixer scope

The fixer may edit only:

1. `E:\Fitness\.fitness-sdd\context\FE4_CONTEXT.md`
2. `E:\Fitness\.fitness-sdd\context\FE5_CONTEXT.md`
3. `E:\Fitness\.fitness-sdd\context\FE8_CONTEXT.md`

No other layered document, product file, authority, baseline, snapshot, report, review, patch, or Git state is in repair scope. In particular, do not edit `PROJECT_RULES.md`, `docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md`, `.fitness-rules/RULE_INDEX.md`, any `AGENTS.md`, `BE/**`, `FE/**`, Mobile, or `.fitness-sdd/audit-layered-docs-20260910/**`. The controller may separately create the normal round-2 snapshot/delta/review artifacts; the fixer must not rewrite existing evidence. Use `apply_patch`, do not dispatch subagents, and do not commit, push, reset, restore, checkout, stash, or clean.

## Verdict vocabulary that governs all three edits

- `READY` may describe only an API capability or finalized contract being available for integration.
- `PASS` is the required result for observed Backend test evidence.
- A phase entry gate is open only when its existing FE prerequisite is `PASS`, the required capability/contracts are available (`READY` is acceptable for that availability classification), and every required Backend regression, authorization, and resource-scope test has explicit `PASS` evidence.
- Do not use `READY` as a synonym for a test result, test evidence, authorization evidence, resource-scope evidence, or regression evidence.

## LAYERED-DOCS-001 — Missing pre-task bytes and archive accounting

### Root cause and current evidence

- `baseline-tree/` contains no captured files because the original snapshot command did not expand its wildcard.
- `task-1-round-0-before/` contains only absent markers for the three then-new FE0/FE1/FE2 contexts; the 21 other pre-existing untracked targets have no trustworthy pre-task byte copies.
- `baseline-status.txt` records pre-existing `?? .fitness-rules.rar`, but the archive is absent now and no artifact establishes its bytes/hash, provenance, or authorized disposition.
- Identical baseline/current tracked patch SHA-256 proves preservation only for tracked hunks. It cannot prove the prior contents of untracked layered files or account for the archive.

### Required handling

Do not edit any file in an attempt to close this finding. Do not infer a baseline from current documents, semantic parity, timestamps, porcelain equality, reports, or round-1 snapshots. Do not attribute the archive loss to the user, controller, fixer, or an external event without evidence.

The only honest recovery paths remain:

1. Obtain trustworthy, provenance-backed pre-task byte copies of all 21 pre-existing targets plus the original `.fitness-rules.rar` (or trustworthy evidence of its exact hash/contents and authorized disposition), then hash them and regenerate exact pre-task-to-current deltas; or
2. With explicit owner authorization, restore a known pre-task backup and rerun Task 1 using an enumerated, verified snapshot procedure.

### Constraints that remain unchanged

- Semantic correctness of round-2 edits does not close `LAYERED-DOCS-001`.
- No report/package wording may relabel this as a harmless, external, or resolved change.
- No destructive Git/filesystem operation is authorized.

### Focused verification and acceptance evidence

- Every one of the 21 pre-existing targets has an independently inspectable pre-task regular-file copy and SHA-256.
- Exact per-file before/current deltas and absent/present states are regenerated without relying on porcelain directory entries.
- `.fitness-rules.rar` has trustworthy provenance, SHA-256/content accounting, and explicit authorized disposition.
- Until all three bullets exist, disposition remains `OPEN — GENUINE EVIDENCE BLOCKER`.

## LAYERED-DOCS-004 — Test evidence is incorrectly labelled READY

### Root cause

Round 1 correctly restored the FE prerequisites and made the Backend gates additive, but three contexts still use `READY` for Backend test evidence. That mixes a capability/contract readiness classification with a test verdict and weakens the Section 38A entry gates. `.fitness-rules/RULE_INDEX.md` already uses the correct `PASS` wording and must remain unchanged.

### Exact edits

#### 1. `E:\Fitness\.fitness-sdd\context\FE4_CONTEXT.md`

Change only the two gate statements currently around lines 78 and 84:

- In `## 5. Trạng thái Backend & STOP Conditions`, retain `FE-1 PASS`, the existing `READY`/`BACKEND_FIX_IN_PROGRESS` capability classifications, the one-task rule, the no-client-join rule, and the read-only boundary. Clarify that BE-FOLLOWUP-01A/01B capability/contracts may be classified `READY`, but dispatch additionally requires explicit `PASS` evidence for the focused Backend regressions covering:
  - unlinked Payment-event visibility;
  - successful Payment with related abnormal event appearing in the reconciliation filter;
  - Admin authorization;
  - branch/resource-scope isolation; and
  - safe DTO/redaction boundaries (no raw payload/signature exposure).
- In the first acceptance checkbox, use the same distinction and require those Backend regression/authorization/resource-scope results to be explicitly `PASS` before `FE4-ALL` starts. Do not say contract/test evidence is merely `READY`.

No other FE4 wording is needed. The existing Backend status line remains valid: read basics are `READY`, while reconciliation stays `BACKEND_FIX_IN_PROGRESS` until its required evidence passes.

#### 2. `E:\Fitness\.fitness-sdd\context\FE5_CONTEXT.md`

Change only the three gate statements currently around lines 72, 88–89, and 103:

- In rule 5 (`STOP Conditions`), retain `FE-0 PASS`, all three BLOCKER-03/04/05 contracts, the seven-screen aggregate scope, no placeholder/partial exit, and no READY-subset dispatch. State that the contracts/capabilities may be `READY`, while Backend authorization/resource-scope regression tests for each blocker must have explicit `PASS` evidence before dispatch.
- In `Entry gate của phase`, make the existing lowercase “tests đã pass” requirement unambiguous: require explicit `PASS` evidence for authentication/authorization and exact-current-assignment resource scope for BLOCKER-03, BLOCKER-04, and BLOCKER-05. Required negative scope evidence includes old/foreign/unassigned PT denial or concealment as applicable. Do not change the existing `FE-0 PASS` prerequisite or the no-writer/no-reviewer rule while evidence is missing.
- In the aggregate-task acceptance checkbox, preserve all existing dependencies and replace “Backend tests đã READY” with explicit `PASS` evidence for the required Backend authorization/resource-scope regressions.

Do not change screen count, routes, service/file inventory, official Plan/history distinction, completed-session immutability, notes no-side-effect, 403/404 cleanup, or the prohibition on Admin/Member-self workarounds.

#### 3. `E:\Fitness\.fitness-sdd\context\FE8_CONTEXT.md`

Change only the four gate statements currently around lines 12, 16, 58, and 82:

- For BLOCKER-06 lookup, retain the finalized-contract qualification and code/name/phone allow-list. Capability/contract availability may be `READY`, but Backend tests must have explicit `PASS` evidence for RECEPTIONIST authorization, branch/resource-scope isolation, safe allow-listed results, and denial of cross-role/out-of-scope access.
- For BLOCKER-07 Membership/Gym eligibility, capability/contract availability may be `READY`, but Backend tests must have explicit `PASS` evidence for RECEPTIONIST authorization, branch/member resource scope, and Backend-authoritative eligibility/status behavior. Keep the prohibition on client calculation and Member-self API use.
- In rule 4 (`STOP Conditions`), retain `FE-0 PASS`, both blockers, one `FE8-ALL` writer, all three screens plus shell, and no pre-gate dispatch. State that the two capability/contracts may be `READY`, but all required Backend regression/authorization/resource-scope tests must explicitly be `PASS`.
- In the aggregate-task acceptance checkbox, replace “Backend tests đã READY” with explicit `PASS` evidence for those required Backend regressions.

Do not change QR authority, QR replay/pending behavior, camera/manual fallback, screen/route/file counts, or the ban on Admin/Member-self workarounds.

### Constraints that must remain unchanged

- The edits are verdict-clarification only; they do not reclassify a Backend capability, implement a contract, invent a test, or assert that any test has already passed.
- `FE4-ALL` still requires `FE-1 PASS`; `FE5-ALL` and `FE8-ALL` still require `FE-0 PASS`.
- FE4, FE5, and FE8 remain exactly one aggregate task each. No READY-subset writer/reviewer, blocker placeholder exit, or `PARTIALLY_READY` writer acceptance may be introduced.
- Backend gates remain additive to FE prerequisites. Existing capability/contracts may be named `READY`; only evidence verdicts must be `PASS`.
- Preserve all existing routes, screens, file families, business rules, authorizations, stop conditions, and aggregate-task acceptance rules outside the exact wording corrections above.
- Preserve authoritative global counts and downstream rules: 65 capability rows, 50 router records, 48 screens = 27 Admin + 12 PT + 4 Receptionist + 5 shared, 7 Backend API blockers, 4 Backend fix rows; FE6 still depends on `FE5-ALL PASS`, FE7 still depends on `FE5-ALL PASS` plus dependency compatibility and Reverb/BE-FOLLOWUP-04 readiness, and FE9 retains its complete final gate.

### Focused documentation regression checks

After patching, reread the three files fully as UTF-8 and inspect the exact round-2 delta. The delta must contain only these three paths and only the gate lines identified above.

Run each scan separately from `E:\Fitness`:

```powershell
rtk rg -n --hidden "test evidence.*READY|Backend tests?.*READY|Backend test evidence READY|Backend tests đã READY" .fitness-sdd/context/FE4_CONTEXT.md .fitness-sdd/context/FE5_CONTEXT.md .fitness-sdd/context/FE8_CONTEXT.md
```

Expected: exit code 1 / no matches. `READY` may remain elsewhere only as a capability/contract/backend-status classification.

```powershell
rtk rg -n --hidden "FE-1 PASS|BE-FOLLOWUP-01A/01B|unlinked|abnormal|authorization|branch/resource-scope|redaction|PASS" .fitness-sdd/context/FE4_CONTEXT.md
```

Expected: the entry/acceptance gate preserves `FE-1 PASS` and explicitly requires `PASS` evidence for both payment regressions plus Admin authorization, branch/resource scope, and safe DTO/redaction.

```powershell
rtk rg -n --hidden "FE-0 PASS|BLOCKER-03|BLOCKER-04|BLOCKER-05|authorization|exact-current-assignment|resource scope|PASS|WAITING_DEPENDENCY" .fitness-sdd/context/FE5_CONTEXT.md
```

Expected: all three blockers remain named; the phase still waits without dispatch when evidence is missing; Backend authorization/exact-assignment resource-scope regression evidence is explicitly `PASS`.

```powershell
rtk rg -n --hidden "FE-0 PASS|BLOCKER-06|BLOCKER-07|RECEPTIONIST|authorization|branch/resource-scope|branch/member resource scope|PASS|code|name|phone" .fitness-sdd/context/FE8_CONTEXT.md
```

Expected: both blockers and `FE-0 PASS` remain; lookup still covers code/name/phone; required authorization/resource-scope regression evidence is explicitly `PASS` for both contracts.

```powershell
rtk rg -n --hidden "FE4-ALL|FE5-ALL|FE8-ALL|Không tách subtask|không tiêu thụ writer/reviewer|không dispatch|một task tổng hợp duy nhất" .fitness-sdd/context/FE4_CONTEXT.md .fitness-sdd/context/FE5_CONTEXT.md .fitness-sdd/context/FE8_CONTEXT.md
```

Expected: one aggregate task per phase and all existing no-premature-dispatch/no-subset protections remain.

```powershell
rtk git diff -- .fitness-sdd/context/FE4_CONTEXT.md .fitness-sdd/context/FE5_CONTEXT.md .fitness-sdd/context/FE8_CONTEXT.md
```

Expected: only the exact verdict/evidence clarifications above; no route, screen, file inventory, API, business rule, FE prerequisite, aggregate-task rule, or unrelated wording change.

### Acceptance evidence

`LAYERED-DOCS-004` may close only when a fresh reviewer verifies all of the following:

1. The round-2 before/current package proves exactly the three allowed Phase Context files changed and no unexpected path appears.
2. Every required Backend test, regression, authorization, and resource-scope evidence condition in FE4/FE5/FE8 is explicitly a `PASS` condition.
3. `READY` is used only for capability/contract/backend-status readiness, never as the verdict for test evidence.
4. The existing FE prerequisites, aggregate-task/no-subset rules, and all unaffected phase semantics are byte-preserved outside the narrowly edited gate lines.
5. The scans above have the expected results and the three files were reread through EOF after the patch.

Closing `LAYERED-DOCS-004` does not affect the disposition of `LAYERED-DOCS-001`.

## Mapped finding IDs

`LAYERED-DOCS-001`, `LAYERED-DOCS-004`

## Genuine blocker

`LAYERED-DOCS-001` — no trustworthy pre-task bytes for 21 pre-existing untracked target files and no trustworthy provenance/content/disposition evidence for the missing `.fitness-rules.rar`. Owner-supplied evidence or an explicitly authorized known-backup restore and rerun is required.
