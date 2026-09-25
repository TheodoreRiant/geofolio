import { escHtml } from './escape.mjs';

/* Repli d'un caractère pour la recherche : minuscule, sans accent,
   apostrophes typographiques ramenées à '. « saveurs d'elise » trouve
   ainsi « Saveurs d’Élise ». */
function foldChar(c) {
    var lower = c.toLowerCase();
    if (lower.normalize) {
        lower = lower.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }
    return lower.replace(/[\u2018\u2019\u02bc\u2032`]/g, "'");
}

function foldText(s) {
    return Array.from(String(s === undefined || s === null ? '' : s)).map(foldChar).join('');
}

/* Wrap the first occurrence of `query` in <mark>, en comparant les
   textes repliés (accents, casse, apostrophes) mais en surlignant le
   texte d'origine, échappé. */
function highlightMatch(text, query) {
    if (!text) return '';
    var q = foldText(String(query || '').trim());
    if (!q) return escHtml(text);

    // Position d'origine de chaque caractère du texte replié.
    var chars  = Array.from(text);
    var folded = '';
    var origin = [];
    chars.forEach(function(c, i) {
        var f = foldChar(c);
        for (var k = 0; k < f.length; k++) origin.push(i);
        folded += f;
    });

    var idx = folded.indexOf(q);
    if (idx === -1) return escHtml(text);

    var from = origin[idx];
    var to   = origin[idx + q.length - 1] + 1;
    // Garder un accent combinant avec sa lettre.
    while (to < chars.length && foldChar(chars[to]) === '') to++;

    return escHtml(chars.slice(0, from).join('')) +
           '<mark>' + escHtml(chars.slice(from, to).join('')) + '</mark>' +
           escHtml(chars.slice(to).join(''));
}

/* Libelle de la ligne « responsable » du popup : pluriel des que le
   champ liste plusieurs noms (separes par une virgule). Les libelles,
   ponctuation comprise, viennent de mappedPlacesConfig.i18n. */
function managerLabel(value, i18n) {
    var labels = i18n || {};
    var plural = String(value || '').indexOf(',') !== -1;
    return (plural ? labels.managers : labels.manager) || '';
}

export { foldChar, foldText, highlightMatch, managerLabel };
