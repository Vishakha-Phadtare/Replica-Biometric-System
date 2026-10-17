FROM php:8.2-cli

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Set working directory
WORKDIR /app

# Copy website files
COPY teacher-attendance/ /app

# Start PHP built-in server with router on Render's dynamic port
CMD php -S 0.0.0.0:${PORT:-8080} router.php
