import {
    AMENITY_CODES,
    HOUSEKEEPING_AMENITIES,
    resolveAmenityFromDescription,
} from '@/constants/amenityRequests';

export const CLEANING_CODES = {
    ...AMENITY_CODES,
    ROOM: AMENITY_CODES.ROOM,
};

export { HOUSEKEEPING_AMENITIES, resolveAmenityFromDescription };

/**
 * Resuelve solo peticiones de housekeeping (nunca cocina/restaurante).
 */
export function resolveCleaningRequest(order) {
    const description = order?.description ?? null;
    const amenity = HOUSEKEEPING_AMENITIES.find((a) => a.code === description);
    if (amenity) {
        return {
            type: 'amenity',
            ...amenity,
            isAmenity: true,
            labelKey: amenity.labelKey,
            icon: amenity.icon,
        };
    }

    return {
        type: 'room',
        code: AMENITY_CODES.ROOM,
        labelKey: 'cleaning.room_cleaning',
        icon: 'broom',
        isAmenity: false,
    };
}

/** Pedidos que pertenecen al tablero de limpieza (excluye cocina). */
export function isCleaningBoardOrder(order) {
    if (order?.service_type !== 'limpieza') return false;
    const description = order?.description ?? '';
    if (['amenity:bottled_water', 'amenity:quick_snack'].includes(description)) {
        return false;
    }
    const allowed = new Set([
        ...HOUSEKEEPING_AMENITIES.map((a) => a.code),
        AMENITY_CODES.ROOM,
        '',
    ]);
    return allowed.has(description) || description == null;
}
