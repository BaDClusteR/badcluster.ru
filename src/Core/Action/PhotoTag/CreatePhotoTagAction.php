<?php

declare(strict_types=1);

namespace BC\Core\Action\PhotoTag;

use BC\Core\Action\DTO\CreatePhotoTagRequest;
use BC\Core\Action\DTO\CreatePhotoTagResponse;
use BC\Model\PhotoTag;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use Runway\DataStorage\Exception\DBException;
use Runway\Model\Exception\ModelException;

class CreatePhotoTagAction extends APhotoTagAction implements ICreatePhotoTagAction {
    /**
     * @throws ActionValidationException
     * @throws DBException
     * @throws ModelException
     */
    public function run(CreatePhotoTagRequest $request): CreatePhotoTagResponse {
        $this->validate($request);

        $tag = new PhotoTag();

        $this->syncModel($tag, $request);

        return new CreatePhotoTagResponse($tag);
    }
}
