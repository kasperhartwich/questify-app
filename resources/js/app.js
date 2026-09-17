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

// A double quote inside a double-quoted Alpine attribute ends the attribute
// early, and the browser prints the rest of the component's JavaScript as page
// text. Alpine reports this as "Unexpected end of script" and "Can't find
// variable: <whatever survived>" — true, but neither says the markup broke, so
// the one time it shipped it took a screenshot from Kasper to spot. Name it.
const reportLeakedMarkup = () => {
    const text = document.body?.innerText ?? '';

    // Fragments that only appear as readable text when a tag was closed early.
    // Each is framework markup a player can never legitimately read. Loose
    // tells like "=> {" are deliberately left out: a quest about programming
    // could contain one, and a false alarm here would train us to ignore it.
    const tells = ['x-init="', 'x-data="', '$wire.on(', 'wire:click="'];
    const found = tells.filter((tell) => text.includes(tell));

    if (found.length === 0) {
        return;
    }

    window.Sentry?.captureException(
        new Error('Template markup leaked as visible text — an Alpine attribute closed early'),
        {
            level: 'fatal',
            extra: {
                screen: window.location.pathname,
                matched: found,
                // The leading text is usually enough to identify the component.
                excerpt: text.slice(0, 500),
            },
        },
    );
};

// Once after Alpine has had its chance to render, and again after any
// wire:navigate swap, which re-renders the whole page body.
document.addEventListener('alpine:initialized', () => setTimeout(reportLeakedMarkup, 500));
document.addEventListener('livewire:navigated', () => setTimeout(reportLeakedMarkup, 500));
