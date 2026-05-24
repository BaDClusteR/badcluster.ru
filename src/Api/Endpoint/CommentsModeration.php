<?php

declare(strict_types=1);

namespace BC\Api\Endpoint;

use ApiPlatform\Attribute as API;
use BC\Api\DTO\SuccessfulResultDTO;
use BC\Api\Exception\NotFoundException;
use BC\Model\Comment;

class CommentsModeration extends AEndpoint {
    /**
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'comment_approve', method: 'GET')]
    public function approve(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id
    ): SuccessfulResultDTO {
        $this->setStatus($id, Comment::STATUS_APPROVED);

        return new SuccessfulResultDTO();
    }

    /**
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'comment_reject', method: 'GET')]
    public function reject(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id
    ): SuccessfulResultDTO {
        $this->setStatus($id, Comment::STATUS_DECLINED);

        return new SuccessfulResultDTO();
    }

    /**
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'comment_delete', method: 'GET')]
    public function delete(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id
    ): SuccessfulResultDTO {
        $comment = $this->handleWithException(
            static fn () => Comment::findByUniqueIdentifier($id)
        );

        if (!$comment) {
            throw new NotFoundException("Comment #$id not found.");
        }

        $this->handleWithException(
            static fn () => $comment->remove()
        );

        return new SuccessfulResultDTO();
    }

    /**
     * @throws NotFoundException
     */
    private function setStatus(int $id, string $status): void {
        $comment = $this->handleWithException(
            static fn () => Comment::findByUniqueIdentifier($id)
        );

        if (!$comment) {
            throw new NotFoundException("Коммент #$id не найден.");
        }

        $this->handleWithException(
            static fn () => $comment->setStatus($status)->persist()
        );
    }
}
