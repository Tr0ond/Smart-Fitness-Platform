# RULE_INDEX.md — Smart Fitness Platform Context Routing Table

> **Layered revision:** `2026-09-10` | **Trạng thái:** Bảng định tuyến nạp context chính thức; business-rule version không đổi  
> **Mục đích:** Chỉ định chính xác các file tài liệu cần đọc cho từng loại tác vụ, loại bỏ việc đọc dư thừa token.

---

## 1. Nguyên tắc Định tuyến (Routing Principles)

1. **Nạp tối thiểu (Minimal Working Set):**  
   Mỗi Agent/Subagent chỉ được nạp:
   - `PROJECT_CORE.md` (Bắt buộc cho mọi agent).
   - Module nghiệp vụ/kỹ thuật được chỉ định cụ thể dưới đây cho task hiện tại.
   - File Phase Context (nếu làm Frontend, ví dụ: `.fitness-sdd/context/FE3_CONTEXT.md`).
2. **Nghiêm cấm đọc đệ quy hoặc đọc toàn bộ (Strict Prohibition):**  
   - **CẤM** nạp tất cả các module trong thư mục `.fitness-rules/` "để cho chắc chắn".
   - **CẤM** đọc toàn bộ `PROJECT_RULES.md` (3000 dòng) hoặc `docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md` (2000 dòng) làm context mặc định.
3. **Điều kiện Leo thang về Canonical Document (Escalation Triggers):**  
   Chỉ Controller/Lead Planner hoặc Agent gặp 1 trong các trường hợp sau mới được tra cứu phần tương ứng trong [PROJECT_RULES.md](../PROJECT_RULES.md):
   - Phát hiện xung đột (conflict) giữa hai module hoặc giữa code và tài liệu.
   - Điểm nghiệp vụ chưa rõ ràng (ambiguity) có nguy cơ ảnh hưởng logic dữ liệu.
   - Lỗi phiên bản (version mismatch) giữa các tài liệu.
   - Khi cần kiểm tra lại câu chữ gốc của hợp đồng nghiệp vụ đặc biệt.

---

## 2. Ma trận Định tuyến theo Nghiệp vụ (Domain Routing Matrix)

| Lĩnh vực / Tác vụ được giao | Module bắt buộc phải đọc | Rule IDs & Nội dung quy định |
|---|---|---|
| **Auth, Roles & Phân quyền Tài nguyên** | 1. [PROJECT_CORE.md](PROJECT_CORE.md)<br>2. [domains/auth-resource-scope.md](domains/auth-resource-scope.md) | **Actor:** Member, PT, Receptionist, Admin.<br>**Roles:** ADMIN, PT, RECEPTIONIST, MEMBER.<br>**Quy tắc:** RULE CODE 17, Q04, Q13.<br>Custom database Bearer token, 401 vs 403, kiểm tra quyền sở hữu resource. |
| **Gói tập, Thanh toán & Check-in** | 1. [PROJECT_CORE.md](PROJECT_CORE.md)<br>2. [domains/membership-payment.md](domains/membership-payment.md)<br>3. [engineering/security-integrity.md](engineering/security-integrity.md) | **Quy tắc:** RULE GYM 01–10, 17–23, 25.<br>**Decisions:** Q01, Q02, Q06, Q09, Q10.<br>Thanh toán != Kích hoạt, đồng hồ chung, payOS webhook, QR TTL 90s, snapshot đơn. |
| **Bài tập, Lịch tập & Workout Tracking** | 1. [PROJECT_CORE.md](PROJECT_CORE.md)<br>2. [domains/workout.md](domains/workout.md)<br>3. [engineering/coding-conventions.md](engineering/coding-conventions.md) | **Quy tắc:** RULE GYM 11–16.<br>**Decisions:** Q07A, Q07B, Q07C, Q08, Q11, Q12.<br>Session hoàn thành bất biến, Q08 tracking cơ bản, bài tập-dụng cụ AND, kế hoạch tương lai. |
| **Huấn luyện viên (PT) & Realtime Chat** | 1. [PROJECT_CORE.md](PROJECT_CORE.md)<br>2. [domains/pt-chat.md](domains/pt-chat.md)<br>3. [domains/auth-resource-scope.md](domains/auth-resource-scope.md) | **Quy tắc:** RULE GYM 09, 23, 24.<br>**Decisions:** Q04, Q05, Q06, Q13.<br>Phân công PT theo thời gian, tách Read History/Send New, xác nhận buổi tập, PT note, PT proposal. |
| **Trợ lý AI & Đề xuất Kế hoạch (AI Proposals)** | 1. [PROJECT_CORE.md](PROJECT_CORE.md)<br>2. [domains/ai-proposal.md](domains/ai-proposal.md)<br>3. [engineering/security-integrity.md](engineering/security-integrity.md) | **Quy tắc:** RULE GYM 14, RULE CODE 14.<br>**Decisions:** Q03, Q09.<br>AI Structured Output, Rule Engine validation, Quota 1 lượt/request nghiệp vụ, TTL 24h, User duyệt proposal, chống double-apply. |
| **Database, Migration & Schema** | 1. [PROJECT_CORE.md](PROJECT_CORE.md)<br>2. [domains/database.md](domains/database.md)<br>3. [engineering/security-integrity.md](engineering/security-integrity.md) | **Quy tắc:** RULE CODE 01–04, 15, 16.<br>**Decisions:** Q12.<br>52 bảng tiếng Việt không dấu snake_case, không viết tắt, InnoDB, Transaction, Audit trail Q12. |

