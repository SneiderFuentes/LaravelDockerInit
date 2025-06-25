# Guía de Despliegue en Servidor Linux

Esta guía describe los pasos para desplegar la aplicación en un servidor Linux usando Docker y Docker Compose, asegurando que los servicios se ejecuten de forma persistente.

## 1. Prerrequisitos

Asegúrate de tener Docker y Docker Compose instalados en tu servidor Linux. Puedes seguir las guías oficiales:

-   [Instalar Docker Engine](https://docs.docker.com/engine/install/ubuntu/)
-   [Instalar Docker Compose](https://docs.docker.com/compose/install/)

## 2. Habilitar el Servicio de Docker

Para que Docker se inicie automáticamente cuando el servidor arranca, ejecuta el siguiente comando una sola vez:

```bash
sudo systemctl enable docker
```

## 3. Configurar la Persistencia de los Contenedores

Para garantizar que todos los contenedores se reinicien automáticamente si fallan o si el servidor se reinicia, debemos añadir la política `restart: unless-stopped` a cada servicio en el archivo `docker-compose.yml`.

Abre tu archivo `docker-compose.yml` y añade la línea `restart: unless-stopped` a cada uno de los servicios principales (`app`, `db`, `redis`, `nginx`, `horizon`, `scheduler`).

Por ejemplo, el servicio `app` debería quedar así:

```yaml
# docker-compose.yml
services:
    app: &app-base
        build:
            context: .
            dockerfile: docker/php/Dockerfile
        image: appointments_app
        container_name: appointments_app
        restart: unless-stopped
        working_dir: /var/www
        # inyectamos todo lo de .env en el contenedor
        env_file:
            - .env
        # montamos tu código
        volumes:
            - ./:/var/www
        # Agregamos configuración para acceder al host
        extra_hosts:
            - "host.docker.internal:host-gateway"
        networks:
            - appnet
        depends_on:
            - db
            - redis
        logging:
            driver: "json-file"
            options:
                max-size: "10m"
                max-file: "7"
        environment:
            - TZ=America/Bogota

    db:
        image: mysql:8.0
        container_name: appointments_db
        restart: unless-stopped
        environment:
            MYSQL_ROOT_PASSWORD: secret
            MYSQL_DATABASE: appointments
            MYSQL_USER: appuser
            MYSQL_PASSWORD: apppass
            TZ: America/Bogota
        ports:
            - "3307:3306"
        volumes:
            - dbdata:/var/lib/mysql
            - ./docker/mysql/init:/docker-entrypoint-initdb.d
        networks:
            - appnet
        logging:
            driver: "json-file"
            options:
                max-size: "10m"
                max-file: "7"

    redis:
        image: redis:7-alpine
        container_name: appointments_redis
        restart: unless-stopped
        networks:
            - appnet
        logging:
            driver: "json-file"
            options:
                max-size: "10m"
                max-file: "7"
        environment:
            - TZ=America/Bogota

    nginx:
        image: nginx:stable-alpine
        container_name: appointments_nginx
        restart: unless-stopped
        ports:
            - "80:80"
        volumes:
            - ./:/var/www
            - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
        depends_on:
            - app
        networks:
            - appnet
        logging:
            driver: "json-file"
            options:
                max-size: "10m"
                max-file: "7"
        environment:
            - TZ=America/Bogota

    horizon:
        <<: *app-base
        container_name: appointments_horizon
        restart: unless-stopped
        # inyectamos las mismas vars que en app
        env_file:
            - .env
        # sobreescribimos solo el comando de arranque
        command: ["php", "artisan", "horizon"]
        # Agregamos configuración para acceder al host (heredado de app-base)
        depends_on:
            - redis
            - db
        networks:
            - appnet
        logging:
            driver: "json-file"
            options:
                max-size: "10m"
                max-file: "7"
        environment:
            - TZ=America/Bogota

    ngrok:
        image: ngrok/ngrok:latest # entrypoint = "ngrok"
        container_name: appointments_ngrok
        restart: unless-stopped
        depends_on:
            - nginx
        environment:
            NGROK_AUTHTOKEN: ${NGROK_AUTHTOKEN}
            TZ: America/Bogota
        # ⬇️ El comando se pasa SIN repetir la palabra 'ngrok'
        command:
            - "http" # sub-comando
            - "nginx:80" # <host interno>:<puerto>
            - "--log=stdout" # para ver la URL con docker logs
        ports:
            - "4040:4040"
        networks:
            - appnet
        logging:
            driver: "json-file"
            options:
                max-size: "10m"
                max-file: "7"

    scheduler:
        <<: *app-base
        container_name: appointments_scheduler
        restart: unless-stopped
        command:
            [
                "sh",
                "-c",
                "while :; do php artisan schedule:run --verbose --no-interaction & sleep 60; done",
            ]
        depends_on:
            - db
            - redis
        networks:
            - appnet
        logging:
            driver: "json-file"
            options:
                max-size: "10m"
                max-file: "7"
        environment:
            - TZ=America/Bogota

volumes:
    dbdata:

networks:
    appnet:
```

## 4. Proceso de Despliegue

Sigue estos pasos en tu servidor Linux:

1.  **Clona el repositorio:**

    ```bash
    git clone <URL_de_tu_repositorio>
    cd <nombre_del_directorio>
    ```

2.  **Crea tu archivo de entorno:**
    Copia el archivo de ejemplo y ajusta las variables para tu entorno de producción (base de datos, claves de API, etc.).

    ```bash
    cp .env.example .env
    nano .env
    ```

3.  **Construye y levanta los contenedores:**
    Este comando construirá las imágenes y levantará todos los servicios en segundo plano (`-d`).
    ```bash
    docker-compose up -d --build
    ```

## 5. Verificación

Para comprobar que todos los servicios se están ejecutando correctamente, puedes usar:

```bash
docker-compose ps
```

Deberías ver todos los contenedores con el estado `Up` o `running`.

Con estos pasos, tu aplicación estará desplegada y configurada para ejecutarse de forma continua, reiniciándose automáticamente cuando sea necesario.
