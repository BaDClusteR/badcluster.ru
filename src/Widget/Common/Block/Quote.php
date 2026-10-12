<?php

declare(strict_types=1);

namespace BC\Widget\Common\Block;

use BC\Core\Trait\TypographerTrait;
use BC\Widget\AWidget;

class Quote extends AWidget {
    use TypographerTrait;

    protected function getTemplatePath(): string {
        return $this->isTranslated()
            ? 'common/block/quote-switchable.phtml'
            : 'common/block/quote.phtml';
    }

    protected function getAnchor(): string {
        return (string) ($this->context['anchor'] ?? '');
    }

    protected function getText(): string {
        return $this->getTypographer()->process(
            (string) ($this->context['text'] ?? '')
        );
    }

    protected function getTranslatedText(): string {
        return $this->getTypographer()->process(
            (string) ($this->context['translatedText'] ?? '')
        );
    }

    protected function isTranslated(): bool {
        return (bool) ($this->context['translated'] ?? false);
    }

    protected function getLabelOriginal(): string {
        return (string) ($this->context['labelOriginal'] ?? '');
    }

    protected function getLabelTranslation(): string {
        return (string) ($this->context['labelTranslation'] ?? '');
    }

    protected function sanitizeQuoteText(string $text): string {
        return '<p>' . str_replace(
            ['</p>', '<p data-empty="false">', '<p data-empty="true">'],
            ['', '</p><p>', '</p><p>'],
            $text
        ) . '</p>';
    }
}
