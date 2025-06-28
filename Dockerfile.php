FROM php:8.1-apache

# Copier les fichiers PHP
COPY . /var/www/html/

# Activer les extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    && docker-php-ext-install curl

# Exposer le port 80
EXPOSE 80

# Démarrer Apache
CMD ["apache2-foreground"]
