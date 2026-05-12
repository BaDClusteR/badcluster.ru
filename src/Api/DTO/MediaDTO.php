<?php

namespace BC\Api\DTO;

use ApiPlatform\Attribute\Docs;

readonly class MediaDTO
{
    public function __construct(
        public int $id,
        public string $url,
        public int $width,
        public int $height,
        public string $mime,
        public string $alt,
        /** @var MediaThumbnailDTO[] */
        #[Docs\Property(description: "Thumbnails", childrenType: MediaThumbnailDTO::class)]
        public array $thumbs
    ) {
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'width' => $this->width,
            'height' => $this->height,
            'mime' => $this->mime,
            'alt' => $this->alt,
            'thumbs' => array_map(
                static fn (MediaThumbnailDTO $thumbnailDTO) => $thumbnailDTO->toArray(),
                $this->thumbs
            )
        ];
    }
}
