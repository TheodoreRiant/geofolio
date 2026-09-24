/* ================================================================ */
/*  TYPES - libellé et icône fournis par l'API (catalogue des types)  */
/* ================================================================ */

/* Épingle générique : icône d'un type absent du catalogue. */
var PIN_PATH = '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>';

/* Clé d'un type : son nom en minuscules (les établissements portent
   des noms de types, le catalogue les décrit). */
function typeKey(name) {
    return String(name === undefined || name === null ? '' : name).toLowerCase().trim();
}

/* Catalogue { clé: { label, svgPath } } dans l'ordre reçu de l'API. */
function buildTypeCatalog(types) {
    var catalog = {};
    (types || []).forEach(function(t) {
        if (!t || !t.name) return;
        var key = typeKey(t.name);
        if (key && !catalog[key]) {
            catalog[key] = { label: t.label || t.name, svgPath: t.path || PIN_PATH };
        }
    });
    return catalog;
}

/* Configuration d'affichage d'un type : épingle et nom brut s'il est
   absent du catalogue. */
function resolveTypeConfig(catalog, type, color) {
    var entry = type ? catalog[typeKey(type)] : null;
    return {
        color:   color,
        label:   entry ? entry.label : String(type || '').trim(),
        svgPath: entry ? entry.svgPath : PIN_PATH,
    };
}

export { PIN_PATH, typeKey, buildTypeCatalog, resolveTypeConfig };
