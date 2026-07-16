<?php

declare(strict_types=1);

namespace BC\Logger;

use JsonException;
use Runway\Logger\Enum\LogLevelEnum;
use Runway\Logger\ILogger;

class MaskingLogger implements ILogger {
    protected const string MASK = '***';

    protected const int MAX_DEPTH = 16;

    /**
     * @var string[]
     */
    protected readonly array $sensitiveKeys;

    /**
     * @param string[] $sensitiveKeys
     */
    public function __construct(
        protected readonly ILogger $inner,
        array                      $sensitiveKeys
    ) {
        $this->sensitiveKeys = array_map(strtolower(...), $sensitiveKeys);
    }

    public function setLogLevel(LogLevelEnum $level): static {
        $this->inner->setLogLevel($level);

        return $this;
    }

    public function debug(string $message, array $context = []): void {
        $this->inner->debug($message, $this->maskArray($context));
    }

    public function info(string $message, array $context = []): void {
        $this->inner->info($message, $this->maskArray($context));
    }

    public function deprecated(string $message, array $context = []): void {
        $this->inner->deprecated($message, $this->maskArray($context));
    }

    public function warning(string $message, array $context = []): void {
        $this->inner->warning($message, $this->maskArray($context));
    }

    public function error(string $message, array $context = []): void {
        $this->inner->error($message, $this->maskArray($context));
    }

    public function critical(string $message, array $context = []): void {
        $this->inner->critical($message, $this->maskArray($context));
    }

    public function emergency(string $message, array $context = []): void {
        $this->inner->emergency($message, $this->maskArray($context));
    }

    protected function maskArray(array $data, int $depth = 0): array {
        if ($depth > static::MAX_DEPTH) {
            return $data;
        }

        $result = [];

        foreach ($data as $key => $value) {
            $result[$key] = $this->isSensitiveKey($key)
                ? static::MASK
                : $this->maskValue($value, $depth);
        }

        return $result;
    }

    protected function maskValue(mixed $value, int $depth): mixed {
        if (is_array($value)) {
            return $this->maskArray($value, $depth + 1);
        }

        if (is_object($value)) {
            return $this->maskArray(get_object_vars($value), $depth + 1);
        }

        if (is_string($value)) {
            return $this->maskJsonString($value, $depth);
        }

        return $value;
    }

    /**
     * Request/response bodies are logged as raw JSON strings — mask inside them, too.
     */
    protected function maskJsonString(string $value, int $depth): string {
        $trimmed = ltrim($value);

        if (!str_starts_with($trimmed, '{') && !str_starts_with($trimmed, '[')) {
            return $value;
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $value;
        }

        if (!is_array($decoded)) {
            return $value;
        }

        try {
            return json_encode(
                $this->maskArray($decoded, $depth + 1),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
            );
        } catch (JsonException) {
            return $value;
        }
    }

    protected function isSensitiveKey(int|string $key): bool {
        return is_string($key)
               && in_array(strtolower($key), $this->sensitiveKeys, true);
    }
}
