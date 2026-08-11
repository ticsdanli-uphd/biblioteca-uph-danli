-- Script consolidado de actualizaciones para la base de datos de biblioteca
-- Este script combina todas las actualizaciones realizadas al sistema

-- ========================================================================
-- PARTE 1: Actualizaciones generales del sistema (de sql_updates.sql)
-- ========================================================================

-- 1. Agregar campo carrera_id a la tabla alumnos
ALTER TABLE `alumnos` ADD `carrera_id` INT NULL AFTER `email`;
ALTER TABLE `alumnos` ADD CONSTRAINT `fk_alumno_carrera` FOREIGN KEY (`carrera_id`) REFERENCES `carreras`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- 2. Crear tabla para instituciones externas
CREATE TABLE IF NOT EXISTS `instituciones_externas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Modificar la tabla registro_visitas para incluir más información
ALTER TABLE `registro_visitas` ADD COLUMN IF NOT EXISTS `institucion_id` INT NULL AFTER `nombre_alumno`;
ALTER TABLE `registro_visitas` ADD COLUMN IF NOT EXISTS `carrera_id` INT NULL AFTER `institucion_id`;
ALTER TABLE `registro_visitas` ADD COLUMN IF NOT EXISTS `es_externo` TINYINT(1) NOT NULL DEFAULT '0' AFTER `carrera_id`;

-- Agregar las restricciones de clave foránea si no existen
ALTER TABLE `registro_visitas` ADD CONSTRAINT `fk_visita_institucion` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones_externas`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `registro_visitas` ADD CONSTRAINT `fk_visita_carrera` FOREIGN KEY (`carrera_id`) REFERENCES `carreras`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- 4. Insertar algunas instituciones externas de ejemplo
INSERT IGNORE INTO `instituciones_externas` (`nombre`, `descripcion`) VALUES
('UNAH', 'Universidad Nacional Autónoma de Honduras'),
('UNITEC', 'Universidad Tecnológica Centroamericana'),
('UNICAH', 'Universidad Católica de Honduras'),
('UTH', 'Universidad Tecnológica de Honduras'),
('UJCV', 'Universidad José Cecilio del Valle'),
('USAP', 'Universidad de San Pedro Sula'),
('UPNFM', 'Universidad Pedagógica Nacional Francisco Morazán'),
('Otra', 'Otra institución no listada');

-- ========================================================================
-- PARTE 2: Sistema de reservas (de sql_updates_reservas.sql)
-- ========================================================================

-- 1. Crear tabla para las reservas de libros
CREATE TABLE IF NOT EXISTS `reservas_libros` (
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
  KEY `bibliografia_id` (`bibliografia_id`),
  KEY `user_id` (`user_id`),
  KEY `alumno_id` (`alumno_id`),
  KEY `carrera_id` (`carrera_id`),
  CONSTRAINT `reservas_libros_ibfk_1` FOREIGN KEY (`bibliografia_id`) REFERENCES `bibliografia` (`id`),
  CONSTRAINT `reservas_libros_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `reservas_libros_ibfk_3` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos` (`id`),
  CONSTRAINT `reservas_libros_ibfk_4` FOREIGN KEY (`carrera_id`) REFERENCES `carreras` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Añadir campo para contar reservas activas en la tabla bibliografia
ALTER TABLE `bibliografia` ADD COLUMN IF NOT EXISTS `reservas_activas` int(11) NOT NULL DEFAULT 0;

-- 3. Procedimiento almacenado para actualizar el contador de reservas
DELIMITER $$
DROP PROCEDURE IF EXISTS `actualizar_contador_reservas` $$
CREATE PROCEDURE `actualizar_contador_reservas`(IN libro_id INT)
BEGIN
  DECLARE total_reservas INT;
  
  -- Contar reservas pendientes y notificadas
  SELECT COUNT(*) INTO total_reservas 
  FROM reservas_libros 
  WHERE bibliografia_id = libro_id 
  AND estado IN ('pendiente', 'notificada');
  
  -- Actualizar el contador en la tabla bibliografia
  UPDATE bibliografia SET reservas_activas = total_reservas WHERE id = libro_id;
END$$
DELIMITER ;

-- 4. Trigger para actualizar el contador cuando se añade una reserva
DELIMITER $$
DROP TRIGGER IF EXISTS `after_reserva_insert` $$
CREATE TRIGGER `after_reserva_insert` AFTER INSERT ON `reservas_libros`
FOR EACH ROW
BEGIN
  CALL actualizar_contador_reservas(NEW.bibliografia_id);
END$$
DELIMITER ;

-- 5. Trigger para actualizar el contador cuando se actualiza una reserva
DELIMITER $$
DROP TRIGGER IF EXISTS `after_reserva_update` $$
CREATE TRIGGER `after_reserva_update` AFTER UPDATE ON `reservas_libros`
FOR EACH ROW
BEGIN
  CALL actualizar_contador_reservas(NEW.bibliografia_id);
END$$
DELIMITER ;

-- 6. Trigger para actualizar el contador cuando se elimina una reserva
DELIMITER $$
DROP TRIGGER IF EXISTS `after_reserva_delete` $$
CREATE TRIGGER `after_reserva_delete` AFTER DELETE ON `reservas_libros`
FOR EACH ROW
BEGIN
  CALL actualizar_contador_reservas(OLD.bibliografia_id);
END$$
DELIMITER ;

-- FOTOS FRONTAL Y TRASERA DE LIBROS
ALTER TABLE bibliografia ADD COLUMN foto_frontal VARCHAR(255) NULL AFTER foto;
ALTER TABLE bibliografia ADD COLUMN foto_trasera VARCHAR(255) NULL AFTER foto_frontal;
