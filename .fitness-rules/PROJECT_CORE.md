# PROJECT_CORE.md — Smart Fitness Platform Core Invariants

> **Layered revision:** 2026-09-10 | **Trạng thái:** Bắt buộc cho mọi Agent/Subagent  
> **Canonical Source:** [PROJECT_RULES.md](../PROJECT_RULES.md) (Đối chiếu khi có xung đột)

---

## 1. Thẩm quyền & Nguyên tắc Tài liệu (Source of Truth Policy)

1. **PROJECT_RULES.md là nguồn chân lý tối cao (Canonical Source of Truth):**
   - Tài liệu này (`PROJECT_CORE.md`) cùng hệ thống module trong `.fitness-rules/` là bản biểu diễn ngữ cảnh có cấu trúc nhằm tối ưu token.
   - Khi có bất kỳ sự mơ hồ (ambiguity), mâu thuẫn giữa các module, hoặc xung đột phiên bản, **PROJECT_RULES.md có quyền ưu tiên cao nhất**.
2. **Quy trình nạp ngữ cảnh phân tầng (Layered Context Protocol):**
   - **Mọi Subagent** khi bắt đầu phiên làm việc **BẮT BUỘC** đọc `PROJECT_CORE.md`.
   - Tra cứu [RULE_INDEX.md](RULE_INDEX.md) hoặc đọc trực tiếp chỉ định trong `TASK_PACKET` để nạp các module domain liên quan.
   - **CẤM** tự ý đọc toàn bộ `PROJECT_RULES.md` hay đọc tràn lan tất cả các module nếu không có chỉ định hoặc không xảy ra điều kiện leo thang (escalation). Một workflow/role contract được chỉ định rõ có thể yêu cầu đọc toàn bộ canonical ngay cả khi chưa thấy xung đột.
3. **Tuyệt đối không thay đổi Business Rule & Không tự mở rộng Scope:**
   - Cấm tự ý sửa đổi, nới lỏng hoặc loại bỏ bất kỳ Business Rule, điều kiện biên, exception nào đã quy định trong `Q01–Q13`, `RULE GYM 01–25` và `RULE CODE 01–20`.
   - Không tự ý thêm màn hình, thêm API, thêm cột database hoặc sửa flow nghiệp vụ ngoài phạm vi task packet được giao.

---

## 2. Thẩm quyền Hệ thống & Phân quyền (Authority & Authorization)

1. **Backend là thẩm quyền duy nhất (Backend is the Authority):**
   - **RULE CODE 13:** Tuyệt đối không tin tưởng dữ liệu từ Frontend. Mọi giá trị tính toán (giá gói, thời hạn, số buổi, quota, trạng thái kích hoạt, timestamp) phải được tính và xác thực độc lập tại Backend.
   - **RULE CODE 14:** Tuyệt đối không tin tưởng dữ liệu từ LLM (AI). Output của AI chỉ là đề xuất (Proposal) dưới dạng Structured Output (JSON) và bắt buộc phải đi qua Rule Engine của Backend thẩm định trước khi ghi/áp dụng.
2. **Phân quyền theo tài nguyên (Resource-Scope Authorization):**
   - **RULE CODE 17:** Không xác thực dựa thuần túy trên Role. Mọi thao tác truy xuất/chỉnh sửa dữ liệu cá nhân phải kiểm tra quyền sở hữu tài nguyên:
      - Member chỉ truy cập tài nguyên của chính mình (`user_id == current_user_id`).
      - PT chỉ truy cập học viên đang có phân công hiệu lực theo khoảng thời gian `[ngay_bat_dau, ngay_ket_thuc)` (NULL end = open interval, tối đa 0..1 PT hiệu lực tại một thời điểm, không overlap).
      - Lỗi sai quyền trả về HTTP `403 Forbidden` (hoặc `404 Not Found` nếu cần ẩn sự tồn tại của resource), chưa đăng nhập trả về HTTP `401 Unauthorized`.

---

## 3. Tính Toàn vẹn Dữ liệu & Bất biến Lịch sử (Data & History Integrity)

1. **Buổi tập đã hoàn thành là BẤT BIẾN (Immutable Workout Sessions):**
   - **RULE GYM 13:** Phiên tập (`phien_tap`) ở trạng thái `HOAN_THANH` là **BẤT BIẾN**. Tuyệt đối không được sửa đổi, cập nhật số set, rep, khối lượng tạ, hoặc xóa bài tập sau khi phiên đã hoàn tất.
   - **RULE GYM 14 & 15:** Cả AI và PT đều không được phép sửa buổi tập đã hoàn thành của học viên. PT chỉ được ghi chú nhận xét (`ghi_chu_huan_luyen`).
