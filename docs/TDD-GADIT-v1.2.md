# TDD — Sistema de control administrativo GADIT

Oct 14, 2026 · @Angel Tovar

## 1. Control del documento

Este documento define qué construir, con qué reglas y cómo verificarlo; lo que aquí no está escrito se pregunta al dueño (sección 21), no se inventa.

| Dato                    | Valor                                                        |
| ----------------------- | ------------------------------------------------------------ |
| Versión                 | 1.3, borrador para revisión del dueño y del equipo (1.1: Laravel 13, Livewire 4 y PHP 8.4, con DEC-01, DEC-20 y RIE-15; 1.2: verificación completa contra el Excel, que cambia la sección 4, IMP-06, IMP-10, el informe del importador, PEN-10, PEN-14, PEN-17 a PEN-20, RIE-16 y agrega el Apéndice F; 1.3: la primera carga en MySQL 8.4 (RIE-07) deja `gadit_schema.sql` en v1.2, con colación unicode_ci y llaves RESTRICT explícitas) |
| Fecha                   | 15/10/2026                                                   |
| Sistema                 | Control administrativo GADIT: reemplazo web de `Gastos_GOG.xlsx` |
| Documentos relacionados | `gadit_schema.sql` v1.2 (estructura normativa de la base), `gadit_semillas.sql` (datos de arranque y de prueba), `Gastos_GOG.xlsx` (fuente histórica) |

**Precedencia.** La estructura de la base la fija `gadit_schema.sql`; el comportamiento (reglas, pantallas, permisos) lo fija este TDD. Si chocan, se corrige uno de los dos antes de programar.

**Identificadores.** Cada afirmación verificable lleva ID para citarla en tareas y pruebas.

| Prefijo | Significa                                                    |
| ------- | ------------------------------------------------------------ |
| DEC-nn  | Decisión de diseño ya tomada                                 |
| RN-nn   | Regla de negocio (fórmula o restricción)                     |
| RF-nn   | Requisito funcional (pantalla o comportamiento)              |
| RNF-nn  | Requisito no funcional                                       |
| PEN-nn  | Pendiente con el dueño; trae una decisión por defecto para no bloquear |
| RIE-nn  | Riesgo                                                       |
| IMP-nn  | Regla del importador del Excel (sección 15)                  |

**Prioridad.** M = debe estar en v1; S = debería; C = podría. Un RF sin prioridad es M.

**Cambios al esquema respecto a la versión entregada antes (v1.1).** Salieron de escribir este documento:

1. `cuentas.saldo_inicial` y `cuentas.fecha_saldo_inicial`: sin ellos los saldos de cuentas no se pueden cuadrar el día del arranque (RN-14).
2. `v_saldo_cuentas` suma solo los movimientos posteriores a `fecha_saldo_inicial` y parte de `saldo_inicial`.
3. Índice único `uq_gastos_origen (origen, numero_origen)` en `gastos`: hace repetible la importación del Excel sin duplicar.

**Cómo leerlo.** Backend: secciones 5 a 9 y 15. Frontend: 10 a 14 y 16. QA: 7, 15, 17 y 18. Quien planea el trabajo: 19 a 21.

## 2. Resumen, objetivos y alcance

GADIT necesita un sistema web que sustituya su libro de Excel: cada movimiento se captura una sola vez y los saldos, totales y la utilidad se calculan solos.

**El problema hoy.** El libro tiene 11 hojas y unas 425 fórmulas, pero solo 5 referencias entre hojas: los mismos datos se vuelven a teclear en CAJA, ALFREDO y las hojas por socio. Los gastos no indican proyecto, proveedor ni folio fiscal, así que no se puede saber cuánto gana cada obra. Hay cifras que no cuadran entre hojas (sección 4).

**Objetivos.**

| ID   | Objetivo                   | Se logra cuando                                              |
| ---- | -------------------------- | ------------------------------------------------------------ |
| O1   | Captura única              | Un gasto, cobro o traspaso se registra en un solo lugar y alimenta todos los saldos |
| O2   | Continuidad con el Excel   | Las cifras de control de la sección 15 coinciden al centavo tras importar |
| O3   | Rentabilidad por obra      | Cada gasto nuevo puede ligarse a un proyecto y el reporte muestra margen por proyecto |
| O4   | Control                    | Roles, bitácora, borrado lógico y comprobantes adjuntos funcionan como se define en las secciones 6 y 7 |
| O5   | Cumplimiento fiscal básico | Se guardan UUID, método y forma de pago, y el sistema lista los cobros PPD sin Complemento de Pago |

**En alcance (v1).**

- Autenticación, usuarios y tres roles.
- Catálogos: clientes, proyectos, personas, proveedores con datos bancarios, categorías y cuentas.
- Ingresos: contratos y adendas, ajustes, facturas y cobros.
- Egresos: gastos con comprobantes, cuentas por pagar, préstamos y traspasos entre cuentas.
- Estados de cuenta, tablero, reportes y reparto de utilidad.
- Importación única de CONCENTRADO, GADIT y GADIT 2 desde el Excel.
- Bitácora de cambios y respaldos.

**Fuera de alcance (v1).** Si alguien lo pide, es un cambio de alcance, no un detalle por resolver.

- Moneda distinta de MXN (PEN-01).
- Timbrado o cancelación de CFDI y validación contra el SAT: el sistema solo guarda los datos.
- Contabilidad formal (pólizas, balanza) y cálculo de nómina.
- Conexión con bancos, API pública y app móvil nativa (la web es adaptable a celular).
- Varias empresas, cierres de periodo, doble factor de autenticación y avisos por correo.
- Presupuestos y avance físico de obra.
- Importar PROVEEDORES, PRESTAMOS, ALFREDO, CAJA, las hojas de ingresos por socio ni Hoja1 (DEC-09).

**Criterios de éxito.**

| #    | Criterio                                                     | Cómo se comprueba                                     |
| ---- | ------------------------------------------------------------ | ----------------------------------------------------- |
| 1    | La utilidad con la fórmula del Excel es $3,573,582.70 y con GADIT 2 incluida $3,387,349.65 | Prueba de conciliación de la sección 15, automatizada |
| 2    | Se importan 964 gastos por $16,088,222.19 y 2 renglones sin importe quedan reportados | Mismo reporte de importación                          |
| 3    | Un gasto se captura con 5 campos obligatorios (fecha, importe, concepto, categoría, cuenta) | Prueba funcional de RF-30                             |
| 4    | Ningún rol ve ni hace lo que la matriz de la sección 6 no permite | Pruebas de políticas (sección 18)                     |
| 5    | Una restauración de respaldo en entorno limpio reproduce las cifras de control | Simulacro documentado antes del arranque              |

## 3. Contexto del negocio y glosario

GADIT es una constructora de obra civil que cobra a empresas de energía por pavimentos de concreto en caminos de acceso, casetas y renta de maquinaria; su dinero se mueve entre banco, cajas en efectivo y un intermediario.

**Quiénes intervienen.**

- **Clientes** en el Excel: Iberdrola (Bajío y Centro), Solarig, Eosol y Peren (obra de Caminos SLP).
- **Socios** que se reparten la utilidad: Alejandra, Javier, Álvaro y GPE. El CONCENTRADO simula 30 %, 25 %, 22.5 % y 22.5 %; el porcentaje vigente lo decide el dueño y vive en el sistema (RN-19).
- **Intermediario de efectivo** (Alfredo): recibe y entrega efectivo por cuenta de la empresa y cobra comisión. Es una cuenta más del sistema (tipo `intermediario`).
- **Proveedores** de concreto, materiales, maquinaria, combustible y servicios.

**Ciclo del dinero.** Un contrato se factura en estimaciones; cada factura se cobra (a veces en parcialidades) a una cuenta; de las cuentas salen los gastos, los pagos de préstamos y los traspasos; lo que sobra es la utilidad, que se reparte a los socios.

**Glosario.** Los términos de la tabla se usan igual en pantallas, base de datos y código.

| Término                                      | Significado en este sistema                                  |
| -------------------------------------------- | ------------------------------------------------------------ |
| Contrato / pedido                            | Acuerdo con un cliente por un importe sin IVA; su número de pedido puede ser texto (`00494`) |
| Adenda                                       | Ampliación de un contrato; se modela como otro contrato con tipo `adenda` ligado al original |
| Anticipo, estimación, finiquito (de factura) | Tipos de factura de obra: pago adelantado, avance periódico y cierre de la obra |
| Finiquito (de nómina)                        | Liquidación laboral; es una categoría de gasto, no un tipo de factura |
| Deductiva, no ejecutado, descuento           | Conceptos que reducen lo facturable de un contrato (tabla de ajustes) |
| Retención                                    | Porcentaje que el cliente retiene de una factura; el Excel tiene la columna al 5 % pero en 0 % |
| CFDI y UUID                                  | Factura electrónica y su Folio Fiscal de 36 caracteres, que identifica al comprobante ante el SAT |
| PUE / PPD                                    | Pago en una sola exhibición / en parcialidades o diferido    |
| Complemento de Pago                          | CFDI adicional que emite quien cobró una factura PPD; el plazo se confirma con el contador (PEN-12) |
| Forma de pago                                | Código SAT de dos dígitos: `01` efectivo, `03` transferencia, `99` por definir |
| CLABE                                        | Cuenta interbancaria de 18 dígitos para transferencias SPEI  |
| IMSS, ISR, ISN, SAT                          | Seguro social, impuesto sobre la renta, impuesto sobre nóminas (estatal) y autoridad fiscal; aparecen como categorías de gasto |
| Cuenta                                       | Lugar donde está el dinero: banco, caja en efectivo o intermediario |
| Traspaso                                     | Movimiento entre dos cuentas propias; no es gasto ni ingreso |
| Comisión                                     | Pago por manejar efectivo o por intermediar; se registra como gasto |
| Utilidad de caja                             | Cobrado con IVA menos gastos y cuentas por pagar; no es utilidad contable |
| Semana (SEM)                                 | Semana ISO del año en formato AAAASS (`202504`); el sistema la calcula de la fecha. En el Excel la columna SEM no siempre era esta semana (sección 4, hallazgo 9) |
| Borrado lógico                               | El registro se oculta con `deleted_at` pero se puede restaurar |
| Corte                                        | Fecha en que arranca el sistema; desde ella los saldos de cuenta parten de un saldo contado |

## 4. Hallazgos del Excel: mapa hoja a tabla y cifras de control

El libro `Gastos_GOG.xlsx` no es un solo proyecto: es el control de toda la empresa, con 11 hojas visibles, sin macros, hojas ocultas ni vínculos externos.

**Mapa de hojas.** «Se importa» indica si la migración automática de la sección 15 la carga.

| Hoja               | Qué contiene                                                 | Fórmulas | Destino en el sistema                                        | Se importa  |
| ------------------ | ------------------------------------------------------------ | -------- | ------------------------------------------------------------ | ----------- |
| GADIT              | Libro diario de gastos: 905 partidas por $15,901,989.14, del 31/01/2024 al 04/04/2025; 153 etiquetas de categoría. A la derecha de H hay una leyenda de colores que nunca se aplicó (J85:K87: verde «FALTA COMPROBANTE», naranja «REVISADO») y dos números sueltos (J719 y J720) | 10       | `gastos`                                                     | Sí          |
| GADIT 2            | Continuación de la caja de Alejandra (en GADIT sus partidas terminan el 12/03/2025): 59 partidas por $186,233.05, del 13/03 al 30/05/2025 | 1        | `gastos`                                                     | Sí          |
| CONCENTRADO        | Tablero: contrato, facturado, cobrado, utilidad, reparto y «dónde está el dinero», más cálculos de trabajo (Peren en I7:L18; reparto en C68:F71 y D75:D79) | 145      | `contratos`, `facturas`, `cobros`; el resto son vistas       | Parcial     |
| PROVEEDORES        | Compras a crédito con abonos y estatus. La hoja dice que quedan $50,400 por pagar, pero el pago de MALU ($15,000) aparece en GADIT el 31/01/2025 | 101      | `proveedores`, `cuentas_por_pagar`                           | No (DEC-09) |
| PRESTAMOS          | Seis préstamos recibidos por $4.60 M (de 2 % a 3 % o sin interés), todos pagados entre el 14/11 y el 05/12/2024 (J22 = 0). El de GPE ($256,427.41) es el reembolso de lo que GPE pagó de su bolsa del 01/01 al 20/11/2024. No incluye los préstamos de 2025 (hallazgo 15) | 38       | `prestamos`, `prestamo_pagos`                                | No (DEC-09) |
| ALFREDO            | Cuenta corriente del intermediario de efectivo en dos bloques (29/10/2024 al 15/04/2025 y 23/04 al 30/07/2025). Los cargos «ENVIADO» son dinero que recibe; los abonos son efectivo entregado a socios y cajas, depósitos al banco, pagos a terceros y su comisión (0.8 %, 2 % y después 2.5 %) | 31       | Cuenta tipo `intermediario`, `traspasos` y el gasto de comisión (RN-15) | No          |
| CAJA               | Caja de efectivo de GPE (su título es «CAJA BB» y CONCENTRADO toma G4 como «EFECTIVO GPE»), en tres bloques (29/11/2024 al 17/01/2025, 28/02 al 01/04/2025 y 16/04 al 15/07/2025) que se alimentan con efectivo de Alfredo. Sus gastos son los de responsable CAJA GPE en GADIT, salvo los del tercer bloque (hallazgo 11) | 26       | Cuenta Caja GPE y `traspasos`                                | No          |
| ALVARO INGRESOS    | Préstamos de Álvaro a la empresa ($345,100 y $147,900) y su devolución el 18/11/2024 | 3        | `prestamos`, `prestamo_pagos`                                | No          |
| ALEJANDRA INGRESOS | Dos bloques: transferencias de Alejandra al Bajío (28/10 al 14/11/2024) y entradas y salidas de su caja (09/09 al 27/12/2024): préstamos recibidos, efectivo de Alfredo, pagos de préstamo, maquinaria y comisiones | 8        | `traspasos`, `prestamos`, `prestamo_pagos`                   | No          |
| GPE INGRESOS       | Dinero prestado que administró GPE y en qué se gastó ($1,000,000 desde el 01/11/2024 y $400,000 desde el 07/02/2025): son las partidas PRE GPE de GADIT. Las columnas K a N son notas personales | 7        | `prestamos` (los gastos ya están en GADIT)                   | No          |
| Hoja1              | Borrador de la cuenta de Peren (Caminos SLP) con factor de 80 % | 55       | `contrato_ajustes`                                           | No          |

**Cifras de control** (las que el importador debe reproducir, detalle en la sección 15):

| Concepto                          | Valor                                                        | Nota                                                         |
| --------------------------------- | ------------------------------------------------------------ | ------------------------------------------------------------ |
| Contratos                         | 8 renglones, $17,929,419.87 sin IVA                          | CONCENTRADO D8:D15                                           |
| Facturado                         | 12 facturas, $17,089,286.07                                  | La A5020 (Solarig, $300,000) sigue sin cobrar                |
| Cobrado                           | 11 cobros, $16,789,286.07 sin IVA                            | El IVA suma $2,686,285.77 porque el sistema redondea por cobro; el Excel guarda $2,686,285.7712 |
| Gastos                            | 964 partidas, $16,088,222.19                                 | 905 de GADIT y 59 de GADIT 2                                 |
| Utilidad con la fórmula del Excel | $3,573,582.70                                                | Cobrado + IVA − gastos de GADIT                              |
| Utilidad con GADIT 2              | $3,387,349.65                                                | El Excel no suma GADIT 2                                     |
| Repartido a socios                | $1,800,000.00                                                | 4 pagos de $450,000                                          |
| Reparto con la fórmula del Excel  | Alejandra $1,072,074.81; Javier $893,395.68; Álvaro y GPE $804,056.11 cada uno | D68:D71 = 30 / 25 / 22.5 / 22.5 % de $3,573,582.70; el sistema asigna distinto (hallazgo 14) |

**Inconsistencias conocidas y qué se decide.**

| #    | Hallazgo                                                     | Decisión en el sistema                                       |
| ---- | ------------------------------------------------------------ | ------------------------------------------------------------ |
| 1    | Los gastos no traen proyecto, proveedor, folio ni IVA        | Columnas nuevas opcionales; los históricos quedan sin proyecto y se asignan desde RF-33 |
| 2    | Dos renglones `UTILIDAD ALEJANDDRA ANT` (filas 906 y 909 de GADIT) no tienen importe; ALFREDO registra entregas a socios que GADIT no tiene: adelantos de utilidad de $1.0 M el 05/04 y $1.52 M el 10/04/2025, «DCTO DE UT» de $227,000 el 28/03 y «PAGO PRESTAMO UT» de GPE por $624,500 en abril | Los renglones sin importe se reportan y no se importan; esas entregas las captura el dueño como reparto si corresponde (PEN-14) |
| 3    | `Proveedores crédito` está fijo en 0 en la utilidad          | Se calcula desde `cuentas_por_pagar` (RN-16)                 |
| 4    | La suma de «dónde está el dinero» omite $220,110.60 de efectivo de Alejandra | El saldo es por cuenta y lo calcula la vista (RN-14)         |
| 5    | La celda F68 apunta a una celda vacía y muestra −$3.57 M     | Desaparece: el reparto es una vista (RN-19)                  |
| 6    | Lo que Peren debe sale con tres cifras ($840 k, ≈$1.98 M y $2.15 a $2.33 M) | No se automatiza; los ajustes se capturan a mano (PEN-02)    |
| 7    | Categorías duplicadas (`COMISION` y `COMISIONES`), semana en texto, sumas escritas dentro de celdas | Catálogo cerrado, fecha como `DATE`, semana calculada        |
| 8    | El esquema de efectivo con comisión de 2.5 % tiene implicaciones fiscales | El sistema solo registra; la revisión es del contador (RIE-04) |
| 9    | La columna SEM no es la semana ISO. En 2024 (612 partidas) es la semana de obra contada desde el 09/09/2024 (semana 1), con «0» en las 8 partidas mensuales previas; en 2025 (352 partidas) usa el formato SSAA y coincide con la ISO salvo en 65 partidas: las 59 de GADIT 2 van 1 o 2 semanas adelante y 6 de GADIT difieren en una semana | El sistema usa solo la semana ISO (RN-11). Si el dueño quiere la semana de obra, se calcula desde el inicio del proyecto (PEN-17) |
| 10   | RESPONSABLE mezcla la cuenta con el origen del dinero. SP y GPE: lo pagó el socio de su bolsa y se le reembolsó (SP GPE + GPE = $256,427.41, el «préstamo» de GPE en PRESTAMOS); PRE GPE: se pagó con dinero prestado que administró GPE ($900,000, hoja GPE INGRESOS); CAJA GPE: la caja de efectivo de GPE (hoja CAJA) | Las variantes de GPE van a Caja GPE y el importador anota el valor original en observaciones (IMP-10). La utilidad no cambia: el gasto se cuenta una vez y ni el reembolso ni el préstamo son gasto |
| 11   | Hay gastos que solo están en CAJA y ALFREDO: 55 partidas por $551,521.57 del 16/04 al 30/07/2025 (impuestos $279,448.00, Toño Sistemas $76,450.00, comisiones $71,476.87, IMSS $60,846.22, intereses $13,650.00 y otros $49,650.48), reembolsos de gastos de socios sin detalle por $630,873.81 y, antes de abril de 2025, 3 partidas por $491,000.00 (Apéndice F) | No se importan (DEC-09). El dueño decide si se capturan con su fecha (PEN-18): bajan la utilidad histórica y cambian el reparto, pero no mueven saldos (DEC-19) |
| 12   | 9 partidas tienen en GADIT una fecha con un mes de diferencia respecto de su libro de caja: 5 de CAJA GPE y 4 comisiones de Alfredo (Apéndice F) | Se importa la fecha de GADIT, que es la que suma el Excel; el dueño la corrige si quiere. Solo cambian los reportes por semana y por mes |
| 13   | 17 grupos de partidas idénticas (misma fecha, importe, concepto y responsable) suman 22 partidas de más por $27,315.69. Casi todas son casetas y gasolina repetidas en un viaje; otras pueden ser capturas dobles, por ejemplo HOME DEPOT $4,980.50 (No 126 y 145) | Se importan todas para cuadrar con el Excel; el informe de importación lista los grupos (sección 15) y el dueño borra las que sean dobles |
| 14   | El reparto del Excel (D68:D71) aplica 30 / 25 / 22.5 / 22.5 % a la utilidad que queda después de las 4 entregas iguales de $450,000. El sistema aplica el porcentaje a la utilidad antes de repartir y le resta a cada socio lo que recibió. Con la misma utilidad el total coincide ($3,573,582.70), pero Alejandra recibe $90,000 más, Álvaro y GPE $45,000 menos cada uno y Javier lo mismo. Además, C68 suma $1,227,000 escritos a mano (igual a dos entregas de ALFREDO marcadas como utilidad: $1,000,000 el 05/04 y $227,000 el 28/03/2025), C70 divide entre 4 y D76 ($1,254,000) no tiene fuente | Se usa el método del sistema (RN-19, B8) mientras el dueño no decida otro (PEN-19); los cálculos de trabajo no se migran |
| 15   | Hay deudas que no están en sus hojas: préstamos de $400,000 (07/02/2025, GPE INGRESOS), $55,000 y $200,000 al 3 % (sus intereses están en CAJA; el de $200,000 entró el 15/06/2025) que PRESTAMOS no tiene, y PROVEEDORES no refleja el pago de MALU | Nada de PRESTAMOS ni PROVEEDORES se importa. El dueño entrega la lista de deudas vigentes en la fecha de corte y se capturan antes de arrancar (PEN-20) |
| 16   | Varias cifras están escritas a mano y no se pueden recalcular: «dónde está el dinero» (K62, K63, K66 y K67), PROVEEDORES J34 ($10,400) y PRESTAMOS G8 ($345,100 contra $345,000 en E8; ALVARO INGRESOS confirma $345,100). En PROVEEDORES el abono se calcula como IVA + total, y la celda C1 de ALFREDO muestra el saldo del segundo bloque | Los saldos se recalculan desde el saldo contado en la fecha de corte (DEC-19, RN-14); abonos y pagos se capturan, no se escriben |

**Defectos del origen que el importador debe tolerar:** la fila 469 de GADIT no tiene número (`No`); 2 filas sin importe; 8 filas que solo traen el número de la columna A (GADIT filas 910 a 913 y GADIT 2 filas 62 a 65); la columna `SEM` es texto en celdas con formato de fecha (dos dígitos en 2024 y SSAA en 2025); la hoja GADIT tiene el total en la fila 976 y no debe importarse como partida.

## 5. Decisiones de diseño y supuestos

Estas decisiones ya están tomadas: el equipo las aplica sin volver a discutirlas, y solo cambian por una revisión de este documento.

| ID     | Decisión                                                     | Motivo                                                       |
| ------ | ------------------------------------------------------------ | ------------------------------------------------------------ |
| DEC-01 | Laravel 13 con PHP 8.4 o superior y MySQL 8.4 LTS; Livewire 4, Alpine.js 3 (el que trae Livewire), Tailwind CSS y Vite. Se congela la versión mayor de cada uno hasta terminar v1 | Laravel 13 y Livewire 4 son las líneas con desarrollo activo y el proyecto es nuevo, así que no hay costo de migración; PHP 8.4 es el mínimo de Pest 5; MySQL 8.0 dejó de recibir parches el 30/04/2026 y la 8.4 LTS los recibe hasta 2032. El esquema usa convenciones de Laravel (`bigint unsigned`, `created_at`, `updated_at`, `deleted_at`) |
| DEC-02 | Aplicación monolítica renderizada en servidor; sin SPA ni API pública en v1 | Hasta 10 usuarios y formularios CRUD: la complejidad extra no se justifica |
| DEC-03 | Dinero siempre `DECIMAL(14,2)` en base de datos y `brick/math` (`BigDecimal`) en PHP. Prohibido `float` para importes, porcentajes y totales | Evita errores de centavos                                    |
| DEC-04 | Roles `admin`, `captura` y `consulta` en la columna `usuarios.rol`, aplicados con Policies de Laravel; sin paquete de permisos | Son 3 roles fijos (sección 6)                                |
| DEC-05 | Facturas se cancelan (`estatus`), registros transaccionales se borran con `deleted_at`, catálogos se desactivan (`activo = 0`). El borrado físico está prohibido desde la aplicación, salvo en las tres tablas que lista la sección 9 | Auditoría y recuperación                                     |
| DEC-06 | Las pruebas corren contra MySQL 8.4, nunca SQLite            | El esquema usa vistas, columna generada y restricciones `CHECK` que SQLite no reproduce |
| DEC-07 | Moneda única MXN; no hay columnas de moneda ni tipo de cambio | No hay un solo dato en USD en el Excel (PEN-01)              |
| DEC-08 | IVA por defecto 16.00 %, guardado por contrato y por factura; no hay tasa fija en código salvo el valor inicial de `config/gadit.php` | Una obra puede facturarse con otra tasa                      |
| DEC-09 | La importación automática solo carga CONCENTRADO (contratos, facturas y cobros), GADIT y GADIT 2. Todo lo demás se captura desde la fecha de corte, con saldos iniciales contados | Las demás hojas no traen vínculos confiables (sección 4); cargarlas duplicaría gastos ya incluidos en GADIT |
| DEC-10 | Zona horaria `America/Mexico_City` fija (UTC−6, sin horario de verano) e idioma `es`. Fechas de negocio como `DATE`; auditoría como `TIMESTAMP` | Evita desfases de un día                                     |
| DEC-11 | Adjuntos en disco local privado, nunca en `public/`; tipos PDF, XML, JPG y PNG, hasta 10 MB cada uno | Los comprobantes son datos financieros                       |
| DEC-12 | Bloqueo optimista: un formulario de edición compara `updated_at` al guardar y avisa si otra persona modificó el registro | Dos capturistas pueden abrir el mismo gasto                  |
| DEC-13 | Sin cierres de periodo en v1. El control es el estatus `revisado` (RN-12) y la bitácora | Mantener simple; se reevalúa tras el arranque                |
| DEC-14 | La CLABE se guarda cifrada con el cast `encrypted` de Laravel y la `APP_KEY`; la `APP_KEY` se respalda aparte | Dato financiero sensible (RIE-02)                            |
| DEC-15 | La «utilidad» del sistema es de caja, con la fórmula de RN-18; no sustituye a la contabilidad | Es lo que el Excel calculaba                                 |
| DEC-16 | Nombres de dominio en español (tablas, modelos, rutas, etiquetas) y nombres técnicos en inglés (`Action`, `Policy`, `Service`) | Coincide con el esquema y con el glosario                    |
| DEC-17 | Una sola instalación en un servidor propio (VPS) con Nginx, PHP-FPM y MySQL; el proveedor concreto se define en PEN-06 | El diseño no depende de un proveedor                         |
| DEC-18 | Sin autoservicio de registro: solo el admin crea usuarios. La recuperación de contraseña es por correo con el flujo estándar de Laravel | Pocas personas y datos sensibles                             |
| DEC-19 | Los saldos de cuenta parten de `saldo_inicial` en `fecha_saldo_inicial`; el historial anterior se importa para la utilidad pero no mueve saldos | Las hojas de efectivo no se importan (DEC-09)                |
| DEC-20 | Las pantallas Livewire son componentes de clase (app/Livewire y resources/views/livewire), no de archivo único, y se declaran en las rutas con Route::livewire(). Filtros y búsquedas usan wire:model.live.debounce.300ms; los formularios, wire:model diferido. Nginx no fija la ruta de actualización de Livewire: en la versión 4 lleva un hash derivado de APP\_KEY (/livewire-{hash}/). No se usan paquetes de terceros construidos sobre Livewire | El generador de Livewire 4 crea por defecto componentes de archivo único, con PHP y Blade juntos, y eso borra la capa pantalla, Form Object y Action de la sección 8; los de clase siguen soportados. En la versión 4 los modificadores .blur y .change cambiaron de comportamiento |

