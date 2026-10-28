<?php

namespace BC\Widget\Page\Screenshots;

use BC\Model\Screenshot;
use BC\Widget\AWidget;
use Runway\Exception\Exception;

class Screenshots extends AWidget {
    protected function getTemplatePath(): string {
        return 'about/screenshots.phtml';
    }

    /**
     * @return iterable<array<string, mixed>> The rows of the screenshots (the template maps them).
     */
    protected function getScreenshotsIterator(): iterable {
        try {
            return Screenshot::getQueryBuilder('s')
                             ->where('s.parent_id IS NULL')
                             ->orderBy('s.position', 'DESC')
                             ->iterate();
        } catch (Exception) {
            return [];
        }
    }
}
