FROM php:8.2-cli

# Install required PHP extensions for MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Copy project files
WORKDIR /var/www/html
COPY . /var/www/html/

# Remove files not needed inside the container
RUN rm -f /var/www/html/Dockerfile /var/www/html/railway.json /var/www/html/*.tar.gz /var/www/html/README.md

EXPOSE 8080
ENV PORT=8080

# Railway provides the port to listen on via $PORT at runtime
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t /var/www/html"]
