FROM php:8.4-apache AS app

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/* \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && a2enmod rewrite \
    && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover


FROM node:22-bookworm-slim AS assets

WORKDIR /app
ENV CYPRESS_INSTALL_BINARY=0

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY --from=app /var/www/html/vendor ./vendor
COPY resources ./resources
COPY vite.config.js postcss.config.js ./
RUN npm run build


FROM app

COPY --from=assets /app/public/build ./public/build
COPY docker/entrypoint.sh /usr/local/bin/happynotes-entrypoint

ENV APP_NAME="Happy Notes" \
    APP_ENV=production \
    APP_DEBUG=false \
    APP_URL=http://localhost:8080 \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/data/database.sqlite \
    SESSION_DRIVER=database \
    CACHE_STORE=database \
    QUEUE_CONNECTION=sync \
    LOG_CHANNEL=stderr \
    MAIL_MAILER=log

VOLUME /data
EXPOSE 80

ENTRYPOINT ["happynotes-entrypoint"]
CMD ["apache2-foreground"]
