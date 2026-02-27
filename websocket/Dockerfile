FROM php:8.3-cli
RUN apt-get update && apt-get install -y git unzip curl && docker-php-ext-install pcntl
WORKDIR /app
COPY server.php /app/server.php
EXPOSE 3001
CMD ["php", "/app/server.php", "start"]
