# Task 1 Report — Layered Documentation Synchronization

Status: DONE_WITH_CONCERNS

## 1. Mục tiêu module

Synchronize the layered rules and frontend phase-context documents with the current canonical PROJECT_RULES.md and VUE_WEB_IMPLEMENTATION_PLAN_V2.md without changing product code or either authority.

## 2. Phạm vi và actor/use case

This was one atomic documentation task for the Fitness SDD controller/implementer workflow. The scope covers domain rules, engineering/security/testing rules, Vue architecture/UI guidance, FE-0 through FE-9 context routing, and task-packet handoff semantics. No runtime actor, API, database, or UI behavior was changed.

## 3. File đã tạo

- E:\Fitness\.fitness-sdd\context\FE0_CONTEXT.md
- E:\Fitness\.fitness-sdd\context\FE1_CONTEXT.md
- E:\Fitness\.fitness-sdd\context\FE2_CONTEXT.md
- E:\Fitness\.fitness-sdd\audit-layered-docs-20260910\task-1-report.md (this report)

## 4. File đã sửa/synchronized

- E:\Fitness\.fitness-rules\PROJECT_CORE.md
- E:\Fitness\.fitness-rules\RULE_INDEX.md
- E:\Fitness\.fitness-rules\domains\ai-proposal.md
- E:\Fitness\.fitness-rules\domains\auth-resource-scope.md
- E:\Fitness\.fitness-rules\domains\database.md
- E:\Fitness\.fitness-rules\domains\membership-payment.md
- E:\Fitness\.fitness-rules\domains\pt-chat.md
- E:\Fitness\.fitness-rules\domains\workout.md
- E:\Fitness\.fitness-rules\engineering\coding-conventions.md
- E:\Fitness\.fitness-rules\engineering\security-integrity.md
- E:\Fitness\.fitness-rules\engineering\testing-definition-of-done.md
- E:\Fitness\.fitness-rules\frontend\vue-core.md
- E:\Fitness\.fitness-rules\frontend\visual-ui.md
- E:\Fitness\.fitness-sdd\context\FE3_CONTEXT.md
- E:\Fitness\.fitness-sdd\context\FE4_CONTEXT.md
- E:\Fitness\.fitness-sdd\context\FE5_CONTEXT.md
- E:\Fitness\.fitness-sdd\context\FE6_CONTEXT.md
- E:\Fitness\.fitness-sdd\context\FE7_CONTEXT.md
- E:\Fitness\.fitness-sdd\context\FE8_CONTEXT.md
- E:\Fitness\.fitness-sdd\context\FE9_CONTEXT.md
- E:\Fitness\.fitness-sdd\context\TASK_PACKET_TEMPLATE.md

The 24 declared targets are all present. No other file was intentionally edited.

## 5. Hàm/component/module liên quan

Documentation-only changes define or route:
- FE0 foundation auth/client/router/layout helpers and historical FE0-T01 through FE0-T09.
- FE1 account-oriented Admin views and historical FE1-T01 through FE1-T08.
- FE2 PT account/profile/role/assignment boundaries and historical FE2-T01 through FE2-T08.
- FE3-ALL through FE9-ALL aggregate phase scopes, entry gates, expected source families, functions, docblocks, tests, and exit evidence.
- The canonical 15-section completion-report and conditional canonical-reread protocol.

## 6. Database/API/contract impact

No database, API, migration, code, checkpoint, authority, or runtime contract was changed. Documentation now consistently records M061, Q08 scheduled-workout boundaries, Membership head/tail states, Payment read-only behavior, PT exact-assignment scope, generic AI provider handling, and official stored proposal sources.

## 7. Business rules and Q decisions

