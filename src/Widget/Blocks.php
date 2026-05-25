<?php

declare(strict_types=1);

namespace BC\Widget;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Widget\Common\Block\Gallery;
use BC\Widget\Common\Block\Header;
use BC\Widget\Common\Block\Media;
use BC\Widget\Common\Block\Paragraph;
use BC\Widget\Common\Block\Quote;
use BC\Widget\Common\Block\TableOfContents;
use BC\Widget\Common\Block\Terminal;
use BC\Widget\Common\Block\WList;

class Blocks extends AWidget implements IAssetProvider {
    protected function getTemplatePath(): string {
        return 'common/blocks.phtml';
    }

    protected function getBlocks(): array {
        return (array) ($this->context['blocks'] ?? []);
    }

    public static function getAssets(): array {
        return [
            new AssetDTO(
                'blocks',
                'css/blocks/gallery.css'
            ),
            new AssetDTO(
                'blocks',
                'css/blocks/media.css'
            ),
            new AssetDTO(
                'blocks',
                'css/blocks/quote.css'
            ),
            new AssetDTO(
                'blocks',
                'css/blocks/terminal.css'
            ),
            new AssetDTO(
                'blocks',
                'css/blocks/toc.css'
            ),
            new AssetDTO(
                'blocks',
                'css/blocks/lightbox.css'
            ),

            new AssetDTO(
                'blocks',
                'js/blocks.js'
            ),
            new AssetDTO(
                'blocks',
                'js/gallery.js',
                0
            )
        ];
    }

    protected function renderBlock(string $type, array $data): string {
        $widget = match($type) {
            'paragraph' => new Paragraph($data),
            'media'     => new Media($data),
            'header'    => new Header($data),
            'quote'     => new Quote($data),
            'terminal'  => new Terminal($data),
            'gallery'   => new Gallery($data),
            'toc'       => new TableOfContents($data),
            'list'      => new WList($data),
            default => null
        };

        return (string) $widget?->render();
    }
}
