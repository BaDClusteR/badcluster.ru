<?php

namespace BC\Controller;

use BC\Core\Auth\IAuth;
use BC\Core\Response\JsonResponse;
use BC\Core\Response\SuccessfulJsonResponse;
use BC\Model\Comment as CommentModel;
use BC\Provider\ICommentsProvider;
use DateTime;
use Exception;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Logger\ILogger;
use Runway\Model\Exception\ModelException;
use Runway\Request\IRequest;
use Runway\Request\Response;
use Runway\Singleton\Container;

readonly class Comment {
    public function __construct(
        private IRequest $request,
        private ILogger $logger,
        private IAuth $auth,
    ) {
    }

    public function run(): Response {
        $pageType = $this->request->getPostParameter('type')->asString();
        $pageId = $this->request->getPostParameter('id')->asInt();
        $parentId = $this->auth->isAuthenticated()
            ? $this->request->getPostParameter('parentId')->asInt()
            : null;

        if (!$this->getCommentsProvider()->isPageExist($pageType, $pageId)) {
            return new JsonResponse(
                code: 400,
                data: [
                    'status'  => 'error',
                    'message' => sprintf('Сущность %s с ID = %d не найдена, либо к ней нет доступа', $pageType, $pageId)
                ]
            );
        }

        $nickname = $this->request->getPostParameter('nickname')->asString();
        $comment = $this->request->getPostParameter('comment')->asString();

        try {
            $model = $this->doPost($pageType, $pageId, $nickname, $comment, $parentId);
            $model->persist();
        } catch (Exception $e) {
            $this->logger->error(
                __METHOD__ . ': Ошибка при добавлении комментария',
                [
                    'type'     => $pageType,
                    'id'       => $pageId,
                    'nickname' => $nickname,
                    'comment'  => $comment,
                    'error'    => $e->getMessage()
                ]
            );

            return new JsonResponse(
                code: 500,
                data: [
                    'status'  => 'error',
                    'message' => 'Внутренняя ошибка'
                ]
            );
        }

        return new SuccessfulJsonResponse(
            data: [
                'status'  => 'success',
                'message' => $this->getRandomSuccessMessage()
            ]
        );
    }

    public function getRandomSuccessMessage(): string {
        $messages = $this->getCommentsProvider()->getSuccessMessages();

        return $messages[array_rand($messages)];
    }

    private function getCommentsProvider(): ICommentsProvider {
        return Container::getInstance()->getService(ICommentsProvider::class);
    }

    /**
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    protected function doPost(
        string $pageType,
        int $pageId,
        string $nickname,
        string $comment,
        ?int $parentId
    ): CommentModel {
        $model = new CommentModel();

        $model->setPageType($pageType)
            ->setPageId($pageId)
            ->setName(strip_tags($nickname))
            ->setComment(strip_tags($comment))
            ->setDate(new DateTime('now'))
            ->setIp($this->request->getIpAddress())
            ->setParentId(
                $parentId
                    ? CommentModel::findByUniqueIdentifier($parentId)?->getId()
                    : null
            );

        if ($this->auth->isAuthenticated()) {
            $model->setStatus(CommentModel::STATUS_APPROVED);
        }

        return $model;
    }
}
