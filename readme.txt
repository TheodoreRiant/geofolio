=== Geofolio ===
Contributors: theodoreriant
Tags: map, store locator, locations, directory, leaflet
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.4.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Interactive map of your places with search, filters, a synchronised list and photo popups. Block, Elementor widget and shortcode.

== Description ==

Geofolio turns a list of places into an interactive map your visitors can search and filter, with a list that follows the map and popups showing photos, contact details and opening hours. Add it with the **Geofolio Map block**, the **Elementor widget** or the `[geofolio]` shortcode.

Features:

* marker clusters coloured by entity, and entity pills to filter the map;
* a type filter with faceted counts;
* search with suggestions, insensitive to case, accents and apostrophes;
* a list synchronised with the map, and rich popups with a photo carousel;
* a responsive layout with a mobile toolbar, fullscreen mode, keyboard accessible;
* a Gutenberg block with a live preview in the editor, an Elementor widget with style controls, and a shortcode;
* basemaps from OpenFreeMap, OpenStreetMap, IGN, CARTO, Jawg, MapTiler, Stadia and Thunderforest, with a keyless fallback;
* a CSV import that geocodes addresses, imports photos, entity colours and type icons, plus a sample dataset to try it;
* a public read-only REST API (`geofolio/v1`).

= Who is it for? =

Networks and organisations with several sites (associations, public services, health or social care providers, franchises), directories and store locators, event venues, tourism offices: anyone who wants a map of their places that stays up to date from WordPress.

Places are a WordPress post type with taxonomies (entity with a colour, type with an icon, region, service, accessibility). Editors get geocoding with a draggable marker, a sortable photo gallery, one-click duplication and a CSV import.

No external CDN: Leaflet, MarkerCluster and MapLibre are bundled. The interface is in English and French.

= Accessibility =

The map can be used with the keyboard: search suggestions with arrow keys and Enter, place cards focusable, Escape to leave fullscreen. Results and toasts are announced to screen readers (`aria-live`), entity pills expose their state (`aria-pressed`), the carousel has labelled controls, and animations follow `prefers-reduced-motion`.

= Developers =

Every site-specific behaviour goes through filters (`geofolio_defaults`, `geofolio_type_catalog`, `geofolio_icons`, `geofolio_import_columns`, `geofolio_geocoder_url`, `geofolio_place_slug`, `geofolio_migration_steps` and more), documented in `docs/hooks.md` on GitHub. The map is restyled by overriding `--gfo-*` CSS custom properties. The REST API is documented in `docs/rest-api.md`.

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
3. Add the map to a page with the **Geofolio Map** block (WordPress 6.6 or later), the **Geofolio** Elementor widget, or the `[geofolio]` shortcode.
4. Optional: in **Places → Map settings**, choose the basemap and set the tile provider API key.

== Frequently Asked Questions ==

= Which basemaps are available? =

Positron via OpenFreeMap, OpenStreetMap and IGN (France) maps work without a key. CARTO, Jawg, MapTiler, Stadia and Thunderforest maps need an API key, set in **Map settings** or with the `GEOFOLIO_TILE_API_KEY` constant. Without a valid key, the map falls back to Positron via OpenFreeMap, which looks the same and needs no key.

= Which CSV columns are recognised? =

Name, Type, Type icon, Service, Accessibility, Entity, Entity colour, Description, Audience, Capacity, Address, Postal code, City, Region, Department, Latitude, Longitude, Phone, Email, Website, Manager, Opening hours, in English or French, accents and case ignored. Services and accessibility accept several values separated by `;`. Rows without coordinates are geocoded.

= Can I use another geocoder? =

Yes: the `geofolio_geocoder_url` filter points the import to any GeoJSON geocoder (for example Nominatim with `format=geojson`).

= Which shortcode attributes are available? =

`height` (a CSS length such as `600px` or `80vh`, or `container` to let your stylesheet size the map), `center_lat`, `center_lng`, `zoom`, `fit_bounds` (`false` keeps the centre and zoom instead of zooming to the places), `show_search`, `show_filter`, `show_list`, `show_fullscreen`, `sidebar_position` (`left` or `right`), `sidebar_title`, `sidebar_subtitle`, `tile_style`.

