<?php

declare(strict_types=1);

namespace BC\Widget\Common\Block;

use BC\Model\Media as MediaModel;
use BC\Widget\AWidget;
use Exception;

class Media extends AWidget {
    protected ?MediaModel $media = null {
        get {
            return $this->media;
        }
    }

    protected bool $lazy = false {
        get {
            return $this->lazy;
        }
    }

    protected string $caption = '' {
        get {
            return $this->caption;
        }
    }

    protected bool $lightbox = false {
        get {
            return $this->lightbox;
        }
    }

    protected function getTemplatePath(): string {
        return 'common/block/media.phtml';
    }

    protected function applyContext(array $context): void {
        parent::applyContext($context);

        if (isset($this->context['media']['id'])) {
            try {
                $this->media = MediaModel::findByUniqueIdentifier(
                    (int) $this->context['media']['id']
                );

                $this->lazy = (bool) ($this->context['lazy'] ?? false);
                $this->caption = (string) ($this->context['caption'] ?? '');
                $this->lightbox = (bool) ($this->context['lightbox'] ?? false);
            } catch (Exception) {
            }
        }
    }

    protected function isImage(): bool {
        return $this->media?->isImage() ?? false;
    }

    protected function isVideo(): bool {
        return $this->media?->isVideo() ?? false;
    }
}
