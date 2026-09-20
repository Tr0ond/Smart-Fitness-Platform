# Task 1 Round 1 Repair Brief — Layered Documentation

## Status

`READY_FOR_SCOPED_REPAIR_WITH_GENUINE_BASELINE_BLOCKER`

- The repair mapping is complete for `LAYERED-DOCS-001` through `LAYERED-DOCS-007`.
- `LAYERED-DOCS-002` through `LAYERED-DOCS-007` are technically repairable with small documentation edits listed below.
- `LAYERED-DOCS-001` is **not technically recoverable from the currently available evidence**. It remains an independent preservation-gate blocker even if all semantic repairs pass.
- The fixer must not edit product code, Backend/Mobile/Frontend runtime source, either authority (`PROJECT_RULES.md`, `docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md`), any `AGENTS.md`, schema/API contracts, or unrelated layered documents.

## Allowed repair paths

Only the following task-owned layered documents may change for findings `002`–`007`:

1. `E:\Fitness\.fitness-sdd\context\TASK_PACKET_TEMPLATE.md`
2. `E:\Fitness\.fitness-rules\RULE_INDEX.md`
3. `E:\Fitness\.fitness-sdd\context\FE3_CONTEXT.md`
4. `E:\Fitness\.fitness-sdd\context\FE4_CONTEXT.md`
5. `E:\Fitness\.fitness-sdd\context\FE5_CONTEXT.md`
6. `E:\Fitness\.fitness-sdd\context\FE6_CONTEXT.md`
7. `E:\Fitness\.fitness-sdd\context\FE8_CONTEXT.md`
8. `E:\Fitness\.fitness-sdd\context\FE9_CONTEXT.md`
9. `E:\Fitness\.fitness-rules\domains\workout.md`

The existing `task-1-report.md` may only be appended with the normal round-1 repair/test record when the controller explicitly gives the fixer that reporting exception. Do not rewrite review/baseline artifacts to manufacture preservation evidence.

## LAYERED-DOCS-001 — Missing pre-task byte baseline and unexplained archive loss

### Root cause

- The controller snapshot used a wildcard with `Copy-Item -LiteralPath`; the wildcard was not expanded. `baseline-tree/` contains no files, and `task-1-round-0-before/` contains only the three valid absent markers for `FE0_CONTEXT.md`, `FE1_CONTEXT.md`, and `FE2_CONTEXT.md`.
- The other 21 targets were already untracked, so `baseline-working.patch` and `current-working.patch` cannot contain their contents. Their identical 95,832-byte tracked patches prove only that tracked dirty hunks were preserved.
- `baseline-status.txt` proves `.fitness-rules.rar` existed before Task 1. It is absent from the current workspace, and an exact-name read-only search found no copy in `E:\Fitness` or `E:\$RECYCLE.BIN`. No artifact establishes who removed it or what it contained.
- Consequently, the statement in `task-1-report.md` that current status matches the baseline is unsupported, and the package's attribution of the archive loss to external/user state is not evidence.

### Exact recovery path and logic

There is no honest text-only repair using current files. Do **not** reverse-engineer a baseline from the current layered tree, canonical authorities, semantic parity, porcelain output, timestamps, or the implementer report.

To make this finding repairable, the controller/user must provide a trustworthy pre-task source with provenance:

1. Byte-for-byte copies of all 21 pre-existing untracked target files as they existed before Task 1.
2. The pre-task `.fitness-rules.rar`, or other trustworthy evidence that identifies its exact contents/hash and authorized disposition.
3. If such evidence is obtained, capture it in an explicit baseline location, hash every file, generate exact per-file pre-task-to-current deltas (including absent/present state), regenerate the review package, and correct the report's preservation claim.
4. If no such evidence exists, the only valid route is an owner-authorized restore from a known pre-task backup followed by a fresh Task 1 run with an explicit enumerated snapshot procedure. Do not overwrite the current tree without owner/controller authorization.

### Constraints that remain unchanged

- Semantic review cannot substitute for byte preservation.
- Fixing `002`–`007` does not close `001`.
- Do not label the archive deletion external, intentional, harmless, or recoverable without evidence.
- Do not modify Git state or use reset/restore/checkout/stash/clean.

### Focused checks

- Enumerate baseline snapshot files and verify all 21 pre-existing targets have regular-file copies, not empty directories or inferred reconstructions.
- Record SHA-256 for each pre-task and current target; run exact no-index/per-file comparisons and list every changed path.
- Verify `.fitness-rules.rar` with provenance, hash, and explicit disposition.
- Reconcile baseline/current untracked inventories without relying on directory-level `??` entries.

