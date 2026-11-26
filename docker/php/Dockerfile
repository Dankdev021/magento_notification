# PHP-FPM para Magento Open Source 2.4.8 (suporta PHP 8.3 e 8.4).
FROM php:8.3-fpm-bookworm

# Instalador de extensões mantido pela comunidade (resolve dependências do SO).
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions \
        bcmath \
        ftp \
        gd \
        intl \
        opcache \
        pdo_mysql \
        soap \
        sockets \
        xsl \
        zip

COPY conf/php.ini /usr/local/etc/php/conf.d/zz-magento.ini
COPY conf/www.conf /usr/local/etc/php-fpm.d/zz-magento.conf
COPY bin/ /usr/local/bin/
COPY composer.json.dist /usr/local/share/magento/composer.json.dist

# Remove CRLF caso o repositório tenha sido clonado no Windows sem
# respeitar o .gitattributes; garante que os scripts rodem no Linux.
RUN sed -i 's/\r$//' /usr/local/bin/magento-setup /usr/local/bin/php-fpm-healthcheck /usr/local/bin/create-customer.php \
    && chmod +x /usr/local/bin/magento-setup /usr/local/bin/php-fpm-healthcheck \
    && mkdir -p /var/www/html /var/www/.composer/cache \
    && chown -R www-data:www-data /var/www

ENV COMPOSER_HOME=/var/www/.composer \
    COMPOSER_MEMORY_LIMIT=-1 \
    COMPOSER_PROCESS_TIMEOUT=2000

WORKDIR /var/www/html
