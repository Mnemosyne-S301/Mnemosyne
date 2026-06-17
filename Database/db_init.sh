#!/bin/bash

## The purpose of this code is to initiate the database on the creation of the Docker image.

mysql -u root -e "CREATE USER IF NOT EXISTS 'phpserv'@'%' IDENTIFIED BY 'phpserv';"

mysql -u root -e "CREATE DATABASE IF NOT EXISTS Scolarite;"
mysql -u root Scolarite < /tmp/database_create.sql

mysql -u root -e "CREATE DATABASE IF NOT EXISTS Stats;"
mysql -u root Stats < /tmp/stats_database_create.sql
mysql -u root Stats < /tmp/Procedure_stats_script.sql

mysql -u root -e "GRANT SELECT, INSERT, UPDATE, DELETE, DROP ON Scolarite.* TO 'phpserv'@'%';"
mysql -u root -e "GRANT SELECT, INSERT, UPDATE, DELETE, DROP, EXECUTE ON Stats.* TO 'phpserv'@'%';"