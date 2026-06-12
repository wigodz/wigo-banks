<?php

namespace Tests\Unit\Enums;

use App\Enums\MovementType;
use Tests\TestCase;

class MovementTypeTest extends TestCase
{
    public function test_cases_have_expected_int_values(): void
    {
        $this->assertSame(0, MovementType::Negative->value);
        $this->assertSame(1, MovementType::Positive->value);
    }

    public function test_each_case_has_a_label(): void
    {
        $this->assertSame('Negativo', MovementType::Negative->label());
        $this->assertSame('Positivo', MovementType::Positive->label());
    }
}
