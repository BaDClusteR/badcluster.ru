<?php

declare(strict_types=1);

namespace BC\Widget\Common\Block;

use BC\Core\Helper\MixedValue;
use BC\Core\Trait\TypographerTrait;
use BC\Model\Media as MediaModel;
use BC\Widget\AWidget;
use Exception;

class Media extends AWidget {
    use TypographerTrait;

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

    protected string $alt = '' {
        get {
            return $this->alt;
        }
    }

    protected bool $lightbox = false {
        get {
            return $this->lightbox;
        }
    }

    /** Opt-in border around the image or video. */
    protected bool $border = false {
        get {
            return $this->border;
        }
    }

    protected function getTemplatePath(): string {
        return 'common/block/media.phtml';
    }

    protected function applyContext(array $context): void {
        parent::applyContext($context);

        $media = $this->context['media'] ?? null;

        if (is_array($media) && isset($media['id'])) {
            try {
                $this->media = MediaModel::findByUniqueIdentifier(
                    MixedValue::toInt($media['id'])
                );
                $this->alt = MixedValue::toString($this->context['alt'] ?? '');

                $this->lazy = (bool) ($this->context['lazy'] ?? false);
                $this->caption = $this->getTypographer()->process(
                    MixedValue::toString($this->context['caption'] ?? '')
                );
                $this->lightbox = (bool) ($this->context['lightbox'] ?? false);
                $this->border = (bool) ($this->context['border'] ?? false);
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
