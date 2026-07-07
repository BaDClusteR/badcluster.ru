<?php

declare(strict_types=1);

namespace BC\Core\Action\Screenshot;

use BC\Core\Action\DTO\CreateScreenshotRequest;
use BC\Core\Action\DTO\CreateScreenshotResponse;
use BC\Core\Action\Media\AMediaAction;
use BC\Model\Screenshot;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use DateTime;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\FileSystem\Exception\FileSystemException;
use Runway\Model\Exception\ModelException;

class CreateScreenshotAction extends AMediaAction implements ICreateScreenshotAction {
    /**
     * @throws ActionValidationException
     * @throws DBException
     * @throws FileSystemException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    public function run(CreateScreenshotRequest $request): CreateScreenshotResponse {
        $this->validateMedia($request->media);

        $screenshot = new Screenshot()
            ->setAlt($request->alt)
            ->setPosition($request->position)
            ->setUploadedAt(new DateTime());

        $this->attachMediaFile($screenshot, $request->media);

        $screenshot->persist();

        $this->generateThumbnails($screenshot);

        // Исходная загрузка больше не нужна: файл скопирован в папку скриншотов
        $request->media->remove();

        return new CreateScreenshotResponse($screenshot);
    }
}
