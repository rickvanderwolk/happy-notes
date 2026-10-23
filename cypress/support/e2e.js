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
});
