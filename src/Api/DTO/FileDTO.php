<?php

namespace BC\Api\DTO;

readonly class FileDTO {
    public function __construct(
        public int $id,
        public string $filename,
        public int $size,
        public string $sizeHumanReadable,
        public string $mime,
        public string $url
    ) {
    }
}
