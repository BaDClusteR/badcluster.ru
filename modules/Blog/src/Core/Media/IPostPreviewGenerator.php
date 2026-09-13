<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Media;

use BC\Core\Exception\ImageException;
use BC\Modules\Blog\Model\Post;

interface IPostPreviewGenerator {
    /**
     * Картинка-превью поста для соцсетей и мессенджеров (og:image).
     *
     * @return string JPEG
     *
     * @throws ImageException
     */
    public function generate(Post $post): string;
}
