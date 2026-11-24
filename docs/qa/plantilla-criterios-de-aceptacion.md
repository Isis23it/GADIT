# Plantilla de criterios de aceptación

Copiar este bloque en la descripción de la historia (Jira) antes de construirla.
Cada criterio debe poder marcarse como cumplido o no cumplido sin interpretar.

Reglas para llenarla:

- Cada criterio usa **Dado / Cuando / Entonces**.
- Si el sistema muestra un mensaje, se cita **exacto**, copiado de la sección 7 del TDD (entre « »). Las pruebas comparan ese texto, no solo que haya un error (sección 18).
- Cada regla de negocio citada (RN-xx) debe tener una prueba cuyo nombre empiece con su ID, por ejemplo `it('RN-05: rechaza un cobro que excede el saldo')`.

## Historia
**Como** <rol: admin / captura / consulta>
**quiero** <qué>
**para** <beneficio>

**Referencias del TDD:** RF-__, RN-__, sección __

## Criterios de aceptación
- [ ] **Dado** <estado inicial> **cuando** <acción> **entonces** <resultado visible>.
- [ ] **Dado** <estado inicial> **cuando** <acción que viola una regla> **entonces** aparece «<mensaje exacto de la sección 7>».
- [ ] **Dado** un rol sin permiso **cuando** intenta <acción> **entonces** recibe 403 (aunque el botón no se vea).
- [ ] Funciona a 360 px y a 1,280 px, con teclado y con foco visible.
- [ ] Hay pruebas con el ID de cada regla citada y la suite completa pasa.

## Fuera de alcance
- <lo que esta historia NO incluye>

## Evidencia al cerrar
- PR: #__
- Capturas (360 px y 1,280 px): <enlaces o archivos>
- QA hecho por (distinta persona a quien construyó): <nombre>

---

## Ejemplo llenado (historia ya escrita: cobro de una factura)

**Como** admin
**quiero** registrar un cobro contra una factura
**para** que el saldo pendiente se mantenga al día

**Referencias del TDD:** RN-05, RN-25, sección 7

### Criterios de aceptación
- [ ] **Dado** una factura con saldo pendiente **cuando** registro un cobro cuyo importe es menor o igual al saldo **entonces** el cobro se guarda y el saldo baja por ese importe.
- [ ] **Dado** una factura con saldo pendiente **cuando** registro un cobro mayor al saldo por más de 0.01 **entonces** aparece «El cobro excede el saldo pendiente de la factura (saldo: $X)», con el saldo real en lugar de X.
- [ ] **Dado** una factura con saldo pendiente **cuando** registro un cobro que excede el saldo por exactamente 0.01 **entonces** se acepta (tolerancia de RN-05).
- [ ] **Dado** el formulario de cobro **cuando** pongo una fecha posterior a hoy **entonces** aparece «La fecha no puede ser posterior a hoy».
- [ ] **Dado** una factura cancelada **cuando** intento registrarle un cobro **entonces** se rechaza.
- [ ] Hay pruebas `it('RN-05: …')` y `it('RN-25: …')` que comparan el texto exacto de los mensajes.
