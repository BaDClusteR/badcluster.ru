<?php

namespace BC\Modules\Books\Widget;

use BC\Core\Asset\DTO\AssetDTO;
use BC\Core\Trait\DateConverterTrait;
use BC\Core\Trait\FormatterTrait;
use BC\Modules\Books\Model\Book as BookModel;
use BC\Modules\Books\Model\BookFormat;
use BC\Modules\Books\Model\Chapter;
use BC\Widget\AWidget;
use BC\Widget\IAssetProvider;
use BC\Widget\Page\APage;
use Runway\Exception\Exception;

class Book extends AWidget implements IAssetProvider {
    use DateConverterTrait;
    use FormatterTrait;

    protected function getTemplatePath(): string {
        return 'modules/Books/book.phtml';
    }

    protected function getBook(): BookModel {
        return $this->context['book'];
    }

    protected function getPage(): APage {
        return $this->context['page'];
    }

    protected function getBookAnnotation(): string {
        $rawAnnotation = str_replace(
            "\n\n",
            "\n",
            $this->getBook()->getAnnotation()
        );

        return '<p>' . implode('</p><p>', explode("\n", $rawAnnotation)) . '</p>';
    }

    /**
     * @return BookFormat[]
     */
    protected function getBookFormats(): array {
        try {
            return array_values(
                array_filter(
                    $this->getBook()->getFormats(),
                    static fn (BookFormat $format): bool => $format->getAllowed()
                )
            );
        } catch (Exception) {
            return [];
        }
    }

    /**
     * @return array<string, Chapter[]>
     */
    protected function getBookChapters(): array {
        try {
            $allChapters = array_values(
                array_filter(
                    $this->getBook()->getChapters(),
                    static fn (Chapter $chapter): bool => $chapter->getPublished()
                )
            );

            $result = [
                '' => []
            ];
            $currentGroup = '';

            /** @var Chapter|null $chapter */
            foreach ($allChapters as $chapter) {
                $group = $chapter->getPart();

                if (!$group) {
                    $result[$currentGroup][] = $chapter;
                } else {
                    $result[$group] = [$chapter];
                    $currentGroup = $group;
                }
            }

            return $result;
        } catch (Exception) {
            return [];
        }
    }

    public static function getAssets(): array {
        return [
            new AssetDTO(
                'file',
                'css/common/file.css'
            ),
        ];
    }
}
