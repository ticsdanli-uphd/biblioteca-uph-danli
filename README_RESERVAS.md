# Sistema de Reservas de Libros - Biblioteca UPH

## Descripción

El sistema de reservas de libros permite a los usuarios reservar libros que actualmente no están disponibles para préstamo. Cuando un libro está prestado y no hay ejemplares disponibles, los usuarios pueden registrar una reserva y serán notificados cuando el libro esté disponible para su préstamo.

## Características Implementadas

1. **Reserva de Libros**: Los usuarios pueden reservar libros que no están disponibles actualmente.
2. **Gestión de Reservas**: Los administradores pueden ver, notificar, completar o cancelar reservas.
3. **Notificaciones**: El sistema permite marcar reservas como notificadas cuando se contacta al usuario.
4. **Integración con Préstamos**: Al realizar un préstamo desde una reserva, ésta se marca automáticamente como completada.
5. **Indicadores de Disponibilidad**: La vista de detalles del libro muestra claramente si está disponible o no, y cuántas reservas tiene pendientes.

## Estructura de la Base de Datos

Se ha creado una nueva tabla `reservas_libros` con la siguiente estructura:

```sql
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
```

Además, se ha añadido un campo `reservas_activas` a la tabla `bibliografia` para llevar un contador de reservas activas.

## Flujo de Trabajo

### Para Usuarios

1. El usuario busca un libro en el catálogo.
2. Si el libro no está disponible (todos los ejemplares están prestados), se muestra un botón de "Reservar Libro".
3. El usuario completa el formulario de reserva con los datos del alumno.
4. El sistema registra la reserva y muestra un mensaje de confirmación.

### Para Administradores

1. El administrador accede a "Gestión de Reservas" desde el menú lateral.
2. Puede filtrar las reservas por estado (pendiente, notificada, completada, cancelada).
3. Para cada reserva puede:
   - Marcar como notificada (cuando se contacta al usuario)
   - Completar la reserva (cuando se entrega el libro sin registrar préstamo)
   - Cancelar la reserva
   - Realizar un préstamo directamente desde la reserva

## Archivos Modificados/Creados

1. **Nuevos archivos**:
   - `reservas/list.php`: Gestión de reservas para administradores
   - `books/reservar.php`: Formulario para realizar una reserva
   - `sql_updates_reservas.sql`: Script SQL con la estructura de la tabla y triggers

2. **Archivos modificados**:
   - `books/view.php`: Añadido botón de reserva y estado de disponibilidad
   - `books/prestamo.php`: Integración con el sistema de reservas
   - `includes/header.php`: Añadido enlace a gestión de reservas

## Instalación

Para instalar el sistema de reservas:

1. Ejecutar el script SQL `sql_updates_reservas.sql` en la base de datos.
2. Copiar los nuevos archivos a sus respectivas carpetas.

## Futuras Mejoras

1. **Notificaciones por Email**: Implementar envío automático de correos cuando un libro reservado esté disponible.
2. **Lista de Espera**: Permitir múltiples reservas para un mismo libro y gestionarlas por orden de llegada.
3. **Estadísticas de Reservas**: Añadir reportes sobre los libros más reservados.
4. **Cancelación Automática**: Cancelar reservas automáticamente si el usuario no recoge el libro en un tiempo determinado después de ser notificado.
5. **Reservas desde el Catálogo**: Permitir reservar directamente desde la lista de libros sin entrar al detalle.