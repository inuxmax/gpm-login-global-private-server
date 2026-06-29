# syntax=docker/dockerfile:1.6
#
# Multi-stage build for gpm-login-global-private-server.
#
# - Stage 1 (node-builder)     : compile Vue 3 SPA (public/build/*)
# - Stage 2 (composer-builder) : install PHP deps (vendor/)
# - Stage 3 (runtime)          : slim PHP/Apache image serving the app
#
# Both builder stages run on the native BUILDPLATFORM to avoid slow QEMU
# emulation on multi-arch builds. Their artifacts (JS/CSS + PHP autoload)
# are architecture-independent, so only the final runtime stage is built
# per TARGETPLATFORM by buildx.
#
# Build & publish multi-arch:
#   docker buildx build --platform linux/amd64,linux/arm64 \
#       -t ngochoaitn/gpm-login-global-private-server:latest --push .
#

# -----------------------------------------------------------------------------
# Stage 1 — Vue SPA bundle
# -----------------------------------------------------------------------------
FROM --platform=$BUILDPLATFORM node:20-alpine AS node-builder

WORKDIR /app

# Install FE deps first (cache-friendly)
COPY package.json package-lock.json* ./
RUN if [ -f package-lock.json ]; then \
        npm ci --no-audit --no-fund; \
    else \
        npm install --no-audit --no-fund; \
    fi

# Copy only what Vite needs
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build


# -----------------------------------------------------------------------------
# Stage 2 — PHP dependencies (composer)
#
# Must run under PHP 8.1 because composer.lock pins packages that require
# php <=8.4 (nette/schema, nette/utils). The official composer:* image ships
# with PHP 8.5+ which refuses to resolve. We bring composer into a php:8.1-cli
# image instead.
# -----------------------------------------------------------------------------
FROM --platform=$BUILDPLATFORM php:8.1-cli-alpine AS composer-builder

RUN apk add --no-cache git unzip libzip-dev \
    && docker-php-ext-install zip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MAX_PARALLEL_HTTP=3

# Prime the package cache with composer metadata only (better layer caching)
COPY composer.json composer.lock ./
# prefer-dist is faster but GitHub codeload often returns HTTP 400 on CI/VPS builds;
# retry a few times then fall back to git clone (--prefer-source).
RUN set -e; \
    install_deps() { \
        composer install \
            --no-dev \
            --no-scripts \
            --no-autoloader \
            "$@" \
            --no-interaction \
            --no-progress; \
    }; \
    for attempt in 1 2 3; do \
        echo "[composer] install attempt ${attempt} (--prefer-dist)"; \
        install_deps --prefer-dist && exit 0; \
        echo "[composer] attempt ${attempt} failed, waiting 20s..."; \
        sleep 20; \
    done; \
    echo "[composer] falling back to --prefer-source (git clone)"; \
    install_deps --prefer-source

# Copy the rest of the Laravel source and finalise the autoloader
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative


# -----------------------------------------------------------------------------
# Stage 3 — Runtime (per-target PHP/Apache)
# -----------------------------------------------------------------------------
FROM php:8.1-apache AS runtime

# PHP native extensions + build tools (cleaned up in the same layer)
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libzip-dev \
        zip \
        unzip \
        curl \
        git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd pdo pdo_mysql exif fileinfo zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Application code + composer-installed vendor/
COPY --from=composer-builder /app /var/www/html

# Overwrite/ensure public/build is the freshly compiled SPA bundle
COPY --from=node-builder /app/public/build /var/www/html/public/build

# Runtime config (Apache vhost, PHP ini, entrypoint)
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/php_large_file_upload.ini /usr/local/etc/php/conf.d/php_large_file_upload.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN a2enmod rewrite \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p \
        storage/app/public/profiles \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -fsS http://localhost/ >/dev/null || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
