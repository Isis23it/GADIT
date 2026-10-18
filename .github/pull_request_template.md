## Tarea

Jira: GAD-xx

## Qué cambia

<!-- Resume el cambio en dos o tres líneas. -->

## Reglas y requisitos del TDD que cubre

<!-- Por ejemplo: RN-05, RF-30, SEG-03. Si no aplica, escribe "ninguno". -->

## Cómo probarlo

<!-- Pasos para que quien revisa lo compruebe. Si cambió la interfaz, agrega capturas a 360 px y a 1,280 px. -->

## Lista de verificación

**Siempre**

- [ ] `sail artisan test` pasa
- [ ] `sail bin pint --test` pasa
- [ ] `sail bin phpstan analyse --memory-limit=1G` pasa
- [ ] Las pruebas nuevas tienen el ID de la regla al inicio del nombre (`RN-xx: ...`)
- [ ] No se subió `.env`, ningún Excel, ni datos reales
- [ ] No hay `float` para dinero (se usa `DECIMAL(14,2)` y `brick/math`)

**Si aplica**

- [ ] Los permisos pasan por una Policy y se probó la llamada directa de un rol sin permiso
- [ ] Si el modelo es auditable, crear, editar y eliminar dejan su fila en bitácora
- [ ] Los mensajes son los textos exactos de la sección 7 y los formatos son los de RF-82
- [ ] La pantalla se ve y funciona a 360 px y a 1,280 px, con teclado y con foco visible
- [ ] No hay consultas N+1 y los filtros viajan en la URL
- [ ] Si cambió la estructura de la base: migración, `gadit_schema.sql` y prueba de equivalencia
- [ ] Si cambió un comportamiento: el TDD está corregido en este mismo pull request, con nueva versión

## Notas para quien revisa

<!-- Dudas, decisiones que tomaste o partes que quieres que se miren con cuidado. -->
