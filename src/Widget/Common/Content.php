<?php

declare(strict_types=1);

namespace BC\Widget\Common;

use BC\Widget\Attribute\WidgetList;
use BC\Widget\AWidget;
use BC\Widget\Page\APage;

#[WidgetList('body')]
class Content extends AWidget {
    protected function getTemplatePath(): string {
        return 'common/content.phtml';
    }

    protected function getPage(): ?APage {
        $result = $this->context['page'] ?? null;

        return ($result instanceof APage)
            ? $result
            : null;
    }
}
