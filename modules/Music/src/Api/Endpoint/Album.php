<?php

declare(strict_types=1);

namespace BC\Modules\Music\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Exception\BadRequestException;
use BC\Api\DTO\CreatedDTO;
use BC\Api\DTO\GetEntitiesListRequest;
use BC\Api\DTO\ListResponseDTO;
use BC\Api\DTO\SuccessfulResultDTO;
use BC\Api\Endpoint\AEndpoint;
use BC\Api\Exception\NotFoundException;
use BC\Core\Converter\IDateConverter;
use BC\Core\Formatter\IFormatter;
use BC\Exception\UnprocessableEntityException;
use BC\Model\Media;
use BC\Modules\Music\Api\DataBuilder\Album\IAlbumDataBuilder;
use BC\Modules\Music\Api\DTO\Album\AlbumDTO;
use BC\Modules\Music\Api\DTO\Album\AlbumRowDTO;
use BC\Modules\Music\Model\Album as AlbumModel;
use DateTime;

class Album extends AEndpoint {
    public function __construct(
        private readonly IAlbumDataBuilder $dataBuilder,
        private readonly IDateConverter $dateConverter,
        private readonly IFormatter $formatter
    ) {
    }

    /**
     * @return ListResponseDTO<AlbumRowDTO>
     *
     * @throws BadRequestException
     */
    #[API\Endpoint(path: 'albums', method: 'GET')]
    public function getList(
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
        if ($sortBy === 'releaseDate') {
            $sortBy = 'release_date';
        }

        return $this->getEntitiesList(
            new GetEntitiesListRequest(
                qb: AlbumModel::getQueryBuilder()->orderBy('position', 'ASC'),
                filter: $filter,
                columnsToFind: ['title', 'shortAnnotation', 'annotation'],
                sortBy: $sortBy,
                sortDir: $sortDir,
                page: $page,
                perPage: $perPage,
                sortableColumns: ['title', 'release_date'],
            ),
            fn (AlbumModel $album): AlbumRowDTO => $this->dataBuilder->buildRow($album)
        );
    }

    /**
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'album', method: 'GET')]
    public function getOne(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id
    ): AlbumDTO {
        return $this->getEntity(
            AlbumModel::class,
            $id,
            'Сборник #{{id}} не найден.',
            fn (AlbumModel $album): AlbumDTO => $this->dataBuilder->buildEntity($album)
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'album', method: 'POST')]
    public function create(
        #[API\Parameter(source: 'body', name: 'title')]
        #[API\Assert\NotEmpty]
        string $title,
        #[API\Parameter(source: 'body', name: 'type')]
        #[API\Assert\NotEmpty]
        string $type,
        #[API\Parameter(source: 'body', name: 'shortAnnotation')]
        #[API\Assert\NotEmpty]
        string $shortAnnotation,
        #[API\Parameter(source: 'body', name: 'slug')]
        #[API\Assert\NotEmpty]
        string $slug,
        #[API\Parameter(source: 'body', name: 'releaseDate')]
        #[API\Assert\NotEmpty]
        string $releaseDate,
        #[API\Parameter(source: 'body', name: 'annotation')]
        string $annotation = '',
        #[API\Parameter(source: 'body', name: 'cover')]
        ?array $cover = null,
        #[API\Parameter(source: 'body', name: 'genre')]
        string $genre = '',
        #[API\Parameter(source: 'body', name: 'musicBy')]
        string $musicBy = '',
        #[API\Parameter(source: 'body', name: 'visualBy')]
        string $visualBy = '',
        #[API\Parameter(source: 'body', name: 'coverBy')]
        string $coverBy = '',
        #[API\Parameter(source: 'body', name: 'position')]
        ?int $position = null
    ): CreatedDTO {
        $this->validateEntity($slug, $type, null, 'Ошибки при добавлении сборника.');

        if (!$position) {
            $albumCount = $this->handleWithException(
                static fn () => AlbumModel::getQueryBuilder()
                                          ->count()
            );

            $position = $albumCount * 100;
        }

        /** @var AlbumModel $album */
        $album = $this->handleWithException(
            fn () => new AlbumModel()
                ->setTitle($title)
                ->setType($type)
                ->setAnnotation($annotation)
                ->setShortAnnotation($shortAnnotation)
                ->setSlug($slug)
                ->setReleaseDate($this->dateConverter->toDateTime($releaseDate))
                ->setCover(
                    Media::findByUniqueIdentifier(
                        (int) ($cover['id'] ?? 0)
                    )
                )->setGenre($genre)
                ->setMusicBy($musicBy)
                ->setVisualBy($visualBy)
                ->setCoverBy($coverBy)
                ->setPosition($position)
        );

