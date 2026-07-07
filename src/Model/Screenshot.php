<?php

namespace BC\Model;

use DateTime;
use Runway\DataStorage\Attribute as DS;

/**
 * @generated-model-helpers
 * @method int getId()
 * @method self setId(int $id)
 * @method string getPath()
 * @method self setPath(string $path)
 * @method int getWidth()
 * @method self setWidth(int $width)
 * @method int getHeight()
 * @method self setHeight(int $height)
 * @method int getSize()
 * @method self setSize(int $size)
 * @method string getMime()
 * @method self setMime(string $mime)
 * @method string getAlt()
 * @method self setAlt(string $alt)
 * @method Media|null getParent()
 * @method self setParent(Media|null $parent)
 * @method string getMd5()
 * @method self setMd5(string $md5)
 * @method int getPosition()
 * @method self setPosition(int $position)
 * @method DateTime getUploadedAt()
 * @method self setUploadedAt(DateTime $uploadedAt)
 */
#[DS\Table('screenshots')]
class Screenshot extends Media {
    #[DS\Column]
    protected int $position = 0;

    #[DS\Column]
    protected DateTime $uploadedAt;

    public static function getSubfolder(): string {
        return 'screenshots';
    }

    protected function init(): void {
        parent::init();

        // Дефолт нужен строкам тамбнейлов, которые создает ThumbnailGenerator:
        // он не знает про uploadedAt и без дефолта в базу уедет NULL
        $this->uploadedAt ??= new DateTime();
    }
}
