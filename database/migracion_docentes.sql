-- ============================================================
-- MIGRACIÓN DOCENTES - BIBLIOTECA UPH DANLÍ
-- Ejecutar en la base de datos biblioteca si ya existe.
-- ============================================================

USE `biblioteca`;

CREATE TABLE IF NOT EXISTS `docentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `carrera_id` int(11) DEFAULT NULL,
  `sede_id` int(11) NOT NULL DEFAULT 4,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_docente_usuario` (`usuario_id`),
  KEY `idx_docente_carrera` (`carrera_id`),
  KEY `idx_docente_sede` (`sede_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Si las claves foráneas no existen, se pueden agregar después de
-- comprobar que los tipos de las columnas coinciden en tu instalación.
