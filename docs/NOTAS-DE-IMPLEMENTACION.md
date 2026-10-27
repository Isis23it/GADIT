# Notas de implementación

Diferencias entre lo que dice el TDD v1.2 y lo que quedó instalado. Cuando se publique una nueva versión del TDD, estas notas se incorporan y este archivo se actualiza o se elimina.

| Tema | TDD | Implementación | Qué hacer |
|---|---|---|---|
| Base de pruebas | `gadit_test` (DEC-06, sección 18) | `testing`, que Sail crea sola al iniciar MySQL | Usar `testing`. Corregir el nombre en el TDD |
| Tabla `jobs` | No existe | Laravel trae la migración `create_jobs_table`, pero con `QUEUE_CONNECTION=sync` no se usa | Decide Alondra en GAD-29 si se elimina la migración |
| Tipografía | Del sistema (RF-83) | La plantilla de Laravel carga la fuente Instrument Sans | Quitarla al construir el layout principal |

## Entorno

- PHP 8.4 y MySQL 8.4 LTS, fijados en `compose.yaml`.
- Pruebas con Pest 5 y PHPUnit 13, contra MySQL, nunca SQLite.
- Zona horaria `America/Mexico_City`; la conexión MySQL usa `-06:00`.

## Validación del esquema en MySQL 8.4 (RIE-07, GAD-28)

Fecha: 26/10/2026. Servidor: MySQL 8.4.11 con `ONLY_FULL_GROUP_BY` activo (Laravel Sail).

**Resultado.** `gadit_schema.sql` y `gadit_semillas.sql` cargan sin error. Las 13 vistas se consultan sin error. `gastos.semana_iso` es una columna generada `yearweek(fecha, 3)` y devuelve 202501, 202501, 202601 y 202653 para 30/12/2024, 01/01/2025, 01/01/2026 y 03/01/2027. `v_saldo_cuentas` suma solo los movimientos posteriores a `fecha_saldo_inicial` y parte de `saldo_inicial`.

**Diferencias encontradas y corregidas (el SQL pasó a v1.2).**
1. `SET NAMES utf8mb4` sin colación: los textos fijos de las vistas (`v_facturas.situacion` y `v_movimientos_cuenta.tipo`) tomaban `utf8mb4_0900_ai_ci`. Ahora es `SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci`.
2. Las 32 llaves foráneas no declaraban regla: MariaDB las reporta `RESTRICT` y MySQL `NO ACTION`. Ahora todas llevan `ON DELETE RESTRICT ON UPDATE RESTRICT`.

**Huella de referencia para las migraciones (GAD-29, GAD-30 y GAD-31).** 21 tablas, 13 vistas, 220 columnas de tablas, 72 índices, 32 llaves foráneas (todas RESTRICT) y 19 restricciones CHECK. Todas las tablas y columnas de texto en `utf8mb4_unicode_ci`.

**Para la prueba de equivalencia.** Ignorar el `DEFINER` de las vistas: el SQL cargado como root deja `root@localhost` y las migraciones dejarán al usuario de la aplicación.

**Cómo repetirla.** Con Sail arriba, en la raíz del proyecto:

```bash
docker compose exec -T mysql bash -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD"' <<'SQL'
DROP DATABASE IF EXISTS gadit;
SQL
docker compose exec -T mysql bash -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD"' < docs/gadit_schema.sql
docker compose exec -T mysql bash -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" gadit' < docs/gadit_semillas.sql
```
