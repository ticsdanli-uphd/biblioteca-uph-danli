-- ============================================================
-- BASE DE DATOS COMPLETA - BIBLIOTECA UPH
-- VERSIÓN CORREGIDA Y COMPATIBLE CON EL SISTEMA ACTUAL
-- Sede de trabajo: Danlí (sede_id = 4)
-- MySQL / MariaDB
-- ============================================================

CREATE DATABASE IF NOT EXISTS `biblioteca`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `biblioteca`;

SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
SET FOREIGN_KEY_CHECKS=0;

DROP TRIGGER IF EXISTS `after_reserva_insert`;
DROP TRIGGER IF EXISTS `after_reserva_update`;
DROP TRIGGER IF EXISTS `after_reserva_delete`;
DROP TRIGGER IF EXISTS `trg_registro_visitas_before_insert`;
DROP TRIGGER IF EXISTS `trg_registro_visitas_before_update`;
DROP PROCEDURE IF EXISTS `actualizar_contador_reservas`;

DROP TABLE IF EXISTS `solicitudes_prestamo`;
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
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sede_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sedes` (`id`,`nombre`) VALUES
(1,'Tegucigalpa'),(2,'Comayagua'),(3,'La Lima'),(4,'Danli'),
(5,'Choluteca'),(6,'El Progreso'),(7,'La Paz');
ALTER TABLE `sedes` AUTO_INCREMENT=8;

CREATE TABLE `carreras` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_carrera_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `carreras` (`id`,`nombre`) VALUES
(1,'Ingeniería Electrónica'),
(2,'Ingeniería en Sistemas Computacionales'),
(3,'Ingeniería de la Producción Industrial'),
(4,'Licenciatura en Gerencia de Negocios'),
(5,'Licenciatura de Gerencia en Turismo'),
(6,'Licenciatura en Psicología'),
(7,'Licenciatura en Derecho'),
(8,'Maestría en Derechos Humanos'),
(9,'Maestría en Recursos Humanos y Gestión Empresarial');
ALTER TABLE `carreras` AUTO_INCREMENT=10;

CREATE TABLE `instituciones_externas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_institucion_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `instituciones_externas` (`id`,`nombre`,`descripcion`) VALUES
(1,'UNAH','Universidad Nacional Autónoma de Honduras'),
(2,'UNITEC','Universidad Tecnológica Centroamericana'),
(3,'UNICAH','Universidad Católica de Honduras'),
(4,'UTH','Universidad Tecnológica de Honduras'),
(5,'UJCV','Universidad José Cecilio del Valle'),
(6,'USAP','Universidad de San Pedro Sula'),
(7,'UPNFM','Universidad Pedagógica Nacional Francisco Morazán'),
(8,'Otra','Otra institución no listada');
ALTER TABLE `instituciones_externas` AUTO_INCREMENT=9;

-- ============================================================
-- USUARIOS
-- activo se incluye porque el login/prestamos lo utilizan.
-- Se acepta alumno y usuario para compatibilidad; los nuevos
-- estudiantes deben crearse con role='alumno'.
-- ============================================================
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `nombre` varchar(150) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','docente','alumno','usuario') NOT NULL DEFAULT 'alumno',
  `sede_id` int NOT NULL DEFAULT 4,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuario_username` (`username`),
  KEY `idx_usuario_sede` (`sede_id`),
  KEY `idx_usuario_role` (`role`),
  KEY `idx_usuario_activo` (`activo`),
  CONSTRAINT `fk_usuario_sede`
    FOREIGN KEY (`sede_id`) REFERENCES `sedes`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ALUMNOS
-- El usuario queda vinculado automáticamente por usuario_id.
-- Carrera es opcional para no bloquear la creación.
-- ============================================================
CREATE TABLE `alumnos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `carrera_id` int DEFAULT NULL,
  `sede_id` int NOT NULL DEFAULT 4,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_alumno_usuario` (`usuario_id`),
  KEY `idx_alumno_carrera` (`carrera_id`),
  KEY `idx_alumno_sede` (`sede_id`),
  CONSTRAINT `fk_alumno_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_alumno_carrera`
    FOREIGN KEY (`carrera_id`) REFERENCES `carreras`(`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_alumno_sede`
    FOREIGN KEY (`sede_id`) REFERENCES `sedes`(`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DOCENTES
