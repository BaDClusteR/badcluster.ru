<?php

namespace BC\Core\Media\PostProcessor;

use BC\Core\Trait\LoggerTrait;
use BC\Model\Media;
use Runway\Exception\Exception;

readonly class ImagePostprocessor implements IImagePostprocessor
{
    use LoggerTrait;

    /**
     * @param int $significantSizeDifferenceBytes If the difference between WebP and AVIF size is less than this, then
     *                                            remove AVIF.
     */
    public function __construct(
        private int $significantSizeDifferenceBytes
    ) {
    }

    /**
     * @param Media[][] $thumbnailGroups
     *
     * @return Media[][]
     */
    public function postProcessThumbnails(array $thumbnailGroups): array
    {
        foreach ($thumbnailGroups as $group) {
            $webp = $this->getWebpThumbnail($group);
            $avif = $this->getAvifThumbnail($group);

            if (!$webp || !$avif || $webp->getSize() - $avif->getSize() > $this->significantSizeDifferenceBytes) {
                return $thumbnailGroups;
            }
        }

        /*
         * Remove all AVIF thumbnails if the difference between the size of WebP thumbnails and the size of the
         * corresponding AVIF thumbnails is less than self::SIGNIFICANT_SIZE_DIFFERENCE.
         */
        $newGroups = [];
        foreach ($thumbnailGroups as $group) {
            $webp = $this->getWebpThumbnail($group);
            $avif = $this->getAvifThumbnail($group);

            try {
                $avif->remove();
            } catch (Exception $e) {
                $this->getLogger()->warning(
                    "Cannot delete redundant AVIF thumbnail: {$e->getMessage()}",
                    [
                        'webp_id' => $webp?->getId(),
                        'avif_id' => $avif->getId(),
                        'parent'  => $avif->getParent()->getId(),
                    ]
                );
            }

            $newGroups[] = [$webp];
        }

        return $newGroups;
    }

    private function getWebpThumbnail(array $thumbnailGroup): ?Media {
        return $this->getThumbnailByMimeType($thumbnailGroup, 'image/webp');
    }

    private function getAvifThumbnail(array $thumbnailGroup): ?Media {
        return $this->getThumbnailByMimeType($thumbnailGroup, 'image/avif');
    }

    private function getThumbnailByMimeType(array $thumbnailGroup, string $mimeType): ?Media {
        return array_find(
            $thumbnailGroup,
            static fn(Media $thumbnail) => $thumbnail->getMime() === $mimeType
        );
    }
}
