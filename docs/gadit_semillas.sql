-- =====================================================================
--  GADIT · Datos iniciales (opcional)  ·  correr DESPUÉS de gadit_schema.sql
--  Cargan catálogos de arranque y los bloques CONTRATO / FACTURADO / COBRADO del CONCENTRADO,
--  para comprobar que las vistas reproducen los totales del Excel.
--  Los gastos (GADIT / GADIT 2), proveedores, préstamos y traspasos se cargan con el importador.
--  Los ids asumen tablas vacías.
-- =====================================================================
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gadit;

-- Categorías de arranque (editables; el importador mapea las 159 etiquetas del Excel a estas: 153 en GADIT y 6 más en GADIT 2)
INSERT INTO categorias (nombre, grupo) VALUES
  ('CONCRETO', 'obra'),
  ('MAQUINARIA', 'obra'),
  ('MATERIALES DE OBRA', 'obra'),
  ('FERRETERIA', 'obra'),
  ('LABORATORIO', 'obra'),
  ('TOPOGRAFIA', 'obra'),
  ('AGUA Y PIPAS', 'obra'),
  ('FLETES Y REMOLQUES', 'obra'),
  ('EQUIPO DE SEGURIDAD', 'obra'),
  ('SERVICIOS DE OBRA', 'obra'),
  ('NOMINA', 'nomina'),
  ('FINIQUITOS', 'nomina'),
  ('SAT', 'impuestos'),
  ('IMSS', 'impuestos'),
  ('ISR', 'impuestos'),
  ('ISN', 'impuestos'),
  ('IVA', 'impuestos'),
  ('COMISIONES', 'comisiones'),
  ('COMISION INTERMEDIARIO', 'comisiones'),
  ('UTILIDAD', 'reparto_utilidad'),
  ('INTERESES', 'financiamiento'),
  ('TRAMITES Y COMISIONES DE PRESTAMO', 'financiamiento'),
  ('COMBUSTIBLE', 'logistica'),
  ('CASETAS', 'logistica'),
  ('VEHICULOS', 'logistica'),
  ('VIATICOS', 'logistica'),
  ('ADMINISTRACION', 'administracion'),
  ('SISTEMAS Y SERVICIOS', 'administracion'),
  ('FIANZAS Y SEGUROS', 'administracion'),
  ('RENTAS', 'administracion'),
  ('APOYOS', 'otros'),
  ('VARIOS', 'otros');

INSERT INTO personas (id, nombre, es_socio) VALUES
  (1,'Alejandra',1),
  (2,'Javier',1),
  (3,'Álvaro',1),
  (4,'GPE',1),
  (5,'Alfredo',0);

INSERT INTO cuentas (id, nombre, tipo, persona_id, notas) VALUES
  (1,'Banco del Bajío (GADIT)','banco',NULL,'Supuesto de la carga inicial: todos los cobros entraron aquí'),
  (2,'Caja Alejandra','caja',1,NULL),
  (3,'Alfredo (intermediario)','intermediario',5,NULL),
  (4,'Caja GPE','caja',4,NULL),
  (5,'Caja Javier','caja',2,NULL);

INSERT INTO clientes (id, nombre) VALUES
  (1, 'IBERDROLA BAJIO'),
  (2, 'IBERDROLA CENTRO'),
  (3, 'SOLARIG'),
  (4, 'EOSOL ENERGY'),
  (5, 'PEREN');

INSERT INTO proyectos (id, cliente_id, nombre) VALUES
  (1, 1, 'Pavimentación camino de acceso'),
  (2, 2, 'Caseta'),
  (3, 1, 'Renta de maquinaria'),
  (4, 3, 'Solarig'),
  (5, 4, 'Eosol Energy'),
  (6, 5, 'Caminos SLP');

INSERT INTO contratos (id, proyecto_id, contrato_padre_id, tipo, numero_pedido, concepto, importe) VALUES
  (1, 1, NULL, 'contrato', '4506816475', 'PAVIMENTACIÓN CAMINO DE ACCESO', 8099620.57),
  (2, 1, 1, 'adenda', '4506816475', 'ADENDA PAVIMENTACIÓN CAMINO DE ACCESO', 5156651.00),
  (3, 2, NULL, 'contrato', '4506813047', 'CASETA', 448439.32),
  (4, 3, NULL, 'contrato', '4506893670', 'RENTA MAQUINARIA', 116680.00),
  (5, 4, NULL, 'contrato', '00494', 'SOLARIG', 700044.00),
  (6, 4, NULL, 'contrato', NULL, 'VIAJES (SOLARIG)', 300000.00),
  (7, 5, NULL, 'contrato', 'DC-PR-479_IN-DI-CA-PG-02-R01', 'EOSOL ENERGY', 154700.00),
  (8, 6, NULL, 'contrato', NULL, 'CAMINOS SLP (PEREN)', 2953284.98);

