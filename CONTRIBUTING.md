# Cómo trabajamos en GADIT

Esta guía explica cómo se crean ramas, commits y pull requests, y qué se revisa antes de unir algo a `develop`. Para levantar el entorno, lee primero el [README](README.md). Las reglas del sistema están en el [TDD](docs/TDD-GADIT-v1.2.md).

## Ramas

| Rama | Uso |
|---|---|
| `main` | Solo código estable y entregable. Nadie trabaja aquí |
| `develop` | Rama de integración. A ella llega todo el trabajo |
| `feat/GAD-xx-descripcion` | Una funcionalidad nueva |
| `fix/GAD-xx-descripcion` | Corrección de un error |
| `docs/GAD-xx-descripcion` | Solo documentación |
| `chore/GAD-xx-descripcion` | Configuración, dependencias o mantenimiento |

- Todas las ramas de trabajo salen de `develop` y vuelven a `develop`.
- El nombre va en minúsculas, con guiones, sin acentos ni espacios, y siempre lleva la clave de Jira. Ejemplo: `feat/GAD-71-formulario-de-gasto`.
- A `main` solo sube `develop` cuando hay algo listo para entregar. Nunca se hace push directo a `main`.
- Una rama por tarea. Si descubres otra cosa que arreglar, crea otra rama.

## Commits

Formato: `GAD-xx: qué se hizo`, en una línea y en español.

```
GAD-71: agrega el formulario de captura de gasto
GAD-71: valida que el importe sea distinto de cero (RN-10)
GAD-34: crea el modelo Bitacora y el trait Auditable
```

- Un commit es un cambio con sentido, no "avances".
- Si el cambio implementa una regla del TDD, cita su ID (`RN-05`, `RF-30`).
- Antes de hacer commit, corre `git status` y revisa qué entra. Nunca debe aparecer `.env`, `vendor/`, `node_modules/` ni un Excel.

## Paso a paso de una tarea

1. Pasa la tarjeta de Jira a **En curso**.
2. Crea tu rama desde `develop`:
   ```bash
   git checkout develop
   git pull
   git checkout -b feat/GAD-xx-descripcion
   ```
3. Trabaja y haz commits pequeños.
4. Si `develop` avanzó mientras trabajabas, tráela a tu rama:
   ```bash
   git fetch origin
   git merge origin/develop
   ```
5. Corre las verificaciones (siguiente sección).
6. Sube la rama y abre el pull request hacia `develop`:
   ```bash
   git push -u origin feat/GAD-xx-descripcion
   ```
7. Pasa la tarjeta a **En revisión** y pide la revisión a otra persona del equipo.
8. Cuando te aprueben, fusiona con **Squash and merge** y borra la rama.
9. Pasa la tarjeta a **Hecho**.

## Antes de abrir un pull request

Estas tres verificaciones deben pasar. Son las mismas que correrá la integración continua:

```bash
sail artisan test
sail bin pint --test
sail bin phpstan analyse --memory-limit=1G
```

Si Pint reporta diferencias, corrígelas con `sail bin pint` y vuelve a correr el `--test`.

## Pull requests

- **Título:** `GAD-xx: qué se hizo`, igual que el commit.
- **Base:** siempre `develop`.
- **Descripción:** completa la plantilla que aparece sola. Si cambió la interfaz, agrega una captura.
- **Tamaño:** pequeños. Un pull request grande no se revisa bien.
- **Unión:** *Squash and merge*, para que cada tarea quede como un solo commit en `develop`.
- **Revisión:** otra persona del equipo lo revisa. Nadie aprueba su propio pull request.

## Cómo revisar el trabajo de otra persona

Además de leer el código, comprueba esto:

- Las reglas de negocio coinciden con el TDD (`RN-xx`) y los mensajes son los textos exactos de la sección 7.
- La lógica está en `app/Actions` y no en las pantallas Livewire. Cada capa solo llama hacia abajo.
- Hay una prueba por regla, y su nombre empieza con el ID: `it('RN-05: rechaza un cobro que excede el saldo')`.
- Los permisos pasan por una Policy y se probó la llamada directa de un rol sin permiso, porque ocultar un botón no es seguridad.
- No hay `float` para dinero: los importes usan `DECIMAL(14,2)` y `brick/math`.
- No hay consultas N+1 y los filtros viajan en la URL.
- Si el modelo es auditable, crear, editar y eliminar dejan su fila en bitácora.
- Los nombres de dominio están en español y los técnicos en inglés.
- Responde y resuelve cada comentario antes de fusionar.

## Pruebas

- Se escriben con Pest y corren contra **MySQL 8.4** (base `testing`), nunca contra SQLite.
- El nombre de cada prueba empieza con el ID de lo que cubre, para poder filtrar con `sail artisan test --filter="RN-05"`.
- Los importes en las pruebas se escriben como texto (`'1234.56'`), nunca como número decimal.
- Toda prueba que dependa de la fecha fija la hora con `Carbon::setTestNow(...)` en la zona de México.

## Cambios al TDD y al esquema

- **Si algo cambia el comportamiento** (una regla, una pantalla, un permiso), primero se corrige el TDD, con su ID y nueva versión, y después se programa. El cambio de código y el del documento van en el mismo pull request.
- **Si algo cambia la estructura de la base,** el cambio va en la migración y en `gadit_schema.sql`, y debe pasar la prueba de equivalencia entre ambos.
- Si el TDD no dice algo, **se pregunta**; no se inventa.

## Dependencias

- No se agrega ningún paquete sin aprobarlo en la revisión del pull request.
- No se actualiza la versión mayor de ningún componente del stack mientras dure la v1.
- `composer.lock` y `package-lock.json` cambian solo cuando cambian los paquetes. Si hay un conflicto en uno de ellos, no lo resuelvas a mano: pide ayuda a la persona que administra el repositorio.

## Lo que nunca se sube

- El `.env` ni ninguna contraseña, llave o token.
- El Excel `Gastos_GOG.xlsx` ni ningún archivo con datos financieros reales. El `.gitignore` bloquea `*.xlsx` y `*.xls`, pero revisa siempre `git status`.
- Respaldos de la base de datos.
- Archivos `...:Zone.Identifier` que Windows agrega al copiar desde internet. Bórralos con `rm *:Zone.Identifier`.

## Si algo sale mal

- **Hiciste commit de algo que no debías** y todavía no lo subes: `git reset --soft HEAD~1`, corrige y vuelve a hacer el commit.
- **Ya lo subiste** y contiene un dato sensible: avisa de inmediato a la persona que administra el repositorio. Borrar el archivo en un commit nuevo no lo quita del historial.
- **No sabes en qué rama estás:** `git branch --show-current`.
