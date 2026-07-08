<?php

declare(strict_types=1);

namespace BC\Core\Action\Fact;

use BC\Core\Action\DTO\CreateFactRequest;
use BC\Core\Action\DTO\CreateFactResponse;
use BC\Model\Fact;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use Runway\DataStorage\Exception\DBException;
use Runway\Model\Exception\ModelException;

class CreateFactAction extends AFactAction implements ICreateFactAction {
    /**
     * @throws ActionValidationException
     * @throws DBException
     * @throws ModelException
     */
    public function run(CreateFactRequest $request): CreateFactResponse {
        $this->validate($request);

        $fact = new Fact();

        $this->syncModel($fact, $request);

        return new CreateFactResponse($fact);
    }
}