        $this->handleWithException(
            static fn () => $album->persist()
        );

        return new CreatedDTO(
            $album->getId()
        );
    }

    /**
     * @throws UnprocessableEntityException
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'album', method: 'PUT')]
    public function update(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id,

        #[API\Parameter(source: 'body', name: 'title')]
        #[API\Assert\NotEmpty]
        string $title,
        #[API\Parameter(source: 'body', name: 'type')]
        #[API\Assert\NotEmpty]
        string $type,
        #[API\Parameter(source: 'body', name: 'shortAnnotation')]
        #[API\Assert\NotEmpty]
        string $shortAnnotation,
        #[API\Parameter(source: 'body', name: 'slug')]
        #[API\Assert\NotEmpty]
        string $slug,
        #[API\Parameter(source: 'body', name: 'releaseDate')]
        #[API\Assert\NotEmpty]
        string $releaseDate,
        #[API\Parameter(source: 'body', name: 'annotation')]
        string $annotation = '',
        #[API\Parameter(source: 'body', name: 'cover')]
        ?array $cover = null,
        #[API\Parameter(source: 'body', name: 'genre')]
        string $genre = '',
        #[API\Parameter(source: 'body', name: 'musicBy')]
        string $musicBy = '',
        #[API\Parameter(source: 'body', name: 'visualBy')]
        string $visualBy = '',
        #[API\Parameter(source: 'body', name: 'coverBy')]
        string $coverBy = '',
        #[API\Parameter(source: 'body', name: 'position')]
        ?int $position = null
    ): SuccessfulResultDTO {
        $this->validateEntity($slug, $type, $id, 'Ошибки при редактировании сборника.');

        /** @var AlbumModel|null $album */
        $album = $this->handleWithException(
            static fn () => AlbumModel::findByUniqueIdentifier($id)
        );

        if (!$album) {
            throw new NotFoundException("Сборник #$id не найден");
        }

        $this->handleWithException(
            fn () => $album
                ->setTitle($title)
                ->setType($type)
                ->setAnnotation($annotation)
                ->setShortAnnotation($shortAnnotation)
                ->setSlug($slug)
                ->setReleaseDate($this->dateConverter->toDateTime($releaseDate))
                ->setCover(
                    Media::findByUniqueIdentifier(
                        (int) ($cover['id'] ?? 0)
                    )
                )->setGenre($genre)
                ->setMusicBy($musicBy)
                ->setVisualBy($visualBy)
                ->setCoverBy($coverBy)
                ->setPosition($position)
                ->persist()
        );

        return new SuccessfulResultDTO();
    }

    #[API\Endpoint(path: 'albums', method: 'DELETE')]
    public function delete(
        #[API\Parameter(source: 'body', name: 'rows')]
        array $rows
    ): SuccessfulResultDTO {
        $this->deleteEntities(AlbumModel::class, $rows);

        return new SuccessfulResultDTO();
    }

    /**
     * @throws UnprocessableEntityException
     */
    private function validateEntity(string $slug, string $type, ?int $id, string $errorTitle): void {
        $errors = [];

        if (!in_array($type, ['S', 'A', 'E', 'D'], true)) {
            $errors['type'] = 'Неизвестный тип сборника';
        }

        if ($albumBySlug = $this->getEntityBySlug(AlbumModel::class, $slug, $id)) {
            $errors['slug'] = "Этот слаг уже занят сборником \"{$albumBySlug->getTitle()}\"";
        }

        if (!empty($errors)) {
            throw new UnprocessableEntityException($errors, $errorTitle);
        }
    }
}
