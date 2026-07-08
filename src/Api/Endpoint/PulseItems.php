<?php

declare(strict_types=1);

namespace BC\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Attribute\Docs;
use ApiPlatform\Exception\BadRequestException;
use BC\Api\DataBuilder\PulseItem\IPulseItemDataBuilder;
use BC\Api\DTO\CreatedDTO;
use BC\Api\DTO\GetEntitiesListRequest;
use BC\Api\DTO\ListResponseDTO;
use BC\Api\DTO\PulseItem\PulseItemDTO;
use BC\Api\DTO\PulseItem\PulseItemRowDTO;
use BC\Api\DTO\SuccessfulResultDTO;
use BC\Api\Exception\NotFoundException;
use BC\Core\Action\DTO\CreatePulseItemRequest;
use BC\Core\Action\DTO\SavePulseItemRequest;
use BC\Core\Action\PulseItem\ICreatePulseItemAction;
use BC\Core\Action\PulseItem\ISavePulseItemAction;
use BC\Exception\UnprocessableEntityException;
use BC\Model\Media;
use BC\Model\PulseItem;
use Runway\Singleton\Container;

#[Docs\Group('Pulse')]
class PulseItems extends AEndpoint {
    public function __construct(
        private readonly IPulseItemDataBuilder $dataBuilder
    ) {
    }

    /**
     * @return ListResponseDTO<PulseItemRowDTO>
     *
     * @throws BadRequestException
     */
    #[API\Endpoint(path: 'pulse_items', method: 'GET')]
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
                qb: PulseItem::getQueryBuilder()->orderBy('position', 'ASC'),
                filter: $filter,
                columnsToFind: ['title', 'text'],
                sortBy: $sortBy,
                sortDir: $sortDir,
                page: $page,
                perPage: $perPage,
                sortableColumns: ['title', 'position']
            ),
            fn (PulseItem $item): PulseItemRowDTO => $this->dataBuilder->buildRow($item)
        );
    }

    /**
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'pulse_item', method: 'GET')]
    public function getOne(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id
    ): PulseItemDTO {
        return $this->getEntity(
            PulseItem::class,
            $id,
            'Элемент пульса #{{id}} не найден.',
            fn (PulseItem $item): PulseItemDTO => $this->dataBuilder->buildEntity($item)
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'pulse_item', method: 'POST')]
    public function createItem(
        #[API\Parameter(source: 'body', name: 'image')]
        ?array $image = null,
        #[API\Parameter(source: 'body', name: 'tag')]
        string $tag = '',
        #[API\Parameter(source: 'body', name: 'title')]
        string $title = '',
        #[API\Parameter(source: 'body', name: 'text')]
        string $text = '',
        #[API\Parameter(source: 'body', name: 'statusTitle')]
        string $statusTitle = '',
        #[API\Parameter(source: 'body', name: 'statusText')]
        string $statusText = '',
        #[API\Parameter(source: 'body', name: 'icon')]
        string $icon = '',
        #[API\Parameter(source: 'body', name: 'position')]
        int $position = 0,
        #[API\Parameter(source: 'body', name: 'url')]
        string $url = '',
        #[API\Parameter(source: 'body', name: 'isTall')]
        bool $isTall = false,
        #[API\Parameter(source: 'body', name: 'isSurfaced')]
        bool $isSurfaced = false
    ): CreatedDTO {
        $media = $this->requireMediaIfGiven($image, 'Ошибки при создании элемента');

        $response = null;
        $this->handleActionWithException(
            function () use (&$response, $media, $tag, $title, $text, $statusTitle, $statusText, $icon, $position, $url, $isTall, $isSurfaced) {
                $action = Container::getInstance()->getService(ICreatePulseItemAction::class);
                $response = $action->run(
                    new CreatePulseItemRequest(
                        image: $media,
                        tag: $tag,
                        title: $title,
                        text: $text,
                        statusTitle: $statusTitle,
                        statusText: $statusText,
                        icon: $icon,
                        position: $position,
                        url: $url,
                        isTall: $isTall,
                        isSurfaced: $isSurfaced
                    )
                );
            },
            'Ошибки при создании элемента'
        );

        return new CreatedDTO(
            $response->item->getId()
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'pulse_item', method: 'PUT')]
    public function updateItem(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id,
        #[API\Parameter(source: 'body', name: 'image')]
        ?array $image = null,
        #[API\Parameter(source: 'body', name: 'tag')]
        string $tag = '',
        #[API\Parameter(source: 'body', name: 'title')]
        string $title = '',
        #[API\Parameter(source: 'body', name: 'text')]
        string $text = '',
        #[API\Parameter(source: 'body', name: 'statusTitle')]
        string $statusTitle = '',
        #[API\Parameter(source: 'body', name: 'statusText')]
        string $statusText = '',
        #[API\Parameter(source: 'body', name: 'icon')]
        string $icon = '',
        #[API\Parameter(source: 'body', name: 'position')]
        int $position = 0,
        #[API\Parameter(source: 'body', name: 'url')]
        string $url = '',
        #[API\Parameter(source: 'body', name: 'isTall')]
        bool $isTall = false,
        #[API\Parameter(source: 'body', name: 'isSurfaced')]
        bool $isSurfaced = false
    ): SuccessfulResultDTO {
        $media = $this->requireMediaIfGiven($image, 'Ошибки при сохранении элемента');

        $this->handleActionWithException(
            static function () use ($id, $media, $tag, $title, $text, $statusTitle, $statusText, $icon, $position, $url, $isTall, $isSurfaced) {
                $action = Container::getInstance()->getService(ISavePulseItemAction::class);
                $action->run(
                    new SavePulseItemRequest(
                        id: $id,
                        image: $media,
                        tag: $tag,
                        title: $title,
                        text: $text,
                        statusTitle: $statusTitle,
                        statusText: $statusText,
                        icon: $icon,
                        position: $position,
                        url: $url,
                        isTall: $isTall,
                        isSurfaced: $isSurfaced
                    )
                );
            },
            'Ошибки при сохранении элемента'
        );

        return new SuccessfulResultDTO();
    }

    #[API\Endpoint(path: 'pulse_items', method: 'DELETE')]
    public function delete(
        #[API\Parameter(source: 'body', name: 'rows')]
        array $rows
    ): SuccessfulResultDTO {
        // Картинки живут в медиа-библиотеке и элементу не принадлежат,
        // так что bulk-удаления строк достаточно
        $this->deleteEntities(PulseItem::class, $rows);

        return new SuccessfulResultDTO();
    }

    /**
     * Картинка опциональна: null — элемент без картинки,
     * а вот переданная картинка обязана существовать в медиа-библиотеке.
     *
     * @throws UnprocessableEntityException
     */
    private function requireMediaIfGiven(?array $image, string $errorTitle): ?Media {
        if ($image === null) {
            return null;
        }

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
