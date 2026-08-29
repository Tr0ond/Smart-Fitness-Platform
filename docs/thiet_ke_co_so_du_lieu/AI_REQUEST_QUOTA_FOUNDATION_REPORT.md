# AI REQUEST + QUOTA FOUNDATION REPORT

## 1. Scope

Đã triển khai Backend foundation cho MEMBER tạo yêu cầu AI lập kế hoạch tập, kiểm tra entitlement theo đúng kỳ Membership, reserve/consume/refund quota, kích hoạt kỳ bằng request trả phí hợp lệ đầu tiên, lọc candidate bằng Rule Engine, gọi provider qua abstraction, validate Structured Output và công bố Proposal bất biến để preview. Không Apply Proposal, không sửa Workout Plan/Workout History, không triển khai PT/Chat/FE/Mobile và không gọi LLM thật.

## 2. Environment

- PHP: 8.4.25
- Laravel: 13.29.0
- DB_CONNECTION: `mysql`
- DBMS: MariaDB 10.4.32, InnoDB
- SESSION sql_mode: `STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION`
- Server charset/collation: `utf8mb4` / `utf8mb4_general_ci`; schema/migrations giữ policy `utf8mb4_unicode_ci` và các override `utf8mb4_nopad_bin` đã duyệt.
- Test schema: `smart_fitness_ai_test_20260829_codex7f4a`

## 3. AI Architecture

Flow:

```text
HTTP validation + scope validation
→ Backend Rule Engine
→ candidate snapshots
→ transaction reserve quota + usage + activation
→ commit
→ provider outside transaction
→ Structured Output validation
→ context revalidation
→ transaction consume quota + immutable Proposal
```

- Backend Rule Engine → candidates → provider → Structured Output → Backend validation → Proposal.
- Direct DB authority for LLM: **NO**.
- Direct Workout Plan mutation: **NO**.
- Provider failure compensation is a later transaction and never reverses the committed Membership activation.

## 4. Provider

- Provider interface: `App\Contracts\Ai\WorkoutAiProvider` with `generateStructuredProposal(context)`.
- Production adapter: `UnavailableWorkoutAiProvider`, fail-closed until a provider is explicitly selected and configured.
- Runtime config: `config/ai.php`; application code does not call `env()` directly.
- Real provider configured: **NO**.
- Real LLM call: **NO / NOT PERFORMED**.
- Automated tests use: **FAKE**, with `SUCCESS`, `TIMEOUT`, `RATE_LIMIT`, `SERVER_ERROR`, `MALFORMED_OUTPUT`, `UNKNOWN_EXERCISE`, `INVALID_STRUCTURE` and exact-output injection.

## 5. Endpoints

| Method | Path | Role | Purpose |
| --- | --- | --- | --- |
| POST | `/api/assistant/requests` | MEMBER | Tạo và xử lý một request workout AI trả phí. |
| GET | `/api/assistant/requests` | MEMBER | Đọc tối đa 50 request gần nhất của chính Member. |
| GET | `/api/assistant/requests/{assistantRequest}` | MEMBER | Đọc request và metadata lần gọi an toàn của chính Member. |
| GET | `/api/assistant/proposals/{proposal}` | MEMBER | Preview Proposal AI của chính Member, kể cả đã hết TTL. |

Không có route PATCH/PUT/DELETE Proposal và không có route Apply trong module này.

## 6. AI Entitlement

- Membership source: `ky_han_hoi_vien.cho_phep_tro_ly_tap_luyen`, `gioi_han_luot_tro_ly`, `so_luot_tro_ly_da_dung`, `so_luot_tro_ly_giu_cho`.
- Applicable term: đúng head `CHO_KICH_HOAT` có thể kích hoạt hoặc đúng kỳ hiện tại theo `[ngay_bat_dau, ngay_ket_thuc)`.
- Denied: không Membership, `CHO_THANH_TOAN`, HUY, HET_HAN, snapshot tắt AI và quota hiện tại đã hết.
- Future borrowing: **NO**; kỳ `CHO_DEN_LUOT` không cấp AI trước lượt và quota không được gộp.
- Workout cơ bản không bị gắn entitlement AI.

## 7. Quota

