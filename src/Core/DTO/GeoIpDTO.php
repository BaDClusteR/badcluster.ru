<?php

namespace BC\Core\DTO;

readonly class GeoIpDTO {
    public function __construct(
        public string $ip,
        public string $ipRangeStart,
        public string $ipRangeEnd,
        public string $countryCode,
        public string $country,
        public string $region,
        public string $city,
        public float $latitude,
        public float $longitude
    ) {
    }
}
