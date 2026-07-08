<?php

declare(strict_types=1);

namespace BC\Core\Action\Redirect;

use BC\Core\Action\DTO\SaveRedirectRequest;
use BC\Model\Redirect;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use Runway\DataStorage\Exception\DBException;
use Runway\Exception\Exception;
use Runway\Model\Exception\ModelException;

class SaveRedirectAction extends ARedirectAction implements ISaveRedirectAction {
    /**
     * @throws ActionValidationException
     * @throws DBException
     * @throws Exception
     * @throws ModelException
     */
    public function run(SaveRedirectRequest $request): void {
        /** @var Redirect|null $redirect */
        $redirect = Redirect::findByUniqueIdentifier($request->id);

        if (!$redirect) {
            throw new Exception("Redirect #$request->id not found");
        }

        $this->validate($request);

        $this->syncModel($redirect, $request);
    }
}
