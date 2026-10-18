# GADIT — Sistema de control administrativo

Reemplazo web del libro `Gastos_GOG.xlsx`: cada movimiento (gasto, cobro, traspaso) se captura una sola vez y los saldos, totales y la utilidad se calculan solos.

Toda regla, pantalla y permiso está definida en el TDD: [`docs/TDD-GADIT-v1.2.md`](docs/TDD-GADIT-v1.2.md). **Lo que no está en el TDD se pregunta, no se inventa.** Si algo cambia, primero se corrige el documento y después se programa.

## Stack

| Pieza | Versión |
|---|---|
| PHP | 8.4 |
| Laravel | 13 |
| Livewire | 4.4 o superior |
| MySQL | 8.4 LTS |
| Pruebas | Pest 5 y PHPUnit 13 |
| Calidad | Pint y Larastan (nivel 6) |
| Front | Tailwind CSS, Vite, Alpine.js, Tom Select y Chart.js |

Todo corre en contenedores con **Laravel Sail**: no hace falta instalar PHP, MySQL ni Node en tu computadora. Las versiones están fijadas en `compose.yaml`, `composer.lock` y `package-lock.json`, así que todo el equipo trabaja con lo mismo.

## Requisitos previos (una sola vez)

1. **WSL2 con Ubuntu.** En PowerShell como administrador: `wsl --install`.
2. **Docker Desktop** con la integración de WSL activada: *Settings → Resources → WSL integration* → activa tu Ubuntu.
3. **Git** configurado con tu nombre y correo:
   ```bash
   git config --global user.name "Tu Nombre"
   git config --global user.email "tu-correo@ejemplo.com"
   git config --global init.defaultBranch main
   ```
4. **Llave SSH registrada en GitHub:**
   ```bash
   ssh-keygen -t ed25519 -C "tu-correo@ejemplo.com"
   cat ~/.ssh/id_ed25519.pub
   ```
   Copia la línea completa en <https://github.com/settings/keys> y comprueba con `ssh -T git@github.com`.
5. **Trabaja siempre dentro de Ubuntu, en `~/proyectos`.** Nunca en `/mnt/c/...`: ahí Docker es mucho más lento.
6. **Puertos libres:** 80, 3306, 5173, 1025 y 8025. Si tienes otros contenedores usándolos, detenlos antes de arrancar.

## Arranque del proyecto

Desde la terminal de Ubuntu:

```bash
mkdir -p ~/proyectos && cd ~/proyectos
git clone git@github.com:Isis23it/GADIT.git
cd GADIT
git checkout develop
cp .env.example .env
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php84-composer:latest composer install --ignore-platform-reqs
./vendor/bin/sail up -d --wait
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install
./vendor/bin/sail artisan test
```

**Por qué el `docker run`:** recién clonado todavía no existe la carpeta `vendor`, y por lo tanto tampoco el comando `sail`. Ese comando usa un contenedor temporal con PHP y Composer para instalarla.

**Por qué `--wait`:** en la primera instalación MySQL tarda unos segundos en inicializarse. Con `--wait`, Sail espera a que esté listo antes de seguir; sin esto, `migrate` falla con `Connection refused`.

Cuando termine:

| Servicio | Dirección |
|---|---|
| Aplicación | <http://localhost> |
| Mailpit (correos de desarrollo) | <http://localhost:8025> |

### Atajo para escribir `sail`

```bash
echo "alias sail='./vendor/bin/sail'" >> ~/.bashrc
source ~/.bashrc
```

Si tu terminal usa zsh, el archivo es `~/.zshrc`.

## Comandos del día a día

| Qué quieres hacer | Comando |
|---|---|
| Levantar el entorno | `sail up -d --wait` |
| Apagarlo | `sail down` |
| Compilar el front en modo desarrollo | `sail npm run dev` |
| Correr las pruebas | `sail artisan test` |
| Revisar estilo (solo ver) | `sail bin pint --test` |
| Corregir estilo | `sail bin pint` |
| Análisis estático | `sail bin phpstan analyse --memory-limit=1G` |
| Entrar a MySQL | `sail mysql` |
| Aplicar migraciones | `sail artisan migrate` |

## Pruebas

- Corren contra **MySQL 8.4**, base `testing`, nunca contra SQLite: el esquema usa vistas, columna generada y restricciones `CHECK` que SQLite no reproduce (DEC-06 del TDD).
- La base `testing` la crea Sail sola al iniciar MySQL por primera vez.
- Antes de abrir un pull request deben pasar las tres verificaciones: `sail artisan test`, `sail bin pint --test` y `sail bin phpstan analyse --memory-limit=1G`.

## Flujo de trabajo en Git

| Rama | Para qué sirve |
|---|---|
| `main` | Solo código estable y entregable. No se trabaja aquí |
| `develop` | Rama de integración: a ella llega todo el trabajo del equipo |
| `feat/GAD-xx-descripcion` | Una rama por tarea, que sale de `develop` |

Pasos para cada tarea:

```bash
git checkout develop
git pull
git checkout -b feat/GAD-xx-descripcion
# trabajar y hacer commits
git push -u origin feat/GAD-xx-descripcion
```

- La clave de Jira va en el nombre de la rama y al inicio del commit: `GAD-xx: qué se hizo`. Así la tarjeta se enlaza sola con la rama y el pull request.
- El pull request se abre **hacia `develop`**, nunca hacia `main`, y se fusiona con *Squash and merge*.
- Otra persona del equipo revisa el pull request antes de fusionarlo.
- A `main` solo sube `develop` cuando hay algo listo para entregar.

## Reglas que no se negocian

- **Nunca se sube información real:** ni el `.env`, ni el Excel `Gastos_GOG.xlsx`, ni respaldos o datos financieros. El `.gitignore` ya bloquea `*.xlsx` y `*.xls`, pero revisa siempre `git status` antes de hacer commit.
- **El dinero nunca usa `float`.** Importes en `DECIMAL(14,2)` y `brick/math` en PHP (DEC-03).
- **Nombres de dominio en español** (tablas, modelos, rutas, etiquetas) y nombres técnicos en inglés (`Action`, `Policy`, `Service`) (DEC-16).
- **Pantallas Livewire como componentes de clase**, no de archivo único (DEC-20).
- **Reglas de negocio en `app/Actions`**, no en las pantallas. Cada capa solo llama hacia abajo (sección 8 del TDD).

## Problemas frecuentes

| Síntoma | Causa y solución |
|---|---|
| `Connection refused` al hacer `migrate` | MySQL aún no estaba listo. Espera unos segundos y revisa que `sail ps` muestre `mysql` como `healthy` |
| `port is already allocated` | Otro contenedor usa el puerto 80, 3306 o 5173. Detenlo desde Docker Desktop o con `docker stop` |
| `sail: command not found` | Falta el alias, o no has instalado `vendor`. Usa `./vendor/bin/sail` o crea el alias |
| `Permission denied (publickey)` al hacer push o clone | Tu llave SSH no está registrada en GitHub: <https://github.com/settings/keys> |
| El proyecto va muy lento | Estás trabajando en `/mnt/c`. Muévelo a `~/proyectos` dentro de Ubuntu |
| Aparecen archivos `...:Zone.Identifier` | Windows los agrega a lo que copias desde internet. Bórralos con `rm *:Zone.Identifier` antes del commit |

## Documentación

- [`docs/TDD-GADIT-v1.2.md`](docs/TDD-GADIT-v1.2.md): requisitos, reglas de negocio, modelo de datos, pantallas, pruebas y plan por hitos.
- El esquema de la base lo fija `gadit_schema.sql`; el comportamiento, el TDD. Si chocan, se corrige uno de los dos antes de programar.
