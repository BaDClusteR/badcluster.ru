<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Core\Scanner;

use BC\Core\Scanner\WidgetClassScanner;
use BC\Tests\Fixture\PathsProviderStub;
use BC\Tests\Fixture\Scanner\FinalWidget;
use BC\Tests\Fixture\Scanner\Nested\AbstractWidget;
use BC\Widget\Page404;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WidgetClassScanner::class)]
final class WidgetClassScannerTest extends TestCase {
    public function testClassesOfTheFolders(): void {
        $scanner = new WidgetClassScanner(new PathsProviderStub(widgetPaths: [
            dirname(__DIR__, 3) . '/Fixture/Scanner',
            dirname(__DIR__, 3) . '/Fixture/Missing',
        ]));

        $classes = $scanner->getWidgetClasses();
        sort($classes);

        self::assertSame([FinalWidget::class, AbstractWidget::class], $classes);
    }

    public function testNoFolders(): void {
        self::assertSame([], new WidgetClassScanner(new PathsProviderStub())->getWidgetClasses());
    }

    public function testWidgetsOfTheSite(): void {
        $classes = new WidgetClassScanner(new PathsProviderStub(widgetPaths: [PROJECT_ROOT . '/src/Widget']))->getWidgetClasses();

        self::assertContains(Page404::class, $classes);
        self::assertContains(\BC\Widget\Page\Page404::class, $classes);
        self::assertNotContains(\BC\Widget\IAssetProvider::class, $classes);
    }
}
