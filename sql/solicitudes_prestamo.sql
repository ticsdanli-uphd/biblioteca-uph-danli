-- ============================================================
-- SOLICITUDES DE PRÉSTAMO - Biblioteca UPH Danlí
-- Ejecutar UNA SOLA VEZ en la base de datos biblioteca.
-- No modifica alumnos ni bibliografia.
-- ============================================================

CREATE TABLE IF NOT EXISTS solicitudes_prestamo (
    id INT NOT NULL AUTO_INCREMENT,
    bibliografia_id INT NOT NULL,
    usuario_id INT NOT NULL,
    alumno_id INT DEFAULT NULL,
    nombre_solicitante VARCHAR(255) NOT NULL,
    carrera_id INT DEFAULT NULL,
    sede_id INT NOT NULL DEFAULT 4,
    fecha_solicitud TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_respuesta DATETIME DEFAULT NULL,
    estado ENUM('pendiente','aprobada','rechazada','prestado','cancelada') NOT NULL DEFAULT 'pendiente',
    observaciones TEXT DEFAULT NULL,
    motivo_rechazo VARCHAR(500) DEFAULT NULL,
    atendido_por INT DEFAULT NULL,
    fecha_entrega DATETIME DEFAULT NULL,
    registro_visita_id INT DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_sol_bibliografia (bibliografia_id),
    KEY idx_sol_usuario (usuario_id),
    KEY idx_sol_alumno (alumno_id),
    KEY idx_sol_carrera (carrera_id),
    KEY idx_sol_sede (sede_id),
    KEY idx_sol_estado (estado),
    CONSTRAINT fk_sol_bibliografia FOREIGN KEY (bibliografia_id)
        REFERENCES bibliografia(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_sol_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_sol_alumno FOREIGN KEY (alumno_id)
        REFERENCES alumnos(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_sol_carrera FOREIGN KEY (carrera_id)
        REFERENCES carreras(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_sol_sede FOREIGN KEY (sede_id)
        REFERENCES sedes(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_sol_atendido FOREIGN KEY (atendido_por)
        REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
