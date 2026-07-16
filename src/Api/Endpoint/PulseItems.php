<?php

declare(strict_types=1);

namespace BC\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Attribute\Docs;
use ApiPlatform\Exception\BadRequestException;
use BC\Api\DataBuilder\PulseItem\IPulseItemDataBuilder;
use BC\Api\DTO\CreatedDTO;
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
use BC\Provider\IPulseItemsProvider;
use Runway\Singleton\Container;

#[Docs\Group('Pulse')]
class PulseItems extends AEndpoint {
    public function __construct(
        private readonly IPulseItemDataBuilder $dataBuilder,
        private readonly IPulseItemsProvider $pulseItemsProvider
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
        if ($sortBy && !in_array($sortBy, ['title', 'position'], true)) {
            throw new BadRequestException(
                sprintf("Не могу сортировать по '%s'.", $sortBy)
            );
        }

        // К ручным элементам из базы подмешиваются автоматические из
        // IPulseItemsProvider, поэтому фильтр, сортировка и страницы
        // считаются в PHP по объединенному списку — элементов пульса
        // в любом случае единицы
        $rows = [
            ...$this->getManualRows($filter),
            ...$this->getAutoRows($filter),
        ];

        $this->sortRows($rows, $sortBy ?: 'position', $this->sanitizeSortDirection($sortDir));

        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        return new ListResponseDTO(
            items: array_slice($rows, ($page - 1) * $perPage, $perPage),
            total: count($rows)
        );
    }

    /**
     * @return PulseItemRowDTO[]
     */
    private function getManualRows(string $filter): array {
        $qb = PulseItem::getQueryBuilder();
        $this->addFilter($qb, $filter, ['title', 'text']);

        return array_map(
            fn (PulseItem $item): PulseItemRowDTO => $this->dataBuilder->buildRow($item),
            $this->handleWithException(
                static fn (): array => $qb->getEntities()
            )
        );
    }

    /**
     * У авто-элементов нет записей в базе — id им раздаются отрицательные,
     * чтобы фронт мог отличить их от ручных и не пытаться редактировать.
     *
     * @return PulseItemRowDTO[]
     */
    private function getAutoRows(string $filter): array {
        $items = $this->handleWithException(
            fn (): array => $this->pulseItemsProvider->getPulseItems()
        );

        $filter = mb_strtolower(trim($filter));
        $rows = [];

        foreach (array_values($items) as $i => $item) {
            $matchesFilter = !$filter
                || str_contains(mb_strtolower($item->title), $filter)
                || str_contains(mb_strtolower($item->text), $filter);

            if ($matchesFilter) {
                $rows[] = $this->dataBuilder->buildAutoRow($item, -($i + 1));
            }
        }

        return $rows;
    }

    /**
     * @param PulseItemRowDTO[] $rows
     */
    private function sortRows(array &$rows, string $sortBy, string $sortDir): void {
        usort(
            $rows,
            static function (PulseItemRowDTO $a, PulseItemRowDTO $b) use ($sortBy, $sortDir): int {
                $result = $sortBy === 'title'
                    ? mb_strtolower($a->title) <=> mb_strtolower($b->title)
                    : $a->position <=> $b->position;

                return $sortDir === 'DESC' ? -$result : $result;
            }
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
