<?php

declare(strict_types=1);

namespace BC\Core\Helper;

use BC\Core\Converter\Media\IMediaConverter;
use BC\Model\Media;
use Runway\Exception\Exception;

readonly class BlockHelper implements IBlockHelper {
    public function __construct(
        private IMediaConverter $mediaConverter
    ) {
    }

    public function cleanBlocks(array $content): array {
        foreach ((array) ($content['blocks'] ?? []) as $i => $block) {
            if (
                (string) ($block['type'] ?? '') === 'media'
                && !empty($block['data']['media'])
            ) {
                $content['blocks'][$i]['data']['media'] = $this->cleanMedia($block['data']['media']);
            } elseif (
                (string) ($block['type'] ?? '') === 'gallery'
                && !empty($block['data']['slides'])
            ) {
                foreach ($content['blocks'][$i]['data']['slides'] as $j => $slide) {
                    $content['blocks'][$i]['data']['slides'][$j] = $this->cleanMedia(
                        $content['blocks'][$i]['data']['slides'][$j]
                    );
                }
            }
        }

        return $content;
    }

    /**
     * @throws Exception
     */
    public function enrichBlocks(array $content): array {
        foreach ((array) ($content['blocks'] ?? []) as $i => $block) {
            if (
                (string) ($block['type'] ?? '') === 'media'
                && !empty($block['data']['media']['id'])
            ) {
                $media = Media::findByUniqueIdentifier($block['data']['media']['id']);

                if ($media) {
                    $content['blocks'][$i]['data']['media'] = $this->mediaConverter->convertMedia($media)->toArray();
                }
            } elseif (
                (string) ($block['type'] ?? '') === 'gallery'
                && !empty($block['data']['slides'])
            ) {
                foreach ($content['blocks'][$i]['data']['slides'] as $j => $slide) {
                    $media = Media::findByUniqueIdentifier(
                        (int) ($slide['id'] ?? 0)
                    );
                    if ($media) {
                        $content['blocks'][$i]['data']['slides'][$j] = $this->mediaConverter->convertMedia(
                            $media
                        )->toArray();
                    }
                }
            }
        }

        return $content;
    }

    private function cleanMedia(array $media): ?array {
        if (!empty($media['id'])) {
            try {
                $model = Media::findByUniqueIdentifier(
                    (int) $media['id']
                );

                if ($model) {
                    $model->setAlt(
                        (string) ($media['alt'] ?? '')
                    )->setWidth(
                        (int) ($media['width'] ?? 0)
                    )->setHeight(
                        (int) ($media['height'] ?? 0)
                    );
                    $model->persist();
                }
            } catch (Exception) {
            }

            $media = ['id' => $media['id']];
        }

        return $media;
    }
}
