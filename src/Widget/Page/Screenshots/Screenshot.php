<?php

namespace BC\Widget\Page\Screenshots;

use BC\Model\Screenshot as ScreenshotModel;
use BC\Widget\AWidget;

class Screenshot extends AWidget {
    protected function getTemplatePath(): string {
        return 'about/screenshot.phtml';
    }

    protected function getScreenshot(): ?ScreenshotModel {
        $screenshot = $this->context['screenshot'] ?? null;

        return ($screenshot instanceof ScreenshotModel)
            ? $screenshot
            : null;
    }

    protected function getPosition(): int {
        return (int) ($this->context['position'] ?? 0);
    }
}
