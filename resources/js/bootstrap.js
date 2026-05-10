import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

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