**Supuestos** (si alguno resulta falso, se avisa de inmediato porque cambia el trabajo):

1. Los cobros históricos entraron al banco (cuenta 1). Verificar en PEN-03.
2. Los usuarios activos son menos de 10 y se conectan en horario laboral.
3. El volumen es de unos 1,300 gastos al año; el sistema debe ir bien con 100,000 registros, no necesita escalar más.
4. Quien captura tiene correo electrónico propio.
5. El área que opera el sistema entiende los términos del glosario de la sección 3.

## 6. Roles, permisos y seguridad

Hay tres roles fijos; `admin` puede todo, `captura` registra el día a día sin ver utilidad ni dinero por cuenta, y `consulta` solo lee.

**Matriz de permisos.** L = ver, C = crear, E = editar, B = borrado lógico o desactivar. Un guion es acceso denegado: ni menú ni ruta (responde 403).

| Módulo o acción                                            | admin                       | captura                                                      | consulta             |
| ---------------------------------------------------------- | --------------------------- | ------------------------------------------------------------ | -------------------- |
| Tablero completo (utilidad, reparto, saldos)               | L                           | —                                                            | L                    |
| Tablero operativo (facturado, cobrado, por cobrar, gastos) | L                           | L                                                            | L                    |
| Clientes y proyectos                                       | L C E B                     | L C E                                                        | L                    |
| Contratos, adendas y ajustes                               | L C E B                     | L C E                                                        | L                    |
| Facturas                                                   | L C E y cancelar            | L C E                                                        | L                    |
| Cobros                                                     | L C E B                     | L C E                                                        | L                    |
| Gastos                                                     | L C E B y marcar `revisado` | L C E solo si no está `revisado`; puede marcar `falta_comprobante` | L                    |
| Proveedores y cuentas por pagar                            | L C E B                     | L C E                                                        | L                    |
| Datos bancarios                                            | L C E B, CLABE completa     | L C E, CLABE completa                                        | L, CLABE enmascarada |
| Personas                                                   | L C E B                     | L                                                            | L                    |
| Categorías                                                 | L C E B                     | L                                                            | L                    |
| Cuentas y saldo inicial                                    | L C E B                     | Solo elegirlas en formularios, sin saldos                    | L                    |
| Traspasos y estados de cuenta                              | L C E B                     | —                                                            | L                    |
| Préstamos y sus pagos                                      | L C E B                     | —                                                            | L                    |
| Reparto de utilidad                                        | L C E                       | —                                                            | L                    |
| Adjuntos                                                   | L C B                       | L C en registros que puede editar                            | L                    |
| Reportes y exportaciones                                   | Todos                       | Solo los operativos (RF-61)                                  | Todos                |
| Restaurar registros borrados                               | Sí                          | —                                                            | —                    |
| Bitácora                                                   | L                           | —                                                            | —                    |
| Usuarios                                                   | L C E B                     | —                                                            | —                    |

**Cómo se aplica.**

1. Una Policy por modelo con `viewAny`, `view`, `create`, `update`, `delete` y `restore`. Acciones especiales: `Factura::cancel`, `Gasto::markReviewed`, `DatoBancario::viewFullClabe` y `Tablero::viewFull`.
2. Cada acción de Livewire llama a `authorize()`; ocultar un botón no es seguridad.
3. Todas las rutas llevan los middlewares `auth` y `usuario.activo`; un usuario con `activo = 0` pierde la sesión en su siguiente petición.
4. La prueba de la matriz es automatizada: una prueba por rol y por fila (sección 18).

**Controles de seguridad.**

| ID     | Control                                                      |
| ------ | ------------------------------------------------------------ |
| SEG-01 | Contraseña de al menos 12 caracteres y no filtrada (`Password::min(12)->uncompromised()`); hash por defecto de Laravel |
| SEG-02 | Inicio de sesión limitado a 5 intentos por minuto por correo y dirección IP, con mensaje claro de espera |
| SEG-03 | Cookie `Secure`, `HttpOnly` y `SameSite=Lax`; sesión en base de datos que caduca a los 60 minutos de inactividad; sin «recordarme» |
| SEG-04 | Solo HTTPS, con redirección desde HTTP y HSTS de un año      |
| SEG-05 | Cabeceras `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: same-origin` y una CSP en modo solo reporte durante v1 |
| SEG-06 | Toda entrada se valida en servidor; Blade escapa la salida; prohibido `DB::raw` con datos del usuario, salvo listas blancas de columnas para ordenar |
| SEG-07 | La CLABE completa no aparece en logs, bitácora, exportaciones ni mensajes de error; se muestra como `•••• •••• •••• 1234` |
| SEG-08 | Los adjuntos se validan por tipo real del archivo (`finfo`), se guardan con nombre UUID y se sirven por un controlador que revisa permisos; nunca por URL pública |
| SEG-09 | `.env` fuera del repositorio; la `APP_KEY` se guarda también en un gestor de contraseñas; rotarla requiere `APP_PREVIOUS_KEYS` y el comando `gadit:recifrar-clabes` (prioridad C) |
| SEG-10 | En producción, el usuario de base de datos de la aplicación no tiene `DROP` ni `ALTER`; las migraciones corren con otro usuario |
| SEG-11 | `composer audit` y `npm audit` en cada integración continua; actualizaciones de seguridad cada mes |
| SEG-12 | La bitácora no tiene pantalla de edición ni de borrado       |
| SEG-13 | Hay datos personales (nombres, cuentas bancarias): el aviso de privacidad y el cumplimiento de la ley federal de protección de datos personales en posesión de particulares se validan con asesor legal (PEN-11) |

## 7. Reglas de negocio

Estas 26 reglas son la fuente de toda fórmula y validación; cada una tiene su prueba automática (sección 18). Los textos entre « » son los mensajes exactos que ve el usuario.

### Dinero, fechas y texto

| ID    | Regla                                                        |
| ----- | ------------------------------------------------------------ |
| RN-01 | Importes con 2 decimales y máximo 999,999,999,999.99; porcentajes con 2 decimales. Todo cálculo usa `BigDecimal` con redondeo medio hacia arriba (igual que `ROUND` de MySQL). Cada importe derivado se redondea al guardarlo y los totales suman importes ya redondeados. Los campos aceptan `1234.56` y `1,234.56` |
| RN-25 | Gastos, cobros, pagos, préstamos y traspasos no pueden tener fecha futura (hora de México) ni anterior al 01/01/2020. Un vencimiento no puede ser anterior a la fecha del documento y `periodo_fin` no puede ser menor que `periodo_inicio`. Mensaje: «La fecha no puede ser posterior a hoy» |
| RN-26 | Los textos se guardan sin espacios sobrantes y sin cambiar mayúsculas. La búsqueda ignora acentos y mayúsculas por la colación `utf8mb4_unicode_ci` |

### Ingresos: contratos, facturas y cobros

RN-02. Una factura calcula sus importes así (ejemplo A5001: importe 5,029,866.82, IVA 804,778.69, total 5,834,645.51):

```latex
retencion = ROUND(importe \times retencion\_pct / 100, 2)
subtotal = importe - retencion
iva = ROUND(subtotal \times iva\_pct / 100, 2)
total = subtotal + iva
```

| ID    | Regla                                                        |
| ----- | ------------------------------------------------------------ |
| RN-03 | `folio` único (máximo 30 caracteres). `uuid` único, 36 caracteres con formato 8-4-4-4-12 en hexadecimal; se guarda en minúsculas. En facturas nuevas el `uuid` es obligatorio; las importadas pueden no tenerlo |
| RN-04 | `metodo_pago` es obligatorio en facturas nuevas. Con `PPD` la `forma_pago` se fija en `99`; con `PUE` es obligatoria. Cada cobro de una factura `PPD` debe llevar el `complemento_uuid` del Complemento de Pago; sin él aparece en `v_complementos_pendientes`, pero el cobro se puede guardar. `cobros.forma_pago` (la forma real) es obligatoria en cobros nuevos. Con `PUE` y cobros parciales solo hay una advertencia |
| RN-05 | La suma de `importe` de los cobros activos no puede superar el subtotal de la factura (tolerancia de 0.01). Un cobro no puede ser anterior a la fecha de emisión ni ir a una factura cancelada. Mensaje: «El cobro excede el saldo pendiente de la factura (saldo: $X)» |
| RN-06 | El IVA de un cobro se propone como `ROUND(importe × iva_pct / 100, 2)`. Si el cobro liquida la factura, se propone `iva de la factura − IVA ya cobrado` para que no sobren ni falten centavos. La suma de IVA cobrado no puede superar el IVA de la factura en más de 0.05 |
| RN-07 | Situación de la factura (la calcula `v_facturas`): `cancelada` si el estatus es cancelada; `cobrada` si el saldo es 0.01 o menos; `parcial` si hay cobros y queda saldo; `vencida` si no hay cobros y el vencimiento es anterior a hoy; `por_cobrar` en el resto. Además la interfaz marca con una insignia «Vencida» toda factura con saldo y vencimiento pasado, aunque sea parcial |
| RN-08 | Contrato neto = importe − suma de ajustes. Por facturar = neto − suma de subtotales de facturas no canceladas. Por cobrar = facturado − cobrado, ambos sin IVA. Si una factura nueva hace que lo facturado supere el neto se pide confirmar: «Lo facturado excede el contrato neto en $X. ¿Continuar?»; la confirmación queda en la bitácora |
| RN-09 | Una adenda exige `contrato_padre_id` de un contrato de tipo `contrato` del mismo proyecto. Un contrato no tiene padre y no hay adendas de adendas |

### Gastos y cuentas

| ID    | Regla                                                        |
| ----- | ------------------------------------------------------------ |
| RN-10 | El gasto se registra el día en que se paga (base de caja). `importe_con_iva` distinto de 0; los negativos (reembolsos o correcciones) solo los captura el admin y exigen observaciones. `iva_incluido` es opcional y no puede exceder el importe. `concepto` de 1 a 200 caracteres. Categoría y cuenta deben estar activas. El beneficiario es obligatorio, salvo en la importación del Excel (IMP-11), si la categoría es del grupo `comisiones` o `reparto_utilidad` |
| RN-11 | `semana_iso` es `YEARWEEK(fecha, 3)`: semana ISO que empieza en lunes, formato AAAASS. Se muestra como «Sem 04 / 2025» |
| RN-12 | `estatus_revision`: `sin_revisar` por defecto; `captura` o `admin` pueden pasarlo a `falta_comprobante`; solo `admin` lo pasa a `revisado` o lo regresa a `sin_revisar`. Un gasto `revisado` solo lo edita el admin. Subir un adjunto no cambia el estatus por sí solo |
| RN-13 | Un gasto de categoría del grupo `obra` sin proyecto muestra la advertencia «Este gasto no tiene proyecto», pero se guarda y entra a la lista de RF-33 |
| RN-14 | Saldo de cuenta: el saldo contado vale al cierre de `fecha_saldo_inicial`; solo suman los movimientos con fecha posterior. Sin fecha, suman todos. Un saldo negativo no bloquea nada: se muestra en rojo y sale en las alertas del tablero |
| RN-15 | Un traspaso exige origen distinto de destino y monto mayor que 0; `comision_pct` va de 0 a 100. Con comisión mayor que 0 se crea, en la misma transacción, un gasto de categoría `COMISION INTERMEDIARIO` por `ROUND(monto × comision_pct / 100, 2)`, con `traspaso_id`, sin proyecto y en la cuenta de tipo `intermediario` (el destino si ambos lo son); si ninguna lo es, en el origen. Editar o borrar el traspaso edita o borra ese gasto |

RN-14 en fórmula (entradas: cobros con IVA, préstamos recibidos y traspasos entrantes; salidas: gastos, pagos de capital y traspasos salientes):

```latex
saldo = saldo\_inicial + \sum entradas - \sum salidas
```

### Deuda

| ID    | Regla                                                        |
| ----- | ------------------------------------------------------------ |
| RN-16 | Cuenta por pagar: total = importe + IVA; pagado = suma de gastos activos con `cuenta_por_pagar_id`; saldo = total − pagado; pagada si el saldo es 0.01 o menos. Un pago no puede superar el saldo (tolerancia de 0.01): «El pago excede el saldo de la cuenta por pagar ($X)». No se cancela una cuenta con pagos. El gasto de pago hereda proveedor y proyecto de la cuenta |
| RN-17 | Préstamo: saldo de capital = monto − suma de pagos activos; un pago no puede superar el saldo (tolerancia de 0.01). Los intereses y comisiones son gastos con `prestamo_id` (categorías del grupo `financiamiento`); el capital que se devuelve no es gasto. `tasa_periodo` se propone `mensual` (PEN-04) |

### Utilidad y reparto

RN-18. La utilidad de caja es la que calcula `v_resumen_general`. Los préstamos no se restan: el dinero prestado que se gastó ya está en gastos y restarlo de nuevo lo contaría dos veces (hoy el saldo de préstamos es 0, así que el resultado no cambia):

```latex
utilidad\_caja = cobrado\_sin\_iva + iva\_cobrado - gastos\_total - cxp\_saldo
utilidad\_antes\_reparto = utilidad\_caja + reparto\_pagado
```

| ID    | Regla                                                        |
| ----- | ------------------------------------------------------------ |
| RN-19 | Reparto: hay un solo esquema general vigente (`vigente = 1`, sin proyecto); activar otro desactiva el anterior en la misma transacción. Los porcentajes del esquema suman exactamente 100.00, cada uno es mayor que 0 y no se repite persona. Le corresponde = `ROUND(utilidad_antes_reparto × porcentaje / 100, 2)`; ya entregado = gastos del grupo `reparto_utilidad` del socio; pendiente = le corresponde − ya entregado (si es negativo se muestra «Entregado de más»). Una entrega es un gasto de categoría `UTILIDAD` con el socio como beneficiario y sin proyecto. Los esquemas por proyecto existen en el modelo pero no se usan en v1 |

### Datos y control

| ID    | Regla                                                        |
| ----- | ------------------------------------------------------------ |
| RN-20 | Borrado lógico en gastos, cobros, traspasos, préstamos, pagos de préstamo y cuentas por pagar; las facturas se cancelan. Bloqueos: no se cancela una factura con cobros activos; no se borra un préstamo con pagos o intereses activos; no se borra una cuenta por pagar con pagos activos. Al borrar un traspaso se borra su gasto de comisión. Solo el admin restaura, y no se restaura un hijo cuyo padre está borrado o cancelado |
| RN-21 | La bitácora guarda crear, editar y eliminar de cada modelo auditable, con solo los atributos que cambiaron. Una restauración es `editar` con `deleted_at: null`. Nunca guarda `password`, `remember_token`, `clabe` ni `updated_at`. `usuario_id` es nulo en consola. La importación no registra fila por fila: deja un solo registro resumen |
| RN-22 | Datos bancarios: la CLABE son 18 dígitos con dígito verificador válido (Apéndice D), se guarda cifrada y con `clabe_ultimos4`. Pertenece a un proveedor o a una persona, nunca a ambos. Una sola cuenta principal por tercero: al marcar otra se desmarca la anterior en la misma transacción. `banco` es texto libre de hasta 50 caracteres |
| RN-23 | El correo del usuario es único. Los usuarios se desactivan, no se borran. No se puede desactivar ni cambiar de rol al último admin activo, ni un admin a sí mismo |
| RN-24 | Desactivar un catálogo solo lo oculta en listas nuevas; lo ya registrado no cambia. No se desactiva una cuenta con saldo distinto de 0, un proveedor con cuentas por pagar con saldo, ni una persona con préstamos con saldo o en el esquema vigente. Los nombres son únicos: «Ya existe un registro con ese nombre» |

## 8. Arquitectura, stack y despliegue

La aplicación es un solo proyecto Laravel: las pantallas Livewire llaman a casos de uso (Actions) que aplican las reglas de la sección 7 sobre MySQL, y las vistas SQL calculan los totales.

**Capas.** Cada capa solo llama hacia abajo.

| Capa          | Carpeta                | Responsabilidad                                              | Prohibido                                   |
| ------------- | ---------------------- | ------------------------------------------------------------ | ------------------------------------------- |
| Pantallas     | `app/Livewire`         | Componentes de clase (DEC-20): formularios, listados, filtros; valida la entrada con Form Objects y llama a una Action | Reglas de negocio y consultas largas        |
| Casos de uso  | `app/Actions`          | Una clase por operación (`RegistrarCobro`, `CancelarFactura`) con método `handle()`; corre en `DB::transaction`; aplica las RN y lanza `ReglaDeNegocioException` con el mensaje exacto | Leer la petición HTTP                       |
| Modelos       | `app/Models`           | Relaciones, `casts`, `scopes`; un modelo de solo lectura por vista en `app/Models/Vistas` | Escribir en las vistas                      |
| Políticas     | `app/Policies`         | Permisos de la sección 6                                     |                                             |
| Apoyo         | `app/Support`          | `Dinero` (envoltura de `BigDecimal`), `Clabe` (validar y enmascarar), `Cfdi` (leer el XML), `SemanaIso` | Dependencias de Laravel salvo `Str` y `Arr` |
| Consola       | `app/Console/Commands` | `gadit:importar-excel`, gadit:crear-admin, `gadit:recifrar-clabes` |                                             |
| Base de datos | `database/migrations`  | Tablas, vistas, índices y `CHECK`, 1 a 1 con `gadit_schema.sql` |                                             |

**Estructura de carpetas.**

```text
app/
  Actions/{Cobros,Facturas,Gastos,Cxp,Prestamos,Traspasos,Reparto,Importacion}/
  Enums/            RolUsuario, TipoContrato, SituacionFactura, ...
  Livewire/{Tablero,Catalogos,Contratos,Facturas,Cobros,Gastos,Cxp,Dinero,Reparto,Reportes,Admin}/
  Models/ y Models/Vistas/ y Models/Concerns/Auditable.php
  Policies/  Support/  Console/Commands/  Http/Controllers/Descargas*
config/gadit.php  config/sat.php
database/{migrations,seeders}/
resources/views/{livewire,components,layouts}/
tests/{Unit,Feature,Fixtures}/
```

**Paquetes.** No se agrega ningún otro sin aprobarlo en revisión de código.

| Paquete                                                      | Para qué                                                     |
| ------------------------------------------------------------ | ------------------------------------------------------------ |
| `laravel/framework`, `livewire/livewire`                     | Base (Laravel 13) y pantallas (Livewire 4.4 o superior)      |
| `brick/math`                                                 | Aritmética decimal exacta (DEC-03)                           |
| `maatwebsite/excel`                                          | Leer el Excel de origen y exportar XLSX y CSV; línea 4.x, porque la 3.1 ya solo recibe parches de seguridad |
| `barryvdh/laravel-dompdf`                                    | PDF de estado de cuenta y tablero                            |
| `spatie/laravel-backup`                                      | Respaldos de base de datos y adjuntos; versión 10.x, que admite Laravel 13 |
| `laravel-lang/lang`                                          | Textos de Laravel en español                                 |
| npm: `tom-select`, `@alpinejs/mask`, chart.js                | Listas con búsqueda, máscara de importes y gráficas del tablero |
| Desarrollo: `pestphp/pest` con sus plugins de Laravel y Livewire (Pest 5 exige PHP 8.4 y pest-plugin-livewire 5 exige Livewire 4.3.3 o superior), `larastan/larastan`, `laravel/pint` | Pruebas, análisis estático y estilo                          |

Extensiones de PHP: `pdo_mysql`, `mbstring`, `intl`, `bcmath`, `zip`, `gd`, `xml`.

**Base de datos.** Conexión `utf8mb4`, colación `utf8mb4_unicode_ci`, modo estricto, `timezone` `-06:00`. El esquema se validó con `ONLY_FULL_GROUP_BY` activo; no se desactiva. La columna `gastos.semana_iso` se crea con `->storedAs('YEARWEEK(fecha, 3)')`, las restricciones `CHECK` y las vistas con `DB::statement` dentro de la migración, y cada vista lleva su `down()` con `DROP VIEW`.

**Configuración.** `config/gadit.php` guarda los valores de negocio; `.env` solo lo que cambia por entorno.

| Clave                             | Valor inicial                                                |
| --------------------------------- | ------------------------------------------------------------ |
| `gadit.iva_default`               | 16.00                                                        |
| `gadit.adjuntos.max_kb` / `mimes` | 10240 / `pdf,xml,jpg,jpeg,png`                               |
| `gadit.paginacion`                | 25                                                           |
| `gadit.tolerancia_saldo`          | 0.01                                                         |
| `gadit.tolerancia_iva_cobro`      | 0.05                                                         |
| `.env`                            | `APP_ENV`, `APP_URL`, `APP_TIMEZONE=America/Mexico_City`, `APP_LOCALE=es`, `DB_*`, `SESSION_DRIVER=database`, `SESSION_LIFETIME=60`, `CACHE_STORE=database`, `QUEUE_CONNECTION=sync`, `FILESYSTEM_DISK=local`, `MAIL_*`, `LOG_CHANNEL=daily`, `BACKUP_*` |

**Procesos en segundo plano.** v1 no usa colas: el correo y las exportaciones son síncronos (hasta 20,000 filas por exportación). Un cron ejecuta `schedule:run` cada minuto; el programador corre `backup:run` a las 02:00, `backup:clean` a las 02:30 y `backup:monitor` a las 03:00; además corre auth:clear-resets a las 03:30.

**Entornos y entrega.**

- **Local:** Laravel Sail con PHP 8.4 (Sail trae 8.5 por defecto), MySQL 8.4 y Mailpit. Se instala solo con esos dos servicios porque la v1 no usa Redis (sesión y caché en database, colas sync), y la imagen de MySQL se fija a 8.4 en compose.yaml. En Windows corre sobre WSL2 y el proyecto vive dentro del sistema de archivos de Linux, no en /mnt/c, porque ahí es mucho más lento.
- **Integración continua:** instalar, `pint --test`, `larastan`, `pest` con servicio MySQL 8.4, `composer audit`, `npm audit` y `npm run build`. Una rama no se une si falla algo.
- **Producción:** VPS con Nginx, PHP-FPM 8.4 y MySQL 8.4 (DEC-17). Una rama `main` protegida, ramas cortas por tarea y una revisión obligatoria por pull request.
- **Despliegue:** poner en mantenimiento, `git pull`, `composer install --no-dev -o`, `npm ci && npm run build`, `php artisan migrate --force`, `config:cache`, `route:cache`, `view:cache`, quitar mantenimiento. Todo en un `deploy.sh` versionado.

**Observabilidad.** Logs diarios con 14 días de retención; ruta `/up` de salud; un monitor externo de disponibilidad que avisa por correo (PEN-06).

## 9. Modelo de datos

La base tiene 21 tablas y 13 vistas definidas en `gadit_schema.sql` v1.2; las columnas y tipos están allí y esta sección añade lo que el SQL no dice: modelos, borrado, restricciones, etiquetas y relaciones.

**Tablas.** Todo modelo, salvo `Bitacora`, usa el trait `Auditable` (RN-21). Todas las llaves foráneas son `RESTRICT`.

| Tabla                    | Modelo                     | Borrado                  | Llaves y restricciones                                       |
| ------------------------ | -------------------------- | ------------------------ | ------------------------------------------------------------ |
| `usuarios`               | `Usuario`                  | `activo`                 | Correo único                                                 |
| `personas`               | `Persona`                  | `activo`                 | Nombre único                                                 |
| `clientes`               | `Cliente`                  | `activo`                 | Nombre único                                                 |
| `proyectos`              | `Proyecto`                 | `estatus`                | Código único; FK a cliente                                   |
| `contratos`              | `Contrato`                 | `estatus` cancelado      | FK a proyecto y a contrato padre; importe ≥ 0                |
| `contrato_ajustes`       | `ContratoAjuste`           | Físico                   | FK a contrato; el importe positivo resta                     |
| `cuentas`                | `Cuenta`                   | `activa`                 | Nombre único; `saldo_inicial` y `fecha_saldo_inicial`        |
| `facturas`               | `Factura`                  | Cancelación              | Folio y `uuid` únicos; retención de 0 a 100; formatos de `uuid` y `forma_pago` |
| `cobros`                 | `Cobro`                    | `deleted_at`             | FK a factura y cuenta; importe > 0; formato de `complemento_uuid` |
| `categorias`             | `Categoria`                | `activa`                 | Nombre único; `grupo` cerrado                                |
| `proveedores`            | `Proveedor`                | `activo`                 | Nombre único                                                 |
| `datos_bancarios`        | `DatoBancario`             | `activa`                 | Dueño: proveedor o persona (uno solo); `clabe_ultimos4` de 4 dígitos |
| `cuentas_por_pagar`      | `CuentaPorPagar`           | `deleted_at`             | FK a proveedor y proyecto; importe ≥ 0                       |
| `prestamos`              | `Prestamo`                 | `deleted_at`             | FK a persona acreedora y cuenta; monto > 0                   |
| `prestamo_pagos`         | `PrestamoPago`             | `deleted_at`             | FK a préstamo y cuenta; capital > 0                          |
| `traspasos`              | `Traspaso`                 | `deleted_at`             | Origen distinto de destino; monto > 0                        |
| `gastos`                 | `Gasto`                    | `deleted_at`             | 9 FK (categoría y cuenta obligatorias); importe distinto de 0; único (`origen`, `numero_origen`); formato de `cfdi_uuid`; columna generada `semana_iso` |
| `esquemas_reparto`       | `EsquemaReparto`           | `vigente`                | La aplicación garantiza un solo esquema general vigente      |
| `esquema_reparto_socios` | Pivote de `EsquemaReparto` | Físico                   | Llave compuesta (esquema, persona); porcentaje de 0 a 100    |
| `adjuntos`               | `Adjunto`                  | Físico, borra el archivo | Índice (`entidad`, `entidad_id`)                             |
| `bitacora`               | `Bitacora`                 | Nunca                    | Índice (`tabla`, `registro_id`)                              |

El borrado físico solo existe en `contrato_ajustes`, `esquema_reparto_socios` y `adjuntos`, siempre con registro en bitácora.

**Modelos Eloquent.**

- Cada modelo declara `$table` explícito, `$fillable` y `casts`: fechas como `date`, importes como `decimal:2` (se leen con `Dinero`), booleanos y enums respaldados por clase.
- `Usuario` usa la tabla `usuarios` (`config/auth.php`), identifica por `email`, no tiene `remember_token` (`getRememberTokenName()` devuelve cadena vacía) y únicamente el admin lo crea.
- `DatoBancario::clabe` lleva el cast `encrypted`.
- `Adjunto` apunta a su registro con `morphTo('entidad', 'entidad', 'entidad_id')` y `Relation::enforceMorphMap` con las claves `gasto`, `factura`, `cobro`, `contrato`, `cuenta_por_pagar` y `prestamo`.
- `Gasto`, `Cobro`, `Traspaso`, `Prestamo`, `PrestamoPago` y `CuentaPorPagar` usan `SoftDeletes`.

