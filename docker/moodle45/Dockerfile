FROM php:8.3-apache

ARG MOODLE_BRANCH=MOODLE_405_STABLE

ENV MOODLE_URL=http://localhost:8085 \
    MOODLE_DB_HOST=db \
    MOODLE_DB_NAME=moodle \
    MOODLE_DB_USER=moodle \
    MOODLE_DB_PASS=moodle \
    MOODLE_ADMIN_USER=admin \
    MOODLE_ADMIN_PASS=Admin123! \
    MOODLE_ADMIN_EMAIL=admin@example.test

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        default-mysql-client \
        git \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        curl \
        exif \
        gd \
        intl \
        mbstring \
        mysqli \
        opcache \
        soap \
        zip \
    && git clone --depth 1 --branch "${MOODLE_BRANCH}" https://github.com/moodle/moodle.git /usr/src/moodle \
    && rm -rf /usr/src/moodle/.git \
    && cp -a /usr/src/moodle/. /var/www/html/ \
    && rm -rf /usr/src/moodle \
    && apt-get purge -y --auto-remove git \
    && rm -rf /var/lib/apt/lists/*

RUN { \
        echo "max_input_vars=5000"; \
        echo "upload_max_filesize=64M"; \
        echo "post_max_size=64M"; \
        echo "memory_limit=256M"; \
    } > /usr/local/etc/php/conf.d/moodle.ini

COPY . /var/www/html/local/dutydesk
COPY docker/moodle45/entrypoint.sh /usr/local/bin/dutydesk-entrypoint

RUN chmod +x /usr/local/bin/dutydesk-entrypoint \
    && mkdir -p /var/www/moodledata \
    && chown -R www-data:www-data /var/www/html /var/www/moodledata \
    && a2enmod rewrite

ENTRYPOINT ["dutydesk-entrypoint"]
CMD ["apache2-foreground"]
