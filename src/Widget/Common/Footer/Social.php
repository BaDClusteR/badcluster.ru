<?php

namespace BC\Widget\Common\Footer;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Core\DTO\AdminContactsDTO;
use BC\Core\Trait\WebsiteSettingsTrait;
use BC\Widget\Attribute\WidgetList;
use BC\Widget\AWidget;
use BC\Widget\IAssetProvider;

#[WidgetList('footer', 200)]
class Social extends AWidget implements IAssetProvider {
    use WebsiteSettingsTrait;

    protected function getTemplatePath(): string {
        return 'common/footer/social.phtml';
    }

    protected function getContacts(): AdminContactsDTO {
        return $this->getWebsiteSettings()->getAdminContacts();
    }

    public static function getAssets(): array {
        return [
            new AssetDTO(
                'footer',
                'css/footer/social.css'
            )
        ];
    }
}
