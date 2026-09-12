import './bootstrap';

import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import QRCode from 'qrcode';

window.L = L;
window.QRCode = QRCode;

// A failed Livewire roundtrip rejects an internal promise with a bare response
// object. Nothing catches it, so Sentry only ever saw "Object captured as
// promise rejection with keys: body, errors, json, status" — no status, no
// screen, nothing to act on. Report the failure ourselves with the details.
document.addEventListener('livewire:init', () => {
    if (typeof Livewire === 'undefined') {
        return;
    }

    Livewire.hook('request', ({ fail }) => {
        fail(({ status, content }) => {
            window.Sentry?.captureException(
                new Error(`Livewire request failed (${status ?? 'no status'})`),
                {
                    extra: {
                        status,
                        screen: window.location.pathname,
                        body: typeof content === 'string' ? content.slice(0, 2000) : content,
                    },
                },
            );
        });
    });
});
