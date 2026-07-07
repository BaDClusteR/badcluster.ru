<?php

declare(strict_types=1);

namespace BC\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Attribute\Docs;
use ApiPlatform\Exception\BadRequestException;
use BC\Api\DataBuilder\Media\IMediaDataBuilder;
use BC\Api\DTO\GetEntitiesListRequest;
use BC\Api\DTO\ListResponseDTO;
use BC\Api\DTO\Media\MediaDetailsDTO;
use BC\Api\DTO\Media\MediaRowDTO;
use BC\Api\DTO\SuccessfulResultDTO;
use BC\Api\Exception\NotFoundException;
use BC\Core\Action\DTO\SaveMediaRequest;
use BC\Core\Action\Media\ISaveMediaAction;
use BC\Exception\UnprocessableEntityException;
use BC\Model\Media;
use Runway\Singleton\Container;

#[Docs\Group('Media library')]
class MediaLibrary extends AEndpoint {
    public function __construct(
        private readonly IMediaDataBuilder $dataBuilder
    ) {
    }

    /**
     * @return ListResponseDTO<MediaRowDTO>
     *
     * @throws BadRequestException
     */
    #[API\Endpoint(path: 'media', method: 'GET')]
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
            // parent_id IS NULL — тамбнейлы в списке не показываем
                qb: Media::getQueryBuilder()
                         ->where('parent_id IS NULL')
                         ->orderBy('id', 'DESC'),
                filter: $filter,
                columnsToFind: ['path', 'alt', 'mime'],
                sortBy: $sortBy,
                sortDir: $sortDir,
                page: $page,
                perPage: $perPage,
                sortableColumns: ['id']
            ),
            fn (Media $media): MediaRowDTO => $this->dataBuilder->buildRow($media)
        );
    }

    /**
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'media_file', method: 'GET')]
    public function getOne(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id
    ): MediaDetailsDTO {
        return $this->getEntity(
            Media::class,
            $id,
            'Файл #{{id}} не найден.',
            fn (Media $media): MediaDetailsDTO => $this->dataBuilder->buildEntity($media)
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'media_file', method: 'PUT')]
    public function updateMedia(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id,
        #[API\Parameter(source: 'body', name: 'width')]
        int $width = 0,
        #[API\Parameter(source: 'body', name: 'height')]
        int $height = 0,
        #[API\Parameter(source: 'body', name: 'alt')]
        string $alt = ''
    ): SuccessfulResultDTO {
        $this->handleActionWithException(
            static function () use ($id, $width, $height, $alt) {
                $action = Container::getInstance()->getService(ISaveMediaAction::class);
                $action->run(
                    new SaveMediaRequest(
                        id: $id,
                        width: $width,
                        height: $height,
                        alt: $alt
                    )
                );
            },
            'Ошибки при сохранении файла'
        );

        return new SuccessfulResultDTO();
    }

    #[API\Endpoint(path: 'media', method: 'DELETE')]
    public function delete(
        #[API\Parameter(source: 'body', name: 'rows')]
        array $rows
    ): SuccessfulResultDTO {
        // Удаляем через remove() каждой модели: заодно удаляются
        // тамбнейлы и файлы с диска
        $this->handleWithException(
            static function () use ($rows) {
                foreach (Media::find(['id' => $rows]) as $media) {
                    $media->remove();
                }
            }
        );

        return new SuccessfulResultDTO();
    }
}