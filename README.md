# MisterPlan - Gestión de reservas

Aplicación backend para la gestión de reservas de alojamiento, desarrollada como prueba técnica para MisterPlan.

## Objetivo

Desarrollar una API REST en PHP nativo para gestionar reservas, utilizando MySQL para la persistencia de datos.

El proyecto prioriza la seguridad, la validación de entradas, la separación de responsabilidades, la mantenibilidad y la calidad del código.

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

### Consulta de reservas

La clase `ReservaRepository` permite:

- Obtener todas las reservas.
- Buscar una reserva mediante su identificador.
- Filtrar reservas por estado.
- Filtrar por fecha de entrada, estableciendo una fecha inicial y una fecha final.
- Buscar por nombre del huésped mediante coincidencias parciales.
- Combinar varios filtros en una misma consulta.

## Estructura del proyecto

```text
misterplan-reservas/
├── database/
│   └── setup.sql
├── public/
├── scripts/
│   ├── check-database-connection.php
│   └── check-reserva-repository.php
├── src/
│   ├── Config/
│   │   └── Database.php
│   ├── Controllers/
│   ├── Repositories/
│   │   └── ReservaRepository.php
│   ├── Services/
│   └── Validators/
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

El proyecto incluye scripts de verificación ejecutables desde la terminal:

Comprobar la conexión con la base de datos:

```bash
php scripts/check-database-connection.php
```

Comprobar las operaciones de consulta del repositorio:

```bash
php scripts/check-reserva-repository.php
```

## Estado del proyecto

El proyecto se encuentra en desarrollo.

Actualmente están implementados el esquema de base de datos, la configuración de la conexión PDO y la capa de acceso a datos para la consulta y filtrado de reservas.

Las siguientes etapas contemplan la implementación de validadores, servicios, controladores y endpoints REST, así como las operaciones de creación y actualización de reservas, el registro de eventos y la interfaz web con Twig y JavaScript.

## Seguridad

- Las credenciales se gestionan mediante variables de entorno.
- El archivo `.env` está excluido del control de versiones.
- Las consultas a la base de datos utilizan sentencias preparadas.
- La aplicación utiliza una cuenta de base de datos específica, evitando el uso de credenciales administrativas para las operaciones habituales.