The synchronized documents cover:
- Q01/Q02/Q03/Q04/Q05/Q06/Q08/Q09/Q10/Q11/Q12/Q13 where applicable.
- Stored proposal sources TRO_LY and HUAN_LUYEN_VIEN; AI/PT remain display labels only.
- One-business-request AI quota reserve/finalize/refund semantics.
- One Chat usage only when a qualifying Member message wins first activation; later/read/PT/open/subscribe paths create none.
- M061 statuses HOAT_DONG/NGUNG_SU_DUNG, inactive-pivot preservation, exact error INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE, same-state no-op, audit CAP_NHAT_TRANG_THAI_NHOM_CO, and row-lock race serialization.
- Q08 only from an owned valid existing schedule; no direct schedule creation, unscheduled Start, or Free Workout in MVP.
- Active-chain tail CHO_DEN_LUOT; only a new-chain/head term uses CHO_KICH_HOAT.

## 8. Authorization/resource scope

Receptionist scope excludes a dashboard and is limited to lookup, Membership/eligibility status, QR check-in, and basic counter support. PT read/send scope follows exact active assignment and Q05 cleanup. Admin/Receptionist least privilege, Member ownership, current-token auth cleanup, and no Member/Admin API workarounds are documented.

## 9. Validation and failure cases

The documents specify Safe DTO/read-only Payment handling, Backend-owned entitlement/QR decisions, 401/403/404/409/422 behavior, unknown-outcome refetch with stable keys, proposal confirm-time ownership/version/context/TTL/status checks, QR replay/boundary handling, stale-template 409 handling, and blocker/deployment stop conditions.

## 10. Transaction/concurrency/idempotency/audit

The synchronized rules retain all ten canonical idempotency categories, M061 row locks and atomic audit/no-op behavior, Membership activation races and common clock, Q06 exact-term quota, Chat message/usage/activation/outbox atomicity with rollback and same-client-message retry, and Proposal single-apply conflict handling.

## 11. Error/status mapping

Canonical status/error references include:
- INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE
- CAP_NHAT_TRANG_THAI_NHOM_CO
- WORKOUT_TEMPLATE_STALE as a 409 expected_content_version conflict only
- CHO_KICH_HOAT, CHO_DEN_LUOT, DANG_HOAT_DONG, HET_HAN, HUY
- COMPLETE, PARTIALLY_READY, and BLOCKED final-readiness distinctions
- FE5 BLOCKER-03/04/05, FE7 Reverb/BE-FOLLOWUP-04 deployment readiness, and FE8 BLOCKER-06/07 entry gates.

## 12. Test cases/checks run and exact results

Required authority/context reads completed before editing:
- fitness-sdd role contract read to EOF.
- PROJECT_RULES.md read completely in sequential chunks (3017 lines).
- Root AGENTS.md read completely; nested AGENTS.md discovery performed and BE/AGENTS.md was not applicable to the documentation-only scope.
- VUE_WEB_IMPLEMENTATION_PLAN_V2.md read completely in sequential chunks (2046 lines).
- Task brief, baseline-status.txt, baseline-tree, and task-1-round-0-before inspected.

Final scoped checks:
- Forbidden stale-string scan over .fitness-rules and .fitness-sdd/context: PASS, no matches for hoat_dong = 0, /api/v1/member/workouts, components/dung_chung, [inherit | flash | pro], unconditional FULL_CANONICAL_REREAD: NO, or Gemini Flash.
- Stored proposal-source scan: PASS, no stored AI/PT source enums.
- Markdown relative-link resolver: PASS, 24 Markdown files checked, missing=0.
- Scoped reread: PASS, all 24 Markdown files read as UTF-8 after editing.
- Declared-target existence check: PASS, 24/24 present.
- M061 consistency scan: PASS, all seven required files contain M061, exact invariant/error, and audit code.
- Authoritative-count scan: PASS, 65/50/48 and 27/12/4/5 plus 7/4.
- Phase-gate scan: PASS, FE3-9 aggregate-task rule, FE5/FE7/FE8 gates, conditional canonical reread, and 15-section report.
- Baseline porcelain comparison: FAIL — `.fitness-rules.rar` was present initially and is absent now; directory-level porcelain also cannot prove byte preservation inside the pre-existing untracked layered trees.
- No npm/lint/build/product tests were run because the declared scope is documentation only; no code or runtime behavior was changed.

## 13. Evidence cuối trạng thái và baseline/path check

