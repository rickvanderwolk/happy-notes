import { defineConfig } from 'cypress';
import base from './cypress.config.js';

// Screenshots of every screen in light and dark mode, on desktop and phone size, to
// compare before and after a styling change. Not part of the regular run: use
// npm run visual:before and npm run visual:after. Chrome is used because it reliably
// honours a fixed pixel ratio, so a retina screen does not change the screenshots. The
// window is larger than every viewport, so Cypress never has to scale the page down.
export default defineConfig({
    ...base,
    e2e: {
        ...base.e2e,
        specPattern: 'cypress/visual/**/*.cy.js',
        setupNodeEvents(on, config) {
            base.e2e.setupNodeEvents(on, config);
            on('before:browser:launch', (browser, launchOptions) => {
                if (browser.family === 'chromium') {
                    launchOptions.args.push('--force-device-scale-factor=1', '--window-size=1600,1400');
                }
                return launchOptions;
            });
        },
    },
});
