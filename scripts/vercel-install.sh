#!/usr/bin/env sh
set -eu

npm ci

if command -v composer >/dev/null 2>&1; then
  composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
else
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  php composer-setup.php --install-dir=. --filename=composer
  php composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
  php -r "file_exists('composer-setup.php') && unlink('composer-setup.php');"
fi