INSERT INTO facturas (contrato_id, folio, tipo, numero_estimacion, concepto, fecha_emision, fecha_vencimiento, importe, retencion_pct) VALUES
  (1, 'A5001', 'estimacion', 1, 'ESTIMACION I PAVIMENTACION CAMINO DE ACCESO', '2024-11-04', '2024-11-12', 5029866.82, 0.00),
  (1, 'A5005', 'estimacion', 2, 'ESTIMACION II PAVIMENTACION CAMINO DE ACCESO', '2024-11-11', '2024-11-25', 2176976.85, 0.00),
  (1, 'A5006', 'estimacion', 3, 'ESTIMACION III PAVIMENTACION CAMINO DE ACCESO', '2024-11-11', '2024-11-25', 892776.90, 0.00),
  (4, 'A5007', 'otro', NULL, 'RENTA MAQUINARIA', '2024-11-11', '2024-11-25', 116680.00, 0.00),
  (3, 'A5008', 'otro', NULL, 'CIERRE ECONOMICO CASETA', '2024-11-11', '2024-11-25', 448439.32, 0.00),
  (5, 'A5010', 'otro', NULL, 'SOLARIG', '2024-11-14', '2024-11-28', 700044.00, 0.00),
  (7, 'A5012', 'otro', NULL, 'EOSOL ENERGY', '2024-12-05', '2025-01-05', 154700.00, 0.00),
  (8, 'A5014', 'anticipo', NULL, 'PEREN CAMINOS SLP ANTICIPO', '2024-12-11', '2025-01-11', 738321.24, 0.00),
  (8, 'A5016', 'estimacion', 1, 'PEREN CAMINOS SLP ESTIMACION I', '2025-02-15', '2025-02-15', 885985.49, 0.00),
  (8, 'A5017', 'finiquito', 2, 'PEREN CAMINOS SLP ESTIMACION II FINIQUITO', '2025-03-14', '2025-03-14', 488844.45, 0.00),
  (6, 'A5020', 'otro', NULL, 'VIAJES', '2025-03-21', '2025-04-21', 300000.00, 0.00),
  (2, 'A5018', 'otro', NULL, 'ADENDA PAVIMENTACION CAMINO DE ACCESO', '2025-03-27', '2025-03-28', 5156651.00, 0.00);

-- Cobros: se asume que todos entraron al banco (cuenta 1); ajustar si se sabe lo contrario
INSERT INTO cobros (factura_id, cuenta_id, fecha, importe, iva) VALUES
  ((SELECT id FROM facturas WHERE folio = 'A5001'), 1, '2024-11-14', 5029866.82, 804778.69),
  ((SELECT id FROM facturas WHERE folio = 'A5005'), 1, '2024-11-27', 2176976.85, 348316.30),
  ((SELECT id FROM facturas WHERE folio = 'A5008'), 1, '2024-11-27', 448439.32, 71750.29),
  ((SELECT id FROM facturas WHERE folio = 'A5006'), 1, '2024-12-09', 892776.90, 142844.30),
  ((SELECT id FROM facturas WHERE folio = 'A5010'), 1, '2024-12-10', 700044.00, 112007.04),
  ((SELECT id FROM facturas WHERE folio = 'A5014'), 1, '2024-12-12', 738321.24, 118131.40),
  ((SELECT id FROM facturas WHERE folio = 'A5012'), 1, '2024-12-17', 154700.00, 24752.00),
  ((SELECT id FROM facturas WHERE folio = 'A5007'), 1, '2024-12-19', 116680.00, 18668.80),
  ((SELECT id FROM facturas WHERE folio = 'A5016'), 1, '2025-02-15', 885985.49, 141757.68),
  ((SELECT id FROM facturas WHERE folio = 'A5017'), 1, '2025-03-14', 488844.45, 78215.11),
  ((SELECT id FROM facturas WHERE folio = 'A5018'), 1, '2025-03-28', 5156651.00, 825064.16);

INSERT INTO esquemas_reparto (id, nombre, notas) VALUES (1, 'Reparto general (CONCENTRADO)', 'Simulación del Excel: 30 / 25 / 22.5 / 22.5');
INSERT INTO esquema_reparto_socios (esquema_id, persona_id, porcentaje) VALUES
  (1,1,30.00),
  (1,2,25.00),
  (1,3,22.50),
  (1,4,22.50);
