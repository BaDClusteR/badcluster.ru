<?php

namespace BC\Core\Asset\Minifier;

use MatthiasMullie\Minify\CSS as CssMinifier;
use MatthiasMullie\Minify\JS as JsMinifier;

readonly class PhpMinifier implements IMinifier
{
    public function minify(string $content, string $type): string
    {
        return match ($type) {
            'js' => new JsMinifier($content)->minify(),
            'css' => new CssMinifier($content)->minify(),
            default => $content,
        };
    }
}
