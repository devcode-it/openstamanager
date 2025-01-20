# Usa l'immagine ufficiale di PHP con Apache
FROM php:8.3-apache

# Abilita i moduli richiesti
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libicu-dev \
    libcurl4-openssl-dev \
    libxml2-dev \
    libpng-dev \
    libonig-dev \
    unzip \
    libxml2-dev \ 
    libxslt-dev \  
    wget \
    git && \
    docker-php-ext-install zip mbstring pdo_mysql intl soap curl && \
    a2enmod rewrite 

RUN docker-php-ext-install xsl ftp

RUN docker-php-ext-enable xsl ftp

RUN apt-get install -y libpng-dev libjpeg-dev \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install gd
    

# Configura i parametri di PHP
COPY php.ini /usr/local/etc/php/

# Configura l'applicazione
WORKDIR /var/www/html
# Esponi la porta per Apache
EXPOSE 80

# Clona il repository
RUN wget https://github.com/devcode-it/openstamanager/releases/download/v2.6.2/openstamanager-2.6.2.zip && unzip openstamanager-2.6.2.zip

RUN mkdir -p /var/www/html/files/temp /var/www/html/logs
RUN chown -R $USER:www-data /var/www/html/
RUN chmod -R 770 /var/www/html/

# Pulizia file temporanei
RUN apt autoclean && rm -rf /var/lib/apt/lists/*
