-- Modificaciones a la base de datos para mejorar el sistema de biblioteca

-- 1. Agregar campo carrera_id a la tabla alumnos
ALTER TABLE `alumnos` ADD `carrera_id` INT NULL AFTER `email`;
ALTER TABLE `alumnos` ADD CONSTRAINT `fk_alumno_carrera` FOREIGN KEY (`carrera_id`) REFERENCES `carreras`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- 2. Crear tabla para instituciones externas
CREATE TABLE `instituciones_externas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Modificar la tabla registro_visitas para incluir más información
ALTER TABLE `registro_visitas` ADD `institucion_id` INT NULL AFTER `nombre_alumno`;
ALTER TABLE `registro_visitas` ADD `carrera_id` INT NULL AFTER `institucion_id`;
ALTER TABLE `registro_visitas` ADD `es_externo` TINYINT(1) NOT NULL DEFAULT '0' AFTER `carrera_id`;
ALTER TABLE `registro_visitas` ADD CONSTRAINT `fk_visita_institucion` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones_externas`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `registro_visitas` ADD CONSTRAINT `fk_visita_carrera` FOREIGN KEY (`carrera_id`) REFERENCES `carreras`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- 4. Insertar algunas instituciones externas de ejemplo
INSERT INTO `instituciones_externas` (`nombre`, `descripcion`) VALUES
('UNAH', 'Universidad Nacional Autónoma de Honduras'),
('UNITEC', 'Universidad Tecnológica Centroamericana'),
('UNICAH', 'Universidad Católica de Honduras'),
('UTH', 'Universidad Tecnológica de Honduras'),
('UJCV', 'Universidad José Cecilio del Valle'),
('USAP', 'Universidad de San Pedro Sula'),
('UPNFM', 'Universidad Pedagógica Nacional Francisco Morazán'),
('Otra', 'Otra institución no listada');