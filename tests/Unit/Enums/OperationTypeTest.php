<?php

namespace Tests\Unit\Enums;

use App\Enums\OperationType;
use Tests\TestCase;

class OperationTypeTest extends TestCase
{
    public function test_cases_have_expected_int_values(): void
    {
        $this->assertSame(1, OperationType::Deposit->value);
        $this->assertSame(2, OperationType::Transfer->value);
        $this->assertSame(3, OperationType::Reversal->value);
        $this->assertSame(4, OperationType::Withdrawal->value);
    }

    public function test_each_case_has_a_label(): void
    {
        $this->assertSame('Depósito', OperationType::Deposit->label());
        $this->assertSame('Transferência', OperationType::Transfer->label());
        $this->assertSame('Reversão de movimentação', OperationType::Reversal->label());
        $this->assertSame('Saque', OperationType::Withdrawal->label());
    }
}
