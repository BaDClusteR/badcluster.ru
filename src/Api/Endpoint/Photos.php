<?php

declare(strict_types=1);

namespace BC\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Attribute\Docs;
use ApiPlatform\Exception\BadRequestException;
use BC\Api\DataBuilder\Photo\IPhotoDataBuilder;
use BC\Api\DTO\CreatedDTO;
use BC\Api\DTO\GetEntitiesListRequest;
use BC\Api\DTO\ListResponseDTO;
use BC\Api\DTO\Photo\PhotoDTO;
use BC\Api\DTO\Photo\PhotoRowDTO;
use BC\Api\DTO\SuccessfulResultDTO;
use BC\Api\Exception\NotFoundException;
use BC\Core\Action\DTO\CreatePhotoRequest;
use BC\Core\Action\DTO\SavePhotoRequest;
use BC\Core\Action\Photo\ICreatePhotoAction;
use BC\Core\Action\Photo\ISavePhotoAction;
use BC\Exception\UnprocessableEntityException;
use BC\Model\Media;
use BC\Model\Photo;
use BC\Model\PhotoTag;
use Runway\Singleton\Container;

#[Docs\Group('Photos')]
class Photos extends AEndpoint {
    public function __construct(
        private readonly IPhotoDataBuilder $dataBuilder
    ) {
    }

    /**
     * @return ListResponseDTO<PhotoRowDTO>
     *
     * @throws BadRequestException
     */
    #[API\Endpoint(path: 'photos', method: 'GET')]
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
        return $this->getEntitiesList(
            new GetEntitiesListRequest(
            // parent_id IS NULL — отсекаем строки тамбнейлов, они живут в той же таблице
                qb: Photo::getQueryBuilder()
                         ->where('parent_id IS NULL')
                         ->orderBy('position', 'DESC'),
                filter: $filter,
                columnsToFind: ['path', 'alt'],
                sortBy: $sortBy === 'uploadedAt'
                    ? 'uploaded_at'
                    : $sortBy,
                sortDir: $sortDir,
                page: $page,
                perPage: $perPage,
                sortableColumns: ['uploaded_at', 'position']
            ),
            fn (Photo $photo): PhotoRowDTO => $this->dataBuilder->buildRow($photo)
        );
    }

    /**
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'photo', method: 'GET')]
    public function getOne(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id
    ): PhotoDTO {
        return $this->getEntity(
            Photo::class,
            $id,
            'Фотка #{{id}} не найдена.',
            fn (Photo $photo): PhotoDTO => $this->dataBuilder->buildEntity($photo)
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'photo', method: 'POST')]
    public function createPhoto(
        #[API\Parameter(source: 'body', name: 'image')]
        array $image,
        #[API\Parameter(source: 'body', name: 'alt')]
        string $alt = '',
        #[API\Parameter(source: 'body', name: 'position')]
        int $position = 0,
        #[API\Parameter(source: 'body', name: 'tags')]
        array $tags = []
    ): CreatedDTO {
        $media = $this->requireMedia($image, 'Ошибки при создании фотки');

        $response = null;
        $this->handleActionWithException(
            function () use (&$response, $media, $alt, $position, $tags) {
                $action = Container::getInstance()->getService(ICreatePhotoAction::class);
                $response = $action->run(
                    new CreatePhotoRequest(
                        media: $media,
                        alt: $alt,
                        position: $position,
                        tags: $this->findTags($tags)
                    )
                );
            },
            'Ошибки при создании фотки'
        );

        return new CreatedDTO(
            $response->photo->getId()
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'photo', method: 'PUT')]
    public function updatePhoto(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id,
        #[API\Parameter(source: 'body', name: 'image')]
        ?array $image = null,
        #[API\Parameter(source: 'body', name: 'alt')]
        string $alt = '',
        #[API\Parameter(source: 'body', name: 'position')]
        int $position = 0,
        #[API\Parameter(source: 'body', name: 'tags')]
        array $tags = []
    ): SuccessfulResultDTO {
        // image.id = 0 приходит для существующей картинки фотки (см. PhotoDataBuilder),
        // media ищем только для свежей загрузки
        $media = ($image !== null) && ((int) ($image['id'] ?? 0) > 0)
            ? $this->requireMedia($image, 'Ошибки при сохранении фотки')
            : null;

        $this->handleActionWithException(
            function () use ($id, $media, $alt, $position, $tags) {
                $action = Container::getInstance()->getService(ISavePhotoAction::class);
                $action->run(
                    new SavePhotoRequest(
                        id: $id,
                        alt: $alt,
                        position: $position,
                        tags: $this->findTags($tags),
                        media: $media
                    )
                );
            },
            'Ошибки при сохранении фотки'
        );

        return new SuccessfulResultDTO();
    }

    #[API\Endpoint(path: 'photos', method: 'DELETE')]
    public function delete(
        #[API\Parameter(source: 'body', name: 'rows')]
        array $rows
    ): SuccessfulResultDTO {
        // Удаляем через remove() каждой модели, а не bulk-запросом:
        // Media::remove() заодно удаляет файл с диска.
        // Связи с тэгами чистит каскад в базе.
        $this->handleWithException(
            static function () use ($rows) {
                foreach (Photo::find(['id' => $rows]) as $photo) {
                    $photo->remove();
                }
            }
        );

        return new SuccessfulResultDTO();
    }

    /**
     * @throws UnprocessableEntityException
     */
    private function requireMedia(array $image, string $errorTitle): Media {
        $media = $this->findMedia($image);

        if (!$media) {
            throw new UnprocessableEntityException(
                ['image' => 'Загруженная картинка не найдена'],
                $errorTitle
            );
        }

        return $media;
    }

    /**
     * @return PhotoTag[]
     */
    private function findTags(array $tagIds): array {
        $tagIds = array_map('intval', $tagIds);

        return $tagIds
            ? $this->handleWithException(
                static fn (): array => PhotoTag::find(['id' => $tagIds])
            )
            : [];
    }
}