All 24 declared targets are present. The tracked working diff hash remained unchanged and the staged diff remained empty, which proves preservation of tracked dirty authorities/code/docs only. The pre-existing untracked layered-tree bytes cannot be proven from the failed snapshot, and `.fitness-rules.rar` is absent without provenance/disposition evidence. No commit, reset, restore, checkout, stash, clean, or push was performed.

## 14. Phần chưa hoàn thành hoặc blocker

No required documentation edit remains. Product implementation, frontend lint/build/tests, and Backend/Reverb deployment evidence remain outside this documentation task and must be handled by their respective phase tasks. The report is DONE_WITH_CONCERNS solely because runtime tests were intentionally out of scope.

## 15. Rủi ro còn lại

The layered trees were pre-existing untracked user work and the saved baseline-tree did not contain file copies for those directories. Semantic parity has been independently reviewed, but exact preservation of the original bytes remains unprovable; `.fitness-rules.rar` also remains unexplained.

## Round 1 repair record (LAYERED-DOCS-002 through 007)

Status: DONE_WITH_CONCERNS; LAYERED-DOCS-001 remains open and is not technically recoverable from the supplied evidence.

### Scope and changed files

Applied only the controller-authorized repair paths:

- `E:/Fitness/.fitness-sdd/context/TASK_PACKET_TEMPLATE.md`
- `E:/Fitness/.fitness-rules/RULE_INDEX.md`
- `E:/Fitness/.fitness-sdd/context/FE3_CONTEXT.md`
- `E:/Fitness/.fitness-sdd/context/FE4_CONTEXT.md`
- `E:/Fitness/.fitness-sdd/context/FE5_CONTEXT.md`
- `E:/Fitness/.fitness-sdd/context/FE6_CONTEXT.md`
- `E:/Fitness/.fitness-sdd/context/FE8_CONTEXT.md`
- `E:/Fitness/.fitness-sdd/context/FE9_CONTEXT.md`
- `E:/Fitness/.fitness-rules/domains/workout.md`

The report path is the only additional file written under the explicit controller exception. No authority, code, baseline/review artifact, or unrelated layered file was edited.

### Exact round-1 checks and results

- `ROUND1_SCOPED_REREAD`: PASS — all 9 allowed repair files reread as UTF-8.
- `REPORT_TEMPLATE_SCAN`: PASS — canonical 15 headings and order preserved; additive phase/task evidence follows the block.
- `REPORT_TEMPLATE_SOURCES_SCAN`: PASS — this template and `engineering/testing-definition-of-done.md` retain separate `Test Case` and `Test Result` sections.
- `FULL_READ_EXCEPTION_SCAN`: PASS — minimal default is retained; controller/role-contract/conflict/ambiguity/escalation full-read exception and `FULL_CANONICAL_REREAD: CONDITIONAL` are consistent; no unconditional prohibition remains.
- `RULE_INDEX_GATE_SCAN`: PASS — FE3/FE4/FE5/FE8/FE9 rows and complete phase-execution summary include the FE prerequisites plus additive Backend/deployment gates.
- `PHASE_CONTEXT_GATE_SCAN`: PASS — affected context gates, one aggregate task per FE3–FE9, FE6 dependency, and FE7 dependency/readiness wording verified; no READY-subset dispatch rule was introduced.
- `AUTHORITATIVE_COUNT_SCAN`: PASS — 65 capability rows, 50 router records, 48 screens (27 Admin/12 PT/4 Receptionist/5 shared), 7 Backend API blockers, and 4 Backend fix rows.
- `FE6_SOURCE_GUIDANCE_SCAN`: PASS — Frontend does not send/select/control proposal source; Backend persists `nguon_de_xuat = HUAN_LUYEN_VIEN`.
- `FE6_BACKEND_SOURCE_CONTRACT_SCAN`: PASS — verified request omits `nguon_de_xuat`; service persists `HUAN_LUYEN_VIEN`.
- `FE8_LOOKUP_SCOPE_SCAN`: PASS — lookup states the code/name/phone allow-list subject to finalized contract and Backend branch/resource scope.
- `WORKOUT_SCOPE_SCAN`: PASS — Workout Templates cites Section 21; Q12 is MVP no-hard-delete and leaves future retention/anonymization/purge/archive policy open.
- `MARKDOWN_RELATIVE_LINK_CHECK`: PASS — 24 Markdown files checked, missing relative links = 0.
- `ROUND1_ALLOWED_DELTA_CHECK`: PASS — exactly the 9 allowed repair paths differ from `task-1-round-1-before`; no unexpected repair path was found.

