# Smart Fitness Web

Vue 3 + Vite SPA dành cho Admin, Receptionist và PT. FE-0 Foundation có Auth, session restore, role/actor guard, public/error layouts, responsive shell, shared form/query components, Vitest và ESLint. FE-1 Admin Foundation đã có Dashboard, danh sách/chi tiết tài khoản, Hội viên và Nhân viên lễ tân theo Account contract. FE-2 trở đi chưa triển khai; đọc [PROJECT_RULES.md](../PROJECT_RULES.md) và [Plan V2](../docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md) trước khi sửa code.

```powershell
npm ci
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
npm run dev
npm run test
npm run lint
npm run build
```

- Router/guard: `src/router/index.js`, `src/router/bao_ve_tuyen_duong.js`.
- Pinia Auth Store: `src/stores/xac_thuc.store.js`; token/actor chỉ lưu trong `sessionStorage`.
- Axios: single client tại `src/services/api.js`, dùng `VITE_API_BASE_URL`, Bearer snapshot và normalized error.
- Alias `@` trỏ tới `src/`.
- Foundation pages: ba actor login, chọn vai trò, quên/đặt lại mật khẩu, 403 và 404.
- FE-1 Admin pages: `/admin/bang-dieu-khien`, `/admin/tai-khoan`, `/admin/hoi-vien` và `/admin/nhan-vien-le-tan`, gồm các màn chi tiết theo route tương ứng.
- Chưa triển khai: PT management, catalog, Payment, Chat, Workout và các portal nghiệp vụ Receptionist; các API blocker được ghi trong checkpoint.
- Trạng thái thực thi và gate gần nhất: `docs/VUE_WEB_COMPLETION_CHECKPOINT.md`.
- Không chứa secret trong biến `VITE_*`. Không đưa `.env`, `node_modules/`, `dist/` lên Git.

## Cấu trúc chức năng Vue

Mỗi màn hình được đặt trong một feature folder theo vai trò. Các màn FE-0 cũ vẫn giữ cấu trúc component legacy; màn FE-1 mới dùng tên file tiếng Việt không dấu theo Plan V2:

```text
src/pages/admin/
├── bang_dieu_khien/
├── tai_khoan/
├── hoi_vien/
└── nhan_vien_le_tan/
```

- Logic điều khiển riêng của màn hình đặt trong chính `index.vue`.
- Thành phần dùng chung đặt tại `components/dung_chung` hoặc `components/xac_thuc`.
- Gọi API, Auth Store và logic dùng chung vẫn giữ ở `services`, `stores`, `composables` để không nhân bản luồng xác thực.
- Business rule, authorization, payment, membership và dữ liệu quan trọng vẫn do Backend làm authority.
