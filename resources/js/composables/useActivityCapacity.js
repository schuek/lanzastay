/**
 * Aforo de actividades: max_capacity / max_seats null = acceso libre.
 */
export function activityHasLimitedCapacity(activity) {
    if (!activity) return false;
    const cap = activity.max_capacity ?? activity.max_seats;
    return cap !== null && cap !== undefined && cap !== '';
}

export function activityMaxCapacity(activity) {
    if (!activityHasLimitedCapacity(activity)) return null;
    return Math.max(0, Number(activity.max_capacity ?? activity.max_seats ?? 0));
}

export function activityPlazasDisponibles(activity) {
    if (!activityHasLimitedCapacity(activity)) return null;
    const fromApi = activity.plazas_disponibles;
    if (fromApi !== null && fromApi !== undefined) {
        return Math.max(0, Number(fromApi));
    }
    return activityMaxCapacity(activity);
}

export function activityCapacityLabel(activity, t) {
    if (!activityHasLimitedCapacity(activity)) {
        return t('activities.acceso_libre');
    }
    const disponibles = activityPlazasDisponibles(activity) ?? 0;
    const max = activityMaxCapacity(activity) ?? 0;
    return t('activities.plazas_restantes', { n: disponibles, max });
}
