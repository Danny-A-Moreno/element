# Usamos la imagen oficial de PHP con Apache
FROM php:8.2-apache

# Instalamos la extensión de MySQL (Vital para Clever Cloud)
RUN docker-php-ext-install pdo pdo_mysql

# Copiamos todo tu proyecto a la carpeta del servidor
COPY . /var/www/html/

# Damos permisos para que el servidor pueda leer los archivos
RUN chown -R www-data:www-data /var/www/html/

# Habilitamos reescritura de URLs
RUN a2enmod rewrite

# El puerto que usa Render
EXPOSE 80