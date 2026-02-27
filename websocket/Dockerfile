FROM php:8.3-cli
RUN apt-get update && apt-get install -y git unzip curl && docker-php-ext-install pcntl
WORKDIR /app

# Download Workerman
RUN curl -o /tmp/workerman.tar.gz https://github.com/walkor/workerman/archive/refs/tags/v5.1.0.tar.gz \
    && mkdir -p /tmp/workerman \
    && tar -xzf /tmp/workerman.tar.gz -C /tmp/workerman \
    && cp -r /tmp/workerman/workerman-5.1.0/* /app/ \
    && rm -rf /tmp/workerman*

COPY server.php /app/server.php

EXPOSE 3001

CMD ["php", "/app/server.php"]
