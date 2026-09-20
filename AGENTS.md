# AGENTS.md — Smart Fitness Platform

## Bắt buộc đọc trước khi làm việc (Quy trình Nạp Context Phân tầng)

Dự án áp dụng **Kiến trúc Context Phân tầng (Layered Context Architecture)** để tối ưu token và ngăn ngừa quá tải ngữ cảnh cho Subagent:

1. **PROJECT_RULES.md là Canonical Source of Truth:**
   - [PROJECT_RULES.md](PROJECT_RULES.md) ở thư mục gốc là bản đặc tả đã hợp nhất, có giá trị pháp lý cao nhất cho toàn bộ hệ thống.
   - **KHÔNG mặc định đọc toàn bộ 3,000 dòng `PROJECT_RULES.md`** cho mỗi subagent/phiên làm việc thông thường.
2. **Quy trình nạp ngữ cảnh tối thiểu (Minimal Working Context):**
   - **Bước 1:** Bắt buộc đọc [.fitness-rules/PROJECT_CORE.md](.fitness-rules/PROJECT_CORE.md) để nắm toàn bộ Invariant bất biến cốt lõi.
   - **Bước 2:** Tra cứu [.fitness-rules/RULE_INDEX.md](.fitness-rules/RULE_INDEX.md) hoặc đọc chỉ định trong `TASK_PACKET` để nạp chính xác các module nghiệp vụ liên quan (Domain/Engineering/Frontend).
   - **Bước 3 (FE Phase):** Nếu thực hiện nhiệm vụ Frontend, đọc file Phase Context tương ứng trong `.fitness-sdd/context/` (ví dụ: `FE3_CONTEXT.md`).
3. **Khi nào phải đối chiếu Canonical Document (PROJECT_RULES.md)?**
   - Chỉ khi: (1) Phát hiện xung đột giữa các module hoặc giữa code và tài liệu; (2) Có sự mơ hồ nghiệp vụ (ambiguity); (3) Module/version mismatch; (4) Controller/Planner yêu cầu thẩm định canonical cụ thể.
4. **Kỷ luật ngữ cảnh (Anti-Token-Waste):**
   - Cấm tự ý đọc tràn lan tất cả các module trong `.fitness-rules/` "cho chắc chắn".
   - Cấm tự ý thay đổi bất kỳ Business Rule nào (Q01–Q13, RULE GYM 01–25, RULE CODE 01–20).
   - Khi gặp điểm chưa được quyết định có ảnh hưởng nghiệp vụ, hỏi chủ dự án trước khi triển khai. Không tự mở rộng scope.

## Quy trình thực hiện

1. Đọc `PROJECT_CORE.md`, tra cứu module tương ứng qua `RULE_INDEX.md`, và đọc file task context/packet.
2. Trước chức năng lớn, xác định Actor, Use Case, Business Rule, Database, API, Authorization, Validation, Failure Case, Concurrency, Idempotency, Transaction và Test.
3. Chỉ triển khai phạm vi được yêu cầu, tuân thủ naming và comment chuẩn (tiếng Việt không dấu camelCase cho FE, snake_case cho DB).
4. Ưu tiên tính đúng của dữ liệu, phân quyền theo tài nguyên (RULE CODE 17), validation, transaction và kiểm thử.
5. Báo cáo đúng những thay đổi và kiểm thử thực sự đã thực hiện; không tuyên bố hoàn thành dựa riêng vào UI.
6. Khi hoàn thành một module, dùng format báo cáo ở PHẦN XXI của PROJECT_RULES.md (hoặc `engineering/testing-definition-of-done.md`).

## Nhắc lại quy tắc dễ nhầm

- Payment chỉ ghi nhận quyền sở hữu. Lần dùng quyền lợi trả phí đầu tiên hợp lệ kích hoạt kỳ đầu bằng một đồng hồ chung cho Gym/AI/PT.
- Các kỳ mua nối tiếp giữ snapshot riêng, không dùng quyền kỳ sau trước lượt; lượt PT không carry-over.
- PT xác nhận buổi hoàn thành mới trừ lượt; chat không tự trừ lượt.
- AI/PT đề xuất thay đổi kế hoạch phải theo workflow xác nhận tương ứng (Proposal TTL 24h); Workout Session hoàn thành là bất biến (RULE GYM 13).
- Workout Tracking là quyền cơ bản của Member (Q08), không phụ thuộc có gói hay hết hạn Membership.
- Mọi thắc mắc chi tiết tra cứu qua `.fitness-rules/RULE_INDEX.md`.
