# AGENTS.md — Smart Fitness Platform

## Bắt buộc đọc trước khi làm việc

Trước mỗi tác vụ phân tích, thiết kế Database/API, viết hoặc sửa code, test, refactor hay code review trong dự án này, phải đọc **toàn bộ [PROJECT_RULES.md](PROJECT_RULES.md)** ở thư mục gốc.

- Không chỉ đọc tóm tắt, tìm kiếm từ khóa hoặc dựa vào hội thoại cũ.
- Nếu đầu ra bị cắt, đọc tiếp các phần còn thiếu trước khi triển khai.
- Nếu tệp thiếu hoặc không đọc được, báo rõ và dừng phần triển khai phụ thuộc vào quy tắc.
- PROJECT_RULES.md là bản đặc tả đã hợp nhất, gồm quyết định Membership/PT mới nhất. Không áp dụng lại quy tắc lỗi thời từ bản prompt cũ.
- Khi chủ dự án đưa yêu cầu mới thay đổi nghiệp vụ, cập nhật nhất quán các mục, ví dụ và test liên quan; không để hai quy tắc mâu thuẫn cùng có hiệu lực.
- Khi gặp điểm chưa được quyết định có ảnh hưởng nghiệp vụ, hỏi chủ dự án trước khi triển khai phần đó. Không tự mở rộng scope.

## Quy trình thực hiện

1. Đọc PROJECT_RULES.md và các hướng dẫn áp dụng trong phạm vi tệp sẽ sửa.
2. Trước chức năng lớn, xác định Actor, Use Case, Business Rule, Database, API, Authorization, Validation, Failure Case, Concurrency, Idempotency, Transaction và Test.
3. Chỉ triển khai phạm vi được yêu cầu, tuân thủ naming và comment trong PROJECT_RULES.md.
4. Ưu tiên tính đúng của dữ liệu, phân quyền, validation, transaction và kiểm thử.
5. Báo cáo đúng những thay đổi và kiểm thử thực sự đã thực hiện; không tuyên bố hoàn thành dựa riêng vào UI.
6. Khi hoàn thành một module, dùng format báo cáo ở PHẦN XXI của PROJECT_RULES.md.

## Nhắc lại quy tắc dễ nhầm

- Payment chỉ ghi nhận quyền sở hữu. Lần dùng quyền lợi trả phí đầu tiên hợp lệ kích hoạt kỳ đầu bằng một đồng hồ chung cho Gym/AI/PT.
- Các kỳ mua nối tiếp giữ snapshot riêng, không dùng quyền kỳ sau trước lượt; lượt PT không carry-over.
- PT xác nhận buổi hoàn thành mới trừ lượt; chat không tự trừ lượt.
- AI/PT đề xuất thay đổi kế hoạch phải theo workflow xác nhận tương ứng; Workout Session hoàn thành là bất biến.
- Bản nhắc này không thay thế việc đọc toàn bộ PROJECT_RULES.md.
