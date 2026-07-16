<?php

declare(strict_types=1);

namespace BC\Widget\Page\Home;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Core\Trait\AssetBuilderTrait;
use BC\Core\Trait\WebsiteSettingsTrait;
use BC\DTO\PulseItemDTO;
use BC\Model\PulseItem as PulseItemModel;
use BC\Provider\IPulseItemsProvider;
use BC\Widget\AWidget;
use BC\Widget\IAssetProvider;
use Runway\Exception\Exception;
use Runway\Singleton\Container;

class Pulse extends AWidget implements IAssetProvider {
    use AssetBuilderTrait;
    use WebsiteSettingsTrait;

    protected function getTemplatePath(): string {
        return 'home/pulse.phtml';
    }

    public static function getAssets(): array {
        return [
            new AssetDTO('grid', 'css/grid.css'),
        ];
    }

    /**
     * @return PulseItemDTO[]
     */
    protected function getItems(): array {
        $rows = $this->getPulseItemsProvider()->getPulseItems();

        try {
            $rowsFromDatabase = PulseItemModel::find();
        } catch (Exception) {
            $rowsFromDatabase = [];
        }

        /** @var PulseItemModel[] $rowsFromDatabase */
        foreach ($rowsFromDatabase as $row) {
            $rows[] = $this->buildRow($row);
        }

        usort(
            $rows,
            static fn (PulseItemDTO $a, PulseItemDTO $b): int => $a->position <=> $b->position
        );

        return $rows;
    }

    private function buildRow(PulseItemModel $item): PulseItemDTO {
        $statusText = $item->getStatusText();

        return new PulseItemDTO(
            title: $item->getTitle(),
            url: $this->getWebsiteSettings()->getWebRoot() . $item->getUrl(),
            tag: $item->getTag(),
            text: $item->getText(),
            image: $item->getImage(),
            status: $item->getStatusTitle() . ($statusText ? "<strong>$statusText</strong>" : ''),
            icon: $item->getIcon(),
            isTall: $item->getIsTall(),
            isSurfaced: $item->getIsSurfaced(),
            position: $item->getPosition()
        );
    }

    private function getPulseItemsProvider(): IPulseItemsProvider {
        return Container::getInstance()->getService(IPulseItemsProvider::class);
    }
}
