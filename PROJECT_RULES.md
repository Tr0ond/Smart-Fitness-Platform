# MASTER PROMPT – SMART FITNESS PLATFORM

## Đặc tả dự án + Business Rules + Coding Rules

## Trạng thái tài liệu

- Tệp quy tắc chính thức: `PROJECT_RULES.md`.
- Tên bảng/cột chính thức dùng theo [TU_DIEN_DU_LIEU.md](docs/thiet_ke_co_so_du_lieu/TU_DIEN_DU_LIEU.md), thống nhất với [ERD theo mẫu](docs/thiet_ke_co_so_du_lieu/smart_fitness_erd_theo_mau.drawio) và [ERD chi tiết](docs/thiet_ke_co_so_du_lieu/smart_fitness_erd.drawio). Tên chính thức được giữ nguyên; các quyết định Q01–Q13 đã được chủ dự án chốt và tích hợp vào các phần nghiệp vụ bên dưới. Trạng thái sẵn sàng lập kế hoạch migration xem THIET_KE_DATABASE.md; không có migration được tạo/chạy trong bước thiết kế này.
- Ngày hợp nhất: **28/08/2026**.
- Cập nhật đã chốt: tách quyền Chat PT khỏi quota buổi PT trực tiếp; cấp lại Role trên hàng hiện tại và lưu audit; Workout Mode chỉ bắt đầu từ lịch có sẵn; Chat chỉ ghi sử dụng quyền lợi khi tin nhắn Member kích hoạt kỳ.
- Nguồn: Master Prompt, các quyết định Membership/PT đã hợp nhất và vòng review Q01–Q13 ngày 28/08/2026. Q01–Q13 là business rules chính thức, không còn là câu hỏi mở.
- Đây là bản hợp nhất có cập nhật, không phải bản sao nguyên văn của prompt ban đầu. Những phần không được chủ dự án thay đổi vẫn được giữ nguyên.
- Quyết định kích hoạt mới nhất thay thế các mô tả kích hoạt mâu thuẫn trong những bản trước; áp dụng thống nhất cho gói kết hợp và gói chỉ có dịch vụ online.
- Không dùng nội dung từ bản cũ để ghi đè các quy tắc đã hợp nhất ở đây. Chỉ thay đổi business rule khi chủ dự án có yêu cầu mới.
- Canonical Source Policy: Tệp `PROJECT_RULES.md` là nguồn chân lý tối cao (Canonical Source of Truth) của toàn bộ dự án. Nhằm tối ưu token, Agent không mặc định đọc toàn bộ 3,000 dòng của tệp này cho mọi tác vụ thông thường. Thay vào đó, Agent đọc [.fitness-rules/PROJECT_CORE.md](.fitness-rules/PROJECT_CORE.md) và tra cứu module tương ứng qua [.fitness-rules/RULE_INDEX.md](.fitness-rules/RULE_INDEX.md). Chỉ tra cứu trực tiếp các mục trong tệp này khi có xung đột, điểm mơ hồ nghiệp vụ, hoặc khi Controller yêu cầu kiểm chứng canonical.

---

Bạn đang tham gia phát triển đồ án khóa luận tốt nghiệp:

# Hệ thống Trợ lý Tập luyện và Quản lý Thể hình

## Smart Fitness Platform

Hãy coi toàn bộ nội dung dưới đây là **đặc tả chính thức và quy tắc phát triển bắt buộc của dự án**.

Mọi công việc liên quan đến:

* Phân tích nghiệp vụ.
* Thiết kế Database.
* Backend.
* Web Frontend.
* Mobile App.
* REST API.
* AI.
* Realtime.
* Payment.
* QR.
* Test Case.
* Refactor.
* Code Review.

đều phải tuân thủ tài liệu này.

Không được tự ý thay đổi Business Rule hoặc mở rộng phạm vi dự án nếu chưa có yêu cầu.

---

# PHẦN I – TỔNG QUAN DỰ ÁN

## 1. Mục tiêu

Smart Fitness Platform là hệ thống kết hợp giữa:

1. Quản lý hội viên phòng gym.
2. Quản lý Membership và gói tập.
3. Thanh toán trực tuyến.
4. Check-in tại phòng gym bằng QR.
5. Quản lý quá trình tập luyện.
6. Workout Plan.
7. Workout Mode.
8. Workout History.
9. Progress Tracking.
10. Personal Trainer.
11. Realtime Chat giữa PT và Member.
12. AI Fitness Assistant hỗ trợ lập và điều chỉnh kế hoạch tập luyện.

Trọng tâm của khóa luận KHÔNG phải:

> Xây dựng thật nhiều chức năng CRUD cho phòng gym.

Trọng tâm chính là:

> **Quản lý hội viên và hỗ trợ lập, thực hiện, theo dõi và điều chỉnh kế hoạch tập luyện có kiểm soát thông qua PT và AI.**

---

# 2. Ba trụ cột chính

```text
SMART FITNESS PLATFORM
│
├── GYM
│   ├── Member
│   ├── Membership
│   ├── Membership Plan
│   ├── payOS
│   └── QR Check-in
│
├── WORKOUT
│   ├── Exercise Library
│   ├── Workout Template
│   ├── Workout Plan
│   ├── Workout Plan Version
│   ├── Scheduled Workout
│   ├── Workout Mode
│   ├── Workout History
│   └── Progress Tracking
│
└── ASSISTANCE
    ├── Personal Trainer
    ├── PT ↔ Member Realtime Chat
    │
    └── AI Fitness Assistant
        ├── Rule Engine
        ├── LLM
        ├── Structured Output
        ├── Validation
        ├── AI Proposal
        ├── Preview
        ├── User Confirmation
        └── Apply
```

---

# PHẦN II – CÔNG NGHỆ

## 3. Web

Sử dụng:

* Vue.js.
* Vite.
* Vue Router.
* Pinia.
* Axios.
* UI Framework nếu cần.

Web dành cho:

* Admin.
* Receptionist.
* PT.

---

# 4. Mobile

Sử dụng:

* React Native.

Mobile App dành chủ yếu cho:

* Member.

Trong phạm vi khóa luận ưu tiên:

> Android.

Không bắt buộc phải chứng minh hoạt động hoàn chỉnh trên iOS nếu nguồn lực không đủ.

---

# 5. Backend

Sử dụng:

* Laravel.
* RESTful API.

Web và Mobile:

> Dùng chung một Laravel Backend.

Không xây hai Backend riêng biệt.

---

# 6. Database

Sử dụng:

* MariaDB 10.4.32.
* InnoDB.

Đích DBMS kỹ thuật chính thức của dự án là **MariaDB 10.4.32 / InnoDB** (XAMPP, cổng phát triển mặc định 3306). Laravel dùng PDO/MySQL-compatible driver hoặc connection `mariadb` tương ứng; PHP CLI phải lấy từ `E:\Fitness\.tools\php` và giữ theo `composer.lock`. Không coi MySQL 8.4, SQLite hoặc MariaDB phiên bản khác là môi trường preflight tương đương.

Database cần được thiết kế cẩn thận để bảo toàn:

* Membership history.
* Payment history.
* Workout history.
* Workout Plan version.
* AI Proposal.
* Chat history.
* Audit dữ liệu quan trọng.

---

# 6.1. Bảo toàn lịch sử — Q12

Trong MVP không hard-delete lịch sử quan trọng: đơn/thanh toán/webhook, chuỗi/kỳ/snapshot Membership, sử dụng quyền lợi, check-in, phân công/lượt PT, Plan/Version/Schedule, phiên/bài/hiệp tập, Chat, AI request/provider/Proposal và audit.

- Giữ cả lịch HUY/DA_THAY_THE, phiên HUY và phân công đã kết thúc.
- Account/catalog khóa hoặc ngừng sử dụng theo rule hiện tại; không cascade xóa lịch sử.
- Không xóa lịch sử để vượt UNIQUE hoặc tạo lại dữ liệu.
- Anonymization, thời hạn lưu theo pháp luật và purge/archive tự động thuộc Future Development/vận hành sau MVP; không tự đặt số năm retention.

---

# 7. Authentication

Có thể sử dụng:

* Laravel Sanctum.

Web có thể sử dụng:

* Session/Cookie phù hợp SPA.

React Native:

* Token phù hợp Mobile App.

Authorization:

> BẮT BUỘC kiểm tra tại Laravel Backend.

Không được coi việc ẩn nút ở Frontend là Authorization.

---

# 8. Thanh toán

Sử dụng:

* payOS.

Luồng chính:

```text
Member
  ↓
Chọn Membership Plan
  ↓
Mobile gọi Laravel
  ↓
Laravel tạo đơn
  ↓
Laravel gọi payOS
  ↓
Member thanh toán
  ↓
payOS gửi Webhook
  ↓
Laravel xác thực
  ↓
Payment SUCCESS
  ↓
Ghi nhận quyền sở hữu và kỳ quyền lợi Membership
  ↓
Chờ kích hoạt hoặc xếp kỳ nối tiếp theo RULE GYM 01–07
```

Không kích hoạt Membership chỉ dựa vào:

* returnUrl.
* Frontend báo thành công.
* Client gửi `payment_success = true` (ví dụ cờ client tự khai báo, không phải cột database).

Webhook mới là nguồn chính để xác nhận giao dịch.

---

# 8.1. Snapshot đơn, thứ tự thanh toán và đối soát — Q01, Q02, Q10

**Q01:** Khi tạo `don_mua_goi`, chốt giá và snapshot gói/thời hạn/quyền lợi tại `ky_han_hoi_vien` ở trạng thái `CHO_THANH_TOAN` theo thiết kế hiện tại. Snapshot không đổi trong hạn đơn/payment link, kể cả Admin sửa catalog; đơn mua mới dùng catalog mới. Không kéo dài giữ giá quá `don_mua_goi.het_han_thanh_toan_luc`; hạn link không được vượt hạn đơn. Snapshot sau hạn vẫn giữ làm lịch sử, không có nghĩa tiếp tục được cấp quyền.

**Q02:** “Thứ tự mua” dùng để xếp kỳ là thứ tự Backend **lần đầu xác nhận thanh toán hợp lệ**, được ghi dưới khóa Member/chuỗi và cùng transaction cấp kỳ. Dùng `lan_thanh_toan.xac_nhan_luc`, `ky_han_hoi_vien.mua_luc` và `so_thu_tu`; nếu mốc trùng thì thứ tự khóa/cấp `so_thu_tu` quyết định. Retry không đổi mốc/thứ tự. Không dựa thứ tự tạo đơn, thời điểm client/returnUrl hoặc chèn ngược kỳ vào chuỗi đã bắt đầu/đã dùng.

**Q10:** Tiền sai số, trả sau hạn/hủy, payment không khớp, hai link cùng đơn nhận tiền hoặc callback bất thường phải giữ dấu vết để đối soát. Khoản bất thường được đánh dấu `CAN_DOI_SOAT` tại sự kiện/lần thanh toán liên quan, không cấp kỳ kép, cộng thêm ngày, tự hoàn tiền, xóa giao dịch hoặc bỏ qua webhook. Payload không xác thực vẫn bị từ chối và lưu dấu vết, không được coi là tiền hợp lệ. Mỗi đơn chỉ cấp một kỳ đúng theo idempotency; khoản bất thường đến sau không xóa nguồn cấp kỳ hợp lệ trước đó. Refund không thuộc CORE MVP.

---

# 9. Realtime

Sử dụng:

* Laravel Reverb.
* WebSocket.

Mục đích chính:

> PT ↔ Member Realtime Chat.

---

# 10. AI

Sử dụng kiến trúc:

> **Hybrid Rule Engine + LLM**

Có thể kết nối:

* OpenAI.
* Gemini.
* Hoặc nhà cung cấp LLM phù hợp.

React Native KHÔNG gọi trực tiếp LLM.

Luồng:

```text
React Native
     ↓
Laravel
     ↓
AI Module
     ↓
Rule Engine
     ↓
LLM Provider
     ↓
Structured Output
     ↓
Laravel Validation
     ↓
AI Proposal
     ↓
React Native
```

API Key của LLM chỉ được lưu ở Backend.

---

# PHẦN III – PHẠM VI DỰ ÁN

# 11. Chi nhánh

MVP chỉ triển khai:

> **01 chi nhánh phòng gym.**

Database vẫn phải có:

```text
chi_nhanh
```

để đảm bảo khả năng mở rộng về sau.

Không xây đầy đủ:

* Branch Manager.
* Super Admin.
* Multi-branch reporting.
* Cross-branch operation.

Multi-branch:

> Future Development.

---

# 12. Actor chính

MVP có 4 Actor con người:

1. Member.
2. PT.
3. Receptionist.
4. Admin.

External System:

5. payOS.
6. LLM Provider.

AI Fitness Assistant là:

> Module bên trong Smart Fitness Platform.

Không nhất thiết coi AI Assistant là Actor bên ngoài trong Use Case Diagram.

---

# PHẦN IV – ACTOR VÀ CHỨC NĂNG

# 13. Member

Sử dụng React Native App.

Có thể:

