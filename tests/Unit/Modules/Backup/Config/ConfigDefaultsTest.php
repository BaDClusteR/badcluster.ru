<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Backup\Config;

use BC\Modules\Backup\Config\BackupConfig;
use BC\Modules\Backup\Config\DatabaseConfig;
use BC\Modules\Backup\Config\StaticConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DatabaseConfig::class)]
#[CoversClass(StaticConfig::class)]
#[CoversClass(BackupConfig::class)]
final class ConfigDefaultsTest extends TestCase {
    public function testDatabaseDefaults(): void {
        $config = new DatabaseConfig();

        self::assertTrue($config->greedy);
        self::assertSame([], $config->includeTables);
        self::assertSame(['geoip'], $config->excludeTables);
    }

    public function testStaticDefaults(): void {
        $config = new StaticConfig('/srv/static');

        self::assertSame('/srv/static', $config->root);
        self::assertFalse($config->greedy);
        self::assertSame(['cringe', 'games', 'media', 'music', 'photos', 'screenshots'], $config->includeFolders);
        self::assertSame([], $config->excludeFolders);
    }

    public function testArchivePrefixDefault(): void {
        self::assertSame(
            'bc-backup',
            new BackupConfig(new DatabaseConfig(), new StaticConfig('/srv/static'), [], '/tmp')->archivePrefix
        );
    }
}
