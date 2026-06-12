<?php

namespace App\Enums;

enum MovementType: int
{
    case Negative = 0;
    case Positive = 1;

    public function label(): string
    {
        return match ($this) {
            self::Positive => 'Positivo',
            self::Negative => 'Negativo',
        };
    }
}
