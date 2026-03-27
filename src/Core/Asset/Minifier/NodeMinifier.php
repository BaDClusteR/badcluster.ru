<?php

namespace BC\Core\Asset\Minifier;

use Runway\Logger\ILogger;

readonly class NodeMinifier implements IMinifier
{
    public function __construct(
        private ILogger $logger,
        private string $nodePath = 'node'
    ) {
    }

    public function minify(string $content, string $type): string
    {
        $script = match ($type) {
            'js' => $this->getTerserScript(),
            'css' => $this->getLightningCssScript(),
            default => null,
        };

        if ($script === null) {
            return $content;
        }

        $result = $this->execute($script, $content);

        if ($result === null) {
            $this->logger->warning("Node minification failed for $type, returning original content");
            return $content;
        }

        return $result;
    }

    private function getTerserScript(): string
    {
        return <<<'JS'
            const { minify } = require('terser');

            let input = '';
            process.stdin.setEncoding('utf8');
            process.stdin.on('data', chunk => input += chunk);
            process.stdin.on('end', async () => {
                try {
                    const result = await minify(input, { compress: true, mangle: true });
                    process.stdout.write(result.code);
                } catch (e) {
                    process.stderr.write(e.message);
                    process.exit(1);
                }
            });
            JS;
    }

    private function getLightningCssScript(): string
    {
        return <<<'JS'
            const { transform } = require('lightningcss');

            let chunks = [];
            process.stdin.on('data', chunk => chunks.push(chunk));
            process.stdin.on('end', () => {
                try {
                    const result = transform({
                        filename: 'input.css',
                        code: Buffer.concat(chunks),
                        minify: true,
                    });

                    process.stdout.write(result.code);
                } catch (e) {
                    process.stderr.write(e.message);
                    process.exit(1);
                }
            });
            JS;
    }

    private function execute(string $script, string $input): ?string
    {
        $process = proc_open(
            [$this->nodePath, '-e', $script],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );

        if (!is_resource($process)) {
            $this->logger->warning('Failed to start Node.js process');
            return null;
        }

        fwrite($pipes[0], $input);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            $this->logger->warning("Node.js minification exited with code $exitCode: $stderr");
            return null;
        }

        return $stdout !== false ? $stdout : null;
    }
}
