<?php

declare(strict_types=1);

namespace BC\Core\Action\Fact;

use BC\Core\Action\DTO\SaveFactRequest;
use BC\Model\Fact;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use Runway\DataStorage\Exception\DBException;
use Runway\Exception\Exception;
use Runway\Model\Exception\ModelException;

class SaveFactAction extends AFactAction implements ISaveFactAction {
    /**
     * @throws ActionValidationException
     * @throws DBException
     * @throws Exception
     * @throws ModelException
     */
    public function run(SaveFactRequest $request): void {
        /** @var Fact|null $fact */
        $fact = Fact::findByUniqueIdentifier($request->id);

        if (!$fact) {
            throw new Exception("Fact #$request->id not found");
        }

        $this->validate($request);

        $this->syncModel($fact, $request);
    }
}