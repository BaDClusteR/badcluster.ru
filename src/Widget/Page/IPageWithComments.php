<?php

namespace BC\Widget\Page;

use BC\DTO\CommentsConfigDTO;

interface IPageWithComments {
    public function getCommentsConfig(): CommentsConfigDTO;
}
