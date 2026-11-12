// Coming back to a tab that was hidden for a while reloads it, so a note changed on another
// device in the meantime is never edited in its old version.

const setVisibility = (win, state) => {
    Object.defineProperty(win.document, 'visibilityState', { value: state, configurable: true });
    win.document.dispatchEvent(new win.Event('visibilitychange'));
};

const awayFor = (minutes) => cy.window().then((win) => {
    const realNow = win.Date.now.bind(win.Date);
    setVisibility(win, 'hidden');
    win.Date.now = () => realNow() + minutes * 60 * 1000;
    setVisibility(win, 'visible');
});

const markPage = () => cy.window().then((win) => { win.notReloaded = true; });

describe('Coming back to a hidden tab', () => {
    beforeEach(() => {
        cy.request('POST', `${Cypress.config('baseUrl')}/api/test/reset-filters`);
        const user = Cypress.expose('users').user1;
        cy.login(user.email, user.password);
        cy.get('[data-cy="note-list"]').should('be.visible');
    });

    it('Reloads the page after a long time away', () => {
        markPage();
        awayFor(6);
        cy.window().its('notReloaded').should('be.undefined');
        cy.get('[data-cy="note-list"]').should('be.visible');
    });

    it('Keeps the page after a short time away', () => {
        markPage();
        awayFor(1);
        cy.wait(500);
        cy.window().its('notReloaded').should('be.true');
    });

    it('Keeps the page while a field holds unsaved text', () => {
        cy.get('[data-cy="create-new-note"]').click();
        cy.get('[data-cy="new-note-title"]').type('Not saved yet');
        markPage();
        awayFor(6);
        cy.wait(500);
        cy.window().its('notReloaded').should('be.true');
        cy.get('[data-cy="new-note-title"]').should('have.value', 'Not saved yet');
    });
});
