import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const token = localStorage.getItem('doctor_token') || localStorage.getItem('token');
const apiBaseUrl = 'http://localhost:8000';

const echo = new Echo({
    broadcaster: 'reverb',
    key: 'psalebwypqegk6aieh1f',
    wsHost: 'localhost',
    wsPort: 8080,
    wssPort: 8080,
    forceTLS: false,
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