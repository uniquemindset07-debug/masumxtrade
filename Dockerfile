FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache rewrite module (useful for /admin and /api routes)
RUN a2enmod rewrite

# Copy project files into Apache's web root
COPY . /var/www/html/

# Remove files Apache doesn't need to serve
RUN rm -f /var/www/html/Dockerfile /var/www/html/railway.json /var/www/html/*.tar.gz /var/www/html/start.sh /var/www/html/README.md

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Allow .htaccess overrides in the web root
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# start.sh sets Apache's Listen port at runtime, since Railway's $PORT
# is only known when the container actually starts (not at build time)
COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8080
ENV PORT=8080

CMD ["/start.sh"]