* Đăng ký.
* Đăng nhập.
* Quên mật khẩu.
* Quản lý Profile.
* Cập nhật thông tin thể chất.
* Theo dõi cân nặng.
* Theo dõi BMI.
* Thiết lập mục tiêu.
* Thiết lập kinh nghiệm tập.
* Thiết lập số ngày có thể tập.
* Thiết lập dụng cụ.
* Xem Membership Plan.
* Mua gói.
* Gia hạn.
* Thanh toán payOS.
* Xem Membership hiện tại.
* Tạo QR Check-in.
* Xem Exercise Library.
* Xem Workout Template.
* Có Workout Plan cá nhân.
* Sử dụng Workout Mode.
* Ghi Sets.
* Ghi Reps.
* Ghi Weight.
* Rest Timer.
* Hoàn thành Workout Session.
* Xem Workout History.
* Xem Progress.
* Chat với PT.
* Chat với AI.
* Yêu cầu AI tạo kế hoạch.
* Yêu cầu AI đổi lịch.
* Yêu cầu AI thay Exercise.
* Yêu cầu AI giải thích Workout Plan.
* Preview AI Proposal.
* Xác nhận AI Proposal.
* Preview và xác nhận/từ chối Workout Plan Proposal do PT tạo.

---

# 14. PT

Sử dụng Vue Web.

Có thể:
- Đăng nhập.
- Xem Member được phân công, Profile, Workout Plan, Workout History và Progress.
- Theo dõi quá trình tập.
- Chat realtime với Member thuộc quyền.
- Tạo Proposal kế hoạch mới hoặc chỉnh sửa kế hoạch tương lai.
- Điều chỉnh bài, ngày, sets/reps mục tiêu thông qua Proposal.
- Thêm ghi chú tư vấn không sửa kế hoạch chính thức.
- Xác nhận buổi PT hoàn thành để ghi nhận và trừ lượt theo RULE GYM 23.

PT chỉ truy cập Member có quan hệ phụ trách hợp lệ. Không được thay `hoi_vien_id` trên URL để xem dữ liệu người khác.

Thay đổi kế hoạch phải tuân thủ RULE GYM 24; không tự áp dụng thay đổi lớn khi Member chưa xác nhận. Không sửa Workout Session đã hoàn thành.

---

# 14.1. Phân công PT theo thời gian — Q04

Một Member có thể chưa có PT hoặc có đúng một PT đang phụ trách: tối đa **0..1 PT hiệu lực tại một thời điểm**.

`phan_cong_huan_luyen_vien` giữ mọi khoảng lịch sử `[ngay_bat_dau, ngay_ket_thuc)`; NULL ở cận cuối nghĩa là khoảng mở. Không overlap giữa bất kỳ PT nào của cùng Member.

- Đổi PT: kết thúc phân công cũ trước hoặc đúng ranh giới bắt đầu phân công mới; giữ hàng cũ.
- Hai Admin cùng phân công phải khóa `ho_so_hoi_vien` trước, đọc lại các khoảng phân công và kiểm tra overlap trong transaction; khóa các hàng phân công theo id thống nhất.
- A kết thúc 15:00 và B bắt đầu 15:00 hợp lệ; A còn tới 16:00 trong khi B bắt đầu 15:00 bị từ chối.
- UNIQUE cột sinh `hoi_vien_dang_phan_cong_id` chỉ ngăn hai khoảng có `ngay_ket_thuc IS NULL`, không thay thế kiểm tra overlap lịch sử/hữu hạn.
- Không dùng CHECK chéo hàng hoặc NOW() trong generated column để giả giải quyết quy tắc thời gian.

---

# 15. Receptionist

Sử dụng Vue Web.

Có thể:

* Tra cứu Member.
* Xem trạng thái Membership.
* Quét/xác nhận QR Check-in.
* Kiểm tra Membership.
* Hỗ trợ nghiệp vụ hội viên cơ bản.

Receptionist không có quyền quản trị toàn hệ thống.

---

# 16. Admin

Sử dụng Vue Web.

Có thể:

* Quản lý Account.
* Quản lý Role.
* Quản lý Member.
* Quản lý PT.
* Quản lý phân công PT cho Member.
* Quản lý Receptionist.
* Quản lý Membership Plan.
* Quản lý Payment.
* Quản lý Exercise Library.
* Quản lý Workout Template.
* Xem Dashboard cơ bản.
* Cấu hình quyền lợi của Membership Plan.

---

# PHẦN V – ROLE, MEMBERSHIP VÀ ENTITLEMENT

# 17. Free/Premium KHÔNG phải Role

TUYỆT ĐỐI không thiết kế:

```text
ROLE_FREE
ROLE_PREMIUM
```

Không dùng cờ gộp như ví dụ bị cấm dưới đây (không phải cột của mô hình chính thức):

```text
is_premium = true
```

để mở toàn bộ chức năng.

Role chỉ xác định:

> Người đó là ai.

Role gồm:

```text
MEMBER
PT
RECEPTIONIST
ADMIN
```

Quyền sử dụng dịch vụ dựa vào:

1. Role.
2. Membership.
3. Entitlement.
4. Resource Scope.

---

# 17.1. Thu hồi và cấp lại Role trong MVP

Giữ `UNIQUE (nguoi_dung_id, vai_tro_id)` tại `phan_quyen_nguoi_dung`: mỗi cặp chỉ có một hàng trạng thái hiện tại.

- Thu hồi: cập nhật `thu_hoi_luc`; Backend không còn coi Role đó là hiệu lực.
- Cấp lại: cập nhật chính hàng cũ, gán `cap_luc` mới, cập nhật `nguoi_cap_id`, đặt `thu_hoi_luc = NULL`; không INSERT một hàng trùng cặp.
- Giữ nguyên `id` và `ngay_tao`; cập nhật `ngay_cap_nhat`.
- Mỗi lần cấp, thu hồi, cấp lại lưu người thao tác, thời điểm, dữ liệu trước/sau trong `nhat_ky_he_thong`, cùng transaction với thay đổi Role.
- Hàng phân quyền chỉ thể hiện lần cấp hiện tại/gần nhất; lịch sử các lần thay đổi đọc từ audit, không suy ngược từ `cap_luc` đã được cập nhật.
- Retry cùng thao tác không được sinh thêm hàng Role hoặc audit thành công trùng. Không xây bảng lịch sử Role riêng trong MVP.

---

# 18. Entitlement

Quyền dịch vụ phải dựa trên:
1. Role.
2. Quyền sở hữu và trạng thái kỳ Membership.
3. Snapshot quyền lợi của kỳ đang có hiệu lực hoặc kỳ đầu được phép bắt đầu.
4. Resource scope; với PT còn cần quan hệ phụ trách hợp lệ.

Ví dụ:
- Kỳ có quyền check-in: được check-in khi QR và nhân viên hợp lệ.
- Kỳ có quyền AI: được dùng AI theo giới hạn của kỳ.
- Kỳ có `cho_phep_tro_chuyen_huan_luyen_vien = true` và phân công PT A hợp lệ: được Chat PT A, kể cả số buổi PT bằng 0 hoặc đã dùng hết.
- Sử dụng buổi PT trực tiếp cần snapshot `so_buoi_huan_luyen_vien > 0`, còn lượt và phân công hợp lệ; không suy quyền buổi từ cờ Chat PT, cũng không suy quyền Chat từ số buổi.
- PT A chỉ truy cập Member đang thuộc phạm vi phụ trách.

Nếu kỳ đầu còn chờ, lần sử dụng quyền lợi trả phí hợp lệ phải kích hoạt kỳ theo RULE GYM 02, 08 và 21.

Kỳ mua nối tiếp chưa đến lượt không cấp quyền ngay. Không hợp nhất quyền của tất cả các gói đã mua để kiểm tra truy cập.

Membership hết hạn không làm mất quyền xem lịch sử cá nhân theo RULE GYM 11–12.

---

# PHẦN VI – BUSINESS RULES MEMBERSHIP

# RULE GYM 01 – Thanh toán chưa bắt đầu tính thời hạn

Thanh toán thành công chỉ ghi nhận quyền sở hữu gói và kỳ quyền lợi đã mua.

Nếu chưa có chuỗi kỳ đang hoạt động:
- Kỳ đầu ở trạng thái `CHO_KICH_HOAT`.
- `ngay_bat_dau = null`.
- `ngay_ket_thuc = null`.
- Các kỳ đã mua tiếp theo được xếp hàng theo thứ tự mua.

Nếu đang có kỳ còn hiệu lực, kỳ mới được xếp nối tiếp; không kích hoạt quyền lợi mới ngay khi thanh toán.

Quy tắc này áp dụng cả gói Gym + AI + PT và gói chỉ có AI/PT online. Thanh toán không phải hành động sử dụng dịch vụ.

Ví dụ: mua PLUS 30 ngày ngày 01/09/2026, chưa dùng quyền lợi nào thì vẫn chờ kích hoạt.

---

# RULE GYM 02 – Lần sử dụng quyền lợi trả phí đầu tiên kích hoạt Membership

> **Kỳ đầu bắt đầu tính thời hạn khi Member sử dụng lần đầu một quyền lợi có thời hạn thuộc kỳ đó.**

Check-in chỉ là một trong các trigger. AI và PT online cũng có thể kích hoạt, nếu quyền lợi đó có trong kỳ được phép sử dụng.

| Hành động | Kích hoạt kỳ đầu đang chờ? |
| --- | --- |
| Thanh toán thành công | Không |
| Xem thông tin gói | Không |
| Xem Workout History cũ | Không |
| Xem hồ sơ cá nhân | Không |
| Gửi yêu cầu AI đầu tiên thuộc quyền lợi trả phí hợp lệ | Có |
| Sử dụng PT online thuộc quyền lợi gói hợp lệ | Có |
| Check-in gym hợp lệ lần đầu | Có |
| Sử dụng quyền lợi trả phí có thời hạn khác đã được phê duyệt trong scope | Có |

Luồng:

    Payment SUCCESS
        ↓
    CHO_KICH_HOAT
        ↓
    Lần sử dụng quyền lợi trả phí đầu tiên hợp lệ
        ├── AI
        ├── PT online
        └── Check-in gym
        ↓
    Transaction kích hoạt đúng một lần
        ↓
    ky_han_hoi_vien.ngay_bat_dau = su_dung_quyen_loi.chap_nhan_luc
    ky_han_hoi_vien.ngay_ket_thuc = ky_han_hoi_vien.ngay_bat_dau + ky_han_hoi_vien.thoi_han_ngay
    ky_han_hoi_vien.trang_thai = DANG_HOAT_DONG

Trong công thức, `su_dung_quyen_loi` là bản ghi sử dụng trả phí đầu tiên hợp lệ của kỳ; `thoi_han_ngay` là số ngày trong snapshot kỳ.

Ví dụ PLUS 30 ngày gồm Gym + AI + PT:
- 01/09 thanh toán: chờ kích hoạt.
- 03/09 dùng AI lần đầu: kỳ bắt đầu ngày 03/09, kết thúc sau 30 ngày.
- 10/09 check-in gym: dùng kỳ đã hoạt động, không reset hoặc tính lại ngày bắt đầu.

Nếu chưa dùng AI/PT và 05/09 mới check-in lần đầu, kỳ bắt đầu từ thời điểm check-in ngày 05/09.

Ví dụ PREMIUM 90 ngày:
- 01/09 thanh toán, chưa dùng gì: chưa tính ngày.
- 04/09 yêu cầu AI tạo lịch: bắt đầu kỳ 90 ngày.
- 15/09 đến gym: vẫn tính theo mốc 04/09.

Các kỳ đã mua nối tiếp chạy liên tục khi kỳ trước kết thúc theo RULE GYM 05–06; không chờ một lần sử dụng mới cho từng kỳ nối tiếp.

---

# RULE GYM 03 – Trạng thái Membership

Ít nhất gồm:

```text
CHO_THANH_TOAN
CHO_KICH_HOAT
DANG_HOAT_DONG
HET_HAN
HUY
```

Không dùng một boolean duy nhất để mô tả toàn bộ vòng đời.

---

# RULE GYM 04 – Gia hạn phải giữ thời gian còn dư và tách quyền lợi

> **Thời gian được cộng nối tiếp, nhưng quyền lợi của kỳ mới chỉ có hiệu lực sau khi kỳ hiện tại kết thúc.**

Ví dụ BASIC còn 10 ngày, Member mua PLUS 30 ngày có PT:
- 10 ngày đầu vẫn dùng quyền lợi BASIC.
- Sau đó mới đến 30 ngày quyền lợi PLUS.
- Không mất 10 ngày còn lại.
- Không cấp PT của PLUS trong 10 ngày BASIC.
- Không trộn quyền lợi của các kỳ thành một bộ quyền chung.

Phải lưu từng kỳ quyền lợi riêng trong Membership/Subscription, kể cả khi hai lần mua có cùng loại gói.

---

# RULE GYM 05 – Gia hạn khi đang hoạt động

Nếu kỳ hiện tại còn hiệu lực, lần mua mới tạo một kỳ nối tiếp sau kỳ cuối đã mua trong chuỗi.

    ky_moi.ngay_bat_dau = ky_truoc.ngay_ket_thuc
    ky_moi.ngay_ket_thuc = ky_moi.ngay_bat_dau + ky_moi.thoi_han_ngay

`ky_moi` và `ky_truoc` chỉ là bí danh của hai bản ghi `ky_han_hoi_vien` trong công thức, không phải tên bảng/cột mới.

Nếu đã có nhiều kỳ đang chờ đến lượt, nối sau kỳ cuối; không chèn đè lên kỳ đã mua.

- Tổng thời gian sử dụng được kéo dài, nhưng không kéo dài quyền lợi của kỳ cũ sang thời gian kỳ mới.
- Kỳ mới giữ snapshot gói riêng tại thời điểm mua.
- Đến mốc chuyển kỳ, quyền lợi chuyển sang kỳ kế tiếp; không cần một lần check-in, AI hoặc PT mới để khởi động lại đồng hồ.
- Backend phải xác định kỳ có hiệu lực theo thời gian khi kiểm tra quyền, không phụ thuộc duy nhất vào một tác vụ cập nhật trạng thái chạy nền.
- Các mốc thời gian là ranh giới nối tiếp; không tính một thời điểm thuộc đồng thời hai kỳ.