Round-1 before/current SHA-256 evidence:

| File | Before snapshot | Current | Delta |
|---|---|---|---|
| `TASK_PACKET_TEMPLATE.md` | `E7675EB914E38FBFD60056C2A82639177E312BEFFFFA8A78B27678FC3B020A75` | `957269F17414658F86FCA46EADA703E1143D82F886C1CCE2919E07895926F468` | changed |
| `RULE_INDEX.md` | `9CA5B70E551CFCE5944FB2C14EB5142669F3D98E674A7D4A9E71EFD9633EF214` | `7AFF596A4215EB2BC5622CDBF1AE8E2A3439C0825490F115A1FE0B43FE19F86C` | changed |
| `FE3_CONTEXT.md` | `1BF847DB0F1872BAF99EF8DDA84DC21957CD51274B558463DD30CC5513BD19CF` | `E802BDE3D78140F28A76F0CF9558854A34E70108A6870F16BCF518051BD24867` | changed |
| `FE4_CONTEXT.md` | `49B20BAAFA755CD06CBC8AB1D60386BA7EF49623D5BA1FF7C13652F3F023F5CE` | `17AA69CE756EBA7C80DA7778329127C8F11DAE77D7067F5E0BAEED73D5847C0` | changed |
| `FE5_CONTEXT.md` | `DBEDFEF865D43999EAB6C4275C75D1730A6A2E2B4F349E52ADFDCB904174BC5B` | `D6DDE84EF767484FAD8104BB324F5C4154E0B423E6B606FCC6B56F147452C9F0` | changed |
| `FE6_CONTEXT.md` | `2DE47216F6D274E567A2C72BA1199EF1780F2061D9F99F68DDF3FAC83E47188F` | `120435897D17ACDEC7F16028FC87A5BF743C056F22DC134576E0DA5B13863E58` | changed |
| `FE8_CONTEXT.md` | `349193FDAF0645AA28ECB0F2CA6DDD8F3AE5A3F8280C378034835854891B0B4F` | `AD4A6725CCF57F86BD5471F9A3E349D3BAC4009AB559C05F4B89C9BA933C285A` | changed |
| `FE9_CONTEXT.md` | `2214AF54732205AFE7D189EB0275920F801F25F2A5E0AB56F5682ACC341605BE` | `BF97891178FF3C7A80CAD2DC51DA4395734ECDD91B821B41109DF289EA7D9BDC` | changed |
| `workout.md` | `650DB785FA286108AC01A1306416BCDA8441BB68C2554883094521A4DC2899BE` | `45172BCF92D67E4CA5B6E2F38922F88976CD152EEE76EDF76945F8DC59DC6738` | changed |

### Open concern

`LAYERED-DOCS-001` remains open: the supplied `baseline-tree` does not contain byte copies for 21 pre-existing untracked targets, and the pre-task `.fitness-rules.rar` is absent without trustworthy provenance/disposition evidence. The round-1 semantic repairs do not and cannot close that preservation gate. No unsupported status-equality or external-attribution claim is made for this concern.

## Round 2 repair record (LAYERED-DOCS-004)

Status: DONE_WITH_CONCERNS; LAYERED-DOCS-004 wording repair is complete. LAYERED-DOCS-001 remains open as the independent preservation-evidence blocker.

### Scope and changed files

Changed only the three permitted Phase Context files:

- `E:/Fitness/.fitness-sdd/context/FE4_CONTEXT.md`
- `E:/Fitness/.fitness-sdd/context/FE5_CONTEXT.md`
- `E:/Fitness/.fitness-sdd/context/FE8_CONTEXT.md`

