FROM php:8.3-fpm

RUN apt-get update && apt-get install -y zip libpq-dev cron vim tzdata && docker-php-ext-install pgsql pdo_pgsql sysvsem
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN echo "Europe/Warsaw" > /etc/timezone && \
    ln -sf /usr/share/zoneinfo/Europe/Warsaw /etc/localtime && \
    dpkg-reconfigure -f noninteractive tzdata

RUN echo "date.timezone=Europe/Warsaw" > /usr/local/etc/php/conf.d/timezone.ini


# plik nie moze nazywac sie www.conf bo nadpisze cala konfiguracje, a chcemy tylko kilka parametrow nadpisac
ADD www2.conf /usr/local/etc/php-fpm.d/

WORKDIR /var/www
