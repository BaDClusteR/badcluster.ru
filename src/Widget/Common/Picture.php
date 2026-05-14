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

    protected ?Media $image = null {
        get {
            return $this->image;
        }
    }

    /**
     * @var array<int, int> Min media width => image width
     */
    protected array $breakpoints = [] {
        get {
            return $this->breakpoints;
        }
    }

    private bool $isLazyLoad = false;

    protected string $pictureCssClass = '' {
        get {
            return $this->pictureCssClass;
        }
    }

    protected string $imgCssClass = '' {
        get {
            return $this->imgCssClass;
        }
    }

    protected string $caption = '' {
        get {
            return $this->caption;
        }
    }

    public function __construct(array $context = [])
    {
        parent::__construct($context);

        if (($this->context['image'] ?? null) instanceof Media) {
            $this->image = $this->context['image'];
        }

        if (array_key_exists('lazyLoad', $this->context)) {
            $this->isLazyLoad = (bool)$this->context['lazyLoad'];
        }

        if (is_array($this->context['breakpoints'] ?? null)) {
            $this->breakpoints = $this->context['breakpoints'];
        }

        if (!empty($this->context['class'])) {
            $this->imgCssClass = $this->context['class'];
        }

        if (!empty($this->context['pictureClass'])) {
            $this->pictureCssClass = $this->context['pictureClass'];
        }

        if (!empty($this->context['caption'])) {
            $this->caption = $this->context['caption'];
        }
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

    protected function getPictureAttributes(): array {
        $result = [];

        if ($class = $this->pictureCssClass) {
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
        $image = $this->image;
        if (!$image) {
            return [];
        }

        $result = [
            'src'    => $image->getWebPath(),
            'width'  => $image->getWidth(),
            'height' => $image->getHeight(),
            'alt'    => $image->getAlt() ?: $this->caption,
        ];

        if ($this->isLazyLoad) {
            $result['loading'] = 'lazy';
        }

        if ($class = $this->imgCssClass) {
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
