<?php

declare(strict_types=1);

namespace BC\Core\Action\PulseItem;

use BC\Core\Action\DTO\SavePulseItemRequest;
use BC\Model\PulseItem;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use Runway\DataStorage\Exception\DBException;
use Runway\Exception\Exception;
use Runway\Model\Exception\ModelException;

class SavePulseItemAction extends APulseItemAction implements ISavePulseItemAction {
    /**
     * @throws ActionValidationException
     * @throws DBException
     * @throws Exception
     * @throws ModelException
     */
    public function run(SavePulseItemRequest $request): void {
        /** @var PulseItem|null $item */
        $item = PulseItem::findByUniqueIdentifier($request->id);

        if (!$item) {
            throw new Exception("Pulse item #$request->id not found");
        }

        $this->validate($request);

        $this->syncModel($item, $request);
    }
}
