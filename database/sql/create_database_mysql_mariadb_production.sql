CREATE DATABASE IF NOT EXISTS projet_echec2
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'projet_echec2_app'@'%'
IDENTIFIED BY 'change_me_runtime_password';

CREATE USER IF NOT EXISTS 'projet_echec2_migration'@'%'
IDENTIFIED BY 'change_me_migration_password';

GRANT SELECT, INSERT, UPDATE, DELETE
ON projet_echec2.*
TO 'projet_echec2_app'@'%';

GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES
ON projet_echec2.*
TO 'projet_echec2_migration'@'%';

FLUSH PRIVILEGES;
