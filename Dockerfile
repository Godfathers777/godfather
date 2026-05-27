FROM php:8.2-cli

RUN apt-get update && apt-get install -y libcurl4-openssl-dev && docker-php-ext-install curl

WORKDIR /app

COPY . /app/

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080"]
