<?php

namespace BC\Core\Helper;

use BC\Core\DTO\GeoIpDTO;

interface IGeoIpHelper {
    public function getIpInfo(string|int $ip): ?GeoIpDTO;
}
