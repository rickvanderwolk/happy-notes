describe("Notes Tests", () => {
    beforeEach(() => {
        cy.request('POST', `${Cypress.config('baseUrl')}/api/test/reset-filters`);
        const user = Cypress.env("users").user1;
        cy.login(user.email, user.password);
    });

    it("Create new note", () => {
        const tempId = `${Date.now()}`;
        let selectedEmojis = [];
        cy.get('[data-cy="create-new-note"]').click();
        cy.get('[data-cy="new-note-title"]').type(`My first note - ${tempId}`);
        cy.selectFirstSelectableEmoji().then((emoji) => { selectedEmojis.push(emoji); return cy.selectFirstSelectableEmoji(); }).then((emoji) => { selectedEmojis.push(emoji); });
        cy.get('[data-cy="save-new-note"]').click();
        cy.get('[data-cy="note-list"]').should('be.visible');
        cy.get('[data-cy="note-list"] [data-cy="note-list-item"]').first().should("contain", `My first note - ${tempId}`).as("firstNote");
        cy.get("@firstNote").find('[data-cy="emoji-wrapper"]').should("be.visible");
        selectedEmojis.forEach((emoji) => { cy.get("@firstNote").find('[data-cy="emoji-wrapper"]').should("contain.text", emoji); });
    });

    it("Edit note title", () => {
        const updatedTitle = `Updated Note - ${Date.now()}`;
        cy.get('[data-cy="note-list"] [data-cy="note-list-item"]').first().as("firstNote");
        cy.get("@firstNote").click();
        cy.get('[data-cy="note-title"]').click();
        cy.get('[data-cy="note-title-editor"]').type(updatedTitle);
        cy.get('[data-cy="save-note"]').click();
        cy.wait(1000);
        cy.get('[data-cy="note-title"]').should("contain.text", updatedTitle);
    });

    it("Edit note emojis", () => {
        let selectedEmojis = [];
        cy.get('[data-cy="note-list"] [data-cy="note-list-item"]').first().as("firstNote");
        cy.get("@firstNote").click();
        cy.get('[data-cy="note-emoji-wrapper"]').as('emojiWrapper');
        cy.get('[data-cy="note-emoji-wrapper"]').click();
        cy.selectFirstSelectableEmoji().then((emoji) => { selectedEmojis.push(emoji); return cy.selectFirstSelectableEmoji(); }).then((emoji) => { selectedEmojis.push(emoji); });
        cy.get('[data-cy="save-note-emojis"]').click();
        cy.wait(1000);
        selectedEmojis.forEach((emoji) => {
            console.log('check emoji: ' + emoji);
            cy.get('[data-cy="note-emoji-wrapper"]').should("contain.text", emoji);
        });
    });

    it("Edit note body", () => {
        const bodyText = `Note body text - ${Date.now()}`;
        cy.get('[data-cy="note-list"] [data-cy="note-list-item"]').first().as("firstNote");
        cy.get("@firstNote").click();
        cy.get('[data-cy="note-body"]').click();
        cy.get('[data-cy="note-body"]').type(bodyText);
        cy.wait(1000);
        cy.reload();
        cy.get('[data-cy="note-body"]').should("contain.text", bodyText);
    });

    it("Delete note", () => {
        cy.get('[data-cy="note-list"] [data-cy="note-list-item"]').first().as("firstNote");
        cy.get("@firstNote").invoke("text").then((deletedNoteText) => {
            cy.get("@firstNote").click();
            cy.get('[data-cy="delete-note"]').click();
            cy.on("window:confirm", () => true);
            cy.get('[data-cy="note-list"] [data-cy="note-list-item"]').first().invoke("text").should("not.eq", deletedNoteText);
        });
    });
});
