<?php

declare(strict_types=1);

namespace BC\Widget\Common\Block;

use BC\Widget\AWidget;

class Terminal extends AWidget {
    protected function getTemplatePath(): string {
        return 'common/block/terminal.phtml';
    }

    protected function getAnchor(): string {
        return (string) ($this->context['anchor'] ?? '');
    }

    protected function getCipher(): string {
        return (string) ($this->context['cipher'] ?? '');
    }

    protected function getEn(): string {
        return (string) ($this->context['en'] ?? '');
    }

    protected function getRu(): string {
        return (string) ($this->context['ru'] ?? '');
    }

    protected function getTitle(): string {
        return (string) ($this->context['title'] ?? '');
    }

    protected function getKey(): string {
        return (string) ($this->context['key'] ?? '');
    }

    protected function getPage(): string {
        return (string) ($this->context['page'] ?? '');
    }

    protected function getCipherLabel(): string {
        return (string) ($this->context['labelCipher'] ?? '');
    }

    protected function getEnLabel(): string {
        return (string) ($this->context['labelEn'] ?? '');
    }

    protected function getRuLabel(): string {
        return (string) ($this->context['labelRu'] ?? '');
    }

    protected function isShowCipherTab(): bool {
        return (bool) ($this->context['tabCipher'] ?? false);
    }

    protected function isShowEnTab(): bool {
        return (bool) ($this->context['tabEn'] ?? false);
    }

    protected function isShowRuTab(): bool {
        return (bool) ($this->context['tabRu'] ?? false);
    }

    protected function isShowTitle(): bool {
        return (bool) ($this->context['showTitle'] ?? false);
    }

    protected function getTabsCount(): int {
        $count = 0;

        if ($this->isShowCipherTab()) {
            $count++;
        }

        if ($this->isShowEnTab()) {
            $count++;
        }

        if ($this->isShowRuTab()) {
            $count++;
        }

        return $count;
    }

    protected function sanitizeTabContent(string $content): string {
        return str_replace(
            ['<p>', '</p>'],
            '',
            $content
        );
    }
}
