=== Geofolio ===
Contributors: theodoreriant
Tags: map, leaflet, locations, directory, elementor
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Interactive map of places with search, filters, a synchronised list and an Elementor widget.

== Description ==

Geofolio displays your places (offices, shops, venues, services…) on an interactive map:

* marker clusters coloured by entity, and entity pills to filter the map;
* a type filter with faceted counts;
* search with suggestions, insensitive to case, accents and apostrophes;
* a list synchronised with the map, and rich popups with a photo carousel;
* a responsive layout with a mobile drawer, keyboard accessible.

Places are a WordPress post type with taxonomies (entity with a colour, type with an icon, region, service, accessibility). Editors get geocoding with a draggable marker, a sortable photo gallery, one-click duplication and a CSV import.

No build step and no external CDN: Leaflet, MarkerCluster and MapLibre are bundled. The interface is in English and French.

Site-specific behaviour (default values, icons, CSV columns, URL slugs, labels, data migrations) goes through filters, so a companion plugin can adapt Geofolio without modifying it.

= External services =

Geofolio never sends anything to the plugin author. It only contacts the third-party services below, and only to do what the site owner set up:

* **Map tiles.** The visitor's browser loads map tiles from the basemap chosen in **Map settings**. What is sent: the coordinates of the tiles being viewed (area and zoom level), the visitor's IP address as with any embedded resource, and the site's API key for keyed providers. Providers, with their terms and privacy policies: OpenFreeMap (default, no key) https://openfreemap.org/ ; OpenStreetMap https://www.openstreetmap.org/copyright and https://operations.osmfoundation.org/policies/tiles/ ; IGN Géoplateforme https://geoservices.ign.fr/ ; CARTO https://carto.com/ ; Jawg https://www.jawg.io/ ; MapTiler https://www.maptiler.com/ ; Stadia Maps https://stadiamaps.com/ ; Thunderforest https://www.thunderforest.com/ . Keyed providers are used only if the site owner enters a key.
* **Geocoding.** When an editor imports a CSV file without coordinates, or clicks "Geocode address" when editing a place, the postal address is sent to the Base Adresse Nationale API (French public service, no key, https://adresse.data.gouv.fr/ ) to obtain coordinates. Nothing is sent for visitors. The `geofolio_geocoder_url` filter can point the import to another geocoder, such as Nominatim ( https://operations.osmfoundation.org/policies/nominatim/ ).
* **Nothing else.** Leaflet, MarkerCluster, MapLibre and the Poppins font are bundled with the plugin: no CDN, no external script.

= Privacy =

No analytics, no tracking, no data sent to the plugin author. Visitors only reach the map tile provider selected by the site owner. Place data stays in the WordPress database. The Poppins font is served from the plugin (SIL Open Font License).

= Source code and development =

Development happens on GitHub: https://github.com/TheodoreRiant/geofolio (source, issues, changelog, contribution guide). The plugin ships its readable sources; the only third-party code is in `assets/vendor/`, with each library's licence and version.

== Installation ==

1. Upload the plugin and activate it.
2. Add places in **Places**, or import a CSV file (**Places → Import CSV**), or import the sample dataset.
3. Add the map to a page with the **Geofolio** Elementor widget or the `[geofolio]` shortcode.
4. Optional: in **Places → Map settings**, choose the basemap and set the tile provider API key.

== Frequently Asked Questions ==

= Which basemaps are available? =

Positron via OpenFreeMap, OpenStreetMap and IGN (France) maps work without a key. CARTO, Jawg, MapTiler, Stadia and Thunderforest maps need an API key, set in **Map settings** or with the `GEOFOLIO_TILE_API_KEY` constant. Without a valid key, the map falls back to Positron via OpenFreeMap, which looks the same and needs no key.

= Which CSV columns are recognised? =

Name, Type, Type icon, Service, Accessibility, Entity, Entity colour, Description, Audience, Capacity, Address, Postal code, City, Region, Department, Latitude, Longitude, Phone, Email, Website, Manager, Opening hours, in English or French, accents and case ignored. Services and accessibility accept several values separated by `;`. Rows without coordinates are geocoded.

= Can I use another geocoder? =

Yes: the `geofolio_geocoder_url` filter points the import to any GeoJSON geocoder (for example Nominatim with `format=geojson`).

= Which shortcode attributes are available? =

`height`, `center_lat`, `center_lng`, `zoom`, `show_search`, `show_filter`, `show_list`, `show_fullscreen`, `sidebar_position`, `sidebar_title`, `sidebar_subtitle`, `tile_style`.

== Screenshots ==

1. The map with its sidebar, entity pills and clusters.
2. A place popup with its photo carousel.
3. Editing a place: location, contact and gallery.
4. The Elementor widget settings.

== Changelog ==

= 1.2.0 =
* Fix: the settings and CSV import pages were unreachable (403) and missing from the Places menu.
* Elementor widget: responsive map height (tablet and mobile keep their own heights), "Fit the view to the places" switch, styles for the sidebar title and subtitle and for the entity pills.
* The map shows a message when the places cannot be loaded; the results counter is announced to screen readers.
* Faster map loading: the place list and filters are cached, and refreshed as soon as a place, a term or a setting changes.

= 1.1.0 =
* Complete sample dataset: 16 places with photos, contacts, managers, opening hours, entity colours and type icons.
* CSV import: type icon, entity colour, audience, region, accessibility and photo columns; several values per cell.
* Keyless fallback basemap: Positron via OpenFreeMap, worldwide.

= 1.0.0 =
* First public release, derived from a map plugin built for a single client: generic post type and taxonomies, English source strings with a French translation, data-driven type icons, generic CSV import, extension filters, PSR-4 code base.

== Upgrade Notice ==

= 1.2.0 =
Fixes the unreachable settings and import pages. Elementor widget: tablet and mobile now keep their own map height (600px and 85vh by default) instead of the desktop height; set them in the widget if needed.
