<?php

declare(strict_types=1);

namespace BC\Modules\Music\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\DTO\ApiEndpointArgumentFileDTO;
use ApiPlatform\Exception\BadRequestException;
use BC\Api\DTO\CreatedDTO;
use BC\Api\DTO\FileDTO;
use BC\Api\DTO\GetEntitiesListRequest;
use BC\Api\DTO\ListResponseDTO;
use BC\Api\DTO\SuccessfulResultDTO;
use BC\Api\Endpoint\AEndpoint;
use BC\Api\Exception\NotFoundException;
use BC\Core\Converter\IDateConverter;
use BC\Core\Formatter\IFormatter;
use BC\Exception\UnprocessableEntityException;
use BC\Modules\Games\Model\GameMaterialFile;
use BC\Modules\Music\Api\DataBuilder\Track\ITrackDataBuilder;
use BC\Modules\Music\Api\DTO\Track\SongDTO;
use BC\Modules\Music\Api\DTO\Track\TrackDTO;
use BC\Modules\Music\Api\DTO\Track\TrackRowDTO;
use BC\Modules\Music\Model\Album as AlbumModel;
use BC\Modules\Music\Model\Song;
use BC\Modules\Music\Model\Track;
use DateTime;
use getID3;

class TrackEndpoint extends AEndpoint {
    public function __construct(
        private readonly ITrackDataBuilder $dataBuilder,
        private readonly IDateConverter $dateConverter,
        private readonly IFormatter $formatter
    ) {
    }

    /**
     * @return ListResponseDTO<TrackRowDTO>
     *
     * @throws BadRequestException
     */
    #[API\Endpoint(path: 'tracks', method: 'GET')]
    public function getList(
        #[API\Parameter(source: 'query')]
        int $albumId,
        #[API\Parameter(source: 'query')]
        string $filter = '',
        #[API\Parameter(source: 'query')]
        string $sortBy = '',
        #[API\Parameter(source: 'query')]
        string $sortDir = '',
        #[API\Parameter(source: 'query')]
        int $page = 1,
        #[API\Parameter(source: 'query')]
        int $perPage = self::PER_PAGE_DEFAULT
    ): ListResponseDTO {
        return $this->getEntitiesList(
            new GetEntitiesListRequest(
                qb: Track::getQueryBuilder()
                         ->where('album_id = :albumId')
                         ->setVariable('albumId', $albumId)
                         ->orderBy('position', 'ASC'),
                filter: $filter,
                columnsToFind: ['title'],
                sortBy: $sortBy,
                sortDir: $sortDir,
                page: $page,
                perPage: $perPage,
                sortableColumns: [],
            ),
            fn (Track $song): TrackRowDTO => $this->dataBuilder->buildRow($song)
        );
    }

    /**
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'track', method: 'GET')]
    public function getOne(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id
    ): TrackDTO {
        return $this->getEntity(
            Track::class,
            $id,
            'Трек #{{id}} не найден.',
            fn (Track $track): TrackDTO => $this->dataBuilder->buildEntity($track)
        );
    }

    #[API\Endpoint(path: 'song_upload', method: 'POST')]
    public function upload(
        #[API\Parameter(source: 'file', name: 'file')]
        ApiEndpointArgumentFileDTO $file
    ): SongDTO {
        $model = $this->handleWithException(
            static fn () => Song::createFrom(
                $file->tmpName,
                $file->name,
                $file->mimeType
            )
        );

        $songInfo = new getID3()->analyze(
            $model->getLocalPath()
        );
        $duration = (int) round(
            (float) ($songInfo['playtime_seconds'] ?? 0.0)
        );
        $model->setDuration($duration);

        $this->handleWithException(
            static fn () => $model->persist()
        );

        return SongDTO::fromFileDTO(
            $model->toFileDTO(),
            $this->formatter->formatAsHumanReadableDuration($duration)
        );
    }

    /**
     * @throws UnprocessableEntityException
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'track', method: 'POST')]
    public function create(
        #[API\Parameter(source: 'body', name: 'title')]
        #[API\Assert\NotEmpty]
        string $title,
        #[API\Parameter(source: 'body', name: 'albumId')]
        int $albumId,
        #[API\Parameter(source: 'body', name: 'song')]
        array $song,
        #[API\Parameter(source: 'body', name: 'explicitLanguage')]
        bool $explicitLanguage = false,
        #[API\Parameter(source: 'body', name: 'sourceUrl')]
        string $sourceUrl = '',
        #[API\Parameter(source: 'body', name: 'lyrics')]
        string $lyrics = '',
        #[API\Parameter(source: 'body', name: 'clipUrl')]
        string $clipUrl = '',
        #[API\Parameter(source: 'body', name: 'position')]
        ?int $position = null,
        #[API\Parameter(source: 'body', name: 'annotation')]
        string $annotation = ''
    ): CreatedDTO {
        /** @var AlbumModel|null $album */
        $album = $this->handleWithException(
            static fn () => AlbumModel::findByUniqueIdentifier($albumId)
        );

