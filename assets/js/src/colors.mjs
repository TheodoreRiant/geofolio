/* Validate a CSS color before interpolating in inline styles.
   Defends against CSS injection from REST values. Returns the
   fallback when the value is not a recognized hex / rgb format. */
var COLOR_RE = /^(#(?:[0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})|rgba?\([^)]*\))$/;

/* Couleur de repli des marqueurs, fournie par PHP (MappedPlaces\\Map\\Defaults). */
var DEFAULT_COLOR = (typeof mappedPlacesConfig !== 'undefined' && COLOR_RE.test(mappedPlacesConfig.defaultColor || ''))
    ? mappedPlacesConfig.defaultColor
    : 'currentColor';

function sanitizeColor(value, fallback) {
    var fb = fallback || DEFAULT_COLOR;
    if (typeof value !== 'string') return fb;
    var v = value.trim();
    return COLOR_RE.test(v) ? v : fb;
}

/* Couleur d'un lieu : celle de son entité, sinon celle de son type
   (toutes deux validées). */
function resolveEntityColor(place, typeColor) {
    var entityColor = place && place.entity && place.entity.color;
    return sanitizeColor(entityColor || typeColor, typeColor);
}

export { COLOR_RE, DEFAULT_COLOR, sanitizeColor, resolveEntityColor };
