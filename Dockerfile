FROM php:8.3-apache

RUN apt-get update && apt-get install -y libpq-dev && \
    docker-php-ext-install pdo pdo_pgsql && \
    a2dismod mpm_event && \
    a2enmod mpm_prefork rewrite

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

EXPOSE 80
