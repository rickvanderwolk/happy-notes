// Runs axe on every screen in light and dark mode, so a change in colours or markup that
// breaks contrast, labels or structure fails here instead of slipping through.
const themes = ['light', 'dark'];

const guestPages = ['/', '/login', '/register', '/forgot-password'];

const userPages = [
    '/notes',
    '/notes/new',
    '/filter',
    '/filter/exclude',
    '/filter/search',
    '/menu',
    '/insights',
    '/shortcuts',
    '/cleanup',
    '/user/profile',
];

const setColorScheme = (theme) => Cypress.automation('remote:debugger:protocol', {
    command: 'Emulation.setEmulatedMedia',
    params: { features: [{ name: 'prefers-color-scheme', value: theme }] },
});

const logViolations = (violations) => {
    cy.task('table', violations.map(({ id, impact, description, nodes }) => ({
        id,
        impact,
        description,
        nodes: nodes.map(node => node.target.join(' ')).join(', '),
    })));
};

const checkPage = (path) => {
    cy.visit(path);
    cy.injectAxe();
    cy.checkA11y(null, null, logViolations);
};

themes.forEach((theme) => {
    describe(`Accessibility (${theme})`, () => {
        beforeEach(() => {
            setColorScheme(theme);
        });

        guestPages.forEach((path) => {
            it(`has no violations on ${path} as a guest`, () => {
                checkPage(path);
            });
        });

        describe('signed in', () => {
            beforeEach(() => {
                cy.request('POST', `${Cypress.config('baseUrl')}/api/test/reset-filters`);
                const user = Cypress.expose('users').user1;
                cy.login(user.email, user.password);
                cy.get('[data-cy="note-list"]').should('be.visible');
            });

            userPages.forEach((path) => {
                it(`has no violations on ${path}`, () => {
                    checkPage(path);
                });
            });

            it('has no violations on a note and its edit screens', () => {
                cy.visit('/notes');
                cy.get('[data-cy="note-list-item"]').first().invoke('attr', 'href').then((href) => {
                    const notePath = new URL(href).pathname;
                    [notePath, `${notePath}/title`, `${notePath}/emojis`].forEach(checkPage);
                });
            });
        });
    });
});
