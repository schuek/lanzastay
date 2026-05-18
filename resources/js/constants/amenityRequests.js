/** Peticiones rápidas de amenities — códigos en orders.description */
export const AMENITY_CODES = {
    BOTTLED_WATER: 'amenity:bottled_water',
    QUICK_SNACK: 'amenity:quick_snack',
    EXTRA_TOWELS: 'amenity:extra_towels',
    TOILET_PAPER: 'amenity:toilet_paper',
    ROOM: 'cleaning:room',
};

/** Restaurante / cocina — Agua + Bocadillo */
export const RESTAURANT_AMENITIES = [
    {
        code: AMENITY_CODES.BOTTLED_WATER,
        labelKey: 'amenities.bottled_water',
        icon: 'bottle-water',
        serviceType: 'comida',
    },
    {
        code: AMENITY_CODES.QUICK_SNACK,
        labelKey: 'amenities.quick_snack',
        icon: 'burger',
        serviceType: 'comida',
    },
];

/** Housekeeping — Toallas + Papel */
export const HOUSEKEEPING_AMENITIES = [
    {
        code: AMENITY_CODES.EXTRA_TOWELS,
        labelKey: 'amenities.extra_towels',
        icon: 'hands-bubbles',
        serviceType: 'limpieza',
    },
    {
        code: AMENITY_CODES.TOILET_PAPER,
        labelKey: 'amenities.toilet_paper',
        icon: 'toilet-paper',
        serviceType: 'limpieza',
    },
];

export const amenityButtonClass =
    'flex w-full items-center gap-3 rounded-lg border border-[#2F2A26]/10 border-t-2 border-t-[#A64B35] bg-white px-3 py-2.5 text-left text-sm font-medium text-[#2F2A26] shadow-sm transition hover:shadow-md disabled:opacity-50';

export const amenityIconClass = 'text-[#A64B35]';

/**
 * @param {string|null|undefined} description
 */
export function resolveAmenityFromDescription(description) {
    const all = [...RESTAURANT_AMENITIES, ...HOUSEKEEPING_AMENITIES];
    return all.find((a) => a.code === description) ?? null;
}

/** Departamento de destino: cocina (`comida`) o housekeeping (`limpieza`). */
export function serviceTypeForAmenityCode(code) {
    if (RESTAURANT_AMENITIES.some((a) => a.code === code)) {
        return 'comida';
    }
    if (HOUSEKEEPING_AMENITIES.some((a) => a.code === code)) {
        return 'limpieza';
    }
    return 'limpieza';
}
