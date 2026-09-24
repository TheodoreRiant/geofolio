/**
 * ESLint : règles recommandées, adaptées au code de la carte (scripts
 * WordPress classiques, pas de modules) et aux tests Node.
 */
const js      = require('@eslint/js');
const globals = require('globals');

module.exports = [
    js.configs.recommended,
    {
        files: ['assets/js/**/*.js'],
        languageOptions: {
            ecmaVersion: 2020,
            sourceType: 'script',
            globals: {
                ...globals.browser,
                jQuery: 'readonly',
                L: 'readonly',
                maplibregl: 'readonly',
                wp: 'readonly',
                elementorFrontend: 'readonly',
                geofolioConfig: 'readonly',
                geofolioAdmin: 'readonly',
                geofolioGallery: 'readonly',
                geofolioDuplicate: 'readonly',
            },
        },
    },
    {
        files: ['tests/js/**/*.js', 'eslint.config.js'],
        languageOptions: { ecmaVersion: 2022, sourceType: 'commonjs', globals: globals.node },
    },
];
