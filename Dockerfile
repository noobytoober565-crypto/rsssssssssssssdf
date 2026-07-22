FROM php:8.3-fpm

RUN apt-get update && apt-get install -y libpq-dev nginx && \
    docker-php-ext-install pdo pdo_pgsql && \
    rm -rf /var/lib/apt/lists/*

RUN sed -i 's/listen = .*/listen = 9000/' /usr/local/etc/php-fpm.d/www.conf

COPY nginx.conf /etc/nginx/sites-enabled/default

COPY index.php login.php logout.php admin.php api.php db.php /var/www/html/

RUN mkdir -p /var/www/html/uploads && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

EXPOSE 80

CMD sh -c "php-fpm -D && nginx -g 'daemon off;'"