---

# RULE GYM 06 – Mua thêm khi chưa kích hoạt

Các gói đã thanh toán trước lần sử dụng quyền lợi đầu tiên được xếp hàng theo thứ tự mua.

Ví dụ:

    Kỳ 1: BASIC 30 ngày
    Kỳ 2: PLUS 60 ngày
    Kỳ 3: VIP 90 ngày

Khi lần sử dụng quyền lợi hợp lệ đầu tiên diễn ra:
1. BASIC bắt đầu trước.
2. Hết BASIC thì PLUS bắt đầu.
3. Hết PLUS thì VIP bắt đầu.

Nếu chỉ mua 30 ngày rồi thêm 60 ngày, tổng thời gian đã mua là 90 ngày, nhưng vẫn phải giữ hai kỳ với snapshot riêng.

- Trước khi chuỗi bắt đầu, chưa tính ngày sử dụng.
- Không trộn toàn bộ quyền lợi của các gói.
- Không dùng AI/PT của một kỳ ở phía sau để kích hoạt vượt hàng đợi.
- Trigger đầu tiên phải thuộc quyền lợi của kỳ đầu đang chờ được sử dụng.
- Sau khi chuỗi bắt đầu, các kỳ nối tiếp không được tự tạm dừng để chờ sử dụng.

---

# RULE GYM 07 – Mua lại sau khi chuỗi kỳ đã hết hạn

Nếu tất cả kỳ cũ đã hết hạn và không còn kỳ nối tiếp đã mua, Member không còn thời gian dư.

Mua gói mới:

    Payment SUCCESS
        ↓
    CHO_KICH_HOAT
        ↓
    Lần sử dụng quyền lợi trả phí đầu tiên của kỳ mới
        ↓
    DANG_HOAT_DONG

Không cộng ngược vào thời gian đã hết hạn. Không tự kích hoạt kỳ mới từ thanh toán.

Nếu đã có kỳ nối tiếp được mua trong khi chuỗi còn hiệu lực, áp dụng RULE GYM 05; không coi đó là một lần mua lại sau khi chuỗi hết hạn.

---

# RULE GYM 08 – Chỉ Backend được kích hoạt Membership

Frontend và LLM không được quyết định ngày bắt đầu, ngày kết thúc hoặc trạng thái kích hoạt.

Backend dùng nghiệp vụ chung, ví dụ `kichHoatNeuCan(kyMembership)`, tại các luồng:
- `suDungAI()`.
- `suDungDichVuPT()`.
- `checkIn()`.

Trước khi kích hoạt, phải kiểm tra:
- Đăng nhập, resource ownership và quyền thao tác.
- Payment hợp lệ và quyền sở hữu kỳ.
- Đây là kỳ đang có hiệu lực hoặc kỳ đầu thực sự được phép bắt đầu.
- Hành động thuộc quyền lợi snapshot của kỳ.
- Với Chat PT: quan hệ phụ trách hợp lệ và cờ Chat PT của đúng kỳ; không kiểm tra số buổi còn lại để mở chat.
- Với buổi PT trực tiếp: quan hệ phụ trách hợp lệ, tổng buổi được cấp > 0 và còn lượt; cờ Chat PT không phải điều kiện trừ buổi.
- Với check-in: QR, Receptionist và quyền check-in hợp lệ.

Nếu kỳ đầu đang `CHO_KICH_HOAT`, Backend dùng transaction/khóa phù hợp để gán ngày bắt đầu, ngày kết thúc và chuyển trạng thái đúng một lần.

Nếu kỳ đã `DANG_HOAT_DONG` và còn hiệu lực, không kích hoạt lại, không reset thời hạn.

Không kích hoạt kỳ hết hạn, kỳ chưa đến lượt hoặc kỳ không có quyền thực hiện hành động. Request bị từ chối trước khi đủ điều kiện không được kích hoạt gói.

AI, PT và QR có thể đến đồng thời: tất cả phải dùng chung mốc kích hoạt đã được ghi nhận. Cần audit nguồn kích hoạt và idempotency phù hợp.

---

# RULE GYM 09 – Chat PT và buổi PT là hai quyền độc lập

Admin cấu hình riêng:

- `cho_phep_tro_chuyen_huan_luyen_vien BOOLEAN`: quyền Chat PT trong thời hạn kỳ, không có quota số tin nhắn.
- `so_buoi_huan_luyen_vien SMALLINT UNSIGNED`: quota các buổi PT trực tiếp 1-1 của kỳ, tối thiểu 0.

Không dùng cờ PT gộp để mở cả hai quyền. Không cần thêm boolean cho buổi PT: tổng buổi > 0 thể hiện quyền buổi, nhưng mỗi lần sử dụng vẫn phải còn lượt.

| Gói minh họa | Chat PT | Số buổi PT trực tiếp |
| --- | --- | --- |
| BASIC | Không | 0 |
| ONLINE | Có | 0 |
| PLUS | Có | 4 |
| VIP | Có | 12 |

ONLINE có thể gồm Gym + AI + Chat PT và không có buổi PT trực tiếp. Cờ Chat và quota buổi không ràng buộc kéo theo nhau: tắt Chat không bắt buộc số buổi bằng 0; có buổi không tự mở Chat.

Quyền lợi được lưu tại `quyen_loi_goi_tap` và snapshot riêng tại `ky_han_hoi_vien`. Admin sửa catalog không đổi kỳ đã mua. Tên BASIC/ONLINE/PLUS/VIP chỉ để hiển thị, không hard-code quyền theo tên.

Chat vẫn dùng được khi hết lượt PT nếu cờ Chat còn hiệu lực và phân công hợp lệ. Buổi PT chỉ trừ khi hoàn thành theo RULE GYM 23; chat không trừ buổi.

---

# RULE GYM 10 – Membership Plan có cấu hình quyền lợi

Admin có thể cấu hình tại `goi_tap`:

```text
ten_goi
gia
thoi_han_ngay
mo_ta
trang_thai
```

và quyền lợi tại `quyen_loi_goi_tap` (snapshot cùng tên tại `ky_han_hoi_vien`):

```text
cho_phep_vao_phong_tap
cho_phep_tro_ly_tap_luyen
gioi_han_luot_tro_ly
cho_phep_tro_chuyen_huan_luyen_vien
so_buoi_huan_luyen_vien
```

Có thể mở rộng về sau.

Không code kiểu:

```javascript
if (tenGoi === 'VIP') {
   ...
}
```

---

# RULE GYM 11 – Membership hết hạn vẫn xem Workout History

Member hết hạn Membership:

> Vẫn xem được dữ liệu lịch sử cá nhân.

Bao gồm:

* Workout History.
* Workout Session.
* Workout Set.
* Progress.
* Body Measurement.
* Payment History.

Membership hết hạn chỉ khóa các quyền cần gói còn hiệu lực.

---

# RULE GYM 12 – Workout History không phụ thuộc Membership hiện tại

```text
Membership het han
!=
Workout History bi xoa
```

```text
Mua goi moi
!=
Workout History reset
```

Workout History phải tồn tại xuyên suốt vòng đời tài khoản.

---

# RULE GYM 13 – Workout Session đã hoàn thành không được sửa

Khi:

```text
trang_thai = HOAN_THANH
```

không cho phép:

* sửa Exercise;
* sửa Sets;
* sửa Reps;
* sửa Weight;
* thêm Set;
* xóa Set;
* đổi ngày;
* sửa dữ liệu kết quả thực tế.

Buổi đã hoàn thành là:

> Dữ liệu lịch sử bất biến.

---

# RULE GYM 14 – AI không được sửa Workout đã hoàn thành

Ví dụ Member nói:

> Hãy sửa buổi hôm qua từ 3 set thành 4 set.

Nếu Workout Session đó đã hoàn thành:

→ Reject.

AI chỉ được dùng dữ liệu đó để:

* phân tích;
* giải thích;
* đề xuất cho tương lai.

---

# RULE GYM 15 – PT không được sửa Workout Session đã hoàn thành

PT cũng không được chỉnh sửa trực tiếp dữ liệu lịch sử đã hoàn thành.

Nếu sau này cần sửa sai:

→ Xây riêng Correction/Audit Workflow.

Không thuộc MVP.

---

# RULE GYM 16 – Workout Plan tương lai được phép thay đổi

Ví dụ:

```text
Thu 2 Push  → DA HOAN THANH
Thu 4 Pull  → CHUA TAP
Thu 6 Legs  → CHUA TAP
```

Có thể thay đổi:

```text
Thu 4
Thu 6
```

Nhưng không thay đổi:

```text
Workout Session Thu 2
```

đã hoàn thành.

---

# RULE GYM 17 – Payment và Activation là hai sự kiện khác nhau

Payment thành công nghĩa là Member đã mua quyền sử dụng một kỳ dịch vụ.

Lần sử dụng quyền lợi trả phí đầu tiên hợp lệ nghĩa là bắt đầu tính thời gian của kỳ đầu.

    Payment SUCCESS
    ≠
    Membership DANG_HOAT_DONG ngay lập tức

Kỳ có một đồng hồ chung cho tất cả quyền lợi có thời hạn. Không tách mốc Gym, AI và PT.

Đối với chuỗi đã hoạt động, kỳ mua nối tiếp có hiệu lực theo mốc kết thúc kỳ trước; không reset chuỗi tại mỗi lần thanh toán hoặc sử dụng.

---

# RULE GYM 18 – Webhook phải Idempotent

Nếu payOS gửi:

```text
Webhook lan 1
Webhook lan 2
Webhook lan 3
Webhook lan 10
```

thì số ngày Membership:

> Chỉ được cộng MỘT LẦN.

Phải có:

* transaction.
* unique payment reference.
* trạng thái Payment.
* idempotency.
* kiểm tra giao dịch đã xử lý.

---

# RULE GYM 19 – Phải lưu lịch sử mua, gia hạn và snapshot từng kỳ

Phải truy vết được:

    Member
        ↓
    Đơn mua gói
        ↓
    Payment
        ↓
    Kỳ quyền lợi được cấp
        ↓
    Snapshot gói tại thời điểm mua

Mỗi kỳ giữ snapshot tối thiểu về gói, giá, thời hạn, quyền check-in, quyền AI, cờ `cho_phep_tro_chuyen_huan_luyen_vien` và quota `so_buoi_huan_luyen_vien` đã mua. Hai quyền PT được chụp riêng, không gộp thành một cờ.

- Admin sửa cấu hình gói chỉ ảnh hưởng các lần mua sau; không âm thầm sửa quyền lợi của kỳ đã mua.
- Mỗi lần gia hạn có lịch sử và nguồn thanh toán.
- Không chỉ tăng một trường số ngày hoặc thay ngày hết hạn chung rồi làm mất các kỳ.
- Không cấp cùng một kỳ/quyền lợi hai lần khi Payment hoặc request bị gửi lại.

---

# RULE GYM 20 – Không dùng Cron giảm so_ngay_con_lai mỗi ngày

Không thiết kế cột đếm ngược như ví dụ bị cấm dưới đây (không phải cột của mô hình chính thức):

```text
so_ngay_con_lai = 30
```

rồi mỗi ngày:

```text
so_ngay_con_lai--
```

Ưu tiên lưu:

```text
ngay_bat_dau
ngay_ket_thuc
trang_thai
```

và các kỳ quyền lợi/lịch sử gia hạn.

---

# RULE GYM 21 – Một đồng hồ chung cho Gym, AI và PT

Mọi quyền lợi có thời hạn của một kỳ dùng cùng `ngay_bat_dau` và `ngay_ket_thuc`.

- Member có thể bắt đầu bằng AI/PT online trước lần đến gym; hành động sử dụng trả phí đó đồng thời kích hoạt kỳ.
- Không cho dùng quyền lợi online trả phí trong khi để kỳ tiếp tục chờ và không tính ngày.
- Gói `cho_phep_vao_phong_tap = false` vẫn chờ lần sử dụng quyền lợi trả phí đầu tiên, như AI hoặc PT online; không tự hoạt động ngay sau Payment.
- Một kỳ đã hoạt động không được đổi mốc vì Member lần đầu dùng thêm quyền lợi khác.
- Quyền lợi miễn phí, nếu có, không phải lần dùng quyền lợi trả phí của gói.
- Mở màn hình, xem thông tin hoặc đọc dữ liệu cá nhân không được tự biến thành một lần sử dụng trả phí.
- Không xây các đồng hồ Gym/AI/PT riêng trong MVP.
- “Quyền lợi trả phí khác” chỉ là quy tắc tổng quát, không cho phép Agent tự thêm dịch vụ ngoài CORE.

# RULE GYM 22 – Quyền lợi theo kỳ, không dùng trước quyền lợi tương lai

Membership/Subscription là chuỗi các kỳ quyền lợi. Mỗi kỳ có thời hạn và snapshot độc lập.

- Khi đang ở BASIC, không dùng quyền Chat PT/buổi PT trực tiếp hoặc AI chỉ có trong PLUS đang xếp sau; PT Proposal theo Q13 không phụ thuộc gói.
- Khi chuyển sang PLUS, quyền có hiệu lực là snapshot PLUS.
- Số buổi PT và giới hạn dịch vụ thuộc kỳ tương ứng, không thuộc tên gói hiển thị.
- Khi nhiều kỳ chưa bắt đầu, dùng thứ tự mua đã được lưu và kiểm tra ở Backend.
- Khi các thao tác mua/gia hạn đồng thời xảy ra, không tạo các kỳ chồng lấn hoặc mất thứ tự.
- Không tự thêm đổi gói tức thời, hoàn tiền, chuyển nhượng hay carry-over.

