import { configureEcho } from '@laravel/echo-react';
import { readXsrfToken } from '@/lib/assistantApi';

export function configureRealtime(): void {
    const { VITE_REVERB_APP_KEY: key, VITE_REVERB_HOST, VITE_REVERB_PORT, VITE_REVERB_SCHEME } = import.meta.env;

    if (!key) {
        return;
    }

    const port = Number(VITE_REVERB_PORT);

    configureEcho({
        broadcaster: 'reverb',
        key,
        wsHost: VITE_REVERB_HOST,
        wsPort: port,
        wssPort: port,
        forceTLS: VITE_REVERB_SCHEME === 'https',
        enabledTransports: ['ws', 'wss'],
        channelAuthorization: {
            transport: 'ajax',
            endpoint: '/broadcasting/auth',
            headersProvider: () => ({ 'X-XSRF-TOKEN': readXsrfToken() }),
        },
    });
}