2. **Bảo toàn lịch sử & Audit Trail (Q12):**
   - Trong MVP không hard-delete lịch sử quan trọng: đơn mua gói, lần/sự kiện thanh toán, chuỗi/kỳ/snapshot Membership, sử dụng quyền lợi, check-in, phân công/lượt PT, Plan/Version/Schedule, phiên/bài/hiệp tập, tin nhắn chat, request AI/provider/Proposal và audit nhật ký hệ thống.
   - Giữ cả lịch `HUY`/`DA_THAY_THE`, phiên `HUY` và phân công đã kết thúc qua khoảng thời gian `[ngay_bat_dau, ngay_ket_thuc)`; giữ identity, FK và audit của history. Không gọi mọi usage-ledger là append-only/immutable: các dòng nghiệp vụ được phép chuyển trạng thái canonical trong transaction, như AI quota `GIU_CHO` → `DA_TINH`/`DA_TRA` hoặc request `KHONG_AP_DUNG`. Catalog/tài khoản khóa hoặc ngừng sử dụng không cascade xóa lịch sử.
3. **Quyền tập luyện là quyền cơ bản (Q08):**
   - Q08 cho Member xem Plan/Schedule do mình sở hữu và Start, Save Set, Complete từ một lịch hiện có, hợp lệ và thuộc đúng Plan/Version của mình. Không được dùng Q08 để tạo schedule trực tiếp, khởi tạo phiên không có lịch, hoặc tập tự do.
   - Member không có gói hoặc Membership đã hết hạn (`HET_HAN`) **vẫn được phép** thực hiện các thao tác Q08 ở trên và xem lịch sử. Free Workout ngoài lịch là FUTURE DEVELOPMENT.

---

## 4. Bất biến Membership & Thanh toán (Membership & Payment Invariants)

1. **Tách biệt Thanh toán và Kích hoạt (Payment != Activation):**
   - **RULE GYM 01 & 17:** Thanh toán thành công chỉ ghi nhận quyền sở hữu kỳ hạn. Khi thanh toán, kỳ đầu ở trạng thái `trang_thai = CHO_KICH_HOAT`, `ngay_bat_dau = null`, `ngay_ket_thuc = null`.
   - **RULE GYM 02 & RULE GYM 08:** Lần sử dụng quyền lợi trả phí hợp lệ đầu tiên (quét QR vào phòng tập, gửi request AI được chấp nhận, xác nhận buổi tập PT hoàn thành, hoặc tin nhắn Member hợp lệ gửi tới PT) mới kích hoạt kỳ đầu tiên. Mốc kích hoạt được Backend ghi nhận chính xác: `ky_han_hoi_vien.ngay_bat_dau = su_dung_quyen_loi.chap_nhan_luc`, không dùng generic `NOW()` hay client timestamp. Hành động PT chủ động gửi tin, đọc lịch sử, subscribe hoặc mở màn hình chat TUYỆT ĐỐI KHÔNG kích hoạt kỳ hạn.
2. **Một đồng hồ chung & Snapshot từng kỳ:**
   - **RULE GYM 21:** Một đồng hồ thời gian chung duy nhất cho cả quyền Gym, AI và PT trong cùng một kỳ hạn.
   - **RULE GYM 19 & Q01:** Mỗi lần mua/gia hạn tạo một record `ky_han_hoi_vien` riêng biệt kèm snapshot toàn bộ quyền lợi tại thời điểm mua.
   - **RULE GYM 22:** Quyền lợi áp dụng độc lập theo từng kỳ. Cấm dùng trước quyền lợi của kỳ tương lai khi đang ở kỳ hiện tại. Lượt PT không carry-over sang kỳ sau (RULE GYM 23).
3. **Tính toán thời hạn động:**
   - **RULE GYM 20:** Không dùng Cron job để giảm trừ trường `so_ngay_con_lai` mỗi ngày. Thời hạn còn lại được tính động bằng `ngay_ket_thuc - NOW()`.

4. **M061 — vòng đời Muscle Group:**
   - `nhom_co.trang_thai` chỉ nhận `HOAT_DONG` hoặc `NGUNG_SU_DUNG`, mặc định `HOAT_DONG`; GET gồm cả nhóm inactive và không có DELETE.
   - Không xóa/sửa pivot `bai_tap_nhom_co` hiện hữu khi nhóm inactive; giữ nguyên id, vai trò, `ngay_tao`, `ngay_cap_nhat`. Quan hệ mới chỉ nhận nhóm active.
   - PATCH Exercise có replacement `muscle_groups` phải gửi lại mọi quan hệ inactive đúng vai trò; bỏ trường này nghĩa là không cập nhật quan hệ. Thay đổi quan hệ inactive bị từ chối nguyên tử với `INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE`.
   - PATCH cùng trạng thái sau chuẩn hóa là no-op; chuyển trạng thái thật ghi đúng một audit `CAP_NHAT_TRANG_THAI_NHOM_CO` trong cùng transaction. Khóa và đọc lại hàng để tuyến tính hóa race status/relation.

