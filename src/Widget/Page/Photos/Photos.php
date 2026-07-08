<?php

namespace BC\Widget\Page\Photos;

use BC\Model\Photo as PhotoModel;
use BC\Model\PhotoTag;
use BC\Widget\AWidget;
use Runway\Exception\Exception;

class Photos extends AWidget {
    protected function getTemplatePath(): string {
        return 'about/photos.phtml';
    }

    /**
     * @return iterable<PhotoModel>
     */
    protected function getPhotosIterator(): iterable {
        try {
            return PhotoModel::getQueryBuilder('p')
                             ->where('p.parent_id IS NULL')
                             ->orderBy('p.position', 'DESC')
                             ->iterate();
        } catch (Exception) {
            return [];
        }
    }

    /**
     * @return iterable<PhotoTag>
     */
    protected function getPhotoTagsIterator(): iterable {
        try {
            return PhotoTag::iterate([], ['position', 'ASC']);
        } catch (Exception) {
            return [];
        }
    }
}
