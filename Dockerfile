FROM php:8.5-cli

# Install SQLite extension
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && apt-get clean

# Create database directory
RUN mkdir -p /var/www/database && chmod 777 /var/www/database

WORKDIR /var/www/html

EXPOSE 8080
