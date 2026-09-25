# Hooks

Everything specific to a site goes through these filters and actions, usually from a companion plugin.

## Map

| Hook | Receives | Returns |
|---|---|---|
| `mapped_places_defaults` | default map attributes (height, centre, zoom, texts…) | modified values (keys cannot be removed) |
| `mapped_places_default_color` | `#1F4E79` | fallback colour of markers and badges |
| `mapped_places_shortcode_tags` | `['mapped-places']` | shortcodes that display the map (e.g. an old name) |
| `mapped_places_elementor_widget_names` | `['mapped_places_map']` | widget names that display the map |
| `mapped_places_assets_enqueued` (action) | — | runs when map assets are enqueued; enqueue a stylesheet depending on `mapped-places` |

## Types and icons

| Hook | Receives | Returns |
|---|---|---|
| `mapped_places_icons` | icon key => SVG shapes | the icon library (keys: lowercase, digits, dashes) |
| `mapped_places_type_icon` | `'pin'`, term slug, term name | icon key for a type without an icon meta |
| `mapped_places_type_label` | term name, term | displayed label |
| `mapped_places_type_catalog` | described types, in term order | reordered or modified catalogue |

## Content and URLs

| Hook | Receives | Returns |
|---|---|---|
| `mapped_places_place_slug` | `'places'` | URL slug of places |
| `mapped_places_taxonomy_slugs` | taxonomy => slug | URL slugs of taxonomy archives |
| `mapped_places_place_labels` | post type labels | modified labels (e.g. “Offices”) |
| `mapped_places_place_supports` | `['title', 'thumbnail']` | post type supports; add `'editor'` to bring back the full content editor on the place screen |

## People

| Hook | Receives | Returns |
|---|---|---|
| `mapped_places_default_person_role` | “Manager” | role given to names stored in the legacy `manager` field (places not yet edited with the People section) |
| `mapped_places_people_roles` | suggested roles (Director, Manager, Secretary general…) | roles proposed in the Role field; any text stays accepted |

## Import

| Hook | Receives | Returns |
|---|---|---|
| `mapped_places_import_columns` | normalised column => field | recognised columns |
| `mapped_places_import_entity` | entity from the CSV column (or `null`), row fields | entity name or `['slug' => …, 'name' => …]` |
| `mapped_places_import_region` | `''`, department, row fields | region name |
| `mapped_places_default_dataset` | path of the sample dataset | CSV imported by “Import the preset places” |
| `mapped_places_geocoder_url` | French address API URL | a GeoJSON geocoder URL |
| `mapped_places_import_content_labels` | `['description' => '', 'capacity' => 'Capacity:']` | labels written before the description and capacity in imported content |

`data/regions-fr.php` maps French departments to regions, for use with `mapped_places_import_region`.

## Migrations

| Hook | Receives | Returns |
|---|---|---|
| `mapped_places_migration_steps` | `[]` | `MappedPlaces\Migration\Step` instances, run in order, once each (the admin button reruns them all: make them idempotent) |

`MappedPlaces\Plugin::request_rewrite_flush()` asks for rewrite rules to be regenerated on the next request.

### Import from a previous map plugin

| Hook | Receives | Returns |
|---|---|---|
| `mapped_places_legacy_import` | `[]` | Description of a previous map plugin whose data Mapped Places should take over (keys below). When it is set and the old plugin left data (one of its options, or posts of its post type), the step `mapped_places_legacy_import` runs first among the migrations. |

Keys, all optional: `post_type` (old post type), `taxonomies` (old => Mapped Places taxonomy), `post_meta` (old meta key => field of `FieldRegistry`), `term_meta` (old => `_mapl_color` or `_mapl_icon`), `options` (old => `mapped_places_*` option), `elementor_widgets` (old widget names, renamed to `mapped_places_map` in `_elementor_data`, revisions included, one row at a time), `shortcodes` (old tags, renamed to `[mapped-places`), `blocks` (old block names, renamed to `mapped-places/map` in the post content, attributes kept), `delete_post_meta` (old meta keys with no Mapped Places field, deleted from the imported places only), `type_icons` (type name => icon key, exact then partial match ignoring accents, case and typographic apostrophes, for types without an icon), `manager_role` (role given to people converted from the old manager field), `place_slug`, `labels` and `appearance` (values of the Labels and Appearance settings, used only where the site has not set them), `plugin` (old plugin file, deactivated at the end). Nothing specific to a site lives in the core: a companion plugin describes its own predecessor.

The core describes one predecessor itself: **Geofolio 1.x, its former name** (`MappedPlaces\Migration\Legacy\Geofolio`, hooked at priority 5). When the site still holds a `geofolio_settings`, `geofolio_appearance` or `geofolio_labels` option, or a post of type `gfo_place`, and no companion has described another plugin, the import renames `gfo_place`, the `gfo_*` taxonomies, the `_gfo_*` post and term meta, moves those three options, renames the `geofolio_map` widgets, the `[geofolio]` shortcode and the `geofolio/map` block, then deactivates `geofolio/geofolio.php`. It waits for the administrator's confirmation on the Places screens like any legacy import. The `GEOFOLIO_TILE_API_KEY` constant is still honoured by the Map settings.

```php
add_filter('mapped_places_legacy_import', function () {
    return array(
        'post_type'         => 'old_place',
        'taxonomies'        => array('old_type' => 'mapl_type'),
        'post_meta'         => array('_old_city' => 'city'),
        'elementor_widgets' => array('old_map'),
        'shortcodes'        => array('old-map'),
        'type_icons'        => array('library' => 'book'),
        'plugin'            => 'old-map/old-map.php',
    );
});
```
