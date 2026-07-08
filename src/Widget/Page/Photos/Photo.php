<?php

namespace BC\Widget\Page\Photos;

use BC\Model\Photo as PhotoModel;
use BC\Widget\AWidget;

class Photo extends AWidget {
    protected function getTemplatePath(): string {
        return 'about/photo.phtml';
    }

    protected function getPhoto(): ?PhotoModel {
        $photo = $this->context['photo'] ?? null;

        return ($photo instanceof PhotoModel)
            ? $photo
            : null;
    }

    protected function getPosition(): int {
        return (int) ($this->context['position'] ?? 0);
    }
}
