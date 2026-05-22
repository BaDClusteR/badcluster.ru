<?php

namespace BC\Api\Endpoint;

use ApiPlatform\Attribute as API;
use ApiPlatform\Exception\BadRequestException;
use BC\Api\DTO\Comment\CommentDTO;
use BC\Api\DTO\Comment\CommentRowDTO;
use BC\Api\DTO\Comment\CommentParentDTO;
use BC\Api\DTO\Comment\GeoIpDTO;
use BC\Api\DTO\ListResponseDTO;
use BC\Api\DTO\SuccessfulResultDTO;
use BC\Api\Exception\NotFoundException;
use BC\Core\Converter\IDateConverter;
use BC\Core\Helper\IGeoIpHelper;
use BC\Exception\UnprocessableEntityException;
use BC\Model\Comment;

class Comments extends AEndpoint {
    public function __construct(
        private readonly IDateConverter $dateConverter,
        private readonly IGeoIpHelper $geoIpHelper,
    ) {
    }

    /**
     * @return ListResponseDTO<CommentRowDTO>
     * @throws BadRequestException
     */
    #[API\Endpoint(path: 'comments', method: 'GET')]
    public function getList(
        #[API\Parameter(source: 'query')]
        string $filter = '',

        #[API\Parameter(source: 'query')]
        string $sortBy = '',

        #[API\Parameter(source: 'query')]
        string $sortDir = '',

        #[API\Parameter(source: 'query')]
        int $page = 1,

        #[API\Parameter(source: 'query')]
        int $perPage = 25
    ): ListResponseDTO {
        $qb = Comment::getQueryBuilder()->orderBy('date', 'DESC');

        $this->addFilter($qb, $filter, ['name', 'email', 'comment']);
        $total = $this->setSortLimitAndGetTotal($qb, $sortBy, $sortDir, $page, $perPage);

        return $this->handleWithException(
            fn () => new ListResponseDTO(
                items: array_map(
                    fn (Comment $comment): CommentRowDTO => $this->buildListResponseItem($comment),
                    $qb->getEntities()
                ),
                total: $total
            )
        );
    }

    private function buildListResponseItem(Comment $comment): CommentRowDTO {
        $pageType = $comment->getPageType();
        $pageId = $comment->getPageId();

        return new CommentRowDTO(
            id: $comment->getId(),
            date: $this->dateConverter->toShortForm($comment->getDate()),
            name: $comment->getName(),
            comment: $this->prepareComment(
                $comment->getComment()
            ),
            status: $comment->getStatus(),
            page: $this->getPageTitle($pageType, $pageId),
            pageLink: $this->getPageLink($pageType, $pageId)
        );
    }

    /**
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'comment', method: 'GET')]
    public function getOne(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id
    ): CommentDTO {
        /** @var Comment|null $tag */
        $comment = $this->handleWithException(
            static fn () => Comment::findByUniqueIdentifier($id)
        );

        if (!$comment) {
            throw new NotFoundException("Коммент #$id не найден.");
        }

        return $this->handleWithException(
            fn (): CommentDTO => $this->convertDetailedModel($comment)
        );
    }

    /**
     * @throws UnprocessableEntityException
     * @throws NotFoundException
     */
    #[API\Endpoint(path: 'comments', method: 'PUT')]
    public function saveComment(
        #[API\Parameter(source: 'path', name: 'identifier')]
        int $id,

        #[API\Parameter(source: 'body', name: 'date')]
        string $date,

        #[API\Parameter(source: 'body', name: 'name')]
        string $name,

        #[API\Parameter(source: 'body', name: 'comment')]
        string $text,

        #[API\Parameter(source: 'body', name: 'status')]
        string $status
    ): SuccessfulResultDTO {
        if (!in_array($status, Comment::getAllowedStatuses(), true)) {
            throw new UnprocessableEntityException(['status' => "Incorrect status: $status"]);
        }

        $comment = $this->handleWithException(
            static fn () => Comment::findByUniqueIdentifier($id)
        );

        if (!$comment) {
            throw new NotFoundException("Comment #$id not found.");
        }

        $this->handleWithException(
            fn () => $comment->setDate($this->dateConverter->toDateTime($date))
                             ->setName($name)
                             ->setComment($text)
                             ->setStatus($status)
                             ->persist()
        );

        return new SuccessfulResultDTO();
    }

    private function convertDetailedModel(Comment $comment): CommentDTO {
        $pageType = $comment->getPageType();
        $pageId = $comment->getPageId();
        $date = $comment->getDate();
        $ipInfo = $this->geoIpHelper->getIpInfo(
            $comment->getIp()
        );

        $parent = $this->handleWithException(
            static fn () => $comment->getParent()
        );

        return new CommentDTO(
            date: $this->dateConverter->toPickerValue($date),
            dateHumanReadable: $this->dateConverter->toFullForm($date, true),
            name: $comment->getName(),
            email: (string) $comment->getEmail(),
            comment: $comment->getComment(),
            page: $this->getPageTitle($pageType, $pageId),
            pageLink: $this->getPageLink($pageType, $pageId),
            geoIp: new GeoIpDTO(
                ip: $comment->getIp(),
                country: $ipInfo?->country,
                countryCode: $ipInfo?->countryCode,
                city: $ipInfo?->city,
                rangeStart: $ipInfo?->ipRangeStart,
                rangeEnd: $ipInfo?->ipRangeEnd,
            ),
            parent: $parent
                ? new CommentParentDTO(
                    id: $parent->getId(),
                    title: sprintf(
                        '%s (%s)',
                        $parent->getName(),
                        $this->dateConverter->toFullForm($parent->getDate(), true)
                    ),
                    text: $this->prepareComment($parent->getComment()),
                    link: "/admin/comments/{$parent->getId()}"
                ) : null,
            status: $comment->getStatus()
        );
    }

    #[API\Endpoint(path: 'comments', method: 'DELETE')]
    public function deleteComments(
        #[API\Parameter(source: 'body', name: 'rows')]
        array $rows
    ): SuccessfulResultDTO {
        $qb = Comment::getQueryBuilder();

        $this->handleWithException(
            static fn () => $qb->delete()
                               ->where($qb->expr()->in('id', $rows))
                               ->execute()
        );

        return new SuccessfulResultDTO();
    }

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
            throw new NotFoundException("Comment #$id not found.");
        }

        $this->handleWithException(
            static fn () => $comment->setStatus($status)->persist()
        );
    }

    protected function prepareComment(string $comment): string {
        return nl2br($comment);
    }

    protected function getPageTitle(string $pageType, int $pageId): string {
        return '';
    }

    protected function getPageLink(string $pageType, int $pageId): string {
        return '';
    }

    protected function getSortableColumns(): array {
        return ['date', 'name'];
    }
}
