<?php

declare(strict_types=1);

namespace BC\Widget\Page;

use BC\DTO\CommentsConfigDTO;

interface IPageWithComments {
    public function getCommentsConfig(): CommentsConfigDTO;
}
