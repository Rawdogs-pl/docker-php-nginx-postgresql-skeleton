FROM php:8.3-fpm

RUN apt-get update && apt-get install -y zip libpq-dev && docker-php-ext-install pgsql pdo_pgsql sysvsem
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# plik nie moze nazywac sie www.conf bo nadpisze cala konfiguracje, a chcemy tylko kilka parametrow nadpisac
ADD www2.conf /usr/local/etc/php-fpm.d/

WORKDIR /var/www