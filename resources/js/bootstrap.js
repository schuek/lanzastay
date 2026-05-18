import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

export const SUPPORTED_LOCALES = ['es', 'en', 'fr', 'de'];

export function getClientLocale() {
    if (typeof window.__getI18nLocale === 'function') {
        const locale = window.__getI18nLocale();
        if (SUPPORTED_LOCALES.includes(locale)) {
            return locale;
        }
    }

    const stored = localStorage.getItem('hotel_lang');
    return SUPPORTED_LOCALES.includes(stored) ? stored : 'es';
}

function applyAcceptLanguage(config) {
    const locale = getClientLocale();
    config.headers = config.headers ?? {};
    config.headers['Accept-Language'] = locale;
    return config;
}

axios.interceptors.request.use(applyAcceptLanguage);

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

window.Pusher = Pusher;

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http';
const reverbHost = import.meta.env.VITE_REVERB_HOST ?? 'localhost';
const reverbPort = Number(import.meta.env.VITE_REVERB_PORT ?? 8080);

if (reverbKey) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: reverbHost,
        wsPort: reverbScheme === 'https' ? 443 : reverbPort,
        wssPort: reverbScheme === 'https' ? 443 : reverbPort,
        forceTLS: reverbScheme === 'https',
        enabledTransports: ['ws', 'wss'],
    });
} else {
    window.Echo = null;
}
