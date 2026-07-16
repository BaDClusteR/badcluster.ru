<?php

namespace BC\Widget\Common\Footer;

use BC\Core\Asset\DTO\AssetDTO;
use BC\DTO\RandomFactDTO;
use BC\Model\Fact;
use BC\Provider\IRandomFactProvider;
use BC\Widget\AWidget;
use BC\Widget\IAssetProvider;
use Runway\Exception\Exception;
use Runway\Singleton\Container;

class Facts extends AWidget implements IAssetProvider {
    private const array TOOLTIPS = [
        'Генератор бесполезных знаний',
        'Узнать то, не знаю что',
        'Минутка эрудиции',
        'Тайная комната',
        'Здесь прячется контент',
        'Нажми меня, я кнопка',
        'Открыть лутбокс (бесплатно)',
        '+1 к Интеллекту',
        'Компиляция странных данных...'
    ];

    protected function getTemplatePath(): string {
        return 'common/footer/facts.phtml';
    }

    protected function getRandomFact(): RandomFactDTO {
        return Container::getInstance()->getService(IRandomFactProvider::class)->getRandomFact();
    }

    protected function getRandomTooltip(): string {
        return self::TOOLTIPS[array_rand(self::TOOLTIPS)];
    }

    public static function getAssets(): array {
        return [
            new AssetDTO(
                'footer',
                'css/common/modal.css'
            ),
            new AssetDTO(
                'footer',
                'css/footer/facts.css'
            ),
            new AssetDTO(
                'facts',
                'js/common/facts.js'
            )
        ];
    }
}
