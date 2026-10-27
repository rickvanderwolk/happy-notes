// Back from a note, the list is as you left it; it never makes you scroll back to the note.

// A note on the third page, so returning to it also covers the list loading the pages up
// to it. Asked for on the spot: other tests change notes, which moves them up the list.
const noteOnThirdPage = () => cy.request('/notes?page=3&partial=1')
    .its('body')
    .then((body) => body.match(/id="note-([0-9a-f-]+)"/)[1]);

// Back at the list, the note is where it was on the screen when it was tapped
const shouldBeAt = (selector, top) => {
    cy.get(selector).should(($el) => {
        expect($el[0].getBoundingClientRect().top, 'top of the note').to.be.closeTo(top, 2);
    });
};

// Without a tap to go by, the note sits around the middle of the screen
const shouldBeAroundTheMiddle = (selector) => {
    cy.get(selector).should(($el) => {
        const rect = $el[0].getBoundingClientRect();
        const height = $el[0].ownerDocument.defaultView.innerHeight;
        const middle = rect.top + rect.height / 2;
        expect(middle, 'middle of the note').to.be.within(height * 0.3, height * 0.7);
    });
};

// A note well within the screen, the kind you would actually tap
const visibleNote = () => cy.get('[data-cy="note-list-item"]').then(($notes) => {
    const height = $notes[0].ownerDocument.defaultView.innerHeight;
    return Cypress.$([...$notes].find((note) => {
        const rect = note.getBoundingClientRect();
        return rect.top > height * 0.3 && rect.bottom < height * 0.8;
    }));
});

describe('Returning to the list', () => {
    beforeEach(() => {
        cy.request('POST', `${Cypress.config('baseUrl')}/api/test/reset-filters`);
        const user = Cypress.expose('users').user1;
        cy.login(user.email, user.password);
        cy.get('[data-cy="note-list"]').should('be.visible');
    });

    it('is as you left it after closing a note', () => {
        cy.scrollTo(0, 400);
        visibleNote().then(($note) => {
            const id = $note.attr('id');
            const top = $note[0].getBoundingClientRect().top;
            cy.get(`#${id}`).click({ scrollBehavior: false });
            cy.get('[data-cy="note-body"] [contenteditable="true"]').should('exist');
            cy.get('[aria-label="Done"]').click();

            cy.location('hash').should('eq', `#${id}`);
            shouldBeAt(`#${id}`, top);
        });
    });

    it('opens at a note past the first page', () => {
        noteOnThirdPage().then((uuid) => {
            cy.visit(`/notes/${uuid}`);
            cy.get('[data-cy="note-body"] [contenteditable="true"]').should('exist');
            cy.get('[aria-label="Done"]').click();

            cy.location('hash').should('eq', `#note-${uuid}`);
            shouldBeAroundTheMiddle(`#note-${uuid}`);
        });
    });

    it('opens at the note when the list is loaded with it in the address', () => {
        noteOnThirdPage().then((uuid) => {
            // From another page: from the list itself only the hash would change, without a load
            cy.visit('/menu');
            cy.visit(`/notes#note-${uuid}`);
            shouldBeAroundTheMiddle(`#note-${uuid}`);

            cy.reload();
            shouldBeAroundTheMiddle(`#note-${uuid}`);
        });
    });

    it('is as you left it after leaving a note with Escape', () => {
        cy.scrollTo(0, 400);
        visibleNote().then(($note) => {
            const id = $note.attr('id');
            const top = $note[0].getBoundingClientRect().top;
            cy.get(`#${id}`).click({ scrollBehavior: false });
            cy.get('[data-cy="note-body"] [contenteditable="true"]').should('exist');
            cy.get('body').type('{esc}');

            cy.location('hash').should('eq', `#${id}`);
            shouldBeAt(`#${id}`, top);
        });
    });

    it('opens with the note around the middle when there was no tap to go by', () => {
        cy.get('[data-cy="note-list-item"]').eq(10).invoke('attr', 'id').then((id) => {
            cy.visit('/menu');
            cy.visit(`/notes#${id}`);
            shouldBeAroundTheMiddle(`#${id}`);
        });
    });

    it('opens at the top after closing the filter', () => {
        cy.scrollTo(0, 600);
        cy.visit('/filter');
        cy.get('[aria-label="Done"]').click();

        cy.location('pathname').should('eq', '/notes');
        cy.location('hash').should('eq', '');
        cy.window().its('scrollY').should('eq', 0);
    });
});
