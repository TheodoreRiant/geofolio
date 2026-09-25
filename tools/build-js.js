#!/usr/bin/env node
/**
 * Assemble assets/js/mapped-places.js à partir des modules ES de assets/js/src/
 * (esbuild, format IIFE, non minifié : le fichier servi reste lisible).
 *
 *   node tools/build-js.js           écrit assets/js/mapped-places.js
 *   node tools/build-js.js --check   échoue si le fichier n'est pas à jour
 */
'use strict';

const fs      = require('node:fs');
const path    = require('node:path');
const esbuild = require('esbuild');

const ROOT   = path.join(__dirname, '..');
const ENTRY  = path.join(ROOT, 'assets', 'js', 'src', 'index.mjs');
const OUTPUT = path.join(ROOT, 'assets', 'js', 'mapped-places.js');
const BANNER = '/* Built by tools/build-js.js from assets/js/src/: edit the modules, then run npm run build:js. */';

/**
 * Script assemblé.
 *
 * @returns {string}
 */
function build() {
    const result = esbuild.buildSync({
        entryPoints: [ENTRY],
        bundle: true,
        format: 'iife',
        target: 'es2017',
        charset: 'utf8',
        legalComments: 'none',
        banner: { js: BANNER },
        write: false,
        logLevel: 'error',
    });
    return result.outputFiles[0].text;
}

function main() {
    const js = build();
    if (process.argv.includes('--check')) {
        if (fs.readFileSync(OUTPUT, 'utf8') !== js) {
            console.error('assets/js/mapped-places.js is out of date: run npm run build:js');
            process.exit(1);
        }
        return;
    }
    fs.writeFileSync(OUTPUT, js);
}

if (require.main === module) {
    main();
}

module.exports = { build, OUTPUT };
