/**
 * Méthodes de MappedPlacesMap : Autocomplétion de la recherche.
 * Ajoutées au prototype par map.mjs.
 */
import { escHtml } from './escape.mjs';
import { foldText, highlightMatch } from './text.mjs';
import { t } from './i18n.mjs';

const $ = window.jQuery;

const autocompleteMethods = {
    /**
     * Render autocomplete suggestions in the search row that triggered the
     * input. Falls back to the focused search row when no row is provided.
     *
     * @param {string} query - Current search input value
     * @param {jQuery} $row  - Optional .mapl-search-row jQuery wrapper
     */
    renderAutocompleteSuggestions(query, $row) {
        var self = this;

        if (!$row || !$row.length) {
            $row = self.$container.find('.mapl-search-input:focus').closest('.mapl-search-row');
            if (!$row.length) return;
        }

        var $dropdown = $row.find('.mapl-autocomplete-results');
        var $input    = $row.find('.mapl-search-input');

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
            $dropdown.html('<div class="mapl-autocomplete-empty" role="status">' + escHtml(t('noResults')) + '</div>')
                .attr('hidden', false);
            $input.attr('aria-expanded', 'true').removeAttr('aria-activedescendant');
            self._acIndex = -1;
            return;
        }

        var listboxId = $dropdown.attr('id') || 'mapl-ac';
        var itemsHtml = matches.map(function(place) {
            var typeStr = (place.types && place.types[0]) ? place.types[0] : '';
            return '<div class="mapl-autocomplete-item" role="option" ' +
                   'id="' + listboxId + '-item-' + place.id + '" data-place-id="' + place.id + '" ' +
                   'aria-selected="false">' +
                       '<div class="mapl-ac-title">' + highlightMatch(place.title || '', query) + '</div>' +
                       '<div class="mapl-ac-meta">' +
                           highlightMatch(place.city || '', query) +
                           (typeStr ? ' · ' + escHtml(typeStr) : '') +
                       '</div>' +
                   '</div>';
        }).join('');

        $dropdown.html(itemsHtml).attr('hidden', false);
        $input.attr('aria-expanded', 'true').removeAttr('aria-activedescendant');
        self._acIndex = -1;
    },

    /**
     * Move highlight up (-1) or down (1) within the visible dropdown.
     *
     * @param {number} direction - -1 (up) or 1 (down)
     * @param {jQuery} $row      - Optional .mapl-search-row jQuery wrapper
     */
    navigateAutocomplete(direction, $row) {
        if (!$row || !$row.length) {
            $row = this.$container.find('.mapl-search-input:focus').closest('.mapl-search-row');
            if (!$row.length) return;
        }

        var $dropdown = $row.find('.mapl-autocomplete-results:not([hidden])');
        var $items    = $dropdown.find('.mapl-autocomplete-item');
        if ($items.length === 0) return;

        this._acIndex = (this._acIndex + direction + $items.length) % $items.length;
        $items.removeClass('is-highlighted').attr('aria-selected', 'false');
        var $current = $items.eq(this._acIndex).addClass('is-highlighted').attr('aria-selected', 'true');

        $row.find('.mapl-search-input').attr('aria-activedescendant', $current.attr('id'));

        var el = $current[0];
        if (el && el.scrollIntoView) el.scrollIntoView({ block: 'nearest' });
    },

    selectSuggestion(placeId) {
        var id = parseInt(placeId, 10);
        if (!id) return;

        this.closeAutocomplete();
        this.$container.find('.mapl-search-input').val('');
        this.searchTerm = '';
        this.renderAll();
        this.focusPlace(id);
    },

    closeAutocomplete() {
        var self = this;
        this.$container.find('.mapl-autocomplete-results').each(function() {
            var $dropdown = $(this);
            var $input = $dropdown.closest('.mapl-search-row').find('.mapl-search-input');
            self._closeDropdown($dropdown, $input);
        });
    },

    _closeDropdown($dropdown, $input) {
        $dropdown.attr('hidden', true).empty();
        $input.attr('aria-expanded', 'false').removeAttr('aria-activedescendant');
        this._acIndex = -1;
    },
};

export { autocompleteMethods };