---

## 3. Ma trận Định tuyến cho Kỹ thuật & Frontend (Engineering & Frontend Matrix)

| Vai trò / Tác vụ Kỹ thuật | Module bắt buộc phải đọc | Rule IDs & Nội dung quy định |
|---|---|---|
| **Frontend Vue Web (Chung)** | 1. [PROJECT_CORE.md](PROJECT_CORE.md)<br>2. [frontend/vue-core.md](frontend/vue-core.md)<br>3. [frontend/visual-ui.md](frontend/visual-ui.md)<br>4. [engineering/coding-conventions.md](engineering/coding-conventions.md) | **Quy tắc:** RULE CODE 05–08, 09–11.<br>Vue 3 Composition API JavaScript, 7 Pinia stores, Vue Router guards, Light Staff UI (CSS variables), FE naming convention. |
| **Frontend Phase FE-0 (Foundation)** | 1. [PROJECT_CORE.md](PROJECT_CORE.md)<br>2. [FE0_CONTEXT.md](../.fitness-sdd/context/FE0_CONTEXT.md)<br>3. [frontend/vue-core.md](frontend/vue-core.md)<br>4. [frontend/visual-ui.md](frontend/visual-ui.md)<br>5. [engineering/coding-conventions.md](engineering/coding-conventions.md) | Historical tasks `FE0-T01`…`FE0-T09`; auth, client, router, layouts, test/lint foundation; no domain screens. |
| **Frontend Phase FE-1 (Admin Foundation)** | 1. [PROJECT_CORE.md](PROJECT_CORE.md)<br>2. [FE1_CONTEXT.md](../.fitness-sdd/context/FE1_CONTEXT.md)<br>3. [frontend/vue-core.md](frontend/vue-core.md)<br>4. [frontend/visual-ui.md](frontend/visual-ui.md)<br>5. [domains/auth-resource-scope.md](domains/auth-resource-scope.md) | Historical tasks `FE1-T01`…`FE1-T08`; requires FE-0 PASS; account-oriented Admin views only. |
| **Frontend Phase FE-2 (Admin PT)** | 1. [PROJECT_CORE.md](PROJECT_CORE.md)<br>2. [FE2_CONTEXT.md](../.fitness-sdd/context/FE2_CONTEXT.md)<br>3. [frontend/vue-core.md](frontend/vue-core.md)<br>4. [domains/auth-resource-scope.md](domains/auth-resource-scope.md)<br>5. [domains/pt-chat.md](domains/pt-chat.md) | Historical tasks `FE2-T01`…`FE2-T08`; account/profile/assignment distinction, exact blockers and fixes. |
| **Frontend Phase FE3 (Admin Catalog)** | 1. [PROJECT_CORE.md](PROJECT_CORE.md)<br>2. [FE3_CONTEXT.md](../.fitness-sdd/context/FE3_CONTEXT.md)<br>3. [frontend/vue-core.md](frontend/vue-core.md)<br>4. [domains/workout.md](domains/workout.md)<br>5. [domains/membership-payment.md](domains/membership-payment.md)<br>6. [engineering/security-integrity.md](engineering/security-integrity.md) | One aggregate task `FE3-ALL` (12 screens); entry requires FE-1 PASS and actual catalog contracts READY; Package, Equipment, Muscle Group, Exercise, Template; Q11/Q01, M061, exact error `INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE`, audit `CAP_NHAT_TRANG_THAI_NHOM_CO`, `WORKOUT_TEMPLATE_STALE`. |
| **Frontend Phase FE4 (Admin Payment)** | 1. [PROJECT_CORE.md](PROJECT_CORE.md)<br>2. [FE4_CONTEXT.md](../.fitness-sdd/context/FE4_CONTEXT.md)<br>3. [frontend/vue-core.md](frontend/vue-core.md)<br>4. [domains/membership-payment.md](domains/membership-payment.md) | One aggregate task `FE4-ALL` (3 screens); entry requires FE-1 PASS plus BE-FOLLOWUP-01A/01B contract/test evidence PASS; read-only Payment/reconciliation. |
| **Frontend Phase FE5 (PT Workspace)** | 1. [PROJECT_CORE.md](PROJECT_CORE.md)<br>2. [FE5_CONTEXT.md](../.fitness-sdd/context/FE5_CONTEXT.md)<br>3. [frontend/vue-core.md](frontend/vue-core.md)<br>4. [domains/pt-chat.md](domains/pt-chat.md)<br>5. [domains/workout.md](domains/workout.md)<br>6. [domains/auth-resource-scope.md](domains/auth-resource-scope.md) | One aggregate task `FE5-ALL` (7 screens); entry requires FE-0 PASS plus BLOCKER-03/04/05 contracts and Backend tests PASS. |
| **Frontend Phase FE6 (PT Direct + Proposal)** | 1. [PROJECT_CORE.md](PROJECT_CORE.md)<br>2. [FE6_CONTEXT.md](../.fitness-sdd/context/FE6_CONTEXT.md)<br>3. [frontend/vue-core.md](frontend/vue-core.md)<br>4. [domains/pt-chat.md](domains/pt-chat.md)<br>5. [domains/workout.md](domains/workout.md) | One aggregate task `FE6-ALL` (3 screens); requires FE5-ALL PASS; stable key, Q06, Q13. |
| **Frontend Phase FE7 (PT Realtime Chat)** | 1. [PROJECT_CORE.md](PROJECT_CORE.md)<br>2. [FE7_CONTEXT.md](../.fitness-sdd/context/FE7_CONTEXT.md)<br>3. [frontend/vue-core.md](frontend/vue-core.md)<br>4. [domains/pt-chat.md](domains/pt-chat.md)<br>5. [domains/auth-resource-scope.md](domains/auth-resource-scope.md) | One aggregate task `FE7-ALL` (1 screen); requires FE5-ALL, approved dependency compatibility, and Reverb/BE-FOLLOWUP-04 deployment readiness. |
| **Frontend Phase FE8 (Receptionist Portal)** | 1. [PROJECT_CORE.md](PROJECT_CORE.md)<br>2. [FE8_CONTEXT.md](../.fitness-sdd/context/FE8_CONTEXT.md)<br>3. [frontend/vue-core.md](frontend/vue-core.md)<br>4. [domains/membership-payment.md](domains/membership-payment.md)<br>5. [domains/auth-resource-scope.md](domains/auth-resource-scope.md) | One aggregate task `FE8-ALL` (3 screens); entry requires FE-0 PASS plus BLOCKER-06/07 contracts and Backend tests PASS. |
| **Frontend Phase FE9 (Final Acceptance)** | 1. [PROJECT_CORE.md](PROJECT_CORE.md)<br>2. [FE9_CONTEXT.md](../.fitness-sdd/context/FE9_CONTEXT.md)<br>3. [engineering/testing-definition-of-done.md](engineering/testing-definition-of-done.md)<br>4. [engineering/security-integrity.md](engineering/security-integrity.md)<br>5. [frontend/visual-ui.md](frontend/visual-ui.md) | One aggregate task `FE9-ALL`; entry requires FE-0 through FE-8 PASS, all 7 Backend API blockers and 4 fix rows resolved/regression-tested, and realtime/deployment gate ready; no PASS while evidence is open. |
| **Reviewer / Test Verification Agent** | 1. [PROJECT_CORE.md](PROJECT_CORE.md)<br>2. [engineering/testing-definition-of-done.md](engineering/testing-definition-of-done.md)<br>3. Module domain tương ứng của task<br>4. Task context & Git diff | **Quy tắc:** RULE CODE 19, 20.<br>Tiêu chí nghiệm thu DoD, Test Cases 48–54.2, evidence logs. |

