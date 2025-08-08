# Gunakan image PHP resmi dengan Apache
FROM php:8.1-apache

# Salin semua file PHP ke dalam folder /var/www/html di container
COPY . /var/www/html/

# Aktifkan ekstensi PDO untuk MySQL
RUN docker-php-ext-install pdo pdo_mysql
