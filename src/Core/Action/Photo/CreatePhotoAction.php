<?php

declare(strict_types=1);

namespace BC\Core\Action\Photo;

use BC\Core\Action\DTO\CreatePhotoRequest;
use BC\Core\Action\DTO\CreatePhotoResponse;
use BC\Core\Action\Media\AMediaAction;
use BC\Model\Photo;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use DateTime;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\FileSystem\Exception\FileSystemException;
use Runway\Model\Exception\ModelException;

class CreatePhotoAction extends AMediaAction implements ICreatePhotoAction {
    /**
     * @throws ActionValidationException
     * @throws DBException
     * @throws FileSystemException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    public function run(CreatePhotoRequest $request): CreatePhotoResponse {
        $this->validateMedia($request->media);

        $photo = new Photo()
            ->setAlt($request->alt)
            ->setPosition($request->position)
            ->setUploadedAt(new DateTime());

        $this->attachMediaFile($photo, $request->media);

        $photo->persist();

        $photo->syncTags($request->tags);

        $this->generateThumbnails($photo);

        // Исходная загрузка больше не нужна: файл скопирован в папку фоток
        $request->media->remove();

        return new CreatePhotoResponse($photo);
    }
}