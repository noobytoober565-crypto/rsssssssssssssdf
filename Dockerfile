FROM php:8.3-cli

RUN apt-get update && \
    apt-get install -y libpq-dev && \
    docker-php-ext-install pdo_pgsql && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

RUN php -m

WORKDIR /app

COPY *.php ./

RUN mkdir -p uploads

EXPOSE 8000

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8000}"]
