<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Modules\Backup\Uploader\DTO;

use BC\Modules\Backup\Uploader\DTO\UploadResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UploadResult::class)]
final class UploadResultTest extends TestCase {
    public function testOk(): void {
        $result = UploadResult::ok('https://files.example/a.zip', 'rotation failed');

        self::assertTrue($result->success);
        self::assertSame('https://files.example/a.zip', $result->url);
        self::assertSame('rotation failed', $result->error);
    }

    public function testOkWithoutDetails(): void {
        $result = UploadResult::ok();

        self::assertTrue($result->success);
        self::assertNull($result->url);
        self::assertNull($result->error);
    }

    public function testFail(): void {
        $result = UploadResult::fail('Connection refused');

        self::assertFalse($result->success);
        self::assertNull($result->url);
        self::assertSame('Connection refused', $result->error);
    }
}
