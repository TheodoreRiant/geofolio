#!/usr/bin/env node
/**
 * Assemble assets/css/mapped-places.css à partir des partiels de assets/css/src/,
 * concaténés dans l'ordre de leur numéro (l'ordre de la cascade).
 *
 * Pas d'esbuild ici : il reformaterait la feuille et retirerait les
 * commentaires ; la feuille servie reste lisible et son diff relisible.
 *
 *   node tools/build-css.js           écrit assets/css/mapped-places.css
 *   node tools/build-css.js --check   échoue si le fichier n'est pas à jour
 */
'use strict';

const fs   = require('node:fs');
const path = require('node:path');

const ROOT    = path.join(__dirname, '..');
const SRC_DIR = path.join(ROOT, 'assets', 'css', 'src');
const OUTPUT  = path.join(ROOT, 'assets', 'css', 'mapped-places.css');
const BANNER  = '/* Built by tools/build-css.js from assets/css/src/: edit the partials, then run npm run build:css. */\n';

/**
 * Feuille assemblée.
 *
 * @returns {string}
 */
function build() {
    const partials = fs.readdirSync(SRC_DIR).filter((name) => name.endsWith('.css')).sort();
    const body = partials
        .map((name) => fs.readFileSync(path.join(SRC_DIR, name), 'utf8').replace(/\n$/, ''))
        .join('\n');
    return BANNER + body;
}

function main() {
    const css = build();
    if (process.argv.includes('--check')) {
        if (fs.readFileSync(OUTPUT, 'utf8') !== css) {
            console.error('assets/css/mapped-places.css is out of date: run npm run build:css');
            process.exit(1);
        }
        return;
    }
    fs.writeFileSync(OUTPUT, css);
}

if (require.main === module) {
    main();
}

module.exports = { build, OUTPUT };
