<?php

declare(strict_types=1);

namespace BC\Modules\Games\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Exception\BadRequestException;
use BC\Api\DTO\CreatedDTO;
use BC\Api\DTO\ListResponseDTO;
use BC\Api\DTO\SuccessfulResultDTO;
use BC\Api\Endpoint\AEndpoint;
use BC\Api\Exception\NotFoundException;
use BC\Exception\UnprocessableEntityException;
use BC\Modules\Games\Api\DataBuilder\Game\IGameDataBuilder;
use BC\Modules\Games\Api\DTO\GameDTO;
use BC\Modules\Games\Api\DTO\GameRowDTO;
use BC\Modules\Games\Model\Game as GameModel;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Enum\ExpressionJoinConditionTypeEnum;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;

class Game extends AEndpoint {
    public function __construct(
        private readonly IGameDataBuilder $dataBuilder,
    ) {
    }

    /**
     * @return ListResponseDTO<GameRowDTO>
     *
     * @throws BadRequestException
     */
    #[API\Endpoint(path: 'games', method: 'GET')]
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
        if (!$sortBy || $sortBy === 'title') {
            $sortBy = 'g.title';
        }

        if (!$sortDir) {
            $sortDir = 'ASC';
        }

        $qb = GameModel::getQueryBuilder('g')
                       ->leftJoin('game_materials', 'gm', ExpressionJoinConditionTypeEnum::ON, 'gm.game_id = g.id');

        $this->addFilter($qb, $filter, ['g.title']);
        $this->setSortLimitAndGetTotal(
            $qb,
            $sortBy,
            $sortDir,
            $page,
            $perPage,
            ['g.title', 'count']
        );
        $total = $this->handleWithException(
            static fn () => GameModel::getQueryBuilder()->count()
        );
        $qb->select('g.*, (SELECT COUNT(*) FROM `{game_materials}` WHERE game_id = g.id) AS count')
           ->groupBy('g.id');

        return $this->handleWithException(
            fn () => new ListResponseDTO(
                items: array_map(
                    fn (array $tag): GameRowDTO => $this->dataBuilder->buildRow($tag),
                    $qb->getResults()
                ),
                total: $total
            )
        );
    }

    /**
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'game', method: 'GET')]
    public function getOne(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id
    ): GameDTO {
        return $this->getEntity(
            GameModel::class,
            $id,
            'Игра #{{id}} не найдена.',
            fn (GameModel $game): GameDTO => $this->dataBuilder->buildEntity($game)
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'game', method: 'POST')]
    public function create(
        #[API\Parameter(source: 'body', name: 'title')]
        string $title,
        #[API\Parameter(source: 'body', name: 'slug')]
        string $slug,
        #[API\Parameter(source: 'body', name: 'releaseYear')]
        ?string $releaseYear = null,
        #[API\Parameter(source: 'body', name: 'cover')]
        ?array $coverImage = null,
    ): CreatedDTO {
        $this->validateEntity($slug, $title, null, 'Ошибки при добавлении игры.');

        $game = $this->handleWithException(
            fn () => new GameModel()
                ->setTitle($title)
                ->setSlug($slug)
                ->setReleaseYear(
                    $releaseYear
                        ? (int) $releaseYear
                        : null
                )->setCover(
                    $this->findMedia($coverImage)
                )
        );

        $this->handleWithException(
            static fn () => $game->persist()
        );

        return new CreatedDTO(
            id: $game->getId()
        );
    }

    /**
     * @throws UnprocessableEntityException
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'game', method: 'PUT')]
    public function update(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id,
        #[API\Parameter(source: 'body', name: 'title')]
        string $title,
        #[API\Parameter(source: 'body', name: 'slug')]
        string $slug,
        #[API\Parameter(source: 'body', name: 'releaseYear')]
        ?string $releaseYear = null,
        #[API\Parameter(source: 'body', name: 'cover')]
        ?array $coverImage = null,
    ): SuccessfulResultDTO {
        $this->validateEntity($slug, $title, $id, 'Не могу сохранить изменения.');

        $this->handleWithException(
            function () use ($slug, $title, $releaseYear, $coverImage, $id): void {
                $game = GameModel::findByUniqueIdentifier($id);

                if (!$game) {
                    throw new NotFoundException("Игра #$id не найдена");
                }

                $game->setTitle($title)
                     ->setSlug($slug)
                     ->setReleaseYear(
                         $releaseYear
                             ? (int) $releaseYear
                             : null
                     )->setCover(
                        $this->findMedia($coverImage)
                    );
                $game->persist();
            }
        );

        return new SuccessfulResultDTO();
    }

    /**
     * @throws UnprocessableEntityException
     */
    private function validateEntity(string $slug, string $title, ?int $id, string $errorTitle): void {
        $errors = [];

        if (
            $this->handleWithException(
                fn () => $this->isExistsGameWithTitle($title, $id)
            )
        ) {
            $errors['title'] = 'Игра с таким названием уже есть.';
        }

        /** @var GameModel|null $gameBySlug */
        if (
            $gameBySlug = $this->handleWithException(
                static function () use ($slug, $id) {
                    $qb = GameModel::getQueryBuilder()
                                   ->where('slug = :slug')
                                   ->setVariable('slug', $slug);
                    if ($id) {
                        $qb = $qb->andWhere('id != :id')
                                 ->setVariable('id', $id);
                    }

                    return $qb->getFirstEntity();
                }
            )
        ) {
            $errors['slug'] = sprintf('Этот слаг уже занят игрой %s', $gameBySlug->getTitle());
        }

        if (!empty($errors)) {
            throw new UnprocessableEntityException($errors, $errorTitle);
        }
    }

    #[API\Endpoint(path: 'games', method: 'DELETE')]
    public function deletePosts(
        #[API\Parameter(source: 'body', name: 'rows')]
        array $rows
    ): SuccessfulResultDTO {
        $this->deleteEntities(GameModel::class, $rows);

        return new SuccessfulResultDTO();
    }

    /**
     * @throws DBException
     * @throws QueryBuilderException
     */
    protected function isExistsGameWithTitle(string $title, ?int $id = null): bool {
        $title = strtolower(trim($title));

        $qb = GameModel::getQueryBuilder()
                       ->where('LOWER(title) = :title')
                       ->setVariable('title', $title);
        if ($id) {
            $qb->andWhere('id != :id')
               ->setVariable('id', $id);
        }

        return ($qb->count() > 0);
    }
}
