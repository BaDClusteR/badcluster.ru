<?php

declare(strict_types=1);

namespace BC\Core\Action\Redirect;

use BC\Core\Action\DTO\CreateRedirectRequest;
use BC\Core\Action\DTO\SaveRedirectRequest;
use BC\Model\Redirect;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use Runway\Exception\Exception;

abstract class ARedirectAction {
    protected const array ALLOWED_CODES = [301, 410];

    /**
     * @throws ActionValidationException
     */
    protected function validate(CreateRedirectRequest|SaveRedirectRequest $request): void {
        $errors = [];
        $path = trim($request->path);

        if ($path === '') {
            $errors['path'] = 'Укажите путь';
        }

        if (!in_array($request->code, static::ALLOWED_CODES, true)) {
            $errors['code'] = sprintf(
                'Код возврата должен быть одним из: %s',
                implode(', ', static::ALLOWED_CODES)
            );
        }

        if (($request->code === 301) && (trim($request->destination) === '')) {
            $errors['destination'] = 'Укажите адрес назначения';
        }

        if (
            ($path !== '')
            && ($sameRedirect = $this->getRedirectWithTheSamePath($request))
        ) {
            $errors['path'] = sprintf(
                'Такой путь уже занят редиректом #%d',
                $sameRedirect->getId()
            );
        }

        if ($errors) {
            throw new ActionValidationException($errors);
        }
    }

    protected function syncModel(Redirect $redirect, CreateRedirectRequest|SaveRedirectRequest $request): void {
        $redirect->setPath(trim($request->path))
                 ->setCode($request->code)
                 ->setDestination(
                     $request->code === 301
                         ? trim($request->destination)
                         : null
                 )
                 ->persist();
    }

    private function getRedirectWithTheSamePath(CreateRedirectRequest|SaveRedirectRequest $request): ?Redirect {
        $qb = Redirect::getQueryBuilder()
                      ->andWhere('LOWER(path) = :path')
                      ->setVariable('path', mb_strtolower(trim($request->path)));

        if ($request instanceof SaveRedirectRequest) {
            $qb = $qb->andWhere('id != :redirectId')
                     ->setVariable('redirectId', $request->id);
        }

        try {
            /** @var Redirect|null $result */
            $result = $qb->getFirstEntity();
        } catch (Exception) {
            return null;
        }

        return $result;
    }
}
