FROM mysql:8

EXPOSE 3306
ENV MYSQL_ALLOW_EMPTY_PASSWORD=yes

COPY ./*.sql /tmp/
COPY db_init.sh /docker-entrypoint-initdb.d/
## les fichiers .sql et .sh placé dans /docker-entrypoint-initdb.d seront executé au premier demarrage 
## (selon internet, pour les image mysql)
