# Development image: the framework's own server, with the project bind-mounted
# so an edit is picked up without a rebuild.

FROM php:8.5-cli

COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

# redis backs the message transports, intl is required by the framework, and
# pdo_sqlite is the database.
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && install-php-extensions zip intl pdo_sqlite redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

# Dependencies are installed from the lock file alone, so editing source code
# does not invalidate this layer.
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-interaction --no-progress --no-scripts --prefer-dist

COPY . .

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 8000

ENTRYPOINT ["entrypoint"]
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
