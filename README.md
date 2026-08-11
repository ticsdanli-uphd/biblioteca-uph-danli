# Biblioteca UPH - Centro Asociado Danlí

Sistema de gestión de biblioteca desarrollado en PHP + MySQL.

## Importante: GitHub Pages

Este proyecto **NO puede ejecutarse completo en GitHub Pages** porque GitHub Pages
solo publica contenido estático (HTML/CSS/JavaScript) y no ejecuta PHP ni MySQL.

GitHub Pages puede servir para una portada o documentación del proyecto, pero el
sistema real necesita un servidor que ejecute PHP y una base de datos MySQL/MariaDB.

Documentación oficial:
https://docs.github.com/es/pages/getting-started-with-github-pages/creating-a-github-pages-site

## Ejecutar localmente con XAMPP

1. Copia el proyecto a:
   `C:\xampp\htdocs\biblioteca`
2. Crea la base de datos `biblioteca`.
3. Importa `biblioteca.sql` desde phpMyAdmin.
4. Instala dependencias:
   `composer install`
5. Configura las credenciales SMTP en `config/mail.local.php`.
6. Abre:
   `http://localhost/biblioteca/`

## GitHub

Este repositorio está preparado para guardar el código fuente.
No subas contraseñas, claves de aplicación ni archivos `mail.local.php`.

Comandos:

```powershell
git init
git add .
git commit -m "Sistema Biblioteca UPH Danli"
git branch -M main
git remote add origin https://github.com/TU-USUARIO/biblioteca-uph.git
git push -u origin main
```

## Estado de revisión

Se revisó la sintaxis de los archivos PHP de la aplicación (excluyendo vendor).
Se corrigió un error de sintaxis en `tesis/delete.php`.

El proyecto debe probarse contra la base de datos real antes de ponerlo en producción.

## Si quieres publicar una portada en GitHub Pages

En GitHub ve a **Settings → Pages** y selecciona la rama `main` y la carpeta `/docs`.
La portada estará en la URL de Pages de tu repositorio.

Esto publica solamente la portada estática. No convierte el backend PHP/MySQL en un
sistema ejecutable en GitHub Pages.
