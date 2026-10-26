<?php

declare(strict_types=1);

namespace BC\Core\DTO;

readonly class MediaThumbnailDTO {
    public function __construct(
        public int $id,
        public int $width,
        public int $height,
        public string $mime,
        public string $url
    ) {
    }

    /**
     * @return array{id: int, width: int, height: int, mime: string, url: string}
     */
    public function toArray(): array {
        return [
            'id'     => $this->id,
            'width'  => $this->width,
            'height' => $this->height,
            'mime'   => $this->mime,
            'url'    => $this->url,
        ];
    }
}
