// Nothing may stick out sideways: on a narrow phone and with the largest text every screen
// fits the width, so there is never a sideways scroll and nothing falls off the screen.
// Large text is the iOS text size setting taken to its largest, about three times as big.
const sizes = [
    { name: 'a narrow phone', width: 320, height: 640, textScale: 1 },
    { name: 'a phone with the largest text', width: 390, height: 844, textScale: 3 },
];

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
    '/about',
    '/cleanup',
    '/user/profile',
];

const longLink = 'https://example.com/a-link-that-goes-on/and-on/without-a-single-space/to-break-at/until-the-end';

// Elements that reach past the edge, leaving out those inside something that scrolls or
// clips on its own: they do not widen the page.
const stickingOut = (doc, width) => [...doc.body.querySelectorAll('*')]
    .filter((element) => {
        const rect = element.getBoundingClientRect();
        if (rect.width === 0 || (rect.right <= width + 1 && rect.left >= -1)) {
            return false;
        }
        for (let parent = element.parentElement; parent && parent !== doc.body; parent = parent.parentElement) {
            if (getComputedStyle(parent).overflowX !== 'visible') {
                return false;
            }
        }
        return true;
    })
    .map((element) => element.tagName.toLowerCase() + (element.className && typeof element.className === 'string'
        ? `.${element.className.trim().split(/\s+/).join('.')}`
        : ''));

const checkFits = (path, textScale) => {
    cy.visit(path);
    cy.document().then((doc) => {
        doc.documentElement.style.fontSize = `${textScale * 100}%`;
    });
    cy.document().should((doc) => {
        const width = doc.documentElement.clientWidth;
        expect(
            doc.documentElement.scrollWidth,
            `width of ${path}, sticking out: ${stickingOut(doc, width).slice(0, 5).join(', ') || 'none found'}`
        ).to.be.at.most(width);
    });
};

sizes.forEach(({ name, width, height, textScale }) => {
    describe(`Fits the screen on ${name}`, () => {
        beforeEach(() => {
            cy.viewport(width, height);
        });

        guestPages.forEach((path) => {
            it(`${path} as a guest`, () => {
                checkFits(path, textScale);
            });
        });

        describe('signed in', () => {
            beforeEach(() => {
                cy.request('POST', `${Cypress.config('baseUrl')}/api/test/reset-filters`);
                const user = Cypress.expose('users').user1;
                cy.login(user.email, user.password);
                cy.get('[data-cy="note-list"]').should('exist');
            });

            userPages.forEach((path) => {
                it(path, () => {
                    checkFits(path, textScale);
                });
            });

            it('a note with a long link as its title and in its text, and its edit screens', () => {
                cy.visit('/notes/new');
                cy.get('[data-cy="new-note-title"]').type(longLink, { delay: 0 });
                cy.selectFirstSelectableEmoji();
                cy.get('[data-cy="save-new-note"]').click();
                cy.contains('[data-cy="note-list-item"]', longLink).click();
                cy.get('[data-cy="note-body"] [contenteditable="true"]').first().click().type(longLink, { delay: 0 });
                // Gives the autosave time to store the text
                cy.wait(1000);

                cy.location('pathname').then((notePath) => {
                    checkFits('/notes', textScale);
                    [notePath, `${notePath}/title`, `${notePath}/emojis`].forEach((path) => checkFits(path, textScale));
                });
            });
        });
    });
});
