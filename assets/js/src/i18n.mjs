/* Remplacer %s / %d, ou %1$s / %2$d, par les arguments dans l'ordre. */
function formatText(template, args) {
    var i = 0;
    return String(template || '').replace(/%(?:(\d+)\$)?[sd]/g, function(match, position) {
        var index = position ? parseInt(position, 10) - 1 : i++;
        return args[index] !== undefined ? String(args[index]) : '';
    });
}

/* Libelle traduit fourni par PHP (geofolioConfig.i18n), formate. */
function t(key) {
    var i18n = (typeof geofolioConfig !== 'undefined' && geofolioConfig.i18n) || {};
    return formatText(i18n[key], Array.prototype.slice.call(arguments, 1));
}

export { formatText, t };
