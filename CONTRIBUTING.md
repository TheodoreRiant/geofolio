# Contributing to Mapped Places

## Setup

The plugin runs without any build: PHP and JavaScript are served as they are. Node and Composer are only development tools.

```bash
git clone <repository>
cd mapped-places
npm ci                  # esbuild, ESLint, Stylelint
composer install        # optional: PHPUnit 9.6 and PHPCS with the WordPress standard
# or, without Composer:
curl -sSLo phpunit.phar https://phar.phpunit.de/phpunit-11.phar   # ignored by git
```

To try it in a real WordPress: `docker compose up -d`, then http://localhost:8080 (admin / admin). WordPress is installed with Elementor and the sample dataset, and the repository is mounted live in `wp-content/plugins/mapped-places`.

## Tests

Run both suites before every commit:

```bash
php phpunit.phar                 # PHPUnit, in-memory WordPress stubs (tests/stubs/) — or: composer test
node --test tests/js/*.test.js   # ES modules of assets/js/src/, imported directly — or: npm test
npm run lint                     # ESLint (blocking in CI) and Stylelint
composer lint                    # PHPCS, WordPress standard (warnings only in CI)
```

- Write the test first (it must fail), then the code.
- Testable logic lives in pure functions: static methods in PHP, stateless functions in JS modules (`assets/js/src/*.mjs`), which Node tests import directly (`tests/js/modules.js`).
- Every HTML output escapes its data (`escHtml` / `escAttr` in JS, `esc_html` / `esc_attr` / `esc_url` in PHP). The REST API returns plain text.
- Every displayed string is translatable: English source strings, domain `mapped-places`; in JS, through `mappedPlacesConfig.i18n`. Regenerate `languages/mapped-places.pot` with `wp i18n make-pot`, complete `mapped-places-fr_FR.po`, then `msgfmt -o languages/mapped-places-fr_FR.mo languages/mapped-places-fr_FR.po`. These files stay in the repository (they seed translate.wordpress.org) but are excluded from the archive: WordPress loads language packs itself, so the plugin has no `load_plugin_textdomain()` call and no `Domain Path` header.

## Map script

`assets/js/mapped-places.js` is **built** by esbuild from the ES modules in `assets/js/src/` (`index.mjs` is the entry; the `MappedPlacesMap` class lives in `map.mjs`, with its autocomplete, carousel and marker methods in `map-*.mjs`). Edit the modules, then run `npm run build:js`; a Node test fails when the built file is out of date. The output is an unminified IIFE, readable in the browser.

## Stylesheet

`assets/css/mapped-places.css` is **built**: edit the partials in `assets/css/src/` (numbered in cascade order), then run `npm run build:css`. A Node test fails when the built file is out of date. Keep `!important` for the plugin's buttons and the fullscreen mode only (see the header of `01-tokens.css`).

## Commits

`type: description` (types: `feat`, `fix`, `refactor`, `docs`, `test`, `chore`, `perf`, `ci`). The body explains the **cause** and the **why**, not only the what.

## Release

1. Version in `mapped-places.php` (header and `MAPPED_PLACES_VERSION`) and `Stable tag` in `readme.txt`.
2. `CHANGELOG.md` and the `== Changelog ==` section of `readme.txt`.
3. Annotated tag `vX.Y.Z`, push, then a GitHub release with `git archive --prefix=mapped-places/ -o mapped-places.zip vX.Y.Z` (`.gitattributes` excludes development files).
4. Pushing the tag also builds the Docker image and, once the plugin is in the WordPress.org directory, deploys it there (`.github/workflows/wporg-deploy.yml`). Readme and directory assets (`.wordpress-org/`: icon, banner, screenshots, Playground blueprint) are synced from `main` without a release (`wporg-assets.yml`).

Both WordPress.org workflows stay inactive until the repository variable `WPORG_DEPLOY` is `true` and the secrets `SVN_USERNAME` and `SVN_PASSWORD` are set.
