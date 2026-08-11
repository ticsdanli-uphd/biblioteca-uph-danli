-- ============================================================
-- SOLICITUDES DE PRÉSTAMO - Biblioteca UPH Danlí
-- Compatible con database/biblioteca_UPH_COMPLETA_CORREGIDA.sql
-- ============================================================

USE biblioteca;

CREATE TABLE IF NOT EXISTS solicitudes_prestamo (
    id INT NOT NULL AUTO_INCREMENT,
    bibliografia_id INT NOT NULL,
    user_id INT NOT NULL,
    nombre_solicitante VARCHAR(255) NOT NULL,
    carrera_id INT DEFAULT NULL,
    sede_id INT NOT NULL DEFAULT 4,
    estado ENUM('pendiente','aprobada','prestado','rechazada','cancelada','completada')
        NOT NULL DEFAULT 'pendiente',
    observaciones TEXT DEFAULT NULL,
    motivo_rechazo TEXT DEFAULT NULL,
    respuesta_admin TEXT DEFAULT NULL,
    fecha_solicitud TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_respuesta DATETIME DEFAULT NULL,
    fecha_entrega DATETIME DEFAULT NULL,
    atendido_por INT DEFAULT NULL,
    registro_visita_id INT DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_solicitud_libro (bibliografia_id),
    KEY idx_solicitud_usuario (user_id),
    KEY idx_solicitud_carrera (carrera_id),
    KEY idx_solicitud_sede (sede_id),
    KEY idx_solicitud_estado (estado),
    CONSTRAINT fk_solicitud_libro FOREIGN KEY (bibliografia_id)
        REFERENCES bibliografia(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_solicitud_usuario FOREIGN KEY (user_id)
        REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_solicitud_carrera FOREIGN KEY (carrera_id)
        REFERENCES carreras(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_solicitud_sede FOREIGN KEY (sede_id)
        REFERENCES sedes(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_solicitud_admin FOREIGN KEY (atendido_por)
        REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_solicitud_registro FOREIGN KEY (registro_visita_id)
        REFERENCES registro_visitas(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
