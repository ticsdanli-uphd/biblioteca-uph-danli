# Ubicación física de libros - Biblioteca UPH Danlí

La ubicación se almacena en la columna existente `bibliografia.ubicacion`, por lo que **no requiere agregar columnas nuevas**.

## Estructura
- Estantes: A-1, A-2, A-3, A-4, A-5
- Estantes: B-1, B-2, B-3, B-4, B-5
- Niveles por estante: 0, 1, 2, 3 y 4

Ejemplo: `Estante B-3 - Nivel 2`.

El administrador verá esta ubicación en las solicitudes de préstamo y el alumno/docente la verá cuando su solicitud sea aprobada para indicar dónde recoger el libro.

## Fotografías del libro
Cada libro puede tener dos imágenes:
- Foto frontal / portada: `bibliografia.foto_frontal`
- Foto trasera / contraportada: `bibliografia.foto_trasera`
Las imágenes se almacenan en `uploads/`.
