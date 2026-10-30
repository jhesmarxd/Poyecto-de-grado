FROM php:8.2-apache
RUN a2enmod rewrite
RUN apt-get update && apt-get install -y libzip-dev libpng-dev zip unzip nodejs npm && docker-php-ext-install pdo_mysql zip gd
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public

# Parche para TiDB: Elimina migración ENUM conflictiva y ajusta tabla reservas
RUN rm -f database/migrations/2026_10_01_000002_expand_reservation_statuses.php
RUN find database/migrations/ -name "*create_reservas_table.php" -exec sed -i "s/'pendiente', 'confirmada', 'cancelada', 'completada'/'pendiente', 'confirmada', 'cancelada', 'completada', 'afectada_por_configuracion', 'cancelada_por_complejo', 'reprogramada'/g" {} +

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf
EXPOSE 8080
CMD php artisan config:clear && php artisan cache:clear && php artisan db:wipe --force && php artisan migrate --force --seed && apache2-foreground