---

## 5. Bất biến PT & Workflow Đề xuất (PT & Proposal Invariants)

1. **Trừ lượt PT và Phân công theo thời gian:**
   - **Q06:** PT thực hiện xác nhận buổi tập hợp lệ khi đúng kỳ của buổi còn hiệu lực và còn quota (hoặc kỳ đầu được phép kích hoạt theo rule chung); trừ đúng 1 lượt. Member tự gửi request xác nhận bị từ chối. Chat trao đổi không tự động trừ lượt buổi PT (RULE GYM 09).
   - **Q04:** Phân công PT quản lý theo khoảng thời gian `[ngay_bat_dau, ngay_ket_thuc)` (NULL end = open interval). Một Member chỉ có tối đa 0..1 PT hiệu lực tại bất kỳ thời điểm nào; cấm overlap giữa bất kỳ PT nào cùng Member. Đổi PT phải đóng khoảng cũ trước hoặc đúng lúc mở khoảng mới trong cùng transaction + audit.
   - **Q05:** Member luôn được giữ và đọc lại lịch sử chat cũ của mình dù đổi PT hay hết hạn gói. PT cũ mất resource scope sau khi phân công kết thúc, tuyệt đối không được tiếp tục truy cập hay gửi tin qua scope cũ.
2. **Quy trình Đề xuất Thay đổi Kế hoạch (Proposal Workflow):**
   - **RULE GYM 24 & Section 40–43:** Khi trợ lý AI hoặc huấn luyện viên đề xuất thay đổi kế hoạch tập luyện cho Member, bắt buộc phải tạo `de_xuat_ke_hoach_tap` ở trạng thái `CHO_XAC_NHAN`. Giá trị nguồn lưu trữ là `TRO_LY` hoặc `HUAN_LUYEN_VIEN`; nhãn AI/PT chỉ là nhãn hiển thị.
   - Kế hoạch chỉ được cập nhật khi chính **Member bấm xác nhận duyệt proposal**. Proposal hết hạn sau 24 giờ (Q09).

---

## 6. Kỹ thuật & Chuẩn mực Thực thi (Engineering & Execution Invariants)

1. **Transaction & Idempotency:**
   - **RULE CODE 15:** Phải xem xét Database Transaction cho danh sách nghiệp vụ canonical cụ thể: xử lý Webhook; cấp Membership; gia hạn; kích hoạt từ AI/PT/check-in đầu tiên; chuyển kỳ quyền lợi; ghi nhận buổi PT và trừ lượt; áp dụng PT Proposal; QR Redeem; Complete Workout; Apply AI Proposal.
   - **RULE CODE 16:** Bắt buộc xem xét cơ chế chống trùng lặp (Idempotency Key / Unique Constraint / Atomic State Check / Client Message ID) cho đầy đủ 10 nghiệp vụ canonical: (1) payOS Webhook; (2) Membership Renewal; (3) Membership Activation đồng thời AI/PT/QR; (4) Ghi nhận cùng một buổi PT; (5) PT Proposal Apply; (6) QR Redeem; (7) Workout Set retry; (8) Complete Workout; (9) AI Proposal Apply; (10) Chat client retry (chi tiết đầy đủ xem tại `security-integrity.md`).

2. **Quy ước Đặt tên (Naming Standards):**
   - **Database (RULE CODE 01–04):** Tiếng Việt không dấu, snake_case, không viết tắt, tuân thủ danh mục 52 bảng chuẩn trong `TU_DIEN_DU_LIEU.md`.
   - **Frontend (RULE CODE 05–08):** Tên hàm nghiệp vụ và event handler dùng camelCase tiếng Việt không dấu (`layDanhSach`, `xuLyXacNhan`). Tên API Framework giữ nguyên (`ref`, `computed`, `v-model`).
3. **Definition of Done & Bằng chứng Kiểm thử (DoD & Evidence):**
   - **RULE CODE 19 & 20:** Không đánh đổi chất lượng để thêm màn hình. Mọi thay đổi phải có test kiểm chứng (unit/feature test), không tuyên bố hoàn thành chỉ dựa vào giao diện hiển thị.
4. **Quy tắc Chống lãng phí Token (Anti-Token-Waste Protocol):**
   - Không đọc lặp lại các file đã nạp trong cùng một context session.
   - Không spawn subagent riêng lẻ cho từng mục checklist nhỏ; tuân thủ mô hình Task tổng hợp của từng Phase (ví dụ: `FE3-ALL`).
   - Khi cần thêm thông tin, chỉ nạp đúng file module cần thiết được định tuyến qua [RULE_INDEX.md](RULE_INDEX.md).