# RULE GYM 23 – Số buổi PT thuộc từng kỳ Membership

Mỗi kỳ có tổng số buổi PT trực tiếp 1-1 được cấp theo snapshot `so_buoi_huan_luyen_vien` và lịch sử các lần sử dụng. Giá trị 0 nghĩa là không có buổi PT, dù kỳ vẫn có thể có Chat PT.

Có thể lưu tổng đã dùng hoặc số còn lại, nhưng mọi biến động phải truy vết được; không chỉ giảm một con số.

Trong MVP, PT phụ trách xác nhận buổi PT đã hoàn thành:

    PT chọn Member
        ↓
    Ghi nhận buổi PT
        ↓
    Backend kiểm tra quan hệ phụ trách
    + so_buoi_huan_luyen_vien > 0 của kỳ đang được phép sử dụng
    + số buổi đã dùng < tổng buổi được cấp
        ↓
    Kích hoạt nếu đây là lần sử dụng trả phí đầu tiên hợp lệ
        ↓
    Xác nhận buổi HOAN_THANH
        ↓
    Transaction lưu lịch sử và trừ đúng 1 lượt
        ↓
    Audit

Quy tắc:
- Member không được tự ghi nhận đã sử dụng buổi PT.
- Chỉ trừ lượt khi buổi PT `HOAN_THANH`.
- Không trừ vì đặt lịch, hủy, PT hủy hoặc chỉ gửi tin nhắn.
- Dùng Chat PT có thể kích hoạt thời hạn theo mục 33.1, nhưng không đồng nghĩa hoàn thành một buổi PT. Không bắt buộc bật cờ Chat khi xác nhận buổi trực tiếp; không bắt buộc còn buổi khi gửi chat.
- Bản ghi sử dụng phải gắn với đúng kỳ và PT xác nhận.
- Retry hoặc hai request xác nhận cùng một buổi không được trừ hai lần.
- Xác nhận đồng thời phải bảo vệ số lượt còn lại, không cho âm hoặc vượt tổng đã cấp.
- **Q06:** PT phải xác nhận hợp lệ khi đúng kỳ của buổi còn hiệu lực và còn quota (hoặc head được phép kích hoạt theo rule chung). Kỳ đã hết trước xác nhận thì từ chối, không dùng thời điểm hoàn thành để backdate/trừ vào kỳ cũ, không mượn kỳ sau hoặc chọn kỳ khác còn lượt. Giữ lịch sử; correction Admin thuộc Future Development.
- Lượt chưa dùng hết hết hiệu lực cùng kỳ; không chuyển sang kỳ tiếp theo.
- PLUS cũ còn 2 lượt rồi hết hạn, PLUS mới cấp 4 lượt thì kỳ mới có 4, không phải 6.
- Admin/Receptionist chỉ hỗ trợ ghi nhận hoặc điều chỉnh khi được cấp quyền rõ ràng và có audit; không mặc định thêm workflow điều chỉnh vào CORE.
- PT Booking vẫn thuộc PHASE SAU; không cần xây booking để ghi nhận buổi PT hoàn thành trong MVP.

# RULE GYM 24 – PT tạo Proposal cho thay đổi kế hoạch

PT được tạo và đề xuất chỉnh sửa kế hoạch tương lai của Member có quan hệ phụ trách hợp lệ.

**Q13 đã chốt:** Điều kiện quyền nghiệp vụ của PT Proposal chỉ là PT đã xác thực và đang có đúng `phan_cong_huan_luyen_vien` hợp lệ với Member. Không yêu cầu cờ Chat, tổng/còn quota buổi PT hoặc active Membership. Tạo/apply Proposal không trừ lượt, không tạo usage Chat/buổi PT và không kích hoạt Membership; vẫn phải qua toàn bộ workflow xác nhận/validation bên dưới.

Phạm vi:
- Tạo Workout Plan mới.
- Đổi template, số buổi, ngày tập hoặc cấu trúc kế hoạch.
- Thêm, xóa hoặc thay bài tập tương lai.
- Điều chỉnh sets/reps mục tiêu.
- Thêm ghi chú tư vấn.
- Đề xuất phiên bản kế hoạch mới.

Luồng thay đổi dữ liệu kế hoạch:

    PT tạo Workout Plan Proposal
        ↓
    Member xem Preview
        ↓
    Member XAC NHAN / TU CHOI
        ↓
    Nếu xác nhận: Backend revalidate quyền, dữ liệu, phiên bản
        ↓
    Transaction áp dụng và tạo Workout Plan Version mới
        ↓
    Audit

- Các thay đổi lớn phải được Member xác nhận trước khi trở thành kế hoạch chính thức.
- Thay đổi nội dung tập được kê, bao gồm sets/reps mục tiêu, phải đi qua Proposal; không dùng ghi chú để âm thầm sửa dữ liệu này.
- Tạo mới kế hoạch cho Member cũng đi qua luồng Proposal và xác nhận.
- Member từ chối thì kế hoạch chính thức không đổi.
- Ghi chú tư vấn chỉ mang tính thông tin, không thay đổi cấu trúc/nội dung kê tập thì không cần xác nhận.
- Ghi chú phải lưu riêng, không sửa kết quả lịch sử đã hoàn thành.
- AI và PT đều không được sửa Workout Session `HOAN_THANH`.
- Khi áp dụng phải kiểm tra ownership, quan hệ phụ trách còn hợp lệ, phiên bản, trạng thái Proposal, validation và idempotency.
- Nếu kế hoạch đã thay đổi sau lúc tạo Proposal, không ghi đè; cần xử lý xung đột và cho Member xem lại đề xuất phù hợp.
- Khi confirm, nếu phân công nguồn của PT đã hết hiệu lực (kể cả đã có phân công mới), không Apply; chuyển `XUNG_DOT` hoặc trạng thái kết thúc phù hợp. Kiểm tra base version, ownership, future schedule, TTL và trạng thái dưới khóa; không thêm điều kiện Chat/quota/Membership cho Proposal PT.
- Cơ chế Proposal có thể dùng chung thành phần, nhưng phải phân biệt nguồn AI/PT và lưu người tạo; không biến đề xuất PT thành kết quả của một lần gọi LLM.

---

# RULE GYM 25 – Hạn QR vào phòng tập — Q09

- QR Check-in có TTL mặc định **90 giây**, do server configuration/policy tập trung quyết định.
- `ma_vao_phong_tap.het_han_luc` lưu hạn cụ thể từ lúc phát hành; không tính lại hạn QR cũ khi sửa cấu hình.
- Chỉ còn hạn khi thời điểm kiểm tra < `het_han_luc`; đúng hoặc quá hạn bị từ chối.
- Không hard-code TTL rải rác; phát hành QR không kích hoạt Membership.
- QR là credential dùng một lần. Sau check-in thành công, quét lại cùng token bị từ chối bằng conflict có kiểm soát; không trả success lần hai, không tạo thêm usage/lịch sử và không kích hoạt lại Membership.

---

# PHẦN VII – EXERCISE VÀ WORKOUT

# 19. Exercise Library

Exercise có thể chứa:

* Tên.
* Nhóm cơ.
* Equipment.
* Độ khó.
* Hướng dẫn.
* Video/Hình ảnh.
* Metadata.

Equipment ở đây nghĩa là:

> Dụng cụ để thực hiện Exercise.

Ví dụ:

* Dumbbell.
* Barbell.
* Bench.
* Cable Machine.

KHÔNG phải quản lý tài sản thiết bị.

**Q11:** Mọi hàng `bai_tap_dung_cu` của một bài có nghĩa AND: bài cần Bench và Barbell thì phải có cả hai. Không xây nhóm dụng cụ OR trong MVP. Biến thể dùng dụng cụ khác quản lý như bài/biến thể bài khác trong Exercise Library; Rule Engine và Apply kiểm tra đủ tập dụng cụ.

**M061 — vòng đời Muscle Group:** `nhom_co.trang_thai` chỉ nhận `HOAT_DONG` hoặc `NGUNG_SU_DUNG`, mặc định `HOAT_DONG`; Admin quản lý bằng GET/POST/PATCH và không có DELETE. GET vẫn trả cả nhóm ngừng sử dụng để đọc catalog và quan hệ lịch sử. Backend chỉ cho tạo quan hệ `bai_tap_nhom_co` mới với nhóm đang hoạt động. Việc ngừng sử dụng không xóa/sửa pivot hoặc history; quan hệ hiện hữu vẫn đọc được kèm status và phải giữ nguyên id, vai trò, `ngay_tao`, `ngay_cap_nhat` cho tới khi nhóm được kích hoạt lại. Khi PATCH Exercise gửi replacement `muscle_groups`, mọi quan hệ inactive hiện hữu phải được gửi lại đúng vai trò; thêm, bỏ hoặc đổi vai trò bị từ chối nguyên tử với mã `INACTIVE_MUSCLE_GROUP_RELATION_IMMUTABLE`. Bỏ trường `muscle_groups` khỏi PATCH chỉ cập nhật các chiều khác. PATCH nhóm cùng trạng thái sau chuẩn hóa là no-op; chuyển trạng thái thật ghi một audit `CAP_NHAT_TRANG_THAI_NHOM_CO` cùng transaction.

---

# 20. Equipment Maintenance đã loại khỏi Scope

Không triển khai:

* máy nào đang hỏng;
* lịch bảo trì;
* nhân viên sửa;
* ngày mua tài sản;
* quản lý tài sản gym.

---

# 21. Workout Template

Là giáo án mẫu.

Ví dụ:

* Push Pull Legs.
* Upper Lower.
* Full Body.

---

# 22. Workout Plan

Là kế hoạch tập cá nhân của Member.

**Q07A:** Mỗi Member có tối đa một `ke_hoach_tap.trang_thai = DANG_SU_DUNG`; Plan cũ chuyển `LUU_TRU` và giữ lịch sử. Cột sinh nullable `hoi_vien_dang_su_dung_id` bằng `hoi_vien_id` khi đang dùng, ngược lại NULL; UNIQUE bảo vệ một Active Plan. Đổi Plan dưới khóa Member/Plan, cùng transaction với version, lịch hợp lệ và audit; không xóa Plan cũ để né UNIQUE.

Workout Plan có thể được tạo từ:

* Workout Template.
* PT Proposal được Member xác nhận.
* AI Proposal được Member xác nhận.
* Member lựa chọn theo quyền.

---

# 23. Workout Plan Version

Khi thay đổi kế hoạch được phê duyệt theo luồng AI/PT Proposal:

> Phải lưu phiên bản để truy vết và không ghi đè kế hoạch cũ.

Ghi chú tư vấn lưu riêng, không được sửa nội dung kê tập hoặc kết quả thực tế thông qua ghi chú.

Ví dụ:

```text
Workout Plan
│
├── Version 1
├── Version 2
└── Version 3
```

Không sửa dữ liệu cũ theo cách làm mất khả năng tái hiện lịch trước đó.

---

# 24. Scheduled Workout

Là:

> Buổi tập dự kiến trong tương lai.

Ví dụ:

```text
10/09/2026
Push Day
```

**Q07B:** Mỗi Member tối đa một `buoi_tap_du_kien` còn giá trị cho mỗi `ngay_tap`. `CHUA_TAP`, `DANG_TAP`, `HOAN_THANH`, `BO_QUA` giữ slot ngày; `HUY`, `DA_THAY_THE` giải phóng slot nhưng giữ history. Cột sinh `ngay_tap_con_hieu_luc` nhận ngày khi giữ slot, ngược lại NULL; UNIQUE `(hoi_vien_id, ngay_tap_con_hieu_luc)` bảo vệ quy tắc. Giữ `ma_buoi_logic`, `thay_the_buoi_tap_id` và version; thay lịch dưới khóa Member, giải phóng slot cũ trước khi tạo hàng thay thế trong cùng transaction.

---

# 25. Workout Session

Là:

> Buổi tập thực tế đã diễn ra.

Các cột tương ứng tại `phien_tap`:

```text
bat_dau_luc
ket_thuc_luc
trang_thai
```

---

Trong MVP, Member chỉ bắt đầu Workout Mode từ một `buoi_tap_du_kien` đã thuộc Workout Plan/Schedule của mình và đủ điều kiện bắt đầu.

- `phien_tap.buoi_tap_du_kien_id` bắt buộc NOT NULL và có FK thật.
- Backend kiểm tra ownership của lịch, Plan/Version và trạng thái lịch; không tin ID lịch từ client.
- Không cho khởi tạo phiên chỉ từ danh sách bài tự chọn khi chưa có lịch.
- Không tự tạo lịch giả hoặc cho phép FK NULL để đi vòng quy tắc.
- Free Workout ngoài lịch là FUTURE DEVELOPMENT, không thuộc MVP.
- **Q08:** Workout Tracking là chức năng cơ bản của Member, không phải entitlement Membership trả phí. Không thêm cờ quyền Workout.
- Không có Membership đang hoạt động hoặc Membership đã hết hạn vẫn được xem Exercise Library, Plan/Schedule của mình, Start từ lịch hợp lệ, ghi Sets/Reps/Weight, Complete, xem History và Progress.
- Start/Save Set/Complete không yêu cầu active Membership, không tạo usage trả phí/kích hoạt kỳ; vẫn kiểm tra authenticated Member, ownership, Plan/Schedule, trạng thái, idempotency, concurrency và bất biến lịch sử. Quyền Gym/AI/PT Chat/buổi PT vẫn kiểm tra riêng.

---

# 25.1. Phiên đã hủy và tập lại — Q07C

