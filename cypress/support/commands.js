Cypress.Commands.add('login', (email, password) => {
    cy.log('Wait between tests to prevent 429 - Too Many Requests (rate limiting)')
    cy.visit('/login');
    cy.get('[name="email"]').type(email);
    cy.get('[name="password"]').type(password);
    cy.get('button[type="submit"]').click();
});

Cypress.Commands.add('selectFirstSelectableEmoji', () => {
    // The grid now renders every emoji and hides the picked ones client side, so filter
    // down to what is actually on screen instead of asserting the whole set is visible.
    return cy.get('[data-cy="emoji-filter-emoji-selector"] span:visible')
        .first()
        .should('be.visible')
        .then(($emoji) => {
            const emojiText = $emoji.text().trim();
            cy.wrap($emoji).click();
            cy.wait(500);
            return cy.wrap(emojiText);
        });
});
