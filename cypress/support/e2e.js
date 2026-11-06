// ***********************************************************
// This example support/e2e.js is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
//
// You can change the location of this file or turn off
// automatically serving support files with the
// 'supportFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/configuration
// ***********************************************************

// Import commands.js using ES2015 syntax:
import './commands'
import 'cypress-axe'

// Fails a test when a page breaks the content security policy, also while the policy is
// only reported, so a violation shows up here before the policy is enforced.
Cypress.on('window:before:load', (win) => {
    win.addEventListener('securitypolicyviolation', (event) => {
        throw new Error(`CSP violation: ${event.violatedDirective} blocked ${event.blockedURI || 'inline code'} ${event.sample}`);
    });
    win.addEventListener('emoji-filter-changed', () => {
        lastFilterChange = Date.now();
    });
});

// A filter change is saved after a 300ms debounce, and on pagehide with keepalive. A save
// still running when a test ends answers after Cypress cleared the cookies and signs the
// next test back in, so every test waits for its saves to land before the next one starts.
let lastFilterChange = 0;
let pendingSaves = 0;

beforeEach(() => {
    lastFilterChange = 0;
    pendingSaves = 0;
    cy.intercept('POST', '/filter/emojis', (req) => {
        pendingSaves++;
        req.on('after:response', () => {
            pendingSaves--;
        });
    });
});

afterEach(() => {
    cy.wrap(null, { log: false, timeout: 10000 }).should(() => {
        expect(Date.now() - lastFilterChange, 'time since the last filter change').to.be.greaterThan(400);
        expect(pendingSaves, 'filter saves still running').to.eq(0);
    });
});
