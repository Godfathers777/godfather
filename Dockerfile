FROM php:8.2-apache

RUN apt-get update && apt-get install -y libcurl4-openssl-dev && docker-php-ext-install curl

RUN a2dismod mpm_event && a2enmod mpm_prefork rewrite

COPY . /var/www/html/

EXPOSE 80
