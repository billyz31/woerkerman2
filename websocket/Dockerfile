FROM php:8.3-cli
RUN apt-get update && apt-get install -y git unzip curl && docker-php-ext-install pcntl
RUN curl -o /tmp/workerman.tar.gz https://github.com/walkor/workerman/archive/refs/tags/v5.1.0.tar.gz \
    && mkdir -p /tmp/workerman \
    && tar -xzf /tmp/workerman.tar.gz -C /tmp/workerman \
    && cp -r /tmp/workerman/workerman-5.1.0/Applications/Workerman /tmp/workerman/workerman-5.1.0/Autoloader.php /var/www/ \
    && rm -rf /tmp/workerman*
WORKDIR /var/www
COPY server.php /var/www/server.php
EXPOSE 3001
CMD ["php", "/var/www/server.php", "start"]
