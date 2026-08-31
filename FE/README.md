# Smart Fitness Web

Vue 3 + Vite SPA dành cho Admin, Receptionist và PT. Foundation FE-0 hiện có Auth, session restore, role/actor guard, public/error layouts, responsive shell, shared form/query components, Vitest và ESLint. Các màn nghiệp vụ FE-1 trở đi chưa thuộc Foundation này. Đọc [PROJECT_RULES.md](../PROJECT_RULES.md) và [Plan V2](../docs/VUE_WEB_IMPLEMENTATION_PLAN_V2.md) trước khi sửa code.

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
- Trạng thái thực thi và gate gần nhất: `docs/VUE_WEB_COMPLETION_CHECKPOINT.md`.
- Không chứa secret trong biến `VITE_*`. Không đưa `.env`, `node_modules/`, `dist/` lên Git.
