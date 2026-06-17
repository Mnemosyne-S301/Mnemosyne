
FROM php:8.2-apache

ENV PYTHON_BIN=/opt/mnemosyne-venv/bin/python
ENV SYNC_SCRIPT=/var/www/html/scripts/ajout-donnees.py
ENV PATH="/opt/mnemosyne-venv/bin:${PATH}"

ENV DB_HOST=mnemosyne-mysql
ENV DB_PORT=3306
ENV DB_NAME=scolarite

ENV YEARS=2021,2022,2023,2024
ENV FILL_MODE=partial
ENV MIN_CONFIDENCE=0.50
ENV FILL_ALL_REQUESTED_YEARS=1
ENV BRIDGE_EMPTY_YEARS=1
ENV ALLOW_SYNTHETIC=1
ENV MAX_SYNTHETIC_RATIO=1.0
ENV MAX_INSERTIONS=5000
ENV REBUILD_GENERATED=0
ENV DRY_RUN=0

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        python3 \
        python3-pip \
        python3-venv \
    && rm -rf /var/lib/apt/lists/*

RUN python3 -m venv /opt/mnemosyne-venv \
    && /opt/mnemosyne-venv/bin/pip install --no-cache-dir --upgrade pip \
    && /opt/mnemosyne-venv/bin/pip install --no-cache-dir mysql-connector-python

RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli

RUN a2enmod rewrite

WORKDIR /var/www/html

COPY ./Controllers /var/www/html/Controllers
COPY ./Models /var/www/html/Models
COPY ./Services /var/www/html/Services
COPY ./Views /var/www/html/Views
COPY ./Content /var/www/html/Content
COPY ./config /var/www/html/config
COPY ./scripts /var/www/html/scripts
COPY ./*.php /var/www/html/
COPY ./.htaccess /var/www/html/

RUN chmod +x /var/www/html/scripts/ajout-donnees.py \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80

