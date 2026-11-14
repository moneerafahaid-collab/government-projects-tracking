FROM php:8.2-cli
RUN docker-php-ext-install pdo_sqlite
WORKDIR /var/www/html
COPY . .
RUN chmod +x start.sh
CMD ["./start.sh"]