### Acceptance evidence

- A reviewer can independently inspect every pre-task byte copy, every exact task delta, and the archive accounting.
- The regenerated report/package makes no unsupported status-equality or external-attribution claim.
- **Current evidence does not satisfy this acceptance condition; genuine blocker remains.**

## LAYERED-DOCS-002 — Non-canonical completion-report template

### Root cause

`TASK_PACKET_TEMPLATE.md` preserved a count of 15 headings but replaced and reordered the meanings required by `PROJECT_RULES.md` PHẦN XXI. Phase-specific scope and evidence fields displaced canonical sections instead of following them.

### Exact file and logic to change

In `E:\Fitness\.fitness-sdd\context\TASK_PACKET_TEMPLATE.md`, replace only the report list under `#### 7. ĐỊNH DẠNG BÁO CÁO HOÀN THÀNH` with this exact PHẦN XXI order and meaning:

1. `Muc tieu module`
2. `File da tao`
3. `File da sua`
4. `Database lien quan`
5. `API da tao/sua`
6. `Ham chinh` with `ten ham`, `muc dich`, `cach hoat dong`
7. `Business Rule da xu ly`
8. `Authorization`
9. `Validation`
10. `Transaction / Idempotency`
11. `Error Case`
12. `Test Case`
13. `Test Result`
14. `Phan chua hoan thanh`
15. `Rui ro con lai`

After, not inside or instead of, those 15 sections, allow additive phase/task evidence fields for actor/use case, scope, concurrency/audit, exact commands/results, concerns, baseline/path preservation, and dependency-gate evidence.

### Constraints that remain unchanged

- Keep `EXPECTED_REPORT`, explicit controller-supplied workflow/model fields, and final-state evidence requirements.
- Do not change the correct canonical template already present in `engineering/testing-definition-of-done.md`.
- Do not combine `Test Case` and `Test Result`, or Database and API.

### Focused checks

- Compare the 15 headings and order literally with PHẦN XXI.
- Verify additive fields appear only after the canonical 15-section block.
- Scan both report-template sources for the separate `Test Case` and `Test Result` sections.

### Acceptance evidence

- The rendered task packet contains the exact canonical 15 sections in order and preserves any phase evidence only as additions.

## LAYERED-DOCS-003 — Contradictory full-canonical-read rule

### Root cause

The top-level rule in `TASK_PACKET_TEMPLATE.md` says “Tuyệt đối không đọc” the full authorities, while later fields correctly require a full read when a controller, role contract, conflict, ambiguity, or escalation requires it. A recipient can obey the first rule and violate its role contract.

### Exact file and logic to change

In the top blockquote of `E:\Fitness\.fitness-sdd\context\TASK_PACKET_TEMPLATE.md`, replace only the unconditional prohibition with one consistent rule:

- Default to only the files named in `REQUIRED_CONTEXT` and the minimal layered set.
- Do not read the full canonical or Vue plan by default.
- When the controller or applicable role contract requires a full read, or a conflict/ambiguity/escalation triggers it, the recipient must read the specified authority completely in sequential chunks through EOF.

### Constraints that remain unchanged

- Preserve minimal-context routing and the prohibition on indiscriminate reads.
- Preserve `FULL_CANONICAL_REREAD: CONDITIONAL`; do not revert to unconditional `NO` or unconditional full reads for ordinary tasks.

### Focused checks

- Scan the complete template for unconditional “never read full canonical/plan” statements.
- Confirm the top rule, `REQUIRED_CONTEXT`, escalation section, and `FULL_CANONICAL_REREAD` state the same exceptions.

### Acceptance evidence

- A planner/reviewer with a mandatory full-read role contract can follow the template without contradiction, while ordinary implementers still receive minimal context.

## LAYERED-DOCS-004 — Missing phase prerequisites in routing and contexts

### Root cause

The synchronized phase rows retained Backend blocker gates but dropped additive FE phase dependencies from Vue plan Section 38A. The contexts copied the same incomplete entry-gate logic, making early dispatch possible.

### Exact files and logic to change

Make only these prerequisite additions:

