# MisterPlan - Gestión de reservas

Aplicación backend para la gestión de reservas de alojamiento, desarrollada como prueba técnica para MisterPlan.

## Objetivo

Desarrollar una API REST en PHP nativo para gestionar reservas, utilizando MySQL para la persistencia de datos.

El proyecto prioriza la seguridad, la validación de entradas, la separación de responsabilidades, la integridad de los datos, la mantenibilidad y la calidad del código.

## Tecnologías

- **PHP nativo:** lógica backend y acceso a datos mediante PDO.
- **MySQL:** almacenamiento y gestión de la información.
- **Composer:** gestión de dependencias y autoloading.
- **PHP dotenv:** gestión de variables de entorno.
- **Git:** control de versiones.

**Tecnologías previstas para la interfaz:** Twig y JavaScript.

## Funcionalidades implementadas

### Base de datos

- Esquema relacional para la gestión de reservas.
- Tabla `reserva` con información del huésped, alojamiento, fechas, estado e importe.
- Tabla `reserva_evento` para registrar los eventos asociados a cada reserva.
- Claves foráneas, restricciones de integridad e índices.
- Datos de ejemplo para facilitar las pruebas.

### Conexión y acceso a datos

- Conexión a MySQL mediante PDO.
- Configuración mediante variables de entorno.
- Uso de consultas preparadas y parámetros vinculados.
- Separación de la lógica de acceso a datos mediante el patrón Repository.
- Gestión de las operaciones de escritura mediante transacciones.

### Consulta y filtrado de reservas

La clase `ReservaRepository` permite:

- Obtener todas las reservas.
- Buscar una reserva mediante su identificador.
- Filtrar reservas por estado.
- Filtrar por fecha de entrada, estableciendo una fecha inicial y una fecha final.
- Buscar por nombre del huésped mediante coincidencias parciales.
- Combinar varios filtros en una misma consulta.

### Validación de datos

- Validación de los datos necesarios para crear una reserva.
- Comprobación del formato y la longitud de los campos.
- Validación de direcciones de correo electrónico.
- Validación de fechas y coherencia entre entrada y salida.
- Validación del importe y normalización a dos decimales.
- Validación de los estados permitidos.
- Rechazo de campos no reconocidos y valores no válidos.

### Gestión de reservas

La clase `ReservaService` coordina las operaciones de negocio:

- Recuperación de reservas con filtros validados.
- Recuperación de una reserva junto con su historial de eventos.
- Creación de reservas.
- Actualización del estado de una reserva.
- Aplicación de reglas para las transiciones de estado.
- Registro automático de eventos asociados a las operaciones.

Las transiciones de estado implementadas son:

- `pendiente` → `confirmada`
- `pendiente` → `cancelada`
- `confirmada` → `cancelada`

El estado `cancelada` se considera terminal.

### Historial de eventos

La clase `ReservaEventRepository` permite:

- Registrar eventos asociados a una reserva.
- Recuperar el historial de eventos de una reserva.
- Consultar los eventos en orden cronológico.

Los eventos contemplados son `creacion`, `confirmacion` y `cancelacion`.

### Integridad de las operaciones

- Uso de transacciones para coordinar la creación de reservas y sus eventos.
- Uso de `ROLLBACK` cuando una operación transaccional falla.
- Evita conservar una reserva sin su correspondiente evento de creación.
- Evita generar eventos duplicados cuando se solicita el mismo estado.

## Estructura del proyecto

```text
misterplan-reservas/
├── database/
│   └── setup.sql
├── public/
├── scripts/
│   ├── check-database-connection.php
│   ├── check-reserva-repository.php
│   ├── check-reserva-filter-validator.php
│   ├── check-reserva-validator.php
│   ├── check-reserva-write-operations.php
│   ├── check-reserva-event-repository.php
│   └── check-reserva-service.php
├── src/
│   ├── Config/
│   │   └── Database.php
│   ├── Controllers/
│   ├── Repositories/
│   │   ├── ReservaRepository.php
│   │   └── ReservaEventRepository.php
│   ├── Services/
│   │   └── ReservaService.php
│   └── Validators/
│       ├── ReservaFilterValidator.php
│       └── ReservaValidator.php
├── tests/
├── .env.example
├── .gitignore
├── composer.json
├── composer.lock
└── README.md
```

## Requisitos

- PHP con las extensiones PDO y PDO MySQL.
- MySQL o MariaDB.
- Composer.

## Instalación y configuración

1. Clonar el repositorio:

```bash
git clone <URL_DEL_REPOSITORIO>
cd misterplan-reservas
```

2. Instalar las dependencias:

```bash
composer install
```

3. Crear el archivo de configuración local:

```bash
cp .env.example .env
```

4. Configurar en `.env` los parámetros de conexión a la base de datos:

```dotenv
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=misterplan_reservas
DB_USER=misterplan_app
DB_PASSWORD=tu_contraseña
```

5. Importar la estructura de la base de datos y los datos de ejemplo:

```bash
sudo mysql < database/setup.sql
```

La importación debe realizarse con una cuenta MySQL que tenga los permisos necesarios para ejecutar el script.

## Comprobaciones

El proyecto incluye scripts de verificación ejecutables desde la terminal.

Comprobar la conexión con la base de datos:

```bash
php scripts/check-database-connection.php
```

Comprobar las operaciones de consulta del repositorio:

```bash
php scripts/check-reserva-repository.php
```

Comprobar la validación de filtros:

```bash
php scripts/check-reserva-filter-validator.php
```

Comprobar la validación de reservas:

```bash
php scripts/check-reserva-validator.php
```

Comprobar las operaciones de escritura:

```bash
php scripts/check-reserva-write-operations.php
```

Comprobar el repositorio de eventos:

```bash
php scripts/check-reserva-event-repository.php
```

Comprobar la lógica del servicio:

```bash
php scripts/check-reserva-service.php
```

Los scripts de escritura utilizan transacciones y revierten los cambios de prueba para evitar conservar datos temporales.

## Seguridad

- Las credenciales se gestionan mediante variables de entorno.
- El archivo `.env` está excluido del control de versiones.
- Las consultas a la base de datos utilizan sentencias preparadas.
- Los datos de entrada se validan en el servidor.
- Se aplican restricciones de integridad en la base de datos.
- La aplicación utiliza una cuenta de base de datos específica, evitando el uso de credenciales administrativas para las operaciones habituales.
- Las operaciones relacionadas se ejecutan mediante transacciones para preservar la integridad de los datos.

## Estado del proyecto

El proyecto se encuentra en desarrollo.

Actualmente están implementados el esquema de base de datos, la conexión PDO, los repositorios, los validadores y el Service Layer para la consulta, creación y actualización de reservas, así como la gestión de su historial de eventos.

Las siguientes etapas contemplan la implementación de controladores, endpoints REST, gestión de respuestas HTTP y la interfaz web con Twig y JavaScript.
