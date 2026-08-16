describe("Notes Tests", () => {
    beforeEach(() => {
        cy.request('POST', `${Cypress.config('baseUrl')}/api/test/reset-filters`);
        const user = Cypress.expose("users").user1;
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
        // Target the contenteditable EditorJS builds, not the wrapper. Cypress retries
        // this until the editor has initialised, instead of racing it.
        cy.get('[data-cy="note-body"] [contenteditable="true"]').first().click().type(bodyText);
        cy.wait(1000);
        cy.reload();
        cy.get('[data-cy="note-body"]').should("contain.text", bodyText);
    });

    it("Loads the editor on a second note without a page reload", () => {
        // Regression: editor.js is an ES module, so the browser evaluates it once. With
        // client side navigation the script tag is re-inserted but not re-run, which left
        // the body empty on every note after the first until a hard refresh.
        cy.get('[data-cy="note-list"] [data-cy="note-list-item"]').eq(0).click();
        cy.get('[data-cy="note-body"] [contenteditable="true"]').should("exist");

        cy.get('[aria-label="Close"]').click();
        cy.get('[data-cy="note-list"]').should("be.visible");

        cy.get('[data-cy="note-list"] [data-cy="note-list-item"]').eq(1).click();
        cy.get('[data-cy="note-body"] [contenteditable="true"]').should("exist");
        cy.get('#editor-placeholder').should("not.exist");
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
