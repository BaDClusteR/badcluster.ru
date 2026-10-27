<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Books;

use BC\Modules\Books\Widget\Format\Fb2\Block\Paragraph;

/**
 * The FB2 paragraph with its lines and tag exposed.
 */
final class Fb2ParagraphProbe extends Paragraph {
    /**
     * @return list<string>
     */
    public function lines(): array {
        return $this->getLines();
    }

    public function tag(): string {
        return $this->getTag();
    }
}
