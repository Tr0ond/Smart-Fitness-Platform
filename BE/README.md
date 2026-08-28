# Smart Fitness Backend

Laravel REST API skeleton dùng chung cho Vue Web và React Native. Đọc [PROJECT_RULES.md](../PROJECT_RULES.md) trước khi sửa code và xem [README gốc](../README.md) để cài môi trường/chạy dự án.

- PHP 8.4+, Composer 2, cấu hình Laravel/PDO MySQL-compatible tới MariaDB 10.4.32 trong `.env.example`.
- `routes/api.php` đã đăng ký, hiện chưa có endpoint nghiệp vụ.
- `/` là trang skeleton, `/up` là health mặc định Laravel.
- Không có authentication, User model, migration hoặc dữ liệu seed nghiệp vụ.
- Session/cache dùng file, queue dùng `sync`; không cần MariaDB để kiểm tra skeleton.
- Không cài dependency Node hoặc chạy migration ở bước này.

```powershell
php artisan --version
php artisan test
composer validate --strict
composer check-platform-reqs
php artisan serve --host=127.0.0.1 --port=8000
```

Nếu máy hiện tại vẫn dùng PHP XAMPP 8.0 trên PATH, sử dụng PHP portable theo hướng dẫn README gốc.
