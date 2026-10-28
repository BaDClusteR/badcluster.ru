<?php

declare(strict_types=1);

namespace BC\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * The keys the logger of the site masks: the credentials and the session of the admin area in any form.
 */
final class LoggerMaskingConfigTest extends TestCase {
    public function testSessionAndCredentialsAreMasked(): void {
        $config = yaml_parse_file(PROJECT_ROOT . '/config/services.yaml');
        self::assertIsArray($config);

        $keys = $config;

        foreach (['services', 'BC\Logger\MaskingLogger', 'arguments', 'sensitiveKeys'] as $key) {
            self::assertIsArray($keys);
            $keys = $keys[$key] ?? null;
        }

        self::assertIsArray($keys);

        foreach (['password', 'login', 'token', 'X-BC-Token', 'api_session', 'cookie', 'HTTP_COOKIE', 'authorization', 'HTTP_AUTHORIZATION', 'args'] as $key) {
            self::assertContains($key, $keys);
        }
    }
}
