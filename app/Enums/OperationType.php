<?php

namespace App\Enums;

enum OperationType: int
{
    case Deposit = 1;
    case Transfer = 2;
    case Reversal = 3;
    case Withdrawal = 4;

    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'Depósito',
            self::Transfer => 'Transferência',
            self::Reversal => 'Reversão de movimentação',
            self::Withdrawal => 'Saque',
        };
    }
}
