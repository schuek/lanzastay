/**
 * Etiqueta y estilos de categoría de restaurante (Comida, Bebida, Postre, Entrante).
 */
export function useRestaurantCategory() {
    const categoryKeyFromLabel = (label) => {
        const key = String(label ?? '').toLowerCase();
        if (key.includes('bebida')) return 'bebida';
        if (key.includes('postre')) return 'postre';
        if (key.includes('entrante')) return 'entrante';
        if (key.includes('comida')) return 'comida';
        return null;
    };

    const restaurantCategoryLabel = (service) => {
        if (!service) {
            return null;
        }
        const type = (service.service_type ?? 'comida').toString().toLowerCase();
        const serviceCategory = (service.service_category ?? '').toString().toLowerCase();
        const isRestaurantItem =
            type === 'comida' || ['comida', 'bebida', 'postre', 'entrante'].includes(serviceCategory);

        if (!isRestaurantItem) {
            return null;
        }

        return service.categoria_restaurante || service.service_category || 'Comida';
    };

    const restaurantCategoryKey = (service) =>
        categoryKeyFromLabel(restaurantCategoryLabel(service)) ?? 'comida';

    const restaurantCategoryBadgeClass = (label) => {
        const key = categoryKeyFromLabel(label);
        if (key === 'bebida') {
            return 'bg-blue-50 text-blue-800 ring-1 ring-inset ring-blue-100';
        }
        if (key === 'postre') {
            return 'bg-pink-50 text-pink-800 ring-1 ring-inset ring-pink-100';
        }
        if (key === 'entrante') {
            return 'bg-green-50 text-green-800 ring-1 ring-inset ring-green-100';
        }
        if (key === 'comida') {
            return 'bg-orange-50 text-orange-800 ring-1 ring-inset ring-orange-100';
        }
        return 'bg-slate-50 text-slate-700 ring-1 ring-inset ring-slate-100';
    };

    const restaurantCategoryI18nKey = (service) => {
        const key = restaurantCategoryKey(service);
        return `services.category.${key}`;
    };

    return {
        restaurantCategoryLabel,
        restaurantCategoryKey,
        restaurantCategoryBadgeClass,
        restaurantCategoryI18nKey,
        categoryKeyFromLabel,
    };
}
