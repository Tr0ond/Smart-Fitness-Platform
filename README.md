# Smart Fitness Platform

Repository hiện tại là gốc dự án (`E:\Fitness` trên máy khởi tạo); không có thư mục `Smart_Fitness` lồng bên trong.

**Trạng thái Backend:** core REST API đã triển khai trên schema 52 bảng/M001–M060, gồm Auth/Role, catalog, Profile, Membership, payOS, QR check-in, PT, Realtime Chat, Workout, Progress, Dashboard và Gemini AI Proposal/Apply. Tra cứu quy tắc phát triển qua [AGENTS.md](AGENTS.md) và [.fitness-rules/RULE_INDEX.md](.fitness-rules/RULE_INDEX.md). FE và Mobile vẫn là skeleton tích hợp, chưa triển khai màn hình nghiệp vụ.

Tài liệu vận hành chính:

- [Backend README](BE/README.md)
- [Backend API contract](docs/BACKEND_API_CONTRACT.md)
- [Backend completion report](docs/thiet_ke_co_so_du_lieu/BACKEND_CORE_COMPLETION_REPORT.md)
- [Database dictionary](docs/thiet_ke_co_so_du_lieu/TU_DIEN_DU_LIEU.md)

## Các project

| Thư mục | Công nghệ đã cài | Vai trò dự kiến |
| --- | --- | --- |
| `BE/` | Laravel 13.29.0, PHP 8.4, MariaDB 10.4.32 / InnoDB qua PDO MySQL-compatible | REST API dùng chung |
| `FE/` | Vue 3.5.42, Vite 8.2.2, Vue Router 5.3.0, Pinia 4.0.3, Axios 1.20.0 | Web cho Admin, Receptionist, PT |
| `Mobile/` | React Native 0.86.3, React 19.2.3, Community CLI 20.2.0, TypeScript 5.9.3 | Ứng dụng Member, ưu tiên Android |

Các phiên bản chính xác và dependency gián tiếp nằm trong `BE/composer.lock`, `FE/package-lock.json`, `Mobile/package-lock.json`. Dùng `composer install` / `npm ci` khi lấy source, không tự cập nhật lockfile.

## Cấu trúc source

```text
Fitness/
├── BE/
│   ├── app/{Http/Controllers,Models,Providers}/
│   ├── bootstrap/
│   ├── config/
│   ├── database/{factories,migrations,seeders}/
│   ├── public/
│   ├── resources/{css,js,views}/
│   ├── routes/{api.php,web.php,console.php}
│   ├── storage/
│   ├── tests/{Feature,Unit}/
│   ├── artisan
│   ├── composer.json
│   ├── composer.lock
│   ├── .env.example
│   └── README.md
├── FE/
│   ├── public/
│   ├── src/
│   │   ├── assets/
│   │   ├── components/
│   │   ├── layouts/
│   │   ├── pages/
│   │   ├── router/
│   │   ├── services/
│   │   ├── stores/
│   │   ├── utils/
│   │   ├── App.vue
│   │   └── main.js
│   ├── .env.example
│   ├── .gitignore
│   ├── index.html
│   ├── package.json
│   ├── package-lock.json
│   ├── vite.config.js
│   └── README.md
├── Mobile/
│   ├── src/{assets,components,screens,navigation,services,stores,hooks,utils,constants}/
│   ├── android/
│   ├── ios/
│   ├── __tests__/
│   ├── App.tsx
│   ├── index.js
│   ├── app.json
│   ├── babel.config.js
│   ├── metro.config.js
│   ├── jest.config.js
│   ├── tsconfig.json
│   ├── package.json
│   ├── package-lock.json
│   └── README.md
├── AGENTS.md
├── PROJECT_RULES.md
├── README.md
├── .gitignore
└── .gitattributes
```

Các thư mục chưa có code chứa `.gitkeep` để Git giữ cấu trúc. `Mobile/ios/` là phần gốc của template React Native; chưa kiểm tra hoặc cấu hình phát hành iOS.

## Môi trường

