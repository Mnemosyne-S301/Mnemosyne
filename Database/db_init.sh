#!/bin/bash

## The purpose of this code is to initiate the database on the creation of the Docker image.

mysql -u root -e "CREATE USER IF NOT EXISTS 'phpserv'@'%' IDENTIFIED BY 'phpserv';"

mysql -u root -e "CREATE DATABASE IF NOT EXISTS Scolarite;"
mysql -u root Scolarite < /tmp/database_create.sql

mysql -u root -e "CREATE DATABASE IF NOT EXISTS Stats;"
mysql -u root Scolarite < /tmp/stats_database_create.sql
mysql -u root Scolarite < /tmp/Procedure_stats_script.sql

mysql -u root -e "GRANT SELECT, INSERT, UPDATE, DELETE ON Scolarite.* TO 'phpserv'@'%';"
mysql -u root -e "GRANT SELECT, INSERT, UPDATE, DELETE ON Stats.* TO 'phpserv'@'%';"