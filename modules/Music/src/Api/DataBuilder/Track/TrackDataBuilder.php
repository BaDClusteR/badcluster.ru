<?php

declare(strict_types=1);

namespace BC\Modules\Music\Api\DataBuilder\Track;

use BC\Core\Formatter\IFormatter;
use BC\Modules\Music\Api\DTO\Track\SongDTO;
use BC\Modules\Music\Api\DTO\Track\TrackDTO;
use BC\Modules\Music\Api\DTO\Track\TrackRowDTO;
use BC\Modules\Music\Model\Track;
use Runway\Model\Exception\ModelException;

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
                $track->getSong()?->getDuration() ?? 0
            ),
        );
    }

    /**
     * @throws ModelException The track has no song (the song is required in the admin).
     */
    public function buildEntity(Track $track): TrackDTO {
        $song = $track->getSong()
            ?? throw new ModelException(sprintf('Track #%d has no song', $track->getId()));

        return new TrackDTO(
            title: $track->getTitle(),
            explicitLanguage: $track->getExplicitLanguage(),
            sourceUrl: $track->getSourceUrl(),
            lyrics: $track->getLyrics(),
            clipUrl: $track->getClipUrl(),
            position: $track->getPosition(),
            annotation: $track->getAnnotation(),
            song: SongDTO::fromFileDTO(
                $song->toFileDTO(),
                $this->formatter->formatAsHumanReadableDuration(
                    $song->getDuration()
                )
            ),
            albumId: $track->getAlbum()->getId()
        );
    }
}
