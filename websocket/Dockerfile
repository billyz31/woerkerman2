FROM php:8.3-cli
RUN apt-get update && apt-get install -y git unzip curl && docker-php-ext-install pcntl
RUN php -r "copy('https://getcomposer.org/installer','composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm composer-setup.php
WORKDIR /app
COPY composer.json /app/composer.json
RUN composer require workerman/workerman --no-interaction --no-update
RUN composer update --no-interaction --prefer-dist --ignore-platform-reqs
COPY server.php /app/server.php
EXPOSE 3001
CMD ["php", "/app/server.php"]
