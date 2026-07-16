<?php

namespace BC\Provider;

use BC\DTO\RandomFactDTO;

interface IRandomFactProvider {
    public function getRandomFact(): RandomFactDTO;
}