| Modelo           | Relaciones                                                   |
| ---------------- | ------------------------------------------------------------ |
| `Cliente`        | `proyectos`                                                  |
| `Proyecto`       | `cliente`, `contratos`, `gastos`, `cuentasPorPagar`          |
| `Contrato`       | `proyecto`, `padre`, `adendas`, `ajustes`, `facturas`        |
| `Factura`        | `contrato`, `cobros`                                         |
| `Cobro`          | `factura`, `cuenta`                                          |
| `Gasto`          | `categoria`, `cuenta`, `proyecto`, `proveedor`, `beneficiario` (Persona), `cuentaPorPagar`, `prestamo`, `traspaso`, `creadoPor` (Usuario), `adjuntos` |
| `CuentaPorPagar` | `proveedor`, `proyecto`, `pagos` (Gasto)                     |
| `Prestamo`       | `acreedor` (Persona), `cuenta`, `pagos`, `intereses` (Gasto) |
| `Traspaso`       | `origen`, `destino`, `comisiones` (Gasto)                    |
| `DatoBancario`   | `proveedor`, `persona`                                       |
| `EsquemaReparto` | `socios` (Persona, con `porcentaje` en el pivote)            |

**Vistas.** Cada una tiene un modelo de solo lectura en `app/Models/Vistas` y es la única fuente de sus totales.

| Vista                                    | Alimenta                                                     |
| ---------------------------------------- | ------------------------------------------------------------ |
| `v_facturas`                             | Listado de facturas y cobros pendientes (RF-42); retención, subtotal, IVA, total, cobrado, saldo y situación |
| `v_contratos_resumen`                    | Tabla por contrato del tablero y de contratos (RF-40)        |
| `v_complementos_pendientes`              | Alerta y reporte de Complementos de Pago (RF-44)             |
| `v_cuentas_por_pagar`                    | Cuentas por pagar con banco y últimos 4 dígitos de la CLABE (RF-50) |
| `v_prestamos`                            | Préstamos con saldo e intereses (RF-51)                      |
| `v_gastos_detalle`                       | Listado de gastos con nombres resueltos (RF-31)              |
| `v_gastos_categoria` y `v_gastos_semana` | Gráficas y reportes de gasto (RF-60 y RF-61)                 |
| `v_movimientos_cuenta`                   | Estado de cuenta (RF-53)                                     |
| `v_saldo_cuentas`                        | «Dónde está el dinero» del tablero (RF-60)                   |
| `v_resumen_general`                      | Tarjetas de contratado, facturado, cobrado, gastos y utilidad (RF-60) |
| `v_rentabilidad_proyecto`                | Reporte de rentabilidad por proyecto (RF-61)                 |
| `v_reparto_utilidad`                     | Pantalla de reparto (RF-54)                                  |

**Etiquetas de los valores cerrados.** Cada columna `ENUM` es un `Enum` respaldado de PHP en `app/Enums` con la etiqueta en español que sigue; el valor guardado es el de la izquierda.

| Enum                                          | Valores y etiquetas                                          |
| --------------------------------------------- | ------------------------------------------------------------ |
| `RolUsuario`                                  | `admin` Administrador; `captura` Captura; `consulta` Consulta |
| `EstatusProyecto`                             | `en_proceso` En proceso; `terminado` Terminado; `suspendido` Suspendido |
| `TipoContrato` y `EstatusContrato`            | `contrato` Contrato; `adenda` Adenda · `vigente` Vigente; `terminado` Terminado; `cancelado` Cancelado |
| `TipoAjuste`                                  | `no_ejecutado` No ejecutado; `deductiva` Deductiva; `descuento` Descuento; `otro` Otro |
| `TipoCuenta`                                  | `banco` Banco; `caja` Caja en efectivo; `intermediario` Intermediario |
| `TipoFactura`, `EstatusFactura`, `MetodoPago` | `anticipo` Anticipo; `estimacion` Estimación; `finiquito` Finiquito; `otro` Otro · `emitida` Emitida; `cancelada` Cancelada · `PUE` Pago en una sola exhibición; `PPD` Pago en parcialidades o diferido |
| `SituacionFactura` (vista)                    | `por_cobrar` Por cobrar; `parcial` Parcial; `cobrada` Cobrada; `vencida` Vencida; `cancelada` Cancelada |
| `GrupoCategoria`                              | `obra` Obra; `nomina` Nómina; `impuestos` Impuestos y seguridad social; `comisiones` Comisiones; `reparto_utilidad` Reparto de utilidad; `financiamiento` Financiamiento; `logistica` Logística y viáticos; `administracion` Administración; `otros` Otros |
| `EstatusCxp`                                  | `vigente` Vigente; `cancelada` Cancelada                     |
| `PeriodoTasa`                                 | `mensual` Mensual; `anual` Anual; `unica` Única vez          |
| `EstatusRevision`                             | `sin_revisar` Sin revisar; `revisado` Revisado; `falta_comprobante` Falta comprobante |
| `EntidadAdjunto`                              | `gasto` Gasto; `factura` Factura; `cobro` Cobro; `contrato` Contrato; `cuenta_por_pagar` Cuenta por pagar; `prestamo` Préstamo |
| `AccionBitacora`                              | `crear` Crear; `editar` Editar; `eliminar` Eliminar          |
| `TipoMovimiento` (vista)                      | `cobro` Cobro; `prestamo` Préstamo recibido; `traspaso_entrada` Traspaso recibido; `gasto` Gasto; `pago_prestamo` Pago de préstamo; `traspaso_salida` Traspaso enviado |

**Migraciones.**

1. Una migración por tabla, en el orden de `gadit_schema.sql` (`usuarios`, `personas`, `clientes`, `proyectos`, `contratos`, `contrato_ajustes`, `cuentas`, `facturas`, `cobros`, `categorias`, `proveedores`, `datos_bancarios`, `cuentas_por_pagar`, `prestamos`, `prestamo_pagos`, `traspasos`, `gastos`, `esquemas_reparto`, `esquema_reparto_socios`, `adjuntos`, `bitacora`), y después una por vista en el orden del archivo, porque unas dependen de otras.
2. Además las tablas del framework: `migrations`, `password_reset_tokens`, `sessions`, `cache` y `cache_locks`. No hay `jobs` porque no hay colas (sección 8).
3. Prueba de equivalencia: una prueba compara la lista de tablas, columnas, índices y vistas creada por las migraciones con la creada por `gadit_schema.sql`; si difieren, falla.
4. Datos de arranque: `CatalogosBaseSeeder` (personas, cuentas, categorías y esquema de reparto, apto para producción), `UsuariosDemoSeeder` (solo local y pruebas) y `php artisan gadit:crear-admin` para el primer usuario real.

## 10. Especificación funcional: patrones comunes, acceso y catálogos

Los patrones RF-01 a RF-10 valen para todas las pantallas; cada módulo solo dice en qué se aparta de ellos. Los RF se numeran por familia (10 a 26 acceso y catálogos, 30 gastos, 40 ingresos, 50 deuda y dinero, 60 tablero y reportes, 70 administración, 80 interfaz).

### Patrones comunes

| ID    | Patrón                                                       |
| ----- | ------------------------------------------------------------ |
| RF-01 | **Listados.** Paginación de 25 con selector de 10, 25, 50 y 100; orden por cualquier columna con encabezado; búsqueda de texto con espera de 300 ms; filtros guardados en la URL para poder compartirla. Muestran «N registros» y, si hay importes, la suma de lo filtrado. Estado vacío con mensaje y, si el rol puede, botón de crear |
| RF-02 | **Formularios.** Los catálogos simples, cobros, pagos y traspasos se editan en ventana modal; gastos, facturas, contratos, cuentas por pagar y préstamos, en página propia (`/gastos/crear`, `/gastos/{id}/editar`) |
| RF-03 | **Validación.** En servidor, con el mensaje bajo cada campo y el foco en el primer error; lo capturado se conserva. Un error de regla de negocio sale en un aviso rojo sobre el formulario con el texto de la sección 7 |
| RF-04 | **Guardado.** El botón se desactiva mientras guarda (evita doble envío) y al terminar aparece el aviso «Guardado» 4 segundos. Los formularios de captura repetitiva ofrecen «Guardar y capturar otro» |
| RF-05 | **Bloqueo optimista (DEC-12).** Si `updated_at` cambió desde que se abrió el formulario: «Otra persona modificó este registro mientras lo editabas. Recarga para ver los cambios», y no se guarda |
| RF-06 | **Borrar o cancelar.** Ventana de confirmación con el resumen del registro y un motivo opcional que va a `bitacora.despues.motivo`. Después del borrado un aviso ofrece «Deshacer» durante 10 segundos (restaura, solo si el rol puede) |
| RF-07 | **Exportar.** Cada listado tiene «Exportar» en CSV y XLSX; respeta filtros y permisos, llega a 20,000 filas, nunca incluye la CLABE y se llama `gadit_<módulo>_<AAAAMMDD>` |
| RF-08 | **Adjuntos.** Componente único (RF-34) en el detalle de gasto, factura, cobro, contrato, cuenta por pagar y préstamo |
| RF-09 | **Historial.** Pestaña «Historial» en el detalle de cada registro auditado, solo para admin, con la bitácora de ese registro (RF-70) |
| RF-10 | **Rutas.** Nombres en español y en minúsculas (`gastos.index`, `gastos.crear`, `gastos.editar`); `/` redirige al tablero; un registro inexistente da 404 y un permiso denegado da 403 con una página amable; las pantallas Livewire se declaran con Route::livewire() (DEC-20) |

Las listas desplegables de los formularios solo ofrecen registros activos, pero al editar muestran el valor actual aunque ya esté inactivo.

### Acceso y usuarios

| ID    | Pantalla                                     | Comportamiento                                               |
| ----- | -------------------------------------------- | ------------------------------------------------------------ |
| RF-11 | Inicio de sesión `/iniciar-sesion`           | Correo y contraseña. Cualquier fallo, incluido un usuario inactivo, dice «Correo o contraseña incorrectos». Aplica SEG-02. Al entrar va al tablero o a la dirección que se intentaba abrir |
| RF-12 | Cierre de sesión                             | Botón en el menú de usuario; invalida la sesión              |
| RF-13 | Recuperar contraseña `/olvide-mi-contrasena` | Pide el correo y siempre responde «Si el correo existe, enviamos las instrucciones». El enlace del correo vale 60 minutos y lleva a `/restablecer-contrasena`. Aplica SEG-01 |
| RF-14 | Mi perfil `/perfil`                          | Cambia nombre y contraseña (pide la actual). No cambia correo ni rol |
| RF-15 | Usuarios `/usuarios` (admin)                 | Lista con nombre, correo, rol, estado y fecha de alta. Al crear, el admin captura nombre, correo y rol; el sistema envía el correo de «establece tu contraseña» (el flujo de RF-13), no hay contraseñas temporales. Puede cambiar rol y desactivar o reactivar, con RN-23 |

### Catálogos

Los catálogos usan los patrones anteriores, tienen listado con búsqueda por nombre y filtro «Activo», y se editan en ventana modal salvo `Proyecto`.

| ID    | Catálogo y ruta                                              | Columnas del listado                                         | Campos del formulario (obligatorio con \*)                   |
| ----- | ------------------------------------------------------------ | ------------------------------------------------------------ | ------------------------------------------------------------ |
| RF-20 | Clientes `/clientes`                                         | Nombre, RFC, contacto, proyectos activos, activo             | Nombre\* (hasta 120, único); RFC (12 o 13 caracteres, mayúsculas, formato SAT); contacto (hasta 120); activo |
| RF-21 | Proyectos `/proyectos` (página)                              | Código, nombre, cliente, estatus, inicio, fin, contratado, gastado | Cliente\* (activo); código (hasta 30, único); nombre\* (hasta 160); ubicación (hasta 160); estatus (por defecto `en_proceso`); fecha de inicio y de fin (fin no menor que inicio); notas (hasta 255) |
| RF-22 | Personas `/personas`                                         | Nombre, alias, socio, teléfono, activo                       | Nombre\* (hasta 120, único); alias (hasta 60); teléfono (hasta 30); socio (sí o no); activo; notas |
| RF-23 | Proveedores `/proveedores`                                   | Nombre, RFC, teléfono, días de crédito, saldo por pagar, activo | Nombre\* (hasta 140, único); RFC; teléfono; días de crédito (entero de 0 a 365); activo; notas |
| RF-24 | Datos bancarios, dentro del detalle de un proveedor o una persona | Banco, titular, CLABE enmascarada, principal, activa         | Banco\* (hasta 50); CLABE\* (18 dígitos con dígito verificador válido; limpia espacios y guiones al guardar); titular (hasta 120); principal; activa; notas |
| RF-25 | Categorías `/categorias`                                     | Nombre, grupo, activa, número de gastos, total               | Nombre\* (hasta 80, único); grupo\* (los 9 de la sección 9); activa. Cambiar el grupo pide confirmar porque cambia reportes anteriores |
| RF-26 | Cuentas `/cuentas`                                           | Nombre, tipo, custodio, saldo actual (oculto para `captura`), activa | Nombre\* (hasta 100, único); tipo\*; custodio (persona); saldo inicial (por defecto 0.00, puede ser negativo); fecha del saldo inicial (obligatoria si el saldo no es 0); activa; notas |

Puntos finos:

1. **Detalle del proyecto** (`/proyectos/{id}`): pestañas Resumen (de `v_rentabilidad_proyecto`), Contratos, Facturas, Gastos y Cuentas por pagar, cada una con el listado filtrado por ese proyecto.
2. **CLABE completa** (RF-24): un botón «Mostrar» la revela y otro «Copiar» la copia; solo para `admin` y `captura`. La aplicación anota en su log quién la mostró y cuándo, sin el valor.
3. **Saldo inicial** (RF-26): solo el admin lo cambia, con confirmación; el cambio queda en la bitácora (RN-14 y DEC-19).
4. **Desactivar** aplica RN-24: si hay bloqueo, el mensaje dice por qué.

## 11. Especificación funcional: contratos, facturas y cobros

El flujo es contrato, factura y cobro: cada paso nace del anterior, y los saldos salen de `v_contratos_resumen` y `v_facturas`, nunca se capturan.

### Contratos y ajustes (RF-40 y RF-41)

| ID    | Pantalla                                           | Comportamiento                                               |
| ----- | -------------------------------------------------- | ------------------------------------------------------------ |
| RF-40 | Contratos `/contratos` y detalle `/contratos/{id}` | Listado de `v_contratos_resumen` con proyecto, cliente, tipo, pedido, concepto, contratado, ajustes, neto, facturado, por facturar, cobrado y por cobrar; filtros por proyecto, cliente, tipo, estatus y texto; totales al pie. El detalle trae esos siete indicadores arriba y pestañas Facturas (con «Nueva factura» ya ligada al contrato), Ajustes, Adendas, Adjuntos e Historial |
| RF-41 | Ajustes, pestaña del contrato                      | Lista de tipo, concepto, importe y fecha; alta y edición en modal; el borrado es físico y queda en bitácora (sección 9). Las reglas de Peren (factor de 80 %, deductivas) no se calculan: se capturan como ajustes a mano (PEN-02) |

Formulario de contrato (página; obligatorio con \*):

| Campo            | Regla                                                        |
| ---------------- | ------------------------------------------------------------ |
| Proyecto\*       | Activo                                                       |
| Tipo\*           | `contrato` por defecto; `adenda` muestra el campo siguiente  |
| Contrato padre   | Obligatorio en adendas: contratos de tipo `contrato` del mismo proyecto (RN-09) |
| Número de pedido | Texto de hasta 60 caracteres; no se fuerza a número          |
| Concepto\*       | Hasta 200                                                    |
| Importe\*        | 0 o más, sin IVA                                             |
| IVA %            | 16.00 por defecto, de 0 a 100                                |
| Fecha            | Opcional                                                     |
| Estatus          | `vigente` por defecto                                        |
| Notas            | Hasta 255                                                    |

Reglas locales: un contrato con facturas no canceladas no pasa a `cancelado`; bajar el importe por debajo de lo facturado muestra el aviso de RN-08 pero se puede guardar.

### Facturas (RF-42)

**Listado `/facturas`** (de `v_facturas`): folio, UUID (primeros 8 caracteres), cliente, proyecto, contrato, tipo, concepto, emisión, vencimiento, subtotal, IVA, total, cobrado, saldo, situación con insignia y método de pago. Filtros: situación (varias), cliente, proyecto, contrato, rango de emisión, rango de vencimiento, método de pago, texto (folio, UUID, concepto) y «solo con saldo». El pie suma subtotal, IVA, total, cobrado y saldo de lo filtrado. Por fila: ver, editar, cobrar (abre RF-43), cancelar (admin).

**Colores de la insignia:** `cobrada` verde, `parcial` ámbar, `por_cobrar` azul, `vencida` rojo, `cancelada` gris.

| Campo del formulario | Regla                                                        |
| -------------------- | ------------------------------------------------------------ |
| Contrato\*           | Lista «Proyecto · concepto · pedido» de contratos vigentes   |
| Folio\*              | Hasta 30, único (RN-03)                                      |
| UUID\*               | 36 caracteres, formato 8-4-4-4-12; único (RN-03)             |
| Tipo\*               | `estimacion` por defecto                                     |
| Número de estimación | Entero de 1 a 99, opcional                                   |
| Concepto\*           | Hasta 200                                                    |
| Fecha de emisión\*   | No futura (RN-25)                                            |
| Fecha de vencimiento | No anterior a la emisión                                     |
| Importe\*            | Mayor que 0, antes de retención e IVA                        |
| Retención %          | 0 por defecto, de 0 a 100                                    |
| IVA %                | El del contrato                                              |
| Método de pago\*     | `PUE` o `PPD`                                                |
| Forma de pago\*      | Lista SAT (Apéndice C); con `PPD` queda fija en `99` y bloqueada |
| Notas                | Hasta 255                                                    |

Al lado del formulario un panel muestra, en vivo, retención, subtotal, IVA y total con las fórmulas de RN-02. Al guardar corren RN-03, RN-04 y RN-08.

Reglas de edición y cancelación:

1. Con cobros activos no se pueden cambiar contrato, importe, retención ni IVA; sí los textos, fechas, UUID y método de pago.
2. **Cancelar** (solo admin) pide un motivo obligatorio, cambia el estatus a `cancelada` y falla si hay cobros activos (RN-20). El admin también puede **reactivar** una factura cancelada; ambos movimientos quedan en la bitácora.
3. El detalle `/facturas/{id}` muestra los totales, la tabla de cobros, los complementos, los adjuntos y el historial.

### Cobros (RF-43)

**Listado `/cobros`:** fecha, folio, cliente, cuenta, importe, IVA, total, forma de pago, referencia y complemento («Sí», «Pendiente» o «No aplica»). Filtros: rango de fecha, cuenta, cliente, forma de pago y complemento pendiente. Suma al pie.

| Campo del formulario (modal) | Regla                                                        |
| ---------------------------- | ------------------------------------------------------------ |
| Factura\*                    | Preseleccionada si viene de una factura; si no, búsqueda por folio entre facturas con saldo |
| Fecha\*                      | No anterior a la emisión ni futura (RN-05, RN-25); hoy por defecto |
| Cuenta\*                     | Activa; propone la del cobro anterior o, si no hay, el banco |
| Importe\*                    | Mayor que 0; propone el saldo pendiente sin IVA; no puede exceder el saldo (RN-05) |
| IVA                          | Propuesto por RN-06 y editable                               |
| Forma de pago\*              | Lista SAT; la forma real del cobro                           |
| Referencia                   | Hasta 80 (número de rastreo o folio de depósito)             |
| UUID del complemento         | Opcional; se destaca solo si la factura es `PPD` (RN-04)     |
| Notas                        | Hasta 255                                                    |

Al guardar se muestra el nuevo saldo y la situación de la factura. Borrar un cobro es borrado lógico (RN-20) y muestra cómo cambia la factura.

### Complementos de Pago pendientes (RF-44)

La pantalla `/complementos-pendientes` lista `v_complementos_pendientes` con folio, UUID de la factura, cliente, fecha del cobro, monto, forma de pago y días desde el cobro, de más antiguo a más reciente. Se seleccionan uno o varios cobros y «Registrar complemento» les asigna el mismo UUID (formato validado). El tablero muestra la cantidad pendiente. El resaltado por antigüedad lo define `gadit.complemento_alerta_dias`, que queda vacío (sin resaltado) hasta resolver PEN-12.

## 12. Especificación funcional: gastos

Los gastos son la tabla central y lo que más se captura, así que registrar uno debe tomar medio minuto: cinco campos obligatorios y todo lo demás opcional.

### Captura y edición (RF-30)

Rutas `/gastos/crear` y `/gastos/{id}/editar`. Campos (obligatorio con \*):

| Campo                    | Regla                                                        |
| ------------------------ | ------------------------------------------------------------ |
| Fecha\*                  | Hoy por defecto; RN-25                                       |
| Importe con IVA\*        | Distinto de 0; los negativos solo los captura el admin con observaciones (RN-10) |
| Concepto\*               | De 1 a 200 caracteres; sugiere conceptos anteriores que coincidan (prioridad C) |
| Categoría\*              | Lista con búsqueda, agrupada por grupo; solo activas         |
| Cuenta\*                 | De dónde salió el dinero; propone la última cuenta usada por esa persona en su navegador |
| Proyecto                 | Opcional; con una categoría del grupo `obra` y sin proyecto sale el aviso de RN-13 |
| Proveedor                | Opcional                                                     |
| Beneficiario             | Persona; obligatorio en categorías de comisiones y reparto de utilidad (RN-10) |
| IVA incluido             | Opcional; de 0 al importe                                    |
| UUID del CFDI            | Opcional; formato de RN-03                                   |
| Periodo (inicio y fin)   | Solo aparece con categorías de nómina o impuestos; el fin no es menor que el inicio |
| Pago de cuenta por pagar | Opcional: cuentas vigentes del proveedor con saldo. Si se elige, hereda proveedor y proyecto y aplica RN-16 |
| Préstamo                 | Opcional: préstamos con saldo, para intereses y comisiones (RN-17) |
| Estatus de revisión      | `sin_revisar` por defecto; reglas de RN-12                   |
| Observaciones            | Hasta 255                                                    |
| Comprobante              | Foto o archivo opcional; se guarda junto con el gasto (RF-34) |

Comportamientos:

1. **Guardar y capturar otro:** conserva fecha, cuenta, proyecto y categoría; limpia importe, concepto, observaciones y comprobante.
2. **Posible duplicado:** si ya hay un gasto activo con la misma fecha, importe, cuenta y concepto (sin distinguir mayúsculas) sale «Parece duplicado del gasto #N. ¿Guardar de todos modos?» con enlace al otro.
3. **Cuenta por pagar o préstamo:** al guardar se recalcula el saldo y, si el pago lo excede, falla con el mensaje de RN-16 o RN-17.
4. **Auditoría:** `creado_por` es el usuario; `origen` y `numero_origen` quedan nulos en lo capturado a mano.
5. **Duplicar** (prioridad C): desde el detalle abre el formulario con los mismos datos y la fecha de hoy.

El detalle `/gastos/{id}` muestra todos los campos con enlaces al proveedor, la cuenta por pagar, el préstamo y el traspaso relacionados, más adjuntos e historial.

### Listado y filtros (RF-31)

Ruta `/gastos`. Usa el modelo `Gasto` con sus relaciones cargadas (los nombres son los de `v_gastos_detalle`, que se usa en exportaciones y reportes). Orden por defecto: fecha descendente y luego id descendente.

**Columnas:** fecha, semana, concepto, importe, categoría (el grupo en ayuda emergente), cuenta, proyecto, proveedor, beneficiario, estatus de revisión con insignia (`sin_revisar` gris, `revisado` verde, `falta_comprobante` rojo), número de adjuntos y marca de CFDI.

**Filtros:**

| Filtro                          | Detalle                                                      |
| ------------------------------- | ------------------------------------------------------------ |
| Fecha                           | Atajos: hoy, esta semana, este mes, mes pasado; o rango libre |
| Semana                          | Formato AAAASS                                               |
| Categoría y grupo               | Selección múltiple                                           |
| Cuenta, proveedor, beneficiario | Selección múltiple                                           |
| Proyecto                        | Selección múltiple con la opción «Sin proyecto»              |
| Estatus de revisión             | Uno o varios                                                 |
| Importe                         | Mínimo y máximo                                              |
| Comprobante                     | Con o sin adjunto; con o sin CFDI                            |
| Origen                          | Capturado o importado                                        |
| Texto                           | Concepto y observaciones                                     |

El pie muestra el número de partidas y el total de lo filtrado.

### Acciones masivas (RF-32)

Con casillas de selección (hasta 500 filas) aparece una barra de acciones, cada una con confirmación que dice cuántos registros afecta y todas en una transacción con bitácora por fila:

| Acción                     | Quién                                                     |
| -------------------------- | --------------------------------------------------------- |
| Asignar proyecto           | `admin` y `captura` (esta última se salta los `revisado`) |
| Cambiar categoría          | `admin` y `captura` (igual)                               |
| Marcar `falta_comprobante` | `admin` y `captura`                                       |
| Marcar `revisado`          | `admin`                                                   |
| Eliminar (borrado lógico)  | `admin`                                                   |

Asignar proyecto es la herramienta para ordenar el historial importado (PEN-08).

### Colas de trabajo (RF-33)

Son filtros guardados de RF-31 con su propio contador en el tablero:

- `/gastos/sin-proyecto`: gastos sin proyecto de categorías del grupo `obra`; un interruptor los amplía a todas las categorías.
- `/gastos/sin-comprobante`: gastos con estatus `falta_comprobante`.

### Adjuntos (RF-34)

| Aspecto         | Especificación                                               |
| --------------- | ------------------------------------------------------------ |
| Tipos y tamaño  | PDF, XML, JPG, JPEG y PNG; hasta 10 MB cada uno y hasta 10 por registro |
| Subir           | Arrastrar o elegir varios; en celular el botón «Tomar foto» usa la cámara (`capture="environment"`) |
| Guardado        | `storage/app/private/adjuntos/{entidad}/{id}/{uuid}.{ext}`; la tabla guarda el nombre original ya depurado |
| Ver y descargar | PDF e imágenes se abren en el visor; todo se sirve por un controlador que aplica la política del registro padre (SEG-08) |
| Borrar          | Solo `admin`; borra el archivo y deja registro en bitácora   |
| Quién sube      | Quien puede editar el registro padre                         |

### Lectura de XML de CFDI (RF-35, prioridad C)

Al subir un XML a un gasto o una factura, el sistema lee el comprobante (CFDI 3.3 o 4.0) y propone el UUID del timbre fiscal: «Detectamos el CFDI {uuid} por $total. ¿Usar este UUID?». Si el total difiere del importe capturado avisa sin bloquear. No valida firmas ni consulta al SAT. El lector desactiva entidades externas y red (`LIBXML_NONET`) y rechaza XML de más de 2 MB.

## 13. Especificación funcional: cuentas por pagar, préstamos, traspasos y reparto

