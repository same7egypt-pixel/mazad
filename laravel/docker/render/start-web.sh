#!/usr/bin/env sh
set -eu

: "${PORT:?Render must provide PORT for the public Laravel web service.}"
envsubst '$PORT' < /etc/nginx/templates/auction.conf.template > /etc/nginx/conf.d/default.conf

php-fpm -D
exec nginx -g 'daemon off;'
