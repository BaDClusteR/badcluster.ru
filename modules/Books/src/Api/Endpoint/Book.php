<?php

declare(strict_types=1);

namespace BC\Modules\Books\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Exception\BadRequestException;
use BC\Api\DTO\CreatedDTO;
use BC\Api\DTO\GetEntitiesListRequest;
use BC\Api\DTO\ListResponseDTO;
use BC\Api\DTO\SuccessfulResultDTO;
use BC\Api\Endpoint\AEndpoint;
use BC\Api\Exception\NotFoundException;
use BC\Core\Converter\IDateConverter;
use BC\Exception\UnprocessableEntityException;
use BC\Model\Media;
use BC\Modules\Books\Api\DataBuilder\Book\IBookDataBuilder;
use BC\Modules\Books\Api\DTO\Book\BookDTO;
use BC\Modules\Books\Api\DTO\Book\BookFormatsDTO;
use BC\Modules\Books\Api\DTO\Book\BookRowDTO;
use BC\Modules\Books\Core\DTO\BookFormatDTO;
use BC\Modules\Books\Model\Book as BookModel;
use BC\Modules\Books\Model\BookFormat;
use BC\Modules\Books\Provider\BookFormat\IBookFormatProvider;
use DateTime;
use JsonException;

class Book extends AEndpoint {
    public function __construct(
        private readonly IBookDataBuilder $dataBuilder,
        private readonly IBookFormatProvider $formatProvider,
        private readonly IDateConverter $dateConverter
    ) {
    }

    /**
     * @return ListResponseDTO<BookRowDTO>
     *
     * @throws BadRequestException
     */
    #[API\Endpoint(path: 'books', method: 'GET')]
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
        if ($sortBy === 'lastUpdateDate') {
            $sortBy = 'last_update_date';
        }