Giữ `phien_tap.buoi_tap_du_kien_id NOT NULL UNIQUE`: một lịch tối đa một phiên, kể cả phiên `HUY`.

- Không Start phiên thứ hai trên cùng lịch; không xóa hoặc chuyển phiên HUY về đang tập để thử lại.
- Nếu cần tập lại, tạo `buoi_tap_du_kien` thay thế hợp lệ theo workflow schedule/version đã có, rồi Start từ lịch mới.
- Giữ phiên HUY và FK lịch cũ; hàng lịch cũ chuyển HUY/DA_THAY_THE theo bước hủy/thay.
- Do UNIQUE `(phien_ban_ke_hoach_tap_id, ma_buoi_logic)`, thay cùng mã buổi cần version mới theo workflow được xác nhận. Không tạo lịch giả, không đổi FK phiên cũ.
- Chỉ thay lịch chưa tập tương lai hoặc lịch có phiên HUY theo luồng này; không thay lịch đang tập/đã hoàn thành. Lịch mới vẫn phải đúng ownership, ngày và slot.

---

# 26. Session Exercise

Lưu các Exercise thực tế trong buổi tập.

---

# 27. Workout Set

Lưu kết quả từng Set.

Ví dụ:

```text
Bench Press

Set 1
60kg x 10

Set 2
60kg x 9

Set 3
55kg x 10
```

---

# 28. Kế hoạch và thực tế là hai dữ liệu khác nhau

Plan:

```text
Bench Press
3 x 10
```

Thực tế:

```text
60 x 10
60 x 9
55 x 10
```

Workout History phải lưu:

> Kết quả thực tế.

Không lấy dữ liệu Plan thay cho dữ liệu Workout Session.

---

# 29. Database Workout phải bảo toàn lịch sử

Thiết kế logic tối thiểu, dùng tên bảng chính thức tương ứng với các khái niệm trên:

```text
giao_an_mau
      ↓
ke_hoach_tap
      ↓
phien_ban_ke_hoach_tap
      ↓
buoi_tap_du_kien
      ↓
phien_tap
      ↓
bai_tap_trong_phien
      ↓
hiep_tap
```

Đây là luồng khái niệm; FK cụ thể theo từ điển dữ liệu và ERD, không suy thêm FK từ các mũi tên.

AI thay Plan ngày mai:

> Không được làm thay đổi Session hôm qua.

---

# PHẦN VIII – PROGRESS TRACKING

# 30. Progress

Giữ ở mức hợp lý:

* cân nặng;
* số buổi;
* tần suất;
* kết quả Exercise;
* Weight;
* Reps;
* Sets.

Không xây hệ thống phân tích vận động chuyên sâu.

---

# PHẦN IX – PT REALTIME CHAT

# 31. Giữ Realtime Chat

Realtime Chat là chức năng quan trọng vì PT là một điểm nổi bật của dự án.

Luồng:

```text
Member ↔ PT
```

---

# 32. Scope Realtime Chat

MVP cần:

* 1-1 Conversation.
* Text Message.
* Realtime.
* Lưu Database.
* Pagination.
* Reconnect.
* Lấy message bị thiếu.
* Authorization.
* Read status cơ bản nếu đủ thời gian.

Không cần:

* Video Call.
* Voice Call.
* Group Chat.
* Reaction.
* Sticker.
* File.
* Location.
* Edit Message.
* Typing Indicator phức tạp.

---

# 33. Chat Authorization

Member chỉ được chat:

> PT mà Member có quan hệ phụ trách hợp lệ.

PT chỉ đọc:

> Conversation của Member thuộc quyền.

WebSocket không thay thế Backend Authorization.

GỬI tin Chat PT đọc riêng `cho_phep_tro_chuyen_huan_luyen_vien` của kỳ Membership được phép sử dụng, cùng quan hệ phân công hợp lệ. Không dùng số buổi PT còn lại làm điều kiện gửi; không mở quyền kỳ tương lai. Chat không trừ số buổi PT.

**Q05 — tách READ HISTORY và SEND NEW MESSAGE:**

- Member luôn được đọc hội thoại/tin cũ của mình dù hết Membership hoặc đổi PT; không DELETE/sửa nội dung lịch sử.
- Gửi mới phải còn quyền Chat và chính phân công của hội thoại còn hiệu lực. Mất quyền hoặc phân công đã kết thúc thì từ chối gửi, kể cả trả lời công việc đang dở.
- `hoi_thoai.phan_cong_huan_luyen_vien_id` gắn hội thoại với lần phân công. Đổi PT tạo/dùng hội thoại riêng của phân công mới; hội thoại cũ là lịch sử đọc cho Member. Không đổi FK hoặc mở lại hội thoại cũ khi phân công lại PT cũ.
- PT mới không được đọc hội thoại riêng của PT cũ. PT cũ mất resource scope khi phân công kết thúc, không được tiếp tục gửi/truy cập bằng scope cũ.
- Subscribe/reconnect/broadcast kiểm tra đúng quyền đọc; không lấy điều kiện gửi trả phí để khóa Member đọc history, không phát dữ liệu tới PT đã mất scope.

# 33.1. Chat chỉ ghi sử dụng quyền lợi khi kích hoạt kỳ

- Khi Member gửi tin hợp lệ trong lúc kỳ đầu thực sự đang `CHO_KICH_HOAT`: tạo một `su_dung_quyen_loi` loại `TRO_CHUYEN_HUAN_LUYEN`, kích hoạt chuỗi và lưu tin nhắn cùng transaction.
- Tin nhắn kích hoạt tham chiếu bản ghi đó bằng `tin_nhan.su_dung_quyen_loi_id`. Bản ghi này là nguồn `dang_ky_goi_tap.lan_su_dung_dau_tien_id`.
- Khi kỳ đã `DANG_HOAT_DONG`: mỗi lần gửi vẫn kiểm tra quyền Chat của đúng kỳ và phân công, rồi lưu tin/outbox; không tạo thêm bản ghi sử dụng quyền lợi cho từng tin. Các tin đó có `su_dung_quyen_loi_id = NULL`, không cùng trỏ về bản ghi kích hoạt.
- Nếu AI/QR/buổi PT đã kích hoạt trước, kể cả tin Chat PT đầu tiên cũng không tạo thêm usage. Các kỳ nối tiếp tự chạy theo thời gian, không cần usage Chat mới để bắt đầu.
- Không có quota/counter số message. Lịch sử nhắn tin lưu tại `tin_nhan`; không nhân đôi thành ledger quyền lợi.
- PT chủ động gửi, đọc tin, subscribe hoặc Member chỉ mở màn hình không kích hoạt thay Member.
- Hai tin đầu đồng thời hoặc Chat tranh kích hoạt với AI/QR/buổi PT: dùng chung khóa hội viên/chuỗi/kỳ, đọc lại trạng thái sau khóa; chỉ thao tác thắng mới ghi nguồn kích hoạt. Tin đến sau không tạo usage Chat.
- Retry tin kích hoạt trả lại chính tin/usage cũ, không tạo bản ghi mới; lỗi lưu tin hoặc outbox phải rollback cả activation/usage trong transaction đó.
- AI khác Chat PT: request dịch vụ tính quota cần dấu vết theo request và chính sách AI được duyệt; không áp quy tắc “chỉ ghi lần đầu” của Chat cho AI.

---

# PHẦN X – AI FITNESS ASSISTANT

# 34. AI không phải chatbot đơn giản

Không triển khai:

```text
User
↓
LLM
↓
Text
↓
Done
```

Phải triển khai:

```text
User
↓
Laravel
↓
Normalize Request
↓
Rule Engine
↓
Candidate Selection
↓
LLM
↓
Structured Output
↓
Validation
↓
AI Proposal
↓
Preview
↓
User Confirmation
↓
Revalidate
↓
Transaction
↓
Apply
```

---

# 34.1. Quota request AI — Q03

**Một request nghiệp vụ AI hợp lệ = một lượt**, không tính từng message hoặc từng lần retry provider.

1. Backend xác minh quyền AI và điều kiện request.
2. Dưới khóa Member/chuỗi/kỳ/request: revalidate, giữ một lượt quota, lưu `yeu_cau_tro_ly`, `su_dung_quyen_loi` và domain record; nếu là lần dùng trả phí đầu tiên thì kích hoạt kỳ cùng transaction.
3. Sau commit mới gọi provider.
4. Kết quả nghiệp vụ hợp lệ: chuyển `GIU_CHO` sang `DA_TINH`, giảm giữ chỗ và tăng đã dùng đúng một.
5. Lỗi kỹ thuật kết thúc request: trả lượt đúng một lần (`DA_TRA`), giảm counter giữ chỗ hoặc đã dùng tương ứng; không reset/lùi đồng hồ Membership đã kích hoạt.

Request thiếu điều kiện/bị từ chối trước chấp nhận dịch vụ không giữ/trừ quota, không usage và không activation (`KHONG_AP_DUNG`). Hỏi bổ sung trước chấp nhận không tính lượt; các message/provider retry thuộc cùng request không tạo lượt mới. Retry dùng cùng request, không hồi sinh request đã trả quota bằng phản hồi trễ. Hoàn/trả quota, trạng thái request và audit phải transaction-safe/idempotent; không âm counter hoặc trả hai lần.

---

# 35. Phạm vi AI

AI chỉ tập trung:

1. Workout Planning.
2. Workout Adjustment.
3. Exercise Replacement.
4. Workout/Exercise Explanation.

Không mở rộng AI sang:

* Chẩn đoán bệnh.
* Điều trị.
* Chấn thương.
* Thuốc.
* Supplement.
* Dinh dưỡng điều trị.

---

# 36. Rule Engine chịu trách nhiệm

Rule Engine xử lý:

* số ngày tập;
* ngày rảnh;
* Equipment;
* Exercise hợp lệ;
* Exercise tồn tại;
* Exercise Candidate;
* Template Candidate;
* quyền sử dụng;
* lịch trùng;
* ràng buộc nghiệp vụ;
* giới hạn dữ liệu;
* kiểm tra ID;
* quyền truy cập.

Những gì code kiểm tra chắc chắn được:

> Không giao LLM quyết định.

---

# 37. LLM chịu trách nhiệm

LLM hỗ trợ:

* hiểu ngôn ngữ tự nhiên;
* intent detection;
* trích xuất yêu cầu;
* hỏi thêm dữ liệu thiếu;
* giải thích;
* chọn giữa các phương án hợp lệ;
* tạo phản hồi tự nhiên.

Ví dụ:

> "Tôi rảnh thứ 2, 4, 6, mỗi buổi khoảng 60 phút và chỉ có tạ đơn."

LLM có thể trích xuất các thông tin trung gian sau (không phải tên bảng/cột):

- 3 buổi mỗi tuần.
- Rảnh thứ 2, 4, 6.
- 60 phút mỗi buổi.
- Dụng cụ: tạ đơn.

Backend sau đó kiểm tra và chuẩn hóa.

---

# 38. AI bắt buộc Structured Output

Khi AI tạo dữ liệu có thể áp dụng:

> BẮT BUỘC dùng Structured Output.

Ví dụ payload minh họa dùng tên dữ liệu theo từ điển:

```json
{
  "loai_thay_doi": "TAO_MOI",
  "ngay_trong_ke_hoach": [
    {
      "thu_trong_tuan": 2,
      "bai_tap_trong_ke_hoach": [
        {
          "bai_tap_id": 15,
          "so_hiep_muc_tieu": 3,
          "so_lan_lap_toi_thieu": 8,
          "so_lan_lap_toi_da": 12,
          "thoi_gian_nghi_giay": 90
        }
      ]
    }
  ]
}
```

`loai_thay_doi` tương ứng với dữ liệu của `de_xuat_ke_hoach_tap`; các trường kê tập dùng tên cột của `ngay_trong_ke_hoach` và `bai_tap_trong_ke_hoach`. Hai khóa mảng mang tên bảng chỉ mô tả cấu trúc JSON minh họa, không phải cột mới. Ví dụ này không chốt hợp đồng API/LLM và không đổi tên trường bắt buộc của nhà cung cấp bên ngoài.

Không parse ngược text tự do để lưu Workout Plan.

---

# 39. AI Validation

Laravel phải kiểm tra:

* JSON schema.
* Exercise ID tồn tại.
* Exercise nằm trong Candidate.
* Equipment phù hợp.
* Template hợp lệ.
* Set hợp lệ.
* Reps hợp lệ.
* `so_lan_lap_toi_thieu <= so_lan_lap_toi_da`.
* Rest hợp lệ.
* ngày hợp lệ.
* không trùng lịch.
* quyền sử dụng.
* resource ownership.
* Plan Version.
* Proposal status.

---

# 40. AI chỉ tạo Proposal

AI không có quyền:

* tự lưu Plan chính thức;
* tự sửa Plan;
* tự xóa lịch;
* tự sửa Membership;
* tự xác nhận Payment;
* tự thay đổi Role;
* tự chỉnh Workout Session hoàn thành.

AI chỉ:

> ĐỀ XUẤT.

---

# 41. AI Proposal Workflow

```text
User Request
    ↓
Laravel
    ↓
Rule Engine
    ↓
LLM
    ↓
Structured Output
    ↓
Validation lan 1
    ↓
Luu AI Proposal
    ↓
Preview
    ↓
User Confirm
    ↓
Validation lan 2
    ↓
Transaction
    ↓
Apply
    ↓
Audit
```

---

# 42. User phải xác nhận AI Proposal

AI không tự apply.

Frontend phải có màn hình:

> Proposal Preview.

Ví dụ:

```text
AI de xuat thay doi:

Thu 4:
Lat Pulldown
→ One-arm Dumbbell Row

[HUY]
[XAC NHAN]
```