- PHP **8.4 trở lên trong nhánh 8.x**, Composer 2. Lockfile hiện có dependency yêu cầu PHP 8.4; PHP 8.0 của XAMPP trên máy khởi tạo không dùng để chạy project này.
- Bật các extension PHP thông dụng của Laravel và `pdo_mysql`. `composer check-platform-reqs` kiểm tra yêu cầu của dependency; MariaDB 10.4.32 / InnoDB được sử dụng ở giai đoạn phát triển database sau này.
- Node.js 22.13+ trong nhánh 22, hoặc 24.3+. Môi trường đã kiểm tra: Node 22.20.0, npm 10.9.3.
- Khi build Android native: Android Studio, JDK tương thích Gradle, Android SDK Platform 36, Build Tools 36.0.0, NDK 27.1.12297006; cấu hình `JAVA_HOME` và `ANDROID_HOME`/`android/local.properties`. Các phiên bản Android nằm trong `Mobile/android/build.gradle`.

Tham khảo hướng dẫn chính thức: [Laravel](https://laravel.com/framework/docs/13.x/installation), [Vite](https://vite.dev/guide/), [React Native CLI](https://reactnative.dev/docs/getting-started-without-a-framework), [môi trường React Native](https://reactnative.dev/docs/set-up-your-environment).

### PHP và Composer portable trên máy hiện tại

Đã chuẩn bị PHP 8.4.25 và Composer 2.10.3 trong `.tools/` để kiểm tra, không thay đổi PHP hệ thống. Đây là công cụ cục bộ **không đưa lên Git**. Máy khác cần tự cài PHP/Composer phù hợp.

Nếu `php` trên PATH vẫn trỏ tới XAMPP 8.0, chạy từ gốc repository bằng PowerShell:

```powershell
$phpFitness = (Resolve-Path '.tools/php/php.exe').Path
$composerFitness = (Resolve-Path '.tools/composer/composer.phar').Path
Set-Location BE
& $phpFitness $composerFitness install
& $phpFitness artisan --version
& $phpFitness artisan serve --host=127.0.0.1 --port=8000
```

`BE/.env` và app key đã được tạo cục bộ; không đưa lên Git. Không in hoặc chia sẻ app key.

## Chạy Backend

### Chạy nhanh trên Windows

Sau khi đã cài dependency lần đầu, có thể double-click file `start.bat` tại gốc repository. Script sẽ mở hai cửa sổ riêng cho Laravel Backend và Vue Frontend:

```text
E:\Fitness\start.bat
    ├── Backend:  http://127.0.0.1:8000
    └── Frontend: http://127.0.0.1:5173
```

Script không tự chạy migration, seed hoặc `key:generate`. Nếu là lần thiết lập đầu tiên, cần chuẩn bị `BE/.env`, chạy `composer install` trong `BE/` và `npm ci` trong `FE/` trước.

Lần đầu lấy source, với PHP/Composer phù hợp trên PATH:

```powershell
Set-Location BE
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
php artisan serve --host=127.0.0.1 --port=8000
```

Chỉ tạo key khi thiết lập môi trường lần đầu; không chạy lại `key:generate` tùy tiện trên môi trường đang có dữ liệu.

- Trang skeleton: `http://127.0.0.1:8000/`; health mặc định Laravel: `/up`.
- API nghiệp vụ nằm dưới `/api`; xem contract để biết actor, middleware và idempotency header.
- `.env.example` dùng Laravel/PDO tới MariaDB 10.4.32. Chỉ chạy M001–M060 trên database development/test đã chọn đúng; test phải dùng schema cô lập có tên `smart_fitness_*test*`.
- `DatabaseSeeder` tạo catalog nền, tài khoản demo chỉ trong `local/testing`, và import Exercise Dataset từ checkout cục bộ đã pin commit.
- Gemini key, payOS credentials và Reverb secret chỉ nằm ở Backend environment. Không đưa secret vào `VITE_*`, Mobile hoặc Git.
- PT Chat outbox retry cần Laravel Scheduler: chạy `php artisan schedule:work` khi phát triển hoặc cấu hình cron `schedule:run` ở môi trường triển khai.
- Backend không có pipeline Vite riêng: web client nằm ở `FE/`; Blade chỉ có trang trạng thái tĩnh. Không cần `npm install` trong `BE/`.
- Composer setup/dev không tự chạy migration hoặc cài dependency frontend.

Kiểm tra:

```powershell
php artisan --version
php artisan test
composer validate --strict
composer check-platform-reqs
```

## Chạy Vue Web

Mở terminal riêng từ gốc repository:

```powershell
Set-Location FE
npm ci
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
npm run dev -- --host 127.0.0.1
```

Vite thường chạy tại `http://127.0.0.1:5173`. Kiểm tra production build bằng `npm run build`; xem build bằng `npm run preview`.

Router và Pinia được đăng ký trong `src/main.js`. Axios nằm trong `src/services/api.js`, đọc `VITE_API_BASE_URL` (mặc định `http://127.0.0.1:8000/api`). Chưa gọi API, chưa có store nghiệp vụ hoặc interceptor authentication. Không lưu secret trong biến `VITE_*` vì giá trị được đưa vào trình duyệt. Chưa thêm Tailwind; skeleton dùng CSS tối thiểu.

## Chạy React Native Android

Sau khi cài đầy đủ Android toolchain và mở emulator hoặc kết nối thiết bị có USB debugging:

```powershell
Set-Location Mobile
npm ci
npm start
```

Mở terminal khác trong `Mobile/`:

```powershell
npm run android
```

Metro dùng cổng 8081. Android application ID: `com.smartfitness`. Thư mục `navigation`, `services`, `stores` mới chỉ là vị trí dành sẵn; chưa cài navigation/state/API library hoặc cấu hình `.env` cho Mobile.

Kiểm tra source và dependency mà không cần emulator:

```powershell
npm run typecheck
npm run lint
npm test -- --runInBand
npm ls --depth=0
node node_modules/react-native/cli.js config
```

Kiểm tra Metro bundle Android, chạy từ `Mobile/`:

```powershell
New-Item -ItemType Directory -Force ../.tmp/mobile | Out-Null
node node_modules/react-native/cli.js bundle --platform android --dev false --entry-file index.js --bundle-output ../.tmp/mobile/index.android.bundle --assets-dest ../.tmp/mobile/assets --max-workers 2
```

Bundle JavaScript thành công không đồng nghĩa với build APK hoặc chạy thành công trên thiết bị. Debug keystore trong template chỉ dùng phát triển; chưa cấu hình ký/phát hành production.

## Mốc kiểm tra khởi tạo — 28/08/2026

| Kiểm tra | Kết quả |
| --- | --- |
| Laravel `artisan --version` | Laravel Framework 13.29.0 |
| Laravel tests | 3 tests, 4 assertions, đạt |
| Laravel HTTP và định dạng PHP | `/` và `/up` trả HTTP 200; Pint đạt |
| Composer manifest/lock và platform | `validate --strict`, `check-platform-reqs` đạt |
| Vue production build | `npm run build` đạt |
| Mobile TypeScript / ESLint / Jest | Đạt; 1 Jest test |
| Mobile native configuration | Nhận đúng Android project và autolinking safe-area-context |
| Mobile Metro Android bundle | Đạt |
| Dependency audit | Composer, FE, Mobile: không có lỗ hổng được báo tại thời điểm kiểm tra |
| `PROJECT_RULES.md` | Giữ nguyên, SHA-256 trước/sau trùng nhau |

Đây là evidence lịch sử của bước khởi tạo. Trạng thái Backend mới nhất nằm trong completion report; chưa có nghĩa FE/Mobile nghiệp vụ hoặc Android release build đã hoàn thành.

## Git và cấu hình cục bộ

`.gitignore` bỏ qua dependency, build/cache, `.env`, khóa ký cá nhân, SDK path và `.tools/`, `.tmp/`. Các `.env.example`, ba lockfile, Gradle wrapper và debug keystore mẫu vẫn được giữ trong source. `.gitattributes` chuẩn hóa file text về LF, script Windows về CRLF và đánh dấu file nhị phân. Chưa stage, commit hoặc push.
