<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Core\Media;

use BC\Core\Media\Processor\Command\ResizeCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The vipsthumbnail command: every argument is escaped (the command itself is not run).
 */
#[CoversClass(ResizeCommand::class)]
final class ResizeCommandTest extends TestCase {
    public function testArgumentsAreEscaped(): void {
        $command = new ResizeCommandProbe('/static/a b;rm -rf x.jpg', '/static/out $(id).webp', 500, 0, 'Q=80')->command();

        self::assertSame(
            implode(' ', [
                escapeshellarg('vipsthumbnail'),
                escapeshellarg('/static/a b;rm -rf x.jpg'),
                '--size',
                escapeshellarg('500x'),
                '-o',
                escapeshellarg('/static/out $(id).webp[Q=80]'),
            ]),
            $command
        );
    }

    public function testSizeAndNoSaveParameters(): void {
        $command = new ResizeCommandProbe('in.png', 'out.png', 0, 300)->command();

        self::assertStringEndsWith(escapeshellarg('x300') . ' -o ' . escapeshellarg('out.png'), $command);
    }
}

final class ResizeCommandProbe extends ResizeCommand {
    public function command(): string {
        return $this->getCommand();
    }

    protected function getVipsThumbnailPath(): string {
        return 'vipsthumbnail';
    }
}
