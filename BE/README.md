# Smart Fitness Backend

Laravel REST API dùng chung cho Vue Web và React Native. Backend chạy PHP 8.4+, Laravel 13.29 và MariaDB 10.4.32/InnoDB. Trước mọi thay đổi phải đọc toàn bộ [PROJECT_RULES.md](../PROJECT_RULES.md) và [AGENTS.md](../AGENTS.md).

## Module hiện có

- Bearer Auth, register/reset password, Account/Role Admin;
- Package/Exercise/Workout Template catalog;
- Profile, Member preferences, Membership snapshot/queue/lifecycle;
- payOS order/webhook, QR check-in;
- PT assignment, direct session, note/proposal và private Reverb Chat;
- Workout Plan Version, schedule, session/history và Progress;
- Gemini structured AI Proposal + member-confirmed Apply;
- Admin basic Dashboard.

API contract: [docs/BACKEND_API_CONTRACT.md](../docs/BACKEND_API_CONTRACT.md).

## Thiết lập local

```powershell
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve --host=127.0.0.1 --port=8000
```

`DatabaseSeeder` trong `local/testing` cần checkout Exercise Dataset tại `../.tmp/exercises-dataset`; xem [THIRD_PARTY_EXERCISE_DATASET.md](../docs/THIRD_PARTY_EXERCISE_DATASET.md). Không chạy migration/seed/test vào database có dữ liệu cần giữ.

## Runtime phụ trợ

- Gemini: `AI_PROVIDER=gemini`, model qua `AI_MODEL`, key Backend-only qua `GEMINI_API_KEY`.
- Reverb: chạy `php artisan reverb:start` và cấu hình private-channel credentials.
- Chat retry: chạy `php artisan schedule:work`; scheduler gọi `pt-chat:retry-outbox` mỗi phút.
- Production preflight: trước deploy chạy `php artisan smart-fitness:preflight --no-network`. Lệnh yêu cầu `APP_ENV=production`, `APP_DEBUG=false`, Queue khác `sync`, Gemini và Reverb được cấu hình; không in API key/secret. Chỉ dùng `--external` trên staging khi đã được phép gọi Gemini/Reverb.
- payOS: cấu hình ba credential Backend-only và URL return/cancel.

Không có real network call trong automated tests; Gemini dùng `Http::fake`, payOS dùng gateway fake/fixture.

## Test an toàn

PHPUnit chỉ chạy trên schema MariaDB cô lập đã khai báo đồng thời qua
`DB_DATABASE` và `SMART_FITNESS_TEST_DATABASE`. Local mặc định dùng
`smart_fitness_test`; CI dùng `smart_fitness_ci_test`. Không dùng database
development `smart_fitness` hay bất cứ schema có dữ liệu cần giữ.

```powershell
$env:APP_ENV='testing'
$env:DB_DATABASE='smart_fitness_test'
$env:SMART_FITNESS_TEST_DATABASE='smart_fitness_test'
php artisan migrate --force
php artisan db:seed --force
php artisan test
php artisan test --order-by=random --random-order-seed=20260830
vendor/bin/pint --test
composer validate --strict
composer audit
composer check-platform-reqs
```

Health endpoint: `/up`. Source Backend không có pipeline Node/Vite riêng.
