<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/*
 * GAD-31 · Definición de hecho 8 (TDD sección 18, RIE-07).
 *
 * Crea una base de referencia (<base de pruebas>_ref) con docs/gadit_schema.sql y la compara con la base
 * de pruebas, que ya tiene las migraciones. Si alguien cambia una migración y no el SQL (o al revés), falla.
 * Compara tablas, columnas, índices, llaves foráneas, restricciones CHECK y vistas.
 * Se omiten las tablas propias de Laravel, que el SQL no define.
 */

/** Nombre de una conexión extra al mismo servidor, con otra base (o ninguna). */
function equivalenciaConexion(string $nombre, ?string $base): string
{
    config(["database.connections.{$nombre}" => array_merge(
        config('database.connections.'.config('database.default')),
        ['database' => $base],
    )]);
    DB::purge($nombre);

    return $nombre;
}

/**
 * Sentencias de gadit_schema.sql, una por elemento, sin comentarios y sin CREATE DATABASE ni USE.
 * Recorre el texto carácter por carácter para no tocar los «--» ni los «;» que estén dentro de comillas.
 */
function equivalenciaSentencias(): array
{
    $sql = (string) file_get_contents(base_path('docs/gadit_schema.sql'));
    $largo = strlen($sql);
    $sentencias = [];
    $actual = '';
    $comilla = null;

    for ($i = 0; $i < $largo; $i++) {
        $c = $sql[$i];
        $siguiente = $sql[$i + 1] ?? '';

        if ($comilla !== null) {
            $actual .= $c;
            if ($c === '\\' && $comilla !== '`') {
                $actual .= $siguiente;
                $i++;
            } elseif ($c === $comilla) {
                if ($siguiente === $comilla) {
                    $actual .= $siguiente;
                    $i++;
                } else {
                    $comilla = null;
                }
            }

            continue;
        }

        if ($c === '-' && $siguiente === '-' && ctype_space($sql[$i + 2] ?? "\n")) {
            while ($i < $largo && $sql[$i] !== "\n") {
                $i++;
            }
            $actual .= "\n";
        } elseif ($c === '/' && $siguiente === '*' && ($sql[$i + 2] ?? '') !== '!') {
            $fin = strpos($sql, '*/', $i + 2);
            $i = $fin === false ? $largo : $fin + 1;
            $actual .= ' ';
        } elseif ($c === "'" || $c === '"' || $c === '`') {
            $comilla = $c;
            $actual .= $c;
        } elseif ($c === ';') {
            $sentencias[] = trim($actual);
            $actual = '';
        } else {
            $actual .= $c;
        }
    }
    $sentencias[] = trim($actual);

    return array_values(array_filter(
        $sentencias,
        fn (string $s): bool => $s !== '' && ! preg_match('~^(CREATE\s+DATABASE|USE)\b~i', $s),
    ));
}

function equivalenciaNormaliza(?string $texto, string $base): string
{
    $texto = str_replace(["`{$base}`.", '`'], '', (string) $texto);

    return trim(preg_replace('/\s+/', ' ', $texto));
}

