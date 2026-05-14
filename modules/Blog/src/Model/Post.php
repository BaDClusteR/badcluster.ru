<?php

namespace BC\Modules\Blog\Model;

use BC\Core\Trait\WebsiteSettingsTrait;
use BC\Model\Media;
use DateTime;
use Runway\DataStorage\Attribute as DS;
use Runway\DataStorage\Exception\DBException;
use Runway\DataStorage\QueryBuilder\Exception\QueryBuilderException;
use Runway\Exception\Exception;
use Runway\Model\AEntity;
use Runway\Model\Exception\ModelException;

/**
 * @method int getId()
 * @method self setId(int $id)
 * @method string getTitle()
 * @method self setTitle(string $title)
 * @method string getShortTitle()
 * @method self setShortTitle(string $shortTitle)
 * @method string getAnnotation()
 * @method self setAnnotation(string $annotation)
 * @method DateTime getCreatedDate()
 * @method self setCreatedDate(DateTime $createdDate)
 * @method DateTime getPublishDate()
 * @method self setPublishDate(DateTime $publishDate)
 * @method DateTime|null getUpdateDate()
 * @method self setUpdateDate(DateTime|null $updateDate)
 * @method array getContent
 * @method self setContent(array $content)
 * @method bool getPublished()
 * @method self setPublished(bool $published)
 * @method string getSlug()
 * @method self setSlug(string $slug)
 * @method string getMetaDescription()
 * @method self setMetaDescription(string $metaDescription)
 * @method Media|null getCover
 * @method self setCover(?Media $cover)
 * @method Tag[] getPostTags
 */

#[DS\Table("posts")]
class Post extends AEntity
{
    use WebsiteSettingsTrait;

    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected string $title = '';

    #[DS\Column]
    protected string $shortTitle = '';

    #[DS\Column]
    protected string $annotation = '';

    #[DS\Column]
    protected DateTime $createdDate;

    #[DS\Column]
    protected DateTime $publishDate;

    #[DS\Column]
    protected ?DateTime $updateDate = null;

    #[DS\Column]
    protected array $content;

    #[DS\Column]
    protected bool $published = false;

    #[DS\Column]
    protected string $slug = '';

    #[DS\Column]
    protected string $metaDescription = '';

    #[DS\Column]
    protected ?Media $cover = null;

    #[DS\Reference(refModel: PostTag::class, refProp: "post")]
    protected ?array $postTags = null;

    /**
     * @return Tag[]
     */
    public function getTags(): array {
        return array_map(
            static fn(PostTag $pt): Tag => $pt->getTag(),
            $this->getPostTags()
        );
    }

    /**
     * @param Tag[] $tags
     *
     * @throws DBException
     * @throws QueryBuilderException
     * @throws ModelException
     */
    public function syncTags(array $tags): static {
        $tagIds = array_map(
            static fn(Tag $t): int => $t->getId(),
            $tags
        );

        $qb = PostTag::getQueryBuilder();
        $qb->delete()
            ->where('post_id = :postId')
            ->setVariable('postId', $this->id);

        if (!empty($tagIds)) {
            $qb->andWhere('tag_id NOT IN (:tagIds)')
               ->setVariable('tagIds', implode(", ", $tagIds));
        }

        $qb->execute();

        $qb = PostTag::getQueryBuilder()
            ->where('post_id = :postId')
            ->setVariable('postId', $this->id);

        if (!empty($tagIds)) {
            $qb->andWhere(
                $qb->expr()->in('tag_id', $tagIds)
            );
        }

        $existingTagIds = array_map(
            static fn(PostTag $t): int => $t->getTag()->getId(),
            $qb->getEntities()
        );

        foreach ($tagIds as $tagId) {
            if (!in_array($tagId, $existingTagIds, true)) {
                $tag = Tag::findByUniqueIdentifier($tagId);
                if ($tag) {
                    $postTag = new PostTag()
                        ->setTag($tag)
                        ->setPost($this);
                    $postTag->persist();
                }
            }
        }

        $this->postTags = null;

        return $this;
    }

    public function getUrl(): string {
        return sprintf(
            "%s/blog/%s",
            $this->getWebsiteSettings()->getWebRoot(),
            $this->getSlug()
        );
    }
}
