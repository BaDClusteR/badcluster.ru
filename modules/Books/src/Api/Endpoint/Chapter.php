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
use BC\Modules\Books\Api\DataBuilder\Chapter\IChapterDataBuilder;
use BC\Modules\Books\Api\DTO\Book\BookRowDTO;
use BC\Modules\Books\Api\DTO\Chapter\ChapterDTO;
use BC\Modules\Books\Api\DTO\Chapter\ChapterRowDTO;
use BC\Modules\Books\Model\Book as BookModel;
use BC\Modules\Books\Model\Chapter as ChapterModel;
use DateTime;

class Chapter extends AEndpoint {
    public function __construct(
        private readonly IChapterDataBuilder $dataBuilder,
        private readonly IDateConverter $dateConverter
    ) {
    }

    /**
     * @return ListResponseDTO<BookRowDTO>
     *
     * @throws BadRequestException
     */
    #[API\Endpoint(path: 'chapters', method: 'GET')]
    public function getList(
        #[API\Parameter(source: 'query')]
        int $bookId,
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
        if ($sortBy === 'addedDate') {
            $sortBy = 'added_date';
        } elseif ($sortBy === 'updateDate') {
            $sortBy = 'update_date';
        }

        return $this->getEntitiesList(
            new GetEntitiesListRequest(
                qb: ChapterModel::getQueryBuilder()
                                ->where('book_id = :bookId')
                                ->setVariable('bookId', $bookId)
                                ->orderBy('position', 'ASC'),
                filter: $filter,
                columnsToFind: ['title', 'content'],
                sortBy: $sortBy,
                sortDir: $sortDir,
                page: $page,
                perPage: $perPage,
                sortableColumns: ['added_date', 'update_date'],
            ),
            fn (ChapterModel $chapter): ChapterRowDTO => $this->dataBuilder->buildRow($chapter)
        );
    }

    /**
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'chapter', method: 'GET')]
    public function getOne(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id
    ): ChapterDTO {
        return $this->getEntity(
            ChapterModel::class,
            $id,
            'Глава #{{id}} не найдена.',
            fn (ChapterModel $chapter): ChapterDTO => $this->dataBuilder->buildEntity($chapter)
        );
    }

    /**
     * @throws UnprocessableEntityException
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'chapter', method: 'POST')]
    public function create(
        #[API\Parameter(source: 'body', name: 'title')]
        #[API\Assert\NotEmpty]
        string $title,
        #[API\Parameter(source: 'body', name: 'bookId')]
        int $bookId,
        #[API\Parameter(source: 'body', name: 'content')]
        array $content,
        #[API\Parameter(source: 'body', name: 'published')]
        bool $published,
        #[API\Parameter(source: 'body', name: 'slug')]
        #[API\Assert\NotEmpty]
        string $slug,
        #[API\Parameter(source: 'body', name: 'part')]
        string $part = '',
        #[API\Parameter(source: 'body', name: 'position')]
        ?int $position = null,
        #[API\Parameter(source: 'body', name: 'addedDate')]
        ?string $addedDate = null,
    ): CreatedDTO {
        $this->validateEntity($slug, null, $bookId, 'Ошибки при добавлении произведения.');

        /** @var BookModel|null $book */
        $book = $this->handleWithException(
            static fn () => BookModel::findByUniqueIdentifier($bookId)
        );

        if (!$book) {
            throw new NotFoundException("Произведение #$bookId не найдено.");
        }

        if ($position === null) {
            $chapters = $this->handleWithException(
                static fn () => ChapterModel::getQueryBuilder()
                                            ->where('book_id = :bookId')
                                            ->setVariable('bookId', $bookId)
                                            ->count()
            );

            $position = ($chapters + 1) * 100;
        }

        if ($addedDate === null) {
            $addedDate = $this->dateConverter->toPickerValue(
                new DateTime('now')
            );
        }

        $chapter = new ChapterModel()
            ->setBook($book)
            ->setPosition($position)
            ->setContent($content)
            ->setAddedDate($this->dateConverter->toDateTime($addedDate))
            ->setTitle($title)
            ->setPublished($published)
            ->setPart($part)
            ->setSlug($slug)
            ->setUpdateDate(new DateTime('now'));

        $this->handleWithException(
            static function () use ($chapter, $book) {
                $chapter->persist();

                $book->bumpLastUpdateDate();
                $book->generateFormats();
            }
        );

        return new CreatedDTO(
            $chapter->getId()
        );
    }

    /**
     * @throws UnprocessableEntityException
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'chapter', method: 'PUT')]
    public function update(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id,
        #[API\Parameter(source: 'body', name: 'title')]
        #[API\Assert\NotEmpty]
        string $title,
        #[API\Parameter(source: 'body', name: 'content')]
        array $content,
        #[API\Parameter(source: 'body', name: 'published')]
        bool $published,
        #[API\Parameter(source: 'body', name: 'slug')]
        #[API\Assert\NotEmpty]
        string $slug,
        #[API\Parameter(source: 'body', name: 'part')]
        string $part = '',
        #[API\Parameter(source: 'body', name: 'position')]
        ?int $position = null,
        #[API\Parameter(source: 'body', name: 'addedDate')]
        ?string $addedDate = null
    ): SuccessfulResultDTO {
        /** @var ChapterModel|null $chapter */
        $chapter = $this->handleWithException(
            static fn () => ChapterModel::findByUniqueIdentifier($id)
        );

        if (!$chapter) {
            throw new NotFoundException("Глава #$id не найдена.");
        }

        $book = $chapter->getBook();
        $bookId = $book->getId();

        $this->validateEntity($slug, $id, $bookId, 'Ошибки при сохранении главы.');

        if ($position === null) {
            $chapters = $this->handleWithException(
                static fn () => ChapterModel::getQueryBuilder()
                                            ->where('book_id = :bookId')
                                            ->setVariable('bookId', $bookId)
                                            ->count()
            );

            $position = $chapters * 100;
        }

        if ($addedDate === null) {
            $addedDate = $this->dateConverter->toPickerValue(
                new DateTime('now')
            );
        }

        $chapter->setPosition((int) $position)
                ->setContent($content)
                ->setAddedDate($this->dateConverter->toDateTime($addedDate))
                ->setTitle($title)
                ->setPublished($published)
                ->setPart($part)
                ->setSlug($slug)
                ->setUpdateDate(new DateTime('now'));

        $this->handleWithException(
            static function () use ($chapter, $book) {
                $chapter->persist();

                $book->bumpLastUpdateDate();
                $book->generateFormats();
            }
        );

        return new SuccessfulResultDTO();
    }

    /**
     * @throws UnprocessableEntityException
     */
    private function validateEntity(string $slug, ?int $id, int $bookId, string $errorTitle): void {
        $errors = [];

        if (
            $this->getEntityBySlug(
                ChapterModel::class,
                $slug,
                $id,
                ChapterModel::getQueryBuilder()
                            ->where('book_id = :bookId')
                            ->setVariable('bookId', $bookId)
            )
        ) {
            $errors['slug'] = 'Этот слаг уже занят другой главой этого произведения.';
        }

        if (!empty($errors)) {
            throw new UnprocessableEntityException($errors, $errorTitle);
        }
    }

    #[API\Endpoint(path: 'chapters', method: 'DELETE')]
    public function delete(
        #[API\Parameter(source: 'body', name: 'rows')]
        array $rows
    ): SuccessfulResultDTO {
        $this->deleteEntities(ChapterModel::class, $rows);

        return new SuccessfulResultDTO();
    }
}
