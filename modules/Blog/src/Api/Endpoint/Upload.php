<?php

namespace BC\Modules\Blog\Api\Endpoint;

use BC\Model\Media;

class Upload extends \BC\Api\Endpoint\Upload
{
    protected function doWithPurpose(Media $media, ?string $purpose): Media
    {
        if (
            $purpose === 'cover'
            && $media->isImage()
        ) {
            $this->tryGenerateThumbnails($media, [200]);
        }

        return parent::doWithPurpose($media, $purpose);
    }
}
