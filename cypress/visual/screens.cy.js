const themes = ['light', 'dark'];

const viewports = {
    desktop: [1280, 800],
    phone: [390, 844],
};

const guestPages = {
    landing: '/',
    login: '/login',
    register: '/register',
    'forgot-password': '/forgot-password',
    'reset-password': '/reset-password/visual-check-token',
};

const user = Cypress.expose('users').user1;

const userPages = {
    notes: '/notes',
    'note-new': '/notes/new',
    filter: '/filter',
    'filter-exclude': '/filter/exclude',
    'filter-search': '/filter/search',
    menu: '/menu',
    insights: '/insights',
    shortcuts: '/shortcuts',
    cleanup: '/cleanup',
    profile: '/user/profile',
    'confirm-password': '/user/confirm-password',
};

const emulate = (theme) => Cypress.automation('remote:debugger:protocol', {
    command: 'Emulation.setEmulatedMedia',
    params: {
        features: [
            { name: 'prefers-color-scheme', value: theme },
            { name: 'prefers-reduced-motion', value: 'reduce' },
        ],
    },
});

// Every run logs in again, so the list of browser sessions keeps growing. Only the
// current session is kept, which still shows how the list looks. The blinking caret in
// an autofocused field would otherwise differ from one screenshot to the next.
const hideChangingContent = (doc) => {
    const style = doc.createElement('style');
    style.textContent = '.browser-session-item:nth-child(n+2) { display: none !important; } * { caret-color: transparent !important; }';
    doc.head.appendChild(style);
};

const capture = (name, path, readySelector = null) => {
    cy.visit(path);
    // A screenshot could otherwise still catch the previous page while the new one loads.
    cy.location('pathname').should('eq', path);
    cy.document().its('readyState').should('eq', 'complete');
    if (readySelector) {
        cy.get(readySelector).should('exist');
    }
    cy.document().then(hideChangingContent);
    cy.document().its('fonts.status').should('eq', 'loaded');
    cy.screenshot(name, { capture: 'fullPage', overwrite: true });
};

Object.entries(viewports).forEach(([device, [width, height]]) => {
    themes.forEach((theme) => {
        describe(`${device} ${theme}`, () => {
            beforeEach(() => {
                cy.viewport(width, height);
                emulate(theme);
            });

            it('guest pages', () => {
                Object.entries(guestPages).forEach(([name, path]) => {
                    capture(`${device}-${theme}-${name}`, path);
                });
            });

            it('login with an error', () => {
                cy.visit('/login');
                cy.get('[name="email"]').type('nobody@example.com');
                cy.get('[name="password"]').type('wrong-password');
                cy.get('button[type="submit"]').click();
                cy.location('pathname').should('eq', '/login');
                cy.get('.form-errors, .text-red-600').should('exist');
                cy.document().then(hideChangingContent);
                cy.screenshot(`${device}-${theme}-login-error`, { capture: 'fullPage', overwrite: true });
            });

            it('signed-in pages', () => {
                cy.request('POST', `${Cypress.config('baseUrl')}/api/test/reset-filters`);
                cy.login(user.email, user.password);
                cy.get('[data-cy="note-list"]').should('be.visible');

                Object.entries(userPages).forEach(([name, path]) => {
                    capture(`${device}-${theme}-${name}`, path);
                });

                cy.visit('/notes');
                cy.get('[data-cy="note-list-item"]').first().invoke('attr', 'href').then((href) => {
                    const notePath = new URL(href).pathname;
                    // The editor builds the note body after the page has loaded.
                    capture(`${device}-${theme}-note`, notePath, '[data-cy="note-body"] [contenteditable="true"]');
                    capture(`${device}-${theme}-note-title`, `${notePath}/title`);
                    capture(`${device}-${theme}-note-emojis`, `${notePath}/emojis`);
                });
            });
        });
    });
});