Estos módulos llevan lo que se debe, lo que se pidió prestado, el movimiento entre cuentas y lo que toca a cada socio; ninguno guarda saldos, todos salen de vistas.

### Cuentas por pagar (RF-50)

**Listado `/cuentas-por-pagar`** (de `v_cuentas_por_pagar`): proveedor, concepto, proyecto, fecha, vencimiento, total, pagado, saldo, situación y banco con últimos 4 dígitos de la CLABE principal. La situación es `pagada` (saldo de 0.01 o menos), `vencida` (hay saldo y el vencimiento pasó), `cancelada` o `vigente`. Filtros: proveedor, proyecto, situación, rango de fecha y «solo con saldo» (activo por defecto). El pie suma total, pagado y saldo.

| Campo del formulario (página) | Regla                                                        |
| ----------------------------- | ------------------------------------------------------------ |
| Proveedor\*                   | Activo                                                       |
| Proyecto                      | Opcional                                                     |
| Fecha\*                       | RN-25                                                        |
| Vencimiento                   | Propone fecha más los días de crédito del proveedor; no anterior a la fecha |
| Concepto                      | Hasta 200                                                    |
| Importe\*                     | Mayor que 0, sin IVA; no puede quedar por debajo de lo ya pagado |
| IVA                           | Propone `ROUND(importe × 16 / 100, 2)` (`gadit.iva_default`); editable |
| Notas                         | Hasta 255                                                    |

Acciones por fila:

1. **Registrar pago:** abre el gasto de RF-30 con la cuenta por pagar, el proveedor y el proyecto puestos, el importe igual al saldo, el concepto «Pago a {proveedor}: {concepto}» y la última categoría usada con ese proveedor.
2. **Ver datos bancarios:** muestra banco, titular y CLABE (RF-24) a quien tiene permiso, para pagar sin salir de la pantalla.
3. **Cancelar:** falla con pagos activos (RN-16). **Borrar:** solo `admin`, con RN-20.

El detalle muestra los totales, los pagos (gastos ligados), los adjuntos y el historial.

### Préstamos (RF-51)

**Listado `/prestamos`** (de `v_prestamos`): acreedor, fecha, monto, tasa, periodo de la tasa, capital pagado, saldo de capital, intereses pagados, vencimiento y situación: `liquidado` (saldo de 0.01 o menos), `vencido` (hay saldo y el vencimiento pasó) o `vigente`.

| Campo del formulario (página) | Regla                                                        |
| ----------------------------- | ------------------------------------------------------------ |
| Acreedor\*                    | Persona activa                                               |
| Cuenta\*                      | Donde se recibió el dinero                                   |
| Fecha\*                       | RN-25                                                        |
| Monto\*                       | Mayor que 0; no puede quedar por debajo del capital ya pagado |
| Tasa %                        | De 0 a 100; 0 por defecto                                    |
| Periodo de la tasa            | `mensual` por defecto (PEN-04)                               |
| Vencimiento                   | No anterior a la fecha                                       |
| Notas                         | Hasta 255                                                    |

El detalle `/prestamos/{id}` tiene dos tablas y dos acciones:

- **Pagos de capital:** «Registrar pago» abre un modal con fecha\*, cuenta de donde sale\*, monto de capital\* (mayor que 0 y no mayor que el saldo, RN-17) y notas.
- **Intereses y comisiones:** gastos con `prestamo_id`; «Registrar interés o comisión» abre RF-30 con el préstamo puesto y la categoría `INTERESES`.

### Traspasos (RF-52)

**Listado `/traspasos`:** fecha, origen, destino, monto, comisión % y comisión en pesos (del gasto ligado) y concepto. Filtros: rango de fecha, cuenta (como origen o destino) y texto.

| Campo del formulario (modal)  | Regla                                        |
| ----------------------------- | -------------------------------------------- |
| Fecha\*                       | RN-25                                        |
| Origen\* y destino\*          | Cuentas activas y distintas                  |
| Monto\*                       | Mayor que 0                                  |
| Comisión %                    | Opcional, de 0 a 100                         |
| Registrar comisión como gasto | Casilla marcada si hay comisión; sigue RN-15 |
| Concepto                      | Hasta 200                                    |
| Notas                         | Hasta 255                                    |

Editar o borrar un traspaso se propaga a su gasto de comisión (RN-15 y RN-20).

### Estados de cuenta (RF-53)

La pantalla `/cuentas/{id}/estado` (solo `admin` y `consulta`) trae arriba el nombre, el tipo, el saldo actual y el saldo inicial con su fecha. Una fila inicial «Saldo anterior» parte del saldo inicial; después van los movimientos de `v_movimientos_cuenta` posteriores a la fecha del saldo inicial.

- **Columnas:** fecha, tipo (etiquetas de `TipoMovimiento`), concepto, entrada, salida y saldo acumulado.
- **Orden:** fecha ascendente, luego entradas antes que salidas, luego `ref_id`. El saldo acumulado se calcula con una función de ventana sobre todo el periodo posterior al corte y después se recorta al rango, para que el acumulado sea el real.
- **Filtros:** rango de fechas (el mes en curso por defecto) y tipo de movimiento.
- Cada fila abre el registro que la originó. Los saldos negativos se pintan en rojo (RN-14). Se exporta a CSV, XLSX y PDF.
- Los movimientos anteriores al corte no se muestran.

### Reparto de utilidad (RF-54)

La pantalla `/reparto` (`admin` edita, `consulta` lee) tiene tres partes y siempre avisa «Utilidad de caja: no sustituye a la contabilidad» (DEC-15):

1. **Resumen:** el esquema vigente, la utilidad antes del reparto, lo repartido y la utilidad de caja (de `v_resumen_general`).
2. **Por socio** (de `v_reparto_utilidad`): socio, porcentaje, le corresponde, ya entregado y pendiente; un pendiente negativo dice «Entregado de más».
3. **Entregas:** lista de gastos del grupo `reparto_utilidad`. «Registrar entrega» abre un modal con socio\* (del esquema vigente), fecha\*, importe\*, cuenta\* de donde sale y observaciones; crea un gasto de categoría `UTILIDAD` con el socio como beneficiario (RN-19).

**Esquemas** (`/reparto/esquemas`): lista y formulario con nombre\*, notas y filas de socio y porcentaje, con la suma mostrada en vivo (debe ser 100.00). «Activar como vigente» sigue RN-19. Los esquemas no se borran: se desactivan. Cambiar los porcentajes del vigente pide confirmar «Cambiará lo que le corresponde a cada socio» y queda en la bitácora.

## 14. Especificación funcional: tablero, reportes y administración

El tablero reemplaza a CONCENTRADO: muestra lo mismo, pero cada cifra sale de una vista y cada número lleva a la lista que lo explica.

### Tablero (RF-60)

Ruta `/tablero`, pantalla de inicio. Hay dos versiones según el rol: la **completa** (`admin` y `consulta`) y la **operativa** (`captura`). Ocultar utilidad y saldos a `captura` es de conveniencia, no de secreto: quien ve cobros y gastos puede sumarlos. Si el dueño necesita ocultar más a ese rol, es un cambio de alcance (PEN-05).

**Tarjetas.** Cada una abre el listado que la explica.

| Tarjeta                               | Cálculo                                                      | Versión  |
| ------------------------------------- | ------------------------------------------------------------ | -------- |
| Contratado (sin IVA)                  | `v_resumen_general.contratado_sin_iva`                       | Completa |
| Facturado (sin IVA)                   | `facturado_sin_iva`                                          | Ambas    |
| Cobrado (con IVA)                     | `cobrado_sin_iva + iva_cobrado`                              | Ambas    |
| Por cobrar (con IVA)                  | Suma de `v_facturas.saldo` de facturas no canceladas         | Ambas    |
| De lo cual, vencido                   | Misma suma con saldo mayor que 0.01 y vencimiento anterior a hoy | Ambas    |
| Gastos del periodo                    | Suma de gastos en el periodo elegido; en la operativa solo «esta semana» y «este mes» | Ambas    |
| Gastos acumulados                     | `gastos_total`                                               | Completa |
| Por pagar a proveedores               | `cxp_saldo`                                                  | Completa |
| Utilidad de caja                      | `utilidad_caja`                                              | Completa |
| Utilidad antes de reparto y repartido | `utilidad_antes_reparto` y `reparto_pagado`                  | Completa |

**Gráficas** (Chart.js, paleta de la sección 16; cada una con ayuda emergente y los datos accesibles como tabla):

1. **Gasto semanal:** barras de las últimas 12 semanas con gasto, de `v_gastos_semana`.
2. **Gasto por grupo:** barras horizontales ordenadas de mayor a menor, con la suma por `grupo` del periodo elegido.
3. **Facturado y cobrado por mes:** barras agrupadas por mes, con el subtotal de facturas no canceladas por fecha de emisión y el importe de cobros por fecha de cobro.

El selector **Periodo** (todo el tiempo por defecto, este mes, mes pasado, últimos 3 meses, este año o rango libre) afecta a la tarjeta de gastos del periodo y a la gráfica 2; la 1 y la 3 tienen su rango fijo.

**Tablas.**

- **Contratos** (de `v_contratos_resumen`, mayor por cobrar primero): contratado, facturado, cobrado, por cobrar y porcentaje facturado.
- **Facturas con saldo:** las 10 de vencimiento más próximo o más pasado, con enlace al listado completo.
- **Dónde está el dinero** (completa): `v_saldo_cuentas` con total.
- **Reparto** (completa): `v_reparto_utilidad` resumido.

**Alertas.** Contadores con enlace; en gris cuando valen cero:

| Alerta                                            | Condición                                       | Versión  |
| ------------------------------------------------- | ----------------------------------------------- | -------- |
| Facturas vencidas                                 | Con saldo y vencimiento pasado (número y monto) | Ambas    |
| Complementos pendientes                           | `v_complementos_pendientes` (RF-44)             | Ambas    |
| Cuentas por pagar vencidas o que vencen en 7 días | Con saldo                                       | Ambas    |
| Gastos sin proyecto                               | Cola de RF-33                                   | Ambas    |
| Gastos sin comprobante                            | Cola de RF-33                                   | Ambas    |
| Cuentas con saldo negativo                        | RN-14                                           | Completa |
| Préstamos con saldo                               | `v_prestamos`                                   | Completa |

### Reportes (RF-61)

La pantalla `/reportes` lista los reportes, cada uno con sus filtros y exportación. «Op.» son los que ve también `captura`.

| Reporte                               | Fuente                                                       | Filtros                           | Op.  |
| ------------------------------------- | ------------------------------------------------------------ | --------------------------------- | ---- |
| Gasto por semana                      | `v_gastos_semana`                                            | Rango de semanas                  | Sí   |
| Gasto por categoría y grupo           | `v_gastos_categoria`; con fechas, la misma agrupación sobre `gastos` | Rango de fechas, proyecto, cuenta | Sí   |
| Rentabilidad por proyecto             | `v_rentabilidad_proyecto`, con una fila final «gasto general / sin proyecto» | Estatus del proyecto              | No   |
| Cuentas por cobrar con antigüedad     | `v_facturas` con saldo                                       | Cliente, proyecto                 | Sí   |
| Complementos pendientes               | `v_complementos_pendientes`                                  | Cliente, rango de cobro           | Sí   |
| Cuentas por pagar con antigüedad      | `v_cuentas_por_pagar` con saldo                              | Proveedor, proyecto               | Sí   |
| Gastos sin proyecto o sin comprobante | Colas de RF-33                                               | Rango de fechas                   | Sí   |
| Estado de cuenta                      | RF-53                                                        | Cuenta, rango                     | No   |
| Préstamos                             | `v_prestamos`                                                | Acreedor, situación               | No   |
| Reparto de utilidad                   | `v_reparto_utilidad` y entregas                              | Socio                             | No   |

**Antigüedad:** días vencidos = hoy menos vencimiento, en cubetas Corriente (aún no vence), 1 a 30, 31 a 60, 61 a 90 y más de 90, con subtotal por cubeta.

### Exportaciones (RF-62)

| Formato | Especificación                                               |
| ------- | ------------------------------------------------------------ |
| XLSX    | Encabezado en negritas y fijo, importes con formato `#,##0.00`, fechas `dd/mm/aaaa`, anchos ajustados |
| CSV     | UTF-8 con BOM (se abre bien en Excel), coma como separador y punto decimal |
| PDF     | Tamaño carta; solo tablero, estado de cuenta y rentabilidad; encabezado «GADIT» y pie «Generado el dd/mm/aaaa hh:mm por {usuario}» |

### Administración (RF-70 a RF-72)

| ID    | Pantalla             | Comportamiento (solo `admin`)                                |
| ----- | -------------------- | ------------------------------------------------------------ |
| RF-70 | Bitácora `/bitacora` | Fecha y hora, usuario, tabla (con su etiqueta), registro (enlace), acción y resumen «campo: antes → después». Filtros: rango, usuario, tabla, acción e id de registro. No se edita ni se borra (SEG-12) |
| RF-71 | Papelera `/papelera` | Registros con `deleted_at` de gastos, cobros, traspasos, préstamos, pagos y cuentas por pagar: tipo, descripción, importe, fecha de borrado, quién y motivo (de la bitácora). «Restaurar» aplica las comprobaciones de RN-20 y dice por qué no puede |
| RF-72 | Importación          | No hay pantalla: es el comando de consola de la sección 15; su resumen queda como una fila de bitácora con tabla `importacion` |

## 15. Especificación funcional: importador del Excel y conciliación

El importador carga una sola vez el historial de Gastos\_GOG.xlsx (DEC-09) y no adivina: lo que no se puede derivar con certeza queda vacío y aparece en el informe. No lee PROVEEDORES, PRESTAMOS, ALFREDO, CAJA, las tres hojas de INGRESOS ni Hoja1, no crea usuarios y no mueve saldos (DEC-19).

### Comando (RF-72)

| Aspecto          | Especificación                                               |
| ---------------- | ------------------------------------------------------------ |
| Firma            | `php artisan gadit:importar-excel {archivo} {--simular} {--solo=}` |
| `archivo`        | Ruta a una copia del .xlsx. El comando solo lee: nunca escribe en el archivo. Se lee con maatwebsite/excel y valores calculados (concern `WithCalculatedFormulas`) |
| `--simular`      | Ejecuta todo, imprime el informe y deshace la transacción. Se corre siempre antes de la importación real |
| `--solo=`        | `concentrado` o `gastos`. Sin la opción hace ambos, en ese orden |
| Quién lo corre   | Solo consola, con `usuario_id` nulo en la bitácora (RN-21). No hay pantalla |
| Salida           | Informe en pantalla y archivo JSON en `storage/app/private/importaciones/AAAAMMDD-HHMMSS.json` (los simulacros llevan el sufijo `-simulacro`) |
| Código de salida | 0 sin errores. 1 con errores de datos: no se guardó nada. 2 si falla una precondición (IMP-01) |

**Orden de puesta en marcha:**

1. `php artisan migrate`
2. `php artisan db:seed --class=CatalogosBaseSeeder`
3. `php artisan gadit:crear-admin`
4. `php artisan gadit:importar-excel {archivo} --simular` y revisar el informe con el dueño
5. `php artisan gadit:importar-excel {archivo}`
6. Con el admin, capturar el saldo contado y su fecha en cada cuenta (RF-26, DEC-19) y hacer lo de «Después de importar»
7. Correr las pruebas de conciliación de este capítulo (se numeran en la sección 18)

### Reglas del importador

| ID     | Regla                                                        |
| ------ | ------------------------------------------------------------ |
| IMP-01 | **Precondiciones.** Antes de leer el archivo deben existir, con el nombre exacto del seeder, las 4 cuentas de la tabla «Responsable → cuenta», las 32 categorías (Apéndice A) y las 4 personas socio (Alejandra, Javier, Álvaro y GPE). Si falta algo, no se hace nada y se listan todas las faltantes (salida 2) |
| IMP-02 | **Lectura.** Las hojas se buscan por nombre (`GADIT`, `GADIT 2`, `CONCENTRADO`); si falta una de las que se van a importar, es error. Se leen los valores calculados: 9 importes de GADIT son sumas escritas en la celda (D46 `=1699.5+271.92`, D228, D229, D283, D287, D549, D758, D799 y D882) y se toman con su resultado. Un error de fórmula (`#REF!`, `#VALUE!`) o un texto donde va un número es error de fila. El informe lleva el SHA-256 del archivo |
| IMP-03 | **Todo o nada.** Gastos y CONCENTRADO corren en una sola `DB::transaction`. Los errores de fila se acumulan (no se detiene en el primero) y, si hay alguno, se deshace todo y se imprimen con hoja, fila de Excel, columna y motivo |
| IMP-04 | **Importes y fechas.** Un importe pasa por `sprintf('%.6F', $valor)`, luego a BigDecimal y a 2 decimales con redondeo medio hacia arriba (DEC-03, RN-01): 200.16000000000003 queda 200.16. Nunca se suman floats. Las fechas deben ser celdas de fecha de Excel y se guardan como DATE sin hora; un texto en la columna FECHA es error de fila |
| IMP-05 | **Ubicación de los gastos.** En GADIT y GADIT 2 los encabezados están en la fila 2 y las partidas desde la 3. Columnas: A `No`, B `FECHA`, C `SEM`, D `IMPORTE C/IVA`, E `CONCEPTO`, F `CATEGORIA`, G `RESPONSABLE`, H `OBSERVACIONES`. Si los encabezados no coinciden, es error. Lo que esté a la derecha de H no se lee. `SEM` se ignora (mezcla texto y números; la semana la calcula `semana_iso`) |
| IMP-06 | **Filas que no son partidas.** Una fila con «TOTAL» en la columna B (GADIT fila 976, GADIT 2 fila 78) no es una partida: se ignora y su valor alimenta IMP-19. Una fila sin ningún dato, o cuyo único dato es el número de la columna A (GADIT filas 910 a 913 y GADIT 2 filas 62 a 65), se ignora sin avisar |
| IMP-07 | **Partidas sin importe.** Si el importe está vacío o es 0 y la fila tiene fecha, concepto, categoría, responsable u observaciones, no se importa y se reporta (hoja, fila, concepto). En el archivo entregado son 2: GADIT filas 906 y 909, «UTILIDAD ALEJANDDRA ANT» (PEN-14) |
| IMP-08 | **Datos mínimos.** Una partida con importe necesita fecha válida y responsable reconocido; si no, es error de fila. Un concepto vacío se guarda como «(sin concepto)». Una fecha futura o anterior al 01/01/2020 (RN-25) también es error de fila. Ninguna fecha ni cuenta se inventa |
| IMP-09 | **Categoría.** La etiqueta de la columna F se normaliza (espacios recortados y colapsados, mayúsculas, acentos intactos) y se busca en `config/importacion.php`, clave `categorias` (Apéndice A). Si no está, la partida va a la categoría VARIOS y se agrega a `observaciones` «\[cat. original: ETIQUETA\]» (con «(vacía)» si no traía etiqueta). El informe lista cada etiqueta sin mapear con su número de partidas |
| IMP-10 | **Cuenta.** La columna G, normalizada igual, se traduce con la tabla «Responsable → cuenta». Un valor que no esté es error de fila. Si el valor no es el propio de la cuenta (ALEJANDRA, BAJIO, ALFREDO o CAJA GPE), se agrega a observaciones « \[resp. original: VALOR\]» después de la nota de IMP-09, para no perder de dónde salió el dinero: hoy son 121 partidas (GPE 59, PRE GPE 53, SP GPE 8 y SP ALEJANDRA 1) |
| IMP-11 | **Beneficiario.** Solo las partidas de categoría UTILIDAD cuyo concepto empieza con «UTILIDAD » y el nombre de un socio (tabla «Entregas de utilidad») reciben `beneficiario_id`, porque `v_reparto_utilidad` cuenta lo entregado por beneficiario (RN-19). Las demás quedan sin beneficiario, incluidas las 29 de comisiones: es una excepción a RN-10, que rige al capturar o editar en pantalla, y se resuelve después (PEN-15) |
| IMP-12 | **Campos fijos.** `proyecto_id`, `proveedor_id`, `cuenta_por_pagar_id`, `prestamo_id`, `traspaso_id`, `iva_incluido`, `cfdi_uuid` y periodo van nulos. `estatus_revision` es `sin_revisar`, `origen` el nombre de la hoja, `numero_origen` la columna A y `creado_por` nulo. Un concepto de más de 200 caracteres o unas observaciones de más de 255 son error de fila: no se recortan |
| IMP-13 | **Repetible.** Antes de insertar se busca `(origen, numero_origen)` en `uq_gastos_origen`, incluidos los borrados lógicos: si existe se cuenta como «ya existente» y no se toca (ni se actualiza ni se restaura). Una partida sin número (GADIT fila 469) se compara por `(origen, fecha, concepto, importe, cuenta)` entre las de `numero_origen` nulo. Un número repetido dentro de la misma hoja es error |
| IMP-14 | **CONCENTRADO solo en base vacía.** Esa parte corre únicamente si `contratos`, `facturas` y `cobros` no tienen filas (activas ni borradas); si tienen, se omite con una advertencia y el comando sigue con los gastos. Clientes y proyectos se crean si no existen por nombre |
| IMP-15 | **Anclas de CONCENTRADO.** Los bloques se localizan por su encabezado en la columna C, no por número de fila: contratos (`No PEDIDO`, hoy fila 7, renglones 8 a 15), facturas (`CLIENTE`, fila 22, renglones 23 a 34) y cobros (`FECHA` con `FAC` en la D, fila 43, renglones 44 a 54). Cada bloque termina en la primera fila cuya columna clave está vacía: C en contratos, D en facturas y C en cobros |
| IMP-16 | **Contratos.** Cada renglón se traduce con la tabla «Contratos de CONCENTRADO» (cliente, proyecto, tipo, padre, pedido y concepto), porque el Excel no distingue proyectos. Importe = columna D; `iva_pct` 16.00; estatus `vigente`; fecha nula. Un pedido numérico se guarda como texto sin decimales y `00494` conserva sus ceros. Un renglón que no esté en la tabla es error |
| IMP-17 | **Facturas.** Cada renglón de FACTURADO crea una factura: contrato = el renglón de contratos cuya clave (columna C) es igual al texto de la columna G; `folio` D, `fecha_emision` E, `fecha_vencimiento` F, `importe` H, `concepto` M; `retencion_pct` = I ÷ H × 100 (0 si I es 0); `iva_pct` 16.00; estatus `emitida`; `uuid`, `metodo_pago` y `forma_pago` nulos (RN-03, RN-04). `tipo` y `numero_estimacion` salen del concepto, en este orden: contiene FINIQUITO, `finiquito`; contiene ANTICIPO, `anticipo`; contiene ESTIMACION seguida de un número romano del I al X, `estimacion` con ese número; en otro caso, `otro`. Es error si G no resuelve a un contrato, si el cliente de C no es el del contrato o si el IVA de K difiere en más de 0.01 de `ROUND(subtotal × 16 / 100, 2)` |
| IMP-18 | **Cobros.** Cada renglón de COBRADO crea un cobro de la factura con el folio de la columna D: `fecha` C, `importe` E, `iva` = F redondeado a 2 decimales (el Excel guarda 4), cuenta Banco del Bajío (GADIT) por el supuesto PEN-03; `forma_pago`, `referencia` y `complemento_uuid` nulos. Es error si el folio no existe o si los cobros de una factura superan su subtotal (RN-05) |
| IMP-19 | **Autocomprobación.** Antes de confirmar, la suma importada de cada hoja de gastos debe ser igual a su celda TOTAL (GADIT D976, GADIT 2 D78); en CONCENTRADO, contratos igual a C6, subtotales de facturas a C21 y cobros a C42, y el IVA cobrado debe diferir de F59 en no más de 0.01. Si algo no cuadra es error y se deshace todo |
| IMP-20 | **Informe y bitácora.** Al terminar (también con `--simular`) se imprime el informe. Solo en la importación real se guarda una fila en `bitacora` con tabla `importacion`, `registro_id` 0, acción `crear`, usuario nulo y el resumen en `despues` (RN-21). No se escribe una fila por partida |

### Tablas de traducción

Estas tablas viven en `config/importacion.php` (claves `responsables`, `socios_utilidad`, `contratos` y `categorias`) y no en el código del comando.

**Responsable → cuenta.**

| Valor en la columna G                                | Cuenta del sistema      | Partidas |
| ---------------------------------------------------- | ----------------------- | -------- |
| ALEJANDRA (GADIT 611, GADIT 2 59) y SP ALEJANDRA (1) | Caja Alejandra          | 671      |
| BAJIO                                                | Banco del Bajío (GADIT) | 110      |
| ALFREDO                                              | Alfredo (intermediario) | 31       |
| GPE (59), PRE GPE (53), CAJA GPE (32) y SP GPE (8)   | Caja GPE                | 152      |

Las variantes no son cuentas distintas, sino el origen del dinero. **SP** y **GPE**: lo pagó el socio de su bolsa y se le reembolsó después (SP GPE y GPE suman $256,427.41, el renglón de GPE en PRESTAMOS, pagado el 05/12/2024). **PRE GPE**: se pagó con dinero prestado que administró GPE ($900,000, hoja GPE INGRESOS). **CAJA GPE**: la caja de efectivo de GPE (hoja CAJA). Por eso IMP-10 deja anotado el valor original.

**Entregas de utilidad.** El concepto original trae un error de dedo que se respeta.

| El concepto empieza con                                    | Beneficiario |
| ---------------------------------------------------------- | ------------ |
| UTILIDAD ALVARO                                            | Álvaro       |
| UTILIDAD ALEJANDDRA (también se acepta UTILIDAD ALEJANDRA) | Alejandra    |
| UTILIDAD JAVIER                                            | Javier       |
| UTILIDAD GPE                                               | GPE          |

**Contratos de CONCENTRADO.** La clave es el texto de la columna C, recortado.

| Fila | Clave (columna C)             | Cliente          | Proyecto                       | Tipo                      | Pedido           | Concepto en el sistema                | Importe sin IVA |
| ---- | ----------------------------- | ---------------- | ------------------------------ | ------------------------- | ---------------- | ------------------------------------- | --------------- |
| 8    | 4506816475                    | IBERDROLA BAJIO  | Pavimentación camino de acceso | contrato                  | 4506816475       | PAVIMENTACIÓN CAMINO DE ACCESO        | 8,099,620.57    |
| 9    | 4506813047                    | IBERDROLA CENTRO | Caseta                         | contrato                  | 4506813047       | CASETA                                | 448,439.32      |
| 10   | 4506893670                    | IBERDROLA BAJIO  | Renta de maquinaria            | contrato                  | 4506893670       | RENTA MAQUINARIA                      | 116,680.00      |
| 11   | 00494                         | SOLARIG          | Solarig                        | contrato                  | 00494            | SOLARIG                               | 700,044.00      |
| 12   | DC-PR-479\_IN-DI-CA-PG-02-R01 | EOSOL ENERGY     | Eosol Energy                   | contrato                  | igual a la clave | EOSOL ENERGY                          | 154,700.00      |
| 13   | CAMINOS SLP                   | PEREN            | Caminos SLP                    | contrato                  | vacío            | CAMINOS SLP (PEREN)                   | 2,953,284.98    |
| 14   | 4506816475 ADENDA             | IBERDROLA BAJIO  | Pavimentación camino de acceso | adenda, hija de la fila 8 | 4506816475       | ADENDA PAVIMENTACIÓN CAMINO DE ACCESO | 5,156,651.00    |
| 15   | VIAJES                        | SOLARIG          | Solarig                        | contrato                  | vacío            | VIAJES (SOLARIG)                      | 300,000.00      |

