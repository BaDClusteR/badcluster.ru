<?php

namespace BC\Modules\Books\Format;

class Txt implements IBookFormat {
    public function getType(): string {
        return 'txt';
    }
}
