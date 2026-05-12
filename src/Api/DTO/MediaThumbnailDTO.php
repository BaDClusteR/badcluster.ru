<?php

namespace BC\Api\DTO;

readonly class MediaThumbnailDTO
{
    public function __construct(
        public int $id,
        public int $width,
        public int $height,
        public string $mime,
        public string $url
    ) {
    }

    public function toArray(): array
    {
        return [
            'id'     => $this->id,
            'width'  => $this->width,
            'height' => $this->height,
            'mime'   => $this->mime,
            'url'    => $this->url,
        ];
    }
}
