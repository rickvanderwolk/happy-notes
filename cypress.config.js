import { defineConfig } from "cypress";

export default defineConfig({
  allowCypressEnv: false,
  expose: {
      users: {
          user1: {
              email: "user1@example.com",
              password: "password1",
              notes: {
                  first_note: {
                      uuid: 'df6da9cb-9160-4a40-ae21-f041a79bca2c',
                      content: 'Note 1 - user 1',
                  }
              }
          },
          user2: {
              email: "user2@example.com",
              password: "password2",
              notes: {
                  first_note: {
                      uuid: 'b161a8a7-82c3-4ef3-aab2-27aad88b0024',
                      content: 'Note 1 - user 2',
                  }
              }
          },
      },
  },
  e2e: {
      baseUrl: "http://127.0.0.1:8000",
      setupNodeEvents(on, config) {
        // implement node event listeners here
      },
  },
});
