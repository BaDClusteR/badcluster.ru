<?php

declare(strict_types=1);

namespace BC\Modules\Games\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\DTO\ApiEndpointArgumentFileDTO;
use ApiPlatform\Exception\BadRequestException;
use BC\Api\DTO\CreatedDTO;
use BC\Api\DTO\FileDTO;
use BC\Api\DTO\ListResponseDTO;
use BC\Api\DTO\SuccessfulResultDTO;
use BC\Api\Endpoint\AEndpoint;
use BC\Api\Exception\NotFoundException;
use BC\Core\Converter\IDateConverter;
use BC\Core\Formatter\IFormatter;
use BC\Exception\UnprocessableEntityException;
use BC\Modules\Games\Api\DataBuilder\GameMaterial\IGameMaterialDataBuilder;
use BC\Modules\Games\Api\DTO\GameDTO;
use BC\Modules\Games\Api\DTO\GameMaterialDTO;
use BC\Modules\Games\Api\DTO\GameMaterialGameDTO;
use BC\Modules\Games\Api\DTO\GameMaterialGamesDTO;
use BC\Modules\Games\Api\DTO\GameMaterialRowDTO;
use BC\Modules\Games\Model\Game as GameModel;
use BC\Modules\Games\Model\GameMaterial as GameMaterialModel;
use BC\Modules\Games\Model\GameMaterialFile;
use Runway\DataStorage\QueryBuilder\Enum\ExpressionJoinConditionTypeEnum;

class GameMaterial extends AEndpoint {
    public function __construct(
        private readonly IGameMaterialDataBuilder $dataBuilder,
        private readonly IDateConverter $dateConverter
    ) {
    }

