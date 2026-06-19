FROM php:8.2-apache

ENV PYTHON_BIN=/opt/mnemosyne-venv/bin/python
ENV SYNC_SCRIPT=/var/www/html/scripts/ajout-donnees.py
ENV PATH="/opt/mnemosyne-venv/bin:${PATH}"

RUN apt-get update \
    && apt-get install -y --no-install-recommends python3 python3-pip python3-venv \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN a2enmod rewrite

RUN python3 -m venv /opt/mnemosyne-venv \
    && /opt/mnemosyne-venv/bin/pip install --no-cache-dir --upgrade pip \
    && /opt/mnemosyne-venv/bin/pip install --no-cache-dir mysql-connector-python

COPY ./Controllers /var/www/html/Controllers
COPY ./Models /var/www/html/Models
COPY ./Services /var/www/html/Services
COPY ./Views /var/www/html/Views
COPY ./Content /var/www/html/Content
COPY ./scripts /var/www/html/scripts
COPY ./*.php /var/www/html/
COPY ./.htaccess /var/www/html/
COPY ./config /var/www/html/config

RUN chmod +x /var/www/html/scripts/ajout-donnees.py \
    && chown -R www-data:www-data /var/www/html/

EXPOSE 80

