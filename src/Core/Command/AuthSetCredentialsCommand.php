<?php

declare(strict_types=1);

namespace BC\Core\Command;

use ApiPlatform\Core\Singleton\Auth;
use ApiPlatform\Core\Singleton\IAuth;
use ApiPlatform\Exception\BadRequestException;
use Runway\Console\Command\ACommand;
use Runway\Console\Exception\InvalidAnswerException;
use Runway\Console\Input\IInput;
use Runway\Console\Output\IOutput;

/**
 * Логин и пароль админки: в таблице options хранится хеш пароля (Argon2id),
 * все текущие сессии завершаются. Пароль спрашивается без эха, в аргументы
 * его не передаём — иначе он останется в истории шелла и в списке процессов:
 *
 *   php console auth:set-credentials
 */
class AuthSetCredentialsCommand extends ACommand {
    public function __construct(
        private readonly IAuth $auth
    ) {
    }

    public function getName(): string {
        return 'auth:set-credentials';
    }

    public function getDescription(): string {
        return 'Set the login and the password of the admin area (ends all the sessions)';
    }

    protected function execute(IInput $input, IOutput $output): int {
        $login = $output->ask('Login', $this->validateLogin(...));
        $password = $output->secret(
            'Password (at least ' . Auth::MIN_PASSWORD_LENGTH . ' characters)',
            $this->validatePassword(...)
        );
        $output->secret(
            'Password again',
            static fn (string $answer): string => $answer === $password
                ? $answer
                : throw new InvalidAnswerException('The passwords do not match.')
        );

        try {
            $this->auth->setCredentials($login, $password);
        } catch (BadRequestException $e) {
            $output->error($e->getMessage());

            return 1;
        }

        $output->success('The credentials are set, all the sessions are ended.');

        return 0;
    }

    private function validateLogin(string $answer): string {
        $answer = trim($answer);

        return $answer !== '' ? $answer : throw new InvalidAnswerException('The login is empty.');
    }

    private function validatePassword(string $answer): string {
        return mb_strlen($answer) >= Auth::MIN_PASSWORD_LENGTH
            ? $answer
            : throw new InvalidAnswerException('The password is too short.');
    }
}
