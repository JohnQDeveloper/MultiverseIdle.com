FROM dunglas/frankenphp

LABEL maintainer="JohnQDeveloper <public@JohnQDeveloper.com>"

RUN install-php-extensions \
	pdo_mysql \
	gd \
	intl \
	zip \
	opcache

COPY . /app
