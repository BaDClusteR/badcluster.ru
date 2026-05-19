<?php

namespace BC\Core\Action\Comments;

use BC\Core\Action\DTO\GetCommentsRequest;
use BC\Core\Action\DTO\GetCommentsResponse;
use BC\Core\DTO\CommentDTO;
use BC\Core\Trait\LoggerTrait;
use BC\Model\Comment;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;

class GetCommentsAction implements IGetCommentsAction {
    use LoggerTrait;

    /**
     * @throws DBException
     * @throws QueryBuilderException
     */
    public function run(GetCommentsRequest $request): GetCommentsResponse {
        $statuses = ['A'];

        if ($request->includeWaitingForApproval) {
            $statuses[] = 'M';
        }

        if ($request->includeDeclined) {
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
            fn (Comment $comment) => $this->buildComment($comment),
            $qb->getEntities()
        );

        return new GetCommentsResponse($comments);
    }

    /**
     * @throws DBException
     * @throws QueryBuilderException
     */
    private function buildComment(Comment $comment): CommentDTO {
        return new CommentDTO(
            date: $comment->getDate(),
            name: $comment->getName(),
            email: $comment->getEmail(),
            comment: $comment->getComment(),
            ip: $comment->getIp(),
            isApproved: $comment->isApproved(),
            isDeclined: $comment->isDeclined(),
            children: array_map(
                fn (Comment $comment): CommentDTO => $this->buildComment($comment),
                $comment->getChildren()
            )
        );
    }
}
