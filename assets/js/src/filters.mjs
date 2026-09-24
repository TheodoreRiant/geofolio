import { foldText } from './text.mjs';

/* Selection des pastilles d'entities. Un objet vide est l'etat neutre :
   toutes les entities sont visibles. Le premier clic isole l'entity, les
   suivants l'ajoutent ou la retirent ; retirer la derniere ramene a l'etat
   neutre. Retourne toujours un nouvel objet. */
function toggleEntitySelection(selection, slug) {
    var current = selection || {};
    var next    = {};
    Object.keys(current).forEach(function(key) {
        if (key !== slug && current[key] === true) next[key] = true;
    });
    if (current[slug] !== true) next[slug] = true;
    return next;
}

/* Critères de filtrage, partagés par la liste et par les compteurs du
   filtre « Types » (qui appliquent tout sauf le type lui-même). */
/* `q` doit déjà être replié par foldText(). */
function matchesSearch(e, q) {
    if (!q) return true;
    var inText = function(v) { return !!v && foldText(v).indexOf(q) !== -1; };
    var inList = function(list) { return !!list && list.some(inText); };
    return inText(e.title) || inText(e.city) || inText(e.address)
        || (!!e.postal_code && e.postal_code.indexOf(q) !== -1)
        || inList(e.types) || inList(e.services);
}

function matchesType(e, type) {
    if (!type) return true;
    return !!e.types && e.types.some(function(t) {
        return t.toLowerCase().trim() === type;
    });
}

/* Sélection vide = aucun filtre. Les établissements sans entité restent
   toujours visibles. */
function matchesEntities(e, selection) {
    if (!Object.keys(selection || {}).length) return true;
    var slug = (e.entity && e.entity.slug) ? e.entity.slug : null;
    return !slug || selection[slug] === true;
}

/* Nombre d'établissements par type (clé normalisée). */
function countTypes(data) {
    var counts = {};
    data.forEach(function(e) {
        var seen = {};
        (e.types || []).forEach(function(rawType) {
            var t = rawType.toLowerCase().trim();
            if (t && !seen[t]) {
                seen[t] = true;
                counts[t] = (counts[t] || 0) + 1;
            }
        });
    });
    return counts;
}

/* Types à proposer : ceux qui ont des résultats, dans l'ordre connu puis
   les autres par ordre alphabétique (ordre stable d'un filtre à l'autre) ;
   le type sélectionné reste proposé même à 0. */
function visibleTypeKeys(counts, knownOrder, selected) {
    var others = Object.keys(counts).concat(selected ? [selected] : [])
        .filter(function(k, i, all) {
            return knownOrder.indexOf(k) === -1 && all.indexOf(k) === i;
        })
        .sort(function(a, b) { return a.localeCompare(b, 'fr'); });
    return knownOrder.concat(others).filter(function(k) {
        return counts[k] > 0 || (!!selected && k === selected);
    });
}

export { toggleEntitySelection, matchesSearch, matchesType, matchesEntities, countTypes, visibleTypeKeys };
