MODULO GESTION DE USUARIOS - UPH DANLI
=====================================

Sede fija:
    Danlí = sede_id 4

Archivos:
    usuarios/list.php
    usuarios/edit.php
    usuarios/delete.php

Características:
- Solo administrador.
- Todos los usuarios se filtran por sede_id = 4.
- Alumno: puede tener carrera.
- Docente: no solicita carrera.
- Administrador: no solicita carrera.
- Cambiar contraseña valida que el usuario pertenezca a Danlí.
- Eliminar usa desactivación (activo=0) para conservar historial y evitar errores de claves foráneas.

IMPORTANTE:
La tabla usuarios debe tener:
    sede_id INT
    activo INT/BOOLEAN

Y las tablas alumnos/docentes deben tener:
    usuario_id
    sede_id

Si tu base todavía no tiene usuarios.sede_id:
    ALTER TABLE usuarios ADD COLUMN sede_id INT NOT NULL DEFAULT 4;
