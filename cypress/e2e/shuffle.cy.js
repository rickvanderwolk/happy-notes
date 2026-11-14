// A shuffled list shows every note exactly once while it loads more on scroll, and Escape
// on the list brings back the most recent first.

const noteIds = () => cy.get('[data-cy="note-list-item"]').then(($notes) => [...$notes].map((note) => note.id));

// Infinite scroll says so when there is nothing left to load
const scrollToEnd = (tries = 40) => {
    cy.scrollTo('bottom', { ensureScrollable: false });
    cy.wait(250);
    cy.get('#loading').then(($loading) => {
        if ($loading.is(':visible') && $loading.text().includes("That's all!")) {
            return;
        }
        expect(tries, 'scrolls left to reach the end').to.be.greaterThan(0);
        scrollToEnd(tries - 1);
    });
};

const allNoteIds = () => {
    scrollToEnd();
    return noteIds();
};

const shouldHoldEveryNoteOnce = (ids, recent) => {
    expect(new Set(ids).size, 'notes without doubles').to.eq(ids.length);
    expect([...ids].sort(), 'the same notes as the recent list').to.deep.eq([...recent].sort());
};

describe('Shuffle', () => {
    let recent;

    // The order is kept with the user, so leave the list in its usual order for other specs
    after(() => {
        cy.request('POST', `${Cypress.config('baseUrl')}/api/test/reset-filters`);
    });

    beforeEach(() => {
        cy.request('POST', `${Cypress.config('baseUrl')}/api/test/reset-filters`);
        const user = Cypress.expose('users').user1;
        cy.login(user.email, user.password);
        cy.get('[data-cy="note-list"]').should('be.visible');
        allNoteIds().then((ids) => {
            recent = ids;
        });
        cy.scrollTo('top');
    });

    it('shows every note once while scrolling, in another order', () => {
        cy.visit('/menu');
        cy.get('[data-cy="menu-shuffle"]').click();
        cy.get('[data-cy="shuffle-label"]').should('be.visible');

        allNoteIds().then((ids) => {
            expect(recent.length, 'notes to shuffle, more than one page').to.be.greaterThan(15);
            shouldHoldEveryNoteOnce(ids, recent);
            expect(ids).not.to.deep.eq(recent);
        });
    });

    it('keeps its order when another tab shuffles halfway', () => {
        cy.get('body').type('h');
        cy.get('[data-cy="shuffle-label"]').should('be.visible');
        noteIds().then((firstPage) => {
            // Another tab, same session
            cy.request('/notes/shuffle');

            allNoteIds().then((ids) => {
                expect(ids.slice(0, firstPage.length)).to.deep.eq(firstPage);
                shouldHoldEveryNoteOnce(ids, recent);
            });
        });
    });

    it('stays shuffled after leaving a note with Escape', () => {
        cy.get('body').type('h');
        cy.get('[data-cy="shuffle-label"]').should('be.visible');
        cy.get('[data-cy="note-list-item"]').first().click();
        cy.get('[data-cy="note-body"] [contenteditable="true"]').should('exist');
        cy.get('body').type('{esc}');

        cy.location('pathname').should('eq', '/notes');
        cy.get('[data-cy="shuffle-label"]').should('be.visible');
    });

    it('brings back the most recent first with Escape on the list', () => {
        cy.get('body').type('h');
        cy.get('[data-cy="shuffle-label"]').should('be.visible');
        cy.get('body').type('{esc}');

        cy.get('[data-cy="shuffle-label"]').should('not.exist');
        noteIds().then((ids) => {
            expect(ids).to.deep.eq(recent.slice(0, ids.length));
        });
    });
});
