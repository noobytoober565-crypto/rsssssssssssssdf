FROM php:8.3-cli

RUN apt-get update && apt-get install -y libpq-dev && \
    docker-php-ext-install pdo pdo_pgsql && \
    rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY index.php login.php logout.php admin.php api.php db.php ./

RUN mkdir -p uploads && chown -R www-data:www-data /var/www/html

EXPOSE 8000

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8000}"]
