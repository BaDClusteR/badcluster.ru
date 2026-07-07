<?php

declare(strict_types=1);

namespace BC\Core\Action\Screenshot;

use BC\Core\Action\DTO\SaveScreenshotRequest;
use BC\Core\Action\DTO\SaveScreenshotResponse;
use BC\Core\Action\Media\AMediaAction;
use BC\Model\Screenshot;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use Runway\DataStorage\Exception\DBException;
use Runway\Exception\Exception;
use Runway\FileSystem\Exception\CannotDeleteFileException;
use Runway\FileSystem\Exception\FileSystemException;
use Runway\Model\Exception\ModelException;

class SaveScreenshotAction extends AMediaAction implements ISaveScreenshotAction {
    /**
     * @throws ActionValidationException
     * @throws DBException
     * @throws Exception
     * @throws FileSystemException
     * @throws ModelException
     */
    public function run(SaveScreenshotRequest $request): SaveScreenshotResponse {
        /** @var Screenshot|null $screenshot */
        $screenshot = Screenshot::findByUniqueIdentifier($request->id);

        if (!$screenshot) {
            throw new Exception("Screenshot #$request->id not found");
        }

        $screenshot->setAlt($request->alt)
                   ->setPosition($request->position);

        $oldFilePath = null;
        if ($request->media) {
            $this->validateMedia($request->media);
            $oldFilePath = $screenshot->getLocalPath();

            // Тамбнейлы старой картинки удаляем целиком: у новой могут быть
            // другие ширины, и force-перегенерация их не подхватит
            foreach ($screenshot->getThumbnails() as $thumbnail) {
                $thumbnail->remove();
            }

            $this->attachMediaFile($screenshot, $request->media);
        }

        $screenshot->persist();

        if ($request->media) {
            $this->generateThumbnails($screenshot);

            $request->media->remove();

            try {
                $this->getFileSystem()->remove($oldFilePath);
            } catch (CannotDeleteFileException) {
            }
        }

        return new SaveScreenshotResponse($screenshot);
    }
}