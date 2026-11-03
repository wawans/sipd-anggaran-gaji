#!/bin/sh
set -e

# Pastikan direktori writable milik www-data (bind mount dari host)
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

exec "$@"
