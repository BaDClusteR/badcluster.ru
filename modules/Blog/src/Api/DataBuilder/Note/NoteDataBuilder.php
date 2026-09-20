<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Api\DataBuilder\Note;

use BC\Core\Converter\IDateConverter;
use BC\Core\Helper\IBlockHelper;
use BC\Modules\Blog\Api\DTO\NoteDTO;
use BC\Modules\Blog\Api\DTO\NoteRowDTO;
use BC\Modules\Blog\Model\Note;

readonly class NoteDataBuilder implements INoteDataBuilder {
    public function __construct(
        private IDateConverter $dateConverter,
        private IBlockHelper $blockHelper
    ) {
    }

    public function buildRow(Note $note): NoteRowDTO {
        $isPublished = $note->getPublished();

        return new NoteRowDTO(
            id: $note->getId(),
            title: $note->getTitle(),
            slug: $note->getSlug(),
            published: $isPublished,
            publish_date: $isPublished
                ? $this->dateConverter->toShortForm($note->getPublishDate())
                : '—'
        );
    }

    public function buildEntity(Note $note): NoteDTO {
        return new NoteDTO(
            id: $note->getId(),
            title: $note->getTitle(),
            metaDescription: $note->getMetaDescription(),
            publishDate: $this->dateConverter->toPickerValue(
                $note->getPublishDate()
            ),
            content: $this->blockHelper->enrichBlocks(
                $note->getContent()
            ),
            published: $note->getPublished(),
            slug: $note->getSlug()
        );
    }
}
