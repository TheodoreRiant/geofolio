# Hooks

Everything specific to a site goes through these filters and actions, usually from a companion plugin.

## Map

| Hook | Receives | Returns |
|---|---|---|
| `geofolio_defaults` | default map attributes (height, centre, zoom, texts…) | modified values (keys cannot be removed) |
| `geofolio_default_color` | `#1F4E79` | fallback colour of markers and badges |
| `geofolio_shortcode_tags` | `['geofolio']` | shortcodes that display the map (e.g. an old name) |
| `geofolio_elementor_widget_names` | `['geofolio_map']` | widget names that display the map |
| `geofolio_assets_enqueued` (action) | — | runs when map assets are enqueued; enqueue a stylesheet depending on `geofolio` |

## Types and icons

| Hook | Receives | Returns |
|---|---|---|
| `geofolio_icons` | icon key => SVG shapes | the icon library (keys: lowercase, digits, dashes) |
| `geofolio_type_icon` | `'pin'`, term slug, term name | icon key for a type without an icon meta |
| `geofolio_type_label` | term name, term | displayed label |
| `geofolio_type_catalog` | described types, in term order | reordered or modified catalogue |

## Content and URLs

| Hook | Receives | Returns |
|---|---|---|
| `geofolio_place_slug` | `'places'` | URL slug of places |
| `geofolio_taxonomy_slugs` | taxonomy => slug | URL slugs of taxonomy archives |
| `geofolio_place_labels` | post type labels | modified labels (e.g. “Offices”) |
| `geofolio_place_supports` | `['title', 'thumbnail']` | post type supports; add `'editor'` to bring back the full content editor on the place screen |

## People

| Hook | Receives | Returns |
|---|---|---|
| `geofolio_default_person_role` | “Manager” | role given to names stored in the legacy `manager` field (places not yet edited with the People section) |
| `geofolio_people_roles` | suggested roles (Director, Manager, Secretary general…) | roles proposed in the Role field; any text stays accepted |

## Import

| Hook | Receives | Returns |
|---|---|---|
| `geofolio_import_columns` | normalised column => field | recognised columns |
| `geofolio_import_entity` | entity from the CSV column (or `null`), row fields | entity name or `['slug' => …, 'name' => …]` |
| `geofolio_import_region` | `''`, department, row fields | region name |
| `geofolio_default_dataset` | path of the sample dataset | CSV imported by “Import the preset places” |
| `geofolio_geocoder_url` | French address API URL | a GeoJSON geocoder URL |
| `geofolio_import_content_labels` | `['description' => '', 'capacity' => 'Capacity:']` | labels written before the description and capacity in imported content |

`data/regions-fr.php` maps French departments to regions, for use with `geofolio_import_region`.

## Migrations

| Hook | Receives | Returns |
|---|---|---|
| `geofolio_migration_steps` | `[]` | `Geofolio\Migration\Step` instances, run in order, once each (the admin button reruns them all: make them idempotent) |

`Geofolio\Plugin::request_rewrite_flush()` asks for rewrite rules to be regenerated on the next request.

### Import from a previous map plugin

| Hook | Receives | Returns |
|---|---|---|
| `geofolio_legacy_import` | `[]` | Description of a previous map plugin whose data Geofolio should take over (keys below). When it is set and the old plugin left data (one of its options, or posts of its post type), the step `geofolio_legacy_import` runs first among the migrations. |

Keys, all optional: `post_type` (old post type), `taxonomies` (old => Geofolio taxonomy), `post_meta` (old meta key => field of `FieldRegistry`), `term_meta` (old => `_gfo_color` or `_gfo_icon`), `options` (old => `geofolio_*` option), `elementor_widgets` (old widget names, renamed to `geofolio_map` in `_elementor_data`, revisions included), `shortcodes` (old tags, renamed to `[geofolio`), `delete_post_meta` (old meta keys with no Geofolio field, deleted from the imported places only), `type_icons` (type name => icon key, exact then partial match ignoring accents, case and typographic apostrophes, for types without an icon), `manager_role` (role given to people converted from the old manager field), `place_slug`, `labels` and `appearance` (values of the Labels and Appearance settings, used only where the site has not set them), `plugin` (old plugin file, deactivated at the end). Nothing specific to a site lives in the core: a companion plugin describes its own predecessor.

```php
add_filter('geofolio_legacy_import', function () {
    return array(
        'post_type'         => 'old_place',
        'taxonomies'        => array('old_type' => 'gfo_type'),
        'post_meta'         => array('_old_city' => 'city'),
        'elementor_widgets' => array('old_map'),
        'shortcodes'        => array('old-map'),
        'type_icons'        => array('library' => 'book'),
        'plugin'            => 'old-map/old-map.php',
    );
});
```
