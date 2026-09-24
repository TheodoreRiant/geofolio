/* HTML-escape user-provided strings before inserting them into HTML.
   Les deux guillemets sont échappés : le résultat est sûr aussi bien
   dans un contenu que dans un attribut entre guillemets. */
var HTML_ENTITIES = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };

function escHtml(s) {
    if (s === undefined || s === null) return '';
    return String(s).replace(/[&<>"']/g, function(c) { return HTML_ENTITIES[c]; });
}

/* Echappe une valeur destinee a un attribut HTML entre guillemets. */
function escAttr(s) {
    return escHtml(s);
}

/* N'accepte qu'une URL http(s) : une valeur saisie a la main pourrait
   porter un schema dangereux (javascript:, data:). Retourne '' si le
   schema n'est pas sur, ce qui masque simplement le lien. */
function safeUrl(url) {
    if (!url) return '';
    var value = String(url).trim();
    if (/^https?:\/\//i.test(value)) return value;
    // Saisie courante sans schema : « example.org ».
    if (/^[\w.-]+\.[a-z]{2,}(\/|$)/i.test(value)) return 'https://' + value;
    return '';
}

/* Affichage compact d'une URL : sans schema ni / final. */
function prettyUrl(url) {
    return String(url || '')
        .replace(/^https?:\/\//i, '')
        .replace(/\/$/, '');
}

export { HTML_ENTITIES, escHtml, escAttr, safeUrl, prettyUrl };