= Can I use my own colours, font and names? =

Yes, without code: **Places → Map settings → Appearance** sets the main and accent colours, the font (the bundled Poppins or your theme's font) and the corner radius for every map; **Labels and defaults** renames places and entities in the admin, changes the URL slug of places and sets the default title, centre and zoom of new maps. Developers can do the same with filters.

= Do I need Elementor? =

No. The block and the shortcode work on any theme. The Elementor widget is added only when Elementor is active.

= Why do CARTO maps show "API key required"? =

Since 2026, CARTO serves its basemaps only with a key. Enter one in **Places → Map settings**, or keep the default Positron basemap served by OpenFreeMap, which looks the same and needs no key.

= How do I try it quickly? =

In **Places → Import CSV**, import the sample dataset: 16 fictional places with photos, types, entities and opening hours. Then add the block to a page.

= How many places can it handle? =

The map loads all published places once and filters them in the browser, which keeps search instant. Markers are clustered and built once per place, and the REST responses are cached and refreshed as soon as a place changes. Several hundred places work comfortably; for many thousands, test on your hosting.

= Is it GDPR friendly? =

Geofolio sets no cookie, has no analytics and sends nothing to its author. The only third party reached by visitors is the tile provider you choose (see External services). Address geocoding happens only in the admin.

= Can I translate it? =

Yes. The source strings are in English with a French translation included; other languages can be added with any .po editor, and through translate.wordpress.org once the plugin is in the directory.

== Screenshots ==

1. The map with its sidebar, entity pills, type icons and clusters.
2. A place popup with its photo carousel, contact details and opening hours.
3. Search with suggestions, insensitive to accents and case.
4. On a phone: search and type filter above the map, entity pills below them.
5. Editing a place: a form with the location (geocoding and a draggable marker), description, contact details, people and photos.
6. Map settings: basemap forced for the whole site and provider API key.
7. The Geofolio Map block in the editor, with its live preview and settings.
8. The Elementor widget and its settings, with the live map in the Elementor editor.

== Changelog ==

= 1.4.1 =
* Fix: the place popup no longer repeats the postal code and the city when the address already contains them.

= 1.4.0 =
* New: Appearance settings (colours, font, corner radius) and Labels settings (names, URL slug, default map texts).
* New: import from a previous map plugin, described by a filter: data renamed in place, icons matched to types, managers converted to people, Elementor widgets and shortcodes rewritten, after a confirmation screen and a snapshot.
* New: plugin icon and admin menu icon.
* Fix: a REST request with `lat` or `lng` caused a fatal error on PHP 8.
* Fix: icon matching during the import ignores accents and case.

= 1.3.0 =
* New: Geofolio Map block for the block editor, with the real map as preview and all display settings.
* New: the place edit screen is a form (location, description, contact, people, photos) instead of the article editor.
* New: several people per place, each with a role and a name.
* Fix: the location map in the admin could draw only a corner of its tiles.
* Fix: search suggestions no longer split the highlighted word.
* Plugin directory page: readme, icon, banner, screenshots and Live Preview.

= 1.2.1 =
* Fix: every single post or page crashed (fatal error) on sites without Elementor.
* Faster filtering: map markers are built once and reused.
* Internal: stylesheet and map script split into sources, no visible change.

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

= 1.4.0 =
Adds Appearance and Labels settings, an import from a previous map plugin, and fixes a fatal error on PHP 8 with location queries.

= 1.3.0 =
Adds the Geofolio Map block (WordPress 6.6+) and a simpler place edit form with people and roles. Existing managers are kept.

= 1.2.1 =
Critical fix for sites without Elementor: single posts and pages no longer crash. Update now.

= 1.2.0 =
Fixes the unreachable settings and import pages. Elementor widget: tablet and mobile now keep their own map height (600px and 85vh by default) instead of the desktop height; set them in the widget if needed.
