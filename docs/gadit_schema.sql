-- =====================================================================
--  GADIT · Modelo de datos v1.1 (reemplazo de Gastos_GOG.xlsx)
--  v1.1: cuentas.saldo_inicial / fecha_saldo_inicial, v_saldo_cuentas con saldo inicial,
--        índice único uq_gastos_origen (importación repetible)
--  15/10/2026: solo comentarios corregidos tras verificar el Excel (sin cambios de estructura).
--  Motor: MySQL 8.4 LTS en producción (TDD DEC-01) / MariaDB 10.6+ · InnoDB · utf8mb4.
--         Validado en MariaDB 10.11; la carga en MySQL 8.4 es la primera tarea del hito 0 (RIE-07).
--  Convenciones: dinero DECIMAL(14,2) · porcentajes DECIMAL(5,2) (16.00 = 16 %)
--                ids BIGINT UNSIGNED (compatibles con Laravel) · timestamps automáticos
--                borrado lógico (deleted_at) en gastos, cobros, traspasos, prestamos, prestamo_pagos y
--                cuentas_por_pagar. Las facturas no se borran: se cancelan (estatus = 'cancelada').
--                Todas las vistas ya filtran deleted_at IS NULL.
--                Datos fiscales (uuid, metodo_pago, forma_pago, complementos) quedan NULL en lo histórico:
--                el Excel no los tenía.
--
--  DE LA HOJA A LA TABLA
--    CONCENTRADO · CONTRATO    -> contratos (+ contrato_ajustes)
--    CONCENTRADO · FACTURADO   -> facturas
--    CONCENTRADO · COBRADO     -> cobros
--    CONCENTRADO · UTILIDAD    -> vistas v_resumen_general y v_reparto_utilidad
--    GADIT, GADIT 2            -> gastos (+ categorias, cuentas)
--    PROVEEDORES               -> proveedores + cuentas_por_pagar
--    PRESTAMOS                 -> prestamos + prestamo_pagos
--    ALFREDO, CAJA, *INGRESOS  -> cuentas + traspasos (el saldo lo calcula v_saldo_cuentas).
--                                 CAJA es la caja de efectivo de GPE; GPE INGRESOS es dinero prestado
--                                 que GPE administró (sus gastos son los PRE GPE de GADIT). TDD, Apéndice F.
--    Hoja1 (borrador Peren)    -> contrato_ajustes (reglas por confirmar con el dueño)
--    (nuevo, no existía)       -> datos_bancarios, campos SAT (uuid, complementos), v_complementos_pendientes
--
--  FASES (orden sugerido para construir las pantallas CRUD)
--    1 Núcleo:  clientes, proyectos, contratos, facturas, cobros, categorias, cuentas, gastos
--    2 Deuda y dinero: proveedores, datos_bancarios, cuentas_por_pagar, prestamos, traspasos, reparto
--    3 Control: usuarios/roles, adjuntos, bitacora
--  Todas las tablas se crean desde el inicio porque gastos referencia a varias de la fase 2.
--
--  Para reiniciar en desarrollo:  DROP DATABASE gadit;  y volver a correr este archivo.
-- =====================================================================

SET NAMES utf8mb4;  -- evita acentos corruptos aunque el cliente use otra codificación por defecto
CREATE DATABASE IF NOT EXISTS gadit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gadit;

-- ---------------------------------------------------------------------
-- USUARIOS Y PERSONAS
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre      VARCHAR(120) NOT NULL,
  email       VARCHAR(160) NOT NULL,
  password    VARCHAR(255) NOT NULL,
  rol         ENUM('admin','captura','consulta') NOT NULL DEFAULT 'captura',  -- consulta = solo lectura
  activo      TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB;

-- Socios, custodios de efectivo, prestamistas, beneficiarios de comisiones... (un solo catálogo;
-- el "rol" se deduce de dónde aparece la persona: prestamos.acreedor_id, gastos.beneficiario_id, etc.)
CREATE TABLE personas (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre      VARCHAR(120) NOT NULL,
  alias       VARCHAR(60) NULL,
  telefono    VARCHAR(30) NULL,
  es_socio    TINYINT(1) NOT NULL DEFAULT 0,
  activo      TINYINT(1) NOT NULL DEFAULT 1,
  notas       VARCHAR(255) NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_personas_nombre (nombre)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- INGRESOS: cliente -> proyecto -> contrato -> factura -> cobro
-- ---------------------------------------------------------------------
CREATE TABLE clientes (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre      VARCHAR(120) NOT NULL,
  rfc         VARCHAR(13) NULL,
  contacto    VARCHAR(120) NULL,
  activo      TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_clientes_nombre (nombre)
) ENGINE=InnoDB;

-- La obra. Aquí se pega el costo: gastos.proyecto_id es lo que el Excel no tenía.
CREATE TABLE proyectos (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  cliente_id    BIGINT UNSIGNED NOT NULL,
  codigo        VARCHAR(30) NULL,
  nombre        VARCHAR(160) NOT NULL,
  ubicacion     VARCHAR(160) NULL,
  estatus       ENUM('en_proceso','terminado','suspendido') NOT NULL DEFAULT 'en_proceso',
  fecha_inicio  DATE NULL,
  fecha_fin     DATE NULL,
  notas         VARCHAR(255) NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_proyectos_codigo (codigo),
  KEY ix_proyectos_cliente (cliente_id),
  CONSTRAINT fk_proyectos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id)
) ENGINE=InnoDB;

