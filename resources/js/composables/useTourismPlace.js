import { useI18n } from 'vue-i18n';

const PLACE_SLUGS = [
    { slug: 'timanfaya', match: /timanfaya/i },
    { slug: 'jameos', match: /jameos/i },
    { slug: 'cueva', match: /cueva de los verdes|los verdes/i },
    { slug: 'mirador', match: /mirador del r[ií]o/i },
    { slug: 'geria', match: /geria|vinos/i },
    { slug: 'manrique', match: /manrique/i },
];

export function resolveTourismPlaceSlug(name) {
    const normalized = String(name ?? '');
    const found = PLACE_SLUGS.find(({ match }) => match.test(normalized));
    return found?.slug ?? null;
}

export function useTourismPlace() {
    const { t, te } = useI18n();

    const tourismDescription = (place) => {
        const fromDb = String(place?.description ?? '').trim();
        if (fromDb) {
            return fromDb;
        }

        const slug = resolveTourismPlaceSlug(place?.name);
        if (slug) {
            const key = `tourism.places.${slug}`;
            if (te(key)) {
                return t(key);
            }
        }

        return t('tourism.sin_descripcion');
    };

    return { tourismDescription, resolveTourismPlaceSlug };
}