Chỉ sau khi Member xác nhận:

→ Backend mới Apply.

---

# 43. AI Proposal phải chống Apply hai lần

Nếu Member bấm:

```text
XAC NHAN
XAC NHAN
```

hai lần do mạng chậm:

> Chỉ được Apply một lần.

Cần xem xét:

* Idempotency Key.
* Proposal status.
* Database transaction.

---

# 43.1. Hạn Proposal AI/PT — Q09

TTL mặc định **24 giờ** cho cả AI và PT Proposal, lấy từ server configuration/policy tập trung. `de_xuat_ke_hoach_tap.het_han_luc` lưu mốc cụ thể từ lúc tạo; Preview/retry không kéo dài hạn. Confirm tại hoặc sau hạn không Apply; vẫn giữ Proposal làm lịch sử.

---

# PHẦN XI – DASHBOARD

# 44. Dashboard cơ bản

Có thể có:

* Tổng Member.
* Membership đang hoạt động.
* Membership chờ kích hoạt.
* Check-in.
* Giao dịch.
* Tiền đã thu.
* Member mới.

Không xây BI phức tạp.

---

# PHẦN XII – PHẠM VI CUỐI CÙNG

# 45. CORE

Phải ưu tiên:

* Authentication.
* Authorization.
* Member.
* Membership Plan.
* Membership.
* payOS.
* Membership Activation.
* Membership Renewal.
* Dynamic QR.
* QR Check-in.
* Exercise Library.
* Workout Template.
* Workout Plan.
* Workout Plan Version.
* Scheduled Workout.
* Workout Mode.
* Workout History.
* Progress Tracking.
* PT.
* PT Assignment.
* Ghi nhận buổi PT hoàn thành và lịch sử sử dụng lượt theo kỳ.
* PT Workout Plan Proposal, Preview, xác nhận/từ chối và phiên bản.
* Ghi chú tư vấn PT.
* PT ↔ Member Chat.
* AI Fitness Assistant.
* Rule Engine.
* LLM.
* Structured Output.
* AI Validation.
* AI Proposal.
* Proposal Preview.
* Proposal Confirmation.
* Dashboard cơ bản.
* Test.

---

# 46. PHASE SAU

* PT Booking.
* Multi-branch.
* Advanced Notification.
* Advanced Dashboard.
* Advanced Reports.

---

# 46.1. FUTURE DEVELOPMENT

- Free Workout: khởi tạo Workout Session tự do ngoài `buoi_tap_du_kien`. Không đưa vào MVP.
- Correction Admin cho buổi PT xác nhận sau hạn (Q06).
- Anonymization, legal retention period và purge/archive tự động sau MVP khi có yêu cầu vận hành/pháp lý cụ thể (Q12); không tự đặt số năm.

---

# 47. OUT OF SCOPE

Không tự triển khai:

* Equipment Maintenance.
* Asset Management.
* Full HR.
* Multi-branch hoàn chỉnh.
* BI System.
* Nutrition AI.
* Medical AI.
* Diagnosis.
* Injury Treatment.
* Supplement Recommendation.
* Voice Call.
* Video Call.
* Group Chat.

---

# PHẦN XIII – DATABASE NAMING RULES

# RULE CODE 01 – Database dùng tiếng Việt không dấu

Tất cả bảng và cột nghiệp vụ do dự án tự thiết kế:

> BẮT BUỘC dùng tiếng Việt không dấu.

Convention:

> `snake_case`

Ví dụ KHÔNG dùng:

```text
users
members
exercises
workout_plans
payments
start_date
end_date
status
```

Dùng:

```text
nguoi_dung
ho_so_hoi_vien
bai_tap
ke_hoach_tap
lan_thanh_toan
ngay_bat_dau
ngay_ket_thuc
trang_thai
```

---

# RULE CODE 02 – Không viết tắt Database

KHÔNG:

```text
nd
hv
bt
tt
kt
ttai
```

Dùng:

```text
nguoi_dung
ho_so_hoi_vien
bai_tap
lan_thanh_toan
ke_hoach_tap
trang_thai
```

Tên dài hơn nhưng:

> Phải dễ hiểu và dễ maintain.

---

# RULE CODE 03 – Foreign Key cũng phải rõ nghĩa

Dùng đúng tên FK đã có trong từ điển dữ liệu. Ví dụ `hoi_vien_id` tham chiếu `ho_so_hoi_vien.id`, `huan_luyen_vien_id` tham chiếu `ho_so_huan_luyen_vien.id`; không tự đổi FK chỉ để ghép với toàn bộ tên bảng đích.

Ví dụ:

```text
nguoi_dung_id
hoi_vien_id
goi_tap_id
bai_tap_id
ke_hoach_tap_id
chi_nhanh_id
```

Không dùng:

```text
uid
mid
eid
pid
```

---

# RULE CODE 04 – Tên bảng/cột chính thức

Nguồn tên chính thức là [TU_DIEN_DU_LIEU.md](docs/thiet_ke_co_so_du_lieu/TU_DIEN_DU_LIEU.md), đối chiếu với [ERD theo mẫu](docs/thiet_ke_co_so_du_lieu/smart_fitness_erd_theo_mau.drawio) và [ERD chi tiết](docs/thiet_ke_co_so_du_lieu/smart_fitness_erd.drawio).

Danh mục **52 bảng** đã có trong thiết kế hiện tại:

```text
chi_nhanh
nguoi_dung
vai_tro
phan_quyen_nguoi_dung
ho_so_hoi_vien
ho_so_huan_luyen_vien

ngay_ranh_hoi_vien
dung_cu_hoi_vien
chi_so_co_the

the_truy_cap
yeu_cau_dat_lai_mat_khau

goi_tap
quyen_loi_goi_tap
don_mua_goi
lan_thanh_toan
su_kien_thanh_toan

dang_ky_goi_tap
ky_han_hoi_vien
su_dung_quyen_loi

ma_vao_phong_tap
lich_su_vao_phong_tap

phan_cong_huan_luyen_vien
lich_su_su_dung_huan_luyen_vien
ghi_chu_huan_luyen

dung_cu
nhom_co
bai_tap
bai_tap_dung_cu
bai_tap_nhom_co

giao_an_mau
ngay_trong_giao_an
bai_tap_trong_giao_an

ke_hoach_tap
phien_ban_ke_hoach_tap
ngay_trong_ke_hoach
bai_tap_trong_ke_hoach
buoi_tap_du_kien

phien_tap
bai_tap_trong_phien
hiep_tap

hoi_thoai
tin_nhan
su_kien_phat_tin_nhan

hoi_thoai_tro_ly
tin_nhan_tro_ly
yeu_cau_tro_ly
bai_tap_ung_vien
giao_an_ung_vien
lan_goi_mo_hinh

de_xuat_ke_hoach_tap

nhat_ky_he_thong
yeu_cau_chong_lap
```

Dùng đúng tên cột, PK/FK và bảng đích trong từ điển dữ liệu; không suy ra tên mới từ bản dịch thuật ngữ nghiệp vụ hoặc ví dụ của prompt cũ. Các ví dụ tại RULE CODE 01–03 không thay thế danh mục này.

Các ánh xạ dễ nhầm:

| Khái niệm | Bảng/cột chính thức |
| --- | --- |
| Membership/Subscription và kỳ quyền lợi | `dang_ky_goi_tap` là chuỗi; `ky_han_hoi_vien` là từng kỳ; `su_dung_quyen_loi` ghi nhận sử dụng theo các rule đã chốt. |
| Cấu hình và snapshot quyền lợi | `quyen_loi_goi_tap` và `ky_han_hoi_vien` cùng dùng `cho_phep_vao_phong_tap`, `cho_phep_tro_ly_tap_luyen`, `gioi_han_luot_tro_ly`, `cho_phep_tro_chuyen_huan_luyen_vien`, `so_buoi_huan_luyen_vien`. |
| Đơn mua, lần thanh toán payOS và webhook | `don_mua_goi`, `lan_thanh_toan`, `su_kien_thanh_toan` là ba bảng riêng. |
| QR vào phòng và lịch sử check-in | `ma_vao_phong_tap`, `lich_su_vao_phong_tap`; không nhầm với QR thanh toán payOS. |
| Hồ sơ và phân công PT | `ho_so_huan_luyen_vien`, `phan_cong_huan_luyen_vien`. |
| Chat PT 1–1 | `hoi_thoai` có `hoi_vien_id`, `huan_luyen_vien_id`, `phan_cong_huan_luyen_vien_id`, thuộc một lần phân công; tin và outbox ở `tin_nhan`, `su_kien_phat_tin_nhan`. Không có bảng thành viên hội thoại riêng. |
| Hội thoại, yêu cầu AI và lần gọi LLM | `hoi_thoai_tro_ly`, `tin_nhan_tro_ly`, `yeu_cau_tro_ly`, `lan_goi_mo_hinh`; một yêu cầu nghiệp vụ không đồng nghĩa một lần gọi mô hình. |
| Proposal AI/PT | Dùng chung `de_xuat_ke_hoach_tap`, phân biệt bằng `nguon_de_xuat`; không tách thêm bảng Proposal cho AI. |
| Workout Plan Version và Workout History | `phien_ban_ke_hoach_tap` lưu phiên bản kế hoạch; `phien_tap`, `bai_tap_trong_phien`, `hiep_tap` lưu thực tế. Mốc phiên tập là `phien_tap.bat_dau_luc` / `phien_tap.ket_thuc_luc`. |

Tên bảng/cột đã được đồng bộ; Q01–Q13 đã chốt, các cột/ràng buộc bổ sung trực tiếp theo các quyết định này được liệt kê trong từ điển và ERD. Danh mục này không phải lệnh tạo bảng, tạo/chạy migration hoặc triển khai chức năng.

Không tự ý đổi convention hoặc tên chính thức sau khi đã chốt.
---

# PHẦN XIV – FRONTEND NAMING RULES

# RULE CODE 05 – Hàm FE bằng tiếng Việt không dấu

Tất cả hàm nghiệp vụ do nhóm tự viết ở:

* Vue.js.
* React Native.
* Store.
* Composable.
* Hook.
* Helper.
* Service.
* Component Logic.

phải dùng:

> Tiếng Việt không dấu.

Convention:

> camelCase.

Ví dụ KHÔNG:

```javascript
getMember()
loadExercises()
createWorkoutPlan()
handlePayment()
submitForm()
```

Dùng:

```javascript
layThongTinHoiVien()
taiDanhSachBaiTap()
taoKeHoachTap()
xuLyThanhToan()
guiBieuMau()
```

---

# RULE CODE 06 – Event Handler cũng tiếng Việt

KHÔNG:

```javascript
handleLogin()
handleSubmit()
handleClick()
onSuccess()
```

Dùng:

```javascript
xuLyDangNhap()
xuLyGuiBieuMau()
xuLyNhanNut()
xuLyThanhToanThanhCong()
```

Tên phải mô tả nghiệp vụ.

Ưu tiên:

```javascript
xuLyXacNhanDeXuatAI()
```

hơn:

```javascript
xuLyClick()
```

---

# RULE CODE 07 – Biến nghiệp vụ FE ưu tiên tiếng Việt

Ví dụ:

```javascript
const danhSachBaiTap = ref([]);
const keHoachTapHienTai = ref(null);
const hoiVienHienTai = ref(null);
const dangTaiDuLieu = ref(false);
const thongBaoLoi = ref('');
```

Không bắt buộc dịch:

* API framework.
* object thư viện.
* key từ External API.
* chuẩn payload bên thứ ba.

---

# RULE CODE 08 – Không dịch tên API của Framework

Không đổi:

```javascript
ref
computed
watch
onMounted
defineProps
defineEmits
useRoute
useRouter
axios
```

React cũng giữ:

```javascript
useState
useEffect
useMemo
useCallback
```

Chỉ hàm nghiệp vụ do nhóm tạo mới áp dụng naming tiếng Việt.

---

# PHẦN XV – COMMENT / DOCUMENTATION RULES

# RULE CODE 09 – Hàm nghiệp vụ bắt buộc có mô tả

Trước mỗi hàm nghiệp vụ quan trọng phải có comment mô tả:

1. Hàm dùng để làm gì.
2. Input.
3. Cách hoạt động.
4. Output.
5. Side Effect nếu có.

Ví dụ:

```javascript
/**
 * Lay danh sach bai tap phu hop voi dieu kien cua hoi vien.
 *
 * Dau vao:
 * - mucTieu: muc tieu tap luyen hien tai.
 * - danhSachDungCu: danh sach dung cu hoi vien co the su dung.
 *
 * Cach hoat dong:
 * 1. Gui request den Backend.
 * 2. Backend loc Exercise theo muc tieu va dung cu.
 * 3. Nhan danh sach Exercise hop le.
 * 4. Cap nhat state tren giao dien.
 *
 * Ket qua:
 * - Tra ve danh sach bai tap phu hop.
 */
async function layDanhSachBaiTapPhuHop(mucTieu, danhSachDungCu) {
    // ...
}
```

---

# RULE CODE 10 – Không comment vô nghĩa

KHÔNG:

```javascript
// Lay du lieu
const data = await api.get(...);

// Gan du lieu
danhSach.value = data;
```

Comment cần giải thích:

> Tại sao hàm tồn tại và nghiệp vụ hoạt động như thế nào.

---

# RULE CODE 11 – Hàm phức tạp phải mô tả từng bước

Ví dụ:

