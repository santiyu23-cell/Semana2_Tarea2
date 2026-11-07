# CRUD PHP

Proyecto base en PHP 8.1+ con PDO, PostgreSQL y arquitectura simple basada en SOLID.

## Requisitos

- PHP 8.1 o superior.
- PostgreSQL.
- Extensiones PHP `pdo` y `pdo_pgsql`.
- Apache con `mod_rewrite` habilitado.

## Instalación

1. Configura los datos de conexión en `.env`.
2. Ejecuta las migraciones:

   `php bin/migrate.php`

3. Genera un CRUD para una tabla:

   `php 2_generar_crud.php products`

4. Abre el proyecto en el navegador:

   `http://localhost/nombre_carpeta/`

## Capas

- `Controllers`: reciben las peticiones HTTP.
- `Services`: validaciones y reglas de negocio.
- `Repositories`: acceso a datos mediante PDO.
- `Entities`: objetos que representan los registros.
- `Views`: HTML.
- `Core`: utilidades generales.

## Seguridad

- No subir `.env` a Git.
- No publicar archivos internos.
- Mantener `APP_DEBUG=false` en producción.
- Los generadores se ejecutan únicamente por consola.
- En producción, configurar `public/` como directorio público del servidor.

## Rutas

Por convención:

`/recurso/accion/id`

Ejemplo:

`/products/edit/3`
