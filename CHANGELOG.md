# Changelog — Geofolio

Notable changes to Geofolio. Format based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), versions follow [SemVer](https://semver.org/).

## [Unreleased]

### Added
- `uninstall.php`: removes options, transients and migration snapshots; places and taxonomies are deleted only when `GEOFOLIO_UNINSTALL_DATA` is true.
- readme: "External services", "Privacy" and "Source code and development" sections (map tile providers, geocoder, no CDN, no tracking).
- `tests/PluginCheckTest.php`: guards against unescaped output, direct file operations, form data read before nonce verification, undocumented external services.

### Changed
- Import results and the "copy created" notice are carried by a per-user transient instead of URL parameters.
- CSV files are read with `SplFileObject`; temporary files are removed with `wp_delete_file()`.
- Entity colour saved only with a nonce and the `manage_categories` capability.
- Admin notices are dismissible.

### Fixed
- Plugin Check: 39 errors (unescaped `_e()`/`printf`, exception message, `fopen`/`unlink`) and 39 warnings resolved or justified; the archive now passes with 0 errors and 0 warnings.

## [1.1.0] — 2026-09-24

### Added
- Complete sample dataset (`data/sample/`): 16 fictional places with photos from Unsplash (credits in `data/sample/photos/CREDITS.md`), managers, phone numbers from the ranges reserved for fiction, emails, websites, opening hours, services, accessibility, entity colours and type icons.
- CSV import: `Type icon`, `Entity colour`, `Audience`, `Region`, `Accessibility`, `Image` and `Gallery` columns; several values separated by `;` for types, services and accessibility; photos of a trusted dataset added to the media library and reused across places.
- `geofolio_import_content_labels` filter.

### Changed
- Keyless fallback basemap: Positron served by OpenFreeMap (worldwide) instead of the IGN map (France only).
- Imported content no longer contains hard-coded French labels.

## [1.0.0] — 2026-09-24

First release under the Geofolio name, derived from a map plugin built for a single client (version 2.9.0). Everything specific to that client moved to a companion plugin; the history of the 2.x versions stays with it.

### Added
- *Places* post type (`gfo_place`) and taxonomies `gfo_type`, `gfo_region`, `gfo_service`, `gfo_accessibility`, `gfo_entity`; URL slugs and admin labels adjustable by filters.
- Field registry (`Geofolio\Domain\FieldRegistry`): the single source of place meta keys and their sanitisation, shared by meta boxes, REST and import.
- REST API `geofolio/v1`: `places`, `places/{id}`, `filters`, with English keys and a type catalogue (label, icon, SVG path).
- Type icons: a library of 22 generic icons, an icon picker on types, filters for icons, labels and order.
- Generic CSV import: English and French column names, coordinates and contact details read from the file, entity column, filterable geocoder, sample dataset of 12 fictional places.
- Extension filters for defaults, colours, import rules, slugs, labels, migrations, shortcode and widget aliases (see `docs/hooks.md`).
- Generic migration runner with snapshots and a log; steps are provided by filter.
- English source strings and a complete French translation.

### Changed
- PSR-4 code base (`src/`, namespace `Geofolio\`), one class per file, no Composer dependency.
- Shortcode `[geofolio]`, Elementor widget `geofolio_map`, CSS classes `.gfo-*`, custom properties `--gfo-*`, script object `geofolioConfig`.
- Neutral palette and default values; a site restyles the map by overriding `--gfo-*` properties.
- Shared renderer for the shortcode and the widget (a `]` in a title no longer breaks the widget).

### Removed
- Dead Elementor controls (popup link, scrollbar, marker size, map width) and about 900 lines of unused CSS.
- Shortcode attributes `types` and `regions`, never read.