Las categorías que mapea la clave `categorias` (137 etiquetas del Excel hacia las 32 categorías) están en el Apéndice A. El Excel trae 159 etiquetas distintas con importe: 153 en GADIT y 6 más solo en GADIT 2. Las 22 sin regla (32 partidas) van a VARIOS por IMP-09; la etiqueta VARIOS (5 partidas) se mapea a sí misma y no lleva nota.

### Informe de importación

El informe es el mismo en pantalla y en el JSON, con estas partes en este orden:

1. Archivo (nombre, SHA-256), fecha y hora, y modo (real o simulacro).
2. Por hoja de gastos: partidas leídas con importe, importadas, ya existentes, sin importe (con hoja, fila y concepto), con error; suma importada, celda TOTAL y fechas mínima y máxima.
3. Gastos importados por cuenta y por grupo (partidas e importe).
4. Etiquetas sin mapear con su número de partidas, partidas sin número (hoja y fila), grupos de partidas idénticas (misma hoja, fecha, importe, concepto y responsable, con sus números), partidas con nota de responsable original, beneficiarios asignados, y comisiones sin beneficiario (partidas e importe).
5. CONCENTRADO: clientes, proyectos, contratos, facturas y cobros creados, con sumas e IVA cobrado.
6. Autocomprobaciones de IMP-19, cada una con esperado, obtenido y ✓ o ✗.
7. Errores, si los hay: hoja, fila, columna y motivo.

### Cifras de control

Las obtiene quien concilia con el archivo entregado; son las que fijan los criterios de éxito 1 y 2 (sección 2). Salieron de importar el archivo en una base de pruebas MariaDB 10.11 con gadit\_schema.sql v1.1; la primera corrida en MySQL 8.4 debe reproducirlas exactamente.

| Cifra                                      | Valor                                                        |
| ------------------------------------------ | ------------------------------------------------------------ |
| Gastos importados                          | 964 partidas, $16,088,222.19                                 |
| GADIT                                      | 905 partidas, $15,901,989.14, del 31/01/2024 al 04/04/2025 (igual a la celda TOTAL) |
| GADIT 2                                    | 59 partidas, $186,233.05, del 13/03/2025 al 30/05/2025 (igual a la celda TOTAL) |
| Partidas sin importe reportadas            | 2 (GADIT filas 906 y 909)                                    |
| Partida sin número                         | 1 (GADIT fila 469)                                           |
| Partidas en VARIOS                         | 37, $351,186.73 (5 con etiqueta VARIOS y 32 con nota de etiqueta original) |
| Contratos                                  | 8, $17,929,419.87 sin IVA                                    |
| Facturas                                   | 12, $17,089,286.07 sin IVA; la A5020 no tiene cobros         |
| Cobros                                     | 11, $16,789,286.07 sin IVA; IVA $2,686,285.77 (el Excel guarda $2,686,285.7712) |
| Utilidad con la fórmula del Excel          | $3,573,582.70 = cobrado sin IVA + IVA cobrado − gastos de GADIT |
| `utilidad_caja` de `v_resumen_general`     | $3,387,349.65 (con GADIT 2; sin cuentas por pagar ni préstamos) |
| `utilidad_antes_reparto`                   | $5,187,349.65                                                |
| `reparto_pagado`                           | $1,800,000.00 (4 entregas de $450,000)                       |
| Partidas con nota de responsable original  | 121 (GPE 59, PRE GPE 53, SP GPE 8 y SP ALEJANDRA 1)          |
| Grupos de partidas idénticas en el informe | 17, con 22 partidas de más por $27,315.69 (Apéndice F)       |

| Desglose                        | Partidas | Importe      |
| ------------------------------- | -------- | ------------ |
| Cuenta: Banco del Bajío (GADIT) | 110      | 4,464,548.31 |
| Cuenta: Caja Alejandra          | 671      | 4,738,712.23 |
| Cuenta: Alfredo (intermediario) | 31       | 5,458,913.93 |
| Cuenta: Caja GPE                | 152      | 1,426,047.72 |
| Grupo: obra                     | 309      | 7,192,149.01 |
| Grupo: comisiones               | 29       | 2,858,795.73 |
| Grupo: reparto\_utilidad        | 4        | 1,800,000.00 |
| Grupo: nomina                   | 58       | 1,075,337.36 |
| Grupo: logistica                | 357      | 960,391.54   |
| Grupo: impuestos                | 46       | 923,642.17   |
| Grupo: administracion           | 93       | 553,719.65   |
| Grupo: otros                    | 59       | 503,686.73   |
| Grupo: financiamiento           | 9        | 220,500.00   |

`v_reparto_utilidad` con el esquema 30 / 25 / 22.5 / 22.5:

| Socio     | %     | Le corresponde | Ya entregado | Pendiente    |
| --------- | ----- | -------------- | ------------ | ------------ |
| Alejandra | 30.00 | 1,556,204.90   | 450,000.00   | 1,106,204.90 |
| Javier    | 25.00 | 1,296,837.41   | 450,000.00   | 846,837.41   |
| Álvaro    | 22.50 | 1,167,153.67   | 450,000.00   | 717,153.67   |
| GPE       | 22.50 | 1,167,153.67   | 450,000.00   | 717,153.67   |

### Después de importar

1. Capturar los saldos iniciales y su fecha en cada cuenta (RF-26). Sin ellos los saldos de cuentas no cuadran (RN-14).
2. Confirmar con el dueño que los 11 cobros entraron al banco (PEN-03); los que no, se corrigen en RF-43.
3. Asignar proyecto a los gastos de obra con la acción masiva de RF-32 y la cola de RF-33 (PEN-08).
4. Revisar las 32 partidas con «\[cat. original: …\]» (filtro de texto de RF-31) y pasarlas a su categoría con RF-32.
5. Completar el beneficiario de las 29 comisiones históricas (PEN-15). El formulario de edición lo exige por RN-10.
6. Capturar las entregas a socios de PEN-14 si el dueño decide que cuentan.
7. Revisar con el dueño el Apéndice F: borrar las capturas dobles de los 17 grupos de partidas idénticas, corregir si quiere las 9 fechas corridas y decidir PEN-18 (gastos que solo están en CAJA y ALFREDO) y PEN-20 (deudas vigentes en la fecha de corte).

### Pruebas del importador

Se numeran en la sección 18; estas son las que no pueden faltar:

- **Conciliación con el archivo real.** Si la variable de entorno `GADIT_EXCEL_REAL` apunta al .xlsx, importa en una base limpia y compara cada cifra de control; si no está definida, se omite. El archivo real no se guarda en el repositorio por su contenido financiero.
- **Archivo de pruebas generado por código** con las rarezas del original: una fila sin número, dos sin importe, una fila TOTAL, una suma escrita en celda, una etiqueta desconocida, una fecha en texto, una fila con solo el número en A (no debe reportarse), un responsable con prefijo como PRE GPE (debe llevar su nota de IMP-10), dos partidas idénticas (deben salir en el informe) y un responsable desconocido (este último debe hacer fallar la corrida sin guardar nada).
- **Repetible.** Dos corridas seguidas: la segunda reporta todo como «ya existente» y no cambia ninguna suma, incluida la partida sin número; también con una partida borrada lógicamente entre corridas.
- **Todo o nada.** Un error de fila deja las tablas intactas; `--simular` no deja nada ni escribe en bitácora.
- **Precondiciones.** Sin el seeder, la salida es 2 y lista lo que falta.
- **Equivalencia con gadit\_semillas.sql.** Tras importar CONCENTRADO, contratos, facturas y cobros tienen las mismas claves naturales (pedido y concepto, folio, folio y fecha) e importes que los de ese archivo.

## 16. Interfaz y experiencia de uso

Esta sección fija cómo se ve y cómo se comporta todo lo que las secciones 10 a 15 describen por pantalla. Los RF de la familia 80 valen para todas las pantallas; los patrones RF-01 a RF-10 siguen mandando en listados y formularios.

**Principios.**

1. La captura va primero: gastos y cobros se registran en pocos toques, también desde el celular.
2. Un solo patrón por cosa: nada se rediseña por módulo.
3. Ningún dato depende solo del color: cada color lleva texto.
4. Lo que el rol no puede hacer no aparece; la seguridad real es del servidor (sección 6).
5. Antes de borrar, cancelar o descartar se pide confirmar.

### Navegación (RF-80)

Menú lateral con estos grupos y opciones. Un ✓ es que el rol ve la opción; el contenido de cada pantalla sigue la matriz de la sección 6.

| Grupo          | Opción                                                  | Ruta                                                         | admin | captura        | consulta |
| -------------- | ------------------------------------------------------- | ------------------------------------------------------------ | ----- | -------------- | -------- |
| Inicio         | Tablero                                                 | `/tablero`                                                   | ✓     | ✓ (operativo)  | ✓        |
| Ingresos       | Contratos                                               | `/contratos`                                                 | ✓     | ✓              | ✓        |
| Ingresos       | Facturas                                                | `/facturas`                                                  | ✓     | ✓              | ✓        |
| Ingresos       | Cobros                                                  | `/cobros`                                                    | ✓     | ✓              | ✓        |
| Ingresos       | Complementos pendientes                                 | `/complementos-pendientes`                                   | ✓     | ✓              | ✓        |
| Egresos        | Gastos                                                  | `/gastos`                                                    | ✓     | ✓              | ✓        |
| Egresos        | Cuentas por pagar                                       | `/cuentas-por-pagar`                                         | ✓     | ✓              | ✓        |
| Egresos        | Préstamos                                               | `/prestamos`                                                 | ✓     | —              | ✓        |
| Dinero         | Cuentas y estados de cuenta                             | `/cuentas`                                                   | ✓     | —              | ✓        |
| Dinero         | Traspasos                                               | `/traspasos`                                                 | ✓     | —              | ✓        |
| Dinero         | Reparto de utilidad                                     | `/reparto`                                                   | ✓     | —              | ✓        |
| Catálogos      | Clientes, Proyectos, Proveedores, Personas y Categorías | `/clientes`, `/proyectos`, `/proveedores`, `/personas`, `/categorias` | ✓     | ✓              | ✓        |
| Reportes       | Reportes                                                | `/reportes`                                                  | ✓     | ✓ (operativos) | ✓        |
| Administración | Usuarios                                                | `/usuarios`                                                  | ✓     | —              | —        |
| Administración | Bitácora                                                | `/bitacora`                                                  | ✓     | —              | —        |
| Administración | Papelera                                                | `/papelera`                                                  | ✓     | —              | —        |

Comportamientos del menú:

