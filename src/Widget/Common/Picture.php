<?php

namespace BC\Widget\Common;

use BC\Core\Trait\AttributesHelperTrait;
use BC\Core\Trait\PathsProviderTrait;
use BC\Model\Media;
use BC\Widget\AWidget;

class Picture extends AWidget
{
    use PathsProviderTrait;
    use AttributesHelperTrait;

    private ?Media $image = null;

    /**
     * @var array<int, int> Min media width => image width
     */
    private array $breakpoints = [];

    private bool $isLazyLoad = false;

    private string $pictureCssClass = '';

    private string $imgCssClass = '';

    public function __construct(array $context = [])
    {
        parent::__construct($context);

        if (($context['image'] ?? null) instanceof Media) {
            $this->image = $context['image'];
        }

        if (array_key_exists('lazyLoad', $context)) {
            $this->isLazyLoad = (bool)$context['lazyLoad'];
        }

        if (is_array($context['breakpoints'] ?? null)) {
            $this->breakpoints = $context['breakpoints'];
        }

        if (!empty($context['class'])) {
            $this->imgCssClass = $context['class'];
        }

        if (!empty($context['pictureClass'])) {
            $this->pictureCssClass = $context['pictureClass'];
        }
    }

    protected function getImage(): ?Media {
        return $this->image;
    }

    /**
     * @return array<int, int>
     */
    protected function getBreakpoints(): array {
        return $this->breakpoints;
    }

    protected function hasImages(int $width, string $mime): bool
    {
        return $this->image?->getThumbnail($width, $mime)
            || $this->image?->getThumbnail($width * 2, $mime);
    }

    protected function getSource(int $width, string $mime, int $minWidth, int $maxWidth): string {
        if (!$this->hasImages($width, $mime)) {
            return '';
        }

        $thumbnail = $this->image->getThumbnail($width, $mime);
        $thumbnail2x = $this->image->getThumbnail($width * 2, $mime);
        $imagesWebRoot = $this->getPathsProvider()->getImagesWebPath();

        if (!$thumbnail) {
            $thumbnail = $thumbnail2x;
        } elseif (!$thumbnail2x) {
            $thumbnail2x = $this->image->getThumbnail($this->image->getWidth(), $mime);
        }

        if (!$thumbnail2x) {
            return '';
        }

        $srcset = $thumbnail
            ? "$imagesWebRoot/{$thumbnail->getPath()} 1x, $imagesWebRoot/{$thumbnail2x->getPath()} 2x"
            : "$imagesWebRoot/{$thumbnail2x->getPath()}";

        $media = "";
        if ($minWidth > 0 && $maxWidth > 0) {
            $media = "(width >= {$minWidth}px) and (width < {$maxWidth}px)";
        } elseif ($minWidth > 0) {
            $media = "(width >= {$minWidth}px)";
        } elseif ($maxWidth > 0) {
            $media = "(width < {$maxWidth}px)";
        }

        $attrs = [
            'srcset' => $srcset,
            'type'   => $mime,
        ];

        if ($media) {
            $attrs['media'] = $media;
        }

        return "<source " . $this->getAttributesHelper()->getAttributesAsString($attrs) . " />";
    }

    protected function getAllowedMimeTypes(): array {
        return [
            'image/avif',
            'image/webp'
        ];
    }

    protected function getTemplatePath(): string {
        return 'common/picture.phtml';
    }

    protected function getPictureCssClass(): string {
        return $this->pictureCssClass;
    }

    protected function getImgCssClass(): string {
        return $this->imgCssClass;
    }

    protected function getPictureAttributes(): array {
        $result = [];

        if ($class = $this->getPictureCssClass()) {
            $result['class'] = $class;
        }

        return $result;
    }

    protected function getPictureAttributesAsString(): string {
        return $this->getAttributesHelper()->getAttributesAsString(
            $this->getPictureAttributes()
        );
    }

    protected function getImgAttributes(): array {
        $image = $this->getImage();
        if (!$image) {
            return [];
        }

        $result = [
            'src'    => $image->getWebPath(),
            'width'  => $image->getWidth(),
            'height' => $image->getHeight(),
            'alt'    => $image->getAlt(),
        ];

        if ($this->isLazyLoad) {
            $result['loading'] = 'lazy';
        }

        if ($class = $this->getImgCssClass()) {
            $result['class'] = $class;
        }

        return $result;
    }

    protected function getImgAttributesAsString(): string {
        return $this->getAttributesHelper()->getAttributesAsString(
            $this->getImgAttributes()
        );
    }
}