/** Lo que hay en una base, por sección: cada una es un arreglo clave => descripción. */
function equivalenciaCatalogo(string $base): array
{
    $c = DB::connection('equiv_servidor');
    $laravel = ['migrations', 'cache', 'cache_locks', 'sessions', 'password_reset_tokens', 'jobs', 'job_batches', 'failed_jobs'];
    $propia = fn (string $tabla): bool => ! in_array($tabla, $laravel, true);

    $tablas = [];
    foreach ($c->select("SELECT TABLE_NAME AS t FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'", [$base]) as $f) {
        if ($propia($f->t)) {
            $tablas[$f->t] = $f->t;
        }
    }

    $columnas = [];
    foreach ($c->select('SELECT TABLE_NAME AS t, COLUMN_NAME AS c, COLUMN_TYPE AS tipo, IS_NULLABLE AS nulo, COLUMN_DEFAULT AS def, EXTRA AS extra, GENERATION_EXPRESSION AS gen, COLLATION_NAME AS colacion FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ?', [$base]) as $f) {
        if (isset($tablas[$f->t])) {
            $columnas["{$f->t}.{$f->c}"] = sprintf(
                '%s | nulo=%s | defecto=%s | extra=%s | generada=%s | colación=%s',
                $f->tipo, $f->nulo, $f->def ?? 'NULL', $f->extra, equivalenciaNormaliza($f->gen, $base) ?: '-', $f->colacion ?? '-',
            );
        }
    }

    $indices = [];
    foreach ($c->select('SELECT TABLE_NAME AS t, INDEX_NAME AS i, NON_UNIQUE AS nu, SEQ_IN_INDEX AS s, COLUMN_NAME AS c, SUB_PART AS sub, INDEX_TYPE AS tipo FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX', [$base]) as $f) {
        if (isset($tablas[$f->t])) {
            $clave = "{$f->t}.{$f->i}";
            $indices[$clave] ??= ($f->nu ? 'no único' : 'único')." | {$f->tipo} | columnas:";
            $indices[$clave] .= ' '.$f->c.($f->sub ? "({$f->sub})" : '');
        }
    }

    $llaves = [];
    $sqlLlaves = 'SELECT k.TABLE_NAME AS t, k.CONSTRAINT_NAME AS n, k.COLUMN_NAME AS c, k.REFERENCED_TABLE_NAME AS rt, k.REFERENCED_COLUMN_NAME AS rc, r.UPDATE_RULE AS ur, r.DELETE_RULE AS dr '
        .'FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r '
        .'ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND r.TABLE_NAME = k.TABLE_NAME '
        .'WHERE k.TABLE_SCHEMA = ? AND k.REFERENCED_TABLE_NAME IS NOT NULL ORDER BY k.TABLE_NAME, k.CONSTRAINT_NAME, k.ORDINAL_POSITION';
    foreach ($c->select($sqlLlaves, [$base]) as $f) {
        if (isset($tablas[$f->t])) {
            $clave = "{$f->t}.{$f->n}";
            $llaves[$clave] ??= "update={$f->ur} | delete={$f->dr} | {$f->rt}:";
            $llaves[$clave] .= " {$f->c}->{$f->rc}";
        }
    }

    $checks = [];
    $sqlChecks = 'SELECT tc.TABLE_NAME AS t, tc.CONSTRAINT_NAME AS n, cc.CHECK_CLAUSE AS clausula '
        .'FROM information_schema.TABLE_CONSTRAINTS tc JOIN information_schema.CHECK_CONSTRAINTS cc '
        .'ON cc.CONSTRAINT_SCHEMA = tc.CONSTRAINT_SCHEMA AND cc.CONSTRAINT_NAME = tc.CONSTRAINT_NAME '
        ."WHERE tc.TABLE_SCHEMA = ? AND tc.CONSTRAINT_TYPE = 'CHECK'";
    foreach ($c->select($sqlChecks, [$base]) as $f) {
        if (isset($tablas[$f->t])) {
            $checks["{$f->t}.{$f->n}"] = equivalenciaNormaliza($f->clausula, $base);
        }
    }

    $vistas = [];
    foreach ($c->select('SELECT TABLE_NAME AS v, VIEW_DEFINITION AS d FROM information_schema.VIEWS WHERE TABLE_SCHEMA = ?', [$base]) as $f) {
        $vistas[$f->v] = equivalenciaNormaliza($f->d, $base);
    }

    return compact('tablas', 'columnas', 'indices', 'llaves', 'checks', 'vistas');
}

/** Crea la base de referencia con el SQL y devuelve los catálogos de las dos bases (una sola vez por corrida). */
function equivalenciaCatalogos(): array
{
    static $catalogos = null;
    if ($catalogos !== null) {
        return $catalogos;
    }

    $migraciones = config('database.connections.'.config('database.default').'.database');
    if (! str_contains((string) $migraciones, 'test')) {
        throw new RuntimeException("La prueba de equivalencia solo corre contra una base de pruebas, no contra «{$migraciones}».");
    }
    $referencia = $migraciones.'_ref';

    equivalenciaConexion('equiv_servidor', null);
    DB::connection('equiv_servidor')->unprepared("DROP DATABASE IF EXISTS `{$referencia}`");
    DB::connection('equiv_servidor')->unprepared("CREATE DATABASE `{$referencia}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    equivalenciaConexion('equiv_referencia', $referencia);
    foreach (equivalenciaSentencias() as $sentencia) {
        DB::connection('equiv_referencia')->unprepared($sentencia);
    }

    $catalogos = [
        'sql' => equivalenciaCatalogo($referencia),
        'migraciones' => equivalenciaCatalogo($migraciones),
    ];

    DB::connection('equiv_servidor')->unprepared("DROP DATABASE IF EXISTS `{$referencia}`");

    return $catalogos;
}

function equivalenciaDiferencias(array $sql, array $migraciones): array
{
    $diferencias = [];
    foreach (array_diff_key($sql, $migraciones) as $clave => $detalle) {
        $diferencias[] = "Solo en gadit_schema.sql: {$clave} ({$detalle})";
    }
    foreach (array_diff_key($migraciones, $sql) as $clave => $detalle) {
        $diferencias[] = "Solo en las migraciones: {$clave} ({$detalle})";
    }
    foreach (array_intersect_key($sql, $migraciones) as $clave => $detalle) {
        if ($detalle !== $migraciones[$clave]) {
            $diferencias[] = "Distinto en {$clave} → SQL: {$detalle} | migraciones: {$migraciones[$clave]}";
        }
    }

    return $diferencias;
}

it('DH-8: el SQL de referencia define las 21 tablas y las 13 vistas', function () {
    $catalogos = equivalenciaCatalogos();

    expect($catalogos['sql']['tablas'])->toHaveCount(21)
        ->and($catalogos['sql']['vistas'])->toHaveCount(13);
});

it('DH-8: las migraciones y gadit_schema.sql coinciden en :dataset', function (string $seccion) {
    $catalogos = equivalenciaCatalogos();

    expect(equivalenciaDiferencias($catalogos['sql'][$seccion], $catalogos['migraciones'][$seccion]))->toBe([]);
})->with(['tablas', 'columnas', 'indices', 'llaves', 'checks', 'vistas']);
