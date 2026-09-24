/**
 * Accès aux modules sources de la carte (assets/js/src/*.mjs) depuis les
 * tests : import direct des fonctions, et texte des sources pour les tests
 * qui inspectent le code.
 */

const fs   = require('node:fs');
const path = require('node:path');

const SRC = path.join(__dirname, '..', '..', 'assets', 'js', 'src');

/**
 * Charger un module source (require d'un module ES : Node 20.19 et plus).
 *
 * @param {string} name Nom du fichier sans extension (escape, filters…).
 * @returns {object} Ses exports.
 */
function load(name) {
    return require(path.join(SRC, name + '.mjs'));
}

/** Texte de toutes les sources, concaténé dans l'ordre alphabétique. */
const SOURCE = fs.readdirSync(SRC)
    .filter((file) => file.endsWith('.mjs'))
    .sort()
    .map((file) => fs.readFileSync(path.join(SRC, file), 'utf8'))
    .join('\n');

module.exports = { load, SOURCE, SRC };
