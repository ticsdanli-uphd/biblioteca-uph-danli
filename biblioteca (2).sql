-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 13-08-2025 a las 01:07:31
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `biblioteca`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumnos`
--

CREATE TABLE `alumnos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `carrera_id` int(11) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bibliografia`
--

CREATE TABLE `bibliografia` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `autores` varchar(255) DEFAULT NULL,
  `editorial` varchar(255) DEFAULT NULL,
  `edicion` varchar(50) DEFAULT NULL,
  `anio` int(11) DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `catalogacion` varchar(50) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `cantidad` int(11) DEFAULT 0,
  `foto` varchar(255) DEFAULT NULL,
  `sede_id` int(11) DEFAULT NULL,
  `ingresado_por` int(11) DEFAULT NULL,
  `modificado_por` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultima_modificacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reservas_activas` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carreras`
--

CREATE TABLE `carreras` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `carreras`
--

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `instituciones_externas`
--

CREATE TABLE `instituciones_externas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `instituciones_externas`
--

INSERT INTO `instituciones_externas` (`id`, `nombre`, `descripcion`, `fecha_creacion`) VALUES
(1, 'UNAH', 'Universidad Nacional Autónoma de Honduras', '2025-07-22 17:29:14'),
(2, 'UNITEC', 'Universidad Tecnológica Centroamericana', '2025-07-22 17:29:14'),
(3, 'UNICAH', 'Universidad Católica de Honduras', '2025-07-22 17:29:14'),
(4, 'UTH', 'Universidad Tecnológica de Honduras', '2025-07-22 17:29:14'),
(5, 'UJCV', 'Universidad José Cecilio del Valle', '2025-07-22 17:29:14'),
(6, 'USAP', 'Universidad de San Pedro Sula', '2025-07-22 17:29:14'),
(7, 'UPNFM', 'Universidad Pedagógica Nacional Francisco Morazán', '2025-07-22 17:29:14'),
(8, 'Otra', 'Otra institución no listada', '2025-07-22 17:29:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registro_visitas`
--

CREATE TABLE `registro_visitas` (
  `id` int(11) NOT NULL,
  `bibliografia_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo` varchar(50) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `nombre_alumno` varchar(255) DEFAULT NULL,
  `institucion_id` int(11) DEFAULT NULL,
  `carrera_id` int(11) DEFAULT NULL,
  `es_externo` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_devolucion_esperada` date DEFAULT NULL,
  `devuelto` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Disparadores `registro_visitas`
--
DELIMITER $$
CREATE TRIGGER `trg_registro_visitas_before_insert` BEFORE INSERT ON `registro_visitas` FOR EACH ROW BEGIN
  IF NEW.tipo = 'prestamo' AND NEW.fecha_devolucion_esperada IS NULL THEN
    SET NEW.fecha_devolucion_esperada = DATE_ADD(CURDATE(), INTERVAL 3 DAY);
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_registro_visitas_before_update` BEFORE UPDATE ON `registro_visitas` FOR EACH ROW BEGIN
  IF NEW.tipo = 'prestamo' AND NEW.fecha_devolucion_esperada IS NULL THEN
    SET NEW.fecha_devolucion_esperada = DATE_ADD(CURDATE(), INTERVAL 3 DAY);
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas_libros`
--

CREATE TABLE `reservas_libros` (
  `id` int(11) NOT NULL,
  `bibliografia_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `alumno_id` int(11) DEFAULT NULL,
  `nombre_alumno` varchar(255) DEFAULT NULL,
  `fecha_reserva` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_disponibilidad_estimada` date DEFAULT NULL,
  `estado` enum('pendiente','notificada','cancelada','completada') NOT NULL DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL,
  `carrera_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Disparadores `reservas_libros`
--
DELIMITER $$
CREATE TRIGGER `after_reserva_delete` AFTER DELETE ON `reservas_libros` FOR EACH ROW BEGIN
  CALL actualizar_contador_reservas(OLD.bibliografia_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_reserva_insert` AFTER INSERT ON `reservas_libros` FOR EACH ROW BEGIN
  CALL actualizar_contador_reservas(NEW.bibliografia_id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_reserva_update` AFTER UPDATE ON `reservas_libros` FOR EACH ROW BEGIN
  CALL actualizar_contador_reservas(NEW.bibliografia_id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sedes`
--

CREATE TABLE `sedes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sedes`
--

INSERT INTO `sedes` (`id`, `nombre`) VALUES
(1, 'Tegucigalpa'),
(2, 'Comayagua'),
(3, 'La Lima'),
(4, 'Danli'),
(5, 'Choluteca'),
(6, 'El Progreso'),
(7, 'La Paz');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tesis`
--

CREATE TABLE `tesis` (
  `id` int(11) NOT NULL,
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
  `sede_id` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','usuario') NOT NULL,
  `sede_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `visitas_biblioteca`
--

CREATE TABLE `visitas_biblioteca` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_ingreso` time NOT NULL,
  `hora_salida` time DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alumnos`
--
ALTER TABLE `alumnos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_alumno_usuario` (`usuario_id`),
  ADD KEY `fk_alumno_carrera` (`carrera_id`);

--
-- Indices de la tabla `bibliografia`
--
ALTER TABLE `bibliografia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sede_id` (`sede_id`),
  ADD KEY `ingresado_por` (`ingresado_por`),
  ADD KEY `modificado_por` (`modificado_por`);

--
-- Indices de la tabla `carreras`
--
ALTER TABLE `carreras`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `instituciones_externas`
--
ALTER TABLE `instituciones_externas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `registro_visitas`
--
ALTER TABLE `registro_visitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bibliografia_id` (`bibliografia_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_visita_institucion` (`institucion_id`),
  ADD KEY `fk_visita_carrera` (`carrera_id`);

--
-- Indices de la tabla `reservas_libros`
--
ALTER TABLE `reservas_libros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bibliografia_id` (`bibliografia_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `alumno_id` (`alumno_id`),
  ADD KEY `carrera_id` (`carrera_id`);

--
-- Indices de la tabla `sedes`
--
ALTER TABLE `sedes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tesis`
--
ALTER TABLE `tesis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero` (`numero`),
  ADD KEY `fk_tesis_sede` (`sede_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `sede_id` (`sede_id`);

--
-- Indices de la tabla `visitas_biblioteca`
--
ALTER TABLE `visitas_biblioteca`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alumnos`
--
ALTER TABLE `alumnos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `bibliografia`
--
ALTER TABLE `bibliografia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `carreras`
--
ALTER TABLE `carreras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `instituciones_externas`
--
ALTER TABLE `instituciones_externas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `registro_visitas`
--
ALTER TABLE `registro_visitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reservas_libros`
--
ALTER TABLE `reservas_libros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sedes`
--
ALTER TABLE `sedes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `tesis`
--
ALTER TABLE `tesis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `visitas_biblioteca`
--
ALTER TABLE `visitas_biblioteca`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `alumnos`
--
ALTER TABLE `alumnos`
  ADD CONSTRAINT `fk_alumno_carrera` FOREIGN KEY (`carrera_id`) REFERENCES `carreras` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_alumno_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `bibliografia`
--
ALTER TABLE `bibliografia`
  ADD CONSTRAINT `bibliografia_ibfk_1` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`),
  ADD CONSTRAINT `bibliografia_ibfk_2` FOREIGN KEY (`ingresado_por`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `bibliografia_ibfk_3` FOREIGN KEY (`modificado_por`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `registro_visitas`
--
ALTER TABLE `registro_visitas`
  ADD CONSTRAINT `fk_visita_carrera` FOREIGN KEY (`carrera_id`) REFERENCES `carreras` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_visita_institucion` FOREIGN KEY (`institucion_id`) REFERENCES `instituciones_externas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `registro_visitas_ibfk_1` FOREIGN KEY (`bibliografia_id`) REFERENCES `bibliografia` (`id`),
  ADD CONSTRAINT `registro_visitas_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `reservas_libros`
--
ALTER TABLE `reservas_libros`
  ADD CONSTRAINT `reservas_libros_ibfk_1` FOREIGN KEY (`bibliografia_id`) REFERENCES `bibliografia` (`id`),
  ADD CONSTRAINT `reservas_libros_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `reservas_libros_ibfk_3` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos` (`id`),
  ADD CONSTRAINT `reservas_libros_ibfk_4` FOREIGN KEY (`carrera_id`) REFERENCES `carreras` (`id`);

--
-- Filtros para la tabla `tesis`
--
ALTER TABLE `tesis`
  ADD CONSTRAINT `fk_tesis_sede` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`);

--
-- Filtros para la tabla `visitas_biblioteca`
--
ALTER TABLE `visitas_biblioteca`
  ADD CONSTRAINT `visitas_biblioteca_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
