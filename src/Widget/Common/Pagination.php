<?php

namespace BC\Widget\Common;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Widget\AWidget;
use BC\Widget\IAssetProvider;

class Pagination extends AWidget implements IAssetProvider {
    protected function getTemplatePath(): string {
        return 'common/pagination.phtml';
    }

    protected function getCurrentPage(): int {
        return max(1, (int) ($this->context['currPage'] ?? 1));
    }

    protected function getTotalPages(): int {
        return max(1, (int) ($this->context['pages'] ?? 1));
    }

    protected function getUrlPrefix(): string {
        return (string) ($this->context['urlPrefix'] ?? '');
    }

    protected function buildUrl(int $page): string {
        $prefix = $this->getUrlPrefix();

        return $page <= 1
            ? $prefix
            : "$prefix/page/$page";
    }

    public static function getAssets(): array {
        return [
            new AssetDTO(
                'core',
                'css/core/pagination.css'
            ),
        ];
    }
}