- `E:\Fitness\.fitness-rules\RULE_INDEX.md`
  - FE3 row: require `FE-1 PASS` and actual catalog contracts `READY` before `FE3-ALL` dispatch.
  - FE4 row: require `FE-1 PASS` **and** BE-FOLLOWUP-01A/01B tests/evidence `PASS`.
  - FE5 row: require `FE-0 PASS` **and** BLOCKER-03/04/05 contracts plus Backend tests `PASS`.
  - FE8 row: require `FE-0 PASS` **and** BLOCKER-06/07 contracts plus Backend tests `PASS`.
  - FE9 row: require FE-0 through FE-8 `PASS` and all required Backend blocker, fix, and realtime/deployment gates resolved before dispatch.
  - Update the existing phase-execution summary so it repeats these complete gates; do not create a second conflicting dependency table.
- `E:\Fitness\.fitness-sdd\context\FE3_CONTEXT.md`
  - Add an entry-gate/stop-condition statement: no `FE3-ALL` writer until `FE-1 PASS` and actual catalog contracts are `READY`.
- `E:\Fitness\.fitness-sdd\context\FE4_CONTEXT.md`
  - Add `FE-1 PASS` to the existing BE-FOLLOWUP-01A/01B entry gate in both the stop condition and acceptance item.
- `E:\Fitness\.fitness-sdd\context\FE5_CONTEXT.md`
  - Add `FE-0 PASS` to the existing BLOCKER-03/04/05 entry gate and its aggregate-task acceptance wording.
- `E:\Fitness\.fitness-sdd\context\FE8_CONTEXT.md`
  - Add `FE-0 PASS` to the existing BLOCKER-06/07 entry gate and its aggregate-task acceptance wording.
- `E:\Fitness\.fitness-sdd\context\FE9_CONTEXT.md`
  - Add an explicit pre-dispatch entry gate requiring FE-0 through FE-8 `PASS`, all seven Backend API blockers resolved with implementation/authorization/resource-scope evidence, all four fix rows regression-tested, and the required realtime/deployment gate ready.
  - If a dependency later proves missing or regresses, keep truthful `PARTIALLY_READY`/`BLOCKED` reporting; that distinction does not authorize premature dispatch.

### Constraints that remain unchanged

- FE3 through FE9 remain one aggregate task per phase; no READY-subset writer/reviewer.
- Backend gates are additive to FE prerequisites, never replacements.
- Preserve the authoritative counts: 65 capability rows, 50 router records, 48 screens = 27 Admin + 12 PT + 4 Receptionist + 5 shared, 7 blockers, 4 fix rows.
- Do not add a new API, screen, phase, task code, or dependency.

### Focused checks

- For each of FE3, FE4, FE5, FE8, and FE9, compare the `RULE_INDEX.md` row and context entry gate against Vue plan Section 38A.
- Scan that FE3–FE9 each still use exactly one `FE*-ALL` task and no READY-subset dispatch language appears.
- Verify FE6 remains dependent on `FE5-ALL PASS` and FE7 remains dependent on `FE5-ALL PASS`, approved dependency compatibility, and Reverb/BE-FOLLOWUP-04 readiness.
- Re-run authoritative-count scans.

### Acceptance evidence

- A phase-gate matrix shows exact parity between Section 38A, every affected index row, every affected phase context, and the task-packet aggregate-task rule.

## LAYERED-DOCS-005 — Frontend incorrectly told to send PT proposal source

### Root cause

`FE6_CONTEXT.md` correctly says PT is a display label, but its acceptance checklist later instructs the Frontend to send source `HUAN_LUYEN_VIEN`. The verified request schema has no proposal-source field; `PtProposalService` assigns `nguon_de_xuat = HUAN_LUYEN_VIEN` server-side.

### Exact file and logic to change

In `E:\Fitness\.fitness-sdd\context\FE6_CONTEXT.md`, replace only the contradictory acceptance item with:

- Creating a proposal follows Q13 and sends only fields allowed by the verified request contract.
- The Frontend must not send, choose, or control any proposal-source field.
- Backend persists `nguon_de_xuat = HUAN_LUYEN_VIEN` server-side; `PT` is display/domain wording only.

### Constraints that remain unchanged

- Preserve the three screens, verified proposal structure (including fields actually present in the current request), stable idempotency key, Q06 exact-term behavior, Q13 non-prerequisites/non-effects, conflict handling, and `FE5-ALL PASS` dependency.
- Do not add a request field or change Backend source.

### Focused checks

- Compare FE6 payload guidance against `BE\app\Http\Requests\Pt\CreatePtProposalRequest.php` and server assignment in `BE\app\Services\Pt\PtProposalService.php`.
- Scan FE6 context for “send source”, `source`, or `nguon_de_xuat` as a client-controlled field.

### Acceptance evidence

- FE6 acceptance explicitly omits source from client payload and identifies the Backend as the only writer of `HUAN_LUYEN_VIEN`.

