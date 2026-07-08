<?php

declare(strict_types=1);

namespace BC\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Attribute\Docs;
use ApiPlatform\Exception\BadRequestException;
use BC\Api\DataBuilder\Redirect\IRedirectDataBuilder;
use BC\Api\DTO\CreatedDTO;
use BC\Api\DTO\GetEntitiesListRequest;
use BC\Api\DTO\ListResponseDTO;
use BC\Api\DTO\Redirect\RedirectDTO;
use BC\Api\DTO\Redirect\RedirectRowDTO;
use BC\Api\DTO\SuccessfulResultDTO;
use BC\Api\Exception\NotFoundException;
use BC\Core\Action\DTO\CreateRedirectRequest;
use BC\Core\Action\DTO\SaveRedirectRequest;
use BC\Core\Action\Redirect\ICreateRedirectAction;
use BC\Core\Action\Redirect\ISaveRedirectAction;
use BC\Exception\UnprocessableEntityException;
use BC\Model\Redirect;
use Runway\Singleton\Container;

#[Docs\Group('Redirects')]
class Redirects extends AEndpoint {
    public function __construct(
        private readonly IRedirectDataBuilder $dataBuilder
    ) {
    }

    /**
     * @return ListResponseDTO<RedirectRowDTO>
     *
     * @throws BadRequestException
     */
    #[API\Endpoint(path: 'redirects', method: 'GET')]
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
                qb: Redirect::getQueryBuilder()->orderBy('path', 'ASC'),
                filter: $filter,
                columnsToFind: ['path', 'destination'],
                sortBy: $sortBy,
                sortDir: $sortDir,
                page: $page,
                perPage: $perPage,
                sortableColumns: ['path', 'code']
            ),
            fn (Redirect $redirect): RedirectRowDTO => $this->dataBuilder->buildRow($redirect)
        );
    }

    /**
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'redirect', method: 'GET')]
    public function getOne(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id
    ): RedirectDTO {
        return $this->getEntity(
            Redirect::class,
            $id,
            'Редирект #{{id}} не найден.',
            fn (Redirect $redirect): RedirectDTO => $this->dataBuilder->buildEntity($redirect)
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'redirect', method: 'POST')]
    public function createRedirect(
        #[API\Parameter(source: 'body', name: 'path')]
        string $path,
        #[API\Parameter(source: 'body', name: 'code')]
        int $code,
        #[API\Parameter(source: 'body', name: 'destination')]
        string $destination = ''
    ): CreatedDTO {
        $response = null;
        $this->handleActionWithException(
            function () use (&$response, $path, $code, $destination) {
                $action = Container::getInstance()->getService(ICreateRedirectAction::class);
                $response = $action->run(
                    new CreateRedirectRequest(
                        path: $path,
                        code: $code,
                        destination: $destination
                    )
                );
            },
            'Ошибки при создании редиректа'
        );

        return new CreatedDTO(
            $response->redirect->getId()
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'redirect', method: 'PUT')]
    public function updateRedirect(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id,
        #[API\Parameter(source: 'body', name: 'path')]
        string $path,
        #[API\Parameter(source: 'body', name: 'code')]
        int $code,
        #[API\Parameter(source: 'body', name: 'destination')]
        string $destination = ''
    ): SuccessfulResultDTO {
        $this->handleActionWithException(
            static function () use ($id, $path, $code, $destination) {
                $action = Container::getInstance()->getService(ISaveRedirectAction::class);
                $action->run(
                    new SaveRedirectRequest(
                        id: $id,
                        path: $path,
                        code: $code,
                        destination: $destination
                    )
                );
            },
            'Ошибки при сохранении редиректа'
        );

        return new SuccessfulResultDTO();
    }

    #[API\Endpoint(path: 'redirects', method: 'DELETE')]
    public function delete(
        #[API\Parameter(source: 'body', name: 'rows')]
        array $rows
    ): SuccessfulResultDTO {
        $this->deleteEntities(Redirect::class, $rows);

        return new SuccessfulResultDTO();
    }
}