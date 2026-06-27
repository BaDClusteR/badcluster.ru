<?php

namespace BC\Modules\Music\Api\DTO\Track;

use BC\Api\DTO\FileDTO;

readonly class SongDTO extends FileDTO {
    public function __construct(
        int $id,
        string $filename,
        int $size,
        string $sizeHumanReadable,
        string $mime,
        string $url,
        public string $duration
    ) {
        parent::__construct($id, $filename, $size, $sizeHumanReadable, $mime, $url);
    }

    public static function fromFileDTO(FileDTO $fileDTO, string $duration): self {
        return new self(
            $fileDTO->id,
            $fileDTO->filename,
            $fileDTO->size,
            $fileDTO->sizeHumanReadable,
            $fileDTO->mime,
            $fileDTO->url,
            $duration
        );
    }
}