        if (!$album) {
            throw new NotFoundException("Сборник #$albumId не найден.");
        }

        if ($position === null) {
            $tracks = $this->handleWithException(
                static fn () => Track::getQueryBuilder()
                                     ->where('album_id = :albumId')
                                     ->setVariable('albumId', $albumId)
                                     ->count()
            );

            $position = ($tracks + 1) * 100;
        }

        $track = $this->handleWithException(
            fn () => new Track()
                ->setAlbum($album)
                ->setTitle($title)
                ->setExplicitLanguage($explicitLanguage)
                ->setSourceUrl($clipUrl)
                ->setLyrics($lyrics)
                ->setPosition($position)
                ->setAnnotation($annotation)
                ->setSourceUrl($sourceUrl)
                ->setClipUrl($clipUrl)
                ->setSong($this->getSong($song))
        );

        $this->handleWithException(
            static fn () => $track->persist()
        );

        return new CreatedDTO(
            $track->getId()
        );
    }

    /**
     * @throws UnprocessableEntityException
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'track', method: 'PUT')]
    public function update(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id,
        #[API\Parameter(source: 'body', name: 'title')]
        #[API\Assert\NotEmpty]
        string $title,
        #[API\Parameter(source: 'body', name: 'song')]
        array $song,
        #[API\Parameter(source: 'body', name: 'explicitLanguage')]
        bool $explicitLanguage = false,
        #[API\Parameter(source: 'body', name: 'sourceUrl')]
        string $sourceUrl = '',
        #[API\Parameter(source: 'body', name: 'lyrics')]
        string $lyrics = '',
        #[API\Parameter(source: 'body', name: 'clipUrl')]
        string $clipUrl = '',
        #[API\Parameter(source: 'body', name: 'position')]
        ?int $position = null,
        #[API\Parameter(source: 'body', name: 'annotation')]
        string $annotation = ''
    ): SuccessfulResultDTO {
        /** @var Track|null $track */
        $track = $this->handleWithException(
            static fn () => Track::findByUniqueIdentifier($id)
        );

        if (!$track) {
            throw new NotFoundException("Трек #$id не найден.");
        }

        if ($position === null) {
            $album = $track->getAlbum();
            $tracks = $this->handleWithException(
                static fn () => Track::getQueryBuilder()
                                     ->where('album_id = :albumId')
                                     ->setVariable('albumId', $album->getId())
                                     ->count()
            );

            $position = ($tracks + 1) * 100;
        }

        $track = $this->handleWithException(
            fn () => $track
                ->setTitle($title)
                ->setExplicitLanguage($explicitLanguage)
                ->setSourceUrl($clipUrl)
                ->setLyrics($lyrics)
                ->setPosition($position)
                ->setAnnotation($annotation)
                ->setSourceUrl($sourceUrl)
                ->setClipUrl($clipUrl)
                ->setSong(
                    $this->getSong($song)
                )
        );

        $this->handleWithException(
            static fn () => $track->persist()
        );

        return new SuccessfulResultDTO();
    }

    #[API\Endpoint(path: 'chapters', method: 'DELETE')]
    public function delete(
        #[API\Parameter(source: 'body', name: 'rows')]
        array $rows
    ): SuccessfulResultDTO {
        $this->deleteEntities(Song::class, $rows);

        return new SuccessfulResultDTO();
    }

    /**
     * @throws UnprocessableEntityException
     */
    protected function getSong(?array $songData): ?Song {
        $song = null;

        if (!empty($songData['id'])) {
            $song = $this->handleWithException(
                static fn () => Song::findByUniqueIdentifier(
                    (int) ($songData['id'])
                )
            );

            if (!$song) {
                throw new UnprocessableEntityException(
                    ['file' => "Песня #{$songData['id']} не найдена"],
                    "Ошибка: песня #{$songData['id']} не найдена."
                );
            }
        }

        return $song;
    }
}
