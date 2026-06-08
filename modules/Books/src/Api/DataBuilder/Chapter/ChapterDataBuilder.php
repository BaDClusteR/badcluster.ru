<?php

declare(strict_types=1);

namespace BC\Modules\Books\Api\DataBuilder\Chapter;

use BC\Core\Converter\IDateConverter;
use BC\Core\Formatter\IFormatter;
use BC\Modules\Books\Api\DTO\Book\BookDTO;
use BC\Modules\Books\Api\DTO\Book\BookFormatDTO;
use BC\Modules\Books\Api\DTO\Book\BookRowDTO;
use BC\Modules\Books\Api\DTO\Chapter\ChapterDTO;
use BC\Modules\Books\Api\DTO\Chapter\ChapterRowDTO;
use BC\Modules\Books\Model\Book;
use BC\Modules\Books\Model\BookFormat;
use BC\Modules\Books\Model\Chapter;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Model\Exception\ModelException;

readonly class ChapterDataBuilder implements IChapterDataBuilder {
    public function __construct(
        private IDateConverter $dateConverter
    ) {
    }

    public function buildRow(Chapter $chapter): ChapterRowDTO {
        return new ChapterRowDTO(
            id: $chapter->getId(),
            title: $chapter->getTitle(),
            addedDate: $this->dateConverter->toShortForm(
                $chapter->getAddedDate()
            ),
            updateDate: $this->dateConverter->toShortForm(
                $chapter->getUpdateDate()
            ),
            published: $chapter->getPublished()
        );
    }

    public function buildEntity(Chapter $chapter): ChapterDTO {
        return new ChapterDTO(
            title: $chapter->getTitle(),
            content: $chapter->getContent(),
            position: $chapter->getPosition(),
            published: $chapter->getPublished(),
            addedDate: $this->dateConverter->toPickerValue(
                $chapter->getAddedDate()
            ),
            slug: $chapter->getSlug(),
            part: $chapter->getPart()
        );
    }
}
