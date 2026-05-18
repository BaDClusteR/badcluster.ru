<?php

namespace BC\Widget\Common\Block;

use BC\Widget\AWidget;

class Quote extends AWidget {
    protected function getTemplatePath(): string {
        return $this->isTranslated()
            ? 'common/block/quote-switchable.phtml'
            : 'common/block/quote.phtml';
    }

    protected function getAnchor(): string {
        return (string) ($this->context['anchor'] ?? '');
    }

    protected function getText(): string {
        return (string) ($this->context['text'] ?? '');
    }

    protected function getTranslatedText(): string {
        return (string) ($this->context['translatedText'] ?? '');
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
