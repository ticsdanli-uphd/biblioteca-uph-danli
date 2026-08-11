-- ============================================================
-- MIGRACIÓN - BIBLIOTECA UPH DANLÍ
-- Corrige instalaciones existentes SIN borrar libros/usuarios.
-- Estructura de solicitudes:
--   solicitudes_prestamo.user_id  <-- usuario que solicita
--   registro_visitas.user_id      <-- usuario que recibe el libro
-- ============================================================

USE biblioteca;

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- USUARIOS
-- ------------------------------------------------------------
SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios'
   AND COLUMN_NAME='sede_id') = 0,
  'ALTER TABLE usuarios ADD COLUMN sede_id INT NOT NULL DEFAULT 4',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios'
   AND COLUMN_NAME='activo') = 0,
  'ALTER TABLE usuarios ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE usuarios SET sede_id=4 WHERE sede_id IS NULL OR sede_id=0;
UPDATE usuarios SET activo=1 WHERE activo IS NULL;

-- ------------------------------------------------------------
-- ALUMNOS
-- ------------------------------------------------------------
SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='alumnos'
   AND COLUMN_NAME='usuario_id') = 0,
  'ALTER TABLE alumnos ADD COLUMN usuario_id INT NULL AFTER id',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='alumnos'
   AND COLUMN_NAME='sede_id') = 0,
  'ALTER TABLE alumnos ADD COLUMN sede_id INT NOT NULL DEFAULT 4 AFTER carrera_id',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='alumnos'
   AND COLUMN_NAME='activo') = 0,
  'ALTER TABLE alumnos ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE alumnos SET sede_id=4 WHERE sede_id IS NULL OR sede_id=0;
UPDATE alumnos SET activo=1 WHERE activo IS NULL;

-- ------------------------------------------------------------
-- DOCENTES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS docentes (
  id INT NOT NULL AUTO_INCREMENT,
  usuario_id INT NULL,
  nombre VARCHAR(255) NOT NULL,
  telefono VARCHAR(50) NULL,
  email VARCHAR(255) NULL,
  carrera_id INT NULL,
  sede_id INT NOT NULL DEFAULT 4,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_docente_usuario (usuario_id),
  KEY idx_docente_carrera (carrera_id),
  KEY idx_docente_sede (sede_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- SOLICITUDES
-- La versión antigua podía usar usuario_id.
-- El sistema actual usa user_id.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS solicitudes_prestamo (
  id INT NOT NULL AUTO_INCREMENT,
  bibliografia_id INT NOT NULL,
  user_id INT NULL,
  nombre_solicitante VARCHAR(255) NOT NULL,
  carrera_id INT NULL,
  sede_id INT NOT NULL DEFAULT 4,
  estado ENUM('pendiente','aprobada','prestado','rechazada','cancelada','completada')
    NOT NULL DEFAULT 'pendiente',
  observaciones TEXT NULL,
  motivo_rechazo TEXT NULL,
  respuesta_admin TEXT NULL,
  fecha_solicitud TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_respuesta DATETIME NULL,
  fecha_entrega DATETIME NULL,
  atendido_por INT NULL,
  registro_visita_id INT NULL,
  PRIMARY KEY (id),
  KEY idx_solicitud_libro (bibliografia_id),
  KEY idx_solicitud_usuario (user_id),
  KEY idx_solicitud_carrera (carrera_id),
  KEY idx_solicitud_sede (sede_id),
  KEY idx_solicitud_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Si existe usuario_id pero no user_id, crear user_id y copiar.
SET @has_user_id := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitudes_prestamo'
  AND COLUMN_NAME='user_id'
);

SET @has_usuario_id := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitudes_prestamo'
  AND COLUMN_NAME='usuario_id'
);

SET @sql = IF(
  @has_user_id=0,
  'ALTER TABLE solicitudes_prestamo ADD COLUMN user_id INT NULL AFTER bibliografia_id',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  @has_usuario_id>0,
  'UPDATE solicitudes_prestamo SET user_id=usuario_id WHERE user_id IS NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Campos que el flujo de aprobación utiliza.
SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitudes_prestamo'
   AND COLUMN_NAME='nombre_solicitante')=0,
  'ALTER TABLE solicitudes_prestamo ADD COLUMN nombre_solicitante VARCHAR(255) NOT NULL DEFAULT "Usuario Biblioteca"',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitudes_prestamo'
   AND COLUMN_NAME='respuesta_admin')=0,
  'ALTER TABLE solicitudes_prestamo ADD COLUMN respuesta_admin TEXT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitudes_prestamo'
   AND COLUMN_NAME='motivo_rechazo')=0,
  'ALTER TABLE solicitudes_prestamo ADD COLUMN motivo_rechazo TEXT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitudes_prestamo'
   AND COLUMN_NAME='fecha_respuesta')=0,
  'ALTER TABLE solicitudes_prestamo ADD COLUMN fecha_respuesta DATETIME NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitudes_prestamo'
   AND COLUMN_NAME='fecha_entrega')=0,
  'ALTER TABLE solicitudes_prestamo ADD COLUMN fecha_entrega DATETIME NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitudes_prestamo'
   AND COLUMN_NAME='atendido_por')=0,
  'ALTER TABLE solicitudes_prestamo ADD COLUMN atendido_por INT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitudes_prestamo'
   AND COLUMN_NAME='registro_visita_id')=0,
  'ALTER TABLE solicitudes_prestamo ADD COLUMN registro_visita_id INT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- REGISTRO DE PRÉSTAMOS
-- ------------------------------------------------------------
SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='registro_visitas'
   AND COLUMN_NAME='user_id')=0,
  'ALTER TABLE registro_visitas ADD COLUMN user_id INT NULL AFTER bibliografia_id',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='registro_visitas'
   AND COLUMN_NAME='fecha_devolucion')=0,
  'ALTER TABLE registro_visitas ADD COLUMN fecha_devolucion DATETIME NULL AFTER devuelto',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- VERIFICACIÓN
-- ------------------------------------------------------------
SELECT 'Migración completada. El sistema usa solicitudes_prestamo.user_id.' AS resultado;
DESCRIBE usuarios;
DESCRIBE alumnos;
DESCRIBE docentes;
DESCRIBE solicitudes_prestamo;
DESCRIBE registro_visitas;
