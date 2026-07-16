<?php

declare(strict_types=1);

namespace BC\Widget\Common\Footer;

use BC\Core\Trait\FormatterTrait;
use BC\DTO\RandomFactDTO;
use BC\Widget\AWidget;

class Fact extends AWidget {
    use FormatterTrait;

    protected function getTemplatePath(): string {
        return 'common/footer/fact.phtml';
    }

    protected function getFact(): ?RandomFactDTO {
        $fact = $this->context['fact'] ?? null;

        return ($fact instanceof RandomFactDTO)
            ? $fact
            : null;
    }

    protected function formatFactContent(string $content): string {
        return str_replace(
            ['<p>[[', ']]</p>'],
            ['<p class="fact__link">', '</p>'],
            $this->getFormatter()->formatAsHtml($content)
        );
    }
}
