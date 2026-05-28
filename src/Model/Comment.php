<?php

declare(strict_types=1);

namespace BC\Model;

use DateTime;
use Runway\DataStorage\Attribute as DS;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Model\AEntity;
use Runway\Model\Exception\ModelException;

/**
 * @generated-model-helpers
 * @method int getId()
 * @method self setId(int $id)
 * @method int|null getParentId()
 * @method self setParentId(int|null $parentId)
 * @method \DateTime getDate()
 * @method self setDate(\DateTime $date)
 * @method string getName()
 * @method self setName(string $name)
 * @method string|null getEmail()
 * @method self setEmail(string|null $email)
 * @method string getComment()
 * @method self setComment(string $comment)
 * @method string getIp()
 * @method self setIp(string $ip)
 * @method string getPageType()
 * @method self setPageType(string $pageType)
 * @method int getPageId()
 * @method self setPageId(int $pageId)
 * @method string getStatus()
 * @method self setStatus(string $status)
 */
#[DS\Table('comments')]
class Comment extends AEntity {
    public const string STATUS_DECLINED = 'D';
    public const string STATUS_APPROVED = 'A';
    public const string STATUS_ON_MODERATION = 'M';

    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected ?int $parentId;

    #[DS\Column]
    protected DateTime $date;

    #[DS\Column]
    protected string $name;

    #[DS\Column]
    protected ?string $email;

    #[DS\Column]
    protected string $comment;

    #[DS\Column]
    protected string $ip;

    #[DS\Column]
    protected string $pageType;

    #[DS\Column]
    protected int $pageId;

    #[DS\Column]
    protected string $status = self::STATUS_ON_MODERATION;

    /**
     * @throws ModelException
     * @throws DBException
     * @throws QueryBuilderException
     */
    public function getParent(): ?self {
        return $this->parentId
            ? self::findByUniqueIdentifier($this->parentId)
            : null;
    }

    /**
     * @return self[]
     *
     * @throws DBException
     * @throws QueryBuilderException
     */
    public function getChildren(): array {
        return self::getQueryBuilder()->where('parent_id = :id')
                   ->setVariable('id', $this->id)
                   ->getEntities();
    }

    /**
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    public function approve(): void {
        $this->setStatus(self::STATUS_APPROVED);

        $this->persist();
    }

    /**
     * @throws DBException
     * @throws ModelException
     * @throws QueryBuilderException
     */
    public function decline(): void {
        $this->setStatus(self::STATUS_DECLINED);

        $this->persist();
    }

    public function isApproved(): bool {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isDeclined(): bool {
        return $this->status === self::STATUS_DECLINED;
    }

    public function isPending(): bool {
        return $this->status === self::STATUS_ON_MODERATION;
    }

    /**
     * @return string[]
     */
    public static function getAllowedStatuses(): array {
        return [self::STATUS_APPROVED, self::STATUS_DECLINED, self::STATUS_ON_MODERATION];
    }
}
