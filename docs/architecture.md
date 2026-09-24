# Architecture

WordPress plugin with no build step: PHP on the server, JavaScript (jQuery + Leaflet) served as is. Code under `src/` follows PSR-4 (namespace `Geofolio\`), loaded by a small autoloader in `src/autoload.php`.

## Data flow

1. **Storage**: places are `gfo_place` posts. Their fields are post metas whose keys come from `Domain\FieldRegistry` (`_gfo_address`, `_gfo_latitude`…), and their classifications are taxonomies (`gfo_type`, `gfo_entity`…) named in `Domain\Schema`.
2. **Editing**: `Admin\MetaBoxes` writes the fields through the registry's sanitisers; the same sanitisers apply to REST writes (block editor), registered by `Plugin::register_place_meta()`.
3. **Rendering**: the shortcode and the Elementor widget both call `Map\Renderer`, which prepares typed values and includes `views/map.php`. Assets are only enqueued on pages that show a map.
4. **Data loading**: `assets/js/geofolio.js` fetches `geofolio/v1/places` once, then filters client-side (search, type, entities). The response also carries the type catalogue (labels and icons), so the script hard-codes nothing site-specific.
5. **Popup details**: opening a popup fetches `geofolio/v1/places/{id}` for the gallery.

## Main classes

| Class | Role |
|---|---|
| `Plugin` | Bootstrap: components, assets, script configuration, REST meta |
| `Map\Defaults` | Default map values (`geofolio_defaults`) and fallback colour |
| `Map\Renderer` | Map HTML shared by the shortcode and the widget |
| `Map\TileProviders` | Basemap registry and key / URL fallbacks |
| `Domain\Schema` | Stored identifiers: post type, taxonomies, term metas, URL slugs |
| `Domain\FieldRegistry` | Place fields: meta keys, sanitisation, form names |
| `Domain\Icons` | Type icon library and resolution (term meta, filter, pin) |
| `Rest\PlacesController` / `PlaceMapper` | REST routes and the public shape of a place |
| `Import\Importer` / `CsvMapping` | CSV import, column recognition, geocoding |
| `Migration\Runner` | Data migrations provided by filter, with snapshot and log |
| `Elementor\MapWidget` | Elementor widget and its controls |

## Technical choices

- **No CDN**: Leaflet 1.9.4, MarkerCluster 1.4.1, MapLibre GL 3.6.2 and its Leaflet bridge are bundled in `assets/vendor/` (inventory and hashes in `VERSIONS.md`).
- **Plain text API**: the REST API returns decoded plain text; the script escapes everything it displays.
- **Neutral styling**: colours and fonts are `--gfo-*` custom properties on `:root` and `.gfo-map-container`; derived properties (clusters, popup) are declared on the container so that an override reaches them.
- **Extension over configuration**: site-specific behaviour is provided by filters (see [hooks](hooks.md)), typically from a companion plugin.
