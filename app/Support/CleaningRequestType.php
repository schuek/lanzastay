<?php

namespace App\Support;

final class CleaningRequestType
{
    public const AMENITY_EXTRA_TOWELS = 'amenity:extra_towels';

    public const AMENITY_TOILET_PAPER = 'amenity:toilet_paper';

    public const ROOM_CLEANING = 'cleaning:room';

    /**
     * @return list<string>
     */
    public static function amenityCodes(): array
    {
        return [
            self::AMENITY_EXTRA_TOWELS,
            self::AMENITY_TOILET_PAPER,
        ];
    }

    /**
     * @return list<string>
     */
    public static function allowedDescriptions(): array
    {
        return [
            ...self::amenityCodes(),
            self::ROOM_CLEANING,
        ];
    }

    public static function isAmenity(?string $description): bool
    {
        if ($description === null || $description === '') {
            return false;
        }

        return in_array($description, self::amenityCodes(), true);
    }

    public static function isAllowed(?string $description): bool
    {
        if ($description === null || $description === '') {
            return true;
        }

        return in_array($description, self::allowedDescriptions(), true);
    }
}
