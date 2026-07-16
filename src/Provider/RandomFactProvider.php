<?php

namespace BC\Provider;

use BC\DTO\RandomFactDTO;
use BC\Model\Fact;
use Exception;

class RandomFactProvider implements IRandomFactProvider {
    public function getRandomFact(): RandomFactDTO {
        try {
            $fact = Fact::getQueryBuilder('f')
                        ->where("f.title != ''")
                        ->orderBy('RAND()', 'ASC')
                        ->getFirstResult();

            return new RandomFactDTO(
                (string) ($fact['title'] ?? ''),
                (string) ($fact['content'] ?? '')
            );
        } catch (Exception) {
            return new RandomFactDTO('', '');
        }
    }
}
