<?php

namespace BC\Api\DTO\Comment;

readonly class GeoIpDTO {
    public function __construct(
        public string $ip,
        public ?string $country,
        public ?string $countryCode,
        public ?string $city,
        public ?string $rangeStart,
        public ?string $rangeEnd,
    ) {
    }
}
