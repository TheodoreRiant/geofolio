# Mapped Places

Interactive map of places for WordPress: a searchable, filterable map with a synchronised list, rich popups, available as a **Gutenberg block**, an **Elementor widget** and a shortcode. Data lives in WordPress (a *Place* post type with taxonomies), so editors manage it like any other content.

WordPress 6.5+ (block: 6.6+) · PHP 7.4+ · Licence GPLv2 or later · No build needed to run it, no external CDN.

> Documentation: [admin guide](docs/admin-guide.md), [architecture](docs/architecture.md), [REST API](docs/rest-api.md), [hooks](docs/hooks.md). `readme.txt` is the WordPress.org readme; history is in [CHANGELOG.md](CHANGELOG.md).

## Features

**On the map**
- Leaflet map with marker clusters, coloured by entity.
- **Entity pills**: one click isolates an entity, further clicks add or remove one, “Show all” resets.
- **Type filter** with faceted counts (each type shows how many results it would give).
- **Search with suggestions**, insensitive to case, accents and apostrophes.
- Place list synchronised with the map; responsive layout with a mobile drawer; keyboard accessible.
- **Popup**: photo carousel (featured image, then gallery), people (role and name), audience, address, contact, services, opening hours, accessibility, website.

**In the admin**
- *Places* post type edited in a **form** (no article editor): location with geocoding and a draggable marker, short description, contact details, **people with a role and a name**, sortable photo gallery.
- **Duplicate a place** (list and edit screen), as a draft.
- Taxonomies: entity (with a colour), type (with an icon), region, service, accessibility.
- **Map settings**: API key for tile providers, basemap forced on every map.
- **CSV import** with English or French column names, and a sample dataset.
- **Gutenberg block** *Mapped Places Map* with a live map preview in the editor.
- **Elementor widget** with display and style controls.

## Installation

1. Download `mapped-places.zip` from the [latest release](../../releases/latest).
2. **Plugins → Add New → Upload Plugin**, then activate.
3. Add places (menu **Places**) or import a CSV (**Places → Import CSV**, or the sample dataset).
4. Add the map to a page: the **Mapped Places Map** block, the **Mapped Places** Elementor widget, or the `[mapped-places]` shortcode.

The ZIP from a GitHub release includes the French translation and loads it as long as no language pack is installed; the WordPress.org version relies on language packs (translate.wordpress.org).

Try it without installing anything: the WordPress Playground blueprint in [`.wordpress-org/blueprints/blueprint.json`](.wordpress-org/blueprints/blueprint.json) installs the plugin with the sample dataset.

## Shortcode

```text
[mapped-places height="600px" zoom="6" show_list="true" sidebar_position="left"]
```

| Attribute | Default | Role |
|---|---|---|
| `height` | `600px` | Map height (px, vh, vw, %, rem or em) |
| `center_lat` / `center_lng` | `46.6034` / `1.8883` | Initial centre (used when the map has no place) |
| `zoom` | `6` | Initial zoom |
| `show_search` | `true` | Search field |
| `show_filter` | `true` | Type filter |
| `show_list` | `true` | Place list |
| `show_fullscreen` | `false` | Full-screen button |
| `sidebar_position` | `left` | `left` or `right` |
| `sidebar_title` / `sidebar_subtitle` | “Our locations” / empty | Sidebar header |
| `tile_style` | site setting, then Positron | Basemap (see `src/Map/TileProviders.php`) |

Defaults come from `MappedPlaces\Map\Defaults::all()`, shared by the block, the shortcode and the widget, and can be changed with the `mapped_places_defaults` filter.

## Basemaps

Basemaps are declared in `src/Map/TileProviders.php`. A basemap whose key is missing, or whose URL is invalid, falls back to Positron served by OpenFreeMap (no key, worldwide), never to tiles stamped “API KEY REQUIRED”. Set the key in **Map settings** or with `define('MAPPED_PLACES_TILE_API_KEY', '…')` in `wp-config.php`.

## Extending

Everything site-specific goes through filters: default values, colours, type icons and labels, CSV columns, import rules, URL slugs, admin labels, data migrations. See [docs/hooks.md](docs/hooks.md). A companion plugin can adapt Mapped Places to a site without touching its code.

## Development

```text
mapped-places.php          Entry point: constants and autoloader
src/                  PSR-4 classes, namespace MappedPlaces\
  Map/                Defaults, renderer, shortcode, tile providers
  Domain/             Post type, taxonomies, field registry, icons, schema
  Rest/               REST controller and place mapper
  Admin/              Meta boxes, settings page, duplication
  Import/             CSV importer and column mapping
  Migration/          Migration runner, snapshots, term tools
  Blocks/             Gutenberg block (blocks/map: block.json, editor sources and build)
  Elementor/          Elementor integration and widget
views/map.php         Map template
assets/               CSS, JS, fonts, bundled Leaflet / MapLibre (assets/vendor/VERSIONS.md)
languages/            mapped-places.pot and the French translation
tests/                PHPUnit (in-memory WordPress stubs) and Node tests
```

```bash
curl -sSLo phpunit.phar https://phar.phpunit.de/phpunit-11.phar
php phpunit.phar
node --test tests/js/*.test.js
```

Local WordPress: `docker compose up -d`, then http://localhost:8080 (admin / admin), with the sample dataset and the code mounted live.

## En français

Mapped Places est une extension WordPress de carte interactive des lieux : recherche, filtres par type et par entité, liste synchronisée, fiches détaillées, bloc Gutenberg et widget Elementor. L'interface est traduite en français (`languages/mapped-places-fr_FR.po`). Le shortcode est `[mapped-places]` ; tout ce qui est propre à un site passe par des filtres (voir [docs/hooks.md](docs/hooks.md)).

## Licence

GPLv2 or later. Developed by [AllSide Studio](https://allside.studio).