The report path is the controller-authorized append exception. No other layered document, authority, code, baseline/snapshot/review artifact, or Git state was edited.

### Exact Round 2 delta

The verified `task-1-round-2-before` snapshot differs only in the gate lines prescribed by the repair brief:

- FE4: STOP/entry gate and first acceptance checkbox now distinguish `READY` capability/contract availability from explicit `PASS` evidence for unlinked events, abnormal-event reconciliation visibility, Admin authorization, branch/resource-scope isolation, and Safe DTO/redaction boundaries.
- FE5: STOP gate, phase entry gate, and aggregate acceptance now require explicit `PASS` evidence for Backend authentication/authorization and exact-current-assignment resource-scope regressions, including applicable negative-scope cases.
- FE8: BLOCKER-06 lookup, BLOCKER-07 eligibility, STOP gate, and aggregate acceptance now require explicit `PASS` evidence for Receptionist authorization, branch/member scope, safe allow-listed results, Backend-authoritative eligibility/status, and cross-role/out-of-scope denial.

Snapshot/current SHA-256 evidence:

| File | Round-2 before | Current | Delta |
|---|---|---|---|
| `FE4_CONTEXT.md` | `17AA69CE756EBA7C80DA7778329127C8F11DAE77D7067F5E0BAEED73D5847C0` | `997DB0A904EC2952654A76491E931C5B1DAB12C98E58CD94E3951D3E537DCE83` | changed |
| `FE5_CONTEXT.md` | `D6DDE84EF767484FAD8104BB324F5C4154E0B423E6B606FCC6B56F147452C9F0` | `EF027E9B8A6C2A33DB7DE7E1BC839F03FEB59D6A6055CCD2449BDC28564186CB` | changed |
| `FE8_CONTEXT.md` | `AD4A6725CCF57F86BD5471F9A3E349D3BAC4009AB559C05F4B89C9BA933C285A` | `8468E6D001AEDD2459C415C7F7EC2094BECE3ECF39DBFBDC9759F9D677F836FB` | changed |

### Exact Round 2 checks and results

- `ROUND2_READY_TEST_SCAN`: PASS — `rtk rg` exited 1 with no matches for `test evidence.*READY`, `Backend tests?.*READY`, `Backend test evidence READY`, or `Backend tests đã READY`.
- `FE4_GATE_SCAN`: PASS — `FE-1 PASS`, BE-FOLLOWUP-01A/01B, both payment regressions, Admin authorization, branch/resource scope, redaction, and explicit `PASS` evidence are present.
- `FE5_GATE_SCAN`: PASS — `FE-0 PASS`, BLOCKER-03/04/05, authentication/authorization, exact-current-assignment resource scope, negative-scope cases, `WAITING_DEPENDENCY`, and explicit `PASS` evidence are present.
- `FE8_GATE_SCAN`: PASS — `FE-0 PASS`, BLOCKER-06/07, code/name/phone lookup, Receptionist authorization, branch/resource scope, and explicit `PASS` evidence are present.
- `ROUND2_AGGREGATE_GATE_SCAN`: PASS — FE4/FE5/FE8 remain one aggregate task each with no-premature-dispatch protections.
- `ROUND2_FINAL_REREAD`: PASS — all 3 changed files reread as UTF-8 through EOF.
- `MARKDOWN_RELATIVE_LINK_CHECK`: PASS — 24 Markdown files checked, missing relative links = 0.
- `ROUND2_EXACT_DELTA_CHECK`: PASS — only FE4_CONTEXT.md, FE5_CONTEXT.md, and FE8_CONTEXT.md differ from `task-1-round-2-before`; diff hunks are limited to the prescribed gate lines.

### Open concern

`LAYERED-DOCS-001` remains `OPEN — GENUINE EVIDENCE BLOCKER`: no trustworthy pre-task bytes exist for the 21 pre-existing untracked layered targets, and `.fitness-rules.rar` has no trustworthy provenance, hash/content accounting, or authorized disposition. This Round 2 wording repair does not and cannot close that finding.
