<?php

namespace BC\Modules\Books\Format;

class Fb2 implements IBookFormat {
    public function getType(): string {
        return 'fb2';
    }
}