- Limit source: snapshot `ky_han_hoi_vien.gioi_han_luot_tro_ly`; NULL tiếp tục mang nghĩa unlimited theo schema.
- Used: `so_luot_tro_ly_da_dung`.
- Reserved: `so_luot_tro_ly_giu_cho`.
- Reservation transaction: khóa Member → chuỗi/kỳ, revalidate quyền/candidate, tạo `su_dung_quyen_loi` loại `YEU_CAU_TRO_LY`, `yeu_cau_tro_ly` trạng thái `GIU_CHO`, candidate snapshots và tăng reserved đúng 1.
- Success consume: `GIU_CHO → DA_TINH`, reserved -1, used +1 trong transaction tạo Proposal.
- Failure refund: `GIU_CHO → DA_TRA`, reserved -1, used giữ nguyên; nhánh `DA_TINH → DA_TRA` cũng được bảo vệ để hoàn used đúng một lần nếu có lỗi kỹ thuật muộn.
- Request sai/thiếu/out-of-scope/no-candidate bị từ chối trước reservation, usage và activation.

## 8. Membership Activation

- Invalid request: **NO activation**.
- Valid accepted AI request: **YES khi head đang CHO_KICH_HOAT**.
- Provider failure rolls activation back: **NO**.
- First-use reference: `dang_ky_goi_tap.lan_su_dung_dau_tien_id` trỏ đúng `su_dung_quyen_loi` loại `YEU_CAU_TRO_LY` của request.
- Activation dùng lại `MembershipActivationService`; ngày bắt đầu/kết thúc của toàn chuỗi chỉ materialize một lần.
- Member đã active gặp lỗi provider được refund quota nhưng ngày Membership giữ nguyên.

## 9. Candidate Rule Engine

- Profile inputs: mục tiêu, kinh nghiệm, số ngày/tuần, thời lượng, `phien_ban_ho_so`, `moc_thay_doi_ke_hoach`.
- Availability: `ngay_ranh_hoi_vien`, yêu cầu đủ số ngày mong muốn.
- Equipment: `dung_cu_hoi_vien` + `dung_cu` đang hoạt động.
- Exercise catalog: chỉ `bai_tap.trang_thai = HOAT_DONG`, có snapshot `phien_ban_noi_dung`, equipment và `bai_tap_nhom_co`.
- Template candidates: `giao_an_mau` hoạt động, đúng số buổi/tuần.
- Equipment rule: **AND**; toàn bộ dụng cụ bắt buộc phải thuộc tập dụng cụ Member.
- Candidate stable ordering: `bai_tap.id` và `giao_an_mau.id` tăng dần.
- No-equipment exercise: **ALLOWED** nếu các điều kiện khác hợp lệ.
- Runtime chỉ đọc canonical Database; không đọc `.tmp/exercises-dataset`.

## 10. Structured Output

- Schema version: `workout-proposal-v1`.
- Root fields: `loai_thay_doi`, `tieu_de`, `giai_thich`, `ap_dung_tu_ngay`, `ngay_trong_ke_hoach`.
- Day fields: `thu_trong_tuan`, `bai_tap_trong_ke_hoach`.
- Exercise fields: `bai_tap_id`, `thu_tu`, `so_hiep_muc_tieu`, `so_lan_lap_toi_thieu`, `so_lan_lap_toi_da`, `thoi_gian_nghi_giay`.
- Allowed exercise IDs: candidate set only.
- Unknown ID: **REJECTED**, quota refunded and no Proposal.
- Natural-language plan parsing: **NOT USED**.

## 11. Backend Validation

- Required keys/types, string lengths and nested array sizes.
- Request type ↔ `loai_thay_doi` mapping.
- Exact desired day count, unique/allowed weekdays and availability.
- Candidate allow-list, catalog existence/status/content version.
- Equipment AND rule rechecked after provider latency.
- Sets 1..10, reps 1..100, min <= max, rest 0..600.
- Unique exercise and order inside each day.
- Future/non-past effective date.
- Active account/role, owner, profile version, plan-change marker, availability, equipment and active Plan/version context.
- Stale profile/equipment/Plan/candidate results in no Proposal and idempotent quota refund.

## 12. Proposal

- Table: `de_xuat_ke_hoach_tap`.
- Source: `TRO_LY`; owner and `yeu_cau_tro_ly_id` are protected by official FK/UNIQUE constraints.
- Immutable: **YES** for this module; no mutation endpoint and idempotent retry returns the existing row/hash.
- TTL: **24 hours default** from `config('ai.proposal_ttl_hours')`.
- Concrete expiry: **YES**, `het_han_luc` is persisted at creation; later config changes do not alter it.
- Boundary: `[ngay_tao, het_han_luc)`; at expiry it is reported expired but remains readable as history.
- Plan mutated: **NO**; `ke_hoach_tap`, versions, schedule and workout history are read-only context in this module.

## 13. Idempotency

