#!/bin/bash
# Run the first-start initialisation in the background, then the official
# WordPress entrypoint (prepares /var/www/html and wp-config.php, starts Apache).
set -e
mapped-places-init.sh &
exec docker-entrypoint.sh "$@"
