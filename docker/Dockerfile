# Development / acceptance environment for Geofolio.
#
# PHP 8.0, WordPress 7.1.2, hello-elementor theme, Elementor 4.3.1. On first
# start, WordPress is installed with Geofolio and its sample dataset (see
# docker/init.sh); a companion can add its own setup in /docker-init.d/.
#
# PHP 8.0: only the frozen official image "wordpress:php8.0-apache" exists;
# its WordPress core is replaced by the version below.

ARG PHP_VERSION=8.0
FROM wordpress:php${PHP_VERSION}-apache

ARG WP_VERSION=7.1.2
ARG ELEMENTOR_VERSION=4.3.1
ARG GEOFOLIO_VERSION=dev

LABEL org.opencontainers.image.title="Geofolio — WordPress environment" \
      org.opencontainers.image.description="WordPress ${WP_VERSION} + Geofolio ${GEOFOLIO_VERSION}, with a sample dataset" \
      org.opencontainers.image.source="https://github.com/TheodoreRiant/geofolio" \
      org.opencontainers.image.version="${GEOFOLIO_VERSION}" \
      org.opencontainers.image.licenses="GPL-2.0-or-later"

# WordPress core + WP-CLI + hello-elementor theme + Elementor.
# Files of the official image, kept: wp-config-docker.php (reads the
# WORDPRESS_DB_* variables, the entrypoint turns it into wp-config.php) and
# .htaccess (URL rewriting, otherwise permalinks return 404).
# The official image copies /usr/src/wordpress to /var/www/html on first start.
RUN set -eux; \
    cp /usr/src/wordpress/wp-config-docker.php /usr/src/wordpress/.htaccess /tmp/; \
    rm -rf /usr/src/wordpress; \
    curl -fsSL "https://wordpress.org/wordpress-${WP_VERSION}.tar.gz" | tar -xz -C /usr/src; \
    mv /tmp/wp-config-docker.php /tmp/.htaccess /usr/src/wordpress/; \
    curl -fsSLo /tmp/hello.zip https://downloads.wordpress.org/theme/hello-elementor.latest-stable.zip; \
    php -r '$z=new ZipArchive; $z->open("/tmp/hello.zip"); $z->extractTo("/usr/src/wordpress/wp-content/themes"); $z->close();'; \
    rm /tmp/hello.zip; \
    curl -fsSLo /tmp/elementor.zip "https://downloads.wordpress.org/plugin/elementor.${ELEMENTOR_VERSION}.zip"; \
    php -r '$z=new ZipArchive; $z->open("/tmp/elementor.zip"); $z->extractTo("/usr/src/wordpress/wp-content/plugins"); $z->close();'; \
    rm /tmp/elementor.zip; \
    curl -fsSLo /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar; \
    chmod +x /usr/local/bin/wp; \
    chown -R www-data:www-data /usr/src/wordpress

# The plugin as shipped (same scope as the release ZIP).
COPY --chown=www-data:www-data assets/     /usr/src/wordpress/wp-content/plugins/geofolio/assets/
COPY --chown=www-data:www-data src/        /usr/src/wordpress/wp-content/plugins/geofolio/src/
COPY --chown=www-data:www-data views/      /usr/src/wordpress/wp-content/plugins/geofolio/views/
COPY --chown=www-data:www-data data/       /usr/src/wordpress/wp-content/plugins/geofolio/data/
COPY --chown=www-data:www-data languages/  /usr/src/wordpress/wp-content/plugins/geofolio/languages/
COPY --chown=www-data:www-data geofolio.php readme.txt /usr/src/wordpress/wp-content/plugins/geofolio/

# Browser cookies are shared by every port of "localhost": those of other
# local projects quickly exceed Apache's limit (8 KB per header) and the site
# answers "400 Bad Request". Limit raised to 64 KB.
RUN printf 'LimitRequestFieldSize 65536\nLimitRequestLine 65536\n' \
        > /etc/apache2/conf-enabled/large-headers.conf

RUN mkdir -p /docker-init.d

COPY docker/entrypoint.sh /usr/local/bin/geofolio-entrypoint.sh
COPY docker/init.sh /usr/local/bin/geofolio-init.sh
COPY docker/pages.php /usr/local/bin/geofolio-pages.php
RUN chmod +x /usr/local/bin/geofolio-entrypoint.sh /usr/local/bin/geofolio-init.sh

ENTRYPOINT ["geofolio-entrypoint.sh"]
CMD ["apache2-foreground"]
