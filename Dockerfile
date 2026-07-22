FROM php:8.3-cli

RUN apt-get update && \
    apt-get install -y libpq-dev libpq5 && \
    docker-php-ext-configure pgsql --with-pgsql=/usr && \
    docker-php-ext-install pgsql && \
    docker-php-ext-configure pdo_pgsql --with-pgsql=/usr && \
    docker-php-ext-install pdo_pgsql && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

RUN php -m | grep -i pgsql
RUN php -m | grep -i pdo

WORKDIR /var/www/html

COPY index.php login.php logout.php admin.php api.php db.php ./

RUN mkdir -p uploads && chown -R www-data:www-data /var/www/html

EXPOSE 8000

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8000}"]
