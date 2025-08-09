-- Script de rotación de credenciales BD
-- Fecha: 2025-08-09 01:09:48
-- EJECUTAR COMO ROOT/ADMIN EN MYSQL

-- 1. Crear nuevo usuario con permisos limitados
CREATE USER 'soporteia_sec20250809'@'%' IDENTIFIED BY '4S#9i9ijdUGjBUYWqf4*5FJC';

-- 2. Otorgar permisos específicos (principio de menor privilegio)
GRANT SELECT, INSERT, UPDATE, DELETE ON soporteia_bookingkavia.* TO 'soporteia_sec20250809'@'%';
GRANT CREATE, DROP, INDEX, ALTER ON soporteia_bookingkavia.* TO 'soporteia_sec20250809'@'%';

-- 3. Aplicar cambios
FLUSH PRIVILEGES;

-- 4. Verificar nuevo usuario
SELECT User, Host FROM mysql.user WHERE User = 'soporteia_sec20250809';

-- 5. DESPUÉS de verificar que todo funciona, eliminar usuario anterior
-- DROP USER 'soporteia_admin'@'%';
-- FLUSH PRIVILEGES;

