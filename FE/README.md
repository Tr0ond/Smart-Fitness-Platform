# Smart Fitness Web

Vue + Vite skeleton dành cho Admin, Receptionist và PT. Đọc [PROJECT_RULES.md](../PROJECT_RULES.md) trước khi sửa code. Hướng dẫn môi trường chi tiết tại [README gốc](../README.md).

```powershell
npm ci
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
npm run dev
npm run build
```

- Router: `src/router/index.js`.
- Pinia: đăng ký trong `src/main.js`; chưa có store nghiệp vụ.
- Axios: `src/services/api.js`, dùng `VITE_API_BASE_URL`.
- Alias `@` trỏ tới `src/`.
- `layouts/`, `pages/` chỉ có trang skeleton; chưa có chức năng hoặc gọi API.
- Không chứa secret trong biến `VITE_*`. Không đưa `.env`, `node_modules/`, `dist/` lên Git.