-- Pedido / contrato. Una adenda es otro contrato con tipo='adenda' y contrato_padre_id.
CREATE TABLE contratos (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  proyecto_id        BIGINT UNSIGNED NOT NULL,
  contrato_padre_id  BIGINT UNSIGNED NULL,
  tipo               ENUM('contrato','adenda') NOT NULL DEFAULT 'contrato',
  numero_pedido      VARCHAR(60) NULL,        -- '4506816475', '00494', 'DC-PR-479_IN-DI-CA-PG-02-R01'
  concepto           VARCHAR(200) NOT NULL,
  importe            DECIMAL(14,2) NOT NULL,  -- sin IVA
  iva_pct            DECIMAL(5,2) NOT NULL DEFAULT 16.00,
  fecha              DATE NULL,
  estatus            ENUM('vigente','terminado','cancelado') NOT NULL DEFAULT 'vigente',
  notas              VARCHAR(255) NULL,
  created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_contratos_proyecto (proyecto_id),
  KEY ix_contratos_padre (contrato_padre_id),
  KEY ix_contratos_pedido (numero_pedido),
  CONSTRAINT fk_contratos_proyecto FOREIGN KEY (proyecto_id) REFERENCES proyectos (id),
  CONSTRAINT fk_contratos_padre FOREIGN KEY (contrato_padre_id) REFERENCES contratos (id),
  CONSTRAINT ck_contratos_importe CHECK (importe >= 0)
) ENGINE=InnoDB;

