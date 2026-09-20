# Domain: AI Fitness Assistant & Proposal Workflow

> **Module Path:** `.fitness-rules/domains/ai-proposal.md`  
> **Canonical Reference:** [PROJECT_RULES.md](../../PROJECT_RULES.md) (Phần X: 34–43.1; RULE GYM 14; RULE CODE 14; Q03, Q09)

---

## 1. Triết lý AI: Controlled & Hybrid AI System

### Section 34 – AI Không phải Chatbot Đơn giản
- Hệ thống áp dụng kiến trúc **Hybrid Rule Engine + LLM**:
  $$\text{User} \rightarrow \text{Laravel} \rightarrow \text{Normalize Request} \rightarrow \text{Rule Engine} \rightarrow \text{Candidate Selection} \rightarrow \text{LLM} \rightarrow \text{Structured Output} \rightarrow \text{Validation} \rightarrow \text{Proposal} \rightarrow \text{Preview} \rightarrow \text{User Confirm} \rightarrow \text{Revalidate} \rightarrow \text{Transaction} \rightarrow \text{Apply}$$
- **Phạm vi AI (Section 35):** Chỉ tập trung vào Workout Planning, Workout Adjustment, Exercise Replacement, Workout/Exercise Explanation. Tuyệt đối không mở rộng sang chẩn đoán bệnh, điều trị, chấn thương, thuốc, supplement hay dinh dưỡng điều trị.

### Cấu trúc CSDL Trợ lý AI (52 Bảng Chuẩn)
- Hội thoại trợ lý: `hoi_thoai_tro_ly`
- Tin nhắn trợ lý: `tin_nhan_tro_ly`
- Yêu cầu trợ lý: `yeu_cau_tro_ly` (đại diện cho một lượt yêu cầu nghiệp vụ của người dùng)
- Lần gọi mô hình: `lan_goi_mo_hinh` (lưu vết kỹ thuật từng lần gọi API LLM thực tế, token, latency)
- Ứng viên đề xuất: `bai_tap_ung_vien`, `giao_an_ung_vien`
- Đề xuất kế hoạch: `de_xuat_ke_hoach_tap` với `nguon_de_xuat = TRO_LY`. AI là nhãn hiển thị/domain label; không lưu source `AI`.

### Section 36 & 37 – Phân định Trách nhiệm giữa Rule Engine & LLM
- **Rule Engine chịu trách nhiệm (Section 36):**
  - Xử lý: số ngày tập, ngày rảnh, Equipment, Exercise hợp lệ, Exercise tồn tại, Exercise Candidate, Template Candidate, quyền sử dụng, lịch trùng, ràng buộc nghiệp vụ, giới hạn dữ liệu, kiểm tra ID, quyền truy cập. Những gì code kiểm tra chắc chắn được thì tuyệt đối không giao LLM quyết định.
- **LLM chịu trách nhiệm (Section 37):**
  - Hiểu ngôn ngữ tự nhiên, intent detection, trích xuất yêu cầu, hỏi thêm dữ liệu thiếu, giải thích, chọn giữa các phương án hợp lệ, tạo phản hồi tự nhiên. Backend sau đó kiểm tra và chuẩn hóa.

---

## 2. Ràng buộc Kỹ thuật AI (AI Technical Constraints)

### Section 38 – Bắt buộc Structured Output (JSON Schema)
- Khi AI tạo dữ liệu có thể áp dụng, **BẮT BUỘC dùng Structured Output**.
- Tuyệt đối không parse ngược văn bản markdown/text tự do để lưu Workout Plan.
- Cấu trúc JSON minh họa dùng tên dữ liệu theo từ điển: `loai_thay_doi`, mảng `ngay_trong_ke_hoach` và `bai_tap_trong_ke_hoach` (với `bai_tap_id`, `so_hiep_muc_tieu`, `so_lan_lap_toi_thieu`, `so_lan_lap_toi_da`, `thoi_gian_nghi_giay`).

### RULE CODE 14 & Section 39 – AI Validation (Không Tin Dữ liệu LLM)
- Tuyệt đối không bao giờ lấy thẳng output của LLM để cập nhật trực tiếp vào database.
- Backend Laravel bắt buộc phải kiểm tra:
  - JSON schema.
  - Exercise ID tồn tại và nằm trong Candidate.
  - Equipment phù hợp với chi nhánh/học viên.
  - Template hợp lệ.
  - Set hợp lệ, Reps hợp lệ (`so_lan_lap_toi_thieu <= so_lan_lap_toi_da`), Rest hợp lệ.
  - Ngày hợp lệ, không trùng lịch.
  - Quyền sử dụng, resource ownership, Plan Version, Proposal status.

