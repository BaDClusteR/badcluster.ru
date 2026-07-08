<?php

declare(strict_types=1);

namespace BC\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Attribute\Docs;
use ApiPlatform\Exception\BadRequestException;
use BC\Api\DataBuilder\PhotoTag\IPhotoTagDataBuilder;
use BC\Api\DTO\CreatedDTO;
use BC\Api\DTO\GetEntitiesListRequest;
use BC\Api\DTO\ListResponseDTO;
use BC\Api\DTO\Photo\PhotoTagDTO as PhotoTagOptionDTO;
use BC\Api\DTO\Photo\PhotoTagsDTO;
use BC\Api\DTO\PhotoTag\PhotoTagDTO;
use BC\Api\DTO\PhotoTag\PhotoTagRowDTO;
use BC\Api\DTO\SuccessfulResultDTO;
use BC\Api\Exception\NotFoundException;
use BC\Core\Action\DTO\CreatePhotoTagRequest;
use BC\Core\Action\DTO\SavePhotoTagRequest;
use BC\Core\Action\PhotoTag\ICreatePhotoTagAction;
use BC\Core\Action\PhotoTag\ISavePhotoTagAction;
use BC\Exception\UnprocessableEntityException;
use BC\Model\PhotoPhotoTag;
use BC\Model\PhotoTag;
use Runway\Singleton\Container;

#[Docs\Group('Photos')]
class PhotoTags extends AEndpoint {
    public function __construct(
        private readonly IPhotoTagDataBuilder $dataBuilder
    ) {
    }

    /**
     * Плоский список тэгов для контролов вроде мультиселекта на форме фотки.
     */
    #[API\Endpoint(path: 'photo_tag_options', method: 'GET')]
    public function getTags(): PhotoTagsDTO {
        /** @var PhotoTag[] $tags */
        $tags = $this->handleWithException(
            static fn () => PhotoTag::find(orderBy: 'id')
        );

        return new PhotoTagsDTO(
            tags: array_map(
                static fn (PhotoTag $tag): PhotoTagOptionDTO => new PhotoTagOptionDTO(
                    id: $tag->getId(),
                    title: $tag->getTitle()
                ),
                $tags
            )
        );
    }

    /**
     * @return ListResponseDTO<PhotoTagRowDTO>
     *
     * @throws BadRequestException
     */
    #[API\Endpoint(path: 'photo_tags', method: 'GET')]
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
        $photosCounts = $this->getPhotosCounts();

        return $this->getEntitiesList(
            new GetEntitiesListRequest(
                qb: PhotoTag::getQueryBuilder()->orderBy('position', 'ASC'),
                filter: $filter,
                columnsToFind: ['title'],
                sortBy: $sortBy,
                sortDir: $sortDir,
                page: $page,
                perPage: $perPage,
                sortableColumns: ['id', 'title']
            ),
            fn (PhotoTag $tag): PhotoTagRowDTO => $this->dataBuilder->buildRow(
                $tag,
                $photosCounts[$tag->getId()] ?? 0
            )
        );
    }

    /**
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'photo_tag', method: 'GET')]
    public function getOne(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id
    ): PhotoTagDTO {
        return $this->getEntity(
            PhotoTag::class,
            $id,
            'Тэг #{{id}} не найден.',
            fn (PhotoTag $tag): PhotoTagDTO => $this->dataBuilder->buildEntity($tag)
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'photo_tag', method: 'POST')]
    public function createTag(
        #[API\Parameter(source: 'body', name: 'title')]
        string $title,
        #[API\Parameter(source: 'body', name: 'position')]
        int $position = 0
    ): CreatedDTO {
        $response = null;
        $this->handleActionWithException(
            function () use (&$response, $title, $position) {
                $action = Container::getInstance()->getService(ICreatePhotoTagAction::class);
                $response = $action->run(
                    new CreatePhotoTagRequest(
                        title: $title,
                        position: $position
                    )
                );
            },
            'Ошибки при создании тэга'
        );

        return new CreatedDTO(
            $response->tag->getId()
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'photo_tag', method: 'PUT')]
    public function updateTag(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id,
        #[API\Parameter(source: 'body', name: 'title')]
        string $title,
        #[API\Parameter(source: 'body', name: 'position')]
        int $position = 0
    ): SuccessfulResultDTO {
        $this->handleActionWithException(
            static function () use ($id, $title, $position) {
                $action = Container::getInstance()->getService(ISavePhotoTagAction::class);
                $action->run(
                    new SavePhotoTagRequest(
                        id: $id,
                        title: $title,
                        position: $position
                    )
                );
            },
            'Ошибки при сохранении тэга'
        );

        return new SuccessfulResultDTO();
    }

    #[API\Endpoint(path: 'photo_tags', method: 'DELETE')]
    public function delete(
        #[API\Parameter(source: 'body', name: 'rows')]
        array $rows
    ): SuccessfulResultDTO {
        $this->deleteEntities(PhotoTag::class, $rows);

        return new SuccessfulResultDTO();
    }

    /**
     * @return array<int, int> Число фоток по id тэга
     */
    private function getPhotosCounts(): array {
        $rows = $this->handleWithException(
            static fn (): array => PhotoPhotoTag::getQueryBuilder()
                ->select('tag_id', 'COUNT(*) AS photos_count')
                ->groupBy('tag_id')
                ->getResults()
        );

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int)$row['tag_id']] = (int)$row['photos_count'];
        }

        return $counts;
    }
}
