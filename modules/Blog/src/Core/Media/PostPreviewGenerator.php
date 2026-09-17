<?php

declare(strict_types=1);

namespace BC\Modules\Blog\Core\Media;

use BC\Core\Exception\ImageException;
use BC\Modules\Blog\Model\Post;
use BC\Modules\Blog\Model\Tag;
use GdImage;

/**
 * Рисует превью поста на GD: обложка фоном, поверх — затемнение, крупный
 * заголовок слева сверху, логотип с адресом сайта слева снизу и тэги справа
 * снизу.
 */
readonly class PostPreviewGenerator implements IPostPreviewGenerator {
    /** 1.91:1 — пропорция, под которую заточены og:image у FB/Telegram/Twitter. */
    public const int WIDTH = 1280;
    public const int HEIGHT = 670;

    private const int PADDING = 64;
    private const int JPEG_QUALITY = 85;

    private const int TITLE_MAX_FONT_SIZE = 84;
    private const int TITLE_MIN_FONT_SIZE = 28;
    private const int TITLE_FONT_SIZE_STEP = 2;
    /** Доля высоты картинки, которую может занять заголовок целиком. */
    private const float TITLE_MAX_HEIGHT_RATIO = .6;
    private const float TITLE_LINE_HEIGHT = 1.15;

    private const int LOGO_SIZE = 56;
    private const int FOOTER_FONT_SIZE = 26;
    private const int FOOTER_GAP = 20;
    private const string SITE_NAME = 'BaD ClusteR';
    private const int SITE_NAME_FONT_SIZE = 34;

    /**
     * Фон без обложки: серый градиент по диагонали, RGB. Тона светлее, чем
     * хочется видеть в итоге: сверху ещё ляжет фейд.
     */
    private const array FALLBACK_BG_FROM = [0xB4, 0xB4, 0xBA];
    private const array FALLBACK_BG_TO = [0x64, 0x64, 0x6C];

    /** Затемнение сверху, посередине и снизу: 0 — прозрачно, 1 — чёрный. */
    private const float FADE_TOP = .7;
    private const float FADE_MIDDLE = .55;
    private const float FADE_BOTTOM = .75;

    private const string FONT_BOLD = '/static/fonts/ibm-plex-sans/ttf/IBMPlexSans-Bold.ttf';
    private const string FONT_REGULAR = '/static/fonts/ibm-plex-sans/ttf/IBMPlexSans-Regular.ttf';
    private const string LOGO = '/icon-192x192.png';

    /**
     * @inheritDoc
     */
    public function generate(Post $post): string {
        $canvas = $this->createCanvas($post);

        $this->drawFade($canvas);
        $this->drawTitle($canvas, $post->getShortTitle() ?: $post->getTitle());
        $tagsLeftBound = $this->drawLogo($canvas);
        $this->drawTags($canvas, $post->getTags(), $tagsLeftBound);

        ob_start();
        imagejpeg($canvas, null, self::JPEG_QUALITY);

        return (string) ob_get_clean();
    }

    /**
     * Холст с обложкой поста, растянутой по принципу background-size: cover.
     * Без обложки — серый градиент, поверх которого ляжет тот же фейд.
     *
     * @throws ImageException
     */
    private function createCanvas(Post $post): GdImage {
        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        $cover = $post->getCover();
        if (!$cover) {
            $this->drawFallbackBackground($canvas);

            return $canvas;
        }

        $source = $this->loadImage($cover->getLocalPath());
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        $scale = max(self::WIDTH / $sourceWidth, self::HEIGHT / $sourceHeight);
        $cropWidth = (int) round(self::WIDTH / $scale);
        $cropHeight = (int) round(self::HEIGHT / $scale);

        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            (int) (($sourceWidth - $cropWidth) / 2),
            (int) (($sourceHeight - $cropHeight) / 2),
            self::WIDTH,
            self::HEIGHT,
            $cropWidth,
            $cropHeight
        );

        return $canvas;
    }

    /**
     * Фон для постов без обложки: диагональный градиент из двух серых, чтобы
     * картинка не выглядела плоской заглушкой. Фейд поверх него ляжет так же,
     * как и на обложку.
     */
    private function drawFallbackBackground(GdImage $canvas): void {
        [$from, $to] = [self::FALLBACK_BG_FROM, self::FALLBACK_BG_TO];

        // Цвет зависит от x + y, так что каждая диагональ x + y = const —
        // одна линия одного цвета; за края GD обрежет сам
        $length = self::WIDTH + self::HEIGHT;
        for ($diagonal = 0; $diagonal < $length; $diagonal++) {
            $ratio = $diagonal / $length;

            imageline(
                $canvas,
                $diagonal,
                0,
                0,
                $diagonal,
                imagecolorallocate(
                    $canvas,
                    (int) round($from[0] + ($to[0] - $from[0]) * $ratio),
                    (int) round($from[1] + ($to[1] - $from[1]) * $ratio),
                    (int) round($from[2] + ($to[2] - $from[2]) * $ratio)
                )
            );
        }
    }

    /**
     * Вертикальный градиент: темнее у краёв, где лежит текст, светлее в
     * середине, чтобы обложку всё-таки было видно.
     */
    private function drawFade(GdImage $canvas): void {
        imagealphablending($canvas, true);

        $middle = self::HEIGHT / 2;
        for ($y = 0; $y < self::HEIGHT; $y++) {
            $opacity = $y < $middle
                ? self::FADE_TOP + (self::FADE_MIDDLE - self::FADE_TOP) * ($y / $middle)
                : self::FADE_MIDDLE + (self::FADE_BOTTOM - self::FADE_MIDDLE) * (($y - $middle) / $middle);

            imageline(
                $canvas,
                0,
                $y,
                self::WIDTH,
                $y,
                imagecolorallocatealpha($canvas, 0, 0, 0, (int) round(127 * (1 - $opacity)))
            );
        }
    }

    /**
     * Заголовок слева сверху. Размер шрифта подбираем сверху вниз: самый
     * крупный, при котором все строки влезают в TITLE_MAX_HEIGHT_RATIO высоты
     * картинки и ни одно слово не шире рабочей области.
     */
    private function drawTitle(GdImage $canvas, string $title): void {
        $font = PROJECT_ROOT . self::FONT_BOLD;
        $maxWidth = self::WIDTH - 2 * self::PADDING;
        $maxHeight = (int) (self::HEIGHT * self::TITLE_MAX_HEIGHT_RATIO);

        $size = self::TITLE_MAX_FONT_SIZE;
        while (true) {
            $lines = $this->wrap($title, $font, $size, $maxWidth);
            $lineHeight = (int) round($this->getLineHeight($font, $size) * self::TITLE_LINE_HEIGHT);

            $isTooWide = array_any(
                $lines,
                fn (string $line): bool => $this->getTextWidth($font, $size, $line) > $maxWidth
            );

            if (
                (!$isTooWide && count($lines) * $lineHeight <= $maxHeight)
                || $size <= self::TITLE_MIN_FONT_SIZE
            ) {
                break;
            }

            $size -= self::TITLE_FONT_SIZE_STEP;
        }

        $y = self::PADDING + $this->getLineHeight($font, $size);
        foreach ($lines as $line) {
            $this->drawText($canvas, $font, $size, self::PADDING, $y, $line);
            $y += $lineHeight;
        }
    }

    /**
     * Логотип и адрес сайта слева снизу.
     *
     * @return int Правая граница нарисованного — дальше начинается место для тэгов.
     */
    private function drawLogo(GdImage $canvas): int {
        $logo = $this->loadImage(PROJECT_ROOT . self::LOGO);
        $this->lightenLogo($logo);

        $x = self::PADDING;
        $y = self::HEIGHT - self::PADDING - self::LOGO_SIZE;

        imagecopyresampled(
            $canvas,
            $logo,
            $x,
            $y,
            0,
            0,
            self::LOGO_SIZE,
            self::LOGO_SIZE,
            imagesx($logo),
            imagesy($logo)
        );

        $font = PROJECT_ROOT . self::FONT_BOLD;
        $x += self::LOGO_SIZE + self::FOOTER_GAP;
        $this->drawText(
            $canvas,
            $font,
            self::SITE_NAME_FONT_SIZE,
            $x,
            $this->getFooterBaseline(),
            self::SITE_NAME
        );

        return $x + $this->getTextWidth($font, self::SITE_NAME_FONT_SIZE, self::SITE_NAME);
    }

    /**
     * Серые квадраты иконки — это чёрный с прозрачностью 20%, на светлом фоне
     * они серые, а на тёмной обложке пропадают. Перекрашиваем их в белый и
     * делаем почти непрозрачными; форму (в том числе сглаженные края) задаёт
     * исходная альфа, так что она просто масштабируется. Зелёный квадрат не
     * трогаем.
     */
    private function lightenLogo(GdImage $logo): void {
        $width = imagesx($logo);
        $height = imagesy($logo);

        imagealphablending($logo, false);
        imagesavealpha($logo, true);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $color = imagecolorsforindex($logo, imagecolorat($logo, $x, $y));
                $isDark = max($color['red'], $color['green'], $color['blue']) < 0x80;

                if (!$isDark || $color['alpha'] === 127) {
                    continue;
                }

                $opacity = min(1, (127 - $color['alpha']) / 127 * 4);
                imagesetpixel(
                    $logo,
                    $x,
                    $y,
                    imagecolorallocatealpha($logo, 0xFF, 0xFF, 0xFF, (int) round(127 * (1 - $opacity)))
                );
            }
        }
    }

    /**
     * Тэги справа снизу, по правому краю. Если все не влезают между адресом
     * сайта и правым краем — отбрасываем хвост.
     *
     * @param Tag[] $tags
     */
    private function drawTags(GdImage $canvas, array $tags, int $leftBound): void {
        $font = PROJECT_ROOT . self::FONT_REGULAR;
        $maxWidth = self::WIDTH - self::PADDING - $leftBound - 2 * self::FOOTER_GAP;

        $labels = array_map(
            static fn (Tag $tag): string => '#' . $tag->getTitle(),
            $tags
        );

        while ($labels) {
            $text = implode('  ', $labels);
            $width = $this->getTextWidth($font, self::FOOTER_FONT_SIZE, $text);

            if ($width <= $maxWidth) {
                $this->drawText(
                    $canvas,
                    $font,
                    self::FOOTER_FONT_SIZE,
                    self::WIDTH - self::PADDING - $width,
                    $this->getFooterBaseline(),
                    $text
                );

                return;
            }

            array_pop($labels);
        }
    }

    /**
     * Общая базовая линия футера: прописные буквы названия сайта стоят по
     * центру логотипа, тэги — на той же линии, чтобы разный кегль не
     * прыгал по высоте.
     */
    private function getFooterBaseline(): int {
        $capHeight = $this->getTextHeight(PROJECT_ROOT . self::FONT_BOLD, self::SITE_NAME_FONT_SIZE, 'B');

        return (int) round(self::HEIGHT - self::PADDING - self::LOGO_SIZE / 2 + $capHeight / 2);
    }

    /**
     * Раскладывает текст по строкам не шире $maxWidth, перенося целые слова.
     * Слово, которое не влезает и само по себе, остаётся строкой — с этим
     * разбирается подбор размера шрифта.
     *
     * @return string[]
     */
    private function wrap(string $text, string $font, int $size, int $maxWidth): array {
        $lines = [];
        $current = '';

        foreach (preg_split('/\s+/u', trim($text)) ?: [] as $word) {
            $candidate = $current === ''
                ? $word
                : "$current $word";

            if ($current !== '' && $this->getTextWidth($font, $size, $candidate) > $maxWidth) {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    /**
     * Белый текст с лёгкой тенью, чтобы читался и на светлых участках обложки.
     */
    private function drawText(GdImage $canvas, string $font, int $size, int $x, int $y, string $text): void {
        $box = imagettfbbox($size, 0, $font, $text);
        // bbox считается от базовой линии, и левый край может выступать —
        // сдвигаем, чтобы текст начинался ровно в $x
        $x -= $box[0];

        $shadow = imagecolorallocatealpha($canvas, 0, 0, 0, 60);
        $shadowOffset = max(1, (int) round($size / 30));
        imagettftext($canvas, $size, 0, $x + $shadowOffset, $y + $shadowOffset, $shadow, $font, $text);

        imagettftext($canvas, $size, 0, $x, $y, imagecolorallocate($canvas, 0xFF, 0xFF, 0xFF), $font, $text);
    }

    private function getTextWidth(string $font, int $size, string $text): int {
        $box = imagettfbbox($size, 0, $font, $text);

        return $box[2] - $box[0];
    }

    private function getTextHeight(string $font, int $size, string $text): int {
        $box = imagettfbbox($size, 0, $font, $text);

        return $box[1] - $box[7];
    }

    /**
     * Высота строки от верха самых высоких глифов до низа самых низких —
     * по опорной строке, а не по конкретному тексту, чтобы межстрочник не
     * плясал от того, есть ли в строке буквы с хвостами.
     */
    private function getLineHeight(string $font, int $size): int {
        return $this->getTextHeight($font, $size, 'ЙÉg');
    }

    /**
     * @throws ImageException
     */
    private function loadImage(string $path): GdImage {
        $content = @file_get_contents($path);
        $image = $content !== false
            ? @imagecreatefromstring($content)
            : false;

        if (!$image) {
            throw new ImageException("Cannot load image $path");
        }

        return $image;
    }
}
