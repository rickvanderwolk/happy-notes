describe("Filter Tests", () => {
    beforeEach(() => {
        cy.request('POST', `${Cypress.config('baseUrl')}/api/test/reset-filters`);
        const user = Cypress.expose("users").user1;
        cy.login(user.email, user.password);
    });

    // Selecting is handled client side and synced to the server debounced, so leaving the
    // page immediately after a click is the case that must not drop the selection.
    it("Persists a selection made right before pressing enter", () => {
        cy.visit('/filter');
        cy.get('[data-cy="emoji-filter-emoji-selector"] span:visible').first().then(($emoji) => {
            const emoji = $emoji.text().trim();
            cy.wrap($emoji).click();
            cy.get('body').type('{enter}');
            cy.get('[data-cy="note-list"]').should('be.visible');

            cy.visit('/filter');
            cy.get('.emoji-chip').should('contain.text', emoji);
        });
    });

    it("Persists a selection made right before closing the filter", () => {
        cy.visit('/filter');
        cy.get('[data-cy="emoji-filter-emoji-selector"] span:visible').first().then(($emoji) => {
            const emoji = $emoji.text().trim();
            cy.wrap($emoji).click();
            cy.get('[aria-label="Close"]').click();
            cy.get('[data-cy="note-list"]').should('be.visible');

            cy.visit('/filter');
            cy.get('.emoji-chip').should('contain.text', emoji);
        });
    });

    it("Persists several emojis clicked in quick succession", () => {
        cy.visit('/filter');
        const picked = [];
        cy.selectFirstSelectableEmoji().then((e) => { picked.push(e); return cy.selectFirstSelectableEmoji(); })
            .then((e) => { picked.push(e); return cy.selectFirstSelectableEmoji(); })
            .then((e) => {
                picked.push(e);
                cy.get('body').type('{enter}');
                cy.get('[data-cy="note-list"]').should('be.visible');

                cy.visit('/filter');
                picked.forEach((emoji) => {
                    cy.get('.emoji-chips-wrapper').should('contain.text', emoji);
                });
            });
    });

    // Every emoji in the grid comes from the user's own notes, so a selected one is always
    // on at least one note. Asserting against the whole result set rather than a fixed
    // note keeps this independent of what the seeder happened to generate.
    it("Only shows notes carrying the included emoji", () => {
        cy.visit('/filter');
        cy.selectFirstSelectableEmoji().then((emoji) => {
            cy.get('body').type('{enter}');
            cy.get('[data-cy="note-list"]').should('be.visible');
            cy.get('[data-cy="note-list-item"]').should('have.length.greaterThan', 0);
            cy.get('[data-cy="note-list-item"]').each(($card) => {
                cy.wrap($card).should('contain.text', emoji);
            });
        });
    });

    it("Hides notes carrying the excluded emoji", () => {
        cy.visit('/filter/exclude');
        cy.selectFirstSelectableEmoji().then((emoji) => {
            cy.get('body').type('{enter}');
            cy.get('[data-cy="note-list"]').should('be.visible');
            cy.get('[data-cy="note-list-item"]').should('have.length.greaterThan', 0);
            cy.get('[data-cy="note-list-item"]').each(($card) => {
                cy.wrap($card).should('not.contain.text', emoji);
            });
        });
    });

    it("Persists an excluded emoji", () => {
        cy.visit('/filter/exclude');
        cy.selectFirstSelectableEmoji().then((emoji) => {
            cy.get('body').type('{enter}');
            cy.get('[data-cy="note-list"]').should('be.visible');

            cy.visit('/filter/exclude');
            cy.get('.emoji-chip').should('contain.text', emoji);
        });
    });

    it("Persists a search term typed right before leaving", () => {
        cy.visit('/filter/search');
        cy.get('textarea').type('Note 1 -');
        cy.get('body').type('{enter}');
        cy.get('[data-cy="note-list"]').should('be.visible');

        cy.visit('/filter/search');
        cy.get('textarea').should('have.value', 'Note 1 -');
    });

    it("Persists an excluded emoji picked right before closing", () => {
        cy.visit('/filter/exclude');
        cy.selectFirstSelectableEmoji().then((emoji) => {
            cy.get('[aria-label="Close"]').click();
            cy.get('[data-cy="note-list"]').should('be.visible');

            cy.visit('/filter/exclude');
            cy.get('.emoji-chip').should('contain.text', emoji);
        });
    });

    it("Persists removing an excluded emoji right before closing", () => {
        cy.visit('/filter/exclude');
        cy.selectFirstSelectableEmoji().then((emoji) => {
            cy.get('body').type('{enter}');
            cy.get('[data-cy="note-list"]').should('be.visible');

            // Now take it away again and leave immediately.
            cy.visit('/filter/exclude');
            cy.get('.emoji-chip').should('contain.text', emoji).click();
            cy.get('[aria-label="Close"]').click();
            cy.get('[data-cy="note-list"]').should('be.visible');

            cy.visit('/filter/exclude');
            cy.get('.emoji-chip').should('not.exist');
        });
    });

    // Regression: two saves in quick succession used to go through Livewire, which lost
    // track of its own component on the second call and silently dropped the change. The
    // pause matters, it lets the first save fire before the second is queued.
    ['filter', 'filter/exclude'].forEach((page) => {
        it(`Persists two emojis removed one after another on /${page}`, () => {
            cy.visit(`/${page}`);
            cy.selectFirstSelectableEmoji().then(() => cy.selectFirstSelectableEmoji()).then(() => {
                cy.get('body').type('{enter}');
                cy.get('[data-cy="note-list"]').should('be.visible');

                cy.visit(`/${page}`);
                cy.get('.emoji-chip').should('have.length', 2);
                cy.get('.emoji-chip').eq(0).click();
                cy.wait(600);
                cy.get('.emoji-chip').eq(0).click();
                cy.get('[aria-label="Close"]').click();
                cy.get('[data-cy="note-list"]').should('be.visible');

                cy.visit(`/${page}`);
                cy.get('.emoji-chip').should('not.exist');
            });
        });
    });

    it("Deselecting removes the emoji again", () => {
        cy.visit('/filter');
        cy.selectFirstSelectableEmoji().then((emoji) => {
            cy.get('body').type('{enter}');
            cy.get('[data-cy="note-list"]').should('be.visible');

            cy.visit('/filter');
            cy.get('.emoji-chip').should('contain.text', emoji).click();
            cy.get('body').type('{enter}');
            cy.get('[data-cy="note-list"]').should('be.visible');

            cy.visit('/filter');
            cy.get('.emoji-chip').should('not.exist');
        });
    });
});
