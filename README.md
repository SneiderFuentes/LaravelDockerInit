# My Appointments - Sistema de Recordatorios de Citas

Sistema de gestión de recordatorios de citas médicas con arquitectura hexagonal y DDD, integrado con MessageBird para notificaciones por WhatsApp y llamadas de voz.

## Arquitectura

El proyecto sigue una arquitectura hexagonal con Domain-Driven Design (DDD) organizada en Bounded Contexts independientes:

-   **SubaccountManagement**: Gestión de conexiones y configuraciones para diferentes centros médicos
-   **AppointmentManagement**: Core del negocio para gestionar citas
-   **CommunicationManagement**: Integración con MessageBird para WhatsApp y llamadas
-   **FlowManagement**: Orquestación de flujos de comunicación

## Requisitos

-   PHP 8.2 o superior
-   Composer
-   Docker y Docker Compose
-   Cuenta en MessageBird (para features de comunicación)

## Instalación

1. Clonar el repositorio:

    ```bash
    git clone https://github.com/yourusername/my-appointments.git
    cd my-appointments
    ```

2. Copiar el archivo .env.example a .env:

    ```bash
    cp .env.example .env
    ```

3. Configurar las variables de entorno en el archivo .env:

    - Configuración de BD principal y de centros médicos
    - Credenciales de MessageBird
    - IDs de flujos configurados en MessageBird

4. Levantar los contenedores:

    ```bash
    docker-compose up -d
    ```

5. Instalar dependencias:

    ```bash
    docker-compose exec app composer install
    ```

6. Generar clave de aplicación:

    ```bash
    docker-compose exec app php artisan key:generate
    ```

7. Ejecutar migraciones:

    ```bash
    docker-compose exec app php artisan migrate
    ```

8. Cargar datos iniciales:

    ```bash
    docker-compose exec app php artisan db:seed
    docker-compose exec app php artisan subaccounts:seed
    ```

9. Migrar bases de datos de prueba de centros médicos:

    ```bash
    docker-compose exec app php artisan migrate --path=database/migrations/centers
    ```

10. Cargar datos de prueba en centros médicos:
    ```bash
    docker-compose exec app php artisan db:seed --class=CentersTestDataSeeder
    ```

## Estructura del Proyecto

```
src/
  BoundedContext/
    SubaccountManagement/          # Gestión de centros médicos
    AppointmentManagement/         # Gestión de citas
    CommunicationManagement/       # Integración con MessageBird
    FlowManagement/                # Orquestación de flujos
    Shared/                        # Componentes compartidos
```

## Flujo de Trabajo

1. El sistema lee citas pendientes de cada centro médico
2. Envía recordatorios por WhatsApp a los pacientes
3. Los pacientes confirman o cancelan respondiendo al mensaje
4. El sistema actualiza el estado de las citas en la BD del centro
5. Para pacientes que no responden, se programan llamadas de voz automatizadas

## Endpoints API

### Gestión de Centros (SubaccountManagement)

-   `GET /api/subaccounts` - Listar centros
-   `GET /api/subaccounts/{key}` - Ver configuración de un centro

### Gestión de Citas (AppointmentManagement)

-   `GET /api/centers/{centerKey}/appointments/pending` - Listar citas pendientes
-   `POST /api/centers/{centerKey}/appointments/{id}/confirm` - Confirmar cita
-   `POST /api/centers/{centerKey}/appointments/{id}/cancel` - Cancelar cita

## Comandos Útiles

-   Ejecutar tests: `docker-compose exec app php artisan test`
-   Procesar trabajos en cola: `docker-compose exec app php artisan queue:work`
-   Ver logs de Horizon: `docker-compose exec app php artisan horizon:list`
-   Ejecutar solo migraciones de un centro: `docker-compose exec app php artisan migrate --database=mysql_center_a`

## Extender el Sistema

Para agregar un nuevo centro médico:

1. Añadir configuración en `config/subaccounts.php`
2. Agregar conexión en `config/database.php`
3. Ejecutar `php artisan subaccounts:seed`

## Licencia

Este proyecto está licenciado bajo [MIT License](LICENSE).
