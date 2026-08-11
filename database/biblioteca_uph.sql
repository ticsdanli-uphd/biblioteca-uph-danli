-- ============================================================
-- BASE DE DATOS COMPLETA - BIBLIOTECA UPH
-- Versión corregida para Biblioteca UPH - Danlí
-- MariaDB / MySQL
-- ============================================================

CREATE DATABASE IF NOT EXISTS `biblioteca`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `biblioteca`;

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `visitas_biblioteca`;
DROP TABLE IF EXISTS `reservas_libros`;
DROP TABLE IF EXISTS `registro_visitas`;
DROP TABLE IF EXISTS `tesis`;
DROP TABLE IF EXISTS `bibliografia`;
DROP TABLE IF EXISTS `docentes`;
DROP TABLE IF EXISTS `alumnos`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `instituciones_externas`;
DROP TABLE IF EXISTS `carreras`;
DROP TABLE IF EXISTS `sedes`;

CREATE TABLE `sedes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sede_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sedes` (`id`, `nombre`) VALUES
(1, 'Tegucigalpa'),
(2, 'Comayagua'),
(3, 'La Lima'),
(4, 'Danli'),
(5, 'Choluteca'),
(6, 'El Progreso'),
(7, 'La Paz');

ALTER TABLE `sedes` AUTO_INCREMENT = 8;

CREATE TABLE `carreras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_carrera_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `carreras` (`id`, `nombre`) VALUES
(3, 'Ingeniería de la Producción Industrial'),
(1, 'Ingeniería Electrónica'),
(2, 'Ingeniería en Sistemas Computacionales'),
(5, 'Licenciatura de Gerencia en Turismo'),
(7, 'Licenciatura en Derecho'),
(4, 'Licenciatura en Gerencia de Negocios'),
(6, 'Licenciatura en Psicología'),
(8, 'Maestría en Derechos Humanos'),
(9, 'Maestría en Recursos Humanos y Gestión Empresarial');

ALTER TABLE `carreras` AUTO_INCREMENT = 10;

CREATE TABLE `instituciones_externas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_institucion_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `instituciones_externas`
(`id`, `nombre`, `descripcion`) VALUES
(1, 'UNAH', 'Universidad Nacional Autónoma de Honduras'),
(2, 'UNITEC', 'Universidad Tecnológica Centroamericana'),
(3, 'UNICAH', 'Universidad Católica de Honduras'),
(4, 'UTH', 'Universidad Tecnológica de Honduras'),
(5, 'UJCV', 'Universidad José Cecilio del Valle'),
(6, 'USAP', 'Universidad de San Pedro Sula'),
(7, 'UPNFM', 'Universidad Pedagógica Nacional Francisco Morazán'),
(8, 'Otra', 'Otra institución no listada');

ALTER TABLE `instituciones_externas` AUTO_INCREMENT = 9;

