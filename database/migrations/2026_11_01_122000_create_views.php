<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. v_facturas: retención, subtotal, IVA, total, cobrado y saldo por factura.
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_facturas AS
            SELECT x.*,
                   x.subtotal + x.iva                                                                  AS total,
                   COALESCE(k.importe, 0)                                                              AS cobrado_sin_iva,
                   COALESCE(k.iva, 0)                                                                  AS cobrado_iva,
                   COALESCE(k.importe, 0) + COALESCE(k.iva, 0)                                        AS cobrado_total,
                   (x.subtotal + x.iva) - (COALESCE(k.importe, 0) + COALESCE(k.iva, 0))               AS saldo,
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
                     ROUND(f.importe * f.retencion_pct / 100, 2)                                      AS retencion,
                     f.importe - ROUND(f.importe * f.retencion_pct / 100, 2)                          AS subtotal,
                     ROUND((f.importe - ROUND(f.importe * f.retencion_pct / 100, 2)) * f.iva_pct / 100, 2) AS iva
              FROM facturas f
              JOIN contratos c ON c.id = f.contrato_id
              JOIN proyectos p ON p.id = c.proyecto_id
            ) x
            LEFT JOIN (
              SELECT factura_id, SUM(importe) AS importe, SUM(iva) AS iva
              FROM cobros WHERE deleted_at IS NULL GROUP BY factura_id
            ) k ON k.factura_id = x.id
            SQL);

        // 2. v_contratos_resumen: contratado, facturado, por facturar, cobrado y por cobrar.
        DB::statement(<<<'SQL'
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
                   ON f.contrato_id = c.id
            SQL);

        // 3. v_complementos_pendientes: cobros PPD sin complemento de pago emitido.
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_complementos_pendientes AS
            SELECT co.id AS cobro_id, f.folio, f.uuid AS factura_uuid, f.contrato_id, co.fecha,
                   co.importe + co.iva AS monto, co.forma_pago
            FROM cobros co
            JOIN facturas f ON f.id = co.factura_id
            WHERE co.deleted_at IS NULL AND f.metodo_pago = 'PPD' AND co.complemento_uuid IS NULL
            SQL);

        // 4. v_cuentas_por_pagar: saldo de cada CxP con datos bancarios del proveedor.
        DB::statement(<<<'SQL'
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
            WHERE cp.deleted_at IS NULL
            SQL);

        // 5. v_prestamos: saldo de capital e intereses pagados.
        DB::statement(<<<'SQL'
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
            WHERE pr.deleted_at IS NULL
            SQL);

        // 6. v_gastos_detalle: listado con nombres ya resueltos.
        DB::statement(<<<'SQL'
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
            WHERE g.deleted_at IS NULL
            SQL);

        // 7. v_gastos_categoria: total de gastos por grupo y categoría.
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_gastos_categoria AS
            SELECT cat.grupo, cat.nombre AS categoria, COUNT(*) AS partidas, SUM(g.importe_con_iva) AS total
            FROM gastos g
            JOIN categorias cat ON cat.id = g.categoria_id
            WHERE g.deleted_at IS NULL
            GROUP BY cat.id, cat.grupo, cat.nombre
            SQL);

        // 8. v_gastos_semana: corte semanal (semana_iso = AAAASS).
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_gastos_semana AS
            SELECT g.semana_iso, MIN(g.fecha) AS desde, MAX(g.fecha) AS hasta,
                   COUNT(*) AS partidas, SUM(g.importe_con_iva) AS total
            FROM gastos g
            WHERE g.deleted_at IS NULL
            GROUP BY g.semana_iso
            SQL);

        // 9. v_movimientos_cuenta: estado de cuenta unificado (cobros, gastos, traspasos, préstamos, pagos).
        DB::statement(<<<'SQL'
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
            WHERE t.deleted_at IS NULL
            SQL);

        // 10. v_saldo_cuentas: "dónde está el dinero" — saldo_inicial + entradas - salidas.
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_saldo_cuentas AS
            SELECT c.id AS cuenta_id, c.nombre, c.tipo, c.saldo_inicial, c.fecha_saldo_inicial,
                   COALESCE(SUM(m.entrada), 0) AS entradas,
                   COALESCE(SUM(m.salida), 0)  AS salidas,
                   c.saldo_inicial + COALESCE(SUM(m.entrada), 0) - COALESCE(SUM(m.salida), 0) AS saldo
            FROM cuentas c
            LEFT JOIN v_movimientos_cuenta m
                   ON m.cuenta_id = c.id AND (c.fecha_saldo_inicial IS NULL OR m.fecha > c.fecha_saldo_inicial)
            GROUP BY c.id, c.nombre, c.tipo, c.saldo_inicial, c.fecha_saldo_inicial
            SQL);

        // 11. v_resumen_general: bloque UTILIDAD del CONCENTRADO en una sola fila.
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_resumen_general AS
            SELECT t.*,
                   t.cobrado_sin_iva + t.iva_cobrado - t.gastos_total - t.cxp_saldo         AS utilidad_caja,
                   t.cobrado_sin_iva + t.iva_cobrado - t.gastos_total - t.cxp_saldo
                     + t.reparto_pagado                                                      AS utilidad_antes_reparto
            FROM (
              SELECT
                (SELECT COALESCE(SUM(importe), 0)  FROM contratos WHERE estatus <> 'cancelado')           AS contratado_sin_iva,
                (SELECT COALESCE(SUM(subtotal), 0) FROM v_facturas WHERE estatus <> 'cancelada')           AS facturado_sin_iva,
                (SELECT COALESCE(SUM(importe), 0)  FROM cobros WHERE deleted_at IS NULL)                   AS cobrado_sin_iva,
                (SELECT COALESCE(SUM(iva), 0)      FROM cobros WHERE deleted_at IS NULL)                   AS iva_cobrado,
                (SELECT COALESCE(SUM(importe_con_iva), 0) FROM gastos WHERE deleted_at IS NULL)            AS gastos_total,
                (SELECT COALESCE(SUM(saldo), 0)    FROM v_cuentas_por_pagar WHERE estatus = 'vigente')     AS cxp_saldo,
                (SELECT COALESCE(SUM(saldo_capital), 0) FROM v_prestamos)                                  AS prestamos_saldo,
                (SELECT COALESCE(SUM(g.importe_con_iva), 0)
                   FROM gastos g JOIN categorias c ON c.id = g.categoria_id
                  WHERE g.deleted_at IS NULL AND c.grupo = 'reparto_utilidad')                             AS reparto_pagado
            ) t
            SQL);

        // 12. v_rentabilidad_proyecto: ingresos y costo por obra.
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_rentabilidad_proyecto AS
            SELECT y.*, y.cobrado_con_iva - y.gastos_con_iva AS margen_caja
            FROM (
              SELECT p.id AS proyecto_id, p.nombre AS proyecto,
                     COALESCE((SELECT SUM(vf.subtotal)      FROM v_facturas vf WHERE vf.proyecto_id = p.id AND vf.estatus <> 'cancelada'), 0) AS facturado_sin_iva,
                     COALESCE((SELECT SUM(vf.cobrado_total) FROM v_facturas vf WHERE vf.proyecto_id = p.id), 0)                              AS cobrado_con_iva,
                     COALESCE((SELECT SUM(g.importe_con_iva) FROM gastos g WHERE g.proyecto_id = p.id AND g.deleted_at IS NULL), 0)           AS gastos_con_iva
              FROM proyectos p
              UNION ALL
              SELECT NULL, '(gasto general / sin proyecto)', 0, 0,
                     COALESCE((SELECT SUM(g.importe_con_iva) FROM gastos g WHERE g.proyecto_id IS NULL AND g.deleted_at IS NULL), 0)
            ) y
            SQL);

        // 13. v_reparto_utilidad: lo que le toca a cada socio, lo entregado y lo pendiente.
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_reparto_utilidad AS
            SELECT e.id AS esquema_id, e.nombre AS esquema, pe.id AS persona_id, pe.nombre AS socio, s.porcentaje,
                   ROUND(r.utilidad_antes_reparto * s.porcentaje / 100, 2)                        AS le_corresponde,
                   COALESCE(a.entregado, 0)                                                       AS ya_entregado,
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
            WHERE e.vigente = 1 AND e.proyecto_id IS NULL
            SQL);
    }

    public function down(): void
    {
        // Orden inverso de dependencias: las vistas dependientes primero.
        DB::statement('DROP VIEW IF EXISTS v_reparto_utilidad');
        DB::statement('DROP VIEW IF EXISTS v_rentabilidad_proyecto');
        DB::statement('DROP VIEW IF EXISTS v_resumen_general');
        DB::statement('DROP VIEW IF EXISTS v_saldo_cuentas');
        DB::statement('DROP VIEW IF EXISTS v_movimientos_cuenta');
        DB::statement('DROP VIEW IF EXISTS v_gastos_semana');
        DB::statement('DROP VIEW IF EXISTS v_gastos_categoria');
        DB::statement('DROP VIEW IF EXISTS v_gastos_detalle');
        DB::statement('DROP VIEW IF EXISTS v_prestamos');
        DB::statement('DROP VIEW IF EXISTS v_cuentas_por_pagar');
        DB::statement('DROP VIEW IF EXISTS v_complementos_pendientes');
        DB::statement('DROP VIEW IF EXISTS v_contratos_resumen');
        DB::statement('DROP VIEW IF EXISTS v_facturas');
    }
};
