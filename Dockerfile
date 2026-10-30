# syntax=docker/dockerfile:1
#
# myPuskeswan — image produksi (Apache + PHP 8.3)
# Build : docker build -t mypuskeswan:latest .
# Run   : docker compose up -d
#
FROM php:8.3-apache

# ---- Label metadata -------------------------------------------------------
LABEL org.opencontainers.image.title="mypuskeswan" \
      org.opencontainers.image.description="Sistem Informasi Puskeswan (PHP + MySQL)" \
      org.opencontainers.image.source="https://github.com/budinaeka/mypuskeswan_template" \
      org.opencontainers.image.licenses="MIT"

# ---- System dependencies --------------------------------------------------
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg62-turbo-dev \
        libwebp-dev \
        libfreetype6-dev \
        libzip-dev \
        curl \
        tzdata; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install -j"$(nproc)" gd pdo_mysql opcache zip; \
    apt-get purge -y --auto-remove; \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# ---- PHP + Apache config --------------------------------------------------
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-mypuskeswan.ini
COPY docker/apache-security.conf /etc/apache2/conf-available/mypuskeswan.conf
RUN a2enconf mypuskeswan \
 && a2enmod headers rewrite expires \
 && rm -f /etc/apache2/conf-enabled/docker-php.conf.bak

# ---- Application ----------------------------------------------------------
WORKDIR /var/www/html

COPY --chown=www-data:www-data . /var/www/html

# Folder upload harus ada & writable walaupun volume belum ter-mount
RUN mkdir -p /var/www/html/uploads \
 && chown -R www-data:www-data /var/www/html/uploads \
 && chmod 775 /var/www/html/uploads \

# ---- Entrypoint -----------------------------------------------------------
COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

# Konfigurasi runtime (bisa dioverride lewat `environment:` / `.env`)
ENV APP_ENV=production \
    DB_HOST=db \
    DB_PORT=3306 \
    DB_NAME=mypuskeswan_db \
    DB_USER=mypuskeswan \
    DB_PASSWORD=change-me \
    ADMIN_USERNAME=admin \
    ADMIN_PASSWORD=admin123 \
    TZ=Asia/Jakarta

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -fsS http://127.0.0.1/healthz.php || exit 1

ENTRYPOINT ["/usr/local/bin/app-entrypoint"]
CMD ["apache2-foreground"]
