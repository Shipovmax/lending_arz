FROM php:8.3-fpm

ADD https://github.com/mlocati/docker-php-extension-installer/releases/download/2.7.14/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions \
 && install-php-extensions gd mysqli opcache zip intl bcmath exif \
 && apt-get update && apt-get install -y --no-install-recommends curl unzip && rm -rf /var/lib/apt/lists/*

COPY bitrix.ini /usr/local/etc/php/conf.d/zz-bitrix.ini
