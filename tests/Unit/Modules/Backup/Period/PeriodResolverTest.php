<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Backup\Period;

use BC\Modules\Backup\Period\PeriodResolver;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PeriodResolver::class)]
final class PeriodResolverTest extends TestCase {
    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function provideDates(): iterable {
        yield 'ordinary Tuesday' => ['2026-07-21', ['daily']];
        yield 'Sunday' => ['2026-07-26', ['daily']];
        yield 'Monday' => ['2026-07-27', ['daily', 'weekly']];
        yield '1st, not Monday' => ['2026-07-01', ['daily', 'monthly']];
        yield 'Monday the 1st' => ['2026-06-01', ['daily', 'weekly', 'monthly']];
        yield 'Jan 1, not Monday' => ['2026-01-01', ['daily', 'monthly', 'yearly']];
        yield 'Jan 1 on Monday' => ['2029-01-01', ['daily', 'weekly', 'monthly', 'yearly']];
        yield 'Jan 2' => ['2026-01-02', ['daily']];
        yield 'Feb 1' => ['2026-02-01', ['daily', 'monthly']];
        yield 'Nov 1' => ['2026-11-01', ['daily', 'monthly']];
        yield 'Jan 11' => ['2026-01-11', ['daily']];
        yield 'Jan 10' => ['2026-01-10', ['daily']];
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('provideDates')]
    public function testResolve(string $date, array $expected): void {
        self::assertSame($expected, new PeriodResolver()->resolve(new DateTimeImmutable($date . ' 04:00:00')));
    }
}
