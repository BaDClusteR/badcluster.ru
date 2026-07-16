<?php

declare(strict_types=1);

namespace BC\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Attribute\Docs;
use ApiPlatform\Exception\BadRequestException;
use BC\Api\DataBuilder\Fact\IFactDataBuilder;
use BC\Api\DTO\CreatedDTO;
use BC\Api\DTO\Fact\FactDTO;
use BC\Api\DTO\Fact\FactRowDTO;
use BC\Api\DTO\GetEntitiesListRequest;
use BC\Api\DTO\ListResponseDTO;
use BC\Api\DTO\SuccessfulResultDTO;
use BC\Api\Exception\NotFoundException;
use BC\Core\Action\DTO\CreateFactRequest;
use BC\Core\Action\DTO\SaveFactRequest;
use BC\Core\Action\Fact\ICreateFactAction;
use BC\Core\Action\Fact\ISaveFactAction;
use BC\Exception\UnprocessableEntityException;
use BC\Model\Fact;
use Runway\Singleton\Container;

#[Docs\Group('Facts')]
class Facts extends AEndpoint {
    public function __construct(
        private readonly IFactDataBuilder $dataBuilder
    ) {
    }

    /**
     * @return ListResponseDTO<FactRowDTO>
     *
     * @throws BadRequestException
     */
    #[API\Endpoint(path: 'facts', method: 'GET')]
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
                qb: Fact::getQueryBuilder()->orderBy('id', 'DESC'),
                filter: $filter,
                columnsToFind: ['title', 'content'],
                sortBy: $sortBy,
                sortDir: $sortDir,
                page: $page,
                perPage: $perPage,
                sortableColumns: []
            ),
            fn (Fact $fact): FactRowDTO => $this->dataBuilder->buildRow($fact)
        );
    }

    /**
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'fact', method: 'GET')]
    public function getOne(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id
    ): FactDTO {
        return $this->getEntity(
            Fact::class,
            $id,
            'Факт #{{id}} не найден.',
            fn (Fact $fact): FactDTO => $this->dataBuilder->buildEntity($fact)
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'fact', method: 'POST')]
    public function createFact(
        #[API\Parameter(source: 'body', name: 'title')]
        string $title,
        #[API\Parameter(source: 'body', name: 'content')]
        string $content
    ): CreatedDTO {
        $response = null;
        $this->handleActionWithException(
            function () use (&$response, $title, $content) {
                $action = Container::getInstance()->getService(ICreateFactAction::class);
                $response = $action->run(
                    new CreateFactRequest(
                        title: $title,
                        content: $content
                    )
                );
            },
            'Ошибки при создании факта'
        );

        return new CreatedDTO(
            $response->fact->getId()
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'fact', method: 'PUT')]
    public function updateFact(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id,
        #[API\Parameter(source: 'body', name: 'title')]
        string $title,
        #[API\Parameter(source: 'body', name: 'content')]
        string $content
    ): SuccessfulResultDTO {
        $this->handleActionWithException(
            static function () use ($id, $title, $content) {
                $action = Container::getInstance()->getService(ISaveFactAction::class);
                $action->run(
                    new SaveFactRequest(
                        id: $id,
                        title: $title,
                        content: $content
                    )
                );
            },
            'Ошибки при сохранении факта'
        );

        return new SuccessfulResultDTO();
    }

    #[API\Endpoint(path: 'facts', method: 'DELETE')]
    public function delete(
        #[API\Parameter(source: 'body', name: 'rows')]
        array $rows
    ): SuccessfulResultDTO {
        $this->deleteEntities(Fact::class, $rows);

        return new SuccessfulResultDTO();
    }
}
