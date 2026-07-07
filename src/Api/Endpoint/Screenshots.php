<?php

declare(strict_types=1);

namespace BC\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Attribute\Docs;
use ApiPlatform\Exception\BadRequestException;
use BC\Api\DataBuilder\Screenshot\IScreenshotDataBuilder;
use BC\Api\DTO\CreatedDTO;
use BC\Api\DTO\GetEntitiesListRequest;
use BC\Api\DTO\ListResponseDTO;
use BC\Api\DTO\Screenshot\ScreenshotDTO;
use BC\Api\DTO\Screenshot\ScreenshotRowDTO;
use BC\Api\DTO\SuccessfulResultDTO;
use BC\Api\Exception\NotFoundException;
use BC\Core\Action\DTO\CreateScreenshotRequest;
use BC\Core\Action\DTO\SaveScreenshotRequest;
use BC\Core\Action\Screenshot\ICreateScreenshotAction;
use BC\Core\Action\Screenshot\ISaveScreenshotAction;
use BC\Exception\UnprocessableEntityException;
use BC\Model\Media;
use BC\Model\Screenshot;
use Runway\Singleton\Container;

#[Docs\Group('Screenshots')]
class Screenshots extends AEndpoint {
    public function __construct(
        private readonly IScreenshotDataBuilder $dataBuilder
    ) {
    }

    /**
     * @return ListResponseDTO<ScreenshotRowDTO>
     *
     * @throws BadRequestException
     */
    #[API\Endpoint(path: 'screenshots', method: 'GET')]
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
                qb: Screenshot::getQueryBuilder()
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
            fn (Screenshot $screenshot): ScreenshotRowDTO => $this->dataBuilder->buildRow($screenshot)
        );
    }

    /**
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'screenshot', method: 'GET')]
    public function getOne(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id
    ): ScreenshotDTO {
        return $this->getEntity(
            Screenshot::class,
            $id,
            'Скриншот #{{id}} не найден.',
            fn (Screenshot $screenshot): ScreenshotDTO => $this->dataBuilder->buildEntity($screenshot)
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'screenshot', method: 'POST')]
    public function createScreenshot(
        #[API\Parameter(source: 'body', name: 'image')]
        array $image,
        #[API\Parameter(source: 'body', name: 'alt')]
        string $alt = '',
        #[API\Parameter(source: 'body', name: 'position')]
        int $position = 0
    ): CreatedDTO {
        $media = $this->requireMedia($image, 'Ошибки при создании скриншота');

        $response = null;
        $this->handleActionWithException(
            function () use (&$response, $media, $alt, $position) {
                $action = Container::getInstance()->getService(ICreateScreenshotAction::class);
                $response = $action->run(
                    new CreateScreenshotRequest(
                        media: $media,
                        alt: $alt,
                        position: $position
                    )
                );
            },
            'Ошибки при создании скриншота'
        );

        return new CreatedDTO(
            $response->screenshot->getId()
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'screenshot', method: 'PUT')]
    public function updateScreenshot(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id,
        #[API\Parameter(source: 'body', name: 'image')]
        ?array $image = null,
        #[API\Parameter(source: 'body', name: 'alt')]
        string $alt = '',
        #[API\Parameter(source: 'body', name: 'position')]
        int $position = 0
    ): SuccessfulResultDTO {
        // image.id = 0 приходит для существующей картинки скриншота (см. ScreenshotDataBuilder),
        // media ищем только для свежей загрузки
        $media = ($image !== null) && ((int) ($image['id'] ?? 0) > 0)
            ? $this->requireMedia($image, 'Ошибки при сохранении скриншота')
            : null;

        $this->handleActionWithException(
            static function () use ($id, $media, $alt, $position) {
                $action = Container::getInstance()->getService(ISaveScreenshotAction::class);
                $action->run(
                    new SaveScreenshotRequest(
                        id: $id,
                        alt: $alt,
                        position: $position,
                        media: $media
                    )
                );
            },
            'Ошибки при сохранении скриншота'
        );

        return new SuccessfulResultDTO();
    }

    #[API\Endpoint(path: 'screenshots', method: 'DELETE')]
    public function delete(
        #[API\Parameter(source: 'body', name: 'rows')]
        array $rows
    ): SuccessfulResultDTO {
        // Удаляем через remove() каждой модели, а не bulk-запросом:
        // Media::remove() заодно удаляет файл с диска
        $this->handleWithException(
            static function () use ($rows) {
                foreach (Screenshot::find(['id' => $rows]) as $screenshot) {
                    $screenshot->remove();
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
}
