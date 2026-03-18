#!/usr/bin/env sh
set -e

# Ensure Laravel writable directories exist.
# Named volumes override build-time permissions, so we must fix perms at runtime.
mkdir -p /app/storage/logs \
  /app/storage/framework/views \
  /app/storage/framework/cache \
  /app/storage/framework/sessions \
  /app/storage/app/private \
  /app/storage/app/private/datasets \
  /app/storage/app/private/temp \
  /app/bootstrap/cache

# php-fpm runs as 'nobody' (see Dockerfile). Make dirs writable for dev.
# Using chmod 777 is acceptable for local dev; for production use stricter perms.
chmod -R 777 /app/storage /app/bootstrap/cache || true

exec "$@"

