<?php

declare(strict_types=1);

namespace BC\Core\Action\StaticPage;

use BC\Core\Action\DTO\CreateStaticPageRequest;
use BC\Core\Action\DTO\CreateStaticPageResponse;
use BC\Model\StaticPage;
use BC\Modules\Blog\Core\Action\Exception\ActionValidationException;
use DateTime;
use Runway\DataStorage\Exception\DBException;
use Runway\Model\Exception\ModelException;

class CreateStaticPageAction extends AStaticPageAction implements ICreateStaticPageAction {
    /**
     * @throws ActionValidationException
     * @throws DBException
     * @throws ModelException
     */
    public function run(CreateStaticPageRequest $request): CreateStaticPageResponse {
        $this->validate($request);

        $page = new StaticPage()->setCreatedDate(new DateTime());

        $this->syncModel($page, $request);

        return new CreateStaticPageResponse($page);
    }
}
