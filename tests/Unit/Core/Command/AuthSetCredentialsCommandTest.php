<?php

declare(strict_types=1);

namespace BC\Tests\Unit\Core\Command;

use ApiPlatform\Exception\BadRequestException;
use BC\Core\Command\AuthSetCredentialsCommand;
use BC\Tests\Fixture\Auth\ApiAuthStub;
use BC\Tests\Support\BufferedOutput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Runway\Console\Input\Input;

#[CoversClass(AuthSetCredentialsCommand::class)]
final class AuthSetCredentialsCommandTest extends TestCase {
    public function testDefinition(): void {
        $command = new AuthSetCredentialsCommand(new ApiAuthStub());

        self::assertSame('auth:set-credentials', $command->getName());
        self::assertSame('Set the login and the password of the admin area (ends all the sessions)', $command->getDescription());
        self::assertSame([], $command->getParameters(), 'The password is never an argument.');
    }

    public function testCredentialsAreSet(): void {
        $auth = new ApiAuthStub();
        $output = new BufferedOutput(['  admin  ', 'correct horse', 'correct horse']);

        self::assertSame(0, new AuthSetCredentialsCommand($auth)->run(new Input(), $output));

        self::assertSame([['admin', 'correct horse']], $auth->credentials);
        self::assertSame(['Login', 'Password (at least 10 characters)', 'Password again'], $output->questions);
        self::assertSame([], $output->rejections);
        self::assertSame("The credentials are set, all the sessions are ended.\n", $output->stdout);
        self::assertSame('', $output->stderr);
    }

    public function testInvalidAnswersAreAskedAgain(): void {
        $auth = new ApiAuthStub();
        $output = new BufferedOutput([
            '   ',
            'admin',
            'short',
            // 9 characters, 18 bytes.
            'ппппппппп',
            'пппппппппп',
            'other password',
            'пппппппппп',
        ]);

        self::assertSame(0, new AuthSetCredentialsCommand($auth)->run(new Input(), $output));

        self::assertSame([['admin', 'пппппппппп']], $auth->credentials);
        self::assertSame(
            ['The login is empty.', 'The password is too short.', 'The password is too short.', 'The passwords do not match.'],
            $output->rejections
        );
    }

    public function testRefusedCredentials(): void {
        $auth = new ApiAuthStub(setCredentialsException: new BadRequestException('The login is empty.'));
        $output = new BufferedOutput(['admin', 'long enough', 'long enough']);

        self::assertSame(1, new AuthSetCredentialsCommand($auth)->run(new Input(), $output));

        self::assertSame("The login is empty.\n", $output->stderr);
        self::assertSame('', $output->stdout);
    }
}