- La opción de la pantalla actual va resaltada y con `aria-current="page"`.
- Cuatro opciones llevan un contador rojo cuando es mayor que cero: Facturas (vencidas), Complementos pendientes, Cuentas por pagar (vencidas) y Gastos (sin proyecto). Son los números de las alertas del tablero (RF-60) y se calculan al cargar la página, sin sondeo.
- El menú de usuario (arriba a la derecha) muestra nombre y rol (etiqueta de `RolUsuario`), «Mi perfil» y «Cerrar sesión».
- Cada pantalla tiene un título y, a la derecha, una acción principal «Nuevo» o «Nueva» con el sustantivo («Nuevo gasto») solo si el rol puede crear. Los detalles muestran migas de pan de dos niveles («Gastos › #123»).

### Estructura y adaptación (RF-81)

Puntos de corte de Tailwind: móvil menos de 768 px, tableta de 768 a 1023 px y escritorio desde 1024 px.

| Elemento              | Escritorio                                 | Tableta                             | Móvil                                                        |
| --------------------- | ------------------------------------------ | ----------------------------------- | ------------------------------------------------------------ |
| Menú                  | Lateral fijo de 256 px                     | Cajón con botón ☰                   | Cajón con botón ☰ al 80 % del ancho; se cierra al elegir una opción |
| Cabecera              | Título, acción principal y menú de usuario | Igual                               | Título y ☰; la acción principal pasa a un botón redondo «+» abajo a la derecha |
| Listados              | Tabla con encabezado fijo                  | Tabla con desplazamiento horizontal | Tarjetas (tabla siguiente)                                   |
| Filtros               | Barra de búsqueda y panel lateral derecho  | Panel                               | Hoja a pantalla completa con «Aplicar»                       |
| Formularios de página | 2 columnas                                 | 2 columnas                          | 1 columna                                                    |
| Ventanas modales      | 480 o 640 px centradas                     | Igual                               | Pantalla completa                                            |
| Tablero               | Tarjetas en 4 columnas, gráficas en 2      | 2 columnas                          | 1 columna                                                    |
| Botones de guardar    | Al final del formulario                    | Igual                               | Barra fija abajo                                             |

Reglas generales: el contenido mide como máximo 1,280 px y va centrado; todo elemento táctil mide al menos 44 × 44 px; los campos de texto usan 16 px o más para que iOS no acerque la pantalla; no hay modo oscuro en v1.

| Tarjeta móvil de…      | Muestra                                                      |
| ---------------------- | ------------------------------------------------------------ |
| Gastos                 | Concepto (título), importe a la derecha en negritas, fecha, cuenta, categoría e insignia de revisión |
| Facturas               | Folio y cliente (título), total, saldo, vencimiento e insignia de situación |
| Cobros                 | Folio y cliente (título), importe, fecha, cuenta y complemento |
| Cuentas por pagar      | Proveedor y concepto (título), saldo, vencimiento e insignia de situación |
| Cualquier otro listado | Sus primeras 4 columnas, la primera como título; lo demás, en el detalle |

### Formatos (RF-82)

| Dato                               | Formato                                                      | Ejemplo                 |
| ---------------------------------- | ------------------------------------------------------------ | ----------------------- |
| Importe en columnas                | Coma de miles, punto decimal, 2 decimales, sin símbolo, alineado a la derecha con `tabular-nums` | 1,234.56                |
| Importe en tarjetas, totales y pie | Con prefijo «$»                                              | $1,234.56               |
| Negativo                           | Signo menos y rojo; el cero se muestra 0.00                  | −1,234.56               |
| Captura de importes                | Acepta 1234.56 y 1,234.56 (RN-01), máscara de miles, `inputmode="decimal"` |                         |
| Fecha                              | dd/mm/aaaa; el campo es el `input type="date"` nativo y viaja como AAAA-MM-DD | 14/10/2026              |
| Fecha y hora (auditoría)           | dd/mm/aaaa hh:mm, 24 horas, hora de México (DEC-10)          | 14/10/2026 16:05        |
| Semana                             | «Sem SS / AAAA» (RN-11)                                      | Sem 04 / 2025           |
| Periodo                            | Dos fechas con raya                                          | 01/03/2025 – 07/03/2025 |
| Porcentaje                         | 2 decimales, espacio antes del signo                         | 16.00 %                 |
| UUID                               | Primeros 8 caracteres en listados; completo en ayuda emergente con botón «Copiar» | 3f2a91bc…               |
| CLABE                              | «•••• •••• •••• 1234» (SEG-07)                               |                         |
| Valores cerrados                   | La etiqueta en español de la tabla de la sección 9, nunca el valor guardado | Por cobrar              |
| Conteos                            | «N registros»; con 1, «1 registro»                           | 25 registros            |

### Estilo y componentes (RF-83)

Tipografía del sistema (`font-sans` de Tailwind), sin fuentes externas; base de 16 px; tablas de 14 px; título de pantalla de 24 px en seminegritas. La marca es el texto «GADIT»; no hay logotipo. Los iconos son SVG en línea tomados de Heroicons (licencia MIT) y copiados al proyecto, sin paquete nuevo.

**Colores** (clases de Tailwind; el texto sobre fondo claro cumple contraste AA):

| Uso                                              | Clase                                                        |
| ------------------------------------------------ | ------------------------------------------------------------ |
| Acción principal, enlaces, foco                  | `blue-700`, al pasar el ratón `blue-800`                     |
| Texto, texto secundario, bordes, fondo de página | `gray-900`, `gray-600`, `gray-300`, `gray-50` sobre superficies blancas |
| Éxito                                            | texto `green-800` sobre `green-100`                          |
| Advertencia                                      | texto `amber-800` sobre `amber-100`                          |
| Error y peligro                                  | texto `red-800` sobre `red-100`; botón de peligro `red-700`  |
| Información y neutro                             | `blue-800` sobre `blue-100` y `gray-700` sobre `gray-100`    |

**Insignias** (píldora con texto siempre visible):

| Valor                                                     | Color                                  |
| --------------------------------------------------------- | -------------------------------------- |
| Factura: cobrada, parcial, por cobrar, vencida, cancelada | verde, ámbar, azul, rojo, gris (RF-42) |
| Gasto: sin revisar, revisado, falta comprobante           | gris, verde, rojo (RF-31)              |
| Cuenta por pagar: pagada, vigente, vencida, cancelada     | verde, azul, rojo, gris                |
| Préstamo: liquidado, vigente, vencido                     | verde, azul, rojo                      |
| Contrato: vigente, terminado, cancelado                   | azul, verde, gris                      |
| Proyecto: en proceso, terminado, suspendido               | azul, verde, ámbar                     |
| Complemento de cobro: Sí, Pendiente, No aplica            | verde, ámbar, gris                     |
| Activo, inactivo                                          | verde, gris                            |

**Componentes:**

| Componente                       | Especificación                                               |
| -------------------------------- | ------------------------------------------------------------ |
| Botones                          | Uno primario por pantalla o formulario (`blue-700`, texto blanco); secundario con borde gris; peligro en rojo, solo para borrar y cancelar. El texto es un verbo en infinitivo («Guardar», «Cancelar», «Eliminar»). Mientras guarda se desactiva y muestra un giro (`wire:loading`) |
| Campos                           | Etiqueta arriba, asterisco rojo en los obligatorios (con `aria-required`), ayuda en gris debajo y error en rojo debajo con icono. El texto de ejemplo (placeholder) nunca sustituye a la etiqueta |
| Listas con búsqueda (Tom Select) | Para categoría, cuenta, proyecto, proveedor, persona, cliente, contrato y factura. Busca sin distinguir mayúsculas ni acentos, ofrece solo activos (RF-02), añade «Sin proyecto» donde aplica y se maneja con flechas y Enter |
| Importes                         | Campo con máscara (`@alpinejs/mask`), alineado a la derecha, con «$» como prefijo |
| Avisos                           | Éxito verde de 4 s (RF-04); error rojo y advertencia ámbar que se quedan hasta cerrarlos. Arriba a la derecha en escritorio y arriba a todo el ancho en móvil; `role="status"` el de éxito y `role="alert"` los demás |
| Ventanas modales                 | Cierran con Esc, con la X y con clic fuera; si hay cambios sin guardar preguntan «¿Descartar los cambios?». Atrapan el foco y lo devuelven al botón que las abrió. Llevan `role="dialog"` y `aria-modal` |
| Tablas                           | Encabezado fijo al desplazarse, filas con resaltado al pasar el ratón, importes a la derecha, totales en el pie y las acciones de fila en un menú «⋯» al final |
| Filtros                          | Barra con búsqueda y botón «Filtros»; los filtros activos aparecen como fichas con ✕ y un enlace «Limpiar filtros» |
| Estados de carga                 | Giro `wire:loading` si tarda más de 200 ms; las listas no muestran esqueletos |
| Tarjeta de indicador             | Título pequeño, valor grande en `tabular-nums` (rojo si es negativo) y enlace al listado que lo explica |
| Gráficas                         | Chart.js en tema claro con la paleta de arriba, leyenda, ayuda emergente con el importe formateado y un botón «Ver como tabla» para los mismos datos |

### Textos y mensajes (RF-84)

Español de México, tuteo, frases cortas y sin signos de exclamación. Un error dice qué pasó y qué hacer. Los mensajes de validación son los de laravel-lang con los nombres de campo de este documento (`attributes`), y los de reglas de negocio son los textos exactos de la sección 7.

| Situación             | Texto                                                        |
| --------------------- | ------------------------------------------------------------ |
| Guardado              | «Guardado»                                                   |
| Borrado lógico        | «Eliminado» con enlace «Deshacer» por 10 s (RF-06)           |
| Confirmar borrado     | Título «¿Eliminar {registro}?», el resumen del registro y botones «Eliminar» y «Cancelar» |
| Cancelar una factura  | Título «¿Cancelar la factura {folio}?», motivo obligatorio y botones «Cancelar factura» y «Volver» |
| Descartar cambios     | «¿Descartar los cambios?» con «Descartar» y «Seguir editando» |
| Lista vacía           | «Aún no hay {plural}» y, si el rol puede, el botón de crear  |
| Filtros sin resultado | «No hay registros con estos filtros» y «Limpiar filtros»     |
| 403                   | «No tienes permiso para ver esto» y «Ir al tablero»          |
| 404                   | «No encontramos lo que buscas» y «Ir al tablero»             |
| 419 o sesión caducada | «Tu sesión caducó. Inicia sesión de nuevo»; después del acceso vuelve a la pantalla que tenía |
| 500                   | «Algo salió mal» y «El error quedó registrado con la clave {clave}. Si se repite, dísela a quien administra el sistema»; la clave es la de RNF-09 |
| 503 (mantenimiento)   | «Estamos actualizando el sistema. Vuelve en unos minutos»    |

Inicio de sesión: tarjeta centrada de 400 px sobre fondo `gray-50`, título «GADIT», subtítulo «Control administrativo», campos Correo y Contraseña (con botón para mostrarla), botón «Entrar» y enlace «Olvidé mi contraseña». No hay enlace de registro (DEC-18).

### Teclado y accesibilidad (RF-85)

- Todo se puede hacer con teclado; el orden del Tab sigue el orden visual y el foco se ve siempre (anillo de 2 px `blue-700`).
- Cada campo tiene su `label` asociado; los errores se unen al campo con `aria-describedby` y el foco va al primero (RF-03).
- Contraste mínimo de 4.5:1 en texto y 3:1 en componentes (la paleta de RF-83 lo cumple); ningún dato se comunica solo con color.
- Las tablas usan `th` con `scope`; los avisos usan `aria-live`; las gráficas traen su tabla alternativa.
- Atajos: `/` lleva el foco a la búsqueda del listado, Esc cierra ventanas y paneles, y Enter envía el formulario (no en áreas de texto ni con una lista desplegada). No hay más atajos.
- El criterio de aceptación es WCAG 2.1 nivel AA (RNF-07).

### Pantallas clave (RF-86)

**Formulario de gasto (RF-30), escritorio.** Al abrirlo el foco queda en Importe, porque la fecha ya viene llena. Los campos de Beneficiario, Periodo, Pago de cuenta por pagar y Préstamo aparecen solos cuando la categoría o el proveedor los piden; «Más datos» agrupa IVA incluido, UUID del CFDI y Estatus de revisión y empieza cerrado. El navegador recuerda solo la última cuenta usada (`localStorage`, clave `gadit.gasto.cuenta`); si no hay almacenamiento, la pantalla funciona igual.

```
Nuevo gasto
+-------------------------------+-------------------------------+
| Fecha *          [14/10/2026] | Importe con IVA *    $ [ 0.00 ]|
+-------------------------------+-------------------------------+
| Concepto *       [                                           ]|
+-------------------------------+-------------------------------+
| Categoria *      [Buscar...  v]| Cuenta *             [Elegir v]|
| Proyecto         [Sin proyecto v]| Proveedor            [      v]|
+-------------------------------+-------------------------------+
| (solo si aplican) Beneficiario | Periodo | Pago de cuenta por  |
| pagar | Prestamo                                              |
| > Mas datos: IVA incluido | UUID del CFDI | Estatus de revision|
| Observaciones    [                                           ]|
| Comprobante      [Arrastra archivos o elige]  [Tomar foto]    |
+---------------------------------------------------------------+
        [Guardar y capturar otro]   [Guardar]   Cancelar
```

**Tablero completo (RF-60), escritorio.** De arriba abajo: selector de periodo; fila de tarjetas (contratado, facturado, cobrado, por cobrar, vencido, gastos del periodo, por pagar, utilidad de caja); franja de alertas; dos gráficas por fila; tablas de contratos y facturas con saldo; «Dónde está el dinero» y reparto. La versión operativa omite lo marcado «Completa» en la sección 14.

```
Tablero                                  Periodo: [Todo el tiempo v]
+-----------+-----------+-----------+-----------+
| Contratado| Facturado | Cobrado   | Por cobrar|
| $17.9 M   | $17.1 M   | $19.5 M   | $...      |
+-----------+-----------+-----------+-----------+
| Alertas:  Facturas vencidas (n) | Complementos (n) | CxP (n) |
+-----------------------------+-------------------------------+
| Gasto semanal (barras)      | Gasto por grupo (barras)      |
+-----------------------------+-------------------------------+
| Facturado y cobrado por mes | Contratos (tabla)             |
+-----------------------------+-------------------------------+
| Facturas con saldo          | Donde esta el dinero | Reparto|
+-----------------------------+-------------------------------+
```

## 17. Requisitos no funcionales

GADIT es una empresa pequeña con menos de 10 usuarios, así que estos números son objetivos de diseño razonables y no una promesa de escala. Se miden antes del arranque; si alguno no se alcanza, se decide con el dueño antes de abrir el sistema, no después.

| ID     | Tema                       | Requisito                                                    | Cómo se comprueba                                            |
| ------ | -------------------------- | ------------------------------------------------------------ | ------------------------------------------------------------ |
| RNF-01 | Pantallas                  | Listados, detalles y formularios responden en 1 s o menos (percentil 95, medido en el servidor) con 100,000 gastos y 25 filas por página. Un listado hace 12 consultas SQL como máximo y ninguna es carga perezosa | Pest, grupo `rendimiento` (fuera de la corrida normal): crea 100,000 gastos con la fábrica, recorre RF-31, RF-42 y RF-53 y mide tiempo y consultas. Fuera de producción `Model::preventLazyLoading()` está activo |
| RNF-02 | Tablero y reportes         | 2 s o menos (percentil 95) con el mismo volumen; el tablero hace 20 consultas como máximo | La misma prueba. `EXPLAIN` de las vistas `v_gastos_semana` y `v_gastos_categoria` con filtro de fechas, sin recorrer toda la tabla `gastos` |
| RNF-03 | Exportaciones              | 20,000 filas en 30 s o menos y 256 MB de memoria o menos, leyendo por bloques de 1,000 (`FromQuery` de maatwebsite/excel). PHP-FPM con `memory_limit` 512M y `max_execution_time` 60; Nginx con `fastcgi_read_timeout` 60 s | La misma prueba; revisión de la configuración del servidor   |
| RNF-04 | Capacidad                  | 10 usuarios activos y 5 simultáneos; hasta 100,000 gastos, unos 75 años al ritmo de 1,300 al año. VPS mínimo: 2 vCPU, 4 GB de RAM y 50 GB de SSD; los adjuntos crecen unos 3 GB al año (1,300 comprobantes de 2 MB) | Plan contratado (PEN-06); el monitor avisa al llegar al 80 % del disco |
| RNF-05 | Disponibilidad             | 99.5 % al mes de lunes a sábado de 8:00 a 20:00, hora de México. Despliegues y mantenimientos fuera de ese horario, con el modo de mantenimiento encendido 5 minutos o menos | Monitor externo cada 5 minutos sobre `/up` (PEN-06); informe mensual |
| RNF-06 | Navegadores y dispositivos | Las dos últimas versiones estables de Chrome, Edge, Firefox y Safari, incluidos Chrome en Android y Safari en iOS, desde 360 px de ancho. Sin soporte para Internet Explorer | Lista de entrega (Apéndice E), probada a mano en cada navegador |
| RNF-07 | Accesibilidad              | WCAG 2.1 nivel AA en inicio de sesión, tablero, gastos, facturas y cobros (RF-85) | Lighthouse con puntaje de accesibilidad de 90 o más en esas cinco pantallas y un recorrido solo con teclado, anotados en el Apéndice E |
| RNF-08 | Idioma                     | Un solo idioma, español de México: `APP_LOCALE=es` y Carbon en `es_MX`. Los textos van escritos en español en las vistas; las etiquetas de valores cerrados, en `app/Enums`; los mensajes de validación, en laravel-lang. No se prepara infraestructura de traducción | Revisión de código                                           |
| RNF-09 | Observabilidad             | Logs diarios con 14 días de retención. Cada petición recibe una clave de 8 caracteres hexadecimales que va en el contexto del log, en la cabecera `X-Request-Id` y en la página 500 (RF-84). Un error registra la clave, el id del usuario, método, ruta y excepción; nunca el cuerpo de la petición ni la CLABE (SEG-07). Una consulta de más de 1 s deja una advertencia con su SQL | Prueba: provocar una excepción y encontrar su clave en el log |
| RNF-10 | Respaldos y recuperación   | `backup:run` a las 02:00 (base de datos y adjuntos), archivo con contraseña y copia en un disco externo al servidor. Retención de spatie/laravel-backup: todos 7 días, uno diario 30 días, uno semanal 12 semanas y uno mensual 12 meses. `backup:monitor` avisa por correo si el último respaldo tiene más de 1 día. Objetivos: perder como máximo 24 h de datos y volver a operar en 4 h. La contraseña del archivo se guarda junto con la `APP_KEY` (SEG-09) | Simulacro de restauración en un servidor limpio antes del arranque (criterio de éxito 5, sección 2) y cada 6 meses |
| RNF-11 | Calidad de código          | `pint --test` sin diferencias, Larastan en nivel 6 sin errores, `pest --coverage --min=80` y una prueba al menos por cada RN y por cada fila de la matriz de permisos (sección 18) | Integración continua (sección 8)                             |
| RNF-12 | Servidor                   | Abiertos solo los puertos 22 (SSH con llave, sin contraseña y sin root), 80 y 443; MySQL escucha solo en 127.0.0.1; `APP_DEBUG=false` y `expose_php=Off`; certificado TLS con renovación automática; actualizaciones de seguridad del sistema operativo automáticas | Lista de entrega (Apéndice E)                                |
| RNF-13 | Retención de datos         | La bitácora y los registros de negocio no se purgan. Los logs duran 14 días y los respaldos lo que dice RNF-10. Los tokens de recuperación de contraseña vencidos se limpian con `auth:clear-resets` todos los días a las 03:30 en el programador; las sesiones vencidas, con el recolector de Laravel | Revisión del programador (`schedule:list`)                   |

## 18. Estrategia de pruebas

El dinero ajeno no se prueba a ojo: cada regla de la sección 7, cada fila de la matriz de la sección 6 y cada cifra de control de la sección 15 tiene una prueba automática, y las pocas cosas que no se automatizan van en una lista de entrega con responsable.

### Herramientas y convenciones

| Aspecto       | Especificación                                               |
| ------------- | ------------------------------------------------------------ |
| Marco         | Pest con sus plugins de Laravel y Livewire (`Livewire::test`). No hay pruebas de navegador automatizadas en v1; lo visual se cubre con la lista de entrega (Apéndice E) |
| Base de datos | MySQL 8.4, base `gadit_test` (DEC-06), con `RefreshDatabase`: migra una vez por corrida y envuelve cada prueba en una transacción. Las pruebas que necesitan confirmar de verdad (bloqueo optimista con dos conexiones) usan `DatabaseTruncation` |
| Fábricas      | Una por modelo, con estados con nombre: `Factura::factory()->cancelada()`, `Gasto::factory()->revisado()`, `Cuenta::factory()->conSaldoInicial('100000.00', '2025-03-31')`, `Usuario::factory()->admin()`, `->captura()` y `->consulta()`. Los importes se escriben como texto, nunca como float |
| Datos base    | `$this->seed(CatalogosBaseSeeder::class)` en las pruebas que necesitan categorías, cuentas y personas |
| Reloj         | Toda prueba fija la hora con `Carbon::setTestNow('2026-03-16 10:00', 'America/Mexico_City')` salvo que pruebe otra |
| Nombres       | La descripción de cada prueba empieza con el ID de lo que cubre (`it('RN-05: rechaza un cobro que excede el saldo')`), para poder correr `pest --filter="RN-05"` |
| Mensajes      | Las pruebas comparan el texto exacto de los mensajes de la sección 7, no solo que haya error |
| Trazabilidad  | `tests/trazabilidad.php` lista los ID obligatorios: RN-01 a RN-26, SEG-01 a SEG-13, los RF de prioridad M y las reglas IMP. La prueba `tests/Meta/TrazabilidadTest.php` falla si algún ID no aparece en el nombre de alguna prueba (RNF-11) |
| Sin float     | Una prueba meta busca `(float)` y `floatval(` en `app/` y falla si aparece (DEC-03) |

### Tipos de prueba

| Tipo          | Carpeta                     | Qué cubre                                                    | Cuándo corre                                                 |
| ------------- | --------------------------- | ------------------------------------------------------------ | ------------------------------------------------------------ |
| Unidad        | `tests/Unit`                | `app/Support`: `Dinero` (RN-01), `Clabe` (RN-22, Apéndice D), `Cfdi` (RF-35), `SemanaIso` (RN-11), limpieza de texto (RN-26) | Cada integración                                             |
| Reglas        | `tests/Feature/Reglas`      | Una o más pruebas por cada RN, con los casos mínimos de la tabla siguiente | Cada integración                                             |
| Acciones      | `tests/Feature/Acciones`    | Cada Action: corre en transacción y la deshace si falla, escribe la bitácora (RN-21) y respeta el bloqueo optimista (DEC-12) | Cada integración                                             |
| Pantallas     | `tests/Feature/Pantallas`   | Cada RF con Livewire: dibujo, filtros, orden, paginación, validaciones, acciones masivas y exportaciones (número de filas y contenido; nunca la CLABE, SEG-07) | Cada integración                                             |
| Permisos      | `tests/Feature/Permisos`    | La matriz de la sección 6 completa (siguiente apartado)      | Cada integración                                             |
| Seguridad     | `tests/Feature/Seguridad`   | SEG-01 a SEG-13 en lo que se puede probar: límite de 5 intentos por minuto, cookies, cabeceras, adjuntos por tipo real y nombre UUID, la CLABE ausente de logs, bitácora y exportaciones | Cada integración                                             |
| Base de datos | `tests/Feature/BaseDeDatos` | Equivalencia de esquema, vistas contra el Apéndice B, restricciones CHECK, llaves RESTRICT, columna `semana_iso` | Cada integración                                             |
| Importador    | `tests/Feature/Importador`  | Las pruebas de la sección 15 y las cifras de control         | Cada integración; la del archivo real, si existe `GADIT_EXCEL_REAL` |
| Rendimiento   | `tests/Rendimiento`         | RNF-01 a RNF-03, con el grupo `rendimiento`                  | Antes de cada versión y de la entrega                        |

### Pruebas de permisos

La fuente es `tests/Feature/Permisos/matriz.php`, un arreglo con una entrada por cada línea de la matriz de la sección 6 y por cada rol; quien cambie la matriz cambia ese archivo en el mismo pull request. Por cada entrada se prueban tres cosas:

1. **Ruta.** La pantalla de RF-80 responde 200 al rol que la ve y 403 al que no (nunca una redirección al tablero); un invitado va al inicio de sesión y un usuario con `activo = 0` pierde la sesión en su siguiente petición.
2. **Política.** `viewAny`, `view`, `create`, `update`, `delete` y `restore` del modelo, y las acciones especiales `Factura::cancel`, `Gasto::markReviewed`, `DatoBancario::viewFullClabe` y `Tablero::viewFull`.
3. **Llamada directa.** Una acción de Livewire invocada por un rol sin permiso responde 403 aunque el botón no se vea (sección 6, «ocultar un botón no es seguridad»).

Casos por fila que no pueden faltar: `captura` edita un gasto no revisado pero no uno revisado; `captura` marca `falta_comprobante` pero no `revisado`; `consulta` no escribe en ningún módulo; el selector de cuentas que ve `captura` no trae saldos; `consulta` ve la CLABE enmascarada y `captura` completa; `captura` recibe 403 en los reportes no operativos y en las exportaciones que no puede ver.

### Casos mínimos por regla

Además del caso feliz y del mensaje exacto, cada regla se prueba con lo siguiente. Los casos B1 a B11 son los ejemplos resueltos del Apéndice B.

| Regla | Casos mínimos                                                |
| ----- | ------------------------------------------------------------ |
| RN-01 | B3; 999,999,999,999.99 se acepta y 1,000,000,000,000.00 no; «1,234.56» y «1234.56» dan lo mismo |
| RN-02 | B1 y B2; retención de 0 % y de 100 %; IVA de 0 %             |
| RN-03 | Folio repetido y de 31 caracteres; UUID de 35 caracteres, con letras fuera de a a f, en mayúsculas (se guarda en minúsculas) y repetido; factura nueva sin UUID, rechazada; importada sin UUID, aceptada |
| RN-04 | PPD fija la forma de pago en 99; PUE sin forma, rechazada; cobro nuevo sin forma de pago, rechazado; cobro PPD sin complemento se guarda y aparece en `v_complementos_pendientes`; PUE con cobro parcial solo advierte |
| RN-05 | B4; exceso de 0.01 aceptado y de 0.02 rechazado; cobro anterior a la emisión; cobro a factura cancelada |
| RN-06 | B4; suma de IVA que excede por 0.05 aceptada y por 0.06 rechazada |
| RN-07 | Las cinco situaciones con fecha fija; factura parcial con vencimiento pasado lleva también la insignia «Vencida» |
| RN-08 | Contrato con ajustes y facturas, con sus cuatro cifras; factura que excede el neto pide confirmar y deja la confirmación en bitácora; una factura cancelada no cuenta |
| RN-09 | Adenda sin padre, con padre de otro proyecto y con padre adenda, todas rechazadas; contrato con padre, rechazado |
| RN-10 | Importe 0 rechazado; negativo solo admin y con observaciones; `iva_incluido` mayor que el importe; concepto de 0 y de 201 caracteres; categoría o cuenta inactiva; beneficiario obligatorio en comisiones y reparto de utilidad y no en las demás |
| RN-11 | B10                                                          |
| RN-12 | Las seis combinaciones de transición por rol; `captura` no edita un gasto revisado; subir un adjunto no cambia el estatus |
| RN-13 | Gasto de obra sin proyecto se guarda con la advertencia y entra a la cola de RF-33; uno de otro grupo no avisa |
| RN-14 | B5; sin fecha de corte suma todo; un movimiento de la propia fecha de corte no suma; el saldo negativo no bloquea |
| RN-15 | B9; origen igual a destino; monto 0; comisión de 100.01 %; cuenta del gasto con destino intermediario, con ambos y con ninguno; editar y borrar el traspaso se propagan al gasto |
| RN-16 | B6; pago igual al saldo; exceso de 0.01 aceptado y de 0.02 rechazado; cuenta con pagos no se cancela; el gasto hereda proveedor y proyecto |
| RN-17 | B11; los intereses con `prestamo_id` no bajan el capital; pago que excede por 0.02, rechazado |
| RN-18 | B7 con y sin cuentas por pagar, y con un préstamo vigente que no resta |
| RN-19 | B8; esquema de 99.99 y de 100.01 rechazados; porcentaje 0; persona repetida; activar otro esquema desactiva el anterior; «Entregado de más» |
| RN-20 | Borrado lógico de cada tabla que lo usa; los tres bloqueos con su mensaje; borrar un traspaso borra su comisión; no se restaura un hijo con padre borrado; solo el admin restaura |
| RN-21 | Crear, editar y eliminar guardan solo los atributos que cambiaron; nunca `password`, `remember_token`, `clabe` ni `updated_at`; restaurar es editar con `deleted_at` nulo; usuario nulo en consola; la importación deja un solo registro |
| RN-22 | CLABE válida e inválidas del Apéndice D; con dos dueños y sin dueño, rechazada; la segunda cuenta principal desmarca la primera; banco de 51 caracteres, rechazado; `clabe_ultimos4`; el valor guardado en la base no contiene los 18 dígitos |
| RN-23 | Correo repetido; desactivar o cambiar de rol al último admin; que un admin se desactive a sí mismo |
| RN-24 | Desactivar cuenta con saldo, proveedor con saldo y persona con préstamo con saldo o en el esquema vigente; nombre repetido con el mensaje exacto; lo ya registrado no cambia |
| RN-25 | Mañana y 31/12/2019 rechazadas; hoy y 01/01/2020 aceptadas; a las 23:30 de México la fecha de ese día no es futura; vencimiento anterior al documento; periodo\_fin menor que periodo\_inicio |
| RN-26 | Espacios sobrantes recortados, mayúsculas intactas; la búsqueda de «cafe» encuentra «Café» |

### Pruebas de esquema y vistas

- **Equivalencia.** En integración continua se crea una base `gadit_ref` con `mysql < gadit_schema.sql` y otra `gadit_test` con las migraciones. La prueba compara tablas, columnas (tipo, nulabilidad, valor por defecto, columna generada), índices, llaves foráneas, restricciones CHECK y vistas con su definición normalizada; cualquier diferencia falla.
- **Vistas.** Los casos del Apéndice B se crean con las fábricas y se compara cada columna de las 13 vistas que intervienen con el valor escrito allí. Con los datos del Excel (sección 15) se repiten las cifras de control.
- **Modo SQL.** La base de pruebas corre con `ONLY_FULL_GROUP_BY` (el valor por omisión de MySQL 8.4) y no se desactiva.
- **Primera corrida en MySQL 8.4.** `gadit_schema.sql` y `gadit_semillas.sql` se validaron en MariaDB 10.11 con `ONLY_FULL_GROUP_BY`, no en MySQL 8.4. La primera tarea del equipo es correrlos en MySQL 8.4 y registrar el resultado en el pull request (RIE-07). Resultado de la primera corrida (GAD-28, 26/10/2026): ambos archivos cargaron sin error en MySQL 8.4.11 y las 13 vistas se consultaron sin error. Se corrigieron dos diferencias (la colación de la conexión y las llaves foráneas, que MySQL reporta NO ACTION si no declaran regla) y el SQL quedó en v1.2.

### Aceptación con el dueño

La hace el dueño, con un usuario `captura` y otro `admin`, en el entorno de producción, con una importación de prueba de la copia más reciente del Excel (la base se vacía antes del arranque) y antes de abandonar el Excel. Cada paso se marca como pasa o no pasa.

| #    | Escenario                                                    | Resultado esperado                                           |
| ---- | ------------------------------------------------------------ | ------------------------------------------------------------ |
| 1    | Capturar 10 gastos desde un celular, 3 con foto del ticket   | Cada gasto tarda menos de 1 minuto, quedan con adjunto y el total de la semana sube en la suma capturada |
| 2    | Registrar una factura PPD y su cobro sin complemento         | La factura nace con forma 99, el cobro aparece en Complementos pendientes y desaparece al registrar el complemento |
| 3    | Registrar un traspaso con comisión de 2.5 %                  | Se crea el gasto de comisión en la cuenta del intermediario y los dos estados de cuenta cuadran |
| 4    | Dar de alta una compra a crédito y pagarla en dos abonos     | El saldo baja en cada abono y la cuenta queda pagada         |
| 5    | Registrar una entrega de utilidad a un socio                 | «Ya entregado» y «Pendiente» de ese socio cambian en el importe |
| 6    | Comparar el tablero con CONCENTRADO                          | Las cifras de control de la sección 15 coinciden al centavo  |
| 7    | Entrar con `consulta`                                        | No hay botones de crear ni editar y las exportaciones funcionan |
| 8    | Borrar un gasto y deshacerlo, y restaurar otro desde la papelera | Ambos regresan a las listas y la bitácora muestra las dos acciones |

### Criterio de salida

Una versión sale cuando: la integración continua está en verde (sección 8); la prueba de trazabilidad pasa; el grupo `rendimiento` cumple RNF-01 a RNF-03; la conciliación del importador con el archivo real pasa; la lista de entrega está firmada; y los ocho escenarios de aceptación pasan (definición de hecho en la sección 19).

## 19. Plan de implementación, hitos y definición de hecho

El sistema se construye en cuatro hitos que terminan cada uno en algo que el dueño puede usar y verificar. Las 21 tablas se crean desde el hito 0 porque `gastos` referencia a varias del hito 2; lo que se escalona son las pantallas.

### Hitos

| Hito                 | Alcance                                                      | Salida verificable                                           | Días de trabajo |
| -------------------- | ------------------------------------------------------------ | ------------------------------------------------------------ | --------------- |
| 0. Cimientos         | Repositorio, Sail e integración continua (sección 8), con todos los paquetes instalados en PHP 8.4 (DEC-01 y RIE-15); migraciones 1 a 1 con `gadit_schema.sql` y la prueba de equivalencia; `CatalogosBaseSeeder`; `app/Support` (Dinero, Clabe, SemanaIso); distribución y menú (RF-80, RF-81); inicio de sesión y usuarios (RF-11 a RF-15); roles, políticas y matriz de permisos; bitácora (`Auditable`, RN-21) | Integración continua en verde; el esquema carga en MySQL 8.4 (RIE-07); la matriz de permisos pasa; un admin entra y crea usuarios | 8 a 10          |
| 1. Núcleo            | Catálogos (RF-20 a RF-22, RF-25, RF-26); contratos, ajustes, facturas, cobros y complementos (RF-40 a RF-44); gastos, colas y acciones masivas (RF-30 a RF-33); adjuntos (RF-34); importador (sección 15); tablero operativo (RF-60 en su versión operativa); primer despliegue en el VPS con deploy.sh, aún sin usuarios, para comprobar los límites de PHP-FPM y Nginx de RNF-03 | Las cifras de control de la sección 15 coinciden; un gasto se captura con cinco campos desde el celular; el dueño compara el tablero con CONCENTRADO | 20 a 25         |
| 2. Deuda y dinero    | Proveedores y datos bancarios (RF-23, RF-24); cuentas por pagar, préstamos, traspasos, estados de cuenta y reparto (RF-50 a RF-54); tablero completo, reportes y exportaciones (RF-60 a RF-62) | Los escenarios 2 a 5 de aceptación pasan; el reparto muestra lo de la sección 15 | 18 a 22         |
| 3. Control y entrega | Bitácora y papelera (RF-70, RF-71); lectura de CFDI (RF-35, prioridad C, si hay tiempo); respaldos y simulacro (RNF-10); rendimiento y accesibilidad (RNF-01 a RNF-03, RNF-07); lista de entrega; guía rápida de una página por rol; aceptación y arranque | Los ocho escenarios de aceptación pasan; simulacro de restauración documentado; sistema en producción | 10 a 12         |

Total: de 56 a 69 días de trabajo, unas 11 a 14 semanas para una persona de tiempo completo, o de 7 a 9 con dos. Es una estimación gruesa de ±30 %, con una persona en revisión de código y pruebas incluidas, y no un compromiso; se revisa al cerrar el hito 0, cuando ya se midió el ritmo real.

**Decisiones que cada hito necesita** (las define la sección 21; todas traen una decisión por defecto, así que ninguna detiene el trabajo):

| Antes de…             | Decisiones                                                   |
| --------------------- | ------------------------------------------------------------ |
| Cerrar el hito 0      | PEN-06 (servidor, correo y monitor), PEN-16 (administradores) |
| Importar en el hito 1 | PEN-03, PEN-09 y PEN-14 (PEN-08 y PEN-15 pueden esperar a después del arranque) |
| Cerrar el hito 2      | PEN-02, PEN-04, PEN-10, PEN-12, PEN-13, PEN-17 y PEN-19      |
| Arrancar              | PEN-05, PEN-07 (fecha de corte y saldos contados), PEN-11, PEN-18 y PEN-20 (deudas vigentes) |

### Arranque

T es el día de la fecha de corte (PEN-07). Mientras no se cumpla un paso, no se pasa al siguiente.

| Cuándo             | Qué se hace                                                  | Responsable              |
| ------------------ | ------------------------------------------------------------ | ------------------------ |
| T menos 14 días    | Producción lista (RNF-12), dominio con TLS, correo saliente probado y monitor encendido | Equipo                   |
| T menos 7 días     | Simulacro de restauración de respaldo (criterio de éxito 5) y aceptación con el dueño (sección 18) sobre una importación de prueba, que se vacía después | Equipo y dueño           |
| T menos 3 días     | `gadit:importar-excel --simular` con la copia más reciente del Excel y revisión del informe | Equipo y dueño           |
| T                  | Se congela el Excel y se toma su copia. Se corre `backup:run` a mano y después la importación real (orden de la sección 15). El dueño cuenta el efectivo y el saldo bancario y los captura como saldo inicial de cada cuenta con la fecha T; también entrega la lista de préstamos y compras a crédito vigentes, que se capturan ese día (PEN-20). Se crean los usuarios y hay una sesión de capacitación de una hora por rol | Equipo y dueño           |
| T más 1 a T más 14 | Operación en paralelo: todo se captura en el sistema y cada viernes se compara su tablero con el Excel. Cualquier diferencia se corrige en el sistema, no en el Excel | Quien captura y el dueño |
| T más 14           | El Excel queda como archivo histórico de solo lectura; el sistema es la única fuente | Dueño                    |

Si la importación real falla o sus cifras no coinciden, no se abre el sistema: se restaura el respaldo que se tomó justo antes y el Excel sigue siendo la fuente.

### Definición de hecho

Una tarea (un RF o parte de él) está hecha cuando se cumplen todas estas condiciones; un solo «no» la devuelve a desarrollo:

1. Otra persona revisó el pull request y la rama se unió a `main` con la integración continua en verde (pint, Larastan, Pest, `composer audit` y `npm audit`).
2. Hay pruebas cuyo nombre empieza con el ID del RF o de la RN (sección 18) y la prueba de trazabilidad pasa.
3. La política y la matriz de permisos reflejan lo que dice la sección 6, y se probó una llamada directa de un rol sin permiso.
4. Si el modelo es auditable, crear, editar y eliminar dejan su fila en bitácora.
5. Los mensajes son los textos exactos de la sección 7 y los formatos son los de RF-82.
6. La pantalla se ve y funciona a 360 px y a 1,280 px, con teclado y con el foco visible (RF-81 y RF-85).
7. No hay consultas N+1 (`preventLazyLoading`) y los filtros viajan en la URL.
8. Si cambió la estructura de la base, el cambio está en la migración y en `gadit_schema.sql`, y la prueba de equivalencia pasa.
9. Si cambió un comportamiento, este documento se corrige en el mismo pull request y se sube su versión.
10. El dueño la vio funcionar en el entorno de pruebas (basta una demostración por hito).

Un hito está hecho cuando todas sus tareas lo están y se cumple su salida verificable.

### Equipo y control de cambios

| Rol                    | Responsabilidad                                              |
| ---------------------- | ------------------------------------------------------------ |
| Dueño (por GADIT)      | Aprueba este documento, resuelve los PEN, cuenta los saldos iniciales y hace la aceptación |
| Desarrollo             | Construye, escribe las pruebas y corrige este documento cuando algo cambia |
| Revisión de código     | Una persona distinta de quien escribió el código; puede ser otro desarrollador |
| Contador (externo)     | Contesta PEN-12 y RIE-04; no usa el sistema                  |
| Asesor legal (externo) | Valida el aviso de privacidad (PEN-11)                       |

Todo cambio a una regla, pantalla o permiso se escribe primero en este documento, con su ID y una nueva versión (1.1, 1.2…) anotada en la sección 1, y después se programa. Un cambio de alcance (lo que la sección 2 dejó fuera) lo aprueba el dueño y se estima aparte.

## 20. Riesgos

Se repasan al cerrar cada hito (sección 19); un riesgo cuya probabilidad o impacto sube se anota aquí con versión nueva. B es baja, M media y A alta.

| ID     | Riesgo                                                       | Prob. | Impacto | Mitigación                                                   | Vigila           |
| ------ | ------------------------------------------------------------ | ----- | ------- | ------------------------------------------------------------ | ---------------- |
| RIE-01 | Los datos históricos nacen incompletos: 37 partidas caen en VARIOS (32 con una etiqueta sin regla) y ningún gasto histórico tiene proyecto, así que la rentabilidad por proyecto sale incompleta | A     | M       | Colas y acciones masivas (RF-32, RF-33); PEN-08 y PEN-09; el reporte de rentabilidad muestra la fila «sin proyecto» con su monto para que no se confunda con ganancia | Dueño            |
| RIE-02 | Se pierde la `APP_KEY` y las CLABE guardadas ya no se pueden descifrar | B     | A       | Copia de la `APP_KEY` en un gestor de contraseñas (SEG-09); respaldo con contraseña guardada aparte (RNF-10); el simulacro de restauración incluye descifrar una CLABE | Equipo           |
| RIE-03 | Se sigue capturando en el Excel y en el sistema a la vez y las cifras divergen | M     | A       | Operación en paralelo de dos semanas con comparación semanal y Excel de solo lectura al final (sección 19) | Dueño            |
| RIE-04 | El manejo de efectivo y la comisión de 2.5 % del intermediario pueden tener efectos fiscales; el sistema solo registra | M     | A       | El contador revisa el esquema; la utilidad no sustituye a la contabilidad (DEC-15); el sistema conserva comprobantes y bitácora y no aplica lógica fiscal | Dueño y contador |
| RIE-05 | Errores de centavos por decimales binarios o redondeos distintos | M     | M       | DEC-03 y RN-01; prueba meta contra `float` (sección 18); casos B3, B4 y B8; comparación con las cifras de control | Equipo           |
| RIE-06 | El alcance crece (presupuestos, contabilidad, timbrado, bancos) y retrasa lo esencial | M     | M       | Lista de fuera de alcance (sección 2) y control de cambios (sección 19); cada petición se estima aparte | Dueño y equipo   |
| RIE-07 | El esquema solo se probó en MariaDB 10.11 y puede haber diferencias en MySQL 8.4 (por ejemplo en `v_saldo_cuentas` o en la columna generada) | M     | M       | Primera tarea del hito 0: cargar `gadit_schema.sql` y `gadit_semillas.sql` en MySQL 8.4 y correr la prueba de equivalencia; corregir el SQL y las migraciones antes de seguir | Equipo           |
| RIE-08 | Se exponen datos financieros o personales: accesos de más, respaldos sin proteger o el Excel real dentro de un repositorio | M     | A       | Roles de la sección 6; SEG-01 a SEG-13; respaldos con contraseña (RNF-10); el archivo real fuera del repositorio (sección 15); aviso de privacidad validado (PEN-11) | Dueño y equipo   |
| RIE-09 | Un solo servidor sin alta disponibilidad: si falla, el sistema se cae | M     | M       | RPO de 24 h y RTO de 4 h (RNF-10); respaldo fuera del servidor; el simulacro documenta la restauración; el Excel histórico se conserva | Equipo           |
| RIE-10 | Una sola persona sabe operar o mantener el sistema           | M     | A       | Este documento; convenciones de Laravel; al menos dos admins (PEN-16); revisión de código por otra persona | Dueño            |
| RIE-11 | La importación se corre con una versión del Excel distinta de la que se concilia porque alguien lo sigue editando | M     | M       | Congelar el Excel en la fecha de corte; SHA-256 en el informe; simulacro y comparación con CONCENTRADO (sección 19) | Dueño            |
| RIE-12 | Se captura dos veces el mismo movimiento (dos personas o un doble envío) | M     | B       | Botón desactivado al guardar (RF-04), aviso de posible duplicado (RF-30) y bloqueo optimista (DEC-12) | Equipo           |
| RIE-13 | Las exportaciones síncronas de hasta 20,000 filas exceden el tiempo del servidor | B     | B       | Lectura por bloques y límites de RNF-03; si no alcanza, filtrar y, en una versión posterior, usar colas | Equipo           |
| RIE-14 | Los Complementos de Pago de las facturas PPD se emiten tarde porque nadie nota el cobro | M     | A       | Alerta del tablero y cola de RF-44; el plazo se confirma con el contador (PEN-12) | Dueño y contador |
| RIE-15 | La pila es reciente (Laravel 13, Livewire 4, Pest 5, maatwebsite/excel 4) y un paquete falla con otro o cambia un comportamiento del que depende el sistema; por ejemplo, la guía de actualización de Excel 4 no menciona WithCalculatedFormulas, que usa el importador | M     | M       | Primera tarea del hito 0, junto con RIE-07: instalar todo en PHP 8.4, correr la integración continua y el importador con --simular sobre una copia del Excel real; si un paquete falla, se fija la línea anterior (maatwebsite/excel 3.1 admite Laravel 13) y se anota aquí | Equipo           |
| RIE-16 | La historia del Excel está incompleta o tiene errores (gastos fuera de GADIT, deudas fuera de sus hojas, fechas corridas y posibles capturas dobles), así que la utilidad y el reparto históricos importados no son exactos | A     | M       | Apéndice F, PEN-18, PEN-20, el informe del importador y la comparación semanal de la operación en paralelo (sección 19) | Dueño            |

## 21. Pendientes con el dueño y decisión por defecto

Ningún pendiente detiene el trabajo: el equipo construye con la decisión por defecto y solo rehace lo que dice la última columna si el dueño decide otra cosa. Cada respuesta se anota aquí con su fecha y, si cambia el comportamiento, se corrige la sección que corresponda (sección 19).

| ID     | Pendiente                                                    | Decisión por defecto                                         | Decide               | Antes de                         | Si cambia, se toca                                           |
| ------ | ------------------------------------------------------------ | ------------------------------------------------------------ | -------------------- | -------------------------------- | ------------------------------------------------------------ |
| PEN-01 | ¿Habrá ingresos o gastos en dólares?                         | Moneda única MXN y sin columnas de moneda (DEC-07)           | Dueño                | Sin fecha (fuera de v1)          | Moneda y tipo de cambio en gastos, facturas y cobros, y en las vistas: cambio de alcance |
| PEN-02 | Regla de Peren: factor de 80 %, deductivas y las tres cifras de adeudo | No se calcula; los descuentos se capturan como ajustes del contrato (RF-41) | Dueño                | Cerrar el hito 2                 | Una fórmula nueva en contratos y en cuentas por cobrar       |
| PEN-03 | ¿A qué cuenta entró cada cobro histórico?                    | Todos al Banco del Bajío (IMP-18)                            | Dueño                | Importar                         | Corregir la cuenta de cada cobro en RF-43; no cambia saldos porque son anteriores al corte (DEC-19) |
| PEN-04 | Periodo de la tasa de cada préstamo: mensual, anual o única  | `mensual`, editable en cada préstamo (RN-17)                 | Dueño                | Cerrar el hito 2                 | Solo captura; no afecta el capital                           |
| PEN-05 | ¿Debe `captura` ver facturado, cobrado y gastos?             | Sí, solo el tablero operativo de la sección 6                | Dueño                | Arrancar                         | Matriz de permisos, tablero y sus pruebas                    |
| PEN-06 | Servidor, dominio, correo saliente, monitor externo y destino de los respaldos | VPS de 2 vCPU, 4 GB y 50 GB (RNF-04); el equipo propone dos opciones con precio; mientras tanto se trabaja en Sail | Dueño                | Cerrar el hito 0                 | Configuración y `deploy.sh`; no cambia el diseño             |
| PEN-07 | Fecha de corte y saldos iniciales: qué día se cuenta y quién cuenta el efectivo | El primer lunes tras la aceptación; cada custodio cuenta su efectivo ese día y el banco se toma de su estado de cuenta (sección 19) | Dueño                | Arrancar                         | La fecha en `cuentas.fecha_saldo_inicial`; sin código        |
| PEN-08 | Cómo asignar proyecto a los gastos históricos                | No es requisito para arrancar; se asigna por lote con RF-32 y la cola de RF-33 con el criterio del dueño | Dueño                | Sin fecha (después del arranque) | Solo trabajo de captura                                      |
| PEN-09 | Las 22 etiquetas del Excel sin regla de categoría (Apéndice A) | Van a VARIOS con la etiqueta original anotada; el dueño las reclasifica con RF-32 | Dueño                | Importar                         | Filas nuevas en `config/importacion.php`, clave `categorias`, y otra simulación |
| PEN-10 | ¿Es 30 / 25 / 22.5 / 22.5 el esquema de reparto definitivo? (en CAJA Q26:R37 el remanente de una factura de Solarig se reparte 65 % Alejandra y Javier, 20 % Álvaro y 15 % GPE) | El del Excel, cargado por `CatalogosBaseSeeder`; se cambia en RF-54 | Dueño                | Cerrar el hito 2                 | Datos; sin código                                            |
| PEN-11 | Aviso de privacidad y tratamiento de datos de terceros (SEG-13) | Uso interno con usuarios de GADIT y un aviso breve en el inicio de sesión redactado por el equipo; no se abre a terceros hasta que un asesor legal lo valide | Dueño y asesor legal | Arrancar                         | El texto del aviso y, si aplica, el consentimiento           |
| PEN-12 | Plazo para emitir el Complemento de Pago                     | `gadit.complemento_alerta_dias` vacío: se listan y cuentan los pendientes sin resaltar por antigüedad | Contador             | Cerrar el hito 2                 | Un valor de configuración                                    |
| PEN-13 | Retención de 5 %                                             | El Excel tiene la columna al 5 % pero en 0 en las 12 facturas; el formulario propone 0 y se puede cambiar por factura | Dueño                | Cerrar el hito 2                 | El valor inicial del formulario de facturas                  |
| PEN-14 | Entregas a socios anotadas en ALFREDO y no en GADIT: adelantos de utilidad de $1,000,000 (05/04/2025) y $1,515,526.60 (10/04/2025), «DCTO DE UT» de $227,000 (28/03/2025) y «PAGO PRESTAMO UT» de GPE por $624,500 (08 y 10/04/2025); GADIT filas 906 y 909 sin importe | No se importan; el dueño los captura como entregas de utilidad (RF-54) si cuentan | Dueño                | Importar                         | Hasta cuatro entregas; cambia lo pendiente de cada socio     |
| PEN-15 | Beneficiario de las 29 comisiones históricas ($2,858,795.73) | Quedan sin beneficiario; el formulario lo exige solo si alguien edita la partida (RN-10, IMP-11) | Dueño                | Sin fecha (después del arranque) | Captura por partida                                          |
| PEN-16 | Quiénes administran el sistema                               | Al menos dos usuarios admin (el dueño y una persona de confianza) para no quedar bloqueados (RN-23) | Dueño                | Cerrar el hito 0                 | Alta de usuarios                                             |
| PEN-17 | ¿Se necesita la semana de obra (el SEM de 2024, con la semana 1 el 09/09/2024)? | No: el sistema muestra solo la semana ISO (RN-11)            | Dueño                | Cerrar el hito 2                 | Una columna calculada desde proyectos.fecha\_inicio en listados y reportes; la base no cambia |
| PEN-18 | Gastos que solo están en CAJA y ALFREDO (Apéndice F: $551,521.57 del 16/04 al 30/07/2025, $630,873.81 de reembolsos a socios sin detalle y $491,000.00 anteriores) | No se importan; el dueño revisa la lista con el equipo y lo que confirme se captura con su fecha | Dueño                | Arrancar                         | Captura con RF-30: baja la utilidad histórica y cambia el reparto pendiente; no mueve saldos (DEC-19) |
| PEN-19 | Método de reparto: ¿lo entregado se resta a cada socio (sistema) o el porcentaje se aplica a lo que queda después de entregar (Excel, D68:D71)? | El del sistema (RN-19): a cada socio le toca su porcentaje de la utilidad antes de repartir, menos lo que ya recibió | Dueño y socios       | Cerrar el hito 2                 | La fórmula de v\_reparto\_utilidad, RN-19 y el ejemplo B8    |
| PEN-20 | Deudas vigentes en la fecha de corte: préstamos (PRESTAMOS no tiene los de $400,000, $55,000 y $200,000 de 2025) y compras a crédito (PROVEEDORES está desactualizada) | El dueño entrega la lista el día T y se capturan con RF-50 y RF-51 antes de abrir el sistema; no se importan | Dueño                | Arrancar                         | Solo captura                                                 |

## 22. Apéndices

Los apéndices son normativos: si un número de aquí no coincide con lo que calcula el sistema, el error está en el sistema. Los ejemplos del Apéndice B se convierten en pruebas automáticas (sección 18) y sus valores se comprobaron contra gadit\_schema.sql v1.1 cargado con las semillas y la importación del Excel.

### Apéndice A. Mapeo de etiquetas del Excel a categorías

Cada partida de las hojas GADIT y GADIT 2 trae una etiqueta de categoría escrita a mano. El importador (sección 15) las traduce con la clave `categorias` de config/importacion.php. En el Excel hay 159 etiquetas distintas (153 en GADIT y 6 más en GADIT 2): 137 tienen regla y 22 no, y estas últimas van a VARIOS. Las 964 partidas importadas suman 16,088,222.19.

| #    | Categoría                         | Grupo             | Etiquetas del Excel (partidas)                               | Partidas |     Importe (MXN) |
| ---- | --------------------------------- | ----------------- | ------------------------------------------------------------ | -------: | ----------------: |
| 1    | CONCRETO                          | obra              | CONCRETO (24), AGREGADOS CONCRETO                            |       25 |      3,388,647.83 |
| 2    | MAQUINARIA                        | obra              | MAQUINARIA (7), MAQUINA PELADORA, CORTADORA (2)              |       10 |      1,084,302.00 |
| 3    | MATERIALES DE OBRA                | obra              | BASE (5), GONDOLAS (3), MALLA (6), MONTENES (3), PINTURA (4), PASAJUNTAS, VIELETAS, CIMBRA (4), CURACRETO (6), SILLETAS (4), SILLETA, TUBOS (3), EPOXINE (4), ESMALTE, DESMOLDANTE, CEMENTO, SIKAFLU, GAVIONES, BLOCK, SILICONES, TAPA, TAPONES (2), CANASTILLA, ENCADENAMIENTO, MATERIAL, BOTON, CUÑA, VIBORERAS (2), BIDONES |       63 |      1,433,175.24 |
| 4    | FERRETERIA                        | obra              | FERRETERIA (85), ACCESORIOS, GRASA (2), BROCHAS, HERRAMIENTA, EXTENSION, LAMPARA, CARRETILLAS, GENERADOR (2), MOTOBOMBA, BOMBA, HIDROLAVADORA |       98 |        351,680.39 |
| 5    | LABORATORIO                       | obra              | LABORATORIO (3)                                              |        3 |        137,014.40 |
| 6    | TOPOGRAFIA                        | obra              | TOPOGRAFIA (6), TOPOGRAFO, NIVEL (2), PLANOS                 |       10 |        117,602.46 |
| 7    | AGUA Y PIPAS                      | obra              | AGUA PIPA (11), PIPA (2), AGUA (4)                           |       17 |        143,195.00 |
| 8    | FLETES Y REMOLQUES                | obra              | FLETE (3), REMOLQUE (2), ENVIO, CAMIONES SOLARIG (5)         |       11 |        254,724.00 |
| 9    | EQUIPO DE SEGURIDAD               | obra              | EQUIPO DE SEGURIDAD (18), EQUIPO SEGURIDAD (5), SEGURISTA (2), DC3 (5), ALCOHOLIMENTRO, TERMOMETRO, GUANTES, CERTIFICADOS |       34 |         71,975.69 |
| 10   | SERVICIOS DE OBRA                 | obra              | BAÑOS (7), TRABAJOS NOCTURNOS, TRAB NOC (13), HERRERO (6), HERRERIA, FONTANERO, OPERADOR, SUPERVISOR, AUXILIAR, TALACHA (2), PARCHAR, REPARACION (3) |       38 |        209,832.00 |
| 11   | NOMINA                            | nomina            | NOMINA (52)                                                  |       52 |      1,056,535.22 |
| 12   | FINIQUITOS                        | nomina            | FINIQUITO (6)                                                |        6 |         18,802.14 |
| 13   | SAT                               | impuestos         | SAT (12)                                                     |       12 |        562,040.00 |
| 14   | IMSS                              | impuestos         | IMSS (16)                                                    |       16 |        304,555.17 |
| 15   | ISR                               | impuestos         | ISR (2)                                                      |        2 |         29,596.00 |
| 16   | ISN                               | impuestos         | ISN (16)                                                     |       16 |         27,451.00 |
| 17   | IVA                               | impuestos         | — (sin partidas en el Excel)                                 |        0 |              0.00 |
| 18   | COMISIONES                        | comisiones        | COMISION (20), COMISIONES (2)                                |       22 |      2,620,910.48 |
| 19   | COMISION INTERMEDIARIO            | comisiones        | ALFREDO (7)                                                  |        7 |        237,885.25 |
| 20   | UTILIDAD                          | reparto\_utilidad | UTILIDAD (4)                                                 |        4 |      1,800,000.00 |
| 21   | INTERESES                         | financiamiento    | INTERESES (7)                                                |        7 |        178,500.00 |
| 22   | TRAMITES Y COMISIONES DE PRESTAMO | financiamiento    | TRAMITE, APERTURA DE CUENTA                                  |        2 |         42,000.00 |
| 23   | COMBUSTIBLE                       | logistica         | COMBUSTIBLE (244), DIESEL                                    |      245 |        496,377.49 |
| 24   | CASETAS                           | logistica         | CASETA (32), CASETAS (2)                                     |       34 |         77,939.00 |
| 25   | VEHICULOS                         | logistica         | VEHICULO (15), VEHICULOS (7), CAMIONETAS (7), LLANTAS (5), BALATAS, ACEITE (5), LUBRICANTE (2), AUTOZONE (2), LAVADO CAMIONETA, ESTACIONAMIENTO (3), LICENCIA |       49 |        219,359.09 |
| 26   | VIATICOS                          | logistica         | VIATICOS (9), HOSPEDAJE (5), ALIMENTOS (5), COMIDAS, DESAYUNO, CANASTA (2), SALCHICHAS (3), POSADA, TRANSPORTE, AVIONES |       29 |        166,715.96 |
| 27   | ADMINISTRACION                    | administracion    | ADMON (15), OFICINA (20), PAPELERIA (18), TINTAS, LIMPIEZA (2), SERVICIOS, FARMACIA |       58 |        215,462.11 |
| 28   | SISTEMAS Y SERVICIOS              | administracion    | WEB, CONTPAQ (6), INTERNET (4), TELEFONO (2), TARJETAS (3), LAP, CIF |       18 |        144,193.14 |
| 29   | FIANZAS Y SEGUROS                 | administracion    | FIANZA, FIANZAS, SEGURO (2)                                  |        4 |         48,981.73 |
| 30   | RENTAS                            | administracion    | RENTA (12), RENTAS                                           |       13 |        145,082.67 |
| 31   | APOYOS                            | otros             | APOYO (22)                                                   |       22 |        152,500.00 |
| 32   | VARIOS                            | otros             | VARIOS (5) y las 22 etiquetas sin regla: BAJIO (3), COMERCIAL (3), UICEM (3), BONO (2), OVED (2), SANTIAGO (2), TOÑO (2), ALEJANDRA, ALEJANDRO CASTELLANOS, ALVARO, BOTELLAS, CARBONES, CM 201, DEPOSITO BB, DEUDA COLIN, EXTRA, GAS, GTOS VARIOS, GUARDAGANADO, MALU, PEDRO COLIN, PRESTAMO (1 cada una) |       37 |        351,186.73 |
|      | **Total**                         |                   |                                                              |  **964** | **16,088,222.19** |

Las cifras entre paréntesis son partidas; sin cifra, una. Las 32 partidas de etiquetas sin regla llevan en observaciones «\[cat. original: X\]», de modo que nada se pierde y la búsqueda por texto las encuentra. Una etiqueta nueva en un Excel futuro se agrega a la tabla de config/importacion.php; mientras tanto va a VARIOS y el reporte de la importación la lista.

**Revisión con el dueño después de importar.** Nueve partidas de VARIOS (por 48,633.55) tienen una etiqueta o un concepto que sugiere otra cosa. El importador no las reclasifica porque la decisión es del dueño; se corrigen con la edición normal del gasto. Si el dueño confirma que son un traspaso o un préstamo, se elimina el gasto (borrado lógico) y se captura el traspaso o el préstamo. Las cifras de control de la sección 15 se verifican antes de reclasificar.

| Hoja y No    | Fecha      | Concepto                    |   Importe | Qué revisar                                                  |
| ------------ | ---------- | --------------------------- | --------: | ------------------------------------------------------------ |
| GADIT 136    | 14/10/2024 | PEDRO COLIN DEUDA CUERVOS   | 14,000.00 | ¿Pago de una deuda? Pasar a cuenta por pagar si sigue abierta |
| GADIT 224    | 24/10/2024 | DEPOSITO APERTURA CUENTA BB |  1,000.00 | ¿Traspaso de Caja GPE al banco?                              |
| GADIT 227    | 24/10/2024 | PAQUETE DE SERVICIOS        |    168.40 | Comisión bancaria: ADMINISTRACION                            |
| GADIT 271    | 01/11/2024 | COMISION MENSUAL BAJIO      |    580.00 | Comisión bancaria: ADMINISTRACION                            |
| GADIT 305    | 08/11/2024 | COMISION ANUAL CTA GPE BB   |    310.00 | Comisión bancaria: ADMINISTRACION                            |
| GADIT 434    | 30/11/2024 | CAJA CHICA OVED             | 10,000.00 | ¿Traspaso a una caja chica de obra?                          |
| GADIT 438    | 30/11/2024 | CAJA CHICA ALEJANDRA        |  5,000.00 | ¿Traspaso a la caja de Alejandra?                            |
| GADIT 894    | 28/03/2025 | ADEUDO ALVARO               | 13,560.15 | ¿Adeudo o préstamo de un socio?                              |
| GADIT 2 · 41 | 27/04/2025 | DANIEL PEREN PRESTAMO       |  4,015.00 | ¿Préstamo?                                                   |

### Apéndice B. Ejemplos resueltos

Cada ejemplo da los datos de entrada y el resultado exacto. Todo importe se redondea a 2 decimales con medio hacia arriba (RN-01) antes de sumarse. Los ejemplos B1 a B11 son pruebas de la sección 18.

**B1. Factura A5001 (RN-02).** Importe 5,029,866.82; retención 0 %; IVA 16 %.

| Concepto  | Cálculo                            |    Resultado |
| --------- | ---------------------------------- | -----------: |
| Retención | 5,029,866.82 × 0 %                 |         0.00 |
| Subtotal  | 5,029,866.82 − 0.00                | 5,029,866.82 |
| IVA       | 5,029,866.82 × 16 % = 804,778.6912 |   804,778.69 |
| Total     | 5,029,866.82 + 804,778.69          | 5,834,645.51 |

**B2. Factura con retención (RN-02).** Importe 100,000.00; retención 5 %; IVA 16 %. La retención se resta antes de calcular el IVA.

| Concepto  | Cálculo               |  Resultado |
| --------- | --------------------- | ---------: |
| Retención | 100,000.00 × 5 %      |   5,000.00 |
| Subtotal  | 100,000.00 − 5,000.00 |  95,000.00 |
| IVA       | 95,000.00 × 16 %      |  15,200.00 |
| Total     | 95,000.00 + 15,200.00 | 110,200.00 |

**B3. Redondeo (RN-01).** Medio hacia arriba, igual que ROUND de MySQL; los negativos se alejan de cero.

| Valor sin redondear                  |  Resultado |
| ------------------------------------ | ---------: |
| 2.675                                |       2.68 |
| 1.005                                |       1.01 |
| 0.005                                |       0.01 |
| −0.005                               |      −0.01 |
| 348,316.296 (IVA del cobro de A5005) | 348,316.30 |
| 804,778.6912 (IVA de A5001)          | 804,778.69 |

**B4. IVA de cobros parciales sin perder centavos (RN-06).** Factura de 100.05 (IVA 16 % = 16.008, se guarda 16.01) con dos cobros de 50.02 y 50.03.

| Paso                                  | Cálculo                                           | IVA propuesto |
| ------------------------------------- | ------------------------------------------------- | ------------: |
| Cobro 1 de 50.02                      | ROUND(50.02 × 16 / 100, 2) = ROUND(8.0032, 2)     |          8.00 |
| Cobro 2 de 50.03 (liquida la factura) | IVA de la factura − IVA ya cobrado = 16.01 − 8.00 |          8.01 |

Si el segundo cobro usara la fórmula normal, ROUND(8.0048, 2) = 8.00 y sobraría 0.01 de IVA sin cobrar. Resultado: IVA cobrado 16.01, saldo 0.00, situación cobrada.

**B5. Saldo de una cuenta (RN-14).** Saldo inicial contado 100,000.00 al 31/03/2025.

| Fecha      | Movimiento                        |     Entra |      Sale | Suma al saldo                                 |
| ---------- | --------------------------------- | --------: | --------: | --------------------------------------------- |
| 31/03/2025 | Gasto de 5,000.00                 |           |  5,000.00 | No: la fecha no es posterior al saldo inicial |
| 02/04/2025 | Cobro de 20,000.00 + IVA 3,200.00 | 23,200.00 |           | Sí                                            |
| 03/04/2025 | Gasto de 8,000.50                 |           |  8,000.50 | Sí                                            |
| 04/04/2025 | Traspaso saliente de 10,000.00    |           | 10,000.00 | Sí                                            |

Entradas 23,200.00; salidas 18,000.50; saldo = 100,000.00 + 23,200.00 − 18,000.50 = **105,199.50**.

**B6. Cuenta por pagar (RN-16).** Importe 50,000.00 + IVA 8,000.00 = total 58,000.00.

| Paso                                             |    Pagado |     Saldo | Situación                                                    |
| ------------------------------------------------ | --------: | --------: | ------------------------------------------------------------ |
| Sin pagos                                        |      0.00 | 58,000.00 | vigente                                                      |
| Pagos de 20,000.00 y 30,000.00                   | 50,000.00 |  8,000.00 | vigente                                                      |
| Otro pago de 8,000.00                            | 58,000.00 |      0.00 | pagada                                                       |
| Intento de pago de 8,000.01 en lugar de 8,000.00 | rechazado |  8,000.00 | «El pago excede el saldo de la cuenta por pagar ($8,000.00)» |

**B7. Utilidad de caja y qué no se resta (RN-18).** Utilidad de caja = cobrado sin IVA + IVA cobrado − gastos − saldo de cuentas por pagar.

| Caso                                | Cuentas por pagar (saldo) | Préstamo recibido | Utilidad de caja |
| ----------------------------------- | ------------------------: | ----------------: | ---------------: |
| a) Datos del Excel                  |                      0.00 |              0.00 |     3,387,349.65 |
| b) Con una cuenta por pagar abierta |                 50,400.00 |              0.00 |     3,336,949.65 |
| c) Además un préstamo de 100,000.00 |                 50,400.00 |        100,000.00 |     3,336,949.65 |

