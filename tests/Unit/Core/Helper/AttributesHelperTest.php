<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Core\Helper;

use BC\Core\Helper\AttributesHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AttributesHelper::class)]
final class AttributesHelperTest extends TestCase {
    public function testAttributes(): void {
        self::assertSame(
            'class="a b" hidden data-x=""',
            new AttributesHelper()->getAttributesAsString(['class' => 'a b', 'hidden' => null, 'data-x' => ''])
        );
    }

    public function testEmpty(): void {
        self::assertSame('', new AttributesHelper()->getAttributesAsString([]));
    }

    public function testNamesAndValuesAreEscaped(): void {
        self::assertSame(
            'title="&quot;&gt;&lt;script&gt;alert(&#039;x&#039;)&lt;/script&gt;&amp;" a&quot;b',
            new AttributesHelper()->getAttributesAsString([
                'title' => "\"><script>alert('x')</script>&",
                'a"b'   => null,
            ])
        );
    }
}