        return $this->getEntitiesList(
            new GetEntitiesListRequest(
                qb: BookModel::getQueryBuilder()->orderBy('position', 'ASC'),
                filter: $filter,
                columnsToFind: ['title', 'shortAnnotation', 'annotation'],
                sortBy: $sortBy,
                sortDir: $sortDir,
                page: $page,
                perPage: $perPage,
                sortableColumns: ['title', 'last_update_date'],
            ),
            fn (BookModel $book): BookRowDTO => $this->dataBuilder->buildRow($book)
        );
    }

    /**
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'book', method: 'GET')]
    public function getOne(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id
    ): BookDTO {
        return $this->getEntity(
            BookModel::class,
            $id,
            'Книга #{{id}} не найдена.',
            fn (BookModel $book): BookDTO => $this->dataBuilder->buildEntity($book)
        );
    }

    #[API\Endpoint(path: 'book_formats', method: 'GET')]
    public function getFormats(): BookFormatsDTO {
        return new BookFormatsDTO(
            formats: array_map(
                static fn (BookFormatDTO $format): string => $format->type,
                $this->formatProvider->getFormats()
            )
        );
    }

    /**
     * @throws UnprocessableEntityException
     */
    #[API\Endpoint(path: 'book', method: 'POST')]
    public function create(
        #[API\Parameter(source: 'body', name: 'title')]
        #[API\Assert\NotEmpty]
        string $title,

        #[API\Parameter(source: 'body', name: 'type')]
        #[API\Assert\NotEmpty]
        string $type,

        #[API\Parameter(source: 'body', name: 'annotation')]
        #[API\Assert\NotEmpty]
        string $annotation,

        #[API\Parameter(source: 'body', name: 'slug')]
        #[API\Assert\NotEmpty]
        string $slug,

        #[API\Parameter(source: 'body', name: 'lastUpdateDate')]
        #[API\Assert\NotEmpty]
        string $lastUpdateDate,

        #[API\Parameter(source: 'body', name: 'shortAnnotation')]
        #[API\Assert\NotEmpty]
        string $shortAnnotation,

        #[API\Parameter(source: 'body', name: 'cover')]
        ?array $cover = null,

        #[API\Parameter(source: 'body', name: 'coverBg')]
        ?array $coverBg = null,

        #[API\Parameter(source: 'body', name: 'author')]
        ?string $author = null,

        #[API\Parameter(source: 'body', name: 'technicalInfo')]
        ?string $technicalInfo = '',

        #[API\Parameter(source: 'body', name: 'group')]
        string $group = '',

        #[API\Parameter(source: 'body', name: 'position')]
        int $position = 0,

        #[API\Parameter(source: 'body', name: 'formats')]
        array $formats = []
    ): CreatedDTO {
        $this->validateEntity($slug, $type, null, 'Ошибки при добавлении произведения.');

        try {
            $techInfoArray = json_decode($technicalInfo, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $techInfoArray = [];
        }

        $book = $this->handleWithException(
            fn () => new BookModel()
                ->setSlug($slug)
                ->setTitle($title)
                ->setCover(
                    Media::findByUniqueIdentifier(
                        (int) ($cover['id'] ?? 0)
                    )
                )->setAnnotation($annotation)
                ->setAuthor($author)
                ->setTechnicalInfo($techInfoArray)
                ->setCoverBg(
                    Media::findByUniqueIdentifier(
                        (int) ($coverBg['id'] ?? 0)
                    )
                )->setGroup($group)
                ->setPosition($position)
                ->setLastUpdateDate(
                    $this->dateConverter->toDateTime($lastUpdateDate)
                )->setShortAnnotation($shortAnnotation)
                ->setType($type)
        );

        $allowedFormats = array_map(
            static fn (BookFormatDTO $format): string => $format->type,
            $this->formatProvider->getFormats()
        );

        $this->handleWithException(
            static function () use ($book, $formats, $allowedFormats): void {
                foreach ($allowedFormats as $format) {
                    $format = new BookFormat()
                        ->setDateGenerated(new DateTime('now'))
                        ->setType($format)
                        ->setBook($book)
                        ->setFilename((string) ($formats[$format]['filename'] ?? ''))
                        ->setAllowed((bool) ($formats[$format]['allowed'] ?? false));

                    $format->persist();
                }
            }
        );

        return new CreatedDTO(
            $book->getId()
        );
    }

    /**
     * @throws UnprocessableEntityException
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'book', method: 'PUT')]
    public function update(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id,

        #[API\Parameter(source: 'body', name: 'title')]
        #[API\Assert\NotEmpty]
        string $title,

        #[API\Parameter(source: 'body', name: 'type')]
        #[API\Assert\NotEmpty]
        string $type,

        #[API\Parameter(source: 'body', name: 'annotation')]
        #[API\Assert\NotEmpty]
        string $annotation,

        #[API\Parameter(source: 'body', name: 'slug')]
        #[API\Assert\NotEmpty]
        string $slug,

        #[API\Parameter(source: 'body', name: 'lastUpdateDate')]
        #[API\Assert\NotEmpty]
        string $lastUpdateDate,

        #[API\Parameter(source: 'body', name: 'shortAnnotation')]
        #[API\Assert\NotEmpty]
        string $shortAnnotation,

        #[API\Parameter(source: 'body', name: 'cover')]
        ?array $cover = null,

        #[API\Parameter(source: 'body', name: 'coverBg')]
        ?array $coverBg = null,

        #[API\Parameter(source: 'body', name: 'author')]
        ?string $author = null,

        #[API\Parameter(source: 'body', name: 'technicalInfo')]
        ?string $technicalInfo = '',

        #[API\Parameter(source: 'body', name: 'group')]
        string $group = '',

        #[API\Parameter(source: 'body', name: 'position')]
        int $position = 0,

        #[API\Parameter(source: 'body', name: 'formats')]
        array $formats = []
    ): SuccessfulResultDTO {
        $this->validateEntity($slug, $type, $id, 'Ошибки при редактировании произведения.');

        try {
            $techInfoArray = json_decode($technicalInfo, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $techInfoArray = [];
        }

        /** @var BookModel|null $book */
        $book = $this->handleWithException(
            static fn () => BookModel::findByUniqueIdentifier($id)
        );

        if (!$book) {
            throw new NotFoundException("Игра #$id не найдена");
        }

        $this->handleWithException(
            fn () => $book->setSlug($slug)
                          ->setTitle($title)
                          ->setCover(
                              Media::findByUniqueIdentifier(
                                  (int) ($cover['id'] ?? 0)
                              )
                          )->setAnnotation($annotation)
                          ->setAuthor($author)
                          ->setTechnicalInfo($techInfoArray)
                          ->setCoverBg(
                              Media::findByUniqueIdentifier(
                                  (int) ($coverBg['id'] ?? 0)
                              )
                          )->setGroup($group)
                          ->setPosition($position)
                          ->setLastUpdateDate(
                              $this->dateConverter->toDateTime($lastUpdateDate)
                          )->setShortAnnotation($shortAnnotation)
                          ->setType($type)
        );

        $this->handleWithException(
            static fn () => $book->persist()
        );

        $allowedFormats = array_map(
            static fn (BookFormatDTO $format): string => $format->type,
            $this->formatProvider->getFormats()
        );

        $this->handleWithException(
            static function () use ($book, $formats, $allowedFormats): void {
                foreach ($allowedFormats as $format) {
                    $formatModel = BookFormat::findOne([
                        'book' => $book,
                        'type' => $format
                    ]) ?? new BookFormat();

                    $formatModel = $formatModel
                        ->setDateGenerated(new DateTime('now'))
                        ->setType($format)
                        ->setBook($book)
                        ->setFilename((string) ($formats[$format]['filename'] ?? ''))
                        ->setAllowed((bool) ($formats[$format]['allowed'] ?? false));

                    $formatModel->persist();
                }
            }
        );

        return new SuccessfulResultDTO();
    }

    /**
     * @throws UnprocessableEntityException
     */
    private function validateEntity(string $slug, string $type, ?int $id, string $errorTitle): void {
        $errors = [];

        if (!in_array($type, ['A', 'T'], true)) {
            $errors['type'] = 'Неизвестный тип произведения';
        }

        if ($bookBySlug = $this->getEntityBySlug(BookModel::class, $slug, $id)) {
            $errors['slug'] = "Этот слаг уже занят произведением \"{$bookBySlug->getTitle()}\"";
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
        $this->deleteEntities(BookModel::class, $rows);

        return new SuccessfulResultDTO();
    }
}
