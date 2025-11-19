FROM php:8.2-fpm

# Dependencias del sistema
RUN apt-get update && apt-get install -y \
    git zip unzip libzip-dev libpng-dev libonig-dev libxml2-dev \
    libcurl4-openssl-dev libicu-dev zlib1g-dev libpq-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Copiar composer.lock y composer.json para cachear dependencias
# (Se hace en build por volumen en dev, pero útil si haces build de imagen con vendor)
#COPY app/composer.json /var/www/html/composer.json
#RUN composer install --no-dev --optimize-autoloader

# Ajustes de permisos (cuando arranque, ejecuta comandos para storage)
RUN usermod -u 1000 www-data

EXPOSE 9000

CMD ["php-fpm"]
