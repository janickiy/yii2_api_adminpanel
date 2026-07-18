#!/bin/sh
set -eu

for writable_dir in \
    /var/www/html/runtime \
    /var/www/html/backend/runtime \
    /var/www/html/backend/web/assets \
    /var/www/html/frontend/runtime \
    /var/www/html/frontend/web/assets \
    /var/www/html/console/runtime
do
    mkdir -p "$writable_dir"
    chmod 0777 "$writable_dir"
done

exec "$@"
