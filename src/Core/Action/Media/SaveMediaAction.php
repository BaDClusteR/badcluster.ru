<?php

declare(strict_types=1);

namespace BC\Core\Action\Media;

use BC\Core\Action\DTO\SaveMediaRequest;
use BC\Core\Action\DTO\SaveMediaResponse;
use BC\Model\Media;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use Runway\DataStorage\Exception\DBException;
use Runway\Exception\Exception;
use Runway\Model\Exception\ModelException;

class SaveMediaAction implements ISaveMediaAction {
    /**
     * @throws ActionValidationException
     * @throws DBException
     * @throws Exception
     * @throws ModelException
     */
    public function run(SaveMediaRequest $request): SaveMediaResponse {
        /** @var Media|null $media */
        $media = Media::findByUniqueIdentifier($request->id);

        if (!$media) {
            throw new Exception("Media #$request->id not found");
        }

        $this->validate($request);

        $media->setWidth($request->width)
              ->setHeight($request->height)
              ->setAlt($request->alt)
              ->persist();

        return new SaveMediaResponse($media);
    }

    /**
     * @throws ActionValidationException
     */
    private function validate(SaveMediaRequest $request): void {
        $errors = [];

        if ($request->width < 0) {
            $errors['width'] = 'Ширина не может быть отрицательной';
        }

        if ($request->height < 0) {
            $errors['height'] = 'Высота не может быть отрицательной';
        }

        if ($errors) {
            throw new ActionValidationException($errors);
        }
    }
}