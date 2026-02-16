<?php
namespace App\Services;

class GeofenceService
{
    /**
     * Calculate distance between two points using Haversine formula
     * @return float distance in meters
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; // meters

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Check if user is within site radius
     */
    public static function isWithinRadius($userLat, $userLng, $siteLat, $siteLng, $radius): bool
    {
        if (!$siteLat || !$siteLng) return true; // Skip if site coordinates not set
        
        $distance = self::calculateDistance($userLat, $userLng, $siteLat, $siteLng);
        return $distance <= $radius;
    }
}
