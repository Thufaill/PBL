# SkillBridge - PHP 8.3 + Apache + PDO PostgreSQL
# Document root = public/ (folder includes/, db/, docs/, tools/ TIDAK ikut terlayani web).
FROM php:8.3-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev \
 && docker-php-ext-install pdo_pgsql \
 && rm -rf /var/lib/apt/lists/* \
 && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY . /var/www/app

ENV APACHE_DOCUMENT_ROOT=/var/www/app/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
      /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf \
 && printf 'ServerTokens Prod\nServerSignature Off\n<Directory ${APACHE_DOCUMENT_ROOT}>\n  Options -Indexes\n  AllowOverride None\n  Require all granted\n</Directory>\n' \
      > /etc/apache2/conf-available/skillbridge.conf \
 && a2enconf skillbridge \
 && chmod +x /var/www/app/docker/entrypoint.sh

# Platform seperti Render memberi port lewat variabel PORT; default 80.
ENV PORT=80
EXPOSE 80
ENTRYPOINT ["/var/www/app/docker/entrypoint.sh"]
