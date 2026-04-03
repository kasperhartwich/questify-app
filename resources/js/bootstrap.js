import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo / Reverb — real-time WebSocket client
 *
 * Enables Livewire's echo-presence and echo-private event listeners
 * used throughout the session screens (lobby, active quest, host dashboard).
 */
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

if (window.__reverb?.key) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: window.__reverb.key,
        wsHost: window.__reverb.host,
        wsPort: window.__reverb.port ?? 80,
        wssPort: window.__reverb.port ?? 443,
        forceTLS: (window.__reverb.scheme ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
