<?php

declare(strict_types=1);

namespace BC\Core\Action\StaticPage;

use BC\Core\Action\DTO\SaveStaticPageRequest;
use BC\Model\StaticPage;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use Runway\DataStorage\Exception\DBException;
use Runway\Exception\Exception;
use Runway\Model\Exception\ModelException;

class SaveStaticPageAction extends AStaticPageAction implements ISaveStaticPageAction {
    /**
     * @throws ActionValidationException
     * @throws DBException
     * @throws Exception
     * @throws ModelException
     */
    public function run(SaveStaticPageRequest $request): void {
        /** @var StaticPage|null $page */
        $page = StaticPage::findByUniqueIdentifier($request->id);

        if (!$page) {
            throw new Exception("Static page #$request->id not found");
        }

        $this->validate($request);

        // createdDate is set once, at creation, and never touched again.
        $this->syncModel($page, $request);
    }
}
