<?php

declare(strict_types=1);

namespace BC\DTO\AppSettings;

readonly class ModuleDTO
{
    public function __construct(
        public string $id,
        public string $path, // for React router
        public string $remoteEntry,
    ) {
    }

    public function toArray(): array {
        return [
            'id'          => $this->id,
            'path'        => $this->path,
            'remoteEntry' => $this->remoteEntry,
        ];
    }
}
