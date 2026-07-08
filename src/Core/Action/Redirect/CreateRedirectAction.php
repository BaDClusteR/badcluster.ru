<?php

declare(strict_types=1);

namespace BC\Core\Action\Redirect;

use BC\Core\Action\DTO\CreateRedirectRequest;
use BC\Core\Action\DTO\CreateRedirectResponse;
use BC\Model\Redirect;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use Runway\DataStorage\Exception\DBException;
use Runway\Model\Exception\ModelException;

class CreateRedirectAction extends ARedirectAction implements ICreateRedirectAction {
    /**
     * @throws ActionValidationException
     * @throws DBException
     * @throws ModelException
     */
    public function run(CreateRedirectRequest $request): CreateRedirectResponse {
        $this->validate($request);

        $redirect = new Redirect();

        $this->syncModel($redirect, $request);

        return new CreateRedirectResponse($redirect);
    }
}
