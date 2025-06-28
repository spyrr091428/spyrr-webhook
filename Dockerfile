FROM php:8.1-apache

# Copier tous les fichiers dans le répertoire web
COPY . /var/www/html/

# Installer les extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    && docker-php-ext-install curl \
    && a2enmod rewrite

# Donner les bonnes permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exposer le port 80
EXPOSE 80

# Démarrer Apache
CMD ["apache2-foreground"]
