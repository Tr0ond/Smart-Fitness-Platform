<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Tests\Support\TestDatabaseGuard;

abstract class TestCase extends BaseTestCase
{
    private static bool $coSoDuLieuNenDaDuocTao = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (self::$coSoDuLieuNenDaDuocTao) {
            return;
        }

        TestDatabaseGuard::damBaoDatabaseHienTai();

        if (Artisan::call('db:seed', ['--force' => true]) !== 0) {
            throw new RuntimeException('Unable to seed the isolated PHPUnit database.');
        }

        self::$coSoDuLieuNenDaDuocTao = true;
    }
}
