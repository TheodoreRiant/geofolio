# Admin guide

Everything happens in the **Places** menu of the WordPress admin.

## Add a place

1. **Places → Add**.
2. Enter the **title** and the **description** (shown in the map popup).
3. **Excerpt** (side panel): the audience, for example “24 people”. It is shown after “Audience:”.
4. **Location** box: address, postal code, city, then **Geocode address** to place the marker. You can also click the small map to move it.
5. **Contact** box: phone, email, website, opening hours. An empty field is not shown on the map.
6. **Management** box: manager name(s). Separate several names with a comma: the label becomes plural.
7. Side panel: **Entity** (gives the colour), **Place type**, regions, services, accessibility.
8. **Publish**. A draft does not appear on the map.

## Duplicate a place

For a service available at several addresses: hover the row in the list and click **Duplicate**, or use **Duplicate this place** on the edit screen. The copy opens as a **draft**, with “(copy)” in its title and everything else copied. Change the title and address, geocode, then publish.

## Photos

- **Featured image**: the cover, shown first.
- **Photo gallery** box: add or edit photos, drag and drop to reorder. Photos follow the cover; a photo in both places is shown once.
- Without any photo, the popup shows a “Photo coming soon” image.

## Entities, types and icons

- **Places → Entities**: each entity has a **colour**, used for markers, pills and popups. Changing it here is enough: no update overwrites it.
- **Places → Place types**: each type can have an **icon**, chosen from the library. Without a choice, a companion plugin may suggest one; otherwise, a pin.

On the map, clicking an entity pill **shows only that entity**; further clicks add or remove entities; “Show all” resets.

## Map settings

**Places → Map settings**:

- **Provider API key**, for basemaps marked “key required”. It can also be set with `define('GEOFOLIO_TILE_API_KEY', '…')` in `wp-config.php`.
- **Basemap applied to the site**: forces the same basemap on every map, without reopening each Elementor page.

Without a valid key, the map falls back to Positron served by OpenFreeMap (no key); it never shows an error instead of the tiles.

## CSV import

**Places → Import CSV**. The first line names the columns, in English or French (accents and case ignored):

| Column | Content |
|---|---|
| Name | required |
| Type, Type icon | place type, and its icon key (`book`, `people`, `tool`…) if the type has none yet |
| Entity, Entity colour | entity, and its colour (`#1F7A6B`) if the entity has none yet |
| Description, Audience, Capacity | text of the place; audience is shown after “Audience:” |
| Address, Postal code, City, Region, Department | location; rows without latitude / longitude are geocoded |
| Latitude, Longitude | coordinates |
| Phone, Email, Website, Manager, Opening hours | contact and management |
| Services, Accessibility | several values separated by `;` |
| Image, Gallery | photo file names, for datasets shipped with their photos |

**Import the preset places** imports the sample dataset: 16 fictional places with photos, contacts and opening hours (`data/sample/`).

## Add a map to a page

In Elementor, the **Geofolio** widget (display and style settings in the panel), or the `[geofolio]` shortcode (attributes in the [README](../README.md#shortcode)).
