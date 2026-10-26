<?php

declare(strict_types=1);

namespace BC\Modules\Books\Widget\Format\Txt\Block;

use BC\Core\Helper\MixedValue;
use BC\Model\Media as MediaModel;
use BC\Modules\Books\Widget\Format\Txt\Book;
use BC\Widget\AWidget;
use Exception;
use Runway\Exception\RuntimeException;

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
        return 'modules/Books/format/txt/block/media.phtml';
    }

    protected function applyContext(array $context): void {
        parent::applyContext($context);

        $media = $this->context['media'] ?? null;

        if (is_array($media) && isset($media['id'])) {
            try {
                $this->media = MediaModel::findByUniqueIdentifier(
                    MixedValue::toInt($media['id'])
                );

                $this->caption = MixedValue::toString($this->context['caption'] ?? '');
            } catch (Exception) {
            }
        }
    }

    /**
     * Блок может ссылаться на медиа, которое уже удалили из библиотеки: в книге его просто нет
     * (шаблон без медиа упал бы на null).
     */
    public function render(array $context = []): string {
        return $this->media === null
            ? ''
            : parent::render($context);
    }

    protected function isImage(): bool {
        return $this->media?->isImage() ?? false;
    }

    protected function getImageThumbnailPath(): string {
        $media = $this->media;

        if (($media === null) || !$media->isImage()) {
            return '';
        }

        $thumbnail = $media->getThumbnail(1500, 'image/jpeg');
        if ($thumbnail) {
            return $thumbnail->getWebPath();
        }

        return $media->getLocalPath();
    }

    protected function getBook(): Book {
        $book = $this->context['book'] ?? null;

        return ($book instanceof Book)
            ? $book
            : throw new RuntimeException('The widget needs a book');
    }
}
