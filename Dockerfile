FROM dunglas/frankenphp:latest

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