En el caso a) los datos son: cobrado sin IVA 16,789,286.07; IVA cobrado 2,686,285.77; gastos 16,088,222.19. El caso c) no cambia porque el préstamo no se resta: el dinero prestado que se gastó ya está en gastos. El Excel muestra 3,573,582.70 porque su fórmula resta solo los gastos de la hoja GADIT; al restar también los 186,233.05 de GADIT 2 el resultado es 3,387,349.65 (sección 4).

**B8. Reparto de utilidad (RN-19).** Utilidad antes del reparto = utilidad de caja 3,387,349.65 + reparto ya pagado 1,800,000.00 = 5,187,349.65. Esquema 30 / 25 / 22.5 / 22.5.

| Socio     |      % | Le corresponde | Ya entregado | Por entregar |
| --------- | -----: | -------------: | -----------: | -----------: |
| Alejandra |  30.00 |   1,556,204.90 |   450,000.00 | 1,106,204.90 |
| Javier    |  25.00 |   1,296,837.41 |   450,000.00 |   846,837.41 |
| Álvaro    |  22.50 |   1,167,153.67 |   450,000.00 |   717,153.67 |
| GPE       |  22.50 |   1,167,153.67 |   450,000.00 |   717,153.67 |
| Suma      | 100.00 |   5,187,349.65 | 1,800,000.00 | 3,387,349.65 |

Cada «Le corresponde» es ROUND(5,187,349.65 × % / 100, 2); por ejemplo, 5,187,349.65 × 30 % = 1,556,204.895 y se guarda 1,556,204.90. Las cuatro filas suman exactamente la utilidad antes del reparto.

**Comparación con el Excel.** CONCENTRADO D68:D71 aplica los porcentajes a la utilidad que queda después de las entregas (3,573,582.70, sin GADIT 2) y no resta lo que recibió cada socio: Alejandra 1,072,074.81, Javier 893,395.68, Álvaro y GPE 804,056.11. Con esa misma utilidad el sistema da por entregar 1,162,074.81, 893,395.68, 759,056.11 y 759,056.11. Las dos columnas suman 3,573,582.71; cambia la asignación porque las cuatro entregas fueron iguales y no proporcionales. El método lo confirma el dueño (PEN-19).

**B9. Traspaso con comisión de intermediario (RN-15).** Traspaso de 100,000.00 de una cuenta a la cuenta intermediaria, con comisión de 2.5 %.

| Cuenta                  |   Entradas |          Salidas | Efecto en el saldo |
| ----------------------- | ---------: | ---------------: | -----------------: |
| Origen                  |       0.00 |       100,000.00 |        −100,000.00 |
| Destino (intermediaria) | 100,000.00 | 2,500.00 (gasto) |         +97,500.00 |