### RULE GYM 14 – AI Không được sửa Buổi tập đã Hoàn thành
- Dữ liệu lịch sử buổi tập (`phien_tap.trang_thai = HOAN_THANH`) là bất biến.
- AI chỉ đọc dữ liệu quá khứ để làm ngữ cảnh suy luận, tuyệt đối cấm chỉnh sửa bất kỳ bài tập, set tập nào đã diễn ra.

---

## 3. Quota & Hạn ngạch Sử dụng (Q03)

### Section 34.1 & Q03 – Quy tắc Quota Request AI
- **Một request nghiệp vụ AI hợp lệ = một lượt**, không tính từng message hoặc từng lần retry provider.
- **Quy trình xử lý Quota:**
  1. Backend xác minh quyền AI và điều kiện request.
  2. Dưới khóa Member/chuỗi/kỳ/request: revalidate, giữ một lượt quota (`GIU_CHO`), lưu `yeu_cau_tro_ly`, `su_dung_quyen_loi` và domain record; nếu là lần dùng trả phí đầu tiên thì kích hoạt kỳ cùng transaction.
  3. Sau commit mới gọi provider LLM.
  4. Kết quả nghiệp vụ hợp lệ: chuyển `GIU_CHO` sang `DA_TINH`, giảm giữ chỗ và tăng đã dùng đúng một.
  5. Lỗi kỹ thuật kết thúc request: trả lượt đúng một lần (`DA_TRA`), giảm counter giữ chỗ hoặc đã dùng tương ứng; không reset/lùi đồng hồ Membership đã kích hoạt.
- Request thiếu điều kiện/bị từ chối trước chấp nhận dịch vụ: không giữ/trừ quota, không usage và không activation (`KHONG_AP_DUNG`). Hỏi bổ sung trước chấp nhận không tính lượt; các message/provider retry thuộc cùng request không tạo lượt mới. Hoàn/trả quota phải transaction-safe/idempotent, không âm counter hoặc trả hai lần.

---

## 4. Quy trình Đề xuất & Áp dụng (AI Proposal Workflow)

### Section 40 & 42 – Trợ lý chỉ tạo Proposal, cấm tự động Apply
- AI không có quyền: tự lưu Plan chính thức, tự sửa Plan, tự xóa lịch, tự sửa Membership, tự xác nhận Payment, tự thay đổi Role, hay tự chỉnh Workout Session hoàn thành.
- Mọi điều chỉnh từ trợ lý chỉ được lưu vào bảng `de_xuat_ke_hoach_tap` (`nguon_de_xuat = TRO_LY`) ở trạng thái chờ xác nhận (`CHO_XAC_NHAN`). Provider LLM là lựa chọn generic theo cấu hình Backend; module không khóa vào một nhà cung cấp.
- **Hội viên là người quyết định cuối cùng:** Trên giao diện có Proposal Preview (so sánh Diff). Chỉ sau khi Member bấm xác nhận, Backend mới Revalidate và chạy Transaction để Apply.

### Q09 & Section 43.1 – Thời hạn Hiệu lực của Proposal (TTL 24 Giờ)
- TTL mặc định **24 giờ** cho cả AI và PT Proposal, cấu hình tập trung.
- `de_xuat_ke_hoach_tap.het_han_luc` lưu mốc cụ thể từ lúc tạo; Preview/retry không kéo dài hạn.
- Xác nhận tại hoặc sau hạn không Apply; vẫn giữ Proposal làm lịch sử audit.

### Section 43 – Chống Áp dụng Hai Lần (Idempotent Proposal Application)
- Nếu Member bấm xác nhận hai lần do mạng chậm: Chỉ được Apply một lần.
- Cần áp dụng: Idempotency Key, Proposal status check, và Database transaction để đảm bảo tính nguyên tử.

### Confirm-time revalidation và conflict

Khi Member xác nhận, Backend phải khóa Proposal và các bản ghi ngữ cảnh liên quan rồi đọc lại: ownership của Member, source và actor tạo Proposal, base Plan/Version, context và future schedule, TTL 24 giờ, trạng thái hiện tại và các Proposal/Plan thay đổi cạnh tranh. Không tin payload Preview hoặc dữ liệu client đã giữ lâu.

- Nếu TTL hết hoặc trạng thái không còn CHO_XAC_NHAN, giữ Proposal làm lịch sử và không Apply.
- Nếu base version, ownership, source assignment hoặc future schedule không còn khớp, chuyển Proposal sang trạng thái terminal conflict theo contract (XUNG_DOT hoặc trạng thái kết thúc phù hợp), không ghi đè lịch sử và yêu cầu Preview lại.
- Nếu mọi kiểm tra hợp lệ, một transaction tạo version/lịch chính thức, cập nhật Proposal, ghi audit và bảo toàn các row history. Idempotency key và atomic status check làm các retry trả về cùng kết quả, không tạo version thứ hai.
