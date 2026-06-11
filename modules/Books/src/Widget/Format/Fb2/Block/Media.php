<?php

declare(strict_types=1);

namespace BC\Modules\Books\Widget\Format\Fb2\Block;

use BC\Model\Media as MediaModel;
use BC\Modules\Books\Widget\Format\Fb2\Book;
use BC\Widget\AWidget;
use Exception;

class Media extends AWidget {
    protected ?MediaModel $media = null {
        get {
            return $this->media;
        }
    }

    protected string $caption = '' {
        get {
            return $this->caption;
        }
    }

    protected function getTemplatePath(): string {
        return 'modules/Books/format/fb2/block/media.phtml';
    }

    protected function applyContext(array $context): void {
        parent::applyContext($context);

        if (isset($this->context['media']['id'])) {
            try {
                $this->media = MediaModel::findByUniqueIdentifier(
                    (int) $this->context['media']['id']
                );

                $this->caption = (string) ($this->context['caption'] ?? '');
            } catch (Exception) {
            }
        }
    }

    protected function isImage(): bool {
        return $this->media?->isImage() ?? false;
    }

    protected function getImageThumbnailPath(): string {
        if (!$this->isImage()) {
            return '';
        }

        $thumbnail = $this->media->getThumbnail(1500, 'image/jpeg');
        if ($thumbnail) {
            return $thumbnail->getWebPath();
        }

        return $this->media->getLocalPath();
    }

    protected function getBook(): Book {
        return $this->context['book'];
    }
}
