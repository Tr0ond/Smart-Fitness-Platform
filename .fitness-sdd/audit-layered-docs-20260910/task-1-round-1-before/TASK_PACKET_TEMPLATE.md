# Task Packet Protocol Template

> **Location:** `.fitness-sdd/context/TASK_PACKET_TEMPLATE.md`  
> **Mục đích:** Khung truyền giao nhiệm vụ cho Subagent mà không làm phình ngữ cảnh lịch sử (Zero History Bloat).  
> **Quy tắc:** Subagent chỉ nạp các file được chỉ định trong mục `REQUIRED_CONTEXT`. Tuyệt đối không đọc toàn bộ canonical `PROJECT_RULES.md` hay toàn bộ `VUE_WEB_IMPLEMENTATION_PLAN_V2.md`.

---

```markdown
### TASK_PACKET: [TASK_ID] (e.g. FE3-ALL)

**ROLE:** [Implementation Writer | Reviewer | Repair Agent]  
**WORKFLOW_ROLE:** [Initial Planner | Implementer/Fixer | Task Reviewer | Final Reviewer]  
**MODEL:** [controller-supplied exact model id]  
**REASONING_EFFORT:** [controller-supplied supported effort]  
**MODEL_SELECTION_RULE:** Controller supplies the model and reasoning effort; the recipient must not choose or silently substitute either value.  
**OBJECTIVE:** [Tóm tắt mục tiêu duy nhất của task trong 1–2 câu]

---

#### 1. TÌNH TRẠNG HIỆN TẠI (STATE & CHECKPOINT)
- **CURRENT_CHECKPOINT:** `docs/VUE_WEB_COMPLETION_CHECKPOINT.md` (Ghi nhận mốc gần nhất)
- **CURRENT_STATE:** [Mô tả ngắn gọn các file đã có / blocker nếu có]

#### 2. PHẠM VI CHO PHÉP & CẤM (SCOPE BOUNDARIES)
- **ALLOWED_SCOPE:** 
  - [Chỉ định chính xác màn hình / API / Component được phép tạo hoặc sửa]
- **PROHIBITED_SCOPE:** 
  - CẤM sửa mã nguồn Backend nếu làm task Frontend (hoặc ngược lại).
  - CẤM thay đổi bất kỳ Business Rule hay điều kiện kiểm thử nào.
  - CẤM tự ý tạo thêm màn hình ngoài scope.
  - CẤM thêm UI framework (Tailwind, Shadcn-vue) hoặc bật dark mode trong MVP.
- **ALLOWED_FILES:** [Liệt kê tuyệt đối mọi file được phép tạo/sửa; report path là file ghi nhận duy nhất ngoài scope nếu controller cho phép]
- **PROHIBITED_FILES:** `PROJECT_RULES.md`, Vue plan, AGENTS.md, baseline artifacts, code hoặc tài liệu ngoài ALLOWED_FILES.
- **AUTHORITIES:** `E:/Fitness/PROJECT_RULES.md`, `E:/Fitness/docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md`, applicable `AGENTS.md`, và các `.fitness-rules/*` được chỉ định.
- **BASELINE_AND_PREEXISTING:** `E:/Fitness/.fitness-sdd/<task-slug>/baseline-status.txt`, `baseline-tree/`, `task-<N>-round-<R>-before/`; preserve all listed pre-existing changes byte-for-byte outside declared hunks.

#### 3. TÀI LIỆU CONTEXT BẮT BUỘC (REQUIRED_CONTEXT)
*(Mặc định nạp context phân tầng tối thiểu; không đọc tràn lan. Nếu role contract, controller, xung đột hoặc escalation yêu cầu, phải reread đúng phần canonical được chỉ định.)*
- `.fitness-rules/PROJECT_CORE.md`
- [File Phase Context, ví dụ: `.fitness-sdd/context/FEx_CONTEXT.md`]
- [File module domain liên quan trực tiếp được RULE_INDEX.md chỉ định]
- `E:/Fitness/PROJECT_RULES.md` khi controller/role contract/escalation yêu cầu canonical reread; tiếp tục theo chunk đến EOF.
- `E:/Fitness/AGENTS.md` và mọi `AGENTS.md` lồng áp dụng cho file trong scope; đọc đủ đến EOF.

#### 4. QUY TẮC CẦN TUÂN THỦ (CANONICAL_RULE_IDS)
- **RULE GYM:** [e.g. RULE GYM 11, 13]
- **RULE CODE:** [e.g. RULE CODE 05, 06, 07, 08, 20]
- **Q DECISIONS:** [e.g. Q07A, Q11]

#### 5. MÃ NGUỒN LIÊN QUAN (RELEVANT_SOURCE)
- Đường dẫn file cần sửa: `FE/src/...`
- File API Contract: `docs/BACKEND_API_CONTRACT.md` (mục liên quan)
- **Quy tắc kiểm tra mã nguồn thực tế (Actual Source Wins):** Trước khi tạo bất kỳ file nào, bắt buộc kiểm tra mã nguồn thực tế trong `FE/src/`. Mã nguồn thực tế và file inventory chuẩn trong plan thắng tài liệu phác thảo; tuyệt đối không tạo file trùng lặp chỉ vì tên file khác nhau.

#### 5A. DEPENDENCIES & PHASE ENTRY GATE
- **DEPENDENCIES:** [Task/contract/test/deployment prerequisites; specify exact PASS evidence]
- **ENTRY_GATE:** [Controller-supplied evidence required before writer dispatch; FE3-ALL through FE9-ALL are one aggregate task each, never READY-subset writers]
- FE5-ALL waits for BLOCKER-03/04/05 contracts and Backend tests; FE7-ALL waits for FE5-ALL PASS plus Reverb/BE-FOLLOWUP-04 deployment readiness; FE8-ALL waits for BLOCKER-06/07 contracts and Backend tests.
- FE0–FE2 retain only the historical small-task codes listed in their context files; do not create a phase-wide aggregate task code for them.
- **EXPECTED_REPORT:** `E:/Fitness/.fitness-sdd/<task-slug>/task-<N>-report.md` using the canonical 15-section completion report and exact command/evidence results.

#### 6. TIÊU CHÍ NGHIỆM THU & TEST (ACCEPTANCE & TESTS)
- [ ] Giao diện hiển thị đúng Visual UI System MVP (Light staff UI, CSS variables, không UI framework, không dark mode trong MVP) và Responsive.
- [ ] Xử lý đầy đủ các trạng thái truy vấn: Loading, Data, Empty, Error retry.
- [ ] Kiểm thử tự động thành công: `npm run test`; evidence phải nêu pass/fail và mọi skip có lý do được phê duyệt, không dùng mock che lỗi.
- [ ] Kiểm tra lint sạch sẽ: `npm run lint` không có lỗi.
- [ ] Kiểm tra build thành công: `npm run build` không có lỗi.

#### 7. ĐỊNH DẠNG BÁO CÁO HOÀN THÀNH (CANONICAL 15-SECTION REPORT)
Khi hoàn thành nhiệm vụ, ghi báo cáo tại EXPECTED_REPORT và phản hồi tóm tắt. Báo cáo phải có đúng 15 mục sau, với bằng chứng cuối cùng thực sự đã chạy:

```markdown
### Báo cáo Hoàn thành Nhiệm vụ: [TASK_ID]
1. Mục tiêu module
2. Phạm vi và actor/use case
3. File đã tạo
4. File đã sửa
5. Hàm/component/module liên quan
6. Database/API/contract impact
7. Business rules và Q decisions
8. Authorization/resource scope
9. Validation và failure cases
10. Transaction/concurrency/idempotency/audit
11. Error/status mapping
12. Tests/checks đã chạy và kết quả chính xác
13. Evidence cuối trạng thái và baseline/path check
14. Chưa hoàn tất hoặc blocker
15. Rủi ro còn lại
```

#### 8. ĐIỀU KIỆN LEO THANG (ESCALATE WHEN)
- Phát hiện xung đột giữa hợp đồng API Backend và yêu cầu giao diện.
- Nghiệp vụ không rõ ràng có thể ảnh hưởng đến logic dữ liệu.
- Phát hiện thiếu rule mà RULE_INDEX.md không bao quát được.
*(Khi xảy ra escalation: Tra cứu đúng mục liên quan trong PROJECT_RULES.md hoặc báo Controller dừng)*

**FULL_CANONICAL_REREAD:** CONDITIONAL (Mặc định minimal layered context; đặt YES khi controller/role contract yêu cầu, khi escalation, xung đột authority, hoặc ambiguity nghiệp vụ xuất hiện; đọc đúng file/chunk đến EOF).
```
