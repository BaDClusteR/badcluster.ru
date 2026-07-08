<?php

declare(strict_types=1);

namespace BC\Core\Action\PhotoTag;

use BC\Core\Action\DTO\SavePhotoTagRequest;
use BC\Model\PhotoTag;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use Runway\DataStorage\Exception\DBException;
use Runway\Exception\Exception;
use Runway\Model\Exception\ModelException;

class SavePhotoTagAction extends APhotoTagAction implements ISavePhotoTagAction {
    /**
     * @throws ActionValidationException
     * @throws DBException
     * @throws Exception
     * @throws ModelException
     */
    public function run(SavePhotoTagRequest $request): void {
        /** @var PhotoTag|null $tag */
        $tag = PhotoTag::findByUniqueIdentifier($request->id);

        if (!$tag) {
            throw new Exception("Photo tag #$request->id not found");
        }

        $this->validate($request);

        $this->syncModel($tag, $request);
    }
}
