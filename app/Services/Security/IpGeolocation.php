<?php

namespace App\Services\Security;

use GeoIp2\Database\Reader;
use Throwable;

class IpGeolocation
{
    public function lookup(string $ipAddress): array
    {
        $details = [];
        $cityPath = config('progressive_rate_limit.geoip.city_database');
        $asnPath = config('progressive_rate_limit.geoip.asn_database');

        if (is_string($cityPath) && is_file($cityPath)) {
            try {
                $reader = new Reader($cityPath);
                $record = $reader->city($ipAddress);
                $details = [
                    'country_code' => $record->country->isoCode,
                    'region' => $record->mostSpecificSubdivision->name,
                    'city' => $record->city->name,
                ];
                $reader->close();
            } catch (Throwable) {
                $details = [];
            }
        }

        if (is_string($asnPath) && is_file($asnPath)) {
            try {
                $reader = new Reader($asnPath);
                $record = $reader->asn($ipAddress);
                $details['asn'] = $record->autonomousSystemNumber;
                $details['isp'] = $record->autonomousSystemOrganization;
                $reader->close();
            } catch (Throwable) {
                $details['asn'] = null;
                $details['isp'] = null;
            }
        }

        return array_filter($details, static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