-- Usuarios: admin, docente y usuario.
-- NO se crea ningún usuario automáticamente.
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `nombre` varchar(150) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','docente','alumno') NOT NULL DEFAULT 'alumno',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `sede_id` int(11) NOT NULL DEFAULT 4,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuario_username` (`username`),
  KEY `idx_usuario_sede` (`sede_id`),
  CONSTRAINT `fk_usuario_sede`
    FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `alumnos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `carrera_id` int(11) DEFAULT NULL,
  `sede_id` int(11) NOT NULL DEFAULT 4,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_alumno_usuario` (`usuario_id`),
  KEY `idx_alumno_carrera` (`carrera_id`),
  CONSTRAINT `fk_alumno_carrera`
    FOREIGN KEY (`carrera_id`) REFERENCES `carreras` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_alumno_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `docentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `carrera_id` int(11) DEFAULT NULL,
  `sede_id` int(11) NOT NULL DEFAULT 4,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_docente_usuario` (`usuario_id`),
  KEY `idx_docente_carrera` (`carrera_id`),
  KEY `idx_docente_sede` (`sede_id`),
  CONSTRAINT `fk_docente_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_docente_carrera` FOREIGN KEY (`carrera_id`) REFERENCES `carreras` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_docente_sede` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catálogo con los campos solicitados:
-- Código, Dewey, Clasificación, Nombre, Autor(es), Editorial,
-- Edición, Año, ISBN, Estado, Ubicación, Fecha de ingreso,
-- Idioma, Carrera, Cantidad y Sede ID.
CREATE TABLE `bibliografia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `dewey` varchar(50) DEFAULT NULL,
  `clasificacion` varchar(100) DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `autores` varchar(255) DEFAULT NULL,
  `editorial` varchar(255) DEFAULT NULL,
  `edicion` varchar(100) DEFAULT NULL,
  `anio` int(11) DEFAULT NULL,
  `isbn` varchar(30) DEFAULT NULL,
  `estado` enum('Disponible','Prestado','Deteriorado','Baja') NOT NULL DEFAULT 'Disponible',
  `ubicacion` varchar(150) DEFAULT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `idioma` varchar(50) DEFAULT NULL,
  `carrera_id` int(11) DEFAULT NULL,
  `catalogacion` varchar(50) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 0,
  `foto` varchar(255) DEFAULT NULL,
  `sede_id` int(11) NOT NULL DEFAULT 4,
  `ingresado_por` int(11) DEFAULT NULL,
  `modificado_por` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultima_modificacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reservas_activas` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_bibliografia_codigo` (`codigo`),
  KEY `idx_bibliografia_sede` (`sede_id`),
  KEY `idx_bibliografia_carrera` (`carrera_id`),
  KEY `idx_bibliografia_ingresado` (`ingresado_por`),
  KEY `idx_bibliografia_modificado` (`modificado_por`),
  CONSTRAINT `fk_bibliografia_sede`
    FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_bibliografia_carrera`
    FOREIGN KEY (`carrera_id`) REFERENCES `carreras` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_bibliografia_ingresado`
    FOREIGN KEY (`ingresado_por`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_bibliografia_modificado`
    FOREIGN KEY (`modificado_por`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro inicial de ejemplo para Danlí
INSERT INTO `bibliografia`
(`codigo`, `dewey`, `clasificacion`, `nombre`, `autores`, `editorial`,
 `edicion`, `anio`, `isbn`, `estado`, `ubicacion`, `fecha_ingreso`,
 `idioma`, `carrera_id`, `cantidad`, `sede_id`)
VALUES
('UPH-04-BLGM-000001', '100', 'Generalidades', 'dBASE III Plus',
 'Julian Martinez Valero', 'ANAYA Multimedia', 'Cuarta Reimpresion',
 1994, '84-7614-444-X', 'Disponible', NULL, NULL, NULL, NULL, 1, 4);

CREATE TABLE `registro_visitas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bibliografia_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `beneficiario_usuario_id` int(11) DEFAULT NULL,
  `beneficiario_tipo` enum('alumno','docente','externo') DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo` varchar(50) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `nombre_alumno` varchar(255) DEFAULT NULL,
  `institucion_id` int(11) DEFAULT NULL,
  `carrera_id` int(11) DEFAULT NULL,
  `es_externo` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_devolucion_esperada` date DEFAULT NULL,
  `devuelto` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_visita_bibliografia` (`bibliografia_id`),
  KEY `idx_visita_usuario` (`user_id`),
  KEY `idx_visita_institucion` (`institucion_id`),
  KEY `idx_visita_carrera` (`carrera_id`),
  KEY `idx_visita_tipo_devuelto` (`tipo`,`devuelto`),
  CONSTRAINT `fk_visita_bibliografia`
    FOREIGN KEY (`bibliografia_id`) REFERENCES `bibliografia` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_visita_usuario`
    FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_visita_institucion`
    FOREIGN KEY (`institucion_id`) REFERENCES `instituciones_externas` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_visita_carrera`
    FOREIGN KEY (`carrera_id`) REFERENCES `carreras` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reservas_libros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bibliografia_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `alumno_id` int(11) DEFAULT NULL,
  `nombre_alumno` varchar(255) DEFAULT NULL,
  `fecha_reserva` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_disponibilidad_estimada` date DEFAULT NULL,
  `estado` enum('pendiente','notificada','cancelada','completada') NOT NULL DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL,
  `carrera_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_reserva_bibliografia` (`bibliografia_id`),
  KEY `idx_reserva_usuario` (`user_id`),
  KEY `idx_reserva_alumno` (`alumno_id`),
  KEY `idx_reserva_carrera` (`carrera_id`),
  CONSTRAINT `fk_reserva_bibliografia`
    FOREIGN KEY (`bibliografia_id`) REFERENCES `bibliografia` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_reserva_usuario`
    FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_reserva_alumno`
    FOREIGN KEY (`alumno_id`) REFERENCES `alumnos` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_reserva_carrera`
    FOREIGN KEY (`carrera_id`) REFERENCES `carreras` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tesis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` varchar(50) DEFAULT NULL,
  `cuenta` varchar(50) NOT NULL,
  `alumno` varchar(255) NOT NULL,
  `carrera` varchar(100) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `anio_egresado` int(11) DEFAULT NULL,
  `asesor_metodologico` varchar(255) DEFAULT NULL,
  `asesor_tematico` varchar(255) DEFAULT NULL,
  `cantidad` int(11) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `sede_id` int(11) NOT NULL DEFAULT 4,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tesis_numero` (`numero`),
  KEY `idx_tesis_sede` (`sede_id`),
  CONSTRAINT `fk_tesis_sede`
    FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `visitas_biblioteca` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_ingreso` time NOT NULL,
  `hora_salida` time DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_visitas_usuario` (`user_id`),
  CONSTRAINT `fk_visitas_usuario`
    FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Procedimiento para mantener reservas_activas actualizado.
DROP PROCEDURE IF EXISTS `actualizar_contador_reservas`;

DELIMITER $$

CREATE PROCEDURE `actualizar_contador_reservas`(IN p_bibliografia_id INT)
BEGIN
    UPDATE bibliografia
    SET reservas_activas = (
        SELECT COUNT(*)
        FROM reservas_libros
        WHERE bibliografia_id = p_bibliografia_id
          AND estado IN ('pendiente','notificada')
    )
    WHERE id = p_bibliografia_id;
END$$

DELIMITER ;

DROP TRIGGER IF EXISTS `after_reserva_insert`;
DROP TRIGGER IF EXISTS `after_reserva_update`;
DROP TRIGGER IF EXISTS `after_reserva_delete`;

DELIMITER $$

CREATE TRIGGER `after_reserva_insert`
AFTER INSERT ON `reservas_libros`
FOR EACH ROW
BEGIN
    CALL actualizar_contador_reservas(NEW.bibliografia_id);
END$$

CREATE TRIGGER `after_reserva_update`
AFTER UPDATE ON `reservas_libros`
FOR EACH ROW
BEGIN
    CALL actualizar_contador_reservas(OLD.bibliografia_id);

    IF NEW.bibliografia_id <> OLD.bibliografia_id THEN
        CALL actualizar_contador_reservas(NEW.bibliografia_id);
    END IF;
END$$

CREATE TRIGGER `after_reserva_delete`
AFTER DELETE ON `reservas_libros`
FOR EACH ROW
BEGIN
    CALL actualizar_contador_reservas(OLD.bibliografia_id);
END$$

DELIMITER ;

DROP TRIGGER IF EXISTS `trg_registro_visitas_before_insert`;
DROP TRIGGER IF EXISTS `trg_registro_visitas_before_update`;

DELIMITER $$

CREATE TRIGGER `trg_registro_visitas_before_insert`
BEFORE INSERT ON `registro_visitas`
FOR EACH ROW
BEGIN
    IF NEW.tipo = 'prestamo'
       AND NEW.fecha_devolucion_esperada IS NULL THEN
        SET NEW.fecha_devolucion_esperada =
            DATE_ADD(CURDATE(), INTERVAL 3 DAY);
    END IF;
END$$

CREATE TRIGGER `trg_registro_visitas_before_update`
BEFORE UPDATE ON `registro_visitas`
FOR EACH ROW
BEGIN
    IF NEW.tipo = 'prestamo'
       AND NEW.fecha_devolucion_esperada IS NULL THEN
        SET NEW.fecha_devolucion_esperada =
            DATE_ADD(CURDATE(), INTERVAL 3 DAY);
    END IF;
END$$

DELIMITER ;

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;

-- ============================================================
-- DATOS IMPORTANTES
-- Danlí = sede_id 4
-- Roles permitidos: admin, docente, usuario
-- No se crea ningún usuario automáticamente.
-- ============================================================
-- ============================================================
-- NOTA DE COMPATIBILIDAD
-- ============================================================
-- La columna correcta del catálogo es `autores`.
-- En PHP debe utilizarse:
--   INSERT ... autores ...
--   SELECT autores FROM bibliografia
--   WHERE autores LIKE ...
--
-- NO utilizar `autor`, porque esa columna no existe.
-- ===================================================