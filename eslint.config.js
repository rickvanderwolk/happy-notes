import js from '@eslint/js';
import globals from 'globals';

export default [
    {
        ignores: ['public/**', 'vendor/**', 'node_modules/**', 'storage/**', 'bootstrap/cache/**'],
    },
    js.configs.recommended,
    {
        files: ['resources/js/**/*.js'],
        languageOptions: {
            globals: {
                ...globals.browser,
                Livewire: 'readonly',
                Alpine: 'readonly',
            },
        },
    },
    {
        files: ['cypress/**/*.js'],
        languageOptions: {
            globals: {
                ...globals.browser,
                ...globals.mocha,
                cy: 'readonly',
                Cypress: 'readonly',
                expect: 'readonly',
            },
        },
    },
    {
        files: ['*.config.js'],
        languageOptions: {
            globals: globals.node,
        },
    },
];