-- ============================================================
CREATE TABLE `docentes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `carrera_id` int DEFAULT NULL,
  `sede_id` int NOT NULL DEFAULT 4,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_docente_usuario` (`usuario_id`),
  KEY `idx_docente_carrera` (`carrera_id`),
  KEY `idx_docente_sede` (`sede_id`),
  CONSTRAINT `fk_docente_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_docente_carrera`
    FOREIGN KEY (`carrera_id`) REFERENCES `carreras`(`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_docente_sede`
    FOREIGN KEY (`sede_id`) REFERENCES `sedes`(`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BIBLIOGRAFÍA
-- ============================================================
CREATE TABLE `bibliografia` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `dewey` varchar(50) DEFAULT NULL,
  `clasificacion` varchar(100) DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `autores` varchar(255) DEFAULT NULL,
  `editorial` varchar(255) DEFAULT NULL,
  `edicion` varchar(100) DEFAULT NULL,
  `anio` int DEFAULT NULL,
  `isbn` varchar(30) DEFAULT NULL,
  `estado` enum('Disponible','Prestado','Deteriorado','Baja') NOT NULL DEFAULT 'Disponible',
  `ubicacion` varchar(150) DEFAULT 'Biblioteca UPH Danlí',
  `fecha_ingreso` date DEFAULT NULL,
  `idioma` varchar(50) DEFAULT NULL,
  `carrera_id` int DEFAULT NULL,
  `catalogacion` varchar(50) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `cantidad` int NOT NULL DEFAULT 0,
  `foto` varchar(255) DEFAULT NULL,
  `foto_frontal` varchar(255) DEFAULT NULL,
  `foto_trasera` varchar(255) DEFAULT NULL,
  `sede_id` int NOT NULL DEFAULT 4,
  `ingresado_por` int DEFAULT NULL,
  `modificado_por` int DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultima_modificacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `reservas_activas` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_bibliografia_codigo` (`codigo`),
  KEY `idx_bibliografia_sede` (`sede_id`),
  KEY `idx_bibliografia_carrera` (`carrera_id`),
  CONSTRAINT `fk_bibliografia_sede`
    FOREIGN KEY (`sede_id`) REFERENCES `sedes`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_bibliografia_carrera`
    FOREIGN KEY (`carrera_id`) REFERENCES `carreras`(`id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_bibliografia_ingresado`
    FOREIGN KEY (`ingresado_por`) REFERENCES `usuarios`(`id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_bibliografia_modificado`
    FOREIGN KEY (`modificado_por`) REFERENCES `usuarios`(`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `bibliografia`
(`codigo`,`dewey`,`clasificacion`,`nombre`,`autores`,`editorial`,`edicion`,`anio`,`isbn`,`estado`,`ubicacion`,`cantidad`,`sede_id`)
VALUES
('UPH-04-BLGM-000001','100','Generalidades','dBASE III Plus',
 'Julian Martinez Valero','ANAYA Multimedia','Cuarta Reimpresion',
 1994,'84-7614-444-X','Disponible','Biblioteca UPH Danlí',1,4);

-- ============================================================
-- REGISTRO DE VISITAS / PRÉSTAMOS
-- carrera_id permite NULL. Nunca se debe enviar 0 como carrera.
-- ============================================================
CREATE TABLE `registro_visitas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bibliografia_id` int DEFAULT NULL,
  `user_id` int NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tipo` varchar(50) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `nombre_alumno` varchar(255) DEFAULT NULL,
  `institucion_id` int DEFAULT NULL,
  `carrera_id` int DEFAULT NULL,
  `es_externo` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_devolucion_esperada` date DEFAULT NULL,
  `devuelto` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_devolucion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_visita_bibliografia` (`bibliografia_id`),
  KEY `idx_visita_usuario` (`user_id`),
  KEY `idx_visita_institucion` (`institucion_id`),
  KEY `idx_visita_carrera` (`carrera_id`),
  KEY `idx_visita_tipo_devuelto` (`tipo`,`devuelto`),
  CONSTRAINT `fk_visita_bibliografia`
    FOREIGN KEY (`bibliografia_id`) REFERENCES `bibliografia`(`id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_visita_usuario`
    FOREIGN KEY (`user_id`) REFERENCES `usuarios`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_visita_institucion`
    FOREIGN KEY (`institucion_id`) REFERENCES `instituciones_externas`(`id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_visita_carrera`
    FOREIGN KEY (`carrera_id`) REFERENCES `carreras`(`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- RESERVAS
-- ============================================================
CREATE TABLE `reservas_libros` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bibliografia_id` int NOT NULL,
  `user_id` int NOT NULL,
  `alumno_id` int DEFAULT NULL,
  `nombre_alumno` varchar(255) DEFAULT NULL,
  `fecha_reserva` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_disponibilidad_estimada` date DEFAULT NULL,
  `estado` enum('pendiente','notificada','cancelada','completada') NOT NULL DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL,
  `carrera_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_reserva_bibliografia` (`bibliografia_id`),
  KEY `idx_reserva_usuario` (`user_id`),
  KEY `idx_reserva_alumno` (`alumno_id`),
  KEY `idx_reserva_carrera` (`carrera_id`),
  CONSTRAINT `fk_reserva_bibliografia`
    FOREIGN KEY (`bibliografia_id`) REFERENCES `bibliografia`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_reserva_usuario`
    FOREIGN KEY (`user_id`) REFERENCES `usuarios`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_reserva_alumno`
    FOREIGN KEY (`alumno_id`) REFERENCES `alumnos`(`id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_reserva_carrera`
    FOREIGN KEY (`carrera_id`) REFERENCES `carreras`(`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TESIS
-- ============================================================
CREATE TABLE `tesis` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero` varchar(50) DEFAULT NULL,
  `cuenta` varchar(50) NOT NULL,
  `alumno` varchar(255) NOT NULL,
  `carrera` varchar(100) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `anio_egresado` int DEFAULT NULL,
  `asesor_metodologico` varchar(255) DEFAULT NULL,
  `asesor_tematico` varchar(255) DEFAULT NULL,
  `cantidad` int DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sede_id` int NOT NULL DEFAULT 4,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tesis_numero` (`numero`),
  KEY `idx_tesis_sede` (`sede_id`),
  CONSTRAINT `fk_tesis_sede`
    FOREIGN KEY (`sede_id`) REFERENCES `sedes`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- VISITAS A LA BIBLIOTECA
-- ============================================================
CREATE TABLE `visitas_biblioteca` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `fecha` date NOT NULL,
  `hora_ingreso` time NOT NULL,
  `hora_salida` time DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_visitas_usuario` (`user_id`),
  CONSTRAINT `fk_visitas_usuario`
    FOREIGN KEY (`user_id`) REFERENCES `usuarios`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SOLICITUDES DE PRÉSTAMO
-- Compatible con la bandeja administrativa y solicitud_accion.php:
-- user_id, nombre_solicitante, carrera_id, motivo_rechazo,
-- fecha_entrega y registro_visita_id.
-- ============================================================
CREATE TABLE `solicitudes_prestamo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bibliografia_id` int NOT NULL,
  `user_id` int NOT NULL,
  `nombre_solicitante` varchar(255) NOT NULL,
  `carrera_id` int DEFAULT NULL,
  `sede_id` int NOT NULL DEFAULT 4,
  `estado` enum('pendiente','aprobada','prestado','rechazada','cancelada','completada') NOT NULL DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL,
  `motivo_rechazo` text DEFAULT NULL,
  `respuesta_admin` text DEFAULT NULL,
  `fecha_solicitud` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_respuesta` datetime DEFAULT NULL,
  `fecha_entrega` datetime DEFAULT NULL,
  `atendido_por` int DEFAULT NULL,
  `registro_visita_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_solicitud_libro` (`bibliografia_id`),
  KEY `idx_solicitud_usuario` (`user_id`),
  KEY `idx_solicitud_carrera` (`carrera_id`),
  KEY `idx_solicitud_sede` (`sede_id`),
  KEY `idx_solicitud_estado` (`estado`),
  KEY `idx_solicitud_admin` (`atendido_por`),
  KEY `idx_solicitud_registro` (`registro_visita_id`),
  CONSTRAINT `fk_solicitud_libro`
    FOREIGN KEY (`bibliografia_id`) REFERENCES `bibliografia`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_solicitud_usuario`
    FOREIGN KEY (`user_id`) REFERENCES `usuarios`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_solicitud_carrera`
    FOREIGN KEY (`carrera_id`) REFERENCES `carreras`(`id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_solicitud_sede`
    FOREIGN KEY (`sede_id`) REFERENCES `sedes`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_solicitud_admin`
    FOREIGN KEY (`atendido_por`) REFERENCES `usuarios`(`id`)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_solicitud_registro`
    FOREIGN KEY (`registro_visita_id`) REFERENCES `registro_visitas`(`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CONTADOR DE RESERVAS
-- ============================================================
DELIMITER $$

CREATE PROCEDURE `actualizar_contador_reservas`(IN p_bibliografia_id INT)
BEGIN
  UPDATE bibliografia
  SET reservas_activas = (
    SELECT COUNT(*)
    FROM reservas_libros
    WHERE bibliografia_id=p_bibliografia_id
      AND estado IN ('pendiente','notificada')
  )
  WHERE id=p_bibliografia_id;
END$$

CREATE TRIGGER `after_reserva_insert`
AFTER INSERT ON reservas_libros
FOR EACH ROW
BEGIN
  CALL actualizar_contador_reservas(NEW.bibliografia_id);
END$$

CREATE TRIGGER `after_reserva_update`
AFTER UPDATE ON reservas_libros
FOR EACH ROW
BEGIN
  CALL actualizar_contador_reservas(OLD.bibliografia_id);
  IF NEW.bibliografia_id <> OLD.bibliografia_id THEN
    CALL actualizar_contador_reservas(NEW.bibliografia_id);
  END IF;
END$$

CREATE TRIGGER `after_reserva_delete`
AFTER DELETE ON reservas_libros
FOR EACH ROW
BEGIN
  CALL actualizar_contador_reservas(OLD.bibliografia_id);
END$$

-- ============================================================
-- FECHA DE DEVOLUCIÓN AUTOMÁTICA: 3 DÍAS
-- ============================================================
CREATE TRIGGER `trg_registro_visitas_before_insert`
BEFORE INSERT ON registro_visitas
FOR EACH ROW
BEGIN
  IF NEW.tipo='prestamo' AND NEW.fecha_devolucion_esperada IS NULL THEN
    SET NEW.fecha_devolucion_esperada=DATE_ADD(CURDATE(),INTERVAL 3 DAY);
  END IF;
END$$

CREATE TRIGGER `trg_registro_visitas_before_update`
BEFORE UPDATE ON registro_visitas
FOR EACH ROW
BEGIN
  IF NEW.tipo='prestamo' AND NEW.fecha_devolucion_esperada IS NULL THEN
    SET NEW.fecha_devolucion_esperada=DATE_ADD(CURDATE(),INTERVAL 3 DAY);
  END IF;
END$$

DELIMITER ;

SET FOREIGN_KEY_CHECKS=1;
COMMIT;

-- ============================================================
-- VERIFICACIÓN FINAL
-- ============================================================
SELECT 'BASE DE DATOS BIBLIOTECA UPH CREADA CORRECTAMENTE' AS resultado;
SELECT id,nombre FROM sedes WHERE id=4;
DESCRIBE usuarios;
DESCRIBE alumnos;
DESCRIBE docentes;
DESCRIBE solicitudes_prestamo;
DESCRIBE registro_visitas;