<?php

declare(strict_types=1);

namespace BC\Modules\Books\Api\DataBuilder\Book;

use BC\Core\Converter\IDateConverter;
use BC\Core\Formatter\IFormatter;
use BC\Modules\Books\Api\DTO\Book\BookDTO;
use BC\Modules\Books\Api\DTO\Book\BookFormatDTO;
use BC\Modules\Books\Api\DTO\Book\BookRowDTO;
use BC\Modules\Books\Model\Book;
use BC\Modules\Books\Model\BookFormat;
use BC\Modules\Books\Model\Chapter;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Exception\Exception;
use Runway\Model\Exception\ModelException;

readonly class BookDataBuilder implements IBookDataBuilder {
    public function __construct(
        private IDateConverter $dateConverter,
        private IFormatter $formatter
    ) {
    }

    public function buildRow(Book $book): BookRowDTO {
        try {
            $chapterCount = Chapter::getQueryBuilder()
                                   ->where('book_id = :book_id')
                                   ->setVariable('book_id', $book->getId())
                                   ->count();
        } catch (Exception) {
            $chapterCount = 0;
        }

        return new BookRowDTO(
            id: $book->getId(),
            cover: $book->getCover()?->toMediaDTO(),
            title: $book->getTitle(),
            shortAnnotation: $book->getShortAnnotation(),
            type: $book->getType(),
            lastUpdateDate: $this->dateConverter->toShortForm(
                $book->getLastUpdateDate()
            ),
            chapterCount: $this->getChapterCountText($chapterCount)
        );
    }

    public function getChapterCountText(int $count): string {
        $countMod100 = $count % 100;
        if ($countMod100 >= 10 && $countMod100 <= 20) {
            $postfix = 'глав';
        } else {
            $postfix = match ($count % 10) {
                1 => 'глава',
                2, 3, 4 => 'главы',
                default => 'глав'
            };
        }

        return "$count $postfix";
    }

    /**
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    public function buildEntity(Book $book): BookDTO {
        $formatsRaw = $book->getFormats();
        $formats = [];

        foreach ($formatsRaw as $format) {
            $formats[$format->getType()] = $this->buildFormat($format);
        }

        return new BookDTO(
            slug: $book->getSlug(),
            cover: $book->getCover()?->toMediaDTO(),
            coverBg: $book->getCoverBg()?->toMediaDTO(),
            title: $book->getTitle(),
            author: $book->getAuthor(),
            annotation: $book->getAnnotation(),
            shortAnnotation: $book->getShortAnnotation(),
            type: $book->getType(),
            lastUpdateDate: $this->dateConverter->toPickerValue(
                $book->getLastUpdateDate()
            ),
            group: $book->getGroup(),
            position: $book->getPosition(),
            fb2Genre: $book->getFb2Genre(),
            formats: $formats
        );
    }

    protected function buildFormat(BookFormat $format): BookFormatDTO {
        $size = $format->getSize();

        return new BookFormatDTO(
            id: $format->getId(),
            type: $format->getType(),
            allowed: $format->getAllowed(),
            filename: $format->getFilename(),
            size: $size,
            sizeHumanReadable: $this->formatter->formatAsHumanReadableSize($size),
            dateGenerated: $this->dateConverter->toFullForm(
                $format->getDateGenerated(),
                true
            ),
            postfix: $format->getPostfix()
        );
    }
}
