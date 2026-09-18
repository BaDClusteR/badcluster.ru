<?php

declare(strict_types=1);

namespace BC\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Attribute\Docs;
use ApiPlatform\Exception\BadRequestException;
use BC\Api\DataBuilder\StaticPage\IStaticPageDataBuilder;
use BC\Api\DTO\CreatedDTO;
use BC\Api\DTO\GetEntitiesListRequest;
use BC\Api\DTO\ListResponseDTO;
use BC\Api\DTO\StaticPage\StaticPageDTO;
use BC\Api\DTO\StaticPage\StaticPageRowDTO;
use BC\Api\DTO\SuccessfulResultDTO;
use BC\Api\Exception\NotFoundException;
use BC\Core\Action\DTO\CreateStaticPageRequest;
use BC\Core\Action\DTO\SaveStaticPageRequest;
use BC\Core\Action\StaticPage\ICreateStaticPageAction;
use BC\Core\Action\StaticPage\ISaveStaticPageAction;
use BC\Core\Converter\IDateConverter;
use BC\Exception\UnprocessableEntityException;
use BC\Model\StaticPage;
use DateTime;
use Runway\Singleton\Container;

#[Docs\Group('StaticPages')]
class StaticPages extends AEndpoint {
    public function __construct(
        private readonly IStaticPageDataBuilder $dataBuilder,
        private readonly IDateConverter $dateConverter
    ) {
    }

    /**
     * @return ListResponseDTO<StaticPageRowDTO>
     *
     * @throws BadRequestException
     */
    #[API\Endpoint(path: 'static-pages', method: 'GET')]
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
                qb: StaticPage::getQueryBuilder()->orderBy('id', 'DESC'),
                filter: $filter,
                columnsToFind: ['title', 'slug'],
                sortBy: $sortBy,
                sortDir: $sortDir,
                page: $page,
                perPage: $perPage,
                sortableColumns: ['title', 'slug', 'published', 'created_date']
            ),
            fn (StaticPage $staticPage): StaticPageRowDTO => $this->dataBuilder->buildRow($staticPage)
        );
    }

    /**
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'static-page', method: 'GET')]
    public function getOne(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id
    ): StaticPageDTO {
        return $this->getEntity(
            StaticPage::class,
            $id,
            'Страница #{{id}} не найдена.',
            fn (StaticPage $staticPage): StaticPageDTO => $this->dataBuilder->buildEntity($staticPage)
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'static-page', method: 'POST')]
    public function createStaticPage(
        #[API\Parameter(source: 'body', name: 'title')]
        string $title,
        #[API\Parameter(source: 'body', name: 'slug')]
        string $slug,
        #[API\Parameter(source: 'body', name: 'shortTitle')]
        string $shortTitle = '',
        #[API\Parameter(source: 'body', name: 'content')]
        array $content = [],
        #[API\Parameter(source: 'body', name: 'published')]
        bool $published = false,
        #[API\Parameter(source: 'body', name: 'indexable')]
        bool $indexable = true,
        #[API\Parameter(source: 'body', name: 'textBlock')]
        bool $textBlock = true,
        #[API\Parameter(source: 'body', name: 'publishDate')]
        string $publishDate = '',
        #[API\Parameter(source: 'body', name: 'metaDescription')]
        string $metaDescription = '',
        #[API\Parameter(source: 'body', name: 'backLinkText')]
        string $backLinkText = '',
        #[API\Parameter(source: 'body', name: 'backLinkUrl')]
        string $backLinkUrl = ''
    ): CreatedDTO {
        $response = null;
        $request = new CreateStaticPageRequest(
            title: $title,
            shortTitle: $shortTitle,
            content: $content,
            slug: $slug,
            published: $published,
            indexable: $indexable,
            textBlock: $textBlock,
            publishDate: $this->toPublishDate($publishDate),
            metaDescription: $metaDescription,
            backLinkText: $backLinkText,
            backLinkUrl: $backLinkUrl
        );

        $this->handleActionWithException(
            function () use (&$response, $request) {
                $action = Container::getInstance()->getService(ICreateStaticPageAction::class);
                $response = $action->run($request);
            },
            'Ошибки при создании страницы'
        );

        return new CreatedDTO(
            $response->staticPage->getId()
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'static-page', method: 'PUT')]
    public function updateStaticPage(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id,
        #[API\Parameter(source: 'body', name: 'title')]
        string $title,
        #[API\Parameter(source: 'body', name: 'slug')]
        string $slug,
        #[API\Parameter(source: 'body', name: 'shortTitle')]
        string $shortTitle = '',
        #[API\Parameter(source: 'body', name: 'content')]
        array $content = [],
        #[API\Parameter(source: 'body', name: 'published')]
        bool $published = false,
        #[API\Parameter(source: 'body', name: 'indexable')]
        bool $indexable = true,
        #[API\Parameter(source: 'body', name: 'textBlock')]
        bool $textBlock = true,
        #[API\Parameter(source: 'body', name: 'publishDate')]
        string $publishDate = '',
        #[API\Parameter(source: 'body', name: 'metaDescription')]
        string $metaDescription = '',
        #[API\Parameter(source: 'body', name: 'backLinkText')]
        string $backLinkText = '',
        #[API\Parameter(source: 'body', name: 'backLinkUrl')]
        string $backLinkUrl = ''
    ): SuccessfulResultDTO {
        $request = new SaveStaticPageRequest(
            id: $id,
            title: $title,
            shortTitle: $shortTitle,
            content: $content,
            slug: $slug,
            published: $published,
            indexable: $indexable,
            textBlock: $textBlock,
            publishDate: $this->toPublishDate($publishDate),
            metaDescription: $metaDescription,
            backLinkText: $backLinkText,
            backLinkUrl: $backLinkUrl
        );

        $this->handleActionWithException(
            static function () use ($request) {
                $action = Container::getInstance()->getService(ISaveStaticPageAction::class);
                $action->run($request);
            },
            'Ошибки при сохранении страницы'
        );

        return new SuccessfulResultDTO();
    }

    #[API\Endpoint(path: 'static-pages', method: 'DELETE')]
    public function delete(
        #[API\Parameter(source: 'body', name: 'rows')]
        array $rows
    ): SuccessfulResultDTO {
        $this->deleteEntities(StaticPage::class, $rows);

        return new SuccessfulResultDTO();
    }

    /** The publish date is optional: an empty picker means "no date at all". */
    private function toPublishDate(string $publishDate): ?DateTime {
        return trim($publishDate) === ''
            ? null
            : $this->dateConverter->toDateTime($publishDate);
    }
}
