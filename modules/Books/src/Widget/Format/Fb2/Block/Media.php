<?php

declare(strict_types=1);

namespace BC\Modules\Books\Widget\Format\Fb2\Block;

use BC\Core\Helper\MixedValue;
use BC\Model\Media as MediaModel;
use BC\Modules\Books\Widget\Format\Fb2\Book;
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
        return 'modules/Books/format/fb2/block/media.phtml';
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
            // Путь к файлу, а не URL: картинка встраивается в книгу через file_get_contents()
            return $thumbnail->getLocalPath();
        }

        return $media->getLocalPath();
    }

    protected function getBook(): Book {
        $book = $this->context['book'] ?? null;

        return ($book instanceof Book)
            ? $book
            : throw new RuntimeException('The widget needs a book');
    }

    /**
     * Подпись из редактора может содержать разметку и сущности HTML: в FB2 — только текст
     */
    public function getCaptionXml(): string {
        return htmlspecialchars(
            html_entity_decode(strip_tags($this->caption), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}
