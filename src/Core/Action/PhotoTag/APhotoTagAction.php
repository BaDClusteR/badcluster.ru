<?php

declare(strict_types=1);

namespace BC\Core\Action\PhotoTag;

use BC\Core\Action\DTO\CreatePhotoTagRequest;
use BC\Core\Action\DTO\SavePhotoTagRequest;
use BC\Model\PhotoTag;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use Runway\Exception\Exception;

abstract class APhotoTagAction {
    /**
     * @throws ActionValidationException
     */
    protected function validate(CreatePhotoTagRequest|SavePhotoTagRequest $request): void {
        $errors = [];
        $title = trim($request->title);

        if ($title === '') {
            $errors['title'] = 'Укажите название тэга';
        }

        if (
            ($title !== '')
            && ($sameTag = $this->getTagWithTheSameTitle($request))
        ) {
            $errors['title'] = sprintf(
                'Такой тэг уже есть (#%d)',
                $sameTag->getId()
            );
        }

        if ($errors) {
            throw new ActionValidationException($errors);
        }
    }

    protected function syncModel(PhotoTag $tag, CreatePhotoTagRequest|SavePhotoTagRequest $request): void {
        $tag->setTitle(trim($request->title))
            ->setPosition(
                $request->position > 0
                    ? $request->position
                    : $this->getNextPosition()
            )
            ->persist();
    }

    /**
     * Позиция для тэга без явной позиции: максимальная в базе + 100.
     */
    private function getNextPosition(): int {
        try {
            $max = PhotoTag::getQueryBuilder()
                           ->select('MAX(position) AS max_position')
                           ->getFirstScalarResult();
        } catch (Exception) {
            $max = 0;
        }

        return ((int) $max) + 100;
    }

    private function getTagWithTheSameTitle(CreatePhotoTagRequest|SavePhotoTagRequest $request): ?PhotoTag {
        $qb = PhotoTag::getQueryBuilder()
                      ->andWhere('LOWER(title) = :title')
                      ->setVariable('title', mb_strtolower(trim($request->title)));

        if ($request instanceof SavePhotoTagRequest) {
            $qb = $qb->andWhere('id != :tagId')
                     ->setVariable('tagId', $request->id);
        }

        try {
            /** @var PhotoTag|null $result */
            $result = $qb->getFirstEntity();
        } catch (Exception) {
            return null;
        }

        return $result;
    }
}
