-- Estructura de tabla para la tabla `reservas_libros`

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
  KEY `bibliografia_id` (`bibliografia_id`),
  KEY `user_id` (`user_id`),
  KEY `alumno_id` (`alumno_id`),
  KEY `carrera_id` (`carrera_id`),
  CONSTRAINT `reservas_libros_ibfk_1` FOREIGN KEY (`bibliografia_id`) REFERENCES `bibliografia` (`id`),
  CONSTRAINT `reservas_libros_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `reservas_libros_ibfk_3` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos` (`id`),
  CONSTRAINT `reservas_libros_ibfk_4` FOREIGN KEY (`carrera_id`) REFERENCES `carreras` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Añadir campo para contar reservas activas en la tabla bibliografia
ALTER TABLE `bibliografia` ADD COLUMN `reservas_activas` int(11) NOT NULL DEFAULT 0;

-- Procedimiento almacenado para actualizar el contador de reservas
DELIMITER $$
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

-- Trigger para actualizar el contador cuando se añade una reserva
DELIMITER $$
CREATE TRIGGER `after_reserva_insert` AFTER INSERT ON `reservas_libros`
FOR EACH ROW
BEGIN
  CALL actualizar_contador_reservas(NEW.bibliografia_id);
END$$
DELIMITER ;

-- Trigger para actualizar el contador cuando se actualiza una reserva
DELIMITER $$
CREATE TRIGGER `after_reserva_update` AFTER UPDATE ON `reservas_libros`
FOR EACH ROW
BEGIN
  CALL actualizar_contador_reservas(NEW.bibliografia_id);
END$$
DELIMITER ;

-- Trigger para actualizar el contador cuando se elimina una reserva
DELIMITER $$
CREATE TRIGGER `after_reserva_delete` AFTER DELETE ON `reservas_libros`
FOR EACH ROW
BEGIN
  CALL actualizar_contador_reservas(OLD.bibliografia_id);
END$$
DELIMITER ;