    /**
     * @return ListResponseDTO<GameMaterialRowDTO>
     *
     * @throws BadRequestException
     */
    #[API\Endpoint(path: 'materials', method: 'GET')]
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
            $sortBy = 'game_title';
        }

        if (!$sortDir) {
            $sortDir = 'ASC';
        }

        $qb = GameMaterialModel::getQueryBuilder('gm');

        $this->addFilter($qb, $filter, ['gm.title', 'g.title', 'annotation'])
             ->leftJoin('games', 'g', ExpressionJoinConditionTypeEnum::ON, 'g.id = gm.game_id');
        $total = $this->setSortLimitAndGetTotal(
            $qb,
            $sortBy,
            $sortDir,
            $page,
            $perPage,
            ['game_title', 'date_added']
        );
        $qb->select('gm.*, g.title AS game_title');

        return $this->handleWithException(
            fn () => new ListResponseDTO(
                items: array_map(
                    fn (array $material): GameMaterialRowDTO => $this->dataBuilder->buildRow($material),
                    $qb->getResults()
                ),
                total: $total
            )
        );
    }

    /**
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'material', method: 'GET')]
    public function getOne(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id
    ): GameMaterialDTO {
        return $this->getEntity(
            GameMaterialModel::class,
            $id,
            'Материал #{{id}} не найден.',
            fn (GameMaterialModel $material): GameMaterialDTO => $this->dataBuilder->buildEntity($material)
        );
    }

    #[API\Endpoint(path: 'material_games', method: 'GET')]
    public function getMaterialGames(): GameMaterialGamesDTO {
        return $this->handleWithException(
            static fn () => new GameMaterialGamesDTO(
                games: array_map(
                    static fn (GameModel $game): GameMaterialGameDTO => new GameMaterialGameDTO(
                        id: $game->getId(),
                        title: $game->getTitle(),
                        cover: $game->getCover()?->toMediaDTO(),
                        slug: $game->getSlug()
                    ),
                    GameModel::find([], ['title', 'ASC'])
                )
            )
        );
    }

    #[API\Endpoint(path: 'material_upload', method: 'POST')]
    public function upload(
        #[API\Parameter(source: 'file', name: 'file')]
        ApiEndpointArgumentFileDTO $file
    ): FileDTO {
        $model = $this->handleWithException(
            static fn () => GameMaterialFile::createFrom(
                $file->tmpName,
                $file->name,
                $file->mimeType
            )
        );

        $this->handleWithException(
            static fn () => $model->persist()
        );

        return $model->toFileDTO();
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'material', method: 'POST')]
    public function create(
        #[API\Parameter(source: 'body', name: 'annotation')]
        string $annotation,
        #[API\Parameter(source: 'body', name: 'gameId')]
        int $gameId,
        #[API\Parameter(source: 'body', name: 'shortTitle')]
        string $shortTitle,
        #[API\Parameter(source: 'body', name: 'type')]
        string $type,
        #[API\Parameter(source: 'body', name: 'description')]
        array $description = [],
        #[API\Parameter(source: 'body', name: 'setupInstructions')]
        array $setupInstructions = [],
        #[API\Parameter(source: 'body', name: 'file')]
        ?array $file = null,
        #[API\Parameter(source: 'body', name: 'url')]
        string $url = '',
        #[API\Parameter(source: 'body', name: 'title')]
        string $title = '',
        #[API\Parameter(source: 'body', name: 'slug')]
        string $slug = '',
        #[API\Parameter(source: 'body', name: 'dateAdded')]
        ?string $dateAdded = null
    ): CreatedDTO {
        $this->validate(
            $type,
            $title,
            $description,
            $gameId,
            $setupInstructions,
            $file,
            $url,
            $slug,
            null,
            'Ошибки при создании материала'
        );

        $materialFile = $this->getFile($file);
        $game = $this->getGame($gameId);
        $material = $this->handleWithException(
            fn () => new GameMaterialModel()
                ->setTitle($title)
                ->setSlug($slug)
                ->setShortTitle($shortTitle)
                ->setAnnotation($annotation)
                ->setDateAdded(
                    $dateAdded
                        ? $this->dateConverter->toDateTime($dateAdded)
                        : null
                )->setGame($game)
                ->setType($type)
                ->setDescription($description)
                ->setSetupInstructions($setupInstructions)
                ->setFile($materialFile)
                ->setUrl($url)
        );

        $this->handleWithException(
            static fn () => $material->persist()
        );

        return new CreatedDTO(
            id: $material->getId()
        );
    }

    /**
     * @throws UnprocessableEntityException
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'material', method: 'PUT')]
    public function update(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id,
        #[API\Parameter(source: 'body', name: 'annotation')]
        string $annotation,
        #[API\Parameter(source: 'body', name: 'gameId')]
        int $gameId,
        #[API\Parameter(source: 'body', name: 'shortTitle')]
        string $shortTitle,
        #[API\Parameter(source: 'body', name: 'type')]
        string $type,
        #[API\Parameter(source: 'body', name: 'dateAdded')]
        ?string $dateAdded = null,
        #[API\Parameter(source: 'body', name: 'title')]
        string $title = '',
        #[API\Parameter(source: 'body', name: 'slug')]
        string $slug = '',
        #[API\Parameter(source: 'body', name: 'description')]
        array $description = [],
        #[API\Parameter(source: 'body', name: 'setupInstructions')]
        array $setupInstructions = [],
        #[API\Parameter(source: 'body', name: 'file')]
        ?array $file = null,
        #[API\Parameter(source: 'body', name: 'url')]
        string $url = '',
    ): SuccessfulResultDTO {
        $this->validate(
            $type,
            $title,
            $description,
            $gameId,
            $setupInstructions,
            $file,
            $url,
            $slug,
            $id,
            'Ошибки при сохранении материала'
        );

        /** @var GameMaterialModel|null $material */
        $material = $this->handleWithException(
            static fn () => GameMaterialModel::findByUniqueIdentifier($id)
        );

        if (!$material) {
            throw new NotFoundException("Материал #$id не найден.");
        }

        $game = $this->getGame($gameId);
        $this->handleWithException(
            fn () => $material
                ->setTitle($title)
                ->setSlug($slug)
                ->setShortTitle($shortTitle)
                ->setAnnotation($annotation)
                ->setDateAdded(
                    $dateAdded
                        ? $this->dateConverter->toDateTime($dateAdded)
                        : null
                )->setGame($game)
                ->setType($type)
                ->setDescription($description)
                ->setSetupInstructions($setupInstructions)
                ->setFile(
                    $this->getFile($file)
                )->setUrl($url)
                ->persist()
        );

        return new SuccessfulResultDTO();
    }

    #[API\Endpoint(path: 'materials', method: 'DELETE')]
    public function delete(
        #[API\Parameter(source: 'body', name: 'rows')]
        array $rows
    ): SuccessfulResultDTO {
        $this->deleteEntities(GameMaterialModel::class, $rows);

        return new SuccessfulResultDTO();
    }

    /**
     * @throws UnprocessableEntityException
     */
    protected function getFile(?array $file): ?GameMaterialFile {
        $materialFile = null;
        if (!empty($file['id'])) {
            $materialFile = $this->handleWithException(
                static fn () => GameMaterialFile::findByUniqueIdentifier(
                    (int) ($file['id'])
                )
            );

            if (!$materialFile) {
                throw new UnprocessableEntityException(
                    ['file' => "Файл #{$file['id']} не найден"],
                    "Ошибка: файл #{$file['id']} не найден."
                );
            }
        }

        return $materialFile;
    }

    /**
     * @throws UnprocessableEntityException
     */
    protected function getGame(int $gameId): GameModel {
        $game = $this->handleWithException(
            static fn () => GameModel::findByUniqueIdentifier($gameId)
        );

        if (!$game) {
            throw new UnprocessableEntityException(
                ['gameId' => "Игра #$gameId не найдена"],
                "Ошибка: игра #$gameId не найдена."
            );
        }

        return $game;
    }

    /**
     * @throws UnprocessableEntityException
     */
    protected function validate(
        string $type,
        string $title,
        array $description,
        int $gameId,
        array $setupInstructions,
        ?array $file,
        string $url,
        string $slug,
        ?int $id,
        string $errorPrefix,
    ): void {
        $errors = [];

        if ($type === GameMaterialModel::TYPE_FILE && empty($title)) {
            $errors['title'] = 'Укажите заголовок';
        }

        if (empty($gameId)) {
            $errors['gameId'] = 'Выберите игру';
        }

        if ($type === GameMaterialModel::TYPE_FILE && empty($file)) {
            $errors['file'] = 'Загрузите файл';
        }

        if ($type === GameMaterialModel::TYPE_ARTICLE && empty($url)) {
            $errors['url'] = 'Добавьте URL статьи';
        }

        $qb = GameMaterialModel::getQueryBuilder()
                               ->where('game_id = :gameId')
                               ->setVariable('gameId', $gameId);

        if ($this->getEntityBySlug(GameMaterialModel::class, $slug, $id, $qb)) {
            $errors['slug'] = 'Этот слаг уже занят другим материалом';
        }

        if (!empty($errors)) {
            throw new UnprocessableEntityException(
                $errors,
                $errorPrefix . ': ' . implode(
                    ', ',
                    array_map(
                        static fn (string $error): string => strtolower($error),
                        $errors
                    )
                )
            );
        }
    }
}
