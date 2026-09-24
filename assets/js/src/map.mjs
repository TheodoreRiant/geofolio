import { buildTypeCatalog } from './types.mjs';
import { sanitizeColor, resolveEntityColor } from './colors.mjs';
import { escHtml, escAttr } from './escape.mjs';
import { foldText } from './text.mjs';
import { t } from './i18n.mjs';
import { toggleEntitySelection, matchesSearch, matchesType, matchesEntities, countTypes, visibleTypeKeys } from './filters.mjs';
import { resolveTile } from './tiles.mjs';
import { SVG_PHONE, SVG_NO_RESULTS, SVG_FULLSCREEN_ENTER, SVG_FULLSCREEN_EXIT, SVG_HINT } from './icons.mjs';
import { autocompleteMethods } from './map-autocomplete.mjs';
import { carouselMethods } from './map-carousel.mjs';
import { markersMethods } from './map-markers.mjs';

const $ = window.jQuery;

class GeofolioMap {
    constructor(container) {
        this.$container = $(container);
        this.mapId      = this.$container.attr('id');
        this.map        = null;
        this.markers    = null;
        this.markerMap  = {};
        this._markerCache = {};

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
            // Sans ajustement, centre et zoom du réglage restent en place.
            fitBounds:  String(this.$container.data('fit-bounds')) !== 'false',
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
                } catch (e) { // eslint-disable-line no-unused-vars
                    // Carte MapLibre déjà retirée (widget détruit) : rien à redessiner.
                }
                if (gm.isStyleLoaded && gm.isStyleLoaded()) { afterStyle++; }
            }
            if (afterStyle >= 6 || ticks >= 50) { clearInterval(iv); }
        }, 300);
    }

    /* ============================================================ */
    /*  MARKER CLUSTER - modern white style                          */
    /* ============================================================ */

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
                self._markerCache          = {}; // nouvelle liste : marqueurs à reconstruire
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

    /* ============================================================ */
    /*  CARROUSEL (v2.6)                                             */
    /* ============================================================ */

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

    /* ============================================================ */
    /*  MARKERS                                                      */
    /* ============================================================ */

    /* ============================================================ */
    /*  POPUP                                                        */
    /* ============================================================ */

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

        // Fiches assemblées puis insérées en une fois (un seul recalcul de mise en page).
        var cards = this.filteredPlaces.map(function(place) {
            var typeStr  = (place.types && place.types[0]) ? place.types[0] : '';
            var config   = self.getTypeConfig(typeStr);
            var entityColor = resolveEntityColor(place, config.color);
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

            return cardHtml;
        });
        $list.html(cards.join(''));
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

    /* ============================================================ */
    /*  GEOLOCATION                                                  */
    /* ============================================================ */

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
        this._markerCache = {};
        this._escHandler = null;
    }
}

// Méthodes réparties par thème (fichiers map-*.mjs).
Object.assign(GeofolioMap.prototype, autocompleteMethods, carouselMethods, markersMethods);

export { GeofolioMap };
