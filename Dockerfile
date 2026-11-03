FROM php:8.2-apache
RUN a2enmod rewrite
RUN apt-get update && apt-get install -y libzip-dev libpng-dev libpq-dev zip unzip nodejs npm && docker-php-ext-install pdo_mysql pdo_pgsql zip gd
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader
RUN npm ci && npm run build
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf
EXPOSE 8080

# Las migraciones son incrementales: nunca borrar datos al reiniciar.
CMD php artisan migrate --force && apache2-foreground