- Request: `yeu_cau_chong_lap` scoped by user + `TAO_YEU_CAU_TRO_LY` + UUID `Idempotency-Key`; hash uses normalized request.
- Same key/same normalized payload: returns the same logical request/Proposal and consumes one quota.
- Same key/different payload: HTTP 409 `IDEMPOTENCY_CONFLICT`.
- In progress retry: HTTP 409 `IDEMPOTENCY_IN_PROGRESS`.
- Failed request retry: returns the same safe failure without calling provider or reserving again.
- Success finalization: request status + UNIQUE Proposal + locked counter make repeat a no-op.
- Failure compensation: `DA_TRA` is terminal for refund; repeat does not decrement again.

## 14. Concurrency

- Actual parallel: **YES**.
- Test used two independent PHP 8.4 processes and two Laravel/DB connections with quota limit = 1.
- Winner committed reservation before waiting at a Fake provider file barrier; loser was rejected with `AI_QUOTA_EXHAUSTED` while winner was still outside the transaction.
- Provider calls: exactly 1.
- Final state: one request, one Proposal, used = 1, reserved = 0.
- Oversubscription: **BLOCKED**.

## 15. Provider Failure Matrix

| Failure | Request/model result | Quota | Activation | Proposal |
| --- | --- | --- | --- | --- |
| Timeout | `THAT_BAI` / `QUA_HAN` | Refunded | Retained | None |
| Provider 429 | `THAT_BAI` | Refunded | Retained | None |
| Provider 5xx | `THAT_BAI` | Refunded | Retained | None |
| Malformed output | `LOI_CAU_TRUC` | Refunded | Retained | None |
| Invalid structure | `LOI_CAU_TRUC` | Refunded | Retained | None |
| Unknown candidate ID | `LOI_NGHIEP_VU` | Refunded | Retained | None |
| Stale profile/equipment/context | `LOI_NGHIEP_VU` | Refunded | Retained | None |

## 16. Security

- IDOR request/proposal: **PASS**; owner scope from Bearer principal, foreign ID concealed as 404.
- Client-supplied user/member/candidate/proposal/quota authority: **BLOCKED** by Form Request.
- Sensitive provider context: **MINIMIZED**; no email, phone, password hash, token, payment/provider event or bank data.
- Prompt injection treated as untrusted: **YES**; policy is included in provider context and Backend allow-list validation remains authoritative.
- Provider/system secret or raw hidden prompt persisted/logged: **NO**.
- Secrets exposed/committed: **NO**.
- Response only exposes allow-listed request, Proposal and safe model-call status fields.

## 17. Tests

- Validation tests: no token, wrong role, missing/empty/malformed/client-authority payload, out-of-scope and no-candidate; no provider/quota/activation.
- Entitlement: no Membership, unpaid, CHO_KICH_HOAT, active, HUY, expired, disabled AI, exhausted quota and future-term non-borrowing.
- Quota: reserve, consume, refund and two successful requests trên cùng kỳ giới hạn.
- Activation: first valid request activation, first-use pointer, provider-failure retention and active-member date preservation.
- Provider failures: all seven Fake modes; no Proposal and refund exactly once.
- Candidates: equipment AND, no-equipment, inactive exclusion and deterministic ordering.
- Proposal: Structured Output, unknown ID, stale profile/equipment, 24h TTL, read after expiry, no PATCH/overwrite and no Workout mutation.
- Idempotency: success replay, failed replay, payload conflict, finalizer replay and refund replay.
- Concurrency: two actual processes/connections, one provider call, no quota oversubscription.
- Focused AI suite: **PASS — 17 tests, 246 assertions**.
- Full backend run 1: **PASS — 138 tests, 1,973 assertions**.
- Full backend run 2: **PASS — 138 tests, 1,973 assertions**.
- Random `--random-order-seed=20260829`: **PASS — 138 tests, 1,973 assertions**.
- Pint affected-files check: **PASS**.
- Composer validation: **PASS**.
- `git diff --check`: **PASS**.

## 18. Development Database Safety

- `smart_fitness` modified: **NO**.
- Read-only final baseline: migrations = 60, users = 8, `yeu_cau_tro_ly` = 0, AI `su_dung_quyen_loi` = 0, `de_xuat_ke_hoach_tap` = 0, `lan_goi_mo_hinh` = 0.
- Real LLM request: **NO**.
- Test schema: `smart_fitness_ai_test_20260829_codex7f4a`.
- M001–M060 ran only on the isolated schema; migrations were not created or changed.
- Seed baseline: 8 users, 1,324 exercises; after tests all AI request/usage/Proposal/model-call counts returned to 0.
- Cleanup: **PASS**; schema was dropped and `information_schema` verified zero remaining rows for its schema name.

## 19. Deferred

