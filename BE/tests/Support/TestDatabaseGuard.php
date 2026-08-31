<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Chặn tuyệt đối test và child process chạm nhầm database development.
 *
 * Schema test phải được khai báo rõ qua SMART_FITNESS_TEST_DATABASE. Không
 * suy diễn tên schema từ regex lỏng, vì child process concurrency phải dùng
 * đúng cùng schema với process PHPUnit cha.
 */
final class TestDatabaseGuard
{
    public static function damBaoDatabaseHienTai(): void
    {
        $ketNoi = (string) config('database.default');
        $databaseCauHinh = (string) config("database.connections.{$ketNoi}.database");
        $databaseThucTe = (string) (DB::selectOne('SELECT DATABASE() AS ten_database')->ten_database ?? '');

        if ($ketNoi !== 'mysql') {
            throw new LogicException('Test database guard requires the mysql connection.');
        }

        self::damBaoGiaTri(
            (string) config('app.env'),
            $databaseCauHinh,
            $databaseThucTe,
            self::databaseTestDuocCauHinh(),
        );
    }

    /**
     * Child process phải kế thừa nguyên vẹn APP_ENV, DB_DATABASE và
     * SMART_FITNESS_TEST_DATABASE từ PHPUnit cha trước khi bootstrap Laravel.
     *
     * @param  array<string, string>  $ghiDeMoiTruong
     */
    public static function khoiTaoTienTrinhCon(string $database, array $ghiDeMoiTruong = []): void
    {
        self::damBaoGiaTri(
            (string) getenv('APP_ENV'),
            (string) getenv('DB_DATABASE'),
            $database,
            (string) getenv('SMART_FITNESS_TEST_DATABASE'),
        );

        $bienMoiTruong = array_merge([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => $database,
            'SMART_FITNESS_TEST_DATABASE' => $database,
        ], $ghiDeMoiTruong);

        foreach ($bienMoiTruong as $ten => $giaTri) {
            putenv("{$ten}={$giaTri}");
            $_ENV[$ten] = $giaTri;
        }
    }

    /** @return array<string, string> */
    public static function moiTruongTienTrinhCon(): array
    {
        self::damBaoDatabaseHienTai();

        $moiTruongHienTai = getenv();
        $moiTruong = is_array($moiTruongHienTai) ? $moiTruongHienTai : [];
        $database = self::databaseTestDuocCauHinh();

        return array_merge($moiTruong, [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'mysql',
            'DB_DATABASE' => $database,
            'SMART_FITNESS_TEST_DATABASE' => $database,
        ]);
    }

    public static function damBaoGiaTri(
        string $appEnv,
        string $databaseCauHinh,
        string $databaseThucTe,
        string $databaseTestDuocCauHinh,
    ): void {
        if ($appEnv !== 'testing') {
            throw new LogicException('Test database guard requires APP_ENV=testing.');
        }

        if ($databaseTestDuocCauHinh === '') {
            throw new LogicException('SMART_FITNESS_TEST_DATABASE must be configured for tests.');
        }

        if ($databaseCauHinh !== $databaseTestDuocCauHinh
            || $databaseThucTe !== $databaseTestDuocCauHinh) {
            throw new LogicException('Configured, current, and approved test databases must match exactly.');
        }

        $databaseThuong = strtolower($databaseTestDuocCauHinh);
        if ($databaseThuong === 'smart_fitness'
            || ! str_starts_with($databaseThuong, 'smart_fitness_')
            || ! str_contains($databaseThuong, 'test')) {
            throw new LogicException('The approved database must be an isolated smart_fitness_*test schema.');
        }
    }

    private static function databaseTestDuocCauHinh(): string
    {
        $giaTri = getenv('SMART_FITNESS_TEST_DATABASE');

        return $giaTri === false ? '' : $giaTri;
    }
}
