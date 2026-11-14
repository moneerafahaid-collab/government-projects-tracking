#!/bin/sh
set -e
export USE_SQLITE=1
export SQLITE_PATH="${SQLITE_PATH:-/tmp/hail.sqlite}"
php -S "0.0.0.0:${PORT:-10000}" -t /var/www/html
