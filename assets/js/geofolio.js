/**
 * Geofolio - Script principal v4.0
 *
 * Architecture cache-first : les donnees sont chargees une seule fois
 * au demarrage, puis filtrees cote client pour un rendu instantane.
 *
 * v4 key change: sidebar shows ESTABLISHMENT CARDS (not category cards)
 * with a DROPDOWN for type filtering. Search + filter are fully client-side.
 */
(function($) {
    'use strict';

    /* ================================================================ */
    /*  TYPES - libellé et icône fournis par l'API (catalogue des types)  */
    /* ================================================================ */

    /* ---- TYPES : début ---- */

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

    /* ---- TYPES : fin ---- */

    /* Validate a CSS color before interpolating in inline styles.
       Defends against CSS injection from REST values. Returns the
       fallback when the value is not a recognized hex / rgb format. */
    var COLOR_RE = /^(#(?:[0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})|rgba?\([^)]*\))$/;

    /* Couleur de repli des marqueurs, fournie par PHP (Geofolio\\Map\\Defaults). */
    var DEFAULT_COLOR = (typeof geofolioConfig !== 'undefined' && COLOR_RE.test(geofolioConfig.defaultColor || ''))
        ? geofolioConfig.defaultColor
        : 'currentColor';

    function sanitizeColor(value, fallback) {
        var fb = fallback || DEFAULT_COLOR;
        if (typeof value !== 'string') return fb;
        var v = value.trim();
        return COLOR_RE.test(v) ? v : fb;
    }

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
       ponctuation comprise, viennent de geofolioConfig.i18n. */
    function managerLabel(value, i18n) {
        var labels = i18n || {};
        var plural = String(value || '').indexOf(',') !== -1;
        return (plural ? labels.managers : labels.manager) || '';
    }

    /* ---- I18N : début ---- */

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

    /* ---- I18N : fin ---- */

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

    /* ---- CARROUSEL DU POPUP : début ---- */

    /**
     * Construire le HTML d'un carrousel multi-photos. Fallback single
     * image si la galerie n'a qu'une entrée. URL et texte alternatif
     * passent par escAttr() : l'alt est modifiable par un compte Auteur.
     *
     * @param {Array}  images Array d'objets {id, alt, medium, large, srcset, sizes}
     * @param {Object} labels Libelles traduits (slideOf, goToSlide, gallery,
     *                        carousel, previousPhoto, nextPhoto).
     * @returns {string}
     */
    function buildCarouselHtml(images, labels) {
        var l = labels || {};
        if (!images || images.length === 0) return '';

        if (images.length === 1) {
            var img = images[0];
            var url = (img.large && img.large.url) || (img.medium && img.medium.url) || '';
            return '<div class="gfo-popup-image"><img src="' + escAttr(url) + '" alt="' + escAttr(img.alt) + '" loading="lazy" /></div>';
        }

        var total = images.length;
        var slidesHtml = images.map(function(img, i) {
            var url = (img.large && img.large.url) || (img.medium && img.medium.url) || '';
            var srcAttr = (i === 0) ? 'src="' + escAttr(url) + '"' : 'src="" data-src="' + escAttr(url) + '"';
            return '<div class="gfo-carousel-slide" role="group" aria-roledescription="slide" ' +
                   'aria-label="' + escAttr(formatText(l.slideOf, [i + 1, total])) + '" aria-hidden="' + (i !== 0) + '">' +
                       '<img ' + srcAttr + ' alt="' + escAttr(img.alt) + '" loading="lazy" />' +
                   '</div>';
        }).join('');

        var dotsHtml = images.map(function(_, i) {
            return '<button type="button" class="gfo-carousel-dot' + (i === 0 ? ' is-active' : '') + '" ' +
                   'role="tab" aria-selected="' + (i === 0) + '" data-slide="' + i + '" ' +
                   'aria-label="' + escAttr(formatText(l.goToSlide, [i + 1])) + '"></button>';
        }).join('');

        return '<div class="gfo-carousel" role="region" aria-label="' + escAttr(l.gallery) + '" aria-roledescription="' + escAttr(l.carousel) + '" tabindex="0">' +
            '<div class="gfo-carousel-track" data-current="0" style="transform: translateX(0%)">' + slidesHtml + '</div>' +
            '<button type="button" class="gfo-carousel-prev" aria-label="' + escAttr(l.previousPhoto) + '">' +
                '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>' +
            '</button>' +
            '<button type="button" class="gfo-carousel-next" aria-label="' + escAttr(l.nextPhoto) + '">' +
                '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>' +
            '</button>' +
            '<div class="gfo-carousel-counter" aria-live="polite">1 / ' + total + '</div>' +
            '<div class="gfo-carousel-dots" role="tablist">' + dotsHtml + '</div>' +
            '</div>';
    }

    /* ---- CARROUSEL DU POPUP : fin ---- */

    /* ================================================================ */
    /*  FONDS DE CARTE                                                   */
    /* ================================================================ */

    /* Les gabarits d'URL sont definis cote PHP (includes/class-tile-providers.php)
       et transmis via geofolioConfig.tiles, cles API deja injectees. Le JS ne
       code plus aucune URL de fournisseur en dur.

       Seule exception : ce repli de derniere chance, utilise si le JS est servi
       depuis un cache plus recent que le PHP (geofolioConfig.tiles absent).
       Il est volontairement SANS CLE pour ne jamais afficher d'erreur
       d'authentification a la place de la carte. */
    const FALLBACK_TILE = {
        id:          'osm',
        type:        'raster',
        url:         'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        subdomains:  'abc',
        maxZoom:     19,
    };

    /**
     * Construire la definition d'un fond a partir de la table serveur.
     * Retourne null si le fond est absent ou indisponible (clé manquante).
     */
    function readTileProvider(config, id) {
        if (!config || !config.providers || !id) return null;

        const provider = config.providers[id];
        if (!provider || !provider.available || !provider.url) return null;

        return {
            id:          id,
            type:        provider.type || 'raster',
            url:         provider.url,
            attribution: provider.attribution || '',
            subdomains:  provider.subdomains || '',
            maxZoom:     provider.maxZoom || FALLBACK_TILE.maxZoom,
        };
    }

    /**
     * Resoudre le fond a afficher pour un conteneur.
     *
     * Ordre de priorite : fond impose dans les reglages du site > fond de la
     * page (Elementor / shortcode) > fond de repli. On ne retourne jamais un
     * fond dont la cle API manque : le visiteur verrait le message d'erreur du
     * fournisseur a la place de la carte.
     *
     * @param {string} pageStyle     Fond demande par la page.
     * @param {boolean} vectorReady  maplibre-gl-leaflet est-il disponible ?
     */
    function resolveTile(pageStyle, vectorReady) {
        const config = (window.geofolioConfig && window.geofolioConfig.tiles) || null;
        if (!config) return FALLBACK_TILE;

        const wanted = config.forced || pageStyle;
        let tile = readTileProvider(config, wanted);

        // Un fond vectoriel sans son moteur de rendu ne peut pas s'afficher.
        if (tile && tile.type === 'vector' && !vectorReady) {
            tile = null;
        }
        if (!tile) {
            tile = readTileProvider(config, config.fallback);
        }
        return tile || FALLBACK_TILE;
    }

    /* ================================================================ */
    /*  SVG ICON CONSTANTS                                               */
    /* ================================================================ */

    const SVG_SEARCH = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>';

    const SVG_PHONE = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 014.14 12a19.79 19.79 0 01-3.07-8.67A2 2 0 013.05 1.11L6.1 1a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L7.09 8.9a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0121 16.18z"/></svg>';

    const SVG_PHONE_14 = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 014.14 12a19.79 19.79 0 01-3.07-8.67A2 2 0 013.05 1.11L6.1 1a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L7.09 8.9a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0121 16.18z"/></svg>';

    const SVG_LOCATION = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>';

    const SVG_ARROW_RIGHT = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';

    const SVG_NO_RESULTS = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>';

    const SVG_FULLSCREEN_ENTER = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>';

    const SVG_FULLSCREEN_EXIT = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="14" y1="10" x2="21" y2="3"/><line x1="3" y1="21" x2="10" y2="14"/></svg>';

    const SVG_CLOCK = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';

    const SVG_GLOBE = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>';

    const SVG_ACCESS = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="4" r="2"/><path d="M19 13h-6l-1-4H7"/><path d="M9 9v6l-2 5"/><path d="M13 13l3 7"/></svg>';

    const SVG_HINT = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l7.07 16.97 2.51-7.39 7.39-2.51L3 3z"/><path d="M13 13l6 6"/></svg>';

    /* ================================================================ */
    /*  GEOFOLIO MAP CLASS                                               */
    /* ================================================================ */

    class GeofolioMap {
        constructor(container) {
            this.$container = $(container);
            this.mapId      = this.$container.attr('id');
            this.map        = null;
            this.markers    = null;
            this.markerMap  = {};

            // Cache-based data management
            this.allPlaces      = [];
            this.filteredPlaces  = [];
            this.typeCatalog            = {};
            this.searchTerm             = '';
            this.activeFilter           = '';

            // Galerie/carrousel : cache des galeries par place.id pour éviter
            // de refetcher /etablissement/{id} à chaque ouverture de popup.
            this._galleryCache          = {};

            // Pastilles d'entités : { slug: true } pour chaque entité isolée.
            // Objet vide = aucun filtre, toutes les entités visibles.
            this.activeEntities          = {};

            // Geolocation state
            this.userLocation = null;
            this.userMarker   = null;
            this.userCircle   = null;

            // Timers and handlers
            this._searchTimer = null;
            this._escHandler  = null;

            // Cycle de vie : l'editeur Elementor peut detruire l'instance
            // pendant que la requete de donnees est encore en vol. On garde
            // de quoi l'annuler et de quoi ignorer les callbacks tardifs.
            this._destroyed   = false;
            this._dataRequest = null;

            // Configuration from data attributes
            this.config = {
                centerLat:  parseFloat(this.$container.data('center-lat')) || 0,
                centerLng:  parseFloat(this.$container.data('center-lng')) || 0,
                zoom:       parseInt(this.$container.data('zoom'))         || 8,
                tileStyle:  this.$container.data('tile-style')             || 'positron',
            };

            this.init();
        }

        /* ============================================================ */
        /*  INIT                                                         */
        /* ============================================================ */

        init() {
            this.initMap();
            this.initMarkerCluster();
            this.bindEvents();
            this.initControls();
            this.loadAllData();
        }

        /* ============================================================ */
        /*  MAP INIT                                                     */
        /* ============================================================ */

        initMap() {
            var mapCanvas = this.$container.find('.gfo-map-canvas')[0];

            this.map = L.map(mapCanvas, {
                center:          [this.config.centerLat, this.config.centerLng],
                zoom:            this.config.zoom,
                scrollWheelZoom: true,
                zoomControl:     false,
                maxZoom:         19,
            });

            // Boutons de zoom en bas à droite pour libérer le coin haut
            // (où sont les pastilles d'entités flottantes).
            L.control.zoom({ position: 'bottomright' }).addTo(this.map);

            const vectorReady = (typeof L.maplibreGL === 'function');
            const tile = resolveTile(this.config.tileStyle, vectorReady);

            if (tile.type === 'vector') {
                this.addVectorBasemap(tile);
                return;
            }

            this.addRasterBasemap(tile);
        }

        /**
         * Fond RASTER classique (L.tileLayer).
         */
        addRasterBasemap(tile) {
            const options = {
                attribution: tile.attribution,
                maxZoom:     tile.maxZoom,
            };
            if (tile.subdomains) {
                options.subdomains = tile.subdomains;
            }

            L.tileLayer(tile.url, options).addTo(this.map);
        }

        /**
         * Fond VECTORIEL rendu via maplibre-gl-leaflet (Plan IGN epure/gris).
         *
         * maplibre-gl-leaflet ne repeint pas la carte GL au premier rendu
         * (canvas blanc tant qu'on n'a pas interagi) : son _update appelle un
         * gl.update() qui n'existe plus dans maplibre recent. La vue/transform
         * GL est pourtant deja correcte - il suffit de forcer un repaint
         * (resize + triggerRepaint) au chargement. En mode embarque, la boucle
         * de rendu ne demarre pas (les evenements 'load'/'idle' GL ne se
         * declenchent jamais) et la carte GL peut ne pas etre prete a l'init.
         * On la recupere donc PARESSEUSEMENT a chaque tick et on repeint
         * jusqu'a ce que le style soit charge, puis quelques repaints de plus
         * pour peindre les tuiles, avec un plafond de securite.
         */
        addVectorBasemap(tile) {
            const glLayer = L.maplibreGL({
                style:       tile.url,
                attribution: tile.attribution,
            }).addTo(this.map);

            if (this.map.attributionControl && tile.attribution) {
                this.map.attributionControl.addAttribution(tile.attribution);
            }

            var ticks = 0, afterStyle = 0;
            var iv = setInterval(function () {
                ticks++;
                var gm = glLayer.getMaplibreMap ? glLayer.getMaplibreMap() : null;
                if (gm) {
                    try {
                        gm.resize();
                        if (typeof gm.triggerRepaint === 'function') { gm.triggerRepaint(); }
                    } catch (e) {}
                    if (gm.isStyleLoaded && gm.isStyleLoaded()) { afterStyle++; }
                }
                if (afterStyle >= 6 || ticks >= 50) { clearInterval(iv); }
            }, 300);
        }

        /* ============================================================ */
        /*  MARKER CLUSTER - modern white style                          */
        /* ============================================================ */

        initMarkerCluster() {
            this.markers = L.markerClusterGroup({
                chunkedLoading:       true,
                spiderfyOnMaxZoom:    true,
                showCoverageOnHover:  false,
                zoomToBoundsOnClick:  true,
                maxClusterRadius:     50,
                animate:              true,
                animateAddingMarkers: true,
                iconCreateFunction: function(cluster) {
                    var count = cluster.getChildCount();
                    var size  = 'small';
                    if (count > 10) size = 'medium';
                    if (count > 50) size = 'large';

                    var dims = { small: 40, medium: 48, large: 56 };
                    var d    = dims[size];

                    // Constellation de pastilles colorées par entité : une
                    // pastille par établissement enfant (plafonnée à MAX_DOTS),
                    // puis un indicateur "+N" pour les enfants restants. Permet
                    // de visualiser l'étendue d'un coup d'œil au dézoom (#5).
                    var MAX_DOTS = 8;
                    var children = cluster.getAllChildMarkers();
                    var shown    = Math.min(children.length, MAX_DOTS);
                    var dots     = '';
                    for (var i = 0; i < shown; i++) {
                        var color = (children[i] && children[i].entityColor)
                            ? children[i].entityColor
                            : DEFAULT_COLOR;
                        dots += '<span class="gfo-cluster-dot" style="background:' + color + '"></span>';
                    }
                    var remaining = count - shown;
                    if (remaining > 0) {
                        dots += '<span class="gfo-cluster-more">+' + remaining + '</span>';
                    }

                    // Accessibilité : le nombre total reste lisible via title/aria-label.
                    var label = escAttr(t('clusterLabel', count));

                    return L.divIcon({
                        html:      '<div class="gfo-cluster gfo-cluster-' + size + '" title="' + label + '" aria-label="' + label + '">'
                                 + '<span class="gfo-cluster-dots">' + dots + '</span>'
                                 + '</div>',
                        className: 'gfo-cluster-icon',
                        iconSize:  L.point(d, d),
                    });
                },
            });

            this.map.addLayer(this.markers);
        }

        /* ============================================================ */
        /*  EVENT BINDINGS                                               */
        /* ============================================================ */

        bindEvents() {
            var self = this;

            /* ---- Search: desktop sidebar ---- */
            this.$container.on('input', '.gfo-sidebar-header .gfo-search-input', function() {
                self.onSearch($(this).val(), 'desktop');
            });
            this.$container.on('click', '.gfo-sidebar-header .gfo-search-btn', function() {
                var val = self.$container.find('.gfo-sidebar-header .gfo-search-input').val();
                self.onSearch(val, 'desktop');
            });
            this.$container.on('keypress', '.gfo-sidebar-header .gfo-search-input', function(e) {
                if (e.which === 13) {
                    self.onSearch($(this).val(), 'desktop');
                }
            });

            /* ---- Search: mobile toolbar ---- */
            this.$container.on('input', '.gfo-mobile-toolbar .gfo-search-input', function() {
                self.onSearch($(this).val(), 'mobile');
            });
            this.$container.on('click', '.gfo-mobile-toolbar .gfo-search-btn', function() {
                var val = self.$container.find('.gfo-mobile-toolbar .gfo-search-input').val();
                self.onSearch(val, 'mobile');
            });
            this.$container.on('keypress', '.gfo-mobile-toolbar .gfo-search-input', function(e) {
                if (e.which === 13) {
                    self.onSearch($(this).val(), 'mobile');
                }
            });

            /* ---- Filter dropdown: desktop ---- */
            this.$container.on('change', '.gfo-sidebar-header .gfo-filter-select', function() {
                self.onFilter($(this).val(), 'desktop');
            });

            /* ---- Filter dropdown: mobile ---- */
            this.$container.on('change', '.gfo-mobile-toolbar .gfo-filter-select', function() {
                self.onFilter($(this).val(), 'mobile');
            });

            /* ---- Establishment card click ---- */
            this.$container.on('click', '.gfo-place-card', function(e) {
                // Do not fire card click when user clicks a phone link
                if ($(e.target).closest('.gfo-place-phone').length) return;
                var id = parseInt($(this).data('id'), 10);
                self.focusPlace(id);
            });

            /* ---- Establishment card hover -> highlight marker ---- */
            this.$container.on('mouseenter', '.gfo-place-card', function() {
                var id = parseInt($(this).data('id'), 10);
                self.highlightMarker(id, true);
            });
            this.$container.on('mouseleave', '.gfo-place-card', function() {
                var id = parseInt($(this).data('id'), 10);
                self.highlightMarker(id, false);
            });

            /* ---- Entity pills: isoler / ajouter / retirer une entité ---- */
            this.$container.on('click', '.gfo-entity-pill', function() {
                var slug = String($(this).attr('data-entity'));
                self.activeEntities = toggleEntitySelection(self.activeEntities, slug);
                self.syncEntityPills();
                self.renderAll();
            });

            /* ---- Entity pills: « Tout afficher » ---- */
            this.$container.on('click', '.gfo-entity-reset', function() {
                self.activeEntities = {};
                self.syncEntityPills();
                self.renderAll();
            });

            /* ---- Geolocation ---- */
            this.$container.on('click', '.gfo-geoloc-btn', function() {
                self.handleGeolocation();
            });

            /* ---- Autocomplete: live suggestions (scoped to active search row) ---- */
            this.$container.on('input', '.gfo-search-input', function() {
                var $input = $(this);
                var $row   = $input.closest('.gfo-search-row');
                var val    = $input.val();
                clearTimeout(self._acTimer);
                self._acTimer = setTimeout(function() {
                    self.renderAutocompleteSuggestions(val, $row);
                }, 200);
            });

            /* ---- Autocomplete: click a suggestion ---- */
            this.$container.on('click', '.gfo-autocomplete-item', function() {
                self.selectSuggestion($(this).data('place-id'));
            });

            /* ---- Autocomplete: keyboard navigation ---- */
            this.$container.on('keydown', '.gfo-search-input', function(e) {
                var $row      = $(this).closest('.gfo-search-row');
                var $dropdown = $row.find('.gfo-autocomplete-results');
                if (!$dropdown.length || $dropdown.is('[hidden]')) return;

                if (e.key === 'ArrowDown')      { e.preventDefault(); self.navigateAutocomplete(1, $row); }
                else if (e.key === 'ArrowUp')   { e.preventDefault(); self.navigateAutocomplete(-1, $row); }
                else if (e.key === 'Enter') {
                    // Pick the highlighted item, or fall back to the first suggestion
                    var $target = $dropdown.find('.gfo-autocomplete-item.is-highlighted').first();
                    if (!$target.length) {
                        $target = $dropdown.find('.gfo-autocomplete-item').first();
                    }
                    if ($target.length) {
                        e.preventDefault();
                        self.selectSuggestion($target.data('place-id'));
                    }
                }
                else if (e.key === 'Escape') { self.closeAutocomplete(); }
            });

            /* ---- Autocomplete: close on outside click ---- */
            $(document).on('click.gfoAC_' + this.mapId, function(e) {
                if (!$(e.target).closest('.gfo-search-row').length) {
                    self.closeAutocomplete();
                }
            });

            /* ---- Carrousel : lazy-fetch de la galerie à l'ouverture du popup ---- */
            this.map.on('popupopen', function(e) {
                var $node    = $(e.popup._contentNode);
                var $content = $node.find('.gfo-popup-content');
                var placeId   = parseInt($content.data('place-id'), 10);
                if (!placeId) return;

                if (self._galleryCache[placeId]) {
                    self.injectGalleryIntoPopup($node, self._galleryCache[placeId]);
                    return;
                }

                // Annuler tout fetch galerie encore en cours pour éviter
                // qu'une réponse en retard n'injecte une mauvaise galerie.
                if (self._galleryXHR && self._galleryXHR.readyState !== 4) {
                    self._galleryXHR.abort();
                }

                self._galleryXHR = $.ajax({
                    url:    geofolioConfig.restUrl + 'places/' + placeId,
                    method: 'GET',
                    success: function(response) {
                        self._galleryCache[placeId] = response.gallery || [];
                        self.injectGalleryIntoPopup($node, self._galleryCache[placeId]);
                    },
                    error: function(xhr, status) {
                        if (status === 'abort') return;
                        self.injectGalleryIntoPopup($node, []);
                    },
                });
            });
        }

        /* ============================================================ */
        /*  CONTROLS: FULLSCREEN, MOBILE DRAWER, ESC                     */
        /* ============================================================ */

        initControls() {
            var self = this;

            /* ---- Fullscreen toggle ---- */
            this.$container.on('click', '.gfo-fullscreen-btn', function() {
                self.toggleFullscreen();
            });

            /* ---- Mobile sidebar toggle ---- */
            this.$container.on('click', '.gfo-sidebar-toggle', function() {
                self.toggleDrawer();
            });

            /* ---- Drawer handle (tap to close) ---- */
            this.$container.on('click', '.gfo-drawer-handle', function() {
                self.closeDrawer();
            });

            /* ---- ESC key: exit fullscreen ---- */
            this._escHandler = function(e) {
                if (e.key === 'Escape' && self.$container.hasClass('gfo-fullscreen')) {
                    self.exitFullscreen();
                }
            };
            $(document).on('keydown.gfomap_' + this.mapId, this._escHandler);
        }

        /* ============================================================ */
        /*  FULLSCREEN                                                   */
        /* ============================================================ */

        toggleFullscreen() {
            if (this.$container.hasClass('gfo-fullscreen')) {
                this.exitFullscreen();
            } else {
                this.enterFullscreen();
            }
        }

        enterFullscreen() {
            this.$container.addClass('gfo-fullscreen');
            $('body').addClass('gfo-body-fullscreen');

            var $btn = this.$container.find('.gfo-fullscreen-btn');
            $btn.attr('aria-label', t('exitFullscreen'));
            $btn.html(SVG_FULLSCREEN_EXIT);

            var map = this.map;
            setTimeout(function() { map.invalidateSize(); }, 50);
        }

        exitFullscreen() {
            this.$container.removeClass('gfo-fullscreen');
            $('body').removeClass('gfo-body-fullscreen');

            var $btn = this.$container.find('.gfo-fullscreen-btn');
            $btn.attr('aria-label', t('enterFullscreen'));
            $btn.html(SVG_FULLSCREEN_ENTER);

            var map = this.map;
            setTimeout(function() { map.invalidateSize(); }, 50);
        }

        /* ============================================================ */
        /*  MOBILE DRAWER                                                */
        /* ============================================================ */

        toggleDrawer() {
            var $sidebar = this.$container.find('.gfo-map-sidebar');
            var $toggle  = this.$container.find('.gfo-sidebar-toggle span');
            var isOpen   = $sidebar.hasClass('gfo-drawer-open');

            if (isOpen) {
                $sidebar.removeClass('gfo-drawer-open');
                $toggle.text(t('filters'));
                this.$container.find('.gfo-sidebar-toggle')
                    .attr('aria-label', t('openFilters'));
            } else {
                $sidebar.addClass('gfo-drawer-open');
                $toggle.text(t('close'));
                this.$container.find('.gfo-sidebar-toggle')
                    .attr('aria-label', t('closeFilters'));
            }
        }

        closeDrawer() {
            var $sidebar = this.$container.find('.gfo-map-sidebar');
            if ($sidebar.hasClass('gfo-drawer-open')) {
                $sidebar.removeClass('gfo-drawer-open');
                this.$container.find('.gfo-sidebar-toggle span')
                    .text(t('filters'));
            }
        }

        /* ============================================================ */
        /*  DATA LOADING - single request, cache everything              */
        /* ============================================================ */

        /**
         * Loads all etablissements once at startup and populates
         * the local cache. All subsequent filtering is done client-side.
         */
        loadAllData() {
            var self = this;
            this.showLoading(true);

            this._dataRequest = $.ajax({
                url:     geofolioConfig.restUrl + 'places',
                method:  'GET',
                // Route publique : pas de nonce. Périmé (page en cache HTML
                // depuis plus de 24 h), il ferait répondre 403 à WordPress.
                success: function(response) {
                    if (self._destroyed) return;

                    self.typeCatalog           = buildTypeCatalog(response.types);
                    self.allPlaces     = response.places || [];
                    self.filteredPlaces = self.allPlaces.slice();
                    self.buildEntityPills();
                    self.renderAll();
                    self.showLoading(false);
                },
                error: function(xhr, status) {
                    // Requête annulée par destroy() : rien à signaler.
                    if (self._destroyed || status === 'abort') return;
                    self.showLoading(false);
                    self.showToast(t('loadError'));
                },
            });
        }

        /* ============================================================ */
        /*  SEARCH & FILTER                                              */
        /* ============================================================ */

        /**
         * Debounced search handler. Syncs inputs between desktop and
         * mobile, then re-renders everything client-side.
         *
         * @param {string} val    - Search input value
         * @param {string} source - 'desktop' or 'mobile'
         */
        onSearch(val, source) {
            var self = this;
            clearTimeout(this._searchTimer);
            this._searchTimer = setTimeout(function() {
                self.searchTerm = val.trim();

                // Sync the other search input
                if (source === 'desktop') {
                    self.$container.find('.gfo-mobile-toolbar .gfo-search-input').val(val);
                } else {
                    self.$container.find('.gfo-sidebar-header .gfo-search-input').val(val);
                }

                self.renderAll();
            }, 200);
        }

        /* ============================================================ */
        /*  AUTOCOMPLETE                                                 */
        /* ============================================================ */

        /**
         * Render autocomplete suggestions in the search row that triggered the
         * input. Falls back to the focused search row when no row is provided.
         *
         * @param {string} query - Current search input value
         * @param {jQuery} $row  - Optional .gfo-search-row jQuery wrapper
         */
        renderAutocompleteSuggestions(query, $row) {
            var self = this;

            if (!$row || !$row.length) {
                $row = self.$container.find('.gfo-search-input:focus').closest('.gfo-search-row');
                if (!$row.length) return;
            }

            var $dropdown = $row.find('.gfo-autocomplete-results');
            var $input    = $row.find('.gfo-search-input');

            if (!query || query.trim().length < 2) {
                self._closeDropdown($dropdown, $input);
                return;
            }

            var q = foldText(query.trim());
            var matches = self.allPlaces.filter(function(e) {
                return foldText(e.title).indexOf(q) !== -1
                    || foldText(e.city).indexOf(q) !== -1;
            }).slice(0, 8);

            if (matches.length === 0) {
                $dropdown.html('<div class="gfo-autocomplete-empty" role="status">' + escHtml(t('noResults')) + '</div>')
                    .attr('hidden', false);
                $input.attr('aria-expanded', 'true').removeAttr('aria-activedescendant');
                self._acIndex = -1;
                return;
            }

            var listboxId = $dropdown.attr('id') || 'gfo-ac';
            var itemsHtml = matches.map(function(place) {
                var typeStr = (place.types && place.types[0]) ? place.types[0] : '';
                return '<div class="gfo-autocomplete-item" role="option" ' +
                       'id="' + listboxId + '-item-' + place.id + '" data-place-id="' + place.id + '" ' +
                       'aria-selected="false">' +
                           '<div class="gfo-ac-title">' + highlightMatch(place.title || '', query) + '</div>' +
                           '<div class="gfo-ac-meta">' +
                               highlightMatch(place.city || '', query) +
                               (typeStr ? ' · ' + escHtml(typeStr) : '') +
                           '</div>' +
                       '</div>';
            }).join('');

            $dropdown.html(itemsHtml).attr('hidden', false);
            $input.attr('aria-expanded', 'true').removeAttr('aria-activedescendant');
            self._acIndex = -1;
        }

        /**
         * Move highlight up (-1) or down (1) within the visible dropdown.
         *
         * @param {number} direction - -1 (up) or 1 (down)
         * @param {jQuery} $row      - Optional .gfo-search-row jQuery wrapper
         */
        navigateAutocomplete(direction, $row) {
            if (!$row || !$row.length) {
                $row = this.$container.find('.gfo-search-input:focus').closest('.gfo-search-row');
                if (!$row.length) return;
            }

            var $dropdown = $row.find('.gfo-autocomplete-results:not([hidden])');
            var $items    = $dropdown.find('.gfo-autocomplete-item');
            if ($items.length === 0) return;

            this._acIndex = (this._acIndex + direction + $items.length) % $items.length;
            $items.removeClass('is-highlighted').attr('aria-selected', 'false');
            var $current = $items.eq(this._acIndex).addClass('is-highlighted').attr('aria-selected', 'true');

            $row.find('.gfo-search-input').attr('aria-activedescendant', $current.attr('id'));

            var el = $current[0];
            if (el && el.scrollIntoView) el.scrollIntoView({ block: 'nearest' });
        }

        selectSuggestion(placeId) {
            var id = parseInt(placeId, 10);
            if (!id) return;

            this.closeAutocomplete();
            this.$container.find('.gfo-search-input').val('');
            this.searchTerm = '';
            this.renderAll();
            this.focusPlace(id);
        }

        closeAutocomplete() {
            var self = this;
            this.$container.find('.gfo-autocomplete-results').each(function() {
                var $dropdown = $(this);
                var $input = $dropdown.closest('.gfo-search-row').find('.gfo-search-input');
                self._closeDropdown($dropdown, $input);
            });
        }

        _closeDropdown($dropdown, $input) {
            $dropdown.attr('hidden', true).empty();
            $input.attr('aria-expanded', 'false').removeAttr('aria-activedescendant');
            this._acIndex = -1;
        }

        /* ============================================================ */
        /*  CARROUSEL (v2.6)                                             */
        /* ============================================================ */

        /**
         * Construire le slot image « placeholder » affiché lorsqu'un
         * établissement n'a AUCUNE photo (ni galerie ni image à la une).
         * Réutilise la classe .gfo-popup-image avec un modificateur
         * pour styler le rendu générique.
         *
         * @returns {string}
         */
        renderImagePlaceholder() {
            var base = (window.geofolioConfig && geofolioConfig.pluginUrl) ? geofolioConfig.pluginUrl : '';
            var src  = base + 'assets/images/placeholder.svg';
            return '<div class="gfo-popup-image gfo-popup-image--placeholder">' +
                       '<img src="' + src + '" alt="' + escAttr(t('photoPlaceholder')) + '" loading="lazy" />' +
                   '</div>';
        }

        /**
         * Construire le HTML d'un carrousel (voir buildCarouselHtml).
         *
         * @param {Array} images
         * @returns {string}
         */
        renderCarousel(images) {
            return buildCarouselHtml(images, {
                slideOf:       t('slideOf'),
                goToSlide:     t('goToSlide'),
                gallery:       t('gallery'),
                carousel:      t('carousel'),
                previousPhoto: t('previousPhoto'),
                nextPhoto:     t('nextPhoto'),
            });
        }

        /**
         * Initialiser les interactions d'un carrousel inséré dans un popup
         * Leaflet : navigation clavier, swipe tactile, lazy-load de la slide
         * courante et suivante.
         *
         * @param {HTMLElement} rootNode Conteneur du popup
         */
        initCarousel(rootNode) {
            var $carousel = $(rootNode).find('.gfo-carousel');
            if (!$carousel.length) return;

            var $track   = $carousel.find('.gfo-carousel-track');
            var $slides  = $carousel.find('.gfo-carousel-slide');
            var $dots    = $carousel.find('.gfo-carousel-dot');
            var $counter = $carousel.find('.gfo-carousel-counter');
            var total    = $slides.length;
            if (total <= 1) return;

            var state = { index: 0 };

            function ensureImageLoaded(idx) {
                var $img = $slides.eq(idx).find('img');
                if ($img.data('src') && !$img.attr('src')) {
                    $img.attr('src', $img.data('src')).removeAttr('data-src');
                }
            }

            function goTo(i) {
                i = (i + total) % total;
                state.index = i;
                $track.css('transform', 'translateX(-' + (i * 100) + '%)').attr('data-current', i);
                $slides.attr('aria-hidden', 'true').eq(i).attr('aria-hidden', 'false');
                $dots.removeClass('is-active').attr('aria-selected', 'false')
                    .eq(i).addClass('is-active').attr('aria-selected', 'true');
                $counter.text((i + 1) + ' / ' + total);
                ensureImageLoaded(i);
                ensureImageLoaded((i + 1) % total);
            }

            $carousel.off('.carousel');
            $carousel.on('click.carousel', '.gfo-carousel-prev', function(e) { e.stopPropagation(); goTo(state.index - 1); });
            $carousel.on('click.carousel', '.gfo-carousel-next', function(e) { e.stopPropagation(); goTo(state.index + 1); });
            $carousel.on('click.carousel', '.gfo-carousel-dot',  function(e) { e.stopPropagation(); goTo(parseInt($(this).data('slide'), 10)); });

            $carousel.on('keydown.carousel', function(e) {
                if (e.key === 'ArrowLeft')      { e.preventDefault(); goTo(state.index - 1); }
                else if (e.key === 'ArrowRight') { e.preventDefault(); goTo(state.index + 1); }
            });

            var trackEl = $track[0];
            if (trackEl) {
                var startX = 0, startTime = 0;
                // stopPropagation pour que le swipe horizontal du carrousel
                // ne déclenche pas le drag/pan de la carte Leaflet sous-jacente.
                trackEl.addEventListener('touchstart', function(e) {
                    e.stopPropagation();
                    startX    = e.changedTouches[0].clientX;
                    startTime = Date.now();
                }, { passive: true });
                trackEl.addEventListener('touchmove', function(e) {
                    e.stopPropagation();
                }, { passive: true });
                trackEl.addEventListener('touchend', function(e) {
                    e.stopPropagation();
                    var dx = e.changedTouches[0].clientX - startX;
                    var dt = Date.now() - startTime;
                    if (Math.abs(dx) > 40 && dt < 500) {
                        goTo(state.index + (dx < 0 ? 1 : -1));
                    }
                }, { passive: true });
            }
        }

        /**
         * Injecter le carrousel dans le popup ouvert : remplace le
         * placeholder shimmer par le markup carrousel et l'initialise.
         * Fallback sur thumbnail si la galerie est vide ou fetch échoué.
         *
         * @param {jQuery} $node    Le contenu du popup
         * @param {Array}  gallery  Array d'images (peut être vide)
         */
        injectGalleryIntoPopup($node, gallery) {
            var $placeholder = $node.find('.gfo-popup-image-placeholder');
            if (!$placeholder.length) return;

            if (gallery && gallery.length > 0) {
                $placeholder.replaceWith(this.renderCarousel(gallery));
                this.initCarousel($node[0]);
                return;
            }

            // Fallback : essayer le thumbnail (featured image) depuis le cache liste
            var placeId = parseInt($node.find('.gfo-popup-content').data('place-id'), 10);
            var place   = this.allPlaces.find(function(e) { return e.id === placeId; });
            if (place && place.thumbnail) {
                $placeholder.replaceWith('<div class="gfo-popup-image"><img src="' + escAttr(place.thumbnail) + '" alt="' + escAttr(place.title) + '" loading="lazy" /></div>');
            } else {
                // Galerie vide et pas d'image à la une : placeholder générique
                $placeholder.replaceWith(this.renderImagePlaceholder());
            }
        }

        /**
         * Filter dropdown handler. Syncs dropdowns between desktop and
         * mobile, then re-renders everything client-side.
         *
         * @param {string} val    - Selected filter value (type key or '')
         * @param {string} source - 'desktop' or 'mobile'
         */
        onFilter(val, source) {
            this.activeFilter = val;

            // Sync the other dropdown
            if (source === 'desktop') {
                this.$container.find('.gfo-mobile-toolbar .gfo-filter-select').val(val);
            } else {
                this.$container.find('.gfo-sidebar-header .gfo-filter-select').val(val);
            }

            this.renderAll();
        }

        /**
         * Returns the filtered subset of allPlaces based
         * on the current search, type filter and entity selection.
         *
         * @returns {Array} Filtered etablissements
         */
        getFiltered() {
            var type = this.activeFilter;
            return this.getFacetBase().filter(function(e) {
                return matchesType(e, type);
            });
        }

        /**
         * Établissements retenus par tous les filtres sauf le type : base des
         * compteurs du filtre « Types ».
         *
         * @returns {Array}
         */
        getFacetBase() {
            var q         = foldText((this.searchTerm || '').trim());
            var selection = this.activeEntities;
            return this.allPlaces.filter(function(e) {
                return matchesSearch(e, q) && matchesEntities(e, selection);
            });
        }

        /**
         * Rebuild the type dropdowns (desktop + mobile) with faceted counts:
         * each type shows how many results it would give with the current
         * search and entity selection. Types at 0 are hidden, except the
         * selected one, which stays selected.
         */
        updateTypeCounts() {
            var base     = this.getFacetBase();
            var counts   = countTypes(base);
            var selected = this.activeFilter;
            var allLabel = t('filterAll');

            var options = '<option value="">' + escHtml(allLabel) + ' (' + base.length + ')</option>';
            var self = this;
            visibleTypeKeys(counts, Object.keys(this.typeCatalog), selected).forEach(function(k) {
                options += '<option value="' + escAttr(k) + '">'
                    + escHtml(self.getTypeConfig(k).label) + ' (' + (counts[k] || 0) + ')</option>';
            });

            this.$container.find('.gfo-filter-select').html(options).val(selected);
        }

        /**
         * Builds the floating entity filter pills from the cached data.
         * Extracts unique entities and injects pills into the map wrapper.
         * All entities start visible (neutral state, no pill selected).
         */
        buildEntityPills() {
            var entities = {};
            this.allPlaces.forEach(function(e) {
                if (e.entity && e.entity.slug && e.entity.name) {
                    if (!entities[e.entity.slug]) {
                        entities[e.entity.slug] = {
                            name:  e.entity.name,
                            color: sanitizeColor(e.entity.color),
                        };
                    }
                }
            });

            var slugs = Object.keys(entities);
            if (!slugs.length) return;

            // Ne garder de la sélection courante que les entités encore présentes
            var current = this.activeEntities;
            this.activeEntities = slugs.reduce(function(next, slug) {
                if (current[slug] === true) next[slug] = true;
                return next;
            }, {});

            var hint     = t('entityHint');
            var resetTxt = t('entityReset');

            // Build pills HTML, précédées d'une phrase guide pour rendre
            // le filtrage par entité plus intuitif.
            var html = '<div class="gfo-entity-section">'
                + '<p class="gfo-entity-hint">'
                + SVG_HINT
                + '<span>' + escHtml(hint) + '</span>'
                + '<button type="button" class="gfo-entity-reset" hidden>'
                + escHtml(resetTxt)
                + '</button>'
                + '</p>'
                + '<div class="gfo-entity-pills">';
            slugs.forEach(function(slug) {
                var ent = entities[slug];
                html += '<button type="button" class="gfo-entity-pill" aria-pressed="false" '
                    + 'data-entity="' + escAttr(slug) + '" '
                    + 'style="--pill-color:' + ent.color + '">'
                    + '<span class="gfo-entity-pill-dot"></span>'
                    + escHtml(ent.name)
                    + '</button>';
            });
            html += '</div></div>';

            // Idempotent : retirer une ancienne section avant d'injecter
            this.$container.find('.gfo-entity-section').remove();
            this.$container.find('.gfo-map-canvas').before(html);
            this.syncEntityPills();
        }

        /**
         * Reflect the entity selection on the pills: selected pills stay
         * filled (aria-pressed), the others turn to outline while a selection
         * exists. « Tout afficher » only shows when a filter is active.
         */
        syncEntityPills() {
            var selection    = this.activeEntities;
            var hasSelection = Object.keys(selection).length > 0;

            this.$container.find('.gfo-entity-pill').each(function() {
                var selected = selection[String($(this).attr('data-entity'))] === true;
                $(this)
                    .toggleClass('is-selected', selected)
                    .toggleClass('inactive', hasSelection && !selected)
                    .attr('aria-pressed', selected ? 'true' : 'false');
            });

            this.$container.find('.gfo-entity-reset').prop('hidden', !hasSelection);
        }

        /* ============================================================ */
        /*  RENDER ALL                                                   */
        /* ============================================================ */

        /**
         * Master render function. Recomputes the filtered list, then
         * re-renders markers, establishment cards, and the results count.
         */
        renderAll() {
            // Filet pour tous les callbacks asynchrones (recherche, filtres,
            // geolocalisation) qui pourraient survivre a la destruction.
            if (this._destroyed || !this.markers) return;

            this.filteredPlaces = this.getFiltered();
            this.updateTypeCounts();
            this.renderMarkers();
            this.renderPlaceList();
            this.$container.find('.gfo-results-count').text(this.filteredPlaces.length);
        }

        /* ============================================================ */
        /*  TYPE CONFIG HELPER                                           */
        /* ============================================================ */

        /**
         * Resolves the configuration for a given type string from the
         * type catalog sent by the API (pin icon when unknown).
         *
         * @param {string} type - Raw type string from data
         * @returns {{ color: string, label: string, svgPath: string }}
         */
        getTypeConfig(type) {
            return resolveTypeConfig(this.typeCatalog, type, DEFAULT_COLOR);
        }

        /* ============================================================ */
        /*  MARKERS                                                      */
        /* ============================================================ */

        /**
         * Clears all existing markers and re-adds them from the
         * current filteredPlaces list.
         */
        renderMarkers() {
            var self       = this;
            var markerList = [];

            this.markers.clearLayers();
            this.markerMap = {};

            this.filteredPlaces.forEach(function(place) {
                if (!place.lat || !place.lng) return;

                var marker = L.marker([place.lat, place.lng], {
                    icon: self.createMarkerIcon(place),
                });

                marker.bindPopup(self.createPopupContent(place), {
                    maxWidth:  380,
                    maxHeight: 400,
                    className: 'gfo-popup',
                    autoPan:   true,
                    // Padding haut/gauche large pour que le popup ne se glisse
                    // jamais sous les pastilles d'entités flottantes en haut
                    // ni sous le bouton fullscreen. Padding bas plus court car
                    // pas d'obstacle.
                    autoPanPaddingTopLeft:     L.point(20, 110),
                    autoPanPaddingBottomRight: L.point(20, 80),
                });

                marker.placeId = place.id;

                // Couleur d'entité attachée au marqueur : au dézoom, le cluster
                // s'en sert pour afficher une petite pastille colorée par
                // établissement plutôt qu'un simple chiffre (objectif #5).
                var placeType   = (place.types && place.types[0]) ? place.types[0] : '';
                var placeConfig = self.getTypeConfig(placeType);
                marker.entityColor = sanitizeColor(
                    (place.entity && place.entity.color) ? place.entity.color : placeConfig.color,
                    placeConfig.color
                );

                // Marker click -> highlight the corresponding sidebar card
                marker.on('click', function() {
                    self.$container.find('.gfo-place-card').removeClass('active');
                    var $card = self.$container.find('.gfo-place-card[data-id="' + place.id + '"]');
                    $card.addClass('active');

                    if ($card.length) {
                        var $list = self.$container.find('.gfo-place-list');
                        if ($list.length) {
                            $list.animate({
                                scrollTop: $list.scrollTop() + $card.position().top - 60
                            }, 300);
                        }
                    }
                });

                self.markerMap[place.id] = marker;
                markerList.push(marker);
            });

            this.markers.addLayers(markerList);

            // Fit bounds to show all markers (unless geolocation is active)
            if (markerList.length > 0 && !this.userLocation) {
                var bounds = this.markers.getBounds();
                if (bounds.isValid()) {
                    this.map.fitBounds(bounds, { padding: [50, 50], maxZoom: 14 });
                }
            }

            // Re-add user location marker if present
            if (this.userLocation) {
                this.addUserLocationMarker();
            }
        }

        /**
         * Creates a custom map pin icon using SVG and the type color.
         *
         * @param {Object} place - Etablissement data object
         * @returns {L.DivIcon}
         */
        createMarkerIcon(place) {
            var type   = (place.types && place.types[0]) ? place.types[0] : '';
            var config = this.getTypeConfig(type);
            var entityColor = sanitizeColor(
                (place.entity && place.entity.color) ? place.entity.color : config.color,
                config.color
            );
            var w = 40;
            var h = 52;

            return L.divIcon({
                html: '<div class="gfo-marker" data-id="' + place.id + '">'
                    + '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 52" class="gfo-pin-svg">'
                    + '<path d="M20 0C11 0 4 7 4 16c0 12 16 27 16 27s16-15 16-27C36 7 29 0 20 0z" fill="' + entityColor + '" stroke="#fff" stroke-width="1.5"/>'
                    + '<circle cx="20" cy="16" r="5" fill="#fff" />'
                    + '</svg>'
                    + '</div>',
                className:  'gfo-marker-icon',
                iconSize:   [w, h],
                iconAnchor: [w / 2, h],
                popupAnchor:[0, -h - 2],
            });
        }

        /* ============================================================ */
        /*  POPUP                                                        */
        /* ============================================================ */

        /**
         * Creates complete HTML content for a marker popup.
         * Includes type badges, title, address, phone, and link button.
         *
         * @param {Object} place - Etablissement data object
         * @returns {string} HTML string
         */
        createPopupContent(place) {
            var title  = place.title || '';
            var type   = (place.types && place.types[0]) ? place.types[0] : '';
            var config = this.getTypeConfig(type);
            var entityColor = sanitizeColor(
                (place.entity && place.entity.color) ? place.entity.color : config.color,
                config.color
            );
            var fullAddr = [place.address, place.postal_code, place.city].filter(Boolean).join(', ');

            var html = '<div class="gfo-popup-content" data-place-id="' + place.id + '">';

            // Image / Galerie : placeholder shimmer si galerie attendue
            // (le carrousel est injecté en popupopen via fetch detail).
            if (place.gallery_count > 0) {
                html += '<div class="gfo-popup-image-placeholder"></div>';
            } else if (place.thumbnail) {
                html += '<div class="gfo-popup-image"><img src="' + escAttr(place.thumbnail) + '" alt="' + escAttr(title) + '" loading="lazy" /></div>';
            } else {
                // Aucune photo (ni galerie ni image à la une) : placeholder générique
                html += this.renderImagePlaceholder();
            }

            html += '<div class="gfo-popup-body">';

            // Badges: entity (solid colored) + type (outline/light)
            html += '<div class="gfo-popup-badges">';
            if (place.entity && place.entity.name) {
                html += '<span class="gfo-popup-entity-badge" style="background:' + entityColor + ';color:#fff">' + escHtml(place.entity.name) + '</span>';
            }
            if (type) {
                html += '<span class="gfo-popup-type-badge" style="color:#555;background:#f0f0f0;border:1px solid #ddd">' + escHtml(config.label) + '</span>';
            }
            html += '</div>';

            // Title
            html += '<h3 class="gfo-popup-title">' + escHtml(title) + '</h3>';

            // Mission / description complete
            var mission = place.description || place.excerpt || '';
            if (mission) {
                html += '<p class="gfo-popup-desc">' + escHtml(mission) + '</p>';
            }

            // Directeur/trice(s) — singulier/pluriel selon la présence d'une virgule
            if (place.manager) {
                html += '<p class="gfo-popup-manager"><strong>'
                    + escHtml(managerLabel(place.manager, geofolioConfig.i18n))
                    + '</strong>' + escHtml(place.manager) + '</p>';
            }

            // Nombre de personnes soutenues (stored in excerpt)
            if (place.excerpt && place.description && place.excerpt !== place.description) {
                html += '<p class="gfo-popup-nombre"><strong>' + escHtml(t('audience')) + '</strong> ' + escHtml(place.excerpt) + '</p>';
            }

            // Info block
            html += '<div class="gfo-popup-info">';

            if (fullAddr) {
                html += '<p class="gfo-popup-address">' + SVG_LOCATION + '<span>' + escHtml(fullAddr) + '</span></p>';
            }

            if (place.phone) {
                html += '<p class="gfo-popup-phone">' + SVG_PHONE_14 + '<a href="tel:' + escAttr(place.phone) + '">' + escHtml(place.phone) + '</a></p>';
            }

            if (place.email) {
                html += '<p class="gfo-popup-email"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg><a href="mailto:' + escAttr(place.email) + '">' + escHtml(place.email) + '</a></p>';
            }

            if (place.services && place.services.length) {
                html += '<p class="gfo-popup-services"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg><span>' + escHtml(place.services.join(', ')) + '</span></p>';
            }

            // Horaires, site web et accessibilite : renseignes dans l'admin
            // mais jamais affiches jusqu'a la 2.7.5. N'apparaissent que si le
            // champ est rempli, donc rien ne change pour une fiche vide.
            if (place.opening_hours) {
                html += '<p class="gfo-popup-hours">' + SVG_CLOCK
                     + '<span>' + escHtml(place.opening_hours) + '</span></p>';
            }

            if (place.accessibility && place.accessibility.length) {
                html += '<p class="gfo-popup-access">' + SVG_ACCESS
                     + '<span>' + escHtml(place.accessibility.join(', ')) + '</span></p>';
            }

            var siteWeb = safeUrl(place.website);
            if (siteWeb) {
                html += '<p class="gfo-popup-website">' + SVG_GLOBE
                     + '<a href="' + escAttr(siteWeb) + '" target="_blank" rel="noopener noreferrer">'
                     + escHtml(prettyUrl(siteWeb)) + '</a></p>';
            }

            if (place.distance) {
                html += '<p class="gfo-popup-distance"><strong>' + place.distance + ' km</strong></p>';
            }

            html += '</div>';
            html += '</div>';
            html += '</div>';
            return html;
        }

        /* ============================================================ */
        /*  ESTABLISHMENT LIST (sidebar cards)                           */
        /* ============================================================ */

        /**
         * Renders the establishment card list in the sidebar.
         * Each card shows: type icon, name, type badge, city, phone.
         */
        renderPlaceList() {
            var $list = this.$container.find('.gfo-place-list');
            var self  = this;

            $list.empty();

            if (this.filteredPlaces.length === 0) {
                var noResultsText = escHtml(t('noResults'));

                $list.html(
                    '<div class="gfo-no-results">'
                    + SVG_NO_RESULTS
                    + '<p>' + noResultsText + '</p>'
                    + '</div>'
                );
                return;
            }

            this.filteredPlaces.forEach(function(place) {
                var typeStr  = (place.types && place.types[0]) ? place.types[0] : '';
                var config   = self.getTypeConfig(typeStr);
                var entityColor = sanitizeColor(
                    (place.entity && place.entity.color) ? place.entity.color : config.color,
                    config.color
                );
                var cityStr = place.city || '';

                // Phone link (with stopPropagation to prevent card click)
                var phoneHtml = '';
                if (place.phone) {
                    phoneHtml = '<a href="tel:' + escAttr(place.phone) + '" class="gfo-place-phone" onclick="event.stopPropagation()">'
                        + SVG_PHONE
                        + escHtml(place.phone)
                        + '</a>';
                }

                var managerHtml = place.manager
                    ? '<div class="gfo-place-manager">' + escHtml(place.manager) + '</div>'
                    : '';

                var cardHtml = '<div class="gfo-place-card" data-id="' + place.id + '"'
                    + ' data-lat="' + (place.lat || '') + '"'
                    + ' data-lng="' + (place.lng || '') + '"'
                    + ' role="listitem" tabindex="0">'
                    + '<div class="gfo-place-icon" style="background:' + entityColor + '12;color:' + entityColor + '">'
                    + '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
                    + config.svgPath
                    + '</svg>'
                    + '</div>'
                    + '<div class="gfo-place-info">'
                    + '<div class="gfo-place-name">' + escHtml(place.title) + '</div>'
                    + '<div class="gfo-place-meta">'
                    + '<span class="gfo-place-type" style="background:' + entityColor + '18;color:' + entityColor + '">' + escHtml(config.label) + '</span>'
                    + '<span class="gfo-place-city">' + escHtml(cityStr) + '</span>'
                    + '</div>'
                    + managerHtml
                    + phoneHtml
                    + '</div>'
                    + '</div>';

                $list.append(cardHtml);
            });
        }

        /* ============================================================ */
        /*  INTERACTIONS: FOCUS & HIGHLIGHT                              */
        /* ============================================================ */

        /**
         * Focuses on an establishment: highlights the card in the sidebar,
         * zooms to the marker, and opens its popup.
         *
         * @param {number} id - Etablissement ID
         */
        focusPlace(id) {
            this.$container.find('.gfo-place-card').removeClass('active');
            this.$container.find('.gfo-place-card[data-id="' + id + '"]').addClass('active');

            this.closeDrawer();

            var marker = this.markerMap[id];
            if (!marker) return;

            // L'autoPan du popup (autoPanPaddingTopLeft) garantit que le popup
            // ne se glisse pas sous les pastilles d'entités flottantes.
            this.markers.zoomToShowLayer(marker, function() {
                marker.openPopup();
            });
        }

        /**
         * Highlights or un-highlights a marker on the map when hovering
         * over a sidebar card.
         *
         * @param {number}  id     - Etablissement ID
         * @param {boolean} active - Whether to add or remove highlight
         */
        highlightMarker(id, active) {
            var marker = this.markerMap[id];
            if (!marker) return;

            var el = marker.getElement();
            if (!el) return;

            var pin = el.querySelector('.gfo-marker');
            if (!pin) return;

            if (active) {
                pin.style.transform = 'translateY(-5px) scale(1.15)';
                pin.style.filter    = 'drop-shadow(0 8px 14px rgba(0,0,0,0.35))';
            } else {
                pin.style.transform = '';
                pin.style.filter    = '';
            }
        }

        /* ============================================================ */
        /*  GEOLOCATION                                                  */
        /* ============================================================ */

        /**
         * Requests the browser geolocation and centers the map on the
         * user's position, then reloads markers with proximity sorting.
         */
        handleGeolocation() {
            var self = this;
            var $btn = this.$container.find('.gfo-geoloc-btn');

            if (!navigator.geolocation) {
                var errorMsg = t('geolocUnavailable');
                this.showToast(errorMsg);
                return;
            }

            $btn.addClass('loading');

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    self.userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                    };
                    self.map.setView([self.userLocation.lat, self.userLocation.lng], 11);
                    self.addUserLocationMarker();
                    self.renderAll();
                    $btn.removeClass('loading');
                },
                function(error) {
                    var errorMsg = t('geolocError');
                    self.showToast(errorMsg);
                    $btn.removeClass('loading');
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }

        /**
         * Adds a pulsing blue marker at the user's location.
         */
        addUserLocationMarker() {
            this.removeUserLocationMarker();

            if (!this.userLocation) return;

            this.userMarker = L.marker(
                [this.userLocation.lat, this.userLocation.lng],
                {
                    icon: L.divIcon({
                        html:      '<div class="gfo-user-marker"></div>',
                        className: 'gfo-user-marker-icon',
                        iconSize:  [20, 20],
                        iconAnchor:[10, 10],
                    }),
                }
            ).addTo(this.map);

            this.userCircle = L.circle(
                [this.userLocation.lat, this.userLocation.lng],
                {
                    radius:      50000, // 50 km default
                    color:       DEFAULT_COLOR,
                    fillColor:   DEFAULT_COLOR,
                    fillOpacity: 0.08,
                    weight:      1.5,
                    dashArray:   '6 4',
                }
            ).addTo(this.map);
        }

        /**
         * Removes the user location marker and circle from the map.
         */
        removeUserLocationMarker() {
            if (this.userMarker) {
                this.map.removeLayer(this.userMarker);
                this.userMarker = null;
            }
            if (this.userCircle) {
                this.map.removeLayer(this.userCircle);
                this.userCircle = null;
            }
        }

        /* ============================================================ */
        /*  LOADING INDICATOR                                            */
        /* ============================================================ */

        /**
         * Shows or hides the loading overlay on the map canvas.
         *
         * @param {boolean} show - Whether to show or hide the loader
         */
        showLoading(show) {
            this.$container.find('.gfo-map-loading').toggle(show);
        }

        /**
         * Shows a temporary toast notification inside the map container.
         *
         * @param {string} message - Message to display
         */
        showToast(message) {
            var $toast = $('<div class="gfo-toast" role="status">' + escHtml(message) + '</div>');
            this.$container.append($toast);
            setTimeout(function() { $toast.addClass('gfo-toast-visible'); }, 10);
            setTimeout(function() {
                $toast.removeClass('gfo-toast-visible');
                setTimeout(function() { $toast.remove(); }, 300);
            }, 4000);
        }

        /* ============================================================ */
        /*  CLEANUP                                                      */
        /* ============================================================ */

        /**
         * Detaches all event handlers. Called when the widget is
         * destroyed (e.g., by Elementor live editor).
         */
        destroy() {
            this._destroyed = true;

            // Sans cet abort, la reponse arrive apres la destruction et son
            // callback travaille sur une instance videe (this.markers === null).
            if (this._dataRequest && typeof this._dataRequest.abort === 'function') {
                this._dataRequest.abort();
                this._dataRequest = null;
            }

            $(document).off('keydown.gfomap_' + this.mapId);
            $(document).off('.gfoAC_' + this.mapId);
            this.$container.off();

            if (this.map) {
                this.map.remove();
                this.map = null;
            }

            this.markers    = null;
            this.markerMap  = {};
            this._escHandler = null;
        }
    }

    /* ================================================================ */
    /*  INITIALIZATION                                                   */
    /* ================================================================ */

    $(document).ready(function() {
        $('.gfo-map-container').each(function() {
            var instance = new GeofolioMap(this);
            // Store instance on the DOM element for external access
            $(this).data('geofolio', instance);
        });
    });

    // Re-init support for Elementor live editor, for every widget name
    // declared by PHP (geofolio_elementor_widget_names).
    function onElementorWidgetReady($scope) {
        var $container = $scope.find('.gfo-map-container');
        if (!$container.length) return;

        var existing = $container.data('geofolio');

        // Hors editeur, l'instance creee au chargement est deja
        // operationnelle : la detruire pour la recreer doublait la
        // requete REST sur chaque page portant la carte.
        var enEdition = (typeof elementorFrontend.isEditMode === 'function')
            && elementorFrontend.isEditMode();
        if (existing && !enEdition) {
            return;
        }

        // Dans l'editeur, on repart d'une instance neuve a chaque
        // rendu pour refleter les reglages modifies.
        if (existing && typeof existing.destroy === 'function') {
            existing.destroy();
        }
        $container.data('geofolio', new GeofolioMap($container[0]));
    }

    $(window).on('elementor/frontend/init', function() {
        if (typeof elementorFrontend === 'undefined') return;
        var names = (window.geofolioConfig && geofolioConfig.elementorWidgets) || [];
        names.forEach(function(name) {
            elementorFrontend.hooks.addAction('frontend/element_ready/' + name + '.default', onElementorWidgetReady);
        });
    });

})(jQuery);
