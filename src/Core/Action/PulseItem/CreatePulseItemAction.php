<?php

declare(strict_types=1);

namespace BC\Core\Action\PulseItem;

use BC\Core\Action\DTO\CreatePulseItemRequest;
use BC\Core\Action\DTO\CreatePulseItemResponse;
use BC\Model\PulseItem;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use Runway\DataStorage\Exception\DBException;
use Runway\Model\Exception\ModelException;

class CreatePulseItemAction extends APulseItemAction implements ICreatePulseItemAction {
    /**
     * @throws ActionValidationException
     * @throws DBException
     * @throws ModelException
     */
    public function run(CreatePulseItemRequest $request): CreatePulseItemResponse {
        $this->validate($request);

        $item = new PulseItem();

        $this->syncModel($item, $request);

        return new CreatePulseItemResponse($item);
    }
}
