FROM php:8.2-apache
RUN a2enmod rewrite
RUN apt-get update && apt-get install -y libzip-dev libpng-dev zip unzip nodejs npm && docker-php-ext-install pdo_mysql zip gd
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
CMD sed -i "s/80/$PORT/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf && php artisan config:cache && php artisan migrate --force && apache2-foreground
