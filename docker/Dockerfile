FROM php:8.3-apache-bullseye

ENV DEBIAN_FRONTEND=noninteractive \
    LAST_VERSION_URL="https://api.github.com/repos/devcode-it/openstamanager/releases/latest" \
    APP_DIR="/var/www/html"

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    libcurl4-openssl-dev \
    libicu-dev \
    libonig-dev \
    libpng-dev \
    libjpeg-dev \
    libxml2-dev \
    libxslt-dev \
    libzip-dev \
    unzip \
    wget \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install \
        curl \
        ftp \
        gd \
        intl \
        mbstring \
        pdo_mysql \
        soap \
        xsl \
        zip \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/*

COPY php.ini /usr/local/etc/php/

WORKDIR ${APP_DIR}

RUN last_version_v=$(curl -s ${LAST_VERSION_URL} | grep tag_name | cut -d '"' -f 4) && \
    last_version=$(echo $last_version_v | sed 's/^v//') && \
    wget -O openstamanager.zip "https://github.com/devcode-it/openstamanager/releases/download/${last_version_v}/openstamanager-${last_version}.zip" && \
    unzip openstamanager.zip && \
    mkdir -p ${APP_DIR}/files/temp ${APP_DIR}/logs && \
    chown -R www-data:www-data ${APP_DIR} && \
    chmod -R 750 ${APP_DIR} && \
    rm openstamanager.zip

EXPOSE 80