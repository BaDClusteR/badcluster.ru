<?php

declare(strict_types=1);

namespace BC\Modules\Music\Api\DataBuilder\Track;

use BC\Core\Formatter\IFormatter;
use BC\Modules\Music\Api\DTO\Track\SongDTO;
use BC\Modules\Music\Api\DTO\Track\TrackDTO;
use BC\Modules\Music\Api\DTO\Track\TrackRowDTO;
use BC\Modules\Music\Model\Track;

readonly class TrackDataBuilder implements ITrackDataBuilder {
    public function __construct(
        private IFormatter $formatter
    ) {
    }

    public function buildRow(Track $track): TrackRowDTO {
        return new TrackRowDTO(
            id: $track->getId(),
            title: $track->getTitle(),
            duration: $this->formatter->formatAsHumanReadableDuration(
                $track->getSong()->getDuration()
            ),
        );
    }

    public function buildEntity(Track $track): TrackDTO {
        return new TrackDTO(
            title: $track->getTitle(),
            explicitLanguage: $track->getExplicitLanguage(),
            sourceUrl: $track->getSourceUrl(),
            lyrics: $track->getLyrics(),
            clipUrl: $track->getClipUrl(),
            position: $track->getPosition(),
            annotation: $track->getAnnotation(),
            song: SongDTO::fromFileDTO(
                $track->getSong()->toFileDTO(),
                $this->formatter->formatAsHumanReadableDuration(
                    $track->getSong()->getDuration()
                )
            ),
            albumId: $track->getAlbum()->getId()
        );
    }
}
