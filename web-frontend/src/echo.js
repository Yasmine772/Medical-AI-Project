import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const token = localStorage.getItem('doctor_token') || localStorage.getItem('token');

const apiBaseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000';
const apiUrl = new URL(apiBaseUrl);
const useTLS = apiUrl.protocol === 'https:';

const echo = new Echo({
    broadcaster: 'reverb',
    key: 'psalebwypqegk6aieh1f',
    wsHost: apiUrl.hostname,
    wsPort: useTLS ? 443 : 8080,
    wssPort: useTLS ? 443 : 8080,
    forceTLS: useTLS,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: `${apiBaseUrl}/broadcasting/auth`,
    auth: {
        headers: {
            Authorization: token ? `Bearer ${token}` : '',
            Accept: 'application/json',
        },
    },
});

window.Echo = echo;

console.log('Echo initialized with key:', echo.options.key);

export default echo;