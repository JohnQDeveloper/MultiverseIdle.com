FROM dunglas/frankenphp:latest

ARG DB_USER
ARG DB_PASSWORD
ARG DB_HOST
ARG RESEND_API_KEY

ENV DB_USER=${DB_USER}
ENV DB_PASSWORD=${DB_PASSWORD}
ENV DB_HOST=${DB_HOST}
ENV RESEND_API_KEY=${RESEND_API_KEY}

ENV SERVER_NAME=:80

LABEL maintainer="JohnQDeveloper <public@JohnQDeveloper.com>"

RUN install-php-extensions \
	pdo_mysql \
	gd \
	intl \
	zip \
	opcache

COPY "configs/Caddyfile" "/etc/frankenphp/Caddyfile"
COPY "configs/php.ini-development" "/usr/local/etc/php/php.ini"
