FROM php:8.2-apache
# Copia tus archivos al servidor
COPY . /var/www/html/
# Habilita el módulo de reescritura si lo necesitas
RUN a2enmod rewrite
EXPOSE 80

# Usamos una imagen oficial de PHP con Apache
FROM php:8.2-apache

# Instalamos extensiones de PHP necesarias para bases de datos (PDO MySQL)
RUN docker-php-ext-install pdo pdo_mysql

# Copiamos todos tus archivos del proyecto a la carpeta del servidor en la imagen
COPY . /var/www/html/

# Damos permisos correctos a la carpeta
RUN chown -R www-data:www-data /var/www/html/

# Habilitamos el módulo de reescritura de Apache (útil para URLs limpias)
RUN a2enmod rewrite

# Exponemos el puerto 80
EXPOSE 80