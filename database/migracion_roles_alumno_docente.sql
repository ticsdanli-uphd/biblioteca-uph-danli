-- ============================================================
-- MIGRACIÓN PARA SISTEMAS EXISTENTES - BIBLIOTECA UPH DANLÍ
-- No elimina libros, alumnos, préstamos ni usuarios.
-- ============================================================
USE biblioteca;
SET FOREIGN_KEY_CHECKS=0;

-- Danlí es la sede 4 en la base entregada.
-- Usuarios antiguos con role=usuario pasan a ser alumnos.
UPDATE usuarios SET role='alumno' WHERE role='usuario';

-- Agregar estado del usuario si aún no existe.
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='activo')=0,
  'ALTER TABLE usuarios ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER sede_id',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Cambiar enum de roles.
ALTER TABLE usuarios MODIFY role ENUM('admin','docente','alumno') NOT NULL DEFAULT 'alumno';

-- Crear docentes.
CREATE TABLE IF NOT EXISTS docentes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NULL,
  nombre VARCHAR(255) NOT NULL,
  telefono VARCHAR(50) NULL,
  email VARCHAR(255) NULL,
  carrera_id INT NULL,
  sede_id INT NOT NULL DEFAULT 4,
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(usuario_id), INDEX(carrera_id), INDEX(sede_id),
  CONSTRAINT fk_docente_usuario FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_docente_carrera FOREIGN KEY(carrera_id) REFERENCES carreras(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_docente_sede FOREIGN KEY(sede_id) REFERENCES sedes(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar sede_id a alumnos.
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='alumnos' AND COLUMN_NAME='sede_id')=0,
  'ALTER TABLE alumnos ADD COLUMN sede_id INT NOT NULL DEFAULT 4 AFTER carrera_id',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Todos los alumnos existentes quedan en Danlí.
UPDATE alumnos SET sede_id=4 WHERE sede_id IS NULL OR sede_id=0;

-- Campos para asociar un préstamo al alumno/docente que recibe el libro.
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='registro_visitas' AND COLUMN_NAME='beneficiario_usuario_id')=0,
  'ALTER TABLE registro_visitas ADD COLUMN beneficiario_usuario_id INT NULL AFTER user_id',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='registro_visitas' AND COLUMN_NAME='beneficiario_tipo')=0,
  "ALTER TABLE registro_visitas ADD COLUMN beneficiario_tipo ENUM('alumno','docente','externo') NULL AFTER beneficiario_usuario_id",
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS=1;
