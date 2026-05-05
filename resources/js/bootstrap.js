import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;
window.Pusher = Pusher;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;

if (pusherKey) {
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') ?? '';

    const scheme = import.meta.env.VITE_PUSHER_SCHEME ?? 'http';
    const isSecure = scheme === 'https';
    const wsPort = Number(import.meta.env.VITE_PUSHER_PORT ?? (isSecure ? 443 : 6001));

    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
        wsHost: import.meta.env.VITE_PUSHER_HOST ?? window.location.hostname,
        wsPort,
        wssPort: wsPort,
        forceTLS: isSecure,
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
        },
    });
}