## LAYERED-DOCS-006 — Receptionist lookup omits name

### Root cause

`FE8_CONTEXT.md` narrowed the planned allow-list from code/name/phone to only member code/phone.

### Exact file and logic to change

In the lookup description at `E:\Fitness\.fitness-sdd\context\FE8_CONTEXT.md`, change only the searchable-field statement to code, name, or phone, explicitly subject to the finalized Receptionist contract and Backend branch/resource scope.

### Constraints that remain unchanged

- Preserve BLOCKER-06 as a pre-dispatch gate; do not invent query parameter names, returned contact fields, rate limits, or an endpoint implementation.
- Preserve least privilege and the prohibition on Admin/Member-self workarounds.

### Focused checks

- Compare the lookup sentence with Vue plan Sections 18.2 and 33 BLOCKER-06.
- Confirm all three fields are present and qualified by the finalized contract/Backend branch scope.

### Acceptance evidence

- FE8 context states the approved code/name/phone allow-list without claiming an unverified concrete API schema.

## LAYERED-DOCS-007 — Wrong template section and overbroad retention claims

### Root cause

`workout.md` cites canonical Section 20 for Workout Templates even though Section 20 is Equipment Maintenance and Section 21 is Workout Template. Its Q12 prose also turns the MVP no-hard-delete rule into an unlimited permanent-retention rule, foreclosing future anonymization/legal-retention/purge/archive policy that canonical explicitly leaves for future requirements.

### Exact file and logic to change

In `E:\Fitness\.fitness-rules\domains\workout.md`:

1. Change `Workout Templates — Section 20` to `Workout Templates — Section 21`.
2. Replace the sentence that history is tied “vĩnh viễn” and “không bao giờ ... xóa bỏ” with the scoped rule: Membership expiry or a new purchase does not hide, reset, or delete personal Workout History in MVP.
3. Replace the absolute production `DELETE` prohibition with: MVP does not hard-delete important Workout history (`phien_tap`, `bai_tap_trong_phien`, `hiep_tap` and related retained states); anonymization, legal retention periods, and automated purge/archive remain future operational/legal requirements and no retention duration is invented here.

### Constraints that remain unchanged

- Keep completed `HOAN_THANH` sessions immutable under RULE GYM 13 and keep canceled/replaced history.
- Keep Q08 scheduled-workout ownership rules, Q07C replacement schedule/version workflow, M061, Q11, and template copy-on-write/409 stale semantics unchanged.
- Do not add a purge feature, retention duration, legal conclusion, or soft-delete mechanism.

### Focused checks

- Scan for the corrected Section 21 reference.
- Compare all Q12/retention wording in `workout.md` with canonical Sections 6.1, 46.1, and Q12.
- Confirm no remaining absolute permanent-retention statement forecloses future policy, while no MVP hard-delete permission is introduced.

### Acceptance evidence

- The module accurately distinguishes completed-session immutability from MVP history preservation and explicitly reserves future anonymization/legal-retention/purge/archive decisions.

## Round-1 verification and review handoff

After the scoped edits for `002`–`007`:

1. Reread all nine changed layered/context files in full.
2. Resolve all Markdown relative links; result must be zero missing.
3. Produce the exact round-1 before/current diff for only the allowed repair paths and report any unexpected path.
4. Run focused scans for:
   - exact PHẦN XXI 15-section order;
   - conditional canonical full-read exceptions;
   - complete FE3/FE4/FE5/FE8/FE9 entry gates;
   - absence of client-controlled PT proposal source;
   - FE8 code/name/phone lookup wording;
   - Section 21 template reference and MVP-scoped Q12 wording;
   - unchanged authoritative counts and aggregate task rules.
5. Append exact commands/results to `task-1-report.md` only if the controller grants the report-path exception.
6. Send a fresh reviewer the round-1 before snapshot, exact delta, updated report/package, and this repair brief.
7. The reviewer may close `002`–`007` independently. It must keep `001` open until the byte-baseline and archive acceptance evidence above actually exists.

## Mapped finding IDs

`LAYERED-DOCS-001`, `LAYERED-DOCS-002`, `LAYERED-DOCS-003`, `LAYERED-DOCS-004`, `LAYERED-DOCS-005`, `LAYERED-DOCS-006`, `LAYERED-DOCS-007`

## Genuine blocker

`LAYERED-DOCS-001`: no recoverable pre-task bytes for 21 pre-existing untracked targets and no recoverable/currently evidenced `.fitness-rules.rar`; owner-supplied trusted backup/provenance or an owner-authorized restore-and-rerun is required.
