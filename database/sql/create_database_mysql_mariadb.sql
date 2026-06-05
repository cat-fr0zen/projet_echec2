-- Script de confort pour environnement local.
-- Pour la production publique, utiliser le script dedie
-- `create_database_mysql_mariadb_production.sql` avec comptes separes.

CREATE DATABASE IF NOT EXISTS projet_echec2
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'projet_echec2_app'@'127.0.0.1'
IDENTIFIED BY 'change_me_local_dev_password';

GRANT ALL PRIVILEGES
ON projet_echec2.*
TO 'projet_echec2_app'@'127.0.0.1';

FLUSH PRIVILEGES;