La comisión es ROUND(100,000.00 × 2.5 / 100, 2) = 2,500.00. Se crea en la misma transacción como gasto de categoría COMISION INTERMEDIARIO en la cuenta destino, ligado al traspaso. Si el traspaso se elimina, el gasto se elimina con él.

**B10. Semana ISO (RN-11).** YEARWEEK(fecha, 3): la semana empieza en lunes y la semana 1 es la que contiene el primer jueves del año.

| Fecha      | Día       | semana\_iso | Se muestra como |
| ---------- | --------- | ----------- | --------------- |
| 29/12/2024 | domingo   | 202452      | Sem 52 / 2024   |
| 30/12/2024 | lunes     | 202501      | Sem 01 / 2025   |
| 01/01/2025 | miércoles | 202501      | Sem 01 / 2025   |
| 03/01/2021 | domingo   | 202053      | Sem 53 / 2020   |
| 29/12/2025 | lunes     | 202601      | Sem 01 / 2026   |
| 01/01/2026 | jueves    | 202601      | Sem 01 / 2026   |

**B11. Préstamo (RN-17).** Préstamo recibido de 100,000.00; pagos de capital de 40,000.00 y 25,000.00; intereses de 3,000.00 y 2,000.00.

| Dato              | Cálculo                | Resultado |
| ----------------- | ---------------------- | --------: |
| Capital pagado    | 40,000.00 + 25,000.00  | 65,000.00 |
| Saldo de capital  | 100,000.00 − 65,000.00 | 35,000.00 |
| Intereses pagados | 3,000.00 + 2,000.00    |  5,000.00 |

Los intereses son gastos del grupo financiamiento con el préstamo ligado; el capital no es gasto. Un pago de capital de 35,000.01 se rechaza.

### Apéndice C. Catálogos SAT de forma y método de pago

Las dos listas viven en config/gadit.php (claves `formas_pago` y `metodos_pago`) y alimentan los selectores. La base solo exige dos dígitos (CHECK en facturas y cobros); la lista la valida la aplicación. Antes de la primera entrega, el equipo confirma las claves contra la versión vigente del catálogo c\_FormaPago del SAT, porque el SAT puede cambiarlas.

| Clave | Forma de pago                       |
| ----- | ----------------------------------- |
| 01    | Efectivo                            |
| 02    | Cheque nominativo                   |
| 03    | Transferencia electrónica de fondos |
| 04    | Tarjeta de crédito                  |
| 05    | Monedero electrónico                |
| 06    | Dinero electrónico                  |
| 08    | Vales de despensa                   |
| 12    | Dación en pago                      |
| 13    | Pago por subrogación                |
| 14    | Pago por consignación               |
| 15    | Condonación                         |
| 17    | Compensación                        |
| 23    | Novación                            |
| 24    | Confusión                           |
| 25    | Remisión de deuda                   |
| 26    | Prescripción o caducidad            |
| 27    | A satisfacción del acreedor         |
| 28    | Tarjeta de débito                   |
| 29    | Tarjeta de servicios                |
| 30    | Aplicación de anticipos             |
| 31    | Intermediario pagos                 |
| 99    | Por definir                         |

| Método | Significado                      | Efecto en GADIT                                              |
| ------ | -------------------------------- | ------------------------------------------------------------ |
| PUE    | Pago en una sola exhibición      | forma\_pago de la factura obligatoria y distinta de 99       |
| PPD    | Pago en parcialidades o diferido | forma\_pago de la factura fija en 99 y bloqueada; cada cobro exige complemento\_uuid, y sin él aparece en v\_complementos\_pendientes |

Reglas de uso (RN-03 y RN-04):

- La forma de pago de un cobro es la forma real con que entró el dinero y nunca es 99.
- En facturas y cobros importados del Excel, metodo\_pago, forma\_pago y uuid quedan vacíos.
- Los selectores muestran «clave · descripción» (por ejemplo «03 · Transferencia electrónica de fondos») y ordenan 03 y 01 primero.

### Apéndice D. Validación del dígito verificador de la CLABE

Una CLABE tiene 18 dígitos: 3 de banco, 3 de plaza, 11 de cuenta y 1 verificador. El banco se captura como texto libre (RN-22), así que la aplicación valida solo el largo, que sean dígitos y el verificador.

**Algoritmo.** Con los primeros 17 dígitos:

1. Multiplicar cada dígito por su peso; los pesos se repiten en el orden 3, 7, 1 (dígito 1 × 3, dígito 2 × 7, dígito 3 × 1, dígito 4 × 3, etcétera).
2. Quedarse con el último dígito de cada producto (producto módulo 10).
3. Sumar los 17 resultados.
4. Verificador = (10 − (suma módulo 10)) módulo 10.
5. La CLABE es válida si el verificador calculado es igual al dígito 18.

**Ejemplo paso a paso con 002180001234567896** (los datos son ficticios):

| Posición         | 1    | 2    | 3    | 4    | 5    | 6    | 7    | 8    | 9    | 10   | 11   | 12   | 13   | 14   | 15   | 16   | 17   |
| ---------------- | ---- | ---- | ---- | ---- | ---- | ---- | ---- | ---- | ---- | ---- | ---- | ---- | ---- | ---- | ---- | ---- | ---- |
| Dígito           | 0    | 0    | 2    | 1    | 8    | 0    | 0    | 0    | 1    | 2    | 3    | 4    | 5    | 6    | 7    | 8    | 9    |
| Peso             | 3    | 7    | 1    | 3    | 7    | 1    | 3    | 7    | 1    | 3    | 7    | 1    | 3    | 7    | 1    | 3    | 7    |
| Producto mód. 10 | 0    | 0    | 2    | 3    | 6    | 0    | 0    | 0    | 1    | 6    | 1    | 4    | 5    | 2    | 7    | 4    | 3    |

La suma es 44; 44 módulo 10 = 4; 10 − 4 = 6. El dígito 18 es 6, así que la CLABE es válida.

**Casos de prueba** (todos ficticios):

| Valor capturado        | ¿Válida? | Motivo                                         |
| ---------------------- | -------- | ---------------------------------------------- |
| 002180001234567896     | Sí       | Verificador 6 correcto                         |
| 012180000000000015     | Sí       | Verificador 5 correcto                         |
| 030180012345678907     | Sí       | Verificador 7 correcto                         |
| 0021 8000 1234 5678 96 | Sí       | Se limpian espacios y guiones antes de validar |
| 002180001234567895     | No       | El verificador debería ser 6                   |
| 00218000123456789      | No       | 17 dígitos                                     |
| 0021800012345678960    | No       | 19 dígitos                                     |
| 00218000123456789A     | No       | Contiene una letra                             |

Mensaje de error: «La CLABE no es válida. Revisa los 18 dígitos.» No se indica cuál dígito falló.

**Código de referencia** (app/Support/Clabe.php):

```php
final class Clabe
{
    private const PESOS = [3, 7, 1];

    public static function limpiar(string $valor): string
    {
        return preg_replace('/[\s-]/', '', $valor);
    }

    public static function esValida(string $valor): bool
    {
        $clabe = self::limpiar($valor);

        if (! preg_match('/^\d{18}$/D', $clabe)) {
            return false;
        }

        $suma = 0;
        for ($i = 0; $i < 17; $i++) {
            $suma += ((int) $clabe[$i] * self::PESOS[$i % 3]) % 10;
        }

        return (10 - $suma % 10) % 10 === (int) $clabe[17];
    }

    public static function ultimos4(string $clabe): string
    {
        return substr($clabe, -4);
    }

    public static function enmascarar(string $clabe): string
    {
        return '•••• •••• •••• ' . substr($clabe, -4);
    }
}
```

Se guarda la CLABE limpia (18 dígitos) cifrada, y clabe\_ultimos4 en claro para listas y búsquedas. En pantalla, bitácora, logs y exportaciones solo aparece enmascarada, salvo el botón «Mostrar» para admin y captura (RF-24).

### Apéndice E. Lista de verificación de entrega

Nada se entrega al dueño hasta que cada fila tenga evidencia y firma. «Evidencia» es un enlace, captura o archivo guardado en la carpeta de entrega; «Responsable» es quien prueba y firma. La lista se llena durante el hito 3 y se cierra antes del arranque (sección 19).

| #    | Verificación                                                 | Evidencia                                                    | Responsable        |
| ---- | ------------------------------------------------------------ | ------------------------------------------------------------ | ------------------ |
| E-01 | La integración continua está en verde en la rama de entrega: Pest completo sobre MySQL 8.4, pint, Larastan nivel 6 y cobertura mínima de 80 % (RNF-11) | Enlace a la ejecución y reporte de cobertura                 | Desarrollo         |
| E-02 | gadit\_schema.sql v1.2 y las migraciones producen el mismo esquema en MySQL 8.4, con las 21 tablas y las 13 vistas (RIE-07) | Salida de la comparación de esquemas y de las pruebas de vistas | Desarrollo         |
| E-03 | `php artisan migrate --force` y CatalogosBaseSeeder corren sin errores en un servidor limpio y dejan las personas, cuentas, categorías y el esquema de reparto de arranque | Registro de la ejecución                                     | Desarrollo         |
| E-04 | La matriz de permisos (sección 6) pasa completa: cada rol solo hace lo que su fila permite, incluida la CLABE enmascarada | Reporte de tests/Feature/Permisos                            | Desarrollo         |
| E-05 | El servidor cumple RNF-12: solo los puertos 22 (SSH con llave, sin contraseña y sin root), 80 y 443 abiertos; MySQL solo en 127.0.0.1; APP\_DEBUG=false y expose\_php=Off; certificado TLS con renovación automática; actualizaciones de seguridad del sistema operativo automáticas | Lista de comprobación de RNF-12 firmada                      | Desarrollo         |
| E-06 | Respaldo diario de las 02:00 con copia en un disco externo y aviso por correo si falla; simulacro de restauración en un servidor limpio: la base y los adjuntos quedan completos y se descifra una CLABE con la APP\_KEY respaldada (RNF-10: perder como máximo 24 h y volver a operar en 4 h) | Acta del simulacro con tiempos                               | Desarrollo y dueño |
| E-07 | La APP\_KEY y la contraseña del archivo de respaldo están guardadas aparte en el gestor de contraseñas del dueño (RIE-02, SEG-09) | Confirmación escrita del dueño                               | Dueño              |
| E-08 | Importación real hecha con `gadit:importar-excel`; el reporte coincide con las cifras de control de la sección 15: 964 gastos por 16,088,222.19; contratos 8 por 17,929,419.87; facturas 12 por 17,089,286.07; cobros 11 por 16,789,286.07; utilidad de caja 3,387,349.65 | Reporte de la importación y salida de la conciliación        | Desarrollo         |
| E-09 | Las 8 partidas de VARIOS con etiqueta sugerente (Apéndice A) y las 2 sin importe se revisaron con el dueño | Acta con la decisión de cada una                             | Dueño              |
| E-10 | Los ejemplos B1 a B11 del Apéndice B están como pruebas y pasan | Reporte de Pest con los casos B1 a B11                       | Desarrollo         |
| E-11 | Navegadores de RNF-06: las dos últimas versiones de Chrome, Edge, Firefox y Safari, Chrome en Android y Safari en iOS, y pantalla de 360 px de ancho; se probaron login, tablero, captura de gasto, factura y cobro | Tabla de resultados por navegador con capturas               | Desarrollo         |
| E-12 | Accesibilidad de RNF-07: Lighthouse con 90 o más en accesibilidad en login, tablero, gastos, facturas y cobros, y recorrido solo con teclado de la captura de un gasto | Informes de Lighthouse y nota del recorrido                  | Desarrollo         |
| E-13 | La captura de comprobante con la cámara del celular funciona en Android y en iOS, y el archivo queda en el disco privado | Capturas y registro del adjunto                              | Desarrollo         |
| E-14 | Rendimiento RNF-01 a RNF-03 con 100,000 gastos: pantallas p95 de 1 s y 12 consultas como máximo; tablero y reportes de 2 s y 20 consultas; exportación de 20,000 filas en 30 s y 256 MB | Reporte del grupo rendimiento de Pest                        | Desarrollo         |
| E-15 | Los 8 escenarios de aceptación de la sección 18 se ejecutaron con el dueño y los usuarios, todos aprobados | Acta de aceptación firmada                                   | Dueño              |
| E-16 | Guía rápida de una página por rol (admin, captura, consulta) entregada | Archivos PDF                                                 | Desarrollo         |
| E-17 | Capacitación dada a cada rol y primer cierre de semana hecho con el sistema | Lista de asistentes                                          | Desarrollo y dueño |
| E-18 | Existe el administrador del dueño (creado con gadit:crear-admin), UsuariosDemoSeeder no se ejecutó en producción y cada usuario tiene su propia contraseña | Captura de la lista de usuarios                              | Dueño              |
| E-19 | Los pendientes PEN-01 a PEN-20 tienen respuesta anotada o la decisión por defecto está aceptada por escrito | Sección 21 actualizada                                       | Dueño              |
| E-20 | Las 10 condiciones de la definición de hecho (sección 19) están cumplidas | Sección 19 marcada punto por punto                           | Desarrollo         |
| E-21 | El dueño revisó el Apéndice F y respondió PEN-18, PEN-19 y PEN-20 | Acta de la revisión con su fecha y la sección 21 actualizada | Dueño              |

**Firmas de entrega**

| Rol                                 | Nombre | Fecha | Firma |
| ----------------------------------- | ------ | ----- | ----- |
| Dueño del negocio                   |        |       |       |
| Líder del equipo de desarrollo      |        |       |       |
| Persona que ejecutó la capacitación |        |       |       |

### Apéndice F. Trazabilidad del Excel al sistema

Cada elemento del libro tiene aquí su destino, y las listas F2 a F5 son lo que el dueño debe revisar antes de arrancar. Se verificó el 15/10/2026 contra el archivo entregado: 11 hojas visibles y 425 fórmulas, sin macros, validaciones de datos, formatos condicionales, comentarios ni vínculos externos; los únicos nombres definidos son los filtros automáticos. Con `gadit_schema.sql`, `gadit_semillas.sql` y los 964 gastos cargados en MariaDB 10.11, las vistas reprodujeron todas las cifras de control de la sección 15 y, sin GADIT 2, la utilidad del Excel ($3,573,582.70).

**F1. Matriz de trazabilidad**

| Elemento del Excel                                 | Qué hace en el Excel                                         | En el sistema                                                | Estado                                                       |
| -------------------------------------------------- | ------------------------------------------------------------ | ------------------------------------------------------------ | ------------------------------------------------------------ |
| GADIT y GADIT 2, A `No`                            | Número consecutivo de la partida                             | `numero_origen` y unicidad por hoja (IMP-12, IMP-13)         | Igual                                                        |
| B `FECHA`                                          | Día del pago                                                 | `gastos.fecha` (RN-10, RN-25)                                | Igual; 9 fechas corridas (F3)                                |
| C `SEM`                                            | Semana de obra en 2024 (dos dígitos) y SSAA en 2025, como texto | `semana_iso` calculada (RN-11)                               | Cambia (hallazgo 9, PEN-17)                                  |
| D `IMPORTE C/IVA`                                  | Importe pagado; 9 celdas son sumas escritas (D46, D228, D229, D283, D287, D549, D758, D799 y D882) | `importe_con_iva` con el valor calculado (IMP-02, IMP-04)    | Igual                                                        |
| E `CONCEPTO`                                       | Texto libre                                                  | `concepto`                                                   | Igual                                                        |
| F `CATEGORIA`                                      | 159 etiquetas libres                                         | 32 categorías en 9 grupos; lo que no tiene regla va a VARIOS con nota (IMP-09, Apéndice A) | Cambia: catálogo cerrado                                     |
| G `RESPONSABLE`                                    | Cuenta y origen del dinero (SP, PRE, CAJA)                   | `cuenta_id` y nota del valor original (IMP-10)               | Igual, con nota                                              |
| H `OBSERVACIONES`                                  | Notas (11 partidas)                                          | `observaciones`                                              | Igual                                                        |
| GADIT J85:K87                                      | Leyenda de colores para «revisado» y «falta comprobante», sin aplicar a ninguna fila | `estatus_revision` (RN-12); lo histórico entra `sin_revisar` (IMP-12) | Mejora                                                       |
| GADIT D976 y GADIT 2 D78                           | Suma de la hoja                                              | Autocomprobación (IMP-19) y `gastos_total`                   | Igual                                                        |
| Filtros automáticos (GADIT, GADIT 2 y PROVEEDORES) | Filtrar la tabla                                             | Filtros de RF-01 y RF-31                                     | Mejora                                                       |
| CONCENTRADO C6:F18                                 | Contratos: importe, IVA = importe × 0.16 y total             | `contratos` y `v_contratos_resumen` (RN-08)                  | Igual                                                        |
| CONCENTRADO C21:M39                                | Facturas: retención (H × 0 %), subtotal, IVA de 16 %, total y vencimiento | `facturas` y `v_facturas` (RN-02, RN-07)                     | Igual                                                        |
| CONCENTRADO C42:G59                                | Cobros: IVA = importe × 0.16 por cobro; F59 suma el IVA      | `cobros` (RN-05, RN-06), IVA redondeado por cobro            | Igual; el IVA difiere 0.0012                                 |
| CONCENTRADO C61:D66                                | Utilidad = cobrado + IVA − gastos de GADIT − proveedores (0 fijo) − PRESTAMOS!J22 | `utilidad_caja` (RN-18, B7)                                  | Cambia: suma GADIT 2 y las cuentas por pagar; el préstamo es informativo |
| CONCENTRADO J61:K67                                | «Dónde está el dinero»: saldos casi todos escritos a mano; la suma omite K66:K67 | `v_saldo_cuentas` desde el saldo contado (RN-14, DEC-19)     | Cambia (hallazgos 4 y 16)                                    |
| CONCENTRADO D68:D71                                | Reparto: porcentaje sobre la utilidad que queda              | `v_reparto_utilidad` (RN-19, B8)                             | Cambia (hallazgo 14, PEN-19)                                 |
| CONCENTRADO C68, C70, D75:D79, F68 y F71           | Cálculos de trabajo con cifras a mano                        | No se migran                                                 | Fuera (hallazgos 5 y 14)                                     |
| CONCENTRADO I7:L18 y Hoja1                         | Cuenta de Peren: factor de 80 %, no ejecutado y deductivas   | `contrato_ajustes` capturados a mano (RF-41)                 | Fuera del cálculo (PEN-02)                                   |
| PROVEEDORES                                        | Compras a crédito, abonos, fecha de pago, estatus y por pagar | `cuentas_por_pagar` y gastos de pago (RN-16, RF-50)          | Igual desde el corte (PEN-20)                                |
| PRESTAMOS                                          | Préstamos con la tasa escrita en observaciones, abonos y por pagar | `prestamos` (`tasa_pct`, `tasa_periodo`), `prestamo_pagos` e intereses como gasto (RN-17, RF-51) | Igual desde el corte (PEN-04, PEN-20)                        |
| ALFREDO                                            | Envíos, efectivo entregado, depósitos y comisiones           | Cuenta `intermediario` y traspasos con comisión (RN-15, RF-52) | Igual desde el corte                                         |
| CAJA                                               | Caja de GPE en bloques con saldo arriba                      | Cuenta Caja GPE y su estado de cuenta (RF-53)                | Igual desde el corte (PEN-18)                                |
| CAJA Q26:R37                                       | Reparto 65 / 20 / 15 del remanente de una factura de Solarig | Esquemas por proyecto (RN-19, sin uso en v1)                 | Pendiente (PEN-10)                                           |
| ALVARO, ALEJANDRA y GPE INGRESOS                   | Préstamos, transferencias y efectivo recibido y gastado      | `prestamos` y `traspasos`; los gastos ya están en GADIT      | Igual desde el corte                                         |
| GPE INGRESOS K:N                                   | Notas personales (ahorro y cuenta)                           | No aplica                                                    | Fuera                                                        |
| Formatos                                           | Miles con 2 decimales; fecha mm-dd-yy                        | RF-82 (dd/mm/aaaa)                                           | Cambia                                                       |

**F2. Gastos que solo están en CAJA, ALFREDO o ALEJANDRA INGRESOS** (hallazgo 11, PEN-18)

| Origen                                                       | Partidas |      Importe | Qué son                                                      |
| ------------------------------------------------------------ | -------: | -----------: | ------------------------------------------------------------ |
| CAJA (tercer bloque) y ALFREDO (segundo bloque), del 16/04 al 30/07/2025 |       55 |   551,521.57 | ISR, IVA e ISN 279,448.00; Toño Sistemas 76,450.00; comisiones 71,476.87; IMSS e INFONAVIT 60,846.22; intereses 13,650.00; casetas, CONTPAQ, gasolina, correos, Tacoma, viajes y laptop 49,650.48 |
| ALFREDO H21, H28, H29 y H36 (CAJA M32 repite H21)            |        4 |   630,873.81 | Reembolsos de gastos de socios sin detalle: GTOS ALE JAVI 434,405.34; REPOSICIONES GTOS JAVI 103,433.05; JAVIER GTOS 82,800.00; ALVARO GTOS 10,235.42 |
| CAJA H11 y H13                                               |        2 |    60,000.00 | Nóminas del 10 al 16 y del 17 al 23 de marzo de 2025         |
| ALEJANDRA INGRESOS H12                                       |        1 |   431,000.00 | MAQUINARIA del 21/11/2024; GADIT tiene 420,750.00 de MAQUINARIA el 19/11 y PROVEEDORES fila 7 una compra de 431,000.00 pagada el 19/11 |
| Total                                                        |       62 | 1,673,395.38 |                                                              |

Cómo se obtuvo: cada salida de los libros de caja se buscó en GADIT y GADIT 2 por importe exacto, con 20 días de tolerancia y con 35 para hallar fechas corridas. No cuentan traspasos, pagos de capital, entregas a socios ni el efectivo que reparte Alfredo, y las comisiones que aparecen en CAJA y en ALFREDO se cuentan una vez. Es una lista de candidatos: el dueño confirma cada uno.

**F3. Fechas con un mes de diferencia** (hallazgo 12)

| GADIT No | Fecha en GADIT | Libro y celda | Fecha en el libro |   Importe | Concepto         |
| -------- | -------------- | ------------- | ----------------- | --------: | ---------------- |
| 776      | 10/02/2025     | CAJA A30      | 10/01/2025        | 27,213.28 | NOMINA           |
| 788      | 13/02/2025     | CAJA A31      | 13/01/2025        |  1,750.08 | GASOLINA         |
| 789      | 14/02/2025     | CAJA A32      | 14/01/2025        |  3,423.00 | SERVICIO TACOMA  |
| 798      | 16/02/2025     | CAJA A34      | 16/01/2025        |  1,863.28 | GASOLINA         |
| 800      | 17/02/2025     | CAJA A35      | 17/01/2025        | 13,987.56 | NOMINA (29,572)  |
| 672      | 18/01/2025     | ALFREDO A48   | 18/02/2025        |  7,692.31 | COMISION ALFREDO |
| 731      | 27/01/2025     | ALFREDO A51   | 27/02/2025        |  3,846.18 | COMISION ALFREDO |
| 732      | 27/01/2025     | ALFREDO A53   | 27/02/2025        | 11,025.64 | COMISION ALFREDO |
| 735      | 28/01/2025     | ALFREDO A57   | 28/02/2025        |  1,571.51 | COMISION ALFREDO |

**F4. Grupos de partidas idénticas** (hallazgo 13; todas de GADIT)

| Fecha      |   Importe | Concepto            | Responsable | No         | De más |
| ---------- | --------: | ------------------- | ----------- | ---------- | -----: |
| 21/09/2024 |    300.00 | GASOLINA            | ALEJANDRA   | 18, 19     |      1 |
| 23/09/2024 |  1,909.36 | EQUIPO DE SEGURIDAD | ALEJANDRA   | 31, 32, 33 |      2 |
| 23/09/2024 |  1,792.20 | EQUIPO DE SEGURIDAD | ALEJANDRA   | 38, 39, 40 |      2 |
| 26/09/2024 |    129.99 | OFFICE MAC          | ALEJANDRA   | 50, 51     |      1 |
| 30/09/2024 |    500.00 | GASOLINA            | ALEJANDRA   | 53, 54     |      1 |
| 05/10/2024 |  5,012.00 | TOPOGRAFO           | ALEJANDRA   | 75, 77     |      1 |
| 14/10/2024 |  4,980.50 | HOME DEPOT          | ALEJANDRA   | 126, 145   |      1 |
| 22/10/2024 |  1,040.08 | GASOLINA            | ALEJANDRA   | 205, 210   |      1 |
| 29/10/2024 |    160.00 | CASETA              | GPE         | 250, 251   |      1 |
| 19/11/2024 |    162.00 | CASETA              | ALEJANDRA   | 352, 357   |      1 |
| 19/11/2024 |    191.00 | CASETA              | ALEJANDRA   | 353, 356   |      1 |
| 19/11/2024 |    160.00 | CASETA              | ALEJANDRA   | 354, 355   |      1 |
| 10/12/2024 |  1,000.00 | GASOLINA            | ALEJANDRA   | 502, 503   |      1 |
| 10/12/2024 |     53.00 | CASETA              | ALEJANDRA   | 504 a 508  |      4 |
| 11/12/2024 |     53.00 | CASETA              | ALEJANDRA   | 510, 511   |      1 |
| 25/02/2025 |  5,012.00 | CAMIONES SOLARIG    | ALEJANDRA   | 828, 829   |      1 |
| 28/02/2025 |  1,000.00 | GASOLINA            | ALEJANDRA   | 850, 851   |      1 |
| Total      | 27,315.69 |                     |             |            |     22 |

**F5. Deudas fuera de sus hojas** (hallazgo 15, PEN-20)

| Deuda                             | Dónde aparece                                                | Qué falta                                                    |
| --------------------------------- | ------------------------------------------------------------ | ------------------------------------------------------------ |
| Préstamo de $400,000 (07/02/2025) | GPE INGRESOS G6; interés de 3 % en H31 ($12,000)             | No está en PRESTAMOS; ALFREDO C54 registra «GPE (PAGO PRESTAMO)» por $400,000 el 27/02/2025 |
| Préstamo de $55,000 al 3 %        | CAJA fila 19: interés de $1,650 el 16/04/2025                | No está en PRESTAMOS; no hay fecha de entrada ni pago        |
| Préstamo de $200,000 al 3 %       | CAJA L35 (entra el 15/06/2025); intereses de $6,000 el 15/06 y el 15/07 | No está en PRESTAMOS; sin pago registrado al 15/07/2025      |
| Pagos marcados como préstamo      | ALFREDO C49 «EFECTIVO JAVI (PAGO PRESTAMO)» $300,000 (22/02/2025) y C78 a C80 «GPE (PAGO PRESTAMO UT)» $624,500 (abril de 2025) | No se sabe a qué préstamo corresponden                       |
| MALU, $15,000                     | PROVEEDORES fila 5, sin abono                                | GADIT No 748 «MALU ÙLTIMO PAGO» por $15,000 el 31/01/2025    |
| BOLAÑOS, $25,000                  | PROVEEDORES fila 25, pendiente                               | GADIT No 802 «APOYO BOLAÑOS» por $25,000 el 17/02/2025 puede ser el pago |
| LLANTAS GUZMAN, $20,400           | PROVEEDORES fila 34: por pagar $10,400 escrito a mano        | Falta registrar el abono de $10,000                          |