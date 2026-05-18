<?php

namespace App\Support;

final class AmenityRequestType
{
    public const BOTTLED_WATER = 'amenity:bottled_water';

    public const QUICK_SNACK = 'amenity:quick_snack';

    public const EXTRA_TOWELS = 'amenity:extra_towels';

    public const TOILET_PAPER = 'amenity:toilet_paper';

    /**
     * @return list<string>
     */
    public static function restaurantCodes(): array
    {
        return [
            self::BOTTLED_WATER,
            self::QUICK_SNACK,
        ];
    }

    /**
     * @return list<string>
     */
    public static function housekeepingCodes(): array
    {
        return [
            self::EXTRA_TOWELS,
            self::TOILET_PAPER,
        ];
    }

    /**
     * @return list<string>
     */
    public static function allCodes(): array
    {
        return [
            ...self::restaurantCodes(),
            ...self::housekeepingCodes(),
        ];
    }

    public static function isRestaurantAmenity(?string $description): bool
    {
        return $description !== null
            && in_array($description, self::restaurantCodes(), true);
    }

    public static function isHousekeepingAmenity(?string $description): bool
    {
        return $description !== null
            && in_array($description, self::housekeepingCodes(), true);
    }

    public static function isAnyAmenity(?string $description): bool
    {
        return $description !== null
            && in_array($description, self::allCodes(), true);
    }

    public static function serviceTypeFor(?string $description): string
    {
        if (self::isRestaurantAmenity($description)) {
            return 'comida';
        }

        if (self::isHousekeepingAmenity($description)) {
            return 'limpieza';
        }

        return 'limpieza';
    }
}
