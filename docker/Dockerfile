# Usa l'immagine ufficiale di PHP con Apache
FROM php:8.3-apache-bullseye

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
RUN last_version_v=$(curl -s https://api.github.com/repos/devcode-it/openstamanager/releases/latest | grep tag_name | cut -d '"' -f 4) && last_version=$(echo $last_version_v | sed 's/^v//') && wget -O openstamanager.zip "https://github.com/devcode-it/openstamanager/releases/download/${last_version_v}/openstamanager-${last_version}.zip" && unzip openstamanager.zip

RUN mkdir -p /var/www/html/files/temp /var/www/html/logs
RUN chown -R $USER:www-data /var/www/html/
RUN chmod -R 770 /var/www/html/
RUN rm openstamanager.zip

# Pulizia file temporanei
RUN apt autoclean && rm -rf /var/lib/apt/lists/*
