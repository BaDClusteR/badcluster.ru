<?php

declare(strict_types=1);

namespace BC\Modules\Music\Api\DataBuilder\Album;

use BC\Core\Converter\IDateConverter;
use BC\Core\Formatter\IFormatter;
use BC\Modules\Music\Api\DTO\Album\AlbumDTO;
use BC\Modules\Music\Api\DTO\Album\AlbumRowDTO;
use BC\Modules\Music\Model\Album;
use BC\Modules\Music\Model\Track;
use Runway\Exception\Exception;

readonly class AlbumDataBuilder implements IAlbumDataBuilder {
    public function __construct(
        private IDateConverter $dateConverter,
        private IFormatter $formatter
    ) {
    }

    public function buildRow(Album $album): AlbumRowDTO {
        try {
            $trackCount = Track::getQueryBuilder()
                               ->where('album_id = :album_id')
                               ->setVariable('album_id', $album->getId())
                               ->count();
        } catch (Exception) {
            $trackCount = 0;
        }

        return new AlbumRowDTO(
            id: $album->getId(),
            cover: $album->getCover()?->toMediaDTO(),
            title: $album->getTitle(),
            genre: $album->getGenre(),
            type: $album->getTypeHumanReadable(),
            releaseDate: $this->dateConverter->toShortForm($album->getReleaseDate()),
            tracks: $this->getTracksText($trackCount)
        );
    }

    public function getTracksText(int $count): string {
        $countMod100 = $count % 100;
        if ($countMod100 >= 10 && $countMod100 <= 20) {
            $postfix = 'песен';
        } else {
            $postfix = match ($count % 10) {
                1 => 'песня',
                2, 3, 4 => 'песни',
                default => 'песен'
            };
        }

        return "$count $postfix";
    }

    public function buildEntity(Album $album): AlbumDTO {
        return new AlbumDTO(
            title: $album->getTitle(),
            cover: $album->getCover()?->toMediaDTO(),
            slug: $album->getSlug(),
            genre: $album->getGenre(),
            type: $album->getType(),
            releaseDate: $this->dateConverter->toPickerValue($album->getReleaseDate()),
            annotation: $album->getAnnotation(),
            shortAnnotation: $album->getShortAnnotation(),
            musicBy: $album->getMusicBy(),
            visualBy: $album->getVisualBy(),
            coverBy: $album->getCoverBy(),
            position: $album->getPosition()
        );
    }
}
