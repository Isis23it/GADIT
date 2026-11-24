# Plantilla de reporte de defecto

Crear una tarjeta tipo Error en Jira, vinculada a la historia afectada, con este contenido.

**Título:** <pantalla> · <qué falla en una línea>

## Entorno
- Rama / commit: <feat/GAD-__ o develop, hash corto>
- Navegador y versión: <...>
- Dispositivo y ancho de pantalla: <móvil 360 px / escritorio 1,280 px>
- Usuario y rol con el que se probó: <admin / captura / consulta>

## Pasos para reproducirlo
1. <paso>
2. <paso>
3. <paso>

## Resultado esperado
<lo que debería pasar; citar el criterio de aceptación o la regla (RN-xx / RF-xx) y, si hay mensaje, el texto exacto de la sección 7>

## Resultado obtenido
<lo que pasó en realidad; copiar el mensaje de error textual si lo hay>

## Captura
<captura o video; no incluir datos reales de clientes>

## Severidad
- [ ] **Bloqueante:** impide usar la función o pierde datos.
- [ ] **Alta:** la función falla pero hay una vía alterna.
- [ ] **Media:** error visible que no impide trabajar.
- [ ] **Baja:** detalle de texto o de estilo.

## Frecuencia
- [ ] Siempre  - [ ] A veces  - [ ] Una sola vez

## Cierre
- Corregido en PR: #__
- Verificado por (distinta persona a quien lo corrigió): <nombre> el <fecha>

---

## Ejemplo llenado

**Título:** Cobros · acepta un cobro con fecha futura

**Entorno:** feat/GAD-xx, Chrome, escritorio 1,280 px, rol admin.

**Pasos:** 1. Abrir una factura con saldo. 2. Pulsar «Registrar cobro». 3. Poner una fecha de mañana y guardar.

**Resultado esperado:** aparece «La fecha no puede ser posterior a hoy» (RN-25) y no se guarda.

**Resultado obtenido:** el cobro se guarda sin mensaje.

**Severidad:** Alta · **Frecuencia:** Siempre.
