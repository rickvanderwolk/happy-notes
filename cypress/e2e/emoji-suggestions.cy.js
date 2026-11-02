const ROW = '[data-cy="emoji-suggestions"]';
const GRID = '[data-cy="emoji-filter-emoji-selector"]';

const visibleEmojis = ($buttons) => [...$buttons]
    .filter((button) => button.offsetParent !== null)
    .map((button) => button.textContent.trim());

describe("Emoji suggestions", () => {
    beforeEach(() => {
        cy.request('POST', `${Cypress.config('baseUrl')}/api/test/reset-filters`);
        const user = Cypress.expose("users").user1;
        cy.login(user.email, user.password);
        cy.get('[data-cy="note-list"]').should('be.visible');
    });

    it("Shows up to five suggestions that are not repeated in the grid", () => {
        cy.visit('/notes/new');
        cy.get(`${ROW} button`).then(($row) => {
            const row = visibleEmojis($row);
            expect(row.length).to.be.within(1, 5);
            expect(new Set(row).size).to.eq(row.length);
            cy.get(`${GRID} button`).then(($grid) => {
                const grid = visibleEmojis($grid);
                expect(row.filter((emoji) => grid.includes(emoji))).to.deep.eq([]);
            });
        });
    });

    it("Keeps the two favourites in place while typing and the focus when picking", () => {
        cy.visit('/notes/new');
        cy.get(`${ROW} button`).then(($before) => {
            const favourites = visibleEmojis($before).slice(0, 2);
            cy.get('[data-cy="new-note-title"]').type('note ');
            cy.wait(300);
            cy.get(`${ROW} button`).should(($after) => {
                expect(visibleEmojis($after).slice(0, 2)).to.deep.eq(favourites);
            });
            cy.contains(`${ROW} button`, favourites[0]).click();
            cy.get('.emoji-chip').should('contain.text', favourites[0]);
            cy.get(ROW).should('not.contain.text', favourites[0]);
            cy.focused().should('have.attr', 'data-cy', 'new-note-title');
        });
    });

    it("Never suggests a dimmed emoji in the filter", () => {
        cy.visit('/filter');
        cy.get(`${ROW} button`).first().click();
        cy.get('.emoji-chip').should('have.length', 1);
        cy.get('body').should(($body) => {
            const row = visibleEmojis($body.find(`${ROW} button`));
            const dimmed = visibleEmojis($body.find(`${GRID} button.is-unreachable`));
            expect(row.filter((emoji) => dimmed.includes(emoji))).to.deep.eq([]);
        });
    });

    it("Has no suggestions when excluding", () => {
        cy.visit('/filter/exclude');
        cy.get(GRID).should('be.visible');
        cy.get(ROW).should('not.exist');
    });
});
