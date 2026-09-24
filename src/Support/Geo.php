<?php
/**
 * Calculs géographiques purs.
 */

namespace Geofolio\Support;

if (!defined('ABSPATH')) {
    exit;
}

final class Geo {

    /** Rayon moyen de la Terre, en km. */
    const EARTH_RADIUS_KM = 6371;

    /**
     * Distance entre deux points (formule de Haversine), en km, au dixième.
     *
     * @param float $lat1
     * @param float $lng1
     * @param float $lat2
     * @param float $lng2
     * @return float
     */
    public static function distance_km($lat1, $lng1, $lat2, $lng2) {
        $dlat = deg2rad($lat2 - $lat1);
        $dlng = deg2rad($lng2 - $lng1);

        $a = sin($dlat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dlng / 2) ** 2;

        return round(self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);
    }
}