-- "No ejecutado", deductivas y descuentos que reducen lo que se puede facturar (hoja Hoja1 / bloque Peren).
-- Importes en positivo = lo que se resta del contrato.
CREATE TABLE contrato_ajustes (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  contrato_id  BIGINT UNSIGNED NOT NULL,
  tipo         ENUM('no_ejecutado','deductiva','descuento','otro') NOT NULL,
  concepto     VARCHAR(200) NULL,
  importe      DECIMAL(14,2) NOT NULL,
  fecha        DATE NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_ajustes_contrato (contrato_id),
  CONSTRAINT fk_ajustes_contrato FOREIGN KEY (contrato_id) REFERENCES contratos (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- DINERO: dónde está (cajas, bancos, intermediario)
-- ---------------------------------------------------------------------
-- Cada hoja de saldo del Excel (ALFREDO, CAJA, ALEJANDRA/GPE/ALVARO INGRESOS) era un estado de cuenta.
-- Aquí son cuentas, y su saldo sale de los movimientos (v_saldo_cuentas): ya no se teclea dos veces.
CREATE TABLE cuentas (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre      VARCHAR(100) NOT NULL,
  tipo        ENUM('banco','caja','intermediario') NOT NULL,
  persona_id  BIGINT UNSIGNED NULL,   -- quién la custodia
  activa      TINYINT(1) NOT NULL DEFAULT 1,
  saldo_inicial        DECIMAL(14,2) NOT NULL DEFAULT 0.00,  -- saldo contado el día del arranque
  fecha_saldo_inicial  DATE NULL,                            -- solo los movimientos POSTERIORES a esta fecha se suman al saldo inicial
  notas       VARCHAR(255) NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cuentas_nombre (nombre),
  CONSTRAINT fk_cuentas_persona FOREIGN KEY (persona_id) REFERENCES personas (id)
) ENGINE=InnoDB;

CREATE TABLE facturas (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  contrato_id        BIGINT UNSIGNED NOT NULL,
  folio              VARCHAR(30) NOT NULL,          -- 'A5001'
  uuid               CHAR(36) NULL,                 -- Folio Fiscal (UUID) del CFDI; NULL en las históricas
  metodo_pago        ENUM('PUE','PPD') NULL,        -- PPD = parcialidades/diferido: GADIT debe emitir Complemento de Pago al cobrar
  forma_pago         CHAR(2) NULL,                  -- catálogo SAT c_FormaPago ('03' transferencia, '01' efectivo; con PPD va '99')
  tipo               ENUM('anticipo','estimacion','finiquito','otro') NOT NULL DEFAULT 'estimacion',
  numero_estimacion  TINYINT UNSIGNED NULL,
  concepto           VARCHAR(200) NOT NULL,
  fecha_emision      DATE NOT NULL,
  fecha_vencimiento  DATE NULL,
  importe            DECIMAL(14,2) NOT NULL,        -- base, antes de retención e IVA
  retencion_pct      DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  iva_pct            DECIMAL(5,2) NOT NULL DEFAULT 16.00,
  estatus            ENUM('emitida','cancelada') NOT NULL DEFAULT 'emitida',
  notas              VARCHAR(255) NULL,
  created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_facturas_folio (folio),
  UNIQUE KEY uq_facturas_uuid (uuid),
  KEY ix_facturas_contrato (contrato_id),
  KEY ix_facturas_emision (fecha_emision),
  CONSTRAINT fk_facturas_contrato FOREIGN KEY (contrato_id) REFERENCES contratos (id),
  CONSTRAINT ck_facturas_importe CHECK (importe >= 0),
  CONSTRAINT ck_facturas_retencion CHECK (retencion_pct BETWEEN 0 AND 100),
  CONSTRAINT ck_facturas_uuid CHECK (uuid IS NULL OR uuid REGEXP '^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$'),
  CONSTRAINT ck_facturas_forma_pago CHECK (forma_pago IS NULL OR forma_pago REGEXP '^[0-9]{2}$')
) ENGINE=InnoDB;

-- Subtotal, IVA, total y saldo no se guardan: los calcula v_facturas (una sola fuente de verdad).
CREATE TABLE cobros (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  factura_id  BIGINT UNSIGNED NOT NULL,
  cuenta_id   BIGINT UNSIGNED NOT NULL,   -- a qué cuenta entró el dinero
  fecha       DATE NOT NULL,
  importe     DECIMAL(14,2) NOT NULL,     -- sin IVA
  iva         DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  forma_pago  CHAR(2) NULL,                 -- forma real del cobro (catálogo SAT: '03' transferencia, '01' efectivo)
  referencia  VARCHAR(80) NULL,
  complemento_uuid CHAR(36) NULL,           -- UUID del Complemento de Pago que GADIT emite si la factura es PPD
                                            -- (no único: un complemento puede cubrir varios cobros)
  notas       VARCHAR(255) NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at  TIMESTAMP NULL DEFAULT NULL,   -- borrado lógico
  PRIMARY KEY (id),
  KEY ix_cobros_factura (factura_id),
  KEY ix_cobros_complemento (complemento_uuid),
  KEY ix_cobros_cuenta (cuenta_id),
  KEY ix_cobros_fecha (fecha),
  CONSTRAINT fk_cobros_factura FOREIGN KEY (factura_id) REFERENCES facturas (id),
  CONSTRAINT fk_cobros_cuenta FOREIGN KEY (cuenta_id) REFERENCES cuentas (id),
  CONSTRAINT ck_cobros_importe CHECK (importe > 0),
  CONSTRAINT ck_cobros_forma_pago CHECK (forma_pago IS NULL OR forma_pago REGEXP '^[0-9]{2}$'),
  CONSTRAINT ck_cobros_complemento CHECK (complemento_uuid IS NULL OR complemento_uuid REGEXP '^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$')
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CATÁLOGOS DE GASTO
-- ---------------------------------------------------------------------
-- Reemplaza las 153 etiquetas libres de GADIT. 'grupo' permite reportar y separar el reparto de utilidad.
CREATE TABLE categorias (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre      VARCHAR(80) NOT NULL,
  grupo       ENUM('obra','nomina','impuestos','comisiones','reparto_utilidad',
                   'financiamiento','logistica','administracion','otros') NOT NULL,
  activa      TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categorias_nombre (nombre)
) ENGINE=InnoDB;

CREATE TABLE proveedores (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre        VARCHAR(140) NOT NULL,
  rfc           VARCHAR(13) NULL,
  telefono      VARCHAR(30) NULL,
  dias_credito  SMALLINT UNSIGNED NULL,
  activo        TINYINT(1) NOT NULL DEFAULT 1,
  notas         VARCHAR(255) NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_proveedores_nombre (nombre)
) ENGINE=InnoDB;

-- Cuentas bancarias de terceros (proveedores y personas) para poder pagarles. Un tercero puede tener varias.
-- Dato financiero sensible:
--   · la app guarda la CLABE CIFRADA (por eso VARCHAR(255); en claro son 18 dígitos) y valida su
--     dígito verificador al capturar;
--   · clabe_ultimos4 permite mostrarla enmascarada en listados sin descifrar;
--   · no registrar la CLABE en bitacora; mostrarla solo a los roles admin y captura;
--   · una sola cuenta 'principal' por tercero (regla de la app: MySQL no tiene índice único parcial).
CREATE TABLE datos_bancarios (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  proveedor_id    BIGINT UNSIGNED NULL,
  persona_id      BIGINT UNSIGNED NULL,
  banco           VARCHAR(50) NOT NULL,
  clabe           VARCHAR(255) NOT NULL,
  clabe_ultimos4  CHAR(4) NOT NULL,
  titular         VARCHAR(120) NULL,         -- nombre a quien está la cuenta, si difiere del proveedor/persona
  principal       TINYINT(1) NOT NULL DEFAULT 0,
  activa          TINYINT(1) NOT NULL DEFAULT 1,
  notas           VARCHAR(255) NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_bancarios_proveedor (proveedor_id),
  KEY ix_bancarios_persona (persona_id),
  CONSTRAINT fk_bancarios_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores (id),
  CONSTRAINT fk_bancarios_persona FOREIGN KEY (persona_id) REFERENCES personas (id),
  CONSTRAINT ck_bancarios_duenio CHECK ((proveedor_id IS NULL) <> (persona_id IS NULL)),  -- de uno u otro, no ambos
  CONSTRAINT ck_bancarios_ultimos4 CHECK (clabe_ultimos4 REGEXP '^[0-9]{4}$')
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- DEUDA: compras a crédito y préstamos
-- ---------------------------------------------------------------------
-- Hoja PROVEEDORES. El pago NO se anota aquí: se registra como gasto con cuenta_por_pagar_id,
-- así el gasto entra a la fecha en que se paga (igual que hoy en GADIT) y el saldo se calcula.
CREATE TABLE cuentas_por_pagar (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  proveedor_id       BIGINT UNSIGNED NOT NULL,
  proyecto_id        BIGINT UNSIGNED NULL,
  fecha              DATE NOT NULL,
  fecha_vencimiento  DATE NULL,
  concepto           VARCHAR(200) NULL,
  importe            DECIMAL(14,2) NOT NULL,        -- sin IVA
  iva                DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  estatus            ENUM('vigente','cancelada') NOT NULL DEFAULT 'vigente',
  notas              VARCHAR(255) NULL,
  created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at  TIMESTAMP NULL DEFAULT NULL,   -- borrado lógico
  PRIMARY KEY (id),
  KEY ix_cxp_proveedor (proveedor_id),
  KEY ix_cxp_proyecto (proyecto_id),
  CONSTRAINT fk_cxp_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores (id),
  CONSTRAINT fk_cxp_proyecto FOREIGN KEY (proyecto_id) REFERENCES proyectos (id),
  CONSTRAINT ck_cxp_importe CHECK (importe >= 0)
) ENGINE=InnoDB;

-- Hoja PRESTAMOS. Los intereses y comisiones del préstamo se registran como gasto con prestamo_id;
-- la devolución de capital va en prestamo_pagos (no es gasto: no baja la utilidad).
CREATE TABLE prestamos (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  acreedor_id        BIGINT UNSIGNED NOT NULL,       -- persona que prestó
  cuenta_id          BIGINT UNSIGNED NOT NULL,       -- cuenta donde se recibió el dinero
  fecha              DATE NOT NULL,
  monto              DECIMAL(14,2) NOT NULL,
  tasa_pct           DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  tasa_periodo       ENUM('mensual','anual','unica') NOT NULL DEFAULT 'mensual',  -- confirmar por préstamo
  fecha_vencimiento  DATE NULL,
  notas              VARCHAR(255) NULL,
  created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at  TIMESTAMP NULL DEFAULT NULL,   -- borrado lógico
  PRIMARY KEY (id),
  KEY ix_prestamos_acreedor (acreedor_id),
  KEY ix_prestamos_cuenta (cuenta_id),
  CONSTRAINT fk_prestamos_acreedor FOREIGN KEY (acreedor_id) REFERENCES personas (id),
  CONSTRAINT fk_prestamos_cuenta FOREIGN KEY (cuenta_id) REFERENCES cuentas (id),
  CONSTRAINT ck_prestamos_monto CHECK (monto > 0)
) ENGINE=InnoDB;

CREATE TABLE prestamo_pagos (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  prestamo_id    BIGINT UNSIGNED NOT NULL,
  cuenta_id      BIGINT UNSIGNED NOT NULL,           -- de dónde salió el pago
  fecha          DATE NOT NULL,
  monto_capital  DECIMAL(14,2) NOT NULL,
  notas          VARCHAR(255) NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at  TIMESTAMP NULL DEFAULT NULL,   -- borrado lógico
  PRIMARY KEY (id),
  KEY ix_pagos_prestamo (prestamo_id),
  KEY ix_pagos_cuenta (cuenta_id),
  CONSTRAINT fk_pagos_prestamo FOREIGN KEY (prestamo_id) REFERENCES prestamos (id),
  CONSTRAINT fk_pagos_cuenta FOREIGN KEY (cuenta_id) REFERENCES cuentas (id),
  CONSTRAINT ck_pagos_monto CHECK (monto_capital > 0)
) ENGINE=InnoDB;

-- Dinero que se mueve entre cuentas (hoja ALFREDO: "ENVIADO", depósitos al banco, efectivo a Javier...).
-- La comisión del intermediario se registra como gasto (categoría de comisiones) con traspaso_id.
CREATE TABLE traspasos (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  fecha              DATE NOT NULL,
  cuenta_origen_id   BIGINT UNSIGNED NOT NULL,
  cuenta_destino_id  BIGINT UNSIGNED NOT NULL,
  monto              DECIMAL(14,2) NOT NULL,
  comision_pct       DECIMAL(5,2) NULL,        -- informativo (0.8, 2, 2.5...)
  concepto           VARCHAR(200) NULL,
  notas              VARCHAR(255) NULL,
  created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at  TIMESTAMP NULL DEFAULT NULL,   -- borrado lógico
  PRIMARY KEY (id),
  KEY ix_traspasos_fecha (fecha),
  KEY ix_traspasos_origen (cuenta_origen_id),
  KEY ix_traspasos_destino (cuenta_destino_id),
  CONSTRAINT fk_traspasos_origen FOREIGN KEY (cuenta_origen_id) REFERENCES cuentas (id),
  CONSTRAINT fk_traspasos_destino FOREIGN KEY (cuenta_destino_id) REFERENCES cuentas (id),
  CONSTRAINT ck_traspasos_monto CHECK (monto > 0),
  CONSTRAINT ck_traspasos_distintas CHECK (cuenta_origen_id <> cuenta_destino_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- GASTOS (hojas GADIT y GADIT 2): la tabla central
-- ---------------------------------------------------------------------
CREATE TABLE gastos (
  id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  fecha                DATE NOT NULL,
  semana_iso           INT GENERATED ALWAYS AS (YEARWEEK(fecha, 3)) STORED,  -- 202504 = semana 4 de 2025; reemplaza la columna SEM
  concepto             VARCHAR(200) NOT NULL,
  importe_con_iva      DECIMAL(14,2) NOT NULL,         -- IMPORTE C/IVA del Excel
  iva_incluido         DECIMAL(14,2) NULL,             -- si se conoce (IVA acreditable); NULL en datos históricos
  cfdi_uuid            CHAR(36) NULL,                  -- Folio Fiscal del CFDI del proveedor (no único: un CFDI puede respaldar varios pagos)
  categoria_id         BIGINT UNSIGNED NOT NULL,
  cuenta_id            BIGINT UNSIGNED NOT NULL,       -- de dónde salió el dinero (antes RESPONSABLE)
  proyecto_id          BIGINT UNSIGNED NULL,           -- NULL = gasto general de la empresa
  proveedor_id         BIGINT UNSIGNED NULL,
  beneficiario_id      BIGINT UNSIGNED NULL,           -- persona: comisión, utilidad, apoyo, viático
  cuenta_por_pagar_id  BIGINT UNSIGNED NULL,           -- pago de una compra a crédito
  prestamo_id          BIGINT UNSIGNED NULL,           -- intereses / comisiones de un préstamo
  traspaso_id          BIGINT UNSIGNED NULL,           -- comisión generada por un traspaso
  periodo_inicio       DATE NULL,                      -- nómina semanal, mes de IMSS/ISR/ISN...
  periodo_fin          DATE NULL,
  estatus_revision     ENUM('sin_revisar','revisado','falta_comprobante') NOT NULL DEFAULT 'sin_revisar',  -- la leyenda de colores de GADIT J85:K87, que nunca se aplicó
  observaciones        VARCHAR(255) NULL,
  origen               VARCHAR(20) NULL,               -- 'GADIT' / 'GADIT 2' (trazabilidad de la importación)
  numero_origen        INT UNSIGNED NULL,              -- columna No del Excel
  creado_por           BIGINT UNSIGNED NULL,
  created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at           TIMESTAMP NULL DEFAULT NULL,    -- borrado lógico
  PRIMARY KEY (id),
  UNIQUE KEY uq_gastos_origen (origen, numero_origen),   -- importación repetible (NULL permitido en capturas nuevas)
  KEY ix_gastos_fecha (fecha),
  KEY ix_gastos_semana (semana_iso),
  KEY ix_gastos_categoria (categoria_id),
  KEY ix_gastos_cuenta (cuenta_id),
  KEY ix_gastos_proyecto (proyecto_id),
  KEY ix_gastos_proveedor (proveedor_id),
  KEY ix_gastos_beneficiario (beneficiario_id),
  KEY ix_gastos_cxp (cuenta_por_pagar_id),
  KEY ix_gastos_prestamo (prestamo_id),
  KEY ix_gastos_traspaso (traspaso_id),
  KEY ix_gastos_cfdi (cfdi_uuid),
  CONSTRAINT fk_gastos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias (id),
  CONSTRAINT fk_gastos_cuenta FOREIGN KEY (cuenta_id) REFERENCES cuentas (id),
  CONSTRAINT fk_gastos_proyecto FOREIGN KEY (proyecto_id) REFERENCES proyectos (id),
  CONSTRAINT fk_gastos_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores (id),
  CONSTRAINT fk_gastos_beneficiario FOREIGN KEY (beneficiario_id) REFERENCES personas (id),
  CONSTRAINT fk_gastos_cxp FOREIGN KEY (cuenta_por_pagar_id) REFERENCES cuentas_por_pagar (id),
  CONSTRAINT fk_gastos_prestamo FOREIGN KEY (prestamo_id) REFERENCES prestamos (id),
  CONSTRAINT fk_gastos_traspaso FOREIGN KEY (traspaso_id) REFERENCES traspasos (id),
  CONSTRAINT fk_gastos_creado_por FOREIGN KEY (creado_por) REFERENCES usuarios (id),
  CONSTRAINT ck_gastos_importe CHECK (importe_con_iva <> 0),
  CONSTRAINT ck_gastos_periodo CHECK (periodo_inicio IS NULL OR periodo_fin IS NULL OR periodo_fin >= periodo_inicio),
  CONSTRAINT ck_gastos_cfdi CHECK (cfdi_uuid IS NULL OR cfdi_uuid REGEXP '^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$')
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- REPARTO DE UTILIDAD (bloque UTILIDAD de CONCENTRADO y cálculo suelto de CAJA)
-- ---------------------------------------------------------------------
-- Los anticipos ya entregados se registran como gasto con categoría del grupo 'reparto_utilidad'
-- y beneficiario = socio. Los porcentajes (30/25/22.5/22.5, 65/20/15...) viven aquí, no en celdas.
-- Regla de aplicación: los porcentajes de un esquema deben sumar 100.
CREATE TABLE esquemas_reparto (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre       VARCHAR(100) NOT NULL,
  proyecto_id  BIGINT UNSIGNED NULL,    -- NULL = aplica a toda la empresa
  vigente      TINYINT(1) NOT NULL DEFAULT 1,
  notas        VARCHAR(255) NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_esquemas_proyecto (proyecto_id),
  CONSTRAINT fk_esquemas_proyecto FOREIGN KEY (proyecto_id) REFERENCES proyectos (id)
) ENGINE=InnoDB;

CREATE TABLE esquema_reparto_socios (
  esquema_id  BIGINT UNSIGNED NOT NULL,
  persona_id  BIGINT UNSIGNED NOT NULL,
  porcentaje  DECIMAL(5,2) NOT NULL,
  PRIMARY KEY (esquema_id, persona_id),
  CONSTRAINT fk_esq_socios_esquema FOREIGN KEY (esquema_id) REFERENCES esquemas_reparto (id),
  CONSTRAINT fk_esq_socios_persona FOREIGN KEY (persona_id) REFERENCES personas (id),
  CONSTRAINT ck_esq_socios_pct CHECK (porcentaje > 0 AND porcentaje <= 100)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CONTROL (fase 3)
-- ---------------------------------------------------------------------
-- Comprobantes de cada registro (el Excel no guardaba archivos; el estatus de revisión está en gastos).
CREATE TABLE adjuntos (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  entidad     ENUM('gasto','factura','cobro','contrato','cuenta_por_pagar','prestamo') NOT NULL,
  entidad_id  BIGINT UNSIGNED NOT NULL,
  nombre      VARCHAR(180) NOT NULL,
  ruta        VARCHAR(255) NOT NULL,
  mime        VARCHAR(80) NULL,
  subido_por  BIGINT UNSIGNED NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_adjuntos_entidad (entidad, entidad_id),
  CONSTRAINT fk_adjuntos_usuario FOREIGN KEY (subido_por) REFERENCES usuarios (id)
) ENGINE=InnoDB;

-- Quién cambió qué y cuándo (en Excel no existía). No guardar datos sensibles (CLABE) en antes/despues.
CREATE TABLE bitacora (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id   BIGINT UNSIGNED NULL,
  tabla        VARCHAR(40) NOT NULL,
  registro_id  BIGINT UNSIGNED NOT NULL,
  accion       ENUM('crear','editar','eliminar') NOT NULL,
  antes        JSON NULL,
  despues      JSON NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_bitacora_registro (tabla, registro_id),
  CONSTRAINT fk_bitacora_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB;


-- =====================================================================
--  VISTAS: lo que en el Excel eran fórmulas
-- =====================================================================

-- FACTURADO del CONCENTRADO: retención, subtotal, IVA, total, cobrado y saldo por factura.
CREATE OR REPLACE VIEW v_facturas AS
SELECT x.*,
       x.subtotal + x.iva                                        AS total,
       COALESCE(k.importe, 0)                                    AS cobrado_sin_iva,
       COALESCE(k.iva, 0)                                        AS cobrado_iva,
       COALESCE(k.importe, 0) + COALESCE(k.iva, 0)               AS cobrado_total,
       (x.subtotal + x.iva) - (COALESCE(k.importe, 0) + COALESCE(k.iva, 0)) AS saldo,
       CASE
         WHEN x.estatus = 'cancelada' THEN 'cancelada'
         WHEN (x.subtotal + x.iva) - (COALESCE(k.importe, 0) + COALESCE(k.iva, 0)) <= 0.01 THEN 'cobrada'
         WHEN COALESCE(k.importe, 0) > 0 THEN 'parcial'
         WHEN x.fecha_vencimiento IS NOT NULL AND x.fecha_vencimiento < CURDATE() THEN 'vencida'
         ELSE 'por_cobrar'
       END AS situacion
FROM (
  SELECT f.id, f.folio, f.uuid, f.metodo_pago, f.forma_pago, f.contrato_id, c.proyecto_id, p.cliente_id,
         f.tipo, f.numero_estimacion, f.concepto, f.fecha_emision, f.fecha_vencimiento, f.estatus,
         f.importe,
         ROUND(f.importe * f.retencion_pct / 100, 2) AS retencion,
         f.importe - ROUND(f.importe * f.retencion_pct / 100, 2) AS subtotal,
         ROUND((f.importe - ROUND(f.importe * f.retencion_pct / 100, 2)) * f.iva_pct / 100, 2) AS iva
  FROM facturas f
  JOIN contratos c ON c.id = f.contrato_id
  JOIN proyectos p ON p.id = c.proyecto_id
) x
LEFT JOIN (
  SELECT factura_id, SUM(importe) AS importe, SUM(iva) AS iva FROM cobros WHERE deleted_at IS NULL GROUP BY factura_id
) k ON k.factura_id = x.id;

-- Bloque CONTRATO del CONCENTRADO: contratado, facturado, por facturar, cobrado y por cobrar (todo sin IVA).
CREATE OR REPLACE VIEW v_contratos_resumen AS
SELECT c.id AS contrato_id, c.proyecto_id, c.tipo, c.numero_pedido, c.concepto,
       c.importe                                              AS contratado,
       COALESCE(a.total, 0)                                   AS ajustes,
       c.importe - COALESCE(a.total, 0)                       AS neto,
       COALESCE(f.facturado, 0)                               AS facturado,
       c.importe - COALESCE(a.total, 0) - COALESCE(f.facturado, 0) AS por_facturar,
       COALESCE(f.cobrado, 0)                                 AS cobrado,
       COALESCE(f.facturado, 0) - COALESCE(f.cobrado, 0)      AS por_cobrar
FROM contratos c
LEFT JOIN (SELECT contrato_id, SUM(importe) AS total FROM contrato_ajustes GROUP BY contrato_id) a
       ON a.contrato_id = c.id
LEFT JOIN (SELECT contrato_id, SUM(subtotal) AS facturado, SUM(cobrado_sin_iva) AS cobrado
           FROM v_facturas WHERE estatus <> 'cancelada' GROUP BY contrato_id) f
       ON f.contrato_id = c.id;

-- Cobros de facturas PPD a los que GADIT aún no les emite el Complemento de Pago (el cliente lo necesita
-- para acreditar su IVA). Confirmar plazos de emisión con el contador.
CREATE OR REPLACE VIEW v_complementos_pendientes AS
SELECT co.id AS cobro_id, f.folio, f.uuid AS factura_uuid, f.contrato_id, co.fecha,
       co.importe + co.iva AS monto, co.forma_pago
FROM cobros co
JOIN facturas f ON f.id = co.factura_id
WHERE co.deleted_at IS NULL AND f.metodo_pago = 'PPD' AND co.complemento_uuid IS NULL;

-- Hoja PROVEEDORES: saldo = total de la compra - pagos (gastos ligados). Incluye la cuenta bancaria
-- principal del proveedor (enmascarada) para que quien paga la vea ahí mismo; la CLABE completa se
-- pide a datos_bancarios por id, solo a los roles autorizados.
CREATE OR REPLACE VIEW v_cuentas_por_pagar AS
SELECT cp.id, cp.proveedor_id, pv.nombre AS proveedor, cp.proyecto_id, cp.fecha, cp.fecha_vencimiento,
       cp.concepto, cp.importe, cp.iva, cp.importe + cp.iva AS total,
       COALESCE(pg.pagado, 0) AS pagado,
       cp.importe + cp.iva - COALESCE(pg.pagado, 0) AS saldo,
       cp.estatus,
       db.id AS datos_bancarios_id, db.banco, db.clabe_ultimos4, db.titular AS titular_cuenta
FROM cuentas_por_pagar cp
JOIN proveedores pv ON pv.id = cp.proveedor_id
LEFT JOIN (SELECT cuenta_por_pagar_id, SUM(importe_con_iva) AS pagado
           FROM gastos WHERE deleted_at IS NULL AND cuenta_por_pagar_id IS NOT NULL
           GROUP BY cuenta_por_pagar_id) pg
       ON pg.cuenta_por_pagar_id = cp.id
LEFT JOIN datos_bancarios db
       ON db.id = (SELECT d2.id FROM datos_bancarios d2
                   WHERE d2.proveedor_id = cp.proveedor_id AND d2.activa = 1
                   ORDER BY d2.principal DESC, d2.id LIMIT 1)
WHERE cp.deleted_at IS NULL;

-- Hoja PRESTAMOS: saldo de capital e intereses pagados.
CREATE OR REPLACE VIEW v_prestamos AS
SELECT pr.id, pr.acreedor_id, pe.nombre AS acreedor, pr.fecha, pr.monto, pr.tasa_pct, pr.tasa_periodo,
       COALESCE(pg.capital, 0)             AS capital_pagado,
       pr.monto - COALESCE(pg.capital, 0)  AS saldo_capital,
       COALESCE(ig.intereses, 0)           AS intereses_pagados
FROM prestamos pr
JOIN personas pe ON pe.id = pr.acreedor_id
LEFT JOIN (SELECT prestamo_id, SUM(monto_capital) AS capital
           FROM prestamo_pagos WHERE deleted_at IS NULL GROUP BY prestamo_id) pg
       ON pg.prestamo_id = pr.id
LEFT JOIN (SELECT prestamo_id, SUM(importe_con_iva) AS intereses
           FROM gastos WHERE deleted_at IS NULL AND prestamo_id IS NOT NULL GROUP BY prestamo_id) ig
       ON ig.prestamo_id = pr.id
WHERE pr.deleted_at IS NULL;

-- Listado tipo hoja GADIT, con los nombres ya resueltos.
CREATE OR REPLACE VIEW v_gastos_detalle AS
SELECT g.id, g.fecha, g.semana_iso, g.concepto, g.importe_con_iva, g.iva_incluido,
       cat.nombre AS categoria, cat.grupo, cu.nombre AS cuenta,
       pr.nombre AS proyecto, pv.nombre AS proveedor, pe.nombre AS beneficiario,
       g.estatus_revision, g.periodo_inicio, g.periodo_fin, g.observaciones, g.origen, g.numero_origen
FROM gastos g
JOIN categorias cat ON cat.id = g.categoria_id
JOIN cuentas cu ON cu.id = g.cuenta_id
LEFT JOIN proyectos pr ON pr.id = g.proyecto_id
LEFT JOIN proveedores pv ON pv.id = g.proveedor_id
LEFT JOIN personas pe ON pe.id = g.beneficiario_id
WHERE g.deleted_at IS NULL;

CREATE OR REPLACE VIEW v_gastos_categoria AS
SELECT cat.grupo, cat.nombre AS categoria, COUNT(*) AS partidas, SUM(g.importe_con_iva) AS total
FROM gastos g
JOIN categorias cat ON cat.id = g.categoria_id
WHERE g.deleted_at IS NULL
GROUP BY cat.id, cat.grupo, cat.nombre;

-- Corte semanal (reemplaza la columna SEM): semana_iso = AAAASS.
CREATE OR REPLACE VIEW v_gastos_semana AS
SELECT g.semana_iso, MIN(g.fecha) AS desde, MAX(g.fecha) AS hasta,
       COUNT(*) AS partidas, SUM(g.importe_con_iva) AS total
FROM gastos g
WHERE g.deleted_at IS NULL
GROUP BY g.semana_iso;

-- Estado de cuenta unificado: reemplaza las hojas ALFREDO, CAJA y *INGRESOS.
CREATE OR REPLACE VIEW v_movimientos_cuenta AS
SELECT co.cuenta_id, co.fecha, 'cobro' AS tipo, co.id AS ref_id,
       CONCAT('Cobro factura ', f.folio) AS concepto,
       co.importe + co.iva AS entrada, 0.00 AS salida
FROM cobros co JOIN facturas f ON f.id = co.factura_id
WHERE co.deleted_at IS NULL
UNION ALL
SELECT pr.cuenta_id, pr.fecha, 'prestamo', pr.id, CONCAT('Préstamo de ', pe.nombre), pr.monto, 0.00
FROM prestamos pr JOIN personas pe ON pe.id = pr.acreedor_id
WHERE pr.deleted_at IS NULL
UNION ALL
SELECT t.cuenta_destino_id, t.fecha, 'traspaso_entrada', t.id, CONCAT('Traspaso desde ', o.nombre), t.monto, 0.00
FROM traspasos t JOIN cuentas o ON o.id = t.cuenta_origen_id
WHERE t.deleted_at IS NULL
UNION ALL
SELECT g.cuenta_id, g.fecha, 'gasto', g.id, g.concepto, 0.00, g.importe_con_iva
FROM gastos g WHERE g.deleted_at IS NULL
UNION ALL
SELECT pp.cuenta_id, pp.fecha, 'pago_prestamo', pp.id, 'Pago de capital de préstamo', 0.00, pp.monto_capital
FROM prestamo_pagos pp
WHERE pp.deleted_at IS NULL
UNION ALL
SELECT t.cuenta_origen_id, t.fecha, 'traspaso_salida', t.id, CONCAT('Traspaso a ', d.nombre), 0.00, t.monto
FROM traspasos t JOIN cuentas d ON d.id = t.cuenta_destino_id
WHERE t.deleted_at IS NULL;

-- "Dónde está el dinero" (K62:K67 del CONCENTRADO).
--   saldo = saldo_inicial + entradas - salidas, contando solo los movimientos posteriores a
--   fecha_saldo_inicial (si la cuenta no tiene fecha, cuentan todos).
CREATE OR REPLACE VIEW v_saldo_cuentas AS
SELECT c.id AS cuenta_id, c.nombre, c.tipo, c.saldo_inicial, c.fecha_saldo_inicial,
       COALESCE(SUM(m.entrada), 0) AS entradas,
       COALESCE(SUM(m.salida), 0)  AS salidas,
       c.saldo_inicial + COALESCE(SUM(m.entrada), 0) - COALESCE(SUM(m.salida), 0) AS saldo
FROM cuentas c
LEFT JOIN v_movimientos_cuenta m
       ON m.cuenta_id = c.id AND (c.fecha_saldo_inicial IS NULL OR m.fecha > c.fecha_saldo_inicial)
GROUP BY c.id, c.nombre, c.tipo, c.saldo_inicial, c.fecha_saldo_inicial;

-- Bloque UTILIDAD del CONCENTRADO, en una sola fila.
--   utilidad_caja          = cobrado (con IVA) - gastos - compras a crédito pendientes
--                            (ya descuenta lo repartido a socios, igual que el Excel)
--   utilidad_antes_reparto = utilidad_caja + lo ya repartido (categorías del grupo 'reparto_utilidad')
--   prestamos_saldo        = deuda de préstamos pendiente, solo informativa. El Excel la restaba de la
--                            utilidad, pero el dinero prestado que se gastó ya está en gastos y restarlo
--                            otra vez lo contaría doble. Con los datos actuales el saldo es 0: mismo resultado.
CREATE OR REPLACE VIEW v_resumen_general AS
SELECT t.*,
       t.cobrado_sin_iva + t.iva_cobrado - t.gastos_total - t.cxp_saldo AS utilidad_caja,
       t.cobrado_sin_iva + t.iva_cobrado - t.gastos_total - t.cxp_saldo
         + t.reparto_pagado AS utilidad_antes_reparto
FROM (
  SELECT
    (SELECT COALESCE(SUM(importe), 0)  FROM contratos WHERE estatus <> 'cancelado')          AS contratado_sin_iva,
    (SELECT COALESCE(SUM(subtotal), 0) FROM v_facturas WHERE estatus <> 'cancelada')          AS facturado_sin_iva,
    (SELECT COALESCE(SUM(importe), 0)  FROM cobros WHERE deleted_at IS NULL)                  AS cobrado_sin_iva,
    (SELECT COALESCE(SUM(iva), 0)      FROM cobros WHERE deleted_at IS NULL)                  AS iva_cobrado,
    (SELECT COALESCE(SUM(importe_con_iva), 0) FROM gastos WHERE deleted_at IS NULL)           AS gastos_total,
    (SELECT COALESCE(SUM(saldo), 0)    FROM v_cuentas_por_pagar WHERE estatus = 'vigente')    AS cxp_saldo,
    (SELECT COALESCE(SUM(saldo_capital), 0) FROM v_prestamos)                                 AS prestamos_saldo,
    (SELECT COALESCE(SUM(g.importe_con_iva), 0)
       FROM gastos g JOIN categorias c ON c.id = g.categoria_id
      WHERE g.deleted_at IS NULL AND c.grupo = 'reparto_utilidad')                            AS reparto_pagado
) t;

-- Lo que el Excel no podía: ingresos y costo por obra (más una fila para el gasto general sin proyecto).
CREATE OR REPLACE VIEW v_rentabilidad_proyecto AS
SELECT y.*, y.cobrado_con_iva - y.gastos_con_iva AS margen_caja
FROM (
  SELECT p.id AS proyecto_id, p.nombre AS proyecto,
         COALESCE((SELECT SUM(vf.subtotal)     FROM v_facturas vf WHERE vf.proyecto_id = p.id AND vf.estatus <> 'cancelada'), 0) AS facturado_sin_iva,
         COALESCE((SELECT SUM(vf.cobrado_total) FROM v_facturas vf WHERE vf.proyecto_id = p.id), 0) AS cobrado_con_iva,
         COALESCE((SELECT SUM(g.importe_con_iva) FROM gastos g WHERE g.proyecto_id = p.id AND g.deleted_at IS NULL), 0) AS gastos_con_iva
  FROM proyectos p
  UNION ALL
  SELECT NULL, '(gasto general / sin proyecto)', 0, 0,
         COALESCE((SELECT SUM(g.importe_con_iva) FROM gastos g WHERE g.proyecto_id IS NULL AND g.deleted_at IS NULL), 0)
) y;

-- "Lo que le toca" a cada socio según el esquema general vigente, lo ya entregado y lo pendiente.
CREATE OR REPLACE VIEW v_reparto_utilidad AS
SELECT e.id AS esquema_id, e.nombre AS esquema, pe.id AS persona_id, pe.nombre AS socio, s.porcentaje,
       ROUND(r.utilidad_antes_reparto * s.porcentaje / 100, 2)                       AS le_corresponde,
       COALESCE(a.entregado, 0)                                                      AS ya_entregado,
       ROUND(r.utilidad_antes_reparto * s.porcentaje / 100, 2) - COALESCE(a.entregado, 0) AS pendiente
FROM esquemas_reparto e
JOIN esquema_reparto_socios s ON s.esquema_id = e.id
JOIN personas pe ON pe.id = s.persona_id
CROSS JOIN v_resumen_general r
LEFT JOIN (SELECT g.beneficiario_id, SUM(g.importe_con_iva) AS entregado
           FROM gastos g JOIN categorias c ON c.id = g.categoria_id
           WHERE g.deleted_at IS NULL AND c.grupo = 'reparto_utilidad'
           GROUP BY g.beneficiario_id) a
       ON a.beneficiario_id = pe.id
WHERE e.vigente = 1 AND e.proyecto_id IS NULL;
