describe('Authentication Tests', () => {
    it('Should not log in with an incorrect email', () => {
        const user = Cypress.expose("users").user1;
        cy.login('wrongusername@example.com', user.password);
        cy.url().should('include', '/login');
        cy.contains('These credentials do not match our records').should('be.visible');
    });

    it('Should not log in with an incorrect password', () => {
        const user = Cypress.expose("users").user1;
        cy.login(user.email, 'wrongpassword');
        cy.url().should('include', '/login');
        cy.contains('These credentials do not match our records').should('be.visible');
    });

    it('Should successfully log in with valid credentials (as user 1)', () => {
        const user = Cypress.expose("users").user1;
        cy.login(user.email, user.password);
        cy.url().should('eq', `${Cypress.config('baseUrl')}/notes`);
        cy.get('[data-cy="main-navbar"]').should('exist');
    });

    it('Should successfully log in with valid credentials (as user 2)', () => {
        const user = Cypress.expose("users").user2;
        cy.login(user.email, user.password);
        cy.url().should('eq', `${Cypress.config('baseUrl')}/notes`);
        cy.get('[data-cy="main-navbar"]').should('exist');
    });
});
