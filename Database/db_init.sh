#!/bin/bash

## The purpose of this code is to initiate the database on the creation of the Docker image.

mysql -u root -e "CREATE USER IF NOT EXISTS 'phpserv'@'%' IDENTIFIED BY 'phpserv';"

mysql -u root -e "CREATE DATABASE IF NOT EXISTS scolarite;"
mysql -u root scolarite < /tmp/database_create.sql

mysql -u root -e "CREATE DATABASE IF NOT EXISTS stats;"
mysql -u root stats < /tmp/stats_database_create.sql
mysql -u root stats < /tmp/Procedure_stats_script.sql
mysql -u root -e "GRANT SELECT, LOCK TABLES, INSERT, UPDATE, DELETE, DROP ON scolarite.* TO 'phpserv'@'%';"
mysql -u root -e "GRANT SELECT, LOCK TABLES, INSERT, UPDATE, DELETE, DROP ON stats.* TO 'phpserv'@'%';"
mysql -u root -e "GRANT PROCESS ON *.* TO 'phpserv'@'%';"
## default admin (testing only)
mysql -u root -D scolarite -e "INSERT IGNORE INTO Users (username, password, role) VALUES ('admin', '$2y$10$wO0usZ4ju4ivozFYG3DWq.xF7N4oo9Zpy2G9k6dXaxOVxbgHjR5F.', 'admin');"
