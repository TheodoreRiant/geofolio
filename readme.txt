=== Geofolio ===
Contributors: theodoreriant
Tags: map, leaflet, locations, directory, elementor
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.0
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

= 1.1.0 =
* Complete sample dataset: 16 places with photos, contacts, managers, opening hours, entity colours and type icons.
* CSV import: type icon, entity colour, audience, region, accessibility and photo columns; several values per cell.
* Keyless fallback basemap: Positron via OpenFreeMap, worldwide.

= 1.0.0 =
* First public release, derived from a map plugin built for a single client: generic post type and taxonomies, English source strings with a French translation, data-driven type icons, generic CSV import, extension filters, PSR-4 code base.