```javascript
/**
 * Xac nhan va ap dung de xuat lich tap do AI tao.
 *
 * Cach hoat dong:
 * 1. Gui ma de xuat den Backend.
 * 2. Backend kiem tra de xuat thuoc dung hoi vien.
 * 3. Kiem tra de xuat con hieu luc.
 * 4. Kiem tra Workout Plan co bi thay doi sau khi tao de xuat hay khong.
 * 5. Validate lai Exercise va cac rang buoc.
 * 6. Neu hop le, cap nhat Workout Plan trong Transaction.
 * 7. Tai lai ke hoach tap moi.
 *
 * Luu y:
 * - Frontend khong gui lai toan bo payload AI.
 * - Backend su dung Proposal da duoc luu truoc do.
 */
async function xacNhanDeXuatAI(maDeXuat) {
    // ...
}
```

---

# RULE CODE 12 – Backend cũng phải có Docblock cho nghiệp vụ quan trọng

Đặc biệt:

* payOS.
* Webhook.
* Membership Activation từ quyền lợi trả phí đầu tiên.
* Chuyển kỳ quyền lợi nối tiếp.
* Membership Renewal.
* Ghi nhận buổi PT và trừ lượt.
* PT Proposal và áp dụng sau xác nhận.
* QR Redeem.
* Workout Completion.
* AI Proposal.
* AI Validation.
* AI Apply.
* Chat Authorization.

Comment phải mô tả:

* mục tiêu;
* đầu vào;
* quy trình;
* validation;
* transaction;
* output;
* side effect.

---

# PHẦN XVI – CODING QUALITY RULES

# RULE CODE 13 – Không tin dữ liệu Frontend

Mọi dữ liệu quan trọng từ FE phải Validate.

Ví dụ:

* giá gói;
* Membership Plan;
* User ID;
* PT ID;
* Exercise ID;
* AI Proposal ID;
* Payment status.

Frontend không được tự quyết định:

- Giá gói (`goi_tap.gia`).
- Quyền lợi có hiệu lực theo snapshot `ky_han_hoi_vien`.
- Ngày kết thúc kỳ (`ky_han_hoi_vien.ngay_ket_thuc`).
- Kết quả thanh toán (`lan_thanh_toan.trang_thai`).
- Trạng thái kỳ Membership (`ky_han_hoi_vien.trang_thai`).

---

# RULE CODE 14 – Không tin dữ liệu LLM

LLM Output luôn được xem là:

> Untrusted Input.

Phải validate trước khi lưu.

---

# RULE CODE 15 – Transaction cho nghiệp vụ nhiều bước

Phải xem xét Database Transaction với:

* xử lý Webhook;
* cấp Membership;
* gia hạn;
* kích hoạt từ AI/PT/check-in đầu tiên;
* chuyển kỳ quyền lợi;
* ghi nhận buổi PT và trừ lượt;
* áp dụng PT Proposal;
* QR Redeem;
* Complete Workout;
* Apply AI Proposal.

---

# RULE CODE 16 – Idempotency

Phải xem xét Idempotency với:

* payOS Webhook.
* Membership Renewal.
* Membership Activation khi AI/PT/QR đồng thời.
* Ghi nhận cùng một buổi PT.
* PT Proposal Apply.
* QR Redeem.
* Workout Set gửi lại.
* Complete Workout.
* AI Proposal Apply.
* Chat gửi message nếu client retry.

---

# RULE CODE 17 – Authorization theo Resource

Không chỉ kiểm tra:

```text
vai_tro.ma_vai_tro == PT
```

mà phải kiểm tra:

```text
PT co quyen voi Member nay khong?
```

Không chỉ:

```text
vai_tro.ma_vai_tro == MEMBER
```

mà phải kiểm tra:

```text
Workout Plan nay co thuoc Member nay khong?
```

---

# RULE CODE 18 – Không tự ý thêm chức năng

Mọi chức năng phải được phân loại:

```text
CORE
PHASE SAU
FUTURE DEVELOPMENT
OUT OF SCOPE
```

Nếu không thuộc CORE:

> Không tự triển khai nếu chưa được yêu cầu.

---

# RULE CODE 19 – Không đánh đổi chất lượng để thêm màn hình

Ưu tiên:

* Business Logic.
* Database Integrity.
* Authorization.
* Validation.
* Transaction.
* Idempotency.
* Error Handling.
* Test.
* Audit.

hơn:

* thêm CRUD;
* thêm Animation;
* thêm Dashboard;
* thêm chức năng phụ.

---

# PHẦN XVII – TEST RULES

# 48. Authentication / Authorization Test

Phải test:

* chưa login;
* token không hợp lệ;
* sai Role;
* truy cập dữ liệu Member khác;
* PT truy cập Member không được phân công;
* Member sửa ID URL để xem dữ liệu người khác.
* Thu hồi Role: không còn quyền; cấp lại cập nhật hàng cũ, giữ UNIQUE cặp người dùng/vai trò và giữ id/ngay_tao.
* Audit đủ cấp/thu hồi/cấp lại với người thao tác, thời điểm, trước/sau; thay đổi Role và audit rollback cùng nhau.
* Retry cấp lại không thêm Role hoặc audit thành công trùng.

---

Các ca bổ sung Q04/Q12:

- Hai Admin đồng thời gán hai PT khác nhau cho cùng Member: chỉ một phân công hiệu lực; không overlap.
- Member không có PT hợp lệ; đổi PT đúng ranh giới hợp lệ, hai khoảng hữu hạn giao nhau bị từ chối.
- Xóa account/catalog không cascade mất history; khóa/ngừng sử dụng giữ đầy đủ tham chiếu.
- Muscle Group: list gồm cả hai trạng thái; create/patch chuẩn hóa enum; transition audit nguyên tử; same-state không đổi timestamp/audit; inactive relation không được tạo mới và pivot cũ giữ nguyên; PATCH Exercise thiếu `muscle_groups` không được chạm B29; race status/relation phải tuyến tính hóa dưới khóa hàng.

---

# 49. Payment Test

Phải test:

```text
Payment SUCCESS
Payment FAILED
```

Webhook:

```text
Hop le
Sai signature
Sai so tien
Khong tim thay don
Gui lap
Gui 10 lan
```

Kết quả:

> Một giao dịch chỉ cấp quyền một lần.

Bổ sung Q01/Q02/Q10:

- Admin sửa giá/quyền sau tạo đơn: đơn trong hạn giữ snapshot cũ; đơn mới dùng catalog mới.
- Đơn/link hết hạn: không tự kéo dài giữ giá, không cấp quyền từ khoản trả muộn.
- Webhook lệch thứ tự: cấp thứ tự theo Backend xác nhận hợp lệ lần đầu dưới khóa Member; không chèn ngược chuỗi đã dùng, retry không đổi thứ tự.
- Sai tiền, đơn hủy/hết hạn, không khớp hoặc hai link cùng nhận tiền: giữ dấu vết CAN_DOI_SOAT, tối đa một kỳ, không tự refund.

---

# 50. Membership Test

Phải test:

| Tình huống | Kết quả mong đợi |
| --- | --- |
| Thanh toán gói đầu, chưa dùng quyền lợi | CHO_KICH_HOAT, ngày bắt đầu/kết thúc chưa gán |
| Thanh toán gói chỉ AI/PT, không có check-in | Vẫn chờ lần dùng trả phí đầu tiên |
| Chỉ xem gói, hồ sơ, Workout History cũ | Không kích hoạt |
| Gửi yêu cầu AI trả phí đầu tiên hợp lệ | Kích hoạt đúng kỳ, dùng một thời hạn chung |
| Sử dụng PT online trả phí đầu tiên hợp lệ | Kích hoạt đúng kỳ |
| Check-in hợp lệ là lần sử dụng đầu tiên | Kích hoạt đúng kỳ |
| Đã kích hoạt bằng AI, sau đó mới check-in | Giữ nguyên mốc, không reset |
| Đã kích hoạt bằng PT, sau đó dùng AI | Giữ nguyên mốc |
| AI, PT và QR đến đồng thời | Chỉ một lần kích hoạt, một cặp mốc thời gian |
| Retry hành động kích hoạt | Không kích hoạt lại hoặc cộng thêm ngày |
| Request không được phép | Không kích hoạt |
| BASIC còn 10 ngày, mua PLUS 30 ngày | Giữ 10 ngày BASIC, sau đó 30 ngày PLUS |
| Có kỳ chờ đến lượt, mua thêm | Nối sau kỳ cuối, không chồng lấn |
| Chưa kích hoạt, mua 30 ngày rồi 60 ngày | Hai kỳ xếp hàng, tổng 90 ngày, không trộn quyền |
| Kỳ đầu không có quyền PT, kỳ sau có | Không dùng PT kỳ sau để vượt hàng đợi |
| Kỳ trước kết thúc, có kỳ nối tiếp | Chuyển quyền theo mốc, không chờ dùng dịch vụ mới |
| Toàn bộ chuỗi hết hạn rồi mua mới | Kỳ đầu chuỗi mới CHO_KICH_HOAT |
| Admin đổi quyền/giá gói sau khi mua | Snapshot của kỳ đã mua không đổi |
| Hai lần gia hạn đồng thời | Mỗi giao dịch cấp đúng một kỳ, thứ tự và thời gian nhất quán |
| Webhook trùng | Không tạo thêm kỳ hoặc cộng thời hạn lần nữa |
| ONLINE có Chat PT, số buổi PT = 0 | Gửi chat hợp lệ được phép và có thể kích hoạt; xác nhận buổi PT bị từ chối |
| Còn quyền Chat nhưng đã hết lượt PT | Chat vẫn được phép, không trừ/tạo thêm lượt buổi |
| Tắt Chat nhưng tổng buổi PT > 0 và còn lượt | Không chat; vẫn xác nhận được buổi trực tiếp khi đúng phân công/kỳ |

Phải kiểm tra cả dữ liệu kỳ, quyền lợi hiệu lực và lịch sử thanh toán; không chỉ kiểm tra một trường ngày hết hạn chung.

---

# 51. QR Test

Phải test:

* QR hợp lệ.
* QR hết hạn.
* Q09: QR mặc định 90 giây; đúng/quá het_han_luc bị từ chối, ngay trước hạn còn được kiểm tra các điều kiện khác.
* QR đã sử dụng: lần quét sau bị từ chối có kiểm soát, không trả lại success.
* Membership không hợp lệ.
* Membership hết hạn.
* Kỳ đầu chờ kích hoạt có quyền check-in: kích hoạt khi quét hợp lệ.
* Kỳ đã bắt đầu bằng AI/PT: check-in không reset thời hạn.
* Gói không có quyền check-in: từ chối, không kích hoạt.
* QR scan hai lần: đúng một success, một usage và một lịch sử; lần hai bị conflict.
* Hai Receptionist scan đồng thời.
* QR không thuộc Member.
* Receptionist không có quyền.

---

# 52. Workout Test

Phải test:

* Start Workout từ lịch có sẵn thuộc chính Member.
* Start không có buoi_tap_du_kien_id, lịch của người khác hoặc lịch không đủ điều kiện: từ chối, không tạo phiên/lịch giả.
* Không có Free Workout ngoài lịch trong MVP.
* Q07: hai Plan DANG_SU_DUNG cùng Member hoặc hai lịch giữ slot cùng ngày bị từ chối, kể cả request đồng thời; Plan LUU_TRU/lịch HUY/DA_THAY_THE vẫn giữ history.
* Q07: phiên HUY không Start lại trên lịch cũ; lịch thay thế đúng version/slot thì Start được, phiên HUY và FK cũ còn nguyên.
* Q08: không có gói hoặc Membership HET_HAN vẫn Start lịch hợp lệ, Save Set và Complete được; không usage/activation. Gym/AI/PT Chat/buổi trực tiếp vẫn theo entitlement tương ứng.
* Save Set.
* Retry request.
* Complete Workout.
* Không cho sửa Session đã hoàn thành.
* AI không sửa Session hoàn thành.
* PT không sửa Session hoàn thành.
* PT tạo Proposal nhưng Member chưa xác nhận: Plan chính thức chưa đổi.
* Member xác nhận PT Proposal: tạo phiên bản, giữ nguyên lịch sử.
* Member từ chối: không đổi Plan.
* Ghi chú tư vấn: không thay thế dữ liệu kê tập hoặc kết quả thực tế.
* Sửa Workout Plan không làm đổi Workout History.
* Workout History vẫn tồn tại khi Membership hết hạn.

---

# 53. AI Test

Phải test:

* Structured Output hợp lệ.
* JSON lỗi.
* Exercise ID không tồn tại.
* Exercise tồn tại nhưng ngoài Candidate.
* Sai Equipment.
* `so_lan_lap_toi_thieu > so_lan_lap_toi_da`.
* thiếu thông tin.
* yêu cầu mâu thuẫn.
* không có phương án hợp lệ.
* LLM Timeout.
* Q03: request hợp lệ giữ một lượt; provider retry không thêm quota; lỗi kỹ thuật trả đúng một lượt, retry trả quota không trả hai lần.
* Q03: provider lỗi sau activation không rollback đồng hồ; request bị từ chối trước chấp nhận không quota/activation; không đếm message cùng request.
* Q09: Proposal AI/PT đúng/quá 24 giờ mặc định không Apply; preview/retry không gia hạn.
* Q11: nhiều hàng dụng cụ là AND; thiếu một dụng cụ bị từ chối, không tự hiểu OR.
* LLM trả nội dung ngoài Scope.
* Proposal hết hạn.
* Proposal của người khác.
* Workout Plan thay đổi trước khi Apply.
* Confirm hai lần.
* AI cố sửa Workout đã hoàn thành.
* AI chọn Exercise không tồn tại.
* Yêu cầu AI trả phí đầu tiên hợp lệ kích hoạt kỳ, không cần chờ check-in.
* Đã kích hoạt bằng PT/QR thì dùng AI không reset.
* Không được dùng quyền AI của kỳ tương lai để vượt hàng đợi.

