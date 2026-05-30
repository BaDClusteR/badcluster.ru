<?php

declare(strict_types=1);

namespace BC\Api\DataBuilder\Comment;

use BC\Api\DTO\Comment\CommentDTO;
use BC\Api\DTO\Comment\CommentParentDTO;
use BC\Api\DTO\Comment\CommentRowDTO;
use BC\Api\DTO\Comment\GeoIpDTO;
use BC\Core\Converter\IDateConverter;
use BC\Core\Helper\IGeoIpHelper;
use BC\Model\Comment;
use BC\Provider\Admin\IPageProvider;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Model\Exception\ModelException;

readonly class CommentDataBuilder implements ICommentDataBuilder {
    public function __construct(
        private IDateConverter $dateConverter,
        private IGeoIpHelper $geoIpHelper,
        private IPageProvider $pageProvider
    ) {
    }

    public function buildRow(Comment $comment): CommentRowDTO {
        $pageType = $comment->getPageType();
        $pageId = $comment->getPageId();
        $page = $this->pageProvider->getPage($pageType, $pageId);

        return new CommentRowDTO(
            id: $comment->getId(),
            date: $this->dateConverter->toShortForm($comment->getDate()),
            name: $comment->getName(),
            comment: $this->prepareComment(
                $comment->getComment()
            ),
            status: $comment->getStatus(),
            page: $page->title,
            pageLink: $page->url
        );
    }

    /**
     * @throws ModelException
     * @throws DBException
     * @throws QueryBuilderException
     */
    public function buildEntity(Comment $comment): CommentDTO {
        $pageType = $comment->getPageType();
        $pageId = $comment->getPageId();
        $date = $comment->getDate();
        $ipInfo = $this->geoIpHelper->getIpInfo(
            $comment->getIp()
        );

        $parent = $comment->getParent();
        $page = $this->pageProvider->getPage($pageType, $pageId);

        return new CommentDTO(
            date: $this->dateConverter->toPickerValue($date),
            dateHumanReadable: $this->dateConverter->toFullForm($date, true),
            name: $comment->getName(),
            email: (string) $comment->getEmail(),
            comment: $comment->getComment(),
            page: $page->title,
            pageLink: $page->url,
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

    protected function prepareComment(string $comment): string {
        return nl2br(
            str_replace("\n\n", "\n", $comment)
        );
    }
}
