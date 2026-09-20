# Engineering: Testing Rules & Definition of Done (DoD)

> **Module Path:** `.fitness-rules/engineering/testing-definition-of-done.md`  
> **Canonical Reference:** [PROJECT_RULES.md](../../PROJECT_RULES.md) (Phần XVII: 48–54.2; Phần XVIII: RULE CODE 20; Phần XXI: Báo cáo hoàn thành)

---

## 1. Định nghĩa Hoàn thành (Definition of Done — RULE CODE 20)

Một task/feature hoặc Phase chỉ được công nhận là hoàn thành (**DONE**) khi thỏa mãn đồng thời 5 tiêu chí bắt buộc:

1. **Code Quality:**
   - Mã nguồn tuân thủ đầy đủ chuẩn đặt tên tiếng Việt không dấu (RULE CODE 01–08).
   - Không có biến thừa, không có console log rác, có Docblock mô tả nghiệp vụ (RULE CODE 09–12).
   - Frontend build sạch sẽ (`npm run build`), kiểm tra lint không có lỗi (`npm run lint`); mã nguồn JavaScript Vue 3 Composition API sạch, không có lỗi cú pháp.
2. **Database Integrity:**
   - CSDL tuân thủ 52 bảng chuẩn, kiểu dữ liệu đúng từ điển, khóa ngoại rõ nghĩa.
   - Bắt buộc xem xét Database Transaction cho đầy đủ danh mục nghiệp vụ canonical (RULE CODE 15); không quy đổi thành "mọi lệnh ghi từ 2 bảng trở lên".
   - Không hard-delete dữ liệu lịch sử quan trọng (Q12).
3. **Automated Testing:**
   - Toàn bộ automated tests liên quan đến task (Unit Test, Feature Test, Component Test) chạy vượt qua 100% (**PASS**).
   - Không được dùng mock giả tạo để che giấu lỗi thực tế của API hoặc validation logic.
4. **Security & Resource Authorization:**
   - Đã kiểm tra phân quyền tài nguyên: Người dùng không thể truy cập chéo dữ liệu của người khác bằng cách thay đổi ID trên URL (RULE CODE 17).
   - Dữ liệu đầu vào từ Client và LLM được validate nghiêm ngặt (RULE CODE 13, 14).
5. **Documentation & Verification Evidence:**
   - Có biên bản báo cáo hoàn thành rõ ràng (theo mẫu Phần XXI).
   - Báo cáo trung thực những gì đã thực sự kiểm thử; tuyệt đối không tuyên bố hoàn thành chỉ dựa trên giao diện UI hiển thị.

---

## 2. Danh mục 9 Nhóm Test Bắt buộc (PHẦN XVII: Test Rules)

### 48. Authentication & Authorization Test
- Đăng nhập sai mật khẩu $\rightarrow$ Trả về 401 Unauthorized.
- Token hết hạn hoặc không truyền token $\rightarrow$ Trả về 401.
- Member cố tình gọi API của Admin hoặc PT $\rightarrow$ Trả về 403 Forbidden.
- Member A truy cập một resource Workout/Plan/Schedule không thuộc mình (ví dụ route current-plan với ownership khác) $\rightarrow$ Trả về 403/404 (RULE CODE 17).

### 49. Payment Test (Q01, Q02, Q10)
- Tạo đơn mua gói chốt đúng giá và snapshot quyền lợi (Q01).
- Thanh toán thành công tạo kỳ hạn ở trạng thái `CHO_KICH_HOAT` (RULE GYM 01).
- Webhook payOS gửi lại lần 2 không tạo thêm kỳ hạn trùng lặp (RULE GYM 18).
- Payload webhook không xác thực (chữ ký số sai/không hợp lệ) bị từ chối ngay lập tức (reject) và lưu audit, không được coi là tiền hợp lệ; khoản thanh toán bất thường đã xác thực (sai số tiền, đơn hủy/hết hạn, không khớp đơn) chuyển trạng thái `CAN_DOI_SOAT` (Q10).

