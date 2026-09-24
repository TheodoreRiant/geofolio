#!/bin/bash
# First start: install WordPress, activate Elementor, hello-elementor and
# Geofolio, import the sample dataset and create a map page, then run the
# scripts found in /docker-init.d/ (a companion can add its own setup there).
# Idempotent: does nothing if WordPress is already installed.
set -u

WP="wp --allow-root --path=/var/www/html"
URL="${GEOFOLIO_SITE_URL:-http://localhost:8080}"
log() { echo "[geofolio-init] $*"; }

# Wait for wp-config.php (created by the official entrypoint) and the database.
# No MySQL client in the image: connection test in PHP.
db_ready() {
    php -r '
        mysqli_report(MYSQLI_REPORT_OFF);
        $h = explode(":", getenv("WORDPRESS_DB_HOST") ?: "db");
        $c = @new mysqli($h[0], getenv("WORDPRESS_DB_USER"), getenv("WORDPRESS_DB_PASSWORD"),
                         getenv("WORDPRESS_DB_NAME"), (int) ($h[1] ?? 3306));
        exit($c->connect_errno ? 1 : 0);'
}
ready=0
for i in $(seq 1 90); do
    if [ -f /var/www/html/wp-config.php ] && db_ready; then ready=1; break; fi
    sleep 2
done
if [ "$ready" -ne 1 ]; then
    log "FAILED: wp-config.php or database unavailable after 3 minutes."
    exit 1
fi

if $WP core is-installed 2>/dev/null; then
    log "WordPress already installed: nothing to do."
    exit 0
fi

log "Installing WordPress on $URL"
$WP core install --url="$URL" --title="${GEOFOLIO_SITE_TITLE:-Geofolio (local)}" \
    --admin_user="${GEOFOLIO_ADMIN_USER:-admin}" --admin_password="${GEOFOLIO_ADMIN_PASSWORD:-admin}" \
    --admin_email="dev@example.test" --skip-email
LOCALE="${GEOFOLIO_LOCALE:-en_US}"
if [ "$LOCALE" != "en_US" ]; then
    $WP language core install "$LOCALE" --activate >/dev/null 2>&1 || log "Translation $LOCALE unavailable (offline?): admin in English."
fi
$WP rewrite structure '/%postname%/' >/dev/null   # .htaccess provided by the image
$WP theme activate hello-elementor >/dev/null
$WP plugin activate elementor geofolio ${GEOFOLIO_EXTRA_PLUGINS:-}

if [ "${GEOFOLIO_SAMPLE_DATA:-1}" != "0" ]; then
    log "Importing the sample dataset"
    $WP eval '
        wp_set_current_user(1);
        $importer = \Geofolio\Import\Importer::get_instance();
        $dataset  = \Geofolio\Import\Importer::default_dataset();
        $result   = $importer->import_csv($dataset, true, true, dirname($dataset) . "/photos");
        echo "  " . $result["imported"] . " places imported\n";
    '
    log "Map page (Elementor)"
    $WP eval-file /usr/local/bin/geofolio-pages.php
fi

for script in /docker-init.d/*.sh; do
    [ -f "$script" ] || continue
    log "Running $(basename "$script")"
    WP="$WP" bash "$script"
done

# WP-CLI runs as root: give the created files (translations, uploads) to Apache.
chown -R www-data:www-data /var/www/html/wp-content

log "Ready: $URL (map) · $URL/wp-admin (${GEOFOLIO_ADMIN_USER:-admin} / ${GEOFOLIO_ADMIN_PASSWORD:-admin})"
