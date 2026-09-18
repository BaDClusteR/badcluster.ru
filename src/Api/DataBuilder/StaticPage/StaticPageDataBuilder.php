<?php

declare(strict_types=1);

namespace BC\Api\DataBuilder\StaticPage;

use BC\Api\DTO\StaticPage\StaticPageDTO;
use BC\Api\DTO\StaticPage\StaticPageRowDTO;
use BC\Core\Converter\IDateConverter;
use BC\Core\Helper\IBlockHelper;
use BC\Model\StaticPage;

readonly class StaticPageDataBuilder implements IStaticPageDataBuilder {
    public function __construct(
        private IDateConverter $dateConverter,
        private IBlockHelper $blockHelper
    ) {
    }

    public function buildRow(StaticPage $page): StaticPageRowDTO {
        return new StaticPageRowDTO(
            id: $page->getId(),
            title: $page->getShortTitle() ?: $page->getTitle(),
            slug: $page->getSlug(),
            published: $page->getPublished(),
            created_date: $this->dateConverter->toShortForm($page->getCreatedDate())
        );
    }

    public function buildEntity(StaticPage $page): StaticPageDTO {
        $publishDate = $page->getPublishDate();

        return new StaticPageDTO(
            title: $page->getTitle(),
            shortTitle: $page->getShortTitle(),
            // Media blocks are stored as a bare {id}; the editor needs the url
            // and dimensions to render a preview.
            content: $this->blockHelper->enrichBlocks(
                $page->getContent()
            ),
            slug: $page->getSlug(),
            published: $page->getPublished(),
            indexable: $page->getIndexable(),
            textBlock: $page->getTextBlock(),
            publishDate: $publishDate ? $this->dateConverter->toPickerValue($publishDate) : '',
            metaDescription: $page->getMetaDescription(),
            backLinkText: $page->getBackLinkText(),
            backLinkUrl: $page->getBackLinkUrl()
        );
    }
}