### 50. Membership Test (RULE GYM 01–10, 17–23)
- Lần đầu quét QR hoặc gọi AI kích hoạt kỳ hạn đầu tiên sang `DANG_HOAT_DONG` (RULE GYM 02).
- Mua thêm khi kỳ hiện tại đang hoạt động $\rightarrow$ Tạo kỳ nối tiếp bảo toàn thời gian còn dư (RULE GYM 04, 05).
- Không dùng trước quyền lợi của kỳ tương lai (RULE GYM 22).
- Không cộng dồn buổi PT sang kỳ sau khi kỳ cũ hết hạn (RULE GYM 23).

### 51. QR Access Test (Q09 & Concurrency)
- Mã QR còn hiệu lực theo mốc `het_han_luc` do server cấp (mặc định chính sách 90 giây) quét thành công, ghi nhận lịch sử check-in.
- Mã QR đã quá `het_han_luc` bị từ chối với lỗi "Mã QR đã hết hạn" (Q09).
- Quét lại cùng 1 mã QR đã sử dụng bị từ chối ngay lập tức (Idempotency).
- Hai quầy lễ tân quét cùng 1 mã QR tại cùng 1 giây: Chỉ đúng 1 quầy thành công, quầy còn lại báo lỗi.

### 52. Workout Test (RULE GYM 11–16; Q07A–C, Q08, Q11, Q12)
- Member không có gói hoặc gói hết hạn vẫn Start được buổi tập và log set bình thường (Q08).
- Phiên tập đã bấm hoàn thành (`HOAN_THANH`) cố tình gửi request sửa set/rep $\rightarrow$ Bị chặn 100% (RULE GYM 13).
- Member chỉ có tối đa 1 Plan `DANG_SU_DUNG` (Q07A).
- Lịch tập đã hủy (`HUY`) không thể Start lại trên phiên cũ (Q07C).
- Bài tập yêu cầu nhiều dụng cụ (Q11): Thiếu 1 dụng cụ không cho phép thêm vào lịch.

### 53. AI Assistant Test (Q03, Q09, Structured Output)
- AI trả về JSON đúng schema đã định nghĩa; Backend validate thành công.
- AI trả về ID bài tập không tồn tại $\rightarrow$ Rule Engine bắt lỗi và từ chối lưu.
- Proposal AI hết hạn sau 24 giờ không cho phép Apply (Q09).
- Bấm Apply proposal 2 lần liên tiếp không tạo 2 kế hoạch active (Section 43).
- Quota AI trừ đúng 1 lượt khi thành công; lỗi hệ thống hoàn lại 1 lượt (Q03).

### 54. Realtime Chat Test (Q05)
- Member đổi sang PT mới: Vẫn đọc được lịch sử chat với PT cũ, nhưng không được gửi tin nhắn mới cho PT cũ (Q05).
- PT mới không được đọc lịch sử tin nhắn của PT cũ (Q05). PT cũ mất resource scope khi phân công kết thúc, không được tiếp tục truy cập hay gửi tin qua scope cũ.
- Gói hết hạn: Member vẫn xem lại được lịch sử chat cũ (RULE GYM 11).

### 54.1. PT Session Usage Test (Q06)
- PT xác nhận buổi tập khi kỳ hạn của Member còn hiệu lực và còn quota $\rightarrow$ Thành công, trừ 1 buổi.
- PT cố tình xác nhận buổi tập khi kỳ hạn của Member đã `HET_HAN` $\rightarrow$ Backend từ chối thẳng thừng (Q06).
- Member tự gửi request xác nhận hoàn thành: Backend từ chối thẳng thừng.

### 54.2. PT Proposal Test (Q13)
- PT có phân công active được phép tạo proposal điều chỉnh lịch tập cho học viên dù học viên hết buổi PT trong gói (Q13).
- PT không có phân công active cố tình tạo proposal cho học viên lạ $\rightarrow$ Bị chặn 403.

### 54.3. Required edge-case coverage

The required suite is proportional to the changed surface and must retain the following canonical evidence; a phase is not complete merely because the tests that happened to run passed.

