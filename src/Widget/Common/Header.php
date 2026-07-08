<?php

declare(strict_types=1);

namespace BC\Widget\Common;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Core\Trait\WebsiteSettingsTrait;
use BC\Widget\Attribute\WidgetList;
use BC\Widget\AWidget;
use BC\Widget\IAssetProvider;
use BC\Widget\Page\APage;
use BC\Widget\Page\Page404;

#[WidgetList('body', priority: 0)]
class Header extends AWidget implements IAssetProvider {
    use WebsiteSettingsTrait;

    protected function getTemplatePath(): string {
        return 'common/header.phtml';
    }

    public static function getAssets(): array {
        return [
            new AssetDTO('core', 'css/common/header.css')
        ];
    }

    protected function getLogoUrl(): string {
        return $this->getWebsiteSettings()->getWebRoot();
    }

    public function render(array $context = []): string {
        $this->applyContext($context);

        if ($this->getPage() instanceof Page404) {
            return '';
        }

        return parent::render($context);
    }

    private function getPage(): ?APage {
        $page = $this->context['page'] ?? null;

        return ($page instanceof APage)
            ? $page
            : null;
    }
}