---

# 54. Realtime Chat Test

Phải test:

* Member gửi PT hợp lệ.
* Member gửi PT không thuộc quan hệ.
* PT đọc conversation không thuộc quyền.
* reconnect.
* phân trang.
* gửi message retry.
* message không bị nhân đôi nếu client retry.
* Chat PT với quota buổi bằng 0 hoặc đã dùng hết: vẫn gửi được nếu cờ Chat/kỳ/phân công hợp lệ.
* Tin Member kích hoạt kỳ: đúng một usage TRO_CHUYEN_HUAN_LUYEN, một mốc activation, tin và outbox cùng transaction.
* Gửi tiếp 20 tin: thêm tin nhưng không thêm usage Chat, không trừ buổi PT.
* Kỳ đã kích hoạt bằng AI/QR/buổi PT: tin Chat đầu tiên không tạo usage.
* Hai tin đầu đồng thời và Chat tranh kích hoạt với AI/QR: chỉ một nguồn kích hoạt; retry tin đầu trả lại tin/usage cũ.
* Tin PT chủ động, đọc/subscribe/mở màn hình không kích hoạt; lỗi lưu tin/outbox rollback nguồn kích hoạt của chính transaction đó.
* Mỗi tin sau vẫn kiểm tra quyền hiện thời, từ chối khi kỳ hết hạn hoặc mất phân công; không mượn Chat của kỳ tương lai.
* Q05: Member đọc Chat cũ sau đổi PT/hết Membership được phép; gửi mới vào hội thoại PT cũ hoặc khi hết quyền bị từ chối.
* Q05: PT mới không đọc hội thoại PT cũ; PT cũ không gửi sau hết phân công. Quay lại PT cũ bằng phân công mới phải dùng hội thoại mới; tin cũ không bị chuyển/xóa.

---

# 54.1. PT Usage Test

Phải test:
- PT đúng quan hệ, snapshot tổng buổi > 0 và còn lượt: được ghi nhận buổi trực tiếp, không phụ thuộc cờ Chat.
- PT không phụ trách Member hoặc snapshot tổng buổi = 0: từ chối dù Chat PT được bật.
- Kỳ hết hạn hoặc hết lượt: không trừ và không mượn lượt kỳ sau.
- Chỉ buổi HOAN_THANH mới trừ một lượt.
- Member tự gửi request xác nhận hoàn thành: từ chối.
- Gửi tin nhắn không trừ lượt PT.
- Retry cùng buổi hoặc hai request cùng xác nhận: chỉ một bản ghi sử dụng và một lượt bị trừ.
- Hai buổi khác nhau cùng tranh lượt cuối: không cho số lượt âm.
- Lượt cũ hết hiệu lực cùng kỳ; không carry-over sang kỳ mới.
- Q06: PT xác nhận sau khi kỳ của buổi hết hạn bị từ chối; không backdate, không lấy quota kỳ sau và không tạo correction.
- Mỗi bản ghi truy được kỳ, PT, thời điểm và nguồn thao tác.
- Nếu mở quyền hỗ trợ cho Admin/Receptionist, phải test quyền cụ thể và audit.

# 54.2. PT Proposal Test

Phải test:
- PT tạo đề xuất cho Member đang phụ trách.
- PT không thuộc quan hệ: không tạo, đọc hoặc áp dụng đề xuất.
- Tạo mới, đổi template/ngày/bài/sets/reps chưa xác nhận: không đổi Plan chính thức.
- Member đúng chủ sở hữu xác nhận: revalidate và tạo version.
- Member từ chối: giữ nguyên Plan.
- Proposal của người khác, đã xử lý hoặc không còn hợp lệ: không áp dụng sai.
- Plan thay đổi hoặc quan hệ PT không còn hợp lệ trước xác nhận: xử lý xung đột/từ chối.
- Xác nhận hai lần hoặc đồng thời: chỉ áp dụng một lần.
- Ghi chú không cần xác nhận nhưng không được sửa Plan hoặc Session hoàn thành.
- AI/PT cùng đề xuất từ một version: áp dụng đề xuất đầu không cho đề xuất cũ ghi đè phiên bản mới.
- Q13: PT Proposal vẫn hợp lệ khi Member không có Chat, tổng buổi bằng 0/hết quota hoặc không có active Membership, miễn phân công hợp lệ; không trừ lượt/usage/activation.
- Q13: phân công nguồn kết thúc trước confirm thì không Apply, chuyển XUNG_DOT; không dùng một phân công mới để cứu Proposal cũ.

---

# PHẦN XVIII – ĐỊNH NGHĨA HOÀN THÀNH

# RULE CODE 20 – Definition of Done

Một chức năng KHÔNG được coi là hoàn thành chỉ vì:

> UI chạy.

Phải xem xét đầy đủ:

```text
UI
+
API
+
BUSINESS LOGIC
+
AUTHORIZATION
+
VALIDATION
+
DATABASE
+
ERROR HANDLING
+
TEST
```

Nếu liên quan dữ liệu quan trọng:

```text
+
TRANSACTION
+
IDEMPOTENCY
+
CONCURRENCY
+
AUDIT
```

---

# PHẦN XIX – QUY TRÌNH TRƯỚC KHI CODE

# 55. Trước mỗi chức năng phải xác định

Trước khi viết code một chức năng lớn, Agent phải xác định:

## Actor

Ai sử dụng?

## Use Case

Người dùng muốn làm gì?

## Business Rule

Điều kiện nào phải đúng?

## Database

Đọc/ghi bảng nào?

## API

Endpoint nào?

## Authorization

Ai có quyền?

## Validation

Kiểm tra gì?

## Failure Case

Có thể lỗi ở đâu?

## Concurrency

Có hai request đồng thời không?

## Idempotency

Client/Webhook có thể retry không?

## Transaction

Có nhiều thao tác Database phải thành công cùng nhau không?

## Test

Test happy path và edge case nào?

Chỉ sau khi xác định các nội dung trên mới bắt đầu triển khai.

---

# PHẦN XX – QUY TẮC KHI AI AGENT CODE

Khi tôi yêu cầu bạn viết hoặc sửa code Smart Fitness Platform:

1. Đọc `.fitness-rules/PROJECT_CORE.md`, tra cứu module quy tắc tương ứng qua `.fitness-rules/RULE_INDEX.md` và đọc Task Packet được giao; không mặc định đọc toàn bộ file canonical nếu không có yêu cầu leo thang (escalation).
2. Không thay đổi Business Rule.
3. Không tự mở rộng Scope.
4. Database dùng tiếng Việt không dấu.
5. Database dùng snake_case.
6. Không viết tắt tên bảng/cột; dùng đúng tên chính thức trong từ điển dữ liệu và ERD theo RULE CODE 04.
7. Hàm nghiệp vụ Frontend dùng tiếng Việt không dấu.
8. Hàm Frontend dùng camelCase.
9. Biến nghiệp vụ FE ưu tiên tiếng Việt không dấu.
10. Không dịch API chính thức của framework.
11. Hàm nghiệp vụ phải có comment.
12. Comment phải mô tả mục đích và cách hoạt động.
13. Không viết comment chỉ dịch code.
14. Backend phải Authorization.
15. Không tin Frontend.
16. Không tin LLM.
17. Payment phải kiểm tra Webhook.
18. Payment phải Idempotent.
19. Kỳ đầu Membership bắt đầu tại lần sử dụng quyền lợi trả phí đầu tiên hợp lệ (AI/PT/check-in); mọi quyền lợi của kỳ dùng chung thời hạn.
20. Gia hạn phải bảo toàn thời gian còn dư bằng các kỳ nối tiếp, giữ snapshot riêng; quyền lợi mới không có hiệu lực trước lượt.
21. Quyền Chat PT/buổi PT trực tiếp phụ thuộc snapshot kỳ và phân công; lượt buổi không carry-over. PT Proposal chỉ cần phân công hợp lệ theo Q13.
22. Membership hết hạn vẫn xem Workout History.
23. Workout Session hoàn thành không được sửa.
24. AI không được sửa dữ liệu lịch sử đã hoàn thành.
25. Workout Plan và Workout History phải tách.
26. AI phải dùng Structured Output.
27. AI phải tạo Proposal.
28. User phải Confirm Proposal.
29. Apply Proposal phải Revalidate.
30. Các thao tác quan trọng phải xem xét Transaction.
31. Các request có khả năng gửi lại phải xem xét Idempotency.
32. Phải có Test Case.
33. Khi hoàn thành phải báo các file đã tạo/sửa.
34. Phải mô tả các hàm quan trọng đã tạo.
35. Phải mô tả Database liên quan.
36. Phải mô tả API liên quan.
37. Phải mô tả Validation.
38. Phải mô tả Error Case.
39. Phải mô tả Test đã chạy.
40. Không được chỉ trả lời "Đã hoàn thành".
41. PT tạo/sửa kế hoạch thông qua Proposal và Member xác nhận; ghi chú tư vấn không được sửa dữ liệu kế hoạch.
42. PT xác nhận buổi hoàn thành mới trừ lượt; retry không trừ hai lần.
43. Không tách đồng hồ Gym/AI/PT và không kích hoạt gói chỉ-online ngay từ thanh toán.
44. Không dùng quyền của kỳ mua sau để vượt hàng đợi.
45. Chat PT và quota buổi PT trực tiếp là hai quyền độc lập, lưu riêng trong catalog và snapshot kỳ.
46. Cấp lại Role cập nhật hàng hiện tại; lịch sử thu hồi/cấp lại lưu audit cùng transaction, không thêm bảng lịch sử Role.
47. Workout Mode chỉ bắt đầu từ lịch có sẵn; phien_tap.buoi_tap_du_kien_id NOT NULL; Free Workout thuộc FUTURE DEVELOPMENT.
48. Chỉ tin Chat Member thực sự kích hoạt kỳ tạo usage Chat; tin sau vẫn kiểm tra quyền nhưng không tạo usage/counter tin nhắn.
49. Tuân thủ toàn bộ Q01–Q13 đã chốt ở các phần nghiệp vụ; không coi chúng là câu hỏi mở. Workout và PT Proposal không phụ thuộc Membership theo Q08/Q13; không hard-delete history theo Q12.

---

# PHẦN XXI – KHI AGENT HOÀN THÀNH MỘT MODULE

Agent phải báo cáo theo format:

```text
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
```

---

# PHẦN XXII – ĐIỂM NỔI BẬT KHI BẢO VỆ

Khi phát triển dự án luôn ưu tiên chứng minh được:

## 1. Hybrid AI

```text
Rule Engine + LLM
```

LLM không tự quyết định toàn bộ.

---

## 2. Controlled AI

```text
AI
↓
Structured Output
↓
Validation
↓
Proposal
↓
Preview
↓
User Confirmation
↓
Revalidation
↓
Apply
```

---

## 3. Workout Data Integrity

```text
Plan thay doi
!=
History thay doi
```

Workout Session hoàn thành là dữ liệu lịch sử bất biến.

---

## 4. Payment và Membership Consistency

Webhook gửi nhiều lần:

> Không cấp Membership nhiều lần.

Gia hạn:

> Không mất thời gian còn dư; quyền lợi từng kỳ nối tiếp vẫn tách riêng.

Kích hoạt:

> Dùng quyền lợi trả phí đầu tiên bắt đầu một thời hạn chung; AI/PT/QR đồng thời không kích hoạt hai lần.

---

## 5. QR Concurrency

Hai thiết bị quét cùng QR:

> Chỉ một Check-in thành công.

---

## 6. Resource Authorization

PT không thể đọc Member không thuộc quyền.

Member không thể thay ID để đọc dữ liệu người khác.

---

## 7. Realtime PT Support

Member có thể giao tiếp realtime với PT thật.

AI:

> Không thay thế PT.

---

# PHẦN XXIII – NGUYÊN TẮC THIẾT KẾ CUỐI CÙNG

Hệ thống phải ưu tiên:

> **Business Rule đúng hơn số lượng chức năng.**

> **Dữ liệu đúng hơn giao diện nhiều.**

> **Test tốt hơn CRUD nhiều.**

> **AI có kiểm soát hơn AI tự do.**

> **Workout History phải đáng tin cậy.**

> **Payment phải nhất quán.**

> **Authorization phải nằm ở Backend.**

> **AI chỉ được đề xuất, không được tự ý thay đổi dữ liệu chính thức.**

---

# KẾT LUẬN DỰ ÁN

Smart Fitness Platform trong phạm vi khóa luận là:

> **Hệ thống quản lý hội viên phòng gym một chi nhánh, tích hợp gói tập, payOS, QR Check-in, quản lý quá trình tập luyện, theo dõi tiến độ, hỗ trợ Personal Trainer qua Realtime Chat và sử dụng Hybrid Rule Engine + LLM để hỗ trợ lập và điều chỉnh Workout Plan có kiểm soát.**

Hệ thống không tập trung vào việc tạo nhiều màn hình quản trị.

Giá trị chính nằm ở:

* nghiệp vụ Membership;
* Payment consistency;
* QR;
* Workout Data Model;
* Workout History;
* PT;
* Realtime;
* AI có kiểm soát;
* Structured Output;
* Validation;
* Proposal Workflow;
* Authorization;
* Transaction;
* Idempotency;
* Edge Case Testing.

Mọi thiết kế và code sau này phải phục vụ những mục tiêu trên.

**Không tự ý thay đổi các quy tắc trong MASTER PROMPT nếu chưa có yêu cầu mới từ chủ dự án.**
