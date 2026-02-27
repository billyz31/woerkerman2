FROM php:8.3-cli
RUN apt-get update && apt-get install -y git unzip libzip-dev curl && docker-php-ext-install pdo_mysql
WORKDIR /var/www
COPY index.php /var/www/
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/var/www"]
