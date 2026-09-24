# Security policy

## Supported versions

Only the latest release receives security fixes.

## Reporting a vulnerability

**Do not open a public issue.** Email **theodore.riant@protonmail.com** with:

- the affected version and the URL or file involved;
- steps to reproduce and the observed impact (exposed data, action possible without permission…);
- if possible, a suggested fix.

You will get an acknowledgement within 3 business days; a fix follows as soon as possible depending on severity, then a mention in the **Security** section of the [CHANGELOG](CHANGELOG.md).

## Scope

- REST routes `geofolio/v1` (public, read-only).
- Admin screens: place editing, duplication, map settings, CSV import.
- Map display (content injection through place data).

Secrets (basemap API keys, hosting credentials) are never committed: the tile key is set in the admin or with the `GEOFOLIO_TILE_API_KEY` constant in `wp-config.php`.
