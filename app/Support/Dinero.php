<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use OverflowException;

/** Envoltura de BigDecimal para importes y porcentajes (RN-01, DEC-03). */
final class Dinero
{
    private const MAX = '999999999999.99';

    private const ESCALA_INTERNA = 20;

    private function __construct(private readonly BigDecimal $valor) {}

    /** Acepta '1234.56' y '1,234.56' (RN-01). Lanza OverflowException si supera el máximo. */
    public static function of(mixed $valor): self
    {
        self::rechazarFloat($valor);

        if (is_string($valor)) {
            $valor = str_replace(',', '', $valor);
        }

        $decimal = BigDecimal::of($valor);

        if ($decimal->abs()->isGreaterThan(BigDecimal::of(self::MAX))) {
            throw new OverflowException('El importe excede el máximo de 999,999,999,999.99 (RN-01).');
        }

        return new self($decimal);
    }

    public function plus(mixed $otro): self
    {
        if (! $otro instanceof self) {
            self::rechazarFloat($otro);
        }
        $b = $otro instanceof self ? $otro->valor : BigDecimal::of($otro);

        return new self($this->valor->plus($b));
    }

    public function minus(mixed $otro): self
    {
        if (! $otro instanceof self) {
            self::rechazarFloat($otro);
        }
        $b = $otro instanceof self ? $otro->valor : BigDecimal::of($otro);

        return new self($this->valor->minus($b));
    }

    /** Multiplica sin redondear; usa round() al guardar (RN-01). */
    public function times(mixed $factor): self
    {
        self::rechazarFloat($factor);

        return new self($this->valor->multipliedBy($factor));
    }

    /** Divide con escala interna; usa round() al guardar (RN-01). */
    public function dividedBy(mixed $divisor): self
    {
        self::rechazarFloat($divisor);

        return new self($this->valor->dividedBy($divisor, self::ESCALA_INTERNA, RoundingMode::HalfUp));
    }

    /** PHP convierte float a string antes de llegar a string|int — se usa mixed para interceptarlo (DEC-03). */
    private static function rechazarFloat(mixed $valor): void
    {
        if (is_float($valor)) {
            throw new \InvalidArgumentException('No se aceptan float: use string para evitar imprecisión binaria (DEC-03).');
        }
    }

    /** Redondea a 2 decimales medio hacia arriba, igual que ROUND de MySQL (RN-01). */
    public function round(): self
    {
        return new self($this->valor->toScale(2, RoundingMode::HalfUp));
    }

    public function isGreaterThan(self $otro): bool
    {
        return $this->valor->isGreaterThan($otro->valor);
    }

    public function isLessThan(self $otro): bool
    {
        return $this->valor->isLessThan($otro->valor);
    }

    public function isEqualTo(self $otro): bool
    {
        return $this->valor->isEqualTo($otro->valor);
    }

    public function toBigDecimal(): BigDecimal
    {
        return $this->valor;
    }

    public function __toString(): string
    {
        return (string) $this->valor;
    }
}