---

## 4. Phase execution and authoritative counts

FE-3 → FE-9 mỗi phase là đúng một task tổng hợp: FE3-ALL, FE4-ALL, FE5-ALL, FE6-ALL, FE7-ALL, FE8-ALL, FE9-ALL. Các mục checklist không phải task con và không cho phép dispatch writer chỉ cho READY subset. FE3-ALL chờ FE-1 PASS và catalog contracts READY; FE4-ALL chờ FE-1 PASS và BE-FOLLOWUP-01A/01B; FE5-ALL chờ FE-0 PASS và BLOCKER-03/04/05; FE6-ALL chờ FE5-ALL PASS; FE7-ALL chờ FE5-ALL PASS, dependency compatibility và Reverb/BE-FOLLOWUP-04; FE8-ALL chờ FE-0 PASS và BLOCKER-06/07; FE9-ALL chờ FE-0 → FE-8 PASS cùng toàn bộ backend/fix/realtime gates.

Các count authoritative của Vue plan phải giữ nguyên: 65 capability rows, 50 router records, 48 screens = 27 Admin + 12 PT + 4 Receptionist + 5 shared, 7 Backend API blockers và 4 Backend fix-in-progress rows.

---

## 5. Mục lục File Quy tắc Chi tiết (File Manifest)

