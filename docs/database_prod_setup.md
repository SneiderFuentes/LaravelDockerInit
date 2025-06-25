# Configuración de Conexión a Base de Datos Externa en Producción

## 1. Configuración en el Servidor MySQL (Ubuntu)

```bash
# 1.1 Conectarse al servidor MySQL
sudo mysql -u root -p

# 1.2 Crear usuario específico para la aplicación
CREATE USER 'app_user'@'%' IDENTIFIED BY 'tu_password_seguro';

# 1.3 Otorgar permisos al usuario (solo los necesarios)
GRANT SELECT, INSERT, UPDATE, DELETE ON datosipsndx.* TO 'app_user'@'%';
FLUSH PRIVILEGES;
```

## 2. Configurar MySQL para Aceptar Conexiones Remotas

```bash
# 2.1 Editar el archivo de configuración de MySQL
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf

# 2.2 Buscar y modificar la línea bind-address
# Cambiar:
# bind-address = 127.0.0.1
# Por:
# bind-address = 0.0.0.0

# 2.3 Reiniciar MySQL
sudo systemctl restart mysql
```

## 3. Configurar Firewall

```bash
# 3.1 Permitir conexiones al puerto MySQL
sudo ufw allow from [IP_DEL_CONTENEDOR_DOCKER] to any port 3306

# 3.2 Verificar reglas del firewall
sudo ufw status
```

## 4. Configuración en la Aplicación Docker

### 4.1 Variables de Entorno (.env)

```env
DB_HOST_DATOSIPSNDX=IP_DEL_SERVIDOR_MYSQL
DB_PORT_DATOSIPSNDX=3306
DB_DATABASE_DATOSIPSNDX=datosipsndx
DB_USERNAME_DATOSIPSNDX=app_user
DB_PASSWORD_DATOSIPSNDX=tu_password_seguro
```

### 4.2 Docker Compose

```yaml
services:
    app:
        # ... otras configuraciones ...
        networks:
            - app-network

networks:
    app-network:
        driver: bridge
```

## 5. Verificación de Conexión

```bash
# 5.1 Desde el servidor de la aplicación
telnet IP_DEL_SERVIDOR_MYSQL 3306

# 5.2 Desde dentro del contenedor Docker
docker exec -it nombre_contenedor bash
telnet IP_DEL_SERVIDOR_MYSQL 3306
```

## 6. Consideraciones de Seguridad

1. Usar contraseñas fuertes
2. Limitar los permisos del usuario MySQL solo a las operaciones necesarias
3. Configurar el firewall para permitir conexiones solo desde IPs específicas
4. Considerar usar SSL/TLS para la conexión MySQL
5. Mantener actualizados tanto MySQL como el sistema operativo

## 7. Monitoreo

1. Revisar logs de MySQL: `/var/log/mysql/error.log`
2. Monitorear conexiones activas:
    ```sql
    SHOW PROCESSLIST;
    ```
3. Configurar alertas para intentos de conexión fallidos

## 8. Backup

1. Configurar respaldos automáticos de la base de datos
2. Verificar periódicamente la integridad de los respaldos
3. Documentar y probar el proceso de restauración

## 9. Troubleshooting

Si hay problemas de conexión, verificar:

1. Estado del servicio MySQL
2. Reglas del firewall
3. Configuración de bind-address
4. Permisos del usuario
5. Logs de MySQL y de la aplicación
