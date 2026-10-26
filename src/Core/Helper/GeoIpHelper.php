<?php

declare(strict_types=1);

namespace BC\Core\Helper;

use BC\Core\DTO\GeoIpDTO;
use BC\Model\GeoIp;
use Runway\Exception\Exception;
use Runway\Logger\ILogger;

readonly class GeoIpHelper implements IGeoIpHelper {
    public function __construct(
        private ILogger $logger
    ) {
    }

    public function getIpInfo(int|string $ip): ?GeoIpDTO {
        $ipAsLong = is_string($ip)
            ? ip2long($ip)
            : $ip;

        // ip2long() gives false for an invalid address
        if (!$ipAsLong) {
            return null;
        }

        try {
            $info = GeoIp::getQueryBuilder()
                         ->where('ip_start <= :ip')
                         ->andWhere('ip_end >= :ip')
                         ->setVariable('ip', $ipAsLong)
                         ->getFirstEntity();
        } catch (Exception $e) {
            $this->logger->warning(
                sprintf('[%s] error while getting an IP info: %s', __METHOD__, $e->getMessage()),
                [
                    'ip'       => $ip,
                    'ipAsLong' => $ipAsLong,
                ]
            );

            return null;
        }

        // An address outside the known ranges
        if (!($info instanceof GeoIp)) {
            return null;
        }

        return new GeoIpDTO(
            ip: long2ip($ipAsLong),
            ipRangeStart: long2ip($info->getIpStart()),
            ipRangeEnd: long2ip($info->getIpEnd()),
            countryCode: $info->getCountryCode(),
            country: $info->getCountry(),
            region: $info->getRegion(),
            city: $info->getCity(),
            latitude: (float) $info->getLatitude(),
            longitude: (float) $info->getLongitude()
        );
    }
}
