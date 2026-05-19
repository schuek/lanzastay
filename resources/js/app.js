import $ from 'jquery';
window.$ = window.jQuery = $;

import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { createI18n } from 'vue-i18n';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { SUPPORTED_LOCALES } from './bootstrap';
import es from './locales/es.json';
import en from './locales/en.json';
import fr from './locales/fr.json';
import de from './locales/de.json';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const savedLocale = localStorage.getItem('hotel_lang') || 'es';
const initialLocale = SUPPORTED_LOCALES.includes(savedLocale) ? savedLocale : 'es';

const i18n = createI18n({
    legacy: false,
    locale: initialLocale,
    fallbackLocale: 'es',
    messages: {
        es,
        en,
        fr,
        de,
    },
});

document.documentElement.lang = initialLocale;

window.__getI18nLocale = () => i18n.global.locale.value;

createInertiaApp({
    title: (title) => title || appName,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18n)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
