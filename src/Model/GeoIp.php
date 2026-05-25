<?php

declare(strict_types=1);

namespace BC\Model;

use Runway\DataStorage\Attribute as DS;
use Runway\Model\AEntity;

/**
 * @method int getId()
 * @method self setId(int $id)
 * @method int getIpStart()
 * @method self setIpStart(int $ipStart)
 * @method int getIpEnd()
 * @method self setIpEnd(int $ipEnd)
 * @method string getCountryCode()
 * @method self setCountryCode(string $countryCode)
 * @method string getCountry()
 * @method self setCountry(string $country)
 * @method string getRegion()
 * @method self setRegion(string $region)
 * @method string getCity()
 * @method self setCity(string $city)
 * @method float getLatitude()
 * @method self setLatitude(float $latitude)
 * @method float getLongitude()
 * @method self setLongitude(float $longitude)
 */
#[DS\Table('geoip')]
class GeoIp extends AEntity {
    #[DS\Id]
    protected int $id;

    #[DS\Column]
    protected int $ipStart;

    #[DS\Column]
    protected int $ipEnd;

    #[DS\Column]
    protected string $countryCode;

    #[DS\Column]
    protected string $country;

    #[DS\Column]
    protected string $region;

    #[DS\Column]
    protected string $city;

    #[DS\Column]
    protected float $latitude;

    #[DS\Column]
    protected float $longitude;
}
