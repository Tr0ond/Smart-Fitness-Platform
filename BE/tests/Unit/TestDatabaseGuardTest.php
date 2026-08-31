<?php

declare(strict_types=1);

namespace Tests\Unit;

use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TestDatabaseGuard;
use Tests\TestCase;

class TestDatabaseGuardTest extends TestCase
{
    public function test_accepts_local_test_database(): void
    {
        TestDatabaseGuard::damBaoGiaTri(
            'testing',
            'smart_fitness_test',
            'smart_fitness_test',
            'smart_fitness_test',
        );

        $this->assertTrue(true);
    }

    public function test_accepts_ci_test_database(): void
    {
        TestDatabaseGuard::damBaoGiaTri(
            'testing',
            'smart_fitness_ci_test',
            'smart_fitness_ci_test',
            'smart_fitness_ci_test',
        );

        $this->assertTrue(true);
    }

    #[DataProvider('giaTriKhongAnToan')]
    public function test_rejects_unsafe_or_inconsistent_configuration(
        string $appEnv,
        string $databaseCauHinh,
        string $databaseThucTe,
        string $databaseTestDuocCauHinh,
    ): void {
        $this->expectException(LogicException::class);

        TestDatabaseGuard::damBaoGiaTri(
            $appEnv,
            $databaseCauHinh,
            $databaseThucTe,
            $databaseTestDuocCauHinh,
        );
    }

    /** @return array<string, array{string, string, string, string}> */
    public static function giaTriKhongAnToan(): array
    {
        return [
            'development database' => ['testing', 'smart_fitness', 'smart_fitness', 'smart_fitness'],
            'non-testing environment' => ['local', 'smart_fitness_test', 'smart_fitness_test', 'smart_fitness_test'],
            'configured database mismatch' => ['testing', 'smart_fitness_test', 'smart_fitness_ci_test', 'smart_fitness_test'],
            'missing approved database' => ['testing', 'smart_fitness_test', 'smart_fitness_test', ''],
            'name without test suffix' => ['testing', 'smart_fitness_isolated', 'smart_fitness_isolated', 'smart_fitness_isolated'],
        ];
    }
}
