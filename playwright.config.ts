import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/Playwright',
    fullyParallel: false,
    workers: 1,
    timeout: 120_000,
    expect: { timeout: 10_000 },
    use: {
        baseURL: 'https://questify-app.test',
        ignoreHTTPSErrors: true,
        viewport: { width: 390, height: 844 },
        locale: 'en-US',
        permissions: ['geolocation'],
        geolocation: { latitude: 55.6761, longitude: 12.5683 },
        actionTimeout: 10_000,
        navigationTimeout: 30_000,
    },
    projects: [
        {
            name: 'mobile-chrome',
            use: {
                browserName: 'chromium',
                isMobile: true,
                hasTouch: true,
            },
        },
    ],
});
