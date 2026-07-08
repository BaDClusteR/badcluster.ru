<?php

declare(strict_types=1);

namespace BC\Widget\Page\Home;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Widget\AWidget;
use BC\Widget\Page\APage;

class HomePage extends APage {
    public function getHeader(): string {
        return 'Привет!';
    }

    public function getDescription(): array {
        $contacts = $this->getWebsiteSettings()->getAdminContacts();

        return [
            "Добро пожаловать в цифровой уголок BaD ClusteR'a.",
            'Здесь я выкладываю то, чем мне хотелось бы поделиться с миром. На сайте царит творческий хаос: переводы книг соседствуют с сейвами для игр, а самописные скрипты — с экспериментальной музыкой. Но главное — в каждый байт информации здесь вложена частичка души.',
            'Чувствуйте себя как дома, берите что нужно, оставайтесь сколько захотите :)',
            "Если захочется поделиться идеей или просто поболтать — пишите! Меня можно найти в <a href=\"$contacts->telegram\">Telegram</a> или по старинке написать на <a href=\"mailto:$contacts->email\">Email</a>. А если интересно, в какую сингловую бродилку я сейчас залипаю по ночам — заглядывайте в <a href=\"$contacts->steam\">профиль Steam</a>. Добавляйтесь в друзья, померяемся ачивками :)",
        ];
    }

    public function getMainWidget(): AWidget {
        return new Pulse();
    }

    public function getMetaDescription(): string {
        return "Цифровой уголок BaD ClusteR'а. Творческий хаос, где переводы книг и сейвы для старых игр соседствуют с AI-музыкой и кодом. Добро пожаловать!";
    }

    public function getCanonicalUrl(): string {
        return $this->getWebRoot();
    }

    public static function getAssets(): array {
        return [
            new AssetDTO('critical', 'js/critical/singleton.js', -100),
            new AssetDTO('critical', 'js/critical/event-dispatcher.js', -50),
            new AssetDTO('critical', 'js/critical/theme.js'),

            new AssetDTO('scripts', 'js/common/theme-switcher.js'),
            new AssetDTO('scripts', 'js/common/header.js'),
            new AssetDTO('scripts', 'js/common/scripts.js'),
            new AssetDTO('scripts', 'js/common/tabs.js'),

            new AssetDTO('core', 'css/core/reset.css', -100),
            new AssetDTO('core', 'css/core/font.css', -50),
            new AssetDTO('core', 'css/core/style.css', 0),
            new AssetDTO('core', 'css/core/tooltip.css', 100),

            new AssetDTO('noscript', 'css/noscript.css'),

            new AssetDTO('footer', 'css/common/footer.css')
        ];
    }
}
