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
        $blocks = $content['blocks'] ?? null;

        if (!is_array($blocks)) {
            return $content;
        }

        foreach ($blocks as $i => $block) {
            if (!is_array($block) || !is_array($block['data'] ?? null)) {
                continue;
            }

            $data = $block['data'];

            if (
                (($block['type'] ?? '') === 'media')
                && !empty($data['media'])
                && is_array($data['media'])
            ) {
                $data['media'] = $this->cleanMedia($data['media']);
            } elseif (
                (($block['type'] ?? '') === 'gallery')
                && !empty($data['slides'])
                && is_array($data['slides'])
            ) {
                foreach ($data['slides'] as $j => $slide) {
                    if (is_array($slide)) {
                        $data['slides'][$j] = $this->cleanMedia($slide);
                    }
                }
            }

            $block['data'] = $data;
            $blocks[$i] = $block;
        }

        $content['blocks'] = $blocks;

        return $content;
    }

    /**
     * @throws Exception
     */
    public function enrichBlocks(array $content): array {
        $blocks = $content['blocks'] ?? null;

        if (!is_array($blocks)) {
            return $content;
        }

        foreach ($blocks as $i => $block) {
            if (!is_array($block) || !is_array($block['data'] ?? null)) {
                continue;
            }

            $data = $block['data'];

            if (
                (($block['type'] ?? '') === 'media')
                && is_array($data['media'] ?? null)
                && !empty($data['media']['id'])
            ) {
                $media = Media::findByUniqueIdentifier(
                    MixedValue::toInt($data['media']['id'])
                );

                if ($media) {
                    $data['media'] = $this->mediaConverter->convertMedia($media)->toArray();
                }
            } elseif (
                (($block['type'] ?? '') === 'gallery')
                && !empty($data['slides'])
                && is_array($data['slides'])
            ) {
                foreach ($data['slides'] as $j => $slide) {
                    $media = Media::findByUniqueIdentifier(
                        MixedValue::toInt(is_array($slide) ? ($slide['id'] ?? 0) : 0)
                    );

                    if ($media) {
                        $data['slides'][$j] = $this->mediaConverter->convertMedia(
                            $media
                        )->toArray();
                    }
                }
            }

            $block['data'] = $data;
            $blocks[$i] = $block;
        }

        $content['blocks'] = $blocks;

        return $content;
    }

    /**
     * @param array<mixed> $media The media of a block: ['id' => ..., 'alt' => ..., 'width' => ..., 'height' => ...].
     *
     * @return array<mixed>
     */
    private function cleanMedia(array $media): array {
        if (!empty($media['id'])) {
            try {
                $model = Media::findByUniqueIdentifier(
                    MixedValue::toInt($media['id'])
                );

                if ($model) {
                    $model->setAlt(
                        MixedValue::toString($media['alt'] ?? '')
                    )->setWidth(
                        MixedValue::toInt($media['width'] ?? 0)
                    )->setHeight(
                        MixedValue::toInt($media['height'] ?? 0)
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
