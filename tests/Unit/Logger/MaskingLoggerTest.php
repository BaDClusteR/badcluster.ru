<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Logger;

use BC\Logger\MaskingLogger;
use BC\Tests\Support\LoggerSpy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runway\Logger\Enum\LogLevelEnum;
use stdClass;

#[CoversClass(MaskingLogger::class)]
final class MaskingLoggerTest extends TestCase {
    /** The keys of config/services.yaml. */
    private const array SENSITIVE_KEYS = ['login', 'password', 'token', 'X-BC-Token', 'args'];

    private LoggerSpy $inner;

    private MaskingLogger $logger;

    protected function setUp(): void {
        $this->inner = new LoggerSpy();
        $this->logger = new MaskingLogger($this->inner, self::SENSITIVE_KEYS);
    }

    /**
     * @return iterable<array{string}>
     */
    public static function provideLevels(): iterable {
        yield ['debug'];
        yield ['info'];
        yield ['deprecated'];
        yield ['warning'];
        yield ['error'];
        yield ['critical'];
        yield ['emergency'];
    }

    #[DataProvider('provideLevels')]
    public function testEveryLevelIsMasked(string $level): void {
        $this->logger->{$level}('password=message is not masked', ['password' => 'secret', 'user' => 'me']);

        self::assertSame(
            [['level' => $level, 'message' => 'password=message is not masked', 'context' => ['password' => '***', 'user' => 'me']]],
            $this->inner->records
        );
    }

    public function testEmptyContext(): void {
        $this->logger->error('Message');

        self::assertSame([], $this->inner->records[0]['context']);
    }

    public function testKeysAreCaseInsensitive(): void {
        $this->logger->error('Request', [
            'headers' => ['x-bc-token' => 'abc', 'X-BC-TOKEN' => 'def', 'Content-Type' => 'application/json'],
            'Login'   => 'admin',
            'TOKEN'   => 'xyz',
        ]);

        self::assertSame(
            [
                'headers' => ['x-bc-token' => '***', 'X-BC-TOKEN' => '***', 'Content-Type' => 'application/json'],
                'Login'   => '***',
                'TOKEN'   => '***',
            ],
            $this->inner->records[0]['context']
        );
    }

    public function testOnlyWholeKeysAreSensitive(): void {
        $context = ['password_hint' => 'x', 'tokens' => 'y', 'my_login' => 'z', 0 => 'login', 'arg' => 1];

        $this->logger->warning('Message', $context);

        self::assertSame($context, $this->inner->records[0]['context']);
    }

    public function testWholeValueOfSensitiveKeyIsMasked(): void {
        $this->logger->error('Trace', [
            'trace' => [
                ['function' => 'authenticate', 'args' => ['admin', 'secret']],
                ['function' => 'run', 'args' => []],
            ],
        ]);

        self::assertSame(
            ['trace' => [['function' => 'authenticate', 'args' => '***'], ['function' => 'run', 'args' => '***']]],
            $this->inner->records[0]['context']
        );
    }

    public function testScalarValuesAreKept(): void {
        $context = ['int' => 5, 'float' => 1.5, 'bool' => false, 'null' => null, 'text' => 'password: x'];

        $this->logger->info('Message', $context);

        self::assertSame($context, $this->inner->records[0]['context']);
    }

    public function testObjectsBecomeMaskedArrays(): void {
        $object = new stdClass();
        $object->login = 'admin';
        $object->name = 'Name';
        $object->nested = new stdClass();
        $object->nested->password = 'secret';

        $this->logger->error('Object', ['user' => $object]);

        self::assertSame(
            ['user' => ['login' => '***', 'name' => 'Name', 'nested' => ['password' => '***']]],
            $this->inner->records[0]['context']
        );
        self::assertSame('admin', $object->login, 'The object is not changed.');
    }

    public function testJsonStringsAreMasked(): void {
        $this->logger->error('Bodies', [
            'request'  => '{"login":"admin","password":"secret","path":"/a/б"}',
            'response' => ' [{"token":"abc"},{"id":1}]',
            'nested'   => '{"data":"{\"password\":\"inner\"}"}',
        ]);

        self::assertSame(
            [
                'request'  => '{"login":"***","password":"***","path":"\/a\/б"}',
                'response' => '[{"token":"***"},{"id":1}]',
                'nested'   => '{"data":"{\"password\":\"***\"}"}',
            ],
            $this->inner->records[0]['context']
        );
    }

    /**
     * @return iterable<array{string}>
     */
    public static function provideNotJsonObjects(): iterable {
        yield 'not JSON' => ['{password: secret}'];
        yield 'JSON scalar' => ['"password"'];
        yield 'number' => ['12345'];
        yield 'text' => ['login=admin&password=secret'];
        yield 'braces inside' => ['text {"password":"x"}'];
        yield 'empty' => [''];
    }

    #[DataProvider('provideNotJsonObjects')]
    public function testOtherStringsAreKept(string $value): void {
        $this->logger->error('Message', ['body' => $value]);

        self::assertSame(['body' => $value], $this->inner->records[0]['context']);
    }

    public function testJsonThatCannotBeEncodedAgainIsKept(): void {
        // A float that json_encode() cannot write back (INF after decoding).
        $value = '{"value": 1e400, "password": "secret"}';

        $this->logger->error('Message', ['body' => $value]);

        self::assertSame(['body' => $value], $this->inner->records[0]['context']);
    }

    public function testMaskingDepthIsLimited(): void {
        $this->logger->error('Deep', ['data' => self::nest(['password' => 'secret'], 15)]);
        $this->logger->error('Deeper', ['data' => self::nest(['password' => 'secret', 'other' => 1], 16)]);

        // Depth 16 (the context is depth 0) is masked, depth 17 is left as is.
        self::assertSame(['data' => self::nest(['password' => '***'], 15)], $this->inner->records[0]['context']);
        self::assertSame(
            ['data' => self::nest(['password' => 'secret', 'other' => 1], 16)],
            $this->inner->records[1]['context']
        );
    }

    public function testObjectsCountForTheDepth(): void {
        $deep = new stdClass();
        $deep->password = 'secret';
        $data = self::nest(['object' => $deep], 14);

        $this->logger->error('Deep object', ['data' => $data]);
        $this->logger->error('Deeper object', ['data' => ['data' => $data]]);

        // The object is at depth 15, its properties at depth 16.
        self::assertSame(['data' => self::nest(['object' => ['password' => '***']], 14)], $this->inner->records[0]['context']);
        self::assertSame(
            ['data' => ['data' => self::nest(['object' => ['password' => 'secret']], 14)]],
            $this->inner->records[1]['context']
        );
    }

    public function testJsonCountsForTheDepth(): void {
        $json = (string)json_encode(self::nest(['password' => 'secret'], 15));

        $this->logger->error('Deep JSON', ['data' => $json]);
        $this->logger->error('Deeper JSON', ['data' => ['data' => $json]]);

        self::assertSame(
            ['data' => (string)json_encode(self::nest(['password' => '***'], 15))],
            $this->inner->records[0]['context']
        );
        self::assertSame(['data' => ['data' => $json]], $this->inner->records[1]['context']);
    }

    public function testLogLevelIsSetOnTheInnerLogger(): void {
        self::assertSame($this->logger, $this->logger->setLogLevel(LogLevelEnum::WARNING));
        self::assertSame(LogLevelEnum::WARNING, $this->inner->logLevel);
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<mixed> The data under $levels "k" keys.
     */
    private static function nest(array $data, int $levels): array {
        for ($i = 0; $i < $levels; $i++) {
            $data = ['k' => $data];
        }

        return $data;
    }
}
