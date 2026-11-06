Cypress.Commands.add('login', (email, password) => {
    cy.log('Wait between tests to prevent 429 - Too Many Requests (rate limiting)')
    cy.visit('/login');
    cy.get('[name="email"]').type(email);
    cy.get('[name="password"]').type(password);
    cy.get('button[type="submit"]').click();
});

Cypress.Commands.add('selectFirstSelectableEmoji', () => {
    // The grid hides picked and suggested emojis client side. Suggestions change once the
    // suggestion data has loaded and 250ms after typing a title, which can hide the emoji
    // about to be clicked, so wait for the picker to settle before picking one.
    cy.document().then((doc) => {
        if (doc.querySelector('[data-cy="emoji-suggestions"]')) {
            cy.window().its('emojiSuggestionData').should('not.be.empty');
        }
    });
    cy.wait(300);
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
