# syntax=docker/dockerfile:1

############################
# 1. PHP dependencies
############################
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
        --ignore-platform-reqs

COPY . .

RUN composer dump-autoload --optimize --no-dev --no-scripts

############################
# 2. Frontend assets
#    Wayfinder shells out to `php artisan`, so this stage needs PHP too.
############################
FROM php:8.3-cli AS assets

# Wayfinder shells out to `php artisan`, so the asset stage needs PHP as well as Node.
# Node 24 keeps npm 11, which is what generated the committed lockfile.
RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl gnupg git unzip \
    && curl -fsSL https://deb.nodesource.com/setup_24.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=vendor /app /app

# storage/framework is excluded from the build context, but the view compiler
# needs its directories before `php artisan` (Wayfinder) can run.
RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache

# `npm ci` is rejected by npm >= 11.19 because the committed lockfile omits the
# optional transitive packages of @laravel/multiplex (react, ink, ...). `npm install`
# still installs from the lockfile and fills in those missing entries.
RUN npm install --no-audit --no-fund \
    && npm run build \
    && rm -rf node_modules

############################
# 3. Runtime: php-fpm + nginx
############################
FROM php:8.3-fpm-alpine AS runtime

RUN apk add --no-cache nginx su-exec libpq icu-libs libzip libcurl \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS postgresql-dev libzip-dev icu-dev curl-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql bcmath intl zip opcache pcntl curl \
    && apk del .build-deps

WORKDIR /var/www/html

COPY --from=assets --chown=www-data:www-data /app /var/www/html

COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/php/app.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

RUN ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage \
    && chmod +x /usr/local/bin/entrypoint \
    && mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80 9000

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["php-fpm"]
