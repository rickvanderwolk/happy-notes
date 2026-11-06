describe('Export Tests', () => {
    [['user1', 'user2'], ['user2', 'user1']].forEach(([own, other]) => {
        ['json', 'csv'].forEach((format) => {
            it(`${own} only exports their own notes as ${format}`, () => {
                const user = Cypress.expose("users")[own];
                const differentUser = Cypress.expose("users")[other];
                cy.login(user.email, user.password);
                cy.url().should('include', '/notes');

                cy.request(`/user/export?format=${format}`).then((response) => {
                    const content = typeof response.body === 'string' ? response.body : JSON.stringify(response.body);

                    expect(response.status).to.eq(200);
                    expect(response.headers['content-disposition']).to.match(new RegExp(`^attachment; filename="happynotes_export_.*\\.${format}"$`));
                    expect(content).to.include(user.notes.first_note.uuid);
                    expect(content).to.include(user.notes.first_note.content);
                    expect(content).not.to.include(differentUser.notes.first_note.uuid);
                    expect(content).not.to.include(differentUser.notes.first_note.content);
                });
            });
        });
    });

    it('Does not export anything for a guest', () => {
        cy.request({ url: '/user/export?format=json', followRedirect: false }).then((response) => {
            expect(response.status).to.eq(302);
            expect(response.headers.location).to.include('/login');
            expect(response.headers['content-disposition']).to.be.undefined;
        });
    });
});
