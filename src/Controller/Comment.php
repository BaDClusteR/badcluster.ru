<?php

declare(strict_types=1);

namespace BC\Controller;

use BC\Core\Auth\IAuth;
use BC\Core\Comment\ICommentSpamFilter;
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
    /**
     * Имя поля-ловушки в форме комментария. Настоящей почты в форме нет, поэтому
     * заполненный email — верный признак бота. Разметку рисует шаблон
     * templates/common/comments.phtml, имя берётся отсюда же.
     */
    public const string HONEYPOT_FIELD = 'email';

    public function __construct(
        private IRequest $request,
        private ILogger $logger,
        private IAuth $auth
    ) {
    }

    public function run(): Response {
        $pageType = $this->request->getPostParameter('type')->asString();
        $pageId = $this->request->getPostParameter('id')->asInt();
        $parentId = $this->request->getPostParameter('parentId')->asInt() ?: null;

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

        // Спамеру отвечаем ровно тем же, чем и живому человеку, но комментарий
        // никуда не сохраняем и в Slack о нём не сообщаем — чтобы бот не понял,
        // что его отфильтровали, и не начал подбирать обход
        if ($rejectReason = $this->getRejectReason($nickname, $comment)) {
            // Уровень warning, а не info: на проде LOG_LEVEL=2, и info в лог не попадёт,
            // а запись нужна именно там — по ней проверяют, не отсеяло ли живого человека
            $this->logger->warning(
                __METHOD__ . ': Комментарий отброшен как спам',
                [
                    'reason'   => $rejectReason,
                    'type'     => $pageType,
                    'id'       => $pageId,
                    'nickname' => $nickname,
                    'comment'  => $comment,
                    'ip'       => $this->request->getIpAddress()
                ]
            );

            return new SuccessfulJsonResponse(
                data: [
                    'status'  => 'success',
                    'message' => $this->getRandomSuccessMessage()
                ]
            );
        }

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

    private function getSpamFilter(): ICommentSpamFilter {
        return Container::getInstance()->getService(ICommentSpamFilter::class);
    }

    /**
     * @return string|null Причина отбраковки для лога или null, если комментарий похож на живой
     */
    private function getRejectReason(string $nickname, string $comment): ?string {
        // Ханипот: поле спрятано стилями, живой человек его не увидит и не заполнит,
        // а бот, заполняющий все поля формы подряд (особенно с именем email), — заполнит
        if (trim($this->request->getPostParameter(self::HONEYPOT_FIELD)->asString()) !== '') {
            return 'honeypot';
        }

        if ($this->getSpamFilter()->isSpam($nickname, $comment)) {
            return 'blacklist';
        }

        return null;
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
