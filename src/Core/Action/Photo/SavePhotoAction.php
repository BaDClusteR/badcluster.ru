<?php

declare(strict_types=1);

namespace BC\Core\Action\Photo;

use BC\Core\Action\DTO\SavePhotoRequest;
use BC\Core\Action\DTO\SavePhotoResponse;
use BC\Core\Action\Media\AMediaAction;
use BC\Model\Photo;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use Runway\DataStorage\Exception\DBException;
use Runway\Exception\Exception;
use Runway\FileSystem\Exception\CannotDeleteFileException;
use Runway\FileSystem\Exception\FileSystemException;
use Runway\Model\Exception\ModelException;

class SavePhotoAction extends AMediaAction implements ISavePhotoAction {
    /**
     * @throws ActionValidationException
     * @throws DBException
     * @throws Exception
     * @throws FileSystemException
     * @throws ModelException
     */
    public function run(SavePhotoRequest $request): SavePhotoResponse {
        /** @var Photo|null $photo */
        $photo = Photo::findByUniqueIdentifier($request->id);

        if (!$photo) {
            throw new Exception("Photo #$request->id not found");
        }

        $photo->setAlt($request->alt)
              ->setPosition($request->position);

        $oldFilePath = null;
        if ($request->media) {
            $this->validateMedia($request->media);
            $oldFilePath = $photo->getLocalPath();

            // Тамбнейлы старой картинки удаляем целиком: у новой могут быть
            // другие ширины, и force-перегенерация их не подхватит
            foreach ($photo->getThumbnails() as $thumbnail) {
                $thumbnail->remove();
            }

            $this->attachMediaFile($photo, $request->media);
        }

        $photo->persist();

        $photo->syncTags($request->tags);

        if ($request->media) {
            $this->generateThumbnails($photo);

            $request->media->remove();

            try {
                $this->getFileSystem()->remove($oldFilePath);
            } catch (CannotDeleteFileException) {
            }
        }

        return new SavePhotoResponse($photo);
    }
}