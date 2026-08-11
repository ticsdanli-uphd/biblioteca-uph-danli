BIBLIOTECA UPH - RESPONSIVE GLOBAL

1. Copie assets/css/responsive.css a biblioteca/assets/css/
2. Copie assets/js/responsive.js a biblioteca/assets/js/
3. Copie tools/instalar_responsive.php a biblioteca/tools/
4. Abra http://localhost/biblioteca/tools/instalar_responsive.php
5. Pruebe el sistema.
6. ELIMINE tools/instalar_responsive.php por seguridad.

El cambio se concentra en header.php porque todos los módulos del proyecto incluyen ese archivo.


SOLUCIÓN ERROR: Table biblioteca.docentes doesn't exist
===========================================================
1. Si es una instalación nueva, importa biblioteca.sql.
2. Si ya tienes una base de datos biblioteca con información, ejecuta
   database/migracion_docentes.sql desde phpMyAdmin.
3. login.php ya no depende de la tabla docentes para autenticar, por lo que
   los usuarios existentes pueden iniciar sesión mientras haces la migración.
