# Flujo de solicitudes de préstamo - Biblioteca UPH Danlí

1. Ejecuta `sql/solicitudes_prestamo.sql` en la base de datos `biblioteca`.
2. Reemplaza `books/prestamo.php`.
3. Copia `admin/solicitudes_prestamo.php` y `admin/solicitud_accion.php` a `biblioteca/admin/`.
4. Copia `usuario/mis_prestamos.php` a `biblioteca/usuario/`.
5. En el menú administrativo agrega el enlace a `admin/solicitudes_prestamo.php`.
6. En el menú de alumno/docente agrega `usuario/mis_prestamos.php`.

Flujo:
Alumno/Docente -> Solicitar préstamo -> Notificación administrativa -> Aceptar -> Estado "Aprobada - pendiente de recoger" -> el alumno ve ubicación -> personal entrega -> "Marcar como prestado/entregado" -> estado "Prestado" y se crea el registro en `registro_visitas`.

La ubicación se toma de `bibliografia.ubicacion`.
