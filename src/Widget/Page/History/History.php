<?php

namespace BC\Widget\Page\History;

use BC\Core\Trait\FormatterTrait;
use BC\Model\Media;
use BC\Widget\AWidget;
use BC\Widget\Common\Block\Media as MediaBlock;
use Runway\Exception\Exception;

class History extends AWidget {
    use FormatterTrait;

    protected function getTemplatePath(): string {
        return 'about/history.phtml';
    }

    protected function renderScreenshot(string $path, string $caption, string $link = ''): string {
        $rawCaption = $caption;

        if ($link) {
            $caption = "<a href='$link' target='_blank' class='snapshot-link'>$caption</a>";
        }

        try {
            $imageId = Media::getQueryBuilder('m')
                            ->select('m.id')
                            ->where('m.path = :path')
                            ->setVariable('path', $path)
                            ->getFirstScalarResult();
        } catch (Exception) {
            return '';
        }

        return new MediaBlock([
            'media'    => [
                'id' => $imageId
            ],
            'lightbox' => true,
            'lazy'     => true,
            'caption'  => $caption,
            'alt'      => $rawCaption
        ])->render();
    }
}
