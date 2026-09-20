<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Widget\Page;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Core\Trait\WebsiteSettingsTrait;
use BC\Modules\Blog\Model\Note as NoteModel;
use BC\Modules\Blog\Widget\Note as NoteWidget;
use BC\Widget\AWidget;
use BC\Widget\DTO\BackLinkDTO;
use BC\Widget\DTO\MetaTagDTO;
use BC\Widget\Page\APageWithBlocks;
use Runway\Exception\RuntimeException;

class NotePage extends APageWithBlocks {
    use WebsiteSettingsTrait;

    private ?NoteModel $note = null;

    /**
     * @inheritDoc
     */
    public static function getAssets(): array {
        return [
            new AssetDTO(
                'post',
                'css/modules/Blog/post.css'
            ),
        ];
    }

    protected function applyContext(array $context): void {
        parent::applyContext($context);

        if (!$this->note && !(($context['note'] ?? null) instanceof NoteModel)) {
            throw new RuntimeException(__METHOD__ . ': note is not set or not an instance of ' . NoteModel::class);
        }

        $this->note ??= $context['note'];
    }

    public function getHeader(): string {
        return '';
    }

    public function getDescription(): array {
        return [];
    }

    public function getMainWidget(): AWidget {
        return new NoteWidget([
            'note' => $this->note,
            'page' => $this,
        ]);
    }

    public function getBackLink(): ?BackLinkDTO {
        return new BackLinkDTO(
            url: $this->getWebsiteSettings()->getWebRoot() . '/notes',
            text: 'Назад к заметкам'
        );
    }

    public function getCssBundles(): array {
        $list = parent::getCssBundles();
        $list[] = 'post';

        return $list;
    }

    public function getTitle(): string {
        return $this->note->getTitle() . ' :: ' . $this->getTitleBase();
    }

    public function getMetaTitle(): string {
        return $this->note->getTitle() . ' — ' . parent::getMetaTitle();
    }

    public function getMetaDescription(): string {
        return $this->note->getMetaDescription();
    }

    public function getOpenGraphType(): string {
        return 'article';
    }

    public function getMetaTags(): array {
        return [
            ...parent::getMetaTags(),
            new MetaTagDTO(
                name: 'og:article:author',
                content: 'BaD ClusteR'
            ),
            new MetaTagDTO(
                name: 'og:article:published_time',
                content: $this->note->getPublishDate()->format(DATE_ATOM),
            ),
        ];
    }

    public function getCanonicalUrl(): string {
        return $this->note
            ? $this->getWebRoot() . '/notes/' . $this->note->getSlug()
            : '';
    }
}
