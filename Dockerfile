FROM php:8.3-cli

RUN apt-get update && \
    apt-get install -y libpq-dev && \
    docker-php-ext-install pdo_pgsql && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

RUN echo "extension=pdo_pgsql.so" > /usr/local/etc/php/conf.d/pdo_pgsql.ini && \
    php -m | grep -i pgsql

WORKDIR /app

COPY *.php ./

RUN mkdir -p uploads

EXPOSE 8000

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8000}"]