- **Role, assignment, and Q12:** revoke removes access; regrant updates the existing role row, preserves id/ngay_tao/UNIQUE, records actor/time/before/after audit, and retry adds no duplicate audit. Concurrent Admin assignments serialize one effective PT, reject overlap, and preserve closed history. Account/catalog deactivation never cascades history.
- **M061:** GET includes HOAT_DONG and NGUNG_SU_DUNG; enum/default normalization is tested; create relation with inactive group is rejected; existing inactive pivot keeps id/role/timestamps; replacement payload must echo it or returns INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE; omitted muscle_groups leaves pivots untouched; same-state PATCH is no-op; real transition writes one CAP_NHAT_TRANG_THAI_NHOM_CO audit atomically; concurrent status/relation changes are serialized. Equipment tests remain on their own lifecycle.
- **Payment and queues:** snapshot survives catalog edits; payment ordering follows first valid Backend confirmation under lock; out-of-order/late/duplicate webhooks, wrong amount, expired/canceled order, and two links receiving money produce at most one term and CAN_DOI_SOAT evidence without auto-refund. A new active-chain tail is CHO_DEN_LUOT, never CHO_KICH_HOAT; only a new-chain/head term waits for first paid use. Boundary entitlement works without a background status updater.
- **Membership and QR:** viewing package/profile/history does not activate; accepted AI, PT direct, Member Chat, and QR first use share one clock; retries and AI/PT/QR races activate once; future terms cannot be borrowed; PT quota never carries over. QR boundary at exactly/just before het_han_luc, policy-default 90 seconds, one-use replay, wrong Member, invalid Membership, and two Receptionists racing are covered.
- **Scheduled Workout/Q08:** Start requires an existing owned valid buoi_tap_du_kien and non-null FK; no unscheduled/free Start; no active or expired Membership still permits valid Q08 Start/Save Set/Complete without paid usage. Plan/schedule slot uniqueness and concurrent replacement are tested. A HUY session cannot restart on its old schedule; replacement schedule/version keeps the old session and FK. Completed Session, AI, and PT edits are rejected; notes do not mutate Plan/history; Q11 equipment is AND.
- **AI:** Structured Output/schema, unknown/out-of-candidate Exercise, invalid reps/rest/calendar, ownership, Plan Version, Proposal status, TTL, base-context conflict, and double confirm are covered. One business request reserves one quota; provider retry does not add turns; provider error refunds once; late response cannot revive a refunded request or roll back an already activated clock.
- **Chat/Q05:** Member history remains readable after expiry or reassignment; send requires current exact assignment plus term Chat entitlement. Member-winning first message creates one TRO_CHUYEN_HUAN_LUYEN usage, activation, message, and outbox atomically; later Member/PT/read/open/subscribe messages create none. First-message/outbox failure rolls back, retry returns the same message/usage, and concurrent activation has one winner. PT old/new scope, leave/reconnect cleanup, sequence dedupe/catch-up, and no direct-quota side effect are covered.
- **PT direct/Q06 and Proposal/Q13:** exact term/quota, expired-term rejection, no backdate/future borrowing, Member self rejection, retry and two sessions competing for the last quota are tested. PT Proposal create works with assignment only and no Membership/Chat/direct quota prerequisite or side effect; confirm revalidates source assignment, ownership, base version/context, future schedule, TTL/status under lock, moves conflicts to terminal state, preserves history, and applies once.

Test evidence must name the exact command, scope, pass/fail/skip counts, environment, and final-state tree inspected. Skips or a reduced suite require an explicit concern and cannot be silently treated as PASS.

---

## 3. Mẫu Báo cáo Nghiệm thu Module (PHẦN XXI — 15 sections)

Khi một module hoặc Phase hoàn thành, Agent bắt buộc phải xuất trình báo cáo theo định dạng chuẩn:

```markdown
MODULE DA HOAN THANH

1. Muc tieu module
2. File da tao
3. File da sua
4. Database lien quan
5. API da tao/sua
6. Ham chinh
   - ten ham
   - muc dich
   - cach hoat dong
7. Business Rule da xu ly
8. Authorization
9. Validation
10. Transaction / Idempotency
11. Error Case
12. Test Case
13. Test Result
14. Phan chua hoan thanh
15. Rui ro con lai

Phase-specific evidence fields (for example routes, screenshots, build, lint, naming, docblock, secret scan, deployment, or blocker status) may be added after these 15 sections; they do not replace them.
```