### Domains:
- `domains/auth-resource-scope.md`: Định danh Actor, Role, Resource Ownership, Authorization Gates, Q04, Q13.
- `domains/membership-payment.md`: Chi tiết RULE GYM 01–10, 17–23, 25; Q01, Q02, Q06, Q09, Q10; payOS integration.
- `domains/workout.md`: Chi tiết RULE GYM 11–16; Q07A–C, Q08, Q11, Q12; Cấu trúc bài tập, kế hoạch, phiên tập.
- `domains/pt-chat.md`: Chi tiết RULE GYM 09, 23, 24; Q04, Q05, Q06, Q13; Realtime Chat, PT assignment, PT notes, Proposal.
- `domains/ai-proposal.md`: Chi tiết RULE GYM 14; Q03, Q09; provider-agnostic Structured Output, Rule Engine validation, Quota.
- `domains/database.md`: Chi tiết RULE CODE 01–04; Danh mục 52 bảng chuẩn, quy tắc khóa chính/ngoại, Q12 audit trail.

### Engineering:
- `engineering/coding-conventions.md`: RULE CODE 05–12; Quy ước đặt tên tiếng Việt không dấu camelCase, docblocks, comment bước.
- `engineering/testing-definition-of-done.md`: RULE CODE 19, 20; Definition of Done, 9 nhóm test cases (PHẦN XVII: 48–54.2).
- `engineering/security-integrity.md`: RULE CODE 13–17; Thẩm quyền Backend, Transaction, Idempotency, Concurrency controls.

### Frontend:
- `frontend/vue-core.md`: Kiến trúc Vue 3 Composition API, Pinia stores, Vue Router, API client, Error handling, Echo realtime.
- `frontend/visual-ui.md`: Hệ thống Visual UI chuẩn MVP (Light staff UI, CSS variables / scoped styles, không dark mode trong MVP, không Tailwind, không Shadcn-vue, không UI framework trong MVP).
