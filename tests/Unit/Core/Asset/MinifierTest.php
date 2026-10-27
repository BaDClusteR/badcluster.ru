<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Core\Asset;

use BC\Core\Asset\Minifier\IMinifier;
use BC\Core\Asset\Minifier\MinifierFactory;
use BC\Core\Asset\Minifier\NodeMinifier;
use BC\Core\Asset\Minifier\PhpMinifier;
use BC\Tests\Support\EnvStub;
use BC\Tests\Support\LoggerSpy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[CoversClass(PhpMinifier::class)]
#[CoversClass(NodeMinifier::class)]
#[CoversClass(MinifierFactory::class)]
final class MinifierTest extends TestCase {
    public function testPhpMinifier(): void {
        $minifier = new PhpMinifier();

        self::assertSame('body{color:red}', $minifier->minify("body {\n    color: red;\n}\n/* comment */\n", 'css'));
        self::assertSame('var a=1;function f(){return a}', $minifier->minify("var a = 1;\n// comment\nfunction f() {\n    return a;\n}\n", 'js'));
        self::assertSame("  keep  me  ", $minifier->minify('  keep  me  ', 'svg'));
    }

    public function testNodeMinifierKeepsOtherTypes(): void {
        $logger = new LoggerSpy();

        self::assertSame(' <svg/> ', new NodeMinifier($logger, '/nonexistent/node')->minify(' <svg/> ', 'svg'));
        self::assertSame([], $logger->records, 'Node is not run.');
    }

    public function testFactoryUsesPhpWithoutNode(): void {
        $factory = new MinifierFactory(new EnvStub(['NODE_PATH' => '']), new LoggerSpy());

        self::assertSame('a{b:c}', $factory->minify('a { b: c; }', 'css'));
        self::assertInstanceOf(PhpMinifier::class, self::getMinifier($factory));
    }

    public function testFactoryUsesNode(): void {
        $factory = new MinifierFactory(new EnvStub(['NODE_PATH' => '/usr/local/bin/node']), new LoggerSpy());

        self::assertSame('kept', $factory->minify('kept', 'txt'));

        $minifier = self::getMinifier($factory);
        self::assertInstanceOf(NodeMinifier::class, $minifier);
        self::assertSame('/usr/local/bin/node', new ReflectionProperty(NodeMinifier::class, 'nodePath')->getValue($minifier));
        self::assertSame($minifier, self::getMinifier($factory), 'The minifier is created once.');

        $factory->minify('again', 'txt');
        self::assertSame($minifier, self::getMinifier($factory));
    }

    private static function getMinifier(MinifierFactory $factory): ?IMinifier {
        $minifier = new ReflectionProperty(MinifierFactory::class, 'minifier')->getValue($factory);
        self::assertTrue($minifier === null || $minifier instanceof IMinifier);

        return $minifier;
    }
}
