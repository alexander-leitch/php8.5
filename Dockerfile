FROM php:8.5-cli

# Install dependencies and extensions
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    curl \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_sqlite \
    && apt-get clean

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Create database directory
RUN mkdir -p /var/www/database && chmod 777 /var/www/database

WORKDIR /var/www/html

EXPOSE 8080
