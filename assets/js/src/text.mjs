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

/* Le mot (ou groupe de mots) figure-t-il dans le texte, comparés repliés,
   et entier : « Lyon » ne se trouve pas dans « Lyonnais ». */
function containsWord(text, word) {
    var t = foldText(text);
    var w = foldText(word).trim();
    if (!w) return false;
    var from = 0;
    var idx;
    while ((idx = t.indexOf(w, from)) !== -1) {
        var before = idx === 0 ? '' : t.charAt(idx - 1);
        var after  = t.charAt(idx + w.length);
        if (!/[a-z0-9]/.test(before) && !/[a-z0-9]/.test(after)) return true;
        from = idx + 1;
    }
    return false;
}

/* Adresse affichée : le code postal et la ville ne sont ajoutés que s'ils ne
   figurent pas déjà dans le champ adresse (fichiers importés avec une
   adresse complète et des colonnes code postal / ville remplies). */
function formatAddress(place) {
    var address = String((place && place.address) || '').trim();
    var parts   = address ? [address] : [];
    [place && place.postal_code, place && place.city].forEach(function(value) {
        var v = String(value || '').trim();
        if (v && !containsWord(address, v)) parts.push(v);
    });
    return parts.join(', ');
}

export { foldChar, foldText, highlightMatch, managerLabel, formatAddress };
