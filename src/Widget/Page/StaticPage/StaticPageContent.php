<?php

declare(strict_types=1);

namespace BC\Widget\Page\StaticPage;

use BC\Model\StaticPage as StaticPageModel;
use BC\Widget\AWidget;
use Runway\Exception\RuntimeException;

class StaticPageContent extends AWidget {
    protected ?StaticPageModel $staticPage = null {
        get {
            return $this->staticPage;
        }
    }

    protected function applyContext(array $context): void {
        parent::applyContext($context);

        if (($context['staticPage'] ?? null) instanceof StaticPageModel) {
            $this->staticPage = $context['staticPage'];
        }

        if (!$this->staticPage) {
            throw new RuntimeException(
                __METHOD__ . ': staticPage is not set or not an instance of ' . StaticPageModel::class
            );
        }
    }

    protected function getTemplatePath(): string {
        return 'static-page/content.phtml';
    }
}
