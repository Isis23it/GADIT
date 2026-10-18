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
