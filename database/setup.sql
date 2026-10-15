-- MisterPlan - Gestion de reservas
-- Esquema inicial de la base de datos.
-- MySQL 8 / MariaDB compatible.

CREATE DATABASE IF NOT EXISTS misterplan_reservas
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE misterplan_reservas;


-- Tabla principal de reservas

CREATE TABLE reserva (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre_huesped VARCHAR(120) NOT NULL,
    email_huesped VARCHAR(254) NOT NULL,
    nombre_alojamiento VARCHAR(160) NOT NULL,
    fecha_entrada DATE NOT NULL,
    fecha_salida DATE NOT NULL,
    estado ENUM(
        'pendiente',
        'confirmada',
        'cancelada'
    ) NOT NULL DEFAULT 'pendiente',
    importe DECIMAL(10, 2) NOT NULL,
    notas TEXT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    INDEX idx_reserva_fecha_entrada (fecha_entrada),
    INDEX idx_reserva_estado_fecha (
        estado,
        fecha_entrada
    ),

    CONSTRAINT chk_reserva_fechas
        CHECK (fecha_salida > fecha_entrada),

    CONSTRAINT chk_reserva_importe
        CHECK (importe >= 0)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;


-- Historial de eventos de cada reserva

CREATE TABLE reserva_evento (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reserva_id BIGINT UNSIGNED NOT NULL,
    tipo ENUM(
        'creacion',
        'confirmacion',
        'cancelacion'
    ) NOT NULL,
    descripcion VARCHAR(500) NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    INDEX idx_evento_reserva_fecha (
        reserva_id,
        fecha_creacion,
        id
    ),

    CONSTRAINT fk_evento_reserva
        FOREIGN KEY (reserva_id)
        REFERENCES reserva (id)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;



-- Datos de demostración: reservas
-- Los identificadores explícitos permiten asociar
-- los eventos a reservas conocidas en este conjunto de prueba.

INSERT INTO reserva (
    id,
    nombre_huesped,
    email_huesped,
    nombre_alojamiento,
    fecha_entrada,
    fecha_salida,
    estado,
    importe,
    notas,
    fecha_creacion
) VALUES
(
    1, 'Lucía Martín', 'lucia.martin@example.com',
    'Hotel Costa Azul', '2026-10-20', '2026-10-24',
    'pendiente', 480.00, 'Llegada prevista por la tarde',
    '2026-10-15 09:15:00'
),
(
    2, 'Daniel García', 'daniel.garcia@example.com',
    'Apartamentos Sol', '2026-11-02', '2026-11-06',
    'confirmada', 640.00, NULL,
    '2026-10-10 12:30:00'
),
(
    3, 'Marta López', 'marta.lopez@example.com',
    'Casa Rural El Olivo', '2026-11-10', '2026-11-13',
    'cancelada', 270.00, 'Cancelada a petición del huésped',
    '2026-08-01 16:45:00'
),
(
    4, 'Alejandro Ruiz', 'alejandro.ruiz@example.com',
    'Hotel Centro', '2026-10-18', '2026-10-21',
    'confirmada', 315.50, NULL,
    '2026-09-28 10:00:00'
),
(
    5, 'Sofía Fernández', 'sofia.fernandez@example.com',
    'Villa Mar', '2026-12-23', '2026-12-30',
    'pendiente', 1120.00, 'Preferencia por habitación con vistas al mar',
    '2026-10-14 17:20:00'
),
(
    6, 'Pablo Sánchez', 'pablo.sanchez@example.com',
    'Hostal Plaza', '2026-10-12', '2026-10-14',
    'cancelada', 150.00, NULL,
    '2026-09-30 11:10:00'
),
(
    7, 'Inés Romero', 'ines.romero@example.com',
    'Apartamentos Sierra', '2026-11-15', '2026-11-19',
    'confirmada', 520.00, 'Viaje de senderismo',
    '2026-10-10 14:00:00'
),
(
    8, 'Mateo Torres', 'mateo.torres@example.com',
    'Hotel Costa Azul', '2026-10-25', '2026-10-27',
    'pendiente', 198.00, NULL,
    '2026-10-14 18:00:00'
);


-- Historial de eventos de demostración

INSERT INTO reserva_evento (
    reserva_id,
    tipo,
    descripcion,
    fecha_creacion
) VALUES
(1, 'creacion', 'Reserva creada', '2026-10-15 09:15:00'),

(2, 'creacion', 'Reserva creada', '2026-10-10 12:30:00'),
(2, 'confirmacion', 'Reserva confirmada', '2026-10-11 08:20:00'),

(3, 'creacion', 'Reserva creada', '2026-08-01 16:45:00'),
(3, 'confirmacion', 'Reserva confirmada', '2026-08-02 10:00:00'),
(3, 'cancelacion', 'Reserva cancelada a petición del huésped',
 '2026-09-01 15:30:00'),

(4, 'creacion', 'Reserva creada', '2026-09-28 10:00:00'),
(4, 'confirmacion', 'Reserva confirmada', '2026-09-29 09:00:00'),

(5, 'creacion', 'Reserva creada', '2026-10-14 17:20:00'),

(6, 'creacion', 'Reserva creada', '2026-09-30 11:10:00'),
(6, 'confirmacion', 'Reserva confirmada', '2026-10-01 09:00:00'),
(6, 'cancelacion', 'Reserva cancelada',
 '2026-10-05 14:30:00'),

(7, 'creacion', 'Reserva creada', '2026-10-10 14:00:00'),
(7, 'confirmacion', 'Reserva confirmada', '2026-10-11 10:15:00'),

(8, 'creacion', 'Reserva creada', '2026-10-14 18:00:00');