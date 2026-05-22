<?php

namespace BC\Core\Action\Comments;

use BC\Core\Action\DTO\GetCommentsRequest;
use BC\Core\Action\DTO\GetCommentsResponse;
use BC\Core\Auth\IAuth;
use BC\Core\DTO\CommentDTO;
use BC\Core\Trait\LoggerTrait;
use BC\Model\Comment;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;

class GetCommentsAction implements IGetCommentsAction {
    use LoggerTrait;

    public function __construct(
        private readonly IAuth $auth
    ) {
    }

    /**
     * @throws DBException
     * @throws QueryBuilderException
     */
    public function run(GetCommentsRequest $request): GetCommentsResponse {
        $statuses = ['A'];

        if (
            $request->includePending
            || (
                $request->includePending === null
                && $this->auth->isAuthenticated()
            )
        ) {
            $statuses[] = 'M';
        }

        if (
            $request->includeDeclined
            || (
                $request->includeDeclined === null
                && $this->auth->isAuthenticated()
            )
        ) {
            $statuses[] = 'D';
        }

        $qb = Comment::getQueryBuilder();

        $qb->where(
            $qb->expr()->isNull('parent_id')
        )->andWhere(
            $qb->expr()->in('status', $statuses)
        )->orderBy('date', 'DESC');

        if ($request->pageType) {
            $qb->andWhere('page_type = :pageType')
                ->setVariable('pageType', $request->pageType);
        }

        if ($request->pageId) {
            $qb->andWhere('page_id = :pageId')
                ->setVariable('pageId', $request->pageId);
        }

        $comments = array_map(
            fn (Comment $comment) => $this->buildComment($comment, $statuses),
            $qb->getEntities()
        );

        return new GetCommentsResponse($comments);
    }

    /**
     * @param string[] $allowedStatuses
     *
     * @throws DBException
     * @throws QueryBuilderException
     */
    private function buildComment(Comment $comment, array $allowedStatuses): CommentDTO {
        return new CommentDTO(
            id: $comment->getId(),
            date: $comment->getDate(),
            name: $comment->getName(),
            email: $comment->getEmail(),
            comment: $comment->getComment(),
            ip: $comment->getIp(),
            isApproved: $comment->isApproved(),
            isDeclined: $comment->isDeclined(),
            children: array_map(
                fn (Comment $comment): CommentDTO => $this->buildComment($comment, $allowedStatuses),
                array_values(
                    array_filter(
                        $comment->getChildren(),
                        static fn (Comment $comment): bool => in_array($comment->getStatus(), $allowedStatuses, true)
                    )
                )
            )
        );
    }
}