- Proposal Apply: **DEFERRED**.
- Workout Plan/Version/Schedule mutation: **DEFERRED**.
- PT Proposal: **DEFERRED**.
- PT assignment/direct service: **DEFERRED**.
- PT Chat/realtime: **DEFERRED**.
- Nutrition: **OUT OF SCOPE**.
- Medical/injury/supplement: **OUT OF SCOPE**.
- FE/Mobile: **DEFERRED**.
- Real provider selection, credential setup and paid/network smoke: **DEFERRED** until explicitly approved.

## 20. Files Changed

Configuration/integration:

- `BE/.env.example`
- `BE/config/ai.php`
- `BE/app/Providers/AppServiceProvider.php`
- `BE/routes/api.php`

Provider contract/data/errors:

- `BE/app/Contracts/Ai/WorkoutAiProvider.php`
- `BE/app/Data/Ai/WorkoutAiResult.php`
- `BE/app/Exceptions/Ai/AiWorkflowException.php`
- `BE/app/Exceptions/Ai/AiProviderException.php`
- `BE/app/Gateways/UnavailableWorkoutAiProvider.php`

HTTP/services:

- `BE/app/Http/Controllers/Api/Ai/AiRequestController.php`
- `BE/app/Http/Requests/Ai/CreateAiRequest.php`
- `BE/app/Services/Ai/AiRequestNormalizer.php`
- `BE/app/Services/Ai/AiCandidateRuleEngine.php`
- `BE/app/Services/Ai/AiStructuredOutputValidator.php`
- `BE/app/Services/Ai/AiRequestService.php`
- `BE/app/Services/Ai/AiRequestQueryService.php`

Tests/report:

- `BE/tests/Concerns/CreatesAiFixtures.php`
- `BE/tests/Fakes/FakeWorkoutAiProvider.php`
- `BE/tests/Feature/AiRequestApiTest.php`
- `BE/tests/Feature/AiCandidateRuleEngineTest.php`
- `BE/tests/Feature/AiQuotaConcurrencyTest.php`
- `BE/tests/Support/run_ai_request.php`
- `docs/thiet_ke_co_so_du_lieu/AI_REQUEST_QUOTA_FOUNDATION_REPORT.md`

Không sửa M001–M060, Model, Seeder, PROJECT_RULES, Database Design/Data Dictionary/ERD, FE hoặc Mobile.

## 21. Deviations

NONE. Provider business choice chưa được chốt nên runtime adapter mặc định fail-closed; automated proof dùng Fake provider như yêu cầu. Module không thêm rate limit tùy ý và không dùng quota như HTTP throttle.

## 22. Final Gate

- AI REQUEST API = PASS
- AI REQUEST OWNERSHIP / IDOR = PASS
- AI ENTITLEMENT = PASS
- AI QUOTA CHECK = PASS
- AI QUOTA RESERVATION = PASS
- AI QUOTA CONSUMPTION = PASS
- AI QUOTA FAILURE REFUND = PASS
- AI QUOTA CONCURRENCY = PASS
- VALID AI REQUEST ACTIVATION = PASS
- INVALID REQUEST NON-ACTIVATION = PASS
- PROVIDER FAILURE ACTIVATION RETAINED = PASS
- RULE ENGINE = PASS
- EQUIPMENT AND RULE = PASS
- CANDIDATE ALLOW-LIST = PASS
- STRUCTURED OUTPUT = PASS
- UNKNOWN EXERCISE ID = BLOCKED
- BACKEND POST-LLM VALIDATION = PASS
- IMMUTABLE PROPOSAL = PASS
- PROPOSAL TTL 24H = PASS
- AI DIRECT DATABASE AUTHORITY = BLOCKED
- AI DIRECT WORKOUT PLAN WRITE = BLOCKED
- REQUEST IDEMPOTENCY = PASS
- FINALIZATION IDEMPOTENCY = PASS
- REFUND IDEMPOTENCY = PASS
- FULL BACKEND TEST SUITE = PASS
- TEST ORDER INDEPENDENCE = PASS
- TEST DATABASE CLEANUP = PASS
- `smart_fitness` = SAFE

- AI REQUEST + QUOTA FOUNDATION = PASS
- AI ENTITLEMENT = IMPLEMENTED
- AI QUOTA RESERVATION/CONSUMPTION = IMPLEMENTED
- AI FAILURE REFUND = IMPLEMENTED
- MEMBERSHIP ACTIVATION BY VALID AI REQUEST = IMPLEMENTED
- RULE ENGINE CANDIDATES = IMPLEMENTED
- STRUCTURED LLM CONTRACT = IMPLEMENTED
- BACKEND AI VALIDATION = IMPLEMENTED
- IMMUTABLE WORKOUT PROPOSAL = IMPLEMENTED
- WORKOUT PLAN APPLY = NOT YET IMPLEMENTED
- DATABASE = READY FOR NEXT MODULE
