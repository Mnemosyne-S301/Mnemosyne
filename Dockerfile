FROM php:8.2-apache

EXPOSE 80

RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN a2enmod rewrite

COPY ./Controllers /var/www/html/Controllers
COPY ./Models /var/www/html/Models
COPY ./Services /var/www/html/Services
COPY ./Views /var/www/html/Views
COPY ./Content /var/www/html/Content
COPY ./*.php /var/www/html/
COPY ./.htaccess /var/www/html/
COPY ./config /var/www/html/config

RUN chown -R www-data:www-data /var/www/html/

