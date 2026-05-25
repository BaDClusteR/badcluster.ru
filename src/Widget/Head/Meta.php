<?php

declare(strict_types=1);

namespace BC\Widget\Head;

use BC\Widget\Attribute\WidgetList;
use BC\Widget\AWidget;
use BC\Widget\DTO\MetaTagDTO;
use BC\Widget\Page\APage;

#[WidgetList('head')]
class Meta extends AWidget {
    protected ?APage $page;

    protected function getTemplatePath(): string {
        return 'head/meta.phtml';
    }

    protected function applyContext(array $context): void {
        parent::applyContext($context);

        $this->page = ($this->context['page'] ?? null) instanceof APage
            ? $this->context['page']
            : null;
    }

    /**
     * @return MetaTagDTO[]
     */
    protected function getMeta(): array {
        $description = (string) $this->page?->getMetaDescription();

        return [
            new MetaTagDTO(
                name: 'viewport',
                content: 'width=device-width, height=device-height, initial-scale=1.0, viewport-fit=cover'
            ),
            new MetaTagDTO(
                name: 'description',
                content: $description
            ),
            new MetaTagDTO(
                name: 'author',
                content: 'BaD ClusteR'
            ),
            new MetaTagDTO(
                name: 'og:title',
                content: $this->page?->getMetaTitle() ?? $this->page?->getTitle() ?? ''
            ),
            new MetaTagDTO(
                name: 'og:description',
                content: $description
            ),
            ...$this->getOpenGraphMeta(),
            ...(array) $this->page?->getMetaTags(),
        ];
    }

    private function getOpenGraphMeta(): array {
        $result = [
            new MetaTagDTO(
                name: 'og:site_name',
                content: 'BaD ClusteR — цифровой архив'
            ),
            new MetaTagDTO(
                name: 'og:type',
                content: $this->page?->getOpenGraphType() ?? 'website'
            ),
        ];

        if (
            $this->page
            && ($image = $this->page->getPageImage())
        ) {
            $result = [
                ...$result,
                new MetaTagDTO(
                    name: 'og:image',
                    content: $image->url
                ),
                new MetaTagDTO(
                    name: 'og:image:width',
                    content: (string) $image->width
                ),
                new MetaTagDTO(
                    name: 'og:image:height',
                    content: (string) $image->height
                ),
            ];
        }

        return $result;
    }
}
