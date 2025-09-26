FROM php:8.3-cli-bookworm

# Installeer systeemafhankelijkheden voor pdo_mysql
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      default-mysql-client \
      libpq-dev \
      libzip-dev \
      unzip \
 && docker-php-ext-install pdo_mysql \
 && rm -rf /var/lib/apt/lists/*

# Niet als root draaien
RUN useradd -u 10001 -m appuser
USER appuser

WORKDIR /app
COPY --chown=appuser:appuser app /app
EXPOSE 8080

# Built-in webserver voor demo/doel: 1 endpoint
CMD ["php", "-d", "memory_limit=512M", "-d", "upload_max_filesize=100M", "-d", "post_max_size=100M", "-d", "max_execution_time=300", "-S", "0.0.0.0:8080", "-t", "/app/public"]
