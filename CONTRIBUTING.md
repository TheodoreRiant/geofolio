# Contributing to Geofolio

## Setup

The plugin runs without any build: PHP and JavaScript are served as they are. Node and Composer are only development tools.

```bash
git clone <repository>
cd geofolio
npm ci                  # esbuild, ESLint, Stylelint
composer install        # optional: PHPUnit 9.6 and PHPCS with the WordPress standard
# or, without Composer:
curl -sSLo phpunit.phar https://phar.phpunit.de/phpunit-11.phar   # ignored by git
```

To try it in a real WordPress: `docker compose up -d`, then http://localhost:8080 (admin / admin). WordPress is installed with Elementor and the sample dataset, and the repository is mounted live in `wp-content/plugins/geofolio`.

## Tests

Run both suites before every commit:

```bash
php phpunit.phar                 # PHPUnit, in-memory WordPress stubs (tests/stubs/) — or: composer test
node --test tests/js/*.test.js   # pure functions extracted from assets/js/geofolio.js — or: npm test
npm run lint                     # ESLint (blocking in CI) and Stylelint
composer lint                    # PHPCS, WordPress standard (warnings only in CI)
```

- Write the test first (it must fail), then the code.
- Testable logic lives in pure functions: static methods in PHP, stateless functions in JS. Node tests extract JS functions from the source file using comment markers (`/* ---- NAME : début ---- */`): do not rename them without updating the test.
- Every HTML output escapes its data (`escHtml` / `escAttr` in JS, `esc_html` / `esc_attr` / `esc_url` in PHP). The REST API returns plain text.
- Every displayed string is translatable: English source strings, domain `geofolio`; in JS, through `geofolioConfig.i18n`. Regenerate `languages/geofolio.pot` with `wp i18n make-pot`.

## Commits

`type: description` (types: `feat`, `fix`, `refactor`, `docs`, `test`, `chore`, `perf`, `ci`). The body explains the **cause** and the **why**, not only the what.

## Release

1. Version in `geofolio.php` (header and `GEOFOLIO_VERSION`) and `Stable tag` in `readme.txt`.
2. `CHANGELOG.md` and the `== Changelog ==` section of `readme.txt`.
3. Annotated tag `vX.Y.Z`, push, then a GitHub release with `git archive --prefix=geofolio/ -o geofolio.zip vX.Y.Z` (`.gitattributes` excludes development files).
