<?php

declare(strict_types=1);

namespace BC\Modules\Backup\Command;

use Runway\Console\Command\ACommand;
use Runway\Console\Input\IInput;
use Runway\Console\Output\IOutput;
use Runway\Console\Parameter\Enum\ParameterModeEnum;
use Throwable;

/**
 * One-time interactive OAuth authorisation for the Google Drive destination.
 *
 * Prerequisite: an OAuth client of type "Desktop app" in the Google Cloud project
 * (APIs & Services → Credentials → Create credentials → OAuth client ID), its JSON
 * downloaded locally. Desktop clients may use loopback redirect URIs without registration.
 *
 * The command starts a loopback HTTP listener, prints the consent URL to open in a browser,
 * catches Google's redirect with the authorisation code, exchanges it for a refresh token
 * and writes a token file understood by GoogleDriveClient. Point BACKUP_GDRIVE_KEY_FILE at
 * that file.
 *
 * Run this on a machine WITH a browser; the resulting token file is portable — copy it to
 * the server afterwards.
 */
class GdriveAuthCommand extends ACommand {
    private const string TOKEN_URI = 'https://oauth2.googleapis.com/token';
    private const string AUTH_URI = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const string SCOPE = 'https://www.googleapis.com/auth/drive';

    public function getName(): string {
        return 'backup:gdrive-auth';
    }

    public function getDescription(): string {
        return 'Authorise the Google Drive backup destination (one-time, needs a browser)';
    }

    protected function configure(): void {
        $this->addArgument(
            'credentials',
            ParameterModeEnum::REQUIRED,
            'Path to the OAuth client JSON ("Desktop app") downloaded from Google Cloud Console'
        )->addOption(
            'output',
            'o',
            ParameterModeEnum::VALUE_REQUIRED,
            'Where to write the token file',
            PROJECT_ROOT . '/.config/gdrive-token.json'
        )->addOption(
            'port',
            'p',
            ParameterModeEnum::VALUE_REQUIRED,
            'Loopback port for the OAuth redirect',
            '8117'
        );
    }

    protected function execute(IInput $input, IOutput $output): int {
        $credentialsPath = (string)$input->getArgument('credentials');
        $outputPath = (string)$input->getOption('output');
        $port = (int)$input->getOption('port');

        try {
            [$clientId, $clientSecret] = $this->readClientCredentials($credentialsPath);
        } catch (Throwable $e) {
            $output->error($e->getMessage());

            return 1;
        }

        $redirectUri = 'http://127.0.0.1:' . $port;

        $authUrl = self::AUTH_URI . '?' . http_build_query([
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => self::SCOPE,
            'access_type'   => 'offline',
            'prompt'        => 'consent',
        ]);

        $server = @stream_socket_server('tcp://127.0.0.1:' . $port, $errno, $errstr);
        if ($server === false) {
            $output->error("Cannot listen on 127.0.0.1:$port ($errstr). Pick another port with --port.");

            return 1;
        }

        $output->writeln('');
        $output->info('Open this URL in your browser and approve access:');
        $output->writeln('');
        $output->writeln($authUrl);
        $output->writeln('');
        $output->info('Waiting for the browser redirect on ' . $redirectUri . ' (5 minutes)…');

        try {
            $code = $this->waitForAuthCode($server);
        } catch (Throwable $e) {
            $output->error($e->getMessage());

            return 1;
        } finally {
            fclose($server);
        }

        try {
            $refreshToken = $this->exchangeCode($code, $clientId, $clientSecret, $redirectUri);
            $this->writeTokenFile($outputPath, $clientId, $clientSecret, $refreshToken);
        } catch (Throwable $e) {
            $output->error($e->getMessage());

            return 1;
        }

        $output->success('Token saved to ' . $outputPath);
        $output->writeln('Now point the backup config at it:');
        $output->writeln('  BACKUP_GDRIVE_KEY_FILE=' . $outputPath);

        return 0;
    }

    /**
     * @return array{0: string, 1: string} [clientId, clientSecret]
     */
    private function readClientCredentials(string $path): array {
        if (!is_readable($path)) {
            throw new \RuntimeException('Cannot read the credentials file: ' . $path);
        }

        $data = json_decode((string)file_get_contents($path), true);

        // Google wraps desktop-client credentials in "installed" (or "web" for web clients).
        $client = $data['installed'] ?? $data['web'] ?? $data;

        if (empty($client['client_id']) || empty($client['client_secret'])) {
            throw new \RuntimeException(
                'The credentials file has no client_id/client_secret. '
                . 'Download the JSON of an OAuth client of type "Desktop app".'
            );
        }

        return [$client['client_id'], $client['client_secret']];
    }

    /**
     * Accepts one HTTP request on the loopback listener and extracts ?code=… from it.
     *
     * @param resource $server
     */
    private function waitForAuthCode($server): string {
        $connection = @stream_socket_accept($server, 300);
        if ($connection === false) {
            throw new \RuntimeException('Timed out waiting for the browser redirect.');
        }

        $requestLine = (string)fgets($connection, 4096);
        preg_match('/^GET\s+(\S+)/', $requestLine, $match);
        parse_str((string)parse_url($match[1] ?? '', PHP_URL_QUERY), $query);

        $isOk = isset($query['code']);
        $message = $isOk
            ? 'Authorised! You can close this tab and return to the console.'
            : 'Authorisation failed: ' . ($query['error'] ?? 'no code in the redirect') . ' — see the console.';

        fwrite(
            $connection,
            "HTTP/1.1 200 OK\r\nContent-Type: text/html; charset=utf-8\r\nConnection: close\r\n\r\n"
            . '<html lang="en"><body style="font-family: sans-serif"><h2>' . htmlspecialchars($message) . '</h2></body></html>'
        );
        fclose($connection);

        if (!$isOk) {
            throw new \RuntimeException('Authorisation failed: ' . ($query['error'] ?? 'no code in the redirect'));
        }

        return (string)$query['code'];
    }

    private function exchangeCode(string $code, string $clientId, string $clientSecret, string $redirectUri): string {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => self::TOKEN_URI,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri'  => $redirectUri,
            ]),
            CURLOPT_CONNECTTIMEOUT => 30,
        ]);

        $response = curl_exec($curl);
        if ($response === false) {
            throw new \RuntimeException('Token exchange failed: ' . curl_error($curl));
        }

        $data = json_decode((string)$response, true);

        if (empty($data['refresh_token'])) {
            throw new \RuntimeException(
                'Token endpoint returned no refresh_token: ' . mb_substr((string)$response, 0, 300)
            );
        }

        return $data['refresh_token'];
    }

    private function writeTokenFile(string $path, string $clientId, string $clientSecret, string $refreshToken): void {
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new \RuntimeException('Cannot create directory ' . $dir);
        }

        $json = json_encode(
            [
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
            ],
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT
        );

        if (file_put_contents($path, $json . "\n") === false) {
            throw new \RuntimeException('Cannot write the token file: ' . $path);
        }

        @chmod($path, 0600);
    }
}
