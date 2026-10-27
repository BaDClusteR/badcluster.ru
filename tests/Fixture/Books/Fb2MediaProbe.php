<?php

declare(strict_types=1);

namespace BC\Tests\Fixture\Books;

use BC\Modules\Books\Widget\Format\Fb2\Block\Media;

/**
 * The FB2 media block with the path of its image exposed.
 */
final class Fb2MediaProbe extends Media {
    public function imagePath(): string {
        return $this->getImageThumbnailPath();
    }

    public function image(): bool {
        return $this->isImage();
    }
}
