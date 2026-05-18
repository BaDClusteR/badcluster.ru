<?php

namespace BC\Widget\Common\Footer;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Core\Trait\WebsiteSettingsTrait;
use BC\Widget\Attribute\WidgetList;
use BC\Widget\AWidget;
use BC\Widget\IAssetProvider;
use Random\RandomException;

#[WidgetList('footer')]
class Tagline extends AWidget implements IAssetProvider {
    use WebsiteSettingsTrait;

    private const array TAGLINES = [
        'From <a href="mailto:{{email}}">BaD ClusteR</a> with ❤️. 2005 — {{year}}',
        'Hand-crafted with ❤️ and ☕. 2005 — {{year}}',
        'Coded with soul. Broken by tests. 2005 — {{year}}',
        '&lt;/&gt; with ❤️ by <a href="mailto:{{email}}">BaD ClusteR</a>. 2005 — {{year}}',
        'Coded with ❤️, compiled with zero warnings. 2005 — {{year}}',
        'Made with ❤️ (and some swearing). 2005 — {{year}}'
    ];

    protected function getTemplatePath(): string {
        return 'common/footer/tagline.phtml';
    }

    protected function getTagline(): string {
        return str_replace(
            ['{{email}}', '{{year}}'],
            [$this->getWebsiteSettings()->getAdminContacts()->email, date('Y')],
            self::TAGLINES[$this->getTaglineIndex()]
        );
    }

    private function getTaglineIndex(): int {
        try {
            return random_int(0, count(self::TAGLINES) - 1);
        } catch (RandomException) {
            return 0;
        }
    }

    public static function getAssets(): array {
        return [
            new AssetDTO(
                'footer',
                'css/footer/tagline.css'
            )
        ];
    }
}
