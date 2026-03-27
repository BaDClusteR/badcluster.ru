<?php

namespace BC\Core\Asset\Minifier;

interface IMinifier
{
    /**
     * @param string $type 'js' or 'css'.
     */
    public function minify(string $content, string $type): string;